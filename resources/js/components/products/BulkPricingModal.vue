<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useAlerts } from '@/composables/useAlerts';
import { type Product } from '@/types';
import { Form } from '@inertiajs/vue3';
import { Calculator, DollarSign } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    open: boolean;
    products: Product[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'prices-updated': [];
}>();

const { addAlert } = useAlerts();

const selectedProducts = ref<number[]>([]);
const selectAll = ref(false);

const form = Form({
    product_ids: [] as number[],
    price_change_type: 'percentage' as 'percentage' | 'fixed_amount' | 'set_price',
    price_change_value: '',
    apply_to_variants: true,
    valid_from: new Date().toISOString().split('T')[0],
    valid_to: '',
    notes: '',
});

const priceChangeTypes = [
    { value: 'percentage', label: 'Percentage Change', description: 'Increase/decrease by percentage' },
    { value: 'fixed_amount', label: 'Fixed Amount', description: 'Add/subtract fixed amount' },
    { value: 'set_price', label: 'Set Price', description: 'Set specific price for all products' },
];

// Watch for modal open/close to reset form
watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            resetForm();
        }
    }
);

// Watch for select all checkbox
watch(selectAll, (value) => {
    if (value) {
        selectedProducts.value = props.products.map(p => p.id);
    } else {
        selectedProducts.value = [];
    }
});

// Watch for individual product selection
watch(selectedProducts, (selected) => {
    selectAll.value = selected.length === props.products.length;
});

const previewCalculations = computed(() => {
    if (!form.price_change_value || selectedProducts.value.length === 0) {
        return [];
    }

    const changeValue = parseFloat(form.price_change_value);
    if (isNaN(changeValue)) return [];

    return props.products
        .filter(p => selectedProducts.value.includes(p.id))
        .map(product => {
            const currentPrice = product.unit_price || 0;
            let newPrice = currentPrice;

            switch (form.price_change_type) {
                case 'percentage':
                    newPrice = currentPrice * (1 + changeValue / 100);
                    break;
                case 'fixed_amount':
                    newPrice = currentPrice + changeValue;
                    break;
                case 'set_price':
                    newPrice = changeValue;
                    break;
            }

            const difference = newPrice - currentPrice;
            const percentageChange = currentPrice > 0 ? (difference / currentPrice) * 100 : 0;

            return {
                product,
                currentPrice,
                newPrice: Math.max(0, newPrice),
                difference,
                percentageChange,
            };
        });
});

const totalProducts = computed(() => selectedProducts.value.length);
const averageIncrease = computed(() => {
    if (previewCalculations.value.length === 0) return 0;
    const totalIncrease = previewCalculations.value.reduce((sum, calc) => sum + calc.difference, 0);
    return totalIncrease / previewCalculations.value.length;
});

function resetForm() {
    selectedProducts.value = [];
    selectAll.value = false;
    form.product_ids = [];
    form.price_change_type = 'percentage';
    form.price_change_value = '';
    form.apply_to_variants = true;
    form.valid_from = new Date().toISOString().split('T')[0];
    form.valid_to = '';
    form.notes = '';
    form.clearErrors();
}

function toggleProduct(productId: number) {
    const index = selectedProducts.value.indexOf(productId);
    if (index > -1) {
        selectedProducts.value.splice(index, 1);
    } else {
        selectedProducts.value.push(productId);
    }
}

function handleSubmit() {
    form.product_ids = selectedProducts.value;

    form.post('/products/bulk-update-prices', {
        preserveScroll: true,
        onSuccess: () => {
            addAlert(
                `Successfully updated prices for ${totalProducts.value} products`,
                'success',
                { title: 'Bulk Price Update Complete' }
            );
            emit('update:open', false);
            emit('prices-updated');
        },
        onError: (errors) => {
            const errorMessage = Object.values(errors).flat().join(', ');
            addAlert(
                errorMessage || 'Failed to update prices',
                'error',
                { title: 'Bulk Update Failed' }
            );
        },
    });
}

function handleCancel() {
    emit('update:open', false);
}

const getChangeDescription = () => {
    switch (form.price_change_type) {
        case 'percentage':
            return `${form.price_change_value}% ${parseFloat(form.price_change_value) >= 0 ? 'increase' : 'decrease'}`;
        case 'fixed_amount':
            return `GH₵${Math.abs(parseFloat(form.price_change_value)).toFixed(2)} ${parseFloat(form.price_change_value) >= 0 ? 'increase' : 'decrease'}`;
        case 'set_price':
            return `Set to GH₵${parseFloat(form.price_change_value).toFixed(2)}`;
        default:
            return '';
    }
};

