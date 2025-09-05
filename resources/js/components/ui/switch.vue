<script setup lang="ts">
interface Props {
    modelValue?: boolean;
    disabled?: boolean;
    id?: string;
    name?: string;
    class?: string;
}

interface Emits {
    (e: 'update:modelValue', value: boolean): void;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: false,
    disabled: false,
    class: '',
});

const emit = defineEmits<Emits>();

function toggle() {
    if (!props.disabled) {
        emit('update:modelValue', !props.modelValue);
    }
}
</script>

<template>
    <button
        type="button"
        :id="id"
        :name="name"
        :disabled="disabled"
        :class="`relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-50 ${
            modelValue 
                ? 'bg-primary' 
                : 'bg-input'
        } ${props.class}`"
        :aria-checked="modelValue"
        role="switch"
        @click="toggle"
    >
        <span 
            :class="`pointer-events-none block h-5 w-5 rounded-full bg-background shadow-lg ring-0 transition-transform ${
                modelValue 
                    ? 'translate-x-5' 
                    : 'translate-x-0'
            }`"
        />
    </button>
</template>