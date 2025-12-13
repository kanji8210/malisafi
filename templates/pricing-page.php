<?php
/**
 * Pricing Page Template
 *
 * Shortcode: [malisafi_pricing]
 *
 * @package MalisafiMLS
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get plans
$plans = Malisafi_Stripe::get_plans();
$is_configured = Malisafi_Stripe::is_configured();
$current_user_id = get_current_user_id();

// Get user's current subscription if logged in
$user_subscription = null;
if ($current_user_id) {
    $user_subscription = Malisafi_Stripe::get_user_subscription($current_user_id);
}
?>

<div class="malisafi-pricing-section">
    
    <?php if (!$is_configured) : ?>
        <div class="pricing-notice">
            <p><?php _e('Subscriptions are not available at the moment. Please check back later.', 'malisafi-mls'); ?></p>
        </div>
        <?php return; ?>
    <?php endif; ?>
    
    <div class="pricing-header" style="text-align: center; margin-bottom: 50px;">
        <h1><?php _e('Choose Your Plan', 'malisafi-mls'); ?></h1>
        <p style="font-size: 18px; color: #666; max-width: 600px; margin: 20px auto;">
            <?php _e('Select the perfect subscription plan for your real estate business. All plans include essential features to help you succeed.', 'malisafi-mls'); ?>
        </p>
    </div>
    
    <div class="pricing-grid">
        
        <?php foreach ($plans as $plan_id => $plan) : ?>
            <?php
            $is_current_plan = $user_subscription && $user_subscription->plan_type === $plan_id;
            $plan_classes = ['pricing-card'];
            
            // Highlight recommended plan
            if ($plan_id === 'agent_premium') {
                $plan_classes[] = 'recommended';
            }
            
            if ($is_current_plan) {
                $plan_classes[] = 'current-plan';
            }
            ?>
            
            <div class="<?php echo implode(' ', $plan_classes); ?>" data-plan="<?php echo esc_attr($plan_id); ?>">
                
                <?php if ($plan_id === 'agent_premium') : ?>
                    <div class="recommended-badge">
                        <?php _e('Most Popular', 'malisafi-mls'); ?>
                    </div>
                <?php endif; ?>
                
                <div class="plan-header">
                    <h2><?php echo esc_html($plan['name']); ?></h2>
                    <div class="plan-price">
                        <span class="currency"><?php 
                            $currency = isset($plan['currency']) ? $plan['currency'] : 'USD';
                            echo esc_html(Malisafi_Stripe::get_currency_symbol($currency)); 
                        ?></span>
                        <span class="amount"><?php echo number_format($plan['price'], 0); ?></span>
                        <span class="period">/<?php echo esc_html($plan['interval']); ?></span>
                    </div>
                    <p class="plan-description"><?php echo esc_html($plan['description']); ?></p>
                </div>
                
                <div class="plan-features">
                    <ul>
                        <?php foreach ($plan['features'] as $feature) : ?>
                            <li>
                                <span class="feature-icon">✓</span>
                                <?php echo esc_html($feature); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="plan-action">
                    <?php if ($is_current_plan) : ?>
                        <button class="btn-current" disabled>
                            <?php _e('Current Plan', 'malisafi-mls'); ?>
                        </button>
                        <a href="<?php echo esc_url(home_url('/dashboard')); ?>" class="btn-manage">
                            <?php _e('Manage Subscription', 'malisafi-mls'); ?>
                        </a>
                    <?php elseif ($current_user_id) : ?>
                        <button class="btn-subscribe" data-plan="<?php echo esc_attr($plan_id); ?>">
                            <?php 
                            if ($user_subscription) {
                                _e('Switch Plan', 'malisafi-mls');
                            } else {
                                _e('Get Started', 'malisafi-mls');
                            }
                            ?>
                        </button>
                    <?php else : ?>
                        <a href="<?php echo wp_login_url(get_permalink()); ?>" class="btn-subscribe">
                            <?php _e('Login to Subscribe', 'malisafi-mls'); ?>
                        </a>
                    <?php endif; ?>
                </div>
                
            </div>
            
        <?php endforeach; ?>
        
    </div>
    
    <div class="pricing-faq" style="margin-top: 80px; max-width: 800px; margin-left: auto; margin-right: auto;">
        <h2 style="text-align: center; margin-bottom: 40px;"><?php _e('Frequently Asked Questions', 'malisafi-mls'); ?></h2>
        
        <div class="faq-item">
            <h3><?php _e('Can I change my plan later?', 'malisafi-mls'); ?></h3>
            <p><?php _e('Yes! You can upgrade or downgrade your plan at any time. Changes will be prorated automatically.', 'malisafi-mls'); ?></p>
        </div>
        
        <div class="faq-item">
            <h3><?php _e('What payment methods do you accept?', 'malisafi-mls'); ?></h3>
            <p><?php _e('We accept all major credit cards through Stripe, including Visa, Mastercard, American Express, and more.', 'malisafi-mls'); ?></p>
        </div>
        
        <div class="faq-item">
            <h3><?php _e('Can I cancel my subscription?', 'malisafi-mls'); ?></h3>
            <p><?php _e('Yes, you can cancel your subscription at any time from your dashboard. You\'ll retain access until the end of your billing period.', 'malisafi-mls'); ?></p>
        </div>
        
        <div class="faq-item">
            <h3><?php _e('Do you offer refunds?', 'malisafi-mls'); ?></h3>
            <p><?php _e('We offer a 14-day money-back guarantee on all plans. Contact support if you\'re not satisfied.', 'malisafi-mls'); ?></p>
        </div>
    </div>
    
</div>

<style>
.malisafi-pricing-section {
    padding: 40px 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.pricing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 30px;
    margin-bottom: 40px;
}

.pricing-card {
    background: white;
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 40px 30px;
    position: relative;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
}

.pricing-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.pricing-card.recommended {
    border-color: #2271b1;
    box-shadow: 0 5px 20px rgba(34, 113, 177, 0.2);
}

.pricing-card.current-plan {
    border-color: #00a32a;
    background: #f0f9f2;
}

.recommended-badge {
    position: absolute;
    top: -15px;
    left: 50%;
    transform: translateX(-50%);
    background: #2271b1;
    color: white;
    padding: 5px 20px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.plan-header {
    text-align: center;
    margin-bottom: 30px;
}

.plan-header h2 {
    font-size: 24px;
    margin-bottom: 15px;
    color: #1d2327;
}

.plan-price {
    font-size: 48px;
    font-weight: bold;
    color: #2271b1;
    margin: 20px 0;
    line-height: 1;
}

.plan-price .currency {
    font-size: 24px;
    vertical-align: top;
}

.plan-price .period {
    font-size: 18px;
    font-weight: normal;
    color: #666;
}

.plan-description {
    color: #666;
    font-size: 14px;
    margin-top: 10px;
}

.plan-features {
    flex-grow: 1;
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
    font-size: 15px;
    color: #1d2327;
}

.plan-features li:last-child {
    border-bottom: none;
}

.feature-icon {
    display: inline-block;
    width: 20px;
    height: 20px;
    background: #00a32a;
    color: white;
    border-radius: 50%;
    text-align: center;
    line-height: 20px;
    margin-right: 10px;
    font-size: 12px;
}

.plan-action {
    text-align: center;
}

.btn-subscribe,
.btn-current,
.btn-manage {
    display: inline-block;
    width: 100%;
    padding: 15px 30px;
    background: #2271b1;
    color: white;
    text-decoration: none;
    border: none;
    border-radius: 6px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-bottom: 10px;
}

.btn-subscribe:hover {
    background: #135e96;
    transform: translateY(-2px);
}

.btn-current {
    background: #00a32a;
    cursor: default;
}

.btn-manage {
    background: transparent;
    color: #2271b1;
    border: 2px solid #2271b1;
}

.btn-manage:hover {
    background: #2271b1;
    color: white;
}

.recommended .btn-subscribe {
    background: #2271b1;
}

.pricing-faq {
    padding: 40px 20px;
}

.faq-item {
    margin-bottom: 30px;
    padding-bottom: 30px;
    border-bottom: 1px solid #e0e0e0;
}

.faq-item:last-child {
    border-bottom: none;
}

.faq-item h3 {
    font-size: 18px;
    margin-bottom: 10px;
    color: #1d2327;
}

.faq-item p {
    color: #666;
    line-height: 1.6;
}

.pricing-notice {
    text-align: center;
    padding: 40px;
    background: #fff8e5;
    border: 1px solid #ffb900;
    border-radius: 8px;
    margin: 40px 0;
}

@media (max-width: 768px) {
    .pricing-grid {
        grid-template-columns: 1fr;
    }
    
    .plan-price {
        font-size: 36px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.btn-subscribe').on('click', function() {
        const plan = $(this).data('plan');
        const button = $(this);
        
        button.prop('disabled', true).text('<?php _e('Loading...', 'malisafi-mls'); ?>');
        
        $.ajax({
            url: malisafiAjax.ajaxurl,
            method: 'POST',
            data: {
                action: 'malisafi_create_checkout',
                nonce: malisafiAjax.nonce,
                plan: plan
            },
            success: function(response) {
                if (response.success && response.data.url) {
                    window.location.href = response.data.url;
                } else {
                    alert(response.data.message || '<?php _e('An error occurred. Please try again.', 'malisafi-mls'); ?>');
                    button.prop('disabled', false).text('<?php _e('Get Started', 'malisafi-mls'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('An error occurred. Please try again.', 'malisafi-mls'); ?>');
                button.prop('disabled', false).text('<?php _e('Get Started', 'malisafi-mls'); ?>');
            }
        });
    });
});
</script>
