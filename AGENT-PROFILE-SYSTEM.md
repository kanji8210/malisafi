# Agent Profile System - Complete Implementation Guide

## Overview

The Malisafi MLS Agent Profile System provides agents with comprehensive profile management and public visibility. Users can view agent profiles, rate them, and see their listings. Agents can edit their profiles with photos, bio, contact info, and social media links.

## Architecture

### Components

1. **AJAX Handler** - `includes/class-agent-profile-ajax.php`
   - Photo upload handler: `upload_agent_photo`
   - Profile save handler: `save_agent_profile`
   - Review submission: `submit_agent_review`

2. **Shortcodes** - `includes/class-dashboard-shortcodes.php`
   - Dashboard profile editor: `[malisafi_agent_dashboard]` with `?section=profile`
   - Public profile view: `[malisafi_agent_profile_view]?agent_id=123`

3. **Templates**
   - Dashboard editor: `templates/agent-dashboard-profile.php` (411 lines)
   - Public profile: `templates/agent-profile-public.php` (320 lines)

4. **Styling**
   - Dashboard CSS: `assets/css/agent-dashboard-modern.css` (600+ lines)
   - Public CSS: `assets/css/agent-profile-public.css` (500+ lines)

### Database Schema

#### wp_mf_agent_ratings Table

```sql
CREATE TABLE wp_mf_agent_ratings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agent_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED (1-5),
    review_title VARCHAR(255),
    review_text TEXT,
    property_id BIGINT UNSIGNED (optional),
    verified_client BOOLEAN,
    helpful_count INT DEFAULT 0,
    not_helpful_count INT DEFAULT 0,
    agent_response TEXT,
    agent_responded_at DATETIME,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
```

## Agent Meta Fields

All agent profile data stored as post meta on `malisafi_agent` post type:

| Meta Key | Type | Description |
|----------|------|-------------|
| `_agent_user_id` | integer | WordPress user ID |
| `_agent_photo` | integer | Attachment ID |
| `_agent_bio` | text | Professional biography |
| `_agent_email` | email | Contact email |
| `_agent_phone` | text | Phone number |
| `_agent_whatsapp` | text | WhatsApp number |
| `_agent_specialties` | text | Comma-separated specialties |
| `_agent_experience` | integer | Years of experience |
| `_agent_license` | text | Real estate license number |
| `_agent_languages` | text | Languages spoken |
| `_agent_facebook` | url | Facebook profile URL |
| `_agent_twitter` | url | Twitter profile URL |
| `_agent_linkedin` | url | LinkedIn profile URL |
| `_agent_instagram` | url | Instagram profile URL |

## Frontend Features

### Agent Dashboard Profile Editor

**Location**: Agent Dashboard → Profile section
**URL**: `/agent-dashboard/?section=profile`
**Shortcode**: `[malisafi_agent_dashboard]`

**Features**:
- Photo upload with preview (400x400px recommended, max 2MB)
- Remove existing photo
- Basic information form:
  - Email (required)
  - Phone (required)
  - WhatsApp number
  - License number
  - Years of experience
  - Languages spoken
- Professional info:
  - Bio/description
  - Specialties (comma-separated)
- Social media links:
  - Facebook
  - Twitter
  - LinkedIn
  - Instagram
- View public profile link
- Real-time validation
- Success/error messages

**Technical Details**:

Form submission:
```javascript
FormData → AJAX POST → save_agent_profile action
→ Create/update malisafi_agent post
→ Update all meta fields
→ Return success with agent_id
```

Photo upload:
```javascript
File input → Preview generation
→ AJAX POST → upload_agent_photo action
→ wp_handle_upload() → Create attachment
→ Return attachment_id
→ Store in _agent_photo meta
```

### Public Agent Profile View

**Location**: Standalone page or embedded on website
**URL**: `/agent-profile/?agent_id=123` or custom page
**Shortcode**: `[malisafi_agent_profile_view agent_id="123"]`

**Sections**:

1. **Agent Header**
   - Profile photo (150x150px circle)
   - Agent name
   - License number badge
   - Star rating with review count
   - Years of experience

2. **Contact Section**
   - Phone button (tel: link)
   - WhatsApp button (wa.me/)
   - Email button (mailto:)
   - Send message button

3. **Stats Bar**
   - Active listings count
   - Total reviews count
   - Years of experience

4. **Bio & Specialties**
   - Full biography text
   - Specialty tags (comma-separated list)

5. **Current Listings Grid**
   - 6 most recent published properties
   - Property image, title, price
   - Bedroom/bathroom counts
   - View more link

6. **Reviews Section**
   - Average star rating
   - Review list with:
     - Reviewer name & avatar
     - Star rating
     - Review comment
     - Publication date
   - "Write a Review" button (logged-in users only)
   - Review modal for submission

