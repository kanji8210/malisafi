<?php
/**
 * Subscriptions & Billing Admin Template
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

// Handle form submission
if (isset($_POST['malisafi_save_stripe_settings']) && check_admin_referer('malisafi_stripe_settings')) {
    update_option('malisafi_stripe_mode', sanitize_text_field($_POST['stripe_mode']));
    update_option('malisafi_stripe_test_publishable_key', sanitize_text_field($_POST['test_publishable_key']));
    update_option('malisafi_stripe_test_secret_key', sanitize_text_field($_POST['test_secret_key']));
    update_option('malisafi_stripe_live_publishable_key', sanitize_text_field($_POST['live_publishable_key']));
    update_option('malisafi_stripe_live_secret_key', sanitize_text_field($_POST['live_secret_key']));
    update_option('malisafi_stripe_webhook_secret', sanitize_text_field($_POST['webhook_secret']));
    
    // Save price IDs
    update_option('malisafi_stripe_price_agent_basic', sanitize_text_field($_POST['price_agent_basic']));
    update_option('malisafi_stripe_price_agent_premium', sanitize_text_field($_POST['price_agent_premium']));
    update_option('malisafi_stripe_price_owner_basic', sanitize_text_field($_POST['price_owner_basic']));
    update_option('malisafi_stripe_price_developer', sanitize_text_field($_POST['price_developer']));
    
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Stripe settings saved successfully!', 'malisafi-mls') . '</p></div>';
}

// Get current values
$stripe_mode = get_option('malisafi_stripe_mode', 'test');
$test_publishable = get_option('malisafi_stripe_test_publishable_key', '');
$test_secret = get_option('malisafi_stripe_test_secret_key', '');
$live_publishable = get_option('malisafi_stripe_live_publishable_key', '');
$live_secret = get_option('malisafi_stripe_live_secret_key', '');
$webhook_secret = get_option('malisafi_stripe_webhook_secret', '');

$price_agent_basic = get_option('malisafi_stripe_price_agent_basic', '');
$price_agent_premium = get_option('malisafi_stripe_price_agent_premium', '');
$price_owner_basic = get_option('malisafi_stripe_price_owner_basic', '');
$price_developer = get_option('malisafi_stripe_price_developer', '');

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'settings';

// Get subscription stats
global $wpdb;
$subscriptions_table = $wpdb->prefix . 'mf_subscriptions';

$total_subscriptions = $wpdb->get_var("SELECT COUNT(*) FROM {$subscriptions_table} WHERE status = 'active'");
$total_subscriptions = $total_subscriptions !== null ? intval($total_subscriptions) : 0;

$monthly_revenue = $wpdb->get_var("SELECT SUM(
    CASE 
        WHEN plan_type = 'agent_basic' THEN 29.99
        WHEN plan_type = 'agent_premium' THEN 99.99
        WHEN plan_type = 'owner_basic' THEN 19.99
        WHEN plan_type = 'developer' THEN 199.99
        ELSE 0
    END
) FROM {$subscriptions_table} WHERE status = 'active'");
$monthly_revenue = $monthly_revenue !== null ? floatval($monthly_revenue) : 0.0;

$subscriptions_by_plan = $wpdb->get_results("
    SELECT plan_type, COUNT(*) as count 
    FROM {$subscriptions_table} 
    WHERE status = 'active' 
    GROUP BY plan_type
");
?>

<div class="wrap">
    <h1><?php _e('Subscriptions & Billing', 'malisafi-mls'); ?></h1>
    
    <!-- Tabs -->
    <nav class="nav-tab-wrapper wp-clearfix">
        <a href="<?php echo admin_url('admin.php?page=malisafi-subscriptions&tab=settings'); ?>" 
           class="nav-tab <?php echo $current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Stripe Settings', 'malisafi-mls'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=malisafi-subscriptions&tab=plans'); ?>" 
           class="nav-tab <?php echo $current_tab === 'plans' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Subscription Plans', 'malisafi-mls'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=malisafi-subscriptions&tab=subscriptions'); ?>" 
           class="nav-tab <?php echo $current_tab === 'subscriptions' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Active Subscriptions', 'malisafi-mls'); ?>
            <span class="count">(<?php echo $total_subscriptions; ?>)</span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=malisafi-subscriptions&tab=stats'); ?>" 
           class="nav-tab <?php echo $current_tab === 'stats' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Statistics', 'malisafi-mls'); ?>
        </a>
    </nav>
    
    <div class="subscription-content" style="margin-top: 20px;">
        
        <?php if ($current_tab === 'settings') : ?>
            
            <!-- Stripe Settings -->
            <div class="card" style="max-width: 800px;">
                <h2><?php _e('Stripe API Configuration', 'malisafi-mls'); ?></h2>
                
                <?php if (Malisafi_Stripe::is_configured()) : ?>
                    <div class="notice notice-success inline">
                        <p><span class="dashicons dashicons-yes-alt"></span> <?php _e('Stripe is configured and ready!', 'malisafi-mls'); ?></p>
                    </div>
                <?php else : ?>
                    <div class="notice notice-warning inline">
                        <p><span class="dashicons dashicons-warning"></span> <?php _e('Stripe is not configured yet. Please add your API keys below.', 'malisafi-mls'); ?></p>
                    </div>
                <?php endif; ?>
                
                <form method="post">
                    <?php wp_nonce_field('malisafi_stripe_settings'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Stripe Mode', 'malisafi-mls'); ?></th>
                            <td>
                                <label>
                                    <input type="radio" name="stripe_mode" value="test" <?php checked($stripe_mode, 'test'); ?>>
                                    <?php _e('Test Mode', 'malisafi-mls'); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="radio" name="stripe_mode" value="live" <?php checked($stripe_mode, 'live'); ?>>
                                    <?php _e('Live Mode', 'malisafi-mls'); ?>
                                </label>
                                <p class="description"><?php _e('Use test mode for development and testing.', 'malisafi-mls'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <h3><?php _e('Test API Keys', 'malisafi-mls'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="test_publishable_key"><?php _e('Test Publishable Key', 'malisafi-mls'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="test_publishable_key" name="test_publishable_key" 
                                       value="<?php echo esc_attr($test_publishable); ?>" 
                                       class="regular-text" placeholder="pk_test_...">
                                <p class="description"><?php _e('Starts with pk_test_', 'malisafi-mls'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="test_secret_key"><?php _e('Test Secret Key', 'malisafi-mls'); ?></label>
                            </th>
                            <td>
                                <input type="password" id="test_secret_key" name="test_secret_key" 
                                       value="<?php echo esc_attr($test_secret); ?>" 
                                       class="regular-text" placeholder="sk_test_...">
                                <p class="description"><?php _e('Starts with sk_test_', 'malisafi-mls'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <h3><?php _e('Live API Keys', 'malisafi-mls'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="live_publishable_key"><?php _e('Live Publishable Key', 'malisafi-mls'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="live_publishable_key" name="live_publishable_key" 
                                       value="<?php echo esc_attr($live_publishable); ?>" 
                                       class="regular-text" placeholder="pk_live_...">
                                <p class="description"><?php _e('Starts with pk_live_', 'malisafi-mls'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="live_secret_key"><?php _e('Live Secret Key', 'malisafi-mls'); ?></label>
                            </th>
                            <td>
                                <input type="password" id="live_secret_key" name="live_secret_key" 
                                       value="<?php echo esc_attr($live_secret); ?>" 
                                       class="regular-text" placeholder="sk_live_...">
                                <p class="description"><?php _e('Starts with sk_live_', 'malisafi-mls'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <h3><?php _e('Webhook Configuration', 'malisafi-mls'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Webhook URL', 'malisafi-mls'); ?></th>
                            <td>
                                <code><?php echo rest_url('malisafi/v1/stripe-webhook'); ?></code>
                                <p class="description">
                                    <?php _e('Add this URL to your Stripe webhook endpoints.', 'malisafi-mls'); ?>
                                    <a href="https://dashboard.stripe.com/webhooks" target="_blank"><?php _e('Configure in Stripe', 'malisafi-mls'); ?></a>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="webhook_secret"><?php _e('Webhook Signing Secret', 'malisafi-mls'); ?></label>
                            </th>
                            <td>
                                <input type="password" id="webhook_secret" name="webhook_secret" 
                                       value="<?php echo esc_attr($webhook_secret); ?>" 
                                       class="regular-text" placeholder="whsec_...">
                                <p class="description"><?php _e('Starts with whsec_', 'malisafi-mls'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <h3><?php _e('Stripe Price IDs', 'malisafi-mls'); ?></h3>
                    <p class="description" style="margin-left: 0;">
                        <?php _e('Create products and prices in your Stripe dashboard, then paste the Price IDs here.', 'malisafi-mls'); ?>
                        <a href="https://dashboard.stripe.com/products" target="_blank"><?php _e('Manage in Stripe', 'malisafi-mls'); ?></a>
                    </p>
                    
                    <table class="form-table">
                        <?php
                        $default_plans = Malisafi_Stripe::get_plans();
                        $plan_keys = array(
                            'agent_basic' => 'price_agent_basic',
                            'agent_premium' => 'price_agent_premium',
                            'owner_basic' => 'price_owner_basic',
                            'developer' => 'price_developer'
                        );
                        
                        foreach ($plan_keys as $plan_key => $field_name) :
                            $plan_data = isset($default_plans[$plan_key]) ? $default_plans[$plan_key] : null;
                            if (!$plan_data) continue;
                            
                            $label = $plan_data['name'];
                            $price_display = Malisafi_Stripe::format_price($plan_data['price'], isset($plan_data['currency']) ? $plan_data['currency'] : 'USD');
                        ?>
                        <tr>
                            <th scope="row">
                                <label for="<?php echo esc_attr($field_name); ?>"><?php echo esc_html($label); ?> <?php _e('Price ID', 'malisafi-mls'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="<?php echo esc_attr($field_name); ?>" name="<?php echo esc_attr($field_name); ?>" 
                                       value="<?php echo esc_attr(${$field_name}); ?>" 
                                       class="regular-text" placeholder="price_...">
                                <p class="description"><?php echo esc_html($price_display); ?>/<?php echo esc_html($plan_data['interval']); ?></p>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" name="malisafi_save_stripe_settings" class="button button-primary">
                            <?php _e('Save Settings', 'malisafi-mls'); ?>
                        </button>
                    </p>
                </form>
            </div>
            
        <?php elseif ($current_tab === 'plans') : ?>
            
            <!-- Subscription Plans -->
            <div class="subscription-plans-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 20px;">
                
                <?php 
                $plans = Malisafi_Stripe::get_plans();
                foreach ($plans as $plan_id => $plan) : 
                ?>
                    <div class="plan-card" style="background: white; border: 2px solid #ddd; border-radius: 8px; padding: 25px; text-align: center;">
                        <h3 style="margin-top: 0;"><?php echo esc_html($plan['name']); ?></h3>
                        <div class="plan-price" style="font-size: 32px; font-weight: bold; color: #2271b1; margin: 15px 0;">
                            <?php 
                                $currency = isset($plan['currency']) ? $plan['currency'] : 'USD';
                                echo esc_html(Malisafi_Stripe::format_price($plan['price'], $currency));
                            ?>
                            <span style="font-size: 16px; font-weight: normal; color: #666;">/<?php echo $plan['interval']; ?></span>
                        </div>
                        
                        <ul style="list-style: none; padding: 0; margin: 20px 0; text-align: left;">
                            <?php foreach ($plan['features'] as $feature) : ?>
                                <li style="padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                                    <span class="dashicons dashicons-yes" style="color: #00a32a;"></span>
                                    <?php echo esc_html($feature); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <?php if (!empty($plan['stripe_price_id'])) : ?>
                            <span style="color: #00a32a; font-size: 12px;">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php _e('Configured', 'malisafi-mls'); ?>
                            </span>
                        <?php else : ?>
                            <span style="color: #d63638; font-size: 12px;">
                                <span class="dashicons dashicons-warning"></span>
                                <?php _e('Not configured', 'malisafi-mls'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
            </div>
            
        <?php elseif ($current_tab === 'subscriptions') : ?>
            
            <!-- Active Subscriptions List -->
            <?php
            $active_subscriptions = $wpdb->get_results("
                SELECT s.*, u.user_login, u.user_email, u.display_name
                FROM {$subscriptions_table} s
                LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
                WHERE s.status = 'active'
                ORDER BY s.created_at DESC
            ");
            ?>
            
            <?php if (!empty($active_subscriptions)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('User', 'malisafi-mls'); ?></th>
                            <th><?php _e('Plan', 'malisafi-mls'); ?></th>
                            <th><?php _e('Status', 'malisafi-mls'); ?></th>
                            <th><?php _e('Started', 'malisafi-mls'); ?></th>
                            <th><?php _e('Current Period', 'malisafi-mls'); ?></th>
                            <th><?php _e('Revenue', 'malisafi-mls'); ?></th>
                            <th><?php _e('Actions', 'malisafi-mls'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($active_subscriptions as $subscription) : ?>
                            <?php
                            $plans = Malisafi_Stripe::get_plans();
                            $plan_info = isset($plans[$subscription->plan_type]) ? $plans[$subscription->plan_type] : null;
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($subscription->display_name); ?></strong><br>
                                    <small><?php echo esc_html($subscription->user_email); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo $plan_info ? esc_html($plan_info['name']) : esc_html($subscription->plan_type); ?></strong>
                                </td>
                                <td>
                                    <span class="status-badge" style="background: #00a32a; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px;">
                                        <?php echo esc_html(ucfirst($subscription->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo date_i18n('M j, Y', strtotime($subscription->created_at)); ?></td>
                                <td>
                                    <?php echo date_i18n('M j', strtotime($subscription->current_period_start)); ?> - 
                                    <?php echo date_i18n('M j, Y', strtotime($subscription->current_period_end)); ?>
                                </td>
                                <td><strong>$<?php echo $plan_info ? number_format($plan_info['price'], 2) : '0.00'; ?></strong></td>
                                <td>
                                    <a href="https://dashboard.stripe.com/subscriptions/<?php echo esc_attr($subscription->stripe_subscription_id); ?>" 
                                       target="_blank" class="button button-small">
                                        <?php _e('View in Stripe', 'malisafi-mls'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="no-items" style="text-align: center; padding: 60px 20px; background: white; border: 1px solid #ddd;">
                    <span class="dashicons dashicons-admin-users" style="font-size: 60px; color: #ccc;"></span>
                    <p><?php _e('No active subscriptions yet.', 'malisafi-mls'); ?></p>
                </div>
            <?php endif; ?>
            
        <?php elseif ($current_tab === 'stats') : ?>
            
            <!-- Statistics -->
            <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                
                <div class="stat-card" style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px;">
                    <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px;"><?php _e('Active Subscriptions', 'malisafi-mls'); ?></h3>
                    <div style="font-size: 36px; font-weight: bold; color: #2271b1;"><?php echo number_format($total_subscriptions); ?></div>
                </div>
                
                <div class="stat-card" style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px;">
                    <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px;"><?php _e('Monthly Revenue', 'malisafi-mls'); ?></h3>
                    <div style="font-size: 36px; font-weight: bold; color: #00a32a;">$<?php echo number_format($monthly_revenue, 2); ?></div>
                </div>
                
                <?php foreach ($subscriptions_by_plan as $plan_stat) : ?>
                    <?php
                    $plans = Malisafi_Stripe::get_plans();
                    $plan_info = isset($plans[$plan_stat->plan_type]) ? $plans[$plan_stat->plan_type] : null;
                    ?>
                    <div class="stat-card" style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px;">
                        <h3 style="margin: 0 0 10px 0; color: #666; font-size: 14px;">
                            <?php echo $plan_info ? esc_html($plan_info['name']) : esc_html($plan_stat->plan_type); ?>
                        </h3>
                        <div style="font-size: 36px; font-weight: bold; color: #2271b1;"><?php echo number_format($plan_stat->count); ?></div>
                        <small style="color: #666;">
                            $<?php echo $plan_info ? number_format($plan_info['price'] * $plan_stat->count, 2) : '0.00'; ?>/month
                        </small>
                    </div>
                <?php endforeach; ?>
                
            </div>
            
        <?php endif; ?>
        
    </div>
</div>

<style>
.card {
    background: white;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    padding: 20px;
}

.nav-tab-wrapper .count {
    background: #2271b1;
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    margin-left: 5px;
}

.notice.inline {
    margin: 15px 0;
    padding: 12px;
}
</style>
