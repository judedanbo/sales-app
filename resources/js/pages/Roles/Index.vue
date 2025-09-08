<script setup lang="ts">
import RoleFiltersComponent from '@/components/roles/RoleFilters.vue';
import RoleFormModal from '@/components/roles/RoleFormModal.vue';
import RolesTable from '@/components/roles/RolesTable.vue';
import RoleStats from '@/components/roles/RoleStats.vue';
import { Button } from '@/components/ui/button';
import PageHeader from '@/components/ui/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/roles';
import { type BreadcrumbItem, type PaginatedData, type Role, type RoleFilters, type RoleStatistics } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Shield } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Props {
    roles: PaginatedData<Role>;
    filters: RoleFilters;
    statistics: RoleStatistics;
    guardNames: string[];
    allPermissions: Permissions;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Roles',
        href: index().url,
    },
];

// Local filter state
const localFilters = ref<RoleFilters>({
    search: props.filters.search || '',
    guard_name: props.filters.guard_name || '',
    has_users: props.filters.has_users || '',
    sort_by: props.filters.sort_by || 'name',
    sort_direction: props.filters.sort_direction || 'asc',
});

const isLoading = ref(false);
const selectedRoles = ref<number[]>([]);
const showCreateModal = ref(false);

// Selection handlers
const toggleSelection = (roleId: number) => {
    const index = selectedRoles.value.indexOf(roleId);
    if (index > -1) {
        selectedRoles.value.splice(index, 1);
    } else {
        selectedRoles.value.push(roleId);
    }
};

const selectAll = () => {
    if (selectedRoles.value.length === props.roles.data.length) {
        selectedRoles.value = [];
    } else {
        selectedRoles.value = props.roles.data.map((role) => role.id);
    }
};

const clearSelection = () => {
    selectedRoles.value = [];
};

// Clear selection when page changes
watch(
    () => props.roles.current_page,
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
        } else if (newFilters.guard_name !== oldFilters?.guard_name || newFilters.has_users !== oldFilters?.has_users) {
            applyFilters();
        }
    },
    { deep: true },
);

// Filter out default values from filters
function getFilteredParameters(filters: RoleFilters) {
    const defaults = {
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

// Apply filters to the server
function applyFilters() {
    isLoading.value = true;
    router.reload({
        data: getFilteredParameters(localFilters.value),
        preserveScroll: true,
        only: ['roles'],
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
        only: ['roles'],
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
function handleDelete(role: Role) {
    if (confirm(`Are you sure you want to delete the role "${role.display_name || role.name}"?`)) {
        router.delete(`/roles/${role.id}`, {
            preserveScroll: true,
        });
    }
}

function handleFiltersUpdate(newFilters: RoleFilters) {
    localFilters.value = { ...newFilters };
}

function handleRoleCreated(role: Role) {
    // Refresh the page to show the new role while preserving filters
    router.reload({
        data: getFilteredParameters(localFilters.value),
        preserveScroll: true,
        only: ['roles', 'statistics'],
    });
}

function handlePageChange(page: number) {
    goToPage(page);
}

const clearFilters = () => {
    localFilters.value = {
        search: '',
        guard_name: '',
        has_users: '',
        sort_by: 'name',
        sort_direction: 'asc',
    };
    applyFilters();
};
</script>

<template>
    <Head title="Roles" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-8">
            <!-- Header Section -->
            <PageHeader title="Roles" description="Manage roles and their permissions">
                <template #action>
                    <Button @click="showCreateModal = true">
                        <Shield class="mr-2 h-4 w-4" />
                        Add Role
                    </Button>
                </template>
            </PageHeader>

            <!-- Stats Cards -->
            <!-- {{ props.statistics }} -->
            <RoleStats :statistics="props.statistics" />

            <!-- Filters Section -->
            <RoleFiltersComponent
                :filters="localFilters"
                :guard-names="props.guardNames"
                @update:filters="handleFiltersUpdate"
                @clear="clearFilters"
            />

            <!-- Roles Table -->
            <RolesTable
                :roles="props.roles"
                :filters="localFilters"
                :selected-roles="selectedRoles"
                :is-loading="isLoading"
                @sort="handleSort"
                @delete="handleDelete"
                @select="toggleSelection"
                @select-all="selectAll"
                @clear-selection="clearSelection"
                @page-change="handlePageChange"
            />
        </div>

        <!-- Create Role Modal -->
        <RoleFormModal
            :open="showCreateModal"
            :permissions="props.allPermissions"
            :guard-names="props.guardNames"
            @update:open="showCreateModal = $event"
            @role-created="handleRoleCreated"
        />
    </AppLayout>
</template>
