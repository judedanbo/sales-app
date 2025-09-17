<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { type Category, type Product, type StockMovementFilters } from '@/types';
import { Calendar, Filter, RotateCcw, Search, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    filters: StockMovementFilters;
    products?: Product[];
    categories?: Category[];
    showAdvanced?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showAdvanced: false,
});

const emit = defineEmits<{
    'update:filters': [filters: StockMovementFilters];
    'clear': [];
}>();

const localFilters = ref<StockMovementFilters>({ ...props.filters });
const showAdvancedFilters = ref(props.showAdvanced);

// Movement types for filtering
const movementTypes = [
    { value: 'initial_stock', label: 'Initial Stock' },
    { value: 'purchase', label: 'Purchase' },
    { value: 'sale', label: 'Sale' },
    { value: 'return_from_customer', label: 'Customer Return' },
    { value: 'return_to_supplier', label: 'Supplier Return' },
    { value: 'adjustment', label: 'Stock Adjustment' },
    { value: 'transfer_in', label: 'Transfer In' },
    { value: 'transfer_out', label: 'Transfer Out' },
    { value: 'damaged', label: 'Damaged' },
    { value: 'expired', label: 'Expired' },
    { value: 'theft', label: 'Theft/Loss' },
    { value: 'manufacturing', label: 'Manufacturing' },
    { value: 'reservation', label: 'Reserved' },
    { value: 'release_reservation', label: 'Released Reservation' },
];

const referenceTypes = [
    { value: 'purchase_order', label: 'Purchase Order' },
    { value: 'sales_order', label: 'Sales Order' },
    { value: 'transfer_order', label: 'Transfer Order' },
    { value: 'adjustment', label: 'Stock Adjustment' },
    { value: 'return', label: 'Return' },
];

const stockStatuses = [
    { value: 'in_stock', label: 'In Stock' },
    { value: 'low_stock', label: 'Low Stock' },
    { value: 'out_of_stock', label: 'Out of Stock' },
];

watch(
    () => localFilters.value,
    (newFilters) => {
        emit('update:filters', { ...newFilters });
    },
    { deep: true }
);

const hasActiveFilters = computed(() => {
    return Object.values(localFilters.value).some(value => value && value !== '');
});

const clearFilters = () => {
    localFilters.value = {
        search: '',
        product_id: '',
        variant_id: '',
        movement_type: '',
        date_from: '',
        date_to: '',
        created_by: '',
        reference_type: '',
        sort_by: 'movement_date',
        sort_direction: 'desc',
    };
    emit('clear');
};

const toggleAdvanced = () => {
    showAdvancedFilters.value = !showAdvancedFilters.value;
};
</script>

