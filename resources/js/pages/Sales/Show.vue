<template>
    <AppLayout>
        <Head :title="`Sale ${sale.sale_number}`" />

        <template #header>
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                        Sale {{ sale.sale_number }}
                    </h1>
                    <div class="flex items-center space-x-2 mt-1">
                        <SaleStatusBadge :status="sale.status" />
                        <span class="text-sm text-gray-500">
                            {{ formatDate(sale.sale_date) }}
                        </span>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <ReceiptActions
                        :sale-data="receiptData"
                        @printed="handleReceiptPrinted"
                        @error="handleReceiptError"
                    />

                    <PermissionGuard permission="view_qr_codes">
                        <Button @click="showQRModal = true" variant="outline">
                            <QrCode class="h-4 w-4 mr-2" />
                            QR Code
                        </Button>
                    </PermissionGuard>

                    <PermissionGuard permission="void_sales">
                        <Button
                            v-if="sale.status === 'completed'"
                            @click="showVoidModal = true"
                            variant="destructive"
                        >
                            <Ban class="h-4 w-4 mr-2" />
                            Void Sale
                        </Button>
                    </PermissionGuard>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Sale Overview -->
                <Card>
                    <CardHeader>
                        <CardTitle>Sale Information</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <Label class="text-sm font-medium text-gray-500">Sale Number</Label>
                                <p class="font-mono text-sm">{{ sale.sale_number }}</p>
                            </div>
                            <div>
                                <Label class="text-sm font-medium text-gray-500">Receipt Number</Label>
                                <p class="font-mono text-sm">{{ sale.receipt_number || sale.sale_number }}</p>
                            </div>
                            <div>
                                <Label class="text-sm font-medium text-gray-500">Date & Time</Label>
                                <p class="text-sm">{{ formatDateTime(sale.sale_date) }}</p>
                            </div>
                            <div>
                                <Label class="text-sm font-medium text-gray-500">Payment Method</Label>
                                <p class="text-sm capitalize">{{ sale.payment_method.replace('_', ' ') }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4" v-if="sale.cashier || sale.school">
                            <div v-if="sale.cashier">
                                <Label class="text-sm font-medium text-gray-500">Cashier</Label>
                                <p class="text-sm">{{ sale.cashier.name }}</p>
                            </div>
                            <div v-if="sale.school">
                                <Label class="text-sm font-medium text-gray-500">School</Label>
                                <p class="text-sm">{{ sale.school.name }}</p>
                            </div>
                        </div>

                        <div v-if="sale.notes">
                            <Label class="text-sm font-medium text-gray-500">Notes</Label>
                            <p class="text-sm bg-gray-50 dark:bg-gray-800 p-3 rounded">{{ sale.notes }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Customer Information -->
                <Card v-if="sale.customer_info">
                    <CardHeader>
                        <CardTitle>Customer Information</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div v-if="sale.customer_info.name">
                                <Label class="text-sm font-medium text-gray-500">Name</Label>
                                <p class="text-sm">{{ sale.customer_info.name }}</p>
                            </div>
                            <div v-if="sale.customer_info.phone">
                                <Label class="text-sm font-medium text-gray-500">Phone</Label>
                                <p class="text-sm">{{ sale.customer_info.phone }}</p>
                            </div>
                            <div v-if="sale.customer_info.email">
                                <Label class="text-sm font-medium text-gray-500">Email</Label>
                                <p class="text-sm">{{ sale.customer_info.email }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Sale Items -->
                <Card>
                    <CardHeader>
                        <CardTitle>Items Purchased</CardTitle>
                        <CardDescription>
                            {{ sale.items.length }} item{{ sale.items.length !== 1 ? 's' : '' }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Tax</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="item in sale.items" :key="item.id">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ item.product_name }}
                                                </div>
                                                <div v-if="item.product?.category" class="text-xs text-gray-500">
                                                    {{ item.product.category.name }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-xs font-mono text-gray-500">
                                                {{ item.product_sku || 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                            {{ item.quantity }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                            {{ formatCurrency(item.unit_price) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                            {{ formatCurrency(item.tax_amount) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            {{ formatCurrency(item.line_total) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <!-- Void Information -->
                <Card v-if="sale.status === 'voided'">
                    <CardHeader>
                        <CardTitle class="text-red-600">Void Information</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <Label class="text-sm font-medium text-gray-500">Voided Date</Label>
                                <p class="text-sm">{{ formatDateTime(sale.voided_at) }}</p>
                            </div>
                            <div v-if="sale.voided_by">
                                <Label class="text-sm font-medium text-gray-500">Voided By</Label>
                                <p class="text-sm">{{ sale.voided_by.name }}</p>
                            </div>
                            <div v-if="sale.void_reason">
                                <Label class="text-sm font-medium text-gray-500">Reason</Label>
                                <p class="text-sm">{{ sale.void_reason }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Totals Summary -->
                <Card>
                    <CardHeader>
                        <CardTitle>Order Summary</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Subtotal:</span>
                            <span class="text-sm font-medium">{{ formatCurrency(sale.subtotal) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Tax:</span>
                            <span class="text-sm font-medium">{{ formatCurrency(sale.tax_amount) }}</span>
                        </div>
                        <hr class="border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between">
                            <span class="text-base font-semibold">Total:</span>
                            <span class="text-base font-bold">{{ formatCurrency(sale.total_amount) }}</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Quick Actions -->
                <Card>
                    <CardHeader>
                        <CardTitle>Quick Actions</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <Button @click="copyVerificationURL" variant="outline" class="w-full justify-start">
                            <Copy class="h-4 w-4 mr-2" />
                            Copy Verification URL
                        </Button>

                        <Button @click="showQRModal = true" variant="outline" class="w-full justify-start">
                            <QrCode class="h-4 w-4 mr-2" />
                            Show QR Code
                        </Button>

                        <Button @click="router.visit('/sales')" variant="outline" class="w-full justify-start">
                            <ArrowLeft class="h-4 w-4 mr-2" />
                            Back to Sales
                        </Button>
                    </CardContent>
                </Card>

                <!-- Audit Trail -->
                <Card>
                    <CardHeader>
                        <CardTitle>Audit Trail</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div class="text-sm">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Created:</span>
                                <span class="font-medium">{{ formatDateTime(sale.created_at) }}</span>
                            </div>
                        </div>
                        <div class="text-sm" v-if="sale.updated_at !== sale.created_at">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Modified:</span>
                                <span class="font-medium">{{ formatDateTime(sale.updated_at) }}</span>
                            </div>
                        </div>
                        <div class="text-sm" v-if="sale.verification_token">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Verification:</span>
                                <Badge variant="outline">Available</Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- QR Code Modal -->
        <QRCodeModal
            v-if="showQRModal"
            :verification-url="verificationURL"
            :sale-number="sale.sale_number"
            @close="showQRModal = false"
        />

        <!-- Void Sale Modal -->
        <VoidSaleModal
            v-if="showVoidModal"
            :sale="sale"
            @close="showVoidModal = false"
            @voided="handleSaleVoided"
        />
    </AppLayout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import PermissionGuard from '@/components/PermissionGuard.vue';
import ReceiptActions from '@/components/sales/ReceiptActions.vue';
import QRCodeModal from '@/components/sales/QRCodeModal.vue';
import VoidSaleModal from '@/components/sales/VoidSaleModal.vue';
import SaleStatusBadge from '@/components/sales/SaleStatusBadge.vue';
import { useAlerts } from '@/composables/useAlerts';
import { useCurrency } from '@/composables/useCurrency';
import {
    ArrowLeft,
    Ban,
    Copy,
    QrCode
} from 'lucide-vue-next';

interface SaleItem {
    id: number;
    product_id: number;
    quantity: number;
    unit_price: number;
    line_total: number;
    tax_amount: number;
    product_name: string;
    product_sku?: string;
    product?: {
        id: number;
        name: string;
        sku: string;
        category?: {
            id: number;
            name: string;
        };
    };
}

interface Sale {
    id: number;
    sale_number: string;
    receipt_number?: string;
    sale_date: string;
    status: 'completed' | 'voided';
    subtotal: number;
    tax_amount: number;
    total_amount: number;
    payment_method: string;
    customer_info?: {
        name?: string;
        phone?: string;
        email?: string;
    };
    notes?: string;
    verification_token?: string;
    voided_at?: string;
    void_reason?: string;
    created_at: string;
    updated_at: string;
    cashier?: {
        id: number;
        name: string;
    };
    school?: {
        id: number;
        name: string;
    };
    voided_by?: {
        id: number;
        name: string;
    };
    items: SaleItem[];
}

interface Props {
    sale: Sale;
}

const props = defineProps<Props>();

// Composables
const { success, error } = useAlerts();
const { formatCurrency } = useCurrency();

// Reactive data
const showQRModal = ref(false);
const showVoidModal = ref(false);

// Computed properties
const verificationURL = computed(() => {
    if (props.sale.verification_token) {
        return `${window.location.origin}/receipt/${props.sale.verification_token}`;
    }
    return `${window.location.origin}/receipt/temp/${props.sale.sale_number}`;
});

const receiptData = computed(() => ({
    sale_number: props.sale.sale_number,
    receipt_number: props.sale.receipt_number || props.sale.sale_number,
    sale_date: props.sale.sale_date,
    cashier_name: props.sale.cashier?.name || 'System',
    customer_name: props.sale.customer_info?.name,
    payment_method: props.sale.payment_method,
    subtotal: props.sale.subtotal,
    tax_amount: props.sale.tax_amount,
    total_amount: props.sale.total_amount,
    items: props.sale.items.map(item => ({
        name: item.product_name,
        sku: item.product_sku || '',
        quantity: item.quantity,
        unit_price: item.unit_price,
        line_total: item.line_total,
    })),
    business_name: 'School Sales System',
    verification_url: verificationURL.value,
}));

// Methods
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-GH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatDateTime = (dateString: string) => {
    return new Date(dateString).toLocaleString('en-GH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const copyVerificationURL = async () => {
    try {
        await navigator.clipboard.writeText(verificationURL.value);
        success('Verification URL copied to clipboard!');
    } catch (err) {
        error('Failed to copy URL to clipboard');
    }
};

const handleReceiptPrinted = () => {
    success('Receipt printed successfully!');
};

const handleReceiptError = (message: string) => {
    error(message);
};

const handleSaleVoided = () => {
    showVoidModal.value = false;
    // Refresh the page to show updated status
    router.reload();
};
</script>

<style scoped>
/* Custom styles for the sales show page */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>