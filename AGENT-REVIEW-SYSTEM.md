# Agent Review System - Complete Guide

## Overview

Complete agent rating and review system for Malisafi MLS plugin. Allows clients to rate agents, write reviews, and vote on review helpfulness. Includes admin moderation panel.

## Features Implemented

### ✅ Public Features

1. **Star Rating System** (1-5 stars)
   - Interactive star selection
   - Visual hover effects
   - Responsive design

2. **Review Submission**
   - Rating (required, 1-5 stars)
   - Review title (optional, 100 chars max)
   - Review text (required, 10-500 chars)
   - Character counter
   - Verified client badge support

3. **Review Display**
   - Agent average rating
   - Total review count
   - Individual reviews with:
     - Reviewer name and avatar
     - Star rating
     - Review title
     - Review text
     - Time posted
     - Verified client badge
     - Helpful votes

4. **Helpful Voting**
   - "Yes" / "No" buttons
   - Vote counts displayed
   - One vote per user per review
   - Prevents duplicate voting

5. **Agent Responses**
   - Agents can respond to reviews
   - Response timestamp
   - Distinguished styling

### ✅ Admin Features

1. **Moderation Panel**
   - Location: WP Admin → Malisafi MLS → Agent Ratings
   - Three tabs: Pending, Approved, Rejected
   - Counts for each status
   - Bulk actions

2. **Rating Management**
   - Approve/Reject ratings
   - Delete ratings permanently
   - View full review details
   - See helpful vote counts
   - Verified client indicator

3. **Automatic Updates**
   - Agent average rating recalculated on approve/reject
   - Meta fields updated: `_malisafi_agent_rating`, `_malisafi_agent_rating_count`
   - Cache cleared automatically

## File Structure

```
malisafi/
├── templates/
│   └── agent-profile-public.php          # Review display & modal
├── assets/
│   ├── css/
│   │   └── agent-profile-public.css      # Review styles
│   └── js/
│       └── agent-actions.js              # AJAX handlers
├── includes/
│   └── class-agent-profile-ajax.php      # Backend AJAX
└── admin/
    └── class-agent-ratings-admin.php     # Moderation panel
```

## Database Schema

**Table:** `wp_mf_agent_ratings`

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| agent_id | BIGINT | Agent post ID |
| user_id | BIGINT | Reviewer user ID |
| rating | TINYINT | 1-5 stars |
| review_title | VARCHAR(255) | Optional title |
| review_text | TEXT | Review content |
| property_id | BIGINT | Associated property (optional) |
| verified_client | BOOLEAN | Has worked with agent |
| helpful_count | INT | "Yes" votes |
| not_helpful_count | INT | "No" votes |
| agent_response | TEXT | Agent's reply |
| agent_responded_at | DATETIME | Response timestamp |
| status | ENUM | pending/approved/rejected |
| created_at | TIMESTAMP | Submission time |
| updated_at | TIMESTAMP | Last modified |

**Unique Key:** `(user_id, agent_id)` - One review per user per agent

## Usage

### Display Agent Profile

```php
// In template or shortcode
echo do_shortcode('[malisafi_agent_profile_view id="' . $agent_id . '"]');
```

### Get Agent Rating

```php
$avg_rating = get_post_meta($agent_id, '_malisafi_agent_rating', true);
$rating_count = get_post_meta($agent_id, '_malisafi_agent_rating_count', true);

echo "★ " . round($avg_rating, 1) . " ({$rating_count} reviews)";
```

### Check if User Can Rate

```php
$current_user = wp_get_current_user();
$can_rate = false;

if ($current_user->ID) {
    $linked_user_id = get_post_meta($agent_id, '_agent_user_id', true);
    
    // Cannot rate yourself
    if ($current_user->ID != $linked_user_id) {
        // Agents cannot rate other agents
        $is_agent = in_array('malisafi_agent_basic', $current_user->roles) || 
                   in_array('malisafi_agent_premium', $current_user->roles);
        
        if (!$is_agent) {
            $can_rate = true;
        }
    }
}
```

## AJAX Endpoints

### Submit Review

**Action:** `submit_agent_review` or `malisafi_rate_agent`  
**Method:** POST  
**Nonce:** `agent_actions_nonce`

**Parameters:**
- `agent_id` (int, required)
- `rating` (int, 1-5, required)
- `review_title` (string, optional, max 100 chars)
- `review_text` (string, required, min 10 chars)
- `property_id` (int, optional)

**Response:**
```json
{
  "success": true,
  "data": {
    "message": "Thank you! Your review has been submitted successfully.",
    "average_rating": 4.5,
    "total_ratings": 12
  }
}
```

### Vote on Review

**Action:** `malisafi_helpful_review`  
**Method:** POST  
**Nonce:** `agent_actions_nonce`

**Parameters:**
- `review_id` (int, required)
- `helpful` (bool, required) - true = Yes, false = No

**Response:**
```json
{
  "success": true,
  "data": {
    "message": "Thank you for your feedback!",
    "helpful_count": 5,
    "not_helpful_count": 1
  }
}
```

### Admin: Approve Rating

**Action:** `approve_agent_rating`  
**Method:** POST  
**Nonce:** `moderate_rating`  
**Capability:** `moderate_comments`

**Parameters:**
- `rating_id` (int, required)

