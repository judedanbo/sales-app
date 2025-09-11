<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SystemUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $this->unauthorized($request, 'Authentication required.');
        }

        $user = Auth::user();

        if (! $user->isSystemUser()) {
            return $this->unauthorized($request, 'Access restricted to system administrators.');
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

        // Check if this is an Inertia request by looking for the X-Inertia header
        if ($request->header('X-Inertia')) {
            return inertia('Error', [
                'status' => 403,
                'message' => $message,
            ], 403)->toResponse($request);
        }

        abort(403, $message);
    }
}
