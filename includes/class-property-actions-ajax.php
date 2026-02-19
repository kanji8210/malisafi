<?php
/**
 * Property Actions AJAX Handler
 * Handles favorites, reports, and contact requests
 */

namespace MalisafiMLS;

if (!defined('ABSPATH')) {
    exit;
}

class Property_Actions_Ajax {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // AJAX Actions
        add_action('wp_ajax_malisafi_toggle_favorite', array($this, 'toggle_favorite'));
        add_action('wp_ajax_nopriv_malisafi_toggle_favorite', array($this, 'toggle_favorite_guest'));
        
        add_action('wp_ajax_malisafi_send_inquiry', array($this, 'send_inquiry'));
        add_action('wp_ajax_nopriv_malisafi_send_inquiry', array($this, 'send_inquiry'));
        
        add_action('wp_ajax_malisafi_contact_agent', array($this, 'contact_agent'));
        add_action('wp_ajax_nopriv_malisafi_contact_agent', array($this, 'contact_agent'));
        // Admin-only DB repair endpoint (AJAX)
        add_action('wp_ajax_malisafi_db_repair', array($this, 'db_repair'));
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Enqueue scripts and styles for single property page
     */
    public function enqueue_scripts() {
        if (is_singular('malisafi_property')) {
            wp_enqueue_style(
                'malisafi-single-property',
                MALISAFI_MLS_URL . 'assets/css/single-property.css',
                array(),
                '1.0.0'
            );
            
            wp_enqueue_script(
                'malisafi-single-property',
                MALISAFI_MLS_URL . 'assets/js/malisafi-single-property.js',
                array('jquery'),
                '1.0.0',
                true
            );

            $login_url = Page_Manager::get_page_url('login');
            if (!$login_url) {
                $login_url = wp_login_url(get_permalink());
            } else {
                $login_url = add_query_arg('redirect_to', rawurlencode(get_permalink()), $login_url);
            }

            $register_client_url = Page_Manager::get_page_url('register_client');
            if (!$register_client_url) {
                $register_client_url = home_url('/register-client/');
            }
            
            wp_localize_script('malisafi-single-property', 'malisafi_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('malisafi_ajax_nonce'),
                'report_nonce' => wp_create_nonce('malisafi_report_property'),
                'user_logged_in' => is_user_logged_in(),
                'login_url' => $login_url,
                'register_client_url' => $register_client_url
            ));
        }
    }
    
    /**
     * Toggle favorite for logged in users
     */
    public function toggle_favorite() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'malisafi_property_nonce')) {
            wp_send_json_error(array('message' => 'Invalid security token.'));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'You must be logged in to add favorites.'));
        }
        
        $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
        $user_id = get_current_user_id();
        
        if (!$property_id || get_post_type($property_id) !== 'malisafi_property') {
            wp_send_json_error(array('message' => 'Invalid property.'));
        }
        
        // Get current favorites
        $favorites = get_user_meta($user_id, '_malisafi_favorites', true);
        $favorites = $favorites ? explode(',', $favorites) : array();
        
        // Toggle favorite
        $key = array_search($property_id, $favorites);
        if ($key !== false) {
            // Remove from favorites
            unset($favorites[$key]);
            $favorited = false;
        } else {
            // Add to favorites
            $favorites[] = $property_id;
            $favorited = true;
        }
        
        // Save favorites
        $favorites = array_filter($favorites);
        update_user_meta($user_id, '_malisafi_favorites', implode(',', $favorites));
        
        wp_send_json_success(array(
            'favorited' => $favorited,
            'count' => count($favorites)
        ));
    }
    
    /**
     * Handle favorite for non-logged in users
     */
    public function toggle_favorite_guest() {
        $login_url = Page_Manager::get_page_url('login');
        if (!$login_url) {
            $login_url = wp_login_url();
        }

        $register_client_url = Page_Manager::get_page_url('register_client');
        if (!$register_client_url) {
            $register_client_url = home_url('/register-client/');
        }

        wp_send_json_error(array(
            'message' => 'Please log in as a client to add properties to your favorites.',
            'login_url' => $login_url,
            'register_client_url' => $register_client_url,
            'login_required' => true
        ));
    }
    
    /**
     * Handle property inquiry
     */
    public function send_inquiry() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'malisafi_ajax_nonce')) {
            error_log('Malisafi: Invalid nonce in send_inquiry');
            wp_send_json_error(array('message' => 'Invalid security token.'));
        }

        $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
        $name = isset($_POST['inquiry_name']) ? sanitize_text_field($_POST['inquiry_name']) : '';
        $email = isset($_POST['inquiry_email']) ? sanitize_email($_POST['inquiry_email']) : '';
        $phone = isset($_POST['inquiry_phone']) ? sanitize_text_field($_POST['inquiry_phone']) : '';
        $message = isset($_POST['inquiry_message']) ? sanitize_textarea_field($_POST['inquiry_message']) : '';

        // Honeypot field (should be empty). Also check simple time-based trap.
        $honeypot = isset($_POST['hp_name']) ? sanitize_text_field($_POST['hp_name']) : '';
        $form_ts = isset($_POST['form_ts']) ? intval($_POST['form_ts']) : 0;
        if (!empty($honeypot)) {
            error_log('Malisafi: Honeypot triggered in send_inquiry');
            wp_send_json_error(array('message' => 'Failed spam check.'));
        }
        $now = time();
        if ($form_ts > 0 && ($now - $form_ts) < 3) {
            error_log('Malisafi: Form submitted too quickly - possible bot.');
            wp_send_json_error(array('message' => 'Failed spam check.'));
        }

        // Optional server-side reCAPTCHA v2/v3 verification
        if (get_option('malisafi_inquiry_recaptcha_enabled')) {
            $recaptcha_response = isset($_POST['g-recaptcha-response']) ? sanitize_text_field($_POST['g-recaptcha-response']) : '';
            $recaptcha_secret = get_option('malisafi_inquiry_recaptcha_secret');
            if (empty($recaptcha_response) || empty($recaptcha_secret)) {
                error_log('Malisafi: reCAPTCHA required but missing response/secret');
                wp_send_json_error(array('message' => 'Spam protection check failed.'));
            }

            $verify = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array(
                'body' => array(
                    'secret' => $recaptcha_secret,
                    'response' => $recaptcha_response,
                    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
                ),
                'timeout' => 10
            ));

            if (is_wp_error($verify)) {
                error_log('Malisafi: reCAPTCHA verification error: ' . $verify->get_error_message());
                wp_send_json_error(array('message' => 'Spam protection check failed.'));
            }

            $body = wp_remote_retrieve_body($verify);
            $data = json_decode($body, true);
            if (empty($data) || empty($data['success'])) {
                error_log('Malisafi: reCAPTCHA failed: ' . $body);
                wp_send_json_error(array('message' => 'Spam protection check failed.'));
            }
        }

        // Validation
        if (!$property_id || get_post_type($property_id) !== 'malisafi_property') {
            wp_send_json_error(array('message' => 'Invalid property.'));
        }

        if (empty($name)) {
            wp_send_json_error(array('message' => 'Please provide your name.'));
        }

        if (empty($email) || !is_email($email)) {
            wp_send_json_error(array('message' => 'Please provide a valid email address.'));
        }

        if (empty($message)) {
            wp_send_json_error(array('message' => 'Please provide a message.'));
        }

        // Rate-limit: max 5 inquiries per 5 minutes per IP+property+email
        $ip = $this->get_client_ip();
        $rate_key = 'malisafi_inquiry_rate_' . md5($ip . '|' . $property_id . '|' . $email);
        $rate_count = intval(get_transient($rate_key));
        if ($rate_count >= 5) {
            wp_send_json_error(array('message' => 'Too many inquiries. Please try again later.'));
        }

        // Get property and agent info
        $property = get_post($property_id);
        $property_title = $property->post_title;
        $property_url = get_permalink($property_id);
        $agent_id = $property->post_author;
        $agent_email = get_the_author_meta('user_email', $agent_id);
        $agent_name = get_the_author_meta('display_name', $agent_id);

        // Check agent's agency for fallback
        $agency = Agency_Manager::get_agent_agency($agent_id);
        $agency_id = null;
        $agency_email = null;
        if ($agency) {
            $agency_id = $agency->id;
            $agency_email = $agency->agency_email ?: $agency->owner_email;
        }

        // Determine recipient: prefer agent, then agency
        $recipient_email = '';
        if (!empty($agent_email) && is_email($agent_email)) {
            $recipient_email = $agent_email;
        } elseif (!empty($agency_email) && is_email($agency_email)) {
            $recipient_email = $agency_email;
        } else {
            error_log('Malisafi: No recipient email found for inquiry on property ' . $property_id);
            wp_send_json_error(array('message' => 'Unable to deliver inquiry: recipient not found.'));
        }

        // Prepare email
        $subject = 'New Property Inquiry: ' . $property_title;
        $email_message = "Hello {$agent_name},\n\n";
        $email_message .= "You have received a new inquiry about your property.\n\n";
        $email_message .= "Property: {$property_title}\n";
        $email_message .= "URL: {$property_url}\n\n";
        $email_message .= "Inquirer Details:\n";
        $email_message .= "Name: {$name}\n";
        $email_message .= "Email: {$email}\n";
        if (!empty($phone)) {
            $email_message .= "Phone: {$phone}\n";
        }
        $email_message .= "\nMessage:\n{$message}\n\n";
        $email_message .= "Please respond to this inquiry as soon as possible.\n\n";
        $email_message .= "Best regards,\n";
        $email_message .= get_bloginfo('name') . " Team";

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
            'Reply-To: ' . $email
        );

        // Attempt to send email and record result
        $sent = wp_mail($recipient_email, $subject, $email_message, $headers);
        
        // Log the email attempt
        self::log_email_attempt(array(
            'to' => $recipient_email,
            'subject' => $subject,
            'status' => $sent ? 'sent' : 'failed',
            'property_id' => $property_id,
            'client_email' => $email,
            'timestamp' => current_time('mysql'),
            'type' => 'inquiry_notification'
        ));

        // Insert into DB (always record inquiry even if email fails)
        global $wpdb;
        $table_name = $wpdb->prefix . 'mf_inquiries';

        $inquiry_db = array(
            'property_id' => $property_id,
            'client_id' => get_current_user_id() ?: 0,
            'agent_id' => $agent_id,
            'agency_id' => $agency_id,
            'inquiry_type' => 'general',
            'message' => $message,
            'status' => $sent ? 'new' : 'email_failed',
            'email_sent' => $sent ? 1 : 0,
            'email_recipient' => $recipient_email,
            'client_name' => $name,
            'client_phone' => $phone,
            'client_email' => $email,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'client_ip' => $ip
        );

        $inserted = $wpdb->insert($table_name, $inquiry_db);
        if (!$inserted) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Malisafi: Failed to insert inquiry DB record.');
                error_log('Malisafi Debug: table=' . $table_name);
                error_log('Malisafi Debug: last_error=' . $wpdb->last_error);
                error_log('Malisafi Debug: last_query=' . $wpdb->last_query);
                error_log('Malisafi Debug: insert_payload=' . wp_json_encode($inquiry_db));
                error_log('Malisafi Debug: DB error trace: ' . print_r($wpdb, true));
            }
            wp_send_json_error(array('message' => 'Failed to save inquiry. Please try again later.'));
        }

        $inquiry_id = $wpdb->insert_id;

        // Trigger agency notification hook for other listeners
        do_action('malisafi_inquiry_created', $inquiry_id, array(
            'property_id' => $property_id,
            'agent_id' => $agent_id,
            'agency_id' => $agency_id,
            'client_name' => $name,
            'client_email' => $email,
            'client_phone' => $phone,
            'message' => $message,
            'property_title' => $property_title,
            'property_url' => $property_url
        ));

        // Record analytics interaction: inquiry
        try {
            $interaction_data = json_encode([
                'client_name' => $name,
                'client_email' => $email,
                'client_phone' => $phone
            ]);

            $session_id = session_id() ?: (isset($_COOKIE['malisafi_session']) ? sanitize_text_field($_COOKIE['malisafi_session']) : '');

            $wpdb->insert(
                $wpdb->prefix . 'mf_property_interactions',
                [
                    'property_id' => $property_id,
                    'user_id' => get_current_user_id() ?: null,
                    'interaction_type' => 'inquiry',
                    'interaction_data' => $interaction_data,
                    'session_id' => $session_id
                ],
                ['%d', '%d', '%s', '%s', '%s']
            );
        } catch (\Throwable $e) {
            error_log('Malisafi: Failed to record inquiry interaction: ' . $e->getMessage());
        }

        // Store in agent's meta for backward compatibility and agent dashboard
        $meta_data = array(
            'property_id' => $property_id,
            'agent_id' => $agent_id,
            'agency_id' => $agency_id,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'message' => $message,
            'date' => current_time('mysql'),
            'ip' => $ip,
            'inquiry_id' => $inquiry_id,
            'is_guest' => !get_current_user_id()
        );

        $agent_inquiries = get_user_meta($agent_id, '_malisafi_inquiries', true);
        $agent_inquiries = $agent_inquiries ? maybe_unserialize($agent_inquiries) : array();
        $agent_inquiries[] = $meta_data;
        update_user_meta($agent_id, '_malisafi_inquiries', $agent_inquiries);

        // Store in agency's meta if agent belongs to an agency
        if ($agency && isset($agency->user_id)) {
            $agency_meta_data = $meta_data;
            $agency_meta_data['agent_name'] = $agent_name;
            $agency_meta_data['agent_email'] = $agent_email;

            $agency_inquiries = get_user_meta($agency->user_id, '_malisafi_agency_inquiries', true);
            $agency_inquiries = $agency_inquiries ? maybe_unserialize($agency_inquiries) : array();
            $agency_inquiries[] = $agency_meta_data;
            update_user_meta($agency->user_id, '_malisafi_agency_inquiries', $agency_inquiries);
        }

        // Increment rate count (expire after 5 minutes)
        set_transient($rate_key, $rate_count + 1, 5 * MINUTE_IN_SECONDS);

        if ($sent) {
            wp_send_json_success(array('message' => 'Your message has been sent successfully.'));
        }

        // Mail failed but stored
        wp_send_json_error(array('message' => 'Message saved but failed to send email. The agent will be notified via the dashboard.'));
    }

    /**
     * Get client IP address (respecting basic proxy headers)
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                return trim($ips[0]);
            }
        }
        return '0.0.0.0';
    }
    
    /**
     * Handle contact agent request
     */
    public function contact_agent() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'malisafi_property_nonce')) {
            wp_send_json_error(array('message' => 'Invalid security token.'));
        }
        
        $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
        $agent_id = isset($_POST['agent_id']) ? intval($_POST['agent_id']) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        
        // Validation
        if (!$property_id || get_post_type($property_id) !== 'malisafi_property') {
            wp_send_json_error(array('message' => 'Invalid property.'));
        }
        
        if (empty($name) || empty($email) || empty($message)) {
            wp_send_json_error(array('message' => 'Please fill in all required fields.'));
        }
        
        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Please provide a valid email address.'));
        }
        
        // Get property and agent info
        $property = get_post($property_id);
        $property_title = $property->post_title;
        $property_url = get_permalink($property_id);
        $agent_email = get_the_author_meta('user_email', $agent_id);
        $agent_name = get_the_author_meta('display_name', $agent_id);
        
        // Check if agent belongs to an agency
        $agency = \MalisafiMLS\Agency_Manager::get_agent_agency($agent_id);
        $agency_id = null;
        $agency_email = null;
        $agency_name = null;
        
        if ($agency) {
            $agency_id = $agency->id;
            $agency_email = $agency->agency_email ?: $agency->owner_email;
            $agency_name = $agency->agency_name;
        }
        
        // Send email to agent
        $subject = 'New Property Inquiry: ' . $property_title;
        $email_message = "Hello {$agent_name},\n\n";
        $email_message .= "You have received a new inquiry about your property: {$property_title}\n\n";
        $email_message .= "From: {$name}\n";
        $email_message .= "Email: {$email}\n";
        if ($phone) {
            $email_message .= "Phone: {$phone}\n";
        }
        $email_message .= "\nMessage:\n{$message}\n\n";
        $email_message .= "Property URL: {$property_url}\n";
        
        $headers = array(
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
            'Reply-To: ' . $name . ' <' . $email . '>'
        );
        
        $sent_to_agent = wp_mail($agent_email, $subject, $email_message, $headers);
        
        // Send email to agency if agent belongs to one
        $sent_to_agency = false;
        if ($agency && $agency_email) {
            $agency_subject = 'New Property Inquiry for Your Agent: ' . $property_title;
            $agency_message = "Hello {$agency_name} Team,\n\n";
            $agency_message .= "Your agent {$agent_name} has received a new inquiry about their property: {$property_title}\n\n";
            $agency_message .= "From: {$name}\n";
            $agency_message .= "Email: {$email}\n";
            if ($phone) {
                $agency_message .= "Phone: {$phone}\n";
            }
            $agency_message .= "\nMessage:\n{$message}\n\n";
            $agency_message .= "Property URL: {$property_url}\n";
            $agency_message .= "Agent: {$agent_name} ({$agent_email})\n";
            
            $sent_to_agency = wp_mail($agency_email, $agency_subject, $agency_message, $headers);
        }
        
        $email_sent = $sent_to_agent || $sent_to_agency;
        
        $email_sent = $sent_to_agent || $sent_to_agency;
        
        if ($email_sent) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'mf_inquiries';

            // Store inquiry in database table
            $inquiry_data = array(
                'property_id' => $property_id,
                    'client_id' => get_current_user_id() ?: 0, // 0 for guest users to match DB NOT NULL constraint
                'agent_id' => $agent_id,
                'agency_id' => $agency_id, // Store agency ID if agent belongs to one
                'inquiry_type' => 'general',
                'message' => $message,
                'status' => 'new',
                'client_phone' => $phone,
                'client_email' => $email,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            );

            $inserted = $wpdb->insert($table_name, $inquiry_data);

            if ($inserted) {
                $inquiry_id = $wpdb->insert_id;
                
                // Trigger agency notification hook
                do_action('malisafi_inquiry_created', $inquiry_id, array(
                    'property_id' => $property_id,
                    'agent_id' => $agent_id,
                    'agency_id' => $agency_id,
                    'client_name' => $name,
                    'client_email' => $email,
                    'client_phone' => $phone,
                    'message' => $message,
                    'property_title' => $property_title,
                    'property_url' => $property_url
                ));
                
                // Also save as user meta for backward compatibility and agent dashboard
                $meta_data = array(
                    'property_id' => $property_id,
                    'agent_id' => $agent_id,
                    'agency_id' => $agency_id,
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'message' => $message,
                    'date' => current_time('mysql'),
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'inquiry_id' => $inquiry_id,
                    'is_guest' => !get_current_user_id()
                );

                // Store in agent's meta
                $agent_inquiries = get_user_meta($agent_id, '_malisafi_inquiries', true);
                $agent_inquiries = $agent_inquiries ? maybe_unserialize($agent_inquiries) : array();
                $agent_inquiries[] = $meta_data;
                update_user_meta($agent_id, '_malisafi_inquiries', $agent_inquiries);
                // Store in agency's meta if agent belongs to an agency
                if ($agency && isset($agency->user_id)) {
                    $agency_meta_data = $meta_data;
                    $agency_meta_data['agent_name'] = $agent_name;
                    $agency_meta_data['agent_email'] = $agent_email;
                    
                    $agency_inquiries = get_user_meta($agency->user_id, '_malisafi_agency_inquiries', true);
                    $agency_inquiries = $agency_inquiries ? maybe_unserialize($agency_inquiries) : array();
                    $agency_inquiries[] = $agency_meta_data;
                    update_user_meta($agency->user_id, '_malisafi_agency_inquiries', $agency_inquiries);
                }

                wp_send_json_success(array('message' => 'Your message has been sent successfully.'));
            } else {
                error_log('Malisafi: Failed to insert inquiry DB record (secondary path).');
                error_log('Malisafi Debug: table=' . $table_name);
                error_log('Malisafi Debug: last_error=' . $wpdb->last_error);
                error_log('Malisafi Debug: last_query=' . $wpdb->last_query);
                error_log('Malisafi Debug: insert_payload=' . wp_json_encode($inquiry_data));
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('Malisafi Debug: DB error trace: ' . print_r($wpdb, true));
                }
                wp_send_json_error(array('message' => 'Message sent but failed to save inquiry record.'));
            }
        } else {
            wp_send_json_error(array('message' => 'Failed to send message. Please try again.'));
        }
    }

    /**
     * Admin AJAX: Repair / alter core plugin tables safely.
     * - Adds `client_ip` to mf_inquiries if missing
     * - If table is missing or other errors occur, renames table to backup and recreates via Database::create_tables()
     */
    public function db_repair() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions.'));
        }

        // optional nonce check
        if (!empty($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'malisafi_admin_db_repair')) {
            wp_send_json_error(array('message' => 'Invalid nonce.'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'mf_inquiries';

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '" . $wpdb->esc_like( $table_name ) . "'");
        if (!$table_exists) {
            error_log('Malisafi DB Repair: table not found: ' . $table_name);
            // Attempt to recreate all tables
            try {
                if (class_exists('\MalisafiMLS\Database')) {
                    \MalisafiMLS\Database::create_tables();
                    wp_send_json_success(array('message' => 'Tables recreated (table was missing).'));
                } else {
                    wp_send_json_error(array('message' => 'Database class not available.'));
                }
            } catch (\Throwable $e) {
                wp_send_json_error(array('message' => 'Failed to recreate tables: ' . $e->getMessage()));
            }
        }

        // Check for client_ip column
        $col = $wpdb->get_row("SHOW COLUMNS FROM {$table_name} LIKE 'client_ip'");
        if ($col) {
            wp_send_json_success(array('message' => 'No action required: `client_ip` column already exists.'));
        }

        // Try to add the column
        $alter_sql = "ALTER TABLE {$table_name} ADD COLUMN `client_ip` VARCHAR(45) NOT NULL DEFAULT '' AFTER `updated_at`";

        $res = $wpdb->query($alter_sql);
        if ($res === false) {
            // If ALTER failed due to severe schema issues, fallback to backup+recreate
            $last_error = $wpdb->last_error;
            error_log('Malisafi DB Repair: ALTER failed: ' . $last_error);
            // Rename current table to a timestamped backup
            $backup_name = $table_name . '_backup_' . date('Ymd_His');
            $rename_sql = "RENAME TABLE {$table_name} TO {$backup_name}";
            $rename_res = $wpdb->query($rename_sql);
            if ($rename_res === false) {
                error_log('Malisafi DB Repair: Failed to rename table: ' . $wpdb->last_error);
                wp_send_json_error(array('message' => 'Failed to alter or backup the table: ' . $wpdb->last_error));
            }

            // Recreate tables using plugin's Database class
            try {
                if (class_exists('\MalisafiMLS\Database')) {
                    \MalisafiMLS\Database::create_tables();
                    wp_send_json_success(array('message' => 'Table renamed to backup and tables recreated. Backup: ' . $backup_name));
                } else {
                    wp_send_json_error(array('message' => 'Database class not available to recreate tables.'));
                }
            } catch (\Throwable $e) {
                wp_send_json_error(array('message' => 'Recreate failed: ' . $e->getMessage()));
            }
        }

        wp_send_json_success(array('message' => '`client_ip` column added successfully.'));
    }

    /**
     * Log email attempts to wp_options for debugging
     * Stores last 100 email attempts with timestamp, recipient, status
     * 
     * @param array $log_data Email attempt data
     */
    public static function log_email_attempt($log_data) {
        // Get existing log
        $log = get_option('malisafi_inquiry_email_log', array());
        
        // Add timestamp if not present
        if (!isset($log_data['timestamp'])) {
            $log_data['timestamp'] = current_time('mysql');
        }
        
        // Add to beginning of array
        array_unshift($log, $log_data);
        
        // Keep only last 100 entries
        if (count($log) > 100) {
            $log = array_slice($log, 0, 100);
        }
        
        // Save back to options
        update_option('malisafi_inquiry_email_log', $log, false);
        
        // Also log to error_log for server logs
        $status_text = isset($log_data['status']) ? $log_data['status'] : 'unknown';
        $to = isset($log_data['to']) ? $log_data['to'] : 'unknown';
        $subject = isset($log_data['subject']) ? $log_data['subject'] : 'no subject';
        
        error_log(sprintf(
            'Malisafi Email: %s | To: %s | Subject: %s | Property: %d',
            strtoupper($status_text),
            $to,
            $subject,
            isset($log_data['property_id']) ? $log_data['property_id'] : 0
        ));
    }
}

// Initialize
Property_Actions_Ajax::get_instance();
