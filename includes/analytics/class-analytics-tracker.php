<?php
/**
 * Analytics Event Tracker
 *
 * Handles real-time tracking of user events and activities
 *
 * @package MalisafiMLS
 * @subpackage Analytics
 * @since 1.0.0
 */

namespace MalisafiMLS\Analytics;

if (!defined('ABSPATH')) {
    exit;
}

class Analytics_Tracker {

    /**
     * Initialize tracker hooks
     */
    public static function init() {
        // User activity tracking
        add_action('wp_login', [__CLASS__, 'track_login'], 10, 2);
        add_action('wp_logout', [__CLASS__, 'track_logout']);
        add_action('init', [__CLASS__, 'start_session']);
        
        // Property tracking
        add_action('template_redirect', [__CLASS__, 'track_property_view']);
        add_action('save_post_malisafi_property', [__CLASS__, 'track_property_submission'], 10, 3);
        
        // AJAX handlers
        add_action('wp_ajax_malisafi_track_funnel', [__CLASS__, 'ajax_track_funnel']);
        add_action('wp_ajax_malisafi_track_interaction', [__CLASS__, 'ajax_track_interaction']);
        add_action('wp_ajax_nopriv_malisafi_track_interaction', [__CLASS__, 'ajax_track_interaction']);
        add_action('wp_ajax_malisafi_track_view_duration', [__CLASS__, 'ajax_track_view_duration']);
        add_action('wp_ajax_nopriv_malisafi_track_view_duration', [__CLASS__, 'ajax_track_view_duration']);
        
        // Enqueue tracking scripts
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_tracking_scripts']);
    }

    /**
     * Start session for tracking
     */
    public static function start_session() {
        if (!session_id() && !headers_sent()) {
            session_start();
        }
    }

    /**
     * Get current session ID
     */
    private static function get_session_id() {
        if (session_id()) {
            return session_id();
        }
        
        // Fallback to cookie-based session
        if (isset($_COOKIE['malisafi_session'])) {
            return sanitize_text_field($_COOKIE['malisafi_session']);
        }
        
        // Generate new session
        $session_id = wp_generate_uuid4();
        setcookie('malisafi_session', $session_id, time() + (86400 * 30), '/');
        return $session_id;
    }

    /**
     * Get device type
     */
    private static function get_device_type() {
        if (wp_is_mobile()) {
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            return (stripos($ua, 'tablet') !== false || stripos($ua, 'ipad') !== false) ? 'tablet' : 'mobile';
        }
        return 'desktop';
    }

