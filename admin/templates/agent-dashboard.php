<?php
/**
 * Agent Dashboard Template
 *
 * @package MalisafiMLS
 */

if (!defined('ABSPATH')) {
    exit;
}

$chat_page_url = function_exists('\MalisafiMLS\Page_Manager::get_page_url')
    ? \MalisafiMLS\Page_Manager::get_page_url('internal_chat')
    : '';

if (empty($chat_page_url) || $chat_page_url === home_url()) {
    $chat_page_url = home_url('/internal-chat/');
}

$admin_chat_user_id = 0;
$moderator_chat_user_id = 0;

$moderators = get_users(array(
    'role' => 'malisafi_moderator',
    'number' => 1,
    'orderby' => 'ID',
    'order' => 'ASC',
    'fields' => array('ID'),
));
if (!empty($moderators) && !empty($moderators[0]->ID)) {
    $moderator_chat_user_id = intval($moderators[0]->ID);
}

$admins = get_users(array(
    'role' => 'administrator',
    'number' => 1,
    'orderby' => 'ID',
    'order' => 'ASC',
    'fields' => array('ID'),
));
if (!empty($admins) && !empty($admins[0]->ID)) {
    $admin_chat_user_id = intval($admins[0]->ID);
}

$chat_unread_count_for_user = static function($current_user_id, $target_user_id) {
    if (empty($current_user_id) || empty($target_user_id)) {
        return 0;
    }

    global $wpdb;

    $threads_table = $wpdb->prefix . 'mf_chat_threads';
    $participants_table = $wpdb->prefix . 'mf_chat_participants';
    $notifications_table = $wpdb->prefix . 'mf_chat_notifications';

    if ($wpdb->get_var("SHOW TABLES LIKE '{$threads_table}'") !== $threads_table
        || $wpdb->get_var("SHOW TABLES LIKE '{$participants_table}'") !== $participants_table
        || $wpdb->get_var("SHOW TABLES LIKE '{$notifications_table}'") !== $notifications_table) {
        return 0;
    }

    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(n.id)
         FROM {$notifications_table} n
         INNER JOIN {$threads_table} t ON t.id = n.thread_id AND t.thread_type = 'direct'
         INNER JOIN {$participants_table} p_me ON p_me.thread_id = t.id AND p_me.user_id = %d
         INNER JOIN {$participants_table} p_other ON p_other.thread_id = t.id AND p_other.user_id = %d
         WHERE n.user_id = %d AND n.is_read = 0",
        $current_user_id,
        $target_user_id,
        $current_user_id
    ));

    return intval($count);
};

$current_chat_user_id = get_current_user_id();
$moderator_chat_unread = $chat_unread_count_for_user($current_chat_user_id, $moderator_chat_user_id);
$admin_chat_unread = $chat_unread_count_for_user($current_chat_user_id, $admin_chat_user_id);
?>

