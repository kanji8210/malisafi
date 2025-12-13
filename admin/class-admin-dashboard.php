<?php
/**
 * Admin Dashboard Management
 *
 * @package MalisafiMLS
 */

/**
 * Malisafi_Admin_Dashboard class
 */
class Malisafi_Admin_Dashboard {
    
    /**
     * Initialize the dashboard
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menus'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add admin menus
     */
    public static function add_admin_menus() {
        // Main Dashboard - Only for Malisafi Admins & Moderators
        add_menu_page(
            __('Malisafi Dashboard', 'malisafi-mls'),
            __('Malisafi', 'malisafi-mls'),
            'manage_malisafi_settings',
            'malisafi-dashboard',
            array(__CLASS__, 'render_dashboard'),
            'dashicons-building',
            25
        );
        
        // Dashboard Submenu (same as main)
        add_submenu_page(
            'malisafi-dashboard',
            __('Dashboard Overview', 'malisafi-mls'),
            __('Dashboard', 'malisafi-mls'),
            'manage_malisafi_settings',
            'malisafi-dashboard',
            array(__CLASS__, 'render_dashboard')
        );
        
        // Properties Management
        add_submenu_page(
            'malisafi-dashboard',
            __('All Properties', 'malisafi-mls'),
            __('Properties', 'malisafi-mls'),
            'moderate_properties',
            'malisafi-properties',
            array(__CLASS__, 'render_properties_list')
        );
        
        // Moderation Queue
        add_submenu_page(
            'malisafi-dashboard',
            __('Moderation Queue', 'malisafi-mls'),
            __('Moderation', 'malisafi-mls'),
            'moderate_properties',
            'malisafi-moderation',
            array(__CLASS__, 'render_moderation_queue')
        );
        
        // User Management
        add_submenu_page(
            'malisafi-dashboard',
            __('User Management', 'malisafi-mls'),
            __('Users', 'malisafi-mls'),
            'manage_malisafi_settings',
            'malisafi-users',
            array(__CLASS__, 'render_users_management')
        );
        
        // Subscriptions & Billing
        add_submenu_page(
            'malisafi-dashboard',
            __('Subscriptions', 'malisafi-mls'),
            __('Subscriptions', 'malisafi-mls'),
            'manage_malisafi_settings',
            'malisafi-subscriptions',
            array(__CLASS__, 'render_subscriptions')
        );

        // Plans management (CRUD for subscription plans)
        add_submenu_page(
            'malisafi-dashboard',
            __('Plans', 'malisafi-mls'),
            __('Plans', 'malisafi-mls'),
            'manage_malisafi_settings',
            'malisafi-plans',
            array(__CLASS__, 'render_plans')
        );
        
        // Property Types & Statuses Management
        add_submenu_page(
            'malisafi-dashboard',
            __('Property Types', 'malisafi-mls'),
            __('Property Types', 'malisafi-mls'),
            'manage_malisafi_settings',
            'edit-tags.php?taxonomy=malisafi_property_type&post_type=malisafi_property'
        );
        
        add_submenu_page(
            'malisafi-dashboard',
            __('Listing Status', 'malisafi-mls'),
            __('Listing Status', 'malisafi-mls'),
            'manage_malisafi_settings',
            'edit-tags.php?taxonomy=malisafi_property_status&post_type=malisafi_property'
        );
        
        // Analytics & Reports
        add_submenu_page(
            'malisafi-dashboard',
            __('Analytics & Reports', 'malisafi-mls'),
            __('Analytics', 'malisafi-mls'),
            'manage_malisafi_settings',
            'malisafi-analytics',
            array(__CLASS__, 'render_analytics')
        );
        
        // Developer Projects
        add_submenu_page(
            'malisafi-dashboard',
            __('Developer Projects', 'malisafi-mls'),
            __('Developers', 'malisafi-mls'),
            'manage_malisafi_settings',
            'malisafi-developers',
            array(__CLASS__, 'render_developers')
        );
        
        // Settings
        add_submenu_page(
            'malisafi-dashboard',
            __('Platform Settings', 'malisafi-mls'),
            __('Settings', 'malisafi-mls'),
            'manage_malisafi_settings',
            'malisafi-settings',
            array(__CLASS__, 'render_settings')
        );
        
        // Pages Management
        add_submenu_page(
            'malisafi-dashboard',
            __('Pages Management', 'malisafi-mls'),
            __('Pages', 'malisafi-mls'),
            'manage_malisafi_settings',
            'malisafi-pages',
            array(__CLASS__, 'render_pages_management')
        );
        
        // Database Tools (Admin only)
        add_submenu_page(
            'malisafi-dashboard',
            __('Database Tools', 'malisafi-mls'),
            __('Database Tools', 'malisafi-mls'),
            'manage_options',
            'malisafi-database-tools',
            array(__CLASS__, 'render_database_tools')
        );
        
        // Remove the default submenu duplicate
        remove_submenu_page('malisafi-dashboard', 'malisafi-dashboard');
    }
    
