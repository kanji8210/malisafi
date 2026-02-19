<?php
/**
 * Unified Plans & Subscriptions Management
 * All-in-one interface for Stripe configuration, plan management, and subscription assignments
 *
 * @package MalisafiMLS
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check permissions
if (!current_user_can('manage_malisafi_settings')) {
    wp_die(__('You do not have permission to access this page.', 'malisafi-mls'));
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['malisafi_save_stripe_settings']) && check_admin_referer('malisafi_stripe_settings')) {
        // Save Stripe settings
        update_option('malisafi_stripe_mode', sanitize_text_field($_POST['stripe_mode']));
        update_option('malisafi_stripe_test_publishable_key', sanitize_text_field($_POST['test_publishable_key']));
        update_option('malisafi_stripe_test_secret_key', sanitize_text_field($_POST['test_secret_key']));
        update_option('malisafi_stripe_live_publishable_key', sanitize_text_field($_POST['live_publishable_key']));
        update_option('malisafi_stripe_live_secret_key', sanitize_text_field($_POST['live_secret_key']));
        update_option('malisafi_stripe_webhook_secret', sanitize_text_field($_POST['webhook_secret']));
        
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Stripe settings saved successfully!', 'malisafi-mls') . '</p></div>';
    } elseif (isset($_POST['malisafi_plans_action']) && check_admin_referer('malisafi_plans_save', '_malisafi_plans_nonce')) {
        // Save plans
        $action = sanitize_text_field($_POST['malisafi_plans_action']);
        $plans = get_option('malisafi_mls_plans', array());
        
        if ($action === 'save_all' && isset($_POST['plans'])) {
            foreach ($_POST['plans'] as $key => $plan_data) {
                $plans[sanitize_text_field($key)] = array(
                    'name' => sanitize_text_field($plan_data['name']),
                    'price' => floatval($plan_data['price']),
                    'currency' => sanitize_text_field($plan_data['currency']),
                    'interval' => sanitize_text_field($plan_data['interval']),
                    'features' => sanitize_text_field($plan_data['features']),
                    'stripe_price_id' => sanitize_text_field($plan_data['stripe_price_id']),
                    'max_listings' => intval($plan_data['max_listings']),
                    'featured_listings' => intval($plan_data['featured_listings']),
                    'is_active' => isset($plan_data['is_active']) ? 1 : 0,
                );
            }
            update_option('malisafi_mls_plans', $plans);
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Plans saved successfully!', 'malisafi-mls') . '</p></div>';
        } elseif ($action === 'initialize_role_based') {
            // Initialize default plans
            $plans = array(
                'client' => array(
                    'name' => 'Client (Free)',
                    'price' => 0,
                    'currency' => 'KES',
                    'interval' => 'month',
                    'features' => 'Browse properties, Save favorites, Contact agents',
                    'stripe_price_id' => '',
                    'max_listings' => 0,
                    'featured_listings' => 0,
                    'is_active' => 1,
                ),
                'agent_basic' => array(
                    'name' => 'Agent Basic',
                    'price' => 2999,
                    'currency' => 'KES',
                    'interval' => 'month',
                    'features' => 'List up to 10 properties, Standard support, Email notifications',
                    'stripe_price_id' => '',
                    'max_listings' => 10,
                    'featured_listings' => 1,
                    'is_active' => 1,
                ),
                'agent_premium' => array(
                    'name' => 'Agent Premium',
                    'price' => 9999,
                    'currency' => 'KES',
                    'interval' => 'month',
                    'features' => 'Unlimited listings, Priority support, Featured properties, Analytics dashboard',
                    'stripe_price_id' => '',
                    'max_listings' => -1,
                    'featured_listings' => 5,
                    'is_active' => 1,
                ),
                'owner_basic' => array(
                    'name' => 'Property Owner',
                    'price' => 1999,
                    'currency' => 'KES',
                    'interval' => 'month',
                    'features' => 'List up to 3 properties, Basic support',
                    'stripe_price_id' => '',
                    'max_listings' => 3,
                    'featured_listings' => 0,
                    'is_active' => 1,
                ),
                'developer' => array(
                    'name' => 'Developer',
                    'price' => 19999,
                    'currency' => 'KES',
                    'interval' => 'month',
                    'features' => 'Unlimited projects, API access, Priority support, Custom branding',
                    'stripe_price_id' => '',
                    'max_listings' => -1,
                    'featured_listings' => 10,
                    'is_active' => 1,
                ),
            );
            update_option('malisafi_mls_plans', $plans);
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Default plans initialized successfully!', 'malisafi-mls') . '</p></div>';
        }
    } elseif (isset($_POST['malisafi_assign_subscription']) && check_admin_referer('malisafi_assign_subscription')) {
        // Manual subscription assignment
        global $wpdb;
        $user_id = intval($_POST['user_id']);
        $plan_type = sanitize_text_field($_POST['plan_type']);
        $end_date = sanitize_text_field($_POST['end_date']);
        
        $subscriptions_table = $wpdb->prefix . 'mf_subscriptions';
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$subscriptions_table} WHERE user_id = %d AND status = 'active'",
            $user_id
        ));
        
        if ($existing) {
            // Update existing
            $wpdb->update(
                $subscriptions_table,
                array(
                    'plan_type' => $plan_type,
                    'current_period_end' => $end_date,
                ),
                array('user_id' => $user_id, 'status' => 'active'),
                array('%s', '%s'),
                array('%d', '%s')
            );
        } else {
            // Insert new
            $wpdb->insert(
                $subscriptions_table,
                array(
                    'user_id' => $user_id,
                    'stripe_subscription_id' => 'manual_' . time(),
                    'stripe_customer_id' => '',
                    'plan_type' => $plan_type,
                    'status' => 'active',
                    'current_period_start' => current_time('mysql'),
                    'current_period_end' => $end_date,
                    'created_at' => current_time('mysql'),
                ),
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );
        }
        
        // Update user role
        $user = get_user_by('id', $user_id);
        if ($user) {
            $user->set_role($plan_type);
        }
        
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Subscription assigned successfully!', 'malisafi-mls') . '</p></div>';
    }
}

// Get current values
$stripe_mode = get_option('malisafi_stripe_mode', 'test');
$test_publishable = get_option('malisafi_stripe_test_publishable_key', '');
$test_secret = get_option('malisafi_stripe_test_secret_key', '');
$live_publishable = get_option('malisafi_stripe_live_publishable_key', '');
$live_secret = get_option('malisafi_stripe_live_secret_key', '');
$webhook_secret = get_option('malisafi_stripe_webhook_secret', '');

$plans = get_option('malisafi_mls_plans', array());
if (!is_array($plans)) {
    $plans = array();
}

$default_currency = get_option('malisafi_mls_currency', 'KES');

// Check if Stripe is configured
$is_stripe_configured = Malisafi_Stripe::is_configured();

// Get subscription statistics
global $wpdb;
$subscriptions_table = $wpdb->prefix . 'mf_subscriptions';
$total_active = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$subscriptions_table} WHERE status = %s", 'active'));
$total_active = $total_active !== null ? intval($total_active) : 0;

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';
?>

<div class="wrap">
    <h1><?php _e('Plans & Subscriptions Management', 'malisafi-mls'); ?></h1>
    <p class="description"><?php _e('Complete subscription management: Configure Stripe, manage plans, and assign subscriptions to users.', 'malisafi-mls'); ?></p>
    
    <!-- Stripe Configuration Status -->
    <div class="notice <?php echo $is_stripe_configured ? 'notice-success' : 'notice-warning'; ?>" style="margin: 20px 0;">
        <p>
            <span class="dashicons dashicons-<?php echo $is_stripe_configured ? 'yes-alt' : 'warning'; ?>" style="color: <?php echo $is_stripe_configured ? '#46b450' : '#f39c12'; ?>;"></span>
            <strong><?php _e('Stripe Status:', 'malisafi-mls'); ?></strong> 
            <?php if ($is_stripe_configured): ?>
                <?php _e('Configured and ready', 'malisafi-mls'); ?> (<?php echo $stripe_mode === 'live' ? __('Live Mode', 'malisafi-mls') : __('Test Mode', 'malisafi-mls'); ?>)
            <?php else: ?>
                <?php _e('Not configured - Enter your Stripe API keys below to enable subscriptions', 'malisafi-mls'); ?>
            <?php endif; ?>
        </p>
    </div>
    
    <!-- Tabs Navigation -->
    <nav class="nav-tab-wrapper wp-clearfix" style="margin-bottom: 20px;">
        <a href="<?php echo admin_url('admin.php?page=malisafi-plans-subscriptions&tab=overview'); ?>" 
           class="nav-tab <?php echo $current_tab === 'overview' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-dashboard"></span>
            <?php _e('Overview', 'malisafi-mls'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=malisafi-plans-subscriptions&tab=stripe'); ?>" 
           class="nav-tab <?php echo $current_tab === 'stripe' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-admin-network"></span>
            <?php _e('Stripe Settings', 'malisafi-mls'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=malisafi-plans-subscriptions&tab=plans'); ?>" 
           class="nav-tab <?php echo $current_tab === 'plans' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-cart"></span>
            <?php _e('Subscription Plans', 'malisafi-mls'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=malisafi-plans-subscriptions&tab=subscriptions'); ?>" 
           class="nav-tab <?php echo $current_tab === 'subscriptions' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-groups"></span>
            <?php _e('Active Subscriptions', 'malisafi-mls'); ?>
            <span class="count">(<?php echo $total_active; ?>)</span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=malisafi-plans-subscriptions&tab=assign'); ?>" 
           class="nav-tab <?php echo $current_tab === 'assign' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-admin-users"></span>
            <?php _e('Assign Subscription', 'malisafi-mls'); ?>
        </a>
    </nav>
    
    <?php if ($current_tab === 'overview'): ?>
        <!-- OVERVIEW TAB -->
        <div class="malisafi-settings-section">
            <h2><?php _e('Quick Overview', 'malisafi-mls'); ?></h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
                <div class="malisafi-stat-card" style="background: #fff; border: 1px solid #ddd; border-left: 4px solid #737d5d; padding: 20px; border-radius: 4px;">
                    <h3 style="margin-top: 0; color: #737d5d;"><?php _e('Stripe Status', 'malisafi-mls'); ?></h3>
                    <p style="font-size: 24px; font-weight: bold; color: <?php echo $is_stripe_configured ? '#46b450' : '#f39c12'; ?>;">
                        <?php echo $is_stripe_configured ? __('Configured', 'malisafi-mls') : __('Not Configured', 'malisafi-mls'); ?>
                    </p>
                    <p style="color: #666; margin: 0;">
                        <?php echo $is_stripe_configured ? 
                            ($stripe_mode === 'live' ? __('Live Mode Active', 'malisafi-mls') : __('Test Mode Active', 'malisafi-mls')) : 
                            __('Configure in Stripe Settings tab', 'malisafi-mls'); 
                        ?>
                    </p>
                </div>
                
                <div class="malisafi-stat-card" style="background: #fff; border: 1px solid #ddd; border-left: 4px solid #2271b1; padding: 20px; border-radius: 4px;">
                    <h3 style="margin-top: 0; color: #2271b1;"><?php _e('Available Plans', 'malisafi-mls'); ?></h3>
                    <p style="font-size: 24px; font-weight: bold; color: #2271b1;">
                        <?php echo count($plans); ?>
                    </p>
                    <p style="color: #666; margin: 0;">
                        <?php printf(__('%d active plans for users to choose from', 'malisafi-mls'), count(array_filter($plans, function($p) { return !empty($p['is_active']); }))); ?>
                    </p>
                </div>
                
                <div class="malisafi-stat-card" style="background: #fff; border: 1px solid #ddd; border-left: 4px solid #46b450; padding: 20px; border-radius: 4px;">
                    <h3 style="margin-top: 0; color: #46b450;"><?php _e('Active Subscriptions', 'malisafi-mls'); ?></h3>
                    <p style="font-size: 24px; font-weight: bold; color: #46b450;">
                        <?php echo $total_active; ?>
                    </p>
                    <p style="color: #666; margin: 0;">
                        <?php _e('Users with active paid subscriptions', 'malisafi-mls'); ?>
                    </p>
                </div>
                
                <div class="malisafi-stat-card" style="background: #fff; border: 1px solid #ddd; border-left: 4px solid #f39c12; padding: 20px; border-radius: 4px;">
                    <h3 style="margin-top: 0; color: #f39c12;"><?php _e('Public Pricing Page', 'malisafi-mls'); ?></h3>
                    <p style="font-size: 24px; font-weight: bold; color: #f39c12;">
                        <?php echo $is_stripe_configured ? '✓' : '✗'; ?>
                    </p>
                    <?php 
                    $pricing_page_id = \MalisafiMLS\Page_Manager::get_page_id('pricing');
                    if ($pricing_page_id): 
                    ?>
                        <a href="<?php echo get_permalink($pricing_page_id); ?>" target="_blank" class="button" 
                        <?php if (!$is_stripe_configured) echo 'disabled style="pointer-events: none; opacity: 0.5;"'; ?>>
                            <?php _e('View Pricing Page', 'malisafi-mls'); ?>
                        </a>
                    <?php else: ?>
                        <p style="color: #666; margin: 0;"><?php _e('Pricing page not found', 'malisafi-mls'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="notice notice-info" style="margin-top: 30px;">
                <h3><?php _e('Quick Start Guide', 'malisafi-mls'); ?></h3>
                <ol style="margin-left: 20px;">
                    <li><strong><?php _e('Configure Stripe:', 'malisafi-mls'); ?></strong> <?php _e('Go to the "Stripe Settings" tab and enter your API keys', 'malisafi-mls'); ?></li>
                    <li><strong><?php _e('Setup Plans:', 'malisafi-mls'); ?></strong> <?php _e('Visit the "Subscription Plans" tab to create or initialize role-based plans', 'malisafi-mls'); ?></li>
                    <li><strong><?php _e('Create Stripe Products:', 'malisafi-mls'); ?></strong> <?php _e('Create matching products in your Stripe Dashboard and copy the Price IDs', 'malisafi-mls'); ?></li>
                    <li><strong><?php _e('Users Subscribe:', 'malisafi-mls'); ?></strong> <?php _e('Users can now visit the pricing page and subscribe to plans', 'malisafi-mls'); ?></li>
                    <li><strong><?php _e('Manual Assignment:', 'malisafi-mls'); ?></strong> <?php _e('Use the "Assign Subscription" tab to manually assign plans to specific users', 'malisafi-mls'); ?></li>
                </ol>
            </div>
        </div>
        
    <?php elseif ($current_tab === 'stripe'): ?>
        <!-- STRIPE SETTINGS TAB -->
        <div class="malisafi-settings-section">
            <h2><?php _e('Stripe API Configuration', 'malisafi-mls'); ?></h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('malisafi_stripe_settings'); ?>
                <input type="hidden" name="malisafi_save_stripe_settings" value="1" />
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="stripe_mode"><?php _e('Stripe Mode', 'malisafi-mls'); ?></label>
                        </th>
                        <td>
                            <select name="stripe_mode" id="stripe_mode" class="regular-text" onchange="toggleStripeMode(this.value)">
                                <option value="test" <?php selected($stripe_mode, 'test'); ?>><?php _e('Test Mode', 'malisafi-mls'); ?></option>
                                <option value="live" <?php selected($stripe_mode, 'live'); ?>><?php _e('Live Mode', 'malisafi-mls'); ?></option>
                            </select>
                            <p class="description"><?php _e('Use Test Mode for development, Live Mode for production.', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <div id="test-keys-section" style="<?php echo $stripe_mode === 'test' ? '' : 'display:none;'; ?>">
                    <h3><?php _e('Test Mode Keys', 'malisafi-mls'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="test_publishable_key"><?php _e('Test Publishable Key', 'malisafi-mls'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="test_publishable_key" id="test_publishable_key" 
                                       value="<?php echo esc_attr($test_publishable); ?>" class="regular-text code" 
                                       placeholder="pk_test_..." />
                                <p class="description"><?php _e('Starts with pk_test_', 'malisafi-mls'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="test_secret_key"><?php _e('Test Secret Key', 'malisafi-mls'); ?></label>
                            </th>
                            <td>
                                <input type="password" name="test_secret_key" id="test_secret_key" 
                                       value="<?php echo esc_attr($test_secret); ?>" class="regular-text code" 
                                       placeholder="sk_test_..." />
                                <p class="description"><?php _e('Starts with sk_test_ (keep this secret!)', 'malisafi-mls'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div id="live-keys-section" style="<?php echo $stripe_mode === 'live' ? '' : 'display:none;'; ?>">
                    <h3 style="color: #d63638;"><?php _e('Live Mode Keys', 'malisafi-mls'); ?></h3>
                    <div class="notice notice-warning inline">
                        <p><strong><?php _e('Warning:', 'malisafi-mls'); ?></strong> <?php _e('Live mode will charge real money. Only use live keys when you\'re ready for production!', 'malisafi-mls'); ?></p>
                    </div>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="live_publishable_key"><?php _e('Live Publishable Key', 'malisafi-mls'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="live_publishable_key" id="live_publishable_key" 
                                       value="<?php echo esc_attr($live_publishable); ?>" class="regular-text code" 
                                       placeholder="pk_live_..." />
                                <p class="description"><?php _e('Starts with pk_live_', 'malisafi-mls'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="live_secret_key"><?php _e('Live Secret Key', 'malisafi-mls'); ?></label>
                            </th>
                            <td>
                                <input type="password" name="live_secret_key" id="live_secret_key" 
                                       value="<?php echo esc_attr($live_secret); ?>" class="regular-text code" 
                                       placeholder="sk_live_..." />
                                <p class="description"><?php _e('Starts with sk_live_ (keep this secret!)', 'malisafi-mls'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <h3><?php _e('Webhook Configuration', 'malisafi-mls'); ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label><?php _e('Webhook URL', 'malisafi-mls'); ?></label>
                        </th>
                        <td>
                            <input type="text" value="<?php echo esc_url(rest_url('malisafi/v1/stripe-webhook')); ?>" 
                                   class="large-text code" readonly onclick="this.select()" />
                            <p class="description">
                                <?php _e('Copy this URL and add it to your Stripe webhook settings.', 'malisafi-mls'); ?>
                                <a href="https://dashboard.stripe.com/webhooks" target="_blank"><?php _e('Configure in Stripe Dashboard', 'malisafi-mls'); ?></a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="webhook_secret"><?php _e('Webhook Signing Secret', 'malisafi-mls'); ?></label>
                        </th>
                        <td>
                            <input type="password" name="webhook_secret" id="webhook_secret" 
                                   value="<?php echo esc_attr($webhook_secret); ?>" class="regular-text code" 
                                   placeholder="whsec_..." />
                            <p class="description"><?php _e('Get this from your Stripe webhook settings after creating the webhook.', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Save Stripe Settings', 'malisafi-mls'), 'primary large'); ?>
            </form>
            
            <div class="notice notice-info" style="margin-top: 30px;">
                <h3><?php _e('Where to Find Your Stripe Keys', 'malisafi-mls'); ?></h3>
                <ol style="margin-left: 20px;">
                    <li><?php _e('Go to', 'malisafi-mls'); ?> <a href="https://dashboard.stripe.com/apikeys" target="_blank">https://dashboard.stripe.com/apikeys</a></li>
                    <li><?php _e('Copy your Publishable key (starts with pk_test_ or pk_live_)', 'malisafi-mls'); ?></li>
                    <li><?php _e('Click "Reveal test key" or "Create secret key" and copy your Secret key', 'malisafi-mls'); ?></li>
                    <li><?php _e('For webhooks, go to', 'malisafi-mls'); ?> <a href="https://dashboard.stripe.com/webhooks" target="_blank">https://dashboard.stripe.com/webhooks</a></li>
                    <li><?php _e('Add the webhook URL shown above and copy the Signing secret', 'malisafi-mls'); ?></li>
                </ol>
            </div>
        </div>
        
        <script>
        function toggleStripeMode(mode) {
            document.getElementById('test-keys-section').style.display = mode === 'test' ? '' : 'none';
            document.getElementById('live-keys-section').style.display = mode === 'live' ? '' : 'none';
        }
        </script>
        
    <?php elseif ($current_tab === 'plans'): ?>
        <!-- SUBSCRIPTION PLANS TAB -->
        <div class="malisafi-settings-section">
            <h2><?php _e('Manage Subscription Plans', 'malisafi-mls'); ?></h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('malisafi_plans_save', '_malisafi_plans_nonce'); ?>
                <input type="hidden" name="malisafi_plans_action" value="save_all" />
                
                <?php if (empty($plans)): ?>
                    <div class="notice notice-warning" style="padding: 20px; background: #fff; border-left: 4px solid #f39c12; margin: 20px 0;">
                        <h3 style="margin-top: 0;"><?php _e('No Plans Defined', 'malisafi-mls'); ?></h3>
                        <p><?php _e('Get started by initializing role-based plans with default configurations:', 'malisafi-mls'); ?></p>
                        <ul style="margin-left: 20px; list-style: disc;">
                            <li><strong><?php _e('Client (Free)', 'malisafi-mls'); ?></strong> - <?php _e('Basic browsing access', 'malisafi-mls'); ?></li>
                            <li><strong><?php _e('Agent Basic (KSh 2,999/mo)', 'malisafi-mls'); ?></strong> - <?php _e('Up to 10 listings', 'malisafi-mls'); ?></li>
                            <li><strong><?php _e('Agent Premium (KSh 9,999/mo)', 'malisafi-mls'); ?></strong> - <?php _e('Unlimited listings + featured', 'malisafi-mls'); ?></li>
                            <li><strong><?php _e('Property Owner (KSh 1,999/mo)', 'malisafi-mls'); ?></strong> - <?php _e('Up to 3 personal listings', 'malisafi-mls'); ?></li>
                            <li><strong><?php _e('Developer (KSh 19,999/mo)', 'malisafi-mls'); ?></strong> - <?php _e('Unlimited projects + API access', 'malisafi-mls'); ?></li>
                        </ul>
                        <p>
                            <button class="button button-primary button-large" type="submit" name="malisafi_plans_action" value="initialize_role_based" style="margin-top: 10px;">
                                <span class="dashicons dashicons-update" style="margin-top: 3px;"></span>
                                <?php _e('Initialize Default Plans', 'malisafi-mls'); ?>
                            </button>
                        </p>
                    </div>
                <?php else: ?>
                    <?php foreach ($plans as $key => $plan): ?>
                        <div class="plan-block" style="background: #fff; border: 1px solid #ddd; border-radius: 5px; padding: 20px; margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <h3 style="margin-top: 0; color: #737d5d;">
                                    <?php echo esc_html($plan['name']); ?>
                                    <small style="color: #999; font-weight: normal;">(<?php echo esc_html($key); ?>)</small>
                                </h3>
                                <label style="display: flex; align-items: center; gap: 8px;">
                                    <input type="checkbox" name="plans[<?php echo esc_attr($key); ?>][is_active]" value="1" 
                                           <?php checked(!empty($plan['is_active']), true); ?> />
                                    <span><?php _e('Active', 'malisafi-mls'); ?></span>
                                </label>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 15px;">
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">
                                        <?php _e('Plan Name', 'malisafi-mls'); ?>
                                    </label>
                                    <input type="text" name="plans[<?php echo esc_attr($key); ?>][name]" 
                                           value="<?php echo esc_attr($plan['name']); ?>" class="regular-text" />
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 10px;">
                                    <div>
                                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">
                                            <?php _e('Price', 'malisafi-mls'); ?>
                                        </label>
                                        <input type="number" name="plans[<?php echo esc_attr($key); ?>][price]" 
                                               value="<?php echo esc_attr($plan['price']); ?>" class="regular-text" step="0.01" min="0" />
                                    </div>
                                    <div>
                                        <label style="display: block; margin-bottom: 5px; font-weight: 600;">
                                            <?php _e('Currency', 'malisafi-mls'); ?>
                                        </label>
                                        <select name="plans[<?php echo esc_attr($key); ?>][currency]" class="regular-text">
                                            <option value="USD" <?php selected($plan['currency'], 'USD'); ?>>USD ($)</option>
                                            <option value="KES" <?php selected($plan['currency'], 'KES'); ?>>KES (KSh)</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">
                                        <?php _e('Billing Interval', 'malisafi-mls'); ?>
                                    </label>
                                    <select name="plans[<?php echo esc_attr($key); ?>][interval]" class="regular-text">
                                        <option value="month" <?php selected($plan['interval'], 'month'); ?>><?php _e('Monthly', 'malisafi-mls'); ?></option>
                                        <option value="year" <?php selected($plan['interval'], 'year'); ?>><?php _e('Yearly', 'malisafi-mls'); ?></option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">
                                        <?php _e('Stripe Price ID', 'malisafi-mls'); ?>
                                    </label>
                                    <input type="text" name="plans[<?php echo esc_attr($key); ?>][stripe_price_id]" 
                                           value="<?php echo esc_attr($plan['stripe_price_id'] ?? ''); ?>" class="regular-text code" 
                                           placeholder="price_..." />
                                    <p class="description"><?php _e('From Stripe Dashboard', 'malisafi-mls'); ?></p>
                                </div>
                                
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">
                                        <?php _e('Max Properties', 'malisafi-mls'); ?>
                                    </label>
                                    <input type="number" name="plans[<?php echo esc_attr($key); ?>][max_listings]" 
                                           value="<?php echo esc_attr($plan['max_listings']); ?>" class="regular-text" min="-1" />
                                    <p class="description"><?php _e('-1 = unlimited', 'malisafi-mls'); ?></p>
                                </div>
                                
                                <div>
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">
                                        <?php _e('Featured Properties/Month', 'malisafi-mls'); ?>
                                    </label>
                                    <input type="number" name="plans[<?php echo esc_attr($key); ?>][featured_listings]" 
                                           value="<?php echo esc_attr($plan['featured_listings']); ?>" class="regular-text" min="0" />
                                </div>
                                
                                <div style="grid-column: 1 / -1;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">
                                        <?php _e('Features (comma-separated)', 'malisafi-mls'); ?>
                                    </label>
                                    <textarea name="plans[<?php echo esc_attr($key); ?>][features]" rows="2" class="large-text"><?php 
                                        echo esc_textarea(is_array($plan['features']) ? implode(', ', $plan['features']) : $plan['features']); 
                                    ?></textarea>
                                </div>
                            </div>
                            
                            <input type="hidden" name="plans[<?php echo esc_attr($key); ?>][key]" value="<?php echo esc_attr($key); ?>" />
                        </div>
                    <?php endforeach; ?>
                    
                    <?php submit_button(__('Save All Plans', 'malisafi-mls'), 'primary large'); ?>
                <?php endif; ?>
            </form>
        </div>
        
    <?php elseif ($current_tab === 'subscriptions'): ?>
        <!-- ACTIVE SUBSCRIPTIONS TAB -->
        <div class="malisafi-settings-section">
            <h2><?php _e('Active Subscriptions', 'malisafi-mls'); ?></h2>
            
            <?php
            $active_subscriptions = $wpdb->get_results($wpdb->prepare(
                "SELECT s.*, u.user_login, u.user_email, u.display_name 
                FROM {$subscriptions_table} s 
                LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID 
                WHERE s.status = %s 
                ORDER BY s.created_at DESC",
                'active'
            ));
            ?>
            
            <?php if (empty($active_subscriptions)): ?>
                <div class="notice notice-info inline">
                    <p><?php _e('No active subscriptions yet. Users can subscribe via the pricing page once Stripe is configured.', 'malisafi-mls'); ?></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('User', 'malisafi-mls'); ?></th>
                            <th><?php _e('Plan', 'malisafi-mls'); ?></th>
                            <th><?php _e('Status', 'malisafi-mls'); ?></th>
                            <th><?php _e('Start Date', 'malisafi-mls'); ?></th>
                            <th><?php _e('End Date', 'malisafi-mls'); ?></th>
                            <th><?php _e('Stripe ID', 'malisafi-mls'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($active_subscriptions as $subscription): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($subscription->display_name); ?></strong><br>
                                    <small><?php echo esc_html($subscription->user_email); ?></small>
                                </td>
                                <td><?php echo esc_html(ucwords(str_replace('_', ' ', $subscription->plan_type))); ?></td>
                                <td>
                                    <span style="display: inline-block; padding: 3px 8px; background: #46b450; color: #fff; border-radius: 3px; font-size: 11px;">
                                        <?php echo strtoupper($subscription->status); ?>
                                    </span>
                                </td>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($subscription->current_period_start)); ?></td>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($subscription->current_period_end)); ?></td>
                                <td><code style="font-size: 11px;"><?php echo esc_html($subscription->stripe_subscription_id); ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
    <?php elseif ($current_tab === 'assign'): ?>
        <!-- MANUAL ASSIGNMENT TAB -->
        <div class="malisafi-settings-section">
            <h2><?php _e('Manually Assign Subscription', 'malisafi-mls'); ?></h2>
            <p class="description"><?php _e('Assign or update subscriptions for specific users without requiring payment. Useful for promotions, testing, or special arrangements.', 'malisafi-mls'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('malisafi_assign_subscription'); ?>
                <input type="hidden" name="malisafi_assign_subscription" value="1" />
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="user_id"><?php _e('Select User', 'malisafi-mls'); ?></label>
                        </th>
                        <td>
                            <?php
                            $users = get_users(array('orderby' => 'display_name', 'order' => 'ASC'));
                            ?>
                            <select name="user_id" id="user_id" class="regular-text" required>
                                <option value=""><?php _e('-- Select User --', 'malisafi-mls'); ?></option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user->ID; ?>">
                                        <?php echo esc_html($user->display_name); ?> (<?php echo esc_html($user->user_email); ?>) 
                                        - <?php echo esc_html(implode(', ', $user->roles)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Choose the user to assign a subscription to', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="plan_type"><?php _e('Subscription Plan', 'malisafi-mls'); ?></label>
                        </th>
                        <td>
                            <select name="plan_type" id="plan_type" class="regular-text" required>
                                <option value=""><?php _e('-- Select Plan --', 'malisafi-mls'); ?></option>
                                <?php foreach ($plans as $key => $plan): ?>
                                    <option value="<?php echo esc_attr($key); ?>">
                                        <?php echo esc_html($plan['name']); ?> 
                                        <?php if ($plan['price'] > 0): ?>
                                            (<?php echo Malisafi_Stripe::get_currency_symbol($plan['currency']); ?><?php echo number_format($plan['price'], 2); ?>/<?php echo $plan['interval']; ?>)
                                        <?php else: ?>
                                            (<?php _e('Free', 'malisafi-mls'); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('The plan will determine user role and property limits', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="end_date"><?php _e('Subscription End Date', 'malisafi-mls'); ?></label>
                        </th>
                        <td>
                            <input type="date" name="end_date" id="end_date" class="regular-text" 
                                   value="<?php echo date('Y-m-d', strtotime('+1 month')); ?>" required />
                            <p class="description"><?php _e('When this subscription should expire (can be extended later)', 'malisafi-mls'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Assign Subscription', 'malisafi-mls'), 'primary large'); ?>
            </form>
            
            <div class="notice notice-info" style="margin-top: 30px;">
                <h3><?php _e('About Manual Assignments', 'malisafi-mls'); ?></h3>
                <ul style="margin-left: 20px;">
                    <li><?php _e('Manual assignments bypass Stripe payment and create local subscriptions', 'malisafi-mls'); ?></li>
                    <li><?php _e('The user\'s role will be changed to match the selected plan', 'malisafi-mls'); ?></li>
                    <li><?php _e('Property limits will be applied based on the plan configuration', 'malisafi-mls'); ?></li>
                    <li><?php _e('These subscriptions won\'t auto-renew - you must extend them manually', 'malisafi-mls'); ?></li>
                    <li><?php _e('Use this for: promotional access, testing, team members, or special arrangements', 'malisafi-mls'); ?></li>
                </ul>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.malisafi-settings-section {
    background: #fff;
    padding: 20px;
    margin: 20px 0;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.malisafi-settings-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #737d5d;
    color: #737d5d;
}

.plan-block:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-color: #737d5d !important;
}

.form-table th {
    width: 200px;
}

.notice.inline {
    margin: 20px 0;
}

.count {
    background: #737d5d;
    color: #fff;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 12px;
    margin-left: 5px;
}
</style>
