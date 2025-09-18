<template>
    <AppLayout>
        <template #header>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Point of Sale</h1>
        </template>

        <div class="grid h-[calc(100vh-200px)] grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Product Search & Selection -->
            <div class="rounded-lg bg-white p-6 shadow lg:col-span-2 dark:bg-gray-800">
                <div class="mb-4">
                    <div class="relative">
                        <Input
                            v-model="search"
                            placeholder="Search products by name, SKU, or barcode..."
                            class="w-full pl-10"
                            @input="searchProducts"
                        />
                        <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-gray-400" />
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="grid max-h-[80vh] grid-cols-2 gap-4 overflow-y-auto md:grid-cols-3 lg:grid-cols-4">
                    <div
                        v-for="product in filteredProducts"
                        :key="product.id"
                        @click="addToCart(product)"
                        class="cursor-pointer rounded-lg border border-gray-200 p-4 transition-colors hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700"
                    >
                        <div class="mb-3 flex aspect-square items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700">
                            <Package class="h-8 w-8 text-gray-400" />
                        </div>
                        <h3 class="mb-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ product.name }}
                        </h3>
                        <p class="mb-2 text-xs text-gray-600 dark:text-gray-400">SKU: {{ product.sku }}</p>
                        <p class="text-lg font-bold text-blue-600 dark:text-blue-400">
                            {{ formatCurrency(product.unit_price) }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Stock: {{ product.inventory?.quantity_available || 0 }}</p>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredProducts.length === 0" class="py-12 text-center">
                    <Package class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No products found</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Try adjusting your search terms</p>
                </div>
            </div>

            <!-- Shopping Cart & Checkout -->
            <div class="flex h-full flex-col rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <!-- Fixed Header -->
                <div class="mb-4 flex-none">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Shopping Cart</h2>
                </div>

                <!-- Scrollable Cart Items -->
                <div class="mb-4 min-h-0 flex-1 overflow-y-auto">
                    <div v-if="cart.length === 0" class="py-8 text-center">
                        <ShoppingCart class="mx-auto h-12 w-12 text-gray-400" />
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Cart is empty</p>
                    </div>

                    <div v-else class="max-h-[40vh] space-y-2 overflow-y-auto">
                        <div
                            v-for="(item, index) in cart"
                            :key="index"
                            class="flex items-center justify-between rounded-lg border border-gray-200 p-3 dark:border-gray-700"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ item.product.name }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatCurrency(item.unit_price) }} each</p>
                            </div>
                            <div class="ml-2 flex items-center space-x-2">
                                <Button @click="updateQuantity(index, item.quantity - 1)" variant="outline" size="sm" class="h-8 w-8 p-0">
                                    <Minus class="h-3 w-3" />
                                </Button>
                                <span class="w-8 text-center text-sm font-medium">{{ item.quantity }}</span>
                                <Button @click="updateQuantity(index, item.quantity + 1)" variant="outline" size="sm" class="h-8 w-8 p-0">
                                    <Plus class="h-3 w-3" />
                                </Button>
                                <Button @click="removeFromCart(index)" variant="destructive" size="sm" class="ml-2 h-8 w-8 p-0">
                                    <Trash2 class="h-3 w-3" />
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fixed Footer: Cart Summary & Actions -->
                <div class="flex-none border-t border-gray-200 pt-4 dark:border-gray-700">
                    <div class="mb-4 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span>Subtotal:</span>
                            <span>{{ formatCurrency(subtotal) }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span>Tax:</span>
                            <span>{{ formatCurrency(taxAmount) }}</span>
                        </div>
                        <div class="flex justify-between border-t border-gray-200 pt-2 text-lg font-bold dark:border-gray-700">
                            <span>Total:</span>
                            <span>{{ formatCurrency(total) }}</span>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="mb-4">
                        <Label class="text-sm font-medium text-gray-700 dark:text-gray-300"> Payment Method </Label>
                        <select
                            v-model="paymentMethod"
                            class="mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700"
                        >
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="mobile_money">Mobile Money</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <!-- Customer Info -->
                    <div class="mb-4">
                        <Label class="text-sm font-medium text-gray-700 dark:text-gray-300"> Customer Name (Optional) </Label>
                        <Input v-model="customerName" placeholder="Enter customer name" class="mt-1" />
                    </div>

                    <!-- Receipt Options -->
                    <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-md">
                        <div class="flex items-center space-x-2">
                            <input
                                id="auto-print"
                                v-model="autoPrintReceipt"
                                type="checkbox"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            />
                            <Label for="auto-print" class="text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                <Printer class="inline h-4 w-4 mr-1" />
                                Auto-print receipt after sale
                            </Label>
                        </div>
                    </div>

                    <!-- Process Sale Button -->
                    <Button @click="processSale" :disabled="cart.length === 0 || processing" class="w-full" size="lg">
                        <div v-if="processing" class="flex items-center">
                            <div class="mr-2 h-4 w-4 animate-spin rounded-full border-b-2 border-white"></div>
                            Processing...
                        </div>
                        <div v-else>Process Sale ({{ formatCurrency(total) }})</div>
                    </Button>

                    <!-- Clear Cart Button -->
                    <Button v-if="cart.length > 0" @click="clearCart" variant="outline" class="mt-2 w-full"> Clear Cart </Button>

                    <!-- Print Last Receipt Button -->
                    <Button
                        v-if="lastSaleData"
                        @click="showReceiptModal = true"
                        variant="outline"
                        class="mt-2 w-full"
                        :disabled="isPrinting"
                    >
                        <Receipt class="mr-2 h-4 w-4" />
                        <span v-if="isPrinting">Printing...</span>
                        <span v-else>Print Last Receipt</span>
                    </Button>
                </div>
            </div>
        </div>

        <!-- Receipt Modal -->
        <div
            v-if="showReceiptModal && lastSaleData"
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
                        ×
                    </Button>
                </div>

                <ReceiptTemplate
                    :receipt-data="transformSaleToReceipt(lastSaleData)"
                    @printed="showReceiptModal = false"
                />
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useAlerts } from '@/composables/useAlerts';
import { useCurrency } from '@/composables/useCurrency';
import { useReceipt } from '@/composables/useReceipt';
import { Minus, Package, Plus, Printer, Receipt, Search, ShoppingCart, Trash2 } from 'lucide-vue-next';
import ReceiptTemplate from '@/components/sales/ReceiptTemplate.vue';

