<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import Progress from '@/components/ui/progress.vue';
import { Skeleton } from '@/components/ui/skeleton';
import { type InventoryStatistics } from '@/types';
import { AlertTriangle, ArrowDown, ArrowUp, Package, TrendingDown, TrendingUp, Warehouse } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    statistics: InventoryStatistics;
    isLoading?: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'view-low-stock': [];
    'view-movements': [];
    'view-category': [categoryId: number];
    'view-product': [productId: number];
}>();

const formatCurrency = (amount: number) => `GH₵${amount.toFixed(2)}`;

const getMovementIcon = (type: string) => {
    switch (type) {
        case 'in':
        case 'purchase':
        case 'return':
            return ArrowUp;
        case 'out':
        case 'sale':
        case 'damage':
            return ArrowDown;
        default:
            return Package;
    }
};

const getMovementColor = (type: string) => {
    switch (type) {
        case 'in':
        case 'purchase':
        case 'return':
            return 'text-green-600 dark:text-green-400';
        case 'out':
        case 'sale':
        case 'damage':
            return 'text-red-600 dark:text-red-400';
        default:
            return 'text-gray-600 dark:text-gray-400';
    }
};

const getStockStatusColor = (percentage: number) => {
    if (percentage >= 70) return 'bg-green-500';
    if (percentage >= 30) return 'bg-yellow-500';
    return 'bg-red-500';
};

const totalStockValue = computed(() => {
    return props.statistics?.total_stock_value || 0;
});

