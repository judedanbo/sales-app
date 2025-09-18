<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center">
        <!-- Backdrop -->
        <div
            class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
            @click="$emit('close')"
        ></div>

        <!-- Modal -->
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-2">
                    <AlertTriangle class="h-5 w-5 text-red-500" />
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Void Sale
                    </h3>
                </div>
                <Button @click="$emit('close')" variant="ghost" size="sm">
                    <X class="h-4 w-4" />
                </Button>
            </div>

            <!-- Content -->
            <div class="space-y-4">
                <!-- Warning -->
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                    <div class="flex">
                        <AlertTriangle class="h-5 w-5 text-red-400 mr-3 mt-0.5 flex-shrink-0" />
                        <div class="text-sm">
                            <h4 class="font-medium text-red-800 dark:text-red-400 mb-1">
                                Permanent Action
                            </h4>
                            <p class="text-red-700 dark:text-red-300">
                                Voiding this sale will permanently mark it as void and restore inventory.
                                This action cannot be undone.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Sale Information -->
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Sale Details</h4>
                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Sale Number:</span>
                            <span class="font-mono">{{ sale.sale_number }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Total Amount:</span>
                            <span class="font-medium">{{ formatCurrency(sale.total_amount) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Items:</span>
                            <span>{{ sale.items.length }} item{{ sale.items.length !== 1 ? 's' : '' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Void Reason -->
                <div>
                    <Label for="void-reason" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Reason for Void <span class="text-red-500">*</span>
                    </Label>
                    <select
                        id="void-reason"
                        v-model="voidReason"
                        class="mt-1 w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                        required
                    >
                        <option value="">Select a reason...</option>
                        <option value="customer_request">Customer Request</option>
                        <option value="pricing_error">Pricing Error</option>
                        <option value="product_unavailable">Product Unavailable</option>
                        <option value="payment_issue">Payment Issue</option>
                        <option value="system_error">System Error</option>
                        <option value="cashier_error">Cashier Error</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <!-- Custom Reason -->
                <div v-if="voidReason === 'other'">
                    <Label for="custom-reason" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Please specify
                    </Label>
                    <textarea
                        id="custom-reason"
                        v-model="customReason"
                        rows="3"
                        class="mt-1 w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                        placeholder="Enter the reason for voiding this sale..."
                    ></textarea>
                </div>

                <!-- Actions -->
                <div class="flex space-x-3 pt-2">
                    <Button @click="$emit('close')" variant="outline" class="flex-1">
                        Cancel
                    </Button>
                    <Button
                        @click="handleVoid"
                        :disabled="!canVoid || isVoiding"
                        variant="destructive"
                        class="flex-1"
                    >
                        <span v-if="isVoiding">Voiding...</span>
                        <span v-else>Void Sale</span>
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useAlerts } from '@/composables/useAlerts';
import { useCurrency } from '@/composables/useCurrency';
import { AlertTriangle, X } from 'lucide-vue-next';

interface Sale {
    id: number;
    sale_number: string;
    total_amount: number;
    items: any[];
}

interface Props {
    sale: Sale;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    close: [];
    voided: [];
}>();

// Composables
const { success, error } = useAlerts();
const { formatCurrency } = useCurrency();

// Reactive data
const voidReason = ref('');
const customReason = ref('');
const isVoiding = ref(false);

// Computed
const canVoid = computed(() => {
    if (!voidReason.value) return false;
    if (voidReason.value === 'other' && !customReason.value.trim()) return false;
    return true;
});

const finalReason = computed(() => {
    if (voidReason.value === 'other') {
        return customReason.value.trim();
    }
    return voidReason.value.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
});

// Methods
const handleVoid = async () => {
    if (!canVoid.value) return;

    isVoiding.value = true;

    try {
        await router.post(`/sales/${props.sale.id}/void`, {
            void_reason: finalReason.value,
        }, {
            onSuccess: () => {
                success('Sale voided successfully');
                emit('voided');
            },
            onError: (errors) => {
                console.error('Void sale errors:', errors);
                error('Failed to void sale. Please try again.');
            },
            onFinish: () => {
                isVoiding.value = false;
            },
        });
    } catch (err) {
        console.error('Void sale error:', err);
        error('Failed to void sale. Please try again.');
        isVoiding.value = false;
    }
};
</script>

<style scoped>
/* Modal animations */
.modal-enter-active,
.modal-leave-active {
    transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
    transform: scale(0.9);
}
</style>