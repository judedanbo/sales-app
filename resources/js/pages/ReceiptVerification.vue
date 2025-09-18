<template>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-2xl mx-auto px-4">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                    <CheckCircle class="w-8 h-8 text-green-600" />
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Receipt Verified</h1>
                <p class="text-gray-600">This is an authentic receipt from {{ receiptData.business_name }}</p>
                <p v-if="receiptData.is_fallback" class="text-sm text-amber-600 mt-2">
                    ⚠️ Using fallback verification. For enhanced security, consider upgrading to token-based verification.
                </p>
            </div>

            <!-- Receipt Display -->
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <!-- Business Header -->
                <div class="bg-gray-900 text-white text-center py-6 px-4">
                    <h2 class="text-2xl font-bold">{{ receiptData.business_name }}</h2>
                    <p v-if="receiptData.business_address" class="text-gray-300 text-sm mt-1">
                        {{ receiptData.business_address }}
                    </p>
                    <p v-if="receiptData.business_phone" class="text-gray-300 text-sm">
                        Tel: {{ receiptData.business_phone }}
                    </p>
                </div>

                <!-- Sale Information -->
                <div class="p-6 border-b">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <div class="mb-3">
                                <span class="font-medium text-gray-700">Receipt #:</span>
                                <span class="ml-2 font-mono">{{ receiptData.receipt_number }}</span>
                            </div>
                            <div class="mb-3">
                                <span class="font-medium text-gray-700">Sale #:</span>
                                <span class="ml-2 font-mono">{{ receiptData.sale_number }}</span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Date:</span>
                                <span class="ml-2">{{ receiptData.sale_date }}</span>
                            </div>
                        </div>
                        <div>
                            <div class="mb-3">
                                <span class="font-medium text-gray-700">Cashier:</span>
                                <span class="ml-2">{{ receiptData.cashier_name }}</span>
                            </div>
                            <div v-if="receiptData.customer_name" class="mb-3">
                                <span class="font-medium text-gray-700">Customer:</span>
                                <span class="ml-2">{{ receiptData.customer_name }}</span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Payment:</span>
                                <span class="ml-2 uppercase">{{ receiptData.payment_method }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items -->
                <div class="p-6 border-b">
                    <h3 class="font-medium text-gray-900 mb-4">Items Purchased</h3>
                    <div class="space-y-3">
                        <div
                            v-for="item in receiptData.items"
                            :key="item.sku || item.name"
                            class="flex justify-between items-start"
                        >
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">{{ item.name }}</p>
                                <p class="text-sm text-gray-500">
                                    {{ item.quantity }} x {{ formatCurrency(item.unit_price) }}
                                    <span v-if="item.sku" class="ml-1">({{ item.sku }})</span>
                                </p>
                            </div>
                            <p class="font-medium text-gray-900 ml-4">
                                {{ formatCurrency(item.line_total) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Totals -->
                <div class="p-6 bg-gray-50">
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal:</span>
                            <span class="font-medium">{{ formatCurrency(receiptData.subtotal) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tax:</span>
                            <span class="font-medium">{{ formatCurrency(receiptData.tax_amount) }}</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold pt-2 border-t">
                            <span>Total:</span>
                            <span>{{ formatCurrency(receiptData.total_amount) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Status -->
                <div class="p-6 border-t">
                    <div class="flex items-center justify-center">
                        <div class="flex items-center space-x-2">
                            <div
                                :class="{
                                    'bg-green-100 text-green-800': receiptData.status === 'completed',
                                    'bg-yellow-100 text-yellow-800': receiptData.status === 'pending',
                                    'bg-red-100 text-red-800': receiptData.status === 'voided',
                                }"
                                class="px-3 py-1 rounded-full text-xs font-medium uppercase tracking-wide"
                            >
                                {{ receiptData.status }}
                            </div>
                            <CheckCircle class="w-5 h-5 text-green-600" />
                        </div>
                    </div>
                    <p class="text-center text-xs text-gray-500 mt-4">
                        Verified on {{ currentDateTime }}
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8">
                <p class="text-sm text-gray-600 mb-4">
                    This receipt has been cryptographically verified as authentic.
                </p>
                <p class="text-xs text-gray-500">
                    Verification Token: {{ receiptData.verification_token }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { CheckCircle } from 'lucide-vue-next';
import { useCurrency } from '@/composables/useCurrency';

interface ReceiptItem {
    sku?: string;
    name: string;
    quantity: number;
    unit_price: number;
    line_total: number;
}

interface ReceiptData {
    sale_number: string;
    receipt_number: string;
    sale_date: string;
    cashier_name: string;
    customer_name?: string;
    payment_method: string;
    subtotal: number;
    tax_amount: number;
    total_amount: number;
    status: string;
    items: ReceiptItem[];
    business_name: string;
    business_address?: string;
    business_phone?: string;
    verification_token: string;
    is_fallback?: boolean;
}

interface Props {
    receiptData: ReceiptData;
}

defineProps<Props>();

// Composables
const { formatCurrency } = useCurrency();

// Computed
const currentDateTime = computed(() => {
    return new Date().toLocaleString('en-GH', {
        dateStyle: 'medium',
        timeStyle: 'short'
    });
});
</script>

<style scoped>
@media print {
    .min-h-screen {
        min-height: auto;
    }

    body {
        margin: 0;
        padding: 0;
    }
}
</style>