<?php
/**
 * Plan Manager - Handle user plan assignments and checks
 *
 * @package MalisafiMLS
 */

namespace MalisafiMLS;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plan_Manager class
 * Manages user subscriptions and plan assignments
 */
class Plan_Manager {
    
    /**
     * Initialize plan manager
     */
    public static function init() {
        // AJAX handlers
        add_action('wp_ajax_malisafi_assign_plan', array(__CLASS__, 'ajax_assign_plan'));
        add_action('wp_ajax_malisafi_remove_plan', array(__CLASS__, 'ajax_remove_plan'));
        add_action('wp_ajax_malisafi_delete_subscription', array(__CLASS__, 'ajax_delete_subscription'));
        add_action('wp_ajax_malisafi_extend_subscription', array(__CLASS__, 'ajax_extend_subscription'));
        add_action('wp_ajax_malisafi_update_subscription_dates', array(__CLASS__, 'ajax_update_subscription_dates'));
        add_action('wp_ajax_malisafi_check_user_plan', array(__CLASS__, 'ajax_check_user_plan'));
        
        // Shortcode for plan status
        add_shortcode('malisafi_plan_status', array(__CLASS__, 'plan_status_shortcode'));
        
        // Enqueue scripts
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_frontend_scripts'));
    }
    
    /**
     * Check if user has an active plan
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public static function user_has_plan($user_id) {
        $subscription = self::get_user_subscription($user_id);
        return $subscription && $subscription->status === 'active';
    }
    
    /**
     * Get user subscription
     * 
     * @param int $user_id User ID
     * @return object|null Subscription object or null
     */
    public static function get_user_subscription($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'mf_subscriptions';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC LIMIT 1",
            $user_id
        ));
    }
    
    /**
     * Get plan details for a user
     * 
     * @param int $user_id User ID
     * @return array|null Plan details or null
     */
    public static function get_user_plan_details($user_id) {
        $subscription = self::get_user_subscription($user_id);
        if (!$subscription) {
            return null;
        }
        
        // Get plan details from Stripe class
        if (class_exists('Malisafi_Stripe')) {
            $plans = \Malisafi_Stripe::get_plans();
            if (isset($plans[$subscription->plan_type])) {
                $plan = $plans[$subscription->plan_type];
                $plan['subscription_status'] = $subscription->status;
                $plan['subscription_id'] = $subscription->id;
                $plan['current_period_end'] = $subscription->current_period_end;
                return $plan;
            }
        }
        
        // Fallback plan info
        return array(
            'name' => ucwords(str_replace('_', ' ', $subscription->plan_type)),
            'subscription_status' => $subscription->status,
            'subscription_id' => $subscription->id,
            'plan_type' => $subscription->plan_type,
            'current_period_end' => $subscription->current_period_end
        );
    }
    
    /**
     * Assign plan to user (Admin only)
     * 
     * @param int $user_id User ID
     * @param string $plan_type Plan type
     * @param int $duration_months Duration in months (default: 12)
     * @return bool|WP_Error
     */
    public static function assign_plan($user_id, $plan_type, $duration_months = 12) {
        global $wpdb;
        
        // Validate user
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return new \WP_Error('invalid_user', __('Invalid user ID.', 'malisafi-mls'));
        }
        
        // Validate plan type
        $valid_plans = array('agent_basic', 'agent_premium', 'owner_basic', 'developer');
        if (!in_array($plan_type, $valid_plans)) {
            return new \WP_Error('invalid_plan', __('Invalid plan type.', 'malisafi-mls'));
        }
        
        $table = $wpdb->prefix . 'mf_subscriptions';
        
        // Check if user already has a subscription
        $existing = self::get_user_subscription($user_id);
        
        $start_date = current_time('mysql');
        $end_date = date('Y-m-d H:i:s', strtotime("+{$duration_months} months"));
        
        if ($existing) {
            // Update existing subscription
            $result = $wpdb->update(
                $table,
                array(
                    'plan_type' => $plan_type,
                    'status' => 'active',
                    'current_period_start' => $start_date,
                    'current_period_end' => $end_date,
                    'updated_at' => $start_date
                ),
                array('id' => $existing->id),
                array('%s', '%s', '%s', '%s', '%s'),
                array('%d')
            );
        } else {
            // Create new subscription
            $result = $wpdb->insert(
                $table,
                array(
                    'user_id' => $user_id,
                    'plan_type' => $plan_type,
                    'status' => 'active',
                    'current_period_start' => $start_date,
                    'current_period_end' => $end_date,
                    'created_at' => $start_date,
                    'updated_at' => $start_date
                ),
                array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
            );
        }
        
        if ($result === false) {
            return new \WP_Error('db_error', __('Failed to assign plan.', 'malisafi-mls'));
        }
        
        // Update user role based on plan
        $role_map = array(
            'agent_basic' => 'malisafi_agent_basic',
            'agent_premium' => 'malisafi_agent_premium',
            'owner_basic' => 'malisafi_owner',
            'developer' => 'malisafi_developer'
        );
        
        if (isset($role_map[$plan_type])) {
            $user->set_role($role_map[$plan_type]);
        }
        
        // Update user limits
        self::update_user_limits($user_id, $plan_type);
        
        // Log action
        do_action('malisafi_plan_assigned', $user_id, $plan_type, $duration_months);
        
        return true;
    }
    
    /**
     * Remove/cancel user plan
     * 
     * @param int $user_id User ID
     * @return bool|WP_Error
     */
    public static function remove_plan($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'mf_subscriptions';
        
        $subscription = self::get_user_subscription($user_id);
        if (!$subscription) {
            return new \WP_Error('no_subscription', __('User has no active subscription.', 'malisafi-mls'));
        }
        
        $result = $wpdb->update(
            $table,
            array(
                'status' => 'canceled',
                'updated_at' => current_time('mysql')
            ),
            array('id' => $subscription->id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            return new \WP_Error('db_error', __('Failed to cancel subscription.', 'malisafi-mls'));
        }
        
        // Log action
        do_action('malisafi_plan_removed', $user_id, $subscription->plan_type);
        
        return true;
    }
    
    /**
     * Permanently delete user subscription (Admin only)
     * 
     * @param int $user_id User ID
     * @return bool|WP_Error
     */
    public static function delete_subscription($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'mf_subscriptions';
        
        $subscription = self::get_user_subscription($user_id);
        if (!$subscription) {
            return new \WP_Error('no_subscription', __('User has no subscription to delete.', 'malisafi-mls'));
        }
        
        // Delete from database
        $result = $wpdb->delete(
            $table,
            array('id' => $subscription->id),
            array('%d')
        );
        
        if ($result === false) {
            return new \WP_Error('db_error', __('Failed to delete subscription.', 'malisafi-mls'));
        }
        
        // Optionally remove user limits
        $limits_table = $wpdb->prefix . 'mf_user_limits';
        $wpdb->delete($limits_table, array('user_id' => $user_id), array('%d'));
        
        // Log action
        do_action('malisafi_subscription_deleted', $user_id, $subscription->plan_type);
        
        return true;
    }
    
    /**
     * Extend user subscription by adding months to end date
     * 
     * @param int $user_id User ID
     * @param int $months Number of months to extend
     * @return bool|WP_Error
     */
    public static function extend_subscription($user_id, $months = 1) {
        global $wpdb;
        $table = $wpdb->prefix . 'mf_subscriptions';
        
        $subscription = self::get_user_subscription($user_id);
        if (!$subscription) {
            return new \WP_Error('no_subscription', __('User has no subscription to extend.', 'malisafi-mls'));
        }
        
        // Calculate new end date
        $current_end = $subscription->current_period_end;
        $new_end = date('Y-m-d H:i:s', strtotime($current_end . " +{$months} months"));
        
        $result = $wpdb->update(
            $table,
            array(
                'current_period_end' => $new_end,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $subscription->id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result === false) {
            return new \WP_Error('db_error', __('Failed to extend subscription.', 'malisafi-mls'));
        }
        
        // Log action
        do_action('malisafi_subscription_extended', $user_id, $months, $new_end);
        
        return true;
    }
    
    /**
     * Update subscription dates manually (Admin only)
     * 
     * @param int $user_id User ID
     * @param string $start_date Start date (Y-m-d H:i:s)
     * @param string $end_date End date (Y-m-d H:i:s)
     * @return bool|WP_Error
     */
    public static function update_subscription_dates($user_id, $start_date = null, $end_date = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'mf_subscriptions';
        
        $subscription = self::get_user_subscription($user_id);
        if (!$subscription) {
            return new \WP_Error('no_subscription', __('User has no subscription to update.', 'malisafi-mls'));
        }
        
        $update_data = array('updated_at' => current_time('mysql'));
        $format = array('%s');
        
        if ($start_date) {
            $update_data['current_period_start'] = $start_date;
            $format[] = '%s';
        }
        
        if ($end_date) {
            $update_data['current_period_end'] = $end_date;
            $format[] = '%s';
        }
        
        if (count($update_data) === 1) {
            return new \WP_Error('no_dates', __('No dates provided to update.', 'malisafi-mls'));
        }
        
        $result = $wpdb->update(
            $table,
            $update_data,
            array('id' => $subscription->id),
            $format,
            array('%d')
        );
        
        if ($result === false) {
            return new \WP_Error('db_error', __('Failed to update subscription dates.', 'malisafi-mls'));
        }
        
        // Log action
        do_action('malisafi_subscription_dates_updated', $user_id, $start_date, $end_date);
        
        return true;
    }
    
    /**
     * Update user limits based on plan
     * 
     * @param int $user_id User ID
     * @param string $plan_type Plan type
     */
    private static function update_user_limits($user_id, $plan_type) {
        global $wpdb;
        $table = $wpdb->prefix . 'mf_user_limits';
        
        // Get plan limits from Stripe class
        $limits = array(
            'max_listings' => 0,
            'featured_listings' => 0,
            'can_boost' => false,
            'analytics_access' => false
        );
        
        if (class_exists('Malisafi_Stripe')) {
            $plans = \Malisafi_Stripe::get_plans();
            if (isset($plans[$plan_type])) {
                $plan = $plans[$plan_type];
                $limits = array(
                    'max_listings' => $plan['max_listings'] ?? 0,
                    'featured_listings' => $plan['featured_listings'] ?? 0,
                    'can_boost' => !empty($plan['can_boost']),
                    'analytics_access' => !empty($plan['analytics_access'])
                );
            }
        }
        
        // Check if user limits exist
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d",
            $user_id
        ));
        
        if ($existing) {
            // Update existing
            $wpdb->update(
                $table,
                $limits,
                array('user_id' => $user_id),
                array('%d', '%d', '%d', '%d'),
                array('%d')
            );
        } else {
            // Insert new
            $limits['user_id'] = $user_id;
            $limits['used_listings'] = 0;
            $wpdb->insert($table, $limits);
        }
    }
    
    /**
     * AJAX: Assign plan to user (Admin only)
     */
    public static function ajax_assign_plan() {
        // Check permissions
        if (!current_user_can('manage_malisafi_settings') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'malisafi-mls')));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'malisafi_admin_plan_nonce')) {
            wp_send_json_error(array('message' => __('Invalid security token.', 'malisafi-mls')));
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $plan_type = isset($_POST['plan_type']) ? sanitize_text_field($_POST['plan_type']) : '';
        $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 12;
        
        if (!$user_id || !$plan_type) {
            wp_send_json_error(array('message' => __('Missing required fields.', 'malisafi-mls')));
        }
        
        $result = self::assign_plan($user_id, $plan_type, $duration);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array(
            'message' => __('Plan assigned successfully!', 'malisafi-mls'),
            'plan_type' => $plan_type,
            'duration' => $duration
        ));
    }
    
    /**
     * AJAX: Remove plan from user (Admin only)
     */
    public static function ajax_remove_plan() {
        // Check permissions
        if (!current_user_can('manage_malisafi_settings') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'malisafi-mls')));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'malisafi_admin_plan_nonce')) {
            wp_send_json_error(array('message' => __('Invalid security token.', 'malisafi-mls')));
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Missing user ID.', 'malisafi-mls')));
        }
        
        $result = self::remove_plan($user_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => __('Plan removed successfully!', 'malisafi-mls')));
    }
    
    /**
     * AJAX: Delete subscription permanently (Admin only)
     */
    public static function ajax_delete_subscription() {
        // Check permissions
        if (!current_user_can('manage_malisafi_settings') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'malisafi-mls')));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'malisafi_admin_plan_nonce')) {
            wp_send_json_error(array('message' => __('Invalid security token.', 'malisafi-mls')));
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Missing user ID.', 'malisafi-mls')));
        }
        
        $result = self::delete_subscription($user_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => __('Subscription deleted permanently!', 'malisafi-mls')));
    }
    
    /**
     * AJAX: Extend subscription (Admin only)
     */
    public static function ajax_extend_subscription() {
        // Check permissions
        if (!current_user_can('manage_malisafi_settings') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'malisafi-mls')));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'malisafi_admin_plan_nonce')) {
            wp_send_json_error(array('message' => __('Invalid security token.', 'malisafi-mls')));
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $months = isset($_POST['months']) ? intval($_POST['months']) : 1;
        
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Missing user ID.', 'malisafi-mls')));
        }
        
        $result = self::extend_subscription($user_id, $months);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        // Get updated subscription
        $subscription = self::get_user_subscription($user_id);
        
        wp_send_json_success(array(
            'message' => sprintf(__('Subscription extended by %d month(s)!', 'malisafi-mls'), $months),
            'new_end_date' => date_i18n(get_option('date_format'), strtotime($subscription->current_period_end))
        ));
    }
    
    /**
     * AJAX: Update subscription dates (Admin only)
     */
    public static function ajax_update_subscription_dates() {
        // Check permissions
        if (!current_user_can('manage_malisafi_settings') && !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'malisafi-mls')));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'malisafi_admin_plan_nonce')) {
            wp_send_json_error(array('message' => __('Invalid security token.', 'malisafi-mls')));
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : null;
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : null;
        
        if (!$user_id) {
            wp_send_json_error(array('message' => __('Missing user ID.', 'malisafi-mls')));
        }
        
        // Convert dates to MySQL format if provided
        if ($start_date) {
            $start_date = date('Y-m-d H:i:s', strtotime($start_date));
        }
        if ($end_date) {
            $end_date = date('Y-m-d H:i:s', strtotime($end_date));
        }
        
        $result = self::update_subscription_dates($user_id, $start_date, $end_date);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => __('Subscription dates updated successfully!', 'malisafi-mls')));
    }
    
    /**
     * AJAX: Check user plan (Frontend)
     */
    public static function ajax_check_user_plan() {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Please log in.', 'malisafi-mls')));
        }
        
        $user_id = get_current_user_id();
        $plan_details = self::get_user_plan_details($user_id);
        
        wp_send_json_success(array(
            'has_plan' => !empty($plan_details),
            'plan' => $plan_details
        ));
    }
    
    /**
     * Shortcode: Display user plan status
     */
    public static function plan_status_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_upgrade' => 'yes',
            'compact' => 'no'
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '';
        }
        
        $user_id = get_current_user_id();
        $plan_details = self::get_user_plan_details($user_id);
        $has_plan = self::user_has_plan($user_id);
        
        ob_start();
        include MALISAFI_MLS_PATH . 'templates/plan-status.php';
        return ob_get_clean();
    }
    
    /**
     * Enqueue admin scripts
     */
    public static function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'malisafi-users') === false) {
            return;
        }
        
        wp_enqueue_script(
            'malisafi-plan-manager-admin',
            MALISAFI_MLS_URL . 'assets/js/plan-manager-admin.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('malisafi-plan-manager-admin', 'malisafiPlanManager', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('malisafi_admin_plan_nonce'),
            'i18n' => array(
                'confirm_assign' => __('Are you sure you want to assign this plan?', 'malisafi-mls'),
                'confirm_remove' => __('Are you sure you want to remove this plan?', 'malisafi-mls'),
                'success' => __('Operation completed successfully!', 'malisafi-mls'),
                'error' => __('An error occurred. Please try again.', 'malisafi-mls')
            )
        ));
    }
    
    /**
     * Enqueue frontend scripts
     */
    public static function enqueue_frontend_scripts() {
        if (!is_user_logged_in()) {
            return;
        }
        
        wp_enqueue_style(
            'malisafi-plan-status',
            MALISAFI_MLS_URL . 'assets/css/plan-status.css',
            array(),
            '1.0.0'
        );
        
        wp_enqueue_script(
            'malisafi-plan-status',
            MALISAFI_MLS_URL . 'assets/js/plan-status.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('malisafi-plan-status', 'malisafiPlanStatus', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'has_plan' => self::user_has_plan(get_current_user_id())
        ));
        
        // Enqueue banner notification for users without plans
        $user_id = get_current_user_id();
        $has_plan = self::user_has_plan($user_id);
        
        if (!$has_plan) {
            wp_enqueue_style(
                'malisafi-plan-banner',
                MALISAFI_MLS_URL . 'assets/css/plan-banner.css',
                array(),
                '1.0.0'
            );
            
            wp_enqueue_script(
                'malisafi-plan-banner',
                MALISAFI_MLS_URL . 'assets/js/plan-banner.js',
                array('jquery'),
                '1.0.0',
                true
            );
            
            $pricing_url = \MalisafiMLS\Page_Manager::get_page_url('pricing');
            if (!$pricing_url) {
                $pricing_url = home_url('/pricing/');
            }
            
            wp_localize_script('malisafi-plan-banner', 'malisafiPlanNotification', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'pricing_url' => $pricing_url,
                'checkPlan' => true,
                'i18n' => array(
                    'title' => __('Get Started with a Plan!', 'malisafi-mls'),
                    'message' => __('You don\'t have an active subscription. Choose a plan to unlock all features and start listing properties.', 'malisafi-mls'),
                    'button' => __('View Plans', 'malisafi-mls')
                )
            ));
        }
    }
}

// Initialize
Plan_Manager::init();
