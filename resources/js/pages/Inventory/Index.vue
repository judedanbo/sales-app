<script setup lang="ts">
import InventoryDashboard from '@/components/products/InventoryDashboard.vue';
import LowStockAlerts from '@/components/products/LowStockAlerts.vue';
import StockAdjustmentModal from '@/components/products/StockAdjustmentModal.vue';
import StockMovementTable from '@/components/products/StockMovementTable.vue';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useAlerts } from '@/composables/useAlerts';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type InventoryStatistics, type PaginatedData, type Product, type ProductInventory, type StockMovement, type StockMovementFilters } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Download, Plus, Upload } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const { addAlert } = useAlerts();

interface LowStockProduct extends Product {
    inventory?: ProductInventory;
}

interface Props {
    statistics: InventoryStatistics;
    movements: PaginatedData<StockMovement>;
    lowStockProducts: LowStockProduct[];
    filters: StockMovementFilters;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Inventory',
        href: '/inventory',
    },
];

const activeTab = ref('dashboard');
const isLoading = ref(false);
const showAdjustmentModal = ref(false);
const selectedProduct = ref<Product | null>(null);
const selectedProductInventory = ref<ProductInventory | null>(null);

// Local filter state for movements
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

// Debounced filter function
const debouncedFilter = useDebounceFn(() => {
    applyFilters();
}, 500);

// Watch for changes in local filters
watch(
    () => localFilters.value,
    (newFilters, oldFilters) => {
        if (newFilters.search !== oldFilters?.search) {
            debouncedFilter();
        } else {
            applyFilters();
        }
    },
    { deep: true }
);

function applyFilters() {
    isLoading.value = true;

    const params: Record<string, any> = {};

    Object.entries(localFilters.value).forEach(([key, value]) => {
        if (value && value !== '') {
            params[key] = value;
        }
    });

    // Add tab parameter to maintain state
    params.tab = activeTab.value;

    router.get('/inventory', params, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
}

function handleSort(column: string) {
    if (localFilters.value.sort_by === column) {
        localFilters.value.sort_direction = localFilters.value.sort_direction === 'asc' ? 'desc' : 'asc';
    } else {
        localFilters.value.sort_by = column;
        localFilters.value.sort_direction = 'asc';
    }
}

function handlePageChange(page: number) {
    const params: Record<string, any> = {};

    Object.entries(localFilters.value).forEach(([key, value]) => {
        if (value && value !== '') {
            params[key] = value;
        }
    });

    params.page = page;
    params.tab = activeTab.value;

    router.get('/inventory', params, {
        preserveScroll: true,
        preserveState: true,
    });
}

function handleUpdateFilters(newFilters: StockMovementFilters) {
    localFilters.value = { ...localFilters.value, ...newFilters };
}

function handleViewLowStock() {
    activeTab.value = 'low-stock';
}

function handleViewMovements() {
    activeTab.value = 'movements';
}

function handleViewCategory(categoryId: number) {
    router.visit(`/categories/${categoryId}`);
}

function handleViewProduct(productId: number) {
    router.visit(`/products/${productId}`);
}

function handleReorderProduct(product: LowStockProduct) {
    // In a real implementation, this would create a purchase order
    addAlert(
        `Reorder initiated for ${product.name}`,
        'success',
        { title: 'Reorder Created' }
    );
}

function handleAdjustStock(product: LowStockProduct) {
    selectedProduct.value = product;
    selectedProductInventory.value = product.inventory || null;
    showAdjustmentModal.value = true;
}

function handleCreatePurchaseOrder(products: LowStockProduct[]) {
    // In a real implementation, this would create a purchase order
    addAlert(
        `Purchase order created for ${products.length} products`,
        'success',
        { title: 'Purchase Order Created' }
    );
}

function handleViewMovementDetails(movement: StockMovement) {
    // In a real implementation, this would show a detailed modal
    addAlert(
        `Viewing details for movement #${movement.id}`,
        'info',
        { title: 'Movement Details' }
    );
}

function handleAdjustmentCreated() {
    // Refresh the page to show updated data
    router.reload();
}

function handleExportInventory() {
    // In a real implementation, this would generate an export
    addAlert(
        'Inventory export started. You will receive an email when ready.',
        'info',
        { title: 'Export Started' }
    );
}

function handleImportInventory() {
    // In a real implementation, this would open an import modal
    addAlert(
        'Import functionality coming soon',
        'info',
        { title: 'Coming Soon' }
    );
}

function handleBulkAdjustment() {
    // In a real implementation, this would open a bulk adjustment modal
    addAlert(
        'Bulk adjustment functionality coming soon',
        'info',
        { title: 'Coming Soon' }
    );
}
</script>

<template>
    <Head title="Inventory Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-8">
            <!-- Header Section -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Inventory Management</h1>
                    <p class="text-muted-foreground">
                        Monitor stock levels, track movements, and manage inventory across all products.
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="handleExportInventory">
                        <Download class="h-4 w-4 mr-2" />
                        Export
                    </Button>
                    <Button variant="outline" @click="handleImportInventory">
                        <Upload class="h-4 w-4 mr-2" />
                        Import
                    </Button>
                    <Button @click="handleBulkAdjustment">
                        <Plus class="h-4 w-4 mr-2" />
                        Bulk Adjustment
                    </Button>
                </div>
            </div>

            <!-- Tabs -->
            <Tabs v-model="activeTab" class="space-y-6">
                <TabsList class="grid w-full grid-cols-3">
                    <TabsTrigger value="dashboard">Dashboard</TabsTrigger>
                    <TabsTrigger value="movements">Stock Movements</TabsTrigger>
                    <TabsTrigger value="low-stock">Low Stock Alerts</TabsTrigger>
                </TabsList>

                <!-- Dashboard Tab -->
                <TabsContent value="dashboard" class="space-y-6">
                    <InventoryDashboard
                        :statistics="statistics"
                        :is-loading="isLoading"
                        @view-low-stock="handleViewLowStock"
                        @view-movements="handleViewMovements"
                        @view-category="handleViewCategory"
                        @view-product="handleViewProduct"
                    />
                </TabsContent>

                <!-- Stock Movements Tab -->
                <TabsContent value="movements" class="space-y-6">
                    <StockMovementTable
                        :movements="movements"
                        :filters="localFilters"
                        :is-loading="isLoading"
                        @update:filters="handleUpdateFilters"
                        @sort="handleSort"
                        @page-change="handlePageChange"
                        @view-details="handleViewMovementDetails"
                    />
                </TabsContent>

                <!-- Low Stock Alerts Tab -->
                <TabsContent value="low-stock" class="space-y-6">
                    <LowStockAlerts
                        :products="lowStockProducts"
                        :is-loading="isLoading"
                        @reorder-product="handleReorderProduct"
                        @adjust-stock="handleAdjustStock"
                        @view-product="handleViewProduct"
                        @create-purchase-order="handleCreatePurchaseOrder"
                    />
                </TabsContent>
            </Tabs>
        </div>

        <!-- Stock Adjustment Modal -->
        <StockAdjustmentModal
            :open="showAdjustmentModal"
            :product="selectedProduct"
            :inventory="selectedProductInventory"
            @update:open="showAdjustmentModal = $event"
            @adjustment-created="handleAdjustmentCreated"
        />
    </AppLayout>
</template>