## API Endpoints

### AJAX Actions

All actions use nonce verification for security.

#### upload_agent_photo

**POST** `/wp-admin/admin-ajax.php?action=upload_agent_photo`

**Parameters**:
```javascript
{
    photo: File,  // from file input
    nonce: string // wp_nonce_field('upload_agent_photo', 'nonce')
}
```

**Response**:
```json
{
    "success": true,
    "data": {
        "attachment_id": 123,
        "url": "https://example.com/wp-content/uploads/2024/01/photo.jpg"
    }
}
```

**Validation**:
- File type: JPEG, PNG, WebP only
- File size: Max 2MB
- User must be logged in

#### save_agent_profile

**POST** `/wp-admin/admin-ajax.php?action=save_agent_profile`

**Parameters**:
```javascript
{
    agent_id: integer,              // 0 for new profile
    agent_email: string,            // required
    agent_phone: string,            // required
    agent_whatsapp: string,
    agent_bio: string,
    agent_specialties: string,
    agent_experience: integer,
    agent_license: string,
    agent_languages: string,
    agent_photo_id: integer,        // from upload_agent_photo
    agent_facebook: string,         // URL
    agent_twitter: string,          // URL
    agent_linkedin: string,         // URL
    agent_instagram: string,        // URL
    agent_profile_nonce: string     // wp_nonce_field('save_agent_profile')
}
```

**Response**:
```json
{
    "success": true,
    "data": {
        "message": "Profile saved successfully!",
        "agent_id": 123
    }
}
```

**Process**:
1. Verify nonce & user authentication
2. Sanitize all inputs
3. Check required fields (email, phone)
4. Create agent post if agent_id = 0
5. Link to current user via `_agent_user_id` meta
6. Update all meta fields
7. Return agent_id

#### submit_agent_review

**POST** `/wp-admin/admin-ajax.php?action=submit_agent_review`

**Parameters**:
```javascript
{
    agent_id: integer,              // required
    rating: integer,                // 1-5, required
    comment: string                 // optional
}
```

**Response**:
```json
{
    "success": true,
    "data": {
        "message": "Thank you! Your review is pending approval."
    }
}
```

**Process**:
1. User must be logged in
2. Verify rating is 1-5
3. Check user hasn't already reviewed this agent
4. Insert into `wp_mf_agent_ratings` table
5. Set status = 'pending' (requires admin approval)
6. Return success message

## Usage Examples

### Display Agent Profile on Page

Add shortcode to page content:
```
[malisafi_agent_profile_view agent_id="123"]
```

Or embed in template:
```php
<?php
// In theme or plugin template file
echo do_shortcode('[malisafi_agent_profile_view agent_id="123"]');
?>
```

### Display Agent Dashboard

For agents viewing their own profile:
```
[malisafi_agent_dashboard]
```

Navigate to Profile section via menu.

### Create Agent Profile Link

Generate link to specific agent profile:
```php
<?php
$agent_id = get_post_meta($property_id, '_agent_id', true);
$profile_url = add_query_arg('agent_id', $agent_id, home_url('/agent-profile/'));
echo '<a href="' . esc_url($profile_url) . '">View Agent Profile</a>';
?>
```

### Display Agent Properties

In agent profile public view:
```php
<?php
$args = array(
    'post_type' => 'malisafi_property',
    'post_status' => 'publish',
    'meta_query' => array(
        array(
            'key' => '_agent_id',
            'value' => $agent_id,
            'compare' => '='
        )
    ),
    'posts_per_page' => 6
);
$properties = new WP_Query($args);
?>
```

## Security Considerations

### Nonce Verification

All AJAX actions verify nonces:
```php
check_ajax_referer('upload_agent_photo', 'nonce');
check_ajax_referer('save_agent_profile', 'agent_profile_nonce');
```

### User Authentication

- Profile edit: User must be logged in
- Profile view: Public, no login required
- Reviews: User must be logged in

### Capability Checks

Profile editing:
```php
// Owner can only edit own profile
$linked_user = get_post_meta($agent_id, '_agent_user_id', true);
if ($linked_user != $current_user->ID && !current_user_can('manage_options')) {
    wp_send_json_error();
}
```

### Input Sanitization

```php
sanitize_email()       // for email fields
sanitize_text_field()  // for text inputs
sanitize_textarea_field() // for textarea
esc_url_raw()         // for URLs
intval()              // for numbers
```

### File Upload Security

- Whitelist file types: JPEG, PNG, WebP
- Enforce size limit: 2MB max
- Use `wp_handle_upload()` with allowed types
- Create attachment via `wp_insert_attachment()`
- Generate metadata with `wp_generate_attachment_metadata()`

## Review Moderation

### Workflow

