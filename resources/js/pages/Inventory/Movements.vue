<script setup lang="ts">
import StockMovementTable from '@/components/products/StockMovementTable.vue';
import { Button } from '@/components/ui/button';
import PageHeader from '@/components/ui/PageHeader.vue';
import { useAlerts } from '@/composables/useAlerts';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type PaginatedData, type StockMovement, type StockMovementFilters } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { ArrowLeft, Download, FileSpreadsheet } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const { addAlert } = useAlerts();

interface Props {
    movements: PaginatedData<StockMovement>;
    filters: StockMovementFilters;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Inventory',
        href: '/inventory',
    },
    {
        title: 'Stock Movements',
        href: '/inventory/movements',
    },
];

const isLoading = ref(false);

// Local filter state
const localFilters = ref<StockMovementFilters>({
    search: props.filters.search || '',
    product_id: props.filters.product_id || '',
    variant_id: props.filters.variant_id || '',
    movement_type: props.filters.movement_type || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    created_by: props.filters.created_by || '',
    reference_type: props.filters.reference_type || '',
    sort_by: props.filters.sort_by || 'movement_date',
    sort_direction: props.filters.sort_direction || 'desc',
});

// Debounced filter updates
const debouncedFilterUpdate = useDebounceFn((filters: StockMovementFilters) => {
    isLoading.value = true;
    router.get('/inventory/movements', filters, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            isLoading.value = false;
        },
        onError: () => {
            addAlert('Failed to load stock movements. Please try again.', 'destructive', {
                title: 'Load Error'
            });
        }
    });
}, 300);

// Watch for filter changes
watch(localFilters, (newFilters) => {
    debouncedFilterUpdate(newFilters);
}, { deep: true });

// Event handlers
const handleFiltersUpdate = (filters: StockMovementFilters) => {
    localFilters.value = { ...filters };
};

const handleSort = (column: string) => {
    if (localFilters.value.sort_by === column) {
        localFilters.value.sort_direction = localFilters.value.sort_direction === 'asc' ? 'desc' : 'asc';
    } else {
        localFilters.value.sort_by = column;
        localFilters.value.sort_direction = 'desc';
    }
};

const handlePageChange = (page: number) => {
    router.get('/inventory/movements', {
        ...localFilters.value,
        page
    }, {
        preserveState: true,
        preserveScroll: false,
    });
};

const handleViewDetails = (movement: StockMovement) => {
    // TODO: Implement movement details modal or page
    addAlert(`Viewing details for movement #${movement.id}`, 'info', {
        title: 'Movement Details'
    });
};

const handleGoBack = () => {
    router.visit('/inventory');
};

const handleExportMovements = () => {
    // TODO: Implement export functionality
    addAlert('Export functionality coming soon', 'info', {
        title: 'Export'
    });
};
</script>

<template>
    <Head title="Stock Movements" />

    <AppLayout>
        <div class="space-y-6">
            <PageHeader
                title="Stock Movements"
                description="Detailed view of all inventory transactions and stock changes"
                :breadcrumbs="breadcrumbs"
            >
                <template #actions>
                    <Button variant="outline" @click="handleGoBack">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to Dashboard
                    </Button>
                    <Button variant="outline" @click="handleExportMovements">
                        <FileSpreadsheet class="mr-2 h-4 w-4" />
                        Export
                    </Button>
                </template>
            </PageHeader>

            <StockMovementTable
                :movements="movements"
                :filters="localFilters"
                :is-loading="isLoading"
                @update:filters="handleFiltersUpdate"
                @sort="handleSort"
                @page-change="handlePageChange"
                @view-details="handleViewDetails"
            />
        </div>
    </AppLayout>
</template>