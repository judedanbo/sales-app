<script setup lang="ts">
import PermissionRoleModal from '@/components/permissions/PermissionRoleModal.vue';
import Badge from '@/components/ui/badge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import PageHeader from '@/components/ui/PageHeader.vue';
import Separator from '@/components/ui/separator/Separator.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as permissionsIndex, show } from '@/routes/permissions';
import { type BreadcrumbItem, type Permission, type Role, type User } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Key, Shield, ShieldCheck, Users2 } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    permission: Permission & {
        roles: Array<
            Role & {
                users: User[];
            }
        >;
        display_name: string;
        category: string;
        category_display: string;
        roles_count: number;
        users_count: number;
    };
    allRoles?: Role[];
}

const props = defineProps<Props>();

// Modal state
const isRoleModalOpen = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Permissions',
        href: permissionsIndex().url,
    },
    {
        title: props.permission.display_name || props.permission.name,
        href: show(props.permission.id).url,
    },
];

// Get category color based on permission category
const getCategoryColor = (category: string) => {
    const colors: Record<string, string> = {
        users: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        roles: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        schools: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        sales: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        products: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        reports: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
        system: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        inventory: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900 dark:text-cyan-200',
        audit: 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-200',
    };

    return colors[category.toLowerCase()] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
};

// Get guard variant for badge
const getGuardVariant = (guard: string) => {
    switch (guard) {
        case 'web':
            return 'default';
        case 'api':
            return 'secondary';
        default:
            return 'outline';
    }
};

// Format date helper
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Modal handlers
const handleManageRoles = () => {
    isRoleModalOpen.value = true;
};

const handleRolesUpdated = () => {
    // Refresh the permission data
    router.reload({
        only: ['permission'],
    });
};
</script>

<template>
    <Head :title="`Permission: ${permission.display_name || permission.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-8">
            <!-- Header Section -->
            <PageHeader
                :title="permission.display_name || permission.name"
                :description="`Permission details and role assignments for ${permission.name}`"
            >
                <template #badge>
                    <Badge :variant="getGuardVariant(permission.guard_name)" class="ml-2">
                        {{ permission.guard_name.toUpperCase() }}
                    </Badge>
                </template>
            </PageHeader>

            <!-- Permission Overview -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <!-- Basic Information -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Key class="h-5 w-5" />
                            Permission Information
                        </CardTitle>
                        <CardDescription>Basic details about this permission</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Permission Name</label>
                            <p class="rounded bg-muted p-2 font-mono text-sm">{{ permission.name }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Display Name</label>
                            <p class="text-sm">{{ permission.display_name || permission.name }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Category</label>
                            <Badge :class="getCategoryColor(permission.category)">
                                {{ permission.category_display || permission.category }}
                            </Badge>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Guard</label>
                            <Badge :variant="getGuardVariant(permission.guard_name)">
                                {{ permission.guard_name.toUpperCase() }}
                            </Badge>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Created</label>
                            <p class="text-sm">{{ formatDate(permission.created_at) }}</p>
                        </div>
                        <div v-if="permission.updated_at !== permission.created_at">
                            <label class="text-sm font-medium text-muted-foreground">Last Updated</label>
                            <p class="text-sm">{{ formatDate(permission.updated_at) }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Statistics -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <ShieldCheck class="h-5 w-5" />
                            Usage Statistics
                        </CardTitle>
                        <CardDescription>How this permission is being used</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">Assigned to Roles</span>
                            <span class="text-lg font-semibold">{{ permission.roles_count }}</span>
                        </div>
                        <Separator />
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">Total Users</span>
                            <span class="text-lg font-semibold">{{ permission.users_count }}</span>
                        </div>
                        <Separator />
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">Coverage</span>
                            <span class="text-lg font-semibold">
                                {{ permission.roles_count > 0 ? 'Active' : 'Unused' }}
                            </span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Quick Actions -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Shield class="h-5 w-5" />
                            Quick Actions
                        </CardTitle>
                        <CardDescription>Common management actions</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <Button variant="outline" size="sm" class="w-full justify-start" @click="handleManageRoles">
                            <Shield class="mr-2 h-4 w-4" />
                            Manage Role Assignments
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <!-- Roles Section -->
            <Card v-if="permission.roles && permission.roles.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Shield class="h-5 w-5" />
                        Assigned Roles
                    </CardTitle>
                    <CardDescription>
                        {{ permission.roles_count }} role{{ permission.roles_count !== 1 ? 's' : '' }} have this permission
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div v-for="role in permission.roles" :key="role.id" class="flex items-center justify-between rounded-md border p-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                                    <Shield class="h-5 w-5 text-primary" />
                                </div>
                                <div>
                                    <h4 class="font-medium">{{ role.display_name || role.name }}</h4>
                                    <p class="text-sm text-muted-foreground">{{ role.name }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="text-right">
                                    <p class="text-sm font-medium">{{ role.users.length }} user{{ role.users.length !== 1 ? 's' : '' }}</p>
                                    <Badge :variant="getGuardVariant(role.guard_name)" class="text-xs">
                                        {{ role.guard_name }}
                                    </Badge>
                                </div>
                                <Button variant="ghost" size="sm">
                                    <Shield class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- No Roles Message -->
            <Card v-else>
                <CardContent class="py-8 text-center">
                    <Shield class="mx-auto mb-4 h-12 w-12 text-muted-foreground/50" />
                    <h3 class="mb-2 text-lg font-semibold">No Roles Assigned</h3>
                    <p class="mb-4 text-muted-foreground">This permission hasn't been assigned to any roles yet.</p>
                    <Button variant="outline" disabled>
                        <Shield class="mr-2 h-4 w-4" />
                        Assign to Roles
                    </Button>
                </CardContent>
            </Card>

            <!-- Users with this Permission -->
            <Card v-if="permission.roles && permission.roles.some((role) => role.users.length > 0)">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Users2 class="h-5 w-5" />
                        Users with this Permission
                    </CardTitle>
                    <CardDescription>Recent users who have this permission through role assignments</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <template v-for="role in permission.roles" :key="role.id">
                            <div
                                v-for="user in role.users.slice(0, 5)"
                                :key="user.id"
                                class="flex items-center justify-between rounded-md border p-3"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10">
                                        <span class="text-sm font-medium">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ user.name }}</p>
                                        <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <Badge variant="outline" class="text-xs"> via {{ role.display_name || role.name }} </Badge>
                                    <p class="mt-1 text-xs text-muted-foreground" v-if="user.school">
                                        {{ user.school.school_name }}
                                    </p>
                                </div>
                            </div>
                        </template>

                        <div v-if="permission.users_count > 5" class="border-t pt-3 text-center">
                            <p class="text-sm text-muted-foreground">Showing 5 of {{ permission.users_count }} total users</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Role Management Modal -->
        <PermissionRoleModal
            :open="isRoleModalOpen"
            :permission="permission"
            :all-roles="allRoles"
            @update:open="isRoleModalOpen = $event"
            @roles-updated="handleRolesUpdated"
        />
    </AppLayout>
</template>
