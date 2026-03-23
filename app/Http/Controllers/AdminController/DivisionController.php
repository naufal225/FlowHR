<?php

namespace App\Http\Controllers\AdminController;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\User;
use App\Enums\Roles;
use Illuminate\Http\Request;
use PhpParser\Node\Scalar\MagicConst\Dir;

class DivisionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;
        $divisions = Division::where('name', 'like', '%' . $search . '%')->latest()->paginate(10);
        return view('admin.division.index', compact('divisions'));
    }

    public function show(Division $division) {

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
        $approvers = User::whereHas('roles', fn($q) => $q->where('name', Roles::Approver->value))
            ->where('division_id', $division->id)
            ->get();
        return view('admin.division.update', compact(['division', 'approvers']));
    }

    public function update(Request $request, Division $division)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:divisions,name,'.$division->id.',id',
            'leader_id' => 'exists:users,id'
        ]);

        $division->update([
            'name' => $validated['name'],
            'leader_id' => $validated['leader_id']
        ]);

        return redirect()->route('admin.divisions.index')->with('success', 'Successfully update division.');
    }

    public function destroy()
    {

    }
}
