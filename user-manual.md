# Sales Application User Manual

## Table of Contents

1. [Getting Started](#getting-started)
2. [User Management](#user-management)
3. [Role Management](#role-management)
4. [Schools Management](#schools-management)
5. [Categories Management](#categories-management)
6. [Products Management](#products-management)
7. [Audit Management](#audit-management)
8. [Dashboard Overview](#dashboard-overview)
9. [System Administration](#system-administration)

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
- **Categories**: Product category management with hierarchical organization
- **Products**: Product inventory and catalog management with pricing and categories
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

### Deleting Users

1. Navigate to Users → Index
2. Find the user you want to delete in the users table
3. Click the "⋯" menu button for the user
4. Select "Delete" from the dropdown menu
5. A professional confirmation modal will appear showing:
   - **Title**: "Delete User"
   - **User Name**: The name of the user being deleted
   - **Warning Message**: Confirmation text asking if you're sure
   - **Action Buttons**: "Cancel" and "Delete User" (red button)
6. Click "Delete User" to confirm, or "Cancel" to abort
7. During deletion, the modal shows a loading state with a spinner
8. The modal automatically closes when deletion is complete
9. The user list refreshes to reflect the changes
10. A success notification confirms the deletion

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

### Deleting Roles

1. Navigate to Roles → Index
2. Find the role you want to delete in the roles table
3. Click the "⋯" menu button for the role
4. Select "Delete" from the dropdown menu
5. A professional confirmation modal will appear showing:
   - **Title**: "Delete Role"
   - **Role Name**: The display name of the role being deleted
   - **Warning Message**: Confirmation text asking if you're sure
   - **Action Buttons**: "Cancel" and "Delete Role" (red button)
6. Click "Delete Role" to confirm, or "Cancel" to abort
7. During deletion, the modal shows a loading state with a spinner
8. The modal automatically closes when deletion is complete
9. The roles list refreshes to reflect the changes
10. A success notification confirms the deletion

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

### Deleting Schools

1. Navigate to Schools → Index
2. Find the school you want to delete in the schools table
3. Click the "⋯" menu button for the school
4. Select "Delete" from the dropdown menu
5. A professional confirmation modal will appear showing:
   - **Title**: "Delete School" 
   - **School Name**: The name of the school being deleted
   - **Warning Message**: Confirmation text asking if you're sure
   - **Action Buttons**: "Cancel" and "Delete School" (red button)
6. Click "Delete School" to confirm, or "Cancel" to abort
7. During deletion, the modal shows a loading state with a spinner
8. The modal automatically closes when deletion is complete
9. The schools list refreshes to reflect the changes
10. A success notification confirms the deletion

**Note**: School deletion may be restricted if the school has active users or related data. Administrators and super administrators have the necessary permissions to delete schools.

---

## Categories Management

### Category Overview

The Categories system provides comprehensive management of product categorization for the sales application:

- **Hierarchical Structure**: Support for parent-child category relationships with unlimited depth
- **Category Details**: Name, slug, description, and status management
- **Sort Order**: Custom ordering for category organization
- **Filtering and Search**: Advanced filtering by parent category, status, and search functionality
- **Statistics**: Real-time metrics showing category usage and hierarchy information

### Accessing Categories

1. Click "Categories" in the main navigation sidebar (folder icon)
2. The Categories interface is accessible to all authenticated users
3. The main categories page displays:
   - **Statistics Cards**: Total categories, active/inactive counts, root categories, and categories with children
   - **Filter Panel**: Search, parent category filter, and status filter options
   - **Categories Table**: Hierarchical listing of all categories with actions

### Understanding Category Hierarchy

#### Root Categories
- Top-level categories with no parent
- Displayed without indentation in the hierarchy
- Can contain child categories (subcategories)
- Example: "Uniforms", "Books", "Stationery"

#### Child Categories (Subcategories)  
- Categories that belong to a parent category
- Displayed with visual hierarchy indicators (├─ prefix)
- Can have their own child categories for deeper organization
- Example: Under "Uniforms" → "Shirts", "Pants", "Shoes"

#### Category Depth
- Visual indicators show the level of nesting
- Folder icons indicate categories with children (open folder) vs. leaf categories (closed folder)
- Breadcrumb navigation helps understand category relationships

### Viewing Categories

#### Categories Index Page

The main categories page provides:

**Statistics Overview:**
- **Total Categories**: Complete count of all categories in system
- **Active Categories**: Count of currently active categories (green badge)
- **Inactive Categories**: Count of disabled categories (orange badge)  
- **Root Categories**: Count of top-level categories (blue badge)
- **Categories with Children**: Count of categories containing subcategories (purple badge)

**Basic Filtering Options:**
- **Search**: Real-time search across category names, descriptions, and slugs (500ms debounced)
- **Parent Category**: Filter to show categories under specific parent or root categories only
- **Status**: Filter by active/inactive status
- **Clear Filters**: Reset all filters to default state

**Advanced Filtering System:**
- **Active Filter Chips**: Visual indicators showing currently applied filters with individual removal options
- **Quick Filter Presets**: One-click filters for common scenarios:
  - **Root Categories**: Show only top-level categories
  - **Active with Products**: Categories that are active and have products assigned
  - **With Children**: Categories containing subcategories
  - **Recently Created**: Categories created within the last week
  - **Inactive**: Categories marked as disabled
- **Advanced Filter Panel** (expandable):
  - **Date Ranges**: Filter by creation date, update date ranges
  - **Creator Filter**: Filter by specific user who created categories
  - **Children Status**: Filter by categories with/without child categories
  - **Products Status**: Filter by categories with/without assigned products
  - **Sort Order Range**: Filter by numeric sort order values
  - **Include Deleted**: Option to include soft-deleted categories in results

**Categories Table:**
- **Hierarchy Display**: Visual tree structure with connecting lines and folder icons
- **Category Information**: Name, slug (URL-friendly identifier), description
- **Parent Relationships**: Shows parent category name with navigation links
- **Children Count**: Badges showing number of direct child categories
- **Products Count**: Shows how many products are assigned to each category
- **Status**: Active/inactive status badges with color coding
- **Sort Order**: Numeric ordering for category sequence
- **Creation Date**: When category was initially created
- **Actions Menu**: View, edit, and delete options per category

#### Category Details Page

Navigate to individual category pages by clicking category names:

**Category Information:**
- Full category details with name, description, and metadata
- Breadcrumb navigation showing category hierarchy path
- Parent category relationship (if applicable)
- Status and creation information

**Child Categories Table:**
- List of all direct child categories (subcategories)
- Same table format as main categories page
- Actions to view individual child categories
- Direct navigation to child category detail pages

**Statistics:**
- Child categories count
- Products associated with this category
- Status and activity information

### Category Actions

#### Viewing Categories
- Click category name to view detailed information
- Navigate hierarchy using breadcrumb links
- Use parent category links to explore relationships
- Filter and search to find specific categories

#### Managing Category Status
- Categories can be marked as active (available) or inactive (disabled)
- Status changes affect category visibility in product assignment
- Color-coded badges clearly indicate current status
- Status filter helps manage active vs. inactive categories

### Navigation Features

#### Hierarchical Navigation
- **Breadcrumbs**: Show complete path from root to current category
- **Parent Links**: Click parent category names to navigate up hierarchy
- **Child Navigation**: Click child category names to navigate down hierarchy
- **Home Links**: Return to main categories listing from any level

#### Search and Filtering
- **Real-time Search**: Search updates results as you type (500ms delay)
- **Combined Filters**: Use multiple filters simultaneously for precise results
- **URL State**: Filters maintain state in browser URL for bookmarking and sharing
- **Filter Persistence**: Filter settings preserved when navigating between pages

#### Pagination and Sorting
- **Pagination**: Navigate through large category lists efficiently
- **Sortable Columns**: Click column headers to sort by name, creation date, or sort order
- **Sort Direction**: Toggle between ascending and descending order
- **Custom Ordering**: Categories can be manually ordered using sort order field

### Category Tree View

Use the "Tree View" button for alternative category visualization:
- Expanded hierarchical view showing all category relationships
- Visual tree structure with connecting lines and chevron icons
- **Expand All/Collapse All** toggle functionality for quick navigation
- Collapsible/expandable category branches with proper state management
- Overview of entire category organization with visual hierarchy indicators
- Fixed expansion functionality ensures child categories display correctly when expanded

### Permission-Based Access

Categories system respects user permissions:
- **View Access**: All authenticated users can view categories
- **Modification Rights**: Editing requires appropriate permissions
- **Administrative Functions**: Category creation/deletion requires admin rights
- **Bulk Operations**: Advanced operations restricted to authorized users

### Best Practices

#### Category Organization
- **Logical Hierarchy**: Organize categories in intuitive parent-child relationships
- **Consistent Naming**: Use clear, descriptive category names
- **Appropriate Depth**: Avoid overly deep hierarchies (generally 3-4 levels maximum)
- **Regular Review**: Periodically review and reorganize category structure

#### Category Management  
- **Status Management**: Mark unused categories as inactive rather than deleting
- **Documentation**: Use description fields to clarify category purpose
- **Sort Order**: Maintain logical ordering within category groups
- **Parent Assignment**: Ensure categories are properly nested under appropriate parents

### Troubleshooting Categories

#### Categories Not Showing
- Check if category is marked as active
- Verify user has view permissions  
- Clear any active filters that might hide category
- Refresh page and check again

#### Hierarchy Display Issues
- Verify parent-child relationships are correctly set
- Check for circular references (category set as its own parent)
- Ensure sort order is properly configured
- Review breadcrumb navigation for hierarchy path

#### Filter/Search Problems
- **Search Not Working**: Fixed in recent update - search now properly triggers on input changes
- Clear all filters and try again
- Check spelling in search terms
- Try partial matches instead of exact terms
- Verify data exists matching filter criteria
- **Search Delay**: Search is debounced by 500ms for performance - wait briefly for results
- **Filter Chips**: Use active filter chips to see what filters are currently applied
- **Advanced Filters**: Check if advanced filter panel contains additional active filters

---

## Products Management

### Product Overview

The Products system provides comprehensive management of inventory items and catalog products for the sales application:

- **Product Information**: SKU, name, description, and category assignment
- **Pricing Management**: Unit prices with tax rate calculations and Ghana Cedis (GHS) currency support
- **Inventory Tracking**: Quantity on hand, reorder levels, and low stock alerts
- **Categorization**: Hierarchical category assignment with parent-child relationships
- **Status Management**: Active/inactive status control for product availability

### Accessing Products

1. Click "Products" in the main navigation sidebar (package icon)
2. The Products interface is accessible to users with product management permissions
3. The main products page displays:
   - **Statistics Cards**: Total products, active/inactive counts, low stock alerts, and category distribution
   - **Filter Panel**: Search, category filter, status filter, and price range options
   - **Products Table**: Comprehensive listing of all products with actions and details

### Understanding Product Structure

#### Product Information Fields

**Basic Details:**
- **SKU (Stock Keeping Unit)**: Unique identifier with smart generation patterns
- **Name**: Product display name for customer-facing interfaces
- **Description**: Detailed product information and specifications
- **Category**: Hierarchical category assignment for organization
- **Status**: Active (available for sale) or inactive (hidden from sales)

**Pricing Information:**
- **Unit Price**: Base price in Ghana Cedis (GHS) before tax
- **Tax Rate**: Percentage-based tax rate (displayed as 0-100%, stored as 0-1 decimal)
- **Final Price**: Calculated total including tax (Unit Price × (1 + Tax Rate))
- **Currency**: All prices displayed in Ghana Cedis with GH₵ symbol

**Inventory Details:**
- **Quantity on Hand**: Current stock level available for sale
- **Reorder Level**: Minimum stock threshold for reorder alerts
- **Unit Type**: Product measurement unit (pieces, kilograms, liters, etc.)
- **Location**: Physical storage location for inventory management

### Creating Products

1. Navigate to Products → Index
2. Click "Add Product" button
3. Fill out the comprehensive product form:

**Basic Information Section:**
- **Product Name**: Clear, descriptive name for the product
- **Description**: Detailed product information and specifications
- **Category**: Select from hierarchical category dropdown
- **Status**: Set as active (available) or inactive (hidden)

**Pricing Section:**
- **Unit Price**: Enter base price in Ghana Cedis (without tax)
- **Tax Rate**: Enter as percentage (e.g., 18 for 18% VAT)
  - System displays percentage input for user-friendly entry
  - Automatically converts to decimal (0-1) for backend validation
  - Helper text guides percentage entry format

**Inventory Section:**
- **Current Quantity**: Initial stock level
- **Reorder Level**: Minimum stock threshold for alerts
- **Unit Type**: Select from dropdown options:
  - Pieces (individual items)
  - Kilograms (weight-based)
  - Liters (volume-based)
  - Boxes (packaged items)
  - Sets (grouped items)
- **Storage Location**: Physical location for inventory tracking

**SKU Generation:**
- **Auto-generate**: System creates SKU from product name
- **Pattern Selection**: Choose from predefined patterns:
  - **Product Pattern**: PRD-XXXX format
  - **Item Pattern**: ITM-XXXX format
  - **Goods Pattern**: GDS-XXXX format
  - **Stock Pattern**: STK-XXXX format
- **Custom Entry**: Enter completely custom SKU code
- **Uniqueness**: System validates SKU uniqueness across all products

4. Click "Create Product" to save

### Viewing Products

#### Products Index Page

The main products page provides:

**Product Statistics Overview:**
- **Total Products**: Complete count of all products in catalog
- **Active Products**: Count of currently available products (green badge)
- **Inactive Products**: Count of disabled/hidden products (orange badge)
- **Low Stock Alerts**: Count of products below reorder level (red badge)
- **Categories Used**: Count of categories with assigned products (blue badge)

**Search and Filtering Options:**
- **Search**: Real-time search across product names, descriptions, and SKUs (500ms debounced)
- **Category Filter**: Filter by specific category or view uncategorized products
- **Status Filter**: Filter by active/inactive status
- **Price Range**: Filter by minimum and maximum price ranges
- **Stock Level**: Filter by in-stock, low stock, or out of stock status
- **Clear Filters**: Reset all filters to default state

**Products Table:**
- **Product Information**: Name, SKU, description with truncation
- **Category Assignment**: Shows assigned category with navigation links
- **Pricing Display**: Unit price, tax rate, and calculated final price in GHS format
- **Inventory Status**: Current quantity, reorder level, and stock status indicators
- **Status Badges**: Active/inactive status with color coding (green/orange)
- **Creation Date**: When product was initially added to system
- **Actions Menu**: View, edit, duplicate, and delete options per product

#### Product Details Page

Navigate to individual product pages by clicking product names:

**Comprehensive Product Information:**
- Full product details with name, description, SKU, and metadata
- Category assignment with breadcrumb navigation to parent categories
- Complete pricing breakdown: unit price, tax rate, final price calculation
- Inventory status with current quantity, reorder level, and location
- Status information and creation/modification timestamps

**Related Information:**
- **Category Details**: Link to assigned category with hierarchy path
- **Inventory History**: Track stock movements and quantity changes
- **Price History**: Previous pricing changes and tax rate modifications
- **Sales Data**: Products sold, revenue generated, popular combinations

### Product Pricing System

#### Ghana Cedis (GHS) Currency Support

**Price Display Format:**
- All prices displayed with GH₵ symbol prefix
- Two decimal places for precise pricing (GH₵ 125.50)
- Consistent currency formatting throughout the application
- Final price calculation includes tax automatically

**Tax Rate Management:**
- **User Input**: Enter tax rates as percentages (0-100%)
- **Backend Storage**: Automatically converted to decimals (0-1) for calculations
- **Display**: Shows percentage format for user clarity (18.0% VAT)
- **Calculation**: Final Price = Unit Price × (1 + Tax Rate decimal)
- **Validation**: Supports fractional percentages (e.g., 12.5% tax rate)

#### Price Calculations

**Automatic Calculations:**
- System automatically calculates final price including tax
- Real-time price updates when tax rate or unit price changes
- Consistent rounding to 2 decimal places for currency display
- Price history tracking for audit and analysis purposes

**Example Pricing:**
- Unit Price: GH₵ 100.00
- Tax Rate: 18% (stored as 0.18)
- Final Price: GH₵ 100.00 × (1 + 0.18) = GH₵ 118.00

### SKU Management System

#### Smart SKU Generation

**Auto-Generation from Name:**
- System creates SKU by processing product name
- Removes special characters and spaces
- Converts to uppercase format
- Adds numeric suffix for uniqueness
- Example: "School Uniform Shirt" → "SCHOOL-UNIFORM-SHIRT-001"

**Pattern-Based Generation:**
- **PRD-XXXX**: Product prefix with 4-digit number (PRD-0001)
- **ITM-XXXX**: Item prefix with sequential numbering (ITM-0045)
- **GDS-XXXX**: Goods prefix for general merchandise (GDS-0123)
- **STK-XXXX**: Stock prefix for inventory tracking (STK-0089)

**Custom SKU Entry:**
- Manual entry for specific business requirements
- System validates uniqueness across entire product catalog
- Supports alphanumeric characters, hyphens, and underscores
- Maximum 50 characters with validation feedback

#### SKU Validation

**Uniqueness Checking:**
- Real-time validation during product creation and editing
- Clear error messages if SKU already exists
- Suggestions for alternative SKUs if conflicts occur
- Database-level constraint enforcement for data integrity

### Unit Type Management

#### Available Unit Types

The system supports various unit types for different product categories:

- **Pieces**: Individual countable items (uniforms, books, calculators)
- **Kilograms**: Weight-based products (bulk stationery, sports equipment)
- **Liters**: Volume-based products (liquids, chemicals, cleaning supplies)
- **Boxes**: Packaged items sold in box quantities (bulk paper, supplies)
- **Sets**: Grouped items sold as complete sets (geometry sets, art supplies)

#### Unit Type Selection

**Dropdown Interface:**
- Clear labeling for each unit type with descriptions
- Consistent terminology across product catalog
- Integration with inventory calculations and stock alerts
- Proper display in sales interfaces and receipts

### Product Actions

#### Managing Product Status

**Active Products:**
- Available for sale in POS systems
- Visible in product catalogs and searches
- Included in inventory calculations and reports
- Green status badge indicates availability

**Inactive Products:**
- Hidden from sales interfaces and catalogs
- Preserved in system for historical records
- Can be reactivated when needed
- Orange status badge indicates disabled status
- Inventory still tracked for auditing purposes

#### Bulk Operations

**Bulk Status Updates:**
- Select multiple products for simultaneous status changes
- Activate/deactivate multiple products efficiently
- Bulk category reassignment capabilities
- Mass price updates with percentage increases/decreases

#### Product Duplication

**Clone Existing Products:**
- Duplicate product structure for similar items
- Copy pricing, category, and basic information
- Generate new unique SKU automatically
- Modify details as needed for new product variant

### Inventory Integration

#### Stock Level Monitoring

**Current Quantity Tracking:**
- Real-time inventory levels with sales integration
- Automatic updates when sales transactions occur
- Manual adjustments for inventory corrections
- Stock movement history for audit trails

**Low Stock Alerts:**
- Configurable reorder level thresholds
- Visual indicators for products needing restocking
- Dashboard alerts for low inventory attention
- Automated reporting for procurement planning

#### Inventory Locations

**Physical Storage Tracking:**
- Assign storage locations for efficient warehouse management
- Support for multiple location tracking
- Location-based inventory reports and searches
- Integration with picking and fulfillment processes

### Permission-Based Access

Products system respects user permissions:

- **View Access**: Users with product view permissions can browse catalog
- **Creation Rights**: Product creation requires appropriate management permissions
- **Modification Access**: Editing products restricted to authorized users
- **Bulk Operations**: Advanced operations require higher-level permissions
- **Price Management**: Pricing changes may require additional approval workflows

### Best Practices

#### Product Information Management

**Naming Conventions:**
- Use clear, descriptive product names
- Include important attributes in names (size, color, model)
- Maintain consistency across similar product lines
- Avoid abbreviations that may confuse users

**Description Guidelines:**
- Provide comprehensive product specifications
- Include dimensions, materials, and key features
- Use consistent formatting for similar product types
- Update descriptions when product specifications change

#### Category Assignment

**Logical Organization:**
- Assign products to most specific appropriate category
- Use parent categories for broader organization
- Review category structure periodically for optimization
- Ensure consistent categorization across product lines

#### Pricing Management

**Regular Price Reviews:**
- Monitor competitor pricing for market competitiveness
- Review tax rates for compliance with current regulations
- Document price changes with justification
- Maintain price history for trend analysis

#### Inventory Accuracy

**Stock Level Maintenance:**
- Conduct regular physical inventory counts
- Update reorder levels based on sales velocity
- Monitor stock movements for unusual patterns
- Maintain accurate location information

### Troubleshooting Products

#### Products Not Displaying

**Visibility Issues:**
- Check if product is marked as active
- Verify user has view permissions for products
- Clear any active filters that might hide product
- Refresh page and check search criteria

#### SKU Generation Problems

**SKU Conflicts:**
- System will display error for duplicate SKUs
- Try different pattern or manual entry
- Check if similar product already exists
- Contact administrator for SKU policy clarification

#### Pricing Calculation Issues

**Tax Rate Problems:**
- Ensure tax rate is entered as percentage (not decimal)
- Verify current tax regulations for accuracy
- Check calculation: Final = Unit × (1 + Tax Rate decimal)
- Review price display formatting for currency symbol

#### Search and Filter Problems

**Search Not Working:**
- Clear search field and try partial terms
- Check spelling and try alternative keywords
- Verify data exists matching search criteria
- Try clearing all filters and search again

**Filter Issues:**
- Review active filter chips for current selections
- Try individual filters to isolate problems
- Clear all filters and apply selectively
- Check if combination of filters yields no results

### Integration with Other Systems

#### Category Integration

**Hierarchical Categories:**
- Products automatically inherit category hierarchy
- Category changes affect product organization
- Category deletion requires product reassignment
- Category filters work across product searches

#### Sales Integration

**POS System Integration:**
- Active products available in point-of-sale interface
- Pricing automatically calculated with tax
- Inventory levels update with each sale
- Product information displays on receipts

#### Reporting Integration

**Analytics and Reports:**
- Product performance tracking across sales
- Inventory turnover analysis and trends
- Category-based sales reporting
- Price history and change impact analysis

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

**Delete Confirmation Modal Issues:**
- If delete confirmation modal doesn't appear, check user permissions
- Modal should show automatically after clicking delete action
- Ensure JavaScript is enabled in your browser
- Try refreshing the page if modal appears to freeze
- Check that the record still exists if modal won't open

**Search Not Working:**
- Clear search field and try again
- Check spelling and try partial matches
- Verify data exists in the system

### Permission-Based Access Issues

**If You Cannot Access Certain Pages:**
- Verify your user type supports the functionality (SYSTEM_ADMIN for /users, /roles, /permissions)
- Check if your account has been assigned the appropriate role
- Super Administrators have unrestricted access to all routes
- Contact system administrator for role assignment issues

**Time-Based Access Restrictions:**
- Some routes (like /audits) may have time-based access controls
- Super Administrators can bypass time restrictions
- System Administrators may be restricted to business hours (9 AM - 6 PM, Mon-Fri)
- Emergency override access can be granted by Super Administrators

**Audit Route Specific:**
- Audit trail access requires: Super Admin, System Admin, Auditor, or School Admin role
- Super Administrators have 24/7 access to audit functionality
- Other administrators may be subject to business hours restrictions
- Contact Super Administrator if you cannot access audit trails

### Getting Help

- Contact system administrator for access issues
- Check user permissions if functionality is missing
- Report bugs through the application's feedback system

---

## Updates and Changes

### Recent Enhancements

**Products Management System Implementation (September 2025):**
- Implemented comprehensive Product Management System with full CRUD operations and advanced features
- Created Product model with comprehensive attributes: SKU, name, description, category assignment, pricing, and inventory tracking
- Built API and Frontend controllers for seamless Laravel + Inertia.js integration with Vue 3 components
- Added Products navigation to main sidebar with Package icon and proper permission-based access control
- **Smart SKU Generation System**: Auto-generation from product names, pattern-based options (PRD-, ITM-, GDS-, STK-), and custom entry with uniqueness validation
- **Ghana Cedis (GHS) Currency Support**: Complete currency localization with GH₵ symbol, proper formatting, and price calculations
- **Tax Rate Conversion System**: User-friendly percentage input (0-100%) with automatic decimal conversion (0-1) for backend validation
- **Unit Type Dropdown**: Converted from text input to select dropdown with options (Pieces, Kilograms, Liters, Boxes, Sets)
- **Table UI Components**: Created comprehensive table component library (Table, TableHeader, TableBody, TableRow, TableCell) for consistent data presentation
- Fixed table component import errors and built successful Vite production assets
- Added TypeScript interfaces for Product entities and form data structures
- **Comprehensive Form Validation**: ProductFormFields.vue with real-time validation, computed properties, and watchers for smart form behavior
- Enhanced CategoryFormFields.vue and modal components with auto-slug generation and parent selection
- Successfully tested all features with proper form submission, validation, and user feedback systems

**Enhanced Alert Notification System (September 2025):**
- Implemented unified alert system replacing all toast notifications throughout the application
- Enhanced AlertsContainer with z-[9999] layering ensuring alerts float above all page components
- Added flexible positioning system with 6 positions (top-left, top-center, top-right, bottom-left, bottom-center, bottom-right)
- Implemented priority-based alert styling with critical alerts displaying backdrop overlays for immediate attention
- Created context-aware alert positioning: forms use top-center, table operations use bottom-right, navigation uses top-right
- Removed vue-sonner dependency and Toaster components reducing bundle size and improving performance
- Added enhanced visual styling with backdrop blur effects, enhanced shadows, and professional appearance
- Replaced all browser confirm() and alert() dialogs with professional modal interfaces
- Enhanced TestAlerts page with comprehensive demonstration of all alert features and positioning options
- Improved accessibility with consistent alert behavior, proper focus management, and screen reader support

**Categories Index Search & Tree Expansion Fix (September 2025):**
- Fixed search functionality not affecting category table by correcting Vue.js watcher logic
- Resolved CategoryTree expansion issue where "Expand All/Collapse All" button wasn't working
- Separated search watcher from other filters for proper 500ms debouncing behavior
- Enhanced CategoryController with comprehensive advanced filtering capabilities
- Implemented visual filter chips showing active filters with individual removal options
- Added quick filter presets for common scenarios (root categories, active with products, etc.)
- Created advanced collapsible filter panel with date ranges, creator filters, and status options
- Enhanced filter persistence across pagination and navigation for better user experience
- Fixed chevron icons and proper state management in CategoryTree component

**System Administrator Audit Access Fix (January 2025):**
- Fixed role name mismatches preventing super administrators from accessing audit routes
- Super administrators can now access /audits, /users, /roles, and /permissions as intended
- Enhanced time-based access controls with proper super admin overrides
- Improved error handling for access restrictions with clearer user feedback
- Fixed middleware configuration issues affecting administrative route access

**Frontend Permission System (January 2025):**
- Implemented comprehensive Vue.js authorization composables for permission checking
- Added permission-based conditional rendering throughout the application
- Enhanced navigation with role-based menu hiding/showing
- Created PermissionGuard component for advanced access control
- Integrated real-time permission checking across all Vue components

**Role Users Management System:**
- Added comprehensive user assignment modal interface
- Implemented bulk user operations with real-time updates
- Enhanced search and filtering capabilities
- Improved user experience with immediate visual feedback

**Delete Confirmation Modal System (September 2025):**
- Implemented professional delete confirmation modals across Schools, Users, and Roles management
- Replaced browser confirm dialogs with accessible, branded confirmation interfaces
- Added loading states and automatic modal cleanup after successful deletions
- Enhanced user experience with clear action buttons and descriptive messaging
- Consistent architecture pattern across all management interfaces

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

*Last Updated: September 2025*
*Version: 2.2.0*