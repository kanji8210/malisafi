<?php
/**
 * Agent Actions AJAX Handler
 * Handles ratings and reports for agents
 *
 * @package MalisafiMLS
 */

namespace MalisafiMLS;

if (!defined('ABSPATH')) {
    exit;
}

class Agent_Actions_Ajax {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // AJAX handlers for logged-in users
        add_action('wp_ajax_malisafi_rate_agent', array($this, 'rate_agent'));
        add_action('wp_ajax_malisafi_report_agent', array($this, 'report_agent'));
        add_action('wp_ajax_malisafi_helpful_review', array($this, 'mark_helpful'));
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        if (!is_admin()) {
            wp_enqueue_style(
                'malisafi-agent-profile',
                plugins_url('malisafi/assets/css/agent-profile.css'),
                array(),
                '1.0.0'
            );
            
            wp_enqueue_script(
                'malisafi-agent-actions',
                plugins_url('malisafi/assets/js/agent-actions.js'),
                array('jquery'),
                '1.0.0',
                true
            );
            
            wp_localize_script('malisafi-agent-actions', 'malisafiAgentAjax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('malisafi_agent_nonce'),
                'isLoggedIn' => is_user_logged_in(),
                'messages' => array(
                    'loginRequired' => __('Please log in to perform this action.', 'malisafi-mls'),
                    'ratingSuccess' => __('Thank you for your rating!', 'malisafi-mls'),
                    'reportSuccess' => __('Report submitted successfully. We will review it.', 'malisafi-mls'),
                    'error' => __('An error occurred. Please try again.', 'malisafi-mls'),
                )
            ));
        }
    }
    
    /**
     * Rate an agent
     */
    public function rate_agent() {
        check_ajax_referer('malisafi_agent_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to rate an agent.', 'malisafi-mls')));
        }
        
        $agent_id = isset($_POST['agent_id']) ? intval($_POST['agent_id']) : 0;
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
        $review_title = isset($_POST['review_title']) ? sanitize_text_field($_POST['review_title']) : '';
        $review_text = isset($_POST['review_text']) ? sanitize_textarea_field($_POST['review_text']) : '';
        $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : null;
        
        // Validation
        if ($agent_id <= 0 || $rating < 1 || $rating > 5) {
            wp_send_json_error(array('message' => __('Invalid data provided.', 'malisafi-mls')));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'mf_agent_ratings';
        $user_id = get_current_user_id();
        
        // Check if user already rated this agent
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$table} WHERE user_id = %d AND agent_id = %d",
            $user_id,
            $agent_id
        ));
        
        if ($existing) {
            // Update existing rating
            $wpdb->update(
                $table,
                array(
                    'rating' => $rating,
                    'review_title' => $review_title,
                    'review_text' => $review_text,
                    'property_id' => $property_id,
                    'updated_at' => current_time('mysql')
                ),
                array('id' => $existing->id),
                array('%d', '%s', '%s', '%d', '%s'),
                array('%d')
            );
            
            $message = __('Your rating has been updated.', 'malisafi-mls');
        } else {
            // Insert new rating
            $wpdb->insert(
                $table,
                array(
                    'agent_id' => $agent_id,
                    'user_id' => $user_id,
                    'rating' => $rating,
                    'review_title' => $review_title,
                    'review_text' => $review_text,
                    'property_id' => $property_id,
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%d', '%d', '%s', '%s', '%d', '%s')
            );
            
            $message = __('Thank you for your rating!', 'malisafi-mls');
        }
        
        // Get updated average
        $avg_rating = $this->get_agent_average_rating($agent_id);
        
        wp_send_json_success(array(
            'message' => $message,
            'average_rating' => $avg_rating['average'],
            'total_ratings' => $avg_rating['total']
        ));
    }
    
    /**
     * Report an agent
     */
    public function report_agent() {
        check_ajax_referer('malisafi_agent_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to report an agent.', 'malisafi-mls')));
        }
        
        $agent_id = isset($_POST['agent_id']) ? intval($_POST['agent_id']) : 0;
        $report_type = isset($_POST['report_type']) ? sanitize_text_field($_POST['report_type']) : '';
        $report_reason = isset($_POST['report_reason']) ? sanitize_textarea_field($_POST['report_reason']) : '';
        
        // Validation
        $valid_types = array('spam', 'inappropriate', 'fraud', 'harassment', 'fake_info', 'other');
        if ($agent_id <= 0 || !in_array($report_type, $valid_types) || empty($report_reason)) {
            wp_send_json_error(array('message' => __('Invalid data provided.', 'malisafi-mls')));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'mf_agent_reports';
        $user_id = get_current_user_id();
        
        // Insert report
        $wpdb->insert(
            $table,
            array(
                'agent_id' => $agent_id,
                'reported_by' => $user_id,
                'report_type' => $report_type,
                'report_reason' => $report_reason,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );
        
        wp_send_json_success(array(
            'message' => __('Report submitted successfully. We will review it shortly.', 'malisafi-mls')
        ));
    }
    
    /**
     * Mark review as helpful
     */
    public function mark_helpful() {
        check_ajax_referer('malisafi_agent_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in.', 'malisafi-mls')));
        }
        
        $review_id = isset($_POST['review_id']) ? intval($_POST['review_id']) : 0;
        $helpful = isset($_POST['helpful']) ? (bool)$_POST['helpful'] : true;
        
        if ($review_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid review ID.', 'malisafi-mls')));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'mf_agent_ratings';
        
        $field = $helpful ? 'helpful_count' : 'not_helpful_count';
        
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table} SET {$field} = {$field} + 1 WHERE id = %d",
            $review_id
        ));
        
        $counts = $wpdb->get_row($wpdb->prepare(
            "SELECT helpful_count, not_helpful_count FROM {$table} WHERE id = %d",
            $review_id
        ));
        
        wp_send_json_success(array(
            'helpful_count' => $counts->helpful_count,
            'not_helpful_count' => $counts->not_helpful_count
        ));
    }
    
    /**
     * Get agent average rating
     */
    private function get_agent_average_rating($agent_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'mf_agent_ratings';
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                AVG(rating) as average,
                COUNT(*) as total
            FROM {$table}
            WHERE agent_id = %d AND status = 'approved'",
            $agent_id
        ));
        
        return array(
            'average' => $stats ? round($stats->average, 1) : 0,
            'total' => $stats ? $stats->total : 0
        );
    }
    
    /**
     * Get agent ratings
     */
    public static function get_agent_ratings($agent_id, $limit = 10, $offset = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'mf_agent_ratings';
        
        $ratings = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, u.display_name as reviewer_name
            FROM {$table} r
            LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
            WHERE r.agent_id = %d AND r.status = 'approved'
            ORDER BY r.created_at DESC
            LIMIT %d OFFSET %d",
            $agent_id,
            $limit,
            $offset
        ));
        
        return $ratings;
    }
}

// Initialize
Agent_Actions_Ajax::get_instance();
