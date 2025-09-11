<?php

namespace App\Policies;

use App\Models\User;
use OwenIt\Auditing\Models\Audit;

class AuditPolicy
{
    /**
     * Determine whether the user can view any audit records.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_audit_trail') ||
               $user->hasRole(['System Admin', 'Auditor']);
    }

    /**
     * Determine whether the user can view the audit record.
     */
    public function view(User $user, Audit $audit): bool
    {
        // System admins and auditors can view all audit records
        if ($user->hasRole(['System Admin', 'Auditor']) || $user->hasPermissionTo('view_audit_trail')) {
            return true;
        }

        // Users can view their own audit records
        if ($audit->user_id === $user->id) {
            return true;
        }

        // School admins can view audit records for their school's data
        if ($user->hasRole('School Admin') && $user->school_id) {
            // Check if the audited model is school-related and belongs to their school
            return $this->isSchoolRelatedAudit($audit, $user->school_id);
        }

        return false;
    }

    /**
     * Determine whether the user can view the audit dashboard.
     */
    public function viewDashboard(User $user): bool
    {
        return $user->hasPermissionTo('view_audit_dashboard') ||
               $user->hasRole(['System Admin', 'Auditor', 'School Admin']);
    }

    /**
     * Determine whether the user can view audit statistics.
     */
    public function viewStatistics(User $user): bool
    {
        return $user->hasPermissionTo('view_audit_statistics') ||
               $user->hasRole(['System Admin', 'Auditor', 'School Admin']);
    }

    /**
     * Determine whether the user can view audit timeline for a specific record.
     */
    public function viewTimeline(User $user, ?string $modelType = null, ?int $modelId = null): bool
    {
        // System admins and auditors can view all timelines
        if ($user->hasRole(['System Admin', 'Auditor']) || $user->hasPermissionTo('view_audit_trail')) {
            return true;
        }

        // If it's a User model timeline, check if it's their own or they have permission
        if ($modelType === 'App\\Models\\User' && $modelId) {
            if ($modelId === $user->id) {
                return true; // Users can view their own timeline
            }

            // School admins can view timelines of users in their school
            if ($user->hasRole('School Admin') && $user->school_id) {
                $targetUser = User::find($modelId);

                return $targetUser && $targetUser->school_id === $user->school_id;
            }
        }

        // For School models, check school ownership
        if ($modelType === 'App\\Models\\School' && $modelId) {
            if ($user->hasRole('School Admin') && $user->school_id === $modelId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can view audits for a specific model.
     */
    public function viewForModel(User $user, string $modelType, int $modelId): bool
    {
        return $this->viewTimeline($user, $modelType, $modelId);
    }

    /**
     * Determine whether the user can view audits for a specific user.
     */
    public function viewForUser(User $user, int $userId): bool
    {
        // Users can view their own audit records
        if ($user->id === $userId) {
            return true;
        }

        // System admins and auditors can view all user audits
        if ($user->hasRole(['System Admin', 'Auditor']) || $user->hasPermissionTo('view_audit_trail')) {
            return true;
        }

        // School admins can view audits for users in their school
        if ($user->hasRole('School Admin') && $user->school_id) {
            $targetUser = User::find($userId);

            return $targetUser && $targetUser->school_id === $user->school_id;
        }

        return false;
    }

    /**
     * Determine whether the user can export audit data.
     */
    public function export(User $user): bool
    {
        return $user->hasPermissionTo('export_audit_data') ||
               $user->hasRole(['System Admin', 'Auditor']);
    }

    /**
     * Determine whether the user can view audit records filtered by date range.
     */
    public function viewDateRange(User $user): bool
    {
        return $user->hasPermissionTo('view_audit_trail') ||
               $user->hasRole(['System Admin', 'Auditor', 'School Admin']);
    }

    /**
     * Check if an audit record is related to a specific school.
     */
    private function isSchoolRelatedAudit(Audit $audit, int $schoolId): bool
    {
        $auditableType = $audit->auditable_type;
        $auditableId = $audit->auditable_id;

        // Direct school audit
        if ($auditableType === 'App\\Models\\School') {
            return $auditableId === $schoolId;
        }

        // School-related models
        $schoolRelatedModels = [
            'App\\Models\\SchoolContact',
            'App\\Models\\SchoolAddress',
            'App\\Models\\SchoolManagement',
            'App\\Models\\SchoolOfficial',
            'App\\Models\\SchoolDocument',
            'App\\Models\\AcademicYear',
            'App\\Models\\SchoolClass',
        ];

        if (in_array($auditableType, $schoolRelatedModels)) {
            try {
                $model = $auditableType::find($auditableId);

                return $model && $model->school_id === $schoolId;
            } catch (\Exception $e) {
                return false;
            }
        }

        // User audits for school users
        if ($auditableType === 'App\\Models\\User') {
            try {
                $user = User::find($auditableId);

                return $user && $user->school_id === $schoolId;
            } catch (\Exception $e) {
                return false;
            }
        }

        return false;
    }
}
