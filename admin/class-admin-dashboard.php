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
            'edit_posts', // allow agents to access custom form pages
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
        
        // Inquiries Management
        add_submenu_page(
            'malisafi-dashboard',
            __('Property Inquiries', 'malisafi-mls'),
            __('Inquiries', 'malisafi-mls'),
            'manage_malisafi_settings',
            'malisafi-inquiries',
            array(__CLASS__, 'render_inquiries')
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
        
        // Unified Plans & Subscriptions Management
        add_submenu_page(
            'malisafi-dashboard',
            __('Plans & Subscriptions', 'malisafi-mls'),
            __('Plans & Subscriptions', 'malisafi-mls'),
            'manage_malisafi_settings',
            'malisafi-plans-subscriptions',
            array(__CLASS__, 'render_unified_subscriptions')
        );
        
        // Advanced Subscription Manager
        add_submenu_page(
            'malisafi-dashboard',
            __('Subscription Manager', 'malisafi-mls'),
            __('Subscription Manager', 'malisafi-mls'),
            'manage_malisafi_settings',
            'malisafi-subscription-manager',
            array(__CLASS__, 'render_subscription_manager')
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
        // Capability check
        if (!current_user_can('manage_malisafi_settings')) {
            wp_die(__('You do not have permission to access this page.', 'malisafi-mls'));
        }
        
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
        // Capability check
        if (!current_user_can('moderate_properties')) {
            wp_die(__('You do not have permission to moderate properties.', 'malisafi-mls'));
        }
        
        $template = MALISAFI_MLS_PATH . 'admin/templates/moderation-queue.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<div class="wrap"><h1>' . __('Moderation Queue', 'malisafi-mls') . '</h1>';
            echo '<p>' . __('Moderation queue template coming soon.', 'malisafi-mls') . '</p></div>';
        }
    }
    
    /**
     * Render inquiries management
     */
    public static function render_inquiries() {
        // Capability check
        if (!current_user_can('manage_malisafi_settings') && !current_user_can('manage_options')) {
            wp_die(__('You do not have permission to manage inquiries.', 'malisafi-mls'));
        }
        
        // Use the existing inquiries admin class
        if (class_exists('Malisafi_Inquiries_Admin')) {
            \Malisafi_Inquiries_Admin::render_page();
        } else {
            echo '<div class="wrap"><h1>' . __('Property Inquiries', 'malisafi-mls') . '</h1>';
            echo '<p>' . __('Inquiries management is not available.', 'malisafi-mls') . '</p></div>';
        }
    }
    
    /**
     * Render users management
     */
    public static function render_users_management() {
        // Capability check
        if (!current_user_can('manage_malisafi_settings')) {
            wp_die(__('You do not have permission to manage users.', 'malisafi-mls'));
        }
        
        $template = MALISAFI_MLS_PATH . 'admin/templates/users-management.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<div class="wrap"><h1>' . __('User Management', 'malisafi-mls') . '</h1>';
            echo '<p>' . __('User management template coming soon.', 'malisafi-mls') . '</p></div>';
        }
    }


    /**
     * Render unified plans & subscriptions management page
     */
    public static function render_unified_subscriptions() {
        // Capability check
        if (!current_user_can('manage_malisafi_settings')) {
            wp_die(__('You do not have permission to manage plans and subscriptions.', 'malisafi-mls'));
        }
        
        $template = MALISAFI_MLS_PATH . 'admin/templates/unified-subscriptions.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<div class="wrap"><h1>' . __('Plans & Subscriptions', 'malisafi-mls') . '</h1>';
            echo '<p>' . __('Unified management template not found.', 'malisafi-mls') . '</p></div>';
        }
    }

    /**
     * Render advanced subscription manager
     */
    public static function render_subscription_manager() {
        // Capability check
        if (!current_user_can('manage_malisafi_settings')) {
            wp_die(__('You do not have permission to manage subscriptions.', 'malisafi-mls'));
        }
        
        $template = MALISAFI_MLS_PATH . 'admin/templates/subscription-manager.php';
        if (file_exists($template)) {
            include $template;
        } else {
            echo '<div class="wrap"><h1>' . __('Advanced Subscription Manager', 'malisafi-mls') . '</h1>';
            echo '<p>' . __('Subscription manager template not found.', 'malisafi-mls') . '</p></div>';
        }
    }


    
    /**
     * Render analytics
     */
    public static function render_analytics() {
        // Capability check
        if (!current_user_can('manage_malisafi_settings')) {
            wp_die(__('You do not have permission to access analytics.', 'malisafi-mls'));
        }
        
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
        // Capability check
        if (!current_user_can('manage_malisafi_settings')) {
            wp_die(__('You do not have permission to access developer projects.', 'malisafi-mls'));
        }
        
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
        // Capability check
        if (!current_user_can('manage_malisafi_settings')) {
            wp_die(__('You do not have permission to manage settings.', 'malisafi-mls'));
        }
        
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
        // Capability check
        if (!current_user_can('manage_malisafi_settings')) {
            wp_die(__('You do not have permission to manage pages.', 'malisafi-mls'));
        }
        
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
        // Capability check
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access database tools.', 'malisafi-mls'));
        }
        
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
        
        // Enqueue global variables
        wp_enqueue_style(
            'malisafi-mls-variables',
            MALISAFI_MLS_URL . 'assets/css/variables.css',
            array(),
            MALISAFI_MLS_VERSION
        );

        // Enqueue styles
        wp_enqueue_style(
            'malisafi-admin',
            MALISAFI_MLS_URL . 'assets/css/admin.css',
            array('malisafi-mls-variables'),
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
        
        // Localize script for AJAX and strings
        wp_localize_script('malisafi-admin', 'malisafi_admin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'ajax_url' => admin_url('admin-ajax.php'), // Legacy support
            'nonce' => wp_create_nonce('malisafi_admin_nonce'),
            'submissionNonce' => wp_create_nonce('malisafi_property_submission'),
            'uploadNonce' => wp_create_nonce('malisafi_upload_images'),
            'refNonce' => wp_create_nonce('malisafi_generate_ref_id'),
            'fieldRegistry' => \MalisafiMLS\Property_Submission::get_field_registry(),
            'strings' => array(
                'saving' => __('Saving...', 'malisafi-mls'),
                'saved' => __('Saved', 'malisafi-mls'),
                'error' => __('Error saving/uploading', 'malisafi-mls'),
                'uploading' => __('Uploading...', 'malisafi-mls'),
                'uploadError' => __('Upload failed', 'malisafi-mls'),
                'confirmDelete' => __('Are you sure you want to delete this image?', 'malisafi-mls'),
                'submitProperty' => __('Submit Property', 'malisafi-mls'),
                'submitting' => __('Submitting...', 'malisafi-mls'),
                'success' => __('Property submitted successfully!', 'malisafi-mls'),
                'confirm_delete' => __('Are you sure you want to delete this item?', 'malisafi-mls'),
                'media_select_title' => __('Select Property Images', 'malisafi-mls'),
                'media_select_button' => __('Use Images', 'malisafi-mls'),
            )
        ));
    }
}
