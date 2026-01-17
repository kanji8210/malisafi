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
        
        // Handle view clearing
        add_action('admin_init', array(__CLASS__, 'handle_clear_agent_view'));
    }
    
    /**
     * Handle clearing agent view
     */
    public static function handle_clear_agent_view() {
        if (isset($_GET['clear_agent_view']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'clear_agent_view')) {
            delete_user_meta(get_current_user_id(), '_viewing_as_agent_id');
            
            // Use wp_safe_redirect with exit - NO OUTPUT BEFORE THIS
            wp_safe_redirect(remove_query_arg(array('clear_agent_view', '_wpnonce')));
            exit;
        }
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
                'malisafi-properties',
                array(__CLASS__, 'render_properties_list')
            );
            
            add_submenu_page(
                'malisafi-agent-dashboard',
                __('Add Property', 'malisafi-mls'),
                __('Add Property', 'malisafi-mls'),
                'edit_posts',
                'malisafi-property-edit',
                array(__CLASS__, 'render_property_edit')
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
     * Render properties list
     */
    public static function render_properties_list() {
        $agent_id = self::get_current_agent_id();
        
        if (!$agent_id) {
            echo '<div class="wrap">';
            echo '<h1>' . __('Access Denied', 'malisafi-mls') . '</h1>';
            echo '<div class="notice notice-error"><p>' . __('No agent profile found.', 'malisafi-mls') . '</p></div>';
            echo '</div>';
            return;
        }
        
        if ($template = self::get_template_path('properties-list.php')) {
            include $template;
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
     * Get template path
     */
    private static function get_template_path($template_name) {
        $template = plugin_dir_path(dirname(__FILE__)) . 'admin/templates/' . $template_name;
        
        // Check if template exists
        if (!file_exists($template)) {
            // Fallback to simple output
            echo '<div class="notice notice-warning"><p>' . sprintf(__('Template file %s not found.', 'malisafi-mls'), $template_name) . '</p></div>';
            return false;
        }
        
        return $template;
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
            'post_status' => array('publish', 'pending', 'draft'),
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
        // Start output buffering to prevent header errors
        if (!ob_get_level()) {
            ob_start();
        }
        
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
            
            ob_end_flush();
            return;
        }
        
        global $wpdb;
        
        // Get linked user ID for this agent
        $linked_user_id = get_post_meta($agent_id, '_malisafi_linked_user', true);
        
        // COMPREHENSIVE QUERY: Count ALL properties that could belong to this agent
        if ($linked_user_id) {
            // Count by both post_author and meta
            $total_properties = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key IN ('_malisafi_agent_id', '_property_agent_id')
                WHERE p.post_type = 'malisafi_property'
                AND (p.post_author = %d OR pm.meta_value = %d)",
                $linked_user_id,
                $agent_id
            ));
            
            $active_listings = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key IN ('_malisafi_agent_id', '_property_agent_id')
                WHERE p.post_type = 'malisafi_property'
                AND p.post_status = 'publish'
                AND (p.post_author = %d OR pm.meta_value = %d)",
                $linked_user_id,
                $agent_id
            ));
            
            $pending_properties = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key IN ('_malisafi_agent_id', '_property_agent_id')
                WHERE p.post_type = 'malisafi_property'
                AND p.post_status = 'pending'
                AND (p.post_author = %d OR pm.meta_value = %d)",
                $linked_user_id,
                $agent_id
            ));
        } else {
            // No linked user - only use meta
            $total_properties = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT pm.post_id) 
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE pm.meta_key IN ('_malisafi_agent_id', '_property_agent_id')
                AND pm.meta_value = %d
                AND p.post_type = 'malisafi_property'",
                $agent_id
            ));
            
            $active_listings = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT pm.post_id) 
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE pm.meta_key IN ('_malisafi_agent_id', '_property_agent_id')
                AND pm.meta_value = %d
                AND p.post_type = 'malisafi_property'
                AND p.post_status = 'publish'",
                $agent_id
            ));
            
            $pending_properties = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(DISTINCT pm.post_id) 
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE pm.meta_key IN ('_malisafi_agent_id', '_property_agent_id')
                AND pm.meta_value = %d
                AND p.post_type = 'malisafi_property'
                AND p.post_status = 'pending'",
                $agent_id
            ));
        }
        
        // Get recent properties
        if ($linked_user_id) {
            $recent_properties = $wpdb->get_results($wpdb->prepare(
                "SELECT DISTINCT p.ID, p.post_title, p.post_status, p.post_date
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key IN ('_malisafi_agent_id', '_property_agent_id')
                WHERE p.post_type = 'malisafi_property'
                AND (p.post_author = %d OR pm.meta_value = %d)
                ORDER BY p.post_date DESC
                LIMIT 10",
                $linked_user_id,
                $agent_id
            ));
        } else {
            $recent_properties = $wpdb->get_results($wpdb->prepare(
                "SELECT DISTINCT p.ID, p.post_title, p.post_status, p.post_date
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE pm.meta_key IN ('_malisafi_agent_id', '_property_agent_id')
                AND pm.meta_value = %d
                AND p.post_type = 'malisafi_property'
                ORDER BY p.post_date DESC
                LIMIT 10",
                $agent_id
            ));
        }
        
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
        
        if ($template = self::get_template_path('agent-dashboard.php')) {
            include $template;
        }
        
        ob_end_flush();
    }
    
    /**
     * Render agent profile
     */
    public static function render_agent_profile() {
        // Start output buffering
        if (!ob_get_level()) {
            ob_start();
        }
        
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
            
            ob_end_flush();
            return;
        }
        
        if ($template = self::get_template_path('agent-profile.php')) {
            include $template;
        }
        
        ob_end_flush();
    }
    
    /**
     * Render agent leads
     */
    public static function render_agent_leads() {
        // Start output buffering
        if (!ob_get_level()) {
            ob_start();
        }
        
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
            
            ob_end_flush();
            return;
        }
        
        global $wpdb;
        
        // Get linked user ID for this agent
        $linked_user_id = get_post_meta($agent_id, '_malisafi_linked_user', true);
        
        // Check if legacy leads table exists
        $table_name = $wpdb->prefix . 'mf_leads';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
        
        $leads = array();
        if ($table_exists) {
            if ($linked_user_id) {
                $leads = $wpdb->get_results($wpdb->prepare(
                    "SELECT l.*, p.post_title as property_title
                    FROM {$table_name} l
                    INNER JOIN {$wpdb->posts} p ON l.property_id = p.ID
                    WHERE p.post_type = 'malisafi_property'
                    AND p.post_author = %d
                    ORDER BY l.created_at DESC
                    LIMIT 50",
                    $linked_user_id
                ));
            } else {
                $leads = $wpdb->get_results($wpdb->prepare(
                    "SELECT l.*, p.post_title as property_title
                    FROM {$table_name} l
                    INNER JOIN {$wpdb->postmeta} pm ON l.property_id = pm.post_id
                    INNER JOIN {$wpdb->posts} p ON l.property_id = p.ID
                    WHERE pm.meta_key = '_malisafi_agent_id' 
                    AND pm.meta_value = %d
                    AND p.post_type = 'malisafi_property'
                    ORDER BY l.created_at DESC
                    LIMIT 50",
                    $agent_id
                ));
            }
        }
        
        if ($template = self::get_template_path('agent-leads.php')) {
            include $template;
        }
        
        ob_end_flush();
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
        
        // Enqueue WordPress media library for image uploads
        wp_enqueue_media();
        
        // Check if files exist before enqueueing
        $plugin_dir = plugin_dir_url(dirname(__FILE__));
        
        // Enqueue CSS
        $css_files = array(
            'agent-dashboard' => 'assets/css/agent-dashboard.css',
            'malisafi-mls-admin' => 'assets/css/admin.css'
        );
        
        foreach ($css_files as $handle => $file) {
            wp_enqueue_style($handle, $plugin_dir . $file, array(), '1.0.0');
        }
        
        // Enqueue JS
        wp_enqueue_script('malisafi-agent-dashboard', $plugin_dir . 'assets/js/agent-dashboard.js', array('jquery'), '1.0.0', true);
        wp_enqueue_script('malisafi-mls-admin', $plugin_dir . 'assets/js/admin.js', array('jquery'), '1.0.0', false);
        
        // Localize scripts
        wp_localize_script('malisafi-agent-dashboard', 'malisafiAgent', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('switch_agent_view')
        ));
        
        wp_localize_script('malisafi-mls-admin', 'malisafi_admin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('malisafi_mls_nonce'),
            'strings' => array(
                'media_select_title' => __('Select Property Images', 'malisafi-mls'),
                'media_select_button' => __('Use Images', 'malisafi-mls')
            )
        ));
    }

    /**
     * Render property create/edit page (custom handler)
     */
    public static function render_property_edit() {
        // Check if output buffering is needed
        if (!ob_get_level()) {
            ob_start();
        }
        
        // Check permissions FIRST
        $user = wp_get_current_user();
        $allowed_roles = array('malisafi_agent_basic', 'malisafi_agent_premium', 'malisafi_owner', 'malisafi_developer', 'malisafi_moderator');
        if (!array_intersect($allowed_roles, $user->roles)) {
            wp_die(__('You do not have permission to access this page.', 'malisafi-mls'));
        }

        // Handle form submission BEFORE any output
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['malisafi_property_edit_nonce']) && wp_verify_nonce($_POST['malisafi_property_edit_nonce'], 'malisafi_property_edit')) {
            $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
            $property_title = sanitize_text_field($_POST['property_title'] ?? '');
            $property_gps = sanitize_text_field($_POST['property_gps'] ?? '');

            if (empty($property_title)) {
                $error = __('Title is required.', 'malisafi-mls');
            } else {
                $post_status = current_user_can('moderate_properties') ? 'publish' : 'pending';
                $post_data = array(
                    'post_title'   => $property_title,
                    'post_type'    => 'malisafi_property',
                    'post_status'  => $post_status,
                    'post_author'  => get_current_user_id()
                );

                if ($property_id) {
                    // Editing existing property
                    if (!current_user_can('moderate_properties')) {
                        $post_data['post_status'] = 'pending';
                    }
                    
                    $post_data['ID'] = $property_id;
                    $new_id = wp_update_post($post_data, true);
                    
                    if (is_wp_error($new_id)) {
                        $error = $new_id->get_error_message();
                    } else {
                        $agent_id = self::get_current_agent_id();
                        if ($agent_id) {
                            update_post_meta($new_id, '_malisafi_agent_id', $agent_id);
                        }
                        update_post_meta($new_id, '_property_gps', $property_gps);
                        $property_id = $new_id;
                        $message = !current_user_can('moderate_properties') 
                            ? __('Property updated successfully. It has been sent for approval.', 'malisafi-mls')
                            : __('Property updated successfully.', 'malisafi-mls');
                    }
                } else {
                    // Creating new property - DO REDIRECT IMMEDIATELY
                    $new_id = wp_insert_post($post_data, true);
                    
                    if (is_wp_error($new_id)) {
                        $error = $new_id->get_error_message();
                    } else {
                        $agent_id = self::get_current_agent_id();
                        if ($agent_id) {
                            update_post_meta($new_id, '_malisafi_agent_id', $agent_id);
                        }
                        update_post_meta($new_id, '_property_gps', $property_gps);
                        
                        // CRITICAL: Redirect immediately with NO output before
                        wp_safe_redirect(
                            admin_url('admin.php?page=malisafi-property-edit&property_id=' . $new_id . '&created=1')
                        );
                        exit; // Stop execution immediately
                    }
                }
            }
        }

        // Get property ID if editing (after POST handling)
        $property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : 0;

        // Initialize variables
        $property_title = '';
        $property_gps = '';
        
        // Load existing data if editing (only if not just redirected)
        if ($property_id && empty($_POST)) {
            $post = get_post($property_id);
            if ($post && $post->post_type === 'malisafi_property') {
                $property_title = $post->post_title;
                $property_gps = get_post_meta($property_id, '_property_gps', true);
            }
        }

        // Check for created parameter
        if (isset($_GET['created']) && $_GET['created'] == 1) {
            $message = __('Property created successfully.', 'malisafi-mls');
        }

        // NOW output HTML
        echo '<div class="wrap">';
        echo '<h1>';
        echo $property_id ? __('Edit Property', 'malisafi-mls') : __('Add Property', 'malisafi-mls');
        echo '</h1>';
        
        if (!empty($message)) {
            echo '<div class="notice notice-success"><p>' . esc_html($message) . '</p></div>';
        }
        if (!empty($error)) {
            echo '<div class="notice notice-error"><p>' . esc_html($error) . '</p></div>';
        }
        
        // Include template
        $template_path = plugin_dir_path(dirname(__FILE__)) . 'admin/templates/property-edit-form.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="notice notice-error"><p>' . __('Property edit form template not found.', 'malisafi-mls') . '</p></div>';
        }
        
        echo '</div>';
        
        // Clean output buffer
        ob_end_flush();
    }
}