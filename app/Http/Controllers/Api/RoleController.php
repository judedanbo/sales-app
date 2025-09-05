<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleCollection;
use App\Http\Resources\RoleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
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

        return new RoleCollection($roles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $role = Role::create($validated);

        // Assign permissions if provided
        if (isset($validated['permissions'])) {
            $permissions = Permission::whereIn('name', $validated['permissions'])->get();
            $role->syncPermissions($permissions);
        }

        // Load relationships for response
        $role->load(['permissions', 'users']);

        return response()->json([
            'data' => new RoleResource($role),
            'message' => 'Role created successfully.',
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role): JsonResponse
    {
        $role->load(['permissions', 'users']);

        return response()->json([
            'data' => new RoleResource($role),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $validated = $request->validated();

        $role->update($validated);

        // Update permissions if provided
        if (isset($validated['permissions'])) {
            $permissions = Permission::whereIn('name', $validated['permissions'])->get();
            $role->syncPermissions($permissions);
        }

        // Load relationships for response
        $role->load(['permissions', 'users']);

        return response()->json([
            'data' => new RoleResource($role),
            'message' => 'Role updated successfully.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role): JsonResponse
    {
        // Check if role has users assigned
        if ($role->users()->exists()) {
            return response()->json([
                'message' => 'Cannot delete role that has users assigned to it.',
            ], Response::HTTP_CONFLICT);
        }

        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully.',
        ], Response::HTTP_NO_CONTENT);
    }

    /**
     * Assign permission to role.
     */
    public function assignPermission(Request $request, Role $role): JsonResponse
    {
        $request->validate([
            'permission' => 'required|string|exists:permissions,name',
        ]);

        $permission = Permission::findByName($request->permission);
        $role->givePermissionTo($permission);

        $role->load(['permissions', 'users']);

        return response()->json([
            'data' => new RoleResource($role),
            'message' => "Permission '{$permission->name}' assigned to role successfully.",
        ]);
    }

    /**
     * Remove permission from role.
     */
    public function removePermission(Request $request, Role $role): JsonResponse
    {
        $request->validate([
            'permission' => 'required|string|exists:permissions,name',
        ]);

        $permission = Permission::findByName($request->permission);
        $role->revokePermissionTo($permission);

        $role->load(['permissions', 'users']);

        return response()->json([
            'data' => new RoleResource($role),
            'message' => "Permission '{$permission->name}' removed from role successfully.",
        ]);
    }

    /**
     * Sync permissions to role.
     */
    public function syncPermissions(Request $request, Role $role): JsonResponse
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $permissions = Permission::whereIn('name', $request->permissions)->get();
        $role->syncPermissions($permissions);

        $role->load(['permissions', 'users']);

        return response()->json([
            'data' => new RoleResource($role),
            'message' => 'Role permissions updated successfully.',
        ]);
    }

    /**
     * Assign users to role.
     */
    public function assignUsers(Request $request, Role $role): JsonResponse
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

        $role->load(['permissions', 'users']);

        $count = count($userIds);

        return response()->json([
            'data' => new RoleResource($role),
            'message' => "{$count} users assigned to role successfully.",
        ]);
    }

    /**
     * Remove users from role.
     */
    public function removeUsers(Request $request, Role $role): JsonResponse
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

        $role->load(['permissions', 'users']);

        $count = count($userIds);

        return response()->json([
            'data' => new RoleResource($role),
            'message' => "{$count} users removed from role successfully.",
        ]);
    }

    /**
     * Get role statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_roles' => Role::count(),
            'roles_with_users' => Role::has('users')->count(),
            'roles_without_users' => Role::doesntHave('users')->count(),
            'total_permissions' => Permission::count(),
            'roles_by_guard' => Role::groupBy('guard_name')
                ->selectRaw('guard_name, count(*) as count')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->guard_name => $item->count];
                }),
            'popular_roles' => Role::withCount('users')
                ->orderByDesc('users_count')
                ->limit(5)
                ->get()
                ->map(function ($role) {
                    return [
                        'name' => $role->name,
                        'users_count' => $role->users_count,
                    ];
                }),
        ];

        return response()->json($stats);
    }
}
