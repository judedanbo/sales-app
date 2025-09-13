<?php

namespace App\Policies;

use App\Models\ClassProductRequirement;
use App\Models\User;

class ClassProductRequirementPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_school_product_usage');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ClassProductRequirement $classProductRequirement): bool
    {
        // School-level users can only view requirements for their own school
        if ($user->isSchoolUser()) {
            return $user->school_id === $classProductRequirement->school_id &&
                   $user->hasPermissionTo('view_school_product_usage');
        }

        // System users can view all requirements if they have permission
        return $user->isSystemUser() && $user->hasPermissionTo('view_school_product_usage');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage_class_requirements');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ClassProductRequirement $classProductRequirement): bool
    {
        // School-level users can only update requirements for their own school
        if ($user->isSchoolUser()) {
            return $user->school_id === $classProductRequirement->school_id &&
                   $user->hasPermissionTo('manage_class_requirements');
        }

        // System users can update any requirement if they have permission
        return $user->isSystemUser() && $user->hasPermissionTo('manage_class_requirements');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ClassProductRequirement $classProductRequirement): bool
    {
        // Cannot delete requirements that are close to their deadline
        if ($classProductRequirement->required_by &&
            $classProductRequirement->required_by <= now()->addDays(7)) {
            return false;
        }

        // School-level users can only delete requirements for their own school
        if ($user->isSchoolUser()) {
            return $user->school_id === $classProductRequirement->school_id &&
                   $user->hasPermissionTo('manage_class_requirements');
        }

        // System users can delete any requirement if they have permission
        return $user->isSystemUser() && $user->hasPermissionTo('manage_class_requirements');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ClassProductRequirement $classProductRequirement): bool
    {
        // School-level users can only restore requirements for their own school
        if ($user->isSchoolUser()) {
            return $user->school_id === $classProductRequirement->school_id &&
                   $user->hasPermissionTo('manage_class_requirements');
        }

        // System users can restore any requirement if they have permission
        return $user->isSystemUser() && $user->hasPermissionTo('manage_class_requirements');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ClassProductRequirement $classProductRequirement): bool
    {
        // Only system users with special permission can force delete
        return $user->isSystemUser() && $user->hasPermissionTo('manage_class_requirements');
    }

    /**
     * Determine whether the user can approve the requirement.
     */
    public function approve(User $user, ClassProductRequirement $classProductRequirement): bool
    {
        // Cannot approve own submissions (separation of duties)
        if ($classProductRequirement->created_by === $user->id) {
            return false;
        }

        // School-level users can only approve requirements for their own school
        if ($user->isSchoolUser()) {
            return $user->school_id === $classProductRequirement->school_id &&
                   $user->hasPermissionTo('approve_class_requirements');
        }

        // System users can approve any requirement if they have permission
        return $user->isSystemUser() && $user->hasPermissionTo('approve_class_requirements');
    }

    /**
     * Determine whether the user can copy requirements to another year.
     */
    public function copy(User $user, ClassProductRequirement $classProductRequirement): bool
    {
        // School-level users can only copy requirements for their own school
        if ($user->isSchoolUser()) {
            return $user->school_id === $classProductRequirement->school_id &&
                   $user->hasPermissionTo('copy_class_requirements');
        }

        // System users can copy any requirement if they have permission
        return $user->isSystemUser() && $user->hasPermissionTo('copy_class_requirements');
    }

    /**
     * Determine whether the user can assign products to classes.
     */
    public function assign(User $user, ClassProductRequirement $classProductRequirement): bool
    {
        // School-level users can only assign for their own school
        if ($user->isSchoolUser()) {
            return $user->school_id === $classProductRequirement->school_id &&
                   $user->hasPermissionTo('assign_products_to_classes');
        }

        // System users can assign for any school if they have permission
        return $user->isSystemUser() && $user->hasPermissionTo('assign_products_to_classes');
    }

    /**
     * Determine whether the user can export requirements data.
     */
    public function export(User $user): bool
    {
        return $user->hasPermissionTo('export_class_requirements');
    }

    /**
     * Determine whether the user can perform bulk operations.
     */
    public function bulkUpdate(User $user): bool
    {
        return $user->hasPermissionTo('manage_class_requirements');
    }

    /**
     * Determine whether the user can view usage analytics.
     */
    public function viewAnalytics(User $user, ClassProductRequirement $classProductRequirement): bool
    {
        // School-level users can only view analytics for their own school
        if ($user->isSchoolUser()) {
            return $user->school_id === $classProductRequirement->school_id &&
                   $user->hasPermissionTo('view_school_product_usage');
        }

        // System users can view analytics for any school if they have permission
        return $user->isSystemUser() && $user->hasPermissionTo('view_school_product_usage');
    }

    /**
     * Determine whether the user can manage the requirement as a school user.
     */
    public function manageForSchool(User $user, int $schoolId): bool
    {
        // School-level users can only manage for their own school
        if ($user->isSchoolUser()) {
            return $user->school_id === $schoolId &&
                   $user->hasPermissionTo('manage_class_requirements');
        }

        // System users can manage for any school if they have permission
        return $user->isSystemUser() && $user->hasPermissionTo('manage_class_requirements');
    }
}
