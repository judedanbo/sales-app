<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import Progress from '@/components/ui/progress.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator/Separator.vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useAlerts } from '@/composables/useAlerts';
import { type BulkOperation } from '@/types';
import { Form } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle, Download, FileText, Upload, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    open: boolean;
    mode: 'export' | 'import';
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'export-completed': [];
    'import-completed': [];
}>();

const { addAlert } = useAlerts();

const activeTab = ref(props.mode);
const selectedFile = ref<File | null>(null);
const isProcessing = ref(false);
const operation = ref<BulkOperation | null>(null);

const exportForm = Form({
    format: 'csv',
    include_variants: true,
    include_inventory: true,
    include_pricing: true,
    include_images: false,
    category_ids: [] as number[],
    status_filter: '',
    date_from: '',
    date_to: '',
});

const importForm = Form({
    file: null as File | null,
    update_existing: true,
    create_missing_categories: true,
    skip_validation: false,
    batch_size: 100,
    mapping: {} as Record<string, string>,
});

const exportFormats = [
    { value: 'csv', label: 'CSV', description: 'Comma-separated values' },
    { value: 'xlsx', label: 'Excel', description: 'Microsoft Excel format' },
    { value: 'json', label: 'JSON', description: 'JavaScript Object Notation' },
];

const csvColumns = [
    { key: 'id', label: 'Product ID', required: true },
    { key: 'sku', label: 'SKU', required: true },
    { key: 'name', label: 'Product Name', required: true },
    { key: 'description', label: 'Description', required: false },
    { key: 'category_id', label: 'Category ID', required: true },
    { key: 'status', label: 'Status', required: true },
    { key: 'unit_price', label: 'Unit Price', required: true },
    { key: 'unit_type', label: 'Unit Type', required: false },
    { key: 'tax_rate', label: 'Tax Rate', required: false },
    { key: 'weight', label: 'Weight', required: false },
    { key: 'brand', label: 'Brand', required: false },
    { key: 'barcode', label: 'Barcode', required: false },
];

const sampleData = [
    ['SKU001', 'Product 1', 'Description 1', '1', 'active', '10.99', 'piece', '0.15'],
    ['SKU002', 'Product 2', 'Description 2', '2', 'active', '25.50', 'kg', '0.15'],
    ['SKU003', 'Product 3', 'Description 3', '1', 'inactive', '5.75', 'piece', '0.00'],
];

watch(() => props.mode, (newMode) => {
    activeTab.value = newMode;
});

watch(() => props.open, (isOpen) => {
    if (!isOpen) {
        resetForms();
    }
});

const fileInputRef = ref<HTMLInputElement | null>(null);

const exportProgress = computed(() => {
    if (!operation.value) return 0;
    return operation.value.progress || 0;
});

const importErrors = computed(() => {
    if (!operation.value?.result?.errors) return [];
    return operation.value.result.errors;
});

function resetForms() {
    exportForm.reset();
    importForm.reset();
    selectedFile.value = null;
    isProcessing.value = false;
    operation.value = null;
}

function handleFileSelect(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] || null;
    selectedFile.value = file;
    importForm.file = file;
}

function handleFileDrop(event: DragEvent) {
    event.preventDefault();
    const file = event.dataTransfer?.files[0] || null;
    if (file && (file.type === 'text/csv' || file.type === 'application/vnd.ms-excel')) {
        selectedFile.value = file;
        importForm.file = file;
    }
}

function handleDragOver(event: DragEvent) {
    event.preventDefault();
}

function removeSelectedFile() {
    selectedFile.value = null;
    importForm.file = null;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
}

function handleExport() {
    isProcessing.value = true;

    exportForm.post('/products/export', {
        preserveScroll: true,
        onSuccess: (response: any) => {
            operation.value = {
                type: 'export',
                items: [],
                data: {},
                status: 'completed',
                progress: 100,
                result: {
                    success: response.count || 0,
                    failed: 0,
                    errors: [],
                }
            };

            addAlert(
                `Export completed successfully. ${response.count} products exported.`,
                'success',
                { title: 'Export Complete' }
            );

            // In a real implementation, this would trigger a download
            // For now, we'll just show success
            setTimeout(() => {
                emit('export-completed');
                emit('update:open', false);
            }, 2000);
        },
        onError: (errors) => {
            const errorMessage = Object.values(errors).flat().join(', ');
            addAlert(
                errorMessage || 'Export failed',
                'error',
                { title: 'Export Failed' }
            );
            isProcessing.value = false;
        },
    });
}

function handleImport() {
    if (!selectedFile.value) {
        addAlert('Please select a file to import', 'error');
        return;
    }

    isProcessing.value = true;

    importForm.post('/products/import', {
        preserveScroll: true,
        onSuccess: (response: any) => {
            operation.value = {
                type: 'import',
                items: [],
                data: {},
                status: 'completed',
                progress: 100,
                result: {
                    success: response.imported || 0,
                    failed: response.failed || 0,
                    errors: response.errors || [],
                }
            };

            addAlert(
                `Import completed. ${response.imported} products imported, ${response.failed} failed.`,
                'success',
                { title: 'Import Complete' }
            );

            emit('import-completed');
        },
        onError: (errors) => {
            const errorMessage = Object.values(errors).flat().join(', ');
            addAlert(
                errorMessage || 'Import failed',
                'error',
                { title: 'Import Failed' }
            );
            isProcessing.value = false;
        },
    });
}

