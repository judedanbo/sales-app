# Momentum Trail User Manual

This manual documents the integration and usage of Momentum Trail for type-safe routing in the Sales App.

## Overview

Momentum Trail provides type-safe route generation for Laravel applications, enabling frontend TypeScript code to reference Laravel routes with full IDE support and compile-time validation.

## Installation and Configuration

### 1. Package Installation
```bash
# Laravel package
composer require momentum-trail/laravel-trail

# NPM package
npm install momentum-trail
```

### 2. Laravel Configuration

The package is configured in `config/trail.php`:

```php
<?php

return [
    'output' => [
        'routes' => resource_path('scripts/routes/routes.json'),
        'typescript' => resource_path('scripts/types/routes.d.ts'),
    ],
];
```

### 3. Directory Structure

Momentum Trail requires specific directories for generated files:

```
resources/
├── scripts/
│   ├── routes/
│   │   └── routes.json          # Generated route data
│   └── types/
│       └── routes.d.ts          # TypeScript definitions
└── js/
    └── routes/
        └── inventory/
            └── index.ts         # Route helpers
```

### 4. Frontend Integration

The Vue.js integration is configured in `resources/js/app.ts`:

```typescript
import { trail } from 'momentum-trail';
import routeData from '../scripts/routes/routes.json';

createInertiaApp({
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        app.use(plugin);
        app.use(trail, {
            routes: routeData,
            absolute: false
        });

        app.mount(el);
    }
});
```

## Usage Guide

### 1. Route Generation

To generate route definitions after adding new routes:

```bash
php artisan trail:generate
```

This command creates:
- `resources/scripts/routes/routes.json` - Route data for frontend
- `resources/scripts/types/routes.d.ts` - TypeScript definitions

### 2. Creating Route Helpers

Create route helper files for specific sections:

**File: `resources/js/routes/inventory/index.ts`**
```typescript
import { route, current } from 'momentum-trail';

export const index = () => route('inventory.index');
export const movements = () => route('inventory.movements');
```

### 3. Using Route Helpers in Components

```vue
<script setup lang="ts">
import { index as inventoryIndex } from '@/routes/inventory';

// Direct usage
const navigateToInventory = () => {
    router.visit(inventoryIndex());
};
</script>

<template>
    <Link :href="inventoryIndex()">
        Inventory Dashboard
    </Link>
</template>
```

### 4. Using the Route Function Directly

```typescript
import { route } from 'momentum-trail';

// Generate URLs for routes
const inventoryUrl = route('inventory.index');           // "/inventory"
const movementsUrl = route('inventory.movements');       // "/inventory/movements"
const productUrl = route('products.show', { id: 123 }); // "/products/123"
```

### 5. Navigation Integration

**AppSidebar.vue Example:**
```vue
<script setup lang="ts">
import { index as inventoryIndex } from '@/routes/inventory';
import { Warehouse } from 'lucide-vue-next';

const navigationItems = [
    {
        title: 'Inventory',
        href: inventoryIndex(),
        icon: Warehouse,
    }
];
</script>
```

## Advanced Features

### 1. Route Parameters

For routes with parameters:

```typescript
// Laravel route: /products/{id}/edit
const editUrl = route('products.edit', { id: productId });

// Laravel route: /schools/{school}/classes/{class}
const classUrl = route('schools.classes.show', {
    school: schoolId,
    class: classId
});
```

### 2. Current Route Detection

```typescript
import { current } from 'momentum-trail';

// Check if current route matches
const isInventoryPage = current('inventory.*');
const isSpecificRoute = current('inventory.index');
```

### 3. TypeScript Autocomplete

With proper configuration, your IDE will provide:
- ✅ Route name autocomplete
- ✅ Parameter validation
- ✅ Compile-time error checking
- ✅ IntelliSense for all available routes

## Development Workflow

### 1. Adding New Routes

1. Add routes to Laravel (`routes/web.php` or `routes/api.php`)
2. Run `php artisan trail:generate`
3. Create route helpers in appropriate files
4. Use in Vue components with full TypeScript support

