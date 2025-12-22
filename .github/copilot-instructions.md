# Malisafi MLS - AI Agent Instructions

## Project Overview

WordPress plugin for Multi-Listing Service (MLS) real estate property management with Stripe subscription integration. Targeted for Kenya market with specific location taxonomy.

## Architecture

### Core Structure

- **Namespace**: `MalisafiMLS\` - All includes/ classes use this
- **Non-namespaced**: Admin classes (`admin/class-*.php`) use global namespace with `Malisafi_` prefix
- **Entry Point**: [malisafi-mls.php](malisafi-mls.php#L1) - Defines constants, autoloader, hooks
- **Orchestrator**: [includes/class-core.php](includes/class-core.php#L1) - Loads dependencies, initializes components via `Loader`

### Key Components

1. **Post Types**: `malisafi_property` and `malisafi_agent` CPTs with hierarchical taxonomies (type, status, location, features)
2. **Role System**: 6 custom roles (client, agent_basic, agent_premium, owner, developer, moderator) - see [ROLES.md](ROLES.md)
3. **Database**: Custom tables with `mf_` prefix (subscriptions, user_limits, properties, inquiries, favorites, analytics) - see [includes/class-database.php](includes/class-database.php#L1)
4. **Stripe Integration**: Subscription-based billing with webhook handling - requires Composer dependencies

## Critical Patterns

### 1. Class Initialization

```php
// Static init() pattern for admin/global classes
class Malisafi_Property_Submit {
    public static function init() {
        $instance = new self();
        add_action('hook_name', array($instance, 'method'));
    }
}

// Constructor pattern for namespaced classes
namespace MalisafiMLS;
class Core {
    public function __construct() {
        $this->load_dependencies();
    }
}
```

### 2. Hook Registration

Use `Loader` class for organized hook management in [includes/class-core.php](includes/class-core.php#L117):

```php
$this->loader->add_action('admin_init', $admin, 'register_settings');
$this->loader->add_filter('the_content', $public, 'filter_property_content');
```

### 3. Kenya Location System

**DO NOT** use generic country/state fields. Use Kenya-specific:

- `_malisafi_country` → Always "Kenya" (read-only)
- `_malisafi_county` → Required dropdown (47 counties)
- `_malisafi_neighbourhood` → Optional estate/area name
- `_malisafi_setting` → Required: urban/semi-rural/rural/isolated

Helper: `malisafi_get_kenya_counties()` in [includes/kenya-location-helpers.php](includes/kenya-location-helpers.php)

### 4. Design System

**ALWAYS** use CSS variables from [assets/css/variables.css](assets/css/variables.css):

```css
/* Correct */
background: var(--mls-accent);
color: var(--mls-text-primary);
border: 1px solid var(--mls-border-light);

/* NEVER hardcode colors */
background: #737d5d; /* ❌ Wrong */
```

Color palette: `--mls-dark`, `--mls-grey-green`, `--mls-light-grey` - see [DESIGN-SYSTEM.md](DESIGN-SYSTEM.md)

### 5. AJAX Pattern

All AJAX handlers use singleton + nonce verification:

```php
class Property_Filters_Ajax {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_ajax_action_name', array($this, 'handler'));
        add_action('wp_ajax_nopriv_action_name', array($this, 'handler'));
    }
}
```

Enqueue localized script data via `wp_enqueue_scripts` hook with `malisafiAjax` object.

## Development Workflows

### Setup & Dependencies

```powershell
# Install Stripe dependency (REQUIRED for subscriptions)
cd c:\xampp\htdocs\wordpress\wp-content\plugins\malisafi
composer install