const formatCurrency = (amount: number) => `GH₵${amount.toFixed(2)}`;
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="max-w-6xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <DollarSign class="h-5 w-5" />
                    Bulk Price Update
                </DialogTitle>
                <DialogDescription>
                    Update prices for multiple products at once. Changes will create new price versions.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Product Selection -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium">Select Products</h3>
                        <div class="flex items-center space-x-2">
                            <Checkbox
                                id="select-all"
                                v-model:checked="selectAll"
                            />
                            <Label for="select-all">Select All ({{ products.length }} products)</Label>
                        </div>
                    </div>

                    <div class="border rounded-lg max-h-60 overflow-y-auto">
                        <div class="grid grid-cols-1 divide-y">
                            <div
                                v-for="product in products"
                                :key="product.id"
                                class="flex items-center space-x-3 p-3 hover:bg-gray-50 dark:hover:bg-gray-800/50"
                            >
                                <Checkbox
                                    :id="`product-${product.id}`"
                                    :checked="selectedProducts.includes(product.id)"
                                    @update:checked="toggleProduct(product.id)"
                                />
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium">{{ product.name }}</div>
                                    <div class="text-sm text-gray-500">
                                        {{ product.sku }} • {{ formatCurrency(product.unit_price || 0) }}
                                    </div>
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ product.category?.name }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        {{ selectedProducts.length }} products selected
                    </div>
                </div>

                <!-- Price Change Configuration -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium">Price Change</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="price_change_type">Change Type</Label>
                            <Select v-model="form.price_change_type">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select change type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="type in priceChangeTypes" :key="type.value" :value="type.value">
                                        <div>
                                            <div>{{ type.label }}</div>
                                            <div class="text-xs text-gray-500">{{ type.description }}</div>
                                        </div>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <div v-if="form.errors.price_change_type" class="text-sm text-red-600">
                                {{ form.errors.price_change_type }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="price_change_value">
                                {{ form.price_change_type === 'percentage' ? 'Percentage (%)' :
                                   form.price_change_type === 'fixed_amount' ? 'Amount (GH₵)' :
                                   'New Price (GH₵)' }}
                            </Label>
                            <Input
                                id="price_change_value"
                                v-model="form.price_change_value"
                                type="number"
                                step="0.01"
                                :placeholder="form.price_change_type === 'percentage' ? '10' : '5.00'"
                                :error="form.errors.price_change_value"
                            />
                            <div v-if="form.price_change_value" class="text-sm text-gray-600">
                                {{ getChangeDescription() }}
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2">
                        <Checkbox
                            id="apply_to_variants"
                            v-model:checked="form.apply_to_variants"
                        />
                        <Label for="apply_to_variants">Also apply to product variants</Label>
                    </div>
                </div>

                <!-- Effective Dates -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium">Effective Dates</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="valid_from">Effective From</Label>
                            <Input
                                id="valid_from"
                                v-model="form.valid_from"
                                type="date"
                                :error="form.errors.valid_from"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="valid_to">Effective Until (Optional)</Label>
                            <Input
                                id="valid_to"
                                v-model="form.valid_to"
                                type="date"
                                :error="form.errors.valid_to"
                            />
                            <p class="text-sm text-gray-500">Leave empty for permanent change</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="notes">Notes (Optional)</Label>
                        <Textarea
                            id="notes"
                            v-model="form.notes"
                            placeholder="Add notes about this price change..."
                            :error="form.errors.notes"
                        />
                    </div>
                </div>

                <!-- Preview -->
                <div v-if="previewCalculations.length > 0" class="space-y-4">
                    <h3 class="text-lg font-medium flex items-center gap-2">
                        <Calculator class="h-5 w-5" />
                        Preview Changes
                    </h3>

                    <!-- Summary -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-blue-50 dark:bg-blue-900/10 rounded-lg">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                {{ totalProducts }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Products Affected</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                {{ formatCurrency(averageIncrease) }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Average Change</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                {{ new Date(form.valid_from).toLocaleDateString() }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Effective Date</div>
                        </div>
                    </div>

                    <!-- Detailed Preview -->
                    <div class="border rounded-lg max-h-60 overflow-y-auto">
                        <div class="grid grid-cols-1 divide-y">
                            <div class="grid grid-cols-4 gap-4 p-3 bg-gray-50 dark:bg-gray-800/50 font-medium text-sm">
                                <div>Product</div>
                                <div>Current Price</div>
                                <div>New Price</div>
                                <div>Change</div>
                            </div>
                            <div
                                v-for="calc in previewCalculations.slice(0, 10)"
                                :key="calc.product.id"
                                class="grid grid-cols-4 gap-4 p-3 text-sm"
                            >
                                <div>
                                    <div class="font-medium">{{ calc.product.name }}</div>
                                    <div class="text-gray-500">{{ calc.product.sku }}</div>
                                </div>
                                <div>{{ formatCurrency(calc.currentPrice) }}</div>
                                <div class="font-medium">{{ formatCurrency(calc.newPrice) }}</div>
                                <div :class="{
                                    'text-green-600 dark:text-green-400': calc.difference > 0,
                                    'text-red-600 dark:text-red-400': calc.difference < 0,
                                    'text-gray-600 dark:text-gray-400': calc.difference === 0
                                }">
                                    {{ calc.difference >= 0 ? '+' : '' }}{{ formatCurrency(calc.difference) }}
                                    ({{ calc.percentageChange >= 0 ? '+' : '' }}{{ calc.percentageChange.toFixed(1) }}%)
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="previewCalculations.length > 10" class="text-sm text-gray-600 dark:text-gray-400 text-center">
                        Showing first 10 products. {{ previewCalculations.length - 10 }} more products will be affected.
                    </div>
                </div>
            </form>

            <DialogFooter>
                <Button type="button" variant="outline" @click="handleCancel">
                    Cancel
                </Button>
                <Button
                    type="button"
                    @click="handleSubmit"
                    :disabled="form.processing || selectedProducts.length === 0 || !form.price_change_value"
                >
                    {{ form.processing ? 'Updating...' : `Update ${totalProducts} Products` }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>