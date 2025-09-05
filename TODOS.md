# Sales Application Development Roadmap

## Phase 1: Planning & Requirements (Week 1-2) ✅

- [x] **Requirements Gathering**
    - [x] Define target users: **Staff, Administration, Audit**
    - [x] Identify product categories: **Uniforms, Books, Stationery, Merchandise**
    - [x] Document business rules: **Cash payment only, No discounts, No credit limits**
- [x] **Technical Architecture**
    - [x] Choose tech stack: **Laravel 12 + Inertia.js v2 + Vue 3 + TypeScript + Tailwind CSS v4**
    - [x] Design system architecture: **RESTful API with /api/v1/ base, SQLite/MySQL schema defined**
        - **API Endpoints Structure:**
            - Auth: `/api/v1/auth/` (login, logout, profile, refresh-token) - Laravel Sanctum
            - Users: `/api/v1/users/` (CRUD operations, Admin only)
            - Products: `/api/v1/products/` (CRUD, search, categories)
            - Inventory: `/api/v1/inventory/` (stock management, movements, low-stock alerts)
            - Sales: `/api/v1/sales/` (POS transactions, receipts, void)
            - Reports: `/api/v1/reports/` (daily/weekly/monthly, audit-trail, exports)
            - **Communication & File Management APIs:**
                - Email Notifications: `/api/v1/notifications/email/` (Laravel Mail with queues)
                - Import/Export: `/api/v1/import/` and `/api/v1/export/` (Laravel Excel)
                - File Management: `/api/v1/files/` (Laravel Storage)
            - **School Information APIs:**
                - Schools: `/api/v1/schools/` (CRUD, contacts, addresses, management, officials, documents)
                - Academic Years: `/api/v1/schools/:id/academic-years/` (CRUD, set current year)
                - School Classes: `/api/v1/schools/:id/school-classes/` (CRUD school classes)
                - Class Requirements: `/api/v1/schools/:id/academic-years/:year_id/school-classes/:class_id/requirements`
                - Price Management: `/api/v1/products/:id/price-changes/` (versioned pricing system)
        - **Database Tables (Laravel Migrations):**
            - users (Laravel's default auth table extended)
            - categories (id, name, description)
            - products (id, sku, name, description, category_id, status)
            - inventory (id, product_id, quantity, reorder_level, location)
            - sales (id, sale_number, sale_date, total_amount, payment_method, cashier_id, status)
            - sale_items (id, sale_id, product_id, quantity, unit_price, subtotal)
            - stock_movements (id, product_id, movement_type, quantity, reference_id)
            - audit_trail (via Laravel Auditing package)
            - sessions (Laravel's database sessions)
            - **School Information System:**
                - schools (id, school_code, school_name, school_type, board_affiliation, status)
                - school_contacts (id, school_id, contact_type, phone_primary, email_primary, website)
                - school_addresses (id, school_id, address_type, address_line1, city, state_province, country)
                - school_management (id, school_id, management_type, ownership_type, managing_authority)
                - school_officials (id, school_id, user_id, official_type, name, qualification, email, phone)
                - school_documents (id, school_id, document_type, document_name, document_number, file_url)
                - academic_years (id, school_id, year_name, start_date, end_date, is_current)
                - school_classes (id, school_id, class_name, class_code, min_age, max_age, order_sequence)
                - class_product_requirements (id, school_id, academic_year_id, class_id, product_id, is_required)
                - product_prices (id, product_id, version_number, price, final_price, status, valid_from)
    - [x] Setup development environment: **Docker with Laravel Sail**
- [x] **Additional Planning Tasks**
    - [x] Define user roles and permissions (Laravel Policies & Gates):
        - **Staff**: Create sales, view products, manage own sales
        - **Admin**: Full CRUD on all entities, void sales, user management, reports
        - **Audit**: Read-only access to all data, audit trail access, export reports
        - **School Admin**: School-level management for school officials
        - **Principal**: Academic oversight and school operations
        - **Teacher**: Teaching-related functions and student management
        - **System Admin**: Full system access across all schools
    - [x] Design RESTful API structure with Laravel API Resources
    - [x] Create project structure: **Laravel 12 structure with Inertia pages in resources/js/pages/**
    - [x] Setup version control (Git repository): **Git initialized with .gitignore configured**
    - [x] Define coding standards: **Laravel Pint for PHP, ESLint + Prettier for JS/Vue**
    - [x] Create development workflow: **Git flow with main branch, feature branches**
    - [x] Setup environment variables configuration (.env structure)
    - [x] Plan data backup and recovery strategy: **Database dumps, Laravel Backup package**

## Phase 2: Database Design (Week 2-3) 🚧 IN PROGRESS

- [ ] **Core Entities Implementation**
    - [x] ✅ **Settings System** (Added ccba365)
        - [x] Create settings table migration with JSON data storage
        - [x] Implement type-safe settings classes (General, Mail, Sales, Inventory)
        - [x] Add SettingsSeeder with default configuration values
        - [x] Register settings as singletons in AppServiceProvider
        - [x] Create InitializeSettings command for deployment setup
    - [x] ✅ **Schools System** (Added 56ed8a8)
        - [x] Create Laravel migrations for schools and related tables
        - [x] Implement Eloquent models with relationships
        - [x] Add model factories for all school entities
        - [x] Create database seeders with sample data
        - [x] Build complete CRUD interface with Vue 3 + Inertia
    - [x] ✅ **User Management System** (Added Current)
        - [x] Extend users table migration with user_type, school_id, profile fields
        - [x] Create UserType enum with 7 user roles (STAFF, ADMIN, AUDIT, SCHOOL_ADMIN, PRINCIPAL, TEACHER, SYSTEM_ADMIN)
        - [x] Integrate spatie/laravel-permission with comprehensive role-based permissions
        - [x] Update User model with relationships, scopes, and helper methods
        - [x] Create UserRolesSeeder with 34 permissions across all system areas
        - [x] Enhance UserFactory with intelligent school association and custom states
        - [x] Add role assignment methods and permission checking functionality
        - [x] Comprehensive testing of user creation, role assignment, and relationships
    - [ ] Create remaining Laravel migrations for other tables
    - [ ] Implement remaining Eloquent models with relationships
    - [ ] Add model factories for remaining entities
    - [ ] Create database seeders for remaining entities
- [ ] **Database Implementation**
    - [ ] Run `php artisan make:migration` for each table
    - [ ] Define foreign keys and constraints in migrations
    - [ ] Add indexes for performance in migrations
    - [ ] Setup database triggers for audit trail (or use Laravel Auditing)
    - [ ] Create comprehensive seed data with factories
- [ ] **Price Tracking Implementation**
    - [ ] Create ProductPrice model with versioning logic
    - [ ] Implement price change approval workflow with Laravel Events
    - [ ] Add scheduled price changes with Laravel Scheduler
    - [ ] Create bulk price update jobs
- [ ] **School Class Requirements Implementation**
    - [ ] Create ClassProductRequirement model and relationships
    - [ ] Implement requirement copy functionality
    - [ ] Add validation rules for requirements
    - [ ] Create requirement templates

## Phase 3: Backend Development (Week 3-6)

- [ ] **Inventory Module**
    - [ ] Create Product controller with API Resources
    - [ ] Implement stock management service class
    - [ ] Setup low stock notifications with Laravel Notifications
    - [ ] Create inventory report generators
    - [ ] **Price Management**
        - [ ] Create PriceController with approval workflow
        - [ ] Implement price history tracking
        - [ ] Add bulk update functionality
        - [ ] Create price change notifications
    - [ ] **School Class Requirements**
        - [ ] Create RequirementController with CRUD operations
        - [ ] Implement bulk requirement management
        - [ ] Add copy from previous year functionality
        - [ ] Create compliance reporting
- [ ] **Sales Module**
    - [ ] Create SaleController for POS operations
    - [ ] Implement cart service with session storage
    - [ ] Generate receipts with Laravel PDF
    - [ ] Create sales analytics with Laravel Query Builder
- [ ] **Authentication & Authorization**
    - [ ] Setup Laravel Sanctum for API authentication
    - [ ] Create role-based middleware
    - [ ] Implement Laravel Policies for authorization
    - [ ] Add two-factor authentication (optional)
    - [ ] Setup email verification with Laravel

## Phase 4: Frontend Development (Week 5-8)

- [ ] **Inventory Interface (Vue 3 + Inertia)**
    - [ ] Create Product Index/Create/Edit pages
    - [ ] Build stock monitoring dashboard with charts
    - [ ] Implement batch updates with Vue forms
    - [ ] Add barcode scanning (optional)
    - [ ] **Price Management Interface**
        - [ ] Create price history component
        - [ ] Build approval workflow UI
        - [ ] Add bulk price update modal
        - [ ] Create pending changes dashboard
    - [ ] **School Class Requirements Interface**
        - [ ] Build requirements setup wizard
        - [ ] Create bulk management data tables
        - [ ] Add copy functionality UI
        - [ ] Build compliance dashboard with charts
- [ ] **Sales Interface**
    - [ ] Create POS terminal page with Vue 3
    - [ ] Build product search with autocomplete
    - [ ] Implement cart management with Pinia store
    - [ ] Create checkout flow with Inertia forms
- [ ] **Reports Dashboard**
    - [ ] Build dashboard with Chart.js/ApexCharts
    - [ ] Create data tables with vue-good-table
    - [ ] Add export functionality UI
    - [ ] Implement date range filters

## Phase 5: Integration & Features (Week 7-9)

- [ ] **Payment Integration**
    - [ ] Implement cash handling with drawer management
    - [ ] Add receipt printer integration
    - [ ] Create daily cash reconciliation
- [ ] **Reporting System**
    - [ ] Setup Laravel Excel for exports
    - [ ] Configure Laravel PDF for receipts
    - [ ] Implement Laravel Mail for notifications
    - [ ] Add Laravel Queue for async processing
- [ ] **Advanced Features**
    - [ ] Create import jobs with Laravel Excel
    - [ ] Implement returns with transaction reversal
    - [ ] Add audit logging with Laravel Auditing
    - [ ] Setup Laravel Telescope for debugging

## Phase 6: Testing (Week 8-10)

- [ ] **Unit Testing (Pest 4)**
    - [ ] Write model tests with factories
    - [ ] Test API endpoints with Pest
    - [ ] Validate business logic
    - [ ] Test form requests validation
- [ ] **Integration Testing**
    - [ ] Create browser tests with Pest 4
    - [ ] Test complete workflows
    - [ ] Validate payment processing
    - [ ] Test email notifications
- [ ] **Performance Testing**
    - [ ] Run Laravel Debugbar profiling
    - [ ] Optimize database queries with eager loading
    - [ ] Implement caching with Redis
    - [ ] Setup Laravel Horizon for queue monitoring

## Phase 7: Deployment & Launch (Week 10-11)

- [ ] **Production Setup**
    - [ ] Configure production server (Ubuntu/Nginx)
    - [ ] Setup Laravel Forge/Envoyer (optional)
    - [ ] Configure SSL with Let's Encrypt
    - [ ] Setup Redis for caching/sessions
- [ ] **Database Migration**
    - [ ] Run production migrations
    - [ ] Import existing data
    - [ ] Verify data integrity
- [ ] **Optimization**
    - [ ] Run `php artisan optimize`
    - [ ] Compile assets with `npm run build`
    - [ ] Configure OPcache
    - [ ] Setup CDN for assets

## Phase 8: Post-Launch (Week 12+)

- [ ] **Maintenance**
    - [ ] Monitor with Laravel Telescope
    - [ ] Track errors with Sentry/Bugsnag
    - [ ] Regular security updates
- [ ] **Enhancements**
    - [ ] Add PWA support
    - [ ] Implement real-time updates with Laravel Echo
    - [ ] Add multi-language support
    - [ ] Create mobile app with Capacitor

## Key Deliverables

### MVP Features (Weeks 1-6)

- [ ] Basic inventory tracking with Eloquent ORM
- [ ] Simple POS system with Inertia pages
- [ ] User authentication with Laravel Sanctum

### Full Release (Weeks 7-11)

- [ ] Complete reporting with Laravel Excel
- [ ] Payment processing with receipts
- [ ] Advanced inventory with low stock alerts

### Future Enhancements

- [ ] Mobile application with Laravel API
- [ ] Parent portal with Inertia
- [ ] AI-powered inventory predictions

## Development Commands Reference

### Daily Development

```bash
# Start development environment
composer run dev

# Run database migrations
php artisan migrate

# Seed database
php artisan db:seed

# Format code
vendor/bin/pint --dirty
npm run format

# Run tests
php artisan test
```

### Creating Components

```bash
# Create model with migration, factory, and seeder
php artisan make:model Product -mfs

# Create controller with API methods
php artisan make:controller ProductController --api

# Create form request
php artisan make:request StoreProductRequest

# Create test
php artisan make:test ProductTest --pest
```

### Deployment

```bash
# Build for production
npm run build
php artisan optimize

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## Tech Stack Summary

- **Backend**: Laravel 12, PHP 8.4
- **Frontend**: Vue 3, TypeScript, Inertia.js v2
- **Styling**: Tailwind CSS v4, shadcn/ui
- **Database**: SQLite (dev), MySQL/PostgreSQL (prod)
- **Testing**: Pest 4 with browser testing
- **Development Tools**: Laravel Sail, Telescope, Debugbar
- **Code Quality**: Laravel Pint, ESLint, Prettier

## Recent Updates

### January 2025

- ✅ Migrated from Nuxt.js to Laravel 12 + Inertia.js v2
- ✅ Set up Laravel Sail for Docker development
- ✅ Configured Pest 4 for modern testing
- ✅ Added Laravel Telescope and Debugbar for debugging
- ✅ Implemented Tailwind CSS v4 with new import syntax
- ✅ Created CLAUDE.md with Laravel Boost guidelines
- ✅ Set up Git repository with proper Laravel .gitignore

### September 2025

- ✅ **Settings Management System** (Commit ccba365)
    - Added comprehensive settings system with type-safe configuration classes
    - Implemented database storage with JSON data structure for flexible settings
    - Created seeder with sensible default values for all settings categories
    - Added management command for easy deployment and initialization
- ✅ **Schools Management System** (Commit 56ed8a8)
    - Created complete School model with 8 related models (contacts, addresses, management, officials, documents, academic years, classes)
    - Implemented API and Frontend controllers with full CRUD operations
    - Built Schools/Index.vue with data table, filtering, sorting, and pagination
    - Added TypeScript interfaces for all School-related models
    - Configured API routes at /api/schools/_ and web routes at /schools/_
    - Implemented soft deletes, bulk operations, and statistics endpoints
    - Created comprehensive factories and seeders for testing
- ✅ **Schools Index Checkbox Fix**
    - Fixed reka-ui Checkbox component event handling in Schools/Index.vue
    - Changed from :checked/:update:checked to :model-value/:update:model-value
    - Corrected both header select-all and individual row checkboxes
    - Verified Export Selected button appears when schools are selected
    - Updated SchoolSeeder to generate 25 records as requested
    - Added browser testing configuration and built production assets
- ✅ **Schools System Enhancements with Enums** (Commit 1b88da4)
    - Created SchoolStatus enum for better type safety and extensibility
    - Updated School model to use SchoolStatus enum with proper casting
    - Enhanced SchoolController with improved filter handling and state management
    - Added new reusable UI components (PageHeader, Pagination)
    - Created schools-specific components directory structure
    - Updated AppSidebar navigation with Schools section and proper routing
    - Added Schools/Dashboard.vue for school-specific dashboard views
    - Improved frontend filter persistence and pagination handling
    - Added comprehensive NavigationTest for route accessibility testing
    - Applied consistent code formatting across project files
- ✅ **URL Parameter Filtering Enhancement**
    - Updated Schools Index page to only include non-default filter values in URL parameters
    - Added getFilteredParameters() helper function to clean URL parameters
    - Enhanced applyFilters() and goToPage() functions to use filtered data
    - Maintains backward compatibility while providing cleaner, more meaningful URLs
    - All tests continue to pass with improved parameter handling
- ✅ **Interactive Dashboard with Schools Data** (Current)
    - **Created DashboardController** with comprehensive schools statistics and data
    - **Built Dashboard Components**:
        - DashboardStats: Real-time metrics with progress indicators and trend analysis
        - SchoolsChart: Interactive distribution charts by type and board affiliation
        - RecentSchools: Recent activity tracking with quick access actions
    - **Added Supporting UI Components**: Progress, Badge components for enhanced UX
    - **Enhanced Dashboard Features**:
        - Executive overview with total schools, active/inactive breakdown
        - Student and teacher metrics with calculated ratios
        - Data completeness tracking with visual progress indicators
        - Schools needing attention alerts with color-coded warnings
        - Interactive links to Schools management pages
        - Responsive design for desktop and mobile viewing
    - **Real-time Data Integration**: Live statistics from Schools database
    - **Performance Metrics**: Student-teacher ratios, data quality percentages
    - **Quick Actions**: Direct navigation to create, view, and manage schools
    - Successfully replaced placeholder dashboard with actionable business insights
- ✅ **Complete School Management System Enhancement** (Current)
    - **Enhanced Schools Table Actions Menu**:
        - Added organized action sections with separators for better UX
        - Implemented Add Contact and Add Address actions with Phone/MapPin icons
        - Created Add Academic Year and Add Class actions with Calendar/GraduationCap icons
        - All actions properly routed through modal systems with confirmation feedback
    - **School Class Management System**:
        - Created comprehensive SchoolClassModal.vue with full form validation
        - Built SchoolClassController API with unique class code validation per school
        - Implemented Frontend/SchoolClassController for Inertia.js integration
        - Added Schools/Classes/Index.vue with card-based responsive design
        - Features: CRUD operations, smart sorting by order sequence, age range display
        - Success notifications with 2-second auto-close and table refresh
    - **Academic Year Management System**:
        - Developed SchoolAcademicYearModal.vue with intelligent date handling
        - Created AcademicYearController with "current year" logic and transactions
        - Built Frontend/AcademicYearController for complete academic year CRUD
        - Added Schools/AcademicYears/Index.vue with status-based visual organization
        - Features: Current year highlighting, date range formatting, status calculation
        - Smart default date generation based on typical academic calendar (July-June)
    - **Frontend Architecture Improvements**:
        - Added TypeScript interfaces for SchoolClass and AcademicYear entities
        - Created dedicated Vue page components with consistent AppLayout integration
        - Implemented proper breadcrumb navigation and PageHeader components
        - Enhanced modal system with success alerts and auto-close functionality
    - **Backend API Enhancement**:
        - Built nested resource routes: /api/schools/{school}/classes and /academic-years
        - Added complete validation rules with unique constraints per school
        - Implemented soft deletes, restore, and force delete operations
        - Enhanced relationship management with proper Eloquent model connections
    - **Filter State Preservation**:
        - Fixed filter reset issues when creating/editing schools, classes, or academic years
        - Implemented getFilteredParameters() helper in both Index and Table components
        - All reload operations now preserve current filter state for better UX
        - Added preserveScroll: true to prevent page jumping during updates
    - **Testing & Quality Assurance**:
        - Successfully built production assets with Vite
        - Fixed import paths and component dependencies
        - Created test data and verification procedures
        - All modal interactions tested and working correctly
- ✅ **User Management System with Role-Based Access Control** (Completed)
    - **Extended User Model**:
        - Added migration with user_type (UserType enum), school_id, phone, department, bio fields
        - Enhanced with status, last_login_at, created_by, updated_by audit fields
        - Integrated spatie/laravel-permission HasRoles trait for role-based access control
        - Added performance indexes for user_type + status and school_id + user_type queries
    - **UserType Enum System**:
        - Created comprehensive enum with 7 user roles: STAFF, ADMIN, AUDIT, SCHOOL_ADMIN, PRINCIPAL, TEACHER, SYSTEM_ADMIN
        - Each type includes descriptive label() and description() methods for UI display
        - Proper casting in User model for type safety throughout application
    - **Role-Based Permissions System**:
        - Created UserRolesSeeder with 34 granular permissions across all system areas
        - Permissions cover: sales, products, inventory, users, schools, reports, audit, system administration
        - Each user type assigned appropriate permission set matching business requirements
        - Automatic role assignment based on user_type with assignRoleFromUserType() method
    - **Enhanced User Model Features**:
        - Added relationships: school() (BelongsTo), sales() (HasMany), schoolOfficial() (HasOne)
        - Created scopes: scopeActive(), scopeOfType(), scopeForSchool() for efficient querying
        - Helper methods: isSchoolUser(), isSystemUser(), canManageSchools(), canManageUsers()
        - Proper display name generation with titles based on user type
        - Update login timestamp tracking with updateLastLogin() method
    - **Comprehensive Factory System**:
        - Updated UserFactory with support for all new fields and intelligent school association
        - Added factory states: type(), schoolUser(), systemUser(), inactive() for testing flexibility
        - Smart shouldHaveSchool() logic automatically assigns schools to school-specific user types
        - Realistic fake data generation for phone, department, bio, and activity timestamps
    - **Testing & Verification**:
        - Comprehensive test suite covering user creation, role assignment, and permission checking
        - Verified proper school associations for school-specific user types (PRINCIPAL, TEACHER, SCHOOL_ADMIN)
        - Tested helper methods for user type classification and permission validation
        - All relationships and scopes working correctly with proper data integrity
        - Role-permission system integrated and functioning as designed
- ✅ **Schools Index Filter Bug Fix** (Completed - January 5, 2025)
    - **Fixed Filter State Management Issues**:
        - Resolved issue where selecting one filter would reset all other filters
        - Fixed search field resetting after typing only one character
        - Changed from router.visit() to router.get() with preserveState: true
        - Simplified filter parameter building to pass directly to router.get()
    - **Enhanced Filter Synchronization**:
        - Added watcher to sync localFilters with props.filters on navigation
        - Fixed handleFiltersUpdate to properly merge filter changes
        - Updated child component to emit only changed properties
        - Increased debounce delay from 300ms to 500ms for better typing experience
    - **Improved State Preservation**:
        - All filter values now properly preserved during navigation
        - Query strings correctly maintain all active filters
        - Pagination and sorting maintain filter state
        - Filter changes no longer cause other selections to reset
- ✅ **User Management System Statistics Enhancement** (Completed - January 5, 2025)
    - **Enhanced Statistics Across All Controllers**:
        - **UserController Statistics**: Added total, active, inactive, recent users count
        - Added by_type breakdown with formatted labels and counts for all user types
        - Added by_school statistics showing top 10 schools by user count with school names
        - **RoleController Statistics**: Added total, with_users, without_users, with_permissions counts
        - Added total_permissions, recent roles (last 30 days), popular_roles (top 5 by user count)
        - Added by_guard distribution showing role counts per authentication guard
        - **PermissionController Statistics**: Added total, with_roles, without_roles, categories count
        - Added usage_percentage calculation for permission utilization tracking
        - Added by_category breakdown showing permission distribution across categories
        - Added most_used permissions (top 5) with roles count and category information
    - **TypeScript Interface Updates**:
        - Updated UserStatistics interface with proper by_type structure (label + count objects)
        - Updated RoleStatistics interface with popular_roles array and by_guard Record
        - Updated PermissionStatistics interface with usage_percentage, by_category, and most_used arrays
        - All interfaces properly typed for frontend consumption with comprehensive data structures
    - **Vue Component Integration**:
        - Fixed missing statistics props issues in Roles, Permissions, and Users index pages
        - Removed unused props (guardNames, permissions) that were causing component errors
        - Updated test assertions to expect statistics props in all controller tests
        - All index pages now properly receive and display comprehensive statistics data
    - **System Testing & Validation**:
        - Successfully tested all three controllers with real data showing accurate statistics
        - Verified Users: 6 total (5 active, 1 inactive) with proper type distribution
        - Verified Roles: 3 total (all with users and permissions) with guard breakdown
        - Verified Permissions: 7 total (100% usage) with category and popularity metrics
        - All statistics calculations proven accurate and providing valuable insights for system administration
