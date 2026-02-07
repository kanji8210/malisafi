<?php
/**
 * Core plugin class
 *
 * @package MalisafiMLS
 */

namespace MalisafiMLS;

/**
 * Core class
 */
class Core {
    
    /**
     * Plugin loader
     *
     * @var Loader
     */
    protected $loader;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }
    
    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        require_once MALISAFI_MLS_PATH . 'includes/class-loader.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-i18n.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-database.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-user-creation-helper.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-post-types.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-property-manager.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-project-submission.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-role-manager.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-stripe.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-featured-properties.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-shortcodes.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-cache-manager.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-validator.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-property-filters-ajax.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-property-actions-ajax.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-agent-actions-ajax.php';
            require_once MALISAFI_MLS_PATH . 'includes/class-agent-profile-ajax.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-login-customizer.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-page-manager.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-registration-handler.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-property-approval-workflow.php';
        require_once MALISAFI_MLS_PATH . 'admin/class-admin.php';
        require_once MALISAFI_MLS_PATH . 'admin/class-admin-dashboard.php';
        require_once MALISAFI_MLS_PATH . 'admin/class-dashboard-widgets.php';
        require_once MALISAFI_MLS_PATH . 'admin/class-user-manager.php';
        require_once MALISAFI_MLS_PATH . 'admin/class-property-submit.php';
        require_once MALISAFI_MLS_PATH . 'admin/class-property-moderation.php';
        require_once MALISAFI_MLS_PATH . 'admin/class-agent-dashboard.php';
        require_once MALISAFI_MLS_PATH . 'admin/class-agent-management.php';
        require_once MALISAFI_MLS_PATH . 'public/class-public.php';
        
        // Analytics classes
        require_once MALISAFI_MLS_PATH . 'includes/analytics/class-analytics-database.php';
        require_once MALISAFI_MLS_PATH . 'includes/analytics/class-analytics-tracker.php';
        require_once MALISAFI_MLS_PATH . 'includes/analytics/class-analytics-core.php';
        require_once MALISAFI_MLS_PATH . 'includes/analytics/class-analytics-properties.php';
        require_once MALISAFI_MLS_PATH . 'includes/analytics/class-analytics-advanced.php';
        require_once MALISAFI_MLS_PATH . 'includes/analytics/class-analytics-admin.php';
        
        // Fraud reporting system
        require_once MALISAFI_MLS_PATH . 'includes/class-fraud-report-ajax.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-fraud-report-shortcode.php';
        require_once MALISAFI_MLS_PATH . 'admin/class-admin-fraud-reports.php';
        
        // Agent settings AJAX handler
        require_once MALISAFI_MLS_PATH . 'includes/ajax/class-agent-settings-ajax.php';
        
        $this->loader = new Loader();
        
        // Initialize role manager
        \Malisafi_Roles_Manager::init();
        
        // Initialize property approval workflow
        \Malisafi_Property_Approval_Workflow::init();
        
        // Initialize admin dashboard
        \Malisafi_Admin_Dashboard::init();
        
        // Initialize agent dashboard
        \Malisafi_Agent_Dashboard::init();
        
        // Initialize agent management
        \Malisafi_Agent_Management::init();
        
        // Initialize analytics tracker
        \MalisafiMLS\Analytics\Analytics_Tracker::init();
        
        // Initialize analytics admin
        \MalisafiMLS\Analytics\Analytics_Admin::init();
        
        // Initialize dashboard widgets
        \Malisafi_Dashboard_Widgets::init();
        
        // Initialize user manager
        \Malisafi_User_Manager::init();
        
        // Initialize property submission
        \Malisafi_Property_Submit::init();

        
        // Initialize property moderation
        \Malisafi_Property_Moderation::init();
        
        // Initialize Stripe integration
        \Malisafi_Stripe::init();
        
        // Initialize featured properties
        $featured_properties = new Featured_Properties();
        
        // Initialize login customizer
        Login_Customizer::init();
        
        // Initialize page manager
        Page_Manager::init();
        
        // Initialize property filters AJAX
        Property_Filters_Ajax::get_instance();
        
        // Initialize property actions AJAX (favorites, reports, contacts)
        Property_Actions_Ajax::get_instance();
        
        // Initialize agent actions AJAX (ratings, reports)
        Agent_Actions_Ajax::get_instance();
        
        // Initialize cache manager
        Cache_Manager::init();
        
        // Initialize property submission system
        require_once MALISAFI_MLS_PATH . 'includes/class-property-submission.php';
        Property_Submission::init();

        // Initialize project submission system
        Project_Submission::init();

        
        // Initialize property access control
        require_once MALISAFI_MLS_PATH . 'includes/class-property-access-control.php';
        Property_Access_Control::init();
        
        // Initialize redirects to custom editor for properties
        if (class_exists('MalisafiMLS\\Post_Types') && method_exists('MalisafiMLS\\Post_Types', 'init_redirects')) {
            \MalisafiMLS\Post_Types::init_redirects();
        }
        
        // Initialize property success page (admin)
        require_once MALISAFI_MLS_PATH . 'admin/class-property-success.php';
        Property_Success::init();
        
        // Ensure reference IDs exist for properties
        require_once MALISAFI_MLS_PATH . 'includes/class-reference-id.php';
        Reference_ID::init();
    }
    
    /**
     * Set plugin locale
     */
    private function set_locale() {
        $plugin_i18n = new I18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }
    
    /**
     * Register admin hooks
     */
    private function define_admin_hooks() {
        $admin = new \MalisafiMLS\Admin\Admin();
        
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $admin, 'add_plugin_admin_menu');
        $this->loader->add_action('admin_init', $admin, 'register_settings');
        
        // Post types and taxonomies
        $post_types = new Post_Types();
        $this->loader->add_action('init', $post_types, 'register_property_post_type');
        $this->loader->add_action('init', $post_types, 'register_taxonomies');
        $this->loader->add_action('add_meta_boxes', $post_types, 'add_property_meta_boxes');
        $this->loader->add_action('save_post', $post_types, 'save_property_meta', 10, 2);
        $this->loader->add_action('init', $post_types, 'register_project_post_type');
        $this->loader->add_action('add_meta_boxes', $post_types, 'add_project_meta_boxes');
        $this->loader->add_action('save_post', $post_types, 'save_project_meta', 10, 2);
        
        // Analytics tracker
        \MalisafiMLS\Analytics\Analytics_Tracker::init();
    }
    
    /**
     * Register public hooks
     */
    private function define_public_hooks() {
        $plugin_public = new \MalisafiMLS\PublicArea\PublicArea();
        
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

        // Keep trashed properties for one week
        $this->loader->add_action('wp_trash_post', $this, 'schedule_property_trash_cleanup');
        $this->loader->add_action('untrash_post', $this, 'unschedule_property_trash_cleanup');
        $this->loader->add_action('malisafi_delete_trashed_property', $this, 'delete_trashed_property');
        
        // Register shortcodes
        $this->loader->add_shortcode('malisafi_properties', $plugin_public, 'properties_shortcode');
        $this->loader->add_shortcode('malisafi_property_search', $plugin_public, 'search_shortcode');
        $this->loader->add_shortcode('malisafi_featured_properties', $plugin_public, 'featured_properties_shortcode');
    }

    /**
     * Schedule deletion for trashed properties after 7 days.
     */
    public function schedule_property_trash_cleanup($post_id) {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'malisafi_property') {
            return;
        }

        $timestamp = time() + (7 * DAY_IN_SECONDS);
        if (!wp_next_scheduled('malisafi_delete_trashed_property', array($post_id))) {
            wp_schedule_single_event($timestamp, 'malisafi_delete_trashed_property', array($post_id));
        }
    }

    /**
     * Clear scheduled deletion when a property is restored.
     */
    public function unschedule_property_trash_cleanup($post_id) {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'malisafi_property') {
            return;
        }

        $timestamp = wp_next_scheduled('malisafi_delete_trashed_property', array($post_id));
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'malisafi_delete_trashed_property', array($post_id));
        }
    }

    /**
     * Permanently delete trashed properties after retention window.
     */
    public function delete_trashed_property($post_id) {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'malisafi_property' || $post->post_status !== 'trash') {
            return;
        }

        wp_delete_post($post_id, true);
    }
    
    /**
     * Run the plugin
     */
    public function run() {
        $this->loader->run();
    }
}
