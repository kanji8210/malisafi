<?php
// Enqueue chat modal CSS for improved styling
wp_enqueue_style('malisafi-chat-modal', MALISAFI_MLS_URL . 'assets/css/chat-modal.css', array('malisafi-mls-variables'), MALISAFI_MLS_VERSION);
?>
<?php
/**
 * Moderation Queue Template
 *
 * @package MalisafiMLS
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check permissions
if (!current_user_can('moderate_properties')) {
    wp_die(__('You do not have permission to access this page.', 'malisafi-mls'));
}

// Display messages
if (isset($_GET['message'])) {
    $messages = array(
        'property_verified' => __('Property verified successfully.', 'malisafi-mls'),
        'property_rejected' => __('Property rejected.', 'malisafi-mls'),
        'report_dismissed' => __('Report dismissed.', 'malisafi-mls')
    );
    
    if (isset($messages[$_GET['message']])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($messages[$_GET['message']]) . '</p></div>';
    }
}

// Check if moderation class exists
if (!class_exists('Malisafi_Property_Moderation')) {
    echo '<div class="wrap"><div class="notice notice-error"><p>' . __('Moderation system is not available. Please refresh the page.', 'malisafi-mls') . '</p></div></div>';
    return;
}

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'pending';

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
?>

<div class="wrap malisafi-moderation-page">
    <h1><?php _e('Moderation Queue', 'malisafi-mls'); ?></h1>
    
    <!-- Tabs -->
    <nav class="nav-tab-wrapper wp-clearfix">
        <a href="<?php echo admin_url('admin.php?page=malisafi-moderation&tab=pending'); ?>" 
           class="nav-tab <?php echo $current_tab === 'pending' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Pending Verification', 'malisafi-mls'); ?>
            <?php
            try {
                $pending_count = Malisafi_Property_Moderation::get_pending_verification(array('posts_per_page' => -1))->found_posts;
                if ($pending_count > 0) {
                    echo '<span class="count">(' . $pending_count . ')</span>';
                }
            } catch (Exception $e) {
                // Silently fail for counts
            }
            ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=malisafi-moderation&tab=reports'); ?>" 
           class="nav-tab <?php echo $current_tab === 'reports' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Reported Properties', 'malisafi-mls'); ?>
            <?php
            try {
                $reports = Malisafi_Property_Moderation::get_reported_properties('pending');
                $reports_count = is_array($reports) ? count($reports) : 0;
                if ($reports_count > 0) {
                    echo '<span class="count">(' . $reports_count . ')</span>';
                }
            } catch (Exception $e) {
                // Silently fail for counts
            }
            ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=malisafi-moderation&tab=verified'); ?>" 
           class="nav-tab <?php echo $current_tab === 'verified' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Verified Properties', 'malisafi-mls'); ?>
        </a>
    </nav>
    
    <div class="moderation-content">
        
        <?php if ($current_tab === 'pending') : ?>
            
            <!-- Pending Verification -->
            <?php
            $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
            try {
                $pending_properties = Malisafi_Property_Moderation::get_pending_verification(array('paged' => $paged));
            } catch (Exception $e) {
                echo '<div class="notice notice-error"><p>' . esc_html__('Error loading pending properties. Please check database tables.', 'malisafi-mls') . '</p></div>';
                $pending_properties = new WP_Query(array('post_type' => 'none')); // Empty query
            }
            ?>
            
            <?php if ($pending_properties->have_posts()) : ?>
                <div class="pending-properties-grid">
                    <?php while ($pending_properties->have_posts()) : $pending_properties->the_post(); ?>
                        <?php
                        $property_id = get_the_ID();
                        $price = get_post_meta($property_id, '_malisafi_price', true);
                        $price = !empty($price) ? floatval($price) : 0;
                        $city = get_post_meta($property_id, '_malisafi_city', true);
                        $bedrooms = get_post_meta($property_id, '_malisafi_bedrooms', true);
                        $bathrooms = get_post_meta($property_id, '_malisafi_bathrooms', true);
                        $area = get_post_meta($property_id, '_malisafi_area', true);
                        $area = !empty($area) ? floatval($area) : 0;
                        $author_id = (int) get_post_field('post_author', $property_id);

                        $chat_recipient_user_id = absint(get_post_meta($property_id, '_agent_user_id', true));

                        if ($chat_recipient_user_id <= 0) {
                            $property_agent_post_id = absint(get_post_meta($property_id, '_malisafi_agent_id', true));
                            if ($property_agent_post_id <= 0) {
                                $property_agent_post_id = absint(get_post_meta($property_id, '_property_agent_id', true));
                            }

                            if ($property_agent_post_id > 0) {
                                $chat_recipient_user_id = absint(get_post_meta($property_agent_post_id, '_agent_user_id', true));
                                if ($chat_recipient_user_id <= 0) {
                                    $chat_recipient_user_id = absint(get_post_meta($property_agent_post_id, '_malisafi_linked_user', true));
                                }
                            }
                        }

                        if ($chat_recipient_user_id <= 0 && $author_id > 0) {
                            $chat_recipient_user_id = $author_id;
                        }

                        $author = $chat_recipient_user_id > 0 ? get_user_by('id', $chat_recipient_user_id) : false;
                        
                        // Get report count safely
                        $report_count = 0;
                        try {
                            $report_count = Malisafi_Property_Moderation::get_report_count($property_id);
                        } catch (Exception $e) {
                            // Table might not exist yet
                        }
                        ?>
                        
                        <div class="property-moderation-card">
                            <div class="property-thumbnail">
                                <?php if (has_post_thumbnail()) : ?>
                                    <?php the_post_thumbnail('medium'); ?>
                                <?php else : ?>
                                    <div class="no-image">
                                        <span class="dashicons dashicons-admin-home"></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($report_count > 0) : ?>
                                    <span class="report-badge">
                                        <span class="dashicons dashicons-warning"></span>
                                        <?php printf(_n('%d report', '%d reports', $report_count, 'malisafi-mls'), $report_count); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="property-details">
                                <h3>
                                    <a href="<?php the_permalink(); ?>" target="_blank"><?php the_title(); ?></a>
                                </h3>
                                
                                <div class="property-meta">
                                    <span class="price"><strong>$<?php echo number_format($price); ?></strong></span>
                                    <?php if ($city) : ?>
                                        <span class="location">
                                            <span class="dashicons dashicons-location"></span>
                                            <?php echo esc_html($city); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="property-specs">
                                    <?php if ($bedrooms) : ?>
                                        <span><span class="dashicons dashicons-admin-home"></span> <?php echo $bedrooms; ?> <?php _e('beds', 'malisafi-mls'); ?></span>
                                    <?php endif; ?>
                                    <?php if ($bathrooms) : ?>
                                        <span><span class="dashicons dashicons-admin-home"></span> <?php echo $bathrooms; ?> <?php _e('baths', 'malisafi-mls'); ?></span>
                                    <?php endif; ?>
                                    <?php if ($area) : ?>
                                        <span><span class="dashicons dashicons-editor-expand"></span> <?php echo number_format($area); ?> sq ft</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="property-author">
                                    <?php _e('Posted by:', 'malisafi-mls'); ?>
                                    <strong><?php echo $author ? esc_html($author->display_name) : esc_html__('Unknown', 'malisafi-mls'); ?></strong>
                                    <span class="post-date"><?php echo human_time_diff(get_the_time('U'), current_time('timestamp')) . ' ' . __('ago', 'malisafi-mls'); ?></span>
                                </div>
                                
                                <div class="property-excerpt">
                                    <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
                                </div>
                                
                                <div class="moderation-actions">
                                    <button type="button" class="button button-primary verify-property" data-property-id="<?php echo $property_id; ?>">
                                        <span class="dashicons dashicons-yes-alt"></span>
                                        <?php _e('Verify', 'malisafi-mls'); ?>
                                    </button>
                                    <button type="button" class="button reject-property" data-property-id="<?php echo $property_id; ?>">
                                        <span class="dashicons dashicons-dismiss"></span>
                                        <?php _e('Reject', 'malisafi-mls'); ?>
                                    </button>
                                    <?php
                                    $is_featured = get_post_meta($property_id, '_malisafi_featured', true);
                                    if ($is_featured === '1') :
                                    ?>
                                        <button type="button" class="button toggle-featured" data-property-id="<?php echo $property_id; ?>" data-action="remove" style="color: #b4ab74;">
                                            <span class="dashicons dashicons-star-filled"></span>
                                            <?php _e('Unfeatured', 'malisafi-mls'); ?>
                                        </button>
                                    <?php else : ?>
                                        <button type="button" class="button toggle-featured" data-property-id="<?php echo $property_id; ?>" data-action="add" style="color: #737d5d;">
                                            <span class="dashicons dashicons-star-empty"></span>
                                            <?php _e('Make Featured', 'malisafi-mls'); ?>
                                        </button>
                                    <?php endif; ?>
                                    <a href="<?php echo admin_url('admin.php?page=malisafi-properties&action=edit&property_id=' . $property_id); ?>" class="button">
                                        <span class="dashicons dashicons-edit"></span>
                                        <?php _e('Edit', 'malisafi-mls'); ?>
                                    </a>
                                    <?php if ($author && !empty($author->ID)) : ?>
                                        <?php
                                        $chat_page_url = function_exists('\MalisafiMLS\Page_Manager::get_page_url')
                                            ? \MalisafiMLS\Page_Manager::get_page_url('internal_chat')
                                            : '';

                                        if (empty($chat_page_url) || $chat_page_url === home_url()) {
                                            $chat_page_url = home_url('/internal-chat/');
                                        }

                                        $chat_with_author_url = add_query_arg('chat_with', intval($chat_recipient_user_id), $chat_page_url);
                                        $text_agent_unread = $chat_unread_count_for_user($current_chat_user_id, intval($chat_recipient_user_id));
                                        ?>
                                        <a href="<?php echo esc_url($chat_with_author_url); ?>" class="button malisafi-open-chat-popup" data-chat-with="<?php echo intval($chat_recipient_user_id); ?>" data-chat-prefill="<?php echo esc_attr(sprintf(__('Property #%1$d: %2$s - ', 'malisafi-mls'), intval($property_id), get_the_title($property_id))); ?>">
                                            <span class="dashicons dashicons-email"></span>
                                            <?php _e('Text Agent', 'malisafi-mls'); ?>
                                            <?php if ($text_agent_unread > 0): ?>
                                                <span class="count" style="background: #d63638; color: #fff; border-radius: 10px; padding: 2px 8px; margin-left: 8px; font-size: 12px;"><?php echo intval($text_agent_unread); ?></span>
                                            <?php endif; ?>
                                        </a>
                                        <div class="malisafi-chat-modal" style="display:none;">
                                            <div class="malisafi-chat-modal-content">
                                                <span class="malisafi-chat-modal-close">&times;</span>
                                                <div class="malisafi-chat-modal-body">
                                                    <!-- Chat will be loaded here -->
                                                </div>
                                            </div>
                                        </div>
                                    <script>
                                    jQuery(document).ready(function($) {
                                        $('.malisafi-open-chat-popup').on('click', function(e) {
                                            e.preventDefault();
                                            var agentId = $(this).data('chat-with');
                                            var prefill = $(this).data('chat-prefill');
                                            var modal = $(this).closest('.property-moderation-card').find('.malisafi-chat-modal');
                                            modal.show();
                                            // Load chat interface via AJAX
                                            $.post(ajaxurl, {
                                                action: 'malisafi_chat_bootstrap',
                                                nonce: malisafiChat.nonce
                                            }, function(response) {
                                                if (response.success) {
                                                    // Open thread with agent
                                                    $.post(ajaxurl, {
                                                        action: 'malisafi_chat_open_thread',
                                                        nonce: malisafiChat.nonce,
                                                        target_user_id: agentId
                                                    }, function(threadResp) {
                                                        if (threadResp.success) {
                                                            var messages = threadResp.data.messages || [];
                                                            var html = '<div class="malisafi-chat-thread">';
                                                            html += '<div class="malisafi-chat-messages">';
                                                            messages.forEach(function(msg) {
                                                                html += '<div class="malisafi-chat-message' + (msg.isOwn ? ' own' : '') + '"><strong>' + msg.senderName + ':</strong> ' + msg.message + '<br><small>' + msg.createdAtHuman + '</small></div>';
                                                            });
                                                            html += '</div>';
                                                            html += '<textarea class="malisafi-chat-input" placeholder="'+malisafiChat.i18n.typeMessage+'">'+prefill+'</textarea>';
                                                            html += '<button class="malisafi-chat-send-btn">'+malisafiChat.i18n.send+'</button>';
                                                            html += '</div>';
                                                            modal.find('.malisafi-chat-modal-body').html(html);
                                                            // Send message handler
                                                            modal.find('.malisafi-chat-send-btn').off('click').on('click', function() {
                                                                var msg = modal.find('.malisafi-chat-input').val();
                                                                $.post(ajaxurl, {
                                                                    action: 'malisafi_chat_send_message',
                                                                    nonce: malisafiChat.nonce,
                                                                    thread_id: threadResp.data.threadId,
                                                                    message: msg
                                                                }, function(sendResp) {
                                                                    if (sendResp.success && sendResp.data.message) {
                                                                        var newMsg = sendResp.data.message;
                                                                        var msgHtml = '<div class="malisafi-chat-message own"><strong>' + newMsg.senderName + ':</strong> ' + newMsg.message + '<br><small>' + newMsg.createdAtHuman + '</small></div>';
                                                                        modal.find('.malisafi-chat-messages').append(msgHtml);
                                                                        modal.find('.malisafi-chat-input').val('');
                                                                    } else {
                                                                        alert(malisafiChat.i18n.sendFailed);
                                                                    }
                                                                });
                                                            });
                                                        } else {
                                                            modal.find('.malisafi-chat-modal-body').html('<p>'+malisafiChat.i18n.unableOpen+'</p>');
                                                        }
                                                    });
                                                } else {
                                                    modal.find('.malisafi-chat-modal-body').html('<p>'+malisafiChat.i18n.loadFailed+'</p>');
                                                }
                                            });
                                        });
                                        // Close modal handler
                                        $('.malisafi-chat-modal-close').on('click', function() {
                                            $(this).closest('.malisafi-chat-modal').hide();
                                        });
                                    });
                                    </script>
                                    <?php endif; ?>
                                    <a href="<?php the_permalink(); ?>" class="button" target="_blank">
                                        <span class="dashicons dashicons-visibility"></span>
                                        <?php _e('View', 'malisafi-mls'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($pending_properties->max_num_pages > 1) : ?>
                    <div class="tablenav bottom">
                        <div class="tablenav-pages">
                            <?php
                            echo paginate_links(array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => __('&laquo;'),
                                'next_text' => __('&raquo;'),
                                'total' => $pending_properties->max_num_pages,
                                'current' => $paged
                            ));
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
                
            <?php else : ?>
                <div class="no-items">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <p><?php _e('No properties pending verification.', 'malisafi-mls'); ?></p>
                </div>
            <?php endif; ?>
            
        <?php elseif ($current_tab === 'reports') : ?>
            
            <!-- Reported Properties -->
            <?php
            try {
                $reports = Malisafi_Property_Moderation::get_reported_properties('pending');
                $reasons = Malisafi_Property_Moderation::get_report_reasons();
            } catch (Exception $e) {
                echo '<div class="notice notice-error"><p>' . esc_html__('Error loading reports. Please check database tables.', 'malisafi-mls') . '</p></div>';
                $reports = array();
                $reasons = array();
            }
            ?>
            
            <?php if (!empty($reports)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Property', 'malisafi-mls'); ?></th>
                            <th><?php _e('Reported By', 'malisafi-mls'); ?></th>
                            <th><?php _e('Reason', 'malisafi-mls'); ?></th>
                            <th><?php _e('Details', 'malisafi-mls'); ?></th>
                            <th><?php _e('Date', 'malisafi-mls'); ?></th>
                            <th><?php _e('Actions', 'malisafi-mls'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report) : ?>
                            <tr>
                                <td>
                                    <strong>
                                        <a href="<?php echo get_permalink($report->property_id); ?>" target="_blank">
                                            <?php echo esc_html($report->post_title); ?>
                                        </a>
                                    </strong>
                                </td>
                                <td><?php echo esc_html($report->reporter_name); ?></td>
                                <td>
                                    <span class="reason-badge">
                                        <?php echo isset($reasons[$report->reason]) ? esc_html($reasons[$report->reason]) : esc_html($report->reason); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($report->details) : ?>
                                        <span class="report-details" title="<?php echo esc_attr($report->details); ?>">
                                            <?php echo esc_html(wp_trim_words($report->details, 10)); ?>
                                        </span>
                                    <?php else : ?>
                                        <span style="color: #999;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date_i18n('M j, Y g:i a', strtotime($report->created_at)); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=malisafi-properties&action=edit&property_id=' . $report->property_id); ?>" class="button button-small">
                                        <?php _e('Review Property', 'malisafi-mls'); ?>
                                    </a>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=malisafi_dismiss_report&report_id=' . $report->id), 'malisafi_dismiss_report_' . $report->id); ?>" 
                                       class="button button-small">
                                        <?php _e('Dismiss', 'malisafi-mls'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="no-items">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <p><?php _e('No reported properties.', 'malisafi-mls'); ?></p>
                </div>
            <?php endif; ?>
            
        <?php elseif ($current_tab === 'verified') : ?>
            
            <!-- Verified Properties -->
            <?php
            $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
            $verified_properties = new WP_Query(array(
                'post_type' => 'malisafi_property',
                'post_status' => array('publish', 'pending', 'draft'),
                'posts_per_page' => 20,
                'paged' => $paged,
                'meta_query' => array(
                    array(
                        'key' => '_malisafi_verified',
                        'value' => '1',
                        'compare' => '='
                    )
                )
            ));
            ?>
            
            <?php if ($verified_properties->have_posts()) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Property', 'malisafi-mls'); ?></th>
                            <th><?php _e('Price', 'malisafi-mls'); ?></th>
                            <th><?php _e('Location', 'malisafi-mls'); ?></th>
                            <th><?php _e('Author', 'malisafi-mls'); ?></th>
                            <th><?php _e('Verified Date', 'malisafi-mls'); ?></th>
                            <th><?php _e('Verified By', 'malisafi-mls'); ?></th>
                            <th><?php _e('Actions', 'malisafi-mls'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($verified_properties->have_posts()) : $verified_properties->the_post(); ?>
                            <?php
                            $property_id = get_the_ID();
                            $price = get_post_meta($property_id, '_malisafi_price', true);
                            $price = !empty($price) ? floatval($price) : 0;
                            $city = get_post_meta($property_id, '_malisafi_city', true);
                            $verified_date = get_post_meta($property_id, '_malisafi_verified_date', true);
                            $verified_by_id = get_post_meta($property_id, '_malisafi_verified_by', true);
                            $verified_by = get_user_by('id', $verified_by_id);
                            ?>
                            <tr>
                                <td>
                                    <strong>
                                        <a href="<?php the_permalink(); ?>" target="_blank"><?php the_title(); ?></a>
                                    </strong>
                                </td>
                                <td><strong>$<?php echo number_format($price); ?></strong></td>
                                <td><?php echo $city ? esc_html($city) : '<span style="color: #999;">—</span>'; ?></td>
                                <td><?php the_author(); ?></td>
                                <td><?php echo $verified_date ? date_i18n('M j, Y', strtotime($verified_date)) : '—'; ?></td>
                                <td><?php echo $verified_by ? esc_html($verified_by->display_name) : '—'; ?></td>
                                <td>
                                    <?php
                                    $is_featured_v = get_post_meta($property_id, '_malisafi_featured', true);
                                    if ($is_featured_v === '1') :
                                    ?>
                                        <button type="button" class="button button-small toggle-featured" data-property-id="<?php echo $property_id; ?>" data-action="remove" style="color: #b4ab74;">
                                            <span class="dashicons dashicons-star-filled"></span>
                                            <?php _e('Unfeatured', 'malisafi-mls'); ?>
                                        </button>
                                    <?php else : ?>
                                        <button type="button" class="button button-small toggle-featured" data-property-id="<?php echo $property_id; ?>" data-action="add" style="color: #737d5d;">
                                            <span class="dashicons dashicons-star-empty"></span>
                                            <?php _e('Make Featured', 'malisafi-mls'); ?>
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" class="button button-small unapprove-property" data-property-id="<?php echo $property_id; ?>" style="color: #d63638;">
                                        <span class="dashicons dashicons-undo"></span>
                                        <?php _e('Unapprove', 'malisafi-mls'); ?>
                                    </button>
                                    <a href="<?php echo admin_url('admin.php?page=malisafi-properties&action=edit&property_id=' . $property_id); ?>" class="button button-small">
                                        <?php _e('Edit', 'malisafi-mls'); ?>
                                    </a>
                                    <a href="<?php the_permalink(); ?>" class="button button-small" target="_blank">
                                        <?php _e('View', 'malisafi-mls'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; wp_reset_postdata(); ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <?php if ($verified_properties->max_num_pages > 1) : ?>
                    <div class="tablenav bottom">
                        <div class="tablenav-pages">
                            <?php
                            echo paginate_links(array(
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'prev_text' => __('&laquo;'),
                                'next_text' => __('&raquo;'),
                                'total' => $verified_properties->max_num_pages,
                                'current' => $paged
                            ));
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
                
            <?php else : ?>
                <div class="no-items">
                    <span class="dashicons dashicons-admin-home"></span>
                    <p><?php _e('No verified properties yet.', 'malisafi-mls'); ?></p>
                </div>
            <?php endif; ?>
            
        <?php endif; ?>
        
    </div>
</div>

<!-- Reject Property Modal -->
<div id="reject-modal" class="malisafi-modal" style="display: none;">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2><?php _e('Reject Property', 'malisafi-mls'); ?></h2>
        <p><?php _e('Please provide a reason for rejecting this property. The owner will be notified.', 'malisafi-mls'); ?></p>
        <textarea id="rejection-reason" rows="5" style="width: 100%;"></textarea>
        <div class="modal-actions">
            <button type="button" class="button button-primary" id="confirm-reject">
                <?php _e('Reject Property', 'malisafi-mls'); ?>
            </button>
            <button type="button" class="button cancel-modal">
                <?php _e('Cancel', 'malisafi-mls'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Unapprove Property Modal -->
<div id="unapprove-modal" class="malisafi-modal" style="display: none;">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2><?php _e('Unapprove Property', 'malisafi-mls'); ?></h2>
        <p><?php _e('Please provide a reason for reverting this property approval. It will be moved back to pending status and the owner will be notified.', 'malisafi-mls'); ?></p>
        <textarea id="unapproval-reason" rows="5" style="width: 100%;"></textarea>
        <div class="modal-actions">
            <button type="button" class="button button-primary" id="confirm-unapprove" style="background: #d63638; border-color: #d63638;">
                <?php _e('Unapprove Property', 'malisafi-mls'); ?>
            </button>
            <button type="button" class="button cancel-modal">
                <?php _e('Cancel', 'malisafi-mls'); ?>
            </button>
        </div>
    </div>
</div>

<style>
.malisafi-moderation-page .nav-tab-wrapper .count {
    background: #d63638;
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    margin-left: 5px;
}

.moderation-content {
    margin-top: 20px;
}

.pending-properties-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.property-moderation-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    transition: box-shadow 0.3s;
}

.property-moderation-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.property-thumbnail {
    position: relative;
    height: 200px;
    overflow: hidden;
    background: #f5f5f5;
}

.property-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.property-thumbnail .no-image {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #ccc;
}

.property-thumbnail .no-image .dashicons {
    font-size: 60px;
    width: 60px;
    height: 60px;
}

.report-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(214, 54, 56, 0.9);
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
}

.verification-status {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    margin-left: 10px;
}

.verification-status.verified {
    background: rgba(46, 125, 50, 0.1);
    color: #2e7d32;
    border: 1px solid #2e7d32;
}

.property-details {
    padding: 15px;
}

.property-details h3 {
    margin: 0 0 10px 0;
    font-size: 18px;
}

.property-details h3 a {
    text-decoration: none;
    color: #1d2327;
}

.property-details h3 a:hover {
    color: #2271b1;
}

.property-meta {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.property-meta .price {
    font-size: 20px;
    color: #2271b1;
}

.property-meta .location {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #666;
}

.property-specs {
    display: flex;
    gap: 15px;
    margin-bottom: 10px;
    font-size: 13px;
    color: #666;
}

.property-specs span {
    display: flex;
    align-items: center;
    gap: 3px;
}

.property-author {
    font-size: 13px;
    color: #666;
    margin-bottom: 10px;
}

.property-author .post-date {
    color: #999;
    margin-left: 5px;
}

.property-excerpt {
    font-size: 13px;
    color: #666;
    margin-bottom: 15px;
    line-height: 1.5;
}

.moderation-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.moderation-actions .button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.no-items {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
}

.no-items .dashicons {
    font-size: 60px;
    width: 60px;
    height: 60px;
    color: #00a32a;
}

.no-items p {
    font-size: 16px;
    color: #666;
    margin-top: 10px;
}

.reason-badge {
    background: #dba617;
    color: white;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
}

.report-details {
    cursor: help;
}

/* Modal */
.malisafi-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.malisafi-modal .modal-content {
    background-color: white;
    margin: 10% auto;
    padding: 30px;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    position: relative;
}

