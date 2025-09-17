<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useAlerts } from '@/composables/useAlerts';
import { type Product } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { Calculator, Package } from 'lucide-vue-next';
import { computed, watch } from 'vue';

interface Props {
    open: boolean;
    product: Product;
    currentStock: number;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'stock-adjusted': [];
}>();

const { addAlert } = useAlerts();

const form = useForm({
    adjustment_type: 'increase' as 'increase' | 'decrease' | 'set',
    quantity: 0,
    reason: '',
    notes: '',
});

const adjustmentTypes = [
    { value: 'increase', label: 'Add Stock', description: 'Increase inventory (e.g., new stock, returns)' },
    { value: 'decrease', label: 'Remove Stock', description: 'Decrease inventory (e.g., damage, theft, sales)' },
    { value: 'set', label: 'Set Exact Quantity', description: 'Set inventory to a specific amount' },
];

const adjustmentReasons = [
    { value: 'Physical Stock Count', label: 'Physical Stock Count' },
    { value: 'Damaged Goods', label: 'Damaged Goods' },
    { value: 'Theft/Loss', label: 'Theft/Loss' },
    { value: 'Expired Products', label: 'Expired Products' },
    { value: 'Customer Return', label: 'Customer Return' },
    { value: 'Found Stock', label: 'Found Stock' },
    { value: 'System Correction', label: 'System Correction' },
    { value: 'Other', label: 'Other' },
];

const currentStock = computed(() => {
    return props.currentStock;
});

const calculatedNewStock = computed(() => {
    const current = currentStock.value;
    const quantity = form.quantity || 0;

    switch (form.adjustment_type) {
        case 'increase':
            return current + quantity;
        case 'decrease':
            return Math.max(0, current - quantity);
        case 'set':
            return quantity;
        default:
            return current;
    }
});

const stockDifference = computed(() => {
    return calculatedNewStock.value - currentStock.value;
});

const isValidAdjustment = computed(() => {
    return form.quantity > 0 && form.reason.trim() !== '';
});

// Watch for product prop changes to populate form
watch(
    () => props.product,
    (newProduct) => {
        if (newProduct) {
            // Product is already available
        }
    },
    { immediate: true },
);

// Watch for open prop changes to reset form when modal opens
watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            resetForm();
        }
    },
);

function resetForm() {
    form.adjustment_type = 'increase';
    form.quantity = 0;
    form.reason = 'Physical Stock Count';
    form.notes = '';
    form.clearErrors();
}

function handleAdjustmentTypeChange() {
    // Reset quantity field when adjustment type changes
    form.quantity = 0;
}

function handleSubmit() {
    const url = `/products/${props.product.id}/stock-adjustment`;

    form.post(url, {
        preserveScroll: true,
        onSuccess: () => {
            addAlert('Stock adjustment completed successfully', 'success', { title: 'Stock Updated' });
            emit('update:open', false);
            emit('stock-adjusted');
        },
        onError: (errors) => {
            const errorMessage = Object.values(errors).flat().join(', ');
            addAlert(errorMessage || 'Failed to adjust stock', 'destructive', { title: 'Adjustment Failed' });
        },
    });
}

function handleCancel() {
    emit('update:open', false);
}

const productDisplayName = computed(() => {
    return props.product?.name || 'Unknown Product';
});

