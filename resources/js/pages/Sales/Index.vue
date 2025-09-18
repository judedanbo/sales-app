<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Sales History</h1>
                <div class="flex space-x-2">
                    <Button @click="router.visit('/sales/pos')" class="bg-green-600 hover:bg-green-700">
                        <Plus class="mr-2 h-4 w-4" />
                        New Sale
                    </Button>
                </div>
            </div>
        </template>

        <div class="rounded-lg bg-white shadow dark:bg-gray-800">
            <!-- Filters -->
            <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <Label class="text-sm font-medium text-gray-700 dark:text-gray-300"> Search </Label>
                        <Input v-model="filters.search" placeholder="Search by sale number..." class="mt-1" />
                    </div>
                    <div>
                        <Label class="text-sm font-medium text-gray-700 dark:text-gray-300"> Status </Label>
                        <select
                            v-model="filters.status"
                            class="mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700"
                        >
                            <option value="">All Statuses</option>
                            <option value="completed">Completed</option>
                            <option value="voided">Voided</option>
                        </select>
                    </div>
                    <div>
                        <Label class="text-sm font-medium text-gray-700 dark:text-gray-300"> Payment Method </Label>
                        <select
                            v-model="filters.payment_method"
                            class="mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700"
                        >
                            <option value="">All Methods</option>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <Label class="text-sm font-medium text-gray-700 dark:text-gray-300"> Date Range </Label>
                        <Input v-model="filters.date_from" type="date" class="mt-1" />
                    </div>
                </div>
            </div>

            <!-- Sales Statistics -->
            <div class="border-b border-gray-200 bg-gray-50 p-6 dark:border-gray-700 dark:bg-gray-900">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ statistics.total_sales || 0 }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Total Sales</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                            {{ formatCurrency(statistics.total_revenue || 0) }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Total Revenue</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                            {{ formatCurrency(statistics.average_order_value || 0) }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Average Order</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ statistics.completion_rate || 0 }}%</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">Completion Rate</div>
                    </div>
                </div>
            </div>

            <!-- Sales Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">Sale #</th>
                            <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                Customer
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">Items</th>
                            <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">Payment</th>
                            <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase dark:text-gray-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                        <tr v-for="sale in sales" :key="sale.id">
                            <td class="px-6 py-4 text-sm font-medium whitespace-nowrap text-gray-900 dark:text-gray-100">
                                {{ sale.sale_number }}
                            </td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                {{ formatDate(sale.sale_date) }}
                            </td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                {{ sale.customer_info?.name || 'Walk-in' }}
                            </td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">{{ sale.items_count || 0 }} items</td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                {{ formatCurrency(sale.total_amount) }}
                            </td>
                            <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                <span class="capitalize">{{ sale.payment_method.replace('_', ' ') }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <SaleStatusBadge :status="sale.status" />
                            </td>
                            <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                <div class="flex space-x-2">
                                    <Button @click="viewSale(sale.id)" variant="outline" size="sm">
                                        <Eye class="h-4 w-4" />
                                    </Button>
                                    <Button @click="showReceipt(sale.id)" :disabled="isLoadingPreview" variant="outline" size="sm">
                                        <Printer class="h-4 w-4" />
                                    </Button>
                                    <Button v-if="sale.status === 'completed'" @click="voidSale(sale)" variant="destructive" size="sm">
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="sales.length === 0">
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">No sales found</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="meta && meta.last_page > 1" class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        Showing {{ meta.current_page }} of {{ meta.last_page }} pages ({{ meta.total }} total sales)
                    </div>
                    <div class="flex space-x-2">
                        <Button @click="goToPage(meta.current_page - 1)" :disabled="meta.current_page <= 1" variant="outline" size="sm">
                            Previous
                        </Button>
                        <Button @click="goToPage(meta.current_page + 1)" :disabled="meta.current_page >= meta.last_page" variant="outline" size="sm">
                            Next
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Void Sale Modal -->
        <VoidSaleModal v-if="showVoidModal && voidModalSale" :sale="voidModalSale" @close="handleVoidModalClose" @voided="handleSaleVoided" />

        <!-- Receipt Modal -->
        <div
            v-if="showReceiptModal && currentReceiptData"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            @click="showReceiptModal = false"
        >
            <div
                class="bg-white rounded-lg shadow-xl max-w-md w-full m-4 max-h-[90vh] overflow-y-auto"
                @click.stop
            >
                <div class="flex items-center justify-between p-4 border-b">
                    <h3 class="text-lg font-semibold">Receipt</h3>
                    <Button @click="showReceiptModal = false" variant="ghost" size="sm">
                        <X class="h-4 w-4" />
                    </Button>
                </div>
                <ReceiptTemplate
                    :receipt-data="currentReceiptData"
                    @printed="handleReceiptPrinted"
                    @error="handleReceiptError"
                />
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import SaleStatusBadge from '@/components/sales/SaleStatusBadge.vue';
import VoidSaleModal from '@/components/sales/VoidSaleModal.vue';
import ReceiptTemplate from '@/components/sales/ReceiptTemplate.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useAlerts } from '@/composables/useAlerts';
import { useCurrency } from '@/composables/useCurrency';
import { useReceipt } from '@/composables/useReceipt';
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import { Eye, Plus, Printer, X } from 'lucide-vue-next';
import { onMounted, ref, watch } from 'vue';

interface Sale {
    id: number;
    sale_number: string;
    sale_date: string;
    status: string;
    total_amount: number;
    payment_method: string;
    customer_info?: {
        name?: string;
    };
    items_count: number;
}

interface Statistics {
    total_sales: number;
    total_revenue: number;
    average_order_value: number;
    completion_rate: number;
}

interface Meta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

// Composables
const { formatCurrency } = useCurrency();
const { success, error } = useAlerts();
const { transformSaleToReceipt } = useReceipt();

// Reactive data
const sales = ref<Sale[]>([]);
const statistics = ref<Statistics>({
    total_sales: 0,
    total_revenue: 0,
    average_order_value: 0,
    completion_rate: 0,
});
const meta = ref<Meta | null>(null);

const filters = ref({
    search: '',
    status: '',
    payment_method: '',
    date_from: '',
});

const loading = ref(false);
const isLoadingPreview = ref(false);
const voidModalSale = ref<Sale | null>(null);
const showVoidModal = ref(false);
const showReceiptModal = ref(false);
const currentReceiptData = ref<any>(null);

// Methods
const loadSales = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();

        Object.entries(filters.value).forEach(([key, value]) => {
            if (value) {
                params.append(key, value);
            }
        });

        const response = await fetch(`/api/sales?${params.toString()}`, {
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (!response.ok) throw new Error('Failed to load sales');

        const result = await response.json();
        sales.value = result.data || [];
        meta.value = result.meta || null;
    } catch (err) {
        console.error('Failed to load sales:', err);
        error('Failed to load sales');
        sales.value = [];
    } finally {
        loading.value = false;
    }
};

const loadStatistics = async () => {
    try {
        const response = await fetch('/api/sales/statistics', {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) throw new Error('Failed to load statistics');

        const result = await response.json();
        statistics.value = result.data || {};
    } catch (err) {
        console.error('Failed to load statistics:', err);
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const viewSale = (saleId: number) => {
    router.visit(`/sales/${saleId}`);
};

const showReceipt = async (saleId: number) => {
    if (isLoadingPreview.value) return;

    isLoadingPreview.value = true;
    try {
        // Fetch complete sale data from API
        const response = await fetch(`/api/sales/${saleId}`, {
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (!response.ok) throw new Error('Failed to fetch sale data');

        const result = await response.json();
        const saleData = result.data;

        // Transform data and show in modal
        currentReceiptData.value = transformSaleToReceipt(saleData);
        showReceiptModal.value = true;
    } catch (err) {
        console.error('Failed to load receipt:', err);
        error('Failed to load receipt');
    } finally {
        isLoadingPreview.value = false;
    }
};

const handleReceiptPrinted = () => {
    showReceiptModal.value = false;
    success('Receipt printed successfully!');
};

const handleReceiptError = (message: string) => {
    error(message);
};

const voidSale = (sale: Sale) => {
    voidModalSale.value = sale;
    showVoidModal.value = true;
};

const handleVoidModalClose = () => {
    voidModalSale.value = null;
    showVoidModal.value = false;
};

const handleSaleVoided = () => {
    handleVoidModalClose();
    loadSales(); // Reload the sales list
};

const goToPage = (page: number) => {
    // Update URL with new page
    const params = new URLSearchParams(window.location.search);
    params.set('page', page.toString());
    window.history.pushState({}, '', `${window.location.pathname}?${params.toString()}`);
    loadSales();
};

// Debounced filter function
const debouncedLoadSales = useDebounceFn(loadSales, 500);

// Watchers
watch(filters, debouncedLoadSales, { deep: true });

// Lifecycle
onMounted(() => {
    loadSales();
    loadStatistics();
});
</script>
