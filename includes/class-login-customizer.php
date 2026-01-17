<?php
/**
 * Login Page Customizer
 *
 * Customizes the WordPress login page with Malisafi branding
 *
 * @package MalisafiMLS
 */

namespace MalisafiMLS;

if (!defined('ABSPATH')) {
    exit;
}

class Login_Customizer {
    
    /**
     * Initialize login customization
     */
    public static function init() {
        add_action('login_enqueue_scripts', [__CLASS__, 'custom_login_styles']);
        add_filter('login_headerurl', [__CLASS__, 'custom_login_url']);
        add_filter('login_headertext', [__CLASS__, 'custom_login_title']);
        add_action('login_head', [__CLASS__, 'add_favicon']);
        add_filter('login_errors', [__CLASS__, 'custom_login_errors']);
        add_action('login_footer', [__CLASS__, 'add_custom_footer']);
        add_action('login_head', [__CLASS__, 'inject_logo_css']);
        add_action('login_footer', [__CLASS__, 'add_register_link']);
        add_filter('show_admin_bar', [__CLASS__, 'filter_admin_bar']);
        add_filter('login_redirect', [__CLASS__, 'redirect_to_dashboard'], 10, 3);
        add_action('admin_bar_menu', [__CLASS__, 'add_dashboard_link'], 999);
        
        // Block WordPress dashboard access for Malisafi users
        add_action('admin_init', [__CLASS__, 'block_wp_dashboard_access']);
        
        // Remove WordPress dashboard menu items for Malisafi users
        add_action('admin_menu', [__CLASS__, 'remove_wp_menu_items'], 999);
        
        // Hide WordPress dashboard widgets for Malisafi users
        add_action('wp_dashboard_setup', [__CLASS__, 'remove_dashboard_widgets'], 999);
    }
    
    /**
     * Remove WordPress menu items for Malisafi users
     */
    public static function remove_wp_menu_items() {
        $user = wp_get_current_user();
        
        // Skip if admin or moderator
        if (in_array('administrator', $user->roles) || in_array('malisafi_moderator', $user->roles)) {
            return;
        }
        
        // Check if user has Malisafi role
        $malisafi_roles = array('malisafi_agent_basic', 'malisafi_agent_premium', 'malisafi_owner', 'malisafi_developer', 'malisafi_client');
        $has_malisafi_role = array_intersect($malisafi_roles, $user->roles);
        
        if ($has_malisafi_role) {
            // Remove WordPress core menu items
            remove_menu_page('index.php');                  // Dashboard
            remove_menu_page('edit.php');                   // Posts
            // Keep upload.php (Media) for property images
            remove_menu_page('edit.php?post_type=page');    // Pages
            remove_menu_page('edit-comments.php');          // Comments
            remove_menu_page('themes.php');                 // Appearance
            remove_menu_page('plugins.php');                // Plugins
            remove_menu_page('users.php');                  // Users
            remove_menu_page('tools.php');                  // Tools
            remove_menu_page('options-general.php');        // Settings
            
            // Keep only Malisafi menus, Media, and Profile
        }
    }
    
    /**
     * Remove WordPress dashboard widgets for Malisafi users
     */
    public static function remove_dashboard_widgets() {
        $user = wp_get_current_user();
        
        // Skip if admin or moderator
        if (in_array('administrator', $user->roles) || in_array('malisafi_moderator', $user->roles)) {
            return;
        }
        
        // Check if user has Malisafi role
        $malisafi_roles = array('malisafi_agent_basic', 'malisafi_agent_premium', 'malisafi_owner', 'malisafi_developer', 'malisafi_client');
        $has_malisafi_role = array_intersect($malisafi_roles, $user->roles);
        
        if ($has_malisafi_role) {
            // Remove all default WordPress dashboard widgets
            remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
            remove_meta_box('dashboard_activity', 'dashboard', 'normal');
            remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
            remove_meta_box('dashboard_primary', 'dashboard', 'side');
        }
    }
    
    /**
     * Block WordPress dashboard access for Malisafi users and redirect to appropriate dashboard
     */
    public static function block_wp_dashboard_access() {
        $user = wp_get_current_user();
        
        // Skip if no user or user is admin/moderator
        if (!$user || !isset($user->roles)) {
            return;
        }
        
        // Allow admins and moderators to access WP dashboard
        if (in_array('administrator', $user->roles) || in_array('malisafi_moderator', $user->roles)) {
            return;
        }
        
        // Check if user has a Malisafi role (but not admin/moderator)
        $malisafi_roles = array('malisafi_agent_basic', 'malisafi_agent_premium', 'malisafi_owner', 'malisafi_developer', 'malisafi_client', 'malisafi_hunter');
        $has_malisafi_role = array_intersect($malisafi_roles, $user->roles);
        
        if (!$has_malisafi_role) {
            return;
        }
        
        // Allow AJAX requests
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }
        
