<template>
    <div class="flex space-x-2">
        <Button @click="handlePrint" :disabled="isPrinting" variant="outline">
            <Printer class="h-4 w-4 mr-2" />
            <span v-if="isPrinting">Printing...</span>
            <span v-else>Print Receipt</span>
        </Button>

        <Button @click="handlePreview" variant="outline">
            <Eye class="h-4 w-4 mr-2" />
            Preview
        </Button>

        <Button @click="handleDownload" :disabled="isGenerating" variant="outline">
            <Download class="h-4 w-4 mr-2" />
            <span v-if="isGenerating">Generating...</span>
            <span v-else>Download PDF</span>
        </Button>
    </div>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { useReceipt, type ReceiptData } from '@/composables/useReceipt';
import { Download, Eye, Printer } from 'lucide-vue-next';

interface Props {
    saleData: ReceiptData;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    printed: [];
    error: [message: string];
}>();

// Composables
const { printReceipt, previewReceipt, downloadReceiptPDF, isPrinting, isGenerating } = useReceipt();

// Methods
const handlePrint = async () => {
    try {
        await printReceipt(props.saleData);
        emit('printed');
    } catch (err) {
        const message = err instanceof Error ? err.message : 'Failed to print receipt';
        emit('error', message);
    }
};

const handlePreview = () => {
    try {
        previewReceipt(props.saleData);
    } catch (err) {
        const message = err instanceof Error ? err.message : 'Failed to preview receipt';
        emit('error', message);
    }
};

const handleDownload = async () => {
    try {
        await downloadReceiptPDF(props.saleData);
    } catch (err) {
        const message = err instanceof Error ? err.message : 'Failed to generate PDF';
        emit('error', message);
    }
};
</script>