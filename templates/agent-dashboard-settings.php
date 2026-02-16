<?php
/**
 * Agent Dashboard - Settings Section
 *
 * @package MalisafiMLS
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current user settings
$user_id = get_current_user_id();
$settings = get_user_meta($user_id, 'malisafi_agent_settings', true);

// Default settings
$defaults = [
    'display_mode' => 'light',
    'language' => 'en',
    'date_format' => 'd/m/Y',
    'currency' => 'KES',
    'units' => 'metric',
    'properties_view' => 'grid',
    'properties_sort' => 'date_desc',
    'properties_per_page' => 12,
    'notify_new_lead' => 'yes',
    'notify_property_approved' => 'yes',
    'notify_property_rejected' => 'yes',
    'notify_limit_reached' => 'yes',
    'notify_weekly_report' => 'yes',
    'notify_favorites' => 'no',
    'show_phone_public' => 'yes',
    'show_email_public' => 'no',
    'profile_searchable' => 'yes',
    'accept_direct_messages' => 'yes',
    'analytics_period' => '30',
    'show_stats_overview' => 'yes',
    'show_stats_leads' => 'yes',
    'show_stats_views' => 'yes'
];

$settings = wp_parse_args($settings, $defaults);

// Get subscription info
global $wpdb;
$subscription = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}mf_subscriptions WHERE user_id = %d ORDER BY created_at DESC LIMIT 1",
    $user_id
));

// Get current limits
$limits = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}mf_user_limits WHERE user_id = %d",
    $user_id
));

// Count user's properties for display
$property_count = count(get_posts([
    'post_type' => 'malisafi_property',
    'author' => $user_id,
    'post_status' => ['publish', 'pending', 'draft'],
    'posts_per_page' => -1,
    'fields' => 'ids'
]));
?>

<div class="dashboard-settings">
    <div class="settings-header">
        <h1><?php _e('Settings', 'malisafi-mls'); ?></h1>
        <p class="subtitle"><?php _e('Configure your dashboard preferences and account options', 'malisafi-mls'); ?></p>
    </div>

    <!-- Subscription Info Banner -->
    <?php if ($subscription && $subscription->status === 'active') : ?>
        <div class="subscription-info-banner">
            <div class="banner-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="banner-content">
                <h3><?php _e('Active Subscription', 'malisafi-mls'); ?></h3>
                <p>
                    <?php 
                    $plan_names = [
                        'agent_basic' => __('Agent Basic', 'malisafi-mls'),
                        'agent_premium' => __('Agent Premium', 'malisafi-mls'),
                        'owner_basic' => __('Owner Basic', 'malisafi-mls'),
                        'developer' => __('Developer', 'malisafi-mls')
                    ];
                    echo sprintf(
                        __('You are on the %s plan', 'malisafi-mls'),
                        '<strong>' . esc_html($plan_names[$subscription->plan_type] ?? ucwords(str_replace('_', ' ', $subscription->plan_type))) . '</strong>'
                    );
                    if ($limits && $limits->max_properties != -1) {
                        echo ' • ' . sprintf(__('%d of %d properties used', 'malisafi-mls'), esc_html($property_count), esc_html($limits->max_properties));
                    }
                    ?>
                </p>
            </div>
            <div class="banner-action">
                <a href="<?php echo esc_url(add_query_arg('section', 'subscription')); ?>" class="button button-primary">
                    <?php _e('Manage Subscription', 'malisafi-mls'); ?>
                </a>
            </div>
        </div>
    <?php else : ?>
        <div class="subscription-info-banner no-subscription">
            <div class="banner-icon">
                <span class="dashicons dashicons-info"></span>
            </div>
            <div class="banner-content">
                <h3><?php _e('No Active Subscription', 'malisafi-mls'); ?></h3>
                <p><?php _e('Subscribe to a plan to unlock full features and list properties.', 'malisafi-mls'); ?></p>
            </div>
            <div class="banner-action">
                <a href="<?php echo esc_url(add_query_arg('section', 'subscription')); ?>" class="button button-primary">
                    <?php _e('View Plans', 'malisafi-mls'); ?>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <form id="agentSettingsForm" class="settings-form">
        <?php wp_nonce_field('malisafi_save_agent_settings', 'settings_nonce'); ?>

        <!-- Display Preferences -->
        <div class="settings-section">
            <div class="section-header">
                <span class="dashicons dashicons-admin-appearance"></span>
                <h2><?php _e('Display Preferences', 'malisafi-mls'); ?></h2>
            </div>
            <div class="section-content">
                <div class="settings-row">
                    <label for="display_mode"><?php _e('Display Mode', 'malisafi-mls'); ?></label>
                    <select name="display_mode" id="display_mode">
                        <option value="light" <?php selected($settings['display_mode'], 'light'); ?>><?php _e('Light Mode', 'malisafi-mls'); ?></option>
                        <option value="dark" <?php selected($settings['display_mode'], 'dark'); ?>><?php _e('Dark Mode (Coming Soon)', 'malisafi-mls'); ?> 🌙</option>
                    </select>
                </div>

                <div class="settings-row">
                    <label for="language"><?php _e('Language', 'malisafi-mls'); ?></label>
                    <select name="language" id="language">
                        <option value="en" <?php selected($settings['language'], 'en'); ?>>English</option>
                        <option value="sw" <?php selected($settings['language'], 'sw'); ?>>Kiswahili</option>
                        <option value="fr" <?php selected($settings['language'], 'fr'); ?>>Français</option>
                    </select>
                </div>

                <div class="settings-row">
                    <label for="date_format"><?php _e('Date Format', 'malisafi-mls'); ?></label>
                    <select name="date_format" id="date_format">
                        <option value="d/m/Y" <?php selected($settings['date_format'], 'd/m/Y'); ?>>DD/MM/YYYY</option>
                        <option value="m/d/Y" <?php selected($settings['date_format'], 'm/d/Y'); ?>>MM/DD/YYYY</option>
                        <option value="Y-m-d" <?php selected($settings['date_format'], 'Y-m-d'); ?>>YYYY-MM-DD</option>
                    </select>
                </div>

                <div class="settings-row">
                    <label for="currency"><?php _e('Currency', 'malisafi-mls'); ?></label>
                    <select name="currency" id="currency">
                        <option value="KES" <?php selected($settings['currency'], 'KES'); ?>>KES (Kenyan Shilling)</option>
                        <option value="USD" <?php selected($settings['currency'], 'USD'); ?>>USD (US Dollar)</option>
                        <option value="EUR" <?php selected($settings['currency'], 'EUR'); ?>>EUR (Euro)</option>
                    </select>
                </div>

                <div class="settings-row">
                    <label for="units"><?php _e('Units', 'malisafi-mls'); ?></label>
                    <select name="units" id="units">
                        <option value="metric" <?php selected($settings['units'], 'metric'); ?>><?php _e('Metric (m², km)', 'malisafi-mls'); ?></option>
                        <option value="imperial" <?php selected($settings['units'], 'imperial'); ?>><?php _e('Imperial (sqft, miles)', 'malisafi-mls'); ?></option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Property Listing Preferences -->
        <div class="settings-section">
            <div class="section-header">
                <span class="dashicons dashicons-admin-home"></span>
                <h2><?php _e('Property Listing Preferences', 'malisafi-mls'); ?></h2>
            </div>
            <div class="section-content">
                <div class="settings-row">
                    <label for="properties_view"><?php _e('Default View', 'malisafi-mls'); ?></label>
                    <select name="properties_view" id="properties_view">
                        <option value="grid" <?php selected($settings['properties_view'], 'grid'); ?>><?php _e('Grid View', 'malisafi-mls'); ?></option>
                        <option value="list" <?php selected($settings['properties_view'], 'list'); ?>><?php _e('List View', 'malisafi-mls'); ?></option>
                    </select>
                </div>

                <div class="settings-row">
                    <label for="properties_sort"><?php _e('Default Sort', 'malisafi-mls'); ?></label>
                    <select name="properties_sort" id="properties_sort">
                        <option value="date_desc" <?php selected($settings['properties_sort'], 'date_desc'); ?>><?php _e('Newest First', 'malisafi-mls'); ?></option>
                        <option value="date_asc" <?php selected($settings['properties_sort'], 'date_asc'); ?>><?php _e('Oldest First', 'malisafi-mls'); ?></option>
                        <option value="price_desc" <?php selected($settings['properties_sort'], 'price_desc'); ?>><?php _e('Price: High to Low', 'malisafi-mls'); ?></option>
                        <option value="price_asc" <?php selected($settings['properties_sort'], 'price_asc'); ?>><?php _e('Price: Low to High', 'malisafi-mls'); ?></option>
                    </select>
                </div>

                <div class="settings-row">
                    <label for="properties_per_page"><?php _e('Properties Per Page', 'malisafi-mls'); ?></label>
                    <select name="properties_per_page" id="properties_per_page">
                        <option value="6" <?php selected($settings['properties_per_page'], 6); ?>>6</option>
                        <option value="12" <?php selected($settings['properties_per_page'], 12); ?>>12</option>
                        <option value="24" <?php selected($settings['properties_per_page'], 24); ?>>24</option>
                        <option value="48" <?php selected($settings['properties_per_page'], 48); ?>>48</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Email Notifications -->
        <div class="settings-section">
            <div class="section-header">
                <span class="dashicons dashicons-email"></span>
                <h2><?php _e('Email Notifications', 'malisafi-mls'); ?></h2>
            </div>
            <div class="section-content">
                <div class="settings-row toggle-row">
                    <label for="notify_new_lead">
                        <span><?php _e('New Lead Received', 'malisafi-mls'); ?></span>
                        <small><?php _e('Get notified when someone inquires about your property', 'malisafi-mls'); ?></small>
                    </label>
                    <label class="toggle-switch">
                        <input type="checkbox" name="notify_new_lead" id="notify_new_lead" value="yes" <?php checked($settings['notify_new_lead'], 'yes'); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="settings-row toggle-row">
                    <label for="notify_property_approved">
                        <span><?php _e('Property Approved', 'malisafi-mls'); ?></span>
                        <small><?php _e('Notification when your property is approved and published', 'malisafi-mls'); ?></small>
                    </label>
                    <label class="toggle-switch">
                        <input type="checkbox" name="notify_property_approved" id="notify_property_approved" value="yes" <?php checked($settings['notify_property_approved'], 'yes'); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="settings-row toggle-row">
                    <label for="notify_property_rejected">
                        <span><?php _e('Property Rejected', 'malisafi-mls'); ?></span>
                        <small><?php _e('Notification when your property submission needs revision', 'malisafi-mls'); ?></small>
                    </label>
                    <label class="toggle-switch">
                        <input type="checkbox" name="notify_property_rejected" id="notify_property_rejected" value="yes" <?php checked($settings['notify_property_rejected'], 'yes'); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="settings-row toggle-row">
                    <label for="notify_limit_reached">
                        <span><?php _e('Listing Limit Reached', 'malisafi-mls'); ?></span>
                        <small><?php _e('Alert when you approach or reach your subscription limit', 'malisafi-mls'); ?></small>
                    </label>
                    <label class="toggle-switch">
                        <input type="checkbox" name="notify_limit_reached" id="notify_limit_reached" value="yes" <?php checked($settings['notify_limit_reached'], 'yes'); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="settings-row toggle-row">
                    <label for="notify_weekly_report">
                        <span><?php _e('Weekly Activity Report', 'malisafi-mls'); ?></span>
                        <small><?php _e('Receive a summary of views, leads, and activity every Monday', 'malisafi-mls'); ?></small>
                    </label>
                    <label class="toggle-switch">
                        <input type="checkbox" name="notify_weekly_report" id="notify_weekly_report" value="yes" <?php checked($settings['notify_weekly_report'], 'yes'); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="settings-row toggle-row">
                    <label for="notify_favorites">
                        <span><?php _e('Property Favorited', 'malisafi-mls'); ?></span>
                        <small><?php _e('Know when users add your properties to favorites', 'malisafi-mls'); ?></small>
                    </label>
                    <label class="toggle-switch">
                        <input type="checkbox" name="notify_favorites" id="notify_favorites" value="yes" <?php checked($settings['notify_favorites'], 'yes'); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Privacy Settings -->
        <div class="settings-section">
            <div class="section-header">
                <span class="dashicons dashicons-lock"></span>
                <h2><?php _e('Privacy & Visibility', 'malisafi-mls'); ?></h2>
            </div>
            <div class="section-content">
                <div class="settings-row toggle-row">
                    <label for="show_phone_public">
                        <span><?php _e('Show Phone Number Publicly', 'malisafi-mls'); ?></span>
                        <small><?php _e('Display your phone number on property listings', 'malisafi-mls'); ?></small>
                    </label>
                    <label class="toggle-switch">
                        <input type="checkbox" name="show_phone_public" id="show_phone_public" value="yes" <?php checked($settings['show_phone_public'], 'yes'); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="settings-row toggle-row">
                    <label for="show_email_public">
                        <span><?php _e('Show Email Publicly', 'malisafi-mls'); ?></span>
                        <small><?php _e('Display your email address on property listings', 'malisafi-mls'); ?></small>
                    </label>
                    <label class="toggle-switch">
                        <input type="checkbox" name="show_email_public" id="show_email_public" value="yes" <?php checked($settings['show_email_public'], 'yes'); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="settings-row toggle-row">
                    <label for="profile_searchable">
                        <span><?php _e('Profile Searchable', 'malisafi-mls'); ?></span>
                        <small><?php _e('Allow search engines to index your agent profile', 'malisafi-mls'); ?></small>
                    </label>
                    <label class="toggle-switch">
                        <input type="checkbox" name="profile_searchable" id="profile_searchable" value="yes" <?php checked($settings['profile_searchable'], 'yes'); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="settings-row toggle-row">
                    <label for="accept_direct_messages">
                        <span><?php _e('Accept Direct Messages', 'malisafi-mls'); ?></span>
                        <small><?php _e('Allow users to send you direct messages', 'malisafi-mls'); ?></small>
                    </label>
                    <label class="toggle-switch">
                        <input type="checkbox" name="accept_direct_messages" id="accept_direct_messages" value="yes" <?php checked($settings['accept_direct_messages'], 'yes'); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Subscription Info (Read-only) -->
        <div class="settings-section">
            <div class="section-header">
                <span class="dashicons dashicons-admin-generic"></span>
                <h2><?php _e('Subscription & Limits', 'malisafi-mls'); ?></h2>
            </div>
            <div class="section-content">
                <?php if ($subscription): ?>
                    <div class="subscription-info">
                        <div class="info-row">
                            <span class="label"><?php _e('Current Plan:', 'malisafi-mls'); ?></span>
                            <span class="value plan-badge plan-<?php echo esc_attr($subscription->plan_type); ?>">
                                <?php echo esc_html(ucwords(str_replace('_', ' ', $subscription->plan_type))); ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="label"><?php _e('Status:', 'malisafi-mls'); ?></span>
                            <span class="value status-<?php echo esc_attr($subscription->status); ?>">
                                <?php echo esc_html(ucfirst($subscription->status)); ?>
                            </span>
                        </div>
                        <?php if ($limits): ?>
                            <div class="info-row">
                                <span class="label"><?php _e('Active Properties:', 'malisafi-mls'); ?></span>
                                <span class="value">
                                    <?php echo esc_html($limits->used_listings ?? 0); ?> / 
                                    <?php echo ($limits->max_listings ?? 0) === 999999 ? '∞' : esc_html($limits->max_listings ?? 0); ?>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="label"><?php _e('Featured Slots:', 'malisafi-mls'); ?></span>
                                <span class="value">
                                    <?php 
                                    $featured_count = $wpdb->get_var($wpdb->prepare(
                                        "SELECT COUNT(*) FROM {$wpdb->posts} p 
                                        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
                                        WHERE p.post_type = 'malisafi_property' 
                                        AND p.post_author = %d 
                                        AND p.post_status = 'publish'
                                        AND pm.meta_key = '_malisafi_featured' 
                                        AND pm.meta_value = '1'",
                                        $user_id
                                    ));
                                    echo esc_html($featured_count); 
                                    ?> / 
                                    <?php echo ($limits->featured_listings ?? 0) === 999999 ? '∞' : esc_html($limits->featured_listings ?? 0); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        <div class="info-row">
                            <a href="<?php echo esc_url(\MalisafiMLS\Page_Manager::get_page_url('pricing')); ?>" class="button button-secondary">
                                <?php _e('View Plans & Upgrade', 'malisafi-mls'); ?>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="no-subscription">
                        <?php _e('No active subscription found.', 'malisafi-mls'); ?>
                        <a href="<?php echo esc_url(\MalisafiMLS\Page_Manager::get_page_url('pricing')); ?>">
                            <?php _e('Subscribe Now', 'malisafi-mls'); ?>
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Analytics Preferences -->
        <div class="settings-section">
            <div class="section-header">
                <span class="dashicons dashicons-chart-area"></span>
                <h2><?php _e('Analytics & Dashboard', 'malisafi-mls'); ?></h2>
            </div>
            <div class="section-content">
                <div class="settings-row">
                    <label for="analytics_period"><?php _e('Default Stats Period', 'malisafi-mls'); ?></label>
                    <select name="analytics_period" id="analytics_period">
                        <option value="7" <?php selected($settings['analytics_period'], '7'); ?>><?php _e('Last 7 Days', 'malisafi-mls'); ?></option>
                        <option value="30" <?php selected($settings['analytics_period'], '30'); ?>><?php _e('Last 30 Days', 'malisafi-mls'); ?></option>
                        <option value="90" <?php selected($settings['analytics_period'], '90'); ?>><?php _e('Last 90 Days', 'malisafi-mls'); ?></option>
                    </select>
                </div>

                <div class="settings-row toggle-row">
                    <label for="show_stats_overview">
                        <span><?php _e('Show Overview Stats', 'malisafi-mls'); ?></span>
                        <small><?php _e('Display summary cards on dashboard home', 'malisafi-mls'); ?></small>
                    </label>
                    <label class="toggle-switch">
                        <input type="checkbox" name="show_stats_overview" id="show_stats_overview" value="yes" <?php checked($settings['show_stats_overview'], 'yes'); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="settings-row toggle-row">
                    <label for="show_stats_leads">
                        <span><?php _e('Show Lead Statistics', 'malisafi-mls'); ?></span>
                        <small><?php _e('Display lead conversion metrics', 'malisafi-mls'); ?></small>
                    </label>
                    <label class="toggle-switch">
                        <input type="checkbox" name="show_stats_leads" id="show_stats_leads" value="yes" <?php checked($settings['show_stats_leads'], 'yes'); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="settings-row toggle-row">
                    <label for="show_stats_views">
                        <span><?php _e('Show View Statistics', 'malisafi-mls'); ?></span>
                        <small><?php _e('Display property view counts and trends', 'malisafi-mls'); ?></small>
                    </label>
                    <label class="toggle-switch">
                        <input type="checkbox" name="show_stats_views" id="show_stats_views" value="yes" <?php checked($settings['show_stats_views'], 'yes'); ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="settings-actions">
            <button type="submit" class="button button-primary button-large">
                <span class="dashicons dashicons-saved"></span>
                <?php _e('Save All Settings', 'malisafi-mls'); ?>
            </button>
            <span class="save-status"></span>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('#agentSettingsForm').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $button = $form.find('button[type="submit"]');
        const $status = $('.save-status');
        
        // Disable button
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php esc_html_e('Saving...', 'malisafi-mls'); ?>');
        
        // Collect form data
        const formData = new FormData(this);
        formData.append('action', 'malisafi_save_agent_settings');
        
        // Add unchecked checkboxes as 'no'
        $('input[type="checkbox"]', $form).each(function() {
            if (!this.checked) {
                formData.set(this.name, 'no');
            }
        });
        
        $.ajax({
            url: malisafiAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $status.html('<span class="success">✓ ' + response.data.message + '</span>').fadeIn();
                    setTimeout(() => $status.fadeOut(), 3000);
                } else {
                    $status.html('<span class="error">✗ ' + response.data.message + '</span>').fadeIn();
                }
            },
            error: function() {
                $status.html('<span class="error">✗ <?php esc_html_e('Error saving settings', 'malisafi-mls'); ?></span>').fadeIn();
            },
            complete: function() {
                $button.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> <?php esc_html_e('Save All Settings', 'malisafi-mls'); ?>');
            }
        });
    });
});
</script>
