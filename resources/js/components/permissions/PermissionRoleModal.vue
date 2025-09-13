<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import ScrollArea from '@/components/ui/scroll-area/ScrollArea.vue';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { useAlerts } from '@/composables/useAlerts';
import type { Permission, Role } from '@/types';
import { AlertCircle, ChevronDown, ChevronRight, Loader2, Search, Shield, ShieldCheck, ShieldOff } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    open: boolean;
    permission?: Permission & {
        roles?: Role[];
        display_name?: string;
    };
    allRoles?: Role[];
}

interface Emits {
    'update:open': [open: boolean];
    'roles-updated': [permission: Permission];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();
const { error, success } = useAlerts();

const processing = ref(false);
const errors = ref<Record<string, string>>({});
const searchQuery = ref('');
const expandedGroups = ref<Set<string>>(new Set());
const selectedRoles = ref<Set<number>>(new Set());

// Initialize selected roles when permission changes
watch(
    () => props.permission,
    (permission) => {
        if (permission?.roles) {
            selectedRoles.value = new Set(permission.roles.map((r) => r.id));
        } else {
            selectedRoles.value = new Set();
        }
    },
    { immediate: true },
);

// Reset state when modal opens/closes
watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            errors.value = {};
            searchQuery.value = '';
            expandedGroups.value.clear();
        } else {
            // Expand all groups by default when opening
            if (roleGroups.value) {
                expandedGroups.value = new Set(roleGroups.value.map((g) => g.guard).filter((g): g is string => g !== undefined));
            }
        }
    },
);

// Group roles by guard
const roleGroups = computed(() => {
    if (!props.allRoles) return [];

    const groups: Record<string, { guard: string; display_name: string; roles: Role[]; count: number }> = {};

    props.allRoles.forEach((role) => {
        const guard = role.guard_name || 'web';

        if (!groups[guard]) {
            groups[guard] = {
                guard: guard,
                display_name: guard.charAt(0).toUpperCase() + guard.slice(1),
                roles: [],
                count: 0,
            };
        }

        groups[guard].roles.push(role);
        groups[guard].count++;
    });

    return Object.values(groups).sort((a, b) => a.guard.localeCompare(b.guard));
});

// Filter roles based on search
const filteredGroups = computed(() => {
    if (!searchQuery.value) return roleGroups.value;

    const query = searchQuery.value.toLowerCase();

    return roleGroups.value
        .map((group) => ({
            ...group,
            roles: group.roles.filter(
                (r) => r.name.toLowerCase().includes(query) || (r.display_name && r.display_name.toLowerCase().includes(query)),
            ),
            count: group.roles.filter((r) => r.name.toLowerCase().includes(query) || (r.display_name && r.display_name.toLowerCase().includes(query)))
                .length,
        }))
        .filter((group) => group.count > 0);
});

// Toggle group expansion
const toggleGroup = (guard: string) => {
    if (expandedGroups.value.has(guard)) {
        expandedGroups.value.delete(guard);
    } else {
        expandedGroups.value.add(guard);
    }
};

// Toggle role selection
const toggleRole = (roleId: number) => {
    if (selectedRoles.value.has(roleId)) {
        selectedRoles.value.delete(roleId);
    } else {
        selectedRoles.value.add(roleId);
    }
};

// Toggle all roles in a group
const toggleGroupRoles = (group: { guard: string; roles: Role[] }) => {
    const groupRoleIds = group.roles.map((r) => r.id);
    const allSelected = groupRoleIds.every((id) => selectedRoles.value.has(id));

    if (allSelected) {
        // Deselect all
        groupRoleIds.forEach((id) => selectedRoles.value.delete(id));
    } else {
        // Select all
        groupRoleIds.forEach((id) => selectedRoles.value.add(id));
    }
};

// Check if all roles in a group are selected
const isGroupFullySelected = (group: { roles: Role[] }) => {
    return group.roles.every((r) => selectedRoles.value.has(r.id));
};

// Check if some roles in a group are selected
const isGroupPartiallySelected = (group: { roles: Role[] }) => {
    const selectedCount = group.roles.filter((r) => selectedRoles.value.has(r.id)).length;
    return selectedCount > 0 && selectedCount < group.roles.length;
};

// Select all roles
const selectAll = () => {
    if (props.allRoles) {
        selectedRoles.value = new Set(props.allRoles.map((r) => r.id));
    }
};

// Clear all roles
const clearAll = () => {
    selectedRoles.value.clear();
};

// Get stats for display
const selectionStats = computed(() => {
    const total = props.allRoles?.length || 0;
    const selected = selectedRoles.value.size;
    const percentage = total > 0 ? Math.round((selected / total) * 100) : 0;

    return {
        selected,
        total,
        percentage,
    };
});

// Handle form submission
const handleSubmit = async () => {
    if (!props.permission) return;

    processing.value = true;
    errors.value = {};

    try {
        // Get role IDs from selected roles
        const selectedRoleIds = Array.from(selectedRoles.value);

        // Make direct API call
        const response = await fetch(`/api/permissions/${props.permission.id}/sync-roles`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                // Add CSRF token if available
                ...(document.querySelector('meta[name="csrf-token"]') && {
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
                }),
            },
            body: JSON.stringify({
                role_ids: selectedRoleIds,
            }),
        });

        if (!response.ok) {
            const errorData = await response.json();
            errors.value = errorData.errors || { general: 'An error occurred while updating role assignments.' };
            return;
        }

        // Success - close modal and emit update
        success(`Role assignments updated successfully for permission "${props.permission?.display_name || props.permission?.name}"`, {
            position: 'top-center',
            duration: 4000,
        });
        emit('update:open', false);
        if (props.permission) {
            emit('roles-updated', props.permission);
        }
    } catch (err) {
        error('Failed to update role assignments. Please try again.', {
            position: 'top-center',
            priority: 'critical',
            persistent: true,
        });
        errors.value = { general: 'Network error. Please try again.' };
    } finally {
        processing.value = false;
    }
};

