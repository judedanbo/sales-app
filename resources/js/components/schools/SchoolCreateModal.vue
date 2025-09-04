<script setup lang="ts">
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import SchoolFormFields from './SchoolFormFields.vue';

interface Props {
    open: boolean;
}

interface Emits {
    'update:open': [open: boolean];
    'school-created': [school: any];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

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

// Load form data when modal opens
watch(
    () => props.open,
    async (isOpen) => {
        if (isOpen && formData.value.schoolTypes.length === 0) {
            try {
                const response = await fetch('/schools/form-data');
                const data = await response.json();
                formData.value = data;
            } catch (error) {
                console.error('Failed to load form data:', error);
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

    router.post('/schools', form.value, {
        onSuccess: (page) => {
            emit('school-created', page.props.school);
            emit('update:open', false);
            form.value = { ...initialForm };
        },
        onError: (pageErrors) => {
            errors.value = pageErrors;
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
                <SheetTitle>Add New School</SheetTitle>
                <SheetDescription> Create a new school record with all the necessary information. </SheetDescription>
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
