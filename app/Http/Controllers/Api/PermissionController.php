<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionCollection;
use App\Http\Resources\PermissionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
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

        return new PermissionCollection($permissions);
    }

    /**
     * Display the specified resource.
     */
    public function show(Permission $permission): JsonResponse
    {
        $permission->load(['roles.users']);

        return response()->json([
            'data' => new PermissionResource($permission),
        ]);
    }

    /**
     * Get permissions grouped by category.
     */
    public function grouped(Request $request): JsonResponse
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

        // Group permissions by category (first word before underscore)
        $totalPermissions = $permissions->count();
        $grouped = $permissions->groupBy(function ($permission) {
            $parts = explode('_', $permission->name);

            return $parts[0] ?? 'other';
        })->map(function ($permissions, $category) use ($totalPermissions) {
            $count = $permissions->count();

            return [
                'category' => $category,
                'display_name' => ucfirst($category),
                'permissions' => $permissions->map(function ($permission) {
                    return new PermissionResource($permission);
                }),
                'count' => $count,
                'percentage' => $totalPermissions > 0 ? round(($count / $totalPermissions) * 100, 1) : 0,
            ];
        })->sortByDesc('count')->values();

        return response()->json([
            'data' => $grouped,
            'total_permissions' => $permissions->count(),
            'total_categories' => $grouped->count(),
        ]);
    }

    /**
     * Get all available permission categories.
     */
    public function categories(): JsonResponse
    {
        $permissions = Permission::select('name')->get();
        $totalPermissions = Permission::count();

        $categories = $permissions->map(function ($permission) {
            $parts = explode('_', $permission->name);

            return $parts[0] ?? 'other';
        })->unique()->sort()->values()->map(function ($category) use ($totalPermissions) {
            $count = Permission::where('name', 'like', "{$category}_%")->count();

            return [
                'value' => $category,
                'label' => ucfirst($category),
                'count' => $count,
                'percentage' => $totalPermissions > 0 ? round(($count / $totalPermissions) * 100, 1) : 0,
            ];
        })->sortByDesc('count');

        return response()->json([
            'data' => $categories,
        ]);
    }

    /**
     * Get permissions assigned to a specific role.
     */
    public function byRole(Role $role): JsonResponse
    {
        $role->load(['permissions']);

        $grouped = $role->permissions->groupBy(function ($permission) {
            $parts = explode('_', $permission->name);

            return $parts[0] ?? 'other';
        })->map(function ($permissions, $category) {
            return [
                'category' => $category,
                'display_name' => ucfirst($category),
                'permissions' => $permissions->map(function ($permission) {
                    return new PermissionResource($permission);
                }),
                'count' => $permissions->count(),
            ];
        })->values();

        return response()->json([
            'data' => $grouped,
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => ucwords(str_replace('_', ' ', $role->name)),
            ],
            'total_permissions' => $role->permissions->count(),
        ]);
    }

    /**
     * Get permissions assigned to a specific user.
     */
    public function byUser(\App\Models\User $user): JsonResponse
    {
        $allPermissions = $user->getAllPermissions();

        $grouped = $allPermissions->groupBy(function ($permission) {
            $parts = explode('_', $permission->name);

            return $parts[0] ?? 'other';
        })->map(function ($permissions, $category) {
            return [
                'category' => $category,
                'display_name' => ucfirst($category),
                'permissions' => $permissions->map(function ($permission) {
                    return new PermissionResource($permission);
                }),
                'count' => $permissions->count(),
            ];
        })->values();

        return response()->json([
            'data' => $grouped,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'user_type' => $user->user_type,
            ],
            'total_permissions' => $allPermissions->count(),
            'direct_permissions' => $user->permissions->count(),
            'role_permissions' => $user->getPermissionsViaRoles()->count(),
        ]);
    }

    /**
     * Check if user has specific permission.
     */
    public function checkUserPermission(Request $request, \App\Models\User $user): JsonResponse
    {
        $request->validate([
            'permission' => 'required|string|exists:permissions,name',
        ]);

        $permission = $request->permission;
        $hasPermission = $user->can($permission);

        // Get how the permission is granted
        $source = 'none';
        if ($hasPermission) {
            if ($user->permissions->contains('name', $permission)) {
                $source = 'direct';
            } elseif ($user->getPermissionsViaRoles()->contains('name', $permission)) {
                $source = 'role';
            }
        }

        return response()->json([
            'has_permission' => $hasPermission,
            'permission' => $permission,
            'source' => $source,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Get permission statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_permissions' => Permission::count(),
            'permissions_with_roles' => Permission::has('roles')->count(),
            'permissions_without_roles' => Permission::doesntHave('roles')->count(),
            'total_roles' => Role::count(),
            'permissions_by_guard' => Permission::groupBy('guard_name')
                ->selectRaw('guard_name, count(*) as count')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->guard_name => $item->count];
                }),
            'permissions_by_category' => Permission::get()->groupBy(function ($permission) {
                $parts = explode('_', $permission->name);

                return $parts[0] ?? 'other';
            })->map(function ($permissions, $category) {
                $count = $permissions->count();
                $totalPermissions = Permission::count();

                return [
                    'category' => $category,
                    'display_name' => ucfirst($category),
                    'count' => $count,
                    'percentage' => $totalPermissions > 0 ? round(($count / $totalPermissions) * 100, 1) : 0,
                ];
            })->sortByDesc('count')->values(),
            'most_used_permissions' => Permission::withCount('roles')
                ->orderByDesc('roles_count')
                ->limit(10)
                ->get()
                ->map(function ($permission) {
                    return [
                        'name' => $permission->name,
                        'display_name' => ucwords(str_replace('_', ' ', $permission->name)),
                        'roles_count' => $permission->roles_count,
                    ];
                }),
        ];

        return response()->json($stats);
    }
}
