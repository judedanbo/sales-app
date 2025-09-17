<script setup lang="ts">
import BulkPricingModal from '@/components/products/BulkPricingModal.vue';
import PriceHistoryChart from '@/components/products/PriceHistoryChart.vue';
import PricingCalculator from '@/components/products/PricingCalculator.vue';
import PricingRulesTable from '@/components/products/PricingRulesTable.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useAlerts } from '@/composables/useAlerts';
import { useCurrency } from '@/composables/useCurrency';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type PaginatedData, type PricingRule, type PricingRuleFilters, type PricingStatistics, type Product, type ProductPrice } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Calculator, DollarSign, Percent, Plus, TrendingUp } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const { addAlert } = useAlerts();

interface Props {
    statistics: PricingStatistics;
    rules: PaginatedData<PricingRule>;
    products: Product[];
    selectedProduct?: Product;
    priceHistory?: ProductPrice[];
    filters: PricingRuleFilters;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pricing',
        href: '/pricing',
    },
];

const activeTab = ref('overview');
const isLoading = ref(false);
const showBulkPricingModal = ref(false);
const selectedProductForCalculator = ref<Product | null>(props.selectedProduct || null);

// Local filter state for rules
const localFilters = ref<PricingRuleFilters>({
    search: props.filters.search || '',
    rule_type: props.filters.rule_type || '',
    status: props.filters.status || '',
    valid_from: props.filters.valid_from || '',
    valid_to: props.filters.valid_to || '',
    applies_to: props.filters.applies_to || '',
    created_by: props.filters.created_by || '',
    sort_by: props.filters.sort_by || 'created_at',
    sort_direction: props.filters.sort_direction || 'desc',
});

// Debounced filter function
const debouncedFilter = useDebounceFn(() => {
    applyFilters();
}, 500);

// Watch for changes in local filters
watch(
    () => localFilters.value,
    (newFilters, oldFilters) => {
        if (newFilters.search !== oldFilters?.search) {
            debouncedFilter();
        } else {
            applyFilters();
        }
    },
    { deep: true }
);

function applyFilters() {
    isLoading.value = true;

    const params: Record<string, any> = {};

    Object.entries(localFilters.value).forEach(([key, value]) => {
        if (value && value !== '') {
            params[key] = value;
        }
    });

    // Add tab parameter to maintain state
    params.tab = activeTab.value;

    // Add selected product for calculator
    if (selectedProductForCalculator.value) {
        params.product_id = selectedProductForCalculator.value.id;
    }

    router.get('/pricing', params, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            isLoading.value = false;
        },
    });
}

function handleSort(column: string) {
    if (localFilters.value.sort_by === column) {
        localFilters.value.sort_direction = localFilters.value.sort_direction === 'asc' ? 'desc' : 'asc';
    } else {
        localFilters.value.sort_by = column;
        localFilters.value.sort_direction = 'asc';
    }
}

function handlePageChange(page: number) {
    const params: Record<string, any> = {};

    Object.entries(localFilters.value).forEach(([key, value]) => {
        if (value && value !== '') {
            params[key] = value;
        }
    });

    params.page = page;
    params.tab = activeTab.value;

    if (selectedProductForCalculator.value) {
        params.product_id = selectedProductForCalculator.value.id;
    }

    router.get('/pricing', params, {
        preserveScroll: true,
        preserveState: true,
    });
}

function handleEditRule(rule: PricingRule) {
    // In a real implementation, this would open an edit modal
    addAlert(
        `Edit rule: ${rule.name}`,
        'info',
        { title: 'Edit Rule' }
    );
}

function handleDeleteRule(rule: PricingRule) {
    // In a real implementation, this would show a confirmation dialog
    addAlert(
        `Delete rule: ${rule.name}`,
        'info',
        { title: 'Delete Rule' }
    );
}

function handleToggleRule(rule: PricingRule) {
    // In a real implementation, this would toggle the rule status
    const newStatus = rule.status === 'active' ? 'inactive' : 'active';
    addAlert(
        `Rule ${rule.name} ${newStatus === 'active' ? 'activated' : 'deactivated'}`,
        'success',
        { title: 'Rule Updated' }
    );
}