const productSku = computed(() => {
    return props.product?.sku || '';
});
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <Package class="h-5 w-5" />
                    Stock Adjustment
                </DialogTitle>
                <DialogDescription> Adjust inventory levels for {{ productDisplayName }} </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Product Information -->
                <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-900/50">
                    <div class="flex items-center space-x-3">
                        <div v-if="product?.image_url" class="h-12 w-12 flex-shrink-0">
                            <img :src="product.image_url" :alt="productDisplayName" class="h-12 w-12 rounded object-cover" />
                        </div>
                        <div v-else class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded bg-gray-200 dark:bg-gray-700">
                            <Package class="h-6 w-6 text-gray-400" />
                        </div>
                        <div>
                            <div class="font-medium">{{ productDisplayName }}</div>
                            <div class="text-sm text-gray-500">{{ productSku }}</div>
                            <div class="text-sm font-medium text-blue-600 dark:text-blue-400">Current Stock: {{ currentStock }} units</div>
                        </div>
                    </div>
                </div>

                <!-- Adjustment Type -->
                <div class="space-y-2">
                    <Label for="adjustment_type">Adjustment Type</Label>
                    <Select v-model="form.adjustment_type" @update:model-value="handleAdjustmentTypeChange">
                        <SelectTrigger>
                            <SelectValue placeholder="Select adjustment type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="type in adjustmentTypes" :key="type.value" :value="type.value">
                                <div>
                                    <div>{{ type.label }}</div>
                                    <div class="text-xs text-gray-500">{{ type.description }}</div>
                                </div>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <div v-if="form.errors.adjustment_type" class="text-sm text-red-600">
                        {{ form.errors.adjustment_type }}
                    </div>
                </div>

                <!-- Quantity Input -->
                <div class="space-y-2">
                    <Label for="quantity">
                        {{
                            form.adjustment_type === 'increase'
                                ? 'Quantity to Add'
                                : form.adjustment_type === 'decrease'
                                  ? 'Quantity to Remove'
                                  : 'New Stock Quantity'
                        }}
                    </Label>
                    <Input id="quantity" v-model="form.quantity" type="number" :min="0" step="1" placeholder="0" :error="form.errors.quantity" />
                    <div v-if="form.errors.quantity" class="text-sm text-red-600">
                        {{ form.errors.quantity }}
                    </div>
                </div>

                <!-- Reason -->
                <div class="space-y-2">
                    <Label for="reason">Reason</Label>
                    <Select v-model="form.reason">
                        <SelectTrigger>
                            <SelectValue placeholder="Select reason" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="reason in adjustmentReasons" :key="reason.value" :value="reason.value">
                                {{ reason.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <div v-if="form.errors.reason" class="text-sm text-red-600">
                        {{ form.errors.reason }}
                    </div>
                </div>

                <!-- Notes -->
                <div class="space-y-2">
                    <Label for="notes">Notes (Optional)</Label>
                    <Textarea id="notes" v-model="form.notes" placeholder="Add notes about this adjustment..." :error="form.errors.notes" />
                </div>

                <!-- Calculation Preview -->
                <div v-if="isValidAdjustment" class="rounded-lg bg-blue-50 p-4 dark:bg-blue-900/10">
                    <div class="mb-3 flex items-center gap-2">
                        <Calculator class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        <span class="font-medium text-blue-600 dark:text-blue-400">Adjustment Preview</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4 text-sm md:grid-cols-4">
                        <div>
                            <div class="text-gray-600 dark:text-gray-400">Current Stock</div>
                            <div class="font-medium">{{ currentStock }} units</div>
                        </div>
                        <div>
                            <div class="text-gray-600 dark:text-gray-400">New Stock</div>
                            <div class="font-medium">{{ calculatedNewStock }} units</div>
                        </div>
                        <div>
                            <div class="text-gray-600 dark:text-gray-400">Difference</div>
                            <div
                                :class="[
                                    'font-medium',
                                    stockDifference > 0
                                        ? 'text-green-600 dark:text-green-400'
                                        : stockDifference < 0
                                          ? 'text-red-600 dark:text-red-400'
                                          : 'text-gray-600 dark:text-gray-400',
                                ]"
                            >
                                {{ stockDifference > 0 ? '+' : '' }}{{ stockDifference }} units
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <DialogFooter>
                <Button type="button" variant="outline" @click="handleCancel"> Cancel </Button>
                <Button type="button" @click="handleSubmit" :disabled="form.processing || !isValidAdjustment">
                    {{ form.processing ? 'Processing...' : 'Apply Adjustment' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
