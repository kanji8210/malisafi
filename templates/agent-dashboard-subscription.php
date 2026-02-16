<?php
/**
 * Agent Dashboard - Subscription Section
 * Allows agents to view their current subscription and select/upgrade plans
 *
 * @package MalisafiMLS
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current user
$user_id = get_current_user_id();

// Get user's current subscription
global $wpdb;
$subscription = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}mf_subscriptions WHERE user_id = %d ORDER BY created_at DESC LIMIT 1",
    $user_id
));

// Get user limits
$limits = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}mf_user_limits WHERE user_id = %d",
    $user_id
));

// Get available plans
$plans = array();
if (class_exists('Malisafi_Stripe') && Malisafi_Stripe::is_configured()) {
    $plans = Malisafi_Stripe::get_plans();
}

// Count user's properties
$property_count = count(get_posts([
    'post_type' => 'malisafi_property',
    'author' => $user_id,
    'post_status' => ['publish', 'pending', 'draft'],
    'posts_per_page' => -1,
    'fields' => 'ids'
]));
?>

<div class="dashboard-subscription">
    <div class="subscription-header">
        <h1><?php _e('My Subscription', 'malisafi-mls'); ?></h1>
        <p class="subtitle"><?php _e('Manage your subscription plan and view usage details', 'malisafi-mls'); ?></p>
    </div>

    <?php if ($subscription && $subscription->status === 'active') : ?>
        <!-- Current Subscription -->
        <div class="current-subscription-card">
            <div class="subscription-badge">
                <span class="dashicons dashicons-yes-alt"></span>
                <span><?php _e('Active Subscription', 'malisafi-mls'); ?></span>
            </div>

            <div class="subscription-details">
                <h2>
                    <?php 
                    $plan_names = [
                        'agent_basic' => __('Agent Basic', 'malisafi-mls'),
                        'agent_premium' => __('Agent Premium', 'malisafi-mls'),
                        'owner_basic' => __('Owner Basic', 'malisafi-mls'),
                        'developer' => __('Developer', 'malisafi-mls')
                    ];
                    echo isset($plan_names[$subscription->plan_type]) ? esc_html($plan_names[$subscription->plan_type]) : ucwords(str_replace('_', ' ', $subscription->plan_type));
                    ?>
                </h2>

                <div class="subscription-info-grid">
                    <div class="info-item">
                        <span class="info-label"><?php _e('Status:', 'malisafi-mls'); ?></span>
                        <span class="status-badge active"><?php echo esc_html(ucfirst($subscription->status)); ?></span>
                    </div>
                    
                    <?php if ($subscription->current_period_end) : ?>
                        <div class="info-item">
                            <span class="info-label"><?php _e('Renewal Date:', 'malisafi-mls'); ?></span>
                            <span class="info-value"><?php echo date_i18n(get_option('date_format'), strtotime($subscription->current_period_end)); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($limits) : ?>
                        <div class="info-item">
                            <span class="info-label"><?php _e('Property Limit:', 'malisafi-mls'); ?></span>
                            <span class="info-value">
                                <?php 
                                if ($limits->max_properties == -1) {
                                    _e('Unlimited', 'malisafi-mls');
                                } else {
                                    echo esc_html($limits->max_properties);
                                }
                                ?>
                            </span>
                        </div>

                        <div class="info-item">
                            <span class="info-label"><?php _e('Current Usage:', 'malisafi-mls'); ?></span>
                            <span class="info-value">
                                <?php echo esc_html($property_count); ?> 
                                <?php if ($limits->max_properties != -1) : ?>
                                    / <?php echo esc_html($limits->max_properties); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($subscription->stripe_subscription_id)) : ?>
                    <div class="subscription-actions">
                        <button type="button" id="manage-stripe-subscription" class="button button-primary">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <?php _e('Manage Billing', 'malisafi-mls'); ?>
                        </button>
                        <p class="description"><?php _e('Update payment method, view invoices, or cancel your subscription.', 'malisafi-mls'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else : ?>
        <!-- No Active Subscription -->
        <div class="no-subscription-card">
            <div class="icon-wrapper">
                <span class="dashicons dashicons-info"></span>
            </div>
            <h2><?php _e('No Active Subscription', 'malisafi-mls'); ?></h2>
            <p><?php _e('You currently don\'t have an active subscription plan. Choose a plan below to unlock full features and start listing properties!', 'malisafi-mls'); ?></p>
        </div>
    <?php endif; ?>

    <!-- Available Plans -->
    <?php if (!empty($plans)) : ?>
        <div class="available-plans-section">
            <h2><?php _e('Available Plans', 'malisafi-mls'); ?></h2>
            <p class="section-description">
                <?php 
                if ($subscription && $subscription->status === 'active') {
                    _e('Upgrade or change your subscription plan:', 'malisafi-mls');
                } else {
                    _e('Choose a plan to get started:', 'malisafi-mls');
                }
                ?>
            </p>

            <div class="plans-grid">
                <?php foreach ($plans as $plan_id => $plan) : 
                    $is_current_plan = $subscription && $subscription->plan_type === $plan_id && $subscription->status === 'active';
                    $plan_classes = ['plan-card'];
                    
                    if ($is_current_plan) {
                        $plan_classes[] = 'current-plan';
                    }
                    
                    if ($plan_id === 'agent_premium') {
                        $plan_classes[] = 'recommended';
                    }
                ?>
                    <div class="<?php echo implode(' ', $plan_classes); ?>">
                        <?php if ($plan_id === 'agent_premium') : ?>
                            <div class="plan-badge"><?php _e('Most Popular', 'malisafi-mls'); ?></div>
                        <?php endif; ?>
                        
                        <?php if ($is_current_plan) : ?>
                            <div class="current-badge"><?php _e('Current Plan', 'malisafi-mls'); ?></div>
                        <?php endif; ?>

                        <div class="plan-header">
                            <h3><?php echo esc_html($plan['name']); ?></h3>
                            <div class="plan-price">
                                <?php 
                                $currency = isset($plan['currency']) ? $plan['currency'] : 'USD';
                                $symbol = Malisafi_Stripe::get_currency_symbol($currency);
                                ?>
                                <span class="currency"><?php echo esc_html($symbol); ?></span>
                                <span class="amount"><?php echo number_format($plan['price'], 0); ?></span>
                                <span class="period">/<?php echo esc_html($plan['interval']); ?></span>
                            </div>
                        </div>

                        <div class="plan-features">
                            <ul>
                                <?php if (!empty($plan['features'])) :
                                    foreach ($plan['features'] as $feature) : ?>
                                        <li>
                                            <span class="dashicons dashicons-yes"></span>
                                            <?php echo esc_html($feature); ?>
                                        </li>
                                    <?php endforeach;
                                endif; ?>
                            </ul>
                        </div>

                        <div class="plan-action">
                            <?php if ($is_current_plan) : ?>
                                <button class="button button-secondary" disabled>
                                    <?php _e('Current Plan', 'malisafi-mls'); ?>
                                </button>
                            <?php elseif (!empty($plan['stripe_price_id'])) : ?>
                                <button type="button" class="button button-primary select-plan-btn" 
                                        data-plan="<?php echo esc_attr($plan_id); ?>"
                                        data-price-id="<?php echo esc_attr($plan['stripe_price_id']); ?>">
                                    <?php 
                                    if ($subscription && $subscription->status === 'active') {
                                        _e('Switch to This Plan', 'malisafi-mls');
                                    } else {
                                        _e('Choose Plan', 'malisafi-mls');
                                    }
                                    ?>
                                </button>
                            <?php else : ?>
                                <button class="button button-secondary" disabled>
                                    <?php _e('Contact Admin', 'malisafi-mls'); ?>
                                </button>
                                <p class="plan-notice"><?php _e('This plan requires admin configuration', 'malisafi-mls'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else : ?>
        <!-- Stripe Not Configured -->
        <div class="stripe-not-configured">
            <div class="notice-box">
                <span class="dashicons dashicons-warning"></span>
                <h3><?php _e('Subscriptions Not Available', 'malisafi-mls'); ?></h3>
                <p><?php _e('Subscription plans are not currently configured. Please contact the administrator for more information.', 'malisafi-mls'); ?></p>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.dashboard-subscription {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.subscription-header {
    margin-bottom: 40px;
    text-align: center;
}

.subscription-header h1 {
    font-size: 32px;
    font-weight: 600;
    color: var(--mls-dark, #2c3e50);
    margin-bottom: 10px;
}

.subscription-header .subtitle {
    font-size: 16px;
    color: #666;
}

.current-subscription-card {
    background: linear-gradient(135deg, var(--mls-accent, #737d5d) 0%, var(--mls-grey-green, #8a9475) 100%);
    color: white;
    padding: 40px;
    border-radius: 12px;
    margin-bottom: 40px;
    box-shadow: 0 8px 20px rgba(115, 125, 93, 0.2);
}

.subscription-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.2);
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 20px;
}

.subscription-details h2 {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 30px;
    color: white;
}

.subscription-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.info-label {
    font-size: 14px;
    opacity: 0.9;
    font-weight: 500;
}

.info-value {
    font-size: 18px;
    font-weight: 600;
}

.status-badge.active {
    background: rgba(255, 255, 255, 0.3);
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 14px;
    display: inline-block;
    font-weight: 600;
}

.subscription-actions {
    margin-top: 30px;
    padding-top: 30px;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
}

.subscription-actions .button {
    background: white;
    color: var(--mls-accent, #737d5d);
    border: none;
    padding: 12px 24px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.subscription-actions .button:hover {
    background: #f8f9fa;
    color: var(--mls-dark, #2c3e50);
}

.subscription-actions .description {
    color: rgba(255, 255, 255, 0.8);
    margin-top: 10px;
}

.no-subscription-card {
    background: #f8f9fa;
    border: 2px dashed #ddd;
    padding: 60px 40px;
    text-align: center;
    border-radius: 12px;
    margin-bottom: 40px;
}

.no-subscription-card .icon-wrapper {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    background: var(--mls-light-grey, #e8eae6);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.no-subscription-card .icon-wrapper .dashicons {
    font-size: 40px;
    width: 40px;
    height: 40px;
    color: var(--mls-accent, #737d5d);
}

.no-subscription-card h2 {
    font-size: 24px;
    color: var(--mls-dark, #2c3e50);
    margin-bottom: 15px;
}

.no-subscription-card p {
    font-size: 16px;
    color: #666;
    max-width: 600px;
    margin: 0 auto;
}

.available-plans-section {
    margin-top: 50px;
}

.available-plans-section h2 {
    font-size: 28px;
    font-weight: 600;
    color: var(--mls-dark, #2c3e50);
    margin-bottom: 10px;
    text-align: center;
}

.section-description {
    text-align: center;
    font-size: 16px;
    color: #666;
    margin-bottom: 40px;
}

.plans-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 30px;
    margin-top: 30px;
}

.plan-card {
    background: white;
    border: 2px solid #e8eae6;
    border-radius: 12px;
    padding: 30px;
    position: relative;
    transition: all 0.3s ease;
}

.plan-card:hover {
    border-color: var(--mls-accent, #737d5d);
    box-shadow: 0 8px 20px rgba(115, 125, 93, 0.15);
    transform: translateY(-5px);
}

.plan-card.current-plan {
    border-color: var(--mls-accent, #737d5d);
    background: linear-gradient(to bottom, rgba(115, 125, 93, 0.05) 0%, white 50%);
}

.plan-card.recommended {
    border-color: var(--mls-accent, #737d5d);
    border-width: 3px;
}

.plan-badge,
.current-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--mls-accent, #737d5d);
    color: white;
    padding: 4px 16px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.current-badge {
    background: var(--mls-grey-green, #8a9475);
}

.plan-header {
    text-align: center;
    margin-bottom: 25px;
}

.plan-header h3 {
    font-size: 22px;
    font-weight: 600;
    color: var(--mls-dark, #2c3e50);
    margin-bottom: 15px;
}

.plan-price {
    display: flex;
    align-items: baseline;
    justify-content: center;
    gap: 5px;
}

.plan-price .currency {
    font-size: 20px;
    font-weight: 600;
    color: var(--mls-accent, #737d5d);
}

.plan-price .amount {
    font-size: 42px;
    font-weight: 700;
    color: var(--mls-dark, #2c3e50);
}

.plan-price .period {
    font-size: 16px;
    color: #666;
}

.plan-features {
    margin-bottom: 30px;
}

.plan-features ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.plan-features li {
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.plan-features li:last-child {
    border-bottom: none;
}

.plan-features .dashicons {
    color: var(--mls-accent, #737d5d);
    font-size: 20px;
    flex-shrink: 0;
}

.plan-action {
    text-align: center;
}

.plan-action .button {
    width: 100%;
    padding: 12px 24px;
    font-size: 16px;
    font-weight: 600;
    border: none;
}

.plan-action .button-primary {
    background: var(--mls-accent, #737d5d);
    color: white;
}

.plan-action .button-primary:hover {
    background: var(--mls-dark, #2c3e50);
}

.plan-notice {
    margin-top: 10px;
    font-size: 13px;
    color: #666;
}

.stripe-not-configured {
    margin-top: 40px;
}

.notice-box {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 30px;
    border-radius: 8px;
    text-align: center;
}

.notice-box .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #ffc107;
    margin-bottom: 15px;
}

.notice-box h3 {
    font-size: 20px;
    color: var(--mls-dark, #2c3e50);
    margin-bottom: 10px;
}

.notice-box p {
    font-size: 15px;
    color: #666;
}

@media (max-width: 768px) {
    .plans-grid {
        grid-template-columns: 1fr;
    }
    
    .subscription-info-grid {
        grid-template-columns: 1fr;
    }
    
    .current-subscription-card {
        padding: 30px 20px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle plan selection
    $('.select-plan-btn').on('click', function() {
        var button = $(this);
        var plan = button.data('plan');
        var priceId = button.data('price-id');
        
        if (!priceId) {
            alert('<?php _e("This plan is not configured yet. Please contact support.", "malisafi-mls"); ?>');
            return;
        }
        
        button.prop('disabled', true).text('<?php _e("Processing...", "malisafi-mls"); ?>');
        
        // Create Stripe checkout session
        $.ajax({
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            type: 'POST',
            data: {
                action: 'malisafi_create_checkout',
                plan: plan,
                nonce: '<?php echo wp_create_nonce("malisafi_stripe_nonce"); ?>'
            },
            success: function(response) {
                if (response.success && response.data.url) {
                    window.location.href = response.data.url;
                } else {
                    alert(response.data.message || '<?php _e("Error creating checkout session.", "malisafi-mls"); ?>');
                    button.prop('disabled', false).text('<?php _e("Choose Plan", "malisafi-mls"); ?>');
                }
            },
            error: function() {
                alert('<?php _e("Error connecting to payment processor.", "malisafi-mls"); ?>');
                button.prop('disabled', false).text('<?php _e("Choose Plan", "malisafi-mls"); ?>');
            }
        });
    });
    
    // Handle manage billing button
    $('#manage-stripe-subscription').on('click', function() {
        var button = $(this);
        button.prop('disabled', true).find('.dashicons').addClass('dashicons-update spinning');
        
        $.ajax({
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            type: 'POST',
            data: {
                action: 'malisafi_create_portal',
                nonce: '<?php echo wp_create_nonce("malisafi_stripe_nonce"); ?>'
            },
            success: function(response) {
                if (response.success && response.data.url) {
                    window.location.href = response.data.url;
                } else {
                    alert(response.data.message || '<?php _e("Error accessing billing portal.", "malisafi-mls"); ?>');
                    button.prop('disabled', false).find('.dashicons').removeClass('dashicons-update spinning');
                }
            },
            error: function() {
                alert('<?php _e("Error connecting to billing portal.", "malisafi-mls"); ?>');
                button.prop('disabled', false).find('.dashicons').removeClass('dashicons-update spinning');
            }
        });
    });
});
</script>
