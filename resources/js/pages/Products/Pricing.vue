<script setup lang="ts">
import PermissionGuard from '@/components/PermissionGuard.vue';
import PriceHistoryChart from '@/components/products/PriceHistoryChart.vue';
import PricingCalculator from '@/components/products/PricingCalculator.vue';
import UpdatePriceModal from '@/components/products/UpdatePriceModal.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import PageHeader from '@/components/ui/PageHeader.vue';
import { useCurrency } from '@/composables/useCurrency';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type PricingRule, type Product, type ProductPrice } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, DollarSign, Edit, TrendingUp } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    product: Product;
    pricing_rules: PricingRule[];
    price_history: ProductPrice[];
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
        title: 'Pricing',
        href: `/products/${props.product.id}/pricing`,
    },
];

const isUpdatePriceModalOpen = ref(false);

// Event handlers
const handleEditPrice = () => {
    isUpdatePriceModalOpen.value = true;
};

const handlePriceCreated = () => {
    // Refresh the page to show the updated data
    router.reload();
};

const { formatCurrency: formatPrice } = useCurrency();

const formatDate = (dateString: string): string => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getRuleTypeColor = (type: string): string => {
    switch (type) {
        case 'bulk_discount':
            return 'bg-blue-100 text-blue-800';
        case 'promotional':
            return 'bg-green-100 text-green-800';
        case 'seasonal':
            return 'bg-orange-100 text-orange-800';
        case 'member_discount':
            return 'bg-purple-100 text-purple-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const getActiveRules = () => {
    return props.pricing_rules.filter(
        (rule) =>
            rule.status === 'active' &&
            (!rule.valid_from || new Date(rule.valid_from) <= new Date()) &&
            (!rule.valid_to || new Date(rule.valid_to) >= new Date()),
    );
};

const getPriceChange = () => {
    if (props.price_history.length < 2) return null;

    const current = props.price_history[0];
    const previous = props.price_history[1];
    const change = current.price - previous.price;
    const percentage = (change / previous.price) * 100;

    return {
        amount: change,
        percentage: percentage,
        isIncrease: change > 0,
    };
};
</script>

<template>
    <Head :title="`${product.name} - Pricing`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-8">
            <!-- Header -->
            <PageHeader :title="`${product.name} Pricing`" :description="`Manage pricing, rules, and history for ${product.name}`">
                <template #action>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" @click="router.visit(`/products/${product.id}`)">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Back to Product
                        </Button>
                    </div>
                </template>
            </PageHeader>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Current Pricing -->
                <div class="space-y-6">
                    <!-- Current Price Card -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <DollarSign class="h-5 w-5" />
                                Current Price
                            </CardTitle>
                            <CardDescription>Base unit price for {{ product.name }}</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-3xl font-bold">{{ formatPrice(product.unit_price) }}</div>
                                        <div class="text-sm text-muted-foreground">per {{ product.unit_type }}</div>

                                        <!-- Price Change Indicator -->
                                        <div v-if="getPriceChange()" class="mt-2 flex items-center gap-1 text-sm">
                                            <TrendingUp
                                                :class="getPriceChange()?.isIncrease ? 'text-green-600' : 'rotate-180 text-red-600'"
                                                class="h-4 w-4"
                                            />
                                            <span :class="getPriceChange()?.isIncrease ? 'text-green-600' : 'text-red-600'">
                                                {{ getPriceChange()?.isIncrease ? '+' : '' }}{{ formatPrice(getPriceChange()?.amount || 0) }} ({{
                                                    getPriceChange()?.percentage.toFixed(1)
                                                }}%)
                                            </span>
                                            <span class="text-muted-foreground">from last change</span>
                                        </div>
                                    </div>

                                    <PermissionGuard permission="edit_products">
                                        <Button variant="outline" @click="handleEditPrice">
                                            <Edit class="mr-2 h-4 w-4" />
                                            Update Price
                                        </Button>
                                    </PermissionGuard>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <!-- Recent Price Changes -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Price Changes</CardTitle>
                            <CardDescription>Latest pricing updates for this product</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="price_history.length === 0" class="py-8 text-center">
                                <TrendingUp class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                                <p class="text-gray-500 dark:text-gray-400">No price history</p>
                                <p class="text-sm text-gray-400 dark:text-gray-500">Price changes will appear here</p>
                            </div>

                            <div v-else class="space-y-3">
                                <div
                                    v-for="(price, index) in price_history.slice(0, 5)"
                                    :key="index"
                                    class="flex items-center justify-between border-b py-2 last:border-b-0"
                                >
                                    <div class="space-y-1">
                                        <div class="font-medium">{{ formatPrice(price.price) }}</div>
                                        <div class="text-sm text-muted-foreground">
                                            {{ formatDate(price.effective_date) }}
                                        </div>
                                        <div v-if="price.reason" class="text-xs text-muted-foreground">
                                            {{ price.reason }}
                                        </div>
                                    </div>
                                    <div v-if="index > 0" class="text-right">
                                        <div
                                            :class="price.price > price_history[index - 1].price ? 'text-green-600' : 'text-red-600'"
                                            class="text-sm font-medium"
                                        >
                                            {{ price.price > price_history[index - 1].price ? '+' : ''
                                            }}{{ formatPrice(price.price - price_history[index - 1].price) }}
                                        </div>
                                        <div class="text-xs text-muted-foreground">
                                            {{
                                                (((price.price - price_history[index - 1].price) / price_history[index - 1].price) * 100).toFixed(1)
                                            }}%
                                        </div>
                                    </div>
                                    <div v-else class="text-sm text-muted-foreground">Current</div>
                                </div>

                                <div v-if="price_history.length > 5" class="pt-2 text-center">
                                    <Button variant="ghost" size="sm" @click="router.visit(`/pricing?product_id=${product.id}&tab=history`)">
                                        View Full History
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    <!-- Active Pricing Rules
                    <Card>
                        <CardHeader>
                            <CardTitle>Active Pricing Rules</CardTitle>
                            <CardDescription>Rules currently affecting this product's pricing</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="getActiveRules().length === 0" class="text-center py-8">
                                <DollarSign class="h-12 w-12 mx-auto text-gray-400 mb-4" />
                                <p class="text-gray-500 dark:text-gray-400">No active pricing rules</p>
                                <p class="text-sm text-gray-400 dark:text-gray-500">
                                    This product uses base pricing only
                                </p>
                            </div>

                            <div v-else class="space-y-3">
                                <div
                                    v-for="rule in getActiveRules().slice(0, 5)"
                                    :key="rule.id"
                                    class="flex items-center justify-between p-3 border rounded-lg"
                                >
                                    <div class="space-y-1">
                                        <div class="font-medium">{{ rule.name }}</div>
                                        <div class="flex items-center gap-2">
                                            <span
                                                :class="getRuleTypeColor(rule.rule_type)"
                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                            >
                                                {{ rule.rule_type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                                            </span>
                                            <span class="text-sm text-muted-foreground">
                                                {{ rule.discount_type === 'percentage' ? `${rule.discount_value}%` : formatPrice(rule.discount_value || 0) }} off
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-right text-sm text-muted-foreground">
                                        <div>{{ rule.usage_count || 0 }} uses</div>
                                        <div v-if="rule.valid_to">
                                            Expires {{ formatDate(rule.valid_to) }}
                                        </div>
                                    </div>
                                </div>

                                <div v-if="getActiveRules().length > 5" class="text-center">
                                    <Button variant="ghost" size="sm" @click="router.visit('/pricing')">
                                        View All Rules ({{ getActiveRules().length }})
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card> -->
                </div>

                <!-- Right Column -->
                <div class="space-y-6">
                    <!-- Pricing Calculator -->
                    <PricingCalculator :product="product" :pricing-rules="pricing_rules" />
                </div>
            </div>

            <!-- Price History Chart -->
            <PriceHistoryChart :prices="price_history" :isLoading="isLoading" />
        </div>

        <!-- Update Price Modal -->
        <UpdatePriceModal
            :open="isUpdatePriceModalOpen"
            :product="product"
            @update:open="isUpdatePriceModalOpen = $event"
            @price-created="handlePriceCreated"
        />
    </AppLayout>
</template>
