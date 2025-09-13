<script setup lang="ts">
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { useAlerts } from '@/composables/useAlerts';
import type { School } from '@/types';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import SchoolFormFields from './SchoolFormFields.vue';

const { addAlert } = useAlerts();

interface Props {
    open: boolean;
    school?: School | null;
}

interface Emits {
    'update:open': [open: boolean];
    'school-updated': [school: any];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();
const { success, error } = useAlerts();

const processing = ref(false);
const errors = ref<Record<string, string>>({});

// Form data
const formData = ref({
    schoolTypes: [],
    boardAffiliations: [],
    mediumOfInstructions: [],
});

// Initial form state
const initialForm = {
    school_name: '',
    school_code: '',
    school_type: '',
    board_affiliation: '',
    established_date: '',
    // principal_name: '',
    // medium_of_instruction: '',
    // total_students: '',
    // total_teachers: '',
    // website: '',
    // description: '',
    is_active: true,
};

const form = ref({ ...initialForm });

// Load form data and populate form when modal opens
watch(
    () => props.open,
    async (isOpen) => {
        if (isOpen) {
            // Load form data if not already loaded
            if (formData.value.schoolTypes.length === 0) {
                try {
                    const response = await fetch('/schools/form-data');
                    const data = await response.json();
                    formData.value = data;
                } catch (error) {
                    console.error('Failed to load form data:', error);
                }
            }

            // Populate form with school data
            if (props.school) {
                form.value = {
                    school_name: props.school.school_name || '',
                    school_code: props.school.school_code || '',
                    school_type: props.school.school_type || '',
                    board_affiliation: props.school.board_affiliation || '',
                    established_date: props.school.established_date || '',
                    // principal_name: props.school.principal_name || '',
                    // medium_of_instruction: props.school.medium_of_instruction || '',
                    // total_students: props.school.total_students?.toString() || '',
                    // total_teachers: props.school.total_teachers?.toString() || '',
                    // website: props.school.website || '',
                    // description: props.school.description || '',
                    is_active: props.school.is_active ?? true,
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
    if (!props.school) return;

    processing.value = true;
    errors.value = {};

    router.put(`/schools/${props.school.id}`, form.value, {
        onSuccess: (page) => {
            const school = page.props.school;
            showAlert('success', `School "${props.school?.school_name || 'School'}" has been updated successfully!`, 'School Updated');
            emit('school-updated', school);
            emit('update:open', false);
        },
        onError: (pageErrors) => {
            errors.value = pageErrors;
            const errorMessages = Object.values(pageErrors).flat();
            showAlert('error', errorMessages.join(', ') || 'Failed to update school. Please check your input and try again.', 'Update Failed');
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
                <SheetTitle>Edit School</SheetTitle>
                <SheetDescription> Update the school information below. </SheetDescription>
            </SheetHeader>

            <div class="mt-6 px-8">
                <SchoolFormFields
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
