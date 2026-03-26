<?php

namespace App\Http\Controllers\SuperAdminController;

use App\Enums\Roles;
use App\Http\Controllers\Controller;
use App\Http\Requests\ManageUserStoreRequest;
use App\Http\Requests\ManageUserUpdateRequest;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private readonly UserManagementService $userManagementService)
    {
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $users = $this->userManagementService->getPaginatedUsers($search);

        return view('super-admin.user.index', compact('users', 'search'));
    }

    public function create(): View
    {
        $divisions = $this->userManagementService->getDivisions();
        $roles = $this->userManagementService->getAssignableRoles();
        $roleLabels = $this->userManagementService->getRoleLabels();
        $officeLocations = $this->userManagementService->getOfficeLocations();

        return view('super-admin.user.create', compact('divisions', 'roles', 'roleLabels', 'officeLocations'));
    }

    public function store(ManageUserStoreRequest $request): RedirectResponse
    {
        $this->userManagementService->createUser($request->validated());

        return redirect()
            ->route('super-admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function show(User $user): View
    {
        abort_if($user->hasRole(Roles::SuperAdmin->value), 403);

        $user->load(['roles:id,name', 'division:id,name', 'officeLocation:id,name']);
        $user->role_display = $this->userManagementService->formatRoleDisplay($user);

        return view('super-admin.user.show', compact('user'));
    }

    public function edit(User $user): View
    {
        abort_if($user->hasRole(Roles::SuperAdmin->value), 403);

        $divisions = $this->userManagementService->getDivisions();
        $roles = $this->userManagementService->getAssignableRoles();
        $roleLabels = $this->userManagementService->getRoleLabels();
        $officeLocations = $this->userManagementService->getOfficeLocations();
        $userRoles = $user->roles->pluck('name')->toArray();

        return view(
            'super-admin.user.update',
            compact('user', 'divisions', 'roles', 'roleLabels', 'officeLocations', 'userRoles')
        );
    }

    public function update(ManageUserUpdateRequest $request, User $user): RedirectResponse
    {
        abort_if($user->hasRole(Roles::SuperAdmin->value), 403);

        $validated = $request->validated();
        $updatedUser = $this->userManagementService->updateUser($user, $validated);

        if (
            $this->userManagementService->shouldLogoutAfterRoleUpdate(
                user: $updatedUser,
                updatedRoles: $validated['roles'],
                activeRole: session('active_role')
            )
        ) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }

        return redirect()
            ->route('super-admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_if($user->hasRole(Roles::SuperAdmin->value), 403, 'Cannot delete Super Admin.');

        $this->userManagementService->deleteUser($user);

        return redirect()
            ->route('super-admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
