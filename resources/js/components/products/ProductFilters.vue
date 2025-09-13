<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import Switch from '@/components/ui/switch.vue';
import { type ProductFilters } from '@/types';
import { Filter, Search, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    filters: ProductFilters;
    categories: Array<{ value: number; label: string }>;
    unitTypes: Array<{ value: string; label: string }>;
    brands: Array<{ value: string; label: string }>;
}

interface Emits {
    'update:filters': [filters: ProductFilters];
    clear: [];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const showAdvancedFilters = ref(false);

// Computed property to check if any filters are applied
const hasActiveFilters = computed(() => {
    return (
        props.filters.search !== '' ||
        props.filters.category_id !== '' ||
        props.filters.status !== '' ||
        props.filters.price_from !== '' ||
        props.filters.price_to !== '' ||
        props.filters.low_stock !== '' ||
        props.filters.unit_type !== '' ||
        props.filters.brand !== ''
    );
});

// Status options
const statusOptions = [
    { value: '', label: 'All Statuses' },
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
    { value: 'discontinued', label: 'Discontinued' },
];

// Update filters function
const updateFilters = (newFilters: Partial<ProductFilters>) => {
    emit('update:filters', { ...props.filters, ...newFilters });
};

// Clear all filters
const clearAllFilters = () => {
    emit('clear');
    showAdvancedFilters.value = false;
};

// Handle input changes
const handleSearchChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    updateFilters({ search: target.value });
};

const handleCategoryChange = (value: string) => {
    updateFilters({ category_id: value });
};

const handleStatusChange = (value: string) => {
    updateFilters({ status: value });
};

const handleUnitTypeChange = (value: string) => {
    updateFilters({ unit_type: value });
};

const handleBrandChange = (value: string) => {
    updateFilters({ brand: value });
};

const handlePriceFromChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    updateFilters({ price_from: target.value });
};

const handlePriceToChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    updateFilters({ price_to: target.value });
};

const handleLowStockChange = (checked: boolean) => {
    updateFilters({ low_stock: checked ? 'true' : '' });
};
</script>