    /**
     * Render main dashboard
     */
    public static function render_dashboard() {
        $template = MALISAFI_MLS_PATH . 'admin/templates/dashboard-main.php';
        if (file_exists($template)) {
            include $template;
        } else {
            // Fallback to existing dashboard
            include MALISAFI_MLS_PATH . 'admin/partials/dashboard-display.php';
        }
    }
    
    /**
     * Render properties list
     */
    public static function render_properties_list() {
        $template = MALISAFI_MLS_PATH . 'admin/templates/properties-list.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<div class="wrap"><h1>' . __('Properties Management', 'malisafi-mls') . '</h1>';
            echo '<p>' . __('Properties list template coming soon.', 'malisafi-mls') . '</p></div>';
        }
    }
    
    /**
     * Render moderation queue
     */
    public static function render_moderation_queue() {
        $template = MALISAFI_MLS_PATH . 'admin/templates/moderation-queue.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<div class="wrap"><h1>' . __('Moderation Queue', 'malisafi-mls') . '</h1>';
            echo '<p>' . __('Moderation queue template coming soon.', 'malisafi-mls') . '</p></div>';
        }
    }
    
    /**
     * Render users management
     */
    public static function render_users_management() {
        $template = MALISAFI_MLS_PATH . 'admin/templates/users-management.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<div class="wrap"><h1>' . __('User Management', 'malisafi-mls') . '</h1>';
            echo '<p>' . __('User management template coming soon.', 'malisafi-mls') . '</p></div>';
        }
    }
    
    /**
     * Render subscriptions
     */
    public static function render_subscriptions() {
        $template = MALISAFI_MLS_PATH . 'admin/templates/subscriptions.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<div class="wrap"><h1>' . __('Subscriptions & Billing', 'malisafi-mls') . '</h1>';
            echo '<p>' . __('Subscriptions template coming soon.', 'malisafi-mls') . '</p></div>';
        }
    }

    /**
     * Render plans management page and handle saves.
     */
    public static function render_plans() {
        // Capability check
        if (!current_user_can('manage_malisafi_settings')) {
            wp_die(__('You do not have permission to manage plans.', 'malisafi-mls'));
        }

        // Handle POST save
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['malisafi_plans_action'])) {
            if (!isset($_POST['_malisafi_plans_nonce']) || !wp_verify_nonce($_POST['_malisafi_plans_nonce'], 'malisafi_plans_save')) {
                wp_die(__('Invalid request.', 'malisafi-mls'));
            }

            $action = sanitize_text_field($_POST['malisafi_plans_action']);
            $plans = get_option('malisafi_mls_plans', array());
            if (!is_array($plans)) {
                $plans = array();
            }

            if ($action === 'save_all' && isset($_POST['plans']) && is_array($_POST['plans'])) {
                $new = array();
                foreach ($_POST['plans'] as $key => $p) {
                    $k = sanitize_key($key);
                    $name = sanitize_text_field($p['name']);
                    $price = floatval($p['price']);
                    $currency = sanitize_text_field($p['currency']);
                    $interval = sanitize_text_field($p['interval']);
                    $stripe = sanitize_text_field($p['stripe_price_id']);
                    $features = array();
                    if (!empty($p['features'])) {
                        if (is_array($p['features'])) {
                            foreach ($p['features'] as $f) {
                                $features[] = sanitize_text_field($f);
                            }
                        } else {
                            // Accept comma-separated string as a convenience from the UI
                            $tmp = explode(',', (string) $p['features']);
                            foreach ($tmp as $f) {
                                $f = trim($f);
                                if ($f !== '') {
                                    $features[] = sanitize_text_field($f);
                                }
                            }
                        }
                    }

                    $new[$k] = array(
                        'name' => $name,
                        'price' => $price,
                        'currency' => $currency,
                        'interval' => $interval,
                        'features' => $features,
                        'stripe_price_id' => $stripe,
                    );
                }

                update_option('malisafi_mls_plans', $new);
                $plans = $new;
                add_settings_error('malisafi-plans', 'saved', __('Plans saved.', 'malisafi-mls'), 'updated');
            }

            if ($action === 'reset_defaults') {
                delete_option('malisafi_mls_plans');
                add_settings_error('malisafi-plans', 'reset', __('Plans reset to defaults.', 'malisafi-mls'), 'updated');
                $plans = array();
            }
        } else {
            $plans = get_option('malisafi_mls_plans', array());
        }

        // Allow admin notices to be displayed
        settings_errors('malisafi-plans');

        $template = MALISAFI_MLS_PATH . 'admin/templates/plans.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<div class="wrap"><h1>' . __('Plans Management', 'malisafi-mls') . '</h1>';
            echo '<p>' . __('Plans management template missing.', 'malisafi-mls') . '</p></div>';
        }
    }
    
    /**
     * Render analytics
     */
    public static function render_analytics() {
        $template = MALISAFI_MLS_PATH . 'admin/templates/analytics.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<div class="wrap"><h1>' . __('Analytics & Reports', 'malisafi-mls') . '</h1>';
            echo '<p>' . __('Analytics template coming soon.', 'malisafi-mls') . '</p></div>';
        }
    }
    
    /**
     * Render developers page
     */
    public static function render_developers() {
        $template = MALISAFI_MLS_PATH . 'admin/templates/developers.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<div class="wrap"><h1>' . __('Developer Projects', 'malisafi-mls') . '</h1>';
            echo '<p>' . __('Developer projects template coming soon.', 'malisafi-mls') . '</p></div>';
        }
    }
    
    /**
     * Render settings page
     */
    public static function render_settings() {
        $template = MALISAFI_MLS_PATH . 'admin/templates/settings.php';
        if (file_exists($template)) {
            include $template;
        } else {
            // Fallback to existing settings
            include MALISAFI_MLS_PATH . 'admin/partials/settings-display.php';
        }
    }
    
    /**
     * Render pages management page
     */
    public static function render_pages_management() {
        $template = MALISAFI_MLS_PATH . 'admin/templates/pages-management.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<div class="wrap"><h1>' . __('Pages Management', 'malisafi-mls') . '</h1>';
            echo '<p>' . __('Pages management template not found.', 'malisafi-mls') . '</p></div>';
        }
    }
    
    /**
     * Render database tools page
     */
    public static function render_database_tools() {
        $template = MALISAFI_MLS_PATH . 'admin/templates/database-tools.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<div class="wrap"><h1>' . __('Database Tools', 'malisafi-mls') . '</h1>';
            echo '<p>' . __('Database tools template not found.', 'malisafi-mls') . '</p></div>';
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public static function enqueue_admin_scripts($hook) {
        // Only load on Malisafi pages
        if (strpos($hook, 'malisafi') === false) {
            return;
        }
        
        // Enqueue WordPress media library for property pages
        if (strpos($hook, 'malisafi-properties') !== false) {
            wp_enqueue_media();
        }
        
        // Enqueue styles
        wp_enqueue_style(
            'malisafi-admin',
            MALISAFI_MLS_URL . 'assets/css/admin.css',
            array(),
            MALISAFI_MLS_VERSION
        );
        
        // Enqueue scripts
        wp_enqueue_script(
            'malisafi-admin',
            MALISAFI_MLS_URL . 'assets/js/admin.js',
            array('jquery', 'wp-util'),
            MALISAFI_MLS_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('malisafi-admin', 'malisafi_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('malisafi_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'malisafi-mls'),
                'error' => __('An error occurred. Please try again.', 'malisafi-mls'),
                'success' => __('Operation completed successfully.', 'malisafi-mls'),
            )
        ));
    }
}
