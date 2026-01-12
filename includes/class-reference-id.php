<?php
namespace MalisafiMLS;

if (!defined('ABSPATH')) exit;

class Reference_ID {
    
    /**
     * Initialize hooks
     */
    public static function init() {
        add_action('save_post_malisafi_property', array(__CLASS__, 'ensure'), 10, 2);
        add_action('wp_ajax_malisafi_generate_reference_id', array(__CLASS__, 'ajax_generate'));
    }
    
    /**
     * AJAX handler to generate reference ID on demand
     */
    public static function ajax_generate() {
        check_ajax_referer('malisafi_generate_ref_id', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'malisafi-mls')));
        }
        
        $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
        
        if (!$property_id) {
            wp_send_json_error(array('message' => __('Invalid property ID.', 'malisafi-mls')));
        }
        
        $property = get_post($property_id);
        if (!$property || $property->post_type !== 'malisafi_property') {
            wp_send_json_error(array('message' => __('Property not found.', 'malisafi-mls')));
        }
        
        // Check if already has one
        $existing = get_post_meta($property_id, '_malisafi_reference_id', true);
        if (!empty($existing)) {
            wp_send_json_success(array(
                'reference_id' => $existing,
                'message' => __('Reference ID already exists.', 'malisafi-mls')
            ));
        }
        
        // Generate new ID
        $ref = 'PROP-' . gmdate('Ymd') . '-' . $property_id;
        update_post_meta($property_id, '_malisafi_reference_id', $ref);
        
        wp_send_json_success(array(
            'reference_id' => $ref,
            'message' => __('Reference ID generated!', 'malisafi-mls')
        ));
    }
    
    /**
     * Ensure a reference ID exists for a property
     */
    public static function ensure($post_id, $post) {
        // Only for our post type and not autosaves/revisions
        if ($post->post_type !== 'malisafi_property') {
            return;
        }
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        // Skip on bulk/quick edits without capability
        if (! current_user_can('edit_post', $post_id)) {
            return;
        }
        $existing = get_post_meta($post_id, '_malisafi_reference_id', true);
        if (empty($existing)) {
            $ref = 'PROP-' . gmdate('Ymd') . '-' . $post_id;
            update_post_meta($post_id, '_malisafi_reference_id', $ref);
        }
    }
    
    /**
     * Generate reference IDs for all properties missing them
     * Can be called manually or via admin action
     */
    public static function generate_missing_ids() {
        global $wpdb;
        
        // Find all properties without a reference ID
        $properties_without_ref = $wpdb->get_col("
            SELECT p.ID 
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_malisafi_reference_id'
            WHERE p.post_type = 'malisafi_property'
            AND (pm.meta_value IS NULL OR pm.meta_value = '')
        ");
        
        $count = 0;
        foreach ($properties_without_ref as $post_id) {
            $ref = 'PROP-' . gmdate('Ymd') . '-' . $post_id;
            update_post_meta($post_id, '_malisafi_reference_id', $ref);
            $count++;
        }
        
        return $count;
    }
}
