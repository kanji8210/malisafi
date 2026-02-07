<?php
/**
 * Page Manager - Handles automatic page creation and shortcode assignment
 *
 * @package MalisafiMLS
 */

namespace MalisafiMLS;

/**
 * Page_Manager class
 */
class Page_Manager {
    
    /**
     * Required pages configuration
     */
    private static $required_pages = array(
        // Public Pages
        'properties' => array(
            'title' => 'Properties',
            'slug' => 'properties',
            'shortcode' => '[malisafi_properties]',
            'description' => 'Main properties listing page',
            'parent' => 0
        ),
        'advanced_filters' => array(
            'title' => 'Advanced Filters',
            'slug' => 'advanced_filters',
            'shortcode' => '[malisafi_properties]',
            'description' => 'Advanced filters and search results page',
            'parent' => 0
        ),
        'property_search' => array(
            'title' => 'Property Search',
            'slug' => 'property-search',
            'shortcode' => '[malisafi_property_search]',
            'description' => 'Advanced property search page',
            'parent' => 0
        ),
        'featured_properties' => array(
            'title' => 'Featured Properties',
            'slug' => 'featured-properties',
            'shortcode' => '[malisafi_featured_properties]',
            'description' => 'Featured properties showcase',
            'parent' => 0
        ),
        'submit_property' => array(
            'title' => 'Submit Property',
            'slug' => 'submit-property',
            'shortcode' => '[malisafi_submit_property]',
            'description' => 'Front-end property submission wizard',
            'parent' => 0
        ),
        'property_submitted' => array(
            'title' => 'Property Submitted',
            'slug' => 'property-submitted',
            'shortcode' => '[malisafi_property_success]',
            'description' => 'Submission success/confirmation page',
            'parent' => 0
        ),
        'my_properties' => array(
            'title' => 'My Properties',
            'slug' => 'my-properties',
            'shortcode' => '[malisafi_agent_properties]',
            'description' => 'User/agent properties management page',
            'parent' => 0
        ),
        'agents' => array(
            'title' => 'Our Agents',
            'slug' => 'agents',
            'shortcode' => '[malisafi_agents]',
            'description' => 'All agents listing',
            'parent' => 0
        ),
        'pricing' => array(
            'title' => 'Pricing & Plans',
            'slug' => 'pricing',
            'shortcode' => '[malisafi_pricing]',
            'description' => 'Subscription plans page',
            'parent' => 0
        ),
        
        // Client Dashboard Pages
        'client_dashboard' => array(
            'title' => 'My Dashboard',
            'slug' => 'client-dashboard',
            'shortcode' => '[malisafi_client_dashboard]',
            'description' => 'Client dashboard homepage',
            'parent' => 0
        ),
        'client_favorites' => array(
            'title' => 'My Favorites',
            'slug' => 'my-favorites',
            'shortcode' => '[malisafi_favorites]',
            'description' => 'Saved properties',
            'parent' => 'client_dashboard'
        ),
        'client_searches' => array(
            'title' => 'Saved Searches',
            'slug' => 'saved-searches',
            'shortcode' => '[malisafi_saved_searches]',
            'description' => 'User saved searches',
            'parent' => 'client_dashboard'
        ),
        'client_inquiries' => array(
            'title' => 'My Inquiries',
            'slug' => 'my-inquiries',
            'shortcode' => '[malisafi_client_inquiries]',
            'description' => 'Property inquiries history',
            'parent' => 'client_dashboard'
        ),
        
        // Agent Dashboard Pages
        'agent_dashboard' => array(
            'title' => 'Agent Dashboard',
            'slug' => 'agent-dashboard',
            'shortcode' => '[malisafi_agent_dashboard]',
            'description' => 'Agent dashboard homepage',
            'parent' => 0
        ),
        'agent_properties' => array(
            'title' => 'My Properties',
            'slug' => 'agent-properties',
            'shortcode' => '[malisafi_agent_properties]',
            'description' => 'Agent properties list',
            'parent' => 'agent_dashboard'
        ),
        'agent_add_property' => array(
            'title' => 'Add Property',
            'slug' => 'add-property',
            'shortcode' => '[malisafi_agent_add_property]',
            'description' => 'Property submission form (redirects to backend)',
            'parent' => 'agent_dashboard'
        ),
        'agent_leads' => array(
            'title' => 'My Leads',
            'slug' => 'agent-leads',
            'shortcode' => '[malisafi_agent_leads]',
            'description' => 'Agent leads management',
            'parent' => 'agent_dashboard'
        ),
        'agent_profile' => array(
            'title' => 'Agent Profile',
            'slug' => 'agent-profile',
            'shortcode' => '[malisafi_agent_profile_view]',
            'description' => 'Public agent profile viewer',
            'parent' => 0
        ),
        
        // Owner Dashboard Pages
        'owner_dashboard' => array(
            'title' => 'Owner Dashboard',
            'slug' => 'owner-dashboard',
            'shortcode' => '[malisafi_owner_dashboard]',
            'description' => 'Property owner dashboard',
            'parent' => 0
        ),
        'owner_properties' => array(
            'title' => 'My Properties',
            'slug' => 'owner-properties',
            'shortcode' => '[malisafi_owner_properties]',
            'description' => 'Owner properties list',
            'parent' => 'owner_dashboard'
        ),
        'owner_add_property' => array(
            'title' => 'List Property',
            'slug' => 'list-property',
            'shortcode' => '[malisafi_property_submit role="owner"]',
            'description' => 'Owner property listing form',
            'parent' => 'owner_dashboard'
        ),
        'owner_inquiries' => array(
            'title' => 'Inquiries',
            'slug' => 'owner-inquiries',
            'shortcode' => '[malisafi_owner_inquiries]',
            'description' => 'Inquiries for owner properties',
            'parent' => 'owner_dashboard'
        ),
        
        // Agency Dashboard Pages
        'agency_dashboard' => array(
            'title' => 'Agency Dashboard',
            'slug' => 'agency-dashboard',
            'shortcode' => '[malisafi_agency_dashboard]',
            'description' => 'Agency dashboard homepage',
            'parent' => 0
        ),
        'agency_agents' => array(
            'title' => 'My Agents',
            'slug' => 'agency-agents',
            'shortcode' => '[malisafi_agency_agents]',
            'description' => 'Agency agents management',
            'parent' => 'agency_dashboard'
        ),
        'agency_inquiries' => array(
            'title' => 'Agent Inquiries',
            'slug' => 'agency-inquiries',
            'shortcode' => '[malisafi_agency_inquiries]',
            'description' => 'Inquiries received by agency agents',
            'parent' => 'agency_dashboard'
        ),
        
        // Developer Dashboard Pages
        'developer_dashboard' => array(
            'title' => 'Developer Dashboard',
            'slug' => 'developer-dashboard',
            'shortcode' => '[malisafi_developer_dashboard]',
            'description' => 'Developer project dashboard',
            'parent' => 0
        ),
        'developer_projects' => array(
            'title' => 'My Projects',
            'slug' => 'projects',
            'shortcode' => '[malisafi_developer_projects]',
            'description' => 'Developer projects list',
            'parent' => 'developer_dashboard'
        ),
        'developer_add_project' => array(
            'title' => 'Add Project',
            'slug' => 'add-project',
            'shortcode' => '[malisafi_project_submit]',
            'description' => 'Create a new development project',
            'parent' => 'developer_dashboard'
        ),
        'developer_analytics' => array(
            'title' => 'Analytics',
            'slug' => 'developer-analytics',
            'shortcode' => '[malisafi_developer_analytics]',
            'description' => 'Project analytics and reports',
            'parent' => 'developer_dashboard'
        ),
        
        // Account Pages
        'login' => array(
            'title' => 'Login',
            'slug' => 'login',
            'shortcode' => '[malisafi_login]',
            'description' => 'User login page',
            'parent' => 0
        ),
        'register' => array(
            'title' => 'Register',
            'slug' => 'register',
            'shortcode' => '[malisafi_registration]',
            'description' => 'User registration page',
            'parent' => 0
        ),
        'register_client' => array(
            'title' => 'Register - Client',
            'slug' => 'register-client',
            'shortcode' => '[malisafi_register_client]',
            'description' => 'Client registration page',
            'parent' => 0
        ),
        'register_agent' => array(
            'title' => 'Register - Agent',
            'slug' => 'register-agent',
            'shortcode' => '[malisafi_register_agent]',
            'description' => 'Agent registration page',
            'parent' => 0
        ),
        'register_owner' => array(
            'title' => 'Register - Owner',
            'slug' => 'register-owner',
            'shortcode' => '[malisafi_register_owner]',
            'description' => 'Owner registration page',
            'parent' => 0
        ),
        'register_developer' => array(
            'title' => 'Register - Developer',
            'slug' => 'register-developer',
            'shortcode' => '[malisafi_register_developer]',
            'description' => 'Developer registration page',
            'parent' => 0
        ),
        'account' => array(
            'title' => 'My Account',
            'slug' => 'my-account',
            'shortcode' => '[malisafi_account]',
            'description' => 'User account settings',
            'parent' => 0
        )
    );
    
