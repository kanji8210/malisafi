<?php
/**
 * Agent Profile AJAX Handlers
 *
 * @package MalisafiMLS
 */

namespace MalisafiMLS;

if (!defined('ABSPATH')) {
    exit;
}

class Agent_Profile_Ajax {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_ajax_upload_agent_photo', array($this, 'upload_photo'));
        add_action('wp_ajax_save_agent_profile', array($this, 'save_profile'));
        add_action('wp_ajax_submit_agent_review', array($this, 'submit_review'));
        add_action('wp_ajax_nopriv_submit_agent_review', array($this, 'submit_review'));
    }
    
    /**
     * Upload agent photo
     */
    public function upload_photo() {
        check_ajax_referer('upload_agent_photo', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in.', 'malisafi-mls')));
        }
        
        if (empty($_FILES['photo'])) {
            wp_send_json_error(array('message' => __('No file uploaded.', 'malisafi-mls')));
        }
        
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        
        $file = $_FILES['photo'];
        
        // Validate file type
        $allowed_types = array('image/jpeg', 'image/png', 'image/webp');
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(array('message' => __('Invalid file type. Only JPG, PNG, and WebP are allowed.', 'malisafi-mls')));
        }
        
        // Validate file size (2MB max)
        if ($file['size'] > 2 * 1024 * 1024) {
            wp_send_json_error(array('message' => __('File size too large. Maximum 2MB allowed.', 'malisafi-mls')));
        }
        
        $upload = wp_handle_upload($file, array('test_form' => false));
        
        if (isset($upload['error'])) {
            wp_send_json_error(array('message' => $upload['error']));
        }
        
        $attachment_id = wp_insert_attachment(array(
            'post_mime_type' => $upload['type'],
            'post_title' => sanitize_file_name($file['name']),
            'post_content' => '',
            'post_status' => 'inherit'
        ), $upload['file']);
        
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array('message' => $attachment_id->get_error_message()));
        }
        
        $metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        wp_update_attachment_metadata($attachment_id, $metadata);
        
        wp_send_json_success(array(
            'attachment_id' => $attachment_id,
            'url' => wp_get_attachment_url($attachment_id)
        ));
    }
    
    /**
     * Save agent profile
     */
    public function save_profile() {
        check_ajax_referer('save_agent_profile', 'agent_profile_nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in.', 'malisafi-mls')));
        }
        
        $current_user = wp_get_current_user();
        $agent_id = isset($_POST['agent_id']) ? intval($_POST['agent_id']) : 0;
        
        // Sanitize inputs
        $data = array(
            'agent_email' => sanitize_email($_POST['agent_email']),
            'agent_phone' => sanitize_text_field($_POST['agent_phone']),
            'agent_whatsapp' => sanitize_text_field($_POST['agent_whatsapp']),
            'agent_bio' => sanitize_textarea_field($_POST['agent_bio']),
            'agent_specialties' => sanitize_text_field($_POST['agent_specialties']),
            'agent_experience' => intval($_POST['agent_experience']),
            'agent_license' => sanitize_text_field($_POST['agent_license']),
            'agent_languages' => sanitize_text_field($_POST['agent_languages']),
            'agent_photo_id' => intval($_POST['agent_photo_id']),
            'agent_facebook' => esc_url_raw($_POST['agent_facebook']),
            'agent_twitter' => esc_url_raw($_POST['agent_twitter']),
            'agent_linkedin' => esc_url_raw($_POST['agent_linkedin']),
            'agent_instagram' => esc_url_raw($_POST['agent_instagram']),
        );
        
        // Validate required fields
        if (empty($data['agent_email']) || empty($data['agent_phone'])) {
            wp_send_json_error(array('message' => __('Email and phone number are required.', 'malisafi-mls')));
        }
        
        // Create or update agent profile post
        if ($agent_id) {
            // Update existing
            $agent = get_post($agent_id);
            if (!$agent || $agent->post_type !== 'malisafi_agent') {
                wp_send_json_error(array('message' => __('Invalid agent profile.', 'malisafi-mls')));
            }
            
            // Verify ownership
            $linked_user = get_post_meta($agent_id, '_agent_user_id', true);
            if ($linked_user != $current_user->ID && !current_user_can('manage_options')) {
                wp_send_json_error(array('message' => __('Permission denied.', 'malisafi-mls')));
            }
        } else {
            // Create new agent profile
            $agent_id = wp_insert_post(array(
                'post_type' => 'malisafi_agent',
                'post_title' => $current_user->display_name,
                'post_status' => 'publish',
                'post_author' => $current_user->ID
            ));
            
            if (is_wp_error($agent_id)) {
                wp_send_json_error(array('message' => $agent_id->get_error_message()));
            }
            
            update_post_meta($agent_id, '_agent_user_id', $current_user->ID);
        }
        
        // Update meta fields
        update_post_meta($agent_id, '_agent_photo', $data['agent_photo_id']);
        update_post_meta($agent_id, '_agent_email', $data['agent_email']);
        update_post_meta($agent_id, '_agent_phone', $data['agent_phone']);
        update_post_meta($agent_id, '_agent_whatsapp', $data['agent_whatsapp']);
        update_post_meta($agent_id, '_agent_bio', $data['agent_bio']);
        update_post_meta($agent_id, '_agent_specialties', $data['agent_specialties']);
        update_post_meta($agent_id, '_agent_experience', $data['agent_experience']);
        update_post_meta($agent_id, '_agent_license', $data['agent_license']);
        update_post_meta($agent_id, '_agent_languages', $data['agent_languages']);
        update_post_meta($agent_id, '_agent_facebook', $data['agent_facebook']);
        update_post_meta($agent_id, '_agent_twitter', $data['agent_twitter']);
        update_post_meta($agent_id, '_agent_linkedin', $data['agent_linkedin']);
        update_post_meta($agent_id, '_agent_instagram', $data['agent_instagram']);
        
        wp_send_json_success(array(
            'message' => __('Profile saved successfully!', 'malisafi-mls'),
            'agent_id' => $agent_id
        ));
    }
    
    /**
     * Submit agent review
     */
    public function submit_review() {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to submit a review.', 'malisafi-mls')));
        }
        
        $agent_id = isset($_POST['agent_id']) ? intval($_POST['agent_id']) : 0;
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
        $comment = isset($_POST['comment']) ? sanitize_textarea_field($_POST['comment']) : '';
        
        if (!$agent_id || $rating < 1 || $rating > 5) {
            wp_send_json_error(array('message' => __('Invalid rating data.', 'malisafi-mls')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'mf_agent_ratings';
        
        // Check if user already reviewed this agent
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE agent_id = %d AND user_id = %d",
            $agent_id,
            get_current_user_id()
        ));
        
        if ($existing) {
            wp_send_json_error(array('message' => __('You have already reviewed this agent.', 'malisafi-mls')));
        }
        
        // Insert review
        $inserted = $wpdb->insert(
            $table_name,
            array(
                'agent_id' => $agent_id,
                'user_id' => get_current_user_id(),
                'rating' => $rating,
                'comment' => $comment,
                'status' => 'pending', // Requires approval
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%d', '%s', '%s', '%s')
        );
        
        if ($inserted) {
            wp_send_json_success(array(
                'message' => __('Thank you! Your review has been submitted and is pending approval.', 'malisafi-mls')
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to submit review. Please try again.', 'malisafi-mls')));
        }
    }
}

// Initialize
Agent_Profile_Ajax::get_instance();
