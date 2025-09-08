# Sales App User Manual

## Table of Contents
1. [Overview](#overview)
2. [User Management](#user-management)
3. [Role Management](#role-management)
4. [School Management](#school-management)
5. [Settings](#settings)

## Overview

The Sales App is a comprehensive management system designed for educational institutions to manage schools, users, roles, and various administrative tasks. Built with Laravel 12 and Vue 3, it provides a modern, responsive interface with real-time updates.

## User Management

### Accessing User Management
Navigate to `/users` or click "Users" in the main navigation menu.

### Features

#### User Statistics Dashboard
- View total users count
- Monitor active vs inactive users
- Track user distribution by type (System Admin, Admin, Staff)
- See users by role assignments

#### User Filtering & Search
- **Search**: Type in the search box to find users by name or email
- **Filter by Type**: Select from System Admin, Admin, or Staff
- **Filter by School**: Choose specific schools from the dropdown
- **Filter by Status**: Toggle between Active/Inactive users
- **Filter by Role**: Filter users by their assigned roles
- **Clear Filters**: Reset all filters with one click

#### User Table Features
- **Sorting**: Click column headers to sort by:
  - Name (A-Z or Z-A)
  - Email
  - User Type
  - School
  - Status
  - Created Date
- **Pagination**: Navigate through user pages with Previous/Next buttons
- **Bulk Selection**: Select multiple users for bulk operations

#### Creating New Users
1. Click the "Add User" button
2. Fill in required fields:
   - Name
   - Email
   - Password (minimum 8 characters)
   - User Type (System Admin, Admin, or Staff)
3. Optional fields:
   - School assignment
   - Phone number
   - Department
   - Bio/Description
   - Active status
   - Initial role assignments
4. Click "Create User" to save

#### Editing Users
1. Click the edit icon next to any user
2. Modify user information
3. Save changes

#### Deleting Users
- **Soft Delete**: Click the trash icon to move user to trash (recoverable)
- **Restore**: For deleted users, click the restore icon
- **Permanent Delete**: Available for soft-deleted users only

## Role Management

### Accessing Role Management
Navigate to `/roles` or access through the Users section.

### Role System Overview

The application features a comprehensive role-based access control system with 15 hierarchical roles and 83 granular permissions organized into 10 categories:

#### Available Roles (by hierarchy level):
1. **Super Admin** - Complete system control (83 permissions)
2. **System Admin** - Full access except critical operations (80 permissions)
3. **School Admin** - Full school management (24 permissions)
4. **Principal** - School oversight with approval authority (12 permissions)
5. **Academic Coordinator** - Academic program management (7 permissions)
6. **Department Head** - Department-specific management (9 permissions)
7. **Sales Manager** - Complete sales operations (20 permissions)
8. **Sales Rep** - Personal sales management (4 permissions)
9. **Finance Officer** - Financial operations (11 permissions)
10. **HR Manager** - Human resources management (9 permissions)
11. **IT Support** - Technical support (7 permissions)
12. **Data Analyst** - Analytics and reporting (14 permissions)
13. **Auditor** - Compliance and audit (11 permissions)
14. **Teacher** - Class and student management (4 permissions)
15. **Staff** - Basic operational access (3 permissions)
16. **Guest** - Very limited read-only access (3 permissions)

#### Permission Categories:
- **Users** (7 permissions): User account management
- **Roles** (6 permissions): Role and permission management
- **Schools** (11 permissions): School data management
- **Sales** (10 permissions): Sales transaction management
- **Products/Inventory** (7 permissions): Product and stock management
- **Finance** (7 permissions): Financial operations
- **Reports** (6 permissions): Analytics and reporting
- **HR** (5 permissions): Human resources
- **Communication** (4 permissions): Messaging and announcements
- **System** (10 permissions): System administration
- **Support** (4 permissions): Help and support management

### Permission Statistics and Analytics

The system provides detailed permission analytics:
- **Distribution by Category**: Visual representation showing percentage breakdown of permissions (e.g., Manage: 30.1%, View: 27.7%)
- **Usage Tracking**: Most and least used permissions
- **Role Coverage**: Permissions assigned to roles vs. unused permissions
- **Permission Hierarchy**: Understanding which roles have access to specific features

### Managing User Roles

#### Opening the Role Manager
1. In the Users table, locate the user you want to manage
2. Click the shield icon in the Actions column
3. The Role Management modal will open

#### Role Management Interface

The modal displays two main sections:

**Left Side - Current Roles**
- Shows all roles currently assigned to the user
- Displays role count in the header
- Each role shows:
  - Role display name
  - Guard type (web, api, etc.)
  - Checkbox for bulk selection
  - Quick remove button (X)

**Right Side - Available Roles**
- Shows all roles that can be assigned
- Displays available count in the header
- Each role shows:
  - Role display name
  - Guard type
  - Checkbox for bulk selection
  - Quick assign button (✓)

#### Assigning Roles

**Method 1: Quick Assign**
1. Find the role in the "Available Roles" section
2. Click the green checkmark (✓) button
3. Role instantly moves to "Current Roles"

**Method 2: Bulk Assign**
1. Check multiple roles in "Available Roles"
2. Click "Assign Selected (n)" button
3. All selected roles move to "Current Roles"

#### Removing Roles

**Method 1: Quick Remove**
1. Find the role in the "Current Roles" section
2. Click the red X button
3. Role instantly moves back to "Available Roles"

**Method 2: Bulk Remove**
1. Check multiple roles in "Current Roles"
2. Click "Remove Selected (n)" button
3. All selected roles move to "Available Roles"

#### Real-time Updates
- Changes are reflected immediately in the UI
- The role lists update dynamically without page refresh
- User statistics update automatically
- Background synchronization ensures data consistency

### Creating New Roles
1. Navigate to `/roles`
2. Click "Create Role" button
3. Enter role details:
   - Name (system identifier)
   - Display Name (user-friendly name)
   - Guard Name (web, api, etc.)
   - Description
4. Assign permissions to the role
5. Save the new role

### Permission Management and Analytics

#### Viewing Permission Details
- **Permission Index**: View all permissions with filtering and search capabilities
- **Category Breakdown**: Permissions grouped by category with percentage distribution
- **Role Assignment**: See which roles have specific permissions
- **Usage Statistics**: Identify most and least used permissions

#### Permission API Endpoints
- `GET /api/permissions` - List all permissions with pagination and filtering
  - Add `?include_metadata=true` for additional guard names and statistics
- `GET /api/permissions/statistics` - Comprehensive permission analytics
- `GET /api/permissions/grouped` - Permissions grouped by category with percentages
- `GET /api/permissions/categories` - List all permission categories with counts
- `GET /api/permissions/guard-names` - List all available guard names with statistics
- `GET /api/permissions/by-role/{role}` - Permissions assigned to specific role
- `GET /api/permissions/by-user/{user}` - All permissions for specific user
- `POST /api/permissions/check-user/{user}` - Check if user has specific permission

#### Role API Endpoints
- `GET /api/roles` - List all roles with pagination and filtering
  - Add `?include_metadata=true` for additional permissions and guard names data
- `GET /api/roles/statistics` - Comprehensive role analytics and usage statistics
- `GET /api/roles/permissions` - List all available permissions grouped by category
- `GET /api/roles/guard-names` - List all available guard names with role counts
- `GET /api/roles/{role}` - Get specific role with permissions and users
- `POST /api/roles` - Create new role with permission assignments
- `PUT /api/roles/{role}` - Update role details and permissions
- `DELETE /api/roles/{role}` - Delete role (only if no users assigned)
- `POST /api/roles/{role}/sync-permissions` - Synchronize role permissions
- `POST /api/roles/{role}/assign-users` - Assign multiple users to role
- `POST /api/roles/{role}/remove-users` - Remove multiple users from role

### Role Permissions
Each role can have multiple permissions assigned:
- View permissions by clicking on a role
- Add/remove permissions as needed
- Changes take effect immediately
- Permission distribution analytics available
- Real-time permission usage tracking

## School Management

### Accessing Schools
Navigate to `/schools` or click "Schools" in the navigation.

### School Features

#### School Information
- Basic details (name, code, type)
- Contact information
- Physical and postal addresses
- Management structure
- School officials
- Academic years and classes

#### Managing Schools
1. **Create**: Click "Add School" and fill in the comprehensive form
2. **Edit**: Click edit icon to modify school details
3. **View**: Click on school name for detailed view
4. **Delete**: Soft delete with option to restore

#### School Filters
- Search by name or code
- Filter by school type
- Filter by status (active/inactive)
- Sort by various columns

## Settings

### User Settings
Access via `/settings/profile`:
- Update personal information
- Change avatar
- Modify contact details

### Password Management
Access via `/settings/password`:
- Change current password
- Enable two-factor authentication
- View security settings

### Appearance Settings
Access via `/settings/appearance`:
- Toggle dark/light mode
- Adjust display preferences
- Customize UI elements

## Keyboard Shortcuts

- `Ctrl/Cmd + K`: Open command palette
- `Ctrl/Cmd + /`: Toggle search
- `Esc`: Close modals and dialogs

## Tips & Best Practices

1. **Regular Backups**: Ensure data is backed up regularly
2. **Role Assignment**: Follow principle of least privilege
3. **User Deactivation**: Deactivate rather than delete users to maintain audit trails
4. **Bulk Operations**: Use bulk selection for efficiency when managing multiple items
5. **Filtering**: Combine multiple filters for precise data views

## Troubleshooting

### Common Issues

**Cannot assign roles:**
- Verify you have the `manage_roles` permission
- Check if the role exists and is active
- Ensure no conflicting roles are assigned

**Search not working:**
- Clear browser cache
- Check network connectivity
- Verify search syntax is correct

**Changes not saving:**
- Check internet connection
- Verify you have proper permissions
- Look for validation errors in forms

### Getting Help
- Contact system administrator for technical issues
- Report bugs at: https://github.com/anthropics/claude-code/issues
- Check documentation for updates

## Security Notes

- Passwords must be at least 8 characters
- Enable two-factor authentication for admin accounts
- Regular password changes recommended
- Monitor user activity logs
- Review role assignments periodically

---

## Frontend Component Enhancements

### Vue.js Component Updates

The application's Vue.js frontend components have been enhanced to better utilize the comprehensive API endpoints and provide improved user experience:

#### Role Management Components

**Enhanced Role Statistics Display**
- **Popular Roles**: Now displays role display names and guard information alongside user counts
- **Permission Coverage**: Shows "With Permissions" statistics for better role analysis
- **Guard Name Tracking**: Enhanced statistics include guard-based role distribution

**Improved Role Creation/Editing**
- **Categorized Permissions**: Permissions are now organized by category (Users, Roles, Schools, Sales, etc.)
- **Enhanced Data Structure**: Components receive permissions with category information and display names
- **Type Safety**: Full TypeScript integration with proper type definitions for all permission data

#### Permission Management Components

**Permission Statistics Dashboard**
- **Guard-Based Analytics**: New display showing permission distribution across different guards
- **Category Breakdown**: Visual representation of permissions organized by functional categories
- **Usage Statistics**: Enhanced tracking of permission assignment patterns

**Component Architecture**
- **Optimized Data Flow**: Components now use structured API responses with metadata
- **Performance Improvements**: Better data handling reduces unnecessary re-renders
- **Consistent UI**: Standardized display patterns across all role and permission components

#### User Role Management

**Enhanced UserRoleModal**
- **Real-time Updates**: Immediate UI feedback when assigning/removing roles
- **Bulk Operations**: Improved support for selecting multiple roles for assignment/removal
- **Better Error Handling**: Enhanced error states and loading indicators
- **Accessibility**: Improved keyboard navigation and screen reader support

#### Technical Improvements

**TypeScript Integration**
- **New Type Definitions**: Added comprehensive types for permissions, role statistics, and API responses
- **Type Safety**: Full compile-time checking for all API data structures
- **IntelliSense Support**: Better development experience with auto-completion

**API Utilization**
- **Metadata Support**: Components now leverage optional metadata parameters for richer displays
- **Guard Name Statistics**: Utilization of guard-based permission analytics
- **Category Information**: Proper use of permission categorization for organized displays

---

## Recent Updates

### Version 1.1.0 - September 2025
- **Enhanced Role System**: Added comprehensive 15-role hierarchy with 83 granular permissions
- **Permission Analytics**: Implemented percentage-based permission distribution analytics
- **API Enhancements**: Added new permission management API endpoints with statistics
- **Real-time Updates**: Improved UserRoleModal with immediate role assignment/removal updates
- **Metadata Integration**: Enhanced API controllers with optional metadata parameters for permissions and guard names
- **Guard Names Management**: Implemented comprehensive guard name tracking across role and permission controllers
- **Permission Grouping**: Added permission categorization and grouping functionality for better organization
- **Extended API Coverage**: Added dedicated endpoints for permissions list and guard names in role management
- **Vue Component Enhancements**: Updated frontend components to utilize new API endpoints with improved statistics displays
- **Frontend Performance**: Enhanced role and permission components with better data structure utilization and TypeScript type safety
- **UI Improvements**: Added guard-based permission statistics and improved role statistics with display names
- **Sample Data**: Created SampleUsersSeeder with realistic test users for each role type
- **Documentation**: Updated user manual with detailed permission system documentation and new API endpoints

### Version 1.0.0 - September 2025
- Initial release with core functionality
- User management system
- School management features
- Basic role and permission system

---

*Last Updated: September 2025*
*Version: 1.1.0*