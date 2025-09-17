<script setup lang="ts">
import PermissionGuard from '@/components/PermissionGuard.vue';
import ProductVariantModal from '@/components/products/ProductVariantModal.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import DeleteConfirmationModal from '@/components/ui/DeleteConfirmationModal.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useAlerts } from '@/composables/useAlerts';
import { useCurrency } from '@/composables/useCurrency';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Product, type ProductVariant } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { AlertTriangle, ArrowLeft, Edit, Package, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

const { addAlert } = useAlerts();

interface Props {
    product: Product;
    variants: ProductVariant[];
    isLoading?: boolean;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Products',
        href: '/products',
    },
    {
        title: props.product.name,
        href: `/products/${props.product.id}`,
    },
    {
        title: 'Variants',
        href: `/products/${props.product.id}/variants`,
    },
];

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const variantToEdit = ref<ProductVariant | null>(null);
const variantToDelete = ref<ProductVariant | null>(null);
const isDeleting = ref(false);

// Event handlers
const handleEdit = (variant: ProductVariant) => {
    variantToEdit.value = variant;
    showEditModal.value = true;
};

const handleDelete = (variant: ProductVariant) => {
    variantToDelete.value = variant;
    showDeleteModal.value = true;
};

const handleSetDefault = (variant: ProductVariant) => {
    // In a real implementation, this would make an API call to set the default variant
    addAlert(`Set "${variant.name}" as default variant for ${props.product.name}`, 'success', { title: 'Default Variant Updated' });
};

const confirmDelete = () => {
    if (!variantToDelete.value) return;

    isDeleting.value = true;

    // In a real implementation, this would make an API call to delete the variant
    setTimeout(() => {
        addAlert(`Variant "${variantToDelete.value?.name}" has been successfully deleted.`, 'success', { title: 'Variant Deleted' });

        isDeleting.value = false;
        showDeleteModal.value = false;
        variantToDelete.value = null;

        // Refresh the page to update the variants list
        router.reload();
    }, 1000);
};

const cancelDelete = () => {
    showDeleteModal.value = false;
    variantToDelete.value = null;
};

const handleVariantCreated = () => {
    showCreateModal.value = false;
    addAlert('New variant has been created successfully.', 'success', { title: 'Variant Created' });
    // Refresh the page to show the new variant
    router.reload();
};

const handleVariantUpdated = () => {
    showEditModal.value = false;
    variantToEdit.value = null;
    addAlert('Variant has been updated successfully.', 'success', { title: 'Variant Updated' });
    // Refresh the page to show the updated variant
    router.reload();
};

const { formatCurrency: formatPrice } = useCurrency();

const getStockStatusColor = (variant: ProductVariant): string => {
    if (!variant.inventory) return 'text-muted-foreground';
    if (variant.inventory.is_out_of_stock) return 'text-red-600';
    if (variant.inventory.quantity_on_hand <= variant.inventory.reorder_level) return 'text-orange-600';
    return 'text-green-600';
};

const getStockStatusText = (variant: ProductVariant): string => {
    if (!variant.inventory) return 'No inventory data';
    if (variant.inventory.is_out_of_stock) return 'Out of Stock';
    if (variant.inventory.quantity_on_hand <= variant.inventory.reorder_level) return 'Low Stock';
    return 'In Stock';
};
</script>

