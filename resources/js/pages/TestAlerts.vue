<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Alert, AlertDescription, AlertTitle, AlertIcon } from '@/components/ui/alert'
import AppLayout from '@/layouts/AppLayout.vue'
import { useAlerts } from '@/composables/useAlerts'
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'

const { alerts, success, error, warning, info, critical, urgent, addAlert, clearAlerts } = useAlerts()
// Toast functionality converted to alerts - keeping promise for async operations
const mockPromise = {
  promise: <T>(promise: Promise<T>, options: { loading: string; success: string; error: string }) => {
    addAlert(options.loading, 'info', { title: 'Processing', duration: 0 })
    promise
      .then(() => success(options.success))
      .catch(() => error(options.error))
    return promise
  }
}

const showExampleAlert = (variant: 'default' | 'success' | 'destructive' | 'warning' | 'info') => {
  const messages = {
    default: 'This is a default alert message.',
    success: 'Operation completed successfully!',
    destructive: 'An error has occurred.',
    warning: 'Please review your input.',
    info: 'Here is some useful information.'
  }
  
  const titles = {
    default: 'Notice',
    success: 'Success',
    destructive: 'Error',
    warning: 'Warning',
    info: 'Information'
  }
  
  addAlert(messages[variant], variant, {
    title: titles[variant],
    persistent: variant === 'destructive' // Make error alerts persistent
  })
}

const showToast = (variant: 'success' | 'error' | 'warning' | 'info') => {
  const messages = {
    success: 'Alert notification: Success! (converted from toast)',
    error: 'Alert notification: Error occurred! (converted from toast)',
    warning: 'Alert notification: Warning message! (converted from toast)',
    info: 'Alert notification: Information! (converted from toast)'
  }
  
  switch (variant) {
    case 'success':
      success(messages.success, { position: 'bottom-right', duration: 3000 })
      break
    case 'error':
      error(messages.error, { position: 'bottom-right', priority: 'high' })
      break
    case 'warning':
      warning(messages.warning, { position: 'bottom-right', duration: 4000 })
      break
    case 'info':
      info(messages.info, { position: 'bottom-right', duration: 3000 })
      break
  }
}

const simulateAsyncOperation = () => {
  const asyncPromise = new Promise((resolve, reject) => {
    setTimeout(() => {
      Math.random() > 0.5 ? resolve('Success!') : reject('Failed!')
    }, 2000)
  })
  
  mockPromise.promise(asyncPromise, {
    loading: 'Processing...',
    success: 'Operation completed!',
    error: 'Operation failed!'
  })
}

const showPositionedAlert = (position: 'top-left' | 'top-center' | 'top-right' | 'bottom-left' | 'bottom-center' | 'bottom-right') => {
  addAlert(`Alert positioned at ${position}`, 'info', {
    title: `${position.charAt(0).toUpperCase() + position.slice(1)} Alert`,
    position,
    duration: 5000
  })
}

const showPriorityAlert = (priority: 'low' | 'normal' | 'high' | 'critical') => {
  const messages = {
    low: 'This is a low priority notification',
    normal: 'This is a normal priority alert',
    high: 'This is a high priority alert with enhanced styling',
    critical: 'CRITICAL: This alert demands immediate attention with backdrop!'
  }
  
  if (priority === 'critical') {
    critical(messages[priority], {
      title: 'Critical System Alert',
      persistent: true,
      duration: 0
    })
  } else if (priority === 'high') {
    urgent(messages[priority], {
      title: 'High Priority Alert'
    })
  } else {
    addAlert(messages[priority], priority === 'low' ? 'default' : 'info', {
      title: `${priority.charAt(0).toUpperCase() + priority.slice(1)} Priority`,
      priority
    })
  }
}

const debugAlerts = () => {
  console.log('Current alerts:', alerts.value)
  console.log('Alerts length:', alerts.value?.length)
  console.log('Alert system working!')
  
  // Force a test alert
  console.log('Adding test alert...')
  addAlert('Debug test message', 'info', {
    title: 'Debug Test',
    persistent: true
  })
  
  console.log('After adding alert:', alerts.value)
}

const staticAlerts = ref([
  { variant: 'default' as const, title: 'Default Alert', message: 'This is a default alert with an icon.' },
  { variant: 'success' as const, title: 'Success Alert', message: 'This indicates a successful operation.' },
  { variant: 'destructive' as const, title: 'Error Alert', message: 'This indicates an error occurred.' },
  { variant: 'warning' as const, title: 'Warning Alert', message: 'This is a warning message.' },
  { variant: 'info' as const, title: 'Info Alert', message: 'This is an informational message.' },
])
</script>

