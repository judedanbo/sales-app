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

### Role Permissions
Each role can have multiple permissions assigned:
- View permissions by clicking on a role
- Add/remove permissions as needed
- Changes take effect immediately

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

*Last Updated: September 2025*
*Version: 1.0.0*