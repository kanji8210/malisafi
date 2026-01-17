<?php
/**
 * Analytics Admin Menu & Pages
 *
 * @package MalisafiMLS
 * @subpackage Analytics
 * @since 1.0.0
 */

namespace MalisafiMLS\Analytics;

if (!defined('ABSPATH')) {
    exit;
}

class Analytics_Admin {

    /**
     * Initialize analytics admin
     */
    public static function init() {
        // Load migration class
        require_once MALISAFI_MLS_PATH . 'includes/analytics/class-analytics-migration.php';
        Analytics_Migration::init();
        
        add_action('admin_menu', [__CLASS__, 'add_analytics_menu']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_analytics_scripts']);
    }

    /**
     * Add analytics menu
     */
    public static function add_analytics_menu() {
        add_menu_page(
            __('Malisafi Analytics', 'malisafi-mls'),
            __('Analytics', 'malisafi-mls'),
            'manage_options',
            'malisafi-analytics',
            [__CLASS__, 'render_overview_page'],
            'dashicons-chart-line',
            30
        );
        
        add_submenu_page(
            'malisafi-analytics',
            __('Overview', 'malisafi-mls'),
            __('Overview', 'malisafi-mls'),
            'manage_options',
            'malisafi-analytics',
            [__CLASS__, 'render_overview_page']
        );
        
        add_submenu_page(
            'malisafi-analytics',
            __('User Activity', 'malisafi-mls'),
            __('User Activity', 'malisafi-mls'),
            'manage_options',
            'malisafi-analytics-users',
            [__CLASS__, 'render_users_page']
        );
        
        add_submenu_page(
            'malisafi-analytics',
            __('Properties', 'malisafi-mls'),
            __('Properties', 'malisafi-mls'),
            'manage_options',
            'malisafi-analytics-properties',
            [__CLASS__, 'render_properties_page']
        );
        
        add_submenu_page(
            'malisafi-analytics',
            __('Searches', 'malisafi-mls'),
            __('Searches', 'malisafi-mls'),
            'manage_options',
            'malisafi-analytics-searches',
            [__CLASS__, 'render_searches_page']
        );
        
        add_submenu_page(
            'malisafi-analytics',
            __('Revenue', 'malisafi-mls'),
            __('Revenue', 'malisafi-mls'),
            'manage_options',
            'malisafi-analytics-revenue',
            [__CLASS__, 'render_revenue_page']
        );
        
        add_submenu_page(
            'malisafi-analytics',
            __('Fraud Detection', 'malisafi-mls'),
            __('Fraud Detection', 'malisafi-mls'),
            'manage_options',
            'malisafi-analytics-fraud',
            [__CLASS__, 'render_fraud_page']
        );
        
        add_submenu_page(
            'malisafi-analytics',
            __('System Health', 'malisafi-mls'),
            __('System Health', 'malisafi-mls'),
            'manage_options',
            'malisafi-analytics-health',
            [__CLASS__, 'render_health_page']
        );
    }

    /**
     * Enqueue analytics scripts
     */
    public static function enqueue_analytics_scripts($hook) {
        if (strpos($hook, 'malisafi-analytics') === false) {
            return;
        }
        
        wp_enqueue_style(
            'malisafi-analytics-css',
            MALISAFI_MLS_URL . 'assets/css/analytics.css',
            [],
            MALISAFI_MLS_VERSION
        );
        
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', [], '4.4.0', true);
        
        wp_enqueue_script(
            'malisafi-analytics-charts',
            MALISAFI_MLS_URL . 'assets/js/analytics-charts.js',
            ['chart-js', 'jquery'],
            MALISAFI_MLS_VERSION,
            true
        );
        
        wp_localize_script('malisafi-analytics-charts', 'malisafiAnalytics', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('malisafi_analytics_nonce')
        ]);
    }

    /**
     * Render overview page
     */
    public static function render_overview_page() {
        require_once MALISAFI_MLS_PATH . 'admin/analytics/overview.php';
    }

    /**
     * Render users page
     */
    public static function render_users_page() {
        require_once MALISAFI_MLS_PATH . 'admin/analytics/user-activity.php';
    }

    /**
     * Render properties page
     */
    public static function render_properties_page() {
        require_once MALISAFI_MLS_PATH . 'admin/analytics/properties.php';
    }

    /**
     * Render searches page
     */
    public static function render_searches_page() {
        require_once MALISAFI_MLS_PATH . 'admin/analytics/searches.php';
    }

    /**
     * Render revenue page
     */
    public static function render_revenue_page() {
        require_once MALISAFI_MLS_PATH . 'admin/analytics/revenue.php';
    }

    /**
     * Render fraud page
     */
    public static function render_fraud_page() {
        require_once MALISAFI_MLS_PATH . 'admin/analytics/fraud-detection.php';
    }

    /**
     * Render health page
     */
    public static function render_health_page() {
        require_once MALISAFI_MLS_PATH . 'admin/analytics/system-health.php';
    }
}
