# Sales Application User Manual

## Table of Contents

1. [Getting Started](#getting-started)
2. [User Management](#user-management)
3. [Role Management](#role-management)
4. [Schools Management](#schools-management)
5. [Audit Management](#audit-management)
6. [Dashboard Overview](#dashboard-overview)
7. [System Administration](#system-administration)

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

## Audit Management

### Understanding the Audit System

The audit system provides comprehensive tracking of all changes made throughout the application. It automatically records:

- **User Actions**: Who performed each action with full user attribution
- **Data Changes**: Before and after values for all field modifications  
- **Timestamps**: Precise timing of when changes occurred
- **Event Types**: Created, updated, deleted, and restored operations
- **Change Context**: Additional metadata about each modification

### Audit Dashboard

Navigate to "Audits" in the sidebar to access the audit dashboard.

#### Statistics Overview

The dashboard displays key metrics in four main cards:

- **Total Audits**: Complete count of all recorded activities in the system
- **Today**: Number of activities that occurred today
- **This Week**: Activities from the current week
- **This Month**: Activities from the current month

#### Activity Breakdown

**Recent Activity Panel:**
- Lists the 10 most recent audit records across the entire system
- Shows event type badges with color coding (Created: green, Updated: blue, Deleted: red)
- Displays model type and ID (e.g., "User #42", "School #15")
- Includes user attribution showing who performed the action
- Provides relative timestamps ("2m ago", "1h ago", "Just now")

**Activity Types Panel:**
- Visual breakdown of audit events by type
- Color-coded dots matching event type colors
- Shows count and percentage distribution for each event type
- Includes: Created, Updated, Deleted, Restored operations

**Models Activity Panel:**
- Lists top 6 most active models in the system
- Shows audit record counts for each model type
- Helps identify which areas of the system see the most activity
- Models include: User, School, SchoolContact, SchoolAddress, etc.

**Most Active Users Panel:**
- Displays top 5 users by audit record count
- Shows user names with clickable links to their audit timeline
- Includes total audit count for each user
- Helps identify power users and system activity patterns

### Individual Record Timeline

#### Accessing Timelines

You can view the complete audit trail for any record by:

1. **From Dashboard**: Click on any user name in the "Most Active Users" section
2. **Direct Navigation**: Use URLs like `/audits/timeline/User/123` for specific records
3. **From Record Pages**: Look for "View Audit Trail" links on individual record pages

#### Timeline Interface

**Summary Statistics:**
- **Total Events**: Complete count of all changes to this record
- **Created**: Number of creation events (typically 1)
- **Updates**: Count of modification events
- **Deletions**: Number of deletion events (soft deletes)

**Visual Timeline:**
The timeline displays changes in chronological order with:

- **Connecting Line**: Vertical line connecting all events visually
- **Event Icons**: 
  - ➕ Plus icon for creation events
  - ✏️ Edit icon for updates  
  - 🗑️ Trash icon for deletions
  - ↩️ Restore icon for restoration events
- **Color Coding**:
  - 🟢 Green for created events
  - 🔵 Blue for updated events
  - 🔴 Red for deleted events
  - 🟡 Yellow for restored events

#### Event Details

Each timeline event shows:

**Event Header:**
- Color-coded badge with event type
- Relative timestamp ("2h ago")
- User who performed the action

**Event Description:**
- Action summary (e.g., "John Smith created this record")
- Precise timestamp (e.g., "January 15, 2025 at 2:30 PM")
- Link to view detailed audit record

**Changes Summary:**
- High-level description of what changed
- Field count summaries ("Updated 3 fields")
- List of changed fields when 5 or fewer fields modified

**Field-Level Changes:**
For records with detailed changes, you'll see:
- **Field Names**: Clearly labeled for each changed attribute
- **Old Values**: Previous values with strikethrough formatting
- **New Values**: Current values highlighted in green
- **Value Formatting**: 
  - Null values displayed as "null"
  - Boolean values shown as "true"/"false"
  - Complex objects truncated if over 50 characters
  - Arrays formatted as JSON for readability

### Audit Filtering and Search

#### Available Filters

Navigate to the main audit index to access filtering options:

**Model Type Filter:**
- Filter by specific model types (User, School, Contact, etc.)
- Dropdown shows all available auditable models
- Displays both technical name and user-friendly labels

**Event Type Filter:**
- Filter by event types: Created, Updated, Deleted, Restored
- Multiple selection supported
- Visual badges match the color coding throughout the system

**User Filter:**
- Filter by specific user who performed actions
- Searchable dropdown with user names
- Shows user attribution for all audit records

**Date Range Filters:**
- From Date: Filter records from specific date forward
- To Date: Filter records up to specific date
- Supports date picker interface for easy selection

**Search Functionality:**
- Global search across audit record content
- Searches through old values, new values, and metadata
- Real-time search with debounced input for performance
- Works in combination with other filters

#### Using Filters

1. **Single Filter**: Select any filter to narrow down results
2. **Multiple Filters**: Combine filters for precise record location
3. **Clear Filters**: Reset button to clear all active filters
4. **URL Preservation**: Filter state maintained in browser URL
5. **Pagination**: Filtered results properly paginated

### Event Type Reference

#### Created Events
- **When**: New records are added to the system
- **Data Captured**: All initial field values
- **Display**: Green badges and plus icons
- **Example**: "User created with 5 fields set: name, email, user_type, school_id, is_active"

#### Updated Events  
- **When**: Existing records are modified
- **Data Captured**: Both old and new values for changed fields
- **Display**: Blue badges and edit icons
- **Example**: "Updated 2 fields - Changed: phone, department"

#### Deleted Events
- **When**: Records are soft deleted or removed
- **Data Captured**: All values at time of deletion
- **Display**: Red badges and trash icons
- **Example**: "Record deleted with 7 fields"

#### Restored Events
- **When**: Soft deleted records are restored
- **Data Captured**: Restoration metadata and any field changes
- **Display**: Yellow badges and restore icons
- **Example**: "Record restored"

#### Custom Events
- **When**: Special application events (login, logout, etc.)
- **Data Captured**: Event-specific metadata
- **Display**: Gray badges with clock icons
- **Example**: "User login: john@example.com"

### Audit Data Privacy and Security

#### Sensitive Data Handling
- **Excluded Fields**: Passwords, tokens, and sensitive data automatically excluded
- **Data Retention**: Audit records preserved according to compliance requirements
- **Access Control**: Audit viewing restricted by role-based permissions

#### User Attribution
- **Authentication Required**: All audit records linked to authenticated users
- **System Actions**: Automated processes attributed to "System" user
- **Anonymous Actions**: Guest actions tracked with session information

### Troubleshooting Audit Issues

#### Missing Audit Records
- Verify the model implements the Auditable trait
- Check if the field is excluded from auditing in model configuration
- Confirm the action occurred after audit system was enabled

#### Performance Considerations
- Large audit tables may impact query performance
- Use date range filters for better performance on large datasets
- Consider archiving old audit records periodically

#### Timeline Not Loading
- Verify record exists and you have permission to view it
- Check if audit records exist for the specific record
- Try refreshing the page or clearing browser cache

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

**Audit Management System:**
- Comprehensive audit trail tracking for all system changes
- Visual timeline interface with color-coded event types
- Real-time audit dashboard with statistics and activity monitoring  
- Advanced filtering and search capabilities across audit records
- Field-level change tracking with before/after value comparison

**Performance Improvements:**
- Optimized database queries for faster role loading
- Enhanced modal state management for smooth interactions
- Improved table refresh performance after bulk operations
- Efficient audit data loading with pagination and caching

### Future Enhancements

- Advanced filtering options for role management
- Export functionality for user-role assignments and audit records
- Enhanced analytics dashboard for role utilization
- Advanced audit reporting with custom date ranges and data export
- Mobile-responsive improvements for better tablet/phone usage
- Integration with external compliance systems for audit data

---

*Last Updated: January 2025*
*Version: 2.1.0*