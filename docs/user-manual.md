# Sales Application User Manual

## Table of Contents

1. [Getting Started](#getting-started)
2. [Dashboard](#dashboard)
3. [Schools Management](#schools-management)
4. [Navigation](#navigation)
5. [Settings](#settings)
6. [Authentication](#authentication)
7. [API Reference](#api-reference)
8. [Troubleshooting](#troubleshooting)
9. [Keyboard Shortcuts](#keyboard-shortcuts)
10. [FAQ](#faq)

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
- **Search Bar**: Type to filter schools by name or details
- **School Type**: Filter by Primary, Secondary, Higher Secondary, etc.
- **Status**: Active or Inactive schools
- **Board Affiliation**: CBSE, ICSE, State Board, etc.

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

### Base URL
```
/api/schools
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
- **Last Updated**: 2025
- **Framework**: Laravel 12 + Vue 3 + Inertia.js

---

*This manual is regularly updated. For the latest version, always refer to the online documentation at `/docs`.*