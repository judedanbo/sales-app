<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import Badge from '@/components/ui/badge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import PageHeader from '@/components/ui/PageHeader.vue';
import { ScrollArea } from '@/components/ui/scroll-area';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem, Role, User } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Check, Shield, Trash2, UserCheck, UserMinus, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    user: User;
    availableRoles: Role[];
}

const props = defineProps<Props>();

// Local state
const isLoading = ref(false);
const selectedRolesToAdd = ref<string[]>([]);
const selectedRolesToRemove = ref<string[]>([]);

// Computed properties
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/users',
    },
    {
        title: props.user.name,
        href: `/users/${props.user.id}`,
    },
    {
        title: 'Roles',
        href: `/users/${props.user.id}/roles`,
    },
];

const currentRoles = computed(() => props.user.roles || []);
const userRoleNames = computed(() => currentRoles.value.map((role) => role.name));

const availableToAdd = computed(() => props.availableRoles.filter((role) => !userRoleNames.value.includes(role.name)));

const canAssignRoles = computed(() => selectedRolesToAdd.value.length > 0);
const canRemoveRoles = computed(() => selectedRolesToRemove.value.length > 0);

// Helper functions
function getUserInitials(name: string): string {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
}

function formatRoleName(name: string): string {
    return name
        .split('_')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

// Event handlers
async function assignSelectedRoles() {
    if (selectedRolesToAdd.value.length === 0) return;

    isLoading.value = true;

    try {
        for (const roleName of selectedRolesToAdd.value) {
            await router.post(
                `/users/${props.user.id}/assign-role`,
                {
                    role: roleName,
                },
                {
                    preserveScroll: true,
                },
            );
        }

        selectedRolesToAdd.value = [];
        router.reload({ only: ['user', 'availableRoles'] });
    } catch (error) {
        console.error('Error assigning roles:', error);
    } finally {
        isLoading.value = false;
    }
}

async function removeSelectedRoles() {
    if (selectedRolesToRemove.value.length === 0) return;

    isLoading.value = true;

    try {
        for (const roleName of selectedRolesToRemove.value) {
            await router.delete(`/users/${props.user.id}/remove-role`, {
                data: { role: roleName },
                preserveScroll: true,
            });
        }

        selectedRolesToRemove.value = [];
        router.reload({ only: ['user', 'availableRoles'] });
    } catch (error) {
        console.error('Error removing roles:', error);
    } finally {
        isLoading.value = false;
    }
}

async function quickRemoveRole(roleName: string) {
    isLoading.value = true;

    try {
        await router.delete(`/users/${props.user.id}/remove-role`, {
            data: { role: roleName },
            preserveScroll: true,
        });

        router.reload({ only: ['user', 'availableRoles'] });
    } catch (error) {
        console.error('Error removing role:', error);
    } finally {
        isLoading.value = false;
    }
}

async function quickAssignRole(roleName: string) {
    isLoading.value = true;

    try {
        await router.post(
            `/users/${props.user.id}/assign-role`,
            {
                role: roleName,
            },
            {
                preserveScroll: true,
            },
        );

        router.reload({ only: ['user', 'availableRoles'] });
    } catch (error) {
        console.error('Error assigning role:', error);
    } finally {
        isLoading.value = false;
    }
}

// Handle checkbox selections
function toggleRoleToAdd(roleName: string) {
    const index = selectedRolesToAdd.value.indexOf(roleName);
    if (index > -1) {
        selectedRolesToAdd.value.splice(index, 1);
    } else {
        selectedRolesToAdd.value.push(roleName);
    }
}

function toggleRoleToRemove(roleName: string) {
    const index = selectedRolesToRemove.value.indexOf(roleName);
    if (index > -1) {
        selectedRolesToRemove.value.splice(index, 1);
    } else {
        selectedRolesToRemove.value.push(roleName);
    }
}

function goBack() {
    router.visit('/users');
}
</script>

<template>
    <Head :title="`Manage Roles - ${user.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-8">
            <!-- Header Section -->
            <PageHeader :title="`Manage Roles`" :description="`Assign or remove roles for ${user.name}`">
                <template #action>
                    <Button @click="goBack" variant="outline">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to Users
                    </Button>
                </template>
            </PageHeader>

            <!-- User Profile Card -->
            <Card>
                <CardHeader>
                    <div class="flex items-center gap-4">
                        <Avatar class="h-16 w-16">
                            <AvatarImage :src="user.avatar" :alt="user.name" />
                            <AvatarFallback>{{ getUserInitials(user.name) }}</AvatarFallback>
                        </Avatar>
                        <div class="flex-1">
                            <CardTitle class="flex items-center gap-2">
                                {{ user.name }}
                                <span
                                    :class="[
                                        'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
                                        user.is_active
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                            : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    ]"
                                >
                                    <component :is="user.is_active ? UserCheck : UserMinus" class="mr-1 h-3 w-3" />
                                    {{ user.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </CardTitle>
                            <CardDescription class="mt-2 flex items-center gap-4">
                                <span>{{ user.email }}</span>
                                <Badge variant="secondary">
                                    {{ user.user_type_label || user.user_type }}
                                </Badge>
                                <span v-if="user.school" class="text-sm">
                                    {{ user.school.school_name }}
                                </span>
                            </CardDescription>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium">{{ currentRoles.length }} Roles Assigned</p>
                            <p class="text-xs text-muted-foreground">{{ availableToAdd.length }} Available</p>
                        </div>
                    </div>
                </CardHeader>
            </Card>

            <!-- Role Management Grid -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <!-- Current Roles -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="flex items-center gap-2">
                                    <Shield class="h-5 w-5" />
                                    Current Roles
                                </CardTitle>
                                <CardDescription>{{ currentRoles.length }} roles assigned</CardDescription>
                            </div>
                            <Button v-if="canRemoveRoles" @click="removeSelectedRoles" :disabled="isLoading" variant="destructive" size="sm">
                                <Trash2 class="mr-2 h-4 w-4" />
                                Remove Selected ({{ selectedRolesToRemove.length }})
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <ScrollArea class="h-96">
                            <div v-if="currentRoles.length === 0" class="py-12 text-center">
                                <Shield class="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                                <h3 class="mb-2 font-medium">No roles assigned</h3>
                                <p class="mb-4 text-sm text-muted-foreground">This user doesn't have any roles assigned yet.</p>
                                <p class="text-xs text-muted-foreground">Assign roles from the available roles section.</p>
                            </div>

                            <div v-else class="space-y-3">
                                <div
                                    v-for="role in currentRoles"
                                    :key="role.id"
                                    class="flex items-center justify-between rounded-lg border p-4 transition-colors hover:bg-muted/50"
                                >
                                    <div class="flex items-center gap-3">
                                        <Checkbox
                                            :model-value="selectedRolesToRemove.includes(role.name)"
                                            @update:model-value="() => toggleRoleToRemove(role.name)"
                                            :disabled="isLoading"
                                        />
                                        <div>
                                            <p class="font-medium">
                                                {{ role.display_name || formatRoleName(role.name) }}
                                            </p>
                                            <p class="text-sm text-muted-foreground">
                                                {{ role.guard_name }} guard
                                                <span v-if="role.permissions_count"> • {{ role.permissions_count }} permissions </span>
                                            </p>
                                        </div>
                                    </div>
                                    <Button
                                        @click="quickRemoveRole(role.name)"
                                        :disabled="isLoading"
                                        variant="ghost"
                                        size="sm"
                                        class="text-red-600 hover:text-red-700"
                                    >
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </ScrollArea>
                    </CardContent>
                </Card>

                <!-- Available Roles -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="flex items-center gap-2">
                                    <Shield class="h-5 w-5" />
                                    Available Roles
                                </CardTitle>
                                <CardDescription>{{ availableToAdd.length }} roles available to assign</CardDescription>
                            </div>
                            <Button v-if="canAssignRoles" @click="assignSelectedRoles" :disabled="isLoading" size="sm">
                                <Check class="mr-2 h-4 w-4" />
                                Assign Selected ({{ selectedRolesToAdd.length }})
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <ScrollArea class="h-96">
                            <div v-if="availableToAdd.length === 0" class="py-12 text-center">
                                <Check class="mx-auto mb-4 h-12 w-12 text-green-500" />
                                <h3 class="mb-2 font-medium">All roles assigned</h3>
                                <p class="mb-4 text-sm text-muted-foreground">This user has been assigned all available roles.</p>
                                <p class="text-xs text-muted-foreground">Remove roles from the current roles section if needed.</p>
                            </div>

                            <div v-else class="space-y-3">
                                <div
                                    v-for="role in availableToAdd"
                                    :key="role.id"
                                    class="flex items-center justify-between rounded-lg border p-4 transition-colors hover:bg-muted/50"
                                >
                                    <div class="flex items-center gap-3">
                                        <Checkbox
                                            :model-value="selectedRolesToAdd.includes(role.name)"
                                            @update:model-value="() => toggleRoleToAdd(role.name)"
                                            :disabled="isLoading"
                                        />
                                        <div>
                                            <p class="font-medium">
                                                {{ role.display_name || formatRoleName(role.name) }}
                                            </p>
                                            <p class="text-sm text-muted-foreground">
                                                {{ role.guard_name }} guard
                                                <span v-if="role.permissions_count"> • {{ role.permissions_count }} permissions </span>
                                            </p>
                                        </div>
                                    </div>
                                    <Button
                                        @click="quickAssignRole(role.name)"
                                        :disabled="isLoading"
                                        variant="ghost"
                                        size="sm"
                                        class="text-green-600 hover:text-green-700"
                                    >
                                        <Check class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </ScrollArea>
                    </CardContent>
                </Card>
            </div>

            <!-- Role Summary -->
            <Card>
                <CardHeader>
                    <CardTitle>Role Summary</CardTitle>
                    <CardDescription>Overview of current role assignments</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="rounded-lg bg-muted/50 p-4 text-center">
                            <p class="text-2xl font-bold">{{ currentRoles.length }}</p>
                            <p class="text-sm text-muted-foreground">Assigned Roles</p>
                        </div>
                        <div class="rounded-lg bg-muted/50 p-4 text-center">
                            <p class="text-2xl font-bold">{{ availableToAdd.length }}</p>
                            <p class="text-sm text-muted-foreground">Available Roles</p>
                        </div>
                        <div class="rounded-lg bg-muted/50 p-4 text-center">
                            <p class="text-2xl font-bold">{{ props.availableRoles.length }}</p>
                            <p class="text-sm text-muted-foreground">Total Roles</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
