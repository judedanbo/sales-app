# Sales Application User Manual

## Table of Contents

1. [Getting Started](#getting-started)
2. [User Management](#user-management)
3. [Role Management](#role-management)
4. [Schools Management](#schools-management)
5. [Dashboard Overview](#dashboard-overview)
6. [System Administration](#system-administration)

---

## Getting Started

### System Requirements

- Modern web browser (Chrome, Firefox, Safari, Edge)
- Internet connection
- Valid user account with appropriate permissions

### Login

1. Navigate to the application URL
2. Enter your username/email and password
3. Click "Sign In"
4. You'll be redirected to the dashboard based on your user role

### Navigation

The application uses a sidebar navigation system:

- **Dashboard**: Overview of system statistics and recent activity
- **Schools**: Manage school information, academic years, and classes
- **Users**: User account management and role assignments
- **Roles**: Role and permission management
- **Permissions**: System permission overview

---

## User Management

### Viewing Users

1. Click "Users" in the sidebar
2. View comprehensive user statistics including:
   - Total users (active/inactive breakdown)
   - Users by type (Staff, Admin, Teacher, etc.)
   - Users by school (top 10 schools by user count)
3. Use filters to find specific users:
   - Search by name or email
   - Filter by user type
   - Filter by school
   - Filter by status (active/inactive)

### Creating Users

1. Navigate to Users → Index
2. Click "Add User" button
3. Fill out the user form:
   - **Basic Information**: Name, email, phone
   - **System Access**: User type, school association
   - **Additional Details**: Department, bio
4. Click "Create User"
5. The system will automatically assign appropriate roles based on user type

### User Types

The system supports seven user types:

- **STAFF**: General staff members with basic access
- **ADMIN**: Administrative users with management privileges
- **AUDIT**: Read-only access for auditing purposes
- **SCHOOL_ADMIN**: School-level administrative access
- **PRINCIPAL**: School principal with academic oversight
- **TEACHER**: Teaching staff with student management access
- **SYSTEM_ADMIN**: Full system access across all schools

---

## Role Management

### Understanding Roles and Permissions

The application uses a comprehensive role-based access control system:

- **Roles**: Groups of permissions assigned to users
- **Permissions**: Specific actions users can perform
- **Guards**: Authentication contexts (web, api)

### Role Overview

1. Navigate to "Roles" in the sidebar
2. View role statistics:
   - Total roles in system
   - Roles with/without users assigned
   - Roles with/without permissions
   - Popular roles by user count
   - Distribution by guard type

### Managing Role Users

#### From Role Details Page

1. Navigate to Roles → [Role Name]
2. Click "Manage Users" in the Quick Actions section
3. The Role Users Modal will open with two tabs:

**Assigned Users Tab:**
- View all users currently assigned to this role
- Search users by name or email
- Select users for removal (bulk operations supported)
- Click "Remove Selected" to unassign users
- View user details: name, email, status, school, join date

**Available Users Tab:**
- View all users not currently assigned to this role
- Search available users by name or email  
- Select users for assignment (bulk operations supported)
- Click "Assign Selected" to add users to the role
- View user details: name, email, status, school, join date

#### From Roles Table

1. Navigate to Roles → Index
2. Find the role in the table
3. Click the "⋯" menu button for the role
4. Select "Manage Users"
5. The Role Users Modal opens with the same functionality as above

#### Bulk Operations

**Select All / Clear All:**
- Use "Select All" to quickly select all visible users
- Use "Clear All" to deselect all currently selected users
- Selection counters show how many users are selected

**Real-time Updates:**
- Changes are applied immediately without page refresh
- Modal data updates instantly after user assignment/removal
- Table statistics refresh automatically
- Success notifications confirm operations

#### User Information Display

Each user card shows:
- **Avatar**: User initials in a colored circle
- **Name**: Full user name
- **Status**: Active/Inactive badge with color coding
- **Email**: User's email address
- **User Type**: Role type (Staff, Admin, Teacher, etc.)
- **School**: Associated school name (if applicable)
- **Join Date**: When the user was created

### Role Permissions

1. Navigate to Roles → [Role Name]
2. View assigned permissions organized by category:
   - **Users**: User management permissions
   - **Roles**: Role and permission management
   - **Schools**: School administration permissions
   - **Sales**: Sales transaction permissions
   - **Products**: Inventory and product permissions
   - **Reports**: Reporting and analytics permissions
   - **System**: System administration permissions

3. Click "Permissions" to modify role permissions
4. Use the permissions modal to:
   - Search for specific permissions
   - Select/deselect permissions by category
   - View percentage of permissions assigned
   - Apply changes with real-time updates

### Creating and Editing Roles

1. Navigate to Roles → Index
2. Click "Add Role" to create new roles
3. Provide:
   - Role name (unique identifier)
   - Display name (user-friendly name)
   - Guard name (web/api)
   - Initial permissions (optional)

---

## Schools Management

### School Overview

The Schools system provides comprehensive management of educational institutions:

- **Basic Information**: School details, type, board affiliation
- **Contact Information**: Phone numbers, email addresses, website
- **Physical Addresses**: Multiple address types supported
- **Management Details**: Ownership, managing authority
- **Officials**: School staff and administrators
- **Documents**: Important school documentation
- **Academic Structure**: Academic years and class organization

### School Classes

1. Navigate to Schools → [School Name] → Classes
2. Manage class structure:
   - Create classes with unique codes
   - Set age ranges for students
   - Order classes by sequence
   - Track class-specific requirements

### Academic Years

1. Navigate to Schools → [School Name] → Academic Years  
2. Manage academic calendar:
   - Create academic years with date ranges
   - Set current active academic year
   - Track academic progress
   - Plan future academic periods

---

## Dashboard Overview

### Executive Summary

The dashboard provides real-time insights into:

- **School Metrics**: Total schools, active/inactive breakdown
- **Student Statistics**: Enrollment numbers and student-teacher ratios  
- **Data Quality**: Completeness indicators and attention alerts
- **Recent Activity**: Latest school additions and updates

### Quick Actions

- **Create School**: Direct link to add new schools
- **View Schools**: Navigate to schools management
- **System Statistics**: Access detailed analytics

---

## System Administration

### User Role Assignment

**Automatic Role Assignment:**
- Roles are automatically assigned based on user type
- System maintains consistency between user types and role permissions
- Manual role adjustments available for administrators

**Manual Role Management:**
- System administrators can override automatic assignments
- Bulk role updates supported for multiple users
- Audit trail maintains history of role changes

### Permission Management

**Granular Permissions:**
- 83+ specific permissions across all system areas
- Organized into logical categories for easy management
- Percentage-based analytics show permission utilization

**Role Hierarchy:**
- 15 predefined roles from Guest to Super Admin
- Hierarchical permission inheritance
- Flexible assignment supports custom role combinations

### Data Management

**Import/Export:**
- Bulk user imports supported
- Role and permission exports for backup
- CSV format support for data exchange

**Backup and Recovery:**
- Regular database backups recommended
- Role and permission configurations preserved
- User data integrity maintained

### Security Features

**Access Control:**
- Role-based access restricts functionality by user type
- Permission validation on all system operations
- Session management with automatic logout

**Audit Trail:**
- All user actions tracked and logged
- Role assignment changes recorded
- Permission modifications monitored

---

## Troubleshooting

### Common Issues

**Role Assignment Problems:**
- Verify user has appropriate user type set
- Check role permissions include required actions
- Confirm school associations are correct

**Modal Not Opening:**
- Ensure user has role management permissions
- Check browser console for JavaScript errors
- Refresh page and try again

**Search Not Working:**
- Clear search field and try again
- Check spelling and try partial matches
- Verify data exists in the system

### Getting Help

- Contact system administrator for access issues
- Check user permissions if functionality is missing
- Report bugs through the application's feedback system

---

## Updates and Changes

### Recent Enhancements

**Role Users Management System:**
- Added comprehensive user assignment modal interface
- Implemented bulk user operations with real-time updates
- Enhanced search and filtering capabilities
- Improved user experience with immediate visual feedback

**Performance Improvements:**
- Optimized database queries for faster role loading
- Enhanced modal state management for smooth interactions
- Improved table refresh performance after bulk operations

### Future Enhancements

- Advanced filtering options for role management
- Export functionality for user-role assignments
- Enhanced analytics dashboard for role utilization
- Mobile-responsive improvements for better tablet/phone usage

---

*Last Updated: January 2025*
*Version: 2.0.0*