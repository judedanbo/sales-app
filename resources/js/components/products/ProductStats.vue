<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { type ProductStatistics } from '@/types';
import { AlertTriangle, Archive, BadgeCent, Package, ShoppingCart, TrendingUp } from 'lucide-vue-next';

interface Props {
    statistics: ProductStatistics;
}

defineProps<Props>();

// Format currency values in Ghana Cedis
const formatCurrency = (value: number): string => {
    return new Intl.NumberFormat('en-GH', {
        style: 'currency',
        currency: 'GHS',
    }).format(value);
};

// Format percentage
const formatPercentage = (current: number, total: number): string => {
    if (total === 0) return '0%';
    return `${((current / total) * 100).toFixed(1)}%`;
};
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <!-- Total Products -->
        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Total Products</CardTitle>
                <Package class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ statistics.total.toLocaleString() }}</div>
                <p class="text-xs text-muted-foreground">{{ statistics.recent }} added this month</p>
            </CardContent>
        </Card>

        <!-- Active Products -->
        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Active Products</CardTitle>
                <ShoppingCart class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold text-green-600">{{ statistics.active.toLocaleString() }}</div>
                <p class="text-xs text-muted-foreground">{{ formatPercentage(statistics.active, statistics.total) }} of total</p>
            </CardContent>
        </Card>

        <!-- Low Stock Alert -->
        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Low Stock Items</CardTitle>
                <AlertTriangle class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold text-orange-600">{{ statistics.low_stock.toLocaleString() }}</div>
                <p class="text-xs text-muted-foreground">{{ statistics.out_of_stock }} completely out of stock</p>
            </CardContent>
        </Card>

        <!-- Total Value -->
        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Inventory Value</CardTitle>
                <BadgeCent class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ formatCurrency(statistics.value_breakdown.total_value) }}</div>
                <p class="text-xs text-muted-foreground">Avg: {{ formatCurrency(statistics.value_breakdown.average_price) }}</p>
            </CardContent>
        </Card>
    </div>

    <!-- Additional Status Cards -->
    <div class="mt-4 grid gap-4 md:grid-cols-3 lg:grid-cols-6">
        <!-- Inactive Products -->
        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Inactive</CardTitle>
                <Archive class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-xl font-bold text-gray-600">{{ statistics.inactive.toLocaleString() }}</div>
                <p class="text-xs text-muted-foreground">
                    {{ formatPercentage(statistics.inactive, statistics.total) }}
                </p>
            </CardContent>
        </Card>

        <!-- Discontinued Products -->
        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Discontinued</CardTitle>
                <Archive class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-xl font-bold text-red-600">{{ statistics.discontinued.toLocaleString() }}</div>
                <p class="text-xs text-muted-foreground">
                    {{ formatPercentage(statistics.discontinued, statistics.total) }}
                </p>
            </CardContent>
        </Card>

        <!-- Price Range Info -->
        <Card class="md:col-span-2">
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Price Range</CardTitle>
                <TrendingUp class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-sm font-medium">
                    {{ formatCurrency(statistics.value_breakdown.lowest_price) }} - {{ formatCurrency(statistics.value_breakdown.highest_price) }}
                </div>
                <p class="text-xs text-muted-foreground">Lowest to highest price</p>
            </CardContent>
        </Card>

        <!-- Top Categories Preview -->
        <Card class="md:col-span-2" v-if="statistics.by_category.length > 0">
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Top Categories</CardTitle>
                <Package class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="space-y-1">
                    <div v-for="category in statistics.by_category.slice(0, 2)" :key="category.category_id" class="flex justify-between text-sm">
                        <span class="truncate">{{ category.category_name }}</span>
                        <span class="font-medium">{{ category.count }}</span>
                    </div>
                </div>
                <p class="mt-1 text-xs text-muted-foreground" v-if="statistics.by_category.length > 2">
                    +{{ statistics.by_category.length - 2 }} more categories
                </p>
            </CardContent>
        </Card>
    </div>
</template>
