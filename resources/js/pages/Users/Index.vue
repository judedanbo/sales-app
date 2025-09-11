<script setup lang="ts">
import PermissionGuard from '@/components/PermissionGuard.vue';
import { Button } from '@/components/ui/button';
import PageHeader from '@/components/ui/PageHeader.vue';
import UserFiltersComponent from '@/components/users/UserFilters.vue';
import UserFormModal from '@/components/users/UserFormModal.vue';
import UsersTable from '@/components/users/UsersTable.vue';
import UserStats from '@/components/users/UserStats.vue';
import AppLayout from '@/layouts/AppLayout.vue';
// TODO: Import users index route when it's generated
import {
    type BreadcrumbItem,
    type PaginatedData,
    type Role,
    type School,
    type User,
    type UserFilters,
    type UserStatistics,
    type UserTypeOption,
} from '@/types';
import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Plus } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Props {
    users: PaginatedData<User>;
    filters: UserFilters;
    statistics: UserStatistics;
    userTypes: UserTypeOption[];
    schools: School[];
    roles: Role[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/users',
    },
];

// Local filter state
const localFilters = ref<UserFilters>({
    search: props.filters.search || '',
    user_type: props.filters.user_type || undefined,
    school_id: props.filters.school_id || undefined,
    is_active: props.filters.is_active || '',
    role: props.filters.role || '',
    sort_by: props.filters.sort_by || 'name',
    sort_direction: props.filters.sort_direction || 'asc',
});

const isLoading = ref(false);
const selectedUsers = ref<number[]>([]);
const showCreateModal = ref(false);

// Selection handlers
const toggleSelection = (userId: number) => {
    const index = selectedUsers.value.indexOf(userId);
    if (index > -1) {
        selectedUsers.value.splice(index, 1);
    } else {
        selectedUsers.value.push(userId);
    }
};

const selectAll = () => {
    if (selectedUsers.value.length === props.users.data.length) {
        selectedUsers.value = [];
    } else {
        selectedUsers.value = props.users.data.map((user) => user.id);
    }
};

const clearSelection = () => {
    selectedUsers.value = [];
};

// Clear selection when page changes
watch(
    () => props.users.current_page,
    () => {
        clearSelection();
    },
);

// Debounced search function
const debouncedSearch = useDebounceFn(() => {
    applyFilters();
}, 300);

// Watch for changes in local filters
watch(
    () => localFilters.value,
    (newFilters, oldFilters) => {
        // Use debounced search for search field, immediate for others
        if (newFilters.search !== oldFilters?.search) {
            debouncedSearch();
        } else if (
            newFilters.user_type !== oldFilters?.user_type ||
            newFilters.school_id !== oldFilters?.school_id ||
            newFilters.is_active !== oldFilters?.is_active ||
            newFilters.role !== oldFilters?.role
        ) {
            applyFilters();
        }
    },
    { deep: true },
);

// Filter out default values from filters
function getFilteredParameters(filters: UserFilters) {
    const defaults = {
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

// Apply filters to the server
function applyFilters() {
    isLoading.value = true;
    router.reload({
        data: getFilteredParameters(localFilters.value),
        preserveScroll: true,
        only: ['users'],
        onFinish: () => {
            isLoading.value = false;
        },
    });
}

// Handle pagination without URL parameters
function goToPage(page: number) {
    isLoading.value = true;
    const filteredData = getFilteredParameters(localFilters.value);
    router.reload({
        data: {
            ...filteredData,
            page: page,
        },
        preserveScroll: true,
        only: ['users'],
        onFinish: () => {
            isLoading.value = false;
        },
    });
}

// Sort handler
function handleSort(column: string) {
    if (localFilters.value.sort_by === column) {
        localFilters.value.sort_direction = localFilters.value.sort_direction === 'asc' ? 'desc' : 'asc';
    } else {
        localFilters.value.sort_by = column;
        localFilters.value.sort_direction = 'asc';
    }
    applyFilters();
}

// Event handlers for components
function handleDelete(user: User) {
    if (confirm(`Are you sure you want to delete ${user.name}?`)) {
        router.delete(`/users/${user.id}`, {
            preserveScroll: true,
        });
    }
}

function handleRestore(user: User) {
    if (confirm(`Are you sure you want to restore ${user.name}?`)) {
        router.patch(
            `/users/${user.id}/restore`,
            {},
            {
                preserveScroll: true,
            },
        );
    }
}

function handleFiltersUpdate(newFilters: UserFilters) {
    localFilters.value = { ...newFilters };
}

function handleUserCreated(user: User) {
    // Refresh the page to show the new user while preserving filters
    router.reload({
        data: getFilteredParameters(localFilters.value),
        preserveScroll: true,
        only: ['users', 'statistics'],
    });
}

function handleUserUpdated(user: User) {
    // Refresh the page to show updated user data while preserving filters
    router.reload({
        data: getFilteredParameters(localFilters.value),
        preserveScroll: true,
        only: ['users', 'statistics'],
    });
}

function handlePageChange(page: number) {
    goToPage(page);
}

const clearFilters = () => {
    localFilters.value = {
        search: '',
        user_type: undefined,
        school_id: undefined,
        is_active: '',
        role: '',
        sort_by: 'name',
        sort_direction: 'asc',
    };
    applyFilters();
};
</script>

<template>
    <!-- <Head title="Users" /> -->

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-8">
            <!-- Header Section -->
            <PageHeader title="Users" description="Manage system users and their permissions">
                <template #action>
                    <PermissionGuard permission="create_users">
                        <Button @click="showCreateModal = true">
                            <Plus class="mr-2 h-4 w-4" />
                            Add User
                        </Button>
                    </PermissionGuard>
                </template>
            </PageHeader>

            <!-- Stats Cards -->
            <UserStats :statistics="props.statistics" />

            <!-- Filters Section -->
            <UserFiltersComponent
                :filters="localFilters"
                :user-types="props.userTypes"
                :schools="props.schools"
                :roles="props.roles"
                @update:filters="handleFiltersUpdate"
                @clear="clearFilters"
            />

            <!-- Users Table -->
            <UsersTable
                :users="props.users"
                :filters="localFilters"
                :selected-users="selectedUsers"
                :available-roles="props.roles"
                :is-loading="isLoading"
                @sort="handleSort"
                @delete="handleDelete"
                @restore="handleRestore"
                @select="toggleSelection"
                @select-all="selectAll"
                @clear-selection="clearSelection"
                @page-change="handlePageChange"
                @role-updated="handleUserUpdated"
            />
        </div>

        <!-- Create User Modal -->
        <PermissionGuard permission="create_users">
            <UserFormModal
                :open="showCreateModal"
                :user-types="props.userTypes"
                :schools="props.schools"
                :roles="props.roles"
                @update:open="showCreateModal = $event"
                @user-created="handleUserCreated"
            />
        </PermissionGuard>
    </AppLayout>
</template>