<div class="wrap malisafi-agent-dashboard">
    <?php echo $viewing_as_text; ?>
    
    <h1>
        <?php echo esc_html($agent_name); ?> - <?php _e('Dashboard', 'malisafi-mls'); ?>
        <?php if (current_user_can('manage_options')): ?>
            <a href="<?php echo admin_url('post.php?post=' . $agent_id . '&action=edit'); ?>" class="page-title-action">
                <?php _e('Edit Profile', 'malisafi-mls'); ?>
            </a>
        <?php endif; ?>
    </h1>
    
    <?php 
    /* Debug Stats Display - Hidden for production
    // Uncomment below for debugging property query issues
    <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px;">
        <h3 style="margin-top: 0; color: #856404;">📊 Property Stats (Comprehensive Query)</h3>
        <p style="margin: 5px 0;"><strong>Total Properties:</strong> <?php echo $total_properties; ?></p>
        <p style="margin: 5px 0;"><strong>Active (Published):</strong> <?php echo $active_listings; ?></p>
        <p style="margin: 5px 0;"><strong>Pending Approval:</strong> <?php echo $pending_properties; ?></p>
        ...
    </div>
    */ 
    ?>
    
    <!-- Status Badge -->
    <div class="agent-status-badge status-<?php echo esc_attr($agent_status); ?>">
        <?php
        $status_labels = array(
            'active' => __('Active', 'malisafi-mls'),
            'inactive' => __('Inactive', 'malisafi-mls'),
            'on_vacation' => __('On Vacation', 'malisafi-mls'),
            'suspended' => __('Suspended', 'malisafi-mls'),
        );
        echo isset($status_labels[$agent_status]) ? $status_labels[$agent_status] : $status_labels['active'];
        ?>
    </div>
    
    <!-- Statistics Cards -->
    <div class="malisafi-stats-grid">
        <div class="stat-card stat-total">
            <div class="stat-icon">
                <span class="dashicons dashicons-admin-home"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo intval($total_properties); ?></h3>
                <p><?php _e('Total Properties', 'malisafi-mls'); ?></p>
            </div>
        </div>
        
        <div class="stat-card stat-active">
            <div class="stat-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo intval($active_listings); ?></h3>
                <p><?php _e('Active Listings', 'malisafi-mls'); ?></p>
            </div>
        </div>
        
        <div class="stat-card stat-pending">
            <div class="stat-icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo intval($pending_properties); ?></h3>
                <p><?php _e('Pending Approval', 'malisafi-mls'); ?></p>
            </div>
        </div>
        
        <div class="stat-card stat-views">
            <div class="stat-icon">
                <span class="dashicons dashicons-visibility"></span>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format(intval(get_post_meta($agent_id, '_agent_total_views', true))); ?></h3>
                <p><?php _e('Total Views', 'malisafi-mls'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Contact Information -->
    <div class="malisafi-card">
        <h2><?php _e('Contact Information', 'malisafi-mls'); ?></h2>
        <table class="widefat">
            <tr>
                <th><?php _e('Email', 'malisafi-mls'); ?></th>
                <td><a href="mailto:<?php echo esc_attr($agent_email); ?>"><?php echo esc_html($agent_email); ?></a></td>
            </tr>
            <tr>
                <th><?php _e('Mobile', 'malisafi-mls'); ?></th>
                <td><?php echo esc_html($agent_mobile); ?></td>
            </tr>
            <?php
            $whatsapp = get_post_meta($agent_id, '_agent_whatsapp', true);
            if ($whatsapp):
            ?>
            <tr>
                <th><?php _e('WhatsApp', 'malisafi-mls'); ?></th>
                <td><a href="https://wa.me/<?php echo esc_attr(preg_replace('/[^0-9]/', '', $whatsapp)); ?>" target="_blank"><?php echo esc_html($whatsapp); ?></a></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <!-- Recent Properties -->
    <div class="malisafi-card">
        <h2><?php _e('Recent Properties', 'malisafi-mls'); ?></h2>
        <?php if (!empty($recent_properties)): ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php _e('Property', 'malisafi-mls'); ?></th>
                        <th><?php _e('Status', 'malisafi-mls'); ?></th>
                        <th><?php _e('Date', 'malisafi-mls'); ?></th>
                        <th><?php _e('Actions', 'malisafi-mls'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_properties as $property): ?>
                        <tr>
                            <td><strong><?php echo esc_html($property->post_title); ?></strong></td>
                            <td>
                                <?php
                                $status_labels = array(
                                    'publish' => '<span style="color: green;">● ' . __('Published', 'malisafi-mls') . '</span>',
                                    'pending' => '<span style="color: orange;">● ' . __('Pending', 'malisafi-mls') . '</span>',
                                    'draft' => '<span style="color: gray;">● ' . __('Draft', 'malisafi-mls') . '</span>',
                                );
                                echo isset($status_labels[$property->post_status]) ? $status_labels[$property->post_status] : $property->post_status;
                                ?>
                            </td>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($property->post_date)); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=malisafi-property-edit&property_id=' . $property->ID); ?>" class="button button-small"><?php _e('Edit', 'malisafi-mls'); ?></a>
                                <a href="<?php echo get_permalink($property->ID); ?>" class="button button-small" target="_blank"><?php _e('View', 'malisafi-mls'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p style="text-align: center; margin-top: 20px;">
                <a href="<?php echo admin_url('edit.php?post_type=malisafi_property&agent_filter=mine'); ?>" class="button button-primary">
                    <?php _e('View All Properties', 'malisafi-mls'); ?>
                </a>
            </p>
        <?php else: ?>
            <p><?php _e('No properties yet.', 'malisafi-mls'); ?></p>
            <p>
                <?php
                // Redirection Add Property vers le frontend pour agent/owner/developer
                $add_property_url = '';
                if (function_exists('MalisafiMLS\Page_Manager::get_page_url')) {
                    if (current_user_can('malisafi_agent_basic') || current_user_can('malisafi_agent_premium')) {
                        $add_property_url = \MalisafiMLS\Page_Manager::get_page_url('agent_add_property');
                    } elseif (current_user_can('malisafi_owner')) {
                        $add_property_url = \MalisafiMLS\Page_Manager::get_page_url('owner_add_property');
                    } elseif (current_user_can('malisafi_developer')) {
                        $add_property_url = \MalisafiMLS\Page_Manager::get_page_url('agent_add_property');
                    }
                }
                if (!$add_property_url) {
                    $add_property_url = home_url('/add-property/');
                }
                ?>
                <a href="<?php echo esc_url($add_property_url); ?>" class="button button-primary">
                    <?php _e('Add Your First Property', 'malisafi-mls'); ?>
                </a>
            </p>
        <?php endif; ?>
    </div>
    
    <!-- Quick Actions -->
    <div class="malisafi-card">
        <h2><?php _e('Quick Actions', 'malisafi-mls'); ?></h2>
        <div class="quick-actions-grid">
            <a href="<?php echo admin_url('admin.php?page=malisafi-property-edit'); ?>" class="quick-action-btn">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php _e('Add Property', 'malisafi-mls'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=malisafi-agent-leads'); ?>" class="quick-action-btn">
                <span class="dashicons dashicons-email"></span>
                <?php _e('View Leads', 'malisafi-mls'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=malisafi-agent-profile'); ?>" class="quick-action-btn">
                <span class="dashicons dashicons-admin-users"></span>
                <?php _e('Edit Profile', 'malisafi-mls'); ?>
            </a>
            <a href="<?php 
                if (isset($linked_user_id) && $linked_user_id) {
                    echo admin_url('edit.php?post_type=malisafi_property&post_status=pending&author=' . $linked_user_id);
                } else {
                    echo admin_url('edit.php?post_type=malisafi_property&post_status=pending');
                }
            ?>" class="quick-action-btn">
                <span class="dashicons dashicons-clock"></span>
                <?php _e('Pending Properties', 'malisafi-mls'); ?>
                <?php if ($pending_properties > 0): ?>
                    <span class="count" style="background: #d63638; color: #fff; border-radius: 10px; padding: 2px 8px; margin-left: 8px; font-size: 12px;"><?php echo $pending_properties; ?></span>
                <?php endif; ?>
            </a>

            <?php if ($moderator_chat_user_id > 0) : ?>
                <a href="<?php echo esc_url(add_query_arg('chat_with', $moderator_chat_user_id, $chat_page_url)); ?>" class="quick-action-btn">
                    <span class="dashicons dashicons-format-chat"></span>
                    <?php _e('Chat with Moderator', 'malisafi-mls'); ?>
                    <?php if ($moderator_chat_unread > 0): ?>
                        <span class="count" style="background: #d63638; color: #fff; border-radius: 10px; padding: 2px 8px; margin-left: 8px; font-size: 12px;"><?php echo intval($moderator_chat_unread); ?></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>

            <?php if ($admin_chat_user_id > 0) : ?>
                <a href="<?php echo esc_url(add_query_arg('chat_with', $admin_chat_user_id, $chat_page_url)); ?>" class="quick-action-btn">
                    <span class="dashicons dashicons-admin-users"></span>
                    <?php _e('Chat with Admin', 'malisafi-mls'); ?>
                    <?php if ($admin_chat_unread > 0): ?>
                        <span class="count" style="background: #d63638; color: #fff; border-radius: 10px; padding: 2px 8px; margin-left: 8px; font-size: 12px;"><?php echo intval($admin_chat_unread); ?></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Admin Tools: View as Another Agent -->
    <?php if (current_user_can('manage_options')): ?>
        <div class="malisafi-card admin-tools-card">
            <h2><?php _e('Admin Tools: Switch Agent View', 'malisafi-mls'); ?></h2>
            <p><?php _e('As an administrator, you can view the dashboard from any agent\'s perspective:', 'malisafi-mls'); ?></p>
            
            <form method="get" action="">
                <input type="hidden" name="page" value="malisafi-agent-dashboard" />
                <?php wp_nonce_field('switch_agent_view', 'switch_agent_nonce'); ?>
                
                <select name="view_as_agent" id="view_as_agent" style="min-width: 300px;">
                    <option value=""><?php _e('Select an agent...', 'malisafi-mls'); ?></option>
                    <?php
                    $agents = get_posts(array(
                        'post_type' => 'malisafi_agent',
                        'posts_per_page' => -1,
                        'orderby' => 'title',
                        'order' => 'ASC'
                    ));
                    
                    foreach ($agents as $agent):
                        $viewing_agent = get_user_meta(get_current_user_id(), '_viewing_as_agent_id', true);
                        $selected = ($viewing_agent == $agent->ID) ? 'selected' : '';
                    ?>
                        <option value="<?php echo $agent->ID; ?>" <?php echo $selected; ?>><?php echo esc_html($agent->post_title); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit" class="button button-primary"><?php _e('Switch View', 'malisafi-mls'); ?></button>
                
                <?php if (get_user_meta(get_current_user_id(), '_viewing_as_agent_id', true)): ?>
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=malisafi-agent-dashboard&clear_agent_view=1'), 'clear_agent_view'); ?>" class="button">
                        <?php _e('Exit Agent View', 'malisafi-mls'); ?>
                    </a>
                <?php endif; ?>
            </form>
        </div>
    <?php endif; ?>
</div>

<style>
.malisafi-agent-dashboard {
    padding: 20px;
}

.agent-status-badge {
    display: inline-block;
    padding: 5px 15px;
    border-radius: 4px;
    font-weight: bold;
    margin-bottom: 20px;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-inactive {
    background: #e2e3e5;
    color: #383d41;
}

.status-on_vacation {
    background: #fff3cd;
    color: #856404;
}

.status-suspended {
    background: #f8d7da;
    color: #721c24;
}

.malisafi-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.stat-icon {
    font-size: 48px;
    margin-right: 15px;
}

.stat-total .stat-icon { color: #0073aa; }
.stat-active .stat-icon { color: #46b450; }
.stat-pending .stat-icon { color: #f0b849; }
.stat-views .stat-icon { color: #826eb4; }

.stat-content h3 {
    margin: 0;
    font-size: 32px;
    font-weight: bold;
}

.stat-content p {
    margin: 5px 0 0;
    color: #666;
}

.malisafi-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.malisafi-card h2 {
    margin-top: 0;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 10px;
}

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.quick-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 30px;
    background: #f8f9fa;
    border: 2px solid #0073aa;
    border-radius: 8px;
    text-decoration: none;
    color: #0073aa;
    font-weight: bold;
    transition: all 0.3s;
}

.quick-action-btn:hover {
    background: #0073aa;
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.quick-action-btn .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    margin-bottom: 10px;
}

.admin-tools-card {
    background: #fff9e6;
    border-color: #f0b849;
}
</style>