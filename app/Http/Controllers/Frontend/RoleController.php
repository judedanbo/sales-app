<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = Role::with(['permissions', 'users']);

        // Apply filters
        if ($request->filled('guard_name')) {
            $query->where('guard_name', $request->guard_name);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('has_users')) {
            if ($request->boolean('has_users')) {
                $query->has('users');
            } else {
                $query->doesntHave('users');
            }
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');

        if (in_array($sortBy, ['name', 'guard_name', 'created_at', 'updated_at'])) {
            $query->orderBy($sortBy, in_array($sortDirection, ['asc', 'desc']) ? $sortDirection : 'asc');
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $roles = $query->paginate(min($perPage, 100));

        // Transform roles for frontend
        $roles->getCollection()->transform(function ($role) {
            $role->display_name = ucwords(str_replace('_', ' ', $role->name));
            $role->users_count = $role->users->count();
            $role->permissions_count = $role->permissions->count();

            return $role;
        });

        // Popular roles (most users assigned)
        $popularRoles = Role::withCount('users')
            ->orderByDesc('users_count')
            ->limit(5)
            ->get()
            ->map(function ($role) {
                return [
                    'name' => $role->name,
                    'display_name' => ucwords(str_replace('_', ' ', $role->name)),
                    'users_count' => $role->users_count,
                    'guard_name' => $role->guard_name,
                ];
            });

        // Roles by guard
        $byGuard = Role::select('guard_name')
            ->selectRaw('count(*) as count')
            ->groupBy('guard_name')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->guard_name => $item->count];
            });

        // Get all permissions grouped by category
        $allPermissions = Permission::orderBy('name')->get();
        $permissionGroups = $allPermissions->groupBy(function ($permission) {
            $parts = explode('_', $permission->name);

            return $parts[0] ?? 'other';
        })->map(function ($permissions, $category) {
            return [
                'category' => $category,
                'display_name' => ucfirst($category),
                'count' => $permissions->count(),
                'permissions' => $permissions->map(function ($permission) {
                    return [
                        'name' => $permission->name,
                        'display_name' => ucwords(str_replace('_', ' ', $permission->name)),
                        'guard_name' => $permission->guard_name,
                    ];
                })->values(),
            ];
        })->sortByDesc('count')->values();

        // Get all guard names
        $guardNames = Role::select('guard_name')
            ->distinct()
            ->orderBy('guard_name')
            ->pluck('guard_name')
            ->toArray();

        // Calculate statistics
        $statistics = [
            'total' => Role::count(),
            'with_users' => Role::has('users')->count(),
            'without_users' => Role::doesntHave('users')->count(),
            'with_permissions' => Role::has('permissions')->count(),
            'total_permissions' => Permission::count(),
            'recent' => Role::where('created_at', '>=', now()->subDays(30))->count(),
            'popular_roles' => $popularRoles,
            'by_guard' => $byGuard,
        ];

        return Inertia::render('Roles/Index', [
            'roles' => $roles,
            'filters' => $request->only(['guard_name', 'search', 'has_users', 'sort_by', 'sort_direction']),
            'statistics' => $statistics,
            'allPermissions' => $allPermissions,
            'permissionGroups' => $permissionGroups,
            'guardNames' => $guardNames,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $permissions = Permission::orderBy('name')->get()->map(function ($permission) {
            return [
                'name' => $permission->name,
                'display_name' => ucwords(str_replace('_', ' ', $permission->name)),
                'guard_name' => $permission->guard_name,
            ];
        });

        // Group permissions by category
        $permissionGroups = $permissions->groupBy(function ($permission) {
            $parts = explode('_', $permission['name']);

            return $parts[0] ?? 'other';
        });

        return Inertia::render('Roles/Create', [
            'permissions' => $permissions,
            'permissionGroups' => $permissionGroups,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => $validated['guard_name'],
        ]);

        // Assign permissions if provided
        if (isset($validated['permissions'])) {
            $permissions = Permission::whereIn('name', $validated['permissions'])->get();
            $role->syncPermissions($permissions);
        }

        return redirect()->route('roles.index')
            ->with('success', 'Role created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role): Response
    {
        $role->load(['permissions', 'users' => function ($query) {
            $query->with('school:id,school_name')->latest()->take(20);
        }]);

        $role->display_name = ucwords(str_replace('_', ' ', $role->name));
        $role->users_count = $role->users->count();
        $role->permissions_count = $role->permissions->count();

        // Group permissions by category
        $permissionGroups = $role->permissions->groupBy(function ($permission) {
            $parts = explode('_', $permission->name);

            return $parts[0] ?? 'other';
        })->map(function ($permissions, $group) {
            return [
                'group' => ucfirst($group),
                'permissions' => $permissions->map(function ($permission) {
                    return [
                        'name' => $permission->name,
                        'display_name' => ucwords(str_replace('_', ' ', $permission->name)),
                    ];
                }),
            ];
        })->values();

        return Inertia::render('Roles/Show', [
            'role' => $role,
            'permissionGroups' => $permissionGroups,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role): Response
    {
        $role->load(['permissions']);
        $role->display_name = ucwords(str_replace('_', ' ', $role->name));

        $permissions = Permission::orderBy('name')->get()->map(function ($permission) use ($role) {
            return [
                'name' => $permission->name,
                'display_name' => ucwords(str_replace('_', ' ', $permission->name)),
                'guard_name' => $permission->guard_name,
                'assigned' => $role->permissions->contains('name', $permission->name),
            ];
        });

        // Group permissions by category
        $permissionGroups = $permissions->groupBy(function ($permission) {
            $parts = explode('_', $permission['name']);

            return $parts[0] ?? 'other';
        });

        return Inertia::render('Roles/Edit', [
            'role' => $role,
            'permissions' => $permissions,
            'permissionGroups' => $permissionGroups,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $validated = $request->validated();

        $role->update(collect($validated)->only(['name', 'guard_name'])->toArray());

        // Update permissions if provided
        if (isset($validated['permissions'])) {
            $permissions = Permission::whereIn('name', $validated['permissions'])->get();
            $role->syncPermissions($permissions);
        }

        return redirect()->route('roles.show', $role)
            ->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role): RedirectResponse
    {
        // Check if role has users assigned
        if ($role->users()->exists()) {
            return redirect()->route('roles.index')
                ->with('error', 'Cannot delete role that has users assigned to it.');
        }

        $role->delete();

        return redirect()->route('roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    /**
     * Show permissions management page for role.
     */
    public function permissions(Role $role): Response
    {
        $role->load(['permissions']);
        $role->display_name = ucwords(str_replace('_', ' ', $role->name));

        $allPermissions = Permission::orderBy('name')->get();
        $permissionGroups = $allPermissions->groupBy(function ($permission) {
            $parts = explode('_', $permission->name);

            return $parts[0] ?? 'other';
        })->map(function ($permissions, $group) use ($role) {
            return [
                'group' => ucfirst($group),
                'permissions' => $permissions->map(function ($permission) use ($role) {
                    return [
                        'name' => $permission->name,
                        'display_name' => ucwords(str_replace('_', ' ', $permission->name)),
                        'assigned' => $role->permissions->contains('name', $permission->name),
                    ];
                }),
            ];
        })->values();

        return Inertia::render('Roles/Permissions', [
            'role' => $role,
            'permissionGroups' => $permissionGroups,
        ]);
    }

    /**
     * Sync permissions for role.
     */
    public function syncPermissions(Request $request, Role $role): RedirectResponse
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $permissions = Permission::whereIn('name', $request->permissions)->get();
        $role->syncPermissions($permissions);

        return back()->with('success', 'Role permissions updated successfully.');
    }

    /**
     * Show users assigned to role.
     */
    public function users(Role $role): Response
    {
        $role->load(['users.school']);
        $role->display_name = ucwords(str_replace('_', ' ', $role->name));

        return Inertia::render('Roles/Users', [
            'role' => $role,
        ]);
    }

    /**
     * Assign users to role.
     */
    public function assignUsers(Request $request, Role $role): RedirectResponse
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $userIds = $request->user_ids;

        // Get users and assign role
        $users = \App\Models\User::whereIn('id', $userIds)->get();
        foreach ($users as $user) {
            $user->assignRole($role);
        }

        $count = count($userIds);

        return back()->with('success', "{$count} users assigned to role successfully.");
    }

    /**
     * Remove users from role.
     */
    public function removeUsers(Request $request, Role $role): RedirectResponse
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $userIds = $request->user_ids;

        // Get users and remove role
        $users = \App\Models\User::whereIn('id', $userIds)->get();
        foreach ($users as $user) {
            $user->removeRole($role);
        }

        $count = count($userIds);

        return back()->with('success', "{$count} users removed from role successfully.");
    }

    /**
     * Show all permissions page.
     */
    public function allPermissions(): Response
    {
        $allPermissions = Permission::orderBy('name')->get();
        $permissionGroups = $allPermissions->groupBy(function ($permission) {
            $parts = explode('_', $permission->name);

            return $parts[0] ?? 'other';
        })->map(function ($permissions, $category) {
            return [
                'category' => $category,
                'display_name' => ucfirst($category),
                'count' => $permissions->count(),
                'permissions' => $permissions->map(function ($permission) {
                    return [
                        'name' => $permission->name,
                        'display_name' => ucwords(str_replace('_', ' ', $permission->name)),
                        'guard_name' => $permission->guard_name,
                    ];
                })->values(),
            ];
        })->sortByDesc('count')->values();

        $statistics = [
            'total_permissions' => $allPermissions->count(),
            'total_categories' => $permissionGroups->count(),
            'permissions_with_roles' => Permission::has('roles')->count(),
            'permissions_without_roles' => Permission::doesntHave('roles')->count(),
        ];

        return Inertia::render('Roles/AllPermissions', [
            'allPermissions' => $allPermissions,
            'permissionGroups' => $permissionGroups,
            'statistics' => $statistics,
        ]);
    }
}
