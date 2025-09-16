<template>
    <div class="tabs-root" :data-orientation="orientation">
        <slot />
    </div>
</template>

<script setup lang="ts">
import { provide, ref } from 'vue'

interface Props {
    defaultValue?: string
    orientation?: 'horizontal' | 'vertical'
    modelValue?: string
}

const props = withDefaults(defineProps<Props>(), {
    orientation: 'horizontal'
})

const emit = defineEmits<{
    'update:modelValue': [value: string]
}>()

const activeTab = ref(props.modelValue || props.defaultValue || '')

provide('activeTab', activeTab)
provide('setActiveTab', (value: string) => {
    activeTab.value = value
    emit('update:modelValue', value)
})
</script>