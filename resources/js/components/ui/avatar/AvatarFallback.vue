<script setup lang="ts">
interface Props {
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    class: '',
});
</script>

<template>
    <div :class="`flex h-full w-full items-center justify-center rounded-full bg-muted ${props.class}`">
        <slot />
    </div>
</template>
