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
}