    /**
     * Initialize the page manager
     */
    public static function init() {
        add_action('admin_init', array(__CLASS__, 'check_pages_status'));
        add_action('admin_init', array(__CLASS__, 'migrate_pages'));
        add_action('admin_notices', array(__CLASS__, 'pages_status_notice'));
    }
    
    /**
     * Migrate pages to use correct shortcodes
     * This runs once to fix any pages with old shortcodes
     */
    public static function migrate_pages() {
        // Check if migration has already run
        $migration_version = get_option('malisafi_pages_migration_version', '0');
        $current_migration = '1.2'; // Increment this when adding new migrations
        
        if (version_compare($migration_version, $current_migration, '>=')) {
            return; // Already migrated
        }
        
        // Migration 1.0: Fix agent-profile page shortcode
        $agent_profile_id = get_option('malisafi_page_agent_profile');
        if ($agent_profile_id && ($page = get_post($agent_profile_id))) {
            // Check if page has old shortcode
            if ($page->post_content === '[malisafi_agent_profile]') {
                wp_update_post(array(
                    'ID' => $agent_profile_id,
                    'post_content' => '[malisafi_agent_profile_view]',
                    'post_title' => 'Agent Profile' // Update title too
                ));
            }
        }

        // Migration 1.1: Remove deprecated developer add-project pages
        $deprecated_keys = array(
            'developer_add_project',
            'developer_projects'
        );

        foreach ($deprecated_keys as $deprecated_key) {
            $deprecated_id = get_option('malisafi_page_' . $deprecated_key);
            if ($deprecated_id) {
                wp_delete_post($deprecated_id, true);
                delete_option('malisafi_page_' . $deprecated_key);
            }
        }

        $deprecated_paths = array(
            'developer-dashboard/add-project',
            'add-project',
            'developer-dashboard/projects',
            'projects'
        );

        foreach ($deprecated_paths as $deprecated_path) {
            $deprecated_page = get_page_by_path($deprecated_path);
            if ($deprecated_page && $deprecated_page->post_type === 'page') {
                wp_delete_post($deprecated_page->ID, true);
            }
        }

        // Migration 1.2: (Re)create developer project pages with new shortcodes
        $project_keys = array('developer_projects', 'developer_add_project');
        foreach ($project_keys as $project_key) {
            if (!isset(self::$required_pages[$project_key])) {
                continue;
            }

            $page_config = self::$required_pages[$project_key];
            $existing_id = get_option('malisafi_page_' . $project_key);

            if ($existing_id && ($page = get_post($existing_id))) {
                if ($page->post_content !== $page_config['shortcode']) {
                    wp_update_post(array(
                        'ID' => $existing_id,
                        'post_content' => $page_config['shortcode']
                    ));
                }
                continue;
            }

            if ($page_config['parent'] !== 0) {
                $parent_id = get_option('malisafi_page_' . $page_config['parent']);
                $page_config['parent_id'] = $parent_id ? $parent_id : 0;
            }

            $new_page_id = self::create_page($project_key, $page_config);
            if ($new_page_id) {
                update_option('malisafi_page_' . $project_key, $new_page_id);
            }
        }
        
        // Mark migration as complete
        update_option('malisafi_pages_migration_version', $current_migration);
    }
    