.malisafi-modal .close-modal {
    position: absolute;
    right: 15px;
    top: 15px;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #999;
}

.malisafi-modal .close-modal:hover {
    color: #333;
}

.malisafi-modal h2 {
    margin-top: 0;
}

.malisafi-modal .modal-actions {
    margin-top: 20px;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}
</style>

<script>
jQuery(document).ready(function($) {
    var currentPropertyId = null;
    
    // Verify property
    $('.verify-property').on('click', function() {
        var propertyId = $(this).data('property-id');
        
        if (!confirm('<?php _e('Are you sure you want to verify this property?', 'malisafi-mls'); ?>')) {
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'malisafi_verify_property',
                property_id: propertyId,
                nonce: '<?php echo wp_create_nonce('malisafi_moderate_property'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            }
        });
    });
    
    // Reject property - show modal
    $('.reject-property').on('click', function() {
        currentPropertyId = $(this).data('property-id');
        $('#reject-modal').show();
    });
    
    // Close modal
    $('.close-modal, .cancel-modal').on('click', function() {
        $('#reject-modal').hide();
        $('#rejection-reason').val('');
        currentPropertyId = null;
    });
    
    // Confirm reject
    $('#confirm-reject').on('click', function() {
        var reason = $('#rejection-reason').val();
        
        if (!reason.trim()) {
            alert('<?php _e('Please provide a reason for rejection.', 'malisafi-mls'); ?>');
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'malisafi_reject_property',
                property_id: currentPropertyId,
                reason: reason,
                nonce: '<?php echo wp_create_nonce('malisafi_moderate_property'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            }
        });
    });
    
    // Close modal on outside click
    $(window).on('click', function(e) {
        if ($(e.target).is('#reject-modal')) {
            $('#reject-modal').hide();
            $('#rejection-reason').val('');
            currentPropertyId = null;
        }
        if ($(e.target).is('#unapprove-modal')) {
            $('#unapprove-modal').hide();
            $('#unapproval-reason').val('');
            currentPropertyId = null;
        }
    });
    
    // Unapprove property - show modal
    $('.unapprove-property').on('click', function() {
        currentPropertyId = $(this).data('property-id');
        $('#unapprove-modal').show();
    });
    
    // Confirm unapprove
    $('#confirm-unapprove').on('click', function() {
        var reason = $('#unapproval-reason').val();
        
        if (!reason.trim()) {
            alert('<?php _e('Please provide a reason for unapproving this property.', 'malisafi-mls'); ?>');
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'malisafi_unapprove_property',
                property_id: currentPropertyId,
                reason: reason,
                nonce: '<?php echo wp_create_nonce('malisafi_moderate_property'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            }
        });
    });
    
    // Toggle Featured status
    $('.toggle-featured').on('click', function() {
        var propertyId = $(this).data('property-id');
        var action = $(this).data('action'); // 'add' or 'remove'
        var $button = $(this);
        
        var confirmMsg = action === 'add' 
            ? '<?php _e('Make this property featured?', 'malisafi-mls'); ?>'
            : '<?php _e('Remove featured status from this property?', 'malisafi-mls'); ?>';
        
        if (!confirm(confirmMsg)) {
            return;
        }
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'malisafi_admin_toggle_featured',
                property_id: propertyId,
                featured_action: action,
                nonce: '<?php echo wp_create_nonce('malisafi_admin_featured'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || '<?php _e('An error occurred.', 'malisafi-mls'); ?>');
                }
            },
            error: function() {
                alert('<?php _e('An error occurred.', 'malisafi-mls'); ?>');
            }
        });
    });
});
</script>
