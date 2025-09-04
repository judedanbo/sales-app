<script setup lang="ts">
import { computed, provide, ref } from 'vue';

interface Props {
    modelValue?: string;
    disabled?: boolean;
}

interface Emits {
    'update:modelValue': [value: string];
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const isOpen = ref(false);
const selectedValue = computed({
    get: () => props.modelValue || '',
    set: (value: string) => emit('update:modelValue', value)
});

const selectValue = (value: string) => {
    selectedValue.value = value;
    isOpen.value = false;
};

const toggleOpen = () => {
    if (!props.disabled) {
        isOpen.value = !isOpen.value;
    }
};

// Provide context to child components
provide('select', {
    selectedValue,
    selectValue,
    isOpen,
    toggleOpen,
    disabled: computed(() => props.disabled)
});
</script>

<template>
    <div class="relative">
        <slot />
    </div>
</template>