<template>
    <Head :title="`${product.name} - Variants`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-8">
            <!-- Header -->
            <PageHeader
                :title="`${product.name} Variants`"
                :description="`Manage different variations of ${product.name} (size, color, material, etc.)`"
            >
                <template #action>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" @click="router.visit(`/products/${product.id}`)">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Back to Product
                        </Button>

                        <PermissionGuard permission="create_products">
                            <Button @click="showCreateModal = true">
                                <Plus class="mr-2 h-4 w-4" />
                                Add Variant
                            </Button>
                        </PermissionGuard>
                    </div>
                </template>
            </PageHeader>

            <!-- Product Summary Card -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <Package class="h-5 w-5" />
                                {{ product.name }}
                            </CardTitle>
                            <CardDescription> SKU: {{ product.sku }} • Base Price: {{ formatPrice(product.unit_price) }} </CardDescription>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-blue-600">
                                {{ variants.length }}
                            </div>
                            <div class="text-sm text-muted-foreground">Variants</div>
                        </div>
                    </div>
                </CardHeader>
            </Card>

            <!-- Variants Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Product Variants</CardTitle>
                    <CardDescription> Different variations of this product with their own pricing and inventory </CardDescription>
                </CardHeader>
                <CardContent class="p-0">
                    <!-- Loading State -->
                    <div v-if="isLoading" class="p-6">
                        <div class="space-y-4">
                            <Skeleton class="h-4 w-full" />
                            <Skeleton class="h-4 w-full" />
                            <Skeleton class="h-4 w-full" />
                            <Skeleton class="h-4 w-full" />
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else-if="variants.length === 0" class="flex flex-col items-center justify-center p-12">
                        <Package class="mb-4 h-12 w-12 text-muted-foreground" />
                        <h3 class="text-lg font-medium">No variants found</h3>
                        <p class="mb-4 text-center text-muted-foreground">
                            This product doesn't have any variants yet. Create variants to offer different options like size, color, or material.
                        </p>
                        <PermissionGuard permission="create_products">
                            <Button @click="showCreateModal = true">
                                <Plus class="mr-2 h-4 w-4" />
                                Add First Variant
                            </Button>
                        </PermissionGuard>
                    </div>

                    <!-- Variants Table -->
                    <Table v-else>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Variant</TableHead>
                                <TableHead>SKU</TableHead>
                                <TableHead>Price</TableHead>
                                <TableHead>Stock</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Default</TableHead>
                                <TableHead class="w-12">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="variant in variants" :key="variant.id" class="group">
                                <!-- Variant Info -->
                                <TableCell class="font-medium">
                                    <div class="space-y-1">
                                        <div class="font-medium" :title="variant.name">{{ variant.name }}</div>
                                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                            <span v-if="variant.color">Color: {{ variant.color }}</span>
                                            <span v-if="variant.size">Size: {{ variant.size }}</span>
                                            <span v-if="variant.material">Material: {{ variant.material }}</span>
                                        </div>
                                    </div>
                                </TableCell>

                                <!-- SKU -->
                                <TableCell class="font-mono text-sm">
                                    {{ variant.sku }}
                                </TableCell>

                                <!-- Price -->
                                <TableCell>
                                    <div class="space-y-1">
                                        <div class="font-medium">{{ formatPrice(variant.price || product.unit_price) }}</div>
                                        <div v-if="variant.price !== product.unit_price" class="text-xs text-muted-foreground">
                                            Base: {{ formatPrice(product.unit_price) }}
                                        </div>
                                    </div>
                                </TableCell>

                                <!-- Stock -->
                                <TableCell>
                                    <div v-if="variant.inventory" class="space-y-1">
                                        <div class="font-medium">
                                            {{ variant.inventory.quantity_on_hand?.toLocaleString() || 0 }}
                                        </div>
                                        <div class="flex items-center gap-1" :class="getStockStatusColor(variant)">
                                            <AlertTriangle
                                                v-if="
                                                    variant.inventory.is_out_of_stock ||
                                                    variant.inventory.quantity_on_hand <= variant.inventory.reorder_level
                                                "
                                                class="h-3 w-3"
                                            />
                                            <span class="text-xs">{{ getStockStatusText(variant) }}</span>
                                        </div>
                                    </div>
                                    <span v-else class="text-sm text-muted-foreground">No inventory</span>
                                </TableCell>

                                <!-- Status -->
                                <TableCell>
                                    <span
                                        :class="variant.status === 'active' ? 'text-green-600' : 'text-gray-500'"
                                        class="inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-xs font-medium"
                                    >
                                        {{ variant.status }}
                                    </span>
                                </TableCell>

                                <!-- Default -->
                                <TableCell>
                                    <div class="flex items-center gap-2">
                                        <span
                                            v-if="variant.is_default"
                                            class="inline-flex items-center rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800"
                                        >
                                            Default
                                        </span>
                                        <Button
                                            v-else-if="variant.status === 'active'"
                                            variant="ghost"
                                            size="sm"
                                            @click="handleSetDefault(variant)"
                                            class="text-xs"
                                        >
                                            Set Default
                                        </Button>
                                    </div>
                                </TableCell>

                                <!-- Actions -->
                                <TableCell>
                                    <div class="flex items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                        <PermissionGuard permission="edit_products">
                                            <Button variant="ghost" size="sm" @click="handleEdit(variant)">
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                        </PermissionGuard>

                                        <PermissionGuard permission="delete_products">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click="handleDelete(variant)"
                                                :disabled="variant.is_default"
                                                class="text-red-600 hover:bg-red-50 hover:text-red-700"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </PermissionGuard>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>

        <!-- Create Variant Modal -->
        <PermissionGuard permission="create_products">
            <ProductVariantModal
                :open="showCreateModal"
                :product="product"
                @update:open="showCreateModal = $event"
                @variant-created="handleVariantCreated"
            />
        </PermissionGuard>

        <!-- Edit Variant Modal -->
        <PermissionGuard permission="edit_products">
            <ProductVariantModal
                :open="showEditModal"
                :product="product"
                :variant="variantToEdit"
                @update:open="showEditModal = $event"
                @variant-updated="handleVariantUpdated"
            />
        </PermissionGuard>

        <!-- Delete Confirmation Modal -->
        <DeleteConfirmationModal
            :open="showDeleteModal"
            :loading="isDeleting"
            title="Delete Variant"
            message="Are you sure you want to delete this variant?"
            :item-name="variantToDelete?.name"
            danger-text="Delete Variant"
            @update:open="showDeleteModal = $event"
            @confirm="confirmDelete"
            @cancel="cancelDelete"
        />
    </AppLayout>
</template>
