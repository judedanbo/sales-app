<script setup lang="ts">
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { useAlerts } from '@/composables/useAlerts';
import type { Category } from '@/types';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import CategoryFormFields from './CategoryFormFields.vue';

const { addAlert } = useAlerts();

interface Props {
    open: boolean;
    category?: Category | null;
}

interface Emits {
    'update:open': [open: boolean];
    'category-updated': [category: any];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const processing = ref(false);
const errors = ref<Record<string, string>>({});

// Form data
const formData = ref({
    parentCategories: [],
});

// Initial form state
const initialForm = {
    name: '',
    slug: '',
    description: '',
    parent_id: null as number | null,
    sort_order: 0,
    is_active: true,
    color: '',
    icon: '',
};

const form = ref({ ...initialForm });

// Load form data and populate form when modal opens
watch(
    () => props.open,
    async (isOpen) => {
        if (isOpen) {
            // Load form data if not already loaded
            if (formData.value.parentCategories.length === 0) {
                try {
                    const excludeId = props.category?.id;
                    const url = excludeId ? `/categories/form-data?exclude_id=${excludeId}` : '/categories/form-data';
                    const response = await fetch(url);
                    const data = await response.json();
                    formData.value = data;
                } catch {
                    console.error('Failed to load form data');
                }
            }

            // Populate form with category data
            if (props.category) {
                form.value = {
                    name: props.category.name || '',
                    slug: props.category.slug || '',
                    description: props.category.description || '',
                    parent_id: props.category.parent_id || null,
                    sort_order: props.category.sort_order || 0,
                    is_active: props.category.is_active ?? true,
                    color: props.category.color || '',
                    icon: props.category.icon || '',
                };
            } else {
                form.value = { ...initialForm };
            }

            errors.value = {};
        }
    },
);

const updateForm = (newForm: typeof form.value) => {
    form.value = newForm;
};

const showAlert = (variant: 'success' | 'error', message: string, title: string) => {
    addAlert(message, variant, {
        title,
    });
};

const handleSubmit = () => {
    if (!props.category) return;

    processing.value = true;
    errors.value = {};

    router.put(`/categories/${props.category.id}`, form.value, {
        onSuccess: (page) => {
            const category = page.props.category;
            showAlert('success', `Category "${props.category?.name || 'Category'}" has been updated successfully!`, 'Category Updated');
            emit('category-updated', category);
            emit('update:open', false);
        },
        onError: (pageErrors) => {
            errors.value = pageErrors;
            const errorMessages = Object.values(pageErrors).flat();
            showAlert('error', errorMessages.join(', ') || 'Failed to update category. Please check your input and try again.', 'Update Failed');
        },
        onFinish: () => {
            processing.value = false;
        },
    });
};

const handleCancel = () => {
    emit('update:open', false);
    form.value = { ...initialForm };
    errors.value = {};
};

const handleOpenChange = (open: boolean) => {
    emit('update:open', open);
};
</script>

<template>
    <Sheet :open="open" @update:open="handleOpenChange">
        <SheetContent class="w-[600px] overflow-y-auto sm:max-w-[600px]">
            <SheetHeader>
                <SheetTitle>Edit Category</SheetTitle>
                <SheetDescription> Update the category information below. </SheetDescription>
            </SheetHeader>

            <div class="mt-6 px-8">
                <CategoryFormFields
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
