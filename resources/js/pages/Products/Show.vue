<script setup lang="ts">
import PermissionGuard from '@/components/PermissionGuard.vue';
import Badge from '@/components/ui/badge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import DeleteConfirmationModal from '@/components/ui/DeleteConfirmationModal.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import { useAlerts } from '@/composables/useAlerts';
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
    Weight,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    product: Product;
}

const props = defineProps<Props>();

const { success, error } = useAlerts();

const isDeleting = ref(false);
const showDeleteModal = ref(false);

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
const isActive = computed(() => props.product.status === 'active');
const isDiscontinued = computed(() => props.product.status === 'discontinued');
const hasInventory = computed(() => !!props.product.inventory);
const hasLowStock = computed(() => props.product.low_stock);
const hasImage = computed(() => !!props.product.image_url);
const hasDimensions = computed(() => props.product.dimensions?.length || props.product.dimensions?.width || props.product.dimensions?.height);
const hasTags = computed(() => props.product.tags && props.product.tags.length > 0);

// Event handlers
const handleEdit = () => {
    router.get(`/products/${props.product.id}/edit`);
};

const handleDelete = () => {
    showDeleteModal.value = true;
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

// Format helpers
const formatPrice = (price: number): string => {
    return new Intl.NumberFormat('en-GH', {
        style: 'currency',
        currency: 'GHS',
    }).format(price);
};

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
    if (!props.product.dimensions) return 'Not specified';
    const { length, width, height } = props.product.dimensions;
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
    if (props.product.inventory?.is_out_of_stock) return 'text-red-600';
    if (hasLowStock.value) return 'text-orange-600';
    return 'text-green-600';
};

const getStockStatusText = () => {
    if (!hasInventory.value) return 'No inventory data';
    if (props.product.inventory?.is_out_of_stock) return 'Out of Stock';
    if (hasLowStock.value) return 'Low Stock';
    return 'In Stock';
};
</script>

