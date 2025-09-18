<template>
    <div class="receipt-container">
        <!-- Receipt Preview -->
        <div ref="receiptRef" class="receipt">
            <!-- Header -->
            <div class="receipt-header">
                <h2 class="business-name">{{ receiptData.business_name }}</h2>
                <p v-if="receiptData.business_address" class="business-info">{{ receiptData.business_address }}</p>
                <p v-if="receiptData.business_phone" class="business-info">Tel: {{ receiptData.business_phone }}</p>
            </div>

            <!-- Sale Information -->
            <div class="sale-info">
                <div class="info-row">
                    <span class="label">Receipt #:</span>
                    <span class="value">{{ receiptData.receipt_number }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Sale #:</span>
                    <span class="value">{{ receiptData.sale_number }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Date:</span>
                    <span class="value">{{ receiptData.sale_date }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Cashier:</span>
                    <span class="value">{{ receiptData.cashier_name }}</span>
                </div>
                <div v-if="receiptData.customer_name" class="info-row">
                    <span class="label">Customer:</span>
                    <span class="value">{{ receiptData.customer_name }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Payment:</span>
                    <span class="value">{{ receiptData.payment_method.toUpperCase() }}</span>
                </div>
            </div>

            <!-- Items -->
            <div class="items-section">
                <div class="section-divider"></div>
                <div v-for="item in receiptData.items" :key="item.sku" class="item">
                    <div class="item-header">
                        <span class="item-name">{{ item.name }}</span>
                        <span class="item-total">{{ formatCurrency(item.line_total) }}</span>
                    </div>
                    <div class="item-details">
                        {{ item.quantity }} x {{ formatCurrency(item.unit_price) }}
                        <span v-if="item.sku" class="item-sku">({{ item.sku }})</span>
                    </div>
                </div>
                <div class="section-divider"></div>
            </div>

            <!-- Totals -->
            <div class="totals-section">
                <div class="total-row">
                    <span class="label">Subtotal:</span>
                    <span class="value">{{ formatCurrency(receiptData.subtotal) }}</span>
                </div>
                <div class="total-row">
                    <span class="label">Tax:</span>
                    <span class="value">{{ formatCurrency(receiptData.tax_amount) }}</span>
                </div>
                <div class="total-row total-amount">
                    <span class="label">TOTAL:</span>
                    <span class="value">{{ formatCurrency(receiptData.total_amount) }}</span>
                </div>
            </div>

            <!-- Footer -->
            <div class="receipt-footer">
                <div class="section-divider"></div>

                <!-- QR Code Section -->
                <div v-if="receiptData.verification_url" class="qr-code-section">
                    <div class="qr-code-container">
                        <img
                            :src="generateQRCodeDataURL(receiptData.verification_url)"
                            alt="Receipt Verification QR Code"
                            class="qr-code"
                        />
                    </div>
                    <p class="qr-code-text">Scan to verify receipt online</p>
                </div>

                <p class="print-time">Printed: {{ currentDateTime }}</p>
                <p class="thank-you">Thank you for your business!</p>
            </div>
        </div>

        <!-- Print Actions -->
        <div v-if="showActions" class="print-actions">
            <Button @click="handlePrint" :disabled="isPrinting" class="print-btn">
                <Printer class="mr-2 h-4 w-4" />
                <span v-if="isPrinting">Printing...</span>
                <span v-else>Print Receipt</span>
            </Button>

            <Button @click="handlePreview" variant="outline" class="preview-btn">
                <Eye class="mr-2 h-4 w-4" />
                Preview
            </Button>

            <Button @click="handleDownload" :disabled="isGenerating" variant="outline" class="download-btn">
                <Download class="mr-2 h-4 w-4" />
                <span v-if="isGenerating">Generating...</span>
                <span v-else>Download PDF</span>
            </Button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { useCurrency } from '@/composables/useCurrency';
import { useReceipt, type ReceiptData } from '@/composables/useReceipt';
import { useAlerts } from '@/composables/useAlerts';
import { Download, Eye, Printer } from 'lucide-vue-next';

interface Props {
    receiptData: ReceiptData;
    showActions?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showActions: true,
});

const emit = defineEmits<{
    printed: [];
    error: [message: string];
}>();

// Composables
const { formatCurrency } = useCurrency();
const { printReceipt, previewReceipt, downloadReceiptPDF, isPrinting, isGenerating } = useReceipt();
const { success, error } = useAlerts();

// Refs
const receiptRef = ref<HTMLElement>();

// Computed
const currentDateTime = computed(() => {
    return new Date().toLocaleString('en-GH', {
        dateStyle: 'medium',
        timeStyle: 'short'
    });
});

// Methods
const handlePrint = async () => {
    try {
        await printReceipt(props.receiptData);
        success('Receipt sent to printer successfully!');
        emit('printed');
    } catch (err) {
        const message = err instanceof Error ? err.message : 'Failed to print receipt';
        error(message);
        emit('error', message);
    }
};

const handlePreview = () => {
    try {
        previewReceipt(props.receiptData);
    } catch (err) {
        const message = err instanceof Error ? err.message : 'Failed to preview receipt';
        error(message);
        emit('error', message);
    }
};

const handleDownload = async () => {
    try {
        await downloadReceiptPDF(props.receiptData);
        success('Receipt PDF generated successfully!');
    } catch (err) {
        const message = err instanceof Error ? err.message : 'Failed to generate PDF';
        error(message);
        emit('error', message);
    }
};

const generateQRCodeDataURL = (url: string): string => {
    // Simple QR code generation using QR Server API
    const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=${encodeURIComponent(url)}`;
    return qrUrl;
};
</script>

<style scoped>
.receipt-container {
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    background-color: white;
}

.receipt {
    font-family: 'Courier New', monospace;
    font-size: 0.875rem;
    line-height: 1.6;
    max-width: 24rem;
    margin: 0 auto;
    padding: 1.5rem;
    min-height: 500px;
}

.receipt-header {
    text-align: center;
    padding-bottom: 1rem;
    margin-bottom: 1rem;
    border-bottom: 2px dashed #9ca3af;
}

.business-name {
    font-size: 1.125rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
    color: #111827;
}

.business-info {
    font-size: 0.75rem;
    color: #4b5563;
    margin-bottom: 0.25rem;
}

.sale-info {
    margin-bottom: 1rem;
}

.sale-info > div {
    margin-bottom: 0.25rem;
}

.info-row {
    display: flex;
    justify-content: space-between;
}

.label {
    font-weight: 500;
    color: #374151;
}

.value {
    color: #111827;
}

.section-divider {
    margin: 1rem 0;
    border-bottom: 1px dashed #9ca3af;
}

.items-section {
    margin-bottom: 1rem;
}

.item {
    margin-bottom: 0.75rem;
}

.item-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.item-name {
    font-weight: 500;
    color: #111827;
    flex: 1;
    margin-right: 0.5rem;
}

.item-total {
    font-weight: 500;
    color: #111827;
}

.item-details {
    font-size: 0.75rem;
    color: #4b5563;
    margin-top: 0.25rem;
}

.item-sku {
    margin-left: 0.5rem;
}

.totals-section {
    margin-bottom: 1rem;
}

.total-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.25rem;
}

.total-amount {
    font-weight: bold;
    font-size: 1rem;
    padding-top: 0.5rem;
    margin-top: 0.5rem;
    border-top: 1px solid #374151;
}

.receipt-footer {
    text-align: center;
    font-size: 0.75rem;
    color: #4b5563;
}

.qr-code-section {
    margin: 1rem 0;
}

.qr-code-container {
    display: flex;
    justify-content: center;
    margin-bottom: 0.5rem;
}

.qr-code {
    width: 80px;
    height: 80px;
    border: 1px solid #e5e7eb;
    border-radius: 0.25rem;
}

.qr-code-text {
    font-size: 0.625rem;
    color: #6b7280;
    margin: 0;
}

.print-time {
    margin-bottom: 0.5rem;
}

.thank-you {
    font-style: italic;
    margin-top: 1rem;
}

.print-actions {
    display: flex;
    gap: 0.5rem;
    padding: 1rem;
    border-top: 1px solid #e5e7eb;
    background-color: #f9fafb;
}

.print-btn {
    flex: 1;
}

.preview-btn,
.download-btn {
    flex-shrink: 0;
}

/* Print styles */
@media print {
    .receipt-container {
        box-shadow: none;
        border: none;
    }

    .print-actions {
        display: none;
    }

    .receipt {
        max-width: none;
        padding: 8px;
        font-size: 10px;
    }
}
</style>