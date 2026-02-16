# User Plan Management System

**Version:** 1.0  
**Last Updated:** February 16, 2026

## Overview

The Malisafi MLS plugin now includes a comprehensive plan management system that creates a clear link between users and their subscription plans. This system allows admins to assign plans to users manually and notifies users without plans to subscribe.

## Key Features

### 1. **Clear User-Plan Linking**
- Every user has a subscription status visible in admin
- Subscription data stored in `wp_mf_subscriptions` table
- User limits stored in `wp_mf_user_limits` table
- Real-time plan status display

### 2. **Admin Plan Assignment**
- Admins can assign/update plans directly from user edit page
- No payment processing required for manual assignments
- Automatic user role updates based on plan
- Configurable subscription duration

### 3. **User Plan Suggestions**
- Automatic banner notification for users without plans
- Dismissible banner (remembers for 7 days)
- Plan status widget via shortcode
- Direct link to pricing page

## Admin Usage

### Assigning a Plan to a User

1. Navigate to **Malisafi → Users**
2. Click **Edit** on any user
3. Scroll to **Subscription Plan Management** section
4. Click **Assign Plan** button
5. Select plan type and duration
6. Click **Assign Plan**

The system will:
- Create/update subscription record
- Update user role to match plan
- Set user limits (max properties, featured listings, etc.)
- Not process any payment (manual assignment)

### Removing a Plan

1. Go to user edit page
2. In **Subscription Plan Management** section
3. Click **Cancel Subscription**
4. Confirm the action

The subscription status will change to "canceled" but the record remains for audit purposes.

### Available Plan Types

| Plan Type | Role | Features |
|-----------|------|----------|
| `agent_basic` | Agent Basic | Limited listings, basic features |
| `agent_premium` | Agent Premium | Unlimited listings, analytics, featured properties |
| `owner_basic` | Owner | Property owner listings |
| `developer` | Developer | Development project listings |

## Frontend Usage

### Shortcode: Plan Status Widget

Display user's current plan or suggest getting one:

```php
[malisafi_plan_status]
```

**Parameters:**
- `show_upgrade` - Show upgrade button (default: yes)
- `compact` - Use compact display mode (default: no)

**Examples:**

```php
// Full plan status display
[malisafi_plan_status]

// Compact mode
[malisafi_plan_status compact="yes"]

// Without upgrade button
[malisafi_plan_status show_upgrade="no"]
```

### Automatic Plan Notification Banner

For logged-in users without an active plan, a banner automatically appears at the top of the page:
- Shows warning about no active plan
- Provides direct link to pricing page
- Can be dismissed (remembers for 7 days)
- Responsive design

## Developer Usage

### Check if User Has Plan

```php
use MalisafiMLS\Plan_Manager;

$user_id = get_current_user_id();
$has_plan = Plan_Manager::user_has_plan($user_id);

if ($has_plan) {
    // User has active plan
} else {
    // Suggest getting a plan
}
```

### Get User Plan Details

```php
$plan_details = Plan_Manager::get_user_plan_details($user_id);

if ($plan_details) {
    echo 'Plan: ' . $plan_details['name'];
    echo 'Status: ' . $plan_details['subscription_status'];
    echo 'Expires: ' . $plan_details['current_period_end'];
}
```

### Programmatically Assign Plan

```php
$result = Plan_Manager::assign_plan($user_id, 'agent_premium', 12);

if (is_wp_error($result)) {
    echo 'Error: ' . $result->get_error_message();
} else {
    echo 'Plan assigned successfully!';
}
```

### Hooks

**After Plan Assigned:**
```php
add_action('malisafi_plan_assigned', function($user_id, $plan_type, $duration) {
    // Send notification email
    // Log the assignment
    // Custom processing
}, 10, 3);
```

**After Plan Removed:**
```php
add_action('malisafi_plan_removed', function($user_id, $plan_type) {
    // Handle plan removal
    // Send notification
}, 10, 2);
```

## Database Schema

### wp_mf_subscriptions

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| user_id | BIGINT | WordPress user ID |
| plan_type | ENUM | agent_basic, agent_premium, owner_basic, developer |
| status | ENUM | active, canceled, expired, pending |
| stripe_subscription_id | VARCHAR | Stripe subscription ID (if paid) |
| current_period_start | DATETIME | Subscription start date |
| current_period_end | DATETIME | Subscription end date |
| created_at | TIMESTAMP | Record creation date |
| updated_at | TIMESTAMP | Last update date |

