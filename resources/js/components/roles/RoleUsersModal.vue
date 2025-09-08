<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import ScrollArea from '@/components/ui/scroll-area/ScrollArea.vue';
import { Separator } from '@/components/ui/separator';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { assignUsers, removeUsers } from '@/routes/roles';
import type { Role, User } from '@/types';
import { router } from '@inertiajs/vue3';
import { AlertCircle, Loader2, Search, UserCheck, UserMinus, UserPlus, Users2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    open: boolean;
    role?: Role & {
        users?: User[];
        display_name?: string;
        users_count?: number;
    };
    availableUsers?: User[];
}

interface Emits {
    'update:open': [open: boolean];
    'users-updated': [role: Role, availableUsers: User[]];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const processing = ref(false);
const errors = ref<Record<string, string>>({});
const searchQuery = ref('');
const selectedUsersToRemove = ref<Set<number>>(new Set());
const selectedUsersToAdd = ref<Set<number>>(new Set());
const activeTab = ref<'assigned' | 'available'>('assigned');

// Reset state when modal opens/closes
watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            errors.value = {};
            searchQuery.value = '';
            selectedUsersToRemove.value.clear();
            selectedUsersToAdd.value.clear();
            activeTab.value = 'assigned';
        }
    },
);

// Watch for role prop changes to clear selections when data is reloaded
watch(
    () => [props.role?.users, props.availableUsers],
    () => {
        // Clear selections when the underlying data changes (after router.reload)
        selectedUsersToRemove.value.clear();
        selectedUsersToAdd.value.clear();
    },
    { deep: true },
);

// Filter assigned users based on search
const filteredAssignedUsers = computed(() => {
    if (!props.role?.users) return [];

    const query = searchQuery.value.toLowerCase();
    if (!query) return props.role.users;

    return props.role.users.filter((user) => user.name.toLowerCase().includes(query) || user.email.toLowerCase().includes(query));
});

// Filter available users based on search (exclude already assigned users)
const filteredAvailableUsers = computed(() => {
    if (!props.availableUsers) return [];

    const assignedUserIds = new Set(props.role?.users?.map((u) => u.id) || []);
    const availableUsers = props.availableUsers.filter((user) => !assignedUserIds.has(user.id));

    const query = searchQuery.value.toLowerCase();
    if (!query) return availableUsers;

    return availableUsers.filter((user) => user.name.toLowerCase().includes(query) || user.email.toLowerCase().includes(query));
});

// Toggle user selection for removal
const toggleUserToRemove = (userId: number) => {
    if (selectedUsersToRemove.value.has(userId)) {
        selectedUsersToRemove.value.delete(userId);
    } else {
        selectedUsersToRemove.value.add(userId);
    }
};

// Toggle user selection for adding
const toggleUserToAdd = (userId: number) => {
    if (selectedUsersToAdd.value.has(userId)) {
        selectedUsersToAdd.value.delete(userId);
    } else {
        selectedUsersToAdd.value.add(userId);
    }
};

// Select all assigned users for removal
const selectAllAssigned = () => {
    selectedUsersToRemove.value = new Set(filteredAssignedUsers.value.map((u) => u.id));
};

// Clear all selected assigned users
const clearAllAssigned = () => {
    selectedUsersToRemove.value.clear();
};

// Select all available users for adding
const selectAllAvailable = () => {
    selectedUsersToAdd.value = new Set(filteredAvailableUsers.value.map((u) => u.id));
};

// Clear all selected available users
const clearAllAvailable = () => {
    selectedUsersToAdd.value.clear();
};

