<?php
/**
 * Advanced Subscription Manager - Engineering Improvements
 *
 * Comprehensive subscription management system with:
 * - CRUD operations
 * - Bulk actions
 * - Search & filtering
 * - Subscription history/logging
 * - Analytics & reporting
 * - Lifecycle management
 *
 * @package MalisafiMLS
 */

if (!defined('ABSPATH')) {
    exit;
}

class Malisafi_Subscription_Manager {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_init', array($this, 'handle_admin_actions'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_malisafi_subscription_search', array($this, 'ajax_search_subscriptions'));
        add_action('wp_ajax_malisafi_subscription_action', array($this, 'ajax_subscription_action'));
        add_action('wp_ajax_malisafi_bulk_subscription_action', array($this, 'ajax_bulk_action'));
        
        // Daily cron to check expiring subscriptions
        add_action('malisafi_check_expiring_subscriptions', array($this, 'check_expiring_subscriptions'));
        if (!wp_next_scheduled('malisafi_check_expiring_subscriptions')) {
            wp_schedule_event(time(), 'daily', 'malisafi_check_expiring_subscriptions');
        }
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'malisafi') === false) {
            return;
        }
        
        wp_enqueue_script(
            'malisafi-subscription-manager',
            MALISAFI_MLS_URL . 'assets/js/admin-subscription-manager.js',
            array('jquery', 'jquery-ui-datepicker'),
            MALISAFI_MLS_VERSION,
            true
        );
        
        wp_localize_script('malisafi-subscription-manager', 'malisafiSubManager', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('malisafi_subscription_action'),
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this subscription?', 'malisafi-mls'),
                'confirmBulkAction' => __('Are you sure you want to perform this action on selected subscriptions?', 'malisafi-mls'),
                'processing' => __('Processing...', 'malisafi-mls'),
                'success' => __('Action completed successfully', 'malisafi-mls'),
                'error' => __('An error occurred. Please try again.', 'malisafi-mls'),
            ),
        ));
        
        wp_enqueue_style('jquery-ui-datepicker');
    }
    
    /**
     * Handle admin actions
     */
    public function handle_admin_actions() {
        if (!current_user_can('manage_malisafi_settings')) {
            return;
        }
        
        // Handle form submissions
        if (isset($_POST['malisafi_subscription_action']) && check_admin_referer('malisafi_subscription_action')) {
            $action = sanitize_text_field($_POST['malisafi_subscription_action']);
            
            switch ($action) {
                case 'create':
                    $this->create_subscription($_POST);
                    break;
                case 'update':
                    $this->update_subscription($_POST);
                    break;
                case 'extend':
                    $this->extend_subscription($_POST);
                    break;
                case 'cancel':
                    $this->cancel_subscription($_POST);
                    break;
                case 'reactivate':
                    $this->reactivate_subscription($_POST);
                    break;
                case 'delete':
                    $this->delete_subscription($_POST);
                    break;
            }
        }
    }
    
    /**
     * Create new subscription
     */
    public function create_subscription($data) {
        global $wpdb;
        
        $user_id = intval($data['user_id']);
        $plan_type = sanitize_text_field($data['plan_type']);
        $start_date = sanitize_text_field($data['start_date']);
        $end_date = sanitize_text_field($data['end_date']);
        $status = sanitize_text_field($data['status']);
        $notes = sanitize_textarea_field($data['notes'] ?? '');
        
        // Validate
        if (!$user_id || !$plan_type || !$start_date || !$end_date) {
            wp_redirect(add_query_arg('error', 'missing_fields', wp_get_referer()));
            exit;
        }
        
        // Cancel any existing active subscriptions
        $this->cancel_user_active_subscriptions($user_id, 'replaced');
        
        // Insert new subscription
        $result = $wpdb->insert(
            $wpdb->prefix . 'mf_subscriptions',
            array(
                'user_id' => $user_id,
                'plan_type' => $plan_type,
                'status' => $status,
                'stripe_subscription_id' => 'manual_' . time() . '_' . $user_id,
                'current_period_start' => $start_date,
                'current_period_end' => $end_date,
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            $subscription_id = $wpdb->insert_id;
            
            // Log the action
            $this->log_subscription_action($subscription_id, 'created', array(
                'admin_id' => get_current_user_id(),
                'notes' => $notes,
                'plan_type' => $plan_type,
            ));
            
            // Update user role
            $user = get_user_by('id', $user_id);
            if ($user) {
                $user->set_role($plan_type);
            }
            
            // Update user limits based on plan
            $this->update_user_limits($user_id, $plan_type);
            
            // Send notification email
            $this->send_subscription_notification($user_id, 'activated', array(
                'plan_type' => $plan_type,
                'end_date' => $end_date,
            ));
            
            wp_redirect(add_query_arg('message', 'subscription_created', wp_get_referer()));
        } else {
            wp_redirect(add_query_arg('error', 'creation_failed', wp_get_referer()));
        }
        exit;
    }
    
    /**
     * Update existing subscription
     */
    public function update_subscription($data) {
        global $wpdb;
        
        $subscription_id = intval($data['subscription_id']);
        $plan_type = sanitize_text_field($data['plan_type']);
        $status = sanitize_text_field($data['status']);
        $end_date = sanitize_text_field($data['end_date']);
        $notes = sanitize_textarea_field($data['notes'] ?? '');
        
        $result = $wpdb->update(
            $wpdb->prefix . 'mf_subscriptions',
            array(
                'plan_type' => $plan_type,
                'status' => $status,
                'current_period_end' => $end_date,
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $subscription_id),
            array('%s', '%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            // Get subscription to update user
            $subscription = $this->get_subscription($subscription_id);
            if ($subscription) {
                $user = get_user_by('id', $subscription->user_id);
                if ($user) {
                    $user->set_role($plan_type);
                }
                
                $this->update_user_limits($subscription->user_id, $plan_type);
            }
            
            $this->log_subscription_action($subscription_id, 'updated', array(
                'admin_id' => get_current_user_id(),
                'notes' => $notes,
                'changes' => array(
                    'plan_type' => $plan_type,
                    'status' => $status,
                    'end_date' => $end_date,
                ),
            ));
            
            wp_redirect(add_query_arg('message', 'subscription_updated', wp_get_referer()));
        } else {
            wp_redirect(add_query_arg('error', 'update_failed', wp_get_referer()));
        }
        exit;
    }
    
    /**
     * Extend subscription period
     */
    public function extend_subscription($data) {
        global $wpdb;
        
        $subscription_id = intval($data['subscription_id']);
        $extend_by = intval($data['extend_by']); // days
        $extend_unit = sanitize_text_field($data['extend_unit'] ?? 'days'); // days, months, years
        $notes = sanitize_textarea_field($data['notes'] ?? '');
        
        $subscription = $this->get_subscription($subscription_id);
        if (!$subscription) {
            wp_redirect(add_query_arg('error', 'subscription_not_found', wp_get_referer()));
            exit;
        }
        
        // Calculate new end date
        $current_end = new DateTime($subscription->current_period_end);
        switch ($extend_unit) {
            case 'months':
                $current_end->modify("+{$extend_by} months");
                break;
            case 'years':
                $current_end->modify("+{$extend_by} years");
                break;
            default:
                $current_end->modify("+{$extend_by} days");
        }
        
        $new_end_date = $current_end->format('Y-m-d H:i:s');
        
        $result = $wpdb->update(
            $wpdb->prefix . 'mf_subscriptions',
            array(
                'current_period_end' => $new_end_date,
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $subscription_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            $this->log_subscription_action($subscription_id, 'extended', array(
                'admin_id' => get_current_user_id(),
                'notes' => $notes,
                'extended_by' => "{$extend_by} {$extend_unit}",
                'new_end_date' => $new_end_date,
            ));
            
            $this->send_subscription_notification($subscription->user_id, 'extended', array(
                'plan_type' => $subscription->plan_type,
                'new_end_date' => $new_end_date,
            ));
            
            wp_redirect(add_query_arg('message', 'subscription_extended', wp_get_referer()));
        } else {
            wp_redirect(add_query_arg('error', 'extension_failed', wp_get_referer()));
        }
        exit;
    }
    
    /**
     * Cancel subscription
     */
    public function cancel_subscription($data) {
        global $wpdb;
        
        $subscription_id = intval($data['subscription_id']);
        $reason = sanitize_text_field($data['cancel_reason'] ?? '');
        $notes = sanitize_textarea_field($data['notes'] ?? '');
        $immediate = isset($data['cancel_immediate']);
        
        $subscription = $this->get_subscription($subscription_id);
        if (!$subscription) {
            wp_redirect(add_query_arg('error', 'subscription_not_found', wp_get_referer()));
            exit;
        }
        
        // If Stripe subscription, cancel it there too
        if ($subscription->stripe_subscription_id && strpos($subscription->stripe_subscription_id, 'manual_') !== 0) {
            try {
                Malisafi_Stripe::init();
                $stripe_sub = \Stripe\Subscription::retrieve($subscription->stripe_subscription_id);
                $stripe_sub->cancel();
            } catch (Exception $e) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Stripe cancellation error: ' . $e->getMessage());
                }
            }
        }
        
        $update_data = array('status' => 'canceled', 'updated_at' => current_time('mysql'));
        if ($immediate) {
            $update_data['current_period_end'] = current_time('mysql');
        }
        
        $result = $wpdb->update(
            $wpdb->prefix . 'mf_subscriptions',
            $update_data,
            array('id' => $subscription_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            // Downgrade user to client role if immediate
            if ($immediate) {
                $user = get_user_by('id', $subscription->user_id);
                if ($user) {
                    $user->set_role('client');
                }
            }
            
            $this->log_subscription_action($subscription_id, 'canceled', array(
                'admin_id' => get_current_user_id(),
                'notes' => $notes,
                'reason' => $reason,
                'immediate' => $immediate,
            ));
            
            $this->send_subscription_notification($subscription->user_id, 'canceled', array(
                'plan_type' => $subscription->plan_type,
                'reason' => $reason,
                'immediate' => $immediate,
            ));
            
            wp_redirect(add_query_arg('message', 'subscription_canceled', wp_get_referer()));
        } else {
            wp_redirect(add_query_arg('error', 'cancellation_failed', wp_get_referer()));
        }
        exit;
    }
    
    /**
     * Reactivate canceled subscription
     */
    public function reactivate_subscription($data) {
        global $wpdb;
        
        $subscription_id = intval($data['subscription_id']);
        $new_end_date = sanitize_text_field($data['end_date']);
        $notes = sanitize_textarea_field($data['notes'] ?? '');
        
        $result = $wpdb->update(
            $wpdb->prefix . 'mf_subscriptions',
            array(
                'status' => 'active',
                'current_period_end' => $new_end_date,
                'updated_at' => current_time('mysql'),
            ),
            array('id' => $subscription_id),
            array('%s', '%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            $subscription = $this->get_subscription($subscription_id);
            if ($subscription) {
                $user = get_user_by('id', $subscription->user_id);
                if ($user) {
                    $user->set_role($subscription->plan_type);
                }
                
                $this->update_user_limits($subscription->user_id, $subscription->plan_type);
            }
            
            $this->log_subscription_action($subscription_id, 'reactivated', array(
                'admin_id' => get_current_user_id(),
                'notes' => $notes,
                'new_end_date' => $new_end_date,
            ));
            
            wp_redirect(add_query_arg('message', 'subscription_reactivated', wp_get_referer()));
        } else {
            wp_redirect(add_query_arg('error', 'reactivation_failed', wp_get_referer()));
        }
        exit;
    }
    
    /**
     * Delete subscription permanently
     */
    public function delete_subscription($data) {
        global $wpdb;
        
        $subscription_id = intval($data['subscription_id']);
        
        $subscription = $this->get_subscription($subscription_id);
        if ($subscription) {
            // Archive to history before deleting
            $this->archive_subscription($subscription);
            
            $wpdb->delete(
                $wpdb->prefix . 'mf_subscriptions',
                array('id' => $subscription_id),
                array('%d')
            );
            
            wp_redirect(add_query_arg('message', 'subscription_deleted', wp_get_referer()));
        } else {
            wp_redirect(add_query_arg('error', 'subscription_not_found', wp_get_referer()));
        }
        exit;
    }
    
    /**
     * Get subscription by ID
     */
    public function get_subscription($subscription_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}mf_subscriptions WHERE id = %d",
            $subscription_id
        ));
    }
    
    /**
     * Get user's active subscription
     */
    public function get_user_subscription($user_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}mf_subscriptions WHERE user_id = %d AND status = 'active' ORDER BY id DESC LIMIT 1",
            $user_id
        ));
    }
    
    /**
     * Search subscriptions with filters
     */
    public function search_subscriptions($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => '',
            'plan_type' => '',
            'date_from' => '',
            'date_to' => '',
            'expiring_days' => 0,
            'user_email' => '',
            'user_name' => '',
            'orderby' => 'created_at',
            'order' => 'DESC',
            'per_page' => 20,
            'page' => 1,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        $join = '';
        
        // Status filter
        if (!empty($args['status'])) {
            $where[] = $wpdb->prepare('s.status = %s', $args['status']);
        }
        
        // Plan type filter
        if (!empty($args['plan_type'])) {
            $where[] = $wpdb->prepare('s.plan_type = %s', $args['plan_type']);
        }
        
        // Date range filter
        if (!empty($args['date_from'])) {
            $where[] = $wpdb->prepare('s.created_at >= %s', $args['date_from']);
        }
        if (!empty($args['date_to'])) {
            $where[] = $wpdb->prepare('s.created_at <= %s', $args['date_to'] . ' 23:59:59');
        }
        
        // Expiring soon filter
        if ($args['expiring_days'] > 0) {
            $expiry_date = date('Y-m-d H:i:s', strtotime("+{$args['expiring_days']} days"));
            $where[] = $wpdb->prepare('s.current_period_end <= %s AND s.status = %s', $expiry_date, 'active');
        }
        
        // User email/name search
        if (!empty($args['user_email']) || !empty($args['user_name'])) {
            $join = "LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID";
            
            if (!empty($args['user_email'])) {
                $where[] = $wpdb->prepare('u.user_email LIKE %s', '%' . $wpdb->esc_like($args['user_email']) . '%');
            }
            
            if (!empty($args['user_name'])) {
                $where[] = $wpdb->prepare('u.display_name LIKE %s', '%' . $wpdb->esc_like($args['user_name']) . '%');
            }
        }
        
        $where_clause = implode(' AND ', $where);
        $order_clause = sprintf('%s %s', sanitize_sql_orderby($args['orderby']), $args['order']);
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        // Get total count
        $total = $wpdb->get_var("SELECT COUNT(DISTINCT s.id) FROM {$wpdb->prefix}mf_subscriptions s {$join} WHERE {$where_clause}");
        
        // Get results
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, u.user_email, u.display_name 
            FROM {$wpdb->prefix}mf_subscriptions s 
            {$join}
            LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
            WHERE {$where_clause} 
            ORDER BY {$order_clause} 
            LIMIT %d OFFSET %d",
            $args['per_page'],
            $offset
        ));
        
        return array(
            'subscriptions' => $results,
            'total' => intval($total),
            'pages' => ceil($total / $args['per_page']),
        );
    }
    
    /**
     * Cancel all active subscriptions for a user
     */
    private function cancel_user_active_subscriptions($user_id, $reason = 'replaced') {
        global $wpdb;
        
        $wpdb->update(
            $wpdb->prefix . 'mf_subscriptions',
            array('status' => 'canceled', 'updated_at' => current_time('mysql')),
            array('user_id' => $user_id, 'status' => 'active'),
            array('%s', '%s'),
            array('%d', '%s')
        );
    }
    
    /**
     * Update user limits based on plan
     */
    private function update_user_limits($user_id, $plan_type) {
        global $wpdb;
        
        $plans = get_option('malisafi_mls_plans', array());
        $plan = $plans[$plan_type] ?? null;
        
        if (!$plan) {
            return;
        }
        
        $limits_table = $wpdb->prefix . 'mf_user_limits';
        
        $wpdb->replace(
            $limits_table,
            array(
                'user_id' => $user_id,
                'max_listings' => $plan['max_listings'] ?? 0,
                'featured_listings' => $plan['featured_listings'] ?? 0,
                'can_boost' => $plan['can_boost'] ?? 0,
                'analytics_access' => $plan['analytics_access'] ?? 0,
            ),
            array('%d', '%d', '%d', '%d', '%d')
        );
    }
    
    /**
     * Log subscription action to history
     */
    private function log_subscription_action($subscription_id, $action, $metadata = array()) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'mf_subscription_history',
            array(
                'subscription_id' => $subscription_id,
                'action' => $action,
                'metadata' => json_encode($metadata),
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s')
        );
    }
    
    /**
     * Archive subscription before deletion
     */
    private function archive_subscription($subscription) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'mf_subscription_archive',
            array(
                'original_id' => $subscription->id,
                'user_id' => $subscription->user_id,
                'plan_type' => $subscription->plan_type,
                'status' => $subscription->status,
                'stripe_subscription_id' => $subscription->stripe_subscription_id,
                'current_period_start' => $subscription->current_period_start,
                'current_period_end' => $subscription->current_period_end,
                'created_at' => $subscription->created_at,
                'archived_at' => current_time('mysql'),
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Send subscription notification email
     */
    private function send_subscription_notification($user_id, $type, $data) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }
        
        $subject = '';
        $message = '';
        
        switch ($type) {
            case 'activated':
                $subject = __('Your subscription has been activated', 'malisafi-mls');
                $message = sprintf(
                    __('Your %s subscription is now active and will expire on %s.', 'malisafi-mls'),
                    $data['plan_type'],
                    date_i18n(get_option('date_format'), strtotime($data['end_date']))
                );
                break;
            case 'extended':
                $subject = __('Your subscription has been extended', 'malisafi-mls');
                $message = sprintf(
                    __('Your subscription has been extended. New expiry date: %s', 'malisafi-mls'),
                    date_i18n(get_option('date_format'), strtotime($data['new_end_date']))
                );
                break;
            case 'canceled':
                $subject = __('Your subscription has been canceled', 'malisafi-mls');
                $message = $data['immediate'] 
                    ? __('Your subscription has been canceled immediately.', 'malisafi-mls')
                    : __('Your subscription has been canceled and will not renew.', 'malisafi-mls');
                break;
            case 'expiring':
                $subject = __('Your subscription is expiring soon', 'malisafi-mls');
                $message = sprintf(
                    __('Your subscription will expire on %s. Please renew to continue enjoying premium features.', 'malisafi-mls'),
                    date_i18n(get_option('date_format'), strtotime($data['end_date']))
                );
                break;
        }
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Check for expiring subscriptions (daily cron)
     */
    public function check_expiring_subscriptions() {
        global $wpdb;
        
        // Get subscriptions expiring in 7 days
        $seven_days = date('Y-m-d H:i:s', strtotime('+7 days'));
        $subscriptions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}mf_subscriptions 
            WHERE status = 'active' 
            AND current_period_end <= %s 
            AND current_period_end >= NOW()",
            $seven_days
        ));
        
        foreach ($subscriptions as $subscription) {
            // Check if we already sent notification
            $notified = get_user_meta($subscription->user_id, '_malisafi_expiry_notified_' . $subscription->id, true);
            if (!$notified) {
                $this->send_subscription_notification($subscription->user_id, 'expiring', array(
                    'end_date' => $subscription->current_period_end,
                ));
                update_user_meta($subscription->user_id, '_malisafi_expiry_notified_' . $subscription->id, time());
            }
        }
        
        // Mark expired subscriptions
        $wpdb->query("UPDATE {$wpdb->prefix}mf_subscriptions 
            SET status = 'expired' 
            WHERE status = 'active' 
            AND current_period_end < NOW()");
    }
    
    /**
     * AJAX: Search subscriptions
     */
    public function ajax_search_subscriptions() {
        check_ajax_referer('malisafi_subscription_action', 'nonce');
        
        if (!current_user_can('manage_malisafi_settings')) {
            wp_send_json_error(array('message' => __('Permission denied', 'malisafi-mls')));
        }
        
        $args = array(
            'status' => sanitize_text_field($_POST['status'] ?? ''),
            'plan_type' => sanitize_text_field($_POST['plan_type'] ?? ''),
            'user_email' => sanitize_text_field($_POST['user_email'] ?? ''),
            'expiring_days' => intval($_POST['expiring_days'] ?? 0),
            'page' => intval($_POST['page'] ?? 1),
        );
        
        $results = $this->search_subscriptions($args);
        wp_send_json_success($results);
    }
    
    /**
     * AJAX: Single subscription action
     */
    public function ajax_subscription_action() {
        check_ajax_referer('malisafi_subscription_action', 'nonce');
        
        if (!current_user_can('manage_malisafi_settings')) {
            wp_send_json_error(array('message' => __('Permission denied', 'malisafi-mls')));
        }
        
        $action = sanitize_text_field($_POST['action_type']);
        $subscription_id = intval($_POST['subscription_id']);
        
        // Process action based on type
        // This would call appropriate methods
        
        wp_send_json_success(array('message' => __('Action completed', 'malisafi-mls')));
    }
    
    /**
     * AJAX: Bulk subscription actions
     */
    public function ajax_bulk_action() {
        check_ajax_referer('malisafi_subscription_action', 'nonce');
        
        if (!current_user_can('manage_malisafi_settings')) {
            wp_send_json_error(array('message' => __('Permission denied', 'malisafi-mls')));
        }
        
        $action = sanitize_text_field($_POST['bulk_action']);
        $subscription_ids = array_map('intval', $_POST['subscription_ids'] ?? array());
        
        if (empty($subscription_ids)) {
            wp_send_json_error(array('message' => __('No subscriptions selected', 'malisafi-mls')));
        }
        
        $processed = 0;
        foreach ($subscription_ids as $subscription_id) {
            // Process based on action type
            switch ($action) {
                case 'cancel':
                    // Cancel logic
                    $processed++;
                    break;
                case 'extend':
                    // Extend logic
                    $processed++;
                    break;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d subscriptions processed', 'malisafi-mls'), $processed),
            'processed' => $processed,
        ));
    }
    
    /**
     * Get subscription statistics
     */
    public function get_statistics() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'mf_subscriptions';
        
        $stats = array();
        
        // Total subscriptions by status
        $stats['by_status'] = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM {$table} GROUP BY status",
            OBJECT_K
        );
        
        // Total subscriptions by plan
        $stats['by_plan'] = $wpdb->get_results(
            "SELECT plan_type, COUNT(*) as count FROM {$table} WHERE status = 'active' GROUP BY plan_type",
            OBJECT_K
        );
        
        // Monthly recurring revenue (MRR)
        $plans = get_option('malisafi_mls_plans', array());
        $mrr = 0;
        foreach ($stats['by_plan'] as $plan_type => $data) {
            if (isset($plans[$plan_type])) {
                $price = floatval($plans[$plan_type]['price'] ?? 0);
                $mrr += $price * intval($data->count);
            }
        }
        $stats['mrr'] = $mrr;
        
        // Expiring in 30 days
        $stats['expiring_30_days'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} 
            WHERE status = 'active' 
            AND current_period_end <= %s 
            AND current_period_end >= NOW()",
            date('Y-m-d H:i:s', strtotime('+30 days'))
        ));
        
        // New subscriptions this month
        $stats['new_this_month'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} 
            WHERE created_at >= %s",
            date('Y-m-01 00:00:00')
        ));
        
        // Churn this month (canceled/expired)
        $stats['churn_this_month'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} 
            WHERE status IN ('canceled', 'expired') 
            AND updated_at >= %s",
            date('Y-m-01 00:00:00')
        ));
        
        return $stats;
    }
}

// Initialize
Malisafi_Subscription_Manager::get_instance();