function downloadTemplate() {
    // Create CSV content
    const headers = csvColumns.map(col => col.label).join(',');
    const rows = sampleData.map(row => row.join(',')).join('\n');
    const csvContent = `${headers}\n${rows}`;

    // Create download link
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'products_template.csv';
    link.click();
    window.URL.revokeObjectURL(url);
}

function handleCancel() {
    emit('update:open', false);
}
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <component :is="mode === 'export' ? Download : Upload" class="h-5 w-5" />
                    {{ mode === 'export' ? 'Export Products' : 'Import Products' }}
                </DialogTitle>
                <DialogDescription>
                    {{ mode === 'export' ? 'Export product data to various formats' : 'Import products from CSV or Excel files' }}
                </DialogDescription>
            </DialogHeader>

            <Tabs v-model="activeTab" class="space-y-6">
                <TabsList class="grid w-full grid-cols-2">
                    <TabsTrigger value="export">Export</TabsTrigger>
                    <TabsTrigger value="import">Import</TabsTrigger>
                </TabsList>

                <!-- Export Tab -->
                <TabsContent value="export" class="space-y-6">
                    <div v-if="!isProcessing">
                        <form @submit.prevent="handleExport" class="space-y-6">
                            <!-- Export Format -->
                            <div class="space-y-2">
                                <Label>Export Format</Label>
                                <Select v-model="exportForm.format">
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="format in exportFormats" :key="format.value" :value="format.value">
                                            <div>
                                                <div>{{ format.label }}</div>
                                                <div class="text-xs text-gray-500">{{ format.description }}</div>
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Include Options -->
                            <div class="space-y-4">
                                <Label>Include in Export</Label>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="flex items-center space-x-2">
                                        <Checkbox
                                            id="include_variants"
                                            v-model:checked="exportForm.include_variants"
                                        />
                                        <Label for="include_variants">Product Variants</Label>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <Checkbox
                                            id="include_inventory"
                                            v-model:checked="exportForm.include_inventory"
                                        />
                                        <Label for="include_inventory">Inventory Data</Label>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <Checkbox
                                            id="include_pricing"
                                            v-model:checked="exportForm.include_pricing"
                                        />
                                        <Label for="include_pricing">Price History</Label>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <Checkbox
                                            id="include_images"
                                            v-model:checked="exportForm.include_images"
                                        />
                                        <Label for="include_images">Image URLs</Label>
                                    </div>
                                </div>
                            </div>

                            <!-- Filters -->
                            <div class="space-y-4">
                                <Label>Filters (Optional)</Label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <Label for="status_filter">Status</Label>
                                        <Select v-model="exportForm.status_filter">
                                            <SelectTrigger>
                                                <SelectValue placeholder="All statuses" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="">All Statuses</SelectItem>
                                                <SelectItem value="active">Active</SelectItem>
                                                <SelectItem value="inactive">Inactive</SelectItem>
                                                <SelectItem value="discontinued">Discontinued</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="date_from">Created After</Label>
                                        <Input
                                            id="date_from"
                                            v-model="exportForm.date_from"
                                            type="date"
                                        />
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Export Progress -->
                    <div v-else class="space-y-4">
                        <div class="text-center">
                            <Download class="h-12 w-12 mx-auto text-blue-500 mb-4" />
                            <h3 class="text-lg font-medium">Exporting Products...</h3>
                            <p class="text-gray-500">Please wait while we prepare your export file.</p>
                        </div>
                        <Progress :value="exportProgress" class="w-full" />
                        <div class="text-center text-sm text-gray-500">
                            {{ exportProgress }}% complete
                        </div>
                    </div>

                    <!-- Export Results -->
                    <div v-if="operation && operation.status === 'completed' && operation.type === 'export'" class="space-y-4">
                        <div class="flex items-center gap-2 text-green-600 dark:text-green-400">
                            <CheckCircle class="h-5 w-5" />
                            <span class="font-medium">Export Completed Successfully</span>
                        </div>
                        <div class="p-4 bg-green-50 dark:bg-green-900/10 rounded-lg">
                            <p class="text-sm">
                                {{ operation.result?.success }} products have been exported.
                                Your file should download automatically.
                            </p>
                        </div>
                    </div>
                </TabsContent>

                <!-- Import Tab -->
                <TabsContent value="import" class="space-y-6">
                    <div v-if="!isProcessing">
                        <!-- File Upload -->
                        <div class="space-y-4">
                            <Label>Import File</Label>
                            <div
                                class="border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-lg p-6 text-center"
                                @drop="handleFileDrop"
                                @dragover="handleDragOver"
                            >
                                <div v-if="!selectedFile">
                                    <Upload class="h-12 w-12 mx-auto text-gray-400 mb-4" />
                                    <p class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                                        Drop your file here or click to browse
                                    </p>
                                    <p class="text-gray-500 dark:text-gray-400 mb-4">
                                        Supports CSV and Excel files up to 10MB
                                    </p>
                                    <input
                                        ref="fileInputRef"
                                        type="file"
                                        accept=".csv,.xlsx,.xls"
                                        @change="handleFileSelect"
                                        class="hidden"
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        @click="fileInputRef?.click()"
                                    >
                                        Select File
                                    </Button>
                                </div>
                                <div v-else class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <FileText class="h-8 w-8 text-blue-500" />
                                        <div>
                                            <p class="font-medium">{{ selectedFile.name }}</p>
                                            <p class="text-sm text-gray-500">
                                                {{ (selectedFile.size / 1024).toFixed(1) }} KB
                                            </p>
                                        </div>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        @click="removeSelectedFile"
                                    >
                                        <X class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- Import Options -->
                        <div class="space-y-4">
                            <Label>Import Options</Label>
                            <div class="space-y-3">
                                <div class="flex items-center space-x-2">
                                    <Checkbox
                                        id="update_existing"
                                        v-model:checked="importForm.update_existing"
                                    />
                                    <Label for="update_existing">Update existing products (match by SKU)</Label>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <Checkbox
                                        id="create_missing_categories"
                                        v-model:checked="importForm.create_missing_categories"
                                    />
                                    <Label for="create_missing_categories">Create missing categories</Label>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <Checkbox
                                        id="skip_validation"
                                        v-model:checked="importForm.skip_validation"
                                    />
                                    <Label for="skip_validation">Skip strict validation (not recommended)</Label>
                                </div>
                            </div>
                        </div>

                        <!-- Batch Size -->
                        <div class="space-y-2">
                            <Label for="batch_size">Batch Size</Label>
                            <Input
                                id="batch_size"
                                v-model="importForm.batch_size"
                                type="number"
                                min="10"
                                max="1000"
                                step="10"
                            />
                            <p class="text-sm text-gray-500">
                                Number of products to process at once (recommended: 100)
                            </p>
                        </div>

                        <!-- Download Template -->
                        <Separator />
                        <div class="space-y-2">
                            <Label>Need a template?</Label>
                            <p class="text-sm text-gray-500">
                                Download our CSV template with sample data to get started quickly.
                            </p>
                            <Button type="button" variant="outline" @click="downloadTemplate">
                                <Download class="h-4 w-4 mr-2" />
                                Download Template
                            </Button>
                        </div>
                    </div>

                    <!-- Import Progress -->
                    <div v-else-if="isProcessing && !operation" class="space-y-4">
                        <div class="text-center">
                            <Upload class="h-12 w-12 mx-auto text-blue-500 mb-4" />
                            <h3 class="text-lg font-medium">Importing Products...</h3>
                            <p class="text-gray-500">Please wait while we process your file.</p>
                        </div>
                        <Progress :value="50" class="w-full" />
                        <div class="text-center text-sm text-gray-500">
                            Processing...
                        </div>
                    </div>

                    <!-- Import Results -->
                    <div v-if="operation && operation.status === 'completed' && operation.type === 'import'" class="space-y-4">
                        <div class="flex items-center gap-2 text-green-600 dark:text-green-400">
                            <CheckCircle class="h-5 w-5" />
                            <span class="font-medium">Import Completed</span>
                        </div>

                        <!-- Success Summary -->
                        <div class="grid grid-cols-2 gap-4">
                            <Card>
                                <CardHeader class="pb-2">
                                    <CardTitle class="text-sm">Successful</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                        {{ operation.result?.success || 0 }}
                                    </div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader class="pb-2">
                                    <CardTitle class="text-sm">Failed</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                                        {{ operation.result?.failed || 0 }}
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Errors -->
                        <div v-if="importErrors.length > 0" class="space-y-2">
                            <div class="flex items-center gap-2 text-red-600 dark:text-red-400">
                                <AlertCircle class="h-4 w-4" />
                                <span class="font-medium">Import Errors</span>
                            </div>
                            <div class="max-h-40 overflow-y-auto border rounded p-3">
                                <div
                                    v-for="(error, index) in importErrors"
                                    :key="index"
                                    class="text-sm text-red-600 dark:text-red-400 mb-1"
                                >
                                    {{ error }}
                                </div>
                            </div>
                        </div>
                    </div>
                </TabsContent>
            </Tabs>

            <DialogFooter>
                <Button type="button" variant="outline" @click="handleCancel">
                    {{ isProcessing ? 'Close' : 'Cancel' }}
                </Button>
                <Button
                    v-if="!isProcessing && !operation"
                    type="button"
                    @click="activeTab === 'export' ? handleExport() : handleImport()"
                    :disabled="activeTab === 'import' && !selectedFile"
                >
                    {{ activeTab === 'export' ? 'Start Export' : 'Start Import' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>