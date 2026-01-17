# Quick Test Guide - Agent Dashboard "Add Property" Navigation

## Test Scenario

**User Role**: Agent (Basic or Premium)  
**Starting Page**: Agent Dashboard (`/agent-dashboard/`)

## Test Points

### 1. Sidebar Navigation Test
1. Login as agent
2. Go to Agent Dashboard
3. Look at LEFT SIDEBAR
4. **Click "Add Property"** menu item (plus icon)
5. **Expected Result**: Custom property form loads on same page or dedicated `/add-property/` page
6. **Status**: ✅ Working - Form displays with all fields visible

### 2. Quick Action Button Test
1. Stay on Agent Dashboard
2. Look for **"Quick Actions"** section (under welcome message)
3. **Click "Add New Property"** button (blue button)
4. **Expected Result**: Custom property form displays
5. **Status**: ✅ Working - Form shows form fields

### 3. Properties Section Test
1. Click **"My Properties"** in sidebar
2. You should see your current properties list
3. If NO properties exist, there should be an **"Add Your First Property"** button
4. If properties exist, there should be **"Add New Property"** button at top
5. **Click the button**
6. **Expected Result**: Custom property form displays
7. **Status**: ✅ Working - Form is ready to fill

### 4. Empty State Test (First Time Agents)
1. Create new agent account (or use agent with no properties)
2. Go to Agent Dashboard
3. Look at home section - should see "You haven't listed any properties yet" message
4. **Click "Add Your First Property"** button
5. **Expected Result**: Custom property form displays
6. **Status**: ✅ Working - Form loads

### 5. Top Navigation Bar Test (Optional)
1. If you have Malisafi Bar enabled at top of page
2. Look for **"Add Property"** in the navigation bar
3. **Click it**
4. **Expected Result**: Goes to property form
5. **Status**: ✅ Working - Malisafi Bar uses Page_Manager

## Form Validation Test

Once form loads:

### Required Fields (must fill)
- ✅ Property Title
- ✅ Property Price
- ✅ County (dropdown - select any)
- ✅ Setting (urban/semi-rural/rural/isolated)
- ✅ Address

### Optional Fields
- ✅ Description
- ✅ Bedrooms
- ✅ Bathrooms
- ✅ Area (sqm)
- ✅ Features (checkboxes: pool, gym, garden, etc.)
- ✅ Neighbourhood
- ✅ Year Built
- ✅ Garage spaces

### Image Upload
- ✅ Click "Add Images" button
- ✅ Select images from computer
- ✅ Images should preview
- ✅ Can remove images

## Expected Behavior After Form Submit

1. Form validates all required fields
2. If errors, shows error messages
3. If valid, submits via AJAX
4. Shows "Processing..." message
5. On success:
   - Shows "Property created successfully!" message
   - For **Basic Agent**: Status = **Pending** (requires admin approval)
   - For **Premium Agent**: Status = **Published** (goes live immediately)
6. Redirects to success page or dashboard
7. New property appears in "My Properties" list

## Troubleshooting

**Issue**: Form doesn't load, see blank page
- **Fix**: Check WordPress debug log at `/wp-content/debug.log`
- Verify agent role is set correctly: `malisafi_agent_basic` or `malisafi_agent_premium`

**Issue**: Form shows but submit button doesn't work
- **Fix**: Check browser console (F12) for JavaScript errors
- Verify nonce is present in form: `malisafi_property_submit_nonce`

**Issue**: "You do not have permission" message
- **Fix**: Verify user role is agent
- Check: `/wp-admin/users.php` → Edit user → Role should include Agent

**Issue**: Redirect back to admin page
- **Fix**: Old code was redirecting to `/wp-admin/`
- Update code to use latest version from main branch
- Clear browser cache (Ctrl+Shift+Delete)

## Success Indicators

✅ **All tests pass** - All entry points lead to custom form  
✅ **Form displays** - No redirects to admin backend  
✅ **Navigation works** - Sidebar, quick actions, properties page all work  
✅ **Form validates** - Required field validation works  
✅ **Images upload** - Can select and preview images  
✅ **Submit works** - Form submits and creates property  
✅ **Status correct** - Property has correct status (pending/published)  

## Quick Navigation Check

All these should go to the SAME form:

```
1. Sidebar: Add Property (plus icon)
2. Dashboard: Quick Action "Add New Property" 
3. My Properties: "Add New Property" button
4. First time: "Add Your First Property" button
5. Top Bar: "Add Property" (if enabled)

All 5 → Custom property form ✅
```

---

**Last Tested**: January 17, 2026  
**Status**: ✅ All navigation fixed and working  
**Agent Experience**: 🎯 Smooth, no redirects, clean form  
