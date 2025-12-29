<?php
/**
 * Agent Dashboard
 *
 * @package MalisafiMLS
 */

/**
 * Malisafi_Agent_Dashboard class
 */
class Malisafi_Agent_Dashboard {
    
    /**
     * Initialize the agent dashboard
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_agent_dashboard_menu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
        add_action('wp_ajax_switch_agent_view', array(__CLASS__, 'ajax_switch_agent_view'));
        
        // Allow agents to access WordPress backend
        add_filter('user_has_cap', array(__CLASS__, 'grant_backend_access'), 10, 4);
        
        // Prevent WordPress from blocking agent backend access
        add_action('admin_init', array(__CLASS__, 'allow_agent_backend_access'));
    }
    
    /**
     * Allow agent backend access
     * 
     * Prevents WordPress from redirecting agents away from wp-admin
     */
    public static function allow_agent_backend_access() {
        $user = wp_get_current_user();
        
        if (!$user || !isset($user->roles)) {
            return;
        }
        
        // Check if user is an agent or other Malisafi role
        $malisafi_roles = array('malisafi_agent_basic', 'malisafi_agent_premium', 'malisafi_owner', 'malisafi_developer', 'malisafi_client');
        
        if (array_intersect($malisafi_roles, $user->roles)) {
            // Grant minimum capabilities needed for backend access
            if (!current_user_can('read')) {
                $user->add_cap('read');
            }
        }
    }
    
    /**
     * Grant backend access to agents
     * 
     * WordPress blocks backend access for users without certain capabilities.
     * This filter ensures agents can access their dashboard in wp-admin.
     */
    public static function grant_backend_access($allcaps, $caps, $args, $user) {
        // Check if user has agent role
        if (isset($user->roles) && is_array($user->roles)) {
            $agent_roles = array('malisafi_agent_basic', 'malisafi_agent_premium', 'malisafi_owner', 'malisafi_developer', 'malisafi_client');
            if (array_intersect($agent_roles, $user->roles)) {
                // Grant read capability to allow backend access
                $allcaps['read'] = true;
                $allcaps['edit_posts'] = true;
            }
        }
        
        return $allcaps;
    }
    
    /**
     * Add agent dashboard menu
     */
    public static function add_agent_dashboard_menu() {
        // Agent Dashboard for agent roles
        if (self::is_agent_role()) {
            add_menu_page(
                __('My Dashboard', 'malisafi-mls'),
                __('My Dashboard', 'malisafi-mls'),
                'read',
                'malisafi-agent-dashboard',
                array(__CLASS__, 'render_agent_dashboard'),
                'dashicons-businessman',
                3
            );
            
            add_submenu_page(
                'malisafi-agent-dashboard',
                __('My Properties', 'malisafi-mls'),
                __('My Properties', 'malisafi-mls'),
                'read',
                'edit.php?post_type=malisafi_property&agent_filter=mine'
            );
            
            add_submenu_page(
                'malisafi-agent-dashboard',
                __('Add Property', 'malisafi-mls'),
                __('Add Property', 'malisafi-mls'),
                'edit_posts',
                'post-new.php?post_type=malisafi_property'
            );
            
            add_submenu_page(
                'malisafi-agent-dashboard',
                __('My Profile', 'malisafi-mls'),
                __('My Profile', 'malisafi-mls'),
                'read',
                'malisafi-agent-profile',
                array(__CLASS__, 'render_agent_profile')
            );
            
            add_submenu_page(
                'malisafi-agent-dashboard',
                __('Leads & Inquiries', 'malisafi-mls'),
                __('Leads', 'malisafi-mls'),
                'read',
                'malisafi-agent-leads',
                array(__CLASS__, 'render_agent_leads')
            );
        }
    }
    
    /**
     * Check if current user is an agent
     */
    private static function is_agent_role() {
        $user = wp_get_current_user();
        $agent_roles = array('malisafi_agent_basic', 'malisafi_agent_premium');
        return array_intersect($agent_roles, $user->roles) ? true : false;
    }
    