    /**
     * Get all required pages
     */
    public static function get_required_pages() {
        return self::$required_pages;
    }
    
    /**
     * Create all required pages
     */
    public static function create_all_pages() {
        $created_pages = array();
        $parent_pages = array();
        
        // First pass: Create parent pages
        foreach (self::$required_pages as $key => $page) {
            if ($page['parent'] === 0) {
                $page_id = self::create_page($key, $page);
                if ($page_id) {
                    $parent_pages[$key] = $page_id;
                    $created_pages[$key] = $page_id;
                    update_option('malisafi_page_' . $key, $page_id);
                }
            }
        }
        
        // Second pass: Create child pages
        foreach (self::$required_pages as $key => $page) {
            if ($page['parent'] !== 0) {
                $parent_id = isset($parent_pages[$page['parent']]) ? $parent_pages[$page['parent']] : 0;
                $page['parent_id'] = $parent_id;
                
                $page_id = self::create_page($key, $page);
                if ($page_id) {
                    $created_pages[$key] = $page_id;
                    update_option('malisafi_page_' . $key, $page_id);
                }
            }
        }
        
        // Save creation timestamp
        update_option('malisafi_pages_created', current_time('timestamp'));
        
        return $created_pages;
    }
    
    /**
     * Create a single page
     */
    private static function create_page($key, $page_config) {
        // Check if page already exists
        $existing_id = get_option('malisafi_page_' . $key);
        if ($existing_id && ($page = get_post($existing_id))) {
            // Page exists, check if shortcode needs updating
            if ($page->post_content !== $page_config['shortcode']) {
                wp_update_post(array(
                    'ID' => $existing_id,
                    'post_content' => $page_config['shortcode']
                ));
            }
            return $existing_id;
        }
        
        $parent_id = isset($page_config['parent_id']) ? $page_config['parent_id'] : 0;
        
        $page_data = array(
            'post_title' => $page_config['title'],
            'post_name' => $page_config['slug'],
            'post_content' => $page_config['shortcode'],
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => 1,
            'post_parent' => $parent_id,
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        );
        
        $page_id = wp_insert_post($page_data);
        
        if ($page_id && !is_wp_error($page_id)) {
            // Add page description as meta
            update_post_meta($page_id, '_malisafi_page_description', $page_config['description']);
            update_post_meta($page_id, '_malisafi_page_key', $key);
            
            return $page_id;
        }
        
        return false;
    }
    
