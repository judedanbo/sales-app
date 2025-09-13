<script setup lang="ts">
import { computed } from 'vue'
import { 
  CheckCircle2, 
  XCircle, 
  AlertTriangle, 
  Info, 
  AlertCircle 
} from 'lucide-vue-next'
import type { AlertVariants } from '.'

interface Props {
  variant?: AlertVariants['variant']
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
})

const IconComponent = computed(() => {
  switch (props.variant) {
    case 'success':
      return CheckCircle2
    case 'destructive':
      return XCircle
    case 'warning':
      return AlertTriangle
    case 'info':
      return Info
    case 'default':
    default:
      return AlertCircle
  }
})
</script>

<template>
  <component :is="IconComponent" class="h-4 w-4" />
</template>