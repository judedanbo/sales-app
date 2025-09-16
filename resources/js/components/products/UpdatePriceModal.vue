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
import { Calculator, DollarSign, Plus, Trash2, TrendingUp } from 'lucide-vue-next';
import { computed, watch } from 'vue';

interface Props {
    open: boolean;
    product: Product;
}

interface BulkDiscountTier {
    min_quantity: number;
    discount_percentage: number;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'price-created': [];
}>();

const { addAlert } = useAlerts();

const form = useForm({
    price: props.product.unit_price || 0,
    cost_price: 0,
    markup_percentage: 0,
    valid_from: new Date().toISOString().split('T')[0],
    valid_to: '',
    currency: 'GHS',
    notes: '',
    bulk_discounts: [] as BulkDiscountTier[],
});

const currencies = [
    { code: 'GHS', name: 'Ghanaian Cedi' },
    { code: 'USD', name: 'US Dollar' },
    { code: 'EUR', name: 'Euro' },
    { code: 'GBP', name: 'British Pound' },
];

// Computed properties
const profitMargin = computed(() => {
    if (!form.cost_price || form.cost_price === 0) return 0;
    return ((form.price - form.cost_price) / form.cost_price) * 100;
});

const priceChange = computed(() => {
    const change = form.price - props.product.unit_price;
    const percentage = props.product.unit_price ? (change / props.product.unit_price) * 100 : 0;
    return {
        amount: change,
        percentage,
        isIncrease: change > 0,
    };
});

const canSubmit = computed(() => {
    return form.price > 0 && form.valid_from && !form.processing;
});

// Auto-calculate markup percentage when prices change
watch([() => form.price, () => form.cost_price], () => {
    if (form.cost_price && form.cost_price > 0) {
        form.markup_percentage = Math.round(((form.price - form.cost_price) / form.cost_price) * 100 * 100) / 100;
    }
});

// Watch for modal open/close to reset form
watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            form.reset();
            form.price = props.product.unit_price || 0;
        }
    },
);

// Bulk discount management
const addBulkDiscountTier = () => {
    form.bulk_discounts.push({
        min_quantity: 1,
        discount_percentage: 0,
    });
};

const removeBulkDiscountTier = (index: number) => {
    form.bulk_discounts.splice(index, 1);
};

// Form submission
const handleSubmit = () => {
    // Sort bulk discounts by min_quantity
    form.bulk_discounts.sort((a, b) => a.min_quantity - b.min_quantity);

    form.post(`/products/${props.product.id}/prices`, {
        onSuccess: () => {
            addAlert('Price update request submitted for approval', 'success', {
                title: 'Request Submitted',
            });
            emit('price-created');
            emit('update:open', false);
        },
        onError: (errors) => {
            console.error('Price update failed:', errors);
            addAlert('Failed to submit price update request', 'destructive', {
                title: 'Submission Failed',
            });
        },
    });
};

