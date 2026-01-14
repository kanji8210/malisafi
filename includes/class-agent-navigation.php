<?php
/**
 * Custom Agent Navigation Bar
 * 
 * Provides a custom navigation bar for agents instead of WordPress admin bar
 * 
 * @package MalisafiMLS
 */

class Malisafi_Agent_Navigation {
    
    /**
     * Initialize agent navigation
     */
    public static function init() {
        // Hide WordPress admin bar for agents
        add_action('after_setup_theme', array(__CLASS__, 'hide_admin_bar_for_agents'));
        add_filter('show_admin_bar', array(__CLASS__, 'filter_admin_bar'), 10, 1);
        
        // Remove WordPress admin menus for agents
        add_action('admin_menu', array(__CLASS__, 'remove_admin_menus_for_agents'), 999);
        
        // Add custom agent navigation bar
        add_action('admin_head', array(__CLASS__, 'render_agent_navigation'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_navigation_styles'));
        
        // Hide admin notices for agents
        add_action('admin_head', array(__CLASS__, 'hide_admin_notices_for_agents'));
        
        // Redirect agents away from default dashboard
        add_action('admin_init', array(__CLASS__, 'redirect_from_default_dashboard'));
    }
    
    /**
     * Check if current user is an agent (not admin or moderator)
     */
    private static function is_agent() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user = wp_get_current_user();
        $agent_roles = array('malisafi_agent_basic', 'malisafi_agent_premium', 'malisafi_owner', 'malisafi_developer');
        
        // Don't treat admins and moderators as agents
        if (array_intersect(array('administrator', 'malisafi_moderator'), $user->roles)) {
            return false;
        }
        
        return array_intersect($agent_roles, $user->roles) ? true : false;
    }
    
    /**
     * Hide admin bar for agents
     */
    public static function hide_admin_bar_for_agents() {
        if (self::is_agent()) {
            show_admin_bar(false);
        }
    }
    
    /**
     * Filter show_admin_bar
     */
    public static function filter_admin_bar($show) {
        if (self::is_agent()) {
            return false;
        }
        return $show;
    }
    
    /**
     * Remove WordPress admin menus for agents
     */
    public static function remove_admin_menus_for_agents() {
        if (!self::is_agent()) {
            return;
        }
        
        // Remove all default WordPress menus
        remove_menu_page('index.php');                  // Dashboard
        remove_menu_page('edit.php');                   // Posts
        remove_menu_page('upload.php');                 // Media (but keep access via wp_enqueue_media)
        remove_menu_page('edit.php?post_type=page');    // Pages
        remove_menu_page('edit-comments.php');          // Comments
        remove_menu_page('themes.php');                 // Appearance
        remove_menu_page('plugins.php');                // Plugins
        remove_menu_page('users.php');                  // Users
        remove_menu_page('tools.php');                  // Tools
        remove_menu_page('options-general.php');        // Settings
        
        // Remove other post types that might be registered
        remove_menu_page('edit.php?post_type=malisafi_property'); // Default property menu
        remove_menu_page('edit.php?post_type=malisafi_agent');    // Default agent menu
    }
    
    /**
     * Hide admin notices for agents
     */
    public static function hide_admin_notices_for_agents() {
        if (!self::is_agent()) {
            return;
        }
        
        echo '<style>
            .update-nag, .updated, .error, .notice:not(.malisafi-notice) {
                display: none !important;
            }
        </style>';
    }
    
    /**
     * Redirect agents from default dashboard to custom dashboard
     */
    public static function redirect_from_default_dashboard() {
        if (!self::is_agent()) {
            return;
        }
        
        global $pagenow;
        
        // If accessing default dashboard, redirect to agent dashboard
        if ($pagenow === 'index.php' || $pagenow === 'profile.php' || $pagenow === 'user-edit.php') {
            wp_safe_redirect(admin_url('admin.php?page=malisafi-agent-dashboard'));
            exit;
        }
    }
    
    /**
     * Render custom agent navigation bar
     */
    public static function render_agent_navigation() {
        if (!self::is_agent()) {
            return;
        }
        
        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        
        // Get current agent info
        $user = wp_get_current_user();
        $agent_name = $user->display_name;
        
        ?>
        <div id="malisafi-agent-nav" class="malisafi-agent-navigation">
            <div class="agent-nav-container">
                <div class="agent-nav-brand">
                    <span class="agent-nav-logo">🏠</span>
                    <span class="agent-nav-title"><?php echo esc_html(get_bloginfo('name')); ?></span>
                </div>
                
                <nav class="agent-nav-menu">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=malisafi-agent-dashboard')); ?>" 
                       class="agent-nav-item <?php echo ($current_page === 'malisafi-agent-dashboard') ? 'active' : ''; ?>">
                        <span class="dashicons dashicons-dashboard"></span>
                        <?php _e('My Dashboard', 'malisafi-mls'); ?>
                    </a>
                    
                    <a href="<?php echo esc_url(admin_url('admin.php?page=malisafi-properties')); ?>" 
                       class="agent-nav-item <?php echo ($current_page === 'malisafi-properties') ? 'active' : ''; ?>">
                        <span class="dashicons dashicons-admin-home"></span>
                        <?php _e('My Properties', 'malisafi-mls'); ?>
                    </a>
                    
                    <a href="<?php echo esc_url(admin_url('admin.php?page=malisafi-property-edit')); ?>" 
                       class="agent-nav-item <?php echo ($current_page === 'malisafi-property-edit') ? 'active' : ''; ?>">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Add Property', 'malisafi-mls'); ?>
                    </a>
                    
                    <a href="<?php echo esc_url(admin_url('admin.php?page=malisafi-agent-profile')); ?>" 
                       class="agent-nav-item <?php echo ($current_page === 'malisafi-agent-profile') ? 'active' : ''; ?>">
                        <span class="dashicons dashicons-businessman"></span>
                        <?php _e('My Profile', 'malisafi-mls'); ?>
                    </a>
                    
                    <a href="<?php echo esc_url(admin_url('admin.php?page=malisafi-agent-leads')); ?>" 
                       class="agent-nav-item <?php echo ($current_page === 'malisafi-agent-leads') ? 'active' : ''; ?>">
                        <span class="dashicons dashicons-email"></span>
                        <?php _e('Leads', 'malisafi-mls'); ?>
                    </a>
                </nav>
                
                <div class="agent-nav-user">
                    <span class="agent-nav-username">
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php echo esc_html($agent_name); ?>
                    </span>
                    <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="agent-nav-logout" title="<?php _e('Logout', 'malisafi-mls'); ?>">
                        <span class="dashicons dashicons-exit"></span>
                        <?php _e('Logout', 'malisafi-mls'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Enqueue navigation styles
     */
    public static function enqueue_navigation_styles() {
        if (!self::is_agent()) {
            return;
        }
        
        // Enqueue separate CSS file
        wp_enqueue_style(
            'malisafi-agent-navigation',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/agent-navigation.css',
            array(),
            '1.0.0'
        );
        
        // Add body class for agent view
        add_filter('admin_body_class', function($classes) {
            return $classes . ' malisafi-agent-view';
        });
        
        wp_add_inline_style('malisafi-agent-navigation', '
            /* Hide WordPress admin bar */
            #wpadminbar {
                display: none !important;
            }
            
            html.wp-toolbar {
                padding-top: 0 !important;
            }
            
            /* Custom Agent Navigation Bar */
            .malisafi-agent-navigation {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: #ffffff;
                padding: 0;
                margin: 0 0 20px -20px;
                margin-left: -20px;
                margin-right: -20px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                position: sticky;
                top: 0;
                z-index: 9999;
            }
            
            .agent-nav-container {
                display: flex;
                align-items: center;
                justify-content: space-between;
                max-width: 100%;
                padding: 0 30px;
                gap: 20px;
            }
            
            .agent-nav-brand {
                display: flex;
                align-items: center;
                gap: 10px;
                font-weight: 600;
                font-size: 18px;
                padding: 15px 0;
            }
            
            .agent-nav-logo {
                font-size: 24px;
            }
            
            .agent-nav-menu {
                display: flex;
                align-items: center;
                gap: 5px;
                flex: 1;
                justify-content: center;
            }
            
            .agent-nav-item {
                color: rgba(255, 255, 255, 0.9);
                text-decoration: none;
                padding: 12px 20px;
                border-radius: 6px;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 14px;
                font-weight: 500;
                white-space: nowrap;
            }
            
            .agent-nav-item:hover {
                background: rgba(255, 255, 255, 0.2);
                color: #ffffff;
                transform: translateY(-1px);
            }
            
            .agent-nav-item.active {
                background: rgba(255, 255, 255, 0.25);
                color: #ffffff;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .agent-nav-item .dashicons {
                font-size: 18px;
                width: 18px;
                height: 18px;
            }
            
            .agent-nav-user {
                display: flex;
                align-items: center;
                gap: 15px;
            }
            
            .agent-nav-username {
                color: rgba(255, 255, 255, 0.95);
                font-size: 14px;
                display: flex;
                align-items: center;
                gap: 6px;
            }
            
            .agent-nav-logout {
                color: rgba(255, 255, 255, 0.9);
                text-decoration: none;
                padding: 8px 16px;
                border-radius: 6px;
                background: rgba(255, 255, 255, 0.15);
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                gap: 6px;
                font-size: 14px;
                font-weight: 500;
            }
            
            .agent-nav-logout:hover {
                background: rgba(255, 255, 255, 0.25);
                color: #ffffff;
            }
            
            /* Adjust main content for custom nav */
            #wpbody-content {
                padding-top: 0;
            }
            
            .wrap {
                margin-top: 0;
            }
            
            /* Hide WordPress welcome panel for agents */
            .welcome-panel {
                display: none;
            }
            
            /* Mobile responsiveness */
            @media (max-width: 1200px) {
                .agent-nav-menu {
                    gap: 2px;
                }
                
                .agent-nav-item {
                    padding: 10px 15px;
                    font-size: 13px;
                }
            }
            
            @media (max-width: 992px) {
                .agent-nav-container {
                    flex-wrap: wrap;
                    padding: 10px 20px;
                }
                
                .agent-nav-menu {
                    width: 100%;
                    justify-content: flex-start;
                    overflow-x: auto;
                    padding: 5px 0;
                }
                
                .agent-nav-item {
                    padding: 8px 12px;
                    font-size: 12px;
                }
                
                .agent-nav-user {
                    margin-left: auto;
                }
            }
            
            @media (max-width: 600px) {
                .agent-nav-title {
                    display: none;
                }
                
                .agent-nav-username {
                    display: none;
                }
                
                .agent-nav-item span:not(.dashicons) {
                    display: none;
                }
                
                .agent-nav-logout span:not(.dashicons) {
                    display: none;
                }
            }
        ');
    }
}
