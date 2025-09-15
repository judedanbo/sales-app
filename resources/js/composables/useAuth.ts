import type { AppPageProps, User } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed, type ComputedRef } from 'vue';

export function useAuth() {
    const page = usePage<AppPageProps>();

    const user: ComputedRef<User | null> = computed(() => {
        return page.props.auth?.user.data || null;
    });

    const isAuthenticated: ComputedRef<boolean> = computed(() => {
        return !!user.value;
    });

    const isSystemUser: ComputedRef<boolean> = computed(() => {
        return user.value?.is_system_user ?? false;
    });

    const isSchoolUser: ComputedRef<boolean> = computed(() => {
        return user.value?.is_school_user ?? false;
    });

    const userType: ComputedRef<string | undefined> = computed(() => {
        return user.value?.user_type;
    });

    const userTypeLabel: ComputedRef<string | undefined> = computed(() => {
        return user.value?.user_type_label;
    });

    const school: ComputedRef<any> = computed(() => {
        return user.value?.school;
    });

    const schoolId: ComputedRef<number | null> = computed(() => {
        return user.value?.school_id ?? null;
    });

    const displayName: ComputedRef<string> = computed(() => {
        return user.value?.display_name || user.value?.name || 'User';
    });

    const roles: ComputedRef<string[]> = computed(() => {
        return user.value?.roles?.map((role) => role.name) || [];
    });

    const allPermissions: ComputedRef<string[]> = computed(() => {
        return user.value?.all_permissions || [];
    });

    const isActive: ComputedRef<boolean> = computed(() => {
        return user.value?.is_active ?? false;
    });

    const isEmailVerified: ComputedRef<boolean> = computed(() => {
        return !!user.value?.email_verified_at;
    });

    const canManageSchools: ComputedRef<boolean> = computed(() => {
        return user.value?.can_manage_schools ?? false;
    });

    const canManageUsers: ComputedRef<boolean> = computed(() => {
        return user.value?.can_manage_users ?? false;
    });

    return {
        // User data
        user,

        // User state
        isAuthenticated,
        isSystemUser,
        isSchoolUser,
        isActive,
        isEmailVerified,

        // User properties
        userType,
        userTypeLabel,
        displayName,
        school,
        schoolId,

        // Permissions & roles
        roles,
        allPermissions,
        canManageSchools,
        canManageUsers,
    };
}
