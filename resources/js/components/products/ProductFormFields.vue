<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import Switch from '@/components/ui/switch.vue';
import { Textarea } from '@/components/ui/textarea';
import { DollarSign, Globe, Image, Package, Ruler, Tag } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    form: {
        sku: string;
        name: string;
        description: string;
        category_id: string;
        status: string;
        unit_price: string;
        unit_type: string;
        reorder_level: string;
        tax_rate: string;
        weight: string;
        dimensions: {
            length: string;
            width: string;
            height: string;
        };
        color: string;
        brand: string;
        barcode: string;
        image_url: string;
        meta_title: string;
        meta_description: string;
        tags: string;
        is_active: boolean;
    };
    formData: {
        categories: Array<{ value: number; label: string }>;
        unitTypes: Array<{ value: string; label: string }>;
        brands: Array<{ value: string; label: string }>;
        skuPatterns: Array<{ value: string; label: string }>;
        statuses: Array<{ value: string; label: string }>;
    };
    errors: Record<string, string>;
    processing?: boolean;
}

interface Emits {
    'update:form': [form: Props['form']];
    submit: [];
    cancel: [];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// SKU pattern handling
const selectedSkuPattern = ref('auto');
const showCustomSkuInput = ref(false);

// Watch for SKU pattern changes
watch(selectedSkuPattern, (newPattern) => {
    if (newPattern === 'custom') {
        showCustomSkuInput.value = true;
    } else {
        showCustomSkuInput.value = false;
        if (newPattern === 'auto') {
            // Clear SKU to allow auto-generation
            updateForm('sku', '');
        } else if (newPattern !== 'auto') {
            // Set prefix pattern
            updateForm('sku', newPattern);
        }
    }
});

const updateForm = (field: keyof Props['form'] | string, value: any) => {
    if (field.includes('.')) {
        // Handle nested properties like dimensions.length
        const [parent, child] = field.split('.');
        emit('update:form', {
            ...props.form,
            [parent]: {
                ...props.form[parent as keyof Props['form']],
                [child]: value,
            },
        });
    } else {
        emit('update:form', {
            ...props.form,
            [field]: value,
        });
    }
};

const getFieldError = (field: string) => {
    return props.errors[field];
};

const hasError = (field: string) => {
    return !!props.errors[field];
};

// Convert tax_rate from decimal (0-1) to percentage (0-100) for display
const displayTaxRate = computed(() => {
    if (!props.form.tax_rate) return '';
    const decimal = parseFloat(props.form.tax_rate);
    // If value is already a percentage (> 1), use as is
    // If value is a decimal (0-1), convert to percentage
    return decimal > 1 ? decimal.toString() : (decimal * 100).toString();
});

// Handle tax rate input changes
const handleTaxRateChange = (value: string) => {
    // Store as percentage for internal form state (will be converted on submit)
    updateForm('tax_rate', value);
};

// Handle form submission with tax_rate conversion
const handleSubmit = () => {
    // Convert tax_rate from percentage (0-100) to decimal (0-1)
    const formData = { ...props.form };
    if (formData.tax_rate) {
        const taxRatePercentage = parseFloat(formData.tax_rate);
        formData.tax_rate = (taxRatePercentage / 100).toString();
    }
    
    // Update the form with converted tax_rate before submitting
    emit('update:form', formData);
    
    // Emit submit event
    emit('submit');
};
</script>

<template>
    <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Basic Information -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Package class="h-4 w-4" />
                    Basic Information
                </CardTitle>
                <CardDescription>Essential product details and identification</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <!-- Product Name -->
                    <div class="space-y-2">
                        <Label for="name" :class="{ 'text-destructive': hasError('name') }"> Product Name * </Label>
                        <Input
                            id="name"
                            :model-value="form.name"
                            @update:model-value="updateForm('name', $event)"
                            placeholder="Enter product name"
                            :class="{ 'border-destructive': hasError('name') }"
                            required
                        />
                        <p v-if="hasError('name')" class="text-sm text-destructive">
                            {{ getFieldError('name') }}
                        </p>
                    </div>

