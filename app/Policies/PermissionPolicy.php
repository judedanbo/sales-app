<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Permission;

class PermissionPolicy
{
    /**
     * Determine whether the user can view any permissions.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_permissions');
    }

    /**
     * Determine whether the user can view the permission.
     */
    public function view(User $user, Permission $permission): bool
    {
        return $user->hasPermissionTo('view_permissions');
    }

    /**
     * Determine whether the user can create permissions.
     */
    public function create(User $user): bool
    {
        // Only Super Admins can create new permissions
        return $user->hasPermissionTo('create_permissions') &&
               $user->hasRole('Super Admin');
    }

    /**
     * Determine whether the user can update the permission.
     */
    public function update(User $user, Permission $permission): bool
    {
        // Only Super Admins can edit permissions
        return $user->hasPermissionTo('edit_permissions') &&
               $user->hasRole('Super Admin');
    }

    /**
     * Determine whether the user can delete the permission.
     */
    public function delete(User $user, Permission $permission): bool
    {
        // Only Super Admins can delete permissions
        if ($user->hasPermissionTo('delete_permissions') && $user->hasRole('Super Admin')) {
            // Prevent deletion of critical system permissions
            $protectedPermissions = [
                'view_users', 'create_users', 'edit_users', 'delete_users',
                'view_roles', 'create_roles', 'edit_roles', 'delete_roles',
                'view_permissions', 'create_permissions', 'edit_permissions', 'delete_permissions',
                'assign_roles', 'assign_permissions_to_roles',
            ];

            if (in_array($permission->name, $protectedPermissions)) {
                return false;
            }

            // Prevent deletion if permission is assigned to roles
            if ($permission->roles()->exists()) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the permission.
     */
    public function restore(User $user, Permission $permission): bool
    {
        return $user->hasPermissionTo('restore_permissions') &&
               $user->hasRole('Super Admin');
    }

    /**
     * Determine whether the user can permanently delete the permission.
     */
    public function forceDelete(User $user, Permission $permission): bool
    {
        return $user->hasPermissionTo('force_delete_permissions') &&
               $user->hasRole('Super Admin');
    }

    /**
     * Determine whether the user can assign roles to the permission.
     */
    public function assignRoles(User $user, Permission $permission): bool
    {
        // System admins can assign most permissions to roles
        if ($user->hasPermissionTo('assign_permissions_to_roles') && $user->isSystemUser()) {
            // Critical permissions can only be assigned by Super Admins
            $criticalPermissions = [
                'create_permissions', 'edit_permissions', 'delete_permissions', 'force_delete_permissions',
                'create_roles', 'delete_roles', 'force_delete_roles',
                'force_delete_users', 'force_delete_schools',
            ];

            if (in_array($permission->name, $criticalPermissions)) {
                return $user->hasRole('Super Admin');
            }

            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can remove roles from the permission.
     */
    public function removeRoles(User $user, Permission $permission): bool
    {
        return $this->assignRoles($user, $permission);
    }

    /**
     * Determine whether the user can view permission statistics.
     */
    public function viewStatistics(User $user): bool
    {
        return $user->hasPermissionTo('view_permissions') ||
               $user->hasRole(['System Admin', 'Auditor']);
    }

    /**
     * Determine whether the user can view roles assigned to the permission.
     */
    public function viewRoles(User $user, Permission $permission): bool
    {
        return $user->hasPermissionTo('view_permissions') && $user->isSystemUser();
    }

    /**
     * Determine whether the user can export permission data.
     */
    public function export(User $user): bool
    {
        return $user->hasPermissionTo('export_permissions') && $user->isSystemUser();
    }

    /**
     * Determine whether the user can view permission categories.
     */
    public function viewCategories(User $user): bool
    {
        return $user->hasPermissionTo('view_permissions');
    }

    /**
     * Determine whether the user can manage permission categories.
     */
    public function manageCategories(User $user): bool
    {
        return $user->hasPermissionTo('manage_permission_categories') &&
               $user->hasRole('Super Admin');
    }

    /**
     * Determine whether the user can view permission usage analytics.
     */
    public function viewAnalytics(User $user): bool
    {
        return $user->hasPermissionTo('view_permissions') &&
               ($user->hasRole(['System Admin', 'Auditor']) ||
                $user->hasPermissionTo('view_analytics'));
    }

    /**
     * Determine whether the user can sync permission with roles.
     */
    public function syncWithRoles(User $user, Permission $permission): bool
    {
        return $this->assignRoles($user, $permission);
    }
}
