<script setup lang="ts">
import RoleEditModal from '@/components/roles/RoleEditModal.vue';
import RolePermissionsModal from '@/components/roles/RolePermissionsModal.vue';
import RoleUsersModal from '@/components/roles/RoleUsersModal.vue';
import Badge from '@/components/ui/badge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import PageHeader from '@/components/ui/PageHeader.vue';
import Separator from '@/components/ui/separator/Separator.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { destroy, index as rolesIndex, show } from '@/routes/roles';
import { type BreadcrumbItem, type Permission, type Role, type User } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Edit, Settings, Shield, ShieldCheck, Trash2, Users2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface PermissionGroup {
    group: string;
    permissions: {
        id: number;
        name: string;
        display_name: string;
    }[];
}

interface Props {
    role: Role & {
        users: User[];
        permissions: Permission[];
        display_name: string;
        users_count: number;
        permissions_count: number;
    };
    permissionGroups: PermissionGroup[];
    guardNames?: string[];
    allPermissions?: Permission[];
    availableUsers?: User[];
}

const props = defineProps<Props>();

const isDeleting = ref(false);
const isEditModalOpen = ref(false);
const isPermissionsModalOpen = ref(false);
const isUsersModalOpen = ref(false);
const currentRole = ref(props.role);
const availableUsers = ref(props.availableUsers || []);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Roles',
        href: rolesIndex().url,
    },
    {
        title: props.role.display_name || props.role.name,
        href: show(props.role.id).url,
    },
];

// Computed properties
const hasPermissions = computed(() => props.permissionGroups && props.permissionGroups.length > 0);
const hasUsers = computed(() => props.role.users && props.role.users.length > 0);

// Event handlers
const handleEdit = () => {
    isEditModalOpen.value = true;
};

const handleRoleUpdated = (updatedRole: Role) => {
    // Update the current role data
    currentRole.value = { ...currentRole.value, ...updatedRole };
    // Refresh the page to get all updated data including relationships
    router.reload({
        only: ['role', 'permissionGroups'],
    });
};

const handleManagePermissions = () => {
    isPermissionsModalOpen.value = true;
};

const handlePermissionsUpdated = (updatedRole: Role) => {
    // Update the current role data
    currentRole.value = { ...currentRole.value, ...updatedRole };
    // Refresh the page to get all updated data
    router.reload({
        only: ['role', 'permissionGroups'],
    });
};

const handleManageUsers = () => {
    isUsersModalOpen.value = true;
};

const handleUsersUpdated = (updatedRole: Role, updatedAvailableUsers: User[]) => {
    // Update the current role data with the latest information including users
    currentRole.value = { ...currentRole.value, ...updatedRole };
    // Update available users array
    availableUsers.value = updatedAvailableUsers;
    // Close the modal
};

const handleDelete = () => {
    const confirmMessage = `Are you sure you want to delete the role "${props.role.display_name || props.role.name}"?`;

    if (props.role.users_count > 0) {
        alert(
            `Cannot delete role "${props.role.display_name || props.role.name}" because it has ${props.role.users_count} user(s) assigned to it. Please remove all users from this role before deleting.`,
        );
        return;
    }

    if (confirm(confirmMessage)) {
        isDeleting.value = true;
        router.delete(destroy(props.role.id).url, {
            onFinish: () => {
                isDeleting.value = false;
            },
        });
    }
};

// Format guard name
const formatGuardName = (guard: string) => {
    return guard.charAt(0).toUpperCase() + guard.slice(1);
};

// Get badge variant for guard
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