<template>
  <Head title="Test Alerts" />
  
  <AppLayout>
    <div class="space-y-8 p-8">
      <div>
        <h1 class="text-3xl font-bold">Alert System Testing</h1>
        <p class="text-muted-foreground">Test the comprehensive alert and notification system.</p>
      </div>

      <!-- Static Alert Examples -->
      <Card>
        <CardHeader>
          <CardTitle>Static Alert Components</CardTitle>
          <CardDescription>Examples of different alert variants with icons</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <Alert
            v-for="alert in staticAlerts"
            :key="alert.variant"
            :variant="alert.variant"
          >
            <AlertIcon :variant="alert.variant" />
            <AlertTitle>{{ alert.title }}</AlertTitle>
            <AlertDescription>{{ alert.message }}</AlertDescription>
          </Alert>
        </CardContent>
      </Card>

      <!-- Dynamic Alert Testing -->
      <Card>
        <CardHeader>
          <CardTitle>Dynamic Alert System</CardTitle>
          <CardDescription>Test the global alert management system with persistent alerts</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="flex flex-wrap gap-2">
            <Button @click="showExampleAlert('default')">Show Default Alert</Button>
            <Button @click="showExampleAlert('success')" variant="outline">Show Success Alert</Button>
            <Button @click="showExampleAlert('destructive')" variant="destructive">Show Error Alert</Button>
            <Button @click="showExampleAlert('warning')" variant="secondary">Show Warning Alert</Button>
            <Button @click="showExampleAlert('info')" variant="outline">Show Info Alert</Button>
          </div>
          <div class="flex gap-2">
            <Button @click="clearAlerts()" variant="outline" size="sm">Clear All Alerts</Button>
            <Button @click="clearAlerts('destructive')" variant="outline" size="sm">Clear Error Alerts</Button>
          </div>
          <div class="mt-4 p-4 bg-muted rounded-lg">
            <p class="text-sm text-muted-foreground mb-2">Debug Info:</p>
            <p class="text-xs">Current alerts count: {{ alerts.length }}</p>
            <Button @click="debugAlerts" variant="outline" size="sm" class="mt-2">Debug Alert State</Button>
          </div>
        </CardContent>
      </Card>

      <!-- Alert Positioning Testing -->
      <Card>
        <CardHeader>
          <CardTitle>Alert Positioning System</CardTitle>
          <CardDescription>Test alerts in different screen positions with z-[9999] layering</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid gap-2 grid-cols-3">
            <Button @click="showPositionedAlert('top-left')" variant="outline" size="sm">Top Left</Button>
            <Button @click="showPositionedAlert('top-center')" variant="outline" size="sm">Top Center</Button>
            <Button @click="showPositionedAlert('top-right')" variant="outline" size="sm">Top Right</Button>
            <Button @click="showPositionedAlert('bottom-left')" variant="outline" size="sm">Bottom Left</Button>
            <Button @click="showPositionedAlert('bottom-center')" variant="outline" size="sm">Bottom Center</Button>
            <Button @click="showPositionedAlert('bottom-right')" variant="outline" size="sm">Bottom Right</Button>
          </div>
          <p class="text-sm text-muted-foreground">
            Note: Positioning is currently handled globally. Multiple alerts will appear in the same position.
          </p>
        </CardContent>
      </Card>

      <!-- Priority Alert Testing -->
      <Card>
        <CardHeader>
          <CardTitle>Priority Alert System</CardTitle>
          <CardDescription>Test different priority levels with enhanced styling and backdrop effects</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="flex flex-wrap gap-2">
            <Button @click="showPriorityAlert('low')" variant="outline">Low Priority</Button>
            <Button @click="showPriorityAlert('normal')" variant="default">Normal Priority</Button>
            <Button @click="showPriorityAlert('high')" variant="secondary">High Priority</Button>
            <Button @click="showPriorityAlert('critical')" variant="destructive">Critical Priority</Button>
          </div>
          <div class="p-4 bg-muted rounded-lg space-y-2">
            <p class="text-sm font-medium">Priority Features:</p>
            <ul class="text-sm text-muted-foreground space-y-1">
              <li>• <span class="font-medium">Low:</span> Basic styling, normal z-index</li>
              <li>• <span class="font-medium">Normal:</span> Standard alert appearance</li>
              <li>• <span class="font-medium">High:</span> Enhanced shadow and ring styling</li>
              <li>• <span class="font-medium">Critical:</span> Backdrop blur overlay, maximum z-index, persistent</li>
            </ul>
          </div>
        </CardContent>
      </Card>

      <!-- Toast Notifications Testing -->
      <Card>
        <CardHeader>
          <CardTitle>Toast Notification System</CardTitle>
          <CardDescription>Test temporary toast notifications that appear and auto-dismiss</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="flex flex-wrap gap-2">
            <Button @click="showToast('success')" variant="default">Show Success Toast</Button>
            <Button @click="showToast('error')" variant="destructive">Show Error Toast</Button>
            <Button @click="showToast('warning')" variant="secondary">Show Warning Toast</Button>
            <Button @click="showToast('info')" variant="outline">Show Info Toast</Button>
          </div>
          <div>
            <Button @click="simulateAsyncOperation()" variant="outline">Test Promise Toast</Button>
            <p class="text-sm text-muted-foreground mt-2">
              This will show a loading toast, then either success or error based on random outcome.
            </p>
          </div>
        </CardContent>
      </Card>

      <!-- Usage Examples -->
      <Card>
        <CardHeader>
          <CardTitle>Usage Guidelines</CardTitle>
          <CardDescription>When to use different alert types</CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid gap-4 md:grid-cols-2">
            <div>
              <h3 class="font-medium mb-2">Static Alerts</h3>
              <ul class="text-sm space-y-1 text-muted-foreground">
                <li>• Use for form validation errors</li>
                <li>• Important information that needs user attention</li>
                <li>• Persistent warnings or notices</li>
                <li>• Error states that require action</li>
              </ul>
            </div>
            <div>
              <h3 class="font-medium mb-2">Toast Notifications</h3>
              <ul class="text-sm space-y-1 text-muted-foreground">
                <li>• Success confirmations for actions</li>
                <li>• Temporary status updates</li>
                <li>• Loading states for async operations</li>
                <li>• Brief informational messages</li>
              </ul>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>