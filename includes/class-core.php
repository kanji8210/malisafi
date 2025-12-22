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
        require_once MALISAFI_MLS_PATH . 'includes/class-post-types.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-property-manager.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-role-manager.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-stripe.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-featured-properties.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-shortcodes.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-property-filters-ajax.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-property-actions-ajax.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-agent-actions-ajax.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-login-customizer.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-page-manager.php';
        require_once MALISAFI_MLS_PATH . 'includes/class-registration-handler.php';
        require_once MALISAFI_MLS_PATH . 'admin/class-admin.php';
        require_once MALISAFI_MLS_PATH . 'admin/class-admin-dashboard.php';
        require_once MALISAFI_MLS_PATH . 'admin/class-dashboard-widgets.php';
        require_once MALISAFI_MLS_PATH . 'admin/class-user-manager.php';
        require_once MALISAFI_MLS_PATH . 'admin/class-property-submit.php';
        require_once MALISAFI_MLS_PATH . 'admin/class-property-moderation.php';
        require_once MALISAFI_MLS_PATH . 'admin/class-agent-dashboard.php';
        require_once MALISAFI_MLS_PATH . 'public/class-public.php';
        
        $this->loader = new Loader();
        
        // Initialize role manager
        \Malisafi_Roles_Manager::init();
        
        // Initialize admin dashboard
        \Malisafi_Admin_Dashboard::init();
        
        // Initialize agent dashboard
        \Malisafi_Agent_Dashboard::init();
        
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
    }
    
    /**
     * Register public hooks
     */
    private function define_public_hooks() {
        $plugin_public = new \MalisafiMLS\PublicArea\PublicArea();
        
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // Register shortcodes
        $this->loader->add_shortcode('malisafi_properties', $plugin_public, 'properties_shortcode');
        $this->loader->add_shortcode('malisafi_property_search', $plugin_public, 'search_shortcode');
        $this->loader->add_shortcode('malisafi_featured_properties', $plugin_public, 'featured_properties_shortcode');
    }
    
    /**
     * Run the plugin
     */
    public function run() {
        $this->loader->run();
    }
}
