<?php
/**
 * Property Moderation System
 *
 * @package MalisafiMLS
 */

/**
 * Malisafi_Property_Moderation class
 */
class Malisafi_Property_Moderation {
    
    /**
     * Check if database table exists
     */
    private static function table_exists() {
        global $wpdb;
        $table = $wpdb->prefix . 'mf_property_reports';
        return $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    }
    
    /**
     * Initialize moderation system
     */
    public static function init() {
        // AJAX handlers
        add_action('wp_ajax_malisafi_report_property', array(__CLASS__, 'ajax_report_property'));
        add_action('wp_ajax_nopriv_malisafi_report_property', array(__CLASS__, 'ajax_report_property'));
        add_action('wp_ajax_malisafi_verify_property', array(__CLASS__, 'ajax_verify_property'));
        add_action('wp_ajax_malisafi_reject_property', array(__CLASS__, 'ajax_reject_property'));
        add_action('wp_ajax_malisafi_unapprove_property', array(__CLASS__, 'ajax_unapprove_property'));
        
        // Post handlers
        add_action('admin_post_malisafi_moderate_property', array(__CLASS__, 'handle_moderation'));
        add_action('admin_post_malisafi_dismiss_report', array(__CLASS__, 'handle_dismiss_report'));
        
        // Add verification status on property save
        add_action('save_post_malisafi_property', array(__CLASS__, 'set_verification_status'), 10, 2);
        
        // Display verification badge on frontend - DISABLED (badges shown in header instead)
        // add_filter('the_content', array(__CLASS__, 'add_verification_badge'));
    }
    
    /**
     * Set verification status when property is saved
     */
    public static function set_verification_status($post_id, $post) {
        // Skip autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Only for published properties
        if ($post->post_status !== 'publish') {
            return;
        }
        
        // Check if already verified - NEVER reset verified properties
        $current_verification = get_post_meta($post_id, '_malisafi_verified', true);
        if ($current_verification === '1' || $current_verification === 1) {
            return;
        }
        
        // Auto-verify if posted by admin or moderator
        $author = get_user_by('id', $post->post_author);
        if (user_can($author, 'moderate_properties')) {
            update_post_meta($post_id, '_malisafi_verified', 1);
            update_post_meta($post_id, '_malisafi_verified_date', current_time('mysql'));
            update_post_meta($post_id, '_malisafi_verified_by', get_current_user_id());
        } else {
            // Mark as unverified for agents/owners/developers (only if not already set)
            if ($current_verification !== '0' && $current_verification !== 0) {
                update_post_meta($post_id, '_malisafi_verified', 0);
            }
        }
    }
    
    /**
     * AJAX: Report a property
     */
    public static function ajax_report_property() {
        check_ajax_referer('malisafi_report_property', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to report a property.', 'malisafi-mls')));
        }
        
        $property_id = intval($_POST['property_id']);
        $reason = sanitize_text_field($_POST['reason']);
        $details = sanitize_textarea_field($_POST['details'] ?? '');
        
        if (!$property_id || !$reason) {
            wp_send_json_error(array('message' => __('Invalid data provided.', 'malisafi-mls')));
        }
        
        // Create report
        $report_id = self::create_report($property_id, $reason, $details);
        
        if ($report_id) {
            // Update report count
            $report_count = get_post_meta($property_id, '_malisafi_report_count', true);
            $report_count = intval($report_count) + 1;
            update_post_meta($property_id, '_malisafi_report_count', $report_count);
            
            // Notify moderators if threshold reached
            if ($report_count >= 3) {
                self::notify_moderators_high_reports($property_id, $report_count);
            }
            
            wp_send_json_success(array('message' => __('Report submitted successfully. Our team will review it.', 'malisafi-mls')));
        } else {
            wp_send_json_error(array('message' => __('Failed to submit report.', 'malisafi-mls')));
        }
    }
    
