# Property Submission, Editing & Steps Review

## Overview
The Malisafi MLS plugin includes a comprehensive property submission and editing system with a 6-step wizard interface, auto-save functionality, image uploads, and a moderation workflow.

## Property Submission Workflow

### **Step 1: Basic Information**
- **Title** (required, min 5 chars)
- **Description** (recommended min 20 chars)
- **Property ID/Reference** (auto-generated or manual)
- **Price** (required, with currency selection)
- **Property Type** (House, Apartment, Land, Commercial, Industrial)
- **Listing Type** (Sale, Rent, Lease, Short-term Rent)

### **Step 2: Property Details**
- **Bedrooms/Bathrooms** (numeric inputs)
- **Size** (with unit selection: sqm, sqft, acres, hectares)
- **Year Built** (optional)
- **Condition** (New, Excellent, Good, Fair, Needs Renovation)
- **Agent/Contact Information** (for admins/moderators)
- **Sale/Lease Details** (conditional based on listing type):
  - Floor plans, ROI, rental yield, annual income
  - Ownership type, title deed status, developer guarantees
  - Financing options, deposit requirements, tenor
  - Sustainability features, green certification

### **Step 3: Location (Kenya-Specific)**
- **Street Address** (optional)
- **County** (required, 47 Kenyan counties dropdown)
- **Subcounty** (required, dynamic based on county)
- **City/Town** (optional)
- **Area/Neighborhood** (optional)
- **GPS Coordinates** (optional, with "Get My Location" button)
- **Google Maps URL** (optional, extracts coordinates automatically)
- **Privacy Protection**: GPS coordinates are offset on public maps

### **Step 4: Features & Amenities**
- **Key Features**: Parking, Garden, Balcony, Terrace, Pool, Gym, Security, Furnished
- **Amenities**: WiFi, AC, Heating, Elevator, Backup Generator, Water Backup, Playground, Clubhouse

### **Step 5: Images**
- **Featured Image** (required, drag & drop or browse)
- **Gallery Images** (up to 15, drag & drop or browse, reorderable)
- **Supported Formats**: JPG, PNG, WEBP (max 10MB each)
- **Auto-upload** with progress indicators

### **Step 6: Review & Submit**
- **Preview** of all entered information
- **Image Gallery Preview**
- **Final Validation** before submission
- **Submit Button** (sends for approval)

## Key Features

### **Auto-Save Functionality**
- Saves progress every 30 seconds
- Manual "Save Draft" option
- Loads draft data when returning to edit

### **Image Upload System**
- Drag & drop interface
- WordPress media library integration
- Image reordering via drag & drop
- Featured image requirement
- Gallery image limits

### **Validation & Limits**
- User role-based property limits (configurable in admin)
- Real-time form validation
- Required field enforcement
- Property count checking

### **User Permissions**
- **Agents Basic**: Limited submissions (default 10)
- **Agents Premium**: Higher limits (default unlimited)
- **Owners**: Moderate limits (default 3)
- **Developers**: High limits (default unlimited)
- **Moderators**: No limits
- **Admins**: Full access

## Property Editing System

### **Edit Access**
- Users can edit their own properties
- Edit links in agent/owner/developer dashboards
- URL parameter: `?property_id=123`

### **Edit Behavior**
- Loads existing property data into wizard
- Maintains all uploaded images
- Preserves GPS coordinates and location data
- Auto-saves changes during editing

### **Status Changes on Edit**
- **Agent edits**: Property status → `pending` (requires re-approval)
- **Admin/Moderator edits**: Can maintain `publish` status
- **Draft properties**: Remain as drafts

## Moderation & Approval Workflow

### **Submission Status**
- **New Properties**: `pending` (awaiting approval)
- **Agent Basic**: Always `pending`
- **Agent Premium**: Can be `publish` (configurable)
- **Admin/Moderator**: Direct `publish`

### **Approval Process**
- Moderators review pending properties
- Admin interface for property management
- Email notifications (planned)
- Bulk approval capabilities

### **Edit Re-approval**
- Any property edit by non-admin users → `pending`
- Ensures content quality and accuracy
- Prevents unauthorized changes

## Technical Implementation

### **Core Files**
- `includes/class-property-submission.php` - Main submission logic
- `templates/property-submission-wizard.php` - Frontend form
- `assets/js/property-submission.js` - Frontend behavior
- `includes/class-property-approval-workflow.php` - Moderation system

### **AJAX Endpoints**
- `malisafi_save_property_step` - Auto-save
- `malisafi_submit_property` - Final submission
- `malisafi_upload_property_images` - Image uploads
- `malisafi_get_property_draft` - Load drafts

### **Database Integration**
- Custom table: `mf_user_limits` for user restrictions
- WordPress posts table for properties
- Meta fields for all property data
- Taxonomy system for categories

### **Security Features**
- Nonce verification on all requests
- User permission checks
- File upload validation
- XSS protection via sanitization

## Admin Configuration

### **User Limits Settings**
- Configurable limits per role
- Real-time updates
- Fallback to defaults if not set

### **Page Management**
- Dedicated submission pages per role
- Shortcode integration
- URL-based access

## Current Issues & Improvements Needed

### **Known Issues**
1. **Image Upload Buttons**: May not appear in some browsers
2. **GPS Coordinate Extraction**: Google Maps URL parsing
3. **Mobile Responsiveness**: Form layout on small screens
4. **Auto-save Reliability**: Network interruption handling

### **Enhancement Opportunities**
1. **Email Notifications**: Submission and approval alerts
2. **Bulk Operations**: Admin bulk approval/rejection
3. **Advanced Validation**: Business rule enforcement
4. **Analytics**: Submission tracking and metrics
5. **Multi-language**: Enhanced i18n support

## Usage Flow

### **For Agents/Owners**
1. Access submission page via dashboard
2. Complete 6-step wizard
3. Upload required images
4. Submit for approval
5. Receive notification when approved
6. Edit properties as needed (triggers re-approval)

### **For Moderators**
1. Review pending properties in admin
2. Approve or reject submissions
3. Edit properties if needed
4. Monitor submission quality

### **For Admins**
1. Configure user limits and settings
2. Override approvals if needed
3. Manage all properties directly
4. Access full moderation tools

This system provides a comprehensive, user-friendly property management workflow with proper validation, security, and moderation controls.