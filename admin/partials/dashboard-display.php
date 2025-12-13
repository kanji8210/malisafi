<?php
/**
 * Dashboard page
 *
 * @package MalisafiMLS
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$user_roles = $current_user->roles;

// Get user's properties count
$user_properties = wp_count_posts('malisafi_property');
$total_properties = 0;
if ($user_properties) {
    $total_properties = $user_properties->publish + $user_properties->draft + $user_properties->pending;
}

// Check capabilities
$can_view_analytics = current_user_can('view_property_analytics');
$can_feature_properties = current_user_can('feature_properties');
$can_moderate = current_user_can('moderate_properties');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="malisafi-dashboard">
        <div class="malisafi-welcome-panel">
            <h2><?php printf(__('Welcome, %s', 'malisafi-mls'), esc_html($current_user->display_name)); ?></h2>
            <p><?php _e('Your current role:', 'malisafi-mls'); ?> 
                <strong><?php echo esc_html(implode(', ', $user_roles)); ?></strong>
            </p>
        </div>
        
        <div class="malisafi-stats-grid">
            <!-- Properties Stats -->
            <div class="malisafi-stat-box">
                <div class="stat-icon">
                    <span class="dashicons dashicons-admin-home"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html($total_properties); ?></h3>
                    <p><?php _e('Total Properties', 'malisafi-mls'); ?></p>
                </div>
            </div>
            
            <!-- Published Properties -->
            <div class="malisafi-stat-box">
                <div class="stat-icon">
                    <span class="dashicons dashicons-yes"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo isset($user_properties->publish) ? esc_html($user_properties->publish) : '0'; ?></h3>
                    <p><?php _e('Published', 'malisafi-mls'); ?></p>
                </div>
            </div>
            
            <!-- Pending Properties -->
            <div class="malisafi-stat-box">
                <div class="stat-icon">
                    <span class="dashicons dashicons-clock"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo isset($user_properties->pending) ? esc_html($user_properties->pending) : '0'; ?></h3>
                    <p><?php _e('Pending Review', 'malisafi-mls'); ?></p>
                </div>
            </div>
            
            <!-- Draft Properties -->
            <div class="malisafi-stat-box">
                <div class="stat-icon">
                    <span class="dashicons dashicons-edit"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo isset($user_properties->draft) ? esc_html($user_properties->draft) : '0'; ?></h3>
                    <p><?php _e('Drafts', 'malisafi-mls'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="malisafi-quick-actions">
            <h2><?php _e('Quick Actions', 'malisafi-mls'); ?></h2>
            <div class="action-buttons">
                <?php if (current_user_can('edit_properties')) : ?>
                    <a href="<?php echo admin_url('post-new.php?post_type=malisafi_property'); ?>" class="button button-primary">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Add New Property', 'malisafi-mls'); ?>
                    </a>
                <?php endif; ?>
                
                <a href="<?php echo admin_url('edit.php?post_type=malisafi_property'); ?>" class="button">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php _e('View All Properties', 'malisafi-mls'); ?>
                </a>
                
                <?php if ($can_moderate) : ?>
                    <a href="<?php echo admin_url('edit.php?post_status=pending&post_type=malisafi_property'); ?>" class="button">
                        <span class="dashicons dashicons-flag"></span>
                        <?php _e('Moderate Properties', 'malisafi-mls'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($can_view_analytics) : ?>
        <!-- Analytics Section -->
        <div class="malisafi-analytics">
            <h2><?php _e('Analytics Overview', 'malisafi-mls'); ?></h2>
            <p><?php _e('View detailed analytics for your properties.', 'malisafi-mls'); ?></p>
            <!-- Add analytics widgets here -->
        </div>
        <?php endif; ?>
        
        <?php if ($can_feature_properties) : ?>
        <!-- Premium Features -->
        <div class="malisafi-premium-features">
            <h2><?php _e('Premium Features', 'malisafi-mls'); ?></h2>
            <div class="feature-list">
                <div class="feature-item">
                    <span class="dashicons dashicons-star-filled"></span>
                    <span><?php _e('Feature your properties for better visibility', 'malisafi-mls'); ?></span>
                </div>
                <div class="feature-item">
                    <span class="dashicons dashicons-chart-line"></span>
                    <span><?php _e('Advanced analytics and insights', 'malisafi-mls'); ?></span>
                </div>
                <div class="feature-item">
                    <span class="dashicons dashicons-megaphone"></span>
                    <span><?php _e('Boost your listings to reach more buyers', 'malisafi-mls'); ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.malisafi-dashboard {
    margin-top: 20px;
}

.malisafi-welcome-panel {
    background: #fff;
    border: 1px solid #c3c4c7;
    padding: 20px;
    margin-bottom: 20px;
}

.malisafi-welcome-panel h2 {
    margin-top: 0;
}

.malisafi-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.malisafi-stat-box {
    background: #fff;
    border: 1px solid #c3c4c7;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    font-size: 40px;
    color: #2271b1;
}

.stat-icon .dashicons {
    width: 50px;
    height: 50px;
    font-size: 50px;
}

.stat-content h3 {
    margin: 0;
    font-size: 32px;
    color: #1d2327;
}

.stat-content p {
    margin: 5px 0 0 0;
    color: #646970;
}

.malisafi-quick-actions,
.malisafi-analytics,
.malisafi-premium-features {
    background: #fff;
    border: 1px solid #c3c4c7;
    padding: 20px;
    margin-bottom: 20px;
}

.malisafi-quick-actions h2,
.malisafi-analytics h2,
.malisafi-premium-features h2 {
    margin-top: 0;
}

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.action-buttons .button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.feature-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.feature-item .dashicons {
    color: #2271b1;
    font-size: 24px;
    width: 24px;
    height: 24px;
}
</style>
