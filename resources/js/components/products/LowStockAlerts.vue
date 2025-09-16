<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import Progress from '@/components/ui/progress.vue';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { type Product, type ProductInventory } from '@/types';
import { AlertTriangle, Package, ShoppingCart, TrendingDown } from 'lucide-vue-next';
import { computed } from 'vue';

interface LowStockProduct extends Product {
    inventory?: ProductInventory;
    stock_percentage?: number;
    days_until_stockout?: number;
}

interface Props {
    products: LowStockProduct[];
    isLoading?: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'reorder-product': [product: LowStockProduct];
    'adjust-stock': [product: LowStockProduct];
    'view-product': [product: LowStockProduct];
    'create-purchase-order': [products: LowStockProduct[]];
}>();

const criticalProducts = computed(() => {
    return props.products.filter(p =>
        p.inventory?.quantity_on_hand === 0 ||
        (p.inventory?.quantity_on_hand || 0) <= (p.inventory?.minimum_stock_level || 0) * 0.5
    );
});

const warningProducts = computed(() => {
    return props.products.filter(p =>
        (p.inventory?.quantity_on_hand || 0) > (p.inventory?.minimum_stock_level || 0) * 0.5 &&
        (p.inventory?.quantity_on_hand || 0) <= (p.inventory?.minimum_stock_level || 0)
    );
});

const getStockLevel = (product: LowStockProduct) => {
    const current = product.inventory?.quantity_on_hand || 0;
    const minimum = product.inventory?.minimum_stock_level || 0;

    if (current === 0) return { level: 'critical', text: 'Out of Stock', color: 'red' };
    if (current <= minimum * 0.25) return { level: 'critical', text: 'Critical Low', color: 'red' };
    if (current <= minimum * 0.5) return { level: 'very-low', text: 'Very Low', color: 'orange' };
    if (current <= minimum) return { level: 'low', text: 'Low Stock', color: 'yellow' };
    return { level: 'normal', text: 'Normal', color: 'green' };
};

const getStockPercentage = (product: LowStockProduct) => {
    const current = product.inventory?.quantity_on_hand || 0;
    const minimum = product.inventory?.minimum_stock_level || 1;
    return Math.min((current / minimum) * 100, 100);
};

const getPriorityOrder = (product: LowStockProduct) => {
    const stockLevel = getStockLevel(product);
    const current = product.inventory?.quantity_on_hand || 0;

    // Priority: Out of stock > Critical low > Very low > Low stock
    if (current === 0) return 1;
    if (stockLevel.level === 'critical') return 2;
    if (stockLevel.level === 'very-low') return 3;
    return 4;
};

const sortedProducts = computed(() => {
    return [...props.products].sort((a, b) => {
        const priorityA = getPriorityOrder(a);
        const priorityB = getPriorityOrder(b);

        if (priorityA !== priorityB) {
            return priorityA - priorityB;
        }

        // If same priority, sort by percentage (lower first)
        return getStockPercentage(a) - getStockPercentage(b);
    });
});

const totalValue = computed(() => {
    return props.products.reduce((sum, product) => {
        const reorderQty = product.inventory?.reorder_quantity || 10;
        const unitPrice = product.unit_price || 0;
        return sum + (reorderQty * unitPrice);
    }, 0);
});

const formatCurrency = (amount: number) => `GH₵${amount.toFixed(2)}`;

const getStockColor = (level: string) => {
    switch (level) {
        case 'critical':
            return 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400';
        case 'very-low':
            return 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400';
        case 'low':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400';
    }
};

const selectedProducts = computed(() => {
    // For now, return all products. In a real implementation,
    // you'd have checkboxes to select specific products
    return props.products;
});
</script>

