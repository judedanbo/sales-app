<script setup lang="ts">
import { inject, computed } from 'vue';

interface Props {
    placeholder?: string;
}

const props = defineProps<Props>();

const select = inject('select') as any;

const displayValue = computed(() => {
    return select.selectedValue.value || props.placeholder;
});
</script>

<template>
    <span :class="{ 'text-muted-foreground': !select.selectedValue.value }">
        {{ displayValue }}
    </span>
</template>