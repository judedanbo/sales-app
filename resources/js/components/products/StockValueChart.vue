<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { useCurrency } from '@/composables/useCurrency';
import { type CategoryStockData, type InventoryStatistics } from '@/types';
import { BarChart3, PieChart, TrendingUp } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

interface Props {
    statistics: InventoryStatistics;
    categoryData?: CategoryStockData[];
    chartType?: 'pie' | 'doughnut' | 'bar';
    isLoading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    chartType: 'doughnut',
});

const chartContainer = ref<HTMLCanvasElement>();
const chart = ref<any>(null);

const { formatCurrency } = useCurrency();

const chartData = computed(() => {
    if (props.categoryData?.length) {
        return {
            labels: props.categoryData.map(item => item.category_name),
            datasets: [{
                data: props.categoryData.map(item => item.total_value),
                backgroundColor: [
                    '#3B82F6', // blue-500
                    '#10B981', // emerald-500
                    '#F59E0B', // amber-500
                    '#EF4444', // red-500
                    '#8B5CF6', // violet-500
                    '#06B6D4', // cyan-500
                    '#84CC16', // lime-500
                    '#F97316', // orange-500
                ],
                borderWidth: 2,
                borderColor: '#ffffff',
            }]
        };
    }

    // Fallback to status-based data
    const statusData = props.statistics?.by_status;
    if (statusData) {
        return {
            labels: ['In Stock', 'Low Stock', 'Out of Stock'],
            datasets: [{
                data: [statusData.in_stock, statusData.low_stock, statusData.out_of_stock],
                backgroundColor: ['#10B981', '#F59E0B', '#EF4444'],
                borderWidth: 2,
                borderColor: '#ffffff',
            }]
        };
    }

    return null;
});

const chartOptions = computed(() => {
    const baseOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom' as const,
                labels: {
                    padding: 20,
                    usePointStyle: true,
                    font: {
                        size: 12,
                    },
                },
            },
            tooltip: {
                callbacks: {
                    label: (context: any) => {
                        if (props.categoryData?.length) {
                            return `${context.label}: ${formatCurrency(context.raw)}`;
                        }
                        return `${context.label}: ${context.raw} products`;
                    },
                },
            },
        },
    };

    if (props.chartType === 'bar') {
        return {
            ...baseOptions,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: (value: any) => {
                            return props.categoryData?.length ? formatCurrency(value) : `${value} items`;
                        },
                    },
                },
            },
            plugins: {
                ...baseOptions.plugins,
                legend: {
                    display: false,
                },
            },
        };
    }

    return baseOptions;
});

const totalValue = computed(() => {
    if (props.categoryData?.length) {
        return props.categoryData.reduce((sum, item) => sum + item.total_value, 0);
    }
    return props.statistics?.total_stock_value || 0;
});

const topCategory = computed(() => {
    if (!props.categoryData?.length) return null;
    return props.categoryData.reduce((max, item) =>
        item.total_value > max.total_value ? item : max
    );
});

onMounted(async () => {
    if (!chartContainer.value || !chartData.value) return;

    // Dynamically import Chart.js to reduce bundle size
    const { Chart, registerables } = await import('chart.js');
    Chart.register(...registerables);

    const ctx = chartContainer.value.getContext('2d');
    if (!ctx) return;

    chart.value = new Chart(ctx, {
        type: props.chartType === 'pie' ? 'pie' : props.chartType === 'bar' ? 'bar' : 'doughnut',
        data: chartData.value,
        options: chartOptions.value,
    });
});

// Clean up chart on unmount
onBeforeUnmount(() => {
    if (chart.value) {
        chart.value.destroy();
    }
});
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex items-center justify-between">
                <div>
                    <CardTitle class="flex items-center gap-2">
                        <component :is="chartType === 'bar' ? BarChart3 : PieChart" class="h-5 w-5" />
                        Stock Value Distribution
                    </CardTitle>
                    <CardDescription>
                        {{ categoryData?.length ? 'Inventory value breakdown by category' : 'Stock status distribution' }}
                    </CardDescription>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold">{{ formatCurrency(totalValue) }}</div>
                    <p class="text-xs text-muted-foreground">Total Value</p>
                </div>
            </div>
        </CardHeader>
        <CardContent>
            <div v-if="isLoading" class="space-y-4">
                <div class="flex justify-center">
                    <Skeleton class="h-48 w-48 rounded-full" />
                </div>
                <div class="space-y-2">
                    <div v-for="i in 4" :key="i" class="flex items-center justify-between">
                        <Skeleton class="h-4 w-24" />
                        <Skeleton class="h-4 w-16" />
                    </div>
                </div>
            </div>

            <div v-else-if="!chartData" class="text-center py-12">
                <PieChart class="h-12 w-12 mx-auto text-gray-400 mb-4" />
                <p class="text-gray-500 dark:text-gray-400">No data available for chart</p>
            </div>

            <div v-else class="space-y-6">
                <!-- Chart Canvas -->
                <div class="relative h-64">
                    <canvas ref="chartContainer"></canvas>
                </div>

                <!-- Chart Legend & Details -->
                <div class="space-y-4">
                    <!-- Top Performer -->
                    <div v-if="topCategory" class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <TrendingUp class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                <span class="text-sm font-medium">Top Category</span>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold">{{ topCategory.category_name }}</div>
                                <div class="text-sm text-blue-600 dark:text-blue-400">
                                    {{ formatCurrency(topCategory.total_value) }}
                                    <span class="text-xs text-muted-foreground ml-1">
                                        ({{ ((topCategory.total_value / totalValue) * 100).toFixed(1) }}%)
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Breakdown -->
                    <div v-if="categoryData?.length" class="space-y-2">
                        <h4 class="text-sm font-medium text-muted-foreground">Breakdown</h4>
                        <div class="space-y-2 max-h-32 overflow-y-auto">
                            <div
                                v-for="(item, index) in categoryData"
                                :key="item.category_id"
                                class="flex items-center justify-between py-1"
                            >
                                <div class="flex items-center space-x-2">
                                    <div
                                        class="w-3 h-3 rounded-full"
                                        :style="{ backgroundColor: chartData.datasets[0].backgroundColor[index] }"
                                    ></div>
                                    <span class="text-sm">{{ item.category_name }}</span>
                                </div>
                                <div class="text-right text-sm">
                                    <div class="font-medium">{{ formatCurrency(item.total_value) }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ item.total_quantity }} units
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Status Breakdown -->
                    <div v-else-if="statistics?.by_status" class="space-y-2">
                        <h4 class="text-sm font-medium text-muted-foreground">Stock Status</h4>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between py-1">
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                                    <span class="text-sm">In Stock</span>
                                </div>
                                <span class="text-sm font-medium">{{ statistics.by_status.in_stock }}</span>
                            </div>
                            <div class="flex items-center justify-between py-1">
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                    <span class="text-sm">Low Stock</span>
                                </div>
                                <span class="text-sm font-medium">{{ statistics.by_status.low_stock }}</span>
                            </div>
                            <div class="flex items-center justify-between py-1">
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                                    <span class="text-sm">Out of Stock</span>
                                </div>
                                <span class="text-sm font-medium">{{ statistics.by_status.out_of_stock }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>

<script>
import { onBeforeUnmount } from 'vue';
</script>