// Handle cancel
const handleCancel = () => {
    emit('update:open', false);
};

// Get guard color
const getGuardColor = (guard: string) => {
    const colors: Record<string, string> = {
        web: 'text-blue-600 bg-blue-50 dark:text-blue-400 dark:bg-blue-900/20',
        api: 'text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/20',
    };

    return colors[guard.toLowerCase()] || 'text-gray-600 bg-gray-50 dark:text-gray-400 dark:bg-gray-900/20';
};
</script>

<template>
    <Sheet :open="open" @update:open="emit('update:open', $event)">
        <SheetContent class="w-full sm:max-w-2xl">
            <SheetHeader>
                <SheetTitle class="flex items-center gap-2">
                    <Shield class="h-5 w-5" />
                    Manage Role Assignments
                </SheetTitle>
                <SheetDescription>
                    Configure which roles have the <span class="font-semibold">{{ permission?.display_name || permission?.name }}</span> permission
                </SheetDescription>
            </SheetHeader>

            <div class="mt-6 space-y-4 px-8">
                <!-- Search and Actions Bar -->
                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="searchQuery" placeholder="Search roles..." class="pl-9" :disabled="processing" />
                    </div>
                    <Button variant="outline" size="sm" @click="selectAll" :disabled="processing">
                        <ShieldCheck class="mr-1 h-4 w-4" />
                        All
                    </Button>
                    <Button variant="outline" size="sm" @click="clearAll" :disabled="processing">
                        <ShieldOff class="mr-1 h-4 w-4" />
                        None
                    </Button>
                </div>

                <!-- Selection Stats -->
                <div class="flex items-center justify-between rounded-lg bg-muted/50 px-3 py-2 text-sm">
                    <span class="text-muted-foreground">
                        Selected: <span class="font-semibold text-foreground">{{ selectionStats.selected }}</span> / {{ selectionStats.total }}
                    </span>
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-32 overflow-hidden rounded-full bg-muted">
                            <div class="h-full bg-primary transition-all duration-300" :style="`width: ${selectionStats.percentage}%`" />
                        </div>
                        <span class="text-muted-foreground">{{ selectionStats.percentage }}%</span>
                    </div>
                </div>

                <!-- Roles List -->
                <ScrollArea class="h-[400px] pr-4">
                    <div class="space-y-2">
                        <div v-for="group in filteredGroups" :key="group.guard" class="rounded-lg border">
                            <!-- Group Header -->
                            <div
                                class="flex cursor-pointer items-center gap-2 p-3 transition-colors hover:bg-muted/50"
                                @click="toggleGroup(group.guard)"
                            >
                                <Button variant="ghost" size="sm" class="h-5 w-5 p-0" @click.stop="toggleGroup(group.guard)">
                                    <ChevronRight v-if="!expandedGroups.has(group.guard)" class="h-4 w-4" />
                                    <ChevronDown v-else class="h-4 w-4" />
                                </Button>

                                <Checkbox
                                    :model-value="isGroupFullySelected(group)"
                                    :indeterminate="isGroupPartiallySelected(group)"
                                    @update:model-value="() => toggleGroupRoles(group)"
                                    @click.stop
                                />

                                <div class="flex flex-1 items-center gap-2">
                                    <span class="rounded-md px-2 py-0.5 text-xs font-medium" :class="getGuardColor(group.guard)">
                                        {{ group.display_name }} Guard
                                    </span>
                                    <span class="text-sm text-muted-foreground">
                                        ({{ group.roles.filter((r) => selectedRoles.has(r.id)).length }}/{{ group.count }})
                                    </span>
                                </div>
                            </div>

                            <!-- Group Roles -->
                            <div v-if="expandedGroups.has(group.guard)" class="border-t bg-muted/20 p-3">
                                <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                                    <div
                                        v-for="role in group.roles"
                                        :key="role.id"
                                        class="flex items-center gap-2 rounded p-2 transition-colors hover:bg-background"
                                    >
                                        <Checkbox :model-value="selectedRoles.has(role.id)" @update:model-value="() => toggleRole(role.id)" />
                                        <Label :for="`role-${role.id}`" class="flex-1 cursor-pointer text-sm" @click="toggleRole(role.id)">
                                            {{ role.display_name || role.name }}
                                        </Label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- No Results -->
                    <div v-if="filteredGroups.length === 0" class="flex flex-col items-center justify-center py-8 text-center">
                        <Shield class="mb-3 h-12 w-12 text-muted-foreground/50" />
                        <p class="text-sm text-muted-foreground">
                            {{ searchQuery ? 'No roles found matching your search' : 'No roles available' }}
                        </p>
                    </div>
                </ScrollArea>

                <!-- Errors -->
                <div v-if="errors.role_ids || errors.general" class="flex items-center gap-2 text-sm text-destructive">
                    <AlertCircle class="h-4 w-4" />
                    {{ errors.role_ids || errors.general }}
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 border-t pt-6">
                    <Button type="button" variant="outline" @click="handleCancel" :disabled="processing" class="flex-1"> Cancel </Button>
                    <Button type="button" @click="handleSubmit" :disabled="processing" class="flex-1">
                        <Loader2 v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
                        {{ processing ? 'Updating...' : 'Update Role Assignments' }}
                    </Button>
                </div>
            </div>
        </SheetContent>
    </Sheet>
</template>