// Get permission category color
const getCategoryColor = (category: string) => {
    const colors: Record<string, string> = {
        Users: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        Roles: 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        Schools: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        Sales: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        Products: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        Reports: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200',
        System: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        Other: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
    };

    return colors[category] || colors['Other'];
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
</script>

<template>
    <Head :title="`Role: ${role.display_name || role.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-8">
            <!-- Header Section -->
            <PageHeader :title="role.display_name || role.name" :description="`Role details and management for ${role.name}`">
                <template #badge>
                    <Badge :variant="getGuardVariant(role.guard_name)" class="ml-2">
                        {{ formatGuardName(role.guard_name) }}
                    </Badge>
                </template>
                <template #action>
                    <div class="flex gap-2">
                        <Button variant="outline" size="sm" @click="handleEdit">
                            <Edit class="mr-2 h-4 w-4" />
                            Edit
                        </Button>
                        <Button variant="outline" size="sm" @click="handleManagePermissions">
                            <Settings class="mr-2 h-4 w-4" />
                            Permissions
                        </Button>
                        <Button variant="outline" size="sm" @click="handleManageUsers">
                            <Users2 class="mr-2 h-4 w-4" />
                            Users ({{ role.users_count }})
                        </Button>
                        <Button variant="destructive" size="sm" @click="handleDelete" :disabled="isDeleting || role.users_count > 0">
                            <Trash2 class="mr-2 h-4 w-4" />
                            Delete
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <!-- Role Overview -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <!-- Basic Information -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Shield class="h-5 w-5" />
                            Role Information
                        </CardTitle>
                        <CardDescription> Basic details about this role </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Role Name</label>
                            <p class="rounded bg-muted p-2 font-mono text-sm">{{ role.name }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Display Name</label>
                            <p class="text-sm">{{ role.display_name || role.name }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Guard</label>
                            <Badge :variant="getGuardVariant(role.guard_name)">
                                {{ formatGuardName(role.guard_name) }}
                            </Badge>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-muted-foreground">Created</label>
                            <p class="text-sm">{{ formatDate(role.created_at) }}</p>
                        </div>
                        <div v-if="role.updated_at !== role.created_at">
                            <label class="text-sm font-medium text-muted-foreground">Last Updated</label>
                            <p class="text-sm">{{ formatDate(role.updated_at) }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Statistics -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <ShieldCheck class="h-5 w-5" />
                            Statistics
                        </CardTitle>
                        <CardDescription> Usage statistics for this role </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">Permissions</span>
                            <span class="text-lg font-semibold">{{ role.permissions_count }}</span>
                        </div>
                        <Separator />
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">Assigned Users</span>
                            <span class="text-lg font-semibold">{{ role.users_count }}</span>
                        </div>
                        <Separator />
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">Permission Groups</span>
                            <span class="text-lg font-semibold">{{ permissionGroups.length }}</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Quick Actions -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Settings class="h-5 w-5" />
                            Quick Actions
                        </CardTitle>
                        <CardDescription> Common management actions </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <Button variant="outline" size="sm" class="w-full justify-start" @click="handleEdit">
                            <Edit class="mr-2 h-4 w-4" />
                            Edit Role Details
                        </Button>
                        <Button variant="outline" size="sm" class="w-full justify-start" @click="handleManagePermissions">
                            <Shield class="mr-2 h-4 w-4" />
                            Manage Permissions
                        </Button>
                        <Button variant="outline" size="sm" class="w-full justify-start" @click="handleManageUsers">
                            <Users2 class="mr-2 h-4 w-4" />
                            Manage Users
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <!-- Permissions Section -->
            <Card v-if="hasPermissions">
                <CardHeader>
                    <CardTitle>Assigned Permissions</CardTitle>
                    <CardDescription> {{ role.permissions_count }} permissions across {{ permissionGroups.length }} categories </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-6">
                        <div v-for="group in permissionGroups" :key="group.group" class="space-y-3">
                            <div class="flex items-center gap-2">
                                <Badge variant="secondary" :class="getCategoryColor(group.group)">
                                    {{ group.group }}
                                </Badge>
                                <span class="text-sm text-muted-foreground"> {{ group.permissions.length }} permission(s) </span>
                            </div>
                            <div class="ml-4 grid grid-cols-1 gap-2 md:grid-cols-2 lg:grid-cols-3">
                                <div
                                    v-for="permission in group.permissions"
                                    :key="permission.name"
                                    class="flex items-center gap-2 rounded-md bg-muted/50 p-2"
                                >
                                    <ShieldCheck class="h-3 w-3 text-green-500" />
                                    <span class="text-sm">{{ permission.display_name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- No Permissions Message -->
            <Card v-else>
                <CardContent class="py-8 text-center">
                    <Shield class="mx-auto mb-4 h-12 w-12 text-muted-foreground/50" />
                    <h3 class="mb-2 text-lg font-semibold">No Permissions Assigned</h3>
                    <p class="mb-4 text-muted-foreground">This role doesn't have any permissions assigned yet.</p>
                    <Button variant="outline" @click="handleManagePermissions">
                        <Settings class="mr-2 h-4 w-4" />
                        Assign Permissions
                    </Button>
                </CardContent>
            </Card>

            <!-- Users Section -->
            <Card v-if="hasUsers">
                <CardHeader>
                    <CardTitle class="flex items-center justify-between">
                        <span class="flex items-center gap-2">
                            <Users2 class="h-5 w-5" />
                            Assigned Users
                        </span>
                        <Button variant="outline" size="sm" @click="handleManageUsers"> View All ({{ role.users_count }}) </Button>
                    </CardTitle>
                    <CardDescription> Recent users assigned to this role </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-3">
                        <div v-for="user in role.users.slice(0, 10)" :key="user.id" class="flex items-center justify-between rounded-md border p-3">
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
                                <Badge :variant="user.is_active ? 'default' : 'secondary'">
                                    {{ user.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                                <p class="mt-1 text-xs text-muted-foreground" v-if="user.school">
                                    {{ user.school.school_name }}
                                </p>
                            </div>
                        </div>

                        <div v-if="role.users_count > 10" class="border-t pt-3 text-center">
                            <Button variant="ghost" @click="handleManageUsers"> View {{ role.users_count - 10 }} more users </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- No Users Message -->
            <Card v-else>
                <CardContent class="py-8 text-center">
                    <Users2 class="mx-auto mb-4 h-12 w-12 text-muted-foreground/50" />
                    <h3 class="mb-2 text-lg font-semibold">No Users Assigned</h3>
                    <p class="mb-4 text-muted-foreground">This role hasn't been assigned to any users yet.</p>
                    <Button variant="outline" @click="handleManageUsers">
                        <Users2 class="mr-2 h-4 w-4" />
                        Manage User Assignments
                    </Button>
                </CardContent>
            </Card>
        </div>

        <!-- Edit Role Modal -->
        <RoleEditModal
            :open="isEditModalOpen"
            :role="currentRole"
            :guard-names="guardNames"
            @update:open="isEditModalOpen = $event"
            @role-updated="handleRoleUpdated"
        />

        <!-- Manage Permissions Modal -->
        <RolePermissionsModal
            :open="isPermissionsModalOpen"
            :role="currentRole"
            :all-permissions="allPermissions"
            @update:open="isPermissionsModalOpen = $event"
            @permissions-updated="handlePermissionsUpdated"
        />

        <!-- Manage Users Modal -->
        <RoleUsersModal
            :open="isUsersModalOpen"
            :role="currentRole"
            :available-users="availableUsers"
            @update:open="isUsersModalOpen = $event"
            @users-updated="handleUsersUpdated"
        />
    </AppLayout>
</template>
