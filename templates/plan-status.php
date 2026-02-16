<?php
/**
 * Plan Status Display Template
 * Used by [malisafi_plan_status] shortcode
 *
 * @package MalisafiMLS
 */

if (!defined('ABSPATH')) {
    exit;
}

$compact_mode = $atts['compact'] === 'yes';
$show_upgrade = $atts['show_upgrade'] === 'yes';
$pricing_url = \MalisafiMLS\Page_Manager::get_page_url('pricing');
if (!$pricing_url) {
    $pricing_url = home_url('/pricing/');
}
?>

<div class="malisafi-plan-status-widget <?php echo $compact_mode ? 'compact' : ''; ?>">
    <?php if ($has_plan && $plan_details) : ?>
        <!-- User has an active plan -->
        <div class="plan-status-active">
            <?php if (!$compact_mode) : ?>
                <h3 class="plan-status-title">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php _e('Your Plan', 'malisafi-mls'); ?>
                </h3>
            <?php endif; ?>
            
            <div class="plan-details">
                <div class="plan-name">
                    <strong><?php echo esc_html($plan_details['name']); ?></strong>
                    <?php if (isset($plan_details['subscription_status'])) : ?>
                        <span class="plan-badge status-<?php echo esc_attr($plan_details['subscription_status']); ?>">
                            <?php echo esc_html(ucfirst($plan_details['subscription_status'])); ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <?php if (!$compact_mode) : ?>
                    <?php if (isset($plan_details['price'])) : ?>
                        <div class="plan-price">
                            <span class="amount">
                                <?php 
                                $currency = $plan_details['currency'] ?? 'USD';
                                $symbol = $currency === 'KES' ? 'KSh' : '$';
                                echo esc_html($symbol . number_format($plan_details['price'], 2));
                                ?>
                            </span>
                            <span class="interval">/<?php echo esc_html($plan_details['interval'] ?? 'month'); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($plan_details['current_period_end'])) : ?>
                        <div class="plan-expiry">
                            <span class="label"><?php _e('Renews on:', 'malisafi-mls'); ?></span>
                            <span class="date"><?php echo date_i18n(get_option('date_format'), strtotime($plan_details['current_period_end'])); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($plan_details['features'])) : ?>
                        <div class="plan-features">
                            <ul>
                                <?php 
                                $features = is_array($plan_details['features']) ? $plan_details['features'] : explode(',', $plan_details['features']);
                                foreach (array_slice($features, 0, 3) as $feature) : 
                                ?>
                                    <li><span class="dashicons dashicons-yes"></span> <?php echo esc_html(trim($feature)); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <?php if ($show_upgrade && !$compact_mode) : ?>
                <div class="plan-actions">
                    <a href="<?php echo esc_url($pricing_url); ?>" class="button button-secondary">
                        <?php _e('Upgrade Plan', 'malisafi-mls'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
    <?php else : ?>
        <!-- User has no plan - Suggest getting one -->
        <div class="plan-status-inactive">
            <?php if (!$compact_mode) : ?>
                <div class="no-plan-icon">
                    <span class="dashicons dashicons-warning"></span>
                </div>
                <h3 class="plan-status-title"><?php _e('No Active Plan', 'malisafi-mls'); ?></h3>
                <p class="plan-message">
                    <?php _e('You currently don\'t have an active subscription plan. Get started with one of our plans to unlock full features and start listing properties!', 'malisafi-mls'); ?>
                </p>
                <div class="plan-actions">
                    <a href="<?php echo esc_url($pricing_url); ?>" class="button button-primary button-large">
                        <?php _e('View Plans & Pricing', 'malisafi-mls'); ?>
                    </a>
                </div>
            <?php else : ?>
                <div class="plan-compact-alert">
                    <span class="dashicons dashicons-info"></span>
                    <span class="message"><?php _e('No active plan.', 'malisafi-mls'); ?></span>
                    <a href="<?php echo esc_url($pricing_url); ?>" class="upgrade-link">
                        <?php _e('Get a plan', 'malisafi-mls'); ?> →
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