const stockHealthPercentage = computed(() => {
    const total = props.statistics?.total_products || 0;
    const problematic = (props.statistics?.low_stock_items || 0) + (props.statistics?.out_of_stock_items || 0);
    if (total === 0) return 100;
    return ((total - problematic) / total) * 100;
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold tracking-tight">Inventory Dashboard</h2>
                <p class="text-muted-foreground">
                    Monitor stock levels, movements, and inventory health across all products.
                </p>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" @click="$emit('view-movements')">
                    View All Movements
                </Button>
                <Button @click="$emit('view-low-stock')">
                    <AlertTriangle class="h-4 w-4 mr-2" />
                    Low Stock Items
                </Button>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Total Stock Value</CardTitle>
                    <Warehouse class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div v-if="isLoading" class="space-y-2">
                        <Skeleton class="h-8 w-24" />
                        <Skeleton class="h-4 w-16" />
                    </div>
                    <div v-else>
                        <div class="text-2xl font-bold">{{ formatCurrency(totalStockValue) }}</div>
                        <p class="text-xs text-muted-foreground">
                            Across {{ statistics?.total_products || 0 }} products
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Stock Health</CardTitle>
                    <TrendingUp class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div v-if="isLoading" class="space-y-2">
                        <Skeleton class="h-8 w-16" />
                        <Skeleton class="h-2 w-full" />
                    </div>
                    <div v-else>
                        <div class="text-2xl font-bold">{{ stockHealthPercentage.toFixed(0) }}%</div>
                        <Progress :value="stockHealthPercentage" class="mt-2" />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Low Stock Items</CardTitle>
                    <AlertTriangle class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div v-if="isLoading" class="space-y-2">
                        <Skeleton class="h-8 w-12" />
                        <Skeleton class="h-4 w-20" />
                    </div>
                    <div v-else>
                        <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                            {{ statistics?.low_stock_items || 0 }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Need attention
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Out of Stock</CardTitle>
                    <TrendingDown class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div v-if="isLoading" class="space-y-2">
                        <Skeleton class="h-8 w-12" />
                        <Skeleton class="h-4 w-20" />
                    </div>
                    <div v-else>
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                            {{ statistics?.out_of_stock_items || 0 }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Immediate action needed
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <!-- Stock by Category -->
            <Card>
                <CardHeader>
                    <CardTitle>Stock Value by Category</CardTitle>
                    <CardDescription>
                        Inventory distribution across product categories
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="isLoading" class="space-y-4">
                        <div v-for="i in 5" :key="i" class="flex items-center justify-between">
                            <Skeleton class="h-4 w-32" />
                            <Skeleton class="h-4 w-20" />
                        </div>
                    </div>
                    <div v-else-if="!statistics?.stock_value_by_category?.length" class="text-center py-8">
                        <Package class="h-12 w-12 mx-auto text-gray-400 mb-4" />
                        <p class="text-gray-500 dark:text-gray-400">No category data available</p>
                    </div>
                    <div v-else class="space-y-4">
                        <div
                            v-for="category in statistics.stock_value_by_category.slice(0, 6)"
                            :key="category.category_id"
                            class="flex items-center justify-between"
                        >
                            <div class="flex items-center space-x-3 flex-1">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium truncate">{{ category.category_name }}</p>
                                    <p class="text-xs text-gray-500">{{ category.total_quantity }} units</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium">{{ formatCurrency(category.total_value) }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ totalStockValue > 0 ? ((category.total_value / totalStockValue) * 100).toFixed(1) : 0 }}%
                                </p>
                            </div>
                            <Button
                                size="sm"
                                variant="ghost"
                                @click="$emit('view-category', category.category_id)"
                                class="ml-2"
                            >
                                View
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Recent Stock Movements -->
            <Card>
                <CardHeader>
                    <CardTitle>Recent Stock Movements</CardTitle>
                    <CardDescription>
                        Latest inventory transactions
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="isLoading" class="space-y-4">
                        <div v-for="i in 5" :key="i" class="flex items-center space-x-4">
                            <Skeleton class="h-8 w-8 rounded" />
                            <div class="space-y-2 flex-1">
                                <Skeleton class="h-4 w-48" />
                                <Skeleton class="h-3 w-32" />
                            </div>
                            <Skeleton class="h-4 w-16" />
                        </div>
                    </div>
                    <div v-else-if="!statistics?.recent_stock_movements?.length" class="text-center py-8">
                        <Package class="h-12 w-12 mx-auto text-gray-400 mb-4" />
                        <p class="text-gray-500 dark:text-gray-400">No recent movements</p>
                    </div>
                    <div v-else class="space-y-4">
                        <div
                            v-for="movement in statistics.recent_stock_movements.slice(0, 6)"
                            :key="movement.id"
                            class="flex items-center space-x-4"
                        >
                            <div class="flex-shrink-0">
                                <div :class="[
                                    'flex h-8 w-8 items-center justify-center rounded-full',
                                    movement.movement_type === 'in' || movement.movement_type === 'purchase' || movement.movement_type === 'return'
                                        ? 'bg-green-100 dark:bg-green-900/20'
                                        : 'bg-red-100 dark:bg-red-900/20'
                                ]">
                                    <component
                                        :is="getMovementIcon(movement.movement_type)"
                                        :class="['h-4 w-4', getMovementColor(movement.movement_type)]"
                                    />
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate">
                                    {{ movement.product?.name || 'Unknown Product' }}
                                </p>
                                <div class="flex items-center space-x-2 text-xs text-gray-500">
                                    <Badge variant="secondary" class="text-xs">
                                        {{ movement.movement_type }}
                                    </Badge>
                                    <span>{{ new Date(movement.movement_date).toLocaleDateString() }}</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <p :class="[
                                    'text-sm font-medium',
                                    movement.movement_type === 'in' || movement.movement_type === 'purchase' || movement.movement_type === 'return'
                                        ? 'text-green-600 dark:text-green-400'
                                        : 'text-red-600 dark:text-red-400'
                                ]">
                                    {{ movement.movement_type === 'in' || movement.movement_type === 'purchase' || movement.movement_type === 'return' ? '+' : '-' }}{{ movement.quantity }}
                                </p>
                                <p v-if="movement.total_cost" class="text-xs text-gray-500">
                                    {{ formatCurrency(movement.total_cost) }}
                                </p>
                            </div>
                        </div>
                        <Button
                            variant="outline"
                            size="sm"
                            @click="$emit('view-movements')"
                            class="w-full"
                        >
                            View All Movements
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Top Products by Value -->
        <Card>
            <CardHeader>
                <CardTitle>Top Products by Stock Value</CardTitle>
                <CardDescription>
                    Products with the highest inventory value
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="isLoading" class="space-y-4">
                    <div v-for="i in 5" :key="i" class="flex items-center space-x-4">
                        <Skeleton class="h-10 w-10 rounded" />
                        <div class="space-y-2 flex-1">
                            <Skeleton class="h-4 w-48" />
                            <Skeleton class="h-3 w-32" />
                        </div>
                        <Skeleton class="h-4 w-20" />
                    </div>
                </div>
                <div v-else-if="!statistics?.top_products_by_value?.length" class="text-center py-8">
                    <Package class="h-12 w-12 mx-auto text-gray-400 mb-4" />
                    <p class="text-gray-500 dark:text-gray-400">No product data available</p>
                </div>
                <div v-else class="space-y-4">
                    <div
                        v-for="(item, index) in statistics.top_products_by_value.slice(0, 10)"
                        :key="item.product.id"
                        class="flex items-center space-x-4 p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800/50"
                    >
                        <div class="flex-shrink-0">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/20">
                                <span class="text-sm font-medium text-blue-600 dark:text-blue-400">
                                    {{ index + 1 }}
                                </span>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate">{{ item.product.name }}</p>
                            <div class="flex items-center space-x-4 text-xs text-gray-500">
                                <span>{{ item.product.sku }}</span>
                                <span>{{ item.quantity_on_hand }} units</span>
                                <span>{{ formatCurrency(item.product.unit_price || 0) }}/unit</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium">{{ formatCurrency(item.total_value) }}</p>
                            <p class="text-xs text-gray-500">
                                {{ totalStockValue > 0 ? ((item.total_value / totalStockValue) * 100).toFixed(1) : 0 }}% of total
                            </p>
                        </div>
                        <Button
                            size="sm"
                            variant="ghost"
                            @click="$emit('view-product', item.product.id)"
                        >
                            View
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>