<template>
    <Card>
        <CardHeader class="pb-4">
            <div class="flex items-center justify-between">
                <CardTitle class="flex items-center gap-2">
                    <Filter class="h-4 w-4" />
                    Filters
                </CardTitle>
                <div class="flex items-center gap-2">
                    <Button
                        v-if="hasActiveFilters"
                        variant="ghost"
                        size="sm"
                        @click="clearFilters"
                    >
                        <RotateCcw class="h-4 w-4 mr-1" />
                        Clear
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        @click="toggleAdvanced"
                    >
                        {{ showAdvancedFilters ? 'Basic' : 'Advanced' }}
                    </Button>
                </div>
            </div>
        </CardHeader>
        <CardContent class="space-y-4">
            <!-- Basic Filters -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="space-y-2">
                    <Label for="search">Search</Label>
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <Input
                            id="search"
                            v-model="localFilters.search"
                            placeholder="Product name, SKU, reference..."
                            class="pl-10"
                        />
                        <Button
                            v-if="localFilters.search"
                            variant="ghost"
                            size="sm"
                            class="absolute right-1 top-1/2 transform -translate-y-1/2 h-6 w-6 p-0"
                            @click="localFilters.search = ''"
                        >
                            <X class="h-3 w-3" />
                        </Button>
                    </div>
                </div>

                <!-- Movement Type -->
                <div class="space-y-2">
                    <Label for="movement-type">Movement Type</Label>
                    <Select v-model="localFilters.movement_type">
                        <SelectTrigger id="movement-type">
                            <SelectValue placeholder="All types" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Types</SelectItem>
                            <SelectItem
                                v-for="type in movementTypes"
                                :key="type.value"
                                :value="type.value"
                            >
                                {{ type.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Date From -->
                <div class="space-y-2">
                    <Label for="date-from">From Date</Label>
                    <div class="relative">
                        <Calendar class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <Input
                            id="date-from"
                            v-model="localFilters.date_from"
                            type="date"
                            class="pl-10"
                        />
                    </div>
                </div>

                <!-- Date To -->
                <div class="space-y-2">
                    <Label for="date-to">To Date</Label>
                    <div class="relative">
                        <Calendar class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <Input
                            id="date-to"
                            v-model="localFilters.date_to"
                            type="date"
                            class="pl-10"
                        />
                    </div>
                </div>
            </div>

            <!-- Advanced Filters -->
            <div v-if="showAdvancedFilters" class="space-y-4 pt-4 border-t">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Product Selection -->
                    <div v-if="products?.length" class="space-y-2">
                        <Label for="product">Product</Label>
                        <Select v-model="localFilters.product_id">
                            <SelectTrigger id="product">
                                <SelectValue placeholder="All products" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All Products</SelectItem>
                                <SelectItem
                                    v-for="product in products"
                                    :key="product.id"
                                    :value="product.id.toString()"
                                >
                                    {{ product.name }} ({{ product.sku }})
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Reference Type -->
                    <div class="space-y-2">
                        <Label for="reference-type">Reference Type</Label>
                        <Select v-model="localFilters.reference_type">
                            <SelectTrigger id="reference-type">
                                <SelectValue placeholder="All references" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All References</SelectItem>
                                <SelectItem
                                    v-for="ref in referenceTypes"
                                    :key="ref.value"
                                    :value="ref.value"
                                >
                                    {{ ref.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Sort Options -->
                    <div class="space-y-2">
                        <Label for="sort-by">Sort By</Label>
                        <div class="flex space-x-2">
                            <Select v-model="localFilters.sort_by" class="flex-1">
                                <SelectTrigger id="sort-by">
                                    <SelectValue placeholder="Sort by..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="movement_date">Date</SelectItem>
                                    <SelectItem value="created_at">Created</SelectItem>
                                    <SelectItem value="type">Type</SelectItem>
                                    <SelectItem value="quantity_change">Quantity</SelectItem>
                                </SelectContent>
                            </Select>
                            <Select v-model="localFilters.sort_direction" class="w-24">
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="desc">↓</SelectItem>
                                    <SelectItem value="asc">↑</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                <!-- Filter Presets -->
                <div class="flex flex-wrap gap-2 pt-2">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="localFilters.date_from = new Date(Date.now() - 7*24*60*60*1000).toISOString().split('T')[0]"
                    >
                        Last 7 Days
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="localFilters.date_from = new Date(Date.now() - 30*24*60*60*1000).toISOString().split('T')[0]"
                    >
                        Last 30 Days
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="localFilters.date_from = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0]"
                    >
                        This Month
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="() => {
                            localFilters.movement_type = 'purchase';
                            localFilters.sort_by = 'movement_date';
                            localFilters.sort_direction = 'desc';
                        }"
                    >
                        Recent Purchases
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="() => {
                            localFilters.movement_type = 'sale';
                            localFilters.sort_by = 'movement_date';
                            localFilters.sort_direction = 'desc';
                        }"
                    >
                        Recent Sales
                    </Button>
                </div>
            </div>

            <!-- Active Filters Summary -->
            <div v-if="hasActiveFilters" class="pt-4 border-t">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-muted-foreground">
                        {{ Object.values(localFilters).filter(v => v && v !== '').length }} filter(s) applied
                    </span>
                    <Button
                        variant="ghost"
                        size="sm"
                        @click="clearFilters"
                    >
                        Clear All
                    </Button>
                </div>
            </div>
        </CardContent>
    </Card>
</template>