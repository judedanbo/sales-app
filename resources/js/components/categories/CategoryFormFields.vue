<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { watch } from 'vue';

interface Props {
    form: {
        name: string;
        slug: string;
        description: string;
        parent_id: number | null;
        sort_order: number;
        is_active: boolean;
        color: string;
        icon: string;
    };
    formData: {
        parentCategories: Array<{ value: number; label: string }>;
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

// Auto-generate slug from name if slug is empty
watch(
    () => props.form.name,
    (newName) => {
        if (!props.form.slug && newName) {
            const slug = newName
                .toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim();
            updateForm('slug', slug);
        }
    },
);

// Color options for category
const colorOptions = [
    { value: 'blue', label: 'Blue' },
    { value: 'green', label: 'Green' },
    { value: 'red', label: 'Red' },
    { value: 'yellow', label: 'Yellow' },
    { value: 'purple', label: 'Purple' },
    { value: 'pink', label: 'Pink' },
    { value: 'indigo', label: 'Indigo' },
    { value: 'gray', label: 'Gray' },
];

// Icon options for category
const iconOptions = [
    { value: 'folder', label: 'Folder' },
    { value: 'tag', label: 'Tag' },
    { value: 'package', label: 'Package' },
    { value: 'layers', label: 'Layers' },
    { value: 'grid', label: 'Grid' },
    { value: 'list', label: 'List' },
    { value: 'bookmark', label: 'Bookmark' },
    { value: 'star', label: 'Star' },
];
</script>

<template>
    <form @submit.prevent="emit('submit')" class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2">
            <!-- Category Name -->
            <div class="space-y-2">
                <Label for="name" :class="{ 'text-destructive': hasError('name') }"> Category Name * </Label>
                <Input
                    id="name"
                    :model-value="form.name"
                    @update:model-value="updateForm('name', $event)"
                    placeholder="Enter category name"
                    :class="{ 'border-destructive': hasError('name') }"
                    required
                />
                <p v-if="hasError('name')" class="text-sm text-destructive">
                    {{ getFieldError('name') }}
                </p>
            </div>

            <!-- Slug -->
            <div class="space-y-2">
                <Label for="slug" :class="{ 'text-destructive': hasError('slug') }"> Slug </Label>
                <Input
                    id="slug"
                    :model-value="form.slug"
                    @update:model-value="updateForm('slug', $event)"
                    placeholder="category-url-slug"
                    :class="{ 'border-destructive': hasError('slug') }"
                />
                <p v-if="hasError('slug')" class="text-sm text-destructive">
                    {{ getFieldError('slug') }}
                </p>
                <p class="text-sm text-muted-foreground">Leave empty to auto-generate from name</p>
            </div>
        </div>

        <!-- Parent Category -->
        <div class="space-y-2">
            <Label for="parent_id" :class="{ 'text-destructive': hasError('parent_id') }"> Parent Category </Label>
            <Select :model-value="form.parent_id?.toString() || ''" @update:model-value="updateForm('parent_id', $event ? parseInt($event) : null)">
                <SelectTrigger :class="{ 'border-destructive': hasError('parent_id') }">
                    <SelectValue placeholder="Select parent category (optional)" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="">No Parent (Root Category)</SelectItem>
                    <SelectItem v-for="category in formData.parentCategories" :key="category.value" :value="category.value.toString()">
                        {{ category.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <p v-if="hasError('parent_id')" class="text-sm text-destructive">
                {{ getFieldError('parent_id') }}
            </p>
        </div>

        <!-- Description -->
        <div class="space-y-2">
            <Label for="description" :class="{ 'text-destructive': hasError('description') }"> Description </Label>
            <Textarea
                id="description"
                :model-value="form.description"
                @update:model-value="updateForm('description', $event)"
                placeholder="Enter category description"
                rows="3"
                :class="{ 'border-destructive': hasError('description') }"
            />
            <p v-if="hasError('description')" class="text-sm text-destructive">
                {{ getFieldError('description') }}
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <!-- Sort Order -->
            <div class="space-y-2">
                <Label for="sort_order" :class="{ 'text-destructive': hasError('sort_order') }"> Sort Order </Label>
                <Input
                    id="sort_order"
                    type="number"
                    :model-value="form.sort_order"
                    @update:model-value="updateForm('sort_order', parseInt($event) || 0)"
                    placeholder="0"
                    min="0"
                    max="99999"
                    :class="{ 'border-destructive': hasError('sort_order') }"
                />
                <p v-if="hasError('sort_order')" class="text-sm text-destructive">
                    {{ getFieldError('sort_order') }}
                </p>
            </div>

            <!-- Color -->
            <div class="space-y-2">
                <Label for="color">Color</Label>
                <Select :model-value="form.color" @update:model-value="updateForm('color', $event)">
                    <SelectTrigger>
                        <SelectValue placeholder="Select color" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="color in colorOptions" :key="color.value" :value="color.value">
                            {{ color.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <!-- Icon -->
            <div class="space-y-2">
                <Label for="icon">Icon</Label>
                <Select :model-value="form.icon" @update:model-value="updateForm('icon', $event)">
                    <SelectTrigger>
                        <SelectValue placeholder="Select icon" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="icon in iconOptions" :key="icon.value" :value="icon.value">
                            {{ icon.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>

        <!-- Active Status -->
        <div class="flex items-center space-x-2">
            <Checkbox id="is_active" :checked="form.is_active" @update:checked="updateForm('is_active', $event)" />
            <Label for="is_active">Active Category</Label>
            <p v-if="hasError('is_active')" class="ml-2 text-sm text-destructive">
                {{ getFieldError('is_active') }}
            </p>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3 pt-4">
            <Button type="button" variant="outline" @click="emit('cancel')"> Cancel </Button>
            <Button type="submit" :disabled="processing">
                <span v-if="processing">Saving...</span>
                <span v-else>Save Category</span>
            </Button>
        </div>
    </form>
</template>
