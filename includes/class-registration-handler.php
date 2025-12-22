<?php
/**
 * User Registration Handler
 *
 * @package MalisafiMLS
 */

class Malisafi_Registration_Handler {
    
    /**
     * Initialize registration handler
     */
    public static function init() {
        // AJAX handlers
        add_action('wp_ajax_nopriv_malisafi_register_user', array(__CLASS__, 'handle_registration'));
        add_action('wp_ajax_nopriv_malisafi_check_email', array(__CLASS__, 'ajax_check_email'));
        add_action('wp_ajax_nopriv_malisafi_check_username', array(__CLASS__, 'ajax_check_username'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
    }
    
    /**
     * Enqueue registration form scripts and styles
     */
    public static function enqueue_scripts() {
        // Only load on registration page
        if (is_page() && (has_shortcode(get_post()->post_content, 'malisafi_registration') || 
                          has_shortcode(get_post()->post_content, 'malisafi_register'))) {
            
            wp_enqueue_style(
                'malisafi-registration-form',
                MALISAFI_MLS_URL . 'assets/css/registration-form.css',
                array(),
                MALISAFI_MLS_VERSION
            );
            
            wp_enqueue_script(
                'malisafi-registration-form',
                MALISAFI_MLS_URL . 'assets/js/registration-form.js',
                array('jquery'),
                MALISAFI_MLS_VERSION,
                true
            );
            
            wp_localize_script('malisafi-registration-form', 'malisafiRegistration', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('malisafi_registration'),
                'dashboardUrl' => home_url('/dashboard'),
            ));
        }
    }
    
    /**
     * Handle user registration via AJAX
     */
    public static function handle_registration() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'malisafi_registration')) {
            wp_send_json_error(array(
                'message' => __('Security check failed. Please refresh and try again.', 'malisafi-mls')
            ));
        }
        
        // Sanitize and validate input
        $account_type = sanitize_text_field($_POST['account_type'] ?? '');
        $user_role = sanitize_text_field($_POST['user_role'] ?? '');
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $username = sanitize_user($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Agent-specific fields
        $agency_name = sanitize_text_field($_POST['agency_name'] ?? '');
        $license_number = sanitize_text_field($_POST['license_number'] ?? '');
        $years_experience = sanitize_text_field($_POST['years_experience'] ?? '');
        $agent_county = sanitize_text_field($_POST['agent_county'] ?? '');
        $business_address = sanitize_text_field($_POST['business_address'] ?? '');
        $city = sanitize_text_field($_POST['city'] ?? '');
        $specializations = isset($_POST['specializations']) ? array_map('sanitize_text_field', $_POST['specializations']) : array();
        $agent_bio = sanitize_textarea_field($_POST['agent_bio'] ?? '');
        $national_id = sanitize_text_field($_POST['national_id'] ?? '');
        $website = esc_url_raw($_POST['website'] ?? '');
        $whatsapp = sanitize_text_field($_POST['whatsapp'] ?? '');
        
        // New agent fields
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
        
        // Validation
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
            $errors[] = __('Username already exists. Please choose another.', 'malisafi-mls');
        }
        
        if (email_exists($email)) {
            $errors[] = __('Email address is already registered.', 'malisafi-mls');
        }
        
        if (strlen($password) < 8) {
            $errors[] = __('Password must be at least 8 characters long.', 'malisafi-mls');
        }
        
        // Validate agent-specific fields
        if ($account_type === 'agent') {
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
            if (empty($specializations)) {
                $errors[] = __('At least one specialization is required for agents.', 'malisafi-mls');
            }
            if (empty($agent_bio) || strlen($agent_bio) < 100) {
                $errors[] = __('Professional bio is required and must be at least 100 characters.', 'malisafi-mls');
            }
            if (empty($national_id)) {
                $errors[] = __('National ID is required for verification.', 'malisafi-mls');
            }
        }
        
        // Validate role
        $valid_roles = array(
            'malisafi_client',
            'malisafi_agent_basic',
            'malisafi_owner',
            'malisafi_developer'
        );
        
        if (!in_array($user_role, $valid_roles)) {
            $errors[] = __('Invalid account type selected.', 'malisafi-mls');
        }
        
        if (!empty($errors)) {
            wp_send_json_error(array(
                'message' => implode('<br>', $errors)
            ));
        }
        
        // Create user
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_send_json_error(array(
                'message' => $user_id->get_error_message()
            ));
        }
        
        // Update user meta
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name,
            'role' => $user_role
        ));
        
        // Add phone number
        update_user_meta($user_id, 'phone', $phone);
        update_user_meta($user_id, 'account_type', $account_type);
        
        // Add agent-specific metadata
        if ($account_type === 'agent') {
            update_user_meta($user_id, 'agency_name', $agency_name);
            update_user_meta($user_id, 'license_number', $license_number);
            update_user_meta($user_id, 'years_experience', $years_experience);
            update_user_meta($user_id, 'agent_county', $agent_county);
            update_user_meta($user_id, 'business_address', $business_address);
            update_user_meta($user_id, 'city', $city);
            update_user_meta($user_id, 'specializations', $specializations);
            update_user_meta($user_id, 'agent_bio', $agent_bio);
            update_user_meta($user_id, 'national_id', $national_id);
            
            // Optional fields
            if (!empty($website)) {
                update_user_meta($user_id, 'website', $website);
            }
            if (!empty($whatsapp)) {
                update_user_meta($user_id, 'whatsapp', $whatsapp);
            }
            if (!empty($office_phone)) {
                update_user_meta($user_id, 'office_phone', $office_phone);
            }
            if (!empty($languages)) {
                update_user_meta($user_id, 'languages', $languages);
            }
            if (!empty($service_areas)) {
                update_user_meta($user_id, 'service_areas', $service_areas);
            }
            if (!empty($commission_rate)) {
                update_user_meta($user_id, 'commission_rate', $commission_rate);
            }
            
            // Social media
            if (!empty($facebook)) {
                update_user_meta($user_id, 'facebook', $facebook);
            }
            if (!empty($twitter)) {
                update_user_meta($user_id, 'twitter', $twitter);
            }
            if (!empty($linkedin)) {
                update_user_meta($user_id, 'linkedin', $linkedin);
            }
            if (!empty($instagram)) {
                update_user_meta($user_id, 'instagram', $instagram);
            }
            if (!empty($youtube)) {
                update_user_meta($user_id, 'youtube', $youtube);
            }
            
            // Agent approval status - pending by default
            update_user_meta($user_id, 'agent_status', 'pending');
            update_user_meta($user_id, 'agent_registered_date', current_time('mysql'));
            
            // Create agent post type entry
            $agent_post_id = wp_insert_post(array(
                'post_title' => $first_name . ' ' . $last_name,
                'post_type' => 'malisafi_agent',
                'post_status' => 'pending', // Requires admin approval
                'post_author' => $user_id,
                'meta_input' => array(
                    '_agent_user_id' => $user_id,
                    '_agent_email' => $email,
                    '_agent_phone' => $phone,
                    '_agent_mobile' => $phone,  // Same as phone for mobile
                    '_agent_agency_name' => $agency_name,
                    '_agent_license_number' => $license_number,
                    '_agent_experience_years' => $years_experience,
                    '_agent_county' => $agent_county,
                    '_agent_office_address' => $business_address,
                    '_agent_city' => $city,
                    '_agent_specializations' => implode(', ', $specializations),
                    '_agent_bio' => $agent_bio,
                    '_agent_national_id' => $national_id,
                    '_agent_website' => $website,
                    '_agent_whatsapp' => $whatsapp,
                    '_agent_languages' => $languages,
                    '_agent_service_areas' => $service_areas,
                    '_agent_commission_rate' => $commission_rate,
                    '_agent_facebook' => $facebook,
                    '_agent_twitter' => $twitter,
                    '_agent_linkedin' => $linkedin,
                    '_agent_instagram' => $instagram,
                    '_agent_youtube' => $youtube,
                    '_agent_rating' => 0,
                    '_agent_total_reviews' => 0,
                    '_agent_properties_count' => 0,
                    '_agent_status' => 'active',
                )
            ));
            
            if (!is_wp_error($agent_post_id)) {
                update_user_meta($user_id, 'agent_post_id', $agent_post_id);
            }
            
            // Notify admin about new agent registration
            self::notify_admin_new_agent($user_id, $email, $first_name . ' ' . $last_name);
        }
        
        // Log user registration
        do_action('malisafi_user_registered', $user_id, $user_role, $account_type);
        
        // Send welcome email
        self::send_welcome_email($user_id, $email, $first_name, $account_type);
        
        // Auto login
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        
        // Determine redirect URL based on account type
        $redirect_url = home_url('/dashboard');
        
        if ($account_type === 'agent' || $account_type === 'owner' || $account_type === 'developer') {
            $redirect_url = home_url('/agent-dashboard');
        }
        
        wp_send_json_success(array(
            'message' => __('Registration successful! Redirecting...', 'malisafi-mls'),
            'redirect' => $redirect_url,
            'user_id' => $user_id
        ));
    }
    
    /**
     * Notify admin about new agent registration
     */
    private static function notify_admin_new_agent($user_id, $email, $name) {
        $admin_email = get_option('admin_email');
        $subject = sprintf(__('[%s] New Agent Registration Pending Approval', 'malisafi-mls'), get_bloginfo('name'));
        
        $message = sprintf(
            __("A new agent has registered and is pending approval:\n\nName: %s\nEmail: %s\nUser ID: %d\n\nPlease review and approve/reject this agent:\n%s", 'malisafi-mls'),
            $name,
            $email,
            $user_id,
            admin_url('admin.php?page=malisafi-agent-management')
        );
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Check if email exists (AJAX)
     */
    public static function ajax_check_email() {
        check_ajax_referer('malisafi_registration', 'nonce');
        
        $email = sanitize_email($_POST['email'] ?? '');
        
        if (empty($email)) {
            wp_send_json_error(array('message' => __('Email is required.', 'malisafi-mls')));
        }
        
        $exists = email_exists($email);
        
        wp_send_json_success(array(
            'exists' => (bool) $exists
        ));
    }
    
    /**
     * Check if username exists (AJAX)
     */
    public static function ajax_check_username() {
        check_ajax_referer('malisafi_registration', 'nonce');
        
        $username = sanitize_user($_POST['username'] ?? '');
        
        if (empty($username)) {
            wp_send_json_error(array('message' => __('Username is required.', 'malisafi-mls')));
        }
        
        $exists = username_exists($username);
        
        wp_send_json_success(array(
            'exists' => (bool) $exists
        ));
    }
    
    /**
     * Send welcome email to new user
     */
    private static function send_welcome_email($user_id, $email, $first_name, $account_type) {
        $subject = sprintf(__('Welcome to %s!', 'malisafi-mls'), get_bloginfo('name'));
        
        $account_types = array(
            'client' => __('Client', 'malisafi-mls'),
            'agent' => __('Real Estate Agent', 'malisafi-mls'),
            'owner' => __('Property Owner', 'malisafi-mls'),
            'developer' => __('Developer', 'malisafi-mls')
        );
        
        $account_type_label = $account_types[$account_type] ?? __('User', 'malisafi-mls');
        
        $message = sprintf(
            __('Hello %s,', 'malisafi-mls') . "\n\n" .
            __('Welcome to %s! Your account has been successfully created.', 'malisafi-mls') . "\n\n" .
            __('Account Type: %s', 'malisafi-mls') . "\n" .
            __('Email: %s', 'malisafi-mls') . "\n\n" .
            __('You can now log in to your dashboard and start exploring:', 'malisafi-mls') . "\n" .
            '%s' . "\n\n" .
            __('If you have any questions, feel free to contact us.', 'malisafi-mls') . "\n\n" .
            __('Best regards,', 'malisafi-mls') . "\n" .
            __('The %s Team', 'malisafi-mls'),
            $first_name,
            get_bloginfo('name'),
            $account_type_label,
            $email,
            home_url('/dashboard'),
            get_bloginfo('name')
        );
        
        // Allow customization
        $message = apply_filters('malisafi_welcome_email_message', $message, $user_id, $account_type);
        
        wp_mail($email, $subject, $message);
    }
}

// Initialize
Malisafi_Registration_Handler::init();