                    <!-- SKU -->
                    <div class="space-y-2">
                        <Label for="sku-pattern" :class="{ 'text-destructive': hasError('sku') }"> SKU Pattern * </Label>
                        <Select v-model="selectedSkuPattern">
                            <SelectTrigger :class="{ 'border-destructive': hasError('sku') }">
                                <SelectValue placeholder="Select SKU pattern" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="pattern in formData.skuPatterns" :key="pattern.value" :value="pattern.value">
                                    {{ pattern.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        
                        <!-- Custom SKU Input (shown when 'custom' is selected) -->
                        <div v-if="showCustomSkuInput" class="mt-2">
                            <Input
                                id="custom-sku"
                                :model-value="form.sku"
                                @update:model-value="updateForm('sku', $event)"
                                placeholder="Enter custom SKU"
                                :class="{ 'border-destructive': hasError('sku') }"
                                required
                            />
                        </div>
                        
                        <!-- SKU Preview (shown for pattern selections) -->
                        <div v-else-if="selectedSkuPattern !== 'auto' && form.sku" class="mt-2">
                            <p class="text-sm text-muted-foreground">
                                Preview: <span class="font-mono">{{ form.sku }}...</span>
                            </p>
                        </div>
                        
                        <p v-if="hasError('sku')" class="text-sm text-destructive">
                            {{ getFieldError('sku') }}
                        </p>
                    </div>
                </div>

                <!-- Description -->
                <div class="space-y-2">
                    <Label for="description">Description</Label>
                    <Textarea
                        id="description"
                        :model-value="form.description"
                        @update:model-value="updateForm('description', $event)"
                        placeholder="Enter product description"
                        rows="3"
                    />
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <!-- Category -->
                    <div class="space-y-2">
                        <Label for="category_id" :class="{ 'text-destructive': hasError('category_id') }"> Category * </Label>
                        <Select :model-value="form.category_id" @update:model-value="updateForm('category_id', $event)">
                            <SelectTrigger :class="{ 'border-destructive': hasError('category_id') }">
                                <SelectValue placeholder="Select category" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="category in formData.categories" :key="category.value" :value="category.value.toString()">
                                    {{ category.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="hasError('category_id')" class="text-sm text-destructive">
                            {{ getFieldError('category_id') }}
                        </p>
                    </div>

                    <!-- Status -->
                    <div class="space-y-2">
                        <Label for="status" :class="{ 'text-destructive': hasError('status') }"> Status * </Label>
                        <Select :model-value="form.status" @update:model-value="updateForm('status', $event)">
                            <SelectTrigger :class="{ 'border-destructive': hasError('status') }">
                                <SelectValue placeholder="Select status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="active">Active</SelectItem>
                                <SelectItem value="inactive">Inactive</SelectItem>
                                <SelectItem value="discontinued">Discontinued</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="hasError('status')" class="text-sm text-destructive">
                            {{ getFieldError('status') }}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Pricing and Units -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <DollarSign class="h-4 w-4" />
                    Pricing & Units
                </CardTitle>
                <CardDescription>Price information and unit specifications</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid gap-4 md:grid-cols-3">
                    <!-- Unit Price -->
                    <div class="space-y-2">
                        <Label for="unit_price" :class="{ 'text-destructive': hasError('unit_price') }"> Unit Price (GHS) * </Label>
                        <Input
                            id="unit_price"
                            type="number"
                            step="0.01"
                            min="0"
                            :model-value="form.unit_price"
                            @update:model-value="updateForm('unit_price', $event)"
                            placeholder="0.00"
                            :class="{ 'border-destructive': hasError('unit_price') }"
                            required
                        />
                        <p v-if="hasError('unit_price')" class="text-sm text-destructive">
                            {{ getFieldError('unit_price') }}
                        </p>
                    </div>

                    <!-- Unit Type -->
                    <div class="space-y-2">
                        <Label for="unit_type" :class="{ 'text-destructive': hasError('unit_type') }"> Unit Type * </Label>
                        <Select :model-value="form.unit_type" @update:model-value="updateForm('unit_type', $event)">
                            <SelectTrigger :class="{ 'border-destructive': hasError('unit_type') }">
                                <SelectValue placeholder="Select unit type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="unitType in formData.unitTypes" :key="unitType.value" :value="unitType.value">
                                    {{ unitType.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="hasError('unit_type')" class="text-sm text-destructive">
                            {{ getFieldError('unit_type') }}
                        </p>
                    </div>

                    <!-- Tax Rate -->
                    <div class="space-y-2">
                        <Label for="tax_rate">Tax Rate (%)</Label>
                        <Input
                            id="tax_rate"
                            type="number"
                            step="0.01"
                            min="0"
                            max="100"
                            :model-value="displayTaxRate"
                            @update:model-value="handleTaxRateChange"
                            placeholder="0.00"
                        />
                        <p class="text-sm text-muted-foreground">
                            Enter as percentage (e.g., 18 for 18%)
                        </p>
                    </div>
                </div>

                <!-- Reorder Level -->
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="space-y-2">
                        <Label for="reorder_level">Reorder Level</Label>
                        <Input
                            id="reorder_level"
                            type="number"
                            min="0"
                            :model-value="form.reorder_level"
                            @update:model-value="updateForm('reorder_level', $event)"
                            placeholder="Minimum stock level"
                        />
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Physical Properties -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Ruler class="h-4 w-4" />
                    Physical Properties
                </CardTitle>
                <CardDescription>Weight, dimensions, and physical characteristics</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <!-- Weight -->
                    <div class="space-y-2">
                        <Label for="weight">Weight (kg)</Label>
                        <Input
                            id="weight"
                            type="number"
                            step="0.001"
                            min="0"
                            :model-value="form.weight"
                            @update:model-value="updateForm('weight', $event)"
                            placeholder="0.000"
                        />
                    </div>

                    <!-- Color -->
                    <div class="space-y-2">
                        <Label for="color">Color</Label>
                        <Input id="color" :model-value="form.color" @update:model-value="updateForm('color', $event)" placeholder="Enter color" />
                    </div>
                </div>

                <!-- Dimensions -->
                <div>
                    <Label class="text-sm font-medium">Dimensions (cm)</Label>
                    <div class="mt-2 grid gap-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="length" class="text-sm text-muted-foreground">Length</Label>
                            <Input
                                id="length"
                                type="number"
                                step="0.1"
                                min="0"
                                :model-value="form.dimensions.length"
                                @update:model-value="updateForm('dimensions.length', $event)"
                                placeholder="0.0"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="width" class="text-sm text-muted-foreground">Width</Label>
                            <Input
                                id="width"
                                type="number"
                                step="0.1"
                                min="0"
                                :model-value="form.dimensions.width"
                                @update:model-value="updateForm('dimensions.width', $event)"
                                placeholder="0.0"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="height" class="text-sm text-muted-foreground">Height</Label>
                            <Input
                                id="height"
                                type="number"
                                step="0.1"
                                min="0"
                                :model-value="form.dimensions.height"
                                @update:model-value="updateForm('dimensions.height', $event)"
                                placeholder="0.0"
                            />
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Brand and Identification -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Tag class="h-4 w-4" />
                    Brand & Identification
                </CardTitle>
                <CardDescription>Brand information and product identification</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <!-- Brand -->
                    <div class="space-y-2">
                        <Label for="brand">Brand</Label>
                        <Input
                            id="brand"
                            :model-value="form.brand"
                            @update:model-value="updateForm('brand', $event)"
                            placeholder="Enter brand name"
                        />
                    </div>

                    <!-- Barcode -->
                    <div class="space-y-2">
                        <Label for="barcode">Barcode</Label>
                        <Input
                            id="barcode"
                            :model-value="form.barcode"
                            @update:model-value="updateForm('barcode', $event)"
                            placeholder="Enter barcode"
                        />
                    </div>
                </div>

                <!-- Tags -->
                <div class="space-y-2">
                    <Label for="tags">Tags</Label>
                    <Input
                        id="tags"
                        :model-value="form.tags"
                        @update:model-value="updateForm('tags', $event)"
                        placeholder="Enter tags separated by commas"
                    />
                    <p class="text-sm text-muted-foreground">Separate multiple tags with commas</p>
                </div>
            </CardContent>
        </Card>

        <!-- Images and Media -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Image class="h-4 w-4" />
                    Images & Media
                </CardTitle>
                <CardDescription>Product images and visual content</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <!-- Image URL -->
                <div class="space-y-2">
                    <Label for="image_url">Image URL</Label>
                    <Input
                        id="image_url"
                        type="url"
                        :model-value="form.image_url"
                        @update:model-value="updateForm('image_url', $event)"
                        placeholder="https://example.com/image.jpg"
                    />
                </div>
            </CardContent>
        </Card>

        <!-- SEO and Metadata -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Globe class="h-4 w-4" />
                    SEO & Metadata
                </CardTitle>
                <CardDescription>Search engine optimization and metadata</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <!-- Meta Title -->
                <div class="space-y-2">
                    <Label for="meta_title">Meta Title</Label>
                    <Input
                        id="meta_title"
                        :model-value="form.meta_title"
                        @update:model-value="updateForm('meta_title', $event)"
                        placeholder="SEO-friendly title"
                    />
                </div>

                <!-- Meta Description -->
                <div class="space-y-2">
                    <Label for="meta_description">Meta Description</Label>
                    <Textarea
                        id="meta_description"
                        :model-value="form.meta_description"
                        @update:model-value="updateForm('meta_description', $event)"
                        placeholder="SEO description for search engines"
                        rows="2"
                    />
                </div>
            </CardContent>
        </Card>

        <!-- Status -->
        <div class="flex items-center space-x-2">
            <Switch id="is_active" :checked="form.is_active" @update:checked="updateForm('is_active', $event)" />
            <Label for="is_active">Active Product</Label>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3 pt-6">
            <Button type="button" variant="outline" @click="emit('cancel')"> Cancel </Button>
            <Button type="submit" :disabled="processing">
                <span v-if="processing">Creating Product...</span>
                <span v-else>Create Product</span>
            </Button>
        </div>
    </form>
</template>
