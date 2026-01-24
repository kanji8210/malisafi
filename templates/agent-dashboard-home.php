<?php
/**
 * Agent Dashboard Home Section
 *
 * @package MalisafiMLS
 */

if (!defined('ABSPATH')) {
    exit;
}

// Debug: Vérifier si les variables sont définies
if (!isset($total_properties)) {
    error_log('MALISAFI DEBUG: $total_properties not defined in agent-dashboard-home.php');
    $total_properties = 0;
}
if (!isset($published)) {
    error_log('MALISAFI DEBUG: $published not defined in agent-dashboard-home.php');
    $published = 0;
}
if (!isset($pending)) {
    error_log('MALISAFI DEBUG: $pending not defined in agent-dashboard-home.php');
    $pending = 0;
}
if (!isset($recent_properties)) {
    error_log('MALISAFI DEBUG: $recent_properties not defined in agent-dashboard-home.php');
    $recent_properties = [];
}

// Debug: Log actual values
error_log('MALISAFI DEBUG: Dashboard stats - Total: ' . $total_properties . ', Published: ' . $published . ', Pending: ' . $pending);
?>

<div class="dashboard-home">
    <div class="dashboard-header">
        <h1><?php printf(__('Welcome back, %s', 'malisafi-mls'), $current_user->display_name); ?></h1>
        <p class="subtitle"><?php _e('Here\'s an overview of your property listings and leads', 'malisafi-mls'); ?></p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-icon">
                <span class="dashicons dashicons-admin-home"></span>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $total_properties; ?></div>
                <div class="stat-label"><?php _e('Total Properties', 'malisafi-mls'); ?></div>
            </div>
        </div>

        <div class="stat-card success">
            <div class="stat-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $published; ?></div>
                <div class="stat-label"><?php _e('Published', 'malisafi-mls'); ?></div>
            </div>
        </div>

        <div class="stat-card warning">
            <div class="stat-icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $pending; ?></div>
                <div class="stat-label"><?php _e('Pending Approval', 'malisafi-mls'); ?></div>
            </div>
        </div>

        <div class="stat-card info">
            <div class="stat-icon">
                <span class="dashicons dashicons-visibility"></span>
            </div>
            <div class="stat-content">
                <div class="stat-value">
                    <?php
                    $total_views = 0;
                    foreach ($recent_properties as $prop) {
                        $views = get_post_meta($prop->ID, '_property_views', true);
                        $total_views += (int) $views;
                    }
                    echo $total_views;
                    ?>
                </div>
                <div class="stat-label"><?php _e('Total Views', 'malisafi-mls'); ?></div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <h2><?php _e('Quick Actions', 'malisafi-mls'); ?></h2>
        <div class="actions-grid">
            <a href="<?php echo esc_url(\MalisafiMLS\Page_Manager::get_page_url('agent_add_property')); ?>" class="action-card primary">
                <span class="dashicons dashicons-plus-alt"></span>
                <span><?php _e('Add New Property', 'malisafi-mls'); ?></span>
            </a>
            <a href="<?php echo esc_url(add_query_arg('section', 'properties')); ?>" class="action-card">
                <span class="dashicons dashicons-admin-home"></span>
                <span><?php _e('Manage Properties', 'malisafi-mls'); ?></span>
            </a>
            <a href="<?php echo esc_url(add_query_arg('section', 'leads')); ?>" class="action-card">
                <span class="dashicons dashicons-email"></span>
                <span><?php _e('View Leads', 'malisafi-mls'); ?></span>
            </a>
            <a href="<?php echo esc_url(add_query_arg('section', 'profile')); ?>" class="action-card">
                <span class="dashicons dashicons-businessman"></span>
                <span><?php _e('Edit Profile', 'malisafi-mls'); ?></span>
            </a>
        </div>
    </div>

    <!-- Recent Properties -->
    <div class="recent-properties">
        <div class="section-header">
            <h2><?php _e('Recent Properties', 'malisafi-mls'); ?></h2>
            <a href="<?php echo esc_url(add_query_arg('section', 'properties')); ?>" class="button">
                <?php _e('View All', 'malisafi-mls'); ?>
            </a>
        </div>

        <?php if (!empty($recent_properties)): ?>
            <div class="properties-list">
                <?php foreach ($recent_properties as $property): ?>
                    <div class="property-item">
                        <div class="property-thumbnail">
                            <?php 
                            $images = get_post_meta($property->ID, '_property_images', true);
                            if (!empty($images) && is_array($images)) {
                                echo wp_get_attachment_image($images[0], 'thumbnail');
                            } else {
                                echo '<div class="placeholder-img"><span class="dashicons dashicons-admin-home"></span></div>';
                            }
                            
                            // Add status badge - always show
                            $property_status = get_post_meta($property->ID, '_malisafi_status', true);
                            if (!empty($property_status)) {
                                $status_display = ucwords(str_replace('-', ' ', $property_status));
                                $status_class = 'status-' . sanitize_html_class(strtolower(str_replace(' ', '-', $property_status)));
                            } else {
                                $status_display = 'Status Not Recorded';
                                $status_class = 'status-not-recorded';
                            }
                            echo '<span class="status-badge ' . esc_attr($status_class) . '">' . esc_html($status_display) . '</span>';
                            ?>
                        </div>
                        <div class="property-info">
                            <h3><?php echo esc_html($property->post_title); ?></h3>
                            <div class="property-meta">
                                <span class="status status-<?php echo $property->post_status; ?>">
                                    <?php echo ucfirst($property->post_status); ?>
                                </span>
                                <span class="date">
                                    <?php echo human_time_diff(strtotime($property->post_date), current_time('timestamp')) . ' ' . __('ago', 'malisafi-mls'); ?>
                                </span>
                            </div>
                        </div>
                        <div class="property-actions">
                            <a href="<?php echo get_permalink($property->ID); ?>" class="button button-small" target="_blank">
                                <?php _e('View', 'malisafi-mls'); ?>
                            </a>
                            <a href="<?php echo admin_url('admin.php?page=malisafi-property-edit&property_id=' . $property->ID); ?>" class="button button-small">
                                <?php _e('Edit', 'malisafi-mls'); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <span class="dashicons dashicons-admin-home"></span>
                <p><?php _e('You haven\'t listed any properties yet.', 'malisafi-mls'); ?></p>
                <a href="<?php echo esc_url(\MalisafiMLS\Page_Manager::get_page_url('agent_add_property')); ?>" class="button button-primary">
                    <?php _e('Add Your First Property', 'malisafi-mls'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