function handleCreateRule() {
    // In a real implementation, this would open a create modal
    addAlert(
        'Create pricing rule functionality coming soon',
        'info',
        { title: 'Coming Soon' }
    );
}

function handleProductChange(productId: string) {
    const product = props.products.find(p => p.id === parseInt(productId));
    selectedProductForCalculator.value = product || null;

    // Update URL to reflect selected product
    const params: Record<string, any> = {};
    if (product) {
        params.product_id = product.id;
    }
    params.tab = activeTab.value;

    router.get('/pricing', params, {
        preserveScroll: true,
        preserveState: true,
    });
}

function handlePricesUpdated() {
    // Refresh the page to show updated data
    router.reload();
}

const { formatCurrency } = useCurrency();
</script>

<template>
    <Head title="Pricing Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-8">
            <!-- Header Section -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Pricing Management</h1>
                    <p class="text-muted-foreground">
                        Manage pricing rules, track price history, and calculate dynamic pricing.
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" @click="showBulkPricingModal = true">
                        <DollarSign class="h-4 w-4 mr-2" />
                        Bulk Pricing
                    </Button>
                    <Button @click="handleCreateRule">
                        <Plus class="h-4 w-4 mr-2" />
                        Create Rule
                    </Button>
                </div>
            </div>

            <!-- Key Metrics -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Rules</CardTitle>
                        <Percent class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div v-if="isLoading">
                            <Skeleton class="h-8 w-16" />
                        </div>
                        <div v-else>
                            <div class="text-2xl font-bold">{{ statistics?.total_rules || 0 }}</div>
                            <p class="text-xs text-muted-foreground">
                                {{ statistics?.active_rules || 0 }} active
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Discounts</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div v-if="isLoading">
                            <Skeleton class="h-8 w-20" />
                        </div>
                        <div v-else>
                            <div class="text-2xl font-bold">{{ formatCurrency(statistics?.total_discount_amount || 0) }}</div>
                            <p class="text-xs text-muted-foreground">
                                This month
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Active Rules</CardTitle>
                        <Percent class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div v-if="isLoading">
                            <Skeleton class="h-8 w-16" />
                        </div>
                        <div v-else>
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                {{ statistics?.active_rules || 0 }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Currently running
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Expiring Soon</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div v-if="isLoading">
                            <Skeleton class="h-8 w-16" />
                        </div>
                        <div v-else>
                            <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                                {{ statistics?.upcoming_expirations?.length || 0 }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Next 7 days
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Tabs -->
            <Tabs v-model="activeTab" class="space-y-6">
                <TabsList class="grid w-full grid-cols-4">
                    <TabsTrigger value="overview">Overview</TabsTrigger>
                    <TabsTrigger value="rules">Pricing Rules</TabsTrigger>
                    <TabsTrigger value="history">Price History</TabsTrigger>
                    <TabsTrigger value="calculator">Calculator</TabsTrigger>
                </TabsList>

                <!-- Overview Tab -->
                <TabsContent value="overview" class="space-y-6">
                    <div class="grid gap-6 md:grid-cols-2">
                        <!-- Most Used Rules -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Most Used Rules</CardTitle>
                                <CardDescription>
                                    Pricing rules with highest usage
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div v-if="isLoading" class="space-y-4">
                                    <div v-for="i in 5" :key="i" class="flex items-center justify-between">
                                        <Skeleton class="h-4 w-32" />
                                        <Skeleton class="h-4 w-16" />
                                    </div>
                                </div>
                                <div v-else-if="!statistics?.most_used_rules?.length" class="text-center py-8">
                                    <Percent class="h-12 w-12 mx-auto text-gray-400 mb-4" />
                                    <p class="text-gray-500 dark:text-gray-400">No usage data available</p>
                                </div>
                                <div v-else class="space-y-4">
                                    <div
                                        v-for="rule in statistics.most_used_rules.slice(0, 5)"
                                        :key="rule.rule.id"
                                        class="flex items-center justify-between"
                                    >
                                        <div>
                                            <p class="font-medium">{{ rule.rule.name }}</p>
                                            <p class="text-sm text-gray-500">{{ rule.usage_count }} uses</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-medium">{{ formatCurrency(rule.total_discount) }}</p>
                                            <p class="text-sm text-gray-500">Total discount</p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Rules by Type -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Rules by Type</CardTitle>
                                <CardDescription>
                                    Distribution of pricing rule types
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div v-if="isLoading" class="space-y-4">
                                    <div v-for="i in 4" :key="i" class="flex items-center justify-between">
                                        <Skeleton class="h-4 w-28" />
                                        <Skeleton class="h-4 w-8" />
                                    </div>
                                </div>
                                <div v-else-if="!statistics?.rules_by_type" class="text-center py-8">
                                    <Percent class="h-12 w-12 mx-auto text-gray-400 mb-4" />
                                    <p class="text-gray-500 dark:text-gray-400">No rule data available</p>
                                </div>
                                <div v-else class="space-y-4">
                                    <div
                                        v-for="(count, type) in statistics.rules_by_type"
                                        :key="type"
                                        class="flex items-center justify-between"
                                    >
                                        <span class="capitalize">{{ type.replace('_', ' ') }}</span>
                                        <span class="font-medium">{{ count }}</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                <!-- Pricing Rules Tab -->
                <TabsContent value="rules" class="space-y-6">
                    <PricingRulesTable
                        :rules="rules"
                        :filters="localFilters"
                        :is-loading="isLoading"
                        @edit="handleEditRule"
                        @delete="handleDeleteRule"
                        @toggle="handleToggleRule"
                        @sort="handleSort"
                        @page-change="handlePageChange"
                    />
                </TabsContent>

                <!-- Price History Tab -->
                <TabsContent value="history" class="space-y-6">
                    <div class="space-y-4">
                        <!-- Product Selector -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Select Product</CardTitle>
                                <CardDescription>
                                    Choose a product to view its price history
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Select
                                    :model-value="selectedProductForCalculator?.id?.toString() || ''"
                                    @update:model-value="handleProductChange"
                                >
                                    <SelectTrigger class="w-full">
                                        <SelectValue placeholder="Select a product..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="product in products"
                                            :key="product.id"
                                            :value="product.id.toString()"
                                        >
                                            {{ product.name }} ({{ product.sku }})
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </CardContent>
                        </Card>

                        <!-- Price History Chart -->
                        <div v-if="selectedProductForCalculator">
                            <PriceHistoryChart
                                :prices="priceHistory || []"
                                :is-loading="isLoading"
                            />
                        </div>
                        <div v-else class="text-center py-12">
                            <TrendingUp class="h-12 w-12 mx-auto text-gray-400 mb-4" />
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No product selected</h3>
                            <p class="text-gray-500 dark:text-gray-400">
                                Select a product above to view its price history.
                            </p>
                        </div>
                    </div>
                </TabsContent>

                <!-- Calculator Tab -->
                <TabsContent value="calculator" class="space-y-6">
                    <div class="space-y-4">
                        <!-- Product Selector -->
                        <Card>
                            <CardHeader>
                                <CardTitle>Select Product</CardTitle>
                                <CardDescription>
                                    Choose a product to calculate pricing with rules
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Select
                                    :model-value="selectedProductForCalculator?.id?.toString() || ''"
                                    @update:model-value="handleProductChange"
                                >
                                    <SelectTrigger class="w-full">
                                        <SelectValue placeholder="Select a product..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="product in products"
                                            :key="product.id"
                                            :value="product.id.toString()"
                                        >
                                            {{ product.name }} ({{ product.sku }}) - {{ formatCurrency(product.unit_price || 0) }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </CardContent>
                        </Card>

                        <!-- Pricing Calculator -->
                        <div v-if="selectedProductForCalculator">
                            <PricingCalculator
                                :product="selectedProductForCalculator"
                                :pricing-rules="rules.data"
                            />
                        </div>
                        <div v-else class="text-center py-12">
                            <Calculator class="h-12 w-12 mx-auto text-gray-400 mb-4" />
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No product selected</h3>
                            <p class="text-gray-500 dark:text-gray-400">
                                Select a product above to calculate pricing with applied rules.
                            </p>
                        </div>
                    </div>
                </TabsContent>
            </Tabs>
        </div>

        <!-- Bulk Pricing Modal -->
        <BulkPricingModal
            :open="showBulkPricingModal"
            :products="products"
            @update:open="showBulkPricingModal = $event"
            @prices-updated="handlePricesUpdated"
        />
    </AppLayout>
</template>