<template>
    <div class="space-y-6">
        <!-- Summary Cards -->
        <div class="grid gap-4 md:grid-cols-3">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Critical Items</CardTitle>
                    <AlertTriangle class="h-4 w-4 text-red-500" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                        {{ criticalProducts.length }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Immediate attention required
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Warning Items</CardTitle>
                    <TrendingDown class="h-4 w-4 text-yellow-500" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                        {{ warningProducts.length }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Need to reorder soon
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Reorder Value</CardTitle>
                    <ShoppingCart class="h-4 w-4 text-blue-500" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ formatCurrency(totalValue) }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Estimated reorder cost
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- Actions Bar -->
        <div class="flex flex-col sm:flex-row gap-4 justify-between items-start sm:items-center">
            <div>
                <h2 class="text-2xl font-bold tracking-tight">Low Stock Alerts</h2>
                <p class="text-muted-foreground">
                    Products that need immediate attention or reordering.
                </p>
            </div>
            <div class="flex gap-2">
                <Button
                    variant="outline"
                    @click="$emit('create-purchase-order', selectedProducts)"
                    :disabled="selectedProducts.length === 0"
                >
                    <ShoppingCart class="h-4 w-4 mr-2" />
                    Create Purchase Order
                </Button>
            </div>
        </div>

        <!-- Low Stock Table -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <AlertTriangle class="h-5 w-5" />
                    Low Stock Products
                </CardTitle>
                <CardDescription>
                    Products below their minimum stock levels, sorted by urgency.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="isLoading" class="space-y-4">
                    <div v-for="i in 5" :key="i" class="flex items-center space-x-4">
                        <Skeleton class="h-12 w-12 rounded" />
                        <div class="space-y-2 flex-1">
                            <Skeleton class="h-4 w-[250px]" />
                            <Skeleton class="h-4 w-[200px]" />
                        </div>
                        <Skeleton class="h-8 w-[100px]" />
                    </div>
                </div>

                <div v-else-if="products.length === 0" class="text-center py-8">
                    <Package class="h-12 w-12 mx-auto text-green-400 mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">All stock levels are healthy!</h3>
                    <p class="text-gray-500 dark:text-gray-400">
                        No products are currently below their minimum stock levels.
                    </p>
                </div>

                <div v-else class="overflow-hidden rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Product</TableHead>
                                <TableHead>Current Stock</TableHead>
                                <TableHead>Minimum Level</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Suggested Order</TableHead>
                                <TableHead>Estimated Cost</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="product in sortedProducts" :key="product.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <TableCell>
                                    <div class="flex items-center space-x-3">
                                        <div v-if="product.image_url" class="h-10 w-10 flex-shrink-0">
                                            <img
                                                :src="product.image_url"
                                                :alt="product.name"
                                                class="h-10 w-10 rounded object-cover"
                                            />
                                        </div>
                                        <div v-else class="h-10 w-10 flex-shrink-0 bg-gray-100 dark:bg-gray-800 rounded flex items-center justify-center">
                                            <Package class="h-5 w-5 text-gray-400" />
                                        </div>
                                        <div>
                                            <div class="font-medium">{{ product.name }}</div>
                                            <div class="text-sm text-gray-500">{{ product.sku }}</div>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="space-y-2">
                                        <div class="font-medium">{{ product.inventory?.quantity_on_hand || 0 }} units</div>
                                        <Progress :value="getStockPercentage(product)" class="h-2 w-24" />
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="font-medium">{{ product.inventory?.minimum_stock_level || 0 }} units</div>
                                    <div v-if="product.inventory?.reorder_point" class="text-sm text-gray-500">
                                        Reorder at: {{ product.inventory.reorder_point }}
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge :class="getStockColor(getStockLevel(product).level)" variant="secondary">
                                        {{ getStockLevel(product).text }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="font-medium">{{ product.inventory?.reorder_quantity || 10 }} units</div>
                                    <div class="text-sm text-gray-500">
                                        To reach max level
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div class="font-medium">
                                        {{ formatCurrency((product.inventory?.reorder_quantity || 10) * (product.unit_price || 0)) }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ formatCurrency(product.unit_price || 0) }} per unit
                                    </div>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            @click="$emit('adjust-stock', product)"
                                        >
                                            Adjust
                                        </Button>
                                        <Button
                                            size="sm"
                                            @click="$emit('reorder-product', product)"
                                        >
                                            Reorder
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>