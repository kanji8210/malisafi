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
        add_action('wp_ajax_malisafi_rate_agent', array($this, 'submit_review')); // Alias for compatibility
        add_action('wp_ajax_malisafi_helpful_review', array($this, 'vote_helpful'));
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
            'first_name' => isset($_POST['agent_first_name']) ? sanitize_text_field($_POST['agent_first_name']) : '',
            'last_name' => isset($_POST['agent_last_name']) ? sanitize_text_field($_POST['agent_last_name']) : '',
            'display_name' => isset($_POST['agent_display_name']) ? sanitize_text_field($_POST['agent_display_name']) : '',
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
        
        if (empty($data['first_name']) || empty($data['last_name']) || empty($data['display_name'])) {
            wp_send_json_error(array('message' => __('First name, last name, and display name are required.', 'malisafi-mls')));
        }
        
        // Create or update agent profile post
        if ($agent_id) {
            // Update existing
            $agent = get_post($agent_id);
            if (!$agent || $agent->post_type !== 'malisafi_agent') {
                wp_send_json_error(array('message' => __('Invalid agent profile.', 'malisafi-mls')));
            }
            
            // Verify ownership or admin permission
            $linked_user = get_post_meta($agent_id, '_agent_user_id', true);
            $is_admin = current_user_can('manage_options');
            
            if ($linked_user != $current_user->ID && !$is_admin) {
                wp_send_json_error(array('message' => __('Permission denied.', 'malisafi-mls')));
            }
            
            // Determine which user profile to update
            // If admin is editing, use the agent's user ID; otherwise use current user
            $user_id_to_update = $is_admin && $linked_user ? intval($linked_user) : $current_user->ID;
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
            $user_id_to_update = $current_user->ID;
        }
        
        // Update WordPress user profile (name fields)
        // Use the correct user ID (agent's ID if admin is editing, or current user's ID)
        $user_update = array(
            'ID' => $user_id_to_update,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'display_name' => $data['display_name']
        );
        
        $user_updated = wp_update_user($user_update);
        if (is_wp_error($user_updated)) {
            wp_send_json_error(array('message' => __('Failed to update user profile: ', 'malisafi-mls') . $user_updated->get_error_message()));
        }
        
        // Also update the agent post title to match display name
        wp_update_post(array(
            'ID' => $agent_id,
            'post_title' => $data['display_name']
        ));
        
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
        // Verify nonce
        check_ajax_referer('agent_actions_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to submit a review.', 'malisafi-mls')));
        }
        
        $agent_id = isset($_POST['agent_id']) ? intval($_POST['agent_id']) : 0;
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
        $review_title = isset($_POST['review_title']) ? sanitize_text_field($_POST['review_title']) : '';
        $review_text = isset($_POST['review_text']) ? sanitize_textarea_field($_POST['review_text']) : '';
        $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : null;
        
        // Validation
        if (!$agent_id || $rating < 1 || $rating > 5) {
            wp_send_json_error(array('message' => __('Invalid rating data.', 'malisafi-mls')));
        }
        
        if (strlen($review_text) < 10) {
            wp_send_json_error(array('message' => __('Review must be at least 10 characters long.', 'malisafi-mls')));
        }
        
        $current_user_id = get_current_user_id();
        
        // Get agent's linked user ID
        $agent_user_id = get_post_meta($agent_id, '_agent_user_id', true);
        
        // Cannot rate yourself
        if ($current_user_id == $agent_user_id) {
            wp_send_json_error(array('message' => __('You cannot rate yourself.', 'malisafi-mls')));
        }
        
        // Agents cannot rate other agents
        $current_user = wp_get_current_user();
        $is_agent = in_array('malisafi_agent_basic', $current_user->roles) || 
                   in_array('malisafi_agent_premium', $current_user->roles);
        
        if ($is_agent) {
            wp_send_json_error(array('message' => __('Agents cannot rate other agents.', 'malisafi-mls')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'mf_agent_ratings';
        
        // Check if user already reviewed this agent
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE agent_id = %d AND user_id = %d",
            $agent_id,
            $current_user_id
        ));
        
        if ($existing) {
            wp_send_json_error(array('message' => __('You have already reviewed this agent. Each user can only submit one review per agent.', 'malisafi-mls')));
        }
        
        // Check if user is verified client (has worked with this agent)
        $verified_client = false;
        if ($property_id) {
            $property_agent_id = get_post_meta($property_id, '_property_agent', true);
            if ($property_agent_id == $agent_id) {
                $verified_client = true;
            }
        }
        
        // Insert review
        $inserted = $wpdb->insert(
            $table_name,
            array(
                'agent_id' => $agent_id,
                'user_id' => $current_user_id,
                'rating' => $rating,
                'review_title' => $review_title,
                'review_text' => $review_text,
                'property_id' => $property_id,
                'verified_client' => $verified_client,
                'status' => 'approved', // Auto-approve for now, can be changed to 'pending'
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%d', '%s', '%s', '%d', '%d', '%s', '%s')
        );
        
        if ($inserted) {
            // Clear cache if cache manager exists
            if (class_exists('MalisafiMLS\Cache_Manager')) {
                \MalisafiMLS\Cache_Manager::clear_agent_cache($agent_id);
            }
            
            // Calculate new average rating
            $avg_rating = $wpdb->get_var($wpdb->prepare(
                "SELECT AVG(rating) FROM $table_name WHERE agent_id = %d AND status = 'approved'",
                $agent_id
            ));
            
            $total_ratings = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE agent_id = %d AND status = 'approved'",
                $agent_id
            ));
            
            // Update agent meta with rating
            update_post_meta($agent_id, '_malisafi_agent_rating', round($avg_rating, 2));
            update_post_meta($agent_id, '_malisafi_agent_rating_count', $total_ratings);
            
            wp_send_json_success(array(
                'message' => __('Thank you! Your review has been submitted successfully.', 'malisafi-mls'),
                'average_rating' => round($avg_rating, 1),
                'total_ratings' => $total_ratings
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to submit review. Please try again.', 'malisafi-mls')));
        }
    }
    
    /**
     * Vote on review helpfulness
     */
    public function vote_helpful() {
        // Verify nonce
        check_ajax_referer('agent_actions_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to vote.', 'malisafi-mls')));
        }
        
        $review_id = isset($_POST['review_id']) ? intval($_POST['review_id']) : 0;
        $helpful = isset($_POST['helpful']) ? (bool)$_POST['helpful'] : true;
        
        if (!$review_id) {
            wp_send_json_error(array('message' => __('Invalid review ID.', 'malisafi-mls')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'mf_agent_ratings';
        
        // Get current review
        $review = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $review_id
        ));
        
        if (!$review) {
            wp_send_json_error(array('message' => __('Review not found.', 'malisafi-mls')));
        }
        
        // Check if user already voted on this review (using user meta)
        $vote_key = 'review_helpful_' . $review_id;
        $existing_vote = get_user_meta(get_current_user_id(), $vote_key, true);
        
        if ($existing_vote) {
            wp_send_json_error(array('message' => __('You have already voted on this review.', 'malisafi-mls')));
        }
        
        // Update vote count
        $field = $helpful ? 'helpful_count' : 'not_helpful_count';
        $updated = $wpdb->query($wpdb->prepare(
            "UPDATE $table_name SET $field = $field + 1 WHERE id = %d",
            $review_id
        ));
        
        if ($updated !== false) {
            // Save user's vote
            update_user_meta(get_current_user_id(), $vote_key, $helpful ? 'yes' : 'no');
            
            // Get updated counts
            $updated_review = $wpdb->get_row($wpdb->prepare(
                "SELECT helpful_count, not_helpful_count FROM $table_name WHERE id = %d",
                $review_id
            ));
            
            wp_send_json_success(array(
                'message' => __('Thank you for your feedback!', 'malisafi-mls'),
                'helpful_count' => intval($updated_review->helpful_count),
                'not_helpful_count' => intval($updated_review->not_helpful_count)
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to record vote. Please try again.', 'malisafi-mls')));
        }
    }
}

// Initialize
Agent_Profile_Ajax::get_instance();