### 2. Testing Routes

```bash
# Test that routes are generated correctly
php artisan trail:generate

# Verify build works
npm run build

# Check route output
cat resources/scripts/routes/routes.json | grep inventory
```

### 3. Development Server

Routes are automatically available during development:
```bash
# Start dev environment
composer run dev

# Route helpers work immediately in browser
# http://localhost:5173/resources/js/routes/inventory/index.ts
```

## Troubleshooting

### Common Issues

#### 1. "getRoutes() is undefined" Error

**Cause:** Momentum Trail not properly initialized in Vue app

**Solution:** Ensure proper Vue plugin registration in `app.ts`:
```typescript
app.use(trail, {
    routes: routeData,
    absolute: false
});
```

#### 2. Route Files Not Generated

**Cause:** Directory structure missing

**Solution:** Create required directories:
```bash
mkdir -p resources/scripts/routes
mkdir -p resources/scripts/types
```

#### 3. TypeScript Errors

**Cause:** Route definitions not regenerated after Laravel route changes

**Solution:** Regenerate routes:
```bash
php artisan trail:generate
npm run build
```

#### 4. Build Errors

**Cause:** Missing route data import

**Solution:** Ensure route data is imported correctly:
```typescript
import routeData from '../scripts/routes/routes.json';
```

### Debugging

1. **Check route generation:**
   ```bash
   php artisan trail:generate --verbose
   ```

2. **Verify JSON output:**
   ```bash
   cat resources/scripts/routes/routes.json | head -10
   ```

3. **Test in browser console:**
   ```javascript
   // Should show route function
   console.log(route);

   // Test route generation
   console.log(route('inventory.index'));
   ```

## Best Practices

### 1. Organization

- Group related route helpers in logical files
- Use clear, descriptive export names
- Follow consistent naming conventions

### 2. Type Safety

- Always use route helpers instead of hardcoded URLs
- Let TypeScript catch route errors at compile time
- Use IDE autocomplete for route discovery

### 3. Performance

- Route data is bundled at build time
- No runtime route generation overhead
- Cached route lookups for optimal performance

### 4. Maintenance

- Regenerate routes after Laravel route changes
- Keep route helpers organized and up-to-date
- Use meaningful route names in Laravel

## Integration Examples

### Currency Composable Integration

The currency composable (`useCurrency.ts`) works seamlessly with momentum-trail routing:

```typescript
import { useCurrency } from '@/composables/useCurrency';
import { index as inventoryIndex } from '@/routes/inventory';

const { formatCurrency } = useCurrency();

// Navigate to inventory with currency formatting
const viewInventoryWithValue = (value: number) => {
    console.log(`Viewing inventory worth: ${formatCurrency(value)}`);
    router.visit(inventoryIndex());
};
```

### Inventory Navigation

Complete example of inventory navigation with momentum-trail:

```vue
<script setup lang="ts">
import { index as inventoryIndex, movements as inventoryMovements } from '@/routes/inventory';
import { Warehouse, TrendingUp } from 'lucide-vue-next';

const navigationItems = [
    {
        title: 'Inventory Dashboard',
        href: inventoryIndex(),
        icon: Warehouse,
        description: 'View stock levels and health'
    },
    {
        title: 'Stock Movements',
        href: inventoryMovements(),
        icon: TrendingUp,
        description: 'Track inventory changes'
    }
];
</script>
```

## Conclusion

Momentum Trail provides a robust, type-safe routing solution that enhances developer experience and reduces runtime errors. The integration enables:

- **Type Safety:** Compile-time route validation
- **IDE Support:** Full autocomplete and IntelliSense
- **Maintainability:** Centralized route management
- **Performance:** Zero runtime overhead for route generation
- **Developer Experience:** Seamless Laravel-Vue integration

For additional help or questions, refer to the [Momentum Trail Documentation](https://momentum-trail.dev) or check the project's GitHub repository.