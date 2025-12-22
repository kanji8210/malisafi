# Login Redirect & Dashboard Link Guide

## Overview

The plugin now automatically redirects users to their appropriate Malisafi dashboard after login and adds a quick access link to the WordPress admin bar.

## Features

### 1. Automatic Login Redirect

After successful login, users are automatically redirected to their role-specific dashboard instead of the WordPress admin:

| User Role | Redirect Destination |
|-----------|---------------------|
| `malisafi_agent_basic` | Agent Dashboard (`/agent-dashboard`) |
| `malisafi_agent_premium` | Agent Dashboard (`/agent-dashboard`) |
| `malisafi_owner` | Owner Dashboard (`/owner-dashboard`) |
| `malisafi_developer` | Developer Dashboard (`/developer-dashboard`) |
| `malisafi_client` | Client Dashboard (`/client-dashboard`) |
| `administrator` | WordPress Admin (`/wp-admin`) |

**Implementation**: [`includes/class-login-customizer.php`](includes/class-login-customizer.php#L335)

### 2. Admin Bar Dashboard Link

When logged in, users see a "Dashboard" link in the WordPress admin bar with a home icon for quick access to their Malisafi dashboard.

**Link Text by Role**:
- Agent: "Agent Dashboard"
- Owner: "Owner Dashboard"  
- Developer: "Developer Dashboard"
- Client: "My Dashboard"

**Implementation**: [`includes/class-login-customizer.php`](includes/class-login-customizer.php#L368)

## Technical Details

### Login Redirect Method

```php
public static function redirect_to_dashboard($redirect_to, $request, $user) {
    // Checks user roles and maps to appropriate dashboard
    // Uses Page_Manager::get_page_url() to get dashboard URLs
    // Falls back to default $redirect_to if no Malisafi role found
}
```

**Filter Hook**: `login_redirect` (priority 10, 3 arguments)

### Admin Bar Link Method

```php
public static function add_dashboard_link($wp_admin_bar) {
    // Detects user role
    // Gets dashboard URL via Page_Manager::get_page_url()
    // Adds node to admin bar with dashicons-admin-home icon
}
```

**Action Hook**: `admin_bar_menu` (priority 999 - loads last)

## User Experience Flow

### Registration → Login Flow

1. User registers via `[malisafi_registration]` form
2. Account created with role (e.g., `malisafi_agent_basic`)
3. User logs in at `/wp-login.php`
4. **Automatic redirect** to `/agent-dashboard`
5. Admin bar shows **"Agent Dashboard"** link with home icon

### Direct Login

1. User visits `/wp-login.php`
2. Enters credentials
3. **Automatic redirect** based on role
4. Dashboard link appears in admin bar

## Testing Checklist

- [ ] **Agent Registration**: Register → Approve → Login → Redirects to `/agent-dashboard`
- [ ] **Owner Login**: Login as owner → Redirects to `/owner-dashboard`
- [ ] **Client Login**: Login as client → Redirects to `/client-dashboard`
- [ ] **Admin Login**: Login as admin → Redirects to `/wp-admin` (default)
- [ ] **Admin Bar Link**: Verify link shows correct text and URL for each role
- [ ] **Click Admin Bar Link**: Verify it navigates to correct dashboard
- [ ] **No Malisafi Role**: User with only "subscriber" role → Uses default WordPress redirect

## Edge Cases Handled

1. **User without roles**: Returns default `$redirect_to` URL
2. **Missing dashboard page**: `Page_Manager::get_page_url()` returns empty → uses default redirect
3. **Multiple roles**: Checks in priority order (agent > owner > developer > client > admin)
4. **Administrator role**: Always redirects to `/wp-admin` regardless of other roles

## Configuration

No configuration needed - works automatically based on:

1. **User roles** set during registration
2. **Dashboard pages** created by `Page_Manager` (see [PAGES-SYSTEM-GUIDE.md](PAGES-SYSTEM-GUIDE.md))

## Customization

### Change Redirect Priority

Edit [`includes/class-login-customizer.php`](includes/class-login-customizer.php#L335):

```php
// Check roles in different order
if (in_array('malisafi_owner', $user->roles)) {
    $dashboard_url = Page_Manager::get_page_url('owner_dashboard');
} elseif (in_array('malisafi_agent_basic', $user->roles)) {
    // Agent checked second instead of first
```

### Customize Admin Bar Link Icon

Edit [`includes/class-login-customizer.php`](includes/class-login-customizer.php#L400):

```php
'title' => '<span class="ab-icon dashicons dashicons-businessman"></span>' . $dashboard_title,
// Change to any Dashicons icon: https://developer.wordpress.org/resource/dashicons/
```

### Add Submenu Items

```php
// In add_dashboard_link() method, after add_node():
$wp_admin_bar->add_node([
    'parent' => 'malisafi-dashboard',
    'id'     => 'malisafi-properties',
    'title'  => __('My Properties', 'malisafi-mls'),
    'href'   => Page_Manager::get_page_url('my_properties'),
]);
```

## Integration with Other Features

- **Registration System**: Works seamlessly with multi-step registration ([REGISTRATION-SYSTEM-GUIDE.md](REGISTRATION-SYSTEM-GUIDE.md))
- **Page Manager**: Uses `Page_Manager::get_page_url()` to get dashboard URLs ([PAGES-SYSTEM-GUIDE.md](PAGES-SYSTEM-GUIDE.md))
- **Role System**: Supports all 6 custom roles ([ROLES.md](ROLES.md))
- **Login Customization**: Part of existing `Login_Customizer` class with Malisafi branding

## Troubleshooting

### Issue: User redirects to wp-admin instead of dashboard

**Solution**: Check if dashboard page exists:
```php
$url = Page_Manager::get_page_url('agent_dashboard');
var_dump($url); // Should return URL, not empty string
```

### Issue: Admin bar link not showing

**Solution**: Verify user has Malisafi role:
```php
$user = wp_get_current_user();
print_r($user->roles); // Should include malisafi_* role
```

### Issue: Wrong dashboard for user role

**Solution**: Check role priority in `redirect_to_dashboard()` - roles are checked in order

## Files Modified

- [`includes/class-login-customizer.php`](includes/class-login-customizer.php) - Added 2 new methods + 2 hook registrations

## Related Documentation

- [REGISTRATION-SYSTEM-GUIDE.md](REGISTRATION-SYSTEM-GUIDE.md) - User registration flow
- [PAGES-SYSTEM-GUIDE.md](PAGES-SYSTEM-GUIDE.md) - Dashboard page creation
- [ROLES.md](ROLES.md) - User role definitions
- [DASHBOARD-SEPARATION.md](DASHBOARD-SEPARATION.md) - Dashboard structure

## Summary

✅ **Login redirect implemented** - Users auto-redirect to Malisafi dashboards  
✅ **Admin bar link added** - Quick access to dashboard when logged in  
✅ **Role-based routing** - Each role gets correct dashboard  
✅ **No configuration needed** - Works automatically  
✅ **Edge cases handled** - Safe fallback to default redirect
