<template>
    <Badge :variant="badgeVariant" :class="badgeClass">
        <component :is="statusIcon" v-if="statusIcon" class="h-3 w-3 mr-1" />
        {{ statusText }}
    </Badge>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { CheckCircle, XCircle, Clock } from 'lucide-vue-next';

interface Props {
    status: 'completed' | 'voided' | 'pending' | string;
    size?: 'sm' | 'default' | 'lg';
}

const props = withDefaults(defineProps<Props>(), {
    size: 'default',
});

const badgeVariant = computed(() => {
    switch (props.status) {
        case 'completed':
            return 'default';
        case 'voided':
            return 'destructive';
        case 'pending':
            return 'secondary';
        default:
            return 'outline';
    }
});

const badgeClass = computed(() => {
    const sizeClasses = {
        sm: 'text-xs px-2 py-0.5',
        default: 'text-xs px-2.5 py-0.5',
        lg: 'text-sm px-3 py-1',
    };

    return `${sizeClasses[props.size]} font-medium`;
});

const statusText = computed(() => {
    switch (props.status) {
        case 'completed':
            return 'Completed';
        case 'voided':
            return 'Voided';
        case 'pending':
            return 'Pending';
        default:
            return props.status.charAt(0).toUpperCase() + props.status.slice(1);
    }
});

const statusIcon = computed(() => {
    switch (props.status) {
        case 'completed':
            return CheckCircle;
        case 'voided':
            return XCircle;
        case 'pending':
            return Clock;
        default:
            return null;
    }
});
</script>