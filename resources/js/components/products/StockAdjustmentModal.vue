<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useAlerts } from '@/composables/useAlerts';
import { type Product, type ProductInventory, type ProductVariant } from '@/types';
import { Form } from '@inertiajs/vue3';
import { Calculator, Package } from 'lucide-vue-next';
import { computed, watch } from 'vue';

interface Props {
    open: boolean;
    product?: Product | null;
    variant?: ProductVariant | null;
    inventory?: ProductInventory | null;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'adjustment-created': [];
}>();

const { addAlert } = useAlerts();

const form = Form({
    product_id: 0,
    product_variant_id: null as number | null,
    movement_type: 'adjustment' as 'in' | 'out' | 'adjustment',
    quantity: '',
    unit_cost: '',
    movement_date: new Date().toISOString().split('T')[0],
    reference_number: '',
    notes: '',
    new_quantity: '',
    adjustment_reason: '',
});

const movementTypes = [
    { value: 'in', label: 'Add Stock', description: 'Increase inventory (e.g., new stock, returns)' },
    { value: 'out', label: 'Remove Stock', description: 'Decrease inventory (e.g., damage, theft, sales)' },
    { value: 'adjustment', label: 'Set Exact Quantity', description: 'Set inventory to a specific amount' },
];

const adjustmentReasons = [
    { value: 'stock_count', label: 'Physical Stock Count' },
    { value: 'damage', label: 'Damaged Goods' },
    { value: 'theft', label: 'Theft/Loss' },
    { value: 'expired', label: 'Expired Products' },
    { value: 'return', label: 'Customer Return' },
    { value: 'found', label: 'Found Stock' },
    { value: 'correction', label: 'System Correction' },
    { value: 'other', label: 'Other' },
];

const currentStock = computed(() => {
    return props.inventory?.quantity_on_hand || 0;
});

const calculatedNewStock = computed(() => {
    const current = currentStock.value;
    const quantity = parseInt(form.quantity) || 0;

    switch (form.movement_type) {
        case 'in':
            return current + quantity;
        case 'out':
            return Math.max(0, current - quantity);
        case 'adjustment':
            return parseInt(form.new_quantity) || 0;
        default:
            return current;
    }
});

const stockDifference = computed(() => {
    return calculatedNewStock.value - currentStock.value;
});

const isValidAdjustment = computed(() => {
    if (form.movement_type === 'adjustment') {
        return form.new_quantity !== '' && parseInt(form.new_quantity) >= 0;
    }
    return form.quantity !== '' && parseInt(form.quantity) > 0;
});

// Watch for variant prop changes to populate form
watch(
    () => [props.product, props.variant],
    ([newProduct, newVariant]) => {
        if (newProduct) {
            form.product_id = newProduct.id;
            form.product_variant_id = newVariant?.id || null;
        }
    },
    { immediate: true }
);

// Watch for open prop changes to reset form when modal opens
watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            resetForm();
        }
    }
);

function resetForm() {
    form.movement_type = 'adjustment';
    form.quantity = '';
    form.unit_cost = '';
    form.movement_date = new Date().toISOString().split('T')[0];
    form.reference_number = '';
    form.notes = '';
    form.new_quantity = currentStock.value.toString();
    form.adjustment_reason = 'stock_count';
    form.clearErrors();
}

function handleMovementTypeChange() {
    // Reset quantity fields when movement type changes
    form.quantity = '';
    form.new_quantity = currentStock.value.toString();
}

function handleSubmit() {
    // Set the actual quantity change based on movement type
    if (form.movement_type === 'adjustment') {
        const newQty = parseInt(form.new_quantity) || 0;
        form.quantity = (newQty - currentStock.value).toString();
    } else if (form.movement_type === 'out') {
        // Make sure out movements are negative
        const qty = Math.abs(parseInt(form.quantity) || 0);
        form.quantity = (-qty).toString();
    }

    const url = '/stock-movements';

    form.post(url, {
        preserveScroll: true,
        onSuccess: () => {
            addAlert(
                'Stock adjustment completed successfully',
                'success',
                { title: 'Stock Updated' }
            );
            emit('update:open', false);
            emit('adjustment-created');
        },
        onError: (errors) => {
            const errorMessage = Object.values(errors).flat().join(', ');
            addAlert(
                errorMessage || 'Failed to adjust stock',
                'error',
                { title: 'Adjustment Failed' }
            );
        },
    });
}

function handleCancel() {
    emit('update:open', false);
}

const productDisplayName = computed(() => {
    if (props.variant) {
        return props.variant.display_name || `${props.product?.name} - ${props.variant.name}`;
    }
    return props.product?.name || 'Unknown Product';
});

const productSku = computed(() => {
    return props.variant?.sku || props.product?.sku || '';
});

