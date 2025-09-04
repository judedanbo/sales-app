<script setup lang="ts">
import { inject } from 'vue';

interface Props {
    class?: string;
}

const props = defineProps<Props>();

const select = inject('select') as any;
</script>

<template>
    <button
        type="button"
        @click="select.toggleOpen"
        :disabled="select.disabled.value"
        :class="`flex h-9 w-full items-center justify-between rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50 ${props.class}`"
    >
        <slot />
        <svg 
            width="15" 
            height="15" 
            viewBox="0 0 15 15" 
            fill="none" 
            xmlns="http://www.w3.org/2000/svg"
            class="h-4 w-4 opacity-50"
        >
            <path 
                d="m4.5 6 3 3 3-3" 
                stroke="currentColor" 
                stroke-width="1.5" 
                stroke-linecap="round" 
                stroke-linejoin="round"
            />
        </svg>
    </button>
</template>