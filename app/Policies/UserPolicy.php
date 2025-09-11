<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_users');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        // Users can view their own profile
        if ($user->id === $model->id) {
            return true;
        }

        // Check if user has permission to view other users
        if ($user->hasPermissionTo('view_users')) {
            // School-level users can only view users from their school
            if ($user->isSchoolUser() && $model->isSchoolUser()) {
                return $user->school_id === $model->school_id;
            }

            // System users can view all users
            return $user->isSystemUser();
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_users');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Users can update their own profile (limited fields)
        if ($user->id === $model->id) {
            return true;
        }

        // Check if user has permission to edit other users
        if ($user->hasPermissionTo('edit_users')) {
            // School-level users can only edit users from their school
            if ($user->isSchoolUser() && $model->isSchoolUser()) {
                return $user->school_id === $model->school_id;
            }

            // System users can edit all users
            return $user->isSystemUser();
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Users cannot delete themselves
        if ($user->id === $model->id) {
            return false;
        }

        // Check if user has permission to delete users
        if ($user->hasPermissionTo('delete_users')) {
            // School-level users can only delete users from their school
            if ($user->isSchoolUser() && $model->isSchoolUser()) {
                return $user->school_id === $model->school_id;
            }
            // System users can delete users (except other system admins)
            if ($user->isSystemUser()) {
                // Prevent deletion of system admins by non-super-admins
                return ! $model->hasRole('System Admin') || $user->hasRole('Super Admin');
            }
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->hasPermissionTo('restore_users') &&
               ($user->isSystemUser() ||
                ($user->isSchoolUser() && $user->school_id === $model->school_id));
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        // Only system admins can force delete
        return $user->hasPermissionTo('force_delete_users') &&
               $user->isSystemUser() &&
               $user->id !== $model->id;
    }

    /**
     * Determine whether the user can manage roles for the model.
     */
    public function manageRoles(User $user, User $model): bool
    {
        // Users cannot manage their own roles
        if ($user->id === $model->id) {
            return false;
        }

        if ($user->hasPermissionTo('assign_roles')) {
            // School-level users can only manage roles for users from their school
            if ($user->isSchoolUser() && $model->isSchoolUser()) {
                return $user->school_id === $model->school_id;
            }

            // System users can manage all user roles
            return $user->isSystemUser();
        }

        return false;
    }

    /**
     * Determine whether the user can view the model's profile.
     */
    public function viewProfile(User $user, User $model): bool
    {
        // Users can always view their own profile
        if ($user->id === $model->id) {
            return true;
        }

        // Same school users can view each other's profiles
        if ($user->school_id && $model->school_id && $user->school_id === $model->school_id) {
            return true;
        }

        // System users can view all profiles
        return $user->isSystemUser() && $user->hasPermissionTo('view_users');
    }

    /**
     * Determine whether the user can update their own profile.
     */
    public function updateProfile(User $user, User $model): bool
    {
        // Users can update their own profile
        if ($user->id === $model->id) {
            return true;
        }

        // Admin users can update other profiles if they have permission
        return $this->update($user, $model);
    }

    /**
     * Determine whether the user can view audit trails for the model.
     */
    public function viewAuditTrail(User $user, User $model): bool
    {
        // System admins and auditors can view all audit trails
        if ($user->hasRole(['System Admin', 'Auditor']) || $user->hasPermissionTo('view_audit_trail')) {
            return true;
        }

        // School admins can view audit trails for users in their school
        if ($user->hasRole('School Admin') && $model->school_id === $user->school_id) {
            return true;
        }

        // Users can view their own audit trail
        return $user->id === $model->id;
    }
}
