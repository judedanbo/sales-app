<script setup lang="ts">
import PermissionGuard from '@/components/PermissionGuard.vue';
import UserInfo from '@/components/UserInfo.vue';
import { DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import { logout } from '@/routes';
import { index as auditsIndex } from '@/routes/audits';
import { edit } from '@/routes/profile';
import { index as usersIndex } from '@/routes/users';
import type { User } from '@/types';
import { Link, router } from '@inertiajs/vue3';
import { Activity, LogOut, Settings, Users } from 'lucide-vue-next';

interface Props {
    user: User;
}

const handleLogout = () => {
    router.flushAll();
};

defineProps<Props>();
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" :show-roles="true" :show-school="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full" :href="edit()" prefetch as="button">
                <Settings class="mr-2 h-4 w-4" />
                Settings
            </Link>
        </DropdownMenuItem>

        <!-- Quick navigation for privileged users -->
        <PermissionGuard permission="view_users">
            <DropdownMenuItem :as-child="true">
                <Link class="block w-full" :href="usersIndex()" prefetch as="button">
                    <Users class="mr-2 h-4 w-4" />
                    Manage Users
                </Link>
            </DropdownMenuItem>
        </PermissionGuard>

        <PermissionGuard permission="view_audit_trail">
            <DropdownMenuItem :as-child="true">
                <Link class="block w-full" :href="auditsIndex()" prefetch as="button">
                    <Activity class="mr-2 h-4 w-4" />
                    Audit Trail
                </Link>
            </DropdownMenuItem>
        </PermissionGuard>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link class="block w-full" :href="logout()" @click="handleLogout" as="button">
            <LogOut class="mr-2 h-4 w-4" />
            Log out
        </Link>
    </DropdownMenuItem>
</template>
