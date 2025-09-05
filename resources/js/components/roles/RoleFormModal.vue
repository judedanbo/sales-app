<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Textarea } from '@/components/ui/textarea';
import type { Permission, PermissionGroup, Role } from '@/types';
import { router } from '@inertiajs/vue3';
import { ChevronDown, ChevronRight } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    open: boolean;
    role?: Role;
    permissions: Permission[];
    guardNames: string[];
}

interface Emits {
    'update:open': [open: boolean];
    'role-created': [role: Role];
    'role-updated': [role: Role];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const processing = ref(false);
const errors = ref<Record<string, string>>({});

// Initial form state
const initialForm = {
    name: '',
    display_name: '',
    guard_name: 'web',
    description: '',
    permission_ids: [] as number[],
};

const form = ref({ ...initialForm });
const isEditing = ref(false);

// Watch for role prop to determine if editing
watch(
    () => props.role,
    (role) => {
        isEditing.value = !!role;
        if (role) {
            form.value = {
                name: role.name || '',
                display_name: role.display_name || '',
                guard_name: role.guard_name || 'web',
                description: role.description || '',
                permission_ids: role.permissions?.map((permission) => permission.id) || [],
            };
        }
    },
    { immediate: true },
);

// Reset form when modal opens/closes
watch(
    () => props.open,
    (isOpen) => {
        if (isOpen && !props.role) {
            // Reset form when opening for creation
            form.value = { ...initialForm };
            errors.value = {};
            isEditing.value = false;
        } else if (!isOpen) {
            // Clear errors when closing
            errors.value = {};
        }
    },
);

// Group permissions by category
const permissionGroups = computed<PermissionGroup[]>(() => {
    const groups: Record<string, PermissionGroup> = {};

    props.permissions.forEach((permission) => {
        const category = permission.category || 'General';
        const displayName = permission.category_display || category;

        if (!groups[category]) {
            groups[category] = {
                group: category,
                category: category,
                display_name: displayName,
                permissions: [],
                count: 0,
            };
        }

        groups[category].permissions.push(permission);
        groups[category].count++;
    });

    return Object.values(groups).sort((a, b) => a.display_name.localeCompare(b.display_name));
});

// Track expanded groups
const expandedGroups = ref<Set<string>>(new Set());

const toggleGroup = (groupKey: string) => {
    if (expandedGroups.value.has(groupKey)) {
        expandedGroups.value.delete(groupKey);
    } else {
        expandedGroups.value.add(groupKey);
    }
};

const togglePermission = (permissionId: number) => {
    const index = form.value.permission_ids.indexOf(permissionId);
    if (index > -1) {
        form.value.permission_ids.splice(index, 1);
    } else {
        form.value.permission_ids.push(permissionId);
    }
};

const toggleGroupPermissions = (group: PermissionGroup) => {
    const groupPermissionIds = group.permissions.map((p) => p.id);
    const allSelected = groupPermissionIds.every((id) => form.value.permission_ids.includes(id));

    if (allSelected) {
        // Remove all permissions from this group
        form.value.permission_ids = form.value.permission_ids.filter((id) => !groupPermissionIds.includes(id));
    } else {
        // Add all permissions from this group
        groupPermissionIds.forEach((id) => {
            if (!form.value.permission_ids.includes(id)) {
                form.value.permission_ids.push(id);
            }
        });
    }
};

const getGroupSelectionState = (group: PermissionGroup) => {
    const groupPermissionIds = group.permissions.map((p) => p.id);
    const selectedCount = groupPermissionIds.filter((id) => form.value.permission_ids.includes(id)).length;

    if (selectedCount === 0) return 'none';
    if (selectedCount === groupPermissionIds.length) return 'all';
    return 'some';
};

const handleSubmit = () => {
    processing.value = true;
    errors.value = {};

    const submitData = { ...form.value };

    const url = isEditing.value ? `/roles/${props.role?.id}` : '/roles';
    const method = isEditing.value ? 'patch' : 'post';

    router[method](url, submitData, {
        onSuccess: (page) => {
            const role = page.props.role || page.props.roles?.data?.[0];
            if (isEditing.value) {
                emit('role-updated', role);
            } else {
                emit('role-created', role);
            }
            emit('update:open', false);
            if (!isEditing.value) {
                form.value = { ...initialForm };
            }
        },
        onError: (pageErrors) => {
            errors.value = pageErrors;
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};

const handleCancel = () => {
    emit('update:open', false);
    if (!isEditing.value) {
        form.value = { ...initialForm };
    }
    errors.value = {};
};

const handleOpenChange = (open: boolean) => {
    emit('update:open', open);
};

// Auto-generate role name from display name
watch(
    () => form.value.display_name,
    (displayName) => {
        if (!isEditing.value && displayName && !form.value.name) {
            form.value.name = displayName
                .toLowerCase()
                .replace(/[^a-z0-9\s]/g, '')
                .replace(/\s+/g, '_')
                .trim();
        }
    },
);
</script>

<template>
    <Sheet :open="open" @update:open="handleOpenChange">
        <SheetContent class="w-[700px] overflow-y-auto sm:max-w-[700px]">
            <SheetHeader>
                <SheetTitle>{{ isEditing ? 'Edit Role' : 'Add New Role' }}</SheetTitle>
                <SheetDescription>
                    {{ isEditing ? 'Update role information and permissions' : 'Create a new role with specific permissions' }}
                </SheetDescription>
            </SheetHeader>

            <div class="mt-6 px-1">
                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <!-- Basic Information -->
                    <div class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="display_name">Display Name *</Label>
                                <Input
                                    id="display_name"
                                    v-model="form.display_name"
                                    placeholder="Enter role display name"
                                    :class="{ 'border-destructive': errors.display_name }"
                                />
                                <p v-if="errors.display_name" class="text-sm text-destructive">{{ errors.display_name }}</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="name">Role Name *</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    placeholder="role_name"
                                    :class="{ 'border-destructive': errors.name }"
                                    :readonly="isEditing"
                                />
                                <p v-if="errors.name" class="text-sm text-destructive">{{ errors.name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ isEditing ? 'Role name cannot be changed' : 'Auto-generated from display name' }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="guard_name">Guard Name</Label>
                            <select
                                id="guard_name"
                                v-model="form.guard_name"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                                :class="{ 'border-destructive': errors.guard_name }"
                            >
                                <option v-for="guardName in guardNames" :key="guardName" :value="guardName">
                                    {{ guardName }}
                                </option>
                            </select>
                            <p v-if="errors.guard_name" class="text-sm text-destructive">{{ errors.guard_name }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="description">Description</Label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                placeholder="Enter role description..."
                                :class="{ 'border-destructive': errors.description }"
                            />
                            <p v-if="errors.description" class="text-sm text-destructive">{{ errors.description }}</p>
                        </div>
                    </div>

                    <!-- Permissions -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <Label>Permissions</Label>
                            <div class="text-sm text-muted-foreground">{{ form.permission_ids.length }} of {{ permissions.length }} selected</div>
                        </div>

                        <div class="rounded-md border">
                            <div class="max-h-96 overflow-y-auto">
                                <div v-for="group in permissionGroups" :key="group.group" class="border-b last:border-b-0">
                                    <!-- Group Header -->
                                    <div class="flex cursor-pointer items-center justify-between bg-muted/30 p-3" @click="toggleGroup(group.group)">
                                        <div class="flex items-center gap-2">
                                            <component :is="expandedGroups.has(group.group) ? ChevronDown : ChevronRight" class="h-4 w-4" />
                                            <span class="font-medium">{{ group.display_name }}</span>
                                            <span class="text-sm text-muted-foreground">({{ group.count }})</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                @click.stop="toggleGroupPermissions(group)"
                                                :class="{
                                                    'bg-primary text-primary-foreground': getGroupSelectionState(group) === 'all',
                                                    'bg-primary/50': getGroupSelectionState(group) === 'some',
                                                }"
                                            >
                                                {{
                                                    getGroupSelectionState(group) === 'all'
                                                        ? 'Unselect All'
                                                        : getGroupSelectionState(group) === 'some'
                                                          ? 'Select All'
                                                          : 'Select All'
                                                }}
                                            </Button>
                                        </div>
                                    </div>

                                    <!-- Group Permissions -->
                                    <div v-if="expandedGroups.has(group.group)" class="space-y-1 p-2">
                                        <div
                                            v-for="permission in group.permissions"
                                            :key="permission.id"
                                            class="flex items-center space-x-2 rounded p-2 hover:bg-muted/50"
                                        >
                                            <input
                                                :id="`permission-${permission.id}`"
                                                type="checkbox"
                                                :checked="form.permission_ids.includes(permission.id)"
                                                @change="togglePermission(permission.id)"
                                                class="rounded border-input"
                                            />
                                            <div class="flex-1">
                                                <Label :for="`permission-${permission.id}`" class="cursor-pointer text-sm font-normal">
                                                    {{ permission.display_name || permission.name }}
                                                </Label>
                                                <div class="text-xs text-muted-foreground">{{ permission.name }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p v-if="errors.permission_ids" class="text-sm text-destructive">{{ errors.permission_ids }}</p>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-2 border-t pt-4">
                        <Button type="button" variant="outline" @click="handleCancel" :disabled="processing"> Cancel </Button>
                        <Button type="submit" :disabled="processing">
                            {{ processing ? (isEditing ? 'Updating...' : 'Creating...') : isEditing ? 'Update Role' : 'Create Role' }}
                        </Button>
                    </div>
                </form>
            </div>
        </SheetContent>
    </Sheet>
</template>
