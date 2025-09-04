<script setup lang="ts">
interface Props {
    value: number;
    max?: number;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    max: 100,
    class: '',
});

const percentage = Math.min(Math.max((props.value / props.max) * 100, 0), 100);
</script>

<template>
    <div :class="`relative h-2 w-full overflow-hidden rounded-full bg-secondary ${props.class}`">
        <div 
            class="h-full w-full flex-1 bg-primary transition-all duration-300 ease-in-out"
            :style="{ transform: `translateX(-${100 - percentage}%)` }"
        />
    </div>
</template>