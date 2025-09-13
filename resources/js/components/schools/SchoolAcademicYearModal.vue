<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import type { School } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { Calendar } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { useAlerts } from '@/composables/useAlerts';

interface Props {
    open: boolean;
    school: School | null;
}

interface Emits {
    (e: 'update:open', value: boolean): void;
    (e: 'academic-year-created'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();
const { success, error } = useAlerts();

const form = useForm({
    year_name: '',
    start_date: '',
    end_date: '',
    is_current: false,
});

const showSuccess = ref(false);
const successMessage = ref('');

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

// Generate default year name based on dates
const generateYearName = () => {
    if (form.start_date && form.end_date) {
        const startYear = new Date(form.start_date).getFullYear();
        const endYear = new Date(form.end_date).getFullYear();
        if (startYear === endYear) {
            form.year_name = `${startYear}`;
        } else {
            form.year_name = `${startYear}-${endYear}`;
        }
    }
};

watch(
    () => [form.start_date, form.end_date],
    () => {
        if (!form.year_name || form.year_name === '') {
            generateYearName();
        }
    },
);

watch(
    () => props.open,
    (newValue) => {
        if (newValue) {
            // Reset form when opening modal
            form.reset();
            form.clearErrors();
            showSuccess.value = false;
            successMessage.value = '';

            // Set default dates (current year)
            const now = new Date();
            const currentYear = now.getFullYear();
            const currentMonth = now.getMonth();

            // If we're past June, assume next academic year
            if (currentMonth >= 6) {
                form.start_date = `${currentYear}-07-01`;
                form.end_date = `${currentYear + 1}-06-30`;
            } else {
                form.start_date = `${currentYear - 1}-07-01`;
                form.end_date = `${currentYear}-06-30`;
            }

            generateYearName();
        }
    },
);

const handleSubmit = () => {
    if (!props.school) return;

    form.post(`/schools/${props.school.id}/academic-years`, {
        preserveScroll: true,
        onSuccess: () => {
            success(`Academic year "${form.year_name}" has been added successfully to ${props.school?.school_name}!`, {
                position: 'top-center',
                duration: 4000
            });
            emit('academic-year-created');
            isOpen.value = false;
            form.reset();
        },
        onError: (errors) => {
            const errorMessages = Object.values(errors).flat();
            error(errorMessages.join(', ') || 'Failed to create academic year. Please check your input and try again.', {
                position: 'top-center',
                priority: 'high',
                persistent: true
            });
        },
    });
};

const close = () => {
    isOpen.value = false;
    form.reset();
    form.clearErrors();
};
</script>

<template>
    <Sheet v-model:open="isOpen">
        <SheetContent class="sm:max-w-[425px]">
            <SheetHeader>
                <div class="flex items-center justify-between">
                    <div>
                        <SheetTitle>
                            <div class="flex items-center gap-2">
                                <Calendar class="h-5 w-5" />
                                Add Academic Year
                            </div>
                        </SheetTitle>
                        <SheetDescription class="mt-1"> Add a new academic year for {{ school?.school_name }} </SheetDescription>
                    </div>
                </div>
            </SheetHeader>

            <form @submit.prevent="handleSubmit" class="mt-6 space-y-4 px-8">
                <div class="space-y-2">
                    <Label for="year_name">Year Name *</Label>
                    <Input id="year_name" v-model="form.year_name" type="text" placeholder="e.g., 2024-2025" required :disabled="form.processing" />
                    <div v-if="form.errors.year_name" class="text-sm text-red-500">
                        {{ form.errors.year_name }}
                    </div>
                    <p class="text-xs text-muted-foreground">Auto-generated from dates, but can be customized</p>
                </div>

                <div class="space-y-2">
                    <Label for="start_date">Start Date *</Label>
                    <Input id="start_date" v-model="form.start_date" type="date" required :disabled="form.processing" />
                    <div v-if="form.errors.start_date" class="text-sm text-red-500">
                        {{ form.errors.start_date }}
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="end_date">End Date *</Label>
                    <Input id="end_date" v-model="form.end_date" type="date" required :min="form.start_date" :disabled="form.processing" />
                    <div v-if="form.errors.end_date" class="text-sm text-red-500">
                        {{ form.errors.end_date }}
                    </div>
                    <p class="text-xs text-muted-foreground">Must be after the start date</p>
                </div>

                <div class="flex items-start space-x-2">
                    <Checkbox id="is_current" v-model:checked="form.is_current" :disabled="form.processing" />
                    <div class="space-y-1">
                        <Label for="is_current" class="cursor-pointer text-sm font-medium"> Set as Current Academic Year </Label>
                        <p class="text-xs text-muted-foreground">This will mark this year as the active academic year for the school</p>
                    </div>
                </div>
                <div v-if="form.errors.is_current" class="text-sm text-red-500">
                    {{ form.errors.is_current }}
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <Button type="button" variant="outline" @click="close" :disabled="form.processing"> Cancel </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Adding...' : 'Add Academic Year' }}
                    </Button>
                </div>
            </form>
        </SheetContent>
    </Sheet>
</template>
