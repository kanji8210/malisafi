<?php
/**
 * Malisafi Bar - Custom Frontend Navigation Bar
 * 
 * Replaces WordPress admin bar with custom white-labeled bar for agents, 
 * owners, developers, and hunters on the frontend
 * 
 * @package MalisafiMLS
 */

namespace MalisafiMLS;

class Malisafi_Bar {
    
    /**
     * Initialize the Malisafi Bar
     */
    public static function init() {
        // Hide WordPress admin bar for restricted roles
        add_action('after_setup_theme', array(__CLASS__, 'hide_wp_admin_bar'));
        add_filter('show_admin_bar', array(__CLASS__, 'filter_admin_bar'));
        
        // Render custom Malisafi Bar on frontend
        add_action('wp_body_open', array(__CLASS__, 'render_malisafi_bar'), 1);
        add_action('wp_footer', array(__CLASS__, 'render_malisafi_bar_fallback'), 1);
        
        // Enqueue styles and scripts
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
        
        // Block wp-admin access for restricted roles
        add_action('admin_init', array(__CLASS__, 'block_wp_admin_access'));
        
        // Redirect after login to custom dashboard
        add_filter('login_redirect', array(__CLASS__, 'redirect_after_login'), 100, 3);
    }
    
    /**
     * Check if current user has a restricted role (non-admin)
     */
    private static function is_restricted_role() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user = wp_get_current_user();
        
        // Allow admins and moderators full access
        if (array_intersect(array('administrator', 'malisafi_moderator'), $user->roles)) {
            return false;
        }
        
        // Restrict these roles
        $restricted_roles = array(
            'malisafi_agent_basic',
            'malisafi_agent_premium',
            'malisafi_owner',
            'malisafi_developer',
            'malisafi_client',
            'malisafi_hunter'
        );
        