interface Product {
    id: number;
    name: string;
    sku: string;
    unit_price: number;
    tax_rate: number;
    inventory?: {
        quantity_available: number;
    };
}

interface CartItem {
    product: Product;
    quantity: number;
    unit_price: number;
}

// Props from backend
const props = defineProps<{
    products?: Product[];
}>();

// Composables
const { formatCurrency } = useCurrency();
const { success, error } = useAlerts();
const { printReceipt, transformSaleToReceipt, isPrinting } = useReceipt();
const page = usePage();

// Reactive data
const search = ref('');
const cart = ref<CartItem[]>([]);
const paymentMethod = ref('cash');
const customerName = ref('');
const processing = ref(false);
const products = ref<Product[]>([]);
const autoPrintReceipt = ref(true);
const showReceiptModal = ref(false);
const lastSaleData = ref<any>(null);

// Computed properties
const filteredProducts = computed(() => {
    if (!search.value) return products.value;

    const searchTerm = search.value.toLowerCase();
    return products.value.filter((product) => product.name.toLowerCase().includes(searchTerm) || product.sku.toLowerCase().includes(searchTerm));
});

const subtotal = computed(() => {
    return cart.value.reduce((total, item) => {
        return total + item.quantity * item.unit_price;
    }, 0);
});

const taxAmount = computed(() => {
    return cart.value.reduce((total, item) => {
        const lineTotal = item.quantity * item.unit_price;
        return total + lineTotal * item.product.tax_rate;
    }, 0);
});

const total = computed(() => {
    return subtotal.value + taxAmount.value;
});

// Methods
const searchProducts = async () => {
    // Debounced search - in a real app, this would call the API
    // For now, we'll just filter the existing products
};

const addToCart = (product: Product) => {
    // Check if product is already in cart
    const existingIndex = cart.value.findIndex((item) => item.product.id === product.id);

    if (existingIndex !== -1) {
        // Increase quantity
        updateQuantity(existingIndex, cart.value[existingIndex].quantity + 1);
    } else {
        // Add new item
        cart.value.push({
            product,
            quantity: 1,
            unit_price: product.unit_price,
        });
    }
};

const updateQuantity = (index: number, newQuantity: number) => {
    if (newQuantity <= 0) {
        removeFromCart(index);
        return;
    }

    const item = cart.value[index];
    const availableStock = item.product.inventory?.quantity_available || 0;

    if (newQuantity > availableStock) {
        error(`Only ${availableStock} units available for ${item.product.name}`);
        return;
    }

    cart.value[index].quantity = newQuantity;
};

