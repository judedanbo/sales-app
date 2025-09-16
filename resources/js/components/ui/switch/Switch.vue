<template>
    <button
        :id="id"
        role="switch"
        type="button"
        :aria-checked="checked"
        :aria-labelledby="ariaLabelledby"
        :disabled="disabled"
        :class="[
            'peer inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-50',
            checked
                ? 'bg-primary'
                : 'bg-input'
        ]"
        @click="handleToggle"
    >
        <span
            :class="[
                'pointer-events-none block h-5 w-5 rounded-full bg-background shadow-lg ring-0 transition-transform',
                checked ? 'translate-x-5' : 'translate-x-0'
            ]"
        />
    </button>
</template>

<script setup lang="ts">
interface Props {
    id?: string
    checked?: boolean
    disabled?: boolean
    ariaLabelledby?: string
    modelValue?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    checked: false,
    disabled: false
})

const emit = defineEmits<{
    'update:modelValue': [value: boolean]
    'change': [value: boolean]
}>()

const handleToggle = () => {
    if (props.disabled) return

    const newValue = !props.checked && !props.modelValue
    emit('update:modelValue', newValue)
    emit('change', newValue)
}
</script>