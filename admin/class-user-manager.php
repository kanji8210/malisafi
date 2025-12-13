<?php
/**
 * User Management Class
 *
 * @package MalisafiMLS
 */

/**
 * Malisafi_User_Manager class
 */
class Malisafi_User_Manager {
    
    /**
     * Initialize user manager
     */
    public static function init() {
        add_action('admin_post_malisafi_add_user', array(__CLASS__, 'handle_add_user'));
        add_action('admin_post_malisafi_edit_user', array(__CLASS__, 'handle_edit_user'));
        add_action('admin_post_malisafi_delete_user', array(__CLASS__, 'handle_delete_user'));
        add_action('wp_ajax_malisafi_check_email', array(__CLASS__, 'ajax_check_email'));
    }
    
    /**
     * Get all Malisafi users with their subscription info
     */
    public static function get_malisafi_users($args = array()) {
        $defaults = array(
            'role__in' => array(
                'malisafi_client',
                'malisafi_agent_basic',
                'malisafi_agent_premium',
                'malisafi_owner',
                'malisafi_developer',
                'malisafi_moderator'
            ),
            'orderby' => 'registered',
            'order' => 'DESC',
            'number' => 20,
            'paged' => 1
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $user_query = new WP_User_Query($args);
        $users = $user_query->get_results();
        
        // Enrich with subscription data
        global $wpdb;
        $table_subscriptions = $wpdb->prefix . 'mf_subscriptions';
        
        foreach ($users as &$user) {
            $subscription = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table_subscriptions} WHERE user_id = %d ORDER BY created_at DESC LIMIT 1",
                $user->ID
            ));
            
