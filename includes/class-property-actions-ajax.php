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
                plugins_url('malisafi/assets/css/single-property.css'),
                array(),
                '1.0.0'
            );
            
            wp_enqueue_script(
                'malisafi-single-property',
                plugins_url('malisafi/assets/js/single-property.js'),
                array('jquery'),
                '1.0.0',
                true
            );
            
            wp_localize_script('malisafi-single-property', 'malisafiProperty', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('malisafi_property_nonce'),
                'user_logged_in' => is_user_logged_in()
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
        wp_send_json_error(array('message' => 'Please log in to add properties to your favorites.'));
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
        
        $sent = wp_mail($agent_email, $subject, $email_message, $headers);
        
        if ($sent) {
            // Store inquiry in database for tracking
            $inquiry_data = array(
                'property_id' => $property_id,
                'agent_id' => $agent_id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'message' => $message,
                'date' => current_time('mysql'),
                'ip' => $_SERVER['REMOTE_ADDR']
            );
            
            // Save as user meta for the agent
            $inquiries = get_user_meta($agent_id, '_malisafi_inquiries', true);
            $inquiries = $inquiries ? maybe_unserialize($inquiries) : array();
            $inquiries[] = $inquiry_data;
            update_user_meta($agent_id, '_malisafi_inquiries', $inquiries);
            
            wp_send_json_success(array('message' => 'Your message has been sent successfully.'));
        } else {
            wp_send_json_error(array('message' => 'Failed to send message. Please try again.'));
        }
    }
}

// Initialize
Property_Actions_Ajax::get_instance();
