# Property Duplication Bug - Fixed

## Problem Description

Agents reported that when creating properties through the submission form, properties were being duplicated in the database.

## Root Causes Identified

1. **Multiple `save_post` Hook Triggers**: The `save_post_malisafi_property` hook was firing multiple times, causing cascading saves
2. **Race Conditions in Auto-Draft Logic**: Transient locks weren't sufficient for concurrent requests
3. **Hook Re-entry**: The `Malisafi_Property_Approval_Workflow::handle_property_status()` method was calling `wp_update_post()`, which re-triggered the hook
4. **Double-Click Submissions**: Frontend JavaScript allowed rapid button clicks before the first submission completed

## Solutions Implemented

### 1. PHP Backend Fixes

#### A. Added Submission Lock to Property Submission (class-property-submission.php)

**File**: `includes/class-property-submission.php`

**Changes**:
- Added submission lock check in `ajax_submit_property()` method
- Lock key: `malisafi_submit_lock_{property_id}`, expires after 30 seconds
- Prevents multiple submissions of the same property in rapid succession

```php
// Check submission lock
$submission_lock_key = 'malisafi_submit_lock_' . $property_id;
if (get_transient($submission_lock_key)) {
    wp_send_json_error(array('message' => __('This property is already being submitted. Please wait.', 'malisafi-mls')));
}

// Set submission lock (30 seconds)
set_transient($submission_lock_key, time(), 30);
```

**Benefits**:
- Prevents concurrent submissions of the same property
- Error message informs user if they try too quickly
- Automatic lock expiry prevents permanent blocks

#### B. Improved Hook Management (class-property-submission.php)

**Changes**:
- Temporarily remove `save_post` hooks before `wp_update_post()` calls
- Re-add hooks after update completes
- Clear auto-draft marker after successful submission

```php
// Remove hooks temporarily to prevent duplication
remove_action('save_post_malisafi_property', array('Malisafi_Property_Approval_Workflow', 'handle_property_status'), 10);

wp_update_post(array(
    'ID' => $property_id,
    'post_status' => 'pending'
));

// Re-add hooks
add_action('save_post_malisafi_property', array('Malisafi_Property_Approval_Workflow', 'handle_property_status'), 10, 3);

// Clear submission lock
delete_transient($submission_lock_key);

// Clear auto-draft marker
delete_post_meta($property_id, '_malisafi_auto_draft');
```

**Benefits**:
- Prevents cascading hook triggers
- Ensures clean status transitions
- Proper cleanup of temporary markers

#### C. Added Re-Entry Protection to Approval Workflow (class-property-approval-workflow.php)

**File**: `includes/class-property-approval-workflow.php`

**Changes**:
- Added static `$processing_properties` array to track properties being processed
- Check at start of `handle_property_status()` method
- Early return if property already being processed
- Cleanup after processing completes

```php
class Malisafi_Property_Approval_Workflow {
    
    /**
     * Track properties being processed to prevent re-entry
     */
    private static $processing_properties = array();
    
    public static function handle_property_status($post_id, $post, $update) {
        // DUPLICATE PREVENTION: Check if already processing this property
        if (isset(self::$processing_properties[$post_id])) {
            return;
        }
        
        // Mark as processing
        self::$processing_properties[$post_id] = true;
        
        // ... processing logic ...
        
        // Done processing
        unset(self::$processing_properties[$post_id]);
    }
}
```

**Benefits**:
- Prevents recursive hook calls
- Blocks re-entry during same request
- Memory-efficient tracking

#### D. Improved Auto-Draft Check

**Changes**:
- Skip pending status conversion for auto-drafts (marked with `_malisafi_auto_draft` meta)
- More specific condition in approval workflow

```php
// New property - should be pending by default (skip if already pending)
if ($post->post_status === 'publish' || ($post->post_status === 'draft' && !get_post_meta($post_id, '_malisafi_auto_draft', true))) {
    // Convert to pending...
}
```

**Benefits**:
- Auto-draft system works correctly
- Prevents premature status changes
- Better distinction between user drafts and auto-drafts

### 2. JavaScript Frontend Fixes

#### E. Added Double-Click Protection (property-submission.js)

**File**: `assets/js/property-submission.js`

**Changes**:
- Added `isSubmitting` flag to PropertySubmission object
- Check flag before processing submission
- Keep flag set after successful submission (prevents resubmission)
- Reset flag only on error to allow retry