    /**
     * Create property report
     */
    private static function create_report($property_id, $reason, $details) {
        global $wpdb;
        $table = $wpdb->prefix . 'mf_property_reports';
        
        // Check if table exists
        if (!self::table_exists()) {
            return false;
        }
        
        return $wpdb->insert(
            $table,
            array(
                'property_id' => $property_id,
                'reporter_id' => get_current_user_id(),
                'reason' => $reason,
                'details' => $details,
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * AJAX: Verify property
     */
    public static function ajax_verify_property() {
        check_ajax_referer('malisafi_moderate_property', 'nonce');
        
        if (!current_user_can('moderate_properties')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'malisafi-mls')));
        }
        
        $property_id = intval($_POST['property_id']);
        
        if (!$property_id) {
            wp_send_json_error(array('message' => __('Invalid property.', 'malisafi-mls')));
        }
        
        // Mark as verified
        update_post_meta($property_id, '_malisafi_verified', 1);
        update_post_meta($property_id, '_malisafi_verified_date', current_time('mysql'));
        update_post_meta($property_id, '_malisafi_verified_by', get_current_user_id());
        
        // Publish if pending
        $property = get_post($property_id);
        if ($property->post_status === 'pending') {
            wp_update_post(array(
                'ID' => $property_id,
                'post_status' => 'publish'
            ));
        }
        
        wp_send_json_success(array('message' => __('Property verified successfully.', 'malisafi-mls')));
    }
    
