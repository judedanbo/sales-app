<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import Badge from '@/components/ui/badge.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
// import ScrollArea from '@/components/ui/scroll-area/ScrollArea.vue';
import { Separator } from '@/components/ui/separator';
import type { Role, User } from '@/types';
import { router } from '@inertiajs/vue3';
import { Check, Shield, UserCheck, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    open: boolean;
    user: User | null;
    availableRoles: Role[];
}

interface Emits {
    (e: 'update:open', value: boolean): void;
    (e: 'role-updated', user: User): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Local state
const isLoading = ref(false);
const selectedRolesToAdd = ref<string[]>([]);
const selectedRolesToRemove = ref<string[]>([]);
const localUser = ref<User | null>(null);

// Computed properties
const currentRoles = computed(() => localUser.value?.roles || []);
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

// Reset selections when modal opens/closes
watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            selectedRolesToAdd.value = [];
            selectedRolesToRemove.value = [];
            // Create a local copy of the user
            localUser.value = props.user ? { ...props.user, roles: [...(props.user.roles || [])] } : null;
        }
    },
);

// Update local user when props.user changes
watch(
    () => props.user,
    (newUser) => {
        if (newUser && props.open) {
            localUser.value = { ...newUser, roles: [...(newUser.roles || [])] };
        }
    },
    { deep: true },
);

// Event handlers
function handleClose() {
    emit('update:open', false);
}

async function assignSelectedRoles() {
    if (!props.user || selectedRolesToAdd.value.length === 0) return;

    isLoading.value = true;

    try {
        const rolesToAssign = [...selectedRolesToAdd.value];

        for (const roleName of rolesToAssign) {
            await router.post(
                `/users/${props.user.id}/assign-role`,
                {
                    role: roleName,
                },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        // Move role from available to current in local copy
                        const roleToMove = props.availableRoles.find((r) => r.name === roleName);
                        if (roleToMove && localUser.value) {
                            // Add to user's roles
                            if (!localUser.value.roles) {
                                localUser.value.roles = [];
                            }
                            localUser.value.roles.push(roleToMove);
                        }
                        // Remove from selected list
                        selectedRolesToAdd.value = selectedRolesToAdd.value.filter((r) => r !== roleName);
                    },
                },
            );
        }

        // Refresh user data to ensure consistency
        router.reload({
            only: ['users'],
            onSuccess: () => {
                if (props.user) {
                    emit('role-updated', props.user);
                }
            },
        });
    } catch (error) {
        console.error('Error assigning roles:', error);
    } finally {
        isLoading.value = false;
    }
}

async function removeSelectedRoles() {
    if (!props.user || selectedRolesToRemove.value.length === 0) return;

    isLoading.value = true;

    try {
        const rolesToRemove = [...selectedRolesToRemove.value];

        for (const roleName of rolesToRemove) {
            await router.delete(`/users/${props.user.id}/remove-role`, {
                data: { role: roleName },
                preserveScroll: true,
                onSuccess: () => {
                    // Remove from user's current roles in local copy
                    if (localUser.value && localUser.value.roles) {
                        localUser.value.roles = localUser.value.roles.filter((r) => r.name !== roleName);
                    }
                    // Clear from selection
                    selectedRolesToRemove.value = selectedRolesToRemove.value.filter((r) => r !== roleName);
                },
            });
        }

        // Refresh user data to ensure consistency
        router.reload({
            only: ['users'],
            onSuccess: () => {
                if (props.user) {
                    emit('role-updated', props.user);
                }
            },
        });
    } catch (error) {
        console.error('Error removing roles:', error);
    } finally {
        isLoading.value = false;
    }
}

async function quickRemoveRole(roleName: string) {
    if (!props.user) return;

    isLoading.value = true;

    try {
        await router.delete(`/users/${props.user.id}/remove-role`, {
            data: { role: roleName },
            preserveScroll: true,
            onSuccess: () => {
                // Remove from user's current roles immediately in local copy
                if (localUser.value && localUser.value.roles) {
                    localUser.value.roles = localUser.value.roles.filter((r) => r.name !== roleName);
                }
            },
        });

        // Refresh user data to ensure consistency
        router.reload({
            only: ['users'],
            onSuccess: () => {
                if (props.user) {
                    emit('role-updated', props.user);
                }
            },
        });
    } catch (error) {
        console.error('Error removing role:', error);
    } finally {
        isLoading.value = false;
    }
}

