# Sales Application

A comprehensive sales management system built with Laravel 12, Vue 3, and Inertia.js, featuring schools management, user authentication, and role-based permissions.

## Tech Stack

### Backend
- **Laravel 12** - Latest Laravel framework with streamlined structure
- **PHP 8.2+** - Modern PHP with type declarations and enums
- **SQLite** - Default database (configurable to MySQL/PostgreSQL)
- **Inertia.js** - Modern monolith approach, no separate API needed

### Frontend
- **Vue 3** - Composition API with TypeScript support
- **Inertia.js v2** - SPA-like experience with server-side routing
- **Tailwind CSS v4** - Utility-first styling framework
- **shadcn/ui** - Comprehensive component library via reka-ui/radix-vue

### Development & Testing
- **Pest 4** - Browser testing with visual regression support
- **Laravel Telescope** - Application debugging and monitoring
- **Laravel Pint** - Code formatting and style enforcement
- **ESLint + Prettier** - Frontend code quality tools

### Key Packages
- **Spatie Packages**: Permissions, Settings, Query Builder, Activity Log, Backup, Media Library
- **Laravel Auditing** - Model change tracking
- **Laravel Excel** - Spreadsheet import/export
- **Laravel DomPDF** - PDF generation

## Features

### 🏫 Schools Management System
- Complete CRUD operations for schools and related entities
- 8 related models: contacts, addresses, management, officials, documents, academic years, classes
- Advanced filtering, sorting, and pagination
- Bulk operations and soft delete support
- RESTful API with comprehensive frontend integration

### 📁 Categories Management System
- Hierarchical category structure with unlimited parent-child depth
- Complete CRUD operations with soft delete support
- **Advanced filtering system** with search across names, descriptions, and slugs
- **Visual filter management** with active filter chips and quick preset buttons
- **Comprehensive filtering options**: date ranges, creator filters, children/products status
- **CategoryTree expansion** with working "Expand All/Collapse All" functionality
- **Debounced search** (500ms) for optimal performance and user experience  
- Statistics dashboard with real-time metrics
- Breadcrumb navigation and visual tree structure with proper chevron icons
- Filter state persistence across pagination and navigation
- Permission-based access control

### 🔐 Authentication & Authorization
- Complete auth scaffolding (login, register, password reset, email verification)
- Role-based permissions with Spatie Laravel Permission
- Protected routes with middleware and throttling
- User settings and profile management

### 🗑️ Delete Confirmation System
- Professional delete confirmation modals across all management interfaces
- Consistent user experience replacing browser confirm dialogs
- Loading states and automatic modal cleanup after successful operations
- Accessible design with clear action buttons and descriptive messaging
- Integrated with Schools, Users, and Roles management workflows

### ⚙️ Settings Management
- Type-safe settings classes (General, Mail, Sales, Inventory)
- JSON-based flexible configuration storage
- Deployment initialization commands

### 📊 Auditing & Activity Logging
- Comprehensive audit system with timeline dashboard
- Model change tracking and user activity logs
- Admin audit route access with frontend permissions

## Quick Start

### Prerequisites
- PHP 8.2+
- Node.js 18+
- Composer

### Installation

1. **Clone and install dependencies**:
   ```bash
   git clone <repository-url>
   cd sales-app
   composer install
   npm install
   ```

2. **Environment setup**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database setup**:
   ```bash
   php artisan migrate:fresh --seed
   php artisan settings:init
   ```

4. **Start development**:
   ```bash
   composer run dev
   ```
   This starts Laravel server, queue worker, logs, and Vite dev server concurrently.

## Development Commands

### Development Environment
```bash
composer run dev          # Complete dev environment
composer run dev:ssr      # Development with SSR support
npm run dev              # Vite dev server only
php artisan serve        # Laravel server only
```

### Building & Deployment
```bash
npm run build            # Build production assets
npm run build:ssr        # Build with SSR for production
```

### Code Quality
```bash
vendor/bin/pint --dirty  # Format PHP code
npm run format           # Format JS/Vue with Prettier  
npm run lint             # Lint and fix JS/Vue with ESLint
```

### Testing
```bash
php artisan test                              # Run all tests
php artisan test --filter=testName          # Run specific test
php artisan test tests/Feature/SchoolTest.php # Run specific file
php artisan test tests/Browser               # Run browser tests
```

