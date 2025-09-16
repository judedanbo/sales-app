<script setup lang="ts">
import ImageUpload from '@/components/ui/ImageUpload.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { useAlerts } from '@/composables/useAlerts';
import { type Product, type ProductVariant } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

interface Props {
    open: boolean;
    product: Product;
    variant?: ProductVariant | null;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'variant-created': [];
    'variant-updated': [];
}>();

const { addAlert } = useAlerts();

const isEditing = computed(() => !!props.variant);
const modalTitle = computed(() => isEditing.value ? 'Edit Product Variant' : 'Create Product Variant');
const submitButtonText = computed(() => isEditing.value ? 'Update Variant' : 'Create Variant');

const form = useForm({
    product_id: props.product.id,
    sku: '',
    name: '',
    size: '',
    color: '',
    material: '',
    unit_price: '',
    cost_price: '',
    weight: '',
    dimensions: {
        length: '',
        width: '',
        height: '',
    },
    image_url: '',
    gallery: [] as string[],
    status: 'active',
    is_default: false,
    sort_order: 0,
    barcode: '',
    attributes: {} as Record<string, any>,
});

// Size options
const sizeOptions = [
    { value: 'XS', label: 'Extra Small (XS)' },
    { value: 'S', label: 'Small (S)' },
    { value: 'M', label: 'Medium (M)' },
    { value: 'L', label: 'Large (L)' },
    { value: 'XL', label: 'Extra Large (XL)' },
    { value: 'XXL', label: '2X Large (XXL)' },
    { value: 'XXXL', label: '3X Large (XXXL)' },
];

// Color options
const colorOptions = [
    { value: 'Black', label: 'Black' },
    { value: 'White', label: 'White' },
    { value: 'Red', label: 'Red' },
    { value: 'Blue', label: 'Blue' },
    { value: 'Green', label: 'Green' },
    { value: 'Yellow', label: 'Yellow' },
    { value: 'Orange', label: 'Orange' },
    { value: 'Purple', label: 'Purple' },
    { value: 'Pink', label: 'Pink' },
    { value: 'Brown', label: 'Brown' },
    { value: 'Gray', label: 'Gray' },
    { value: 'Navy', label: 'Navy' },
];

// Material options
const materialOptions = [
    { value: 'Cotton', label: 'Cotton' },
    { value: 'Polyester', label: 'Polyester' },
    { value: 'Wool', label: 'Wool' },
    { value: 'Silk', label: 'Silk' },
    { value: 'Leather', label: 'Leather' },
    { value: 'Plastic', label: 'Plastic' },
    { value: 'Metal', label: 'Metal' },
    { value: 'Wood', label: 'Wood' },
    { value: 'Glass', label: 'Glass' },
    { value: 'Paper', label: 'Paper' },
];

// Watch for variant prop changes to populate form
watch(
    () => props.variant,
    (newVariant) => {
        if (newVariant) {
            form.product_id = newVariant.product_id;
            form.sku = newVariant.sku || '';
            form.name = newVariant.name || '';
            form.size = newVariant.size || '';
            form.color = newVariant.color || '';
            form.material = newVariant.material || '';
            form.unit_price = newVariant.unit_price?.toString() || '';
            form.cost_price = newVariant.cost_price?.toString() || '';
            form.weight = newVariant.weight?.toString() || '';
            form.dimensions = {
                length: newVariant.dimensions?.length?.toString() || '',
                width: newVariant.dimensions?.width?.toString() || '',
                height: newVariant.dimensions?.height?.toString() || '',
            };
            form.image_url = newVariant.image_url || '';
            form.gallery = newVariant.gallery || [];
            form.status = newVariant.status;
            form.is_default = newVariant.is_default;
            form.sort_order = newVariant.sort_order;
            form.barcode = newVariant.barcode || '';
            form.attributes = newVariant.attributes || {};
        } else {
            resetForm();
        }
    },
    { immediate: true }
);

// Watch for open prop changes to reset form when modal opens for creation
watch(
    () => props.open,
    (isOpen) => {
        if (isOpen && !props.variant) {
            resetForm();
        }
    }
);

function resetForm() {
    form.product_id = props.product.id;
    form.sku = '';
    form.name = '';
    form.size = '';
    form.color = '';
    form.material = '';
    form.unit_price = '';
    form.cost_price = '';
    form.weight = '';
    form.dimensions = {
        length: '',
        width: '',
        height: '',
    };
    form.image_url = '';
    form.gallery = [];
    form.status = 'active';
    form.is_default = false;
    form.sort_order = 0;
    form.barcode = '';
    form.attributes = {};
    form.clearErrors();
}

function handleSubmit() {
    const url = isEditing.value
        ? `/products/${props.product.id}/variants/${props.variant!.id}`
        : `/products/${props.product.id}/variants`;

    const method = isEditing.value ? 'put' : 'post';

    form[method](url, {
        preserveScroll: true,
        onSuccess: () => {
            addAlert(
                `Variant ${isEditing.value ? 'updated' : 'created'} successfully`,
                'success',
                { title: 'Success' }
            );
            emit('update:open', false);
            if (isEditing.value) {
                emit('variant-updated');
            } else {
                emit('variant-created');
            }
        },
        onError: (errors) => {
            const errorMessage = Object.values(errors).flat().join(', ');
            addAlert(
                errorMessage || `Failed to ${isEditing.value ? 'update' : 'create'} variant`,
                'destructive',
                { title: 'Error' }
            );
        },
    });
}