<template>
    <Card>
        <CardHeader class="pb-4">
            <div class="flex items-center justify-between">
                <CardTitle class="flex items-center gap-2 text-base">
                    <Filter class="h-4 w-4" />
                    Filters
                </CardTitle>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" @click="showAdvancedFilters = !showAdvancedFilters">
                        {{ showAdvancedFilters ? 'Hide' : 'Show' }} Advanced
                    </Button>
                    <Button variant="outline" size="sm" @click="clearAllFilters" :disabled="!hasActiveFilters" class="flex items-center gap-1">
                        <X class="h-3 w-3" />
                        Clear
                    </Button>
                </div>
            </div>
        </CardHeader>
        <CardContent class="space-y-4">
            <!-- Basic Filters Row -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <!-- Search -->
                <div class="space-y-2">
                    <Label for="search">Search Products</Label>
                    <div class="relative">
                        <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            id="search"
                            :value="filters.search"
                            @input="handleSearchChange"
                            placeholder="Search by name, SKU, or barcode..."
                            class="pl-10"
                        />
                    </div>
                </div>

                <!-- Category Filter -->
                <div class="space-y-2">
                    <Label for="category">Category</Label>
                    <Select :value="filters.category_id?.toString()" @update:model-value="handleCategoryChange">
                        <SelectTrigger>
                            <SelectValue placeholder="All Categories" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Categories</SelectItem>
                            <SelectItem v-for="category in categories" :key="category.value" :value="category.value.toString()">
                                {{ category.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Status Filter -->
                <div class="space-y-2">
                    <Label for="status">Status</Label>
                    <Select :value="filters.status" @update:model-value="handleStatusChange">
                        <SelectTrigger>
                            <SelectValue placeholder="All Statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="status in statusOptions" :key="status.value" :value="status.value">
                                {{ status.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Low Stock Toggle -->
                <div class="space-y-2">
                    <Label for="low-stock">Filters</Label>
                    <div class="flex items-center space-x-2">
                        <Switch id="low-stock" :checked="filters.low_stock === 'true'" @update:checked="handleLowStockChange" />
                        <Label for="low-stock" class="text-sm">Low Stock Only</Label>
                    </div>
                </div>
            </div>

            <!-- Advanced Filters -->
            <div v-if="showAdvancedFilters" class="space-y-4 border-t pt-4">
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <!-- Price Range -->
                    <div class="space-y-2 md:col-span-2">
                        <Label>Price Range (GHS)</Label>
                        <div class="flex items-center gap-2">
                            <div class="flex-1">
                                <Input
                                    :value="filters.price_from"
                                    @input="handlePriceFromChange"
                                    placeholder="Min price (GHS)"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                />
                            </div>
                            <span class="text-muted-foreground">to</span>
                            <div class="flex-1">
                                <Input
                                    :value="filters.price_to"
                                    @input="handlePriceToChange"
                                    placeholder="Max price (GHS)"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Unit Type Filter -->
                    <div class="space-y-2">
                        <Label for="unit-type">Unit Type</Label>
                        <Select :value="filters.unit_type" @update:model-value="handleUnitTypeChange">
                            <SelectTrigger>
                                <SelectValue placeholder="All Unit Types" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All Unit Types</SelectItem>
                                <SelectItem v-for="unitType in unitTypes" :key="unitType.value" :value="unitType.value">
                                    {{ unitType.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <!-- Brand Filter -->
                    <div class="space-y-2">
                        <Label for="brand">Brand</Label>
                        <Select :value="filters.brand" @update:model-value="handleBrandChange">
                            <SelectTrigger>
                                <SelectValue placeholder="All Brands" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All Brands</SelectItem>
                                <SelectItem v-for="brand in brands" :key="brand.value" :value="brand.value">
                                    {{ brand.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </div>

            <!-- Active Filters Indicator -->
            <div v-if="hasActiveFilters" class="flex items-center gap-2 text-sm text-muted-foreground">
                <span class="font-medium">Active filters:</span>
                <div class="flex flex-wrap gap-1">
                    <span v-if="filters.search" class="rounded-full bg-blue-100 px-2 py-1 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        Search: "{{ filters.search }}"
                    </span>
                    <span v-if="filters.category_id" class="rounded-full bg-green-100 px-2 py-1 text-green-800 dark:bg-green-900 dark:text-green-200">
                        Category: {{ categories.find((c) => c.value.toString() === filters.category_id?.toString())?.label }}
                    </span>
                    <span v-if="filters.status" class="rounded-full bg-purple-100 px-2 py-1 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                        Status: {{ statusOptions.find((s) => s.value === filters.status)?.label }}
                    </span>
                    <span
                        v-if="filters.low_stock === 'true'"
                        class="rounded-full bg-orange-100 px-2 py-1 text-orange-800 dark:bg-orange-900 dark:text-orange-200"
                    >
                        Low Stock
                    </span>
                    <span
                        v-if="filters.price_from || filters.price_to"
                        class="rounded-full bg-indigo-100 px-2 py-1 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200"
                    >
                        Price: {{ filters.price_from || '0' }} - {{ filters.price_to || '∞' }}
                    </span>
                    <span v-if="filters.unit_type" class="rounded-full bg-teal-100 px-2 py-1 text-teal-800 dark:bg-teal-900 dark:text-teal-200">
                        Unit: {{ unitTypes.find((u) => u.value === filters.unit_type)?.label }}
                    </span>
                    <span v-if="filters.brand" class="rounded-full bg-pink-100 px-2 py-1 text-pink-800 dark:bg-pink-900 dark:text-pink-200">
                        Brand: {{ brands.find((b) => b.value === filters.brand)?.label }}
                    </span>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
