<?php
/**
 * Agent Settings AJAX Handler
 *
 * @package MalisafiMLS
 */

namespace MalisafiMLS;

if (!defined('ABSPATH')) {
    exit;
}

class Agent_Settings_Ajax {
    
    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('wp_ajax_malisafi_save_agent_settings', array($this, 'save_agent_settings'));
    }

    /**
     * Save agent settings via AJAX
     */
    public function save_agent_settings() {
        // Verify nonce
        if (!isset($_POST['settings_nonce']) || !wp_verify_nonce($_POST['settings_nonce'], 'malisafi_save_agent_settings')) {
            wp_send_json_error(array('message' => __('Security check failed', 'malisafi-mls')));
        }

        // Check user is logged in and is agent
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in', 'malisafi-mls')));
        }

        $current_user = wp_get_current_user();
        $is_agent = in_array('malisafi_agent_basic', $current_user->roles) || 
                    in_array('malisafi_agent_premium', $current_user->roles);

        if (!$is_agent) {
            wp_send_json_error(array('message' => __('Access restricted to agents', 'malisafi-mls')));
        }

        // Sanitize and validate settings
        $settings = array(
            // Display preferences
            'display_mode' => sanitize_text_field($_POST['display_mode'] ?? 'light'),
            'language' => sanitize_text_field($_POST['language'] ?? 'en'),
            'date_format' => sanitize_text_field($_POST['date_format'] ?? 'd/m/Y'),
            'currency' => sanitize_text_field($_POST['currency'] ?? 'KES'),
            'units' => sanitize_text_field($_POST['units'] ?? 'metric'),
            
            // Property listing preferences
            'properties_view' => sanitize_text_field($_POST['properties_view'] ?? 'grid'),
            'properties_sort' => sanitize_text_field($_POST['properties_sort'] ?? 'date_desc'),
            'properties_per_page' => absint($_POST['properties_per_page'] ?? 12),
            
            // Email notifications
            'notify_new_lead' => sanitize_text_field($_POST['notify_new_lead'] ?? 'no'),
            'notify_property_approved' => sanitize_text_field($_POST['notify_property_approved'] ?? 'no'),
            'notify_property_rejected' => sanitize_text_field($_POST['notify_property_rejected'] ?? 'no'),
            'notify_limit_reached' => sanitize_text_field($_POST['notify_limit_reached'] ?? 'no'),
            'notify_weekly_report' => sanitize_text_field($_POST['notify_weekly_report'] ?? 'no'),
            'notify_favorites' => sanitize_text_field($_POST['notify_favorites'] ?? 'no'),
            
            // Privacy settings
            'show_phone_public' => sanitize_text_field($_POST['show_phone_public'] ?? 'no'),
            'show_email_public' => sanitize_text_field($_POST['show_email_public'] ?? 'no'),
            'profile_searchable' => sanitize_text_field($_POST['profile_searchable'] ?? 'no'),
            'accept_direct_messages' => sanitize_text_field($_POST['accept_direct_messages'] ?? 'no'),
            
            // Analytics preferences
            'analytics_period' => absint($_POST['analytics_period'] ?? 30),
            'show_stats_overview' => sanitize_text_field($_POST['show_stats_overview'] ?? 'no'),
            'show_stats_leads' => sanitize_text_field($_POST['show_stats_leads'] ?? 'no'),
            'show_stats_views' => sanitize_text_field($_POST['show_stats_views'] ?? 'no')
        );

        // Validate allowed values
        $valid_modes = array('light', 'dark');
        $valid_languages = array('en', 'sw', 'fr');
        $valid_currencies = array('KES', 'USD', 'EUR');
        $valid_units = array('metric', 'imperial');
        $valid_views = array('grid', 'list');
        $valid_sorts = array('date_desc', 'date_asc', 'price_desc', 'price_asc');
        $valid_periods = array(7, 30, 90);

        if (!in_array($settings['display_mode'], $valid_modes)) {
            $settings['display_mode'] = 'light';
        }
        if (!in_array($settings['language'], $valid_languages)) {
            $settings['language'] = 'en';
        }
        if (!in_array($settings['currency'], $valid_currencies)) {
            $settings['currency'] = 'KES';
        }
        if (!in_array($settings['units'], $valid_units)) {
            $settings['units'] = 'metric';
        }
        if (!in_array($settings['properties_view'], $valid_views)) {
            $settings['properties_view'] = 'grid';
        }
        if (!in_array($settings['properties_sort'], $valid_sorts)) {
            $settings['properties_sort'] = 'date_desc';
        }
        if (!in_array($settings['analytics_period'], $valid_periods)) {
            $settings['analytics_period'] = 30;
        }

        // Save settings
        $updated = update_user_meta($current_user->ID, 'malisafi_agent_settings', $settings);

        if ($updated !== false) {
            // Log activity
            $this->log_settings_change($current_user->ID, $settings);

            wp_send_json_success(array(
                'message' => __('Settings saved successfully', 'malisafi-mls'),
                'settings' => $settings
            ));
        } else {
            wp_send_json_error(array('message' => __('No changes detected', 'malisafi-mls')));
        }
    }

    /**
     * Log settings change for analytics
     *
     * @param int $user_id User ID
     * @param array $settings New settings
     */
    private function log_settings_change($user_id, $settings) {
        global $wpdb;

        // Only log if analytics table exists
        $table = $wpdb->prefix . 'mf_user_activity';
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
            $wpdb->insert(
                $table,
                array(
                    'user_id' => $user_id,
                    'activity_type' => 'settings_update',
                    'item_id' => 0,
                    'item_type' => 'settings',
                    'metadata' => json_encode(array(
                        'timestamp' => current_time('mysql'),
                        'ip_address' => $this->get_user_ip()
                    )),
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%s', '%d', '%s', '%s', '%s')
            );
        }
    }

    /**
     * Get user IP address
     *
     * @return string
     */
    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
        } else {
            return sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
        }
    }
}

// Initialize
Agent_Settings_Ajax::get_instance();