        // Allow specific wp-admin pages for Malisafi roles to avoid redirect loops
        // Whitelist: Agent dashboard and property management pages, media, profile, admin-post
        $allowed_pages = array(
            'malisafi-agent-dashboard',
            'malisafi-properties'
        );
        
        $pagenow = isset($GLOBALS['pagenow']) ? $GLOBALS['pagenow'] : '';
        $page_param = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        
        // Allow admin.php with specific Malisafi pages
        if ($pagenow === 'admin.php' && in_array($page_param, $allowed_pages, true)) {
            return; // allow access
        }
        
        // Allow media library and profile for uploads/profile edits
        if ($pagenow === 'upload.php' || $pagenow === 'media-new.php' || $pagenow === 'profile.php' || $pagenow === 'admin-post.php') {
            return; // allow access
        }
        
        // Otherwise, redirect to appropriate FRONTEND dashboard
        $redirect_url = '';
        if (in_array('malisafi_agent_basic', $user->roles) || in_array('malisafi_agent_premium', $user->roles)) {
            $redirect_url = self::get_page_url('agent_dashboard');
        } elseif (in_array('malisafi_owner', $user->roles)) {
            $redirect_url = self::get_page_url('owner_dashboard');
        } elseif (in_array('malisafi_developer', $user->roles)) {
            $redirect_url = self::get_page_url('developer_dashboard');
        } elseif (in_array('malisafi_client', $user->roles)) {
            $redirect_url = self::get_page_url('client_dashboard');
        } elseif (in_array('malisafi_hunter', $user->roles)) {
            $redirect_url = self::get_page_url('hunter_dashboard');
        }
        if (empty($redirect_url)) {
            $redirect_url = home_url('/');
        }
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Get page URL helper
     */
    private static function get_page_url($key) {
        if (class_exists('MalisafiMLS\Page_Manager')) {
            return \MalisafiMLS\Page_Manager::get_page_url($key);
        }
        // Fallback
        return home_url('/dashboard/');
    }
    
    /**
     * Add custom styles to login page
     */
    public static function custom_login_styles() {
        ?>
        <style type="text/css">
            /* Malisafi Login Page Styles */
            :root {
                --malisafi-dark: #1a1a1a;
                --malisafi-grey: #4a4a4a;
                --malisafi-light-grey: #f5f5f5;
                --malisafi-white: #ffffff;
            }
            
            body.login {
                background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 50%, #1a1a1a 100%);
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            }
            
            /* Logo Container */
            #login h1 a {
                background-image: none !important;
                width: 100%;
                height: 100px;
                margin-bottom: 25px;
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            #login h1 a::before {
                content: 'MALISAFI';
                font-size: 48px;
                font-weight: 900;
                letter-spacing: 2px;
                color: var(--malisafi-white);
                text-shadow: 0 4px 12px rgba(0,0,0,0.3);
            }
            