### Admin: Reject Rating

**Action:** `reject_agent_rating`  
**Method:** POST  
**Nonce:** `moderate_rating`  
**Capability:** `moderate_comments`

**Parameters:**
- `rating_id` (int, required)

### Admin: Delete Rating

**Action:** `delete_agent_rating`  
**Method:** POST  
**Nonce:** `moderate_rating`  
**Capability:** `delete_users`

**Parameters:**
- `rating_id` (int, required)

## JavaScript API

**Open Review Modal:**
```javascript
$('#writeReviewBtn').click();
```

**Submit Review Programmatically:**
```javascript
$('.agent-rating-form').trigger('submit');
```

**Show Notification:**
```javascript
// Success message
$('body').append(`
  <div class="malisafi-message malisafi-message-success">
    <span class="message-icon dashicons dashicons-yes-alt"></span>
    <span class="message-text">Success!</span>
    <button class="message-close">&times;</button>
  </div>
`);
```

## Validation Rules

### Client-Side (JavaScript)

- Rating: Must select 1-5 stars
- Review text: Minimum 10 characters, maximum 500
- Character counter shows real-time count

### Server-Side (PHP)

- User must be logged in
- Cannot rate yourself
- Agents cannot rate other agents
- One review per user per agent
- Rating must be 1-5
- Review text minimum 10 characters

## Permission System

| User Role | Can Rate | Can Vote | Can Moderate |
|-----------|----------|----------|--------------|
| Client | ✅ | ✅ | ❌ |
| Agent Basic | ❌ | ✅ | ❌ |
| Agent Premium | ❌ | ✅ | ❌ |
| Owner | ✅ | ✅ | ❌ |
| Developer | ✅ | ✅ | ❌ |
| Moderator | ✅ | ✅ | ✅ |
| Administrator | ✅ | ✅ | ✅ |

## Styling Classes

### Modal
- `.malisafi-modal` - Outer container
- `.modal-overlay` - Dark background
- `.modal-content` - White card
- `.modal-close` - X button

### Star Rating
- `.star-rating-input` - Input container
- `.star` - Individual star
- `.star.active` - Selected star
- `.star.filled` - Display only (reviews)

### Review Display
- `.review-item` - Individual review
- `.review-title` - Review heading
- `.review-stars` - Star display
- `.verified-badge` - Verified client indicator
- `.review-helpful` - Vote buttons container
- `.helpful-btn` - Vote button
- `.agent-response` - Agent reply section

### Messages
- `.malisafi-message` - Notification container
- `.malisafi-message-success` - Green success
- `.malisafi-message-error` - Red error

## Customization

### Change Auto-Approval

In `class-agent-profile-ajax.php` line ~247:

```php
// Change 'approved' to 'pending' to require moderation
'status' => 'pending',
```

### Adjust Character Limits

Template (line ~353):
```html
<textarea name="review_text" maxlength="500"></textarea>
```

JavaScript (line ~78):
```javascript
if (!formData.comment || formData.comment.trim().length < 10) {
```

PHP validation (line ~219):
```php
if (strlen($review_text) < 10) {
```

### Modify Star Colors

CSS (line ~548):
```css
.star-rating-input .star:hover,
.star-rating-input .star.active {
    color: #ffa500; /* Change to your brand color */
}
```

## Troubleshooting

### Reviews Not Submitting

1. Check browser console for JavaScript errors
2. Verify nonce in AJAX request
3. Check user is logged in
4. Ensure user is not an agent (if rating another agent)
5. Check for duplicate reviews

### Modal Not Opening

1. Verify jQuery is loaded
2. Check `agent-actions.js` is enqueued
3. Ensure `#writeReviewBtn` element exists
4. Check for conflicting JavaScript

### Stars Not Clickable

1. Verify `.star-rating-input` class exists
2. Check CSS is loaded
3. Test with browser dev tools
4. Clear cache

### Helpful Votes Not Working

1. Check user is logged in
2. Verify nonce
3. Check user hasn't already voted (stored in user meta)
4. Inspect network tab for AJAX errors

## Performance

- Reviews cached per agent (if Cache_Manager active)
- Database indexes on `agent_id`, `rating`, `status`
- Unique constraint prevents duplicate submissions
- Agent meta updated on approve/reject (no need to query all reviews)

## Security

- ✅ Nonce verification on all AJAX requests
- ✅ Capability checks for admin actions
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS protection (sanitization + escaping)
- ✅ CSRF protection (WordPress nonces)
- ✅ Unique key prevents duplicate reviews
- ✅ User meta prevents duplicate votes

## Future Enhancements

- [ ] Agent response functionality in admin
- [ ] Email notifications for new reviews
- [ ] Review reporting system
- [ ] Review editing (within time limit)
- [ ] Review photos/attachments
- [ ] Review sorting (helpful, recent, rating)
- [ ] Review pagination
- [ ] Review filtering by rating
- [ ] Export reviews to CSV

## Support

For issues or questions:
1. Check browser console
2. Enable WordPress debug mode
3. Check `wp-content/debug.log`
4. Verify database table exists
5. Test with default theme
6. Deactivate other plugins

## Credits

Developed for Malisafi MLS Plugin  
Version: 1.0.0  
Last Updated: January 2026