    /**
     * AJAX: Reject property
     */
    public static function ajax_reject_property() {
        check_ajax_referer('malisafi_moderate_property', 'nonce');
        
        if (!current_user_can('moderate_properties')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'malisafi-mls')));
        }
        
        $property_id = intval($_POST['property_id']);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');
        
        if (!$property_id) {
            wp_send_json_error(array('message' => __('Invalid property.', 'malisafi-mls')));
        }
        
        // Mark as rejected
        update_post_meta($property_id, '_malisafi_verified', -1);
        update_post_meta($property_id, '_malisafi_rejection_reason', $reason);
        update_post_meta($property_id, '_malisafi_rejected_by', get_current_user_id());
        update_post_meta($property_id, '_malisafi_rejected_date', current_time('mysql'));
        
        // Change status to draft
        wp_update_post(array(
            'ID' => $property_id,
            'post_status' => 'draft'
        ));
        
        // Notify property owner
        self::notify_owner_rejection($property_id, $reason);
        
        wp_send_json_success(array('message' => __('Property rejected.', 'malisafi-mls')));
    }
    
    /**
     * AJAX: Unapprove property (revert to pending)
     */
    public static function ajax_unapprove_property() {
        check_ajax_referer('malisafi_moderate_property', 'nonce');
        
        if (!current_user_can('moderate_properties')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'malisafi-mls')));
        }
        
        $property_id = intval($_POST['property_id']);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');
        
        if (!$property_id) {
            wp_send_json_error(array('message' => __('Invalid property.', 'malisafi-mls')));
        }
        
        // Remove verification
        update_post_meta($property_id, '_malisafi_verified', 0);
        delete_post_meta($property_id, '_malisafi_verified_date');
        delete_post_meta($property_id, '_malisafi_verified_by');
        
        // Add unapproval note
        update_post_meta($property_id, '_malisafi_unapproved_reason', $reason);
        update_post_meta($property_id, '_malisafi_unapproved_by', get_current_user_id());
        update_post_meta($property_id, '_malisafi_unapproved_date', current_time('mysql'));
        
        // Change status back to pending
        wp_update_post(array(
            'ID' => $property_id,
            'post_status' => 'pending'
        ));
        
        // Notify property owner
        self::notify_owner_unapproval($property_id, $reason);
        
        wp_send_json_success(array('message' => __('Property approval reverted. Property is now pending review.', 'malisafi-mls')));
    }
    
    /**
     * Handle moderation form submission
     */
    public static function handle_moderation() {
        check_admin_referer('malisafi_moderate_property', 'malisafi_moderation_nonce');
        
        if (!current_user_can('moderate_properties')) {
            wp_die(__('Permission denied.', 'malisafi-mls'));
        }
        
        $property_id = intval($_POST['property_id']);
        $action = sanitize_text_field($_POST['moderation_action']);
        
        if ($action === 'verify') {
            update_post_meta($property_id, '_malisafi_verified', 1);
            update_post_meta($property_id, '_malisafi_verified_date', current_time('mysql'));
            update_post_meta($property_id, '_malisafi_verified_by', get_current_user_id());
            
            // Publish if pending
            $property = get_post($property_id);
            if ($property->post_status === 'pending') {
                wp_update_post(array(
                    'ID' => $property_id,
                    'post_status' => 'publish'
                ));
            }
            
            $message = 'property_verified';
        } else if ($action === 'reject') {
            $reason = sanitize_textarea_field($_POST['rejection_reason'] ?? '');
            
            update_post_meta($property_id, '_malisafi_verified', -1);
            update_post_meta($property_id, '_malisafi_rejection_reason', $reason);
            update_post_meta($property_id, '_malisafi_rejected_by', get_current_user_id());
            update_post_meta($property_id, '_malisafi_rejected_date', current_time('mysql'));
            
            wp_update_post(array(
                'ID' => $property_id,
                'post_status' => 'draft'
            ));
            
            self::notify_owner_rejection($property_id, $reason);
            
            $message = 'property_rejected';
        }
        
        wp_redirect(add_query_arg(array(
            'page' => 'malisafi-moderation',
            'message' => $message
        ), admin_url('admin.php')));
        exit;
    }
    
    /**
     * Handle dismiss report
     */
    public static function handle_dismiss_report() {
        check_admin_referer('malisafi_dismiss_report_' . $_GET['report_id']);
        
        if (!current_user_can('moderate_properties')) {
            wp_die(__('Permission denied.', 'malisafi-mls'));
        }
        
        $report_id = intval($_GET['report_id']);
        
        global $wpdb;
        $table = $wpdb->prefix . 'mf_property_reports';
        
        // Check if table exists
        if (!self::table_exists()) {
            wp_die(__('Database table not found.', 'malisafi-mls'));
        }
        
        $wpdb->update(
            $table,
            array('status' => 'dismissed', 'updated_at' => current_time('mysql')),
            array('id' => $report_id),
            array('%s', '%s'),
            array('%d')
        );
        
        wp_redirect(add_query_arg(array(
            'page' => 'malisafi-moderation',
            'message' => 'report_dismissed'
        ), admin_url('admin.php')));
        exit;
    }
    
    /**
     * Get properties pending verification
     */
    public static function get_pending_verification($args = array()) {
        $defaults = array(
            'post_type' => 'malisafi_property',
            'post_status' => array('publish', 'pending', 'draft'),
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_malisafi_verified',
                    'value' => '0',
                    'compare' => '='
                ),
                array(
                    'key' => '_malisafi_verified',
                    'compare' => 'NOT EXISTS'
                )
            )
        );
        
        $args = wp_parse_args($args, $defaults);
        return new WP_Query($args);
    }
    
    /**
     * Get reported properties
     */
    public static function get_reported_properties($status = 'pending') {
        global $wpdb;
        $table = $wpdb->prefix . 'mf_property_reports';
        
        // Check if table exists
        if (!self::table_exists()) {
            return array();
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, p.post_title, p.post_author, u.user_login as reporter_name
             FROM {$table} r
             LEFT JOIN {$wpdb->posts} p ON r.property_id = p.ID
             LEFT JOIN {$wpdb->users} u ON r.reporter_id = u.ID
             WHERE r.status = %s
             ORDER BY r.created_at DESC",
            $status
        ));
    }
    
    /**
     * Get report count for property
     */
    public static function get_report_count($property_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'mf_property_reports';
        
        // Check if table exists
        if (!self::table_exists()) {
            return 0;
        }
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE property_id = %d AND status = 'pending'",
            $property_id
        ));
    }
    
    /**
     * Check if user has reported property
     */
    public static function has_user_reported($property_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'mf_property_reports';
        
        // Check if table exists
        if (!self::table_exists()) {
            return false;
        }
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE property_id = %d AND reporter_id = %d",
            $property_id, $user_id
        )) > 0;
    }
    
    /**
     * Add verification badge to property content
     */
    public static function add_verification_badge($content) {
        if (!is_singular('malisafi_property')) {
            return $content;
        }
        
        global $post;
        $is_verified = get_post_meta($post->ID, '_malisafi_verified', true);
        
        $badge = '';
        
        if ($is_verified == 1) {
            $verified_date = get_post_meta($post->ID, '_malisafi_verified_date', true);
            $badge = sprintf(
                '<div class="malisafi-verification-badge verified">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span class="badge-text">%s</span>
                    <span class="badge-date">%s</span>
                </div>',
                __('Verified Property', 'malisafi-mls'),
                $verified_date ? sprintf(__('Verified on %s', 'malisafi-mls'), date_i18n('M j, Y', strtotime($verified_date))) : ''
            );
        } else if ($is_verified == 0 || !$is_verified) {
            $badge = sprintf(
                '<div class="malisafi-verification-badge unverified">
                    <span class="dashicons dashicons-warning"></span>
                    <span class="badge-text">%s</span>
                    <p class="badge-description">%s</p>
                </div>',
                __('Unverified Property', 'malisafi-mls'),
                __('This property is pending verification by our team. Information may not be accurate.', 'malisafi-mls')
            );
        } else if ($is_verified == -1) {
            $badge = sprintf(
                '<div class="malisafi-verification-badge rejected">
                    <span class="dashicons dashicons-dismiss"></span>
                    <span class="badge-text">%s</span>
                </div>',
                __('Property Rejected', 'malisafi-mls')
            );
        }
        
        return $badge . $content;
    }
    
    /**
     * Notify property owner of unapproval
     */
    private static function notify_owner_unapproval($property_id, $reason) {
        $property = get_post($property_id);
        $owner = get_user_by('id', $property->post_author);
        
        if (!$owner) {
            return;
        }
        
        $subject = sprintf(__('[Malisafi MLS] Property "%s" approval has been reverted', 'malisafi-mls'), $property->post_title);
        
        $message = sprintf(
            __('Your property "%s" approval has been reverted and is now pending review again.', 'malisafi-mls'),
            $property->post_title
        );
        
        if ($reason) {
            $message .= "\n\n" . sprintf(__('Reason: %s', 'malisafi-mls'), $reason);
        }
        
        $message .= "\n\n" . sprintf(
            __('You can review and update your property here: %s', 'malisafi-mls'),
            admin_url('admin.php?page=malisafi-my-properties')
        );
        
        wp_mail($owner->user_email, $subject, $message);
    }
    
    /**
     * Notify moderators of high report count
     */
    private static function notify_moderators_high_reports($property_id, $count) {
        $property = get_post($property_id);
        $moderators = get_users(array('role__in' => array('administrator', 'malisafi_moderator')));
        
        $subject = sprintf(__('[Malisafi MLS] Property with %d reports needs attention', 'malisafi-mls'), $count);
        $message = sprintf(
            __('The property "%s" has received %d reports and requires your attention.', 'malisafi-mls'),
            $property->post_title,
            $count
        );
        $message .= "\n\n" . admin_url('admin.php?page=malisafi-moderation');
        
        foreach ($moderators as $moderator) {
            wp_mail($moderator->user_email, $subject, $message);
        }
    }
    
    /**
     * Notify property owner of rejection
     */
    private static function notify_owner_rejection($property_id, $reason) {
        $property = get_post($property_id);
        $owner = get_user_by('id', $property->post_author);
        
        $subject = sprintf(__('[Malisafi MLS] Your property "%s" requires changes', 'malisafi-mls'), $property->post_title);
        $message = sprintf(__('Your property "%s" has been reviewed and requires the following changes:', 'malisafi-mls'), $property->post_title);
        $message .= "\n\n" . $reason;
        $message .= "\n\n" . __('Please update your property and resubmit.', 'malisafi-mls');
        $message .= "\n\n" . admin_url('admin.php?page=malisafi-properties&action=edit&property_id=' . $property_id);
        
        wp_mail($owner->user_email, $subject, $message);
    }
    
    /**
     * Get available report reasons
     */
    public static function get_report_reasons() {
        return array(
            'incorrect_info' => __('Incorrect Information', 'malisafi-mls'),
            'fake_listing' => __('Fake/Fraudulent Listing', 'malisafi-mls'),
            'duplicate' => __('Duplicate Listing', 'malisafi-mls'),
            'sold_rented' => __('Already Sold/Rented', 'malisafi-mls'),
            'wrong_price' => __('Wrong Price', 'malisafi-mls'),
            'inappropriate' => __('Inappropriate Content', 'malisafi-mls'),
            'spam' => __('Spam', 'malisafi-mls'),
            'other' => __('Other', 'malisafi-mls')
        );
    }
}