### wp_mf_user_limits

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Primary key |
| user_id | BIGINT | WordPress user ID |
| max_listings | INT | Maximum properties allowed |
| used_listings | INT | Current property count |
| featured_listings | INT | Allowed featured properties |
| can_boost | BOOLEAN | Can boost properties |
| analytics_access | BOOLEAN | Has analytics access |
| updated_at | TIMESTAMP | Last update date |

## UI Components

### Plan Status Widget (Frontend)

Located in: `templates/plan-status.php`

Displays:
- Active plan name and status
- Subscription price and billing period
- Renewal date
- Plan features
- Upgrade button (optional)

For users without plans:
- Warning message
- Call-to-action button
- Link to pricing page

### Admin Plan Management Section

Located in: `admin/templates/users-management.php`

Features:
- Current plan display with status badge
- Plan assignment form (hidden by default)
- Plan type selector
- Duration input (months)
- Cancel subscription button
- Important notes about manual assignment

### Banner Notification

Styled components:
- Fixed position banner at top
- Warning icon and message
- View Plans button
- Dismiss button (X)
- Responsive layout

## AJAX Endpoints

### malisafi_assign_plan
- **Permission:** Admin only
- **Nonce:** malisafi_admin_plan_nonce
- **Parameters:** user_id, plan_type, duration
- **Response:** Success/error message

### malisafi_remove_plan
- **Permission:** Admin only
- **Nonce:** malisafi_admin_plan_nonce
- **Parameters:** user_id
- **Response:** Success/error message

### malisafi_check_user_plan
- **Permission:** Logged-in users
- **Parameters:** None (uses current user)
- **Response:** has_plan (boolean), plan details

## Styling

### CSS Files

1. **plan-status.css** - Plan status widget styles
2. **plan-banner.css** - Banner notification styles

Both use CSS variables from `variables.css`:
- `--mls-accent` - Primary accent color
- `--mls-text-primary` - Primary text color
- `--mls-text-secondary` - Secondary text color
- `--mls-border-light` - Light border color

### JavaScript Files

1. **plan-status.js** - Widget animations and interactions
2. **plan-banner.js** - Banner display logic and dismissal
3. **plan-manager-admin.js** - Admin AJAX handlers

## Integration with Existing Systems

### Stripe Integration

The plan manager works alongside the Stripe integration:
- Manual assignments don't process payments
- Stripe subscriptions automatically create plan records
- Webhook updates subscription status
- Both systems update the same database tables

### User Roles

Plan assignment automatically updates user roles:
- `agent_basic` → `malisafi_agent_basic`
- `agent_premium` → `malisafi_agent_premium`
- `owner_basic` → `malisafi_owner`
- `developer` → `malisafi_developer`

### Property Limits

User limits are automatically set based on plan:
- Max listings from plan configuration
- Featured listings allowance
- Analytics access flag
- Boost property permission

## Best Practices

1. **Manual Assignments**
   - Use for testing or special cases
   - Don't use as replacement for payment system
   - Document reason for manual assignment

2. **Plan Changes**
   - Changing plan updates limits immediately
   - Properties aren't deleted if limit reduced
   - User can't add more until under limit

3. **Banner Dismissal**
   - Cookie expires after 7 days
   - Clears when user gets a plan
   - Shows again if plan expires

4. **Admin Notifications**
   - Consider adding email notifications
   - Log plan assignments for audit trail
   - Monitor manual plan assignments

## Troubleshooting

### Banner Not Showing

Check:
1. User is logged in
2. User has no active plan
3. Banner wasn't recently dismissed (check cookies)
4. JavaScript loaded without errors

### Plan Assignment Fails

Check:
1. User ID is valid
2. Plan type is valid enum value
3. Database tables exist
4. Admin has proper permissions

### Plan Status Not Updating

Check:
1. Cache cleared after plan assignment
2. Database record updated successfully
3. User role updated correctly
4. Limits table synchronized

## Support

For issues or questions:
1. Check error logs in `wp-content/debug.log`
2. Verify database table structure
3. Review AJAX responses in browser console
4. Check user capabilities and permissions

## Related Documentation

- [STRIPE_SETUP_GUIDE.md](STRIPE_SETUP_GUIDE.md) - Payment integration
- [ROLES.md](ROLES.md) - User roles and capabilities
- [DESIGN-SYSTEM.md](DESIGN-SYSTEM.md) - CSS variables and styling
- [ADMIN-SYSTEM.md](ADMIN-SYSTEM.md) - Admin functionality

## Changelog

### Version 1.0 (February 16, 2026)
- Initial release
- Admin plan assignment
- User plan status widget
- Banner notification system
- AJAX handlers for plan management
- Database integration
- Documentation
