<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

interface Props {
    form: {
        school_name: string;
        school_code: string;
        school_type: string;
        board_affiliation: string;
        established_date: string;
        // principal_name: string;
        // medium_of_instruction: string;
        // total_students: number | string;
        // total_teachers: number | string;
        // website: string;
        // description: string;
        is_active: boolean;
    };
    formData: {
        schoolTypes: Array<{ value: string; label: string }>;
        boardAffiliations: Array<{ value: string; label: string }>;
        mediumOfInstructions: Array<{ value: string; label: string }>;
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

const updateForm = (field: keyof Props['form'], value: any) => {
    emit('update:form', {
        ...props.form,
        [field]: value,
    });
};

const getFieldError = (field: string) => {
    return props.errors[field];
};

const hasError = (field: string) => {
    return !!props.errors[field];
};
</script>

<template>
    <form @submit.prevent="emit('submit')" class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2">
            <!-- School Name -->
            <div class="space-y-2">
                <Label for="school_name" :class="{ 'text-destructive': hasError('school_name') }"> School Name * </Label>
                <Input
                    id="school_name"
                    :model-value="form.school_name"
                    @update:model-value="updateForm('school_name', $event)"
                    placeholder="Enter school name"
                    :class="{ 'border-destructive': hasError('school_name') }"
                    required
                />
                <p v-if="hasError('school_name')" class="text-sm text-destructive">
                    {{ getFieldError('school_name') }}
                </p>
            </div>

            <!-- School Code -->
            <div class="space-y-2">
                <Label for="school_code" :class="{ 'text-destructive': hasError('school_code') }"> School Code * </Label>
                <Input
                    id="school_code"
                    :model-value="form.school_code"
                    @update:model-value="updateForm('school_code', $event)"
                    placeholder="Enter school code"
                    :class="{ 'border-destructive': hasError('school_code') }"
                    required
                />
                <p v-if="hasError('school_code')" class="text-sm text-destructive">
                    {{ getFieldError('school_code') }}
                </p>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <!-- School Type -->
            <div class="space-y-2">
                <Label for="school_type" :class="{ 'text-destructive': hasError('school_type') }"> School Type * </Label>
                <Select :model-value="form.school_type" @update:model-value="updateForm('school_type', $event)">
                    <SelectTrigger :class="{ 'border-destructive': hasError('school_type') }">
                        <SelectValue placeholder="Select school type" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="type in formData.schoolTypes" :key="type.value" :value="type.value">
                            {{ type.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <p v-if="hasError('school_type')" class="text-sm text-destructive">
                    {{ getFieldError('school_type') }}
                </p>
            </div>

            <!-- Board Affiliation -->
            <div class="space-y-2">
                <Label for="board_affiliation">Board Affiliation</Label>
                <Select :model-value="form.board_affiliation" @update:model-value="updateForm('board_affiliation', $event)">
                    <SelectTrigger>
                        <SelectValue placeholder="Select board affiliation" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="board in formData.boardAffiliations" :key="board.value" :value="board.value">
                            {{ board.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <!-- Established Date -->
            <div class="space-y-2">
                <Label for="established_date">Established Date</Label>
                <Input
                    id="established_date"
                    type="date"
                    :model-value="form.established_date"
                    @update:model-value="updateForm('established_date', $event)"
                />
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <!-- Medium of Instruction -->
            <!-- <div class="space-y-2">
                <Label for="medium_of_instruction">Medium of Instruction</Label>
                <Select :model-value="form.medium_of_instruction" @update:model-value="updateForm('medium_of_instruction', $event)">
                    <SelectTrigger>
                        <SelectValue placeholder="Select medium" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="medium in formData.mediumOfInstructions" :key="medium.value" :value="medium.value">
                            {{ medium.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div> -->

            <!-- Total Students -->
            <!-- <div class="space-y-2">
                <Label for="total_students">Total Students</Label>
                <Input
                    id="total_students"
                    type="number"
                    :model-value="form.total_students"
                    @update:model-value="updateForm('total_students', $event)"
                    placeholder="0"
                    min="0"
                />
            </div> -->

            <!-- Total Teachers -->
            <!-- <div class="space-y-2">
                <Label for="total_teachers">Total Teachers</Label>
                <Input
                    id="total_teachers"
                    type="number"
                    :model-value="form.total_teachers"
                    @update:model-value="updateForm('total_teachers', $event)"
                    placeholder="0"
                    min="0"
                />
            </div> -->
        </div>

        <!-- Website -->
        <div class="space-y-2">
            <Label for="website">Website</Label>
            <Input
                id="website"
                type="url"
                :model-value="form.website"
                @update:model-value="updateForm('website', $event)"
                placeholder="https://example.com"
            />
        </div>

        <!-- Description -->
        <div class="space-y-2">
            <Label for="description">Description</Label>
            <Textarea
                id="description"
                :model-value="form.description"
                @update:model-value="updateForm('description', $event)"
                placeholder="Enter school description"
                rows="3"
            />
        </div>

        <!-- Status -->
        <div class="flex items-center space-x-2">
            <input
                id="is_active"
                type="checkbox"
                :checked="form.is_active"
                @change="updateForm('is_active', ($event.target as HTMLInputElement).checked)"
                class="focus:ring-opacity-50 rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
            />
            <Label for="is_active">Active School</Label>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3 pt-4">
            <Button type="button" variant="outline" @click="emit('cancel')"> Cancel </Button>
            <Button type="submit" :disabled="processing">
                <span v-if="processing">Saving...</span>
                <span v-else>Save School</span>
            </Button>
        </div>
    </form>
</template>
