<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

trait AuthorizesResourceOperations
{
    /**
     * Authorize viewing any resources of this type.
     */
    protected function authorizeViewAny(string $modelClass): void
    {
        $this->authorizeResource('viewAny', $modelClass);
    }

    /**
     * Authorize viewing a specific resource.
     */
    protected function authorizeView($resource): void
    {
        $this->authorizeResource('view', $resource);
    }

    /**
     * Authorize creating a new resource.
     */
    protected function authorizeCreate(string $modelClass): void
    {
        $this->authorizeResource('create', $modelClass);
    }

    /**
     * Authorize updating a resource.
     */
    protected function authorizeUpdate($resource): void
    {
        $this->authorizeResource('update', $resource);
        $this->validateOwnership($resource);
    }

    /**
     * Authorize deleting a resource.
     */
    protected function authorizeDelete($resource): void
    {
        $this->authorizeResource('delete', $resource);
        $this->validateOwnership($resource);
    }

    /**
     * Authorize restoring a soft-deleted resource.
     */
    protected function authorizeRestore($resource): void
    {
        $this->authorizeResource('restore', $resource);
    }

    /**
     * Authorize permanently deleting a resource.
     */
    protected function authorizeForceDelete($resource): void
    {
        $this->authorizeResource('forceDelete', $resource);
        $this->requireSystemUser(); // Additional security for permanent deletion
    }

    /**
     * Authorize bulk operations with individual resource checks.
     */
    protected function authorizeBulkOperationWithResources(string $operation, string $modelClass, $resources): void
    {
        // Check bulk permission
        $this->authorizeBulkOperation($operation, $modelClass);

        // Validate limits
        if (is_array($resources)) {
            $this->validateBulkOperationLimits($resources);
        } elseif ($resources instanceof Collection) {
            $this->validateBulkOperationLimits($resources->all());
        }

        // Check individual permissions for loaded resources
        if ($resources instanceof Collection || is_array($resources)) {
            foreach ($resources as $resource) {
                if ($resource instanceof Model) {
                    $this->authorizeResource($operation, $resource);
                }
            }
        }
    }

    /**
     * Authorize access to statistics and reports.
     */
    protected function authorizeStatistics(string $modelClass): void
    {
        try {
            $this->authorizeResource('viewAny', $modelClass);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            // Fallback to specific statistics permission
            $this->requireManagementPermission('view_statistics');
        }
    }

    /**
     * Authorize role and permission management operations.
     */
    protected function authorizeRoleManagement($resource = null): void
    {
        if ($resource) {
            $this->authorizeResource('update', $resource);
        }
        $this->requireManagementPermission('assign_roles');
    }

    /**
     * Authorize user activation/deactivation.
     */
    protected function authorizeUserStatusChange($user): void
    {
        $this->authorizeResource('update', $user);

        // Prevent self-deactivation
        if ($user->id === auth()->id()) {
            abort(422, 'You cannot change your own account status.');
        }
    }

    /**
     * Authorize access to audit trails.
     */
    protected function authorizeAuditAccess(): void
    {
        $user = $this->getAuthenticatedUser();

        if (! $user->hasRole(['super_admin', 'system_admin', 'auditor', 'school_admin'])) {
            abort(403, 'Audit trail access requires appropriate role.');
        }

        $this->requireManagementPermission('view_audit_trail');
    }

    /**
     * Authorize school-specific resource access.
     */
    protected function authorizeSchoolResource($school, string $operation): void
    {
        $user = $this->getAuthenticatedUser();

        // System users can access all schools
        if ($user->isSystemUser()) {
            $this->authorizeResource($operation, $school);

            return;
        }

        // School users can only access their own school
        if ($user->isSchoolUser() && $user->school_id === $school->id) {
            $this->authorizeResource($operation, $school);

            return;
        }

        abort(404, 'School not found.');
    }

    /**
     * Authorize nested school resource access (classes, academic years).
     */
    protected function authorizeNestedSchoolResource($school, $nestedResource, string $operation): void
    {
        // First authorize school access
        $this->authorizeSchoolResource($school, 'view');

        // Then authorize the nested resource
        $this->authorizeResource($operation, $nestedResource);

        // Validate the nested resource belongs to the school
        if (method_exists($nestedResource, 'school') && $nestedResource->school_id !== $school->id) {
            abort(404, 'Resource not found in this school.');
        }
    }

    /**
     * Check if user can perform sensitive operations.
     */
    protected function authorizeSensitiveOperation(string $operation): void
    {
        $user = $this->getAuthenticatedUser();

        // Require specific time-based access for sensitive operations
        if (! $user->hasPermissionTo($operation)) {
            abort(403, 'Insufficient permissions for this sensitive operation.');
        }

        // Additional IP and time-based restrictions are handled by middleware
        // but we can add extra validation here if needed
    }

    /**
     * Authorize export operations.
     */
    protected function authorizeExport(string $modelClass): void
    {
        $this->authorizeResource('viewAny', $modelClass);
        $this->requireManagementPermission('export_data');
    }

    /**
     * Authorize import operations.
     */
    protected function authorizeImport(string $modelClass): void
    {
        $this->authorizeResource('create', $modelClass);
        $this->requireManagementPermission('import_data');
        $this->requireSystemUser(); // Imports are system-level operations
    }
}
