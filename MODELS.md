# Laravel Models Documentation

## Overview

This document outlines all Eloquent models required for the Sales Management Application.

**Updated Model Count**: **21 models** (reduced from 27) due to package implementations:

- Models replaced by packages: 6
- Active custom models needed: 21

## 1. Core Sales & Inventory Models (8 models)

### User

- **Extends**: Laravel's default User model
- **Purpose**: Authentication and authorization for all user types
- **Key Fields**: name, email, password, role, user_type, school_id (nullable)
- **Relationships**:
    - HasMany: Sales (as cashier)
    - HasOne: SchoolOfficial
- **Roles**: staff, admin, audit, school_admin, principal, teacher, system_admin

### Category

- **Purpose**: Product categorization
- **Key Fields**: name, description, sort_order, is_active
- **Relationships**:
    - HasMany: Products
- **Categories**: Uniforms, Books, Stationery, Merchandise

### Product

- **Purpose**: Core product information
- **Key Fields**: sku, name, description, category_id, is_active
- **Relationships**:
    - BelongsTo: Category
    - HasMany: ProductPrices
    - HasOne: Inventory
    - HasMany: SaleItems
    - HasMany: ClassProductRequirements

### ProductPrice

- **Purpose**: Versioned pricing with approval workflow
- **Key Fields**: product_id, version_number, price, final_price, status, valid_from, approved_by, approved_at
- **Relationships**:
    - BelongsTo: Product
    - BelongsTo: User (approver)
- **Statuses**: draft, pending, approved, active, expired

### Inventory

- **Purpose**: Stock management and tracking
- **Key Fields**: product_id, quantity, reorder_level, location, last_counted_at
- **Relationships**:
    - BelongsTo: Product
    - HasMany: StockMovements

### Sale

- **Purpose**: Sales transaction records
- **Key Fields**: sale_number, sale_date, total_amount, payment_method, cashier_id, status, voided_by, voided_at
- **Relationships**:
    - BelongsTo: User (cashier)
    - HasMany: SaleItems
    - BelongsTo: School (optional)
- **Statuses**: completed, voided, pending

### SaleItem

- **Purpose**: Individual line items in sales
- **Key Fields**: sale_id, product_id, quantity, unit_price, subtotal
- **Relationships**:
    - BelongsTo: Sale
    - BelongsTo: Product

### StockMovement

- **Purpose**: Track all inventory changes
- **Key Fields**: product_id, movement_type, quantity, reference_id, reference_type, notes, user_id
- **Relationships**:
    - BelongsTo: Product
    - BelongsTo: User
    - MorphTo: Reference (Sale, Adjustment, etc.)
- **Movement Types**: sale, return, adjustment, restock, damage

## 2. School Management Models (10 models)

### School

- **Purpose**: Core school information
- **Key Fields**: school_code, school_name, school_type, board_affiliation, established_date, is_active
- **Relationships**: idels
    - HasMany: SchoolContacts
    - HasMany: SchoolAddresses
    - HasOne: SchoolManagement
    - HasMany: SchoolOfficials
    - HasMany: SchoolDocuments
    - HasMany: AcademicYears
    - HasMany: SchoolClasses
    - HasMany: Sales

### SchoolContact

- **Purpose**: Contact information for schools
- **Key Fields**: school_id, contact_type, phone_primary, phone_secondary, email_primary, email_secondary, website
- **Relationships**:
    - BelongsTo: School
- **Contact Types**: main, admission, accounts, support

### SchoolAddress

- **Purpose**: Physical and mailing addresses
- **Key Fields**: school_id, address_type, address_line1, address_line2, city, state_province, postal_code, country
- **Relationships**:
    - BelongsTo: School
- **Address Types**: physical, mailing, billing

### SchoolManagement

- **Purpose**: Management and ownership information
- **Key Fields**: school_id, management_type, ownership_type, managing_authority, board_name
- **Relationships**:
    - BelongsTo: School
- **Management Types**: private, government, trust, society