    /**
     * Get current agent ID (profile post ID)
     */
    private static function get_current_agent_id() {
        // Check if admin is viewing as another agent
        if (current_user_can('manage_options')) {
            $viewing_agent = get_user_meta(get_current_user_id(), '_viewing_as_agent_id', true);
            if ($viewing_agent) {
                return intval($viewing_agent);
            }
        }
        
        // Get agent profile linked to current user
        $user_id = get_current_user_id();
        
        $args = array(
            'post_type' => 'malisafi_agent',
            'post_status' => array('publish', 'pending', 'draft'), // Include all statuses
            'meta_query' => array(
                array(
                    'key' => '_agent_user_id',
                    'value' => $user_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1,
            'fields' => 'ids'
        );
        
        $agents = get_posts($args);
        return !empty($agents) ? $agents[0] : 0;
    }
    
    /**
     * Render agent dashboard
     */
    public static function render_agent_dashboard() {
        $agent_id = self::get_current_agent_id();
        
        if (!$agent_id) {
            $current_user = wp_get_current_user();
            $user_info = sprintf(
                __('Logged in as: <strong>%s</strong> (Username: %s, Email: %s, Role: %s)', 'malisafi-mls'),
                $current_user->display_name,
                $current_user->user_login,
                $current_user->user_email,
                implode(', ', $current_user->roles)
            );
            
            echo '<div class="wrap">';
            echo '<h1>' . __('Access Denied', 'malisafi-mls') . '</h1>';
            echo '<div class="notice notice-error"><p>' . __('No agent profile found for your account. Please contact the administrator.', 'malisafi-mls') . '</p></div>';
            echo '<div class="notice notice-info"><p>' . $user_info . '</p></div>';
            echo '<p><a href="' . admin_url() . '" class="button button-primary">' . __('Return to Dashboard', 'malisafi-mls') . '</a></p>';
            echo '</div>';
            return;
        }
        
        global $wpdb;
        
        // Get statistics
        $total_properties = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_property_agent_id' AND meta_value = %d",
            $agent_id
        ));
        
        $active_listings = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT pm.post_id) 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_property_agent_id' 
            AND pm.meta_value = %d
            AND p.post_status = 'publish'",
            $agent_id
        ));
        
        $pending_properties = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT pm.post_id) 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_property_agent_id' 
            AND pm.meta_value = %d
            AND p.post_status = 'pending'",
            $agent_id
        ));
        
        // Get recent properties
        $recent_properties = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID, p.post_title, p.post_status, p.post_date
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_property_agent_id' 
            AND pm.meta_value = %d
            ORDER BY p.post_date DESC
            LIMIT 5",
            $agent_id
        ));
        
        $agent_name = get_the_title($agent_id);
        $agent_email = get_post_meta($agent_id, '_agent_email', true);
        $agent_mobile = get_post_meta($agent_id, '_agent_mobile', true);
        $agent_status = get_post_meta($agent_id, '_agent_status', true);
        
        // Admin view switcher
        $viewing_as_text = '';
        if (current_user_can('manage_options')) {
            $viewing_agent = get_user_meta(get_current_user_id(), '_viewing_as_agent_id', true);
            if ($viewing_agent) {
                $viewing_as_text = '<div class="notice notice-info"><p><strong>' . __('Admin View:', 'malisafi-mls') . '</strong> ' . 
                    sprintf(__('You are viewing the dashboard as %s', 'malisafi-mls'), $agent_name) . 
                    ' | <a href="' . wp_nonce_url(admin_url('admin.php?page=malisafi-agent-dashboard&clear_agent_view=1'), 'clear_agent_view') . '">' . 
                    __('Exit Agent View', 'malisafi-mls') . '</a></p></div>';
            }
        }
        
        include plugin_dir_path(dirname(__FILE__)) . 'admin/templates/agent-dashboard.php';
    }
    
    /**
     * Render agent profile
     */
    public static function render_agent_profile() {
        $agent_id = self::get_current_agent_id();
        
        if (!$agent_id) {
            $current_user = wp_get_current_user();
            $user_info = sprintf(
                __('Logged in as: <strong>%s</strong> (Username: %s, Email: %s, Role: %s)', 'malisafi-mls'),
                $current_user->display_name,
                $current_user->user_login,
                $current_user->user_email,
                implode(', ', $current_user->roles)
            );
            
            echo '<div class="wrap">';
            echo '<h1>' . __('Access Denied', 'malisafi-mls') . '</h1>';
            echo '<div class="notice notice-error"><p>' . __('No agent profile found.', 'malisafi-mls') . '</p></div>';
            echo '<div class="notice notice-info"><p>' . $user_info . '</p></div>';
            echo '<p><a href="' . admin_url() . '" class="button button-primary">' . __('Return to Dashboard', 'malisafi-mls') . '</a></p>';
            echo '</div>';
            return;
        }
        
        include plugin_dir_path(dirname(__FILE__)) . 'admin/templates/agent-profile.php';
    }
    
    /**
     * Render agent leads
     */
    public static function render_agent_leads() {
        $agent_id = self::get_current_agent_id();
        
        if (!$agent_id) {
            $current_user = wp_get_current_user();
            $user_info = sprintf(
                __('Logged in as: <strong>%s</strong> (Username: %s, Email: %s, Role: %s)', 'malisafi-mls'),
                $current_user->display_name,
                $current_user->user_login,
                $current_user->user_email,
                implode(', ', $current_user->roles)
            );
            
            echo '<div class="wrap">';
            echo '<h1>' . __('Access Denied', 'malisafi-mls') . '</h1>';
            echo '<div class="notice notice-error"><p>' . __('No agent profile found.', 'malisafi-mls') . '</p></div>';
            echo '<div class="notice notice-info"><p>' . $user_info . '</p></div>';
            echo '<p><a href="' . admin_url() . '" class="button button-primary">' . __('Return to Dashboard', 'malisafi-mls') . '</a></p>';
            echo '</div>';
            return;
        }
        
        global $wpdb;
        
        // Get leads for this agent's properties
        $leads = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, p.post_title as property_title
            FROM {$wpdb->prefix}mf_leads l
            INNER JOIN {$wpdb->postmeta} pm ON l.property_id = pm.post_id
            INNER JOIN {$wpdb->posts} p ON l.property_id = p.ID
            WHERE pm.meta_key = '_property_agent_id' 
            AND pm.meta_value = %d
            ORDER BY l.created_at DESC
            LIMIT 50",
            $agent_id
        ));
        
        include plugin_dir_path(dirname(__FILE__)) . 'admin/templates/agent-leads.php';
    }
    
    /**
     * AJAX handler to switch agent view
     */
    public static function ajax_switch_agent_view() {
        check_ajax_referer('switch_agent_view', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'malisafi-mls')));
        }
        
        $agent_id = isset($_POST['agent_id']) ? intval($_POST['agent_id']) : 0;
        
        if ($agent_id) {
            update_user_meta(get_current_user_id(), '_viewing_as_agent_id', $agent_id);
            wp_send_json_success(array('message' => __('Switched to agent view', 'malisafi-mls')));
        } else {
            delete_user_meta(get_current_user_id(), '_viewing_as_agent_id');
            wp_send_json_success(array('message' => __('Exited agent view', 'malisafi-mls')));
        }
    }
    
    /**
     * Enqueue scripts
     */
    public static function enqueue_scripts($hook) {
        if (strpos($hook, 'malisafi-agent') === false) {
            return;
        }
        
        wp_enqueue_style('malisafi-agent-dashboard', plugin_dir_url(dirname(__FILE__)) . 'assets/css/agent-dashboard.css', array(), '1.0.0');
        wp_enqueue_script('malisafi-agent-dashboard', plugin_dir_url(dirname(__FILE__)) . 'assets/js/agent-dashboard.js', array('jquery'), '1.0.0', true);
        
        wp_localize_script('malisafi-agent-dashboard', 'malisafiAgent', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('switch_agent_view')
        ));
    }
}
