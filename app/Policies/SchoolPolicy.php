<?php

namespace App\Policies;

use App\Models\School;
use App\Models\User;

class SchoolPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_schools');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, School $school): bool
    {
        // School-level users can only view their own school
        if ($user->isSchoolUser()) {
            return $user->school_id === $school->id;
        }

        // System users can view all schools if they have permission
        return $user->isSystemUser() && $user->hasPermissionTo('view_schools');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only system users can create schools
        return $user->isSystemUser() && $user->hasPermissionTo('create_schools');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, School $school): bool
    {
        // School-level users can update their own school
        if ($user->isSchoolUser() && $user->school_id === $school->id) {
            return $user->hasPermissionTo('edit_own_school');
        }

        // System users can update any school if they have permission
        return $user->isSystemUser() && $user->hasPermissionTo('edit_schools');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, School $school): bool
    {
        // Only system users can delete schools
        if ($user->isSystemUser() && $user->hasPermissionTo('delete_schools')) {
            // Prevent deletion if school has active users
            return ! $school->users()->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, School $school): bool
    {
        return $user->isSystemUser() && $user->hasPermissionTo('restore_schools');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, School $school): bool
    {
        return $user->isSystemUser() && $user->hasPermissionTo('force_delete_schools');
    }

    /**
     * Determine whether the user can view classes for the school.
     */
    public function viewClasses(User $user, School $school): bool
    {
        // School-level users can view their own school's classes
        if ($user->isSchoolUser() && $user->school_id === $school->id) {
            return true;
        }

        // System users can view all school classes
        return $user->isSystemUser() && $user->hasPermissionTo('view_schools');
    }

    /**
     * Determine whether the user can manage classes for the school.
     */
    public function manageClasses(User $user, School $school): bool
    {
        // School admins and principals can manage their school's classes
        if ($user->isSchoolUser() && $user->school_id === $school->id) {
            return $user->hasRole(['School Admin', 'Principal']) ||
                   $user->hasPermissionTo('manage_school_classes');
        }

        // System users can manage all school classes
        return $user->isSystemUser() && $user->hasPermissionTo('manage_schools');
    }

    /**
     * Determine whether the user can view academic years for the school.
     */
    public function viewAcademicYears(User $user, School $school): bool
    {
        // School-level users can view their own school's academic years
        if ($user->isSchoolUser() && $user->school_id === $school->id) {
            return true;
        }

        // System users can view all school academic years
        return $user->isSystemUser() && $user->hasPermissionTo('view_schools');
    }

    /**
     * Determine whether the user can manage academic years for the school.
     */
    public function manageAcademicYears(User $user, School $school): bool
    {
        // School admins and principals can manage their school's academic years
        if ($user->isSchoolUser() && $user->school_id === $school->id) {
            return $user->hasRole(['School Admin', 'Principal']) ||
                   $user->hasPermissionTo('manage_academic_years');
        }

        // System users can manage all school academic years
        return $user->isSystemUser() && $user->hasPermissionTo('manage_schools');
    }

    /**
     * Determine whether the user can manage officials for the school.
     */
    public function manageOfficials(User $user, School $school): bool
    {
        // School admins can manage their school's officials
        if ($user->isSchoolUser() && $user->school_id === $school->id) {
            return $user->hasRole('School Admin') ||
                   $user->hasPermissionTo('manage_school_officials');
        }

        // System users can manage all school officials
        return $user->isSystemUser() && $user->hasPermissionTo('manage_school_officials');
    }

    /**
     * Determine whether the user can manage contacts for the school.
     */
    public function manageContacts(User $user, School $school): bool
    {
        // School-level users can manage their own school's contacts
        if ($user->isSchoolUser() && $user->school_id === $school->id) {
            return $user->hasRole(['School Admin', 'Principal']) ||
                   $user->hasPermissionTo('edit_own_school');
        }

        // System users can manage all school contacts
        return $user->isSystemUser() && $user->hasPermissionTo('edit_schools');
    }

    /**
     * Determine whether the user can manage addresses for the school.
     */
    public function manageAddresses(User $user, School $school): bool
    {
        // School-level users can manage their own school's addresses
        if ($user->isSchoolUser() && $user->school_id === $school->id) {
            return $user->hasRole(['School Admin', 'Principal']) ||
                   $user->hasPermissionTo('edit_own_school');
        }

        // System users can manage all school addresses
        return $user->isSystemUser() && $user->hasPermissionTo('edit_schools');
    }

    /**
     * Determine whether the user can manage documents for the school.
     */
    public function manageDocuments(User $user, School $school): bool
    {
        // School admins can manage their school's documents
        if ($user->isSchoolUser() && $user->school_id === $school->id) {
            return $user->hasRole('School Admin') ||
                   $user->hasPermissionTo('manage_school_documents');
        }

        // System users can manage all school documents
        return $user->isSystemUser() && $user->hasPermissionTo('manage_schools');
    }

    /**
     * Determine whether the user can view statistics for the school.
     */
    public function viewStatistics(User $user, School $school): bool
    {
        // School-level users can view their own school's statistics
        if ($user->isSchoolUser() && $user->school_id === $school->id) {
            return true;
        }

        // System users can view all school statistics
        return $user->isSystemUser() &&
               ($user->hasPermissionTo('view_schools') || $user->hasPermissionTo('view_reports'));
    }
}
