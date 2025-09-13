# Alert System Documentation

## Overview

The application features a comprehensive alert system that provides both temporary toast notifications and persistent alerts. The system is built with Vue 3, TypeScript, and integrates seamlessly with the existing Inertia.js workflow.

## Components

### 1. Alert Components (Static Alerts)

#### Alert.vue
The main alert component with support for multiple variants.

**Props:**
- `variant`: `'default' | 'success' | 'destructive' | 'warning' | 'info'`
- `class`: Additional CSS classes

**Usage:**
```vue
<Alert variant="success">
  <AlertIcon variant="success" />
  <AlertTitle>Success!</AlertTitle>
  <AlertDescription>Your operation completed successfully.</AlertDescription>
</Alert>
```

#### AlertIcon.vue
Automatically displays the appropriate icon based on variant:
- `success`: CheckCircle2
- `destructive`: XCircle
- `warning`: AlertTriangle
- `info`: Info
- `default`: AlertCircle

#### AlertTitle.vue
Displays the alert title with appropriate styling.

#### AlertDescription.vue
Displays the alert description/message content.

### 2. Toast Notifications (Temporary)

Toast notifications are temporary messages that appear and auto-dismiss. They're built using `vue-sonner`.

## Composables

### useToast()

Provides methods for displaying temporary toast notifications.

**Methods:**
```typescript
const { success, error, warning, info, loading, dismiss, promise } = useToast()

// Basic toast
success('Operation completed!')
error('Something went wrong!')
warning('Please check your input')
info('New message received')

// Toast with options
success('User created!', {
  description: 'John Doe has been added to the system',
  duration: 5000
})

// Loading toast
const loadingId = loading('Processing...')
// Later dismiss it
dismiss(loadingId)

// Promise-based toast
promise(apiCall(), {
  loading: 'Saving...',
  success: 'Saved successfully!',
  error: 'Failed to save'
})
```

### useAlerts()

Manages persistent alerts that appear in the global alerts container.

**Methods:**
```typescript
const { success, error, warning, info, addAlert, removeAlert, clearAlerts } = useAlerts()

// Add alerts
success('User created successfully')
error('Failed to save user', { persistent: true })
warning('Please review the form')
info('System maintenance scheduled')

// Custom alert
addAlert('Custom message', 'info', {
  title: 'Custom Title',
  dismissible: true,
  persistent: false,
  duration: 5000
})

// Remove alerts
removeAlert('alert-id')
clearAlerts() // Clear all
clearAlerts('destructive') // Clear only error alerts
```

## Integration Points

### 1. Layouts

Both main layouts include the alert system:

**AppSidebarLayout.vue:**
- Global AlertsContainer for persistent alerts
- Toaster for toast notifications (bottom-right)
- Automatic Laravel flash message handling

**AuthSimpleLayout.vue:**
- Toaster for toast notifications (bottom-center)

### 2. Form Integration

Forms automatically handle success and error states:

```vue
<script setup>
import { useToast } from '@/composables/useToast'

const { success, error } = useToast()

const form = useForm({ /* ... */ })

const handleSubmit = () => {
  form.post('/api/endpoint', {
    onSuccess: () => {
      success('Data saved successfully!')
    },
    onError: (errors) => {
      const errorMessages = Object.values(errors).flat()
      error(errorMessages.join(', ') || 'Operation failed')
    }
  })
}
</script>
```

### 3. Delete Confirmations

Delete confirmation modals now include success toasts:

```vue
<script setup>
import { useToast } from '@/composables/useToast'

const { success } = useToast()

// Watch for successful deletion
watch(
  () => props.items.data,
  (newItems) => {
    if (isDeleting.value && itemToDelete.value) {
      const stillExists = newItems.some(item => item.id === itemToDelete.value?.id)
      if (!stillExists) {
        success(`${itemType} "${itemToDelete.value.name}" deleted successfully!`)
        // Clean up modal state
      }
    }
  },
  { deep: true }
)
</script>
```

## Alert Variants & When to Use

### Success (Green)
- ✅ Successful form submissions
- ✅ Successful delete operations
- ✅ Data saved/updated confirmations
- ✅ Import/export completions

