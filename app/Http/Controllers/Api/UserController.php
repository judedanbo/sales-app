<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserType;
use App\Http\Controllers\Concerns\AuthorizesResourceOperations;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use AuthorizesResourceOperations;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorizeViewAny(User::class);
        $query = User::with(['school', 'roles', 'permissions']);

        // Apply filters
        if ($request->filled('user_type')) {
            $query->where('user_type', $request->user_type);
        }

        if ($request->filled('school_id')) {
            $query->where('school_id', $request->school_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->boolean('status'));
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', function (Builder $q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');

        if (in_array($sortBy, ['name', 'email', 'user_type', 'department', 'status', 'last_login_at', 'created_at'])) {
            $query->orderBy($sortBy, in_array($sortDirection, ['asc', 'desc']) ? $sortDirection : 'asc');
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $users = $query->paginate(min($perPage, 100));

        return new UserCollection($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorizeCreate(User::class);
        $validated = $request->validated();

        // Hash password if provided
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        // Set created_by to current user
        $validated['created_by'] = auth()->id();

        $user = User::create($validated);

        // Assign role based on user_type
        $user->assignRoleFromUserType();

        // Load relationships for response
        $user->load(['school', 'roles', 'permissions']);

        return response()->json([
            'data' => new UserResource($user),
            'message' => 'User created successfully.',
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): JsonResponse
    {
        $user->load(['school', 'roles', 'permissions', 'schoolOfficial']);

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        // Hash password if provided
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        // Set updated_by to current user
        $validated['updated_by'] = auth()->id();

        $user->update($validated);

        // Update role if user_type changed
        if (isset($validated['user_type'])) {
            $user->assignRoleFromUserType();
        }

        // Load relationships for response
        $user->load(['school', 'roles', 'permissions']);

        return response()->json([
            'data' => new UserResource($user),
            'message' => 'User updated successfully.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): JsonResponse
    {
        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'You cannot delete your own account.',
            ], Response::HTTP_FORBIDDEN);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ], Response::HTTP_NO_CONTENT);
    }

    /**
     * Get users with trashed records.
     */
    public function withTrashed(Request $request)
    {
        $query = User::withTrashed()->with(['school', 'roles', 'permissions']);

        // Apply same filters as index
        if ($request->filled('user_type')) {
            $query->where('user_type', $request->user_type);
        }

        if ($request->filled('school_id')) {
            $query->where('school_id', $request->school_id);
        }

        $perPage = $request->get('per_page', 15);
        $users = $query->paginate(min($perPage, 100));

        return new UserCollection($users);
    }

    /**
     * Get only trashed users.
     */
    public function onlyTrashed(Request $request)
    {
        $query = User::onlyTrashed()->with(['school', 'roles', 'permissions']);

        $perPage = $request->get('per_page', 15);
        $users = $query->paginate(min($perPage, 100));

        return new UserCollection($users);
    }

    /**
     * Restore a soft-deleted user.
     */
    public function restore(int $id): JsonResponse
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();

        return response()->json([
            'data' => new UserResource($user->load(['school', 'roles', 'permissions'])),
            'message' => 'User restored successfully.',
        ]);
    }

    /**
     * Permanently delete a user.
     */
    public function forceDelete(int $id): JsonResponse
    {
        $user = User::withTrashed()->findOrFail($id);

        // Prevent force deletion of current user
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'You cannot permanently delete your own account.',
            ], Response::HTTP_FORBIDDEN);
        }

        $user->forceDelete();

        return response()->json([
            'message' => 'User permanently deleted.',
        ], Response::HTTP_NO_CONTENT);
    }

    /**
     * Bulk update user status (activate/deactivate).
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
            'status' => 'required|max:10',
        ]);

        $userIds = $request->user_ids;
        $isActive = $request->boolean('is_active');

        // Prevent deactivating current user
        if (! $isActive && in_array(auth()->id(), $userIds)) {
            return response()->json([
                'message' => 'You cannot deactivate your own account.',
            ], Response::HTTP_FORBIDDEN);
        }

        $updated = User::whereIn('id', $userIds)->update([
            'is_active' => $isActive,
            'updated_by' => auth()->id(),
        ]);

        $status = $isActive ? 'activated' : 'deactivated';

        return response()->json([
            'message' => "{$updated} users {$status} successfully.",
        ]);
    }

    /**
     * Assign role to user.
     */
    public function assignRole(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $role = Role::findByName($request->role);
        $user->assignRole($role);

        $user->load(['school', 'roles', 'permissions']);

        return response()->json([
            'data' => new UserResource($user),
            'message' => "Role '{$role->name}' assigned to user successfully.",
        ]);
    }

    /**
     * Remove role from user.
     */
    public function removeRole(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $role = Role::findByName($request->role);
        $user->removeRole($role);

        $user->load(['school', 'roles', 'permissions']);

        return response()->json([
            'data' => new UserResource($user),
            'message' => "Role '{$role->name}' removed from user successfully.",
        ]);
    }

    /**
     * Get user statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::active()->count(),
            'inactive_users' => User::where('is_active', false)->count(),
            'users_by_type' => [],
            'users_by_school' => User::whereNotNull('school_id')
                ->with('school:id,school_name')
                ->get()
                ->groupBy('school.school_name')
                ->map(function ($users) {
                    return $users->count();
                }),
        ];

        // Get user counts by type
        foreach (UserType::cases() as $type) {
            $stats['users_by_type'][$type->value] = [
                'label' => $type->label(),
                'count' => User::where('user_type', $type)->count(),
            ];
        }

        return response()->json($stats);
    }
}
