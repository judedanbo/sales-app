<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import ScrollArea from '@/components/ui/scroll-area/ScrollArea.vue';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { syncPermissions } from '@/routes/roles';
import type { Permission, PermissionGroup, Role } from '@/types';
import { router } from '@inertiajs/vue3';
import { AlertCircle, ChevronDown, ChevronRight, Loader2, Search, Shield, ShieldCheck, ShieldOff } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    open: boolean;
    role?: Role & {
        permissions?: Permission[];
        display_name?: string;
    };
    allPermissions?: Permission[];
}

interface Emits {
    'update:open': [open: boolean];
    'permissions-updated': [role: Role];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const processing = ref(false);
const errors = ref<Record<string, string>>({});
const searchQuery = ref('');
const expandedGroups = ref<Set<string>>(new Set());
const selectedPermissions = ref<Set<number>>(new Set());

// Initialize selected permissions when role changes
watch(
    () => props.role,
    (role) => {
        if (role?.permissions) {
            selectedPermissions.value = new Set(role.permissions.map((p) => p.id));
        } else {
            selectedPermissions.value = new Set();
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
            if (permissionGroups.value) {
                expandedGroups.value = new Set(permissionGroups.value.map((g) => g.category).filter((c): c is string => c !== undefined));
            }
        }
    },
);

// Group permissions by category
const permissionGroups = computed<PermissionGroup[]>(() => {
    if (!props.allPermissions) return [];

    const groups: Record<string, PermissionGroup> = {};

    props.allPermissions.forEach((permission) => {
        const category = permission.category || 'Other';

        if (!groups[category]) {
            groups[category] = {
                category: category,
                display_name: category.charAt(0).toUpperCase() + category.slice(1),
                permissions: [],
                count: 0,
                group: category,
            };
        }

        groups[category].permissions.push(permission);
        groups[category].count++;
    });

    return Object.values(groups).sort((a, b) => {
        const catA = a.category || 'Other';
        const catB = b.category || 'Other';
        return catA.localeCompare(catB);
    });
});

// Filter permissions based on search
const filteredGroups = computed(() => {
    if (!searchQuery.value) return permissionGroups.value;

    const query = searchQuery.value.toLowerCase();

    return permissionGroups.value
        .map((group) => ({
            ...group,
            permissions: group.permissions.filter(
                (p) => p.name.toLowerCase().includes(query) || (p.display_name && p.display_name.toLowerCase().includes(query)),
            ),
            count: group.permissions.filter(
                (p) => p.name.toLowerCase().includes(query) || (p.display_name && p.display_name.toLowerCase().includes(query)),
            ).length,
        }))
        .filter((group) => group.count > 0);
});

// Toggle group expansion
const toggleGroup = (category: string) => {
    if (expandedGroups.value.has(category)) {
        expandedGroups.value.delete(category);
    } else {
        expandedGroups.value.add(category);
    }
};

// Toggle permission selection
const togglePermission = (permissionId: number) => {
    if (selectedPermissions.value.has(permissionId)) {
        selectedPermissions.value.delete(permissionId);
    } else {
        selectedPermissions.value.add(permissionId);
    }
};

// Toggle all permissions in a group
const toggleGroupPermissions = (group: PermissionGroup) => {
    const groupPermissionIds = group.permissions.map((p) => p.id);
    const allSelected = groupPermissionIds.every((id) => selectedPermissions.value.has(id));

    if (allSelected) {
        // Deselect all
        groupPermissionIds.forEach((id) => selectedPermissions.value.delete(id));
    } else {
        // Select all
        groupPermissionIds.forEach((id) => selectedPermissions.value.add(id));
    }
};

// Check if all permissions in a group are selected
const isGroupFullySelected = (group: PermissionGroup) => {
    return group.permissions.every((p) => selectedPermissions.value.has(p.id));
};

// Check if some permissions in a group are selected
const isGroupPartiallySelected = (group: PermissionGroup) => {
    const selectedCount = group.permissions.filter((p) => selectedPermissions.value.has(p.id)).length;
    return selectedCount > 0 && selectedCount < group.permissions.length;
};

// Select all permissions
const selectAll = () => {
    if (props.allPermissions) {
        selectedPermissions.value = new Set(props.allPermissions.map((p) => p.id));
    }
};

// Clear all permissions
const clearAll = () => {
    selectedPermissions.value.clear();
};

// Get stats for display
const selectionStats = computed(() => {
    const total = props.allPermissions?.length || 0;
    const selected = selectedPermissions.value.size;
    const percentage = total > 0 ? Math.round((selected / total) * 100) : 0;

    return {
        selected,
        total,
        percentage,
    };
});

// Handle form submission
const handleSubmit = () => {
    if (!props.role) return;

    processing.value = true;
    errors.value = {};

    // Get permission names from selected IDs
    const selectedPermissionNames = props.allPermissions?.filter((p) => selectedPermissions.value.has(p.id)).map((p) => p.name) || [];

    router.post(
        syncPermissions(props.role.id).url,
        {
            permissions: selectedPermissionNames,
        },
        {
            onSuccess: () => {
                processing.value = false;
                emit('update:open', false);

                // Reload to get updated data
                router.reload({
                    only: ['role', 'permissionGroups'],
                });
            },
            onError: (pageErrors) => {
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

// Get category color
const getCategoryColor = (category: string) => {
    const colors: Record<string, string> = {
        users: 'text-blue-600 bg-blue-50 dark:text-blue-400 dark:bg-blue-900/20',
        roles: 'text-purple-600 bg-purple-50 dark:text-purple-400 dark:bg-purple-900/20',
        schools: 'text-green-600 bg-green-50 dark:text-green-400 dark:bg-green-900/20',
        sales: 'text-yellow-600 bg-yellow-50 dark:text-yellow-400 dark:bg-yellow-900/20',
        products: 'text-orange-600 bg-orange-50 dark:text-orange-400 dark:bg-orange-900/20',
        reports: 'text-indigo-600 bg-indigo-50 dark:text-indigo-400 dark:bg-indigo-900/20',
        system: 'text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20',
        inventory: 'text-cyan-600 bg-cyan-50 dark:text-cyan-400 dark:bg-cyan-900/20',
        audit: 'text-pink-600 bg-pink-50 dark:text-pink-400 dark:bg-pink-900/20',
    };

    return colors[category.toLowerCase()] || 'text-gray-600 bg-gray-50 dark:text-gray-400 dark:bg-gray-900/20';
};
</script>

<template>
    <Sheet :open="open" @update:open="emit('update:open', $event)">
        <SheetContent class="w-full sm:max-w-2xl">
            <SheetHeader>
                <SheetTitle class="flex items-center gap-2">
                    <Shield class="h-5 w-5" />
                    Manage Permissions
                </SheetTitle>
                <SheetDescription>
                    Configure permissions for <span class="font-semibold">{{ role?.display_name || role?.name }}</span>
                </SheetDescription>
            </SheetHeader>

            <div class="mt-6 space-y-4 px-8">
                <!-- Search and Actions Bar -->
                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input v-model="searchQuery" placeholder="Search permissions..." class="pl-9" :disabled="processing" />
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

                <!-- Permissions List -->
                <ScrollArea class="h-[400px] pr-4">
                    <div class="space-y-2">
                        <div v-for="group in filteredGroups" :key="group.category" class="rounded-lg border">
                            <!-- Group Header -->
                            <div
                                class="flex cursor-pointer items-center gap-2 p-3 transition-colors hover:bg-muted/50"
                                @click="toggleGroup(group.category || group.group)"
                            >
                                <Button variant="ghost" size="sm" class="h-5 w-5 p-0" @click.stop="toggleGroup(group.category || group.group)">
                                    <ChevronRight v-if="!expandedGroups.has(group.category || group.group)" class="h-4 w-4" />
                                    <ChevronDown v-else class="h-4 w-4" />
                                </Button>

                                <Checkbox
                                    :model-value="isGroupFullySelected(group)"
                                    :indeterminate="isGroupPartiallySelected(group)"
                                    @update:model-value="() => toggleGroupPermissions(group)"
                                    @click.stop
                                />

                                <div class="flex flex-1 items-center gap-2">
                                    <span class="rounded-md px-2 py-0.5 text-xs font-medium" :class="getCategoryColor(group.category || group.group)">
                                        {{ group.display_name }}
                                    </span>
                                    <span class="text-sm text-muted-foreground">
                                        ({{ group.permissions.filter((p) => selectedPermissions.has(p.id)).length }}/{{ group.count }})
                                    </span>
                                </div>
                            </div>

                            <!-- Group Permissions -->
                            <div v-if="expandedGroups.has(group.category || group.group)" class="border-t bg-muted/20 p-3">
                                <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                                    <div
                                        v-for="permission in group.permissions"
                                        :key="permission.id"
                                        class="flex items-center gap-2 rounded p-2 transition-colors hover:bg-background"
                                    >
                                        <Checkbox
                                            :model-value="selectedPermissions.has(permission.id)"
                                            @update:model-value="() => togglePermission(permission.id)"
                                        />
                                        <Label
                                            :for="`permission-${permission.id}`"
                                            class="flex-1 cursor-pointer text-sm"
                                            @click="togglePermission(permission.id)"
                                        >
                                            {{ permission.display_name || permission.name }}
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
                            {{ searchQuery ? 'No permissions found matching your search' : 'No permissions available' }}
                        </p>
                    </div>
                </ScrollArea>

                <!-- Errors -->
                <div v-if="errors.permissions" class="flex items-center gap-2 text-sm text-destructive">
                    <AlertCircle class="h-4 w-4" />
                    {{ errors.permissions }}
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 border-t pt-6">
                    <Button type="button" variant="outline" @click="handleCancel" :disabled="processing" class="flex-1"> Cancel </Button>
                    <Button type="button" @click="handleSubmit" :disabled="processing" class="flex-1">
                        <Loader2 v-if="processing" class="mr-2 h-4 w-4 animate-spin" />
                        {{ processing ? 'Updating...' : 'Update Permissions' }}
                    </Button>
                </div>
            </div>
        </SheetContent>
    </Sheet>
</template>
