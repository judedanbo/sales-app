<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class OwnerOrPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission, string $parameterName = 'user'): Response
    {
        if (! Auth::check()) {
            return $this->unauthorized($request, 'Authentication required.');
        }

        $user = Auth::user();

        // Check if user has the permission
        if ($user->hasPermissionTo($permission)) {
            return $next($request);
        }

        // Check if user owns the resource
        $resourceId = $request->route($parameterName);
        if ($resourceId && $this->isOwner($user, $resourceId, $parameterName)) {
            return $next($request);
        }

        return $this->unauthorized($request, "Access denied. You need '{$permission}' permission or ownership of this resource.");
    }

    /**
     * Check if the user owns the resource.
     */
    private function isOwner($user, $resourceId, string $parameterName): bool
    {
        switch ($parameterName) {
            case 'user':
                // For user resources, check if it's the same user
                return $user->id == $resourceId;

            case 'school':
                // For school resources, check if user belongs to the school
                return $user->isSchoolUser() && $user->school_id == $resourceId;

            default:
                // For other resources, assume false (extend as needed)
                return false;
        }
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
