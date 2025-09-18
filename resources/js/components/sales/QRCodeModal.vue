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
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Receipt Verification QR Code
                </h3>
                <Button @click="$emit('close')" variant="ghost" size="sm">
                    <X class="h-4 w-4" />
                </Button>
            </div>

            <!-- Content -->
            <div class="text-center space-y-4">
                <!-- QR Code -->
                <div class="flex justify-center">
                    <div class="bg-white p-4 rounded-lg border">
                        <img
                            :src="qrCodeDataURL"
                            :alt="`QR Code for sale ${saleNumber}`"
                            class="w-48 h-48"
                        />
                    </div>
                </div>

                <!-- Instructions -->
                <div class="space-y-2">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Customers can scan this QR code to verify their receipt online.
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-500">
                        Sale: {{ saleNumber }}
                    </p>
                </div>

                <!-- URL Display -->
                <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                    <Label class="text-xs font-medium text-gray-500 uppercase tracking-wide">
                        Verification URL
                    </Label>
                    <div class="flex items-center space-x-2 mt-1">
                        <input
                            ref="urlInput"
                            :value="verificationUrl"
                            readonly
                            class="flex-1 text-xs font-mono bg-transparent border-none outline-none text-gray-700 dark:text-gray-300"
                        />
                        <Button @click="copyURL" variant="ghost" size="sm">
                            <Copy class="h-3 w-3" />
                        </Button>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex space-x-3 pt-2">
                    <Button @click="downloadQRCode" variant="outline" class="flex-1">
                        <Download class="h-4 w-4 mr-2" />
                        Download QR
                    </Button>
                    <Button @click="openInNewTab" variant="outline" class="flex-1">
                        <ExternalLink class="h-4 w-4 mr-2" />
                        Open Link
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useAlerts } from '@/composables/useAlerts';
import { Copy, Download, ExternalLink, X } from 'lucide-vue-next';

interface Props {
    verificationUrl: string;
    saleNumber: string;
}

const props = defineProps<Props>();

defineEmits<{
    close: [];
}>();

// Composables
const { success, error } = useAlerts();

// Refs
const urlInput = ref<HTMLInputElement>();

// Computed
const qrCodeDataURL = computed(() => {
    const size = 200;
    const encodedUrl = encodeURIComponent(props.verificationUrl);
    return `https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&data=${encodedUrl}&format=png&margin=10`;
});

// Methods
const copyURL = async () => {
    try {
        await navigator.clipboard.writeText(props.verificationUrl);
        success('URL copied to clipboard!');
    } catch (err) {
        // Fallback for older browsers
        if (urlInput.value) {
            urlInput.value.select();
            document.execCommand('copy');
            success('URL copied to clipboard!');
        } else {
            error('Failed to copy URL');
        }
    }
};

const downloadQRCode = async () => {
    try {
        const response = await fetch(qrCodeDataURL.value);
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `qr-code-${props.saleNumber}.png`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
        success('QR code downloaded successfully!');
    } catch (err) {
        error('Failed to download QR code');
    }
};

const openInNewTab = () => {
    window.open(props.verificationUrl, '_blank');
};
</script>

<style scoped>
/* Modal animation */
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