<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import Progress from '@/components/ui/progress.vue';
import { Skeleton } from '@/components/ui/skeleton';
import { useCurrency } from '@/composables/useCurrency';
import { type InventoryStatistics, type StockMovement } from '@/types';
import { AlertTriangle, BarChart3, Package, Plus, ShoppingCart, TrendingDown, TrendingUp, Warehouse } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    statistics: InventoryStatistics;
    recentMovements?: StockMovement[];
    isLoading?: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'add-stock': [];
    'create-purchase-order': [];
    'view-reports': [];
    'view-movements': [];
    'view-low-stock': [];
    'bulk-adjustment': [];
}>();

const { formatCurrency } = useCurrency();

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

const getHealthStatus = computed(() => {
    const percentage = stockHealthPercentage.value;
    if (percentage >= 80) return 'Excellent';
    if (percentage >= 60) return 'Good';
    if (percentage >= 40) return 'Fair';
    return 'Needs Attention';
});

const movementSummary = computed(() => {
    if (!props.statistics?.by_movement_type) return [];

    const types = props.statistics.by_movement_type;
    return Object.entries(types).map(([type, count]) => ({
        type,
        count,
        label: type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
    }));
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header with Quick Actions -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold tracking-tight">Inventory Overview</h2>
                <p class="text-muted-foreground">
                    Real-time view of your inventory health and recent activity
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button size="sm" @click="$emit('add-stock')">
                    <Plus class="h-4 w-4 mr-2" />
                    Add Stock
                </Button>
                <Button size="sm" variant="outline" @click="$emit('create-purchase-order')">
                    <ShoppingCart class="h-4 w-4 mr-2" />
                    Create PO
                </Button>
                <Button size="sm" variant="outline" @click="$emit('bulk-adjustment')">
                    <BarChart3 class="h-4 w-4 mr-2" />
                    Bulk Adjust
                </Button>
            </div>
        </div>

        <!-- Key Metrics Grid -->
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
            <!-- Stock Health Card -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Stock Health</CardTitle>
                    <TrendingUp class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div v-if="isLoading" class="space-y-2">
                        <Skeleton class="h-8 w-16" />
                        <Skeleton class="h-2 w-full" />
                        <Skeleton class="h-4 w-20" />
                    </div>
                    <div v-else class="space-y-2">
                        <div class="flex items-center space-x-2">
                            <div :class="['text-2xl font-bold', getHealthColor]">
                                {{ stockHealthPercentage.toFixed(0) }}%
                            </div>
                            <div :class="['text-sm font-medium', getHealthColor]">
                                {{ getHealthStatus }}
                            </div>
                        </div>
                        <Progress
                            :value="stockHealthPercentage"
                            :class="stockHealthPercentage >= 60 ? 'progress-green' : stockHealthPercentage >= 40 ? 'progress-yellow' : 'progress-red'"
                        />
                        <p class="text-xs text-muted-foreground">
                            {{ statistics.total_products - (statistics.low_stock_items + statistics.out_of_stock_items) }} of {{ statistics.total_products }} products healthy
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Total Value Card -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Total Inventory Value</CardTitle>
                    <Warehouse class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div v-if="isLoading" class="space-y-2">
                        <Skeleton class="h-8 w-24" />
                        <Skeleton class="h-4 w-16" />
                    </div>
                    <div v-else>
                        <div class="text-2xl font-bold">{{ formatCurrency(statistics?.total_stock_value || 0) }}</div>
                        <p class="text-xs text-muted-foreground">
                            {{ statistics?.total_products || 0 }} products tracked
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Alert Items Card -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Items Need Attention</CardTitle>
                    <AlertTriangle class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div v-if="isLoading" class="space-y-2">
                        <Skeleton class="h-8 w-12" />
                        <Skeleton class="h-4 w-20" />
                    </div>
                    <div v-else>
                        <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                            {{ (statistics?.low_stock_items || 0) + (statistics?.out_of_stock_items || 0) }}
                        </div>
                        <div class="flex items-center space-x-2 text-xs text-muted-foreground">
                            <span>{{ statistics?.low_stock_items || 0 }} low</span>
                            <span>•</span>
                            <span>{{ statistics?.out_of_stock_items || 0 }} out</span>
                        </div>
                        <Button
                            variant="link"
                            size="sm"
                            class="p-0 h-auto text-xs mt-1"
                            @click="$emit('view-low-stock')"
                        >
                            View Details →
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Recent Activity Card -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Recent Activity</CardTitle>
                    <TrendingDown class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div v-if="isLoading" class="space-y-2">
                        <Skeleton class="h-8 w-12" />
                        <Skeleton class="h-4 w-20" />
                    </div>
                    <div v-else>
                        <div class="text-2xl font-bold">{{ statistics?.recent_movements || 0 }}</div>
                        <p class="text-xs text-muted-foreground">
                            movements last 7 days
                        </p>
                        <Button
                            variant="link"
                            size="sm"
                            class="p-0 h-auto text-xs mt-1"
                            @click="$emit('view-movements')"
                        >
                            View All →
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Movement Type Breakdown -->
        <div class="grid gap-6 md:grid-cols-2">
            <!-- Movement Types Chart -->
            <Card>
                <CardHeader>
                    <CardTitle>Movement Types (Last 30 Days)</CardTitle>
                    <CardDescription>
                        Breakdown of inventory transactions by type
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="isLoading" class="space-y-4">
                        <div v-for="i in 5" :key="i" class="flex items-center justify-between">
                            <Skeleton class="h-4 w-32" />
                            <Skeleton class="h-4 w-12" />
                        </div>
                    </div>
                    <div v-else-if="movementSummary.length === 0" class="text-center py-8">
                        <Package class="h-12 w-12 mx-auto text-gray-400 mb-4" />
                        <p class="text-gray-500 dark:text-gray-400">No movements in the last 30 days</p>
                    </div>
                    <div v-else class="space-y-3">
                        <div
                            v-for="item in movementSummary.slice(0, 6)"
                            :key="item.type"
                            class="flex items-center justify-between py-2"
                        >
                            <div class="flex items-center space-x-3">
                                <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                <span class="text-sm font-medium">{{ item.label }}</span>
                            </div>
                            <span class="text-sm text-muted-foreground font-mono">{{ item.count }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Stock Status Distribution -->
            <Card>
                <CardHeader>
                    <CardTitle>Stock Status Distribution</CardTitle>
                    <CardDescription>
                        Overview of stock levels across all products
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="isLoading" class="space-y-4">
                        <div v-for="i in 3" :key="i" class="flex items-center justify-between">
                            <Skeleton class="h-4 w-24" />
                            <Skeleton class="h-4 w-12" />
                        </div>
                    </div>
                    <div v-else class="space-y-4">
                        <!-- In Stock -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                <span class="text-sm font-medium">In Stock</span>
                            </div>
                            <span class="text-sm text-muted-foreground font-mono">
                                {{ statistics?.by_status?.in_stock || 0 }}
                            </span>
                        </div>

                        <!-- Low Stock -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                <span class="text-sm font-medium">Low Stock</span>
                            </div>
                            <span class="text-sm text-muted-foreground font-mono">
                                {{ statistics?.by_status?.low_stock || 0 }}
                            </span>
                        </div>

                        <!-- Out of Stock -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                <span class="text-sm font-medium">Out of Stock</span>
                            </div>
                            <span class="text-sm text-muted-foreground font-mono">
                                {{ statistics?.by_status?.out_of_stock || 0 }}
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>

<style scoped>
.progress-green .progress-bar {
    background-color: rgb(34, 197, 94);
}

.progress-yellow .progress-bar {
    background-color: rgb(234, 179, 8);
}

.progress-red .progress-bar {
    background-color: rgb(239, 68, 68);
}
</style>