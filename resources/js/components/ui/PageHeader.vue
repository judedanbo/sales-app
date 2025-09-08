<script setup lang="ts">
interface Props {
    title: string;
    description?: string;
    actionHref?: string;
    actionText?: string;
    actionIcon?: any;
}

defineProps<Props>();
defineSlots<{
    action?(): any;
}>();
</script>

<template>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold tracking-tight">{{ title }}</h2>
            <p v-if="description" class="text-muted-foreground">{{ description }}</p>
        </div>
        <slot name="actions" />
    </div>
</template>