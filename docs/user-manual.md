# Sales Application User Manual

## Table of Contents

1. [Getting Started](#getting-started)
2. [Dashboard](#dashboard)
3. [Schools Management](#schools-management)
4. [Navigation](#navigation)
5. [User Management](#user-management)
6. [Settings](#settings)
7. [Authentication](#authentication)
8. [API Reference](#api-reference)
9. [Troubleshooting](#troubleshooting)
10. [Keyboard Shortcuts](#keyboard-shortcuts)
11. [FAQ](#faq)

---

## Getting Started

Welcome to the Sales Application - a comprehensive school management system designed to help you efficiently manage school data, analytics, and operations.

### System Requirements

- Modern web browser (Chrome, Firefox, Safari, Edge)
- JavaScript enabled
- Stable internet connection

### First Login

1. Navigate to the application URL
2. Click "Login" or use the registration link if you're a new user
3. Enter your credentials
4. Verify your email if required
5. You'll be redirected to the Dashboard

---

## Dashboard

The Dashboard is your central command center, providing an overview of all school-related statistics and recent activities.

### Key Features

#### Statistics Overview
- **Total Schools**: Complete count of all schools in the system
- **Active/Inactive Schools**: Status breakdown
- **Data Completeness**: Percentage showing how complete your school records are
- **Schools with Contacts/Addresses**: Data quality indicators

#### Visual Analytics
- **Schools by Type**: Pie chart showing distribution of school types
- **Schools by Board Affiliation**: Chart displaying board-wise distribution
- **Interactive Charts**: Click and hover for detailed information

#### Recent Activity
- **Recent Schools**: Shows the 5 most recently added schools (last 30 days)
- **Quick Access**: Direct links to school details
- **Contact Information**: Quick view of school contacts and addresses

### Using the Dashboard

1. **Navigate** using the main menu or breadcrumbs
2. **Interact** with charts by hovering for details
3. **Click** on recent schools to view full details
4. **Monitor** data completeness and take action to improve data quality

---

## Schools Management

The Schools module is the core of the application, providing comprehensive school data management capabilities.

### Schools Index Page

#### Features
- **Comprehensive Listing**: View all schools in a paginated table
- **Advanced Filtering**: Filter by type, status, board affiliation
- **Search**: Real-time search across school names and details
- **Sorting**: Sort by any column (name, type, status, etc.)
- **Bulk Operations**: Select multiple schools for batch actions

#### Filtering Options
- **Search Bar**: Type to filter schools by name or details (with 500ms debounce for smooth typing)
- **School Type**: Filter by Primary, Secondary, Higher Secondary, etc.
- **Status**: Active or Inactive schools
- **Board Affiliation**: CBSE, ICSE, State Board, etc.
- **Filter Preservation**: All filters are preserved when selecting different options
- **Clear Filters**: Click "Clear Filters" button to reset all filters at once

#### Table Features
- **Pagination**: Navigate through large datasets (15 schools per page)
- **Sorting**: Click column headers to sort
- **Selection**: Use checkboxes for bulk operations
- **Quick Actions**: Edit, view, or delete directly from the table

### School Operations

#### Adding a New School
1. Click the "Add School" button (+ icon)
2. Fill in required information:
   - School name
   - School type
   - Board affiliation
   - Contact details
   - Address information
3. Click "Save" to create the school

#### Editing Schools
1. Click the "Edit" button on any school row
2. Modify the required fields
3. Save changes
4. View updated information immediately

#### Bulk Operations
1. Select multiple schools using checkboxes
2. Choose from available bulk actions:
   - Activate/Deactivate multiple schools
   - Export selected schools
   - Delete multiple schools (with confirmation)

---

## Navigation

### Main Navigation Sidebar

The application features a collapsible sidebar navigation system:

#### Navigation Items
- **Dashboard**: Overview and analytics
- **Schools**: Complete school management
- **Documentation**: This user manual

#### Sidebar Features
- **Collapsible**: Click the hamburger menu (☰) to collapse/expand
- **Icon Mode**: When collapsed, shows only icons with tooltips
- **Responsive**: Automatically adapts to mobile devices
- **Persistent State**: Remembers your preference across sessions

#### How to Use the Sidebar
1. **Toggle**: Click the hamburger menu in the header
2. **Keyboard Shortcut**: Press `Ctrl+B` (Windows/Linux) or `Cmd+B` (Mac)
3. **Hover**: When collapsed, hover over icons to see tooltips
4. **Mobile**: On mobile devices, the sidebar becomes an overlay

### Breadcrumbs
- Navigate using breadcrumb trails at the top of each page
- Click any breadcrumb to quickly return to that section

---

## User Management

The User Management system provides comprehensive role-based access control to ensure proper authorization and security throughout the application.

### User Types and Roles

The system supports seven distinct user types, each with specific permissions and capabilities:

#### System-Wide Users
- **Staff**: Basic sales personnel
  - Create and manage sales transactions
  - View products and inventory
  - Access their own sales history
  
- **Admin**: Department administrators  
  - Full CRUD operations on products and inventory
  - User management capabilities
  - Sales voiding and comprehensive reporting
  - Settings management

- **Audit**: Read-only audit personnel
  - Complete read-only access to all data
  - Audit trail and activity log access
  - Export capabilities for compliance reporting

- **System Admin**: Full system access
  - All permissions across the entire system
  - Cross-school management capabilities
  - Role and permission management
  - System administration functions

#### School-Specific Users
- **School Admin**: School-level administrators
  - School data management and updates
  - School official and staff management
  - Academic year and class setup
  - School-specific reporting

- **Principal**: Academic leadership
  - School operations oversight
  - Academic year and class management
  - Staff viewing and basic reporting

- **Teacher**: Educational staff
  - Class management within their school
  - Limited school data access
  - Basic operational functions

### User Profile Management

#### Profile Information
Each user profile includes:
- **Basic Information**: Name, email, phone number
- **Professional Details**: Department, bio, qualifications
- **School Association**: Linked school for school-specific users
- **Activity Tracking**: Last login, account status
- **Audit Trail**: Created by, updated by information

#### Account Status
- **Active/Inactive**: Control user access to the system
- **School Association**: Automatic assignment for school-specific roles
- **Role Assignment**: Automatic role assignment based on user type

### Permission System

#### Granular Permissions
The system uses 34 distinct permissions across key areas:

**Sales Management**
- Create, view, edit, void sales transactions
- Access to all sales or personal sales only

**Product & Inventory**  
- Product CRUD operations
- Inventory viewing and management
- Stock movement tracking

**User Administration**
- Create, view, edit, delete users
- Role and permission management

**School Management**
- School CRUD operations
- School official and class management
- Academic year setup and management

**Reporting & Audit**
- View and export reports
- Access audit logs and activity trails
- System monitoring capabilities

**System Administration**
- Settings management
- Role and permission configuration
- Full system access

#### Automatic Role Assignment
- Users are automatically assigned roles based on their user type
- Roles can be verified and updated through the user profile
- Permission checks are performed throughout the application

### User Account Creation

#### Admin-Created Accounts
1. Administrators can create accounts for other users
2. Select appropriate user type during creation
3. Assign school association for school-specific users
4. Set initial profile information
5. User receives welcome email with login instructions

#### Self-Registration (If Enabled)
1. Users can register with basic information
2. Email verification required
3. Default role assignment (typically Staff)
4. Admin approval may be required

### Security Features

#### Access Control
- Role-based page access restrictions
- API endpoint permission validation
- Feature-level permission checks

#### Account Security
- Strong password requirements
- Email verification for new accounts
- Account lockout protection
- Login attempt tracking

#### Audit Trail
- All user actions are logged
- Permission changes are tracked
- Login/logout activity monitoring
- Data modification history

### Managing User Accounts

#### For Administrators
1. **View Users**: Access comprehensive user listing
2. **Create Users**: Add new accounts with appropriate roles
3. **Edit Users**: Update profile information and permissions
4. **Deactivate Users**: Temporarily disable access
5. **Role Management**: Assign and modify user permissions

#### For Users
1. **Profile Updates**: Modify personal information
2. **Password Changes**: Update login credentials
3. **View Permissions**: Understand your access level
4. **Activity History**: Review your login and activity history

---

## Settings

Customize your account and application preferences in the Settings section.

### Profile Settings (`/settings/profile`)
- **Personal Information**: Update name and email
- **Account Management**: Change account details
- **Account Deletion**: Permanently delete your account (with confirmation)

### Password Settings (`/settings/password`)
- **Change Password**: Update your login password
- **Security**: Ensure strong password practices
- **Rate Limited**: Protected against brute force attempts

### Appearance Settings (`/settings/appearance`)
- **Theme Selection**: Choose your preferred visual theme
- **Display Options**: Customize the interface appearance
- **Accessibility**: Adjust for better accessibility

---

## Authentication

### User Registration
1. Click "Register" on the login page
2. Provide required information:
   - Name
   - Email address
   - Password (with confirmation)
3. Verify your email address
4. Complete registration

### Login Process
1. Enter your email and password
2. Click "Login"
3. Optional: Use "Remember Me" for persistent sessions
4. Two-factor authentication (if enabled)

### Password Reset
1. Click "Forgot Password?" on login page
2. Enter your email address
3. Check email for reset link
4. Follow the link and set a new password
5. Login with your new password

### Email Verification
- Required for new accounts
- Check your email for verification link
- Click the link to verify your account
- Resend verification email if needed

---

## API Reference

The application provides a comprehensive REST API for programmatic access.

### Base URLs
```
/api/schools  - Schools management
/api/users    - User management (Admin access required)
```

### Authentication
- API requires authentication tokens
- Include authorization header in requests
- Contact administrator for API access

### Available Endpoints

#### Schools API
- `GET /api/schools` - List all schools with pagination
- `POST /api/schools` - Create a new school
- `GET /api/schools/{id}` - Get specific school details
- `PUT /api/schools/{id}` - Update school information
- `DELETE /api/schools/{id}` - Soft delete a school
- `POST /api/schools/{id}/restore` - Restore soft-deleted school
- `DELETE /api/schools/{id}/force` - Permanently delete school

#### Bulk Operations
- `POST /api/schools/bulk-activate` - Activate multiple schools
- `POST /api/schools/bulk-deactivate` - Deactivate multiple schools
- `DELETE /api/schools/bulk-delete` - Bulk soft delete

#### Statistics
- `GET /api/schools/stats` - Get school statistics

#### User Management API
- `GET /api/users` - List all users (Admin only)
- `POST /api/users` - Create a new user account
- `GET /api/users/{id}` - Get specific user details
- `PUT /api/users/{id}` - Update user information
- `DELETE /api/users/{id}` - Deactivate a user account
- `POST /api/users/{id}/activate` - Activate user account
- `POST /api/users/{id}/assign-role` - Assign role to user
- `GET /api/users/{id}/permissions` - Get user permissions

#### User Type and Role Management
- `GET /api/user-types` - List available user types
- `GET /api/roles` - List all roles and permissions
- `POST /api/roles/{role}/assign-permission` - Assign permission to role

### Request/Response Format
- All API requests and responses use JSON format
- Include `Content-Type: application/json` header
- Responses include appropriate HTTP status codes

---

## Troubleshooting

### Common Issues

#### Login Problems
**Issue**: Cannot log in
**Solutions**:
- Check email and password spelling
- Ensure caps lock is off
- Try password reset if needed
- Clear browser cache and cookies
- Check if account is verified

#### Sidebar Not Working
**Issue**: Sidebar won't collapse/expand
**Solutions**:
- Try the keyboard shortcut (Ctrl+B or Cmd+B)
- Refresh the page
- Check JavaScript is enabled
- Clear browser cache

#### Slow Performance
**Issue**: Application loading slowly
**Solutions**:
- Check internet connection
- Close unnecessary browser tabs
- Clear browser cache
- Try a different browser
- Contact support if issue persists

#### Data Not Loading
**Issue**: Schools or dashboard data not appearing
**Solutions**:
- Refresh the page
- Check network connection
- Verify you have proper permissions
- Contact administrator

### Browser Compatibility
- **Recommended**: Latest versions of Chrome, Firefox, Safari, Edge
- **Minimum**: Most browsers from the last 2 years
- **JavaScript**: Must be enabled
- **Cookies**: Required for authentication and preferences

---

## Keyboard Shortcuts

Enhance your productivity with these keyboard shortcuts:

### Global Shortcuts
- `Ctrl+B` / `Cmd+B` - Toggle sidebar navigation
- `Ctrl+/` / `Cmd+/` - Show keyboard shortcuts help
- `Escape` - Close modals and overlays

### Navigation Shortcuts
- `Alt+D` - Go to Dashboard
- `Alt+S` - Go to Schools
- `Alt+P` - Go to Profile Settings

### Schools Page Shortcuts
- `Ctrl+F` / `Cmd+F` - Focus search bar
- `Enter` - Apply filters
- `Ctrl+A` / `Cmd+A` - Select all visible schools
- `Delete` - Delete selected schools (with confirmation)

---

## FAQ

### General Questions

**Q: How do I get access to the application?**
A: Contact your system administrator for account creation and access credentials.

**Q: Can I use the application on mobile devices?**
A: Yes, the application is fully responsive and works on tablets and smartphones.

**Q: How often is data backed up?**
A: Data is backed up regularly. Contact your administrator for specific backup schedules.

### Schools Management

**Q: How many schools can I manage?**
A: There's no hard limit. The system uses pagination to handle large datasets efficiently.

**Q: Can I bulk import schools?**
A: Bulk import functionality may be available. Contact your administrator for import procedures.

**Q: What happens when I delete a school?**
A: Schools are soft-deleted by default, meaning they can be restored. Permanent deletion requires additional confirmation.

### Technical Questions

**Q: Which browsers are supported?**
A: All modern browsers including Chrome, Firefox, Safari, and Edge. Internet Explorer is not supported.

**Q: Can I access the API?**
A: API access requires special permissions. Contact your administrator for API documentation and access tokens.

**Q: How do I report a bug?**
A: Contact your system administrator or use the feedback form if available.

### Data and Privacy

**Q: Is my data secure?**
A: Yes, the application uses industry-standard security practices including encrypted connections and secure authentication.

**Q: Can I export my data?**
A: Export functionality is available for schools data. Use the export options in the Schools section.

**Q: How long is my data retained?**
A: Data retention policies vary by organization. Check with your administrator for specific policies.

---

## Support and Contact

For additional help or support:

1. **Administrator**: Contact your system administrator
2. **Technical Issues**: Report bugs or technical problems
3. **Feature Requests**: Suggest new features or improvements
4. **Training**: Request additional training or documentation

---

## Version Information

- **Application**: Sales Management System
- **Version**: 1.0
- **Last Updated**: January 5, 2025
- **Framework**: Laravel 12 + Vue 3 + Inertia.js
- **Recent Updates**: 
  - Fixed filter state management in Schools Index page
  - Enhanced filter preservation across all selections
  - Improved search debouncing for better user experience

---

*This manual is regularly updated. For the latest version, always refer to the online documentation at `/docs`.*