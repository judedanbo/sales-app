<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = User::with(['school', 'roles']);

        // Apply filters
        if ($request->filled('user_type')) {
            $query->where('user_type', $request->user_type);
        }

        if ($request->filled('school_id')) {
            $query->where('school_id', $request->school_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');

        if (in_array($sortBy, ['name', 'email', 'user_type', 'department', 'is_active', 'last_login_at', 'created_at'])) {
            $query->orderBy($sortBy, in_array($sortDirection, ['asc', 'desc']) ? $sortDirection : 'asc');
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $users = $query->paginate(min($perPage, 100));

        // Get filter options
        $schools = School::select('id', 'school_name')->orderBy('school_name')->get();
        $roles = Role::select('id', 'name')->orderBy('name')->get();
        $userTypes = collect(UserType::cases())->map(function ($type) {
            return [
                'value' => $type->value,
                'label' => $type->label(),
            ];
        });

        // Calculate statistics
        $userTypeStats = [];
        foreach (UserType::cases() as $type) {
            $userTypeStats[$type->value] = [
                'label' => $type->label(),
                'count' => User::where('user_type', $type->value)->count(),
            ];
        }

        // Users by school
        $bySchool = User::whereNotNull('school_id')
            ->with('school:id,school_name')
            ->get()
            ->groupBy('school_id')
            ->map(function ($users, $schoolId) {
                $school = $users->first()->school;

                return [
                    'school_id' => $schoolId,
                    'school_name' => $school ? $school->school_name : 'Unknown School',
                    'count' => $users->count(),
                ];
            })
            ->sortByDesc('count')
            ->values()
            ->take(10); // Top 10 schools by user count

        $statistics = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
            'recent' => User::where('created_at', '>=', now()->subDays(30))->count(),
            'by_type' => $userTypeStats,
            'by_school' => $bySchool,
        ];

        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => $request->only(['user_type', 'school_id', 'is_active', 'role', 'search', 'sort_by', 'sort_direction']),
            'schools' => $schools,
            'roles' => $roles,
            'userTypes' => $userTypes,
            'statistics' => $statistics,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $schools = School::select('id', 'school_name')->where('status', 'active')->orderBy('school_name')->get();
        $userTypes = collect(UserType::cases())->map(function ($type) {
            return [
                'value' => $type->value,
                'label' => $type->label(),
                'description' => $type->description(),
            ];
        });

        return Inertia::render('Users/Create', [
            'schools' => $schools,
            'userTypes' => $userTypes,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['created_by'] = auth()->id();

        $user = User::create($validated);
        $user->assignRoleFromUserType();

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): Response
    {
        $user->load(['school', 'roles', 'permissions', 'schoolOfficial', 'sales' => function ($query) {
            $query->latest()->take(10);
        }]);

        return Inertia::render('Users/Show', [
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): Response
    {
        $user->load(['school', 'roles']);

        $schools = School::select('id', 'school_name')->where('status', 'active')->orderBy('school_name')->get();
        $userTypes = collect(UserType::cases())->map(function ($type) {
            return [
                'value' => $type->value,
                'label' => $type->label(),
                'description' => $type->description(),
            ];
        });

        return Inertia::render('Users/Edit', [
            'user' => $user,
            'schools' => $schools,
            'userTypes' => $userTypes,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $validated['updated_by'] = auth()->id();

        $user->update($validated);

        // Update role if user_type changed
        if (isset($validated['user_type'])) {
            $user->assignRoleFromUserType();
        }

        return redirect()->route('users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Show user roles and permissions management page.
     */
    public function roles(User $user): Response
    {
        $user->load(['roles', 'permissions']);
        $availableRoles = Role::all();

        return Inertia::render('Users/Roles', [
            'user' => $user,
            'availableRoles' => $availableRoles,
        ]);
    }

    /**
     * Assign role to user.
     */
    public function assignRole(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $role = Role::findByName($request->role);
        $user->assignRole($role);

        return back()->with('success', "Role '{$role->name}' assigned to user successfully.");
    }

    /**
     * Remove role from user.
     */
    public function removeRole(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $role = Role::findByName($request->role);
        $user->removeRole($role);

        return back()->with('success', "Role '{$role->name}' removed from user successfully.");
    }

    /**
     * Activate user.
     */
    public function activate(User $user): RedirectResponse
    {
        $user->update([
            'is_active' => true,
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'User activated successfully.');
    }

    /**
     * Deactivate user.
     */
    public function deactivate(User $user): RedirectResponse
    {
        // Prevent deactivating current user
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $user->update([
            'is_active' => false,
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'User deactivated successfully.');
    }

    /**
     * Restore a soft-deleted user.
     */
    public function restore(int $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();

        return redirect()->route('users.show', $user)
            ->with('success', 'User restored successfully.');
    }
}
