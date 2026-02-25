<?php
/**
 * User Management Class
 *
 * @package MalisafiMLS
 */

use MalisafiMLS\User_Creation_Helper;

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
        add_action('admin_post_malisafi_verify_email', array(__CLASS__, 'handle_verify_email'));
        add_action('admin_post_malisafi_send_password_reset', array(__CLASS__, 'handle_send_password_reset'));
        add_action('admin_post_malisafi_send_verification_email', array(__CLASS__, 'handle_send_verification_email'));
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
        
        if (!current_user_can('manage_malisafi_settings') && !current_user_can('manage_options')) {
            wp_die(__('You do not have permission to add users.', 'malisafi-mls'));
        }
        
        // Sanitize basic input
        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $role = sanitize_text_field($_POST['user_role']);
        $password = $_POST['password'];
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        
        // Agent-specific fields
        $agency_name = sanitize_text_field($_POST['agency_name'] ?? '');
        $license_number = sanitize_text_field($_POST['license_number'] ?? '');
        $years_experience = sanitize_text_field($_POST['years_experience'] ?? '');
        $agent_county = sanitize_text_field($_POST['agent_county'] ?? '');
        $business_address = sanitize_text_field($_POST['business_address'] ?? '');
        $city = sanitize_text_field($_POST['city'] ?? '');
        $national_id = sanitize_text_field($_POST['national_id'] ?? '');
        $specializations = isset($_POST['specializations']) ? array_map('sanitize_text_field', $_POST['specializations']) : array();
        $agent_bio = sanitize_textarea_field($_POST['agent_bio'] ?? '');
        $website = esc_url_raw($_POST['website'] ?? '');
        $whatsapp = sanitize_text_field($_POST['whatsapp'] ?? '');
        $office_phone = sanitize_text_field($_POST['office_phone'] ?? '');
        $languages = sanitize_text_field($_POST['languages'] ?? '');
        $service_areas = sanitize_textarea_field($_POST['service_areas'] ?? '');
        $commission_rate = floatval($_POST['commission_rate'] ?? 0);
        
        // Social media
        $facebook = esc_url_raw($_POST['facebook'] ?? '');
        $twitter = esc_url_raw($_POST['twitter'] ?? '');
        $linkedin = esc_url_raw($_POST['linkedin'] ?? '');
        $instagram = esc_url_raw($_POST['instagram'] ?? '');
        $youtube = esc_url_raw($_POST['youtube'] ?? '');
        
        // Validate basic fields
        $errors = array();
        
        if (empty($username) || empty($email) || empty($password)) {
            $errors[] = __('Username, email, and password are required.', 'malisafi-mls');
        }
        
        if (empty($first_name) || empty($last_name)) {
            $errors[] = __('First name and last name are required.', 'malisafi-mls');
        }
        
        if (empty($phone)) {
            $errors[] = __('Phone number is required.', 'malisafi-mls');
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
        
        // Validate agent-specific fields
        $isAgent = strpos($role, 'agent') !== false;
        if ($isAgent) {
            if (empty($agency_name)) {
                $errors[] = __('Agency name is required for agents.', 'malisafi-mls');
            }
            if (empty($license_number)) {
                $errors[] = __('License number is required for agents.', 'malisafi-mls');
            }
            if (empty($years_experience)) {
                $errors[] = __('Years of experience is required for agents.', 'malisafi-mls');
            }
            if (empty($agent_county)) {
                $errors[] = __('Operating county is required for agents.', 'malisafi-mls');
            }
            if (empty($business_address)) {
                $errors[] = __('Business address is required for agents.', 'malisafi-mls');
            }
            if (empty($city)) {
                $errors[] = __('City is required for agents.', 'malisafi-mls');
            }
            if (empty($national_id)) {
                $errors[] = __('National ID is required for agents.', 'malisafi-mls');
            }
            if (empty($specializations)) {
                $errors[] = __('At least one specialization is required for agents.', 'malisafi-mls');
            }
            if (empty($agent_bio) || strlen($agent_bio) < 100) {
                $errors[] = __('Professional bio is required and must be at least 100 characters for agents.', 'malisafi-mls');
            }
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
        
        // Prepare user data for helper
        $user_data = array(
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'role' => $role
        );
        
        // Prepare metadata
        $meta_data = array(
            'phone' => $phone
        );
        
        // Determine account type from role
        if (strpos($role, 'agent') !== false) {
            $meta_data['account_type'] = 'agent';
            // Add all agent fields
            $meta_data['agency_name'] = $agency_name;
            $meta_data['license_number'] = $license_number;
            $meta_data['years_experience'] = $years_experience;
            $meta_data['agent_county'] = $agent_county;
            $meta_data['business_address'] = $business_address;
            $meta_data['city'] = $city;
            $meta_data['national_id'] = $national_id;
            $meta_data['specializations'] = $specializations;
            $meta_data['agent_bio'] = $agent_bio;
            $meta_data['website'] = $website;
            $meta_data['whatsapp'] = $whatsapp;
            $meta_data['office_phone'] = $office_phone;
            $meta_data['languages'] = $languages;
            $meta_data['service_areas'] = $service_areas;
            $meta_data['commission_rate'] = $commission_rate;
            $meta_data['facebook'] = $facebook;
            $meta_data['twitter'] = $twitter;
            $meta_data['linkedin'] = $linkedin;
            $meta_data['instagram'] = $instagram;
            $meta_data['youtube'] = $youtube;
        } elseif (strpos($role, 'owner') !== false) {
            $meta_data['account_type'] = 'owner';
        } elseif (strpos($role, 'developer') !== false) {
            $meta_data['account_type'] = 'developer';
        } else {
            $meta_data['account_type'] = 'client';
        }
        
        // Create user using helper (no auto-login for admin)
        $user_id = User_Creation_Helper::create_user($user_data, $meta_data, false);
        
        if (is_wp_error($user_id)) {
            wp_redirect(add_query_arg(array(
                'page' => 'malisafi-users',
                'action' => 'add',
                'error' => urlencode($user_id->get_error_message())
            ), admin_url('admin.php')));
            exit;
        }
        
        // Send notification email
        if (isset($_POST['send_notification']) && $_POST['send_notification'] == '1') {
            wp_new_user_notification($user_id, null, 'both');
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
        
        if (!current_user_can('manage_malisafi_settings') && !current_user_can('manage_options')) {
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
        
        // Update email verification status
        if (get_option('malisafi_email_verification_enabled')) {
            if (isset($_POST['email_verified']) && $_POST['email_verified'] === '1') {
                update_user_meta($user_id, '_malisafi_email_verified', '1');
                delete_user_meta($user_id, '_malisafi_email_verification_token');
            } else {
                update_user_meta($user_id, '_malisafi_email_verified', '0');
            }
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
        
        if (!current_user_can('manage_malisafi_settings') && !current_user_can('manage_options')) {
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
    
    /**
     * Handle manual email verification
     */
    public static function handle_verify_email() {
        // Security check
        check_admin_referer('malisafi_verify_email_' . $_GET['user_id']);
        
        if (!current_user_can('manage_malisafi_settings') && !current_user_can('manage_options')) {
            wp_die(__('You do not have permission to verify emails.', 'malisafi-mls'));
        }
        
        $user_id = intval($_GET['user_id']);
        $user = get_user_by('id', $user_id);
        
        if (!$user) {
            wp_die(__('User not found.', 'malisafi-mls'));
        }
        
        // Mark email as verified
        update_user_meta($user_id, '_malisafi_email_verified', '1');
        delete_user_meta($user_id, '_malisafi_email_verification_token');
        
        // Log the action
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Malisafi: Email manually verified for user ' . $user->user_login . ' by admin ' . wp_get_current_user()->user_login);
        }
        
        // Redirect back with success message
        wp_redirect(add_query_arg('message', 'email_verified', admin_url('admin.php?page=malisafi-users')));
        exit;
    }
    
    /**
     * Handle sending password reset email
     */
    public static function handle_send_password_reset() {
        // Security check
        check_admin_referer('malisafi_send_password_reset_' . $_GET['user_id']);
        
        if (!current_user_can('manage_malisafi_settings') && !current_user_can('manage_options')) {
            wp_die(__('You do not have permission to send password resets.', 'malisafi-mls'));
        }
        
        $user_id = intval($_GET['user_id']);
        $user = get_user_by('id', $user_id);
        
        if (!$user) {
            wp_die(__('User not found.', 'malisafi-mls'));
        }
        
        // Send password reset email
        $reset_key = get_password_reset_key($user);
        if (!is_wp_error($reset_key)) {
            $reset_url = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user->user_login), 'login');
            
            $subject = __('Password Reset Request', 'malisafi-mls');
            $message = sprintf(
                __('Hello %s,

Someone requested a password reset for your account. If this was you, click the link below to reset your password:

%s

If you did not request a password reset, please ignore this email.

Best regards,
The %s Team',
                'malisafi-mls'),
                $user->display_name,
                $reset_url,
                get_bloginfo('name')
            );
            
            wp_mail($user->user_email, $subject, $message);
        }
        
        // Redirect back with success message
        wp_redirect(add_query_arg('message', 'password_reset_sent', admin_url('admin.php?page=malisafi-users')));
        exit;
    }
    
    /**
     * Handle resending verification email
     */
    public static function handle_send_verification_email() {
        // Security check
        check_admin_referer('malisafi_send_verification_email_' . $_GET['user_id']);
        
        if (!current_user_can('manage_malisafi_settings') && !current_user_can('manage_options')) {
            wp_die(__('You do not have permission to send verification emails.', 'malisafi-mls'));
        }
        
        $user_id = intval($_GET['user_id']);
        
        // Use the existing email settings class to send verification email
        if (class_exists('MalisafiMLS\Email_Settings')) {
            // Get user data for the email
            $user = get_user_by('id', $user_id);
            if ($user) {
                $user_data = array(
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name
                );
                
                // Trigger the verification email
                do_action('malisafi_user_registered', $user_id, $user->roles[0] ?? 'client', 'client', $user_data);
            }
        }
        
        // Redirect back with success message
        wp_redirect(add_query_arg('message', 'verification_email_sent', admin_url('admin.php?page=malisafi-users')));
        exit;
    }
}
