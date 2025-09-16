<template>
    <div
        class="inline-flex h-10 items-center justify-center rounded-md bg-muted p-1 text-muted-foreground"
        role="tablist"
        :aria-orientation="orientation"
    >
        <slot />
    </div>
</template>

<script setup lang="ts">
interface Props {
    orientation?: 'horizontal' | 'vertical'
}

withDefaults(defineProps<Props>(), {
    orientation: 'horizontal'
})
</script>