### SchoolOfficial

- **Purpose**: School administrators and staff
- **Key Fields**: school_id, user_id, official_type, name, qualification, department, email, phone, is_primary
- **Relationships**:
    - BelongsTo: School
    - BelongsTo: User
- **Official Types**: principal, vice_principal, admin, accountant, coordinator

### SchoolDocument

- **Purpose**: Official documents and certificates
- **Key Fields**: school_id, document_type, document_name, document_number, file_url, issue_date, expiry_date
- **Relationships**:
    - BelongsTo: School
- **Document Types**: registration, affiliation, tax_certificate, license

### AcademicYear

- **Purpose**: Academic year periods
- **Key Fields**: school_id, year_name, start_date, end_date, is_current
- **Relationships**:
    - BelongsTo: School
    - HasMany: ClassProductRequirements

### SchoolClass

- **Purpose**: Grade levels and classes
- **Key Fields**: school_id, class_name, class_code, grade_level, min_age, max_age, order_sequence
- **Relationships**:
    - BelongsTo: School
    - HasMany: ClassProductRequirements

### ClassProductRequirement

- **Purpose**: Required products per class per academic year
- **Key Fields**: school_id, academic_year_id, class_id, product_id, is_required, quantity
- **Relationships**:
    - BelongsTo: School
    - BelongsTo: AcademicYear
    - BelongsTo: SchoolClass
    - BelongsTo: Product

### EmailTemplate

- **Purpose**: Reusable email templates
- **Key Fields**: template_name, template_type, subject, body_html, body_text, variables, is_active
- **Relationships**:
    - HasMany: EmailQueues
- **Template Types**: receipt, low_stock_alert, welcome, password_reset

## 3. Communication & File Management Models (2 models - reduced from 5)

### EmailQueue

- **Purpose**: Queue system for email processing
- **Key Fields**: recipient_email, subject, body, template_id, template_data, status, priority, attempts, sent_at
- **Relationships**:
    - BelongsTo: EmailTemplate (optional)
- **Statuses**: pending, processing, sent, failed

### ImportExportLog (Optional)

- **Purpose**: Track import/export operations if detailed logging needed beyond Laravel Excel
- **Key Fields**: type, file_name, file_path, status, total_records, processed_records, success_records, failed_records, error_log, user_id, metadata
- **Relationships**:
    - BelongsTo: User
- **Types**: product_import, inventory_import, sales_export, report_export
- **Note**: Only create if you need detailed tracking beyond what Laravel Excel provides

## 4. Authentication & Security Models (3 models)

### EmailVerificationToken

- **Purpose**: Email verification for registration and email changes
- **Key Fields**: user_id, email, token, expires_at, verified_at
- **Relationships**:
    - BelongsTo: User

### LoginAttempt

- **Purpose**: Track failed login attempts for security
- **Key Fields**: email, ip_address, user_agent, attempted_at, success, failure_reason
- **Relationships**:
    - BelongsTo: User (nullable)

### PasswordResetToken

- **Purpose**: Secure password reset tokens
- **Key Fields**: email, token, expires_at, used_at
- **Note**: Laravel provides password_reset_tokens table by default

## 5. Models Replaced by Packages

### ~~AuditTrail~~ → Replaced by owen-it/laravel-auditing

- **Package Table**: `audits`
- **No custom model needed** - Use `OwenIt\Auditing\Models\Audit`
- **Usage**: Add `Auditable` trait to models that need auditing

### ~~FileStorage~~ → Replaced by spatie/laravel-medialibrary

- **Package Table**: `media`
- **No custom model needed** - Use `Spatie\MediaLibrary\MediaCollections\Models\Media`
- **Usage**: Add `InteractsWithMedia` trait to models that need file uploads

### ~~SmtpConfiguration~~ → Replaced by spatie/laravel-settings

- **Settings Class**: Create `MailSettings` class extending `Settings`
- **Storage**: Database or file-based settings storage
- **No model needed** - Settings are accessed via settings classes

