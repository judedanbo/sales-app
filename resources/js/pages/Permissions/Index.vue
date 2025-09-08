<script setup lang="ts">
import PermissionFiltersComponent from '@/components/permissions/PermissionFilters.vue';
import PermissionsTable from '@/components/permissions/PermissionsTable.vue';
import PermissionStats from '@/components/permissions/PermissionStats.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/permissions';
import { type BreadcrumbItem, type PaginatedData, type Permission, type PermissionFilters, type PermissionStatistics } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { ref, watch } from 'vue';

interface Props {
    permissions: PaginatedData<Permission>;
    filters: PermissionFilters;
    statistics: PermissionStatistics;
    guardNames: string[];
    categories: Array<{ value: string; label: string; display_name: string }>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Permissions',
        href: index().url,
    },
];

// Local filter state
const localFilters = ref<PermissionFilters>({
    search: props.filters.search || '',
    guard_name: props.filters.guard_name || '',
    category: props.filters.category || '',
    has_roles: props.filters.has_roles || '',
    sort_by: props.filters.sort_by || 'name',
    sort_direction: props.filters.sort_direction || 'asc',
});

const isLoading = ref(false);
const selectedPermissions = ref<number[]>([]);

// Selection handlers
const toggleSelection = (permissionId: number) => {
    const index = selectedPermissions.value.indexOf(permissionId);
    if (index > -1) {
        selectedPermissions.value.splice(index, 1);
    } else {
        selectedPermissions.value.push(permissionId);
    }
};

const selectAll = () => {
    if (selectedPermissions.value.length === props.permissions.data.length) {
        selectedPermissions.value = [];
    } else {
        selectedPermissions.value = props.permissions.data.map((permission) => permission.id);
    }
};

const clearSelection = () => {
    selectedPermissions.value = [];
};

// Clear selection when page changes
watch(
    () => props.permissions.current_page,
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
            newFilters.guard_name !== oldFilters?.guard_name ||
            newFilters.category !== oldFilters?.category ||
            newFilters.has_roles !== oldFilters?.has_roles
        ) {
            applyFilters();
        }
    },
    { deep: true },
);

// Filter out default values from filters
function getFilteredParameters(filters: PermissionFilters) {
    const defaults = {
        search: '',
        guard_name: '',
        category: '',
        has_roles: '',
        sort_by: 'name',
        sort_direction: 'asc',
    };

    const filtered: Partial<PermissionFilters> = {};

    Object.entries(filters).forEach(([key, value]) => {
        const typedKey = key as keyof PermissionFilters;
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
        only: ['permissions'],
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
        only: ['permissions'],
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
function handleFiltersUpdate(newFilters: PermissionFilters) {
    localFilters.value = { ...newFilters };
}

function handlePageChange(page: number) {
    goToPage(page);
}

const clearFilters = () => {
    localFilters.value = {
        search: '',
        guard_name: '',
        category: '',
        has_roles: '',
        sort_by: 'name',
        sort_direction: 'asc',
    };
    applyFilters();
};
</script>

<template>
    <Head title="Permissions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-8">
            <!-- Header Section -->
            <PageHeader title="Permissions" description="View and manage system permissions">
                <!-- Permissions are typically not created manually, so no add button -->
            </PageHeader>

            <!-- Stats Cards -->
            <PermissionStats :statistics="props.statistics" />

            <!-- Filters Section -->
            <PermissionFiltersComponent
                :filters="localFilters"
                :guard-names="props.guardNames"
                :categories="props.categories"
                @update:filters="handleFiltersUpdate"
                @clear="clearFilters"
            />

            <!-- Permissions Table -->
            <PermissionsTable
                :permissions="props.permissions"
                :filters="localFilters"
                :selected-permissions="selectedPermissions"
                :is-loading="isLoading"
                @sort="handleSort"
                @select="toggleSelection"
                @select-all="selectAll"
                @clear-selection="clearSelection"
                @page-change="handlePageChange"
            />
        </div>
    </AppLayout>
</template>
