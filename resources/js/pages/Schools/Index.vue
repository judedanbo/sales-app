<script setup lang="ts">
import SchoolCreateModal from '@/components/schools/SchoolCreateModal.vue';
import SchoolFiltersComponent from '@/components/schools/SchoolFilters.vue';
import SchoolsTable from '@/components/schools/SchoolsTable.vue';
import SchoolStats from '@/components/schools/SchoolStats.vue';
import { Button } from '@/components/ui/button';
import PageHeader from '@/components/ui/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/schools';
import { type BreadcrumbItem, type PaginatedData, type School, type SchoolFilters } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Plus } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Props {
    schools: PaginatedData<School>;
    filters: SchoolFilters;
    schoolTypes: Record<string, string>;
    boardAffiliations: Record<string, string>;
    schoolStatuses: Array<{ value: number; label: string }>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Schools',
        href: index().url,
    },
];

// Local filter state
const localFilters = ref<SchoolFilters>({
    search: props.filters.search || '',
    school_type: props.filters.school_type || '',
    is_active: props.filters.is_active || '',
    board_affiliation: props.filters.board_affiliation || '',
    sort_by: props.filters.sort_by || 'school_name',
    sort_direction: props.filters.sort_direction || 'asc',
});

const isLoading = ref(false);
const selectedSchools = ref<number[]>([]);
const showCreateModal = ref(false);

// Selection handlers
const toggleSelection = (schoolId: number) => {
    const index = selectedSchools.value.indexOf(schoolId);
    if (index > -1) {
        selectedSchools.value.splice(index, 1);
    } else {
        selectedSchools.value.push(schoolId);
    }
};

const selectAll = () => {
    if (selectedSchools.value.length === props.schools.data.length) {
        selectedSchools.value = [];
    } else {
        selectedSchools.value = props.schools.data.map((school) => school.id);
    }
};

const clearSelection = () => {
    selectedSchools.value = [];
};

// Clear selection when page changes
watch(
    () => props.schools.current_page,
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
            newFilters.school_type !== oldFilters?.school_type ||
            newFilters.is_active !== oldFilters?.is_active ||
            newFilters.board_affiliation !== oldFilters?.board_affiliation
        ) {
            applyFilters();
        }
    },
    { deep: true },
);

// Filter out default values from filters
function getFilteredParameters(filters: SchoolFilters) {
    const defaults = {
        search: '',
        school_type: '',
        is_active: '',
        board_affiliation: '',
        sort_by: 'school_name',
        sort_direction: 'asc',
    };

    const filtered: Partial<SchoolFilters> = {};

    Object.entries(filters).forEach(([key, value]) => {
        const typedKey = key as keyof SchoolFilters;
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
        only: ['schools'],
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
        only: ['schools'],
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
function handleDelete(school: School) {
    if (confirm(`Are you sure you want to delete ${school.school_name}?`)) {
        router.delete(`/schools/${school.id}`, {
            preserveScroll: true,
        });
    }
}

function handleFiltersUpdate(newFilters: SchoolFilters) {
    localFilters.value = { ...newFilters };
}

function handleSchoolCreated(school: School) {
    // Refresh the page to show the new school

    router.reload({ only: ['schools'] });
}

function handlePageChange(page: number) {
    goToPage(page);
}

const clearFilters = () => {
    localFilters.value = {
        search: '',
        school_type: '',
        is_active: '',
        board_affiliation: '',
        sort_by: 'school_name',
        sort_direction: 'asc',
    };
    applyFilters();
};
</script>

<template>
    <Head title="Schools" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-8">
            <!-- Header Section -->
            <PageHeader title="Schools" description="Manage your schools and their information">
                <template #action>
                    <Button @click="showCreateModal = true">
                        <Plus class="mr-2 h-4 w-4" />
                        Add School
                    </Button>
                </template>
            </PageHeader>

            <!-- Stats Cards -->
            <SchoolStats :schools="props.schools.data" :total-count="props.schools.total" />

            <!-- Filters Section -->
            <SchoolFiltersComponent
                :filters="localFilters"
                :school-types="props.schoolTypes"
                :board-affiliations="props.boardAffiliations"
                :school-statuses="props.schoolStatuses"
                @update:filters="handleFiltersUpdate"
                @clear="clearFilters"
            />

            <!-- Schools Table -->
            <SchoolsTable
                :schools="props.schools"
                :filters="localFilters"
                :selected-schools="selectedSchools"
                :is-loading="isLoading"
                @sort="handleSort"
                @delete="handleDelete"
                @select="toggleSelection"
                @select-all="selectAll"
                @clear-selection="clearSelection"
                @page-change="handlePageChange"
            />
        </div>

        <!-- Create School Modal -->
        <SchoolCreateModal :open="showCreateModal" @update:open="showCreateModal = $event" @school-created="handleSchoolCreated" />
    </AppLayout>
</template>
