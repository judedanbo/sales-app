<?php

namespace App\Http\Middleware;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CascadeAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $relationshipChain, string $resourceParameter): Response
    {
        if (! Auth::check()) {
            return $this->unauthorized($request, 'Authentication required.');
        }

        $user = Auth::user();

        // Validate the cascade relationship
        if (! $this->validateCascadeRelationship($request, $user, $relationshipChain, $resourceParameter)) {
            return $this->unauthorized($request, 'Resource access denied: Invalid relationship chain.');
        }

        return $next($request);
    }

    /**
     * Validate the cascade relationship between resources.
     */
    private function validateCascadeRelationship(Request $request, User $user, string $relationshipChain, string $resourceParameter): bool
    {
        $routeParameters = $request->route()->parameters();

        // Parse the relationship chain (e.g., "school.classes")
        $relationships = explode('.', $relationshipChain);

        switch ($relationships[0]) {
            case 'school':
                return $this->validateSchoolCascade($routeParameters, $user, $relationships, $resourceParameter);
            case 'user':
                return $this->validateUserCascade($routeParameters, $user, $relationships, $resourceParameter);
            default:
                return false;
        }
    }

    /**
     * Validate school-related cascade relationships.
     */
    private function validateSchoolCascade(array $routeParameters, User $user, array $relationships, string $resourceParameter): bool
    {
        $schoolId = $routeParameters['school'] ?? null;
        if (! $schoolId) {
            return false;
        }

        $school = School::find($schoolId);
        if (! $school) {
            return false;
        }

        // Check if user has access to the school
        if (! $this->userHasSchoolAccess($user, $school)) {
            return false;
        }

        // Validate nested relationships
        if (count($relationships) > 1) {
            return $this->validateNestedSchoolRelationship($routeParameters, $school, $relationships[1], $resourceParameter);
        }

        return true;
    }

    /**
     * Validate nested school relationships (classes, academic years, etc.).
     */
    private function validateNestedSchoolRelationship(array $routeParameters, School $school, string $nestedRelation, string $resourceParameter): bool
    {
        $resourceId = $routeParameters[$resourceParameter] ?? null;
        if (! $resourceId) {
            return false;
        }

        switch ($nestedRelation) {
            case 'classes':
                return $this->validateSchoolClassRelationship($school, $resourceId);
            case 'academicYears':
            case 'academic_years':
                return $this->validateAcademicYearRelationship($school, $resourceId);
            case 'users':
                return $this->validateSchoolUserRelationship($school, $resourceId);
            default:
                return false;
        }
    }

    /**
     * Validate that a class belongs to the specified school.
     */
    private function validateSchoolClassRelationship(School $school, $classId): bool
    {
        $schoolClass = SchoolClass::find($classId);

        return $schoolClass && $schoolClass->school_id === $school->id;
    }

    /**
     * Validate that an academic year belongs to the specified school.
     */
    private function validateAcademicYearRelationship(School $school, $academicYearId): bool
    {
        $academicYear = AcademicYear::find($academicYearId);

        return $academicYear && $academicYear->school_id === $school->id;
    }

    /**
     * Validate that a user belongs to the specified school.
     */
    private function validateSchoolUserRelationship(School $school, $userId): bool
    {
        $user = User::find($userId);

        return $user && $user->school_id === $school->id;
    }

    /**
     * Validate user-related cascade relationships.
     */
    private function validateUserCascade(array $routeParameters, User $currentUser, array $relationships, string $resourceParameter): bool
    {
        $userId = $routeParameters['user'] ?? null;
        if (! $userId) {
            return false;
        }

        $targetUser = User::find($userId);
        if (! $targetUser) {
            return false;
        }

        // Check if current user has access to target user
        if (! $this->userHasUserAccess($currentUser, $targetUser)) {
            return false;
        }

        // Validate nested relationships if any
        if (count($relationships) > 1) {
            return $this->validateNestedUserRelationship($routeParameters, $targetUser, $relationships[1], $resourceParameter);
        }

        return true;
    }

    /**
     * Validate nested user relationships.
     */
    private function validateNestedUserRelationship(array $routeParameters, User $user, string $nestedRelation, string $resourceParameter): bool
    {
        $resourceId = $routeParameters[$resourceParameter] ?? null;
        if (! $resourceId) {
            return false;
        }

        switch ($nestedRelation) {
            case 'school':
                return $this->validateUserSchoolRelationship($user, $resourceId);
            case 'roles':
                return $this->validateUserRoleRelationship($user, $resourceId);
            default:
                return false;
        }
    }

    /**
     * Validate that a user belongs to a specific school.
     */
    private function validateUserSchoolRelationship(User $user, $schoolId): bool
    {
        return $user->school_id == $schoolId;
    }

    /**
     * Validate user role relationships.
     */
    private function validateUserRoleRelationship(User $user, $roleId): bool
    {
        return $user->roles()->where('id', $roleId)->exists();
    }

    /**
     * Check if user has access to a school.
     */
    private function userHasSchoolAccess(User $user, School $school): bool
    {
        // System users can access all schools
        if ($user->isSystemUser()) {
            return true;
        }

        // School users can only access their own school
        if ($user->isSchoolUser()) {
            return $user->school_id === $school->id;
        }

        return false;
    }

    /**
     * Check if current user has access to target user.
     */
    private function userHasUserAccess(User $currentUser, User $targetUser): bool
    {
        // Users can access themselves
        if ($currentUser->id === $targetUser->id) {
            return true;
        }

        // System users can access all users
        if ($currentUser->isSystemUser()) {
            return true;
        }

        // School users can access other users from the same school
        if ($currentUser->isSchoolUser() && $targetUser->isSchoolUser()) {
            return $currentUser->school_id === $targetUser->school_id;
        }

        return false;
    }

    /**
     * Handle unauthorized access.
     */
    private function unauthorized(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error' => 'Unauthorized',
            ], 403);
        }

        if ($request->inertia()) {
            return inertia('Error', [
                'status' => 403,
                'message' => $message,
            ], 403);
        }

        abort(403, $message);
    }
}
