<script setup lang="ts">
import PermissionGuard from '@/components/PermissionGuard.vue';
import ProductCreateModal from '@/components/products/ProductCreateModal.vue';
import ProductFiltersComponent from '@/components/products/ProductFilters.vue';
import ProductsTable from '@/components/products/ProductsTable.vue';
import ProductStats from '@/components/products/ProductStats.vue';
import { Button } from '@/components/ui/button';
import DeleteConfirmationModal from '@/components/ui/DeleteConfirmationModal.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import { useAlerts } from '@/composables/useAlerts';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type PaginatedData, type Product, type ProductFilters, type ProductStatistics } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Plus } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const { addAlert } = useAlerts();

interface Props {
    products: PaginatedData<Product>;
    filters: ProductFilters;
    statistics: ProductStatistics;
    categories: Array<{ value: number; label: string }>;
    unitTypes: Array<{ value: string; label: string }>;
    brands: Array<{ value: string; label: string }>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Products',
        href: '/products',
    },
];

// Local filter state - initialize from props
const localFilters = ref<ProductFilters>({
    search: props.filters.search || '',
    category_id: props.filters.category_id || '',
    status: props.filters.status || '',
    price_from: props.filters.price_from || '',
    price_to: props.filters.price_to || '',
    low_stock: props.filters.low_stock || '',
    unit_type: props.filters.unit_type || '',
    brand: props.filters.brand || '',
    sort_by: props.filters.sort_by || 'name',
    sort_direction: props.filters.sort_direction || 'asc',
});

// Update local filters when props change (after navigation)
watch(
    () => props.filters,
    (newFilters) => {
        localFilters.value = {
            search: newFilters.search || '',
            category_id: newFilters.category_id || '',
            status: newFilters.status || '',
            price_from: newFilters.price_from || '',
            price_to: newFilters.price_to || '',
            low_stock: newFilters.low_stock || '',
            unit_type: newFilters.unit_type || '',
            brand: newFilters.brand || '',
            sort_by: newFilters.sort_by || 'name',
            sort_direction: newFilters.sort_direction || 'asc',
        };
    },
    { immediate: true },
);

const isLoading = ref(false);
const selectedProducts = ref<number[]>([]);
const showCreateModal = ref(false);
const showDeleteModal = ref(false);
const productToDelete = ref<Product | null>(null);
const isDeleting = ref(false);

// Selection handlers
const toggleSelection = (productId: number) => {
    const index = selectedProducts.value.indexOf(productId);
    if (index > -1) {
        selectedProducts.value.splice(index, 1);
    } else {
        selectedProducts.value.push(productId);
    }
};

const selectAll = () => {
    if (selectedProducts.value.length === props.products.data.length) {
        selectedProducts.value = [];
    } else {
        selectedProducts.value = props.products.data.map((product) => product.id);
    }
};

const clearSelection = () => {
    selectedProducts.value = [];
};

// Clear selection when page changes
watch(
    () => props.products.current_page,
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
            newFilters.category_id !== oldFilters?.category_id ||
            newFilters.status !== oldFilters?.status ||
            newFilters.price_from !== oldFilters?.price_from ||
            newFilters.price_to !== oldFilters?.price_to ||
            newFilters.low_stock !== oldFilters?.low_stock ||
            newFilters.unit_type !== oldFilters?.unit_type ||
            newFilters.brand !== oldFilters?.brand
        ) {
            applyFilters();
        }
    },
    { deep: true },
);

