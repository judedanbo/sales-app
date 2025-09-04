<script setup lang="ts">
interface Props {
    variant?: 'default' | 'secondary' | 'destructive' | 'outline';
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
    class: '',
});

const variantClasses = {
    default: 'border-transparent bg-primary text-primary-foreground shadow hover:bg-primary/80',
    secondary: 'border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80',
    destructive: 'border-transparent bg-destructive text-destructive-foreground shadow hover:bg-destructive/80',
    outline: 'text-foreground'
};
</script>

<template>
    <div 
        :class="`inline-flex items-center rounded-md border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 ${variantClasses[variant]} ${props.class}`"
    >
        <slot />
    </div>
</template>