            $user->subscription = $subscription;
        }
        
        return array(
            'users' => $users,
            'total' => $user_query->get_total(),
            'pages' => ceil($user_query->get_total() / $args['number'])
        );
    }
    
    /**
     * Handle add user form submission
     */
    public static function handle_add_user() {
        // Security check
        check_admin_referer('malisafi_add_user', 'malisafi_user_nonce');
        
        if (!current_user_can('manage_malisafi_settings')) {
            wp_die(__('You do not have permission to add users.', 'malisafi-mls'));
        }
        
        // Sanitize input
        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $role = sanitize_text_field($_POST['user_role']);
        $password = $_POST['password'];
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        
        // Validate
        $errors = array();
        
        if (empty($username) || empty($email) || empty($password)) {
            $errors[] = __('Username, email, and password are required.', 'malisafi-mls');
        }
        
        if (!is_email($email)) {
            $errors[] = __('Invalid email address.', 'malisafi-mls');
        }
        
        if (username_exists($username)) {
            $errors[] = __('Username already exists.', 'malisafi-mls');
        }
        
        if (email_exists($email)) {
            $errors[] = __('Email already exists.', 'malisafi-mls');
        }
        
        if (strlen($password) < 8) {
            $errors[] = __('Password must be at least 8 characters.', 'malisafi-mls');
        }
        
        if (!empty($errors)) {
            $error_message = implode('<br>', $errors);
            wp_redirect(add_query_arg(array(
                'page' => 'malisafi-users',
                'action' => 'add',
                'error' => urlencode($error_message)
            ), admin_url('admin.php')));
            exit;
        }
        
        // Create user
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_redirect(add_query_arg(array(
                'page' => 'malisafi-users',
                'action' => 'add',
                'error' => urlencode($user_id->get_error_message())
            ), admin_url('admin.php')));
            exit;
        }
        
        // Set user role
        $user = new WP_User($user_id);
        $user->set_role($role);
        
        // Update user meta
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name
        ));
        
        if (!empty($phone)) {
            update_user_meta($user_id, 'phone', $phone);
        }
        
        // Send notification email
        if (isset($_POST['send_notification']) && $_POST['send_notification'] == '1') {
            wp_new_user_notification($user_id, null, 'both');
        }
        
        // Create subscription record if applicable
        if (in_array($role, array('malisafi_agent_basic', 'malisafi_agent_premium', 'malisafi_developer'))) {
            self::create_subscription($user_id, $role);
        }
        
        // Redirect with success message
        wp_redirect(add_query_arg(array(
            'page' => 'malisafi-users',
            'message' => 'user_added',
            'user_id' => $user_id
        ), admin_url('admin.php')));
        exit;
    }
    
    /**
     * Handle edit user form submission
     */
    public static function handle_edit_user() {
        check_admin_referer('malisafi_edit_user', 'malisafi_user_nonce');
        
        if (!current_user_can('manage_malisafi_settings')) {
            wp_die(__('You do not have permission to edit users.', 'malisafi-mls'));
        }
        
        $user_id = intval($_POST['user_id']);
        $user = get_user_by('id', $user_id);
        
        if (!$user) {
            wp_die(__('User not found.', 'malisafi-mls'));
        }
        
        // Update user data
        $update_data = array(
            'ID' => $user_id,
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
        );
        
        // Update email if changed
        $new_email = sanitize_email($_POST['email']);
        if ($new_email !== $user->user_email) {
            if (email_exists($new_email)) {
                wp_redirect(add_query_arg(array(
                    'page' => 'malisafi-users',
                    'action' => 'edit',
                    'user_id' => $user_id,
                    'error' => urlencode(__('Email already exists.', 'malisafi-mls'))
                ), admin_url('admin.php')));
                exit;
            }
            $update_data['user_email'] = $new_email;
        }
        
        wp_update_user($update_data);
        
        // Update role if changed
        $new_role = sanitize_text_field($_POST['user_role']);
        if (!in_array($new_role, $user->roles)) {
            $user->set_role($new_role);
        }
        
        // Update phone
        if (isset($_POST['phone'])) {
            update_user_meta($user_id, 'phone', sanitize_text_field($_POST['phone']));
        }
        
        // Update password if provided
        if (!empty($_POST['password'])) {
            wp_set_password($_POST['password'], $user_id);
        }
        
        wp_redirect(add_query_arg(array(
            'page' => 'malisafi-users',
            'message' => 'user_updated',
            'user_id' => $user_id
        ), admin_url('admin.php')));
        exit;
    }
    
    /**
     * Handle delete user
     */
    public static function handle_delete_user() {
        check_admin_referer('malisafi_delete_user_' . $_GET['user_id']);
        
        if (!current_user_can('manage_malisafi_settings')) {
            wp_die(__('You do not have permission to delete users.', 'malisafi-mls'));
        }
        
        $user_id = intval($_GET['user_id']);
        
        // Don't allow deleting own account
        if ($user_id === get_current_user_id()) {
            wp_redirect(add_query_arg(array(
                'page' => 'malisafi-users',
                'error' => urlencode(__('You cannot delete your own account.', 'malisafi-mls'))
            ), admin_url('admin.php')));
            exit;
        }
        
        // Delete user
        require_once(ABSPATH . 'wp-admin/includes/user.php');
        $result = wp_delete_user($user_id);
        
        if ($result) {
            wp_redirect(add_query_arg(array(
                'page' => 'malisafi-users',
                'message' => 'user_deleted'
            ), admin_url('admin.php')));
        } else {
            wp_redirect(add_query_arg(array(
                'page' => 'malisafi-users',
                'error' => urlencode(__('Failed to delete user.', 'malisafi-mls'))
            ), admin_url('admin.php')));
        }
        exit;
    }
    
    /**
     * AJAX: Check if email exists
     */
    public static function ajax_check_email() {
        check_ajax_referer('malisafi_check_email', 'nonce');
        
        $email = sanitize_email($_POST['email']);
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        $exists = email_exists($email);
        
        // If editing, allow current user's email
        if ($exists && $exists != $user_id) {
            wp_send_json_error(array('message' => __('Email already exists.', 'malisafi-mls')));
        } else {
            wp_send_json_success();
        }
    }
    
    /**
     * Create subscription record for user
     */
    private static function create_subscription($user_id, $role) {
        global $wpdb;
        $table = $wpdb->prefix . 'mf_subscriptions';
        
        // Map role to subscription type
        $plan_type_map = array(
            'malisafi_agent_basic' => 'basic_agent',
            'malisafi_agent_premium' => 'premium_agent',
            'malisafi_developer' => 'developer'
        );
        
        $plan_type = $plan_type_map[$role] ?? 'basic_agent';
        
        $wpdb->insert(
            $table,
            array(
                'user_id' => $user_id,
                'plan_type' => $plan_type,
                'status' => 'active',
                'start_date' => current_time('mysql'),
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Get available roles for user creation
     */
    public static function get_available_roles() {
        return array(
            'malisafi_client' => __('Client', 'malisafi-mls'),
            'malisafi_agent_basic' => __('Agent Basic', 'malisafi-mls'),
            'malisafi_agent_premium' => __('Agent Premium', 'malisafi-mls'),
            'malisafi_owner' => __('Property Owner', 'malisafi-mls'),
            'malisafi_developer' => __('Developer', 'malisafi-mls'),
            'malisafi_moderator' => __('Moderator', 'malisafi-mls')
        );
    }
    
    /**
     * Get role badge HTML
     */
    public static function get_role_badge($role) {
        $badge_colors = array(
            'malisafi_client' => '#8c8f94',
            'malisafi_agent_basic' => '#2271b1',
            'malisafi_agent_premium' => '#d63638',
            'malisafi_owner' => '#00a32a',
            'malisafi_developer' => '#9b51e0',
            'malisafi_moderator' => '#dba617'
        );
        
        $color = $badge_colors[$role] ?? '#8c8f94';
        $role_names = self::get_available_roles();
        $role_name = $role_names[$role] ?? $role;
        
        return sprintf(
            '<span class="malisafi-role-badge" style="background-color: %s; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">%s</span>',
            esc_attr($color),
            esc_html($role_name)
        );
    }
}
