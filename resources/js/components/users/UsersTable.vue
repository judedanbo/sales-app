<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
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
import { useAlerts } from '@/composables/useAlerts';
import { index, show } from '@/routes/users';
import type { PaginatedData, User, UserFilters } from '@/types';
import { Link, router } from '@inertiajs/vue3';
import {
    ChevronDown,
    ChevronUp,
    Download,
    Edit as EditIcon,
    Eye,
    MoreHorizontal,
    Plus,
    RotateCcw,
    Shield,
    Trash2,
    UserCheck,
    UserMinus,
    Users as UsersIcon,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import UserFormModal from './UserFormModal.vue';
import UserRoleModal from './UserRoleModal.vue';

interface Props {
    users: PaginatedData<User>;
    filters: UserFilters;
    selectedUsers: number[];
    availableRoles: Role[];
    isLoading?: boolean;
}

interface Emits {
    (e: 'sort', column: string): void;
    (e: 'delete', user: User): void;
    (e: 'restore', user: User): void;
    (e: 'select', userId: number): void;
    (e: 'select-all'): void;
    (e: 'clear-selection'): void;
    (e: 'page-change', page: number): void;
    (e: 'role-updated', user: User): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();
const { success } = useAlerts();

// Edit modal state
const showEditModal = ref(false);
const userToEdit = ref<User | null>(null);

// Role management modal state
const showRoleModal = ref(false);
const userToManageRoles = ref<User | null>(null);

// Delete modal state
const showDeleteModal = ref(false);
const userToDelete = ref<User | null>(null);
const isDeleting = ref(false);

// Watch for user deletion completion - close modal when user is no longer in the list
watch(
    () => props.users.data,
    (newUsers) => {
        if (isDeleting.value && userToDelete.value) {
            // Check if the user being deleted is no longer in the list
            const stillExists = newUsers.some((user) => user.id === userToDelete.value?.id);
            if (!stillExists) {
                // User was successfully deleted, show success toast
                success(`User "${userToDelete.value?.name}" has been successfully deleted.`, {
                    position: 'bottom-right',
                    duration: 4000,
                });
                // Close the modal
                showDeleteModal.value = false;
                userToDelete.value = null;
                isDeleting.value = false;
            }
        }
    },
    { deep: true },
);

// Selection helpers
const isSelected = (userId: number) => props.selectedUsers.includes(userId);

const isAllSelected = computed(() => props.users.data.length > 0 && props.selectedUsers.length === props.users.data.length);

const isIndeterminate = computed(() => props.selectedUsers.length > 0 && props.selectedUsers.length < props.users.data.length);

// Helper function to get filtered parameters (same as in Index.vue)
function getFilteredParameters(filters: UserFilters) {
    const defaults: UserFilters = {
        search: '',
        user_type: undefined,
        school_id: undefined,
        is_active: '',
        role: '',
        sort_by: 'name',
        sort_direction: 'asc',
    };

    const filtered: Partial<UserFilters> = {};

    Object.entries(filters).forEach(([key, value]) => {
        const typedKey = key as keyof UserFilters;
        if (value !== '' && value !== defaults[typedKey] && value !== undefined) {
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

// Get user initials for avatar
function getUserInitials(name: string): string {
    return name
        .split(' ')
        .map((word) => word.charAt(0))
        .join('')
        .toUpperCase()
        .slice(0, 2);
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

function handleDelete(user: User) {
    userToDelete.value = user;
    showDeleteModal.value = true;
}

function confirmDelete() {
    if (!userToDelete.value) return;

    isDeleting.value = true;
    emit('delete', userToDelete.value);

    // Note: The parent component will handle the actual deletion
    // We'll close the modal when the deletion is complete
}

function cancelDelete() {
    showDeleteModal.value = false;
    userToDelete.value = null;
    isDeleting.value = false;
}

function handleRestore(user: User) {
    emit('restore', user);
}

function toggleSelection(userId: number) {
    emit('select', userId);
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

function handleEdit(user: User) {
    userToEdit.value = user;
    showEditModal.value = true;
}

function handleUserUpdated(user: User) {
    // Refresh the users data while preserving filters
    router.reload({
        data: getFilteredParameters(props.filters),
        preserveScroll: true,
        only: ['users'],
    });
}

// Role management handlers
function handleManageRoles(user: User) {
    userToManageRoles.value = user;
    showRoleModal.value = true;
}

function handleRoleUpdated(user: User) {
    emit('role-updated', user);

    // Refresh the table data while preserving filters
    router.reload({
        data: getFilteredParameters(props.filters),
        preserveScroll: true,
        only: ['users'],
    });
}
</script>

<template>
    <!-- Bulk Actions Bar -->
    <div v-if="selectedUsers.length > 0" class="fixed bottom-6 left-1/2 z-50 -translate-x-1/2">
        <Card class="border-2 border-primary/20 shadow-lg">
            <CardContent class="p-4">
                <div class="flex items-center gap-4">
                    <div class="text-sm font-medium">{{ selectedUsers.length }} {{ selectedUsers.length === 1 ? 'user' : 'users' }} selected</div>
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

    <!-- Users Table -->
    <Card>
        <CardHeader>
            <CardTitle>Users List</CardTitle>
            <CardDescription> Showing {{ users.from || 0 }} to {{ users.to || 0 }} of {{ users.total }} users </CardDescription>
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
                                    aria-label="Select all users"
                                />
                            </th>
                            <th scope="col" class="px-4 py-3">
                                <button @click="handleSort('name')" class="flex items-center gap-1 transition-colors hover:text-foreground">
                                    User
                                    <component :is="getSortIcon('name')" v-if="getSortIcon('name')" class="h-3 w-3" />
                                </button>
                            </th>
                            <th scope="col" class="px-4 py-3">
                                <button @click="handleSort('email')" class="flex items-center gap-1 transition-colors hover:text-foreground">
                                    Email
                                    <component :is="getSortIcon('email')" v-if="getSortIcon('email')" class="h-3 w-3" />
                                </button>
                            </th>
                            <th scope="col" class="px-4 py-3">
                                <button @click="handleSort('user_type')" class="flex items-center gap-1 transition-colors hover:text-foreground">
                                    Type
                                    <component :is="getSortIcon('user_type')" v-if="getSortIcon('user_type')" class="h-3 w-3" />
                                </button>
                            </th>
                            <th scope="col" class="px-4 py-3">School</th>
                            <th scope="col" class="px-4 py-3">Roles</th>
                            <th scope="col" class="px-4 py-3">Status</th>
                            <th scope="col" class="px-4 py-3">
                                <button @click="handleSort('last_login_at')" class="flex items-center gap-1 transition-colors hover:text-foreground">
                                    Last Login
                                    <component :is="getSortIcon('last_login_at')" v-if="getSortIcon('last_login_at')" class="h-3 w-3" />
                                </button>
                            </th>
                            <th scope="col" class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Loading State -->
                        <tr v-if="isLoading">
                            <td colspan="9" class="px-4 py-8 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <Skeleton class="h-4 w-32" />
                                    <Skeleton class="h-4 w-24" />
                                </div>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-else-if="!users.data.length">
                            <td colspan="9" class="px-4 py-8 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <UsersIcon class="h-8 w-8 text-muted-foreground" />
                                    <p class="text-muted-foreground">No users found</p>
                                    <Link :href="index().url">
                                        <Button size="sm">
                                            <Plus class="mr-2 h-4 w-4" />
                                            Add First User
                                        </Button>
                                    </Link>
                                </div>
                            </td>
                        </tr>

                        <!-- Data Rows -->
                        <tr
                            v-else
                            v-for="user in users.data"
                            :key="user.id"
                            class="border-b hover:bg-muted/50"
                            :class="{ 'opacity-50': user.deleted_at }"
                        >
                            <td class="px-4 py-3">
                                <Checkbox
                                    :model-value="isSelected(user.id)"
                                    @update:model-value="() => toggleSelection(user.id)"
                                    :aria-label="`Select ${user.name}`"
                                />
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <Avatar class="h-8 w-8">
                                        <AvatarImage :src="user.avatar" :alt="user.name" />
                                        <AvatarFallback>{{ getUserInitials(user.name) }}</AvatarFallback>
                                    </Avatar>
                                    <div>
                                        <div class="font-medium">{{ user.name }}</div>
                                        <div v-if="user.department" class="text-xs text-muted-foreground">{{ user.department }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div>{{ user.email }}</div>
                                <div v-if="user.phone" class="text-xs text-muted-foreground">{{ user.phone }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <Badge variant="secondary" class="text-xs">
                                    {{ user.user_type_label || user.user_type?.replace('_', ' ') }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <span v-if="user.school" class="text-sm">{{ user.school.school_name }}</span>
                                <span v-else class="text-muted-foreground">-</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <Badge v-for="role in user.roles?.slice(0, 2)" :key="role.id" variant="outline" class="text-xs">
                                        {{ role.display_name || role.name }}
                                    </Badge>
                                    <Badge v-if="(user.roles?.length || 0) > 2" variant="outline" class="text-xs">
                                        +{{ (user.roles?.length || 0) - 2 }} more
                                    </Badge>
                                </div>
                            </td>
                            <td class="px-4 py-3">
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
                                <div v-if="user.deleted_at" class="mt-1 text-xs text-red-600">Deleted {{ formatDate(user.deleted_at) }}</div>
                            </td>
                            <td class="px-4 py-3">
                                {{ formatDate(user.last_login_at) }}
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

                                        <template v-if="!user.deleted_at">
                                            <Link :href="show(user).url">
                                                <DropdownMenuItem>
                                                    <Eye class="mr-2 h-4 w-4" />
                                                    View
                                                </DropdownMenuItem>
                                            </Link>
                                            <DropdownMenuItem @click="handleEdit(user)">
                                                <EditIcon class="mr-2 h-4 w-4" />
                                                Edit
                                            </DropdownMenuItem>

                                            <DropdownMenuSeparator />

                                            <DropdownMenuItem @click="handleManageRoles(user)">
                                                <Shield class="mr-2 h-4 w-4" />
                                                Manage Roles
                                            </DropdownMenuItem>

                                            <DropdownMenuSeparator />

                                            <DropdownMenuItem class="text-red-600 dark:text-red-400" @click="handleDelete(user)">
                                                <Trash2 class="mr-2 h-4 w-4" />
                                                Delete
                                            </DropdownMenuItem>
                                        </template>

                                        <template v-else>
                                            <DropdownMenuItem @click="handleRestore(user)">
                                                <RotateCcw class="mr-2 h-4 w-4" />
                                                Restore
                                            </DropdownMenuItem>
                                        </template>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :current-page="users.current_page" :last-page="users.last_page" :disabled="isLoading" @page-change="handlePageChange" />
        </CardContent>
    </Card>

    <!-- Edit User Modal -->
    <UserFormModal
        :open="showEditModal"
        :user="userToEdit"
        :user-types="[]"
        :schools="[]"
        :roles="availableRoles"
        @update:open="showEditModal = $event"
        @user-updated="handleUserUpdated"
    />

    <!-- Role Management Modal -->
    <UserRoleModal
        :open="showRoleModal"
        :user="userToManageRoles"
        :available-roles="availableRoles"
        @update:open="showRoleModal = $event"
        @role-updated="handleRoleUpdated"
    />

    <!-- Delete Confirmation Modal -->
    <DeleteConfirmationModal
        :open="showDeleteModal"
        :loading="isDeleting"
        title="Delete User"
        message="Are you sure you want to delete"
        :item-name="userToDelete?.name"
        danger-text="Delete User"
        @update:open="showDeleteModal = $event"
        @confirm="confirmDelete"
        @cancel="cancelDelete"
    />
</template>
