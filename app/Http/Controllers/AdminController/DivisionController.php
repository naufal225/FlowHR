<?php

namespace App\Http\Controllers\AdminController;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Role;
use App\Models\User;
use App\Enums\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class DivisionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;
        $divisions = Division::where('name', 'like', '%' . $search . '%')->latest()->paginate(10);
        return view('admin.division.index', compact('divisions'));
    }

    public function show(Division $division): RedirectResponse
    {
        return redirect()->route('admin.divisions.edit', $division);
    }

    public function create()
    {
        return view('admin.division.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:divisions,id'
        ]);

        Division::create([
            'name' => $request->name
        ]);

        return redirect()->route('admin.divisions.index')->with('success', 'Successfully create division.');
    }

    public function edit(Division $division)
    {
        $employees = User::whereHas('roles', fn($q) => $q->where('name', Roles::Employee->value))
            ->where('division_id', $division->id)
            ->get();

        return view('admin.division.update', compact(['division', 'employees']));
    }

    public function update(Request $request, Division $division)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:divisions,name,'.$division->id.',id',
            'leader_id' => 'required|exists:users,id'
        ]);

        $leader = User::query()
            ->whereKey($validated['leader_id'])
            ->where('division_id', $division->id)
            ->whereHas('roles', fn($query) => $query->where('name', Roles::Employee->value))
            ->first();

        if (! $leader) {
            return back()
                ->withErrors(['leader_id' => 'Leader must be an employee in this division.'])
                ->withInput();
        }

        DB::transaction(function () use ($division, $validated, $leader): void {
            $approverRoleId = Role::query()
                ->where('name', Roles::Approver->value)
                ->value('id');

            if ($approverRoleId) {
                DB::table('role_user')->updateOrInsert(
                    [
                        'user_id' => $leader->id,
                        'role_id' => $approverRoleId,
                    ],
                    [
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            $division->update([
                'name' => $validated['name'],
                'leader_id' => $leader->id,
            ]);
        });

        return redirect()->route('admin.divisions.index')->with('success', 'Successfully update division.');
    }

    public function destroy(Division $division): RedirectResponse
    {
        try {
            $divisionName = $division->name;

            DB::transaction(function () use ($division): void {
                $approverRoleId = Role::query()
                    ->where('name', Roles::Approver->value)
                    ->value('id');

                if ($approverRoleId) {
                    $userIdsInDivision = User::query()
                        ->where(function ($query) use ($division) {
                            $query->where('division_id', $division->id);

                            if (!empty($division->leader_id)) {
                                $query->orWhere('id', $division->leader_id);
                            }
                        })
                        ->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', Roles::Approver->value))
                        ->pluck('id');

                    if ($userIdsInDivision->isNotEmpty()) {
                        DB::table('role_user')
                            ->where('role_id', $approverRoleId)
                            ->whereIn('user_id', $userIdsInDivision)
                            ->delete();
                    }
                }

                $division->delete();
            });

            return redirect()
                ->route('admin.divisions.index')
                ->with('success', "Successfully deleted division {$divisionName}.");
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('admin.divisions.index')
                ->with('error', 'Failed to delete division. Please try again.');
        }
    }
}
