import { ref } from 'vue';
import { useCurrency } from './useCurrency';

export interface ReceiptItem {
    name: string;
    sku: string;
    quantity: number;
    unit_price: number;
    line_total: number;
    tax_amount: number;
}

export interface ReceiptData {
    sale_number: string;
    receipt_number: string;
    sale_date: string;
    cashier_name: string;
    customer_name?: string;
    items: ReceiptItem[];
    subtotal: number;
    tax_amount: number;
    total_amount: number;
    payment_method: string;
    business_name: string;
    business_address?: string;
    business_phone?: string;
    verification_url?: string;
}

export function useReceipt() {
    const { formatCurrency } = useCurrency();
    const isGenerating = ref(false);
    const isPrinting = ref(false);

    /**
     * Generate receipt HTML content
     */
    const generateReceiptHTML = (data: ReceiptData): string => {
        const now = new Date().toLocaleString('en-GH', {
            dateStyle: 'medium',
            timeStyle: 'short'
        });

        return `
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt - ${data.receipt_number}</title>
    <style>
        @media print {
            @page { margin: 0; size: 80mm auto; }
            body { margin: 0; }
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 8px;
            max-width: 300px;
            background: white;
            color: black;
        }

        .receipt {
            width: 100%;
        }

        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }

        .header {
            border-bottom: 1px dashed #000;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }

        .business-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .business-info {
            font-size: 10px;
            margin-bottom: 2px;
        }

        .sale-info {
            margin: 8px 0;
            font-size: 10px;
        }

        .items {
            border-bottom: 1px dashed #000;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }

        .item {
            margin-bottom: 4px;
        }

        .item-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1px;
        }

        .item-details {
            font-size: 10px;
            color: #666;
        }

        .totals {
            margin-bottom: 8px;
        }

        .total-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }

        .total-amount {
            font-weight: bold;
            font-size: 14px;
            border-top: 1px solid #000;
            padding-top: 4px;
            margin-top: 4px;
        }

        .footer {
            border-top: 1px dashed #000;
            padding-top: 8px;
            text-align: center;
            font-size: 10px;
        }

        .thank-you {
            margin-top: 8px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <!-- Header -->
        <div class="header center">
            <div class="business-name">${data.business_name}</div>
            ${data.business_address ? `<div class="business-info">${data.business_address}</div>` : ''}
            ${data.business_phone ? `<div class="business-info">Tel: ${data.business_phone}</div>` : ''}
        </div>

        <!-- Sale Information -->
        <div class="sale-info">
            <div><strong>Receipt #:</strong> ${data.receipt_number}</div>
            <div><strong>Sale #:</strong> ${data.sale_number}</div>
            <div><strong>Date:</strong> ${data.sale_date}</div>
            <div><strong>Cashier:</strong> ${data.cashier_name}</div>
            ${data.customer_name ? `<div><strong>Customer:</strong> ${data.customer_name}</div>` : ''}
            <div><strong>Payment:</strong> ${data.payment_method.toUpperCase()}</div>
        </div>

        <!-- Items -->
        <div class="items">
            ${data.items.map(item => `
                <div class="item">
                    <div class="item-line">
                        <span>${item.name}</span>
                        <span>${formatCurrency(item.line_total)}</span>
                    </div>
                    <div class="item-details">
                        ${item.quantity} x ${formatCurrency(item.unit_price)}
                        ${item.sku ? `(${item.sku})` : ''}
                    </div>
                </div>
            `).join('')}
        </div>

        <!-- Totals -->
        <div class="totals">
            <div class="total-line">
                <span>Subtotal:</span>
                <span>${formatCurrency(data.subtotal)}</span>
            </div>
            <div class="total-line">
                <span>Tax:</span>
                <span>${formatCurrency(data.tax_amount)}</span>
            </div>
            <div class="total-line total-amount">
                <span>TOTAL:</span>
                <span>${formatCurrency(data.total_amount)}</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            ${data.verification_url ? `
                <div style="text-align: center; margin: 8px 0;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=${encodeURIComponent(data.verification_url)}"
                         alt="Verification QR Code"
                         style="width: 60px; height: 60px; border: 1px solid #ccc;">
                    <div style="font-size: 8px; margin-top: 2px;">Scan to verify online</div>
                </div>
            ` : ''}
            <div>Printed: ${now}</div>
            <div class="thank-you">Thank you for your business!</div>
        </div>
    </div>
</body>
</html>`;
    };

    /**
     * Print receipt using browser print dialog
     */
    const printReceipt = async (data: ReceiptData): Promise<void> => {
        isPrinting.value = true;

        try {
            // Generate receipt HTML
            const receiptHTML = generateReceiptHTML(data);

            // Create a new window for printing
            const printWindow = window.open('', '_blank', 'width=300,height=600');
            if (!printWindow) {
                throw new Error('Could not open print window. Please check popup settings.');
            }

            // Write content and print
            printWindow.document.write(receiptHTML);
            printWindow.document.close();

            // Wait for content to load, then print
            printWindow.onload = () => {
                setTimeout(() => {
                    printWindow.print();
                    printWindow.close();
                }, 100);
            };

        } catch (error) {
            console.error('Print error:', error);
            throw error;
        } finally {
            isPrinting.value = false;
        }
    };

    /**
     * Generate PDF receipt for download
     */
    const downloadReceiptPDF = async (data: ReceiptData): Promise<void> => {
        isGenerating.value = true;

        try {
            // For now, we'll use the print method as fallback
            // In a production app, you might want to use a library like jsPDF
            await printReceipt(data);
        } catch (error) {
            console.error('PDF generation error:', error);
            throw error;
        } finally {
            isGenerating.value = false;
        }
    };

    /**
     * Preview receipt in a modal/popup
     */
    const previewReceipt = (data: ReceiptData): void => {
        const receiptHTML = generateReceiptHTML(data);
        const previewWindow = window.open('', '_blank', 'width=350,height=700,scrollbars=yes');

        if (previewWindow) {
            previewWindow.document.write(receiptHTML);
            previewWindow.document.close();
        }
    };

    /**
     * Transform sale data to receipt format
     */
    const transformSaleToReceipt = (saleData: any): ReceiptData => {
        // Generate fallback verification URL if no verification_token exists
        let verificationUrl: string | undefined;

        if (saleData.verification_token) {
            // Use proper verification token if available
            verificationUrl = `${window.location.origin}/receipt/${saleData.verification_token}`;
        } else if (saleData.sale_number || saleData.id) {
            // Fallback: create temporary verification URL using sale_number or ID
            // This will work until proper verification tokens are migrated
            const fallbackToken = saleData.sale_number || `sale-${saleData.id}`;
            verificationUrl = `${window.location.origin}/receipt/temp/${fallbackToken}`;
            console.warn('Using fallback verification URL. Consider running verification token migration.');
        }

        return {
            sale_number: saleData.sale_number || '',
            receipt_number: saleData.receipt_number || `REC${saleData.sale_number}`,
            sale_date: new Date(saleData.sale_date || saleData.created_at).toLocaleString('en-GH', {
                dateStyle: 'medium',
                timeStyle: 'short'
            }),
            cashier_name: saleData.cashier?.name || 'System',
            customer_name: saleData.customer_info?.name,
            items: saleData.items?.map((item: any) => ({
                name: item.product?.name || item.product_name || 'Unknown Product',
                sku: item.product?.sku || item.product_sku || '',
                quantity: item.quantity,
                unit_price: parseFloat(item.unit_price),
                line_total: parseFloat(item.line_total),
                tax_amount: parseFloat(item.tax_amount || 0)
            })) || [],
            subtotal: parseFloat(saleData.subtotal || 0),
            tax_amount: parseFloat(saleData.tax_amount || 0),
            total_amount: parseFloat(saleData.total_amount || 0),
            payment_method: saleData.payment_method || 'cash',
            business_name: 'School Sales System', // This could come from settings
            business_address: '', // This could come from settings
            business_phone: '', // This could come from settings
            verification_url: verificationUrl,
        };
    };

    return {
        // State
        isGenerating,
        isPrinting,

        // Methods
        generateReceiptHTML,
        printReceipt,
        downloadReceiptPDF,
        previewReceipt,
        transformSaleToReceipt,
    };
}