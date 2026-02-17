# Property Admin Control - Implementation Guide

## Overview
Implemented full administrative control over properties, allowing admins and moderators to delete properties directly from the property list interface.

## Changes Made

### 1. Role Capabilities (includes/class-role-manager.php)
Added two new capabilities for administrators and moderators:

```php
// Allow admins and moderators to delete any property
$role->add_cap('delete_others_properties', true);
$role->add_cap('delete_properties', true);
```

**Affected Roles:**
- `administrator` - Can delete any property
- `malisafi_moderator` - Can delete any property

### 2. Property List UI (admin/templates/properties-list.php)

#### Icon-Based Action Buttons
Replaced text-based buttons with modern icon-based buttons:

- **Edit** - `dashicons-edit` (Blue on hover)
- **View** - `dashicons-visibility` (Blue on hover)
- **Delete** - `dashicons-trash` (Red, confirmation required)

#### Delete Functionality
```php
<?php 
$can_delete = current_user_can('delete_post', $property_id) 
              || current_user_can('delete_others_properties') 
              || current_user_can('manage_options');
?>
```

**Permission Check (Triple Logic):**
1. `delete_post` - Can delete own posts
2. `delete_others_properties` - Can delete any property
3. `manage_options` - Super admin escape hatch

**Process:**
1. User clicks delete button
2. JavaScript confirmation dialog appears
3. If confirmed, redirects to `admin-post.php?action=malisafi_delete_property`
4. Handler moves property to trash (not permanent delete)
5. Redirects back to property list with success message

#### Styling
Added comprehensive CSS for action buttons:

```css
.row-actions-wrapper {
    display: flex;
    gap: 8px;
    align-items: center;
}

.row-actions-wrapper .button {
    min-width: 30px;
    height: 30px;
    padding: 0 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: all 0.2s ease;
}
```

**Button States:**
- Default: Light gray background, blue icon
- Hover: Blue background, white icon
- Delete (default): White background, red icon
- Delete (hover): Red background, white icon

## How It Works

### Delete Flow
1. **Admin views property list** → `/wp-admin/admin.php?page=malisafi-properties`
2. **Clicks delete button** → JavaScript confirmation
3. **Confirms deletion** → Nonce-verified URL to admin-post handler
4. **Handler processes** → `handle_delete_property()` in class-dashboard-shortcodes.php
5. **Property trashed** → Uses WordPress `wp_trash_post($property_id)`
6. **Redirect back** → Property list with message parameter
7. **Success message** → "Property moved to trash"

### Existing Handlers
The delete functionality uses existing handlers in `includes/class-dashboard-shortcodes.php`:

- `handle_delete_property()` - Moves property to trash (soft delete)
- `handle_delete_property_permanently()` - Permanently deletes property
- `handle_restore_property()` - Restores from trash

Currently only trash functionality is exposed in the UI.

## User Permissions

### Who Can Delete Properties?

| Role | Delete Own | Delete Others | Why |
|------|-----------|---------------|-----|
| Administrator | ✅ Yes | ✅ Yes | `manage_options` + `delete_others_properties` |
| Moderator | ✅ Yes | ✅ Yes | `delete_others_properties` |
| Agent Premium | ✅ Yes | ❌ No | `delete_post` capability only |
| Agent Basic | ✅ Yes | ❌ No | `delete_post` capability only |
| Owner | ✅ Yes | ❌ No | `delete_post` capability only |
| Developer | ✅ Yes | ❌ No | `delete_post` capability only |
| Client | ❌ No | ❌ No | Read-only access |

## Testing Checklist

- [ ] Admin can see delete button on all properties
- [ ] Moderator can see delete button on all properties
- [ ] Agent sees delete button only on own properties
- [ ] Client sees no delete buttons
- [ ] Delete button shows confirmation dialog
- [ ] Property moves to trash (not permanently deleted)
- [ ] Success message appears after deletion
- [ ] Redirects back to property list
- [ ] Icon buttons display correctly
- [ ] Hover states work properly
- [ ] Button tooltips show on hover
- [ ] Responsive on mobile devices

## Future Enhancements

### 1. Bulk Actions
Add bulk delete functionality:
```html
<select name="bulk_action">
    <option value="">Bulk Actions</option>
    <option value="trash">Move to Trash</option>
    <option value="delete">Delete Permanently</option>
</select>
```

### 2. Trash View
Add filter to view trashed properties:
```php
// Show trashed properties
$args['post_status'] = 'trash';

// Add restore button
<a href="..." class="button-restore">
    <span class="dashicons dashicons-undo"></span>
</a>
```

### 3. Permanent Delete
Add "Delete Permanently" for trashed items:
```php
// Only show for trashed properties
if ($property->post_status == 'trash') {
    // Show permanent delete button
}
```

### 4. Status Control
Add quick status change dropdown:
```html
<select onchange="changeStatus(this, property_id)">
    <option value="publish">Publish</option>
    <option value="pending">Pending</option>
    <option value="draft">Draft</option>
</select>
```

### 5. Featured Toggle
Add quick featured property toggle:
```php
<button onclick="toggleFeatured(property_id)" class="button-featured">
    <span class="dashicons dashicons-star-filled"></span>
</button>
```

## Files Modified
1. `includes/class-role-manager.php` - Added capabilities
2. `admin/templates/properties-list.php` - Added delete button and styling

## Related Systems
- **Delete Handlers**: `includes/class-dashboard-shortcodes.php`
- **Role Management**: `includes/class-role-manager.php`
- **Property Post Type**: `includes/class-post-types.php`
- **User Management**: `admin/templates/users-management.php` (similar button style)

## Notes
- Delete operation is soft delete (trash) not permanent
- Nonce verification prevents CSRF attacks
- Triple permission check provides flexibility
- Icon-based UI consistent with modern admin design
- Confirmation dialog prevents accidental deletion
- `manage_options` capability provides admin escape hatch

## Deployment
1. Deactivate and reactivate plugin to refresh capabilities
2. Test with different user roles
3. Verify delete operations log properly
4. Check trash functionality in WordPress admin
