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
        
        add_action('wp_ajax_malisafi_report_property', array($this, 'report_property'));
        add_action('wp_ajax_nopriv_malisafi_report_property', array($this, 'report_property'));
        
        add_action('wp_ajax_malisafi_contact_agent', array($this, 'contact_agent'));
        add_action('wp_ajax_nopriv_malisafi_contact_agent', array($this, 'contact_agent'));
        
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
                MALISAFI_MLS_URL . 'assets/js/single-property.js',
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
            
            wp_localize_script('malisafi-single-property', 'malisafiProperty', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('malisafi_property_nonce'),
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
     * Handle property report
     */
    public function report_property() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'malisafi_property_nonce')) {
            wp_send_json_error(array('message' => 'Invalid security token.'));
        }
        
        $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
        $reason = isset($_POST['report_reason']) ? sanitize_text_field($_POST['report_reason']) : '';
        $details = isset($_POST['report_details']) ? sanitize_textarea_field($_POST['report_details']) : '';
        $reporter_email = isset($_POST['reporter_email']) ? sanitize_email($_POST['reporter_email']) : '';
        
        // Validation
        if (!$property_id || get_post_type($property_id) !== 'malisafi_property') {
            wp_send_json_error(array('message' => 'Invalid property.'));
        }
        
        if (empty($reason)) {
            wp_send_json_error(array('message' => 'Please select a reason for reporting.'));
        }
        
        if (empty($reporter_email) || !is_email($reporter_email)) {
            wp_send_json_error(array('message' => 'Please provide a valid email address.'));
        }
        
        // Get property and agent info
        $property = get_post($property_id);
        $property_title = $property->post_title;
        $property_url = get_permalink($property_id);
        $agent_id = $property->post_author;
        $agent_email = get_the_author_meta('user_email', $agent_id);
        
        // Store report in database (as post meta for admin review)
        $report_data = array(
            'reason' => $reason,
            'details' => $details,
            'reporter_email' => $reporter_email,
            'date' => current_time('mysql'),
            'ip' => $_SERVER['REMOTE_ADDR']
        );
        
        // Get existing reports
        $reports = get_post_meta($property_id, '_malisafi_reports', true);
        $reports = $reports ? maybe_unserialize($reports) : array();
        $reports[] = $report_data;
        
        update_post_meta($property_id, '_malisafi_reports', $reports);
        
        // Send email notification to admin
        $admin_email = get_option('admin_email');
        $subject = 'Property Report: ' . $property_title;
        $message = "A property has been reported on your site.\n\n";
        $message .= "Property: {$property_title}\n";
        $message .= "URL: {$property_url}\n";
        $message .= "Reason: {$reason}\n";
        $message .= "Details: {$details}\n";
        $message .= "Reporter Email: {$reporter_email}\n";
        $message .= "Date: " . current_time('mysql') . "\n";
        
        wp_mail($admin_email, $subject, $message);
        
        wp_send_json_success(array('message' => 'Report submitted successfully.'));
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
                'client_id' => get_current_user_id() ?: null, // NULL for guest users
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
                wp_send_json_error(array('message' => 'Message sent but failed to save inquiry record.'));
            }
        } else {
            wp_send_json_error(array('message' => 'Failed to send message. Please try again.'));
        }
    }
}

// Initialize
Property_Actions_Ajax::get_instance();
