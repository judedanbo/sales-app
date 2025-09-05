<script setup lang="ts">
interface Props {
    src?: string;
    alt?: string;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    class: '',
});
</script>

<template>
    <img 
        :src="src" 
        :alt="alt" 
        :class="`aspect-square h-full w-full ${props.class}`" 
    />
</template>
