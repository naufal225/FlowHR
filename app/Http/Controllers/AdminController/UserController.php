<?php

namespace App\Http\Controllers\AdminController;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordMail;
use App\Models\Division;
use App\Models\Leave;
use App\Models\User;
use App\Enums\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index()
    {
        $search = request('search');
        $users = User::with('roles') // eager load roles
            ->where('name', 'like', '%' . $search . '%')
            ->whereDoesntHave('roles', function ($query) {
                $query->where('name', Roles::SuperAdmin->value);
            })
            ->latest()
            ->paginate(10);

        // Tambahkan kolom 'role_display' untuk tampilan
        $roleLabels = [
            Roles::SuperAdmin->value => 'Super Admin',
            Roles::Admin->value => 'Admin',
            Roles::Finance->value => 'Approver 3',
            Roles::Manager->value => 'Approver 2',
            Roles::Approver->value => 'Approver 1',
            Roles::Employee->value => 'Employee',
        ];

        $users->getCollection()->transform(function ($user) use ($roleLabels) {
            $user->role_display = $user->roles
                ->pluck('name')
                ->map(fn($name) => $roleLabels[$name] ?? ucfirst($name))
                ->join(', ');
            return $user;
        });

        return view('admin.user.index', compact('users'));
    }

    public function create()
    {
        $divisions = Division::latest()->get();
        $roles = collect(Roles::cases())
            ->filter(fn($role) => $role->value !== Roles::SuperAdmin->value)
            ->sortBy(fn($role) => $role->value !== Roles::Employee->value) // Employee = false = 0, jadi paling depan
            ->values();

        $roleLabels = [
            Roles::Employee->value => 'Employee',
            Roles::Approver->value => 'Approver 1',
            Roles::Manager->value => 'Approver 2',
            Roles::Admin->value => 'Admin',
            Roles::Finance->value => 'Approver 3',
        ];

        return view('admin.user.create', compact('divisions', 'roles', 'roleLabels'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email:dns|unique:users,email',
            'roles' => 'required|array|min:1',
            'roles.*' => 'string|in:' . implode(',', Roles::values()),
            'division_id' => 'nullable|exists:divisions,id'
        ], [
            'name.required' => 'The name field is required.',
            'email.required' => 'The email field is required.',
            'email.unique' => 'This email address is already taken.',
            'roles.required' => 'At least one role is required.',
            'roles.*.in' => 'Invalid role selected.',
        ]);

        // Validasi khusus: jika ada role Approver, division_id wajib
        if (array_intersect($validated['roles'], [Roles::Approver->value, Roles::Manager->value])) {
            if (empty($validated['division_id'])) {
                throw ValidationException::withMessages([
                    'division_id' => 'Division is required when assigning role Approver.'
                ]);
            }

            $division = Division::find($validated['division_id']);
            if (!$division) {
                throw ValidationException::withMessages([
                    'division_id' => 'Division not found.'
                ]);
            }
        }

        // Buat user tanpa field `role`
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt('password'),
            'division_id' => $validated['division_id'] ?? null,
        ]);

        // Simpan roles ke pivot
        $roleIds = \App\Models\Role::whereIn('name', $validated['roles'])->pluck('id');
        $user->roles()->attach($roleIds);

        // Jika Approver dipilih → jadikan leader divisi
        if (array_intersect($validated['roles'], [Roles::Approver->value, Roles::Manager->value])) {
            $division = Division::find($validated['division_id']);
            if ($division) {
                // Copot leader lama
                if ($division->leader_id) {
                    $oldLeader = $division->leader;
                    if ($oldLeader) {
                        // Hapus role Approver dari leader lama, biarkan role lain tetap
                        $approverRoleIds = \App\Models\Role::whereIn('name', [
                            Roles::Approver->value,
                            Roles::Manager->value
                        ])->pluck('id');

                        $oldLeader->roles()->detach($approverRoleIds);
                    }
                }
                $division->update(['leader_id' => $user->id]);
            }
        }

        // Kirim email reset password
        $token = Password::createToken($user);
        $resetUrl = route('password.reset', ['token' => $token, 'email' => $user->email]);
        Mail::to($user->email)->queue(new ResetPasswordMail($user->name, $resetUrl));

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        // Pastikan bukan SuperAdmin
        if ($user->hasRole(Roles::SuperAdmin->value)) {
            abort(403);
        }

        $divisions = Division::latest()->get();
        $roles = collect(Roles::cases())
            ->filter(fn($role) => $role->value !== Roles::SuperAdmin->value)
            ->sortBy(fn($role) => $role->value !== Roles::Employee->value) // Employee = false = 0, jadi paling depan
            ->values();

        $roleLabels = [
            Roles::Employee->value => 'Employee',
            Roles::Approver->value => 'Approver 1',
            Roles::Manager->value => 'Approver 2',
            Roles::Admin->value => 'Admin',
            Roles::Finance->value => 'Approver 3',
        ];

        // Ambil role user saat ini (array of names)
        $userRoles = $user->roles->pluck('name')->toArray();

        return view('admin.user.update', compact('user', 'divisions', 'roles', 'roleLabels', 'userRoles'));
    }

    public function update(Request $request, User $user)
    {
        if ($user->hasRole(Roles::SuperAdmin->value)) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email:dns|unique:users,email,' . $user->id,
            'roles' => 'required|array|min:1',
            'roles.*' => 'string|in:' . implode(',', Roles::values()),
            'division_id' => 'nullable|exists:divisions,id'
        ], [
            'roles.required' => 'At least one role is required.',
            'roles.*.in' => 'Invalid role selected.',
        ]);

        // Validasi Approver + division
        if (array_intersect($validated['roles'], [Roles::Approver->value, Roles::Manager->value])) {
            if (empty($validated['division_id'])) {
                throw ValidationException::withMessages([
                    'division_id' => 'Division is required when assigning role Approver.'
                ]);
            }
        }

        // Jika user adalah leader divisi lama, dan pindah divisi atau kehilangan role Approver → copot
        if ($user->division && $user->division->leader_id == $user->id) {
            if (
                ($validated['division_id'] != $user->division_id) ||
                (!array_intersect($validated['roles'], [Roles::Approver->value, Roles::Manager->value]))
            ) {
                $user->division->update(['leader_id' => null]);
            }
        }

        // Update user
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'division_id' => $validated['division_id'] ?? null,
        ]);

        // Sinkronisasi roles
        $roleIds = \App\Models\Role::whereIn('name', $validated['roles'])->pluck('id');
        $user->roles()->sync($roleIds);

        // Jika Approver dipilih → jadikan leader
        if (array_intersect($validated['roles'], [Roles::Approver->value, Roles::Manager->value])) {
            $division = Division::find($validated['division_id']);
            if ($division) {
                // Copot leader lama
                if ($division->leader_id && $division->leader_id != $user->id) {
                    $oldLeader = $division->leader;
                    if ($oldLeader) {
                        $approverRoleIds = \App\Models\Role::whereIn('name', [
                            Roles::Approver->value,
                            Roles::Manager->value
                        ])->pluck('id');

                        $oldLeader->roles()->detach($approverRoleIds);
                    }
                }
                $division->update(['leader_id' => $user->id]);
            }
        }

        // Jika user mengedit diri sendiri dan role berubah → logout
        if (Auth::id() === $user->id) {
            $currentActiveRole = session('active_role');
            if (!in_array($currentActiveRole, $validated['roles'])) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login');
            }
        }

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->hasRole(Roles::SuperAdmin->value)) {
            abort(403, 'Cannot delete Super Admin.');
        }

        // Jika user adalah leader → copot
        if ($user->division && $user->division->leader_id == $user->id) {
            $user->division->update(['leader_id' => null]);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }
}