### Database
```bash
php artisan migrate                # Run migrations
php artisan migrate:fresh --seed   # Reset and seed database
php artisan db:seed               # Seed database
```

## Project Structure

### Laravel 12 Architecture
```
├── app/
│   ├── Http/Controllers/
│   │   ├── Api/              # API endpoints
│   │   └── Frontend/         # Inertia.js endpoints
│   ├── Models/               # Eloquent models
│   └── Console/Commands/     # Auto-registered commands
├── bootstrap/
│   ├── app.php              # Middleware & routing registration
│   └── providers.php        # Service providers
├── resources/js/
│   ├── pages/               # Inertia.js Vue pages
│   ├── components/ui/       # shadcn/ui components
│   └── types/               # TypeScript definitions
└── tests/
    ├── Feature/             # Feature tests
    ├── Unit/               # Unit tests  
    └── Browser/            # Browser tests (Pest 4)
```

### Key Models & Relationships
- **School** - Central entity with 8 related models
- **User** - Authentication with role-based permissions
- **Category** - Hierarchical category management
- **Settings** - Flexible JSON-based configuration

## API Endpoints

### Schools API
```
GET    /api/schools           # List schools with filtering
POST   /api/schools           # Create school
GET    /api/schools/{id}      # Show school with relationships
PUT    /api/schools/{id}      # Update school
DELETE /api/schools/{id}      # Soft delete school
POST   /api/schools/{id}/restore # Restore soft deleted school
```

### Categories API
```
GET    /api/categories        # List categories with hierarchy
POST   /api/categories        # Create category
GET    /api/categories/{id}   # Show category with children
PUT    /api/categories/{id}   # Update category
DELETE /api/categories/{id}   # Soft delete category
POST   /api/categories/{id}/restore # Restore soft deleted category
GET    /api/categories/statistics   # Category statistics
```

### Frontend Routes
```
GET /schools                  # Schools index page
GET /schools/create          # School creation form
GET /schools/{id}            # School detail view
GET /schools/{id}/edit       # School edit form

GET /categories              # Categories index page
GET /categories/{id}         # Category detail view with children
GET /categories/tree         # Tree view visualization
```

## Testing Strategy

- **Feature Tests**: API endpoints, validation, relationships
- **Browser Tests**: End-to-end user workflows with Pest 4
- **Unit Tests**: Individual model and service logic
- **Factories & Seeders**: Comprehensive test data generation

## Recent Enhancements

### Delete Confirmation Modal System (September 2025)
- **Professional Delete Confirmations** - Replaced browser confirm dialogs with branded, accessible modal interfaces
- **Consistent Architecture** - Implemented consistent pattern across Schools, Users, and Roles management
- **Enhanced UX** - Added loading states, automatic cleanup, and descriptive confirmation messages
- **Vue 3 Integration** - Built with Composition API using reactive state management and watchers
- **shadcn/ui Components** - Leveraged Dialog components for professional, accessible design

### Categories System Fixes (September 2025)
- **Fixed search functionality** - Search now properly affects the category table with corrected Vue.js watcher logic
- **Fixed CategoryTree expansion** - "Expand All/Collapse All" functionality now works correctly with proper state management
- **Enhanced filtering system** - Added comprehensive advanced filtering with visual filter chips and quick presets
- **Improved performance** - Separated search watcher with 500ms debouncing for optimal user experience
- **Better UX** - Added active filter chips with individual removal options and filter state persistence

### Technical Improvements
- **Backend**: Enhanced CategoryController with advanced filtering capabilities (date ranges, creator filters, etc.)
- **Frontend**: Fixed Vue.js watcher patterns for proper reactivity and state management  
- **UI/UX**: Implemented visual filter chips, collapsible advanced filters, and quick filter presets
- **TypeScript**: Updated CategoryFilters interface with comprehensive filter option types
- **Performance**: Optimized search with debouncing and improved state management patterns
- **Modal System**: Created reusable DeleteConfirmationModal component with consistent architecture
- **State Management**: Enhanced component lifecycle management with automatic cleanup patterns

## Contributing

1. Follow existing code conventions and Laravel Boost guidelines
2. Write tests for all new features and bug fixes
3. Run code quality tools before committing:
   ```bash
   vendor/bin/pint --dirty
   npm run format
   npm run lint
   ```
4. Ensure all tests pass: `php artisan test`
