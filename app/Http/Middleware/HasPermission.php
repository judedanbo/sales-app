<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class HasPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (! Auth::check()) {
            return $this->unauthorized($request, 'Authentication required.');
        }

        $user = Auth::user();

        foreach ($permissions as $permission) {
            if (! $user->hasPermissionTo($permission)) {
                return $this->unauthorized($request, "Missing required permission: {$permission}");
            }
        }

        return $next($request);
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
            ], 403)->toResponse($request);
        }

        abort(403, $message);
    }
}
