<script setup lang="ts">
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import type { School } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { CheckCircle2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    open: boolean;
    school: School | null;
}

interface Emits {
    (e: 'update:open', value: boolean): void;
    (e: 'class-created'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const form = useForm({
    class_name: '',
    class_code: '',
    grade_level: 1,
    min_age: null as number | null,
    max_age: null as number | null,
    order_sequence: 0,
});

const showSuccess = ref(false);
const successMessage = ref('');

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

watch(
    () => props.open,
    (newValue) => {
        if (newValue) {
            form.reset();
            form.clearErrors();
            showSuccess.value = false;
            successMessage.value = '';
        }
    },
);

const handleSubmit = () => {
    if (!props.school) return;

    form.post(`/schools/${props.school.id}/classes`, {
        preserveScroll: true,
        onSuccess: () => {
            successMessage.value = `Class "${form.class_name}" has been successfully added to ${props.school.school_name}.`;
            showSuccess.value = true;

            // Close the modal after a delay to show the success message
            setTimeout(() => {
                emit('class-created');
                isOpen.value = false;
                form.reset();
                showSuccess.value = false;
            }, 2000);
        },
        onError: (errors) => {
            console.error('Form errors:', errors);
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
                        <SheetTitle>Add Class</SheetTitle>
                        <SheetDescription class="mt-1"> Add a new class for {{ school?.school_name }} </SheetDescription>
                    </div>
                </div>
            </SheetHeader>

            <!-- Success Alert -->
            <Alert v-if="showSuccess" class="mx-8 mt-4 border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20">
                <CheckCircle2 class="h-4 w-4 text-green-600 dark:text-green-400" />
                <AlertDescription class="text-green-800 dark:text-green-200">
                    {{ successMessage }}
                </AlertDescription>
            </Alert>

            <form @submit.prevent="handleSubmit" class="mt-6 space-y-4 px-8">
                <div class="space-y-2">
                    <Label for="class_name">Class Name *</Label>
                    <Input id="class_name" v-model="form.class_name" type="text" placeholder="e.g., Class 10" required :disabled="form.processing" />
                    <div v-if="form.errors.class_name" class="text-sm text-red-500">
                        {{ form.errors.class_name }}
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="class_code">Class Code *</Label>
                    <Input id="class_code" v-model="form.class_code" type="text" placeholder="e.g., CLS10" required :disabled="form.processing" />
                    <div v-if="form.errors.class_code" class="text-sm text-red-500">
                        {{ form.errors.class_code }}
                    </div>
                    <p class="text-xs text-muted-foreground">Must be unique within this school</p>
                </div>

                <div class="space-y-2">
                    <Label for="grade_level">Grade Level *</Label>
                    <Input id="grade_level" v-model.number="form.grade_level" type="number" min="1" max="12" required :disabled="form.processing" />
                    <div v-if="form.errors.grade_level" class="text-sm text-red-500">
                        {{ form.errors.grade_level }}
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="min_age">Minimum Age</Label>
                        <Input
                            id="min_age"
                            v-model.number="form.min_age"
                            type="number"
                            min="3"
                            max="25"
                            placeholder="Optional"
                            :disabled="form.processing"
                        />
                        <div v-if="form.errors.min_age" class="text-sm text-red-500">
                            {{ form.errors.min_age }}
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="max_age">Maximum Age</Label>
                        <Input
                            id="max_age"
                            v-model.number="form.max_age"
                            type="number"
                            min="3"
                            max="25"
                            placeholder="Optional"
                            :disabled="form.processing"
                        />
                        <div v-if="form.errors.max_age" class="text-sm text-red-500">
                            {{ form.errors.max_age }}
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="order_sequence">Order Sequence</Label>
                    <Input
                        id="order_sequence"
                        v-model.number="form.order_sequence"
                        type="number"
                        min="0"
                        placeholder="0"
                        :disabled="form.processing"
                    />
                    <div v-if="form.errors.order_sequence" class="text-sm text-red-500">
                        {{ form.errors.order_sequence }}
                    </div>
                    <p class="text-xs text-muted-foreground">Used for sorting classes in lists</p>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <Button type="button" variant="outline" @click="close" :disabled="form.processing"> Cancel </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Adding...' : 'Add Class' }}
                    </Button>
                </div>
            </form>
        </SheetContent>
    </Sheet>
</template>