    /**
     * Check pages status
     */
    public static function check_pages_status() {
        $status = array();
        
        foreach (self::$required_pages as $key => $page) {
            $page_id = get_option('malisafi_page_' . $key);
            $status[$key] = array(
                'exists' => $page_id && get_post($page_id) ? true : false,
                'page_id' => $page_id,
                'title' => $page['title'],
                'shortcode' => $page['shortcode']
            );
        }
        
        update_option('malisafi_pages_status', $status);
        return $status;
    }
    
    /**
     * Get pages status
     */
    public static function get_pages_status() {
        $status = get_option('malisafi_pages_status');
        if (!$status) {
            $status = self::check_pages_status();
        }
        return $status;
    }
    
    /**
     * Show admin notice if pages are missing
     */
    public static function pages_status_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $status = self::get_pages_status();
        $missing_pages = array();
        
        foreach ($status as $key => $page_status) {
            if (!$page_status['exists']) {
                $missing_pages[] = $page_status['title'];
            }
        }
        
        if (!empty($missing_pages)) {
            // Don't show on the pages management page itself
            $screen = get_current_screen();
            if ($screen && $screen->id === 'malisafi_page_malisafi-pages') {
                return;
            }
            
            // Show prominent notice on all admin pages
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong><?php _e('Malisafi MLS:', 'malisafi-mls'); ?></strong> 
                    <?php printf(
                        __('%d dashboard page%s missing. The Malisafi Bar links may not work correctly.', 'malisafi-mls'),
                        count($missing_pages),
                        count($missing_pages) > 1 ? 's are' : ' is'
                    ); ?>
                    <a href="<?php echo admin_url('admin.php?page=malisafi-pages'); ?>" class="button button-primary" style="margin-left: 10px;">
                        <?php _e('Create Missing Pages Now', 'malisafi-mls'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Get page URL by key
     */
    public static function get_page_url($key) {
        $page_id = get_option('malisafi_page_' . $key);
        if ($page_id && get_post($page_id)) {
            return get_permalink($page_id);
        }
        return home_url();
    }
    
    /**
     * Delete all plugin pages
     */
    public static function delete_all_pages() {
        foreach (self::$required_pages as $key => $page) {
            $page_id = get_option('malisafi_page_' . $key);
            if ($page_id) {
                wp_delete_post($page_id, true);
                delete_option('malisafi_page_' . $key);
            }
        }
        delete_option('malisafi_pages_status');
        delete_option('malisafi_pages_created');
    }
    
    /**
     * Get missing pages
     */
    public static function get_missing_pages() {
        $status = self::get_pages_status();
        $missing = array();
        
        foreach ($status as $key => $page_status) {
            if (!$page_status['exists']) {
                $missing[$key] = self::$required_pages[$key];
            }
        }
        
        return $missing;
    }
    
    /**
     * Recreate a specific page
     */
    public static function recreate_page($key) {
        if (!isset(self::$required_pages[$key])) {
            return false;
        }
        
        // Delete existing page
        $page_id = get_option('malisafi_page_' . $key);
        if ($page_id) {
            wp_delete_post($page_id, true);
        }
        
        // Create new page
        $page_config = self::$required_pages[$key];
        
        // Handle parent
        if ($page_config['parent'] !== 0) {
            $parent_id = get_option('malisafi_page_' . $page_config['parent']);
            $page_config['parent_id'] = $parent_id ? $parent_id : 0;
        }
        
        $new_page_id = self::create_page($key, $page_config);
        
        if ($new_page_id) {
            update_option('malisafi_page_' . $key, $new_page_id);
            return $new_page_id;
        }
        
        return false;
    }
    
    /**
     * Update page shortcode
     */
    public static function update_page_shortcode($page_id, $shortcode) {
        $page = get_post($page_id);
        if (!$page) {
            return false;
        }
        
        wp_update_post(array(
            'ID' => $page_id,
            'post_content' => $shortcode
        ));
        
        return true;
    }
    
    /**
     * Get page by key
     */
    public static function get_page($key) {
        $page_id = get_option('malisafi_page_' . $key);
        if ($page_id) {
            return get_post($page_id);
        }
        return null;
    }
}
