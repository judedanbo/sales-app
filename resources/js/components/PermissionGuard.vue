<script setup lang="ts">
import { useAuth } from '@/composables/useAuth';
import { usePermissions } from '@/composables/usePermissions';

interface Props {
    // Permission-based guards
    permission?: string;
    permissions?: string[];
    anyPermission?: string[];

    // Role-based guards
    role?: string;
    roles?: string[];
    anyRole?: string[];

    // User type guards
    userType?: string;
    userTypes?: string[];

    // System/School user guards
    systemUser?: boolean;
    schoolUser?: boolean;

    // Resource ownership guards
    ownResource?: boolean;
    resourceSchoolId?: number;
    requiresSchool?: boolean;

    // Status guards
    activeUser?: boolean;
    emailVerified?: boolean;

    // Inverse logic
    not?: boolean;

    // Fallback content
    fallback?: string;

    // Debug mode
    debug?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    not: false,
    debug: false,
});

const permissionUtils = usePermissions();
const auth = useAuth();

/**
 * Check if user meets permission requirements
 */
const hasRequiredPermissions = (): boolean => {
    // Single permission check
    if (props.permission) {
        return permissionUtils.hasPermission(props.permission);
    }

    // Multiple permissions (all required)
    if (props.permissions && props.permissions.length > 0) {
        return permissionUtils.hasAllPermissions(...props.permissions);
    }

    // Any permission (at least one required)
    if (props.anyPermission && props.anyPermission.length > 0) {
        return permissionUtils.hasAnyPermission(...props.anyPermission);
    }

    return true;
};

/**
 * Check if user meets role requirements
 */
const hasRequiredRoles = (): boolean => {
    // Single role check
    if (props.role) {
        return permissionUtils.hasRole(props.role);
    }

    // Multiple roles (all required)
    if (props.roles && props.roles.length > 0) {
        return permissionUtils.hasAllRoles(...props.roles);
    }

    // Any role (at least one required)
    if (props.anyRole && props.anyRole.length > 0) {
        return permissionUtils.hasAnyRole(...props.anyRole);
    }

    return true;
};

/**
 * Check if user meets user type requirements
 */
const hasRequiredUserType = (): boolean => {
    if (!auth.user) return false;
    // Single user type check
    if (props.userType) {
        return auth.userType === props.userType;
    }

    // Multiple user types
    if (props.userTypes && props.userTypes.length > 0) {
        return props.userTypes.includes(auth.userType || '');
    }

    return true;
};

/**
 * Check if user meets system/school requirements
 */
const hasSystemSchoolRequirements = (): boolean => {
    // System user requirement
    if (props.systemUser !== undefined) {
        if (props.systemUser && !auth.isSystemUser) return false;
        if (!props.systemUser && auth.isSystemUser) return false;
    }

    // School user requirement
    if (props.schoolUser !== undefined) {
        if (props.schoolUser && !auth.isSchoolUser) return false;
        if (!props.schoolUser && auth.isSchoolUser) return false;
    }

    return true;
};

/**
 * Check resource ownership requirements
 */
const hasResourceOwnership = (): boolean => {
    // Own resource check
    if (props.ownResource !== undefined && props.resourceSchoolId !== undefined) {
        return permissionUtils.ownsSchoolResource(props.resourceSchoolId);
    }

    // Requires school association
    if (props.requiresSchool) {
        return !!auth.schoolId;
    }

    return true;
};

/**
 * Check user status requirements
 */
const hasRequiredStatus = (): boolean => {
    // Active user requirement
    if (props.activeUser !== undefined) {
        if (props.activeUser && !auth.isActive) return false;
        if (!props.activeUser && auth.isActive) return false;
    }

    // Email verified requirement
    if (props.emailVerified !== undefined) {
        if (props.emailVerified && !auth.isEmailVerified) return false;
        if (!props.emailVerified && auth.isEmailVerified) return false;
    }

    return true;
};

/**
 * Main authorization check
 */
const isAuthorized = (): boolean => {
    if (!auth.user) return false;

    const checks = [
        hasRequiredPermissions(),
        hasRequiredRoles(),
        hasRequiredUserType(),
        // hasSystemSchoolRequirements(),
        // hasResourceOwnership(),
        // hasRequiredStatus(),
    ];

    const result = checks.every((check) => check);

    // Apply inverse logic if requested
    return props.not ? !result : result;
};

/**
 * Debug information
 */
const debugInfo = () => {
    if (!props.debug) return null;

    return {
        user: auth.user?.name,
        userType: auth.userType,
        isSystemUser: auth.isSystemUser,
        isSchoolUser: auth.isSchoolUser,
        schoolId: auth.schoolId,
        roles: auth.roles,
        permissions: auth.allPermissions,
        checks: {
            permissions: hasRequiredPermissions(),
            roles: hasRequiredRoles(),
            userType: hasRequiredUserType(),
            systemSchool: hasSystemSchoolRequirements(),
            ownership: hasResourceOwnership(),
            status: hasRequiredStatus(),
        },
        props: props,
        result: isAuthorized(),
    };
};

// Log debug info if requested
if (props.debug) {
    console.log('PermissionGuard Debug:', debugInfo());
}
</script>

<template>
    <div v-if="isAuthorized()">
        <slot />
    </div>
    <div v-else-if="fallback" class="text-sm text-muted-foreground">
        {{ fallback }}
    </div>
    <slot v-else name="fallback" />
</template>