function handleCancel() {
    emit('update:open', false);
}

function handleImageUpload(imageUrl: string) {
    form.image_url = imageUrl;
}
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>{{ modalTitle }}</DialogTitle>
                <DialogDescription>
                    {{ isEditing ? 'Update the variant details below.' : 'Create a new variant for this product.' }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="handleSubmit" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Basic Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium">Basic Information</h3>

                        <div class="space-y-2">
                            <Label for="name">Variant Name (Optional)</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                placeholder="e.g., Premium Edition"
                                :error="form.errors.name"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="sku">SKU (Optional)</Label>
                            <Input
                                id="sku"
                                v-model="form.sku"
                                placeholder="Auto-generated if empty"
                                :error="form.errors.sku"
                            />
                            <p class="text-sm text-gray-500">Leave empty to auto-generate based on product SKU and attributes</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="barcode">Barcode (Optional)</Label>
                            <Input
                                id="barcode"
                                v-model="form.barcode"
                                placeholder="e.g., 1234567890123"
                                :error="form.errors.barcode"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label for="status">Status</Label>
                            <Select v-model="form.status">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="inactive">Inactive</SelectItem>
                                    <SelectItem value="discontinued">Discontinued</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="flex items-center space-x-2">
                            <Switch
                                id="is_default"
                                v-model:checked="form.is_default"
                            />
                            <Label for="is_default">Set as default variant</Label>
                        </div>
                    </div>

                    <!-- Variant Attributes -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium">Variant Attributes</h3>

                        <div class="space-y-2">
                            <Label for="size">Size</Label>
                            <Select v-model="form.size">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select size" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">No specific size</SelectItem>
                                    <SelectItem v-for="size in sizeOptions" :key="size.value" :value="size.value">
                                        {{ size.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-2">
                            <Label for="color">Color</Label>
                            <Select v-model="form.color">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select color" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">No specific color</SelectItem>
                                    <SelectItem v-for="color in colorOptions" :key="color.value" :value="color.value">
                                        {{ color.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-2">
                            <Label for="material">Material</Label>
                            <Select v-model="form.material">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select material" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">No specific material</SelectItem>
                                    <SelectItem v-for="material in materialOptions" :key="material.value" :value="material.value">
                                        {{ material.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Pricing Information -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium">Pricing</h3>

                        <div class="space-y-2">
                            <Label for="unit_price">Unit Price (GH₵)</Label>
                            <Input
                                id="unit_price"
                                v-model="form.unit_price"
                                type="number"
                                step="0.01"
                                placeholder="0.00"
                                :error="form.errors.unit_price"
                            />
                            <p class="text-sm text-gray-500">Leave empty to use product's base price</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="cost_price">Cost Price (GH₵)</Label>
                            <Input
                                id="cost_price"
                                v-model="form.cost_price"
                                type="number"
                                step="0.01"
                                placeholder="0.00"
                                :error="form.errors.cost_price"
                            />
                        </div>
                    </div>

                    <!-- Physical Properties -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium">Physical Properties</h3>

                        <div class="space-y-2">
                            <Label for="weight">Weight (kg)</Label>
                            <Input
                                id="weight"
                                v-model="form.weight"
                                type="number"
                                step="0.001"
                                placeholder="0.000"
                                :error="form.errors.weight"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label>Dimensions (cm)</Label>
                            <div class="grid grid-cols-3 gap-2">
                                <Input
                                    v-model="form.dimensions.length"
                                    type="number"
                                    step="0.1"
                                    placeholder="Length"
                                    :error="form.errors['dimensions.length']"
                                />
                                <Input
                                    v-model="form.dimensions.width"
                                    type="number"
                                    step="0.1"
                                    placeholder="Width"
                                    :error="form.errors['dimensions.width']"
                                />
                                <Input
                                    v-model="form.dimensions.height"
                                    type="number"
                                    step="0.1"
                                    placeholder="Height"
                                    :error="form.errors['dimensions.height']"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Images -->
                <div class="space-y-4">
                    <h3 class="text-lg font-medium">Images</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <Label>Primary Image</Label>
                            <ImageUpload
                                :current-image="form.image_url"
                                :upload-url="`/products/${product.id}/variants/upload-image`"
                                alt="Variant image"
                                @uploaded="handleImageUpload"
                            />
                        </div>

                        <div class="space-y-2">
                            <Label>Gallery Images</Label>
                            <p class="text-sm text-gray-500">Gallery upload functionality coming soon</p>
                        </div>
                    </div>
                </div>
            </form>

            <DialogFooter>
                <Button type="button" variant="outline" @click="handleCancel">
                    Cancel
                </Button>
                <Button
                    type="button"
                    @click="handleSubmit"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Saving...' : submitButtonText }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>