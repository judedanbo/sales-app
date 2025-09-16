<template>
    <button
        :class="[
            'inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
            isActive
                ? 'bg-background text-foreground shadow-sm'
                : 'hover:bg-background/50 hover:text-foreground'
        ]"
        :data-state="isActive ? 'active' : 'inactive'"
        role="tab"
        :aria-selected="isActive"
        :aria-controls="`content-${value}`"
        :id="`trigger-${value}`"
        @click="setActiveTab(value)"
    >
        <slot />
    </button>
</template>

<script setup lang="ts">
import { computed, inject } from 'vue'
import type { Ref } from 'vue'

interface Props {
    value: string
}

const props = defineProps<Props>()

const activeTab = inject<Ref<string>>('activeTab')
const setActiveTab = inject<(value: string) => void>('setActiveTab')

const isActive = computed(() => activeTab?.value === props.value)
</script>