<template>
    <Head :title="product.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-8">
            <!-- Header -->
            <PageHeader :title="product.name" :description="`SKU: ${product.sku}`">
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
                            <div class="flex aspect-square items-center justify-center overflow-hidden rounded-lg bg-muted">
                                <img v-if="hasImage" :src="product.image_url" :alt="product.name" class="h-full w-full object-cover" />
                                <div v-else class="text-center text-muted-foreground">
                                    <Package class="mx-auto mb-2 h-16 w-16" />
                                    <p class="text-sm">No image available</p>
                                </div>
                            </div>
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
                                        {{ product.name }}
                                    </CardTitle>
                                    <CardDescription>{{ product.description || 'No description provided' }}</CardDescription>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Badge :variant="getStatusBadgeVariant(product.status)">
                                        {{ product.status }}
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
                                        {{ product.sku }}
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <div class="text-sm font-medium text-muted-foreground">Category</div>
                                    <div class="flex items-center gap-2">
                                        <Tag class="h-4 w-4" />
                                        {{ product.category?.name || 'No category' }}
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <div class="text-sm font-medium text-muted-foreground">Unit Price</div>
                                    <div class="flex items-center gap-2">
                                        <DollarSign class="h-4 w-4" />
                                        <span class="text-lg font-semibold">{{ formatPrice(product.unit_price) }}</span>
                                        <span class="text-sm text-muted-foreground">per {{ product.unit_type }}</span>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <div class="text-sm font-medium text-muted-foreground">Tax Rate</div>
                                    <div class="flex items-center gap-2">
                                        <TrendingUp class="h-4 w-4" />
                                        {{ product.tax_rate }}%
                                    </div>
                                </div>

                                <div v-if="product.brand" class="space-y-2">
                                    <div class="text-sm font-medium text-muted-foreground">Brand</div>
                                    <div class="flex items-center gap-2">
                                        <Tag class="h-4 w-4" />
                                        {{ product.brand }}
                                    </div>
                                </div>

                                <div v-if="product.barcode" class="space-y-2">
                                    <div class="text-sm font-medium text-muted-foreground">Barcode</div>
                                    <div class="flex items-center gap-2">
                                        <BarChart3 class="h-4 w-4" />
                                        {{ product.barcode }}
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
                                        {{ product.inventory?.quantity_on_hand?.toLocaleString() || 0 }}
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <div class="text-sm font-medium text-muted-foreground">Available</div>
                                    <div class="text-2xl font-bold">
                                        {{ product.inventory?.quantity_available?.toLocaleString() || 0 }}
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <div class="text-sm font-medium text-muted-foreground">Stock Status</div>
                                    <div class="flex items-center gap-2" :class="getStockStatusColor()">
                                        <AlertTriangle v-if="hasLowStock || product.inventory?.is_out_of_stock" class="h-4 w-4" />
                                        <span class="font-medium">{{ getStockStatusText() }}</span>
                                    </div>
                                </div>
                            </div>

                            <div v-if="product.reorder_level" class="mt-4 border-t pt-4">
                                <div class="mb-1 text-sm font-medium text-muted-foreground">Reorder Level</div>
                                <div class="text-sm">{{ product.reorder_level }} {{ product.unit_type }}</div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Physical Properties -->
            <Card v-if="product.weight || hasDimensions || product.color">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Ruler class="h-5 w-5" />
                        Physical Properties
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div v-if="product.weight" class="space-y-2">
                            <div class="text-sm font-medium text-muted-foreground">Weight</div>
                            <div class="flex items-center gap-2">
                                <Weight class="h-4 w-4" />
                                {{ formatWeight(product.weight) }}
                            </div>
                        </div>

                        <div v-if="hasDimensions" class="space-y-2">
                            <div class="text-sm font-medium text-muted-foreground">Dimensions</div>
                            <div class="flex items-center gap-2">
                                <Ruler class="h-4 w-4" />
                                {{ formatDimensions() }}
                            </div>
                        </div>

                        <div v-if="product.color" class="space-y-2">
                            <div class="text-sm font-medium text-muted-foreground">Color</div>
                            <div class="flex items-center gap-2">
                                <Palette class="h-4 w-4" />
                                {{ product.color }}
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
                        <Badge v-for="tag in product.tags" :key="tag" variant="outline" class="text-xs">
                            {{ tag }}
                        </Badge>
                    </div>
                </CardContent>
            </Card>

            <!-- SEO Information -->
            <Card v-if="product.meta_title || product.meta_description">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Globe class="h-5 w-5" />
                        SEO Information
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div v-if="product.meta_title" class="space-y-2">
                        <div class="text-sm font-medium text-muted-foreground">Meta Title</div>
                        <div class="text-sm">{{ product.meta_title }}</div>
                    </div>

                    <div v-if="product.meta_description" class="space-y-2">
                        <div class="text-sm font-medium text-muted-foreground">Meta Description</div>
                        <div class="text-sm leading-relaxed">{{ product.meta_description }}</div>
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
                                {{ formatDate(product.created_at) }}
                            </div>
                            <div v-if="product.creator" class="flex items-center gap-2 text-sm text-muted-foreground">
                                <User class="h-4 w-4" />
                                by {{ product.creator.name }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="text-sm font-medium text-muted-foreground">Last Updated</div>
                            <div class="flex items-center gap-2 text-sm">
                                <Calendar class="h-4 w-4" />
                                {{ formatDate(product.updated_at) }}
                            </div>
                            <div v-if="product.updater" class="flex items-center gap-2 text-sm text-muted-foreground">
                                <User class="h-4 w-4" />
                                by {{ product.updater.name }}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Delete Confirmation Modal -->
        <DeleteConfirmationModal
            :open="showDeleteModal"
            :loading="isDeleting"
            title="Delete Product"
            message="Are you sure you want to delete"
            :item-name="product.name"
            danger-text="Delete Product"
            @update:open="showDeleteModal = $event"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
        />
    </AppLayout>
</template>