### ~~ImportJob/ExportJob~~ → Simplified with maatwebsite/excel

- **Laravel Excel handles import/export** with queued jobs
- **Can use Laravel's built-in `jobs` table for tracking**
- **Optional**: Create simplified `ImportExportLog` model if detailed tracking needed

### ~~User Roles Management~~ → Replaced by spatie/laravel-permission

- **Package Tables**: `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`
- **No custom models needed** - Use `Spatie\Permission\Models\Role` and `Spatie\Permission\Models\Permission`

### ~~Activity Logging~~ → Replaced by spatie/laravel-activitylog

- **Package Table**: `activity_log`
- **No custom model needed** - Use `Spatie\Activitylog\Models\Activity`
- **Usage**: Automatic activity logging with `LogsActivity` trait

## Model Creation Commands

```bash
# Core Sales & Inventory
php artisan make:model Category -mfs
php artisan make:model Product -mfs
php artisan make:model ProductPrice -mf
php artisan make:model Inventory -mf
php artisan make:model Sale -mfs
php artisan make:model SaleItem -mf
php artisan make:model StockMovement -mf

# School Management
php artisan make:model School -mfs
php artisan make:model SchoolContact -mf
php artisan make:model SchoolAddress -mf
php artisan make:model SchoolManagement -mf
php artisan make:model SchoolOfficial -mf
php artisan make:model SchoolDocument -mf
php artisan make:model AcademicYear -mf
php artisan make:model SchoolClass -mf
php artisan make:model ClassProductRequirement -mf
php artisan make:model EmailTemplate -mfs

# Communication & File Management (reduced)
php artisan make:model EmailQueue -mf
php artisan make:model ImportExportLog -mf  # Optional - only if detailed tracking needed

# Authentication & Security
php artisan make:model EmailVerificationToken -m
php artisan make:model LoginAttempt -m
php artisan make:model PasswordResetToken -m

# Note: -m = migration, -f = factory, -s = seeder
```

## Implementation Notes

1. **User Model**: Extend the existing Laravel User model with additional fields via migration
    - Add `HasRoles` trait from spatie/laravel-permission
2. **Soft Deletes**: Consider adding soft deletes to Product, School, and User models
3. **UUID**: Consider using UUIDs for sensitive models like Sale and Payment
4. **Indexing**: Add database indexes on frequently queried fields (sku, sale_number, school_code)
5. **Package Traits to Use**:
    - `Auditable` (owen-it/laravel-auditing) - Add to models needing audit trail
    - `InteractsWithMedia` (spatie/laravel-medialibrary) - Add to models needing file uploads
    - `LogsActivity` (spatie/laravel-activitylog) - Add for activity logging
    - `HasRoles` (spatie/laravel-permission) - Add to User model
6. **Settings Classes**: Create settings classes for app configuration (MailSettings, GeneralSettings, etc.)
7. **Validation**: Create Form Request classes for each model's validation rules
8. **API Resources**: Create API Resource classes for each model for consistent API responses

## Package Configuration Required

1. **spatie/laravel-permission**: Run migrations, assign roles to users
2. **owen-it/laravel-auditing**: Configure which models to audit in config/audit.php
3. **spatie/laravel-medialibrary**: Configure image conversions and storage
4. **maatwebsite/excel**: Configure import/export classes for each data type
5. **spatie/laravel-settings**: Create settings classes and run migrations
6. **spatie/laravel-backup**: Configure backup destinations and schedule
7. **spatie/laravel-activitylog**: Configure which events to log

## Database Considerations

- **Foreign Key Constraints**: Implement CASCADE or RESTRICT based on business rules
- **Unique Constraints**: SKU (products), sale_number (sales), school_code (schools)
- **Composite Indexes**: (school_id, academic_year_id) for requirements
- **Performance**: Consider partitioning sales table by date for large datasets
- **Backup Strategy**: Daily backups with point-in-time recovery capability
