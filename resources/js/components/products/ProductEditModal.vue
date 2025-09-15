<script setup lang="ts">
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { useAlerts } from '@/composables/useAlerts';
import type { Product } from '@/types';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import ProductFormFields from './ProductFormFields.vue';

interface Props {
    open: boolean;
    product?: Product | null;
}

interface Emits {
    'update:open': [open: boolean];
    'product-updated': [product: Product];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();
const { success, error } = useAlerts();

const processing = ref(false);
const errors = ref<Record<string, string>>({});

// Form data
const formData = ref({
    categories: [],
    unitTypes: [],
    brands: [],
    statuses: [],
});

// Initial form state
const getInitialForm = () => ({
    sku: '',
    name: '',
    description: '',
    category_id: '',
    status: 'active',
    unit_price: '',
    unit_type: '',
    reorder_level: '',
    tax_rate: '0',
    weight: '',
    dimensions: {
        length: '',
        width: '',
        height: '',
    },
    color: '',
    brand: '',
    barcode: '',
    image_url: '',
    meta_title: '',
    meta_description: '',
    tags: '',
    is_active: true,
});

const form = ref(getInitialForm());

// Load form data when modal opens
watch(
    () => props.open,
    async (isOpen) => {
        if (isOpen && formData.value.categories.length === 0) {
            try {
                const response = await fetch('/products/form-data');
                const data = await response.json();
                formData.value = data;
            } catch {
                error('Failed to load form data. Please refresh the page and try again.', {
                    position: 'top-center',
                    priority: 'critical',
                    persistent: true,
                });
            }
        }

        if (isOpen && props.product) {
            // Populate form with existing product data
            form.value = {
                sku: props.product.sku || '',
                name: props.product.name || '',
                description: props.product.description || '',
                category_id: props.product.category_id?.toString() || '',
                status: props.product.status || 'active',
                unit_price: props.product.unit_price?.toString() || '',
                unit_type: props.product.unit_type || '',
                reorder_level: props.product.reorder_level?.toString() || '',
                tax_rate: props.product.tax_rate?.toString() || '0',
                weight: props.product.weight?.toString() || '',
                dimensions: {
                    length: props.product.dimensions?.length?.toString() || '',
                    width: props.product.dimensions?.width?.toString() || '',
                    height: props.product.dimensions?.height?.toString() || '',
                },
                color: props.product.color || '',
                brand: props.product.brand || '',
                barcode: props.product.barcode || '',
                image_url: props.product.image_url || '',
                meta_title: props.product.meta_title || '',
                meta_description: props.product.meta_description || '',
                tags: Array.isArray(props.product.tags) ? props.product.tags.join(', ') : (props.product.tags || ''),
                is_active: props.product.is_active !== false,
            };
            errors.value = {};
        } else if (isOpen) {
            // Reset form when opening without product
            form.value = getInitialForm();
            errors.value = {};
        }
    },
);

const updateForm = (newForm: typeof form.value) => {
    form.value = newForm;
};

const handleSubmit = () => {
    if (!props.product?.id) return;

    processing.value = true;
    errors.value = {};

    // Transform tags string to array if needed
    const submitData = {
        ...form.value,
        unit_price: parseFloat(form.value.unit_price) || 0,
        tax_rate: parseFloat(form.value.tax_rate) || 0,
        weight: form.value.weight ? parseFloat(form.value.weight) : null,
        reorder_level: form.value.reorder_level ? parseInt(form.value.reorder_level) : null,
        dimensions: {
            length: form.value.dimensions.length ? parseFloat(form.value.dimensions.length) : null,
            width: form.value.dimensions.width ? parseFloat(form.value.dimensions.width) : null,
            height: form.value.dimensions.height ? parseFloat(form.value.dimensions.height) : null,
        },
        tags: form.value.tags
            ? form.value.tags
                  .split(',')
                  .map((tag) => tag.trim())
                  .filter(Boolean)
            : null,
        _method: 'PUT',
    };

    router.put(`/products/${props.product.id}`, submitData, {
        onSuccess: (page) => {
            const product = page.props.product;
            success(`Product "${product?.name || 'Product'}" has been updated successfully!`, {
                position: 'top-center',
                duration: 4000,
            });
            emit('product-updated', product);
            emit('update:open', false);
        },
        onError: (pageErrors) => {
            errors.value = pageErrors;
            const errorMessages = Object.values(pageErrors).flat();
            error(errorMessages.join(', ') || 'Failed to update product. Please check your input and try again.', {
                position: 'top-center',
                priority: 'high',
                persistent: true,
            });
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};

const handleCancel = () => {
    emit('update:open', false);
    form.value = getInitialForm();
    errors.value = {};
};

const handleOpenChange = (open: boolean) => {
    emit('update:open', open);
};
</script>

<template>
    <Sheet :open="open" @update:open="handleOpenChange">
        <SheetContent class="w-[800px] overflow-y-auto sm:max-w-[800px]">
            <SheetHeader>
                <SheetTitle>Edit Product</SheetTitle>
                <SheetDescription> Update the product information and save your changes. </SheetDescription>
            </SheetHeader>

            <div class="mt-6 px-8">
                <ProductFormFields
                    :form="form"
                    :form-data="formData"
                    :errors="errors"
                    :processing="processing"
                    @update:form="updateForm"
                    @submit="handleSubmit"
                    @cancel="handleCancel"
                />
            </div>
        </SheetContent>
    </Sheet>
</template>