# Or use provided script
.\install-stripe.ps1
```

**Important**: Plugin gracefully degrades if Stripe lib missing - shows admin notice, doesn't break.

### Database Changes

After modifying [includes/class-database.php](includes/class-database.php):

1. Update `malisafi_mls_db_version` in activator
2. Run `flush_rewrite_rules()` if CPT/taxonomy changed
3. Test with [includes/database-upgrade.php](includes/database-upgrade.php) handler

### Testing Shortcuts

- **Property submission**: Use shortcode `[malisafi_property_submit]` on frontend
- **Filters system**: Use `[malisafi_properties_modern]` for AJAX grid/sidebar layout
- **Test Stripe**: Card `4242 4242 4242 4242`, any future date, any CVC

### PHP Configuration

Plugin auto-adjusts in [malisafi-mls.php](malisafi-mls.php#L24):

- `max_execution_time` → 180s (for Stripe operations)
- `memory_limit` → 256M
- Check before heavy operations to avoid timeouts

## Integration Points

### Stripe Subscription Flow

1. User clicks plan on `[malisafi_pricing]` page
2. AJAX `malisafi_create_checkout` creates Stripe Checkout session
3. Redirect to Stripe, payment processed
4. Webhook `/wp-json/malisafi/v1/stripe-webhook` receives event
5. `Malisafi_Stripe::handle_checkout_completed()` updates:
   - `wp_mf_subscriptions` table
   - User role changed via `wp_update_user()`
   - User limits set in `wp_mf_user_limits`

See [STRIPE_SETUP_GUIDE.md](STRIPE_SETUP_GUIDE.md) for API keys, webhook setup.

### Page Management System

`Page_Manager` class auto-creates 28 pages on activation with pre-configured shortcodes. **Never hardcode page IDs** - use:

```php
$page_id = Page_Manager::get_page_id('property-search');
```

Pages stored in `malisafi_mls_pages` option as array. See [PAGES-SYSTEM-GUIDE.md](PAGES-SYSTEM-GUIDE.md).

### Property Moderation Workflow

- Agent Basic: `post_status` → 'pending' (requires approval)
- Agent Premium: Direct 'publish' capability
- Moderators: See pending queue at `/wp-admin/admin.php?page=malisafi-property-moderation`

Check capabilities: `current_user_can('publish_malisafi_properties')`

## Common Tasks

### Adding a Shortcode

1. Add to [includes/class-shortcodes.php](includes/class-shortcodes.php) or `class-dashboard-shortcodes.php`
2. Register in class constructor: `add_shortcode('name', array($this, 'method'))`
3. Return buffered output, never echo directly
4. Enqueue assets conditionally when shortcode detected

### Creating Custom Meta Field

1. Add metabox in [includes/class-post-types.php](includes/class-post-types.php#L230) `add_meta_boxes` hook
2. Save in `save_meta_box()` with nonce verification
3. Sanitize: `sanitize_text_field()` for text, `floatval()` for numbers
4. Prefix with `_malisafi_` for custom fields

### Adding Admin Dashboard Widget

In [admin/class-dashboard-widgets.php](admin/class-dashboard-widgets.php):

```php
public static function init() {
    add_action('wp_dashboard_setup', array(__CLASS__, 'register_widgets'));
}

public static function register_widgets() {
    wp_add_dashboard_widget('id', 'Title', array(__CLASS__, 'render_widget'));
}
```

## File Organization Rules

- **Templates**: Use `templates/` for public, `admin/templates/` for backend
- **Assets**: CSS/JS in `assets/css/` and `assets/js/` with `.min` versions
- **Documentation**: All-caps MD files in root (e.g., `FILTERS-README.md`, `AGENT-SYSTEM-GUIDE.md`)
- **SQL**: Migration scripts in `sql/` directory

## Debugging

- **WordPress Debug**: Already configured in plugin, check `wp-content/debug.log`
- **Stripe Events**: View in Stripe Dashboard → Developers → Webhooks → Events
- **AJAX**: Check browser Network tab for `admin-ajax.php` calls with `action` parameter
- **Verification Script**: Run `php verify-integration.php` from plugin root

## Don't Do This

❌ Skip nonce verification in form submissions  
❌ Use `$_POST` directly without sanitization  
❌ Hardcode colors instead of CSS variables  
❌ Mix namespaced and non-namespaced classes in same file  
❌ Echo in shortcode callbacks - always return  
❌ Modify core WP tables - use custom `mf_` tables  
❌ Skip capability checks: `current_user_can('capability')`  
❌ Use generic "Location" field - must use Kenya counties

## Key Files Reference

- Constants & Autoloader: [malisafi-mls.php](malisafi-mls.php)
- Core Orchestrator: [includes/class-core.php](includes/class-core.php)
- Database Schema: [includes/class-database.php](includes/class-database.php)
- Post Types & Taxonomies: [includes/class-post-types.php](includes/class-post-types.php)
- Role Management: [includes/class-role-manager.php](includes/class-role-manager.php)
- Stripe Integration: [includes/class-stripe.php](includes/class-stripe.php)
- AJAX Filters: [includes/class-property-filters-ajax.php](includes/class-property-filters-ajax.php)
- Modern Filter UI: [templates/properties-filters.php](templates/properties-filters.php)
- Page Auto-creation: [includes/class-page-manager.php](includes/class-page-manager.php)
