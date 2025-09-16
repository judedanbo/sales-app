<template>
    <div
        v-show="isActive"
        :id="`content-${value}`"
        :aria-labelledby="`trigger-${value}`"
        role="tabpanel"
        class="mt-2 ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
    >
        <slot />
    </div>
</template>

<script setup lang="ts">
import { computed, inject } from 'vue'
import type { Ref } from 'vue'

interface Props {
    value: string
}

const props = defineProps<Props>()

const activeTab = inject<Ref<string>>('activeTab')

const isActive = computed(() => activeTab?.value === props.value)
</script>