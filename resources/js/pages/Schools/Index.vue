<script setup lang="ts">
import PermissionGuard from '@/components/PermissionGuard.vue';
import SchoolCreateModal from '@/components/schools/SchoolCreateModal.vue';
import SchoolFiltersComponent from '@/components/schools/SchoolFilters.vue';
import SchoolsTable from '@/components/schools/SchoolsTable.vue';
import SchoolStats from '@/components/schools/SchoolStats.vue';
import { Button } from '@/components/ui/button';
import DeleteConfirmationModal from '@/components/ui/DeleteConfirmationModal.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import { useAlerts } from '@/composables/useAlerts';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/schools';
import { type BreadcrumbItem, type PaginatedData, type School, type SchoolFilters } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Plus } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const { addAlert } = useAlerts();

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

// Local filter state - initialize from props
const localFilters = ref<SchoolFilters>({
    search: props.filters.search || '',
    school_type: props.filters.school_type || '',
    status: props.filters.status || '',
    board_affiliation: props.filters.board_affiliation || '',
    sort_by: props.filters.sort_by || 'school_name',
    sort_direction: props.filters.sort_direction || 'asc',
});

// Update local filters when props change (after navigation)
watch(
    () => props.filters,
    (newFilters) => {
        localFilters.value = {
            search: newFilters.search || '',
            school_type: newFilters.school_type || '',
            status: newFilters.status || '',
            board_affiliation: newFilters.board_affiliation || '',
            sort_by: newFilters.sort_by || 'school_name',
            sort_direction: newFilters.sort_direction || 'asc',
        };
    },
    { immediate: true },
);

const isLoading = ref(false);
const selectedSchools = ref<number[]>([]);
const showCreateModal = ref(false);
const showDeleteModal = ref(false);
const schoolToDelete = ref<School | null>(null);
const isDeleting = ref(false);

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

// Debounced search function with longer delay to prevent early triggers
const debouncedSearch = useDebounceFn(() => {
    applyFilters();
}, 500);

// Watch for changes in local filters
watch(
    () => localFilters.value,
    (newFilters, oldFilters) => {
        // Use debounced search for search field, immediate for others
        if (newFilters.search !== oldFilters?.search) {
            debouncedSearch();
        } else if (
            newFilters.school_type !== oldFilters?.school_type ||
            newFilters.status !== oldFilters?.status ||
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
        status: '',
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

    // Include all current filter values, not just changed ones
    const params: Record<string, any> = {};

    if (localFilters.value.search) {
        params.search = localFilters.value.search;
    }
    if (localFilters.value.school_type) {
        params.school_type = localFilters.value.school_type;
    }
    if (localFilters.value.status) {
        params.status = localFilters.value.status;
    }
    if (localFilters.value.board_affiliation) {
        params.board_affiliation = localFilters.value.board_affiliation;
    }
    if (localFilters.value.sort_by && localFilters.value.sort_by !== 'school_name') {
        params.sort_by = localFilters.value.sort_by;
    }
    if (localFilters.value.sort_direction && localFilters.value.sort_direction !== 'asc') {
        params.sort_direction = localFilters.value.sort_direction;
    }

    router.get('/schools', params, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
}

// Handle pagination without URL parameters
function goToPage(page: number) {
    isLoading.value = true;

    // Include all current filter values
    const params: Record<string, any> = {};

    if (localFilters.value.search) {
        params.search = localFilters.value.search;
    }
    if (localFilters.value.school_type) {
        params.school_type = localFilters.value.school_type;
    }
    if (localFilters.value.status) {
        params.status = localFilters.value.status;
    }
    if (localFilters.value.board_affiliation) {
        params.board_affiliation = localFilters.value.board_affiliation;
    }
    if (localFilters.value.sort_by && localFilters.value.sort_by !== 'school_name') {
        params.sort_by = localFilters.value.sort_by;
    }
    if (localFilters.value.sort_direction && localFilters.value.sort_direction !== 'asc') {
        params.sort_direction = localFilters.value.sort_direction;
    }

    // Add page parameter if it's not the first page
    if (page > 1) {
        params.page = page;
    }

    router.get('/schools', params, {
        preserveScroll: true,
        preserveState: true,
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
    schoolToDelete.value = school;
    showDeleteModal.value = true;
}

const showAlert = (variant: 'success', message: string, title: string) => {
    addAlert(message, variant, {
        title: title,
    });
};

function confirmDelete() {
    if (!schoolToDelete.value) return;

    isDeleting.value = true;

    router.delete(`/schools/${schoolToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            // Successfully deleted, handled in onFinish
            showAlert('success', 'The School ' + schoolToDelete.value?.school_name + ' has been successfully deleted', 'School Deleted');
        },
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            schoolToDelete.value = null;
        },
    });
}

function cancelDelete() {
    showDeleteModal.value = false;
    schoolToDelete.value = null;
}

function handleFiltersUpdate(newFilters: SchoolFilters) {
    localFilters.value = { ...localFilters.value, ...newFilters };
}

function handleSchoolCreated(school: School) {
    // Refresh the page to show the new school while preserving filters
    const params: Record<string, any> = {};

    if (localFilters.value.search) {
        params.search = localFilters.value.search;
    }
    if (localFilters.value.school_type) {
        params.school_type = localFilters.value.school_type;
    }
    if (localFilters.value.status) {
        params.status = localFilters.value.status;
    }
    if (localFilters.value.board_affiliation) {
        params.board_affiliation = localFilters.value.board_affiliation;
    }
    if (localFilters.value.sort_by && localFilters.value.sort_by !== 'school_name') {
        params.sort_by = localFilters.value.sort_by;
    }
    if (localFilters.value.sort_direction && localFilters.value.sort_direction !== 'asc') {
        params.sort_direction = localFilters.value.sort_direction;
    }

    router.get('/schools', params, {
        preserveScroll: true,
        preserveState: true,
    });
}

function handlePageChange(page: number) {
    goToPage(page);
}

const clearFilters = () => {
    localFilters.value = {
        search: '',
        school_type: '',
        status: '',
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
                    <PermissionGuard permission="create_schools">
                        <Button @click="showCreateModal = true">
                            <Plus class="mr-2 h-4 w-4" />
                            Add School
                        </Button>
                    </PermissionGuard>
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
        <PermissionGuard permission="create_schools">
            <SchoolCreateModal :open="showCreateModal" @update:open="showCreateModal = $event" @school-created="handleSchoolCreated" />
        </PermissionGuard>

        <!-- Delete Confirmation Modal -->
        <DeleteConfirmationModal
            :open="showDeleteModal"
            :loading="isDeleting"
            title="Delete School"
            message="Are you sure you want to delete"
            :item-name="schoolToDelete?.school_name"
            danger-text="Delete School"
            @update:open="showDeleteModal = $event"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
        />
    </AppLayout>
</template>
