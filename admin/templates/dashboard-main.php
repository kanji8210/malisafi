<?php
/**
 * Main Dashboard Template
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
global $wpdb;
$statuses = array('publish','pending','draft');
$placeholders = implode(',', array_fill(0, count($statuses), '%s'));
$sql = $wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_author = %d AND post_status IN ($placeholders)",
    array_merge(array('malisafi_property', $current_user->ID), $statuses)
);
$total_properties = (int) $wpdb->get_var($sql);

// Check capabilities
$can_view_analytics = current_user_can('view_property_analytics');
$can_feature_properties = current_user_can('feature_properties');
$can_moderate = current_user_can('moderate_properties');
$is_admin = current_user_can('manage_malisafi_settings');

// Get pending moderation count
$pending_count = 0;
if ($can_moderate) {
    $pending_args = array(
        'post_type' => 'malisafi_property',
        'post_status' => 'pending',
        'posts_per_page' => -1,
        'fields' => 'ids'
    );
    $pending_query = new WP_Query($pending_args);
    $pending_count = $pending_query->found_posts;
    wp_reset_postdata();
}
?>

<div class="wrap malisafi-dashboard-wrap">
    <h1><?php _e('Malisafi Dashboard', 'malisafi-mls'); ?></h1>
    
    <!-- Welcome Section -->
    <div class="malisafi-welcome-panel">
        <div class="welcome-panel-content">
            <h2><?php printf(__('Welcome back, %s!', 'malisafi-mls'), esc_html($current_user->display_name)); ?></h2>
            <p class="about-description">
                <?php _e('Your current role:', 'malisafi-mls'); ?> 
                <strong><?php echo esc_html(implode(', ', array_map('ucfirst', $user_roles))); ?></strong>
            </p>
        </div>
    </div>
    
    <!-- Statistics Grid -->
    <div class="malisafi-stats-grid">
        <div class="malisafi-stat-card">
            <div class="stat-card-icon">
                <span class="dashicons dashicons-admin-home"></span>
            </div>
            <div class="stat-card-content">
                <h3><?php echo esc_html($total_properties); ?></h3>
                <p><?php _e('Total Properties', 'malisafi-mls'); ?></p>
            </div>
        </div>
        
        <div class="malisafi-stat-card">
            <div class="stat-card-icon published">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="stat-card-content">
                <h3><?php echo isset($user_properties->publish) ? esc_html($user_properties->publish) : '0'; ?></h3>
                <p><?php _e('Published', 'malisafi-mls'); ?></p>
            </div>
        </div>
        
        <?php if ($can_moderate && $pending_count > 0) : ?>
        <div class="malisafi-stat-card pending">
            <div class="stat-card-icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="stat-card-content">
                <h3><?php echo esc_html($pending_count); ?></h3>
                <p><?php _e('Pending Review', 'malisafi-mls'); ?></p>
            </div>
        </div>
        <?php else : ?>
        <div class="malisafi-stat-card">
            <div class="stat-card-icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="stat-card-content">
                <h3><?php echo isset($user_properties->pending) ? esc_html($user_properties->pending) : '0'; ?></h3>
                <p><?php _e('Pending', 'malisafi-mls'); ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="malisafi-stat-card">
            <div class="stat-card-icon draft">
                <span class="dashicons dashicons-edit"></span>
            </div>
            <div class="stat-card-content">
                <h3><?php echo isset($user_properties->draft) ? esc_html($user_properties->draft) : '0'; ?></h3>
                <p><?php _e('Drafts', 'malisafi-mls'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="malisafi-quick-actions-panel">
        <h2><?php _e('Quick Actions', 'malisafi-mls'); ?></h2>
        <div class="quick-actions-grid">
            <?php if (current_user_can('edit_properties')) : ?>
            <a href="<?php echo admin_url('post-new.php?post_type=malisafi_property'); ?>" class="quick-action-card">
                <span class="dashicons dashicons-plus-alt"></span>
                <span class="action-title"><?php _e('Add Property', 'malisafi-mls'); ?></span>
            </a>
            <?php endif; ?>
            
            <a href="<?php echo admin_url('edit.php?post_type=malisafi_property'); ?>" class="quick-action-card">
                <span class="dashicons dashicons-list-view"></span>
                <span class="action-title"><?php _e('All Properties', 'malisafi-mls'); ?></span>
            </a>
            
            <?php if ($can_moderate) : ?>
            <a href="<?php echo admin_url('admin.php?page=malisafi-moderation'); ?>" class="quick-action-card">
                <span class="dashicons dashicons-flag"></span>
                <span class="action-title"><?php _e('Moderation Queue', 'malisafi-mls'); ?></span>
            </a>
            <?php endif; ?>
            
            <?php if ($can_view_analytics) : ?>
            <a href="<?php echo admin_url('admin.php?page=malisafi-analytics'); ?>" class="quick-action-card">
                <span class="dashicons dashicons-chart-line"></span>
                <span class="action-title"><?php _e('Analytics', 'malisafi-mls'); ?></span>
            </a>
            <?php endif; ?>
            
            <?php if ($is_admin) : ?>
            <a href="<?php echo admin_url('admin.php?page=malisafi-users'); ?>" class="quick-action-card">
                <span class="dashicons dashicons-groups"></span>
                <span class="action-title"><?php _e('Users', 'malisafi-mls'); ?></span>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=malisafi-settings'); ?>" class="quick-action-card">
                <span class="dashicons dashicons-admin-settings"></span>
                <span class="action-title"><?php _e('Settings', 'malisafi-mls'); ?></span>
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="malisafi-recent-activity-panel">
        <h2><?php _e('Recent Properties', 'malisafi-mls'); ?></h2>
        <?php
        $recent_args = array(
            'post_type' => 'malisafi_property',
            'posts_per_page' => 5,
            'post_status' => array('publish', 'pending', 'draft'),
        );
        
        if (!$is_admin && !$can_moderate) {
            $recent_args['author'] = get_current_user_id();
        }
        
        $recent_query = new WP_Query($recent_args);
        
        if ($recent_query->have_posts()) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Property', 'malisafi-mls'); ?></th>
                        <th><?php _e('Status', 'malisafi-mls'); ?></th>
                        <th><?php _e('Author', 'malisafi-mls'); ?></th>
                        <th><?php _e('Date', 'malisafi-mls'); ?></th>
                        <th><?php _e('Actions', 'malisafi-mls'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($recent_query->have_posts()) : $recent_query->the_post(); ?>
                    <tr>
                        <td>
                            <strong><?php the_title(); ?></strong>
                        </td>
                        <td>
                            <?php
                            $status = get_post_status();
                            $status_label = ucfirst($status);
                            $status_class = 'status-' . $status;
                            ?>
                            <span class="status-badge <?php echo esc_attr($status_class); ?>">
                                <?php echo esc_html($status_label); ?>
                            </span>
                        </td>
                        <td><?php the_author(); ?></td>
                        <td><?php echo get_the_date(); ?></td>
                        <td>
                            <a href="<?php echo get_edit_post_link(); ?>" class="button button-small">
                                <?php _e('Edit', 'malisafi-mls'); ?>
                            </a>
                            <a href="<?php echo get_permalink(); ?>" class="button button-small" target="_blank">
                                <?php _e('View', 'malisafi-mls'); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p><?php _e('No properties found.', 'malisafi-mls'); ?></p>
        <?php endif; 
        wp_reset_postdata();
        ?>
    </div>
    
    <?php if ($can_feature_properties) : ?>
    <!-- Premium Features -->
    <div class="malisafi-premium-panel">
        <h2><?php _e('Premium Features', 'malisafi-mls'); ?></h2>
        <div class="premium-features-grid">
            <div class="premium-feature-card">
                <span class="dashicons dashicons-star-filled"></span>
                <h3><?php _e('Featured Listings', 'malisafi-mls'); ?></h3>
                <p><?php _e('Boost your properties visibility with featured status.', 'malisafi-mls'); ?></p>
            </div>
            <div class="premium-feature-card">
                <span class="dashicons dashicons-chart-line"></span>
                <h3><?php _e('Advanced Analytics', 'malisafi-mls'); ?></h3>
                <p><?php _e('Get detailed insights about your listings performance.', 'malisafi-mls'); ?></p>
            </div>
            <div class="premium-feature-card">
                <span class="dashicons dashicons-megaphone"></span>
                <h3><?php _e('Boost Listings', 'malisafi-mls'); ?></h3>
                <p><?php _e('Increase your reach with boosted advertising.', 'malisafi-mls'); ?></p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.malisafi-dashboard-wrap {
    margin: 20px 20px 20px 0;
}

.malisafi-welcome-panel {
    background: #fff;
    border: 1px solid #c3c4c7;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.malisafi-welcome-panel h2 {
    margin: 0 0 10px;
    font-size: 21px;
}

.malisafi-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.malisafi-stat-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.stat-card-icon {
    font-size: 40px;
    color: #2271b1;
    flex-shrink: 0;
}

.stat-card-icon.published {
    color: #00a32a;
}

.stat-card-icon.draft {
    color: #dba617;
}

.malisafi-stat-card.pending .stat-card-icon {
    color: #d63638;
}

.stat-card-icon .dashicons {
    width: 50px;
    height: 50px;
    font-size: 50px;
}

.stat-card-content h3 {
    margin: 0;
    font-size: 32px;
    color: #1d2327;
    line-height: 1;
}

.stat-card-content p {
    margin: 5px 0 0;
    color: #646970;
    font-size: 13px;
}

.malisafi-quick-actions-panel,
.malisafi-recent-activity-panel,
.malisafi-premium-panel {
    background: #fff;
    border: 1px solid #c3c4c7;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.quick-action-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
    border: 2px solid #c3c4c7;
    border-radius: 4px;
    text-decoration: none;
    color: #2c3338;
    transition: all 0.2s;
    min-height: 100px;
}

.quick-action-card:hover {
    border-color: #2271b1;
    background: #f6f7f7;
    color: #2271b1;
}

.quick-action-card .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
    margin-bottom: 10px;
}

.quick-action-card .action-title {
    font-weight: 600;
    text-align: center;
}

.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 600;
}

.status-badge.status-publish {
    background: #d7f5dd;
    color: #00702e;
}

.status-badge.status-pending {
    background: #fcf3cd;
    color: #735c0f;
}

.status-badge.status-draft {
    background: #e5e5e5;
    color: #50575e;
}

.premium-features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.premium-feature-card {
    padding: 20px;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    text-align: center;
}

.premium-feature-card .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #2271b1;
    margin-bottom: 10px;
}

.premium-feature-card h3 {
    margin: 10px 0;
    font-size: 16px;
}

.premium-feature-card p {
    margin: 0;
    color: #646970;
    font-size: 13px;
}
</style>