1. User submits review via modal
2. Review inserted with `status='pending'`
3. Admin notification (optional - can be implemented)
4. Admin reviews in backend
5. Admin approves/rejects
6. If approved, shows on public profile

### Display Only Approved Reviews

```php
$wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}mf_agent_ratings 
     WHERE agent_id = %d AND status = 'approved' 
     ORDER BY created_at DESC LIMIT 10",
    $agent_id
));
```

## Admin Interface

### Create Review Moderation Page (Future)

Location: `/wp-admin/?page=malisafi-review-moderation`

Would include:
- List of pending reviews
- Approve/reject buttons
- Agent and user details
- Review content
- Bulk actions

## Styling Notes

### CSS Variables Used

From `agent-dashboard-modern.css`:
```css
--mls-dark: #3a4034
--mls-accent: #737d5d
--mls-grey-green: #c5d3b6
--mls-light-grey: #f5f5f5
--mls-text-primary: #333
--mls-border-light: #ddd
```

### Responsive Breakpoints

- Desktop: 1200px+
- Tablet: 768px - 1199px
- Mobile: < 768px

## Testing Checklist

### Agent Profile Editor
- [ ] Upload photo (test file types, sizes)
- [ ] Remove photo
- [ ] Save profile with all fields
- [ ] Validate required fields (email, phone)
- [ ] Test with special characters
- [ ] Verify agent post creation
- [ ] Verify all meta fields saved
- [ ] Update existing profile
- [ ] View public profile link works

### Public Profile View
- [ ] Display all agent information
- [ ] Photo displays correctly
- [ ] Rating calculation correct
- [ ] Review count accurate
- [ ] Properties grid loads
- [ ] Contact buttons functional
- [ ] Responsive design mobile
- [ ] Write review button shows only for logged-in

### Review System
- [ ] Submit review as logged-in user
- [ ] Star rating selection works
- [ ] Validation: 1-5 stars only
- [ ] Prevent duplicate reviews
- [ ] Show pending message
- [ ] Review appears after admin approval
- [ ] Average rating recalculates
- [ ] Review count updates

### Security
- [ ] Nonce verification works
- [ ] Can't edit other user's profile
- [ ] Admin can manage all profiles
- [ ] File upload type validation
- [ ] File size enforcement
- [ ] Input sanitization

## Future Enhancements

1. **Review Moderation Admin Panel**
   - Approve/reject pending reviews
   - Bulk actions
   - Filter by agent/status

2. **Agent Responses**
   - Allow agents to respond to reviews
   - Display responses on profile

3. **Helpful Votes**
   - Users can mark reviews as helpful
   - Track helpful_count in database

4. **Agent Badges**
   - Certified agent badge
   - Top rated badge (4.5+ stars)
   - Recently active badge

5. **Review Filters**
   - Sort by date, rating
   - Filter by rating (5-star, 4-star, etc.)
   - Show verified clients only

6. **Email Notifications**
   - Notify agent of new review
   - Notify reviewer when review approved
   - Monthly rating digest

7. **Agent Directory**
   - Browse all agents
   - Filter/search by name, specialty, location
   - Compare agent ratings

## Troubleshooting

### Photo Upload Fails

**Issue**: "File size too large"
- Check max file size setting: 2MB limit
- Compress image before upload
- Try different image format (JPG/PNG)

**Issue**: "Invalid file type"
- Allowed types: JPEG, PNG, WebP
- Upload correct format file

**Issue**: Attachment not created
- Check WordPress upload directory permissions
- Verify disk space available
- Check error log

### Profile Not Saving

**Issue**: "Failed to save profile"
- Check nonce validity (page refresh)
- Verify user is logged in
- Check required fields (email, phone)
- Review browser console for JS errors

**Issue**: Meta fields not updating
- Check post exists in database
- Verify meta keys spelled correctly
- Check user has edit_post capability

### Reviews Not Showing

**Issue**: "No reviews on public profile"
- Check review status = 'approved' (not 'pending')
- Verify agent_id matches in ratings table
- Check SQL query in template

## File Reference

| File | Lines | Purpose |
|------|-------|---------|
| `includes/class-agent-profile-ajax.php` | 230 | AJAX handlers |
| `includes/class-core.php` | ~80 | Load handler |
| `includes/class-dashboard-shortcodes.php` | 1484 | Shortcode registration |
| `templates/agent-dashboard-profile.php` | 411 | Profile editor |
| `templates/agent-profile-public.php` | 320 | Public view |
| `assets/css/agent-dashboard-modern.css` | 600+ | Dashboard styles |
| `assets/css/agent-profile-public.css` | 500+ | Public view styles |

## Version History

- **v1.0.0** (Current)
  - Initial implementation
  - Photo upload, profile editor
  - Public profile view
  - Review system with pending approval
  - Complete styling and responsiveness

---

**Last Updated**: 2024
**Status**: Production Ready