    /**
     * Track user login
     */
    public static function track_login($user_login, $user) {
        global $wpdb;
        
        // Only track Malisafi roles
        $malisafi_roles = ['malisafi_agent_basic', 'malisafi_agent_premium', 'malisafi_owner', 'malisafi_developer'];
        if (!array_intersect($malisafi_roles, $user->roles)) {
            return;
        }
        
        $wpdb->insert(
            $wpdb->prefix . 'mf_user_activity',
            [
                'user_id' => $user->ID,
                'activity_type' => 'login',
                'session_id' => self::get_session_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'device_type' => self::get_device_type(),
                'referrer' => isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : ''
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );
    }

    /**
     * Track user logout
     */
    public static function track_logout($user_id = null) {
        global $wpdb;
        
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return;
        }
        
        // Calculate session duration
        $last_login = $wpdb->get_row($wpdb->prepare("
            SELECT created_at FROM {$wpdb->prefix}mf_user_activity 
            WHERE user_id = %d AND activity_type = 'login'
            ORDER BY created_at DESC LIMIT 1
        ", $user_id));
        
        $time_spent = 0;
        if ($last_login) {
            $time_spent = time() - strtotime($last_login->created_at);
        }
        
        $wpdb->insert(
            $wpdb->prefix . 'mf_user_activity',
            [
                'user_id' => $user_id,
                'activity_type' => 'logout',
                'time_spent' => $time_spent,
                'session_id' => self::get_session_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
            ],
            ['%d', '%s', '%d', '%s', '%s']
        );
    }

    /**
     * Track property view
     */
    public static function track_property_view() {
        if (!is_singular('malisafi_property')) {
            return;
        }
        
        global $wpdb, $post;
        
        // Get property meta
        $property_meta = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}mf_properties 
            WHERE post_id = %d
        ", $post->ID));
        
        if (!$property_meta) {
            return;
        }
        
        $session_id = self::get_session_id();
        $utm_source = isset($_GET['utm_source']) ? sanitize_text_field(wp_unslash($_GET['utm_source'])) : '';
        $referrer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : '';
        $source = $utm_source !== '' ? $utm_source : ($referrer !== '' ? $referrer : 'direct');
        
        // Insert property view
        $wpdb->insert(
            $wpdb->prefix . 'mf_property_views',
            [
                'property_id' => $property_meta->property_id,
                'post_id' => $post->ID,
                'user_id' => get_current_user_id() ?: null,
                'session_id' => $session_id,
                'view_type' => 'single',
                'source' => $source,
                'referrer' => $referrer,
                'device_type' => self::get_device_type(),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
            ],
            ['%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );
        
        // Update views count
        $wpdb->query($wpdb->prepare("
            UPDATE {$wpdb->prefix}mf_properties 
            SET views_count = views_count + 1, last_viewed = NOW()
            WHERE property_id = %d
        ", $property_meta->property_id));
        
        // Legacy analytics table
        $wpdb->insert(
            $wpdb->prefix . 'mf_analytics',
            [
                'property_id' => $property_meta->property_id,
                'user_id' => get_current_user_id() ?: null,
                'action' => 'view',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'session_id' => $session_id
            ]
        );
    }

    /**
     * Track property submission
     */
    public static function track_property_submission($post_id, $post, $update) {
        global $wpdb;
        
        // Skip autosaves and revisions
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }
        
        $session_id = self::get_session_id();
        $user_id = get_current_user_id();
        
        if ($update) {
            // Property edit
            $wpdb->insert(
                $wpdb->prefix . 'mf_user_activity',
                [
                    'user_id' => $user_id,
                    'activity_type' => 'property_edit',
                    'activity_data' => json_encode(['post_id' => $post_id, 'title' => $post->post_title]),
                    'session_id' => $session_id,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
                ]
            );
        } else {
            // New property submission
            $wpdb->insert(
                $wpdb->prefix . 'mf_user_activity',
                [
                    'user_id' => $user_id,
                    'activity_type' => 'property_add_complete',
                    'activity_data' => json_encode(['post_id' => $post_id, 'title' => $post->post_title]),
                    'session_id' => $session_id,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? ''
                ]
            );
            
            // Mark funnel as complete
            $wpdb->update(
                $wpdb->prefix . 'mf_submission_funnel',
                [
                    'completed' => 1,
                    'property_id' => $post_id
                ],
                [
                    'user_id' => $user_id,
                    'session_id' => $session_id
                ]
            );
            
            // Log final funnel step
            $wpdb->insert(
                $wpdb->prefix . 'mf_submission_funnel',
                [
                    'user_id' => $user_id,
                    'session_id' => $session_id,
                    'step_name' => 'submit_success',
                    'property_id' => $post_id,
                    'completed' => 1
                ]
            );
        }
    }

    /**
     * AJAX: Track funnel step
     */
    public static function ajax_track_funnel() {
        check_ajax_referer('malisafi_nonce', 'nonce');
        
        global $wpdb;
        
        $step_map = [
            'Basic Information' => 'basic_info',
            'Pricing' => 'pricing',
            'Property Details' => 'details',
            'Location' => 'location',
            'Amenities & Features' => 'amenities',
            'Property Images' => 'images'
        ];
        
        $section = sanitize_text_field($_POST['section'] ?? '');
        $step_name = $step_map[$section] ?? 'unknown';
        
        if ($step_name === 'unknown') {
            wp_send_json_error(['message' => 'Invalid section']);
        }
        
        $wpdb->insert(
            $wpdb->prefix . 'mf_submission_funnel',
            [
                'user_id' => get_current_user_id(),
                'session_id' => self::get_session_id(),
                'step_name' => $step_name,
                'step_data' => json_encode([
                    'field' => sanitize_text_field($_POST['field'] ?? ''),
                    'filled' => intval($_POST['has_value'] ?? 0)
                ])
            ]
        );
        
        wp_send_json_success();
    }

    /**
     * AJAX: Track property interaction
     */
    public static function ajax_track_interaction() {
        check_ajax_referer('malisafi_nonce', 'nonce');
        
        global $wpdb;
        
        $property_id = intval($_POST['property_id'] ?? 0);
        $interaction_type = sanitize_text_field($_POST['interaction_type'] ?? '');
        $interaction_data = $_POST['data'] ?? [];
        
        if (!$property_id || !$interaction_type) {
            wp_send_json_error(['message' => 'Missing required fields']);
        }
        
        $wpdb->insert(
            $wpdb->prefix . 'mf_property_interactions',
            [
                'property_id' => $property_id,
                'user_id' => get_current_user_id() ?: null,
                'interaction_type' => $interaction_type,
                'interaction_data' => json_encode($interaction_data),
                'session_id' => self::get_session_id()
            ]
        );
        
        wp_send_json_success();
    }

    /**
     * AJAX: Track view duration
     */
    public static function ajax_track_view_duration() {
        check_ajax_referer('malisafi_nonce', 'nonce');
        
        global $wpdb;
        
        $property_id = intval($_POST['property_id'] ?? 0);
        $duration = intval($_POST['duration'] ?? 0);
        $scroll_depth = intval($_POST['scroll_depth'] ?? 0);
        $gallery_viewed = !empty($_POST['gallery_viewed']);
        $map_viewed = !empty($_POST['map_viewed']);
        
        if (!$property_id) {
            wp_send_json_error(['message' => 'Missing property ID']);
        }
        
        // Update the most recent view
        $wpdb->update(
            $wpdb->prefix . 'mf_property_views',
            [
                'view_duration' => $duration,
                'scroll_depth' => $scroll_depth,
                'gallery_viewed' => $gallery_viewed,
                'map_viewed' => $map_viewed
            ],
            [
                'property_id' => $property_id,
                'session_id' => self::get_session_id()
            ],
            ['%d', '%d', '%d', '%d'],
            ['%d', '%s']
        );
        
        wp_send_json_success();
    }

    /**
     * Enqueue tracking scripts
     */
    public static function enqueue_tracking_scripts() {
        // Only on property pages and submission forms
        if (!is_singular('malisafi_property') && !is_page(['agent-add-property', 'owner-add-property', 'developer-add-property'])) {
            return;
        }
        
        wp_enqueue_script(
            'malisafi-analytics-tracking',
            MALISAFI_MLS_URL . 'assets/js/analytics-tracking.js',
            ['jquery'],
            MALISAFI_MLS_VERSION,
            true
        );
        
        wp_localize_script('malisafi-analytics-tracking', 'malisafiTracking', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('malisafi_nonce'),
            'propertyId' => is_singular('malisafi_property') ? get_the_ID() : 0,
            'sessionId' => self::get_session_id()
        ]);
    }
}
