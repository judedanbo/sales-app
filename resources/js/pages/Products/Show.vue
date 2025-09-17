<script setup lang="ts">
import PermissionGuard from '@/components/PermissionGuard.vue';
import ProductEditModal from '@/components/products/ProductEditModal.vue';
import Badge from '@/components/ui/badge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import DeleteConfirmationModal from '@/components/ui/DeleteConfirmationModal.vue';
import ImageUpload from '@/components/ui/ImageUpload.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import { useAlerts } from '@/composables/useAlerts';
import { useCurrency } from '@/composables/useCurrency';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Product } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Archive,
    BarChart3,
    Calendar,
    DollarSign,
    Edit,
    Globe,
    Hash,
    Image as ImageIcon,
    Package,
    Palette,
    Ruler,
    Tag,
    Trash2,
    TrendingUp,
    User,
    Warehouse,
    Weight,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    product: Product;
}

const props = defineProps<Props>();

const { success, error } = useAlerts();
const { hasPermission } = usePermissions();

const isDeleting = ref(false);
const showDeleteModal = ref(false);
const showEditModal = ref(false);
const activeTab = ref('overview');

// Reactive product data to handle image updates
const reactiveProduct = ref<Product>({ ...props.product });

// Check if user can edit products
const canEditProducts = computed(() => hasPermission('edit_products'));

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Products',
        href: '/products',
    },
    {
        title: props.product.name,
        href: `/products/${props.product.id}`,
    },
];

// Computed properties
const hasInventory = computed(() => !!reactiveProduct.value.inventory);
const hasLowStock = computed(() => reactiveProduct.value.low_stock);
const hasDimensions = computed(
    () => reactiveProduct.value.dimensions?.length || reactiveProduct.value.dimensions?.width || reactiveProduct.value.dimensions?.height,
);
const hasTags = computed(() => reactiveProduct.value.tags && reactiveProduct.value.tags.length > 0);

// Event handlers
const handleEdit = () => {
    showEditModal.value = true;
};

const handleDelete = () => {
    showDeleteModal.value = true;
};

const handleProductUpdated = (updatedProduct: Product) => {
    // Update the reactive product data with the updated product
    reactiveProduct.value = { ...updatedProduct };
    success(`Product "${updatedProduct.name}" has been updated successfully!`, {
        position: 'top-center',
        duration: 4000,
    });
};