const totalCost = computed(() => {
    const qty = Math.abs(parseInt(form.quantity) || 0);
    const cost = parseFloat(form.unit_cost) || 0;
    return qty * cost;
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
                <DialogDescription>
                    Adjust inventory levels for {{ productDisplayName }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Product Information -->
                <div class="p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div v-if="product?.image_url" class="h-12 w-12 flex-shrink-0">
                            <img
                                :src="product.image_url"
                                :alt="productDisplayName"
                                class="h-12 w-12 rounded object-cover"
                            />
                        </div>
                        <div v-else class="h-12 w-12 flex-shrink-0 bg-gray-200 dark:bg-gray-700 rounded flex items-center justify-center">
                            <Package class="h-6 w-6 text-gray-400" />
                        </div>
                        <div>
                            <div class="font-medium">{{ productDisplayName }}</div>
                            <div class="text-sm text-gray-500">{{ productSku }}</div>
                            <div class="text-sm font-medium text-blue-600 dark:text-blue-400">
                                Current Stock: {{ currentStock }} units
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Adjustment Type -->
                <div class="space-y-2">
                    <Label for="movement_type">Adjustment Type</Label>
                    <Select v-model="form.movement_type" @update:model-value="handleMovementTypeChange">
                        <SelectTrigger>
                            <SelectValue placeholder="Select adjustment type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="type in movementTypes" :key="type.value" :value="type.value">
                                <div>
                                    <div>{{ type.label }}</div>
                                    <div class="text-xs text-gray-500">{{ type.description }}</div>
                                </div>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <div v-if="form.errors.movement_type" class="text-sm text-red-600">
                        {{ form.errors.movement_type }}
                    </div>
                </div>

                <!-- Quantity Input -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-if="form.movement_type !== 'adjustment'" class="space-y-2">
                        <Label for="quantity">
                            {{ form.movement_type === 'in' ? 'Quantity to Add' : 'Quantity to Remove' }}
                        </Label>
                        <Input
                            id="quantity"
                            v-model="form.quantity"
                            type="number"
                            min="1"
                            step="1"
                            placeholder="0"
                            :error="form.errors.quantity"
                        />
                    </div>

                    <div v-if="form.movement_type === 'adjustment'" class="space-y-2">
                        <Label for="new_quantity">New Stock Quantity</Label>
                        <Input
                            id="new_quantity"
                            v-model="form.new_quantity"
                            type="number"
                            min="0"
                            step="1"
                            placeholder="0"
                            :error="form.errors.new_quantity"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="unit_cost">Unit Cost (Optional)</Label>
                        <Input
                            id="unit_cost"
                            v-model="form.unit_cost"
                            type="number"
                            step="0.01"
                            placeholder="0.00"
                            :error="form.errors.unit_cost"
                        />
                    </div>
                </div>

                <!-- Reason and Date -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="adjustment_reason">Reason</Label>
                        <Select v-model="form.adjustment_reason">
                            <SelectTrigger>
                                <SelectValue placeholder="Select reason" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="reason in adjustmentReasons" :key="reason.value" :value="reason.value">
                                    {{ reason.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-2">
                        <Label for="movement_date">Adjustment Date</Label>
                        <Input
                            id="movement_date"
                            v-model="form.movement_date"
                            type="date"
                            :error="form.errors.movement_date"
                        />
                    </div>
                </div>

                <!-- Reference and Notes -->
                <div class="space-y-4">
                    <div class="space-y-2">
                        <Label for="reference_number">Reference Number (Optional)</Label>
                        <Input
                            id="reference_number"
                            v-model="form.reference_number"
                            placeholder="e.g., COUNT-2024-001"
                            :error="form.errors.reference_number"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="notes">Notes (Optional)</Label>
                        <Textarea
                            id="notes"
                            v-model="form.notes"
                            placeholder="Add notes about this adjustment..."
                            :error="form.errors.notes"
                        />
                    </div>
                </div>

                <!-- Calculation Preview -->
                <div v-if="isValidAdjustment" class="p-4 bg-blue-50 dark:bg-blue-900/10 rounded-lg">
                    <div class="flex items-center gap-2 mb-3">
                        <Calculator class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        <span class="font-medium text-blue-600 dark:text-blue-400">Adjustment Preview</span>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
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
                            <div :class="[
                                'font-medium',
                                stockDifference > 0 ? 'text-green-600 dark:text-green-400' :
                                stockDifference < 0 ? 'text-red-600 dark:text-red-400' :
                                'text-gray-600 dark:text-gray-400'
                            ]">
                                {{ stockDifference > 0 ? '+' : '' }}{{ stockDifference }} units
                            </div>
                        </div>
                        <div v-if="totalCost > 0">
                            <div class="text-gray-600 dark:text-gray-400">Total Cost</div>
                            <div class="font-medium">GH₵{{ totalCost.toFixed(2) }}</div>
                        </div>
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
                    :disabled="form.processing || !isValidAdjustment"
                >
                    {{ form.processing ? 'Processing...' : 'Apply Adjustment' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>