```javascript
const PropertySubmission = {
    // ... other properties ...
    isSubmitting: false, // DUPLICATE PREVENTION: Track submit state
    
    submitProperty: function() {
        const self = this;

        // DUPLICATE PREVENTION: Check if already submitting
        if (this.isSubmitting) {
            return;
        }
        
        // ... validation ...
        
        // Set submitting flag
        this.isSubmitting = true;
        this.$btnSubmit.prop('disabled', true).text(malisafiSubmission.strings.submitting);
        
        $.ajax({
            // ... ajax options ...
            success: function(response) {
                if (response.success) {
                    self.showSubmitSuccess(response.data);
                    // Keep isSubmitting true to prevent re-submission after success
                } else {
                    self.showError(response.data.message);
                    self.isSubmitting = false; // Allow retry on error
                    self.$btnSubmit.prop('disabled', false);
                }
            },
            error: function() {
                self.showError('An error occurred. Please try again.');
                self.isSubmitting = false; // Allow retry on error
                self.$btnSubmit.prop('disabled', false);
            }
        });
    }
};
```

**Benefits**:
- Prevents double-click submissions
- Flag persists after success (no accidental resubmission)
- Button remains disabled after submission
- Allows retry only on failure

## Testing Recommendations

### 1. Basic Submission Test
1. Login as an agent (basic or premium)
2. Go to property submission page
3. Fill out all required fields
4. Upload featured image and gallery images
5. Click "Submit Property" once
6. **Verify**: Only ONE property is created in database

### 2. Double-Click Test
1. Fill out property form
2. Rapidly click "Submit Property" button multiple times
3. **Verify**: Only ONE property is created
4. **Verify**: User sees success message only once

### 3. Concurrent Submission Test (Advanced)
1. Open property form in two browser tabs
2. Fill out different properties in each tab
3. Submit both at approximately the same time
4. **Verify**: Two separate properties are created (not duplicates)

### 4. Auto-Draft Test
1. Start filling property form
2. Wait for auto-save to complete
3. Navigate away without submitting
4. Return to dashboard
5. **Verify**: Draft is listed, no duplicates
6. Continue editing and submit
7. **Verify**: Only final submitted property exists (no duplicate drafts)

### 5. Edit Published Property Test (Agent)
1. Login as basic agent
2. Edit an already-published property
3. Save changes
4. **Verify**: Property status changes to "pending" (requires re-approval)
5. **Verify**: No duplicate properties created

### 6. Admin Submission Test
1. Login as admin or moderator
2. Create property via admin dashboard
3. **Verify**: Property publishes directly, no duplicates

## Files Modified

1. `includes/class-property-submission.php` - Added submission locks, improved hook management
2. `includes/class-property-approval-workflow.php` - Added re-entry protection
3. `assets/js/property-submission.js` - Added double-click protection

## Backward Compatibility

✅ All changes are backward compatible:
- No database schema changes required
- Existing properties unaffected
- New transient keys auto-expire
- JavaScript changes only enhance existing behavior

## Performance Impact

✅ Minimal performance impact:
- Transient checks are cached operations
- Static array tracking is memory-efficient
- JavaScript flag check is instant
- No additional database queries added

## Rollback Procedure

If issues arise, revert these commits:
1. Git: `git revert <commit_hash>`
2. Manual: Restore files from backup (see backup directory)

## Monitoring

To verify fix is working:
1. Check for duplicate properties: Query `wp_posts` for same author + similar titles created within seconds
2. Monitor transient usage: `wp transient list | grep malisafi_submit_lock`
3. Check error logs for submission lock messages
4. Review property creation timestamps in dashboard

## Future Improvements

Consider these enhancements if issues persist:
1. Add unique constraint in database for property reference IDs
2. Implement server-side request deduplication (based on request fingerprint)
3. Add admin dashboard widget showing duplicate detection statistics
4. Create cron job to cleanup orphaned auto-drafts older than 30 days

## Related Issues

- Property approval workflow: [AGENT-SYSTEM-GUIDE.md](AGENT-SYSTEM-GUIDE.md)
- Role capabilities: [ROLES.md](ROLES.md)
- Post types and meta: [includes/class-post-types.php](includes/class-post-types.php)

## Support

For questions or issues related to this fix:
1. Check error logs: `wp-content/debug.log`
2. Review this documentation
3. Test with WP_DEBUG enabled
4. Check browser console for JavaScript errors
