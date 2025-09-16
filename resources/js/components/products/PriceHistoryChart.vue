<script setup lang="ts">
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { type ProductPrice } from '@/types';
import {
    CategoryScale,
    ChartConfiguration,
    Chart as ChartJS,
    Filler,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Title,
    Tooltip,
} from 'chart.js';
import { TrendingUp } from 'lucide-vue-next';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { Line } from 'vue-chartjs';

// Register Chart.js components
ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler);

interface Props {
    prices: ProductPrice[];
    isLoading?: boolean;
}

const props = defineProps<Props>();

const chartContainer = ref<HTMLCanvasElement | null>(null);
const chartInstance = ref<ChartJS | null>(null);
const timeRange = ref('6m'); // 1m, 3m, 6m, 1y, all
const priceType = ref('final_price'); // price, final_price

const timeRangeOptions = [
    { value: '1m', label: 'Last Month' },
    { value: '3m', label: 'Last 3 Months' },
    { value: '6m', label: 'Last 6 Months' },
    { value: '1y', label: 'Last Year' },
    { value: 'all', label: 'All Time' },
];

const priceTypeOptions = [
    { value: 'price', label: 'Base Price' },
    { value: 'final_price', label: 'Final Price' },
];

const filteredPrices = computed(() => {
    // Validate input data
    if (!props.prices || !Array.isArray(props.prices)) {
        return [];
    }

    let filtered = [...props.prices].filter((price) => {
        // Ensure required fields exist
        return (
            price &&
            price.valid_from &&
            price.price !== null &&
            price.price !== undefined &&
            price.final_price !== null &&
            price.final_price !== undefined
        );
    });

    // Filter by time range
    if (timeRange.value !== 'all') {
        const now = new Date();
        const cutoffDate = new Date();

        switch (timeRange.value) {
            case '1m':
                cutoffDate.setMonth(now.getMonth() - 1);
                break;
            case '3m':
                cutoffDate.setMonth(now.getMonth() - 3);
                break;
            case '6m':
                cutoffDate.setMonth(now.getMonth() - 6);
                break;
            case '1y':
                cutoffDate.setFullYear(now.getFullYear() - 1);
                break;
        }

        filtered = filtered.filter((price) => {
            const validFrom = new Date(price.valid_from);
            return !isNaN(validFrom.getTime()) && validFrom >= cutoffDate;
        });
    }

    // Sort by valid_from date
    return filtered.sort((a, b) => {
        const dateA = new Date(a.valid_from);
        const dateB = new Date(b.valid_from);
        return dateA.getTime() - dateB.getTime();
    });
});

const chartData = computed(() => {
    const data = {
        labels: filteredPrices.value.map((price) => new Date(price.valid_from).toLocaleDateString()),
        datasets: [
            {
                label: priceType.value === 'price' ? 'Base Price' : 'Final Price',
                data: filteredPrices.value.map((price) => (priceType.value === 'price' ? price.price || 0 : price.final_price || price.price || 0)),
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 2,
                pointBackgroundColor: '#3b82f6',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.1,
            },
        ],
    };

    // console.log('filteredPrices: ' + filteredPrices);
    // const data = filteredPrices.value.map((price) => ({
    //     date: new Date(price.valid_from).toLocaleDateString(),
    //     price: priceType.value === 'price' ? price.price || 0 : price.final_price || price.price || 0,
    //     status: price.status || 'unknown',
    //     version: price.version_number || 1,
    // }));

    // console.log('chartData computed:', {
    //     originalPrices: props.prices.length,
    //     originalPrices: props.prices.length,
    //     filteredPrices: filteredPrices.value,
    //     chartData: data,
    //     chartDataLen: data.length,
    //     sampleData: data.slice(0, 2),
    // });

    return data;
});

