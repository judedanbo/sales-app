<script setup lang="ts">
import Badge from '@/components/ui/badge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import DeleteConfirmationModal from '@/components/ui/DeleteConfirmationModal.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import Pagination from '@/components/ui/Pagination.vue';
import { Skeleton } from '@/components/ui/skeleton';
import { index, show } from '@/routes/roles';
import type { PaginatedData, Permission, Role, RoleFilters, User } from '@/types';
import { Link, router } from '@inertiajs/vue3';
import { ChevronDown, ChevronUp, Download, Edit as EditIcon, Eye, Key, MoreHorizontal, Plus, Shield, Trash2, Users } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { useAlerts } from '@/composables/useAlerts';
import RoleFormModal from './RoleFormModal.vue';
import RolePermissionsModal from './RolePermissionsModal.vue';
import RoleUsersModal from './RoleUsersModal.vue';

interface Props {
    roles: PaginatedData<Role>;
    filters: RoleFilters;
    selectedRoles: number[];
    isLoading?: boolean;
    availableUsers: User[];
    allPermissions?: Permission[];
}

interface Emits {
    (e: 'sort', column: string): void;
    (e: 'delete', role: Role): void;
    (e: 'select', roleId: number): void;
    (e: 'select-all'): void;
    (e: 'clear-selection'): void;
    (e: 'page-change', page: number): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();
const { success } = useAlerts();

// Edit modal state
const showEditModal = ref(false);
const roleToEdit = ref<Role | null>(null);

// Users modal state
const showUsersModal = ref(false);
const roleForUsers = ref<Role | null>(null);
// const availableUsers = ref<User[]>([]);

// Permissions modal state
const showPermissionsModal = ref(false);
const roleForPermissions = ref<Role | null>(null);

// Delete modal state
const showDeleteModal = ref(false);
const roleToDelete = ref<Role | null>(null);
const isDeleting = ref(false);

// Selection helpers
const isSelected = (roleId: number) => props.selectedRoles.includes(roleId);

const isAllSelected = computed(() => props.roles.data.length > 0 && props.selectedRoles.length === props.roles.data.length);

const isIndeterminate = computed(() => props.selectedRoles.length > 0 && props.selectedRoles.length < props.roles.data.length);

// Watch for role deletion completion - close modal when role is no longer in the list
watch(
    () => props.roles.data,
    (newRoles) => {
        if (isDeleting.value && roleToDelete.value) {
            // Check if the role being deleted is no longer in the list
            const stillExists = newRoles.some((role) => role.id === roleToDelete.value?.id);
            if (!stillExists) {
                // Role was successfully deleted, show success toast
                success(`Role "${roleToDelete.value?.display_name || roleToDelete.value?.name}" has been successfully deleted.`, {
                    position: 'bottom-right',
                    duration: 4000
                });
                // Close the modal
                showDeleteModal.value = false;
                roleToDelete.value = null;
                isDeleting.value = false;
            }
        }
    },
    { deep: true },
);

// Helper function to get filtered parameters (same as in Index.vue)
function getFilteredParameters(filters: RoleFilters) {
    const defaults: RoleFilters = {
        search: '',
        guard_name: '',
        has_users: '',
        sort_by: 'name',
        sort_direction: 'asc',
    };

    const filtered: Partial<RoleFilters> = {};

    Object.entries(filters).forEach(([key, value]) => {
        const typedKey = key as keyof RoleFilters;
        if (value !== '' && value !== defaults[typedKey]) {
            filtered[typedKey] = value;
        }
    });

    return filtered;
}

// Format date
function formatDate(date: string | undefined) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

// Get sort icon
function getSortIcon(column: string) {
    if (props.filters.sort_by !== column) {
        return null;
    }
    return props.filters.sort_direction === 'asc' ? ChevronUp : ChevronDown;
}

// Event handlers
function handleSort(column: string) {
    emit('sort', column);
}

function handleDelete(role: Role) {
    roleToDelete.value = role;
    showDeleteModal.value = true;
}

function confirmDelete() {
    if (!roleToDelete.value) return;

    isDeleting.value = true;
    // emit('delete', roleToDelete.value);
    router.delete(`/roles/${roleToDelete.value?.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            roleToDelete.value = null;
            isDeleting.value = false;
            // The deletion is initiated - the RolesTable will handle modal cleanup
            // through its own state management or parent-child communication
        },
        onFinish: () => {
            // The deletion is complete - the RolesTable will handle modal cleanup
            // through its own state management or parent-child communication
        },
    });

    // Note: The parent component will handle the actual deletion
    // We'll close the modal when the deletion is complete
}

function cancelDelete() {
    showDeleteModal.value = false;
    roleToDelete.value = null;
    isDeleting.value = false;
}

function toggleSelection(roleId: number) {
    emit('select', roleId);
}

function selectAll() {
    emit('select-all');
}

function clearSelection() {
    emit('clear-selection');
}

function handlePageChange(page: number) {
    emit('page-change', page);
}

function handleEdit(role: Role) {
    roleToEdit.value = role;
    showEditModal.value = true;
}

function handleRoleUpdated(role: Role) {
    // Refresh the roles data while preserving filters
    router.reload({
        data: getFilteredParameters(props.filters),
        only: ['roles'],
    });
}

function handleManageUsers(role: Role) {
    // Get role details with users and available users using router.get for partial data loading
    roleForUsers.value = role;
    showUsersModal.value = true;
    // TODO fetch availableUsers for the role. This is all users not assigned to this role
    // availableUsers.value = []; // Clear previous available users
    // router.get(
    //     show(role).url,
    //     {},
    //     {
    //         only: ['role', 'availableUsers'],
    //         preserveState: true,
    //         onSuccess: (page: any) => {
    //             // Extract the role data with users and availableUsers from the page props
    //             const roleData = page.props.role;
    //             const availableUsersData = page.props.availableUsers;

    //             roleForUsers.value = roleData;
    //             availableUsers.value = availableUsersData || [];
    //             showUsersModal.value = true;
    //         },
    //     },
    // );
}

function handleUsersUpdated() {
    // Refresh the roles data while preserving filters
    router.reload({
        data: getFilteredParameters(props.filters),
        only: ['roles'],
    });
}

function handleManagePermissions(role: Role) {
    roleForPermissions.value = role;
    showPermissionsModal.value = true;
}

function handlePermissionsUpdated() {
    // Refresh the roles data while preserving filters
    router.reload({
        data: getFilteredParameters(props.filters),
        only: ['roles'],
    });
}
</script>

<template>
    <!-- Bulk Actions Bar -->
    <div v-if="selectedRoles.length > 0" class="fixed bottom-6 left-1/2 z-50 -translate-x-1/2">
        <Card class="border-2 border-primary/20 shadow-lg">
            <CardContent class="p-4">
                <div class="flex items-center gap-4">
                    <div class="text-sm font-medium">{{ selectedRoles.length }} {{ selectedRoles.length === 1 ? 'role' : 'roles' }} selected</div>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" size="sm" @click="clearSelection"> Clear </Button>
                        <Button size="sm" class="gap-2">
                            <Download class="h-4 w-4" />
                            Export Selected
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>

    <!-- Roles Table -->
    <Card>
        <CardHeader>
            <CardTitle>Roles List</CardTitle>
            <CardDescription> Showing {{ roles.from || 0 }} to {{ roles.to || 0 }} of {{ roles.total }} roles </CardDescription>
        </CardHeader>
        <CardContent>
            <div class="relative overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-muted/50 text-xs uppercase">
                        <tr>
                            <th scope="col" class="w-12 px-4 py-3">
                                <Checkbox
                                    :model-value="isAllSelected"
                                    :indeterminate="isIndeterminate"
                                    @update:model-value="selectAll"
                                    aria-label="Select all roles"
                                />
                            </th>
                            <th scope="col" class="px-4 py-3">
                                <button @click="handleSort('name')" class="flex items-center gap-1 transition-colors hover:text-foreground">
                                    Role
                                    <component :is="getSortIcon('name')" v-if="getSortIcon('name')" class="h-3 w-3" />
                                </button>
                            </th>
                            <th scope="col" class="px-4 py-3">
                                <button @click="handleSort('guard_name')" class="flex items-center gap-1 transition-colors hover:text-foreground">
                                    Guard
                                    <component :is="getSortIcon('guard_name')" v-if="getSortIcon('guard_name')" class="h-3 w-3" />
                                </button>
                            </th>
                            <th scope="col" class="px-4 py-3">Users</th>
                            <th scope="col" class="px-4 py-3">Permissions</th>
                            <th scope="col" class="px-4 py-3">
                                <button @click="handleSort('created_at')" class="flex items-center gap-1 transition-colors hover:text-foreground">
                                    Created
                                    <component :is="getSortIcon('created_at')" v-if="getSortIcon('created_at')" class="h-3 w-3" />
                                </button>
                            </th>
                            <th scope="col" class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loading State -->
                        <tr v-if="isLoading">
                            <td colspan="7" class="px-4 py-8 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <Skeleton class="h-4 w-32" />
                                    <Skeleton class="h-4 w-24" />
                                </div>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-else-if="!roles.data.length">
                            <td colspan="7" class="px-4 py-8 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <Shield class="h-8 w-8 text-muted-foreground" />
                                    <p class="text-muted-foreground">No roles found</p>
                                    <Link :href="index().url">
                                        <Button size="sm">
                                            <Plus class="mr-2 h-4 w-4" />
                                            Add First Role
                                        </Button>
                                    </Link>
                                </div>
                            </td>
                        </tr>

                        <!-- Data Rows -->
                        <tr v-else v-for="role in roles.data" :key="role.id" class="border-b hover:bg-muted/50">
                            <td class="px-4 py-3">
                                <Checkbox
                                    :model-value="isSelected(role.id)"
                                    @update:model-value="() => toggleSelection(role.id)"
                                    :aria-label="`Select ${role.name}`"
                                />
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <Shield class="h-4 w-4 text-muted-foreground" />
                                    <div>
                                        <div class="font-medium">{{ role.display_name || role.name }}</div>
                                        <div class="text-xs text-muted-foreground">{{ role.name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <Badge variant="secondary" class="text-xs">
                                    {{ role.guard_name }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1">
                                    <Users class="h-3 w-3 text-muted-foreground" />
                                    <span>{{ role.users_count || 0 }}</span>
                                </div>
                                <div v-if="role.sample_users?.length" class="mt-1 text-xs text-muted-foreground">
                                    {{
                                        role.sample_users
                                            .slice(0, 2)
                                            .map((u) => u.name)
                                            .join(', ')
                                    }}
                                    <span v-if="(role.users_count || 0) > 2">+{{ (role.users_count || 0) - 2 }} more</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1">
                                    <Key class="h-3 w-3 text-muted-foreground" />
                                    <span>{{ role.permissions_count || 0 }}</span>
                                </div>
                                <div v-if="role.permissions?.length" class="mt-1 flex flex-wrap gap-1">
                                    <Badge v-for="permission in role.permissions.slice(0, 3)" :key="permission.id" variant="outline" class="text-xs">
                                        {{ permission.display_name || permission.name }}
                                    </Badge>
                                    <Badge v-if="(role.permissions_count || 0) > 3" variant="outline" class="text-xs">
                                        +{{ (role.permissions_count || 0) - 3 }} more
                                    </Badge>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                {{ formatDate(role.created_at) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="ghost" size="sm">
                                            <MoreHorizontal class="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                        <DropdownMenuSeparator />

                                        <Link :href="show(role).url">
                                            <DropdownMenuItem>
                                                <Eye class="mr-2 h-4 w-4" />
                                                View
                                            </DropdownMenuItem>
                                        </Link>
                                        <DropdownMenuItem @click="handleEdit(role)">
                                            <EditIcon class="mr-2 h-4 w-4" />
                                            Edit
                                        </DropdownMenuItem>

                                        <DropdownMenuSeparator />

                                        <DropdownMenuItem @click="handleManageUsers(role)">
                                            <Users class="mr-2 h-4 w-4" />
                                            Manage Users
                                        </DropdownMenuItem>

                                        <DropdownMenuItem @click="handleManagePermissions(role)">
                                            <Key class="mr-2 h-4 w-4" />
                                            Manage Permissions
                                        </DropdownMenuItem>

                                        <DropdownMenuSeparator />

                                        <DropdownMenuItem class="text-red-600 dark:text-red-400" @click="handleDelete(role)">
                                            <Trash2 class="mr-2 h-4 w-4" />
                                            Delete
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :current-page="roles.current_page" :last-page="roles.last_page" :disabled="isLoading" @page-change="handlePageChange" />
        </CardContent>
    </Card>

    <!-- Edit Role Modal -->
    <RoleFormModal
        v-if="roleToEdit"
        :open="showEditModal"
        :role="roleToEdit"
        :permissions="[]"
        :guard-names="[]"
        @update:open="showEditModal = $event"
        @role-updated="handleRoleUpdated"
    />

    <!-- Manage Users Modal -->
    <RoleUsersModal
        v-if="roleForUsers"
        :open="showUsersModal"
        :role="roleForUsers"
        :available-users="availableUsers"
        @update:open="showUsersModal = $event"
        @users-updated="handleUsersUpdated"
    />

    <!-- Manage Permissions Modal -->
    <RolePermissionsModal
        v-if="roleForPermissions"
        :open="showPermissionsModal"
        :role="roleForPermissions"
        :all-permissions="allPermissions"
        @update:open="showPermissionsModal = $event"
        @permissions-updated="handlePermissionsUpdated"
    />

    <!-- Delete Confirmation Modal -->
    <DeleteConfirmationModal
        :open="showDeleteModal"
        :loading="isDeleting"
        title="Delete Role"
        message="Are you sure you want to delete the role"
        :item-name="roleToDelete?.display_name || roleToDelete?.name"
        danger-text="Delete Role"
        @update:open="showDeleteModal = $event"
        @confirm="confirmDelete"
        @cancel="cancelDelete"
    />
</template>
