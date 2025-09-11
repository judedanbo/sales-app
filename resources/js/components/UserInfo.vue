<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { useAuthUtils } from '@/composables/useAuthUtils';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';
import { computed } from 'vue';

interface Props {
    user: User;
    showEmail?: boolean;
    showStatus?: boolean;
    showRoles?: boolean;
    showSchool?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
    showStatus: false,
    showRoles: false,
    showSchool: false,
});

const { getInitials } = useInitials();
const { userStatusVariant } = useAuthUtils();

// Compute whether we should show the avatar image
const showAvatar = computed(() => props.user.avatar && props.user.avatar !== '');

// User status information
const userStatusLabel = computed(() => {
    if (!props.user.is_active) return 'Inactive';
    if (!props.user.email_verified_at) return 'Unverified';
    return 'Active';
});

const userRoleNames = computed(() => {
    return props.user.roles?.map((role) => role.name) || [];
});

const userSchoolName = computed(() => {
    return props.user.school?.name || null;
});
</script>

<template>
    <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
        <AvatarImage v-if="showAvatar" :src="user.avatar!" :alt="user.name" />
        <AvatarFallback class="rounded-lg text-black dark:text-white">
            {{ getInitials(user.name) }}
        </AvatarFallback>
    </Avatar>

    <div class="grid flex-1 text-left text-sm leading-tight">
        <div class="flex items-center gap-2">
            <span class="truncate font-medium">{{ user.name }}</span>
            <Badge v-if="showStatus" :variant="userStatusVariant" class="px-1 py-0 text-xs">
                {{ userStatusLabel }}
            </Badge>
        </div>

        <div class="space-y-1">
            <span v-if="showEmail" class="block truncate text-xs text-muted-foreground">{{ user.email }}</span>

            <div v-if="showRoles && userRoleNames.length > 0" class="flex flex-wrap gap-1">
                <Badge v-for="role in userRoleNames" :key="role" variant="outline" class="px-1 py-0 text-xs">
                    {{ role }}
                </Badge>
            </div>

            <span v-if="showSchool && userSchoolName" class="block truncate text-xs text-muted-foreground"> 📍 {{ userSchoolName }} </span>
        </div>
    </div>
</template>
