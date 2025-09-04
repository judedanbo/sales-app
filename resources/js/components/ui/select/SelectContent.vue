<script setup lang="ts">
import { inject } from 'vue';

const select = inject('select') as any;
</script>

<template>
    <div 
        v-if="select.isOpen.value"
        class="absolute top-full z-50 min-w-full overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md animate-in fade-in-0 zoom-in-95"
    >
        <div class="max-h-64 overflow-y-auto p-1">
            <slot />
        </div>
    </div>
</template>