            #login h1 a::after {
                content: 'MLS';
                position: absolute;
                bottom: 5px;
                font-size: 12px;
                font-weight: 600;
                letter-spacing: 4px;
                color: var(--malisafi-light-grey);
                opacity: 0.8;
            }
            
            /* Login Form */
            #loginform {
                background: var(--malisafi-white);
                border: none;
                border-radius: 16px;
                padding: 40px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.5);
                backdrop-filter: blur(10px);
            }
            
            #loginform label {
                color: var(--malisafi-dark);
                font-weight: 600;
                font-size: 14px;
                margin-bottom: 8px;
            }
            
            #loginform input[type="text"],
            #loginform input[type="password"] {
                background: var(--malisafi-light-grey);
                border: 2px solid transparent;
                border-radius: 8px;
                padding: 14px 16px;
                font-size: 16px;
                transition: all 0.3s ease;
                box-shadow: none;
            }
            
            #loginform input[type="text"]:focus,
            #loginform input[type="password"]:focus {
                background: var(--malisafi-white);
                border-color: var(--malisafi-dark);
                box-shadow: 0 0 0 4px rgba(26, 26, 26, 0.1);
                outline: none;
            }
            
            /* Submit Button */
            #wp-submit {
                background: var(--malisafi-dark);
                border: none;
                border-radius: 8px;
                padding: 14px 32px;
                font-size: 16px;
                font-weight: 600;
                letter-spacing: 0.5px;
                text-transform: uppercase;
                transition: all 0.3s ease;
                width: 100%;
                margin-top: 10px;
                box-shadow: 0 4px 12px rgba(26, 26, 26, 0.3);
            }
            
            #wp-submit:hover {
                background: #000000;
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(26, 26, 26, 0.4);
            }
            
            #wp-submit:active {
                transform: translateY(0);
            }
            
            /* Remember Me */
            .login .forgetmenot label {
                color: var(--malisafi-grey);
                font-weight: 500;
            }
            
            .login input[type="checkbox"] {
                border-color: var(--malisafi-grey);
            }
            
            /* Links */
            #login a,
            #nav a,
            #backtoblog a {
                color: var(--malisafi-white);
                text-decoration: none;
                transition: all 0.3s ease;
                font-weight: 500;
            }
            
            #login a:hover,
            #nav a:hover,
            #backtoblog a:hover {
                color: var(--malisafi-light-grey);
            }
            
            #nav,
            #backtoblog {
                text-align: center;
                margin-top: 20px;
            }
            
            /* Messages */
            .message,
            #login_error {
                border-left: 4px solid var(--malisafi-dark);
                background: var(--malisafi-white);
                border-radius: 8px;
                padding: 16px 20px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            }
            
            .message {
                border-left-color: #10b981;
            }
            
            #login_error {
                border-left-color: #dc2626;
            }
            
            /* Privacy Link */
            .privacy-policy-page-link {
                text-align: center;
                margin-top: 20px;
            }
            
            .privacy-policy-page-link a {
                color: var(--malisafi-light-grey);
                font-size: 13px;
            }
            
            /* Custom Footer */
            .malisafi-login-footer {
                text-align: center;
                margin-top: 30px;
                color: var(--malisafi-light-grey);
                font-size: 13px;
            }
            
            .malisafi-login-footer a {
                color: var(--malisafi-white);
                font-weight: 600;
            }
            
            /* Loading State */
            .login form.loading::after {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255,255,255,0.8);
                border-radius: 16px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            /* Responsive */
            @media screen and (max-width: 768px) {
                #login h1 a::before {
                    font-size: 36px;
                }
                
                #loginform {
                    padding: 30px 20px;
                }
            }
            
            /* Language Switcher */
            .language-switcher {
                text-align: center;
                margin-top: 20px;
            }
            
            .language-switcher select {
                background: rgba(255,255,255,0.1);
                border: 1px solid rgba(255,255,255,0.2);
                color: var(--malisafi-white);
                padding: 8px 16px;
                border-radius: 6px;
                font-size: 13px;
            }
        </style>
        <?php
    }
    
    /**
     * Change login logo URL
     */
    public static function custom_login_url() {
        return home_url();
    }
    
    /**
     * Change login logo title
     */
    public static function custom_login_title() {
        return get_bloginfo('name') . ' - ' . __('Powered by Malisafi MLS', 'malisafi-mls');
    }
    
    /**
     * Add favicon to login page
     */
    public static function add_favicon() {
        $favicon_url = MALISAFI_MLS_URL . 'assets/images/favicon.ico';
        if (file_exists(MALISAFI_MLS_PATH . 'assets/images/favicon.ico')) {
            echo '<link rel="icon" href="' . esc_url($favicon_url) . '" type="image/x-icon" />';
        }
    }
    
    /**
     * Customize login error messages
     */
    public static function custom_login_errors($error) {
        // Make error messages more user-friendly and secure
        if (strpos($error, 'incorrect') !== false) {
            return __('Invalid username or password. Please try again.', 'malisafi-mls');
        }
        return $error;
    }
    
    /**
     * Add custom footer to login page
     */
    public static function add_custom_footer() {
        ?>
        <div class="malisafi-login-footer">
            <p>
                <?php printf(
                    __('Powered by %s', 'malisafi-mls'),
                    '<a href="https://malisafi.com" target="_blank">Malisafi MLS</a>'
                ); ?>
            </p>
            <p style="margin-top: 10px; font-size: 12px; opacity: 0.8;">
                <?php _e('Professional Real Estate Management System', 'malisafi-mls'); ?>
            </p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            // Add loading state on form submit
            $('#loginform').on('submit', function() {
                $(this).addClass('loading');
            });
            
            // Add smooth animations
            $('#loginform').hide().fadeIn(600);
            $('#nav, #backtoblog').hide().fadeIn(800);
        });
        </script>
        <?php
    }
    
    /**
     * Redirect users to appropriate Malisafi dashboard after login
     */
    public static function redirect_to_dashboard($redirect_to, $request, $user) {
        // Check if user object exists and has roles
        if (!isset($user->roles) || !is_array($user->roles)) {
            return $redirect_to;
        }
        
        // Admins and moderators go to WP admin
        if (in_array('administrator', $user->roles) || in_array('malisafi_moderator', $user->roles)) {
            return admin_url();
        }
        
        // Determine dashboard URL based on user role - ALL go to FRONTEND
        $dashboard_url = '';
        
        if (in_array('malisafi_agent_basic', $user->roles) || in_array('malisafi_agent_premium', $user->roles)) {
            $dashboard_url = self::get_page_url('agent_dashboard');
        } elseif (in_array('malisafi_owner', $user->roles)) {
            $dashboard_url = self::get_page_url('owner_dashboard');
        } elseif (in_array('malisafi_developer', $user->roles)) {
            $dashboard_url = self::get_page_url('developer_dashboard');
        } elseif (in_array('malisafi_client', $user->roles)) {
            $dashboard_url = self::get_page_url('client_dashboard');
        } elseif (in_array('malisafi_hunter', $user->roles)) {
            $dashboard_url = self::get_page_url('hunter_dashboard');
        }
        
        // If we found a dashboard URL, use it; otherwise use default redirect
        return !empty($dashboard_url) ? $dashboard_url : $redirect_to;
    }
    
    /**
     * Add Malisafi Dashboard link to admin bar for logged-in users
     */
    public static function add_dashboard_link($wp_admin_bar) {
        if (!is_user_logged_in()) {
            return;
        }
        
        $current_user = wp_get_current_user();
        $dashboard_url = '';
        $dashboard_title = '';
        
        // Determine dashboard URL and title based on user role
        if (in_array('malisafi_agent_basic', $current_user->roles) || in_array('malisafi_agent_premium', $current_user->roles)) {
            // Agents use backend dashboard
            $dashboard_url = admin_url('admin.php?page=malisafi-agent-dashboard');
            $dashboard_title = __('Agent Dashboard', 'malisafi-mls');
        } elseif (in_array('malisafi_owner', $current_user->roles)) {
            $dashboard_url = Page_Manager::get_page_url('owner_dashboard');
            $dashboard_title = __('Owner Dashboard', 'malisafi-mls');
        } elseif (in_array('malisafi_developer', $current_user->roles)) {
            $dashboard_url = Page_Manager::get_page_url('developer_dashboard');
            $dashboard_title = __('Developer Dashboard', 'malisafi-mls');
        } elseif (in_array('malisafi_client', $current_user->roles)) {
            $dashboard_url = Page_Manager::get_page_url('client_dashboard');
            $dashboard_title = __('My Dashboard', 'malisafi-mls');
        }
        
        // Add the link if we have a valid dashboard URL
        if (!empty($dashboard_url)) {
            $wp_admin_bar->add_node([
                'id'    => 'malisafi-dashboard',
                'title' => '<span class="ab-icon dashicons dashicons-admin-home"></span>' . $dashboard_title,
                'href'  => $dashboard_url,
                'meta'  => [
                    'class' => 'malisafi-dashboard-link'
                ]
            ]);
        }
    }

    /**
     * Dynamically inject site logo into login page
     */
    public static function inject_logo_css() {
        $logo_url = self::get_site_logo_url();
        if (!$logo_url) {
            return;
        }
        echo '<style>#login h1 a{background-image:url(' . esc_url($logo_url) . ') !important;background-size:contain;background-repeat:no-repeat;text-indent:-9999px;height:100px;}#login h1 a::before,#login h1 a::after{content:none !important;}</style>';
    }

    /**
     * Get the best available site logo URL
     */
    private static function get_site_logo_url() {
        // Prefer theme custom logo
        $custom_logo_id = function_exists('get_theme_mod') ? get_theme_mod('custom_logo') : 0;
        if ($custom_logo_id) {
            $image = wp_get_attachment_image_src($custom_logo_id, 'full');
            if (!empty($image[0])) {
                return $image[0];
            }
        }
        // Fallback: site icon
        if (function_exists('has_site_icon') && has_site_icon()) {
            $icon = get_site_icon_url(128);
            if ($icon) {
                return $icon;
            }
        }
        return '';
    }

    /**
     * Add Register link below the login form
     */
    public static function add_register_link() {
        $register_url = '';
        if (class_exists('MalisafiMLS\\Page_Manager')) {
            $register_url = \MalisafiMLS\Page_Manager::get_page_url('register');
        }
        if (empty($register_url) && get_option('users_can_register')) {
            $register_url = wp_registration_url();
        }
        if (!empty($register_url)) {
            echo '<div class="malisafi-login-footer" style="margin-top:15px">'
                . '<a class="button" href="' . esc_url($register_url) . '">' . esc_html__('Create an account', 'malisafi-mls') . '</a>'
                . '</div>';
        }
    }

    /**
     * Hide WordPress admin bar for Malisafi roles (non-admin/moderator)
     */
    public static function filter_admin_bar($show) {
        $user = wp_get_current_user();
        if (!$user || !isset($user->roles)) {
            return $show;
        }
        if (in_array('administrator', $user->roles) || in_array('malisafi_moderator', $user->roles)) {
            return $show;
        }
        $roles = array('malisafi_agent_basic','malisafi_agent_premium','malisafi_owner','malisafi_developer','malisafi_client');
        return array_intersect($roles, $user->roles) ? false : $show;
    }
}
