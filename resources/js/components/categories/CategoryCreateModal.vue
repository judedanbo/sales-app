<script setup lang="ts">
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { useAlerts } from '@/composables/useAlerts';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import CategoryFormFields from './CategoryFormFields.vue';

interface Props {
    open: boolean;
}

interface Emits {
    (e: 'update:open', open: boolean): void;
    (e: 'category-created', category: any): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();
const { success, error } = useAlerts();

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

// Load form data when modal opens
watch(
    () => props.open,
    async (isOpen) => {
        if (isOpen && formData.value.parentCategories.length === 0) {
            try {
                const response = await fetch('/categories/form-data');
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

        if (isOpen) {
            // Reset form when modal opens
            form.value = { ...initialForm };
            errors.value = {};
        }
    },
);

const updateForm = (newForm: typeof form.value) => {
    form.value = newForm;
};

const handleSubmit = () => {
    processing.value = true;
    errors.value = {};

    router.post('/categories', form.value, {
        onSuccess: (page: any) => {
            const category = page.props?.category;
            success(`Category "${category?.name || 'New Category'}" has been created successfully!`, {
                position: 'top-center',
                duration: 4000,
            });
            emit('category-created', category);
            emit('update:open', false);
            form.value = { ...initialForm };
        },
        onError: (pageErrors) => {
            errors.value = pageErrors;
            const errorMessages = Object.values(pageErrors).flat();
            error(errorMessages.join(', ') || 'Failed to create category. Please check your input and try again.', {
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
                <SheetTitle>Add New Category</SheetTitle>
                <SheetDescription> Create a new category with all the necessary information. </SheetDescription>
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