const removeFromCart = (index: number) => {
    cart.value.splice(index, 1);
};

const clearCart = () => {
    cart.value = [];
    customerName.value = '';
};

const updateProductStockLocally = (soldItems: any[]) => {
    // Optimistically update product stock in the local products array
    soldItems.forEach(item => {
        const productIndex = products.value.findIndex(p => p.id === item.product_id);
        if (productIndex !== -1 && products.value[productIndex].inventory) {
            const currentAvailable = products.value[productIndex].inventory!.quantity_available;
            const newAvailable = Math.max(0, currentAvailable - item.quantity);

            // Update the product's inventory
            products.value[productIndex].inventory!.quantity_available = newAvailable;
        }
    });
};

const processSale = async () => {
    if (cart.value.length === 0) return;

    processing.value = true;

    try {
        const saleData = {
            items: cart.value.map((item) => ({
                product_id: item.product.id,
                quantity: item.quantity,
                unit_price: item.unit_price,
            })),
            payment_method: paymentMethod.value,
            customer_info: customerName.value
                ? {
                      name: customerName.value,
                  }
                : null,
        };

        router.post('/sales', saleData, {
            onSuccess: async (page) => {
                success('Sale processed successfully!', { position: 'top-center' });

                // Extract sale data from shared data or create fallback
                const saleResponse = {
                    sale_number: `TXN${Date.now()}`,
                    receipt_number: `REC${Date.now()}`,
                    created_at: new Date().toISOString(),
                    items: saleData.items.map(item => ({
                        ...item,
                        product: products.value.find(p => p.id === item.product_id)
                    })),
                    subtotal: subtotal.value,
                    tax_amount: taxAmount.value,
                    total_amount: total.value,
                    payment_method: saleData.payment_method,
                    customer_info: saleData.customer_info,
                    cashier: { name: 'Current User' } // This should come from auth
                };

                // Store sale data for receipt printing
                lastSaleData.value = saleResponse;

                // Debug: Log sale response for troubleshooting
                console.log('Sale response data:', {
                    hasVerificationToken: !!(saleResponse as any).verification_token,
                    verificationToken: (saleResponse as any).verification_token,
                    saleNumber: saleResponse.sale_number,
                    saleId: (saleResponse as any).id,
                    fullResponse: saleResponse
                });

                // Auto-print receipt if enabled
                if (autoPrintReceipt.value) {
                    try {
                        const receiptData = transformSaleToReceipt(saleResponse);
                        console.log('Transformed receipt data:', {
                            hasVerificationUrl: !!receiptData.verification_url,
                            verificationUrl: receiptData.verification_url
                        });
                        await printReceipt(receiptData);
                        success('Receipt sent to printer!', { position: 'top-center' });
                    } catch (printError) {
                        console.warn('Auto-print failed:', printError);
                        // Don't show error for auto-print failure, just log it
                    }
                }

                // Optimistically update product stock locally for immediate feedback
                updateProductStockLocally(saleData.items);

                // Clear the cart
                clearCart();

                // Refresh product data from server to ensure accuracy
                await loadProducts();

                processing.value = false;
            },
            onError: (errors) => {
                console.error('Sale processing error:', errors);
                const errorMessage = errors.message || Object.values(errors)[0] || 'Failed to process sale';
                error(errorMessage);
                processing.value = false;
            },
            onFinish: () => {
                processing.value = false;
            },
        });
    } catch (err) {
        console.error('Sale processing error:', err);
        error(err instanceof Error ? err.message : 'Failed to process sale');
        processing.value = false;
    }
};

const loadProducts = async () => {
    try {
        // First, try to use products passed from backend
        if (props.products && props.products.length > 0) {
            products.value = props.products;
            console.log('Loaded products from backend:', products.value.length);
            return;
        }

        // Fallback: fetch from API
        console.log('Fetching products from API...');
        const response = await fetch('/api/products?status=active', {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();
        products.value = result.data || [];
        console.log('Loaded products from API:', products.value.length);
    } catch (err) {
        console.error('Failed to load products:', err);
        error('Failed to load products. Please refresh the page.');
        products.value = [];
    }
};

// Watch for sale data in shared props (from flash messages)
watch(
    () => (page.props.flash as any)?.sale,
    (newSale) => {
        if (newSale) {
            lastSaleData.value = newSale;
        }
    },
    { immediate: true }
);

// Lifecycle
onMounted(() => {
    loadProducts();
});
</script>
