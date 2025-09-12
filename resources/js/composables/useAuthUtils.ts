import type { NavItem } from '@/types';
import { computed, type ComputedRef } from 'vue';
import { useAuth } from './useAuth';
import { usePermissions } from './usePermissions';

export function useAuthUtils() {
    const auth = useAuth();
    const permissions = usePermissions();

    /**
     * Get user display name with role information
     */
    const userDisplayNameWithRole: ComputedRef<string> = computed(() => {
        const name = auth.displayName.value;
        const roleLabel = auth.userTypeLabel.value;
        return roleLabel ? `${name} (${roleLabel})` : name;
    });

    /**
     * Get user avatar URL or initials
     */
    const userAvatarOrInitials: ComputedRef<string> = computed(() => {
        if (auth.user.value?.avatar) {
            return auth.user.value.avatar;
        }

        const name = auth.displayName.value;
        const initials = name
            .split(' ')
            .map((part) => part.charAt(0))
            .join('')
            .substring(0, 2)
            .toUpperCase();

        return initials;
    });

    /**
     * Get theme class based on user type
     */
    const userTypeThemeClass: ComputedRef<string> = computed(() => {
        const userType = auth.userType.value;

        switch (userType) {
            case 'system_admin':
                return 'theme-red';
            case 'admin':
                return 'theme-orange';
            case 'audit':
                return 'theme-purple';
            case 'school_admin':
                return 'theme-blue';
            case 'principal':
                return 'theme-green';
            case 'teacher':
                return 'theme-teal';
            case 'staff':
                return 'theme-gray';
            default:
                return 'theme-slate';
        }
    });

    /**
     * Get badge variant based on user status
     */
    const userStatusVariant: ComputedRef<'default' | 'secondary' | 'destructive' | 'outline'> = computed(() => {
        if (!auth.isActive.value) return 'destructive';
        if (!auth.isEmailVerified.value) return 'outline';
        return 'default';
    });

    /**
     * Filter navigation items based on permissions
     */
    const filterNavigationByPermissions = (items: NavItem[]): NavItem[] => {
        return items.filter((item) => {
            // Extract permission requirement from href or title
            const href = typeof item.href === 'string' ? item.href : '';

            // Dashboard is always accessible for authenticated users
            if (href.includes('/dashboard') || item.title === 'Dashboard') {
                return true;
            }

            // Schools navigation
            if (href.includes('/schools') || item.title === 'Schools') {
                return permissions.canViewSchools;
            }

            // Categories navigation - accessible to all authenticated users since it's a basic feature
            if (href.includes('/categories') || item.title === 'Categories') {
                return true;
            }

            // Users navigation
            if (href.includes('/users') || item.title === 'Users') {
                return permissions.canViewUsers;
            }

            // Roles navigation
            if (href.includes('/roles') || item.title === 'Roles') {
                return permissions.canViewRoles;
            }

            // Permissions navigation
            if (href.includes('/permissions') || item.title === 'Permissions') {
                return permissions.canViewPermissions;
            }

            // Audits navigation
            if (href.includes('/audits') || item.title === 'Audits') {
                return permissions.canViewAuditTrail;
            }

            // Documentation is accessible to all authenticated users
            if (href.includes('/docs') || item.title === 'Documentation') {
                return true;
            }

            // Default to showing the item (better UX than hiding everything)
            return true;
        });
    };

    /**
     * Check if user can perform action on resource
     */
    const canPerformAction = (
        action: 'view' | 'create' | 'edit' | 'delete' | 'restore' | 'force_delete',
        resource: 'schools' | 'users' | 'roles' | 'permissions' | 'audits',
        resourceData?: { school_id?: number },
    ): boolean => {
        const permissionMap = {
            schools: {
                view: permissions.canViewSchools,
                create: permissions.canCreateSchools,
                edit: permissions.canEditSchools,
                delete: permissions.canDeleteSchools,
                restore: permissions.hasPermission('restore_schools'),
                force_delete: permissions.hasPermission('force_delete_schools'),
            },
            users: {
                view: permissions.canViewUsers,
                create: permissions.canCreateUsers,
                edit: permissions.canEditUsers,
                delete: permissions.canDeleteUsers,
                restore: permissions.hasPermission('restore_users'),
                force_delete: permissions.hasPermission('force_delete_users'),
            },
            roles: {
                view: permissions.canViewRoles,
                create: permissions.canManageRoles,
                edit: permissions.canManageRoles,
                delete: permissions.canManageRoles,
                restore: false,
                force_delete: false,
            },
            permissions: {
                view: permissions.canViewPermissions,
                create: permissions.canManagePermissions,
                edit: permissions.canManagePermissions,
                delete: permissions.canManagePermissions,
                restore: false,
                force_delete: false,
            },
            audits: {
                view: permissions.canViewAuditTrail,
                create: false,
                edit: false,
                delete: false,
                restore: false,
                force_delete: false,
            },
        };

        const hasBasePermission = permissionMap[resource]?.[action];

        // If no base permission, deny access
        if (!hasBasePermission) return false;

        // For school-specific resources, check ownership
        if (resource === 'schools' && resourceData?.school_id) {
            return permissions.canAccessSchoolResource(resourceData.school_id, `${action}_schools`);
        }

        return true;
    };

    /**
     * Get contextual menu items based on permissions
     */
    const getContextualActions = (resource: 'schools' | 'users' | 'roles' | 'permissions', resourceData: any = {}) => {
        const actions = [];

        if (canPerformAction('view', resource, resourceData)) {
            actions.push({ label: 'View', action: 'view', variant: 'ghost' });
        }

        if (canPerformAction('edit', resource, resourceData)) {
            actions.push({ label: 'Edit', action: 'edit', variant: 'ghost' });
        }

        if (canPerformAction('delete', resource, resourceData)) {
            actions.push({ label: 'Delete', action: 'delete', variant: 'destructive' });
        }

        return actions;
    };

    /**
     * Check if user should see development/debug features
     */
    const shouldShowDebugFeatures: ComputedRef<boolean> = computed(() => {
        return auth.isSystemUser.value && (process.env.NODE_ENV === 'development' || permissions.hasRole('Super Admin'));
    });

    return {
        // Display utilities
        userDisplayNameWithRole,
        userAvatarOrInitials,
        userTypeThemeClass,
        userStatusVariant,

        // Navigation utilities
        filterNavigationByPermissions,

        // Action utilities
        canPerformAction,
        getContextualActions,

        // Development utilities
        shouldShowDebugFeatures,
    };
}
