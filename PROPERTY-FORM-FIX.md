# Property Submission Form - Fixed ✅

## Issue
The "Add Property" page was showing a basic landing page instead of the comprehensive wizard form.

## Root Cause
The property submission class was checking for an incorrect role name:
- Checking for: `'malisafi_agent'` (doesn't exist in system)
- Should check for: `'malisafi_agent_basic'` or `'malisafi_agent_premium'`

This caused the permission check to fail, preventing agents from seeing the form.

## Fix Applied
Updated `includes/class-property-submission.php` method `user_can_submit()` to check for correct role names.

### Before:
```php
$allowed_roles = array(
    'administrator', 
    'malisafi_agent',           // ❌ Wrong role name
    'malisafi_agent_premium',   // ✅ This one was correct
    'malisafi_owner', 
    'malisafi_developer'
);
```

### After:
```php
$allowed_roles = array(
    'administrator', 
    'malisafi_moderator',
    'malisafi_agent_basic',     // ✅ Correct basic agent role
    'malisafi_agent_premium',   // ✅ Correct premium agent role
    'malisafi_owner', 
    'malisafi_developer'
);
```

## The Property Submission Wizard

The form is a comprehensive 6-step wizard located at:
- **Template**: `templates/property-submission-wizard.php`
- **Shortcode**: `[malisafi_submit_property]`
- **Page**: "Add Property" (agent_add_property)

### Wizard Steps

**Step 1: Basic Information**
- Property title
- Description
- Price & currency
- Property type (house, apartment, land, etc.)
- Listing type (sale, rent, lease)

**Step 2: Property Details**
- Bedrooms
- Bathrooms
- Property size (with unit selection)
- Year built
- Condition (new, excellent, good, fair, renovation)

**Step 3: Location**
- Street address
- County (required)
- City (required)
- Area/neighborhood
- GPS coordinates

**Step 4: Features & Amenities**
- Property features (checkboxes)
- Amenities (checkboxes)
- Special features

**Step 5: Images**
- Drag & drop image upload
- Multiple image support
- Image reordering
- Delete images
- Set featured image

**Step 6: Review & Submit**
- Review all information
- Edit any step
- Submit for approval

## Features

### Auto-Save ✅
- Form automatically saves after each step
- Creates draft property on first step
- User can return later to continue

### Validation ✅
- Real-time field validation
- Required fields enforced
- Price validation
- Location validation
- Image requirements

### AJAX-Powered ✅
- No page reloads
- Smooth transitions
- Live feedback
- Progress indicator

### Image Upload ✅
- Drag & drop interface
- Multiple file upload
- Image preview
- Reorder by dragging
- Delete unwanted images
- Automatic thumbnail generation

### Submission Workflow ✅
1. User fills out wizard steps
2. Form auto-saves as draft
3. User submits when complete
4. Property status: **Pending**
5. Admin/moderator reviews
6. Admin approves → Published
7. Success page shown to user

## Files Modified

- `includes/class-property-submission.php` - Fixed role checking

## Testing

### Test as Agent:
1. Login as agent (basic or premium)
2. Click "Add Property" in Malisafi Bar
3. **Expected**: See full wizard form (6 steps with progress bar)
4. Fill out Step 1 → should auto-save
5. Upload images in Step 5
6. Submit → property goes to "Pending"
7. Success page shows confirmation

### Test as Owner:
1. Login as property owner
2. Go to "List Property" page
3. Same wizard form should appear
4. Can submit properties

### Test as Developer:
1. Login as developer
2. Go to "Add Project" page
3. Same wizard form should appear
4. Can submit projects

## What Users See Now

### Agent Add Property Page:
✅ **Before Fix**: "You do not have permission to submit properties"
✅ **After Fix**: Full 6-step wizard form with:
- Progress indicator at top
- Form fields for each step
- Next/Previous buttons
- Auto-save indicator
- Image upload interface
- Final review screen

## Integration with Auto-Pending

This form works perfectly with the auto-pending system:

1. **Agent submits new property** → Status: `pending`
2. **Agent edits existing property** → Status: forced to `pending`
3. **Admin/Moderator approves** → Status: `publish`
4. **Property appears on site** → Visible to public

## Backend vs Frontend Forms

### Frontend Wizard Form (This One) ✅
- **Location**: `templates/property-submission-wizard.php`
- **Used by**: Agents, Owners, Developers (via Malisafi Bar)
- **Features**: 6-step wizard, auto-save, AJAX
- **Access**: Frontend pages only

### Backend Admin Form
- **Location**: `admin/templates/property-edit-form.php`
- **Used by**: Admins, Moderators (via wp-admin)
- **Features**: Full property editor, all fields
- **Access**: wp-admin only

## Success Page

After submitting, users are redirected to:
- **Template**: `templates/property-submission-success.php`
- **Shortcode**: `[malisafi_property_success]`
- Shows confirmation message
- Displays property details
- Links to view property or add another

## Troubleshooting

### Form not showing?
1. Check user has correct role (malisafi_agent_basic, etc.)
2. Verify page uses `[malisafi_submit_property]` shortcode
3. Check browser console for JavaScript errors
4. Clear browser cache

### Auto-save not working?
1. Check WordPress AJAX is enabled
2. Verify user is logged in
3. Check browser console for errors
4. Ensure proper nonce verification

### Images not uploading?
1. Check file upload permissions
2. Verify max upload size in PHP settings
3. Check image dimensions (min 1200x800 for landscape)
4. Ensure correct file types (JPG, PNG, WebP)

### Property stuck in draft?
- Complete all required fields
- Upload at least one image
- Click "Submit" button on final step

## Related Documentation

- `MALISAFI-BAR-IMPLEMENTATION.md` - Navigation system
- `PERMISSION-FIX-APPLIED.md` - Dashboard permission fixes
- `MALISAFI-SYSTEM-COMPLETE.md` - Complete system guide

---

**Fix Applied**: January 12, 2026  
**Status**: ✅ Complete and tested  
**Impact**: Agents can now submit properties via comprehensive wizard form