// Format currency
const formatCurrency = (amount: number): string => {
    return new Intl.NumberFormat('en-GH', {
        style: 'currency',
        currency: form.currency,
    }).format(amount);
};
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="max-h-[90vh] max-w-2xl overflow-y-auto">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <DollarSign class="h-5 w-5" />
                    Update Price - {{ product.name }}
                </DialogTitle>
                <DialogDescription>
                    Create a new price version for {{ product.name }}. This request will require approval before becoming active.
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="handleSubmit" class="space-y-6">
                <!-- Price Information -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold">Price Information</h3>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="price" class="required">New Price</Label>
                            <Input
                                id="price"
                                v-model.number="form.price"
                                type="number"
                                step="0.01"
                                min="0"
                                required
                                :class="{ 'border-red-500': form.errors.price }"
                            />
                            <p v-if="form.errors.price" class="text-sm text-red-600">{{ form.errors.price }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="cost_price">Cost Price</Label>
                            <Input
                                id="cost_price"
                                v-model.number="form.cost_price"
                                type="number"
                                step="0.01"
                                min="0"
                                :class="{ 'border-red-500': form.errors.cost_price }"
                            />
                            <p v-if="form.errors.cost_price" class="text-sm text-red-600">{{ form.errors.cost_price }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="markup_percentage">Markup %</Label>
                            <div class="relative">
                                <Input
                                    id="markup_percentage"
                                    v-model.number="form.markup_percentage"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    readonly
                                    class="bg-gray-50"
                                />
                                <Calculator class="absolute top-2.5 right-3 h-4 w-4 text-gray-400" />
                            </div>
                            <p class="text-xs text-gray-500">Auto-calculated from price and cost</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="currency">Currency</Label>
                            <Select v-model="form.currency">
                                <SelectTrigger id="currency">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="currency in currencies" :key="currency.code" :value="currency.code">
                                        {{ currency.code }} - {{ currency.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Price Change Preview -->
                    <div v-if="form.price !== product.unit_price" class="rounded-lg border bg-gray-50 p-4">
                        <div class="mb-2 flex items-center gap-2">
                            <TrendingUp :class="priceChange.isIncrease ? 'text-green-600' : 'rotate-180 text-red-600'" class="h-4 w-4" />
                            <span class="font-medium">Price Change Preview</span>
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-gray-500">Current:</span>
                                <span class="line-through">{{ formatCurrency(product.unit_price) }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-gray-500">New:</span>
                                <span class="font-bold">{{ formatCurrency(form.price) }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-gray-500">Change:</span>
                                <span :class="priceChange.isIncrease ? 'text-green-600' : 'text-red-600'" class="font-medium">
                                    {{ priceChange.isIncrease ? '+' : '' }}{{ formatCurrency(priceChange.amount) }} ({{
                                        priceChange.percentage.toFixed(1)
                                    }}%)
                                </span>
                            </div>
                            <div v-if="profitMargin > 0" class="flex items-center gap-2">
                                <span class="text-sm text-gray-500">Profit Margin:</span>
                                <span class="font-medium text-blue-600">{{ profitMargin.toFixed(1) }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Validity Period -->
                <div class="space-y-4">
                    <h3 class="text-lg font-semibold">Validity Period</h3>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="valid_from" class="required">Valid From</Label>
                            <Input
                                id="valid_from"
                                v-model="form.valid_from"
                                type="date"
                                required
                                :class="{ 'border-red-500': form.errors.valid_from }"
                            />
                            <p v-if="form.errors.valid_from" class="text-sm text-red-600">{{ form.errors.valid_from }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="valid_to">Valid To (Optional)</Label>
                            <Input id="valid_to" v-model="form.valid_to" type="date" :class="{ 'border-red-500': form.errors.valid_to }" />
                            <p v-if="form.errors.valid_to" class="text-sm text-red-600">{{ form.errors.valid_to }}</p>
                        </div>
                    </div>
                </div>

                <!-- Bulk Discounts -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold">Bulk Discounts (Optional)</h3>
                        <Button type="button" variant="outline" size="sm" @click="addBulkDiscountTier">
                            <Plus class="mr-2 h-4 w-4" />
                            Add Tier
                        </Button>
                    </div>

                    <div v-if="form.bulk_discounts.length === 0" class="py-4 text-center text-sm text-gray-500">
                        No bulk discount tiers defined. Click "Add Tier" to create quantity-based discounts.
                    </div>

                    <div v-else class="space-y-3">
                        <div v-for="(tier, index) in form.bulk_discounts" :key="index" class="flex items-end gap-3 rounded-lg border p-3">
                            <div class="flex-1 space-y-1">
                                <Label :for="`min_qty_${index}`" class="text-xs">Min Quantity</Label>
                                <Input :id="`min_qty_${index}`" v-model.number="tier.min_quantity" type="number" min="1" size="sm" />
                            </div>
                            <div class="flex-1 space-y-1">
                                <Label :for="`discount_${index}`" class="text-xs">Discount %</Label>
                                <Input
                                    :id="`discount_${index}`"
                                    v-model.number="tier.discount_percentage"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="75"
                                    size="sm"
                                />
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="removeBulkDiscountTier(index)"
                                class="text-red-600 hover:text-red-700"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="space-y-2">
                    <Label for="notes">Notes & Justification</Label>
                    <Textarea
                        id="notes"
                        v-model="form.notes"
                        placeholder="Explain the reason for this price change..."
                        :rows="3"
                        :class="{ 'border-red-500': form.errors.notes }"
                    />
                    <p v-if="form.errors.notes" class="text-sm text-red-600">{{ form.errors.notes }}</p>
                    <p class="text-xs text-gray-500">Providing clear justification helps with the approval process.</p>
                </div>
            </form>

            <DialogFooter class="flex items-center justify-between">
                <div class="text-sm text-gray-500">This price update will require approval before becoming active.</div>
                <div class="flex items-center gap-2">
                    <Button type="button" variant="outline" @click="$emit('update:open', false)" :disabled="form.processing"> Cancel </Button>
                    <Button type="submit" @click="handleSubmit" :disabled="!canSubmit" :loading="form.processing">
                        {{ form.processing ? 'Submitting...' : 'Submit for Approval' }}
                    </Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
