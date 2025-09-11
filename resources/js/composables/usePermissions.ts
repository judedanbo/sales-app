import { computed, type ComputedRef } from 'vue';
import { useAuth } from './useAuth';

export function usePermissions() {
    const { user, roles, allPermissions, isSystemUser, isSchoolUser, schoolId } = useAuth();

    /**
     * Check if user has a specific permission
     */
    const hasPermission = (permission: string): boolean => {
        if (!user.value || !allPermissions.value) return false;
        return allPermissions.value.includes(permission);
    };

    /**
     * Check if user has any of the given permissions
     */
    const hasAnyPermission = (...permissions: string[]): boolean => {
        if (!user.value || !allPermissions.value) return false;
        return permissions.some((permission) => allPermissions.value.includes(permission));
    };

    /**
     * Check if user has all of the given permissions
     */
    const hasAllPermissions = (...permissions: string[]): boolean => {
        if (!user.value || !allPermissions.value) return false;
        return permissions.every((permission) => allPermissions.value.includes(permission));
    };

    /**
     * Check if user has a specific role
     */
    const hasRole = (role: string): boolean => {
        if (!user.value || !roles.value) return false;
        return roles.value.includes(role);
    };

    /**
     * Check if user has any of the given roles
     */
    const hasAnyRole = (...roleNames: string[]): boolean => {
        if (!user.value || !roles.value) return false;
        return roleNames.some((role) => roles.value.includes(role));
    };

    /**
     * Check if user has all of the given roles
     */
    const hasAllRoles = (...roleNames: string[]): boolean => {
        if (!user.value || !roles.value) return false;
        return roleNames.every((role) => roles.value.includes(role));
    };

    /**
     * Check if user can access schools
     */
    const canViewSchools: ComputedRef<boolean> = computed(() => {
        return hasPermission('view_schools');
    });

    /**
     * Check if user can create schools
     */
    const canCreateSchools: ComputedRef<boolean> = computed(() => {
        return hasPermission('create_schools');
    });

    /**
     * Check if user can edit schools
     */
    const canEditSchools: ComputedRef<boolean> = computed(() => {
        return hasPermission('edit_schools') || hasPermission('edit_own_school');
    });

    /**
     * Check if user can delete schools
     */
    const canDeleteSchools: ComputedRef<boolean> = computed(() => {
        return hasPermission('delete_schools');
    });

    /**
     * Check if user can view users
     */
    const canViewUsers: ComputedRef<boolean> = computed(() => {
        return hasPermission('view_users');
    });

    /**
     * Check if user can create users
     */
    const canCreateUsers: ComputedRef<boolean> = computed(() => {
        return hasPermission('create_users');
    });

    /**
     * Check if user can edit users
     */
    const canEditUsers: ComputedRef<boolean> = computed(() => {
        return hasPermission('edit_users');
    });

    /**
     * Check if user can delete users
     */
    const canDeleteUsers: ComputedRef<boolean> = computed(() => {
        return hasPermission('delete_users');
    });

    /**
     * Check if user can view roles
     */
    const canViewRoles: ComputedRef<boolean> = computed(() => {
        return hasPermission('view_roles');
    });

    /**
     * Check if user can manage roles
     */
    const canManageRoles: ComputedRef<boolean> = computed(() => {
        return hasPermission('manage_roles') || hasPermission('create_roles');
    });

    /**
     * Check if user can assign roles
     */
    const canAssignRoles: ComputedRef<boolean> = computed(() => {
        return hasPermission('assign_roles');
    });

    /**
     * Check if user can view permissions
     */
    const canViewPermissions: ComputedRef<boolean> = computed(() => {
        return hasPermission('view_permissions');
    });

    /**
     * Check if user can manage permissions
     */
    const canManagePermissions: ComputedRef<boolean> = computed(() => {
        return hasPermission('manage_permissions');
    });

    /**
     * Check if user can view audit trail
     */
    const canViewAuditTrail: ComputedRef<boolean> = computed(() => {
        return hasPermission('view_audit_trail') && hasAnyRole('System Admin', 'Auditor', 'School Admin');
    });

    /**
     * Check if user can view audit dashboard
     */
    const canViewAuditDashboard: ComputedRef<boolean> = computed(() => {
        return hasPermission('view_audit_dashboard');
    });

    /**
     * Check if user can perform bulk operations
     */
    const canPerformBulkOperations: ComputedRef<boolean> = computed(() => {
        return hasAnyPermission('bulk_edit_schools', 'bulk_edit_users', 'bulk_manage_roles');
    });

    /**
     * Check if user can access admin features
     */
    const canAccessAdminFeatures: ComputedRef<boolean> = computed(() => {
        return isSystemUser && hasAnyRole('System Admin', 'Admin');
    });

    /**
     * Check if user can access super admin features
     */
    const canAccessSuperAdminFeatures: ComputedRef<boolean> = computed(() => {
        return hasRole('Super Admin');
    });

    /**
     * Check if user can manage school classes
     */
    const canManageSchoolClasses: ComputedRef<boolean> = computed(() => {
        return hasPermission('manage_school_classes');
    });

    /**
     * Check if user can manage academic years
     */
    const canManageAcademicYears: ComputedRef<boolean> = computed(() => {
        return hasPermission('manage_academic_years');
    });

    /**
     * Check if user can view statistics and reports
     */
    const canViewStatistics: ComputedRef<boolean> = computed(() => {
        return hasAnyPermission('view_statistics', 'view_reports', 'view_audit_dashboard');
    });

    /**
     * Check if user owns a specific school resource
     */
    const ownsSchoolResource = (resourceSchoolId: number | null | undefined): boolean => {
        if (isSystemUser.value) return true; // System users can access all resources
        if (!isSchoolUser.value || !schoolId.value) return false;
        return resourceSchoolId === schoolId.value;
    };

    /**
     * Check if user can access school-specific resource
     */
    const canAccessSchoolResource = (resourceSchoolId: number | null | undefined, permission: string): boolean => {
        if (!hasPermission(permission)) return false;
        return ownsSchoolResource(resourceSchoolId);
    };

    return {
        // Core permission functions
        hasPermission,
        hasAnyPermission,
        hasAllPermissions,

        // Role functions
        hasRole,
        hasAnyRole,
        hasAllRoles,

        // School permissions
        canViewSchools,
        canCreateSchools,
        canEditSchools,
        canDeleteSchools,

        // User permissions
        canViewUsers,
        canCreateUsers,
        canEditUsers,
        canDeleteUsers,

        // Role permissions
        canViewRoles,
        canManageRoles,
        canAssignRoles,

        // Permission permissions
        canViewPermissions,
        canManagePermissions,

        // Audit permissions
        canViewAuditTrail,
        canViewAuditDashboard,

        // School-specific permissions
        canManageSchoolClasses,
        canManageAcademicYears,

        // Administrative permissions
        canPerformBulkOperations,
        canAccessAdminFeatures,
        canAccessSuperAdminFeatures,
        canViewStatistics,

        // Resource ownership functions
        ownsSchoolResource,
        canAccessSchoolResource,
    };
}
