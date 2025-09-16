<script setup lang="ts">
import PermissionGuard from '@/components/PermissionGuard.vue';
import Badge from '@/components/ui/badge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import Pagination from '@/components/ui/Pagination.vue';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { type PaginatedData, type Product, type ProductFilters } from '@/types';
import { AlertTriangle, ArrowUpDown, ChevronDown, ChevronUp, DollarSign, Edit, Eye, MoreHorizontal, Package, Tag, Trash2, TrendingUp, Warehouse } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    products: PaginatedData<Product>;
    filters: ProductFilters;
    selectedProducts: number[];
    isLoading?: boolean;
}

interface Emits {
    sort: [column: string];
    delete: [product: Product];
    edit: [product: Product];
    select: [productId: number];
    'select-all': [];
    'clear-selection': [];
    'page-change': [page: number];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Computed properties
const allSelected = computed(() => {
    return props.products.data.length > 0 && props.selectedProducts.length === props.products.data.length;
});

const someSelected = computed(() => {
    return props.selectedProducts.length > 0 && props.selectedProducts.length < props.products.data.length;
});

// Helper functions
const getSortIcon = (column: string) => {
    if (props.filters.sort_by !== column) {
        return ArrowUpDown;
    }
    return props.filters.sort_direction === 'asc' ? ChevronUp : ChevronDown;
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

// Removed unused getStatusColor function as it's not being used in the template

const formatPrice = (price: number): string => {
    return new Intl.NumberFormat('en-GH', {
        style: 'currency',
        currency: 'GHS',
    }).format(price);
};

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString('en-GH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

// Event handlers
const handleSort = (column: string) => {
    emit('sort', column);
};

const handleSelect = (productId: number) => {
    emit('select', productId);
};

const handleSelectAll = () => {
    emit('select-all');
};

const handleDelete = (product: Product) => {
    emit('delete', product);
};

const handleEdit = (product: Product) => {
    emit('edit', product);
};

const handlePageChange = (page: number) => {
    emit('page-change', page);
};

const clearSelection = () => {
    emit('clear-selection');
};
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex items-center justify-between">
                <CardTitle class="flex items-center gap-2">
                    <Package class="h-5 w-5" />
                    Products
                    <span class="text-sm font-normal text-muted-foreground"> ({{ products.total.toLocaleString() }} total) </span>
                </CardTitle>
                <div class="flex items-center gap-2">
                    <span v-if="selectedProducts.length > 0" class="text-sm text-muted-foreground"> {{ selectedProducts.length }} selected </span>
                    <Button v-if="selectedProducts.length > 0" variant="outline" size="sm" @click="clearSelection"> Clear Selection </Button>
                </div>
            </div>
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
            <div v-else-if="products.data.length === 0" class="flex flex-col items-center justify-center p-12">
                <Package class="mb-4 h-12 w-12 text-muted-foreground" />
                <h3 class="text-lg font-medium">No products found</h3>
                <p class="text-center text-muted-foreground">
                    {{
                        filters.search || filters.category_id || filters.status
                            ? 'Try adjusting your filters to see more results.'
                            : 'Get started by creating your first product.'
                    }}
                </p>
            </div>

            <!-- Products Table -->
            <Table v-else>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-12">
                            <Checkbox
                                :checked="allSelected"
                                :indeterminate="someSelected"
                                @update:checked="handleSelectAll"
                                aria-label="Select all products"
                            />
                        </TableHead>
                        <TableHead class="w-16">Image</TableHead>
                        <TableHead class="cursor-pointer select-none" @click="handleSort('name')">
                            <div class="flex items-center gap-2">
                                Name / SKU
                                <component :is="getSortIcon('name')" class="h-4 w-4" />
                            </div>
                        </TableHead>
                        <TableHead class="cursor-pointer select-none" @click="handleSort('category_id')">
                            <div class="flex items-center gap-2">
                                Category
                                <component :is="getSortIcon('category_id')" class="h-4 w-4" />
                            </div>
                        </TableHead>
                        <TableHead class="cursor-pointer select-none" @click="handleSort('unit_price')">
                            <div class="flex items-center gap-2">
                                Price
                                <component :is="getSortIcon('unit_price')" class="h-4 w-4" />
                            </div>
                        </TableHead>
                        <TableHead class="cursor-pointer select-none" @click="handleSort('status')">
                            <div class="flex items-center gap-2">
                                Status
                                <component :is="getSortIcon('status')" class="h-4 w-4" />
                            </div>
                        </TableHead>
                        <TableHead>Stock</TableHead>
                        <TableHead class="cursor-pointer select-none" @click="handleSort('created_at')">
                            <div class="flex items-center gap-2">
                                Created
                                <component :is="getSortIcon('created_at')" class="h-4 w-4" />
                            </div>
                        </TableHead>
                        <TableHead class="w-12"></TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="product in products.data" :key="product.id" class="group">
                        <TableCell>
                            <Checkbox
                                :checked="selectedProducts.includes(product.id)"
                                @update:checked="() => handleSelect(product.id)"
                                :aria-label="`Select ${product.name}`"
                            />
                        </TableCell>

                        <!-- Product Image -->
                        <TableCell>
                            <div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-lg bg-muted">
                                <img v-if="product.image_url" :src="product.image_url" :alt="product.name" class="h-full w-full object-cover" />
                                <Package v-else class="h-4 w-4 text-muted-foreground" />
                            </div>
                        </TableCell>

                        <!-- Name & SKU -->
                        <TableCell class="font-medium">
                            <div class="space-y-1">
                                <div class="truncate font-medium" :title="product.name">{{ product.name }}</div>
                                <div class="flex items-center gap-1 text-sm text-muted-foreground">
                                    <Tag class="h-3 w-3" />
                                    {{ product.sku }}
                                </div>
                            </div>
                        </TableCell>

                        <!-- Category -->
                        <TableCell>
                            <span v-if="product.category" class="text-sm">
                                {{ product.category.name }}
                            </span>
                            <span v-else class="text-sm text-muted-foreground"> No category </span>
                        </TableCell>

                        <!-- Price -->
                        <TableCell>
                            <div class="flex items-center gap-1">
                                <span class="font-medium">{{ formatPrice(product.unit_price) }}</span>
                            </div>
                            <div class="text-xs text-muted-foreground">per {{ product.unit_type }}</div>
                        </TableCell>

                        <!-- Status -->
                        <TableCell>
                            <Badge :variant="getStatusBadgeVariant(product.status)">
                                {{ product.status }}
                            </Badge>
                        </TableCell>

                        <!-- Stock Status -->
                        <TableCell>
                            <div v-if="product.inventory" class="space-y-1">
                                <div class="text-sm font-medium">
                                    {{ product.inventory.quantity_on_hand?.toLocaleString() || 0 }}
                                </div>
                                <div v-if="product.low_stock" class="flex items-center gap-1 text-orange-600">
                                    <AlertTriangle class="h-3 w-3" />
                                    <span class="text-xs">Low Stock</span>
                                </div>
                                <div v-else-if="product.inventory.is_out_of_stock" class="flex items-center gap-1 text-red-600">
                                    <AlertTriangle class="h-3 w-3" />
                                    <span class="text-xs">Out of Stock</span>
                                </div>
                                <div v-else class="text-xs text-green-600">In Stock</div>
                            </div>
                            <span v-else class="text-sm text-muted-foreground"> No inventory </span>
                        </TableCell>

                        <!-- Created Date -->
                        <TableCell class="text-sm text-muted-foreground">
                            {{ formatDate(product.created_at) }}
                        </TableCell>

                        <!-- Actions -->
                        <TableCell>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                                        <MoreHorizontal class="h-4 w-4" />
                                        <span class="sr-only">Open menu</span>
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuLabel>Product Actions</DropdownMenuLabel>

                                    <PermissionGuard permission="view_products">
                                        <DropdownMenuItem as-child>
                                            <a :href="`/products/${product.id}`" class="flex items-center gap-2">
                                                <Eye class="h-4 w-4" />
                                                View Details
                                            </a>
                                        </DropdownMenuItem>
                                    </PermissionGuard>

                                    <PermissionGuard permission="edit_products">
                                        <DropdownMenuItem @click="handleEdit(product)" class="flex items-center gap-2">
                                            <Edit class="h-4 w-4" />
                                            Edit Product
                                        </DropdownMenuItem>
                                    </PermissionGuard>

                                    <DropdownMenuSeparator />
                                    <DropdownMenuLabel>Management</DropdownMenuLabel>

                                    <PermissionGuard permission="view_products">
                                        <DropdownMenuItem as-child>
                                            <a :href="`/products/${product.id}/variants`" class="flex items-center gap-2">
                                                <Package class="h-4 w-4" />
                                                Manage Variants
                                            </a>
                                        </DropdownMenuItem>
                                    </PermissionGuard>

                                    <PermissionGuard permission="view_products">
                                        <DropdownMenuItem as-child>
                                            <a :href="`/products/${product.id}/pricing`" class="flex items-center gap-2">
                                                <DollarSign class="h-4 w-4" />
                                                View Pricing
                                            </a>
                                        </DropdownMenuItem>
                                    </PermissionGuard>

                                    <PermissionGuard permission="view_products">
                                        <DropdownMenuItem as-child>
                                            <a :href="`/products/${product.id}/inventory`" class="flex items-center gap-2">
                                                <Warehouse class="h-4 w-4" />
                                                Inventory Status
                                            </a>
                                        </DropdownMenuItem>
                                    </PermissionGuard>

                                    <PermissionGuard permission="view_products">
                                        <DropdownMenuItem as-child>
                                            <a :href="`/pricing?product_id=${product.id}&tab=history`" class="flex items-center gap-2">
                                                <TrendingUp class="h-4 w-4" />
                                                Price History
                                            </a>
                                        </DropdownMenuItem>
                                    </PermissionGuard>

                                    <DropdownMenuSeparator />

                                    <PermissionGuard permission="delete_products">
                                        <DropdownMenuItem @click="handleDelete(product)" class="text-destructive focus:text-destructive">
                                            <Trash2 class="mr-2 h-4 w-4" />
                                            Delete
                                        </DropdownMenuItem>
                                    </PermissionGuard>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <!-- Pagination -->
            <div v-if="products.data.length > 0" class="border-t px-6 py-4">
                <Pagination
                    :current-page="products.current_page"
                    :last-page="products.last_page"
                    :per-page="products.per_page"
                    :total="products.total"
                    :from="products.from"
                    :to="products.to"
                    @page-change="handlePageChange"
                />
            </div>
        </CardContent>
    </Card>
</template>
