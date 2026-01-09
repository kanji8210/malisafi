<?php
/**
 * Admin area functionality
 *
 * @package MalisafiMLS
 */

namespace MalisafiMLS\Admin;

/**
 * Admin class
 */
class Admin {
    
    /**
     * Enqueue admin styles
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'malisafi-mls-admin',
            MALISAFI_MLS_URL . 'assets/css/admin.css',
            array(),
            MALISAFI_MLS_VERSION,
            'all'
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'malisafi-mls-admin',
            MALISAFI_MLS_URL . 'assets/js/admin.js',
            array('jquery'),
            MALISAFI_MLS_VERSION,
            false
        );
        
        wp_localize_script('malisafi-mls-admin', 'malisafiMLS', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('malisafi_mls_nonce'),
        ));
    }
    
    /**
     * Add plugin admin menu (Legacy - kept for compatibility)
     * Note: Main dashboard is now handled by Malisafi_Admin_Dashboard class
     */
    public function add_plugin_admin_menu() {
        // This method is kept for potential future use or backwards compatibility
        // The main Malisafi dashboard is now managed by class-admin-dashboard.php
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        // General settings
        register_setting('malisafi_mls_general', 'malisafi_mls_currency');
        register_setting('malisafi_mls_general', 'malisafi_mls_currency_symbol');
        register_setting('malisafi_mls_general', 'malisafi_mls_currency_position');
        register_setting('malisafi_mls_general', 'malisafi_mls_thousand_separator');
        register_setting('malisafi_mls_general', 'malisafi_mls_decimal_separator');
        register_setting('malisafi_mls_general', 'malisafi_mls_price_decimals');
        register_setting('malisafi_mls_general', 'malisafi_mls_area_unit');
        register_setting('malisafi_mls_general', 'malisafi_mls_properties_per_page');
        
        // Feature settings
        register_setting('malisafi_mls_features', 'malisafi_mls_enable_front_end_submission');
        register_setting('malisafi_mls_features', 'malisafi_mls_google_maps_api_key');
        register_setting('malisafi_mls_features', 'malisafi_mls_enable_favorite_properties');
        register_setting('malisafi_mls_features', 'malisafi_mls_enable_property_comparison');
        register_setting('malisafi_mls_features', 'malisafi_mls_enable_agent_profiles');

        // Pages required by the plugin — register them in the Features option
        // group so the single settings form can save all values at once.
        register_setting('malisafi_mls_features', 'malisafi_mls_submission_page');
        register_setting('malisafi_mls_features', 'malisafi_mls_favorites_page');
        register_setting('malisafi_mls_features', 'malisafi_mls_agent_profile_page');
        
        // Add settings sections
        add_settings_section(
            'malisafi_mls_general_section',
            __('General Settings', 'malisafi-mls'),
            array($this, 'general_section_callback'),
            'malisafi_mls_general'
        );
        
        add_settings_section(
            'malisafi_mls_features_section',
            __('Feature Settings', 'malisafi-mls'),
            array($this, 'features_section_callback'),
            'malisafi_mls_features'
        );

        // Pages section — render as part of the Features page so one form can
        // submit both feature flags and page selections.
        add_settings_section(
            'malisafi_mls_pages_section',
            __('Required Pages', 'malisafi-mls'),
            array($this, 'pages_section_callback'),
            'malisafi_mls_features'
        );

        // Ensure settings API checks our custom capability instead of the
        // default 'manage_options' for these groups so roles with
        // 'manage_malisafi_settings' or dedicated page capability can save.
        add_filter('option_page_capability_malisafi_mls_general', array($this, 'option_page_capability'));
        // Features: allow either full settings managers or the more limited
        // pages manager to save feature flags. We pick the capability that the
        // current user already has so administrators keep access.
        add_filter('option_page_capability_malisafi_mls_features', array($this, 'option_page_capability_features'));

        // Backwards compatibility: if some code or cached form posts the
        // older 'malisafi_mls_pages' option_page we must allow admins to
        // save. Re-register the legacy option group and map its capability
        // to the main settings capability so options.php accepts it.
        register_setting('malisafi_mls_pages', 'malisafi_mls_submission_page');
        register_setting('malisafi_mls_pages', 'malisafi_mls_favorites_page');
        register_setting('malisafi_mls_pages', 'malisafi_mls_agent_profile_page');
        add_filter('option_page_capability_malisafi_mls_pages', array($this, 'option_page_capability'));
    }
    
    /**
     * General section callback
     */
    public function general_section_callback() {
        echo '<p>' . __('Configure general plugin settings.', 'malisafi-mls') . '</p>';
    }
    
    /**
     * Features section callback
     */
    public function features_section_callback() {
        echo '<p>' . __('Enable or disable plugin features.', 'malisafi-mls') . '</p>';
    }

    /**
     * Pages section callback
     */
    public function pages_section_callback() {
        echo '<p>' . __('Select pages used by the plugin (submission, favorites, agent profile).', 'malisafi-mls') . '</p>';
    }

    /**
     * Return the capability required to save the malisafi option groups.
     *
     * @param string $capability Current capability (ignored)
     * @return string Capability required
     */
    public function option_page_capability($capability) {
        return 'manage_malisafi_settings';
    }

    /**
     * Return capability for pages option group. Allows a more limited cap if
     * desired in the future.
     */
    public function option_page_capability_pages($capability) {
        // Use a dedicated capability name so roles can be granted only page
        // management rights without exposing full settings controls.
        return 'manage_malisafi_pages';
    }

    /**
     * Determine capability for Features option group.
     * Allow administrators (manage_malisafi_settings) or users with the
     * limited pages capability (manage_malisafi_pages) to save.
     *
     * @param string $capability
     * @return string
     */
    public function option_page_capability_features($capability) {
        if (function_exists('current_user_can') && current_user_can('manage_malisafi_settings')) {
            return 'manage_malisafi_settings';
        }

        return 'manage_malisafi_pages';
    }
}