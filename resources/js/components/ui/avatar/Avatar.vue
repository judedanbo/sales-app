<script setup lang="ts">
interface Props {
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    class: '',
});
</script>

<template>
    <div :class="`relative flex h-10 w-10 shrink-0 overflow-hidden rounded-full ${props.class}`">
        <slot />
    </div>
</template>