async function quickAssignRole(roleName: string) {
    if (!props.user) return;

    isLoading.value = true;

    try {
        await router.post(
            `/users/${props.user.id}/assign-role`,
            {
                role: roleName,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    // Move role from available to current immediately in local copy
                    const roleToMove = props.availableRoles.find((r) => r.name === roleName);
                    if (roleToMove && localUser.value) {
                        // Add to user's roles
                        if (!localUser.value.roles) {
                            localUser.value.roles = [];
                        }
                        localUser.value.roles.push(roleToMove);
                    }
                },
            },
        );

        // Refresh user data to ensure consistency
        router.reload({
            only: ['users'],
            onSuccess: () => {
                if (props.user) {
                    emit('role-updated', props.user);
                }
            },
        });
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
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent class="max-h-[80vh] max-w-4xl">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-3">
                    <Shield class="h-5 w-5" />
                    Manage User Roles
                </DialogTitle>
                <DialogDescription v-if="localUser"> Assign or remove roles for {{ localUser.name }} </DialogDescription>
            </DialogHeader>

            <div v-if="localUser" class="space-y-6">
                <!-- User Info Header -->
                <div class="flex items-center gap-4 rounded-lg bg-muted/50 p-4">
                    <Avatar class="h-12 w-12">
                        <AvatarImage :src="localUser.avatar" :alt="localUser.name" />
                        <AvatarFallback>{{ getUserInitials(localUser.name) }}</AvatarFallback>
                    </Avatar>
                    <div>
                        <h3 class="font-semibold">{{ localUser.name }}</h3>
                        <p class="text-sm text-muted-foreground">{{ localUser.email }}</p>
                        <div class="mt-1 flex items-center gap-2">
                            <Badge variant="secondary" class="text-xs">
                                {{ localUser.user_type_label || localUser.user_type }}
                            </Badge>
                            <span
                                :class="[
                                    'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
                                    localUser.is_active
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                        : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                ]"
                            >
                                <UserCheck v-if="localUser.is_active" class="mr-1 h-3 w-3" />
                                {{ localUser.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Two Column Layout -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- Current Roles -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h4 class="font-medium">Current Roles ({{ currentRoles.length }})</h4>
                            <Button v-if="canRemoveRoles" @click="removeSelectedRoles" :disabled="isLoading" variant="destructive" size="sm">
                                Remove Selected ({{ selectedRolesToRemove.length }})
                            </Button>
                        </div>

                        <div class="h-64 overflow-y-auto rounded-md border p-4">
                            <div v-if="currentRoles.length === 0" class="py-8 text-center">
                                <Shield class="mx-auto mb-2 h-8 w-8 text-muted-foreground" />
                                <p class="text-sm text-muted-foreground">No roles assigned</p>
                            </div>

                            <div v-else class="space-y-2">
                                <div
                                    v-for="role in currentRoles"
                                    :key="role.id"
                                    class="flex items-center justify-between rounded-lg border p-3 hover:bg-muted/50"
                                >
                                    <div class="flex items-center gap-3">
                                        <Checkbox
                                            :model-value="selectedRolesToRemove.includes(role.name)"
                                            @update:model-value="() => toggleRoleToRemove(role.name)"
                                            :disabled="isLoading"
                                        />
                                        <div>
                                            <p class="text-sm font-medium">
                                                {{ role.display_name || formatRoleName(role.name) }}
                                            </p>
                                            <p class="text-xs text-muted-foreground">{{ role.guard_name }} guard</p>
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
                        </div>
                    </div>

                    <!-- Available Roles -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h4 class="font-medium">Available Roles ({{ availableToAdd.length }})</h4>
                            <Button v-if="canAssignRoles" @click="assignSelectedRoles" :disabled="isLoading" size="sm">
                                Assign Selected ({{ selectedRolesToAdd.length }})
                            </Button>
                        </div>

                        <div class="h-64 overflow-y-auto rounded-md border p-4">
                            <div v-if="availableToAdd.length === 0" class="py-8 text-center">
                                <Check class="mx-auto mb-2 h-8 w-8 text-green-500" />
                                <p class="text-sm text-muted-foreground">All roles assigned</p>
                            </div>

                            <div v-else class="space-y-2">
                                <div
                                    v-for="role in availableToAdd"
                                    :key="role.id"
                                    class="flex items-center justify-between rounded-lg border p-3 hover:bg-muted/50"
                                >
                                    <div class="flex items-center gap-3">
                                        <Checkbox
                                            :model-value="selectedRolesToAdd.includes(role.name)"
                                            @update:model-value="() => toggleRoleToAdd(role.name)"
                                            :disabled="isLoading"
                                        />
                                        <div>
                                            <p class="text-sm font-medium">
                                                {{ role.display_name || formatRoleName(role.name) }}
                                            </p>
                                            <p class="text-xs text-muted-foreground">{{ role.guard_name }} guard</p>
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
                        </div>
                    </div>
                </div>
            </div>

            <Separator />

            <DialogFooter>
                <Button variant="outline" @click="handleClose" :disabled="isLoading"> Close </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
