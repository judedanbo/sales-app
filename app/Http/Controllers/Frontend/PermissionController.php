<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = Permission::with(['roles']);

        // Apply filters
        if ($request->filled('guard_name')) {
            $query->where('guard_name', $request->guard_name);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('category')) {
            $category = $request->category;
            $query->where('name', 'like', "{$category}_%");
        }

        if ($request->filled('has_roles')) {
            if ($request->boolean('has_roles')) {
                $query->has('roles');
            } else {
                $query->doesntHave('roles');
            }
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');

        if (in_array($sortBy, ['name', 'guard_name', 'created_at', 'updated_at'])) {
            $query->orderBy($sortBy, in_array($sortDirection, ['asc', 'desc']) ? $sortDirection : 'asc');
        }

        // Pagination
        $perPage = $request->get('per_page', 50); // Higher default for permissions
        $permissions = $query->paginate(min($perPage, 200));

        // Transform permissions for frontend
        $permissions->getCollection()->transform(function ($permission) {
            $permission->display_name = ucwords(str_replace('_', ' ', $permission->name));
            $permission->category = $this->getCategory($permission->name);
            $permission->category_display = ucfirst($permission->category);
            $permission->roles_count = $permission->roles->count();

            return $permission;
        });

        // Get categories for filter
        $categories = Permission::get()->map(function ($permission) {
            $parts = explode('_', $permission->name);

            return $parts[0] ?? 'other';
        })->unique()->sort()->values()->map(function ($category) {
            return [
                'value' => $category,
                'label' => ucfirst($category),
            ];
        });

        // Calculate statistics
        $totalPermissions = Permission::count();
        $withRoles = Permission::has('roles')->count();

        $permissionsByCategory = Permission::get()->groupBy(function ($permission) {
            $parts = explode('_', $permission->name);

            return $parts[0] ?? 'other';
        })->map(function ($permissions, $category) use ($totalPermissions) {
            $count = $permissions->count();

            return [
                'category' => $category,
                'label' => ucfirst($category),
                'count' => $count,
                'percentage' => $totalPermissions > 0 ? round(($count / $totalPermissions) * 100, 1) : 0,
            ];
        })->sortByDesc('count')->values();

        // Most used permissions
        $mostUsedPermissions = Permission::withCount('roles')
            ->orderByDesc('roles_count')
            ->limit(5)
            ->get()
            ->map(function ($permission) {
                return [
                    'name' => $permission->name,
                    'display_name' => ucwords(str_replace('_', ' ', $permission->name)),
                    'roles_count' => $permission->roles_count,
                    'category' => $this->getCategory($permission->name),
                ];
            });

        $statistics = [
            'total' => $totalPermissions,
            'with_roles' => $withRoles,
            'without_roles' => Permission::doesntHave('roles')->count(),
            'categories' => $categories->count(),
            'usage_percentage' => $totalPermissions > 0 ? round(($withRoles / $totalPermissions) * 100, 1) : 0,
            'by_category' => $permissionsByCategory,
            'most_used' => $mostUsedPermissions,
        ];

        return Inertia::render('Permissions/Index', [
            'permissions' => $permissions,
            'filters' => $request->only(['guard_name', 'search', 'category', 'has_roles', 'sort_by', 'sort_direction']),
            'categories' => $categories,
            'statistics' => $statistics,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Permission $permission): Response
    {
        $permission->load(['roles.users' => function ($query) {
            $query->with('school:id,school_name')->latest()->take(20);
        }]);

        $permission->display_name = ucwords(str_replace('_', $permission->name));
        $permission->category = $this->getCategory($permission->name);
        $permission->category_display = ucfirst($permission->category);
        $permission->roles_count = $permission->roles->count();
        $permission->users_count = $permission->roles->sum(function ($role) {
            return $role->users->count();
        });

        return Inertia::render('Permissions/Show', [
            'permission' => $permission,
        ]);
    }

    /**
     * Show permissions grouped by category.
     */
    public function grouped(Request $request): Response
    {
        $query = Permission::with(['roles']);

        // Apply filters
        if ($request->filled('guard_name')) {
            $query->where('guard_name', $request->guard_name);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $permissions = $query->orderBy('name')->get();

        // Group permissions by category
        $grouped = $permissions->groupBy(function ($permission) {
            $parts = explode('_', $permission->name);

            return $parts[0] ?? 'other';
        })->map(function ($permissions, $category) {
            return [
                'category' => $category,
                'display_name' => ucfirst($category),
                'permissions' => $permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'display_name' => ucwords(str_replace('_', ' ', $permission->name)),
                        'roles_count' => $permission->roles->count(),
                        'guard_name' => $permission->guard_name,
                    ];
                }),
                'count' => $permissions->count(),
            ];
        })->sortBy('category')->values();

        return Inertia::render('Permissions/Grouped', [
            'permissionGroups' => $grouped,
            'filters' => $request->only(['guard_name', 'search']),
            'totalPermissions' => $permissions->count(),
            'totalCategories' => $grouped->count(),
        ]);
    }

    /**
     * Show permissions by role.
     */
    public function byRole(Role $role): Response
    {
        $role->load(['permissions']);
        $role->display_name = ucwords(str_replace('_', ' ', $role->name));

        $grouped = $role->permissions->groupBy(function ($permission) {
            $parts = explode('_', $permission->name);

            return $parts[0] ?? 'other';
        })->map(function ($permissions, $category) {
            return [
                'category' => $category,
                'display_name' => ucfirst($category),
                'permissions' => $permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'display_name' => ucwords(str_replace('_', ' ', $permission->name)),
                        'guard_name' => $permission->guard_name,
                    ];
                }),
                'count' => $permissions->count(),
            ];
        })->sortBy('category')->values();

        return Inertia::render('Permissions/ByRole', [
            'role' => $role,
            'permissionGroups' => $grouped,
            'totalPermissions' => $role->permissions->count(),
        ]);
    }

    /**
     * Show permissions by user.
     */
    public function byUser(\App\Models\User $user): Response
    {
        $user->load(['roles', 'permissions']);

        $allPermissions = $user->getAllPermissions();
        $directPermissions = $user->permissions;
        $rolePermissions = $user->getPermissionsViaRoles();

        $grouped = $allPermissions->groupBy(function ($permission) {
            $parts = explode('_', $permission->name);

            return $parts[0] ?? 'other';
        })->map(function ($permissions, $category) use ($directPermissions, $rolePermissions) {
            return [
                'category' => $category,
                'display_name' => ucfirst($category),
                'permissions' => $permissions->map(function ($permission) use ($directPermissions, $rolePermissions) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'display_name' => ucwords(str_replace('_', ' ', $permission->name)),
                        'guard_name' => $permission->guard_name,
                        'source' => $directPermissions->contains('name', $permission->name)
                            ? 'direct'
                            : ($rolePermissions->contains('name', $permission->name) ? 'role' : 'unknown'),
                    ];
                }),
                'count' => $permissions->count(),
            ];
        })->sortBy('category')->values();

        return Inertia::render('Permissions/ByUser', [
            'user' => $user,
            'permissionGroups' => $grouped,
            'totalPermissions' => $allPermissions->count(),
            'directPermissions' => $directPermissions->count(),
            'rolePermissions' => $rolePermissions->count(),
        ]);
    }

    /**
     * Show permission statistics dashboard.
     */
    public function statistics(): Response
    {
        // Basic stats
        $totalPermissions = Permission::count();
        $permissionsWithRoles = Permission::has('roles')->count();
        $permissionsWithoutRoles = Permission::doesntHave('roles')->count();
        $totalRoles = Role::count();

        // Permissions by category
        $permissionsByCategory = Permission::get()->groupBy(function ($permission) {
            $parts = explode('_', $permission->name);

            return $parts[0] ?? 'other';
        })->map(function ($permissions, $category) {
            return [
                'category' => $category,
                'display_name' => ucfirst($category),
                'count' => $permissions->count(),
                'percentage' => round(($permissions->count() / Permission::count()) * 100, 1),
            ];
        })->sortByDesc('count')->values();

        // Most used permissions
        $mostUsedPermissions = Permission::withCount('roles')
            ->orderByDesc('roles_count')
            ->limit(10)
            ->get()
            ->map(function ($permission) {
                return [
                    'name' => $permission->name,
                    'display_name' => ucwords(str_replace('_', ' ', $permission->name)),
                    'roles_count' => $permission->roles_count,
                    'category' => $this->getCategory($permission->name),
                ];
            });

        // Unused permissions
        $unusedPermissions = Permission::doesntHave('roles')
            ->orderBy('name')
            ->get()
            ->map(function ($permission) {
                return [
                    'name' => $permission->name,
                    'display_name' => ucwords(str_replace('_', ' ', $permission->name)),
                    'category' => $this->getCategory($permission->name),
                ];
            });

        return Inertia::render('Permissions/Statistics', [
            'stats' => [
                'total_permissions' => $totalPermissions,
                'permissions_with_roles' => $permissionsWithRoles,
                'permissions_without_roles' => $permissionsWithoutRoles,
                'total_roles' => $totalRoles,
                'usage_percentage' => $totalPermissions > 0 ? round(($permissionsWithRoles / $totalPermissions) * 100, 1) : 0,
            ],
            'permissionsByCategory' => $permissionsByCategory,
            'mostUsedPermissions' => $mostUsedPermissions,
            'unusedPermissions' => $unusedPermissions->take(20), // Limit for display
        ]);
    }

    /**
     * Get category from permission name.
     */
    private function getCategory(string $name): string
    {
        $parts = explode('_', $name);

        return $parts[0] ?? 'other';
    }
}
