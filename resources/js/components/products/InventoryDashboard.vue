<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { useCurrency } from '@/composables/useCurrency';
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

const { formatCurrency } = useCurrency();

const getMovementIcon = (type: string) => {
    switch (type) {
        case 'purchase':
        case 'return_from_customer':
        case 'transfer_in':
        case 'manufacturing':
        case 'initial_stock':
        case 'release_reservation':
            return ArrowUp;
        case 'sale':
        case 'return_to_supplier':
        case 'transfer_out':
        case 'damaged':
        case 'expired':
        case 'theft':
        case 'reservation':
            return ArrowDown;
        case 'adjustment':
        default:
            return Package;
    }
};

const getMovementColor = (type: string) => {
    switch (type) {
        case 'purchase':
        case 'return_from_customer':
        case 'transfer_in':
        case 'manufacturing':
        case 'initial_stock':
        case 'release_reservation':
            return 'text-green-600 dark:text-green-400';
        case 'sale':
        case 'return_to_supplier':
        case 'transfer_out':
        case 'damaged':
        case 'expired':
        case 'theft':
        case 'reservation':
            return 'text-red-600 dark:text-red-400';
        case 'adjustment':
        default:
            return 'text-blue-600 dark:text-blue-400';
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

const getHealthColor = computed(() => {
    const percentage = stockHealthPercentage.value;
    if (percentage >= 80) return 'text-green-600 dark:text-green-400';
    if (percentage >= 60) return 'text-yellow-600 dark:text-yellow-400';
    return 'text-red-600 dark:text-red-400';
});

const getHealthProgressColor = computed(() => {
    const percentage = stockHealthPercentage.value;
    if (percentage >= 80) return 'bg-green-500';
    if (percentage >= 60) return 'bg-yellow-500';
    return 'bg-red-500';
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold tracking-tight">Inventory Dashboard</h2>
                <p class="text-muted-foreground">Monitor stock levels, movements, and inventory health across all products.</p>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" @click="$emit('view-movements')"> View All Movements </Button>
                <Button @click="$emit('view-low-stock')">
                    <AlertTriangle class="mr-2 h-4 w-4" />
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
                        <p class="text-xs text-muted-foreground">Across {{ statistics?.total_products || 0 }} products</p>
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
                        <div class="text-2xl font-bold" :class="getHealthColor">{{ stockHealthPercentage.toFixed(0) }}%</div>
                        <div class="relative mt-2 h-2 w-full overflow-hidden rounded-full bg-secondary">
                            <div
                                class="h-full w-full flex-1 transition-all duration-300 ease-in-out"
                                :class="getHealthProgressColor"
                                :style="{ transform: `translateX(-${100 - stockHealthPercentage}%)` }"
                            />
                        </div>
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
                        <p class="text-xs text-muted-foreground">Need attention</p>
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
                        <p class="text-xs text-muted-foreground">Immediate action needed</p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <!-- Stock by Category -->
            <Card>
                <CardHeader>
                    <CardTitle>Stock Value by Category</CardTitle>
                    <CardDescription> Inventory distribution across product categories </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="isLoading" class="space-y-4">
                        <div v-for="i in 5" :key="i" class="flex items-center justify-between">
                            <Skeleton class="h-4 w-32" />
                            <Skeleton class="h-4 w-20" />
                        </div>
                    </div>
                    <div v-else class="py-8 text-center">
                        <Package class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                        <p class="text-gray-500 dark:text-gray-400">Category analytics coming soon</p>
                        <p class="mt-2 text-xs text-gray-400">Currently showing overall stock value of {{ formatCurrency(totalStockValue) }}</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Recent Stock Movements -->
            <Card>
                <CardHeader>
                    <CardTitle>Recent Stock Movements</CardTitle>
                    <CardDescription> Latest inventory transactions </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="isLoading" class="space-y-4">
                        <div v-for="i in 5" :key="i" class="flex items-center space-x-4">
                            <Skeleton class="h-8 w-8 rounded" />
                            <div class="flex-1 space-y-2">
                                <Skeleton class="h-4 w-48" />
                                <Skeleton class="h-3 w-32" />
                            </div>
                            <Skeleton class="h-4 w-16" />
                        </div>
                    </div>
                    <div v-else class="py-8 text-center">
                        <Package class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                        <p class="text-gray-500 dark:text-gray-400">Recent movements shown in main dashboard</p>
                        <Button variant="outline" size="sm" @click="$emit('view-movements')" class="mt-4"> View All Movements </Button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Top Products by Value -->
        <Card>
            <CardHeader>
                <CardTitle>Top Products by Stock Value</CardTitle>
                <CardDescription> Products with the highest inventory value </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="isLoading" class="space-y-4">
                    <div v-for="i in 5" :key="i" class="flex items-center space-x-4">
                        <Skeleton class="h-10 w-10 rounded" />
                        <div class="flex-1 space-y-2">
                            <Skeleton class="h-4 w-48" />
                            <Skeleton class="h-3 w-32" />
                        </div>
                        <Skeleton class="h-4 w-20" />
                    </div>
                </div>
                <div v-else class="py-8 text-center">
                    <Package class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                    <p class="text-gray-500 dark:text-gray-400">Product analytics coming soon</p>
                    <p class="mt-2 text-xs text-gray-400">Total stock value: {{ formatCurrency(totalStockValue) }}</p>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