const confirmDelete = () => {
    isDeleting.value = true;

    router.delete(`/products/${props.product.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            success(`Product "${props.product.name}" has been successfully deleted.`, {
                position: 'top-center',
                duration: 4000,
            });
            router.get('/products');
        },
        onError: (errors) => {
            const errorMessage = Object.values(errors).flat().join(', ');
            error(errorMessage || 'Failed to delete product. Please try again.', {
                position: 'top-center',
                priority: 'high',
            });
        },
        onFinish: () => {
            isDeleting.value = false;
            showDeleteModal.value = false;
        },
    });
};

const cancelDelete = () => {
    showDeleteModal.value = false;
};

const handleImageUploaded = (imageUrl: string) => {
    reactiveProduct.value = { ...reactiveProduct.value, image_url: imageUrl };
};

const handleImageDeleted = () => {
    reactiveProduct.value = { ...reactiveProduct.value, image_url: undefined };
};

// Format helpers
const { formatCurrency: formatPrice } = useCurrency();

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatWeight = (weight?: number): string => {
    if (!weight) return 'Not specified';
    return `${weight} kg`;
};

const formatDimensions = () => {
    if (!reactiveProduct.value.dimensions) return 'Not specified';
    const { length, width, height } = reactiveProduct.value.dimensions;
    if (!length && !width && !height) return 'Not specified';

    const parts = [];
    if (length) parts.push(`L: ${length}cm`);
    if (width) parts.push(`W: ${width}cm`);
    if (height) parts.push(`H: ${height}cm`);

    return parts.join(' × ');
};

const getStatusBadgeVariant = (status: string): 'default' | 'secondary' | 'destructive' => {
    switch (status) {
        case 'active':
            return 'default';
        case 'inactive':
            return 'secondary';
        case 'discontinued':
            return 'destructive';
        default:
            return 'secondary';
    }
};

const getStockStatusColor = () => {
    if (!hasInventory.value) return 'text-muted-foreground';
    if (reactiveProduct.value.inventory?.is_out_of_stock) return 'text-red-600';
    if (hasLowStock.value) return 'text-orange-600';
    return 'text-green-600';
};

const getStockStatusText = () => {
    if (!hasInventory.value) return 'No inventory data';
    if (reactiveProduct.value.inventory?.is_out_of_stock) return 'Out of Stock';
    if (hasLowStock.value) return 'Low Stock';
    return 'In Stock';
};
</script>

<template>
    <Head :title="reactiveProduct.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-8">
            <!-- Header -->
            <PageHeader :title="reactiveProduct.name" :description="`SKU: ${reactiveProduct.sku}`">
                <template #action>
                    <div class="flex items-center gap-2">
                        <PermissionGuard permission="edit_products">
                            <Button variant="outline" @click="handleEdit">
                                <Edit class="mr-2 h-4 w-4" />
                                Edit Product
                            </Button>
                        </PermissionGuard>

                        <PermissionGuard permission="delete_products">
                            <Button variant="destructive" @click="handleDelete" :disabled="isDeleting">
                                <Trash2 class="mr-2 h-4 w-4" />
                                {{ isDeleting ? 'Deleting...' : 'Delete' }}
                            </Button>
                        </PermissionGuard>
                    </div>
                </template>
            </PageHeader>

            <!-- Tab Navigation -->
            <div class="border-b border-gray-200">
                <nav class="flex space-x-8" aria-label="Tabs">
                    <button
                        @click="activeTab = 'overview'"
                        :class="[
                            'flex items-center gap-2 py-2 px-1 border-b-2 font-medium text-sm',
                            activeTab === 'overview'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        <Package class="h-4 w-4" />
                        Overview
                    </button>
                    <button
                        @click="activeTab = 'variants'"
                        :class="[
                            'flex items-center gap-2 py-2 px-1 border-b-2 font-medium text-sm',
                            activeTab === 'variants'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        <Package class="h-4 w-4" />
                        Variants
                    </button>
                    <button
                        @click="activeTab = 'pricing'"
                        :class="[
                            'flex items-center gap-2 py-2 px-1 border-b-2 font-medium text-sm',
                            activeTab === 'pricing'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        <DollarSign class="h-4 w-4" />
                        Pricing
                    </button>
                    <button
                        @click="activeTab = 'inventory'"
                        :class="[
                            'flex items-center gap-2 py-2 px-1 border-b-2 font-medium text-sm',
                            activeTab === 'inventory'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]"
                    >
                        <Warehouse class="h-4 w-4" />
                        Inventory
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="mt-6">
                <!-- Overview Tab -->
                <div v-if="activeTab === 'overview'" class="space-y-6">
                    <div class="grid gap-6 md:grid-cols-3">
                        <!-- Product Image -->
                        <div class="md:col-span-1">
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <ImageIcon class="h-5 w-5" />
                                        Product Image
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <ImageUpload
                                        :current-image="reactiveProduct.image_url || undefined"
                                        :upload-url="`/products/${product.id}/image`"
                                        :delete-url="reactiveProduct.image_url ? `/products/${product.id}/image` : undefined"
                                        :alt="reactiveProduct.name"
                                        :disabled="!canEditProducts"
                                        @uploaded="handleImageUploaded"
                                        @deleted="handleImageDeleted"
                                    />
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Main Information -->
                        <div class="space-y-6 md:col-span-2">
                            <!-- Basic Information -->
                            <Card>
                                <CardHeader>
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <CardTitle class="flex items-center gap-2">
                                                <Package class="h-5 w-5" />
                                                {{ reactiveProduct.name }}
                                            </CardTitle>
                                            <CardDescription>{{ reactiveProduct.description || 'No description provided' }}</CardDescription>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <Badge :variant="getStatusBadgeVariant(reactiveProduct.status)">
                                                {{ reactiveProduct.status }}
                                            </Badge>
                                            <Badge v-if="hasLowStock" variant="destructive"> Low Stock </Badge>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                                        <div class="space-y-2">
                                            <div class="text-sm font-medium text-muted-foreground">SKU</div>
                                            <div class="flex items-center gap-2">
                                                <Hash class="h-4 w-4" />
                                                {{ reactiveProduct.sku }}
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <div class="text-sm font-medium text-muted-foreground">Category</div>
                                            <div class="flex items-center gap-2">
                                                <Tag class="h-4 w-4" />
                                                {{ reactiveProduct.category?.name || 'No category' }}
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <div class="text-sm font-medium text-muted-foreground">Unit Price</div>
                                            <div class="flex items-center gap-2">
                                                <DollarSign class="h-4 w-4" />
                                                <span class="text-lg font-semibold">{{ formatPrice(reactiveProduct.unit_price) }}</span>
                                                <span class="text-sm text-muted-foreground">per {{ reactiveProduct.unit_type }}</span>
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <div class="text-sm font-medium text-muted-foreground">Tax Rate</div>
                                            <div class="flex items-center gap-2">
                                                <TrendingUp class="h-4 w-4" />
                                                {{ reactiveProduct.tax_rate }}%
                                            </div>
                                        </div>

                                        <div v-if="reactiveProduct.brand" class="space-y-2">
                                            <div class="text-sm font-medium text-muted-foreground">Brand</div>
                                            <div class="flex items-center gap-2">
                                                <Tag class="h-4 w-4" />
                                                {{ reactiveProduct.brand }}
                                            </div>
                                        </div>

                                        <div v-if="reactiveProduct.barcode" class="space-y-2">
                                            <div class="text-sm font-medium text-muted-foreground">Barcode</div>
                                            <div class="flex items-center gap-2">
                                                <BarChart3 class="h-4 w-4" />
                                                {{ reactiveProduct.barcode }}
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Inventory Information -->
                            <Card v-if="hasInventory">
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <Archive class="h-5 w-5" />
                                        Inventory Status
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                        <div class="space-y-2">
                                            <div class="text-sm font-medium text-muted-foreground">Quantity on Hand</div>
                                            <div class="text-2xl font-bold">
                                                {{ reactiveProduct.inventory?.quantity_on_hand?.toLocaleString() || 0 }}
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <div class="text-sm font-medium text-muted-foreground">Available</div>
                                            <div class="text-2xl font-bold">
                                                {{ reactiveProduct.inventory?.quantity_available?.toLocaleString() || 0 }}
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <div class="text-sm font-medium text-muted-foreground">Stock Status</div>
                                            <div class="flex items-center gap-2" :class="getStockStatusColor()">
                                                <AlertTriangle v-if="hasLowStock || reactiveProduct.inventory?.is_out_of_stock" class="h-4 w-4" />
                                                <span class="font-medium">{{ getStockStatusText() }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="reactiveProduct.reorder_level" class="mt-4 border-t pt-4">
                                        <div class="mb-1 text-sm font-medium text-muted-foreground">Reorder Level</div>
                                        <div class="text-sm">{{ reactiveProduct.reorder_level }} {{ reactiveProduct.unit_type }}</div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>

                    <!-- Physical Properties -->
                    <Card v-if="reactiveProduct.weight || hasDimensions || reactiveProduct.color">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Ruler class="h-5 w-5" />
                                Physical Properties
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                <div v-if="reactiveProduct.weight" class="space-y-2">
                                    <div class="text-sm font-medium text-muted-foreground">Weight</div>
                                    <div class="flex items-center gap-2">
                                        <Weight class="h-4 w-4" />
                                        {{ formatWeight(reactiveProduct.weight) }}
                                    </div>
                                </div>

                                <div v-if="hasDimensions" class="space-y-2">
                                    <div class="text-sm font-medium text-muted-foreground">Dimensions</div>
                                    <div class="flex items-center gap-2">
                                        <Ruler class="h-4 w-4" />
                                        {{ formatDimensions() }}
                                    </div>
                                </div>

                                <div v-if="reactiveProduct.color" class="space-y-2">
                                    <div class="text-sm font-medium text-muted-foreground">Color</div>
                                    <div class="flex items-center gap-2">
                                        <Palette class="h-4 w-4" />
                                        {{ reactiveProduct.color }}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Tags -->
                    <Card v-if="hasTags">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Tag class="h-5 w-5" />
                                Tags
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex flex-wrap gap-2">
                                <Badge v-for="tag in reactiveProduct.tags" :key="tag" variant="outline" class="text-xs">
                                    {{ tag }}
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- SEO Information -->
                    <Card v-if="reactiveProduct.meta_title || reactiveProduct.meta_description">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Globe class="h-5 w-5" />
                                SEO Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div v-if="reactiveProduct.meta_title" class="space-y-2">
                                <div class="text-sm font-medium text-muted-foreground">Meta Title</div>
                                <div class="text-sm">{{ reactiveProduct.meta_title }}</div>
                            </div>

                            <div v-if="reactiveProduct.meta_description" class="space-y-2">
                                <div class="text-sm font-medium text-muted-foreground">Meta Description</div>
                                <div class="text-sm leading-relaxed">{{ reactiveProduct.meta_description }}</div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Record Information -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Calendar class="h-5 w-5" />
                                Record Information
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <div class="text-sm font-medium text-muted-foreground">Created</div>
                                    <div class="flex items-center gap-2 text-sm">
                                        <Calendar class="h-4 w-4" />
                                        {{ formatDate(reactiveProduct.created_at) }}
                                    </div>
                                    <div v-if="reactiveProduct.creator" class="flex items-center gap-2 text-sm text-muted-foreground">
                                        <User class="h-4 w-4" />
                                        by {{ reactiveProduct.creator.name }}
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <div class="text-sm font-medium text-muted-foreground">Last Updated</div>
                                    <div class="flex items-center gap-2 text-sm">
                                        <Calendar class="h-4 w-4" />
                                        {{ formatDate(reactiveProduct.updated_at) }}
                                    </div>
                                    <div v-if="reactiveProduct.updater" class="flex items-center gap-2 text-sm text-muted-foreground">
                                        <User class="h-4 w-4" />
                                        by {{ reactiveProduct.updater.name }}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Variants Tab -->
                <div v-if="activeTab === 'variants'" class="space-y-6">
                    <PermissionGuard permission="view_products">
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <Package class="h-5 w-5" />
                                    Product Variants
                                </CardTitle>
                                <CardDescription>
                                    Manage different variations of this product (size, color, material, etc.)
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div class="flex items-center justify-center py-8 text-muted-foreground">
                                    <div class="text-center">
                                        <Package class="mx-auto h-12 w-12 mb-4 opacity-50" />
                                        <p>Product variants management will be available here.</p>
                                        <p class="text-sm mt-1">Configure different sizes, colors, and options for this product.</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </PermissionGuard>
                </div>

                <!-- Pricing Tab -->
                <div v-if="activeTab === 'pricing'" class="space-y-6">
                    <PermissionGuard permission="view_products">
                        <div class="grid gap-6 lg:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <DollarSign class="h-5 w-5" />
                                        Pricing Rules
                                    </CardTitle>
                                    <CardDescription>
                                        Manage bulk pricing, discounts, and promotional rules
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div class="flex items-center justify-center py-8 text-muted-foreground">
                                        <div class="text-center">
                                            <DollarSign class="mx-auto h-12 w-12 mb-4 opacity-50" />
                                            <p>Dynamic pricing management will be available here.</p>
                                            <p class="text-sm mt-1">Set up quantity discounts, time-based pricing, and special offers.</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <TrendingUp class="h-5 w-5" />
                                        Pricing Calculator
                                    </CardTitle>
                                    <CardDescription>
                                        Calculate final prices with applied rules and discounts
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div class="space-y-4">
                                        <div class="text-center py-4">
                                            <div class="text-2xl font-bold">
                                                {{ formatPrice(reactiveProduct.unit_price) }}
                                            </div>
                                            <div class="text-sm text-muted-foreground">
                                                Base Price per {{ reactiveProduct.unit_type }}
                                            </div>
                                        </div>
                                        <div class="text-center text-muted-foreground">
                                            <TrendingUp class="mx-auto h-8 w-8 mb-2 opacity-50" />
                                            <p class="text-sm">Price calculation features coming soon</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </PermissionGuard>
                </div>

                <!-- Inventory Tab -->
                <div v-if="activeTab === 'inventory'" class="space-y-6">
                    <PermissionGuard permission="view_products">
                        <div class="grid gap-6 lg:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <Warehouse class="h-5 w-5" />
                                        Inventory Overview
                                    </CardTitle>
                                    <CardDescription>
                                        Current stock levels and inventory health metrics
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div v-if="hasInventory" class="space-y-4">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="text-center p-4 border rounded">
                                                <div class="text-2xl font-bold text-blue-600">
                                                    {{ reactiveProduct.inventory?.quantity_on_hand?.toLocaleString() || 0 }}
                                                </div>
                                                <div class="text-sm text-muted-foreground">On Hand</div>
                                            </div>
                                            <div class="text-center p-4 border rounded">
                                                <div class="text-2xl font-bold text-green-600">
                                                    {{ reactiveProduct.inventory?.quantity_available?.toLocaleString() || 0 }}
                                                </div>
                                                <div class="text-sm text-muted-foreground">Available</div>
                                            </div>
                                        </div>
                                        <div class="flex items-center justify-center gap-2" :class="getStockStatusColor()">
                                            <AlertTriangle v-if="hasLowStock || reactiveProduct.inventory?.is_out_of_stock" class="h-4 w-4" />
                                            <span class="font-medium">{{ getStockStatusText() }}</span>
                                        </div>
                                    </div>
                                    <div v-else class="flex items-center justify-center py-8 text-muted-foreground">
                                        <div class="text-center">
                                            <Warehouse class="mx-auto h-12 w-12 mb-4 opacity-50" />
                                            <p>No inventory data available</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        <BarChart3 class="h-5 w-5" />
                                        Stock Movements
                                    </CardTitle>
                                    <CardDescription>
                                        Recent inventory transactions and adjustments
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div class="flex items-center justify-center py-8 text-muted-foreground">
                                        <div class="text-center">
                                            <BarChart3 class="mx-auto h-12 w-12 mb-4 opacity-50" />
                                            <p>Stock movement tracking will be available here.</p>
                                            <p class="text-sm mt-1">View all inventory adjustments, sales, and restocks.</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </PermissionGuard>
                </div>
            </div>
        </div>

        <!-- Edit Product Modal -->
        <PermissionGuard permission="edit_products">
            <ProductEditModal :open="showEditModal" :product="reactiveProduct" @update:open="showEditModal = $event" @product-updated="handleProductUpdated" />
        </PermissionGuard>

        <!-- Delete Confirmation Modal -->
        <DeleteConfirmationModal
            :open="showDeleteModal"
            :loading="isDeleting"
            title="Delete Product"
            message="Are you sure you want to delete"
            :item-name="reactiveProduct.name"
            danger-text="Delete Product"
            @update:open="showDeleteModal = $event"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
        />
    </AppLayout>
</template>
