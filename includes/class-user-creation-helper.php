<?php
/**
 * User Creation Helper - Unified user creation logic
 *
 * @package MalisafiMLS
 */

namespace MalisafiMLS;

/**
 * User_Creation_Helper class
 * 
 * Centralizes all user creation logic for both admin and frontend
 * Ensures database consistency across all user-related tables
 */
class User_Creation_Helper {
    
    /**
     * Create a complete user with all necessary database entries
     * 
     * @param array $user_data Basic WordPress user data (username, email, password, first_name, last_name, role)
     * @param array $meta_data Additional user metadata (phone, account_type, agent fields, etc.)
     * @param bool  $auto_login Whether to auto-login the user after creation (frontend only)
     * 
     * @return int|WP_Error User ID on success, WP_Error on failure
     */
    public static function create_user($user_data, $meta_data = array(), $auto_login = false) {
        // Validate required fields
        if (empty($user_data['username']) || empty($user_data['email']) || empty($user_data['password'])) {
            return new \WP_Error('missing_fields', __('Username, email, and password are required.', 'malisafi-mls'));
        }
        
        // Check if username exists
        if (username_exists($user_data['username'])) {
            return new \WP_Error('username_exists', __('Username already exists.', 'malisafi-mls'));
        }
        
        // Check if email exists
        if (email_exists($user_data['email'])) {
            return new \WP_Error('email_exists', __('Email already exists.', 'malisafi-mls'));
        }
        
        // Create WordPress user
        $user_id = wp_create_user(
            $user_data['username'],
            $user_data['password'],
            $user_data['email']
        );
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
        // Update user core data
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $user_data['first_name'] ?? '',
            'last_name' => $user_data['last_name'] ?? '',
            'display_name' => trim(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? '')),
            'role' => $user_data['role'] ?? 'malisafi_client'
        ));
        
        // Save standard metadata
        if (!empty($meta_data['phone'])) {
            update_user_meta($user_id, 'phone', $meta_data['phone']);
        }
        
        if (!empty($meta_data['account_type'])) {
            update_user_meta($user_id, 'account_type', $meta_data['account_type']);
        }
        
        // Create subscription record for paid roles
        $paid_roles = array('malisafi_agent_basic', 'malisafi_agent_premium', 'malisafi_owner', 'malisafi_developer');
        if (in_array($user_data['role'], $paid_roles)) {
            self::create_subscription_record($user_id, $user_data['role']);
        }
        
        // Create user limits record
        self::create_user_limits_record($user_id, $user_data['role']);
        
        // Handle agent-specific creation
        if ($meta_data['account_type'] === 'agent' || strpos($user_data['role'], 'agent') !== false) {
            self::create_agent_profile($user_id, $user_data, $meta_data);
        }
        
        // Auto-login if requested (frontend only)
        if ($auto_login && !is_admin()) {
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
        }
        
        // Fire action hook for extensions
        do_action('malisafi_user_created', $user_id, $user_data['role'], $meta_data['account_type'] ?? '');
        
        return $user_id;
    }
    
    /**
     * Create subscription record in mf_subscriptions table
     * 
     * @param int    $user_id User ID
     * @param string $role    WordPress role
     */
    private static function create_subscription_record($user_id, $role) {
        global $wpdb;
        $table = $wpdb->prefix . 'mf_subscriptions';
        
        // Map role to plan_type
        $plan_type_map = array(
            'malisafi_agent_basic' => 'agent_basic',
            'malisafi_agent_premium' => 'agent_premium',
            'malisafi_owner' => 'owner_basic',
            'malisafi_developer' => 'developer'
        );
        
        $plan_type = $plan_type_map[$role] ?? 'agent_basic';
        
        $wpdb->insert(
            $table,
            array(
                'user_id' => $user_id,
                'plan_type' => $plan_type,
                'status' => 'active', // Active by default for admin-created users
                'current_period_start' => current_time('mysql'),
                'current_period_end' => date('Y-m-d H:i:s', strtotime('+1 year')), // 1 year from now
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Create user limits record in mf_user_limits table
     * 
     * @param int    $user_id User ID
     * @param string $role    WordPress role
     */
    private static function create_user_limits_record($user_id, $role) {
        global $wpdb;
        $table = $wpdb->prefix . 'mf_user_limits';
        
        // Define limits based on role
        $limits_map = array(
            'malisafi_client' => array(
                'max_listings' => 0,
                'featured_listings' => 0,
                'can_boost' => false,
                'analytics_access' => false
            ),
            'malisafi_agent_basic' => array(
                'max_listings' => 5,
                'featured_listings' => 1,
                'can_boost' => false,
                'analytics_access' => false
            ),
            'malisafi_agent_premium' => array(
                'max_listings' => -1, // Unlimited
                'featured_listings' => 5,
                'can_boost' => true,
                'analytics_access' => true
            ),
            'malisafi_owner' => array(
                'max_listings' => 3,
                'featured_listings' => 0,
                'can_boost' => false,
                'analytics_access' => false
            ),
            'malisafi_developer' => array(
                'max_listings' => -1, // Unlimited
                'featured_listings' => 10,
                'can_boost' => true,
                'analytics_access' => true
            ),
            'malisafi_moderator' => array(
                'max_listings' => 0,
                'featured_listings' => 0,
                'can_boost' => false,
                'analytics_access' => true
            )
        );
        
        $limits = $limits_map[$role] ?? $limits_map['malisafi_client'];
        
        $wpdb->insert(
            $table,
            array(
                'user_id' => $user_id,
                'max_listings' => $limits['max_listings'],
                'used_listings' => 0,
                'featured_listings' => $limits['featured_listings'],
                'can_boost' => $limits['can_boost'] ? 1 : 0,
                'analytics_access' => $limits['analytics_access'] ? 1 : 0,
                'updated_at' => current_time('mysql')
            ),
            array('%d', '%d', '%d', '%d', '%d', '%d', '%s')
        );
    }
    
    /**
     * Create agent profile (agent post type + metadata)
     * 
     * @param int   $user_id   User ID
     * @param array $user_data Basic user data
     * @param array $meta_data Agent-specific metadata
     */
    private static function create_agent_profile($user_id, $user_data, $meta_data) {
        // Save agent metadata to user meta
        $agent_fields = array(
            'agency_name', 'license_number', 'years_experience', 'agent_county',
            'business_address', 'city', 'specializations', 'agent_bio', 'national_id',
            'website', 'whatsapp', 'office_phone', 'languages', 'service_areas',
            'commission_rate', 'facebook', 'twitter', 'linkedin', 'instagram', 'youtube'
        );
        
        foreach ($agent_fields as $field) {
            if (!empty($meta_data[$field])) {
                update_user_meta($user_id, $field, $meta_data[$field]);
            }
        }
        
        // Agent approval status
        update_user_meta($user_id, 'agent_status', 'pending');
        update_user_meta($user_id, 'agent_registered_date', current_time('mysql'));
        
        // Create agent post type entry
        $agent_post_id = wp_insert_post(array(
            'post_title' => trim(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? '')),
            'post_type' => 'malisafi_agent',
            'post_status' => 'publish', // Changed from 'pending' to 'publish' so agent can access dashboard immediately
            'post_author' => $user_id,
            'meta_input' => array(
                '_agent_user_id' => $user_id,
                '_agent_email' => $user_data['email'],
                '_agent_phone' => $meta_data['phone'] ?? '',
                '_agent_mobile' => $meta_data['phone'] ?? '',
                '_agent_agency_name' => $meta_data['agency_name'] ?? '',
                '_agent_license_number' => $meta_data['license_number'] ?? '',
                '_agent_experience_years' => $meta_data['years_experience'] ?? '',
                '_agent_county' => $meta_data['agent_county'] ?? '',
                '_agent_office_address' => $meta_data['business_address'] ?? '',
                '_agent_city' => $meta_data['city'] ?? '',
                '_agent_specializations' => is_array($meta_data['specializations'] ?? null) 
                    ? implode(', ', $meta_data['specializations']) 
                    : ($meta_data['specializations'] ?? ''),
                '_agent_bio' => $meta_data['agent_bio'] ?? '',
                '_agent_national_id' => $meta_data['national_id'] ?? '',
                '_agent_website' => $meta_data['website'] ?? '',
                '_agent_whatsapp' => $meta_data['whatsapp'] ?? '',
                '_agent_languages' => $meta_data['languages'] ?? '',
                '_agent_service_areas' => $meta_data['service_areas'] ?? '',
                '_agent_commission_rate' => $meta_data['commission_rate'] ?? '',
                '_agent_facebook' => $meta_data['facebook'] ?? '',
                '_agent_twitter' => $meta_data['twitter'] ?? '',
                '_agent_linkedin' => $meta_data['linkedin'] ?? '',
                '_agent_instagram' => $meta_data['instagram'] ?? '',
                '_agent_youtube' => $meta_data['youtube'] ?? '',
                '_agent_rating' => 0,
                '_agent_total_reviews' => 0,
                '_agent_properties_count' => 0,
                '_agent_status' => 'active',
            )
        ), true); // Enable error return
        
        if (!is_wp_error($agent_post_id) && $agent_post_id > 0) {
            update_user_meta($user_id, 'agent_post_id', $agent_post_id);
            
            // Log success for debugging
            error_log(sprintf('[Malisafi] Agent profile created successfully. User ID: %d, Agent Post ID: %d', $user_id, $agent_post_id));
        } else {
            // Log error for debugging
            $error_message = is_wp_error($agent_post_id) ? $agent_post_id->get_error_message() : 'Unknown error creating agent post';
            error_log(sprintf('[Malisafi] Failed to create agent profile. User ID: %d, Error: %s', $user_id, $error_message));
        }
        
        // Notify admin about new agent registration
        self::notify_admin_new_agent($user_id, $user_data);
    }
    
    /**
     * Send notification to admin about new agent registration
     * 
     * @param int   $user_id   User ID
     * @param array $user_data User data
     */
    private static function notify_admin_new_agent($user_id, $user_data) {
        $admin_email = get_option('admin_email');
        $name = trim(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? ''));
        
        $subject = sprintf(
            __('[%s] New Agent Registration Pending Approval', 'malisafi-mls'),
            get_bloginfo('name')
        );
        
        $message = sprintf(
            __("A new agent has registered and is pending approval:\n\nName: %s\nEmail: %s\nUser ID: %d\n\nPlease review and approve/reject this agent:\n%s", 'malisafi-mls'),
            $name,
            $user_data['email'],
            $user_id,
            admin_url('admin.php?page=malisafi-agent-management')
        );
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Get user limits by user ID
     * 
     * @param int $user_id User ID
     * @return object|null User limits object or null
     */
    public static function get_user_limits($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'mf_user_limits';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d",
            $user_id
        ));
    }
    
    /**
     * Get user subscription by user ID
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
     * Update user limits
     * 
     * @param int   $user_id User ID
     * @param array $limits  Array of limits to update
     * @return bool Success or failure
     */
    public static function update_user_limits($user_id, $limits) {
        global $wpdb;
        $table = $wpdb->prefix . 'mf_user_limits';
        
        $update_data = array_intersect_key($limits, array_flip(array(
            'max_listings', 'used_listings', 'featured_listings', 'can_boost', 'analytics_access'
        )));
        
        if (empty($update_data)) {
            return false;
        }
        
        $update_data['updated_at'] = current_time('mysql');
        
        return $wpdb->update(
            $table,
            $update_data,
            array('user_id' => $user_id),
            array('%d', '%d', '%d', '%d', '%d', '%s'),
            array('%d')
        );
    }
    
    /**
     * Check if user has reached their listing limit
     * 
     * @param int $user_id User ID
     * @return bool True if limit reached, false otherwise
     */
    public static function has_reached_listing_limit($user_id) {
        $limits = self::get_user_limits($user_id);
        
        if (!$limits) {
            return true; // No limits record = no access
        }
        
        // -1 = unlimited
        if ($limits->max_listings == -1) {
            return false;
        }
        
        return $limits->used_listings >= $limits->max_listings;
    }
}
