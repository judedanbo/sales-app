<script setup lang="ts">
import { useAlerts } from '@/composables/useAlerts'
import { Alert, AlertDescription, AlertTitle, AlertIcon } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { X } from 'lucide-vue-next'
import { TransitionGroup } from 'vue'

interface Props {
  position?: 'top-left' | 'top-center' | 'top-right' | 'bottom-left' | 'bottom-center' | 'bottom-right'
  backdrop?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  position: 'top-right',
  backdrop: false
})

const { alerts, removeAlert } = useAlerts()

const positionClasses = {
  'top-left': 'top-4 left-4',
  'top-center': 'top-4 left-1/2 -translate-x-1/2',
  'top-right': 'top-4 right-4',
  'bottom-left': 'bottom-4 left-4',
  'bottom-center': 'bottom-4 left-1/2 -translate-x-1/2',
  'bottom-right': 'bottom-4 right-4'
}
</script>

<template>
  <!-- Optional backdrop for critical alerts -->
  <div 
    v-if="backdrop && alerts.length > 0"
    class="fixed inset-0 z-[9998] bg-black/5 backdrop-blur-[2px]"
  />
  
  <!-- Alerts Container -->
  <div 
    :class="[
      'fixed z-[9999] w-full max-w-sm space-y-2',
      positionClasses[position]
    ]"
  >
    <TransitionGroup
      name="alert"
      tag="div"
      class="space-y-2"
    >
      <Alert
        v-for="alert in alerts"
        :key="alert.id"
        :variant="alert.variant"
        :class="[
          'relative shadow-2xl border-2 backdrop-blur-sm',
          alert.priority === 'critical' ? 'ring-2 ring-destructive/50 shadow-destructive/20' : '',
          alert.priority === 'high' ? 'ring-1 ring-primary/30 shadow-primary/10' : ''
        ]"
      >
        <AlertIcon :variant="alert.variant" />
        <div class="flex-1">
          <AlertTitle v-if="alert.title">
            {{ alert.title }}
          </AlertTitle>
          <AlertDescription>
            {{ alert.message }}
          </AlertDescription>
        </div>
        <Button
          v-if="alert.dismissible"
          variant="ghost"
          size="sm"
          class="absolute top-2 right-2 h-auto p-1 hover:bg-background/80"
          @click="removeAlert(alert.id)"
        >
          <X class="h-3 w-3" />
          <span class="sr-only">Dismiss</span>
        </Button>
      </Alert>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.alert-enter-active,
.alert-leave-active {
  transition: all 0.3s ease;
}

.alert-enter-from {
  opacity: 0;
  transform: translateX(100%);
}

.alert-leave-to {
  opacity: 0;
  transform: translateX(100%);
}

.alert-move {
  transition: transform 0.3s ease;
}
</style>