// Filter out default values from filters
function getFilteredParameters(filters: ProductFilters) {
    const defaults = {
        search: '',
        category_id: '',
        status: '',
        price_from: '',
        price_to: '',
        low_stock: '',
        unit_type: '',
        brand: '',
        sort_by: 'name',
        sort_direction: 'asc',
    };

    const filtered: Partial<ProductFilters> = {};

    Object.entries(filters).forEach(([key, value]) => {
        const typedKey = key as keyof ProductFilters;
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
    if (localFilters.value.category_id) {
        params.category_id = localFilters.value.category_id;
    }
    if (localFilters.value.status) {
        params.status = localFilters.value.status;
    }
    if (localFilters.value.price_from) {
        params.price_from = localFilters.value.price_from;
    }
    if (localFilters.value.price_to) {
        params.price_to = localFilters.value.price_to;
    }
    if (localFilters.value.low_stock) {
        params.low_stock = localFilters.value.low_stock;
    }
    if (localFilters.value.unit_type) {
        params.unit_type = localFilters.value.unit_type;
    }
    if (localFilters.value.brand) {
        params.brand = localFilters.value.brand;
    }
    if (localFilters.value.sort_by && localFilters.value.sort_by !== 'name') {
        params.sort_by = localFilters.value.sort_by;
    }
    if (localFilters.value.sort_direction && localFilters.value.sort_direction !== 'asc') {
        params.sort_direction = localFilters.value.sort_direction;
    }

    router.get('/products', params, {
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
    if (localFilters.value.category_id) {
        params.category_id = localFilters.value.category_id;
    }
    if (localFilters.value.status) {
        params.status = localFilters.value.status;
    }
    if (localFilters.value.price_from) {
        params.price_from = localFilters.value.price_from;
    }
    if (localFilters.value.price_to) {
        params.price_to = localFilters.value.price_to;
    }
    if (localFilters.value.low_stock) {
        params.low_stock = localFilters.value.low_stock;
    }
    if (localFilters.value.unit_type) {
        params.unit_type = localFilters.value.unit_type;
    }
    if (localFilters.value.brand) {
        params.brand = localFilters.value.brand;
    }
    if (localFilters.value.sort_by && localFilters.value.sort_by !== 'name') {
        params.sort_by = localFilters.value.sort_by;
    }
    if (localFilters.value.sort_direction && localFilters.value.sort_direction !== 'asc') {
        params.sort_direction = localFilters.value.sort_direction;
    }

    // Add page parameter if it's not the first page
    if (page > 1) {
        params.page = page;
    }

    router.get('/products', params, {
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
function handleDelete(product: Product) {
    productToDelete.value = product;
    showDeleteModal.value = true;
}

const showAlert = (variant: 'success' | 'error', message: string, title: string) => {
    addAlert(message, variant, {
        title: title,
    });
};

function confirmDelete() {
    if (!productToDelete.value) return;

    isDeleting.value = true;

    router.delete(`/products/${productToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showAlert('success', `The product "${productToDelete.value?.name}" has been successfully deleted.`, 'Product Deleted');
        },
        onError: (errors) => {
            const errorMessage = Object.values(errors).flat().join(', ');
            showAlert('error', errorMessage || 'Failed to delete product. Please try again.', 'Delete Failed');
        },
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
            productToDelete.value = null;
        },
    });
}

function cancelDelete() {
    showDeleteModal.value = false;
    productToDelete.value = null;
}

function handleFiltersUpdate(newFilters: ProductFilters) {
    localFilters.value = { ...localFilters.value, ...newFilters };
}

function handleProductCreated(product: Product) {
    // Refresh the page to show the new product while preserving filters
    const params: Record<string, any> = {};

    if (localFilters.value.search) {
        params.search = localFilters.value.search;
    }
    if (localFilters.value.category_id) {
        params.category_id = localFilters.value.category_id;
    }
    if (localFilters.value.status) {
        params.status = localFilters.value.status;
    }
    if (localFilters.value.price_from) {
        params.price_from = localFilters.value.price_from;
    }
    if (localFilters.value.price_to) {
        params.price_to = localFilters.value.price_to;
    }
    if (localFilters.value.low_stock) {
        params.low_stock = localFilters.value.low_stock;
    }
    if (localFilters.value.unit_type) {
        params.unit_type = localFilters.value.unit_type;
    }
    if (localFilters.value.brand) {
        params.brand = localFilters.value.brand;
    }
    if (localFilters.value.sort_by && localFilters.value.sort_by !== 'name') {
        params.sort_by = localFilters.value.sort_by;
    }
    if (localFilters.value.sort_direction && localFilters.value.sort_direction !== 'asc') {
        params.sort_direction = localFilters.value.sort_direction;
    }

    router.get('/products', params, {
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
        category_id: '',
        status: '',
        price_from: '',
        price_to: '',
        low_stock: '',
        unit_type: '',
        brand: '',
        sort_by: 'name',
        sort_direction: 'asc',
    };
    applyFilters();
};
</script>

<template>
    <Head title="Products" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-8">
            <!-- Header Section -->
            <PageHeader title="Products" description="Manage your product catalog and inventory">
                <template #action>
                    <PermissionGuard permission="create_products">
                        <Button @click="showCreateModal = true">
                            <Plus class="mr-2 h-4 w-4" />
                            Add Product
                        </Button>
                    </PermissionGuard>
                </template>
            </PageHeader>

            <!-- Stats Cards -->
            <ProductStats :statistics="props.statistics" />

            <!-- Filters Section -->
            <ProductFiltersComponent
                :filters="localFilters"
                :categories="props.categories"
                :unit-types="props.unitTypes"
                :brands="props.brands"
                @update:filters="handleFiltersUpdate"
                @clear="clearFilters"
            />

            <!-- Products Table -->
            <ProductsTable
                :products="props.products"
                :filters="localFilters"
                :selected-products="selectedProducts"
                :is-loading="isLoading"
                @sort="handleSort"
                @delete="handleDelete"
                @select="toggleSelection"
                @select-all="selectAll"
                @clear-selection="clearSelection"
                @page-change="handlePageChange"
            />
        </div>

        <!-- Create Product Modal -->
        <PermissionGuard permission="create_products">
            <ProductCreateModal :open="showCreateModal" @update:open="showCreateModal = $event" @product-created="handleProductCreated" />
        </PermissionGuard>

        <!-- Delete Confirmation Modal -->
        <DeleteConfirmationModal
            :open="showDeleteModal"
            :loading="isDeleting"
            title="Delete Product"
            message="Are you sure you want to delete"
            :item-name="productToDelete?.name"
            danger-text="Delete Product"
            @update:open="showDeleteModal = $event"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
        />
    </AppLayout>
</template>
