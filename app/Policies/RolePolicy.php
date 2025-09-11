<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    /**
     * Determine whether the user can view any roles.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_roles');
    }

    /**
     * Determine whether the user can view the role.
     */
    public function view(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('view_roles');
    }

    /**
     * Determine whether the user can create roles.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_roles') && $user->isSystemUser();
    }

    /**
     * Determine whether the user can update the role.
     */
    public function update(User $user, Role $role): bool
    {
        // System admins can edit roles
        if ($user->hasPermissionTo('edit_roles') && $user->isSystemUser()) {
            // Prevent editing of higher-level roles by lower-level admins
            if ($role->name === 'Super Admin' && ! $user->hasRole('Super Admin')) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the role.
     */
    public function delete(User $user, Role $role): bool
    {
        // Only system users can delete roles
        if ($user->hasPermissionTo('delete_roles') && $user->isSystemUser()) {
            // Prevent deletion of critical system roles
            $protectedRoles = ['Super Admin', 'System Admin', 'Guest'];
            if (in_array($role->name, $protectedRoles)) {
                return false;
            }

            // Prevent deletion if role has users assigned
            if ($role->users()->exists()) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the role.
     */
    public function restore(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('restore_roles') && $user->isSystemUser();
    }

    /**
     * Determine whether the user can permanently delete the role.
     */
    public function forceDelete(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('force_delete_roles') &&
               $user->isSystemUser() &&
               $user->hasRole('Super Admin');
    }

    /**
     * Determine whether the user can assign users to the role.
     */
    public function assignUsers(User $user, Role $role): bool
    {
        // Users can assign roles if they have permission
        if ($user->hasPermissionTo('assign_roles')) {
            // School admins can only assign school-level roles
            if ($user->hasRole('School Admin')) {
                $schoolRoles = ['Principal', 'Teacher', 'School Admin'];

                return in_array($role->name, $schoolRoles);
            }

            // System users can assign most roles
            if ($user->isSystemUser()) {
                // Only Super Admins can assign Super Admin role
                if ($role->name === 'Super Admin') {
                    return $user->hasRole('Super Admin');
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can remove users from the role.
     */
    public function removeUsers(User $user, Role $role): bool
    {
        return $this->assignUsers($user, $role);
    }

    /**
     * Determine whether the user can manage permissions for the role.
     */
    public function managePermissions(User $user, Role $role): bool
    {
        // Only system admins can manage role permissions
        if ($user->hasPermissionTo('assign_permissions_to_roles') && $user->isSystemUser()) {
            // Only Super Admins can modify Super Admin permissions
            if ($role->name === 'Super Admin') {
                return $user->hasRole('Super Admin');
            }

            // System Admins cannot modify their own role or higher roles
            if ($user->hasRole('System Admin') && in_array($role->name, ['System Admin', 'Super Admin'])) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view role statistics.
     */
    public function viewStatistics(User $user): bool
    {
        return $user->hasPermissionTo('view_roles') ||
               $user->hasRole(['System Admin', 'Auditor']);
    }

    /**
     * Determine whether the user can view users assigned to the role.
     */
    public function viewUsers(User $user, Role $role): bool
    {
        // School admins can view users for school roles
        if ($user->hasRole('School Admin')) {
            $schoolRoles = ['Principal', 'Teacher', 'School Admin'];

            return in_array($role->name, $schoolRoles);
        }

        // System users can view all role users
        return $user->isSystemUser() && $user->hasPermissionTo('view_roles');
    }

    /**
     * Determine whether the user can export role data.
     */
    public function export(User $user): bool
    {
        return $user->hasPermissionTo('export_roles') && $user->isSystemUser();
    }

    /**
     * Determine whether the user can duplicate the role.
     */
    public function duplicate(User $user, Role $role): bool
    {
        return $user->hasPermissionTo('create_roles') &&
               $user->isSystemUser() &&
               $role->name !== 'Super Admin';
    }
}
