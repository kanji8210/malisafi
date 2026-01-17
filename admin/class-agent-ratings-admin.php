<?php
/**
 * Agent Ratings Moderation Admin Page
 *
 * @package MalisafiMLS
 */

if (!defined('ABSPATH')) {
    exit;
}

class Malisafi_Agent_Ratings_Admin {
    
    public static function init() {
        $instance = new self();
        add_action('admin_menu', array($instance, 'add_admin_menu'), 30);
        add_action('wp_ajax_approve_agent_rating', array($instance, 'approve_rating'));
        add_action('wp_ajax_reject_agent_rating', array($instance, 'reject_rating'));
        add_action('wp_ajax_delete_agent_rating', array($instance, 'delete_rating'));
    }
    
    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_submenu_page(
            'malisafi-mls',
            __('Agent Ratings', 'malisafi-mls'),
            __('Agent Ratings', 'malisafi-mls'),
            'moderate_comments',
            'malisafi-agent-ratings',
            array($this, 'render_page')
        );
    }
    
    /**
     * Render moderation page
     */
    public function render_page() {
        global $wpdb;
        $ratings_table = $wpdb->prefix . 'mf_agent_ratings';
        
        // Get filter status
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'pending';
        
        // Get ratings
        $query = "SELECT r.*, p.post_title as agent_name 
                  FROM $ratings_table r 
                  LEFT JOIN {$wpdb->posts} p ON r.agent_id = p.ID 
                  WHERE r.status = %s 
                  ORDER BY r.created_at DESC";
        
        $ratings = $wpdb->get_results($wpdb->prepare($query, $status_filter));
        
        // Get counts for each status
        $pending_count = $wpdb->get_var("SELECT COUNT(*) FROM $ratings_table WHERE status = 'pending'");
        $approved_count = $wpdb->get_var("SELECT COUNT(*) FROM $ratings_table WHERE status = 'approved'");
        $rejected_count = $wpdb->get_var("SELECT COUNT(*) FROM $ratings_table WHERE status = 'rejected'");
        
        ?>
        <div class="wrap">
            <h1><?php _e('Agent Ratings Moderation', 'malisafi-mls'); ?></h1>
            
            <ul class="subsubsub">
                <li>
                    <a href="?page=malisafi-agent-ratings&status=pending" class="<?php echo $status_filter === 'pending' ? 'current' : ''; ?>">
                        <?php _e('Pending', 'malisafi-mls'); ?> <span class="count">(<?php echo $pending_count; ?>)</span>
                    </a> |
                </li>
                <li>
                    <a href="?page=malisafi-agent-ratings&status=approved" class="<?php echo $status_filter === 'approved' ? 'current' : ''; ?>">
                        <?php _e('Approved', 'malisafi-mls'); ?> <span class="count">(<?php echo $approved_count; ?>)</span>
                    </a> |
                </li>
                <li>
                    <a href="?page=malisafi-agent-ratings&status=rejected" class="<?php echo $status_filter === 'rejected' ? 'current' : ''; ?>">
                        <?php _e('Rejected', 'malisafi-mls'); ?> <span class="count">(<?php echo $rejected_count; ?>)</span>
                    </a>
                </li>
            </ul>
            
            <div class="clear"></div>
            
            <?php if (empty($ratings)): ?>
                <p><?php _e('No ratings found.', 'malisafi-mls'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Agent', 'malisafi-mls'); ?></th>
                            <th><?php _e('Reviewer', 'malisafi-mls'); ?></th>
                            <th><?php _e('Rating', 'malisafi-mls'); ?></th>
                            <th><?php _e('Review', 'malisafi-mls'); ?></th>
                            <th><?php _e('Date', 'malisafi-mls'); ?></th>
                            <th><?php _e('Helpful Votes', 'malisafi-mls'); ?></th>
                            <th><?php _e('Actions', 'malisafi-mls'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ratings as $rating): 
                            $reviewer = get_userdata($rating->user_id);
                            $agent_url = admin_url('post.php?post=' . $rating->agent_id . '&action=edit');
                        ?>
                            <tr data-rating-id="<?php echo $rating->id; ?>">
                                <td>
                                    <strong>
                                        <a href="<?php echo esc_url($agent_url); ?>" target="_blank">
                                            <?php echo esc_html($rating->agent_name); ?>
                                        </a>
                                    </strong>
                                </td>
                                <td>
                                    <?php echo $reviewer ? esc_html($reviewer->display_name) : __('Unknown User', 'malisafi-mls'); ?>
                                    <?php if ($rating->verified_client): ?>
                                        <br><span class="dashicons dashicons-yes-alt" style="color: #46b450;" title="<?php esc_attr_e('Verified Client', 'malisafi-mls'); ?>"></span>
                                        <small><?php _e('Verified', 'malisafi-mls'); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="dashicons dashicons-star-<?php echo $i <= $rating->rating ? 'filled' : 'empty'; ?>" style="color: #ffa500;"></span>
                                        <?php endfor; ?>
                                        <strong><?php echo $rating->rating; ?>/5</strong>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($rating->review_title): ?>
                                        <strong><?php echo esc_html($rating->review_title); ?></strong><br>
                                    <?php endif; ?>
                                    <div style="max-width: 400px; overflow: hidden; text-overflow: ellipsis;">
                                        <?php echo esc_html(wp_trim_words($rating->review_text, 20)); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php echo human_time_diff(strtotime($rating->created_at), current_time('timestamp')) . ' ' . __('ago', 'malisafi-mls'); ?>
                                </td>
                                <td>
                                    <span class="dashicons dashicons-thumbs-up" style="color: #46b450;"></span> <?php echo intval($rating->helpful_count); ?>
                                    <br>
                                    <span class="dashicons dashicons-thumbs-down" style="color: #dc3232;"></span> <?php echo intval($rating->not_helpful_count); ?>
                                </td>
                                <td>
                                    <?php if ($status_filter === 'pending'): ?>
                                        <button class="button button-primary approve-rating" data-id="<?php echo $rating->id; ?>">
                                            <?php _e('Approve', 'malisafi-mls'); ?>
                                        </button>
                                        <button class="button reject-rating" data-id="<?php echo $rating->id; ?>">
                                            <?php _e('Reject', 'malisafi-mls'); ?>
                                        </button>
                                    <?php elseif ($status_filter === 'approved'): ?>
                                        <button class="button reject-rating" data-id="<?php echo $rating->id; ?>">
                                            <?php _e('Reject', 'malisafi-mls'); ?>
                                        </button>
                                    <?php elseif ($status_filter === 'rejected'): ?>
                                        <button class="button button-primary approve-rating" data-id="<?php echo $rating->id; ?>">
                                            <?php _e('Approve', 'malisafi-mls'); ?>
                                        </button>
                                    <?php endif; ?>
                                    <button class="button button-link-delete delete-rating" data-id="<?php echo $rating->id; ?>">
                                        <?php _e('Delete', 'malisafi-mls'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Approve rating
            $('.approve-rating').on('click', function() {
                const btn = $(this);
                const ratingId = btn.data('id');
                
                if (!confirm('<?php _e('Are you sure you want to approve this rating?', 'malisafi-mls'); ?>')) {
                    return;
                }
                
                btn.prop('disabled', true).text('<?php _e('Processing...', 'malisafi-mls'); ?>');
                
                $.post(ajaxurl, {
                    action: 'approve_agent_rating',
                    rating_id: ratingId,
                    nonce: '<?php echo wp_create_nonce('moderate_rating'); ?>'
                }, function(response) {
                    if (response.success) {
                        btn.closest('tr').fadeOut(300, function() {
                            $(this).remove();
                        });
                        location.reload();
                    } else {
                        alert(response.data.message);
                        btn.prop('disabled', false).text('<?php _e('Approve', 'malisafi-mls'); ?>');
                    }
                });
            });
            
            // Reject rating
            $('.reject-rating').on('click', function() {
                const btn = $(this);
                const ratingId = btn.data('id');
                
                if (!confirm('<?php _e('Are you sure you want to reject this rating?', 'malisafi-mls'); ?>')) {
                    return;
                }
                
                btn.prop('disabled', true).text('<?php _e('Processing...', 'malisafi-mls'); ?>');
                
                $.post(ajaxurl, {
                    action: 'reject_agent_rating',
                    rating_id: ratingId,
                    nonce: '<?php echo wp_create_nonce('moderate_rating'); ?>'
                }, function(response) {
                    if (response.success) {
                        btn.closest('tr').fadeOut(300, function() {
                            $(this).remove();
                        });
                        location.reload();
                    } else {
                        alert(response.data.message);
                        btn.prop('disabled', false).text('<?php _e('Reject', 'malisafi-mls'); ?>');
                    }
                });
            });
            
            // Delete rating
            $('.delete-rating').on('click', function() {
                const btn = $(this);
                const ratingId = btn.data('id');
                
                if (!confirm('<?php _e('Are you sure you want to permanently delete this rating?', 'malisafi-mls'); ?>')) {
                    return;
                }
                
                btn.prop('disabled', true).text('<?php _e('Deleting...', 'malisafi-mls'); ?>');
                
                $.post(ajaxurl, {
                    action: 'delete_agent_rating',
                    rating_id: ratingId,
                    nonce: '<?php echo wp_create_nonce('moderate_rating'); ?>'
                }, function(response) {
                    if (response.success) {
                        btn.closest('tr').fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert(response.data.message);
                        btn.prop('disabled', false).text('<?php _e('Delete', 'malisafi-mls'); ?>');
                    }
                });
            });
        });
        </script>
        
        <style>
        .rating-stars {
            white-space: nowrap;
        }
        </style>
        <?php
    }
    
    /**
     * Approve rating
     */
    public function approve_rating() {
        check_ajax_referer('moderate_rating', 'nonce');
        
        if (!current_user_can('moderate_comments')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'malisafi-mls')));
        }
        
        $rating_id = isset($_POST['rating_id']) ? intval($_POST['rating_id']) : 0;
        
        if (!$rating_id) {
            wp_send_json_error(array('message' => __('Invalid rating ID.', 'malisafi-mls')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'mf_agent_ratings';
        
        $updated = $wpdb->update(
            $table_name,
            array('status' => 'approved'),
            array('id' => $rating_id),
            array('%s'),
            array('%d')
        );
        
        if ($updated !== false) {
            // Update agent average rating
            $rating = $wpdb->get_row($wpdb->prepare(
                "SELECT agent_id FROM $table_name WHERE id = %d",
                $rating_id
            ));
            
            if ($rating) {
                $this->update_agent_rating($rating->agent_id);
            }
            
            wp_send_json_success(array('message' => __('Rating approved successfully.', 'malisafi-mls')));
        } else {
            wp_send_json_error(array('message' => __('Failed to approve rating.', 'malisafi-mls')));
        }
    }
    
    /**
     * Reject rating
     */
    public function reject_rating() {
        check_ajax_referer('moderate_rating', 'nonce');
        
        if (!current_user_can('moderate_comments')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'malisafi-mls')));
        }
        
        $rating_id = isset($_POST['rating_id']) ? intval($_POST['rating_id']) : 0;
        
        if (!$rating_id) {
            wp_send_json_error(array('message' => __('Invalid rating ID.', 'malisafi-mls')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'mf_agent_ratings';
        
        $updated = $wpdb->update(
            $table_name,
            array('status' => 'rejected'),
            array('id' => $rating_id),
            array('%s'),
            array('%d')
        );
        
        if ($updated !== false) {
            // Update agent average rating
            $rating = $wpdb->get_row($wpdb->prepare(
                "SELECT agent_id FROM $table_name WHERE id = %d",
                $rating_id
            ));
            
            if ($rating) {
                $this->update_agent_rating($rating->agent_id);
            }
            
            wp_send_json_success(array('message' => __('Rating rejected successfully.', 'malisafi-mls')));
        } else {
            wp_send_json_error(array('message' => __('Failed to reject rating.', 'malisafi-mls')));
        }
    }
    
    /**
     * Delete rating permanently
     */
    public function delete_rating() {
        check_ajax_referer('moderate_rating', 'nonce');
        
        if (!current_user_can('delete_users')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'malisafi-mls')));
        }
        
        $rating_id = isset($_POST['rating_id']) ? intval($_POST['rating_id']) : 0;
        
        if (!$rating_id) {
            wp_send_json_error(array('message' => __('Invalid rating ID.', 'malisafi-mls')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'mf_agent_ratings';
        
        // Get agent ID before deleting
        $rating = $wpdb->get_row($wpdb->prepare(
            "SELECT agent_id FROM $table_name WHERE id = %d",
            $rating_id
        ));
        
        $deleted = $wpdb->delete(
            $table_name,
            array('id' => $rating_id),
            array('%d')
        );
        
        if ($deleted) {
            // Update agent average rating
            if ($rating) {
                $this->update_agent_rating($rating->agent_id);
            }
            
            wp_send_json_success(array('message' => __('Rating deleted successfully.', 'malisafi-mls')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete rating.', 'malisafi-mls')));
        }
    }
    
    /**
     * Update agent's average rating
     */
    private function update_agent_rating($agent_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mf_agent_ratings';
        
        $avg_rating = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(rating) FROM $table_name WHERE agent_id = %d AND status = 'approved'",
            $agent_id
        ));
        
        $total_ratings = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE agent_id = %d AND status = 'approved'",
            $agent_id
        ));
        
        update_post_meta($agent_id, '_malisafi_agent_rating', round($avg_rating, 2));
        update_post_meta($agent_id, '_malisafi_agent_rating_count', $total_ratings);
        
        // Clear cache if exists
        if (class_exists('MalisafiMLS\Cache_Manager')) {
            \MalisafiMLS\Cache_Manager::clear_agent_cache($agent_id);
        }
    }
}

// Initialize
Malisafi_Agent_Ratings_Admin::init();
