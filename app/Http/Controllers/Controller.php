<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;

abstract class Controller
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Authorize a resource action with consistent error handling.
     */
    protected function authorizeResource(string $ability, $resource, ?string $parameter = null): void
    {
        try {
            $this->authorize($ability, $resource);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            if (request()->expectsJson()) {
                abort(403, 'This action is unauthorized.');
            }
            abort(403, 'You do not have permission to perform this action.');
        }
    }

    /**
     * Authorize multiple resources for bulk operations.
     */
    protected function authorizeBulkOperation(string $ability, string $modelClass, array $resources = []): void
    {
        // Check bulk permission first
        $this->authorizeResource('bulk'.ucfirst($ability), $modelClass);

        // Check individual resource permissions if resources provided
        foreach ($resources as $resource) {
            if ($resource instanceof Model) {
                $this->authorizeResource($ability, $resource);
            }
        }
    }

    /**
     * Validate resource ownership with fallback to policy authorization.
     */
    protected function validateOwnership($resource, ?int $userId = null): void
    {
        $userId = $userId ?? auth()->id();

        // For models with user_id or owner_id
        if ($resource instanceof Model) {
            if (method_exists($resource, 'isOwnedBy')) {
                if (! $resource->isOwnedBy($userId)) {
                    abort(404, 'Resource not found.');
                }
            } elseif (isset($resource->user_id) && $resource->user_id !== $userId) {
                // Check if user has override permission through policy
                try {
                    $this->authorize('viewAny', get_class($resource));
                } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
                    abort(404, 'Resource not found.');
                }
            }
        }
    }

    /**
     * Check if the current user is a system administrator.
     */
    protected function requireSystemUser(): void
    {
        if (! auth()->user()?->isSystemUser()) {
            abort(403, 'System administrator access required.');
        }
    }

    /**
     * Check if the current user can manage the specified resource type.
     */
    protected function requireManagementPermission(string $permission): void
    {
        if (! auth()->user()?->hasPermissionTo($permission)) {
            abort(403, 'Insufficient permissions to perform this operation.');
        }
    }

    /**
     * Get the authenticated user with error handling.
     */
    protected function getAuthenticatedUser()
    {
        $user = auth()->user();
        if (! $user) {
            abort(401, 'Authentication required.');
        }

        return $user;
    }

    /**
     * Standardized JSON response for authorization errors.
     */
    protected function unauthorizedResponse(string $message = 'This action is unauthorized.'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => 'Unauthorized',
            'code' => 'AUTHORIZATION_FAILED',
        ], 403);
    }

    /**
     * Standardized JSON response for ownership validation errors.
     */
    protected function notFoundResponse(string $message = 'Resource not found.'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => 'Not Found',
            'code' => 'RESOURCE_NOT_FOUND',
        ], 404);
    }

    /**
     * Check bulk operation limits for security.
     */
    protected function validateBulkOperationLimits(array $items, int $maxItems = 100): void
    {
        if (count($items) > $maxItems) {
            abort(422, "Bulk operations are limited to {$maxItems} items at a time.");
        }

        if (empty($items)) {
            abort(422, 'No items provided for bulk operation.');
        }
    }
}