        return array_intersect($restricted_roles, $user->roles) ? true : false;
    }
    
    /**
     * Hide WordPress admin bar for restricted roles
     */
    public static function hide_wp_admin_bar() {
        if (self::is_restricted_role()) {
            show_admin_bar(false);
        }
    }
    
    /**
     * Filter admin bar visibility
     */
    public static function filter_admin_bar($show) {
        if (self::is_restricted_role()) {
            return false;
        }
        return $show;
    }
    
    /**
     * Block wp-admin access for restricted roles
     */
    public static function block_wp_admin_access() {
        // Skip if not restricted role
        if (!self::is_restricted_role()) {
            return;
        }
        
        // Allow AJAX requests
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }
        
        // Redirect to custom dashboard
        $redirect_url = self::get_dashboard_url();
        if ($redirect_url) {
            wp_redirect($redirect_url);
            exit;
        }
    }
    
    /**
     * Redirect after login to custom dashboard
     */
    public static function redirect_after_login($redirect_to, $request, $user) {
        // Check if user object exists and has roles
        if (!isset($user->roles) || !is_array($user->roles)) {
            return $redirect_to;
        }
        
        // Allow admins and moderators to go to wp-admin
        if (array_intersect(array('administrator', 'malisafi_moderator'), $user->roles)) {
            return admin_url();
        }
        
        // Get custom dashboard URL for restricted roles
        $dashboard_url = self::get_dashboard_url($user);
        return $dashboard_url ? $dashboard_url : $redirect_to;
    }
    
    /**
     * Get dashboard URL for current user
     */
    private static function get_dashboard_url($user = null) {
        if (!$user) {
            $user = wp_get_current_user();
        }
        
        if (!$user || !isset($user->roles)) {
            return '';
        }
        
        // Determine dashboard URL based on role
        if (in_array('malisafi_agent_basic', $user->roles) || in_array('malisafi_agent_premium', $user->roles)) {
            return self::get_page_url('agent_dashboard');
        } elseif (in_array('malisafi_owner', $user->roles)) {
            return self::get_page_url('owner_dashboard');
        } elseif (in_array('malisafi_developer', $user->roles)) {
            return self::get_page_url('developer_dashboard');
        } elseif (in_array('malisafi_client', $user->roles)) {
            return self::get_page_url('client_dashboard');
        } elseif (in_array('malisafi_hunter', $user->roles)) {
            return self::get_page_url('hunter_dashboard');
        }
        
        return home_url();
    }
    
    /**
     * Get page URL by key using Page Manager
     */
    private static function get_page_url($key) {
        if (class_exists('MalisafiMLS\Page_Manager')) {
            $url = \MalisafiMLS\Page_Manager::get_page_url($key);
            // If page doesn't exist, return home instead of broken link
            if ($url === home_url() || empty($url)) {
                // Try to get the page, if it doesn't exist, trigger creation
                $page_id = get_option('malisafi_page_' . $key);
                if (!$page_id) {
                    // Page doesn't exist - return a placeholder that won't break
                    return home_url('/?malisafi_missing_page=' . $key);
                }
            }
            return $url;
        }
        // Fallback
        return home_url('/');
    }
    
    /**
     * Render Malisafi Bar
     */
    public static function render_malisafi_bar() {
        // Only show on frontend and for logged-in restricted users
        if (is_admin() || !self::is_restricted_role()) {
            return;
        }
        
        // Check if already rendered
        if (did_action('malisafi_bar_rendered')) {
            return;
        }
        
        $user = wp_get_current_user();
        $user_name = $user->display_name;
        $user_role = self::get_user_role_label($user);
        
        ?>
        <div id="malisafi-bar" class="malisafi-bar">
            <div class="malisafi-bar-container">
                <!-- Brand -->
                <div class="malisafi-bar-brand">
                    <a href="<?php echo esc_url(home_url('/')); ?>">
                        <span class="malisafi-bar-logo">🏠</span>
                        <span class="malisafi-bar-title"><?php echo esc_html(get_bloginfo('name')); ?></span>
                    </a>
                </div>
                
                <!-- Navigation -->
                <nav class="malisafi-bar-nav">
                    <?php echo self::get_navigation_items($user); ?>
                </nav>
                
                <!-- User Menu -->
                <div class="malisafi-bar-user">
                    <button class="malisafi-bar-user-toggle" aria-expanded="false" aria-label="User menu">
                        <span class="malisafi-bar-avatar">
                            <?php echo get_avatar($user->ID, 32); ?>
                        </span>
                        <span class="malisafi-bar-user-info">
                            <span class="malisafi-bar-user-name"><?php echo esc_html($user_name); ?></span>
                            <span class="malisafi-bar-user-role"><?php echo esc_html($user_role); ?></span>
                        </span>
                        <span class="malisafi-bar-dropdown-icon">▼</span>
                    </button>
                    
                    <div class="malisafi-bar-user-dropdown" hidden>
                        <a href="<?php echo esc_url(self::get_dashboard_url()); ?>" class="malisafi-bar-dropdown-item">
                            <span class="dashicons dashicons-dashboard"></span>
                            <?php _e('My Dashboard', 'malisafi-mls'); ?>
                        </a>
                        <a href="<?php echo esc_url(self::get_page_url('profile')); ?>" class="malisafi-bar-dropdown-item">
                            <span class="dashicons dashicons-admin-users"></span>
                            <?php _e('My Profile', 'malisafi-mls'); ?>
                        </a>
                        <div class="malisafi-bar-dropdown-divider"></div>
                        <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="malisafi-bar-dropdown-item">
                            <span class="dashicons dashicons-exit"></span>
                            <?php _e('Logout', 'malisafi-mls'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
        
        do_action('malisafi_bar_rendered');
    }
    
    /**
     * Fallback render if wp_body_open doesn't exist
     */
    public static function render_malisafi_bar_fallback() {
        if (!did_action('malisafi_bar_rendered')) {
            self::render_malisafi_bar();
        }
    }
    
    /**
     * Get navigation items based on user role
     */
    private static function get_navigation_items($user) {
        $items = array();
        
        // Common items
        $items[] = array(
            'url' => self::get_dashboard_url(),
            'label' => __('Dashboard', 'malisafi-mls'),
            'icon' => 'dashicons-dashboard'
        );
        
        // Role-specific items
        if (in_array('malisafi_agent_basic', $user->roles) || in_array('malisafi_agent_premium', $user->roles)) {
            $items[] = array(
                'url' => self::get_page_url('agent_properties'),
                'label' => __('My Properties', 'malisafi-mls'),
                'icon' => 'dashicons-admin-home'
            );
            $items[] = array(
                'url' => self::get_page_url('agent_add_property'),
                'label' => __('Add Property', 'malisafi-mls'),
                'icon' => 'dashicons-plus-alt'
            );
            $items[] = array(
                'url' => self::get_page_url('agent_leads'),
                'label' => __('Leads', 'malisafi-mls'),
                'icon' => 'dashicons-email'
            );
        } elseif (in_array('malisafi_owner', $user->roles)) {
            $items[] = array(
                'url' => self::get_page_url('owner_properties'),
                'label' => __('My Properties', 'malisafi-mls'),
                'icon' => 'dashicons-admin-home'
            );
            $items[] = array(
                'url' => self::get_page_url('owner_add_property'),
                'label' => __('Add Property', 'malisafi-mls'),
                'icon' => 'dashicons-plus-alt'
            );
        } elseif (in_array('malisafi_developer', $user->roles)) {
            $items[] = array(
                'url' => self::get_page_url('developer_projects'),
                'label' => __('My Projects', 'malisafi-mls'),
                'icon' => 'dashicons-admin-multisite'
            );
            $items[] = array(
                'url' => self::get_page_url('developer_add_project'),
                'label' => __('Add Project', 'malisafi-mls'),
                'icon' => 'dashicons-plus-alt'
            );
        }
        
        // Render items
        $output = '';
        foreach ($items as $item) {
            $output .= sprintf(
                '<a href="%s" class="malisafi-bar-nav-item"><span class="dashicons %s"></span>%s</a>',
                esc_url($item['url']),
                esc_attr($item['icon']),
                esc_html($item['label'])
            );
        }
        
        return $output;
    }
    
    /**
     * Get user role label
     */
    private static function get_user_role_label($user) {
        $role_labels = array(
            'malisafi_agent_basic' => __('Basic Agent', 'malisafi-mls'),
            'malisafi_agent_premium' => __('Premium Agent', 'malisafi-mls'),
            'malisafi_owner' => __('Property Owner', 'malisafi-mls'),
            'malisafi_developer' => __('Developer', 'malisafi-mls'),
            'malisafi_client' => __('Client', 'malisafi-mls'),
            'malisafi_hunter' => __('Property Hunter', 'malisafi-mls'),
        );
        
        foreach ($user->roles as $role) {
            if (isset($role_labels[$role])) {
                return $role_labels[$role];
            }
        }
        
        return __('User', 'malisafi-mls');
    }
    
    /**
     * Enqueue styles and scripts
     */
    public static function enqueue_assets() {
        // Only on frontend for restricted roles
        if (is_admin() || !self::is_restricted_role()) {
            return;
        }
        
        wp_enqueue_style(
            'malisafi-bar',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/malisafi-bar.css',
            array(),
            '1.0.0'
        );
        
        wp_enqueue_script(
            'malisafi-bar',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/malisafi-bar.js',
            array('jquery'),
            '1.0.0',
            true
        );
    }
}