### Error/Destructive (Red)
- ❌ Form validation errors
- ❌ API request failures
- ❌ Permission denied errors
- ❌ Critical system errors

### Warning (Yellow)
- ⚠️ Form validation warnings
- ⚠️ Data conflicts
- ⚠️ Confirmation prompts
- ⚠️ Deprecated feature notices

### Info (Blue)
- ℹ️ System notifications
- ℹ️ Feature announcements
- ℹ️ Help tips
- ℹ️ Status updates

### Default (Gray)
- 📝 General notifications
- 📝 Neutral system messages

## Best Practices

### When to Use Toasts vs Alerts

**Use Toasts for:**
- Success confirmations
- Brief status updates
- Non-critical notifications
- Temporary feedback

**Use Persistent Alerts for:**
- Form validation errors
- Critical warnings
- Information requiring user action
- Error states that need acknowledgment

### Toast Guidelines

```typescript
// ✅ Good - Clear, actionable message
success('User John Doe created successfully')

// ❌ Avoid - Too verbose
success('The user with name John Doe and email john@example.com has been successfully created and added to the database with ID 123')

// ✅ Good - Use descriptions for additional context
success('User created', {
  description: 'John Doe has been added to the Admin role'
})

// ✅ Good - Appropriate duration
error('Failed to save', { duration: 6000 }) // Longer for errors

// ✅ Good - Use promise toasts for async operations
promise(saveUser(userData), {
  loading: 'Creating user...',
  success: (user) => `User ${user.name} created!`,
  error: 'Failed to create user'
})
```

### Alert Guidelines

```typescript
// ✅ Good - Persistent error that needs attention
error('Unable to connect to server', { persistent: true })

// ✅ Good - Dismissible info alert
info('New features available in settings', { dismissible: true })

// ✅ Good - Auto-dismissing warning
warning('Session expires in 5 minutes', { duration: 10000 })

// ❌ Avoid - Non-persistent critical errors
error('Payment failed', { persistent: false }) // Should be persistent!
```

## Laravel Integration

### Flash Messages

The system automatically handles Laravel flash messages:

```php
// In your controller
return redirect()->back()->with('success', 'User created successfully!');
// Automatically becomes a toast notification
```

**Supported flash keys:**
- `success` → Success toast
- `error` → Error toast  
- `warning` → Warning toast
- `info` → Info toast
- `message` → Default alert

### API Responses

For API endpoints, return structured responses:

```php
// Success response
return response()->json([
    'message' => 'User created successfully',
    'data' => $user
], 201);

// Error response
return response()->json([
    'message' => 'Validation failed',
    'errors' => $validator->errors()
], 422);
```

## Testing

Test your alerts in the dedicated test page at `/test-alerts` which provides:

- Examples of all alert variants
- Toast notification testing
- Promise-based toast testing
- Interactive examples

## Accessibility

The alert system is built with accessibility in mind:

- Proper ARIA labels and roles
- Keyboard navigation support
- Screen reader compatibility
- High contrast support in dark mode
- Dismissible alerts with clear close buttons

## Browser Support

- Modern browsers with ES6+ support
- Vue 3 compatible browsers
- Toast animations require CSS transitions support

## Troubleshooting

### Toasts Not Appearing
- Verify Toaster component is included in layout
- Check for JavaScript errors in console
- Ensure vue-sonner is properly installed

### Alerts Not Persistent
- Check if persistent: true is set in options
- Verify duration is not overriding persistence
- Check if clearAlerts() is being called unexpectedly

### Styling Issues
- Verify Tailwind CSS classes are available
- Check for CSS conflicts with existing styles
- Ensure dark mode variants are working correctly

### Flash Messages Not Working
- Verify handleFlashMessages is called in layout
- Check that Laravel flash data structure matches expected format
- Ensure usePage() is properly accessing flash data

## Examples

See the complete examples in `/test-alerts` page and refer to the implementation in:
- `resources/js/components/schools/SchoolClassModal.vue`
- `resources/js/components/roles/RolesTable.vue`
- `resources/js/components/users/UsersTable.vue`