const priceStats = computed(() => {
    if (filteredPrices.value.length === 0) {
        return {
            current: 0,
            highest: 0,
            lowest: 0,
            average: 0,
            change: 0,
            changePercentage: 0,
        };
    }

    const prices = filteredPrices.value
        .map((p) => {
            const price = priceType.value === 'price' ? p.price || 0 : p.final_price || p.price || 0;
            return Number(price) || 0;
        })
        .filter((price) => price > 0);

    if (prices.length === 0) {
        return {
            current: 0,
            highest: 0,
            lowest: 0,
            average: 0,
            change: 0,
            changePercentage: 0,
        };
    }

    const current = prices[prices.length - 1];
    const previous = prices.length > 1 ? prices[prices.length - 2] : current;
    const highest = Math.max(...prices);
    const lowest = Math.min(...prices);
    const average = prices.reduce((sum, price) => sum + price, 0) / prices.length;
    const change = current - previous;
    const changePercentage = previous > 0 ? (change / previous) * 100 : 0;

    return {
        current,
        highest,
        lowest,
        average,
        change,
        changePercentage,
    };
});

const chartConfig = computed<ChartConfiguration<'line'>>(() => ({
    type: 'line',
    data: {
        labels: chartData.value.length > 0 ? chartData.value.map((d) => d.date) : [],
        datasets: [
            {
                label: priceType.value === 'price' ? 'Base Price' : 'Final Price',
                data: chartData.value.length > 0 ? chartData.value.map((d) => d.price) : [],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 2,
                pointBackgroundColor: '#3b82f6',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.1,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            intersect: false,
            mode: 'index',
        },
        plugins: {
            title: {
                display: false,
            },
            legend: {
                display: false,
            },
            tooltip: {
                backgroundColor: 'rgba(17, 24, 39, 0.9)',
                titleColor: '#ffffff',
                bodyColor: '#ffffff',
                borderColor: '#374151',
                borderWidth: 1,
                cornerRadius: 8,
                displayColors: false,
                callbacks: {
                    label: (context) => {
                        const price = context.parsed.y;
                        const dataPoint = chartData.value[context.dataIndex];
                        return [`Price: GH₵${price.toFixed(2)}`, `Status: ${dataPoint.status}`, `Version: ${dataPoint.version}`];
                    },
                },
            },
        },
        scales: {
            x: {
                display: true,
                title: {
                    display: true,
                    text: 'Date',
                    color: '#6b7280',
                },
                grid: {
                    color: '#e5e7eb',
                    drawBorder: false,
                },
                ticks: {
                    color: '#6b7280',
                    maxTicksLimit: 6,
                },
            },
            y: {
                display: true,
                title: {
                    display: true,
                    text: 'Price (GH₵)',
                    color: '#6b7280',
                },
                grid: {
                    color: '#e5e7eb',
                    drawBorder: false,
                },
                ticks: {
                    color: '#6b7280',
                    callback: (value) => `GH₵${Number(value).toFixed(2)}`,
                },
            },
        },
    },
}));

const initChart = async () => {
    console.log('initChart called', {
        hasContainer: !!chartContainer.value,
        dataLength: chartData.value.length,
        prices: props.prices.length,
    });

    if (!chartContainer.value) {
        console.log('No chart container found');
        return;
    }

    // Destroy existing chart
    if (chartInstance.value) {
        chartInstance.value.destroy();
        chartInstance.value = null;
    }

    await nextTick();

    // Create new chart
    const ctx = chartContainer.value.getContext('2d');
    if (!ctx) {
        console.log('Could not get canvas context');
        return;
    }

    try {
        console.log('Chart config:', chartConfig.value);
        chartInstance.value = new ChartJS(ctx, chartConfig.value);
        console.log('Chart created successfully', chartInstance.value);
    } catch (error) {
        console.error('Error creating chart:', error);
    }
};

const updateChart = () => {
    if (!chartInstance.value) return;

    // Update chart data
    chartInstance.value.data = chartConfig.value.data;
    if (chartConfig.value.options) {
        Object.assign(chartInstance.value.options, chartConfig.value.options);
    }
    chartInstance.value.update('none');
};

onMounted(async () => {
    // Always initialize chart, even with empty data
    await nextTick();
    await initChart();
});

onUnmounted(() => {
    if (chartInstance.value) {
        chartInstance.value.destroy();
        chartInstance.value = null;
    }
});

watch([timeRange, priceType, () => props.prices], async () => {
    console.log('Watch triggered:', {
        timeRange: timeRange.value,
        priceType: priceType.value,
        pricesLength: props.prices.length,
        chartDataLength: chartData.value.length,
    });

    if (chartData.value.length === 0) {
        if (chartInstance.value) {
            chartInstance.value.destroy();
            chartInstance.value = null;
        }
        return;
    }

    if (chartInstance.value) {
        updateChart();
    } else {
        await initChart();
    }
});
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center gap-2">
                <TrendingUp class="h-5 w-5" />
                Price History
            </CardTitle>
            <CardDescription> Track price changes and trends over time. </CardDescription>
        </CardHeader>
        <CardContent>
            <div v-if="isLoading" class="space-y-4">
                <div class="flex gap-4">
                    <Skeleton class="h-10 w-32" />
                    <Skeleton class="h-10 w-32" />
                </div>
                <Skeleton class="h-[300px] w-full" />
                <div class="grid grid-cols-4 gap-4">
                    <Skeleton class="h-16 w-full" />
                    <Skeleton class="h-16 w-full" />
                    <Skeleton class="h-16 w-full" />
                    <Skeleton class="h-16 w-full" />
                </div>
            </div>

            <div v-else-if="prices.length === 0" class="py-8 text-center">
                <TrendingUp class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                <h3 class="mb-2 text-lg font-medium text-gray-900 dark:text-gray-100">No price history</h3>
                <p class="text-gray-500 dark:text-gray-400">Price history will appear here as you update product prices.</p>
            </div>

            <div v-else class="space-y-6">
                <!-- Controls -->
                <div class="flex flex-col gap-4 sm:flex-row">
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Time Range</label>
                        <Select v-model="timeRange">
                            <SelectTrigger class="w-40">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="option in timeRangeOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Price Type</label>
                        <Select v-model="priceType">
                            <SelectTrigger class="w-40">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="option in priceTypeOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <!-- Chart -->
                <div class="flex h-[300px] w-full justify-center overflow-hidden rounded-lg border p-4">
                    <Line id="priceChart" :data="chartData" class="h-full w-full" width="800" height="300"> </Line>
                </div>

                <!-- Price Statistics -->
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <div class="rounded-lg bg-blue-50 p-4 text-center dark:bg-blue-900/10">
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">GH₵{{ priceStats.current.toFixed(2) }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Current Price</div>
                        <div
                            v-if="priceStats.changePercentage !== 0"
                            class="mt-1 text-xs"
                            :class="{
                                'text-green-600 dark:text-green-400': priceStats.change > 0,
                                'text-red-600 dark:text-red-400': priceStats.change < 0,
                            }"
                        >
                            {{ priceStats.change > 0 ? '+' : '' }}{{ priceStats.changePercentage.toFixed(1) }}%
                        </div>
                    </div>

                    <div class="rounded-lg bg-green-50 p-4 text-center dark:bg-green-900/10">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">GH₵{{ priceStats.highest.toFixed(2) }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Highest</div>
                    </div>

                    <div class="rounded-lg bg-red-50 p-4 text-center dark:bg-red-900/10">
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400">GH₵{{ priceStats.lowest.toFixed(2) }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Lowest</div>
                    </div>

                    <div class="rounded-lg bg-gray-50 p-4 text-center dark:bg-gray-900/10">
                        <div class="text-2xl font-bold text-gray-600 dark:text-gray-400">GH₵{{ priceStats.average.toFixed(2) }}</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Average</div>
                    </div>
                </div>

                <!-- Recent Price Changes -->
                <div v-if="filteredPrices.length > 0" class="space-y-2">
                    <h4 class="font-medium">Recent Changes</h4>
                    <div class="max-h-40 space-y-2 overflow-y-auto">
                        <div
                            v-for="price in filteredPrices.slice(-5).reverse()"
                            :key="price.id"
                            class="flex items-center justify-between rounded-lg border p-3"
                        >
                            <div>
                                <div class="font-medium">Version {{ price.version_number }}</div>
                                <div class="text-sm text-gray-500">
                                    {{ new Date(price.valid_from).toLocaleDateString() }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-medium">GH₵{{ priceType === 'price' ? price.price : price.final_price }}</div>
                                <div class="text-sm text-gray-500">{{ price.status }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
