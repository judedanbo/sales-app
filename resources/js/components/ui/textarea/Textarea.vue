<script setup lang="ts">
interface Props {
    modelValue?: string;
    placeholder?: string;
    rows?: number;
    disabled?: boolean;
    class?: string;
}

interface Emits {
    'update:modelValue': [value: string];
}

const props = withDefaults(defineProps<Props>(), {
    rows: 3,
});

const emit = defineEmits<Emits>();

const handleInput = (event: Event) => {
    const target = event.target as HTMLTextAreaElement;
    emit('update:modelValue', target.value);
};
</script>

<template>
    <textarea
        :value="modelValue"
        @input="handleInput"
        :placeholder="placeholder"
        :rows="rows"
        :disabled="disabled"
        :class="`flex min-h-[60px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 ${props.class}`"
    />
</template>