// Remove selected users from role
const handleRemoveUsers = () => {
    if (!props.role || selectedUsersToRemove.value.size === 0) return;

    processing.value = true;
    errors.value = {};

    const userIds = Array.from(selectedUsersToRemove.value);

    router.delete(removeUsers(props.role.id).url, {
        data: { user_ids: userIds },
        onSuccess: () => {
            processing.value = false;
            selectedUsersToRemove.value.clear();

            // Update role users by removing the selected users locally
            const updatedRole = {
                ...props.role!,
                users: props.role!.users?.filter((user) => !userIds.includes(user.id)) || [],
            };
            updatedRole.users_count = updatedRole.users.length;

            // Update available users by adding back the removed users
            const removedUsers = props.role!.users?.filter((user) => userIds.includes(user.id)) || [];
            const updatedAvailableUsers = [...(props.availableUsers || []), ...removedUsers];

            // Emit updated data to parent - no need for router.reload
            emit('users-updated', updatedRole, updatedAvailableUsers);
        },
        onError: (pageErrors: Record<string, string>) => {
            processing.value = false;
            errors.value = pageErrors;
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};

// Add selected users to role
const handleAssignUsers = () => {
    if (!props.role || selectedUsersToAdd.value.size === 0) return;

    processing.value = true;
    errors.value = {};

    const userIds = Array.from(selectedUsersToAdd.value);

    router.post(
        assignUsers(props.role.id).url,
        {
            user_ids: userIds,
        },
        {
            onSuccess: () => {
                processing.value = false;
                selectedUsersToAdd.value.clear();

                // Get the users that were just assigned
                const assignedUsers = (props.availableUsers || []).filter((user) => userIds.includes(user.id));

                // Update role users by adding the newly assigned users
                const updatedRole = {
                    ...props.role!,
                    users: [...(props.role!.users || []), ...assignedUsers],
                };
                updatedRole.users_count = updatedRole.users.length;

                // Update available users by removing the assigned users
                const updatedAvailableUsers = (props.availableUsers || []).filter((user) => !userIds.includes(user.id));

                // Emit updated data to parent - no need for router.reload
                emit('users-updated', updatedRole, updatedAvailableUsers);
            },
            onError: (pageErrors: Record<string, string>) => {
                processing.value = false;
                errors.value = pageErrors;
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
};

// Handle cancel
const handleCancel = () => {
    emit('update:open', false);
};

// Format date helper
const formatDate = (dateString?: string) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

// Get user initials for avatar
const getUserInitials = (name: string) => {
    return name
        .split(' ')
        .map((n) => n.charAt(0))
        .join('')
        .substring(0, 2)
        .toUpperCase();
};
</script>

<template>
    <Sheet :open="open" @update:open="emit('update:open', $event)">
        <SheetContent class="w-full sm:max-w-4xl">
            <SheetHeader>
                <SheetTitle class="flex items-center gap-2">
                    <Users2 class="h-5 w-5" />
                    Manage Users
                </SheetTitle>
                <SheetDescription>
                    Manage user assignments for <span class="font-semibold">{{ role?.display_name || role?.name }}</span>
                </SheetDescription>
            </SheetHeader>

            <div class="mt-6 space-y-4 px-8">
                <!-- Tab Navigation -->
                <div class="flex space-x-1 rounded-lg bg-muted p-1">
                    <button
                        :class="[
                            'flex-1 rounded-md px-3 py-2 text-sm font-medium transition-all',
                            activeTab === 'assigned' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground',
                        ]"
                        @click="activeTab = 'assigned'"
                        :disabled="processing"
                    >
                        <div class="flex items-center justify-center gap-2">
                            <UserCheck class="h-4 w-4" />
                            Assigned Users ({{ role?.users?.length || 0 }})
                        </div>
                    </button>
                    <button
                        :class="[
                            'flex-1 rounded-md px-3 py-2 text-sm font-medium transition-all',
                            activeTab === 'available' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground',
                        ]"
                        @click="activeTab = 'available'"
                        :disabled="processing"
                    >
                        <div class="flex items-center justify-center gap-2">
                            <UserPlus class="h-4 w-4" />
                            Available Users ({{ filteredAvailableUsers.length }})
                        </div>
                    </button>
                </div>

                <!-- Search Bar -->
                <div class="relative">
                    <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="searchQuery" placeholder="Search users by name or email..." class="pl-9" :disabled="processing" />
                </div>

                <!-- Assigned Users Tab -->
                <div v-if="activeTab === 'assigned'" class="space-y-4">
                    <!-- Action Bar -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                @click="selectAllAssigned"
                                :disabled="processing || filteredAssignedUsers.length === 0"
                            >
                                Select All
                            </Button>
                            <Button variant="outline" size="sm" @click="clearAllAssigned" :disabled="processing || selectedUsersToRemove.size === 0">
                                Clear All
                            </Button>
                        </div>
                        <Button variant="destructive" size="sm" @click="handleRemoveUsers" :disabled="processing || selectedUsersToRemove.size === 0">
                            <Loader2 v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
                            <UserMinus v-else class="mr-2 h-4 w-4" />
                            Remove Selected ({{ selectedUsersToRemove.size }})
                        </Button>
                    </div>

                    <!-- Users List -->
                    <ScrollArea class="h-[400px]">
                        <div class="space-y-2">
                            <div
                                v-for="user in filteredAssignedUsers"
                                :key="user.id"
                                class="flex items-center gap-3 rounded-lg border p-3 transition-colors hover:bg-muted/50"
                            >
                                <Checkbox
                                    :model-value="selectedUsersToRemove.has(user.id)"
                                    @update:model-value="() => toggleUserToRemove(user.id)"
                                    :disabled="processing"
                                />

                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                                    <span class="text-sm font-medium text-primary">
                                        {{ getUserInitials(user.name) }}
                                    </span>
                                </div>

                                <div class="flex-1 space-y-1">
                                    <div class="flex items-center gap-2">
                                        <p class="font-medium">{{ user.name }}</p>
                                        <span
                                            :class="[
                                                'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                                user.is_active
                                                    ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                                                    : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
                                            ]"
                                        >
                                            {{ user.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                                    <div class="flex items-center gap-4 text-xs text-muted-foreground">
                                        <span v-if="user.user_type">Type: {{ user.user_type }}</span>
                                        <span v-if="user.school">School: {{ user.school.school_name }}</span>
                                        <span>Joined: {{ formatDate(user.created_at) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- No Users Message -->
                            <div v-if="filteredAssignedUsers.length === 0" class="flex flex-col items-center justify-center py-8 text-center">
                                <UserCheck class="mb-3 h-12 w-12 text-muted-foreground/50" />
                                <p class="text-sm text-muted-foreground">
                                    {{ searchQuery ? 'No assigned users found matching your search' : 'No users assigned to this role yet' }}
                                </p>
                            </div>
                        </div>
                    </ScrollArea>
                </div>

                <!-- Available Users Tab -->
                <div v-if="activeTab === 'available'" class="space-y-4">
                    <!-- Action Bar -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                @click="selectAllAvailable"
                                :disabled="processing || filteredAvailableUsers.length === 0"
                            >
                                Select All
                            </Button>
                            <Button variant="outline" size="sm" @click="clearAllAvailable" :disabled="processing || selectedUsersToAdd.size === 0">
                                Clear All
                            </Button>
                        </div>
                        <Button @click="handleAssignUsers" :disabled="processing || selectedUsersToAdd.size === 0">
                            <Loader2 v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
                            <UserPlus v-else class="mr-2 h-4 w-4" />
                            Assign Selected ({{ selectedUsersToAdd.size }})
                        </Button>
                    </div>

                    <!-- Users List -->
                    <ScrollArea class="h-[400px]">
                        <div class="space-y-2">
                            <div
                                v-for="user in filteredAvailableUsers"
                                :key="user.id"
                                class="flex items-center gap-3 rounded-lg border p-3 transition-colors hover:bg-muted/50"
                            >
                                <Checkbox
                                    :model-value="selectedUsersToAdd.has(user.id)"
                                    @update:model-value="() => toggleUserToAdd(user.id)"
                                    :disabled="processing"
                                />

                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                                    <span class="text-sm font-medium text-primary">
                                        {{ getUserInitials(user.name) }}
                                    </span>
                                </div>

                                <div class="flex-1 space-y-1">
                                    <div class="flex items-center gap-2">
                                        <p class="font-medium">{{ user.name }}</p>
                                        <span
                                            :class="[
                                                'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                                user.is_active
                                                    ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'
                                                    : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
                                            ]"
                                        >
                                            {{ user.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                                    <div class="flex items-center gap-4 text-xs text-muted-foreground">
                                        <span v-if="user.user_type">Type: {{ user.user_type }}</span>
                                        <span v-if="user.school">School: {{ user.school.school_name }}</span>
                                        <span>Joined: {{ formatDate(user.created_at) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- No Users Message -->
                            <div v-if="filteredAvailableUsers.length === 0" class="flex flex-col items-center justify-center py-8 text-center">
                                <UserPlus class="mb-3 h-12 w-12 text-muted-foreground/50" />
                                <p class="text-sm text-muted-foreground">
                                    {{
                                        searchQuery ? 'No available users found matching your search' : 'All users are already assigned to this role'
                                    }}
                                </p>
                            </div>
                        </div>
                    </ScrollArea>
                </div>

                <!-- Errors -->
                <div v-if="errors.user_ids" class="flex items-center gap-2 text-sm text-destructive">
                    <AlertCircle class="h-4 w-4" />
                    {{ errors.user_ids }}
                </div>

                <!-- Summary -->
                <Separator />

                <div class="rounded-lg bg-muted/50 p-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="font-medium">Role Summary</span>
                        <div class="flex items-center gap-4 text-muted-foreground">
                            <span
                                >Total Assigned: <span class="font-medium text-foreground">{{ role?.users?.length || 0 }}</span></span
                            >
                            <span
                                >Available: <span class="font-medium text-foreground">{{ filteredAvailableUsers.length }}</span></span
                            >
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 border-t pt-4">
                    <Button type="button" variant="outline" @click="handleCancel" :disabled="processing" class="flex-1"> Cancel </Button>
                </div>
            </div>
        </SheetContent>
    </Sheet>
</template>
