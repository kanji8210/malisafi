<?php
/**
 * Dashboard Shortcodes Class
 *
 * Handles all dashboard-related shortcodes for different user roles
 *
 * @package MalisafiMLS
 * @since 1.0.0
 */

namespace MalisafiMLS;

if (!defined('ABSPATH')) {
    exit;
}

class Dashboard_Shortcodes {
    
    /**
     * Initialize the shortcodes
     */
    public static function init() {
        // Client Dashboard Shortcodes
        add_shortcode('malisafi_client_dashboard', [__CLASS__, 'client_dashboard']);
        add_shortcode('malisafi_favorites', [__CLASS__, 'client_favorites']);
        add_shortcode('malisafi_saved_searches', [__CLASS__, 'client_saved_searches']);
        add_shortcode('malisafi_client_inquiries', [__CLASS__, 'client_inquiries']);
        
        // Agent Dashboard Shortcodes
        add_shortcode('malisafi_agent_dashboard', [__CLASS__, 'agent_dashboard']);
        add_shortcode('malisafi_agent_properties', [__CLASS__, 'agent_properties']);
        add_shortcode('malisafi_agent_leads', [__CLASS__, 'agent_leads']);
        add_shortcode('malisafi_agent_profile', [__CLASS__, 'agent_profile']);
        
        
        // Public Agent Profile View
        add_shortcode('malisafi_agent_profile_view', [__CLASS__, 'agent_profile_public']);
        // Owner Dashboard Shortcodes
        add_shortcode('malisafi_owner_dashboard', [__CLASS__, 'owner_dashboard']);
        add_shortcode('malisafi_owner_properties', [__CLASS__, 'owner_properties']);
        add_shortcode('malisafi_owner_inquiries', [__CLASS__, 'owner_inquiries']);
        
        // Developer Dashboard Shortcodes
        add_shortcode('malisafi_developer_dashboard', [__CLASS__, 'developer_dashboard']);
        add_shortcode('malisafi_developer_projects', [__CLASS__, 'developer_projects']);
        add_shortcode('malisafi_developer_analytics', [__CLASS__, 'developer_analytics']);
        
        // Common Shortcodes
        add_shortcode('malisafi_property_submit', [__CLASS__, 'property_submit_form']);
        add_shortcode('malisafi_agent_add_property', [__CLASS__, 'agent_add_property']);
        add_shortcode('malisafi_login', [__CLASS__, 'login_form']);
        add_shortcode('malisafi_register', [__CLASS__, 'register_form']);
        add_shortcode('malisafi_account', [__CLASS__, 'account_page']);
        
        // AJAX handlers
        add_action('wp_ajax_malisafi_custom_login', [__CLASS__, 'ajax_custom_login']);
        add_action('wp_ajax_nopriv_malisafi_custom_login', [__CLASS__, 'ajax_custom_login']);
    }
    
    /**
     * Check if user is logged in, if not show login message
     */
    private static function require_login($required_role = null) {
        if (!is_user_logged_in()) {
            $register_url = Page_Manager::get_page_url('register');
            return '<div class="malisafi-login-required">
                <p>' . __('You must be logged in to view this page.', 'malisafi-mls') . '</p>
                <a href="' . wp_login_url(get_permalink()) . '" class="button">' . __('Login', 'malisafi-mls') . '</a>
                <a href="' . esc_url($register_url . '?type=agent') . '" class="button button-secondary">' . __('Register as Agent', 'malisafi-mls') . '</a>
            </div>';
        }
        
        if ($required_role && !current_user_can($required_role)) {
            return '<div class="malisafi-access-denied">
                <p>' . __('You do not have permission to access this page.', 'malisafi-mls') . '</p>
            </div>';
        }
        
        return false;
    }
    
    /**
     * Client Dashboard
     */
    public static function client_dashboard($atts) {
        $login_check = self::require_login();
        if ($login_check) return $login_check;
        
        $current_user = wp_get_current_user();
        
        ob_start();
        ?>
        <div class="malisafi-client-dashboard">
            <div class="dashboard-header">
                <h1><?php printf(__('Welcome, %s', 'malisafi-mls'), $current_user->display_name); ?></h1>
                <p><?php _e('Manage your property searches, favorites, and inquiries.', 'malisafi-mls'); ?></p>
            </div>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3><?php echo self::get_favorites_count($current_user->ID); ?></h3>
                    <p><?php _e('Favorite Properties', 'malisafi-mls'); ?></p>
                    <a href="<?php echo Page_Manager::get_page_url('client_favorites'); ?>"><?php _e('View All', 'malisafi-mls'); ?></a>
                </div>
                
                <div class="stat-card">
                    <h3><?php echo self::get_saved_searches_count($current_user->ID); ?></h3>
                    <p><?php _e('Saved Searches', 'malisafi-mls'); ?></p>
                    <a href="<?php echo Page_Manager::get_page_url('client_searches'); ?>"><?php _e('View All', 'malisafi-mls'); ?></a>
                </div>
                
                <div class="stat-card">
                    <h3><?php echo self::get_inquiries_count($current_user->ID); ?></h3>
                    <p><?php _e('My Inquiries', 'malisafi-mls'); ?></p>
                    <a href="<?php echo Page_Manager::get_page_url('client_inquiries'); ?>"><?php _e('View All', 'malisafi-mls'); ?></a>
                </div>
            </div>
            
            <div class="dashboard-quick-actions">
                <h2><?php _e('Quick Actions', 'malisafi-mls'); ?></h2>
                <div class="actions-grid">
                    <a href="<?php echo Page_Manager::get_page_url('property_search'); ?>" class="action-button">
                        <span class="dashicons dashicons-search"></span>
                        <?php _e('Search Properties', 'malisafi-mls'); ?>
                    </a>
                    <a href="<?php echo Page_Manager::get_page_url('client_favorites'); ?>" class="action-button">
                        <span class="dashicons dashicons-heart"></span>
                        <?php _e('My Favorites', 'malisafi-mls'); ?>
                    </a>
                    <a href="<?php echo Page_Manager::get_page_url('agents'); ?>" class="action-button">
                        <span class="dashicons dashicons-groups"></span>
                        <?php _e('Find an Agent', 'malisafi-mls'); ?>
                    </a>
                </div>
            </div>
            
            <div class="dashboard-recent-activity">
                <h2><?php _e('Recent Activity', 'malisafi-mls'); ?></h2>
                <?php echo self::get_recent_viewed_properties($current_user->ID); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Client Favorites
     */
    public static function client_favorites($atts) {
        $login_check = self::require_login();
        if ($login_check) return $login_check;
        
        $current_user = wp_get_current_user();
        $favorites = get_user_meta($current_user->ID, 'malisafi_favorites', true) ?: [];
        
        ob_start();
        ?>
        <div class="malisafi-favorites">
            <h1><?php _e('My Favorite Properties', 'malisafi-mls'); ?></h1>
            
            <?php if (empty($favorites)): ?>
                <div class="no-favorites">
                    <p><?php _e('You haven\'t added any properties to your favorites yet.', 'malisafi-mls'); ?></p>
                    <a href="<?php echo Page_Manager::get_page_url('property_search'); ?>" class="button">
                        <?php _e('Browse Properties', 'malisafi-mls'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="properties-grid">
                    <?php
                    $args = [
                        'post_type' => 'malisafi_property',
                        'post__in' => $favorites,
                        'posts_per_page' => 50, // Limit to 50 favorites at a time for performance
                        'orderby' => 'post__in'
                    ];
                    
                    $query = new \WP_Query($args);
                    
                    if ($query->have_posts()) {
                        while ($query->have_posts()) {
                            $query->the_post();
                            echo self::render_property_card(get_the_ID());
                        }
                        wp_reset_postdata();
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Client Saved Searches
     */
    public static function client_saved_searches($atts) {
        $login_check = self::require_login();
        if ($login_check) return $login_check;
        
        $current_user = wp_get_current_user();
        $saved_searches = get_user_meta($current_user->ID, 'malisafi_saved_searches', true) ?: [];
        
        ob_start();
        ?>
        <div class="malisafi-saved-searches">
            <h1><?php _e('My Saved Searches', 'malisafi-mls'); ?></h1>
            
            <?php if (empty($saved_searches)): ?>
                <div class="no-searches">
                    <p><?php _e('You haven\'t saved any searches yet.', 'malisafi-mls'); ?></p>
                    <a href="<?php echo Page_Manager::get_page_url('property_search'); ?>" class="button">
                        <?php _e('Start Searching', 'malisafi-mls'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="searches-list">
                    <?php foreach ($saved_searches as $index => $search): ?>
                        <div class="search-item">
                            <h3><?php echo esc_html($search['name'] ?? __('Unnamed Search', 'malisafi-mls')); ?></h3>
                            <div class="search-criteria">
                                <?php echo self::format_search_criteria($search); ?>
                            </div>
                            <div class="search-actions">
                                <a href="<?php echo self::build_search_url($search); ?>" class="button">
                                    <?php _e('Run Search', 'malisafi-mls'); ?>
                                </a>
                                <button class="button delete-search" data-index="<?php echo $index; ?>">
                                    <?php _e('Delete', 'malisafi-mls'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Client Inquiries
     */
    public static function client_inquiries($atts) {
        $login_check = self::require_login();
        if ($login_check) return $login_check;
        
        global $wpdb;
        $current_user = wp_get_current_user();
        
        // Get inquiries from database
        $table_name = $wpdb->prefix . 'mf_inquiries';
        $inquiries = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE client_id = %d ORDER BY created_at DESC",
            $current_user->ID
        ));
        
        ob_start();
        ?>
        <div class="malisafi-inquiries">
            <h1><?php _e('My Inquiries', 'malisafi-mls'); ?></h1>
            
            <?php if (empty($inquiries)): ?>
                <div class="no-inquiries">
                    <p><?php _e('You haven\'t made any inquiries yet.', 'malisafi-mls'); ?></p>
                </div>
            <?php else: ?>
                <table class="inquiries-table">
                    <thead>
                        <tr>
                            <th><?php _e('Property', 'malisafi-mls'); ?></th>
                            <th><?php _e('Subject', 'malisafi-mls'); ?></th>
                            <th><?php _e('Status', 'malisafi-mls'); ?></th>
                            <th><?php _e('Date', 'malisafi-mls'); ?></th>
                            <th><?php _e('Actions', 'malisafi-mls'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inquiries as $inquiry): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo get_permalink($inquiry->property_id); ?>">
                                        <?php echo get_the_title($inquiry->property_id); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($inquiry->subject); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo esc_attr($inquiry->status); ?>">
                                        <?php echo esc_html(ucfirst($inquiry->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($inquiry->created_at)); ?></td>
                                <td>
                                    <button class="button view-inquiry" data-id="<?php echo $inquiry->id; ?>">
                                        <?php _e('View', 'malisafi-mls'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Agent Dashboard - Modern Frontend Dashboard
     */
    public static function agent_dashboard($atts) {
        // Check if user is logged in first
        if (!is_user_logged_in()) {
            return self::require_login();
        }
        
        // Check if user has agent role
        $user = wp_get_current_user();
        $is_agent = in_array('malisafi_agent_basic', $user->roles) || in_array('malisafi_agent_premium', $user->roles);
        
        if (!$is_agent) {
            return '<div class="malisafi-access-denied"><p>' . __('You do not have permission to access the agent dashboard.', 'malisafi-mls') . '</p></div>';
        }
        
        // Enqueue dashboard CSS
        wp_enqueue_style(
            'malisafi-agent-dashboard-clean',
            MALISAFI_MLS_URL . 'assets/css/agent-dashboard-clean.css',
            array('malisafi-mls-variables', 'dashicons'),
            MALISAFI_MLS_VERSION
        );
        
        wp_enqueue_script(
            'malisafi-agent-dashboard',
            MALISAFI_MLS_URL . 'assets/js/agent-dashboard-modern.js',
            array('jquery'),
            MALISAFI_MLS_VERSION,
            true
        );
        
        $current_user = wp_get_current_user();
        
        // Get stats
        global $wpdb;
        $properties_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_author = %d AND post_type = 'malisafi_property' AND post_status = 'publish'",
            $current_user->ID
        ));
        
        $pending_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_author = %d AND post_type = 'malisafi_property' AND post_status = 'pending'",
            $current_user->ID
        ));
        
        $views_count = get_user_meta($current_user->ID, 'total_property_views', true) ?: 0;
        $leads_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mf_inquiries WHERE agent_id = %d",
            $current_user->ID
        )) ?: 0;
        
        ob_start();
        ?>
        <div class="malisafi-agent-dashboard-clean">
            <div class="dashboard-container">
                <!-- Header -->
                <header class="dashboard-header">
                    <div class="header-content">
                        <div class="header-left">
                            <h1><?php printf(__('Welcome back, %s', 'malisafi-mls'), esc_html($current_user->display_name)); ?></h1>
                            <p><?php _e('Manage your properties and leads from your dashboard', 'malisafi-mls'); ?></p>
                        </div>
                        <div class="header-right">
                            <a href="<?php echo Page_Manager::get_page_url('agent_add_property'); ?>" class="btn btn-primary">
                                <span class="dashicons dashicons-plus-alt"></span>
                                <?php _e('Add New Property', 'malisafi-mls'); ?>
                            </a>
                        </div>
                    </div>
                </header>
                
                <!-- Stats Cards -->
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <span class="dashicons dashicons-admin-home"></span>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo number_format($properties_count); ?></div>
                            <div class="stat-label"><?php _e('Published Properties', 'malisafi-mls'); ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-pending">
                            <span class="dashicons dashicons-clock"></span>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo number_format($pending_count); ?></div>
                            <div class="stat-label"><?php _e('Pending Approval', 'malisafi-mls'); ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-views">
                            <span class="dashicons dashicons-visibility"></span>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo number_format($views_count); ?></div>
                            <div class="stat-label"><?php _e('Total Views', 'malisafi-mls'); ?></div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-leads">
                            <span class="dashicons dashicons-email"></span>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo number_format($leads_count); ?></div>
                            <div class="stat-label"><?php _e('Inquiries', 'malisafi-mls'); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h2><?php _e('Quick Actions', 'malisafi-mls'); ?></h2>
                    <div class="actions-grid">
                        <a href="<?php echo Page_Manager::get_page_url('agent_properties'); ?>" class="action-card">
                            <span class="dashicons dashicons-admin-home"></span>
                            <span><?php _e('My Properties', 'malisafi-mls'); ?></span>
                        </a>
                        <a href="<?php echo Page_Manager::get_page_url('agent_leads'); ?>" class="action-card">
                            <span class="dashicons dashicons-email"></span>
                            <span><?php _e('View Leads', 'malisafi-mls'); ?></span>
                        </a>
                        <a href="<?php echo Page_Manager::get_page_url('agent_profile'); ?>" class="action-card">
                            <span class="dashicons dashicons-businessman"></span>
                            <span><?php _e('My Profile', 'malisafi-mls'); ?></span>
                        </a>
                        <a href="<?php echo Page_Manager::get_page_url('account'); ?>" class="action-card">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <span><?php _e('Settings', 'malisafi-mls'); ?></span>
                        </a>
                    </div>
                </div>
                
                <!-- Recent Properties -->
                <div class="recent-section">
                    <div class="section-header">
                        <h2><?php _e('Recent Properties', 'malisafi-mls'); ?></h2>
                        <a href="<?php echo Page_Manager::get_page_url('agent_properties'); ?>"><?php _e('View All', 'malisafi-mls'); ?></a>
                    </div>
                    <div class="properties-list">
                        <?php
                        $recent_properties = new \WP_Query([
                            'post_type' => 'malisafi_property',
                            'author' => $current_user->ID,
                            'posts_per_page' => 5,
                            'post_status' => ['publish', 'pending']
                        ]);
                        
                        if ($recent_properties->have_posts()) {
                            while ($recent_properties->have_posts()) {
                                $recent_properties->the_post();
                                $price = get_post_meta(get_the_ID(), '_malisafi_price', true);
                                ?>
                                <div class="property-item">
                                    <div class="property-image">
                                        <?php if (has_post_thumbnail()) : ?>
                                            <?php the_post_thumbnail('thumbnail'); ?>
                                        <?php else : ?>
                                            <span class="dashicons dashicons-admin-home"></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="property-info">
                                        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                        <div class="property-price">KES <?php echo number_format($price); ?></div>
                                        <div class="property-status <?php echo get_post_status(); ?>">
                                            <?php echo ucfirst(get_post_status()); ?>
                                        </div>
                                    </div>
                                    <div class="property-actions">
                                        <a href="<?php echo get_edit_post_link(); ?>" class="btn-icon" title="<?php _e('Edit', 'malisafi-mls'); ?>">
                                            <span class="dashicons dashicons-edit"></span>
                                        </a>
                                    </div>
                                </div>
                                <?php
                            }
                            wp_reset_postdata();
                        } else {
                            echo '<p>' . __('No properties yet.', 'malisafi-mls') . '</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Agent Properties List
     */
    public static function agent_properties($atts) {
        // Redirect to dashboard as we now manage properties there
        return self::agent_dashboard($atts);
    }
    
    /**
     * Agent Add Property - Redirect to backend form
     */
    public static function agent_add_property($atts) {
        // Check if user is logged in first
        if (!is_user_logged_in()) {
            return self::require_login();
        }
        
        // Check if user has agent role
        $user = wp_get_current_user();
        $is_agent = in_array('malisafi_agent_basic', $user->roles) || in_array('malisafi_agent_premium', $user->roles);
        
        if (!$is_agent) {
            return '<div class="malisafi-access-denied">
                <p>' . __('You do not have permission to access this page.', 'malisafi-mls') . '</p>
                <p>' . __('This page is for agents only.', 'malisafi-mls') . '</p>
            </div>';
        }
        
        // Display the custom property submit form for agents
        return self::property_submit_form(array('role' => 'agent'));
    }
    
    /**
     * Agent Leads
     */
    public static function agent_leads($atts) {
        return self::agent_dashboard($atts);
    }
    
    /**
     * Agent Profile
     */
    public static function agent_profile($atts) {
        return self::agent_dashboard($atts);
    }
    
    /**
     * Owner Dashboard
     */
    public static function owner_dashboard($atts) {
        // Check if user is logged in first
        if (!is_user_logged_in()) {
            return self::require_login();
        }
        
        // Check if user has owner role
        $user = wp_get_current_user();
        $is_owner = in_array('malisafi_owner', $user->roles);
        
        if (!$is_owner) {
            return '<div class="malisafi-access-denied">
                <p>' . __('You do not have permission to access this page.', 'malisafi-mls') . '</p>
                <p>' . __('This page is for property owners only.', 'malisafi-mls') . '</p>
            </div>';
        }
        
        $current_user = wp_get_current_user();
        
        ob_start();
        ?>
        <div class="malisafi-owner-dashboard">
            <div class="dashboard-header">
                <h1><?php printf(__('Welcome, %s', 'malisafi-mls'), $current_user->display_name); ?></h1>
                <p><?php _e('Manage your properties and inquiries.', 'malisafi-mls'); ?></p>
            </div>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3><?php echo self::get_user_properties_count($current_user->ID); ?></h3>
                    <p><?php _e('My Properties', 'malisafi-mls'); ?></p>
                    <a href="<?php echo Page_Manager::get_page_url('owner_properties'); ?>"><?php _e('View All', 'malisafi-mls'); ?></a>
                </div>
                
                <div class="stat-card">
                    <h3><?php echo self::get_user_inquiries_count($current_user->ID); ?></h3>
                    <p><?php _e('Inquiries Received', 'malisafi-mls'); ?></p>
                    <a href="<?php echo Page_Manager::get_page_url('owner_inquiries'); ?>"><?php _e('View All', 'malisafi-mls'); ?></a>
                </div>
                
                <div class="stat-card">
                    <h3><?php echo self::get_total_views($current_user->ID); ?></h3>
                    <p><?php _e('Total Views', 'malisafi-mls'); ?></p>
                </div>
            </div>
            
            <div class="dashboard-quick-actions">
                <h2><?php _e('Quick Actions', 'malisafi-mls'); ?></h2>
                <div class="actions-grid">
                    <a href="<?php echo Page_Manager::get_page_url('owner_add_property'); ?>" class="action-button primary">
                        <span class="dashicons dashicons-plus"></span>
                        <?php _e('Add New Property', 'malisafi-mls'); ?>
                    </a>
                    <a href="<?php echo Page_Manager::get_page_url('owner_properties'); ?>" class="action-button">
                        <span class="dashicons dashicons-admin-home"></span>
                        <?php _e('My Properties', 'malisafi-mls'); ?>
                    </a>
                    <a href="<?php echo Page_Manager::get_page_url('owner_inquiries'); ?>" class="action-button">
                        <span class="dashicons dashicons-email"></span>
                        <?php _e('View Inquiries', 'malisafi-mls'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Owner Properties
     */
    public static function owner_properties($atts) {
        // Check if user is logged in first
        if (!is_user_logged_in()) {
            return self::require_login();
        }
        
        // Check if user has owner role
        $user = wp_get_current_user();
        $is_owner = in_array('malisafi_owner', $user->roles);
        
        if (!$is_owner) {
            return '<div class="malisafi-access-denied">
                <p>' . __('You do not have permission to access this page.', 'malisafi-mls') . '</p>
            </div>';
        }
        
        $current_user = wp_get_current_user();
        
        $args = [
            'post_type' => 'malisafi_property',
            'author' => $current_user->ID,
            'posts_per_page' => 100, // Limit to 100 properties max for performance
            'post_status' => ['publish', 'pending', 'draft']
        ];
        
        $query = new \WP_Query($args);
        
        ob_start();
        ?>
        <div class="malisafi-owner-properties">
            <div class="page-header">
                <h1><?php _e('My Properties', 'malisafi-mls'); ?></h1>
                <a href="<?php echo Page_Manager::get_page_url('owner_add_property'); ?>" class="button button-primary">
                    <?php _e('Add New Property', 'malisafi-mls'); ?>
                </a>
            </div>
            
            <?php if ($query->have_posts()): ?>
                <table class="properties-table">
                    <thead>
                        <tr>
                            <th><?php _e('Property', 'malisafi-mls'); ?></th>
                            <th><?php _e('Status', 'malisafi-mls'); ?></th>
                            <th><?php _e('Price', 'malisafi-mls'); ?></th>
                            <th><?php _e('Views', 'malisafi-mls'); ?></th>
                            <th><?php _e('Actions', 'malisafi-mls'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($query->have_posts()): $query->the_post(); ?>
                            <tr>
                                <td>
                                    <?php if (has_post_thumbnail()): ?>
                                        <img src="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'); ?>" alt="" style="width:50px;height:50px;object-fit:cover;margin-right:10px;">
                                    <?php endif; ?>
                                    <?php the_title(); ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo get_post_status(); ?>">
                                        <?php echo ucfirst(get_post_status()); ?>
                                    </span>
                                </td>
                                <td><?php echo Property_Manager::format_price(get_post_meta(get_the_ID(), 'price', true)); ?></td>
                                <td><?php echo Property_Manager::get_view_count(get_the_ID()); ?></td>
                                <td>
                                    <a href="<?php echo get_permalink(); ?>" class="button button-small"><?php _e('View', 'malisafi-mls'); ?></a>
                                    <a href="<?php echo get_edit_post_link(); ?>" class="button button-small"><?php _e('Edit', 'malisafi-mls'); ?></a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-properties">
                    <p><?php _e('You haven\'t added any properties yet.', 'malisafi-mls'); ?></p>
                    <a href="<?php echo Page_Manager::get_page_url('owner_add_property'); ?>" class="button button-primary">
                        <?php _e('Add Your First Property', 'malisafi-mls'); ?>
                    </a>
                </div>
            <?php endif; ?>
            
            <?php wp_reset_postdata(); ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Owner Inquiries
     */
    public static function owner_inquiries($atts) {
        // Check if user is logged in first
        if (!is_user_logged_in()) {
            return self::require_login();
        }
        
        // Check if user has owner role
        $user = wp_get_current_user();
        $is_owner = in_array('malisafi_owner', $user->roles);
        
        if (!$is_owner) {
            return '<div class="malisafi-access-denied">
                <p>' . __('You do not have permission to access this page.', 'malisafi-mls') . '</p>
            </div>';
        }
        
        global $wpdb;
        $current_user = wp_get_current_user();
        
        // Get inquiries for user's properties
        $table_name = $wpdb->prefix . 'mf_inquiries';
        $inquiries = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, p.post_title as property_title 
            FROM $table_name i
            LEFT JOIN {$wpdb->posts} p ON i.property_id = p.ID
            WHERE p.post_author = %d
            ORDER BY i.created_at DESC",
            $current_user->ID
        ));
        
        ob_start();
        ?>
        <div class="malisafi-owner-inquiries">
            <h1><?php _e('Property Inquiries', 'malisafi-mls'); ?></h1>
            
            <?php if (empty($inquiries)): ?>
                <div class="no-inquiries">
                    <p><?php _e('No inquiries received yet.', 'malisafi-mls'); ?></p>
                </div>
            <?php else: ?>
                <table class="inquiries-table">
                    <thead>
                        <tr>
                            <th><?php _e('Property', 'malisafi-mls'); ?></th>
                            <th><?php _e('From', 'malisafi-mls'); ?></th>
                            <th><?php _e('Subject', 'malisafi-mls'); ?></th>
                            <th><?php _e('Status', 'malisafi-mls'); ?></th>
                            <th><?php _e('Date', 'malisafi-mls'); ?></th>
                            <th><?php _e('Actions', 'malisafi-mls'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inquiries as $inquiry): ?>
                            <tr>
                                <td><?php echo esc_html($inquiry->property_title); ?></td>
                                <td><?php echo esc_html($inquiry->name); ?><br><small><?php echo esc_html($inquiry->email); ?></small></td>
                                <td><?php echo esc_html($inquiry->subject); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo esc_attr($inquiry->status); ?>">
                                        <?php echo esc_html(ucfirst($inquiry->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo date_i18n(get_option('date_format'), strtotime($inquiry->created_at)); ?></td>
                                <td>
                                    <button class="button view-inquiry" data-id="<?php echo $inquiry->id; ?>">
                                        <?php _e('View', 'malisafi-mls'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Developer Dashboard
     */
    public static function developer_dashboard($atts) {
        // Check if user is logged in first
        if (!is_user_logged_in()) {
            return self::require_login();
        }
        
        // Check if user has developer role
        $user = wp_get_current_user();
        $is_developer = in_array('malisafi_developer', $user->roles);
        
        if (!$is_developer) {
            return '<div class="malisafi-access-denied">
                <p>' . __('You do not have permission to access this page.', 'malisafi-mls') . '</p>
            </div>';
        }
        
        $current_user = wp_get_current_user();
        
        ob_start();
        ?>
        <div class="malisafi-developer-dashboard">
            <div class="dashboard-header">
                <h1><?php printf(__('Welcome, %s', 'malisafi-mls'), $current_user->display_name); ?></h1>
                <p><?php _e('Manage your development projects and properties.', 'malisafi-mls'); ?></p>
            </div>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3><?php echo self::get_user_projects_count($current_user->ID); ?></h3>
                    <p><?php _e('Active Projects', 'malisafi-mls'); ?></p>
                    <a href="<?php echo Page_Manager::get_page_url('developer_projects'); ?>"><?php _e('View All', 'malisafi-mls'); ?></a>
                </div>
                
                <div class="stat-card">
                    <h3><?php echo self::get_user_properties_count($current_user->ID); ?></h3>
                    <p><?php _e('Total Properties', 'malisafi-mls'); ?></p>
                </div>
                
                <div class="stat-card">
                    <h3><?php echo self::get_total_views($current_user->ID); ?></h3>
                    <p><?php _e('Total Views', 'malisafi-mls'); ?></p>
                </div>
            </div>
            
            <div class="dashboard-quick-actions">
                <h2><?php _e('Quick Actions', 'malisafi-mls'); ?></h2>
                <div class="actions-grid">
                    <a href="<?php echo Page_Manager::get_page_url('developer_add_project'); ?>" class="action-button primary">
                        <span class="dashicons dashicons-plus"></span>
                        <?php _e('Add New Project', 'malisafi-mls'); ?>
                    </a>
                    <a href="<?php echo Page_Manager::get_page_url('developer_projects'); ?>" class="action-button">
                        <span class="dashicons dashicons-building"></span>
                        <?php _e('My Projects', 'malisafi-mls'); ?>
                    </a>
                    <a href="<?php echo Page_Manager::get_page_url('developer_analytics'); ?>" class="action-button">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <?php _e('View Analytics', 'malisafi-mls'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Developer Projects
     */
    public static function developer_projects($atts) {
        // Check if user is logged in first
        if (!is_user_logged_in()) {
            return self::require_login();
        }
        
        // Check if user has developer role
        $user = wp_get_current_user();
        $is_developer = in_array('malisafi_developer', $user->roles);
        
        if (!$is_developer) {
            return '<div class="malisafi-access-denied">
                <p>' . __('You do not have permission to access this page.', 'malisafi-mls') . '</p>
            </div>';
        }
        
        ob_start();
        ?>
        <div class="malisafi-developer-projects">
            <h1><?php _e('My Development Projects', 'malisafi-mls'); ?></h1>
            <p><?php _e('Projects management coming soon...', 'malisafi-mls'); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Developer Analytics
     */
    public static function developer_analytics($atts) {
        // Check if user is logged in first
        if (!is_user_logged_in()) {
            return self::require_login();
        }
        
        // Check if user has developer role
        $user = wp_get_current_user();
        $is_developer = in_array('malisafi_developer', $user->roles);
        
        if (!$is_developer) {
            return '<div class="malisafi-access-denied">
                <p>' . __('You do not have permission to access this page.', 'malisafi-mls') . '</p>
            </div>';
        }
        
        ob_start();
        ?>
        <div class="malisafi-developer-analytics">
            <h1><?php _e('Analytics Dashboard', 'malisafi-mls'); ?></h1>
            <p><?php _e('Analytics coming soon...', 'malisafi-mls'); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Property Submit Form
     */
    public static function property_submit_form($atts) {
        $user = wp_get_current_user();
        $allowed_roles = array('malisafi_agent_basic', 'malisafi_agent_premium', 'malisafi_owner', 'malisafi_developer');
        $has_role = false;
        foreach ($allowed_roles as $role) {
            if (in_array($role, $user->roles)) {
                $has_role = true;
                break;
            }
        }
        if (!$has_role) {
            return '<div class="malisafi-access-denied"><p>' . __('You do not have permission to submit a property.', 'malisafi-mls') . '</p></div>';
        }

        $message = '';
        $error = '';
        $redirect_url = '';
        // Traitement du formulaire
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['malisafi_property_submit_nonce']) && wp_verify_nonce($_POST['malisafi_property_submit_nonce'], 'malisafi_property_submit')) {
            $title = sanitize_text_field($_POST['property_title'] ?? '');
            $price = floatval($_POST['property_price'] ?? 0);
            $currency = sanitize_text_field($_POST['property_currency'] ?? 'KES');
            $bedrooms = intval($_POST['property_bedrooms'] ?? 0);
            $bathrooms = intval($_POST['property_bathrooms'] ?? 0);
            $area = floatval($_POST['property_area'] ?? 0);
            $county = sanitize_text_field($_POST['property_county'] ?? '');
            $neighbourhood = sanitize_text_field($_POST['property_neighbourhood'] ?? '');
            $setting = sanitize_text_field($_POST['property_setting'] ?? '');
            $description = wp_kses_post($_POST['property_description'] ?? '');
            $year_built = sanitize_text_field($_POST['property_year_built'] ?? '');
            $garage = intval($_POST['property_garage'] ?? 0);
            $address = sanitize_text_field($_POST['property_address'] ?? '');
            $zip_code = sanitize_text_field($_POST['property_zip_code'] ?? '');
            $property_type = intval($_POST['property_type'] ?? 0);
            $property_status_tax = intval($_POST['property_status_tax'] ?? 0);
            $latitude = floatval($_POST['property_latitude'] ?? 0);
            $longitude = floatval($_POST['property_longitude'] ?? 0);
            
            $features = array(
                'pool' => !empty($_POST['feature_pool']) ? 1 : 0,
                'gym' => !empty($_POST['feature_gym']) ? 1 : 0,
                'garden' => !empty($_POST['feature_garden']) ? 1 : 0,
                'balcony' => !empty($_POST['feature_balcony']) ? 1 : 0,
                'parking' => !empty($_POST['feature_parking']) ? 1 : 0,
                'security' => !empty($_POST['feature_security']) ? 1 : 0,
                'elevator' => !empty($_POST['feature_elevator']) ? 1 : 0,
                'furnished' => !empty($_POST['feature_furnished']) ? 1 : 0,
                'air_conditioning' => !empty($_POST['feature_air_conditioning']) ? 1 : 0,
            );

            if (empty($title) || empty($price) || empty($county) || empty($setting) || empty($address)) {
                $error = __('Please fill all required fields.', 'malisafi-mls');
            } else {
                $post_data = array(
                    'post_title'   => $title,
                    'post_type'    => 'malisafi_property',
                    'post_status'  => 'pending',
                    'post_content' => $description,
                    'post_author'  => get_current_user_id(),
                );
                $new_id = wp_insert_post($post_data, true);
                if (is_wp_error($new_id)) {
                    $error = $new_id->get_error_message();
                } else {
                    // Set taxonomies
                    if ($property_type > 0) {
                        wp_set_object_terms($new_id, $property_type, 'malisafi_property_type');
                    }
                    if ($property_status_tax > 0) {
                        wp_set_object_terms($new_id, $property_status_tax, 'malisafi_property_status');
                    }
                    
                    // Set meta fields
                    update_post_meta($new_id, '_malisafi_price', $price);
                    update_post_meta($new_id, '_malisafi_currency', $currency);
                    update_post_meta($new_id, '_malisafi_bedrooms', $bedrooms);
                    update_post_meta($new_id, '_malisafi_bathrooms', $bathrooms);
                    update_post_meta($new_id, '_malisafi_area', $area);
                    update_post_meta($new_id, '_malisafi_county', $county);
                    update_post_meta($new_id, '_malisafi_neighbourhood', $neighbourhood);
                    update_post_meta($new_id, '_malisafi_setting', $setting);
                    update_post_meta($new_id, '_malisafi_year_built', $year_built);
                    update_post_meta($new_id, '_malisafi_garage', $garage);
                    update_post_meta($new_id, '_malisafi_address', $address);
                    update_post_meta($new_id, '_malisafi_zip_code', $zip_code);
                    update_post_meta($new_id, '_malisafi_latitude', $latitude);
                    update_post_meta($new_id, '_malisafi_longitude', $longitude);
                    foreach ($features as $key => $val) {
                        update_post_meta($new_id, '_malisafi_' . $key, $val);
                    }
                    // Gestion des images
                    if (!empty($_FILES['property_images']['name'][0])) {
                        require_once(ABSPATH . 'wp-admin/includes/file.php');
                        require_once(ABSPATH . 'wp-admin/includes/image.php');
                        require_once(ABSPATH . 'wp-admin/includes/media.php');
                        $gallery_ids = array();
                        foreach ($_FILES['property_images']['name'] as $i => $name) {
                            if ($_FILES['property_images']['error'][$i] === 0) {
                                $file = array(
                                    'name'     => $_FILES['property_images']['name'][$i],
                                    'type'     => $_FILES['property_images']['type'][$i],
                                    'tmp_name' => $_FILES['property_images']['tmp_name'][$i],
                                    'error'    => $_FILES['property_images']['error'][$i],
                                    'size'     => $_FILES['property_images']['size'][$i],
                                );
                                $_FILES['upload_image'] = $file;
                                $attach_id = media_handle_upload('upload_image', $new_id);
                                if (!is_wp_error($attach_id)) {
                                    $gallery_ids[] = $attach_id;
                                }
                            }
                        }
                        if (!empty($gallery_ids)) {
                            update_post_meta($new_id, '_malisafi_gallery_ids', implode(',', $gallery_ids));
                            // Définir la première image comme image à la une
                            set_post_thumbnail($new_id, $gallery_ids[0]);
                        }
                    }
                    $message = __('Property submitted successfully! It will be reviewed by a moderator.', 'malisafi-mls');
                    // Redirection après succès
                    if (function_exists('MalisafiMLS\\Page_Manager::get_page_url')) {
                        if (current_user_can('malisafi_agent_basic') || current_user_can('malisafi_agent_premium')) {
                            $redirect_url = \MalisafiMLS\Page_Manager::get_page_url('agent_dashboard');
                        } elseif (current_user_can('malisafi_owner')) {
                            $redirect_url = \MalisafiMLS\Page_Manager::get_page_url('owner_dashboard');
                        } elseif (current_user_can('malisafi_developer')) {
                            $redirect_url = \MalisafiMLS\Page_Manager::get_page_url('developer_dashboard');
                        }
                    }
                    if (!$redirect_url) {
                        $redirect_url = home_url('/');
                    }
                    echo '<script>setTimeout(function(){ window.location.href = "' . esc_url($redirect_url) . '"; }, 1800);</script>';
                }
            }
        }

        // Enqueue form styles
        wp_enqueue_style('malisafi-property-submit-form', MALISAFI_MLS_URL . 'assets/css/property-submit-form.css', array(), MALISAFI_MLS_VERSION);
        wp_enqueue_style('agent-dashboard-modern', MALISAFI_MLS_URL . 'assets/css/agent-dashboard-modern.css', array(), MALISAFI_MLS_VERSION);
        wp_enqueue_script('agent-dashboard-modern', MALISAFI_MLS_URL . 'assets/js/agent-dashboard-modern.js', array('jquery'), MALISAFI_MLS_VERSION, true);
        
        // Get property types and statuses for dropdowns
        $property_types = get_terms(array('taxonomy' => 'malisafi_property_type', 'hide_empty' => false));
        $property_statuses = get_terms(array('taxonomy' => 'malisafi_property_status', 'hide_empty' => false));
        
        $current_user = wp_get_current_user();
        
        ob_start();
        ?>
        <div class="malisafi-agent-dashboard-modern">
            <!-- Collapsible Sidebar -->
            <aside class="agent-sidebar" id="agentSidebar">
                <div class="sidebar-header">
                    <div class="sidebar-brand">
                        <span class="brand-icon">🏠</span>
                        <span class="brand-text">Malisafi</span>
                    </div>
                    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar">
                        <span class="dashicons dashicons-arrow-left-alt2 sidebar-toggle-icon"></span>
                    </button>
                </div>

                <nav class="sidebar-nav">
                    <?php if (in_array('malisafi_agent_basic', $user->roles) || in_array('malisafi_agent_premium', $user->roles)): ?>
                        <a href="<?php echo esc_url(\MalisafiMLS\Page_Manager::get_page_url('agent_dashboard')); ?>" 
                           class="nav-item"
                           data-tooltip="Dashboard">
                            <span class="nav-icon dashicons dashicons-dashboard"></span>
                            <span class="nav-text"><?php _e('Dashboard', 'malisafi-mls'); ?></span>
                        </a>

                        <a href="<?php echo esc_url(\MalisafiMLS\Page_Manager::get_page_url('agent_properties')); ?>" 
                           class="nav-item"
                           data-tooltip="My Properties">
                            <span class="nav-icon dashicons dashicons-admin-home"></span>
                            <span class="nav-text"><?php _e('My Properties', 'malisafi-mls'); ?></span>
                        </a>

                        <a href="<?php echo esc_url(\MalisafiMLS\Page_Manager::get_page_url('agent_add_property')); ?>" 
                           class="nav-item active"
                           data-tooltip="Add Property">
                            <span class="nav-icon dashicons dashicons-plus-alt"></span>
                            <span class="nav-text"><?php _e('Add Property', 'malisafi-mls'); ?></span>
                        </a>

                        <a href="<?php echo esc_url(\MalisafiMLS\Page_Manager::get_page_url('agent_leads')); ?>" 
                           class="nav-item"
                           data-tooltip="Leads">
                            <span class="nav-icon dashicons dashicons-email"></span>
                            <span class="nav-text"><?php _e('Leads', 'malisafi-mls'); ?></span>
                        </a>

                        <a href="<?php echo esc_url(\MalisafiMLS\Page_Manager::get_page_url('agent_profile')); ?>" 
                           class="nav-item"
                           data-tooltip="My Profile">
                            <span class="nav-icon dashicons dashicons-businessman"></span>
                            <span class="nav-text"><?php _e('My Profile', 'malisafi-mls'); ?></span>
                        </a>
                    <?php elseif (in_array('malisafi_owner', $user->roles)): ?>
                        <a href="<?php echo esc_url(\MalisafiMLS\Page_Manager::get_page_url('owner_dashboard')); ?>" 
                           class="nav-item"
                           data-tooltip="Dashboard">
                            <span class="nav-icon dashicons dashicons-dashboard"></span>
                            <span class="nav-text"><?php _e('Dashboard', 'malisafi-mls'); ?></span>
                        </a>

                        <a href="<?php echo esc_url(\MalisafiMLS\Page_Manager::get_page_url('owner_properties')); ?>" 
                           class="nav-item"
                           data-tooltip="My Properties">
                            <span class="nav-icon dashicons dashicons-admin-home"></span>
                            <span class="nav-text"><?php _e('My Properties', 'malisafi-mls'); ?></span>
                        </a>

                        <a href="<?php echo esc_url(\MalisafiMLS\Page_Manager::get_page_url('owner_add_property')); ?>" 
                           class="nav-item active"
                           data-tooltip="Add Property">
                            <span class="nav-icon dashicons dashicons-plus-alt"></span>
                            <span class="nav-text"><?php _e('Add Property', 'malisafi-mls'); ?></span>
                        </a>

                        <a href="<?php echo esc_url(\MalisafiMLS\Page_Manager::get_page_url('owner_inquiries')); ?>" 
                           class="nav-item"
                           data-tooltip="Inquiries">
                            <span class="nav-icon dashicons dashicons-email"></span>
                            <span class="nav-text"><?php _e('Inquiries', 'malisafi-mls'); ?></span>
                        </a>
                    <?php elseif (in_array('malisafi_developer', $user->roles)): ?>
                        <a href="<?php echo esc_url(\MalisafiMLS\Page_Manager::get_page_url('developer_dashboard')); ?>" 
                           class="nav-item"
                           data-tooltip="Dashboard">
                            <span class="nav-icon dashicons dashicons-dashboard"></span>
                            <span class="nav-text"><?php _e('Dashboard', 'malisafi-mls'); ?></span>
                        </a>

                        <a href="<?php echo esc_url(\MalisafiMLS\Page_Manager::get_page_url('developer_projects')); ?>" 
                           class="nav-item"
                           data-tooltip="My Projects">
                            <span class="nav-icon dashicons dashicons-admin-home"></span>
                            <span class="nav-text"><?php _e('My Projects', 'malisafi-mls'); ?></span>
                        </a>

                        <a href="<?php echo esc_url(\MalisafiMLS\Page_Manager::get_page_url('developer_add_project')); ?>" 
                           class="nav-item active"
                           data-tooltip="Add Project">
                            <span class="nav-icon dashicons dashicons-plus-alt"></span>
                            <span class="nav-text"><?php _e('Add Project', 'malisafi-mls'); ?></span>
                        </a>

                        <a href="<?php echo esc_url(\MalisafiMLS\Page_Manager::get_page_url('developer_analytics')); ?>" 
                           class="nav-item"
                           data-tooltip="Analytics">
                            <span class="nav-icon dashicons dashicons-chart-bar"></span>
                            <span class="nav-text"><?php _e('Analytics', 'malisafi-mls'); ?></span>
                        </a>
                    <?php endif; ?>

                    <div class="sidebar-divider"></div>

                    <a href="<?php echo esc_url(\MalisafiMLS\Page_Manager::get_page_url('account')); ?>" 
                       class="nav-item"
                       data-tooltip="Account">
                        <span class="nav-icon dashicons dashicons-admin-settings"></span>
                        <span class="nav-text"><?php _e('Account', 'malisafi-mls'); ?></span>
                    </a>

                    <a href="<?php echo wp_logout_url(home_url()); ?>" 
                       class="nav-item"
                       data-tooltip="Logout">
                        <span class="nav-icon dashicons dashicons-exit"></span>
                        <span class="nav-text"><?php _e('Logout', 'malisafi-mls'); ?></span>
                    </a>
                </nav>

                <div class="sidebar-footer">
                    <div class="user-info">
                        <div class="user-avatar">
                            <?php echo get_avatar($current_user->ID, 40); ?>
                        </div>
                        <div class="user-details">
                            <div class="user-name"><?php echo esc_html($current_user->display_name); ?></div>
                            <div class="user-role">
                                <?php 
                                if (in_array('malisafi_agent_basic', $current_user->roles) || in_array('malisafi_agent_premium', $current_user->roles)) {
                                    _e('Agent', 'malisafi-mls');
                                } elseif (in_array('malisafi_owner', $current_user->roles)) {
                                    _e('Owner', 'malisafi-mls');
                                } elseif (in_array('malisafi_developer', $current_user->roles)) {
                                    _e('Developer', 'malisafi-mls');
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="agent-main-content">
                <div class="malisafi-property-submit">
            <?php if (!empty($message)) : ?>
                <div class="notice notice-success"><p><?php echo esc_html($message); ?></p></div>
            <?php endif; ?>
            <?php if (!empty($error)) : ?>
                <div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data" autocomplete="off">
                <?php wp_nonce_field('malisafi_property_submit', 'malisafi_property_submit_nonce'); ?>
                
                <!-- Basic Information Section -->
                <div class="form-section">
                    <div class="form-section-header">
                        <h3 class="form-section-title">
                            <span class="dashicons dashicons-admin-home"></span>
                            <?php _e('Basic Information', 'malisafi-mls'); ?>
                        </h3>
                        <p class="form-section-description"><?php _e('Provide the essential details about your property', 'malisafi-mls'); ?></p>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="property_title"><?php _e('Property Title', 'malisafi-mls'); ?> <span class="required">*</span></label>
                            <input type="text" name="property_title" id="property_title" required placeholder="<?php esc_attr_e('e.g. Luxury 3 Bedroom Apartment in Kilimani', 'malisafi-mls'); ?>" />
                            <small><?php _e('A clear, descriptive title helps attract potential buyers', 'malisafi-mls'); ?></small>
                        </div>
                    </div>
                    
                    <div class="form-row two-col">
                        <div class="form-group">
                            <label for="property_type"><?php _e('Property Type', 'malisafi-mls'); ?> <span class="required">*</span></label>
                            <select name="property_type" id="property_type" required>
                                <option value=""><?php _e('Select Type', 'malisafi-mls'); ?></option>
                                <?php if (!empty($property_types) && !is_wp_error($property_types)) : ?>
                                    <?php foreach ($property_types as $type) : ?>
                                        <option value="<?php echo esc_attr($type->term_id); ?>"><?php echo esc_html($type->name); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="property_status_tax"><?php _e('Property Status', 'malisafi-mls'); ?> <span class="required">*</span></label>
                            <select name="property_status_tax" id="property_status_tax" required>
                                <option value=""><?php _e('Select Status', 'malisafi-mls'); ?></option>
                                <?php if (!empty($property_statuses) && !is_wp_error($property_statuses)) : ?>
                                    <?php foreach ($property_statuses as $status) : ?>
                                        <option value="<?php echo esc_attr($status->term_id); ?>"><?php echo esc_html($status->name); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="property_description"><?php _e('Description', 'malisafi-mls'); ?></label>
                            <textarea name="property_description" id="property_description" rows="5" placeholder="<?php esc_attr_e('Describe the property, key features, nearby amenities, etc.', 'malisafi-mls'); ?>"></textarea>
                            <small><?php _e('Provide detailed information about the property to help buyers make informed decisions', 'malisafi-mls'); ?></small>
                        </div>
                    </div>
                </div>
                
                <!-- Pricing Section -->
                <div class="form-section">
                    <div class="form-section-header">
                        <h3 class="form-section-title">
                            <span class="dashicons dashicons-money-alt"></span>
                            <?php _e('Pricing Information', 'malisafi-mls'); ?>
                        </h3>
                        <p class="form-section-description"><?php _e('Set the price for your property', 'malisafi-mls'); ?></p>
                    </div>
                    
                    <div class="form-row two-col">
                        <div class="form-group">
                            <label for="property_price"><?php _e('Price', 'malisafi-mls'); ?> <span class="required">*</span></label>
                            <input type="number" name="property_price" id="property_price" required min="0" step="0.01" placeholder="<?php esc_attr_e('e.g. 12000000', 'malisafi-mls'); ?>" />
                            <small><?php _e('Enter the total price in numbers only', 'malisafi-mls'); ?></small>
                        </div>
                        
                        <div class="form-group">
                            <label for="property_currency"><?php _e('Currency', 'malisafi-mls'); ?></label>
                            <select name="property_currency" id="property_currency">
                                <option value="KES">KES (KSh)</option>
                                <option value="USD">USD ($)</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Property Details Section -->
                <div class="form-section">
                    <div class="form-section-header">
                        <h3 class="form-section-title">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <?php _e('Property Details', 'malisafi-mls'); ?>
                        </h3>
                        <p class="form-section-description"><?php _e('Specify the size and features of your property', 'malisafi-mls'); ?></p>
                    </div>
                    
                    <div class="form-row three-col">
                        <div class="form-group">
                            <label for="property_bedrooms"><?php _e('Bedrooms', 'malisafi-mls'); ?></label>
                            <input type="number" name="property_bedrooms" id="property_bedrooms" min="0" placeholder="<?php esc_attr_e('e.g. 3', 'malisafi-mls'); ?>" />
                        </div>
                        
                        <div class="form-group">
                            <label for="property_bathrooms"><?php _e('Bathrooms', 'malisafi-mls'); ?></label>
                            <input type="number" name="property_bathrooms" id="property_bathrooms" min="0" step="0.5" placeholder="<?php esc_attr_e('e.g. 2', 'malisafi-mls'); ?>" />
                        </div>
                        
                        <div class="form-group">
                            <label for="property_garage"><?php _e('Garage Spaces', 'malisafi-mls'); ?></label>
                            <input type="number" name="property_garage" id="property_garage" min="0" placeholder="<?php esc_attr_e('e.g. 2', 'malisafi-mls'); ?>" />
                        </div>
                    </div>
                    
                    <div class="form-row two-col">
                        <div class="form-group">
                            <label for="property_area"><?php _e('Area (sq ft)', 'malisafi-mls'); ?></label>
                            <input type="number" name="property_area" id="property_area" min="0" step="0.01" placeholder="<?php esc_attr_e('e.g. 1200', 'malisafi-mls'); ?>" />
                        </div>
                        
                        <div class="form-group">
                            <label for="property_year_built"><?php _e('Year Built', 'malisafi-mls'); ?></label>
                            <input type="number" name="property_year_built" id="property_year_built" min="1800" max="<?php echo date('Y') + 5; ?>" placeholder="<?php esc_attr_e('e.g. 2015', 'malisafi-mls'); ?>" />
                        </div>
                    </div>
                </div>
                
                <!-- Location Section -->
                <div class="form-section">
                    <div class="form-section-header">
                        <h3 class="form-section-title">
                            <span class="dashicons dashicons-location"></span>
                            <?php _e('Location', 'malisafi-mls'); ?>
                        </h3>
                        <p class="form-section-description"><?php _e('Provide location details for your property', 'malisafi-mls'); ?></p>
                    </div>
                    
                    <div class="form-row two-col">
                        <div class="form-group">
                            <label for="property_county"><?php _e('County', 'malisafi-mls'); ?> <span class="required">*</span></label>
                            <select name="property_county" id="property_county" required>
                                <option value=""><?php _e('Select County', 'malisafi-mls'); ?></option>
                                <?php
                                if (function_exists('malisafi_get_kenya_counties')) {
                                    $counties = malisafi_get_kenya_counties();
                                    foreach ($counties as $county) {
                                        echo '<option value="' . esc_attr($county) . '">' . esc_html($county) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="property_neighbourhood"><?php _e('Neighbourhood/Estate', 'malisafi-mls'); ?></label>
                            <input type="text" name="property_neighbourhood" id="property_neighbourhood" placeholder="<?php esc_attr_e('e.g. Kilimani, Lavington', 'malisafi-mls'); ?>" />
                        </div>
                    </div>
                    
                    <div class="form-row two-col">
                        <div class="form-group">
                            <label for="property_setting"><?php _e('Setting', 'malisafi-mls'); ?> <span class="required">*</span></label>
                            <select name="property_setting" id="property_setting" required>
                                <option value=""><?php _e('Select Setting', 'malisafi-mls'); ?></option>
                                <option value="urban"><?php _e('Urban', 'malisafi-mls'); ?></option>
                                <option value="semi-rural"><?php _e('Semi-rural', 'malisafi-mls'); ?></option>
                                <option value="rural"><?php _e('Rural', 'malisafi-mls'); ?></option>
                                <option value="isolated"><?php _e('Isolated', 'malisafi-mls'); ?></option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="property_zip_code"><?php _e('Postal Code', 'malisafi-mls'); ?></label>
                            <input type="text" name="property_zip_code" id="property_zip_code" placeholder="<?php esc_attr_e('e.g. 00100', 'malisafi-mls'); ?>" />
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="property_address"><?php _e('Street Address', 'malisafi-mls'); ?> <span class="required">*</span></label>
                            <input type="text" name="property_address" id="property_address" required placeholder="<?php esc_attr_e('e.g. 123 Riverside Drive', 'malisafi-mls'); ?>" />
                            <small><?php _e('For privacy, only general location will be displayed publicly', 'malisafi-mls'); ?></small>
                        </div>
                    </div>
                </div>
                
                <!-- Amenities Section -->
                <div class="form-section">
                    <div class="form-section-header">
                        <h3 class="form-section-title">
                            <span class="dashicons dashicons-star-filled"></span>
                            <?php _e('Features & Amenities', 'malisafi-mls'); ?>
                        </h3>
                        <p class="form-section-description"><?php _e('Select all the features and amenities available in your property', 'malisafi-mls'); ?></p>
                    </div>
                    
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="feature_pool" />
                            <span><?php _e('Swimming Pool', 'malisafi-mls'); ?></span>
                        </label>
                        
                        <label class="checkbox-label">
                            <input type="checkbox" name="feature_gym" />
                            <span><?php _e('Gym/Fitness Center', 'malisafi-mls'); ?></span>
                        </label>
                        
                        <label class="checkbox-label">
                            <input type="checkbox" name="feature_garden" />
                            <span><?php _e('Garden', 'malisafi-mls'); ?></span>
                        </label>
                        
                        <label class="checkbox-label">
                            <input type="checkbox" name="feature_balcony" />
                            <span><?php _e('Balcony/Terrace', 'malisafi-mls'); ?></span>
                        </label>
                        
                        <label class="checkbox-label">
                            <input type="checkbox" name="feature_parking" />
                            <span><?php _e('Parking Space', 'malisafi-mls'); ?></span>
                        </label>
                        
                        <label class="checkbox-label">
                            <input type="checkbox" name="feature_security" />
                            <span><?php _e('24/7 Security', 'malisafi-mls'); ?></span>
                        </label>
                        
                        <label class="checkbox-label">
                            <input type="checkbox" name="feature_elevator" />
                            <span><?php _e('Elevator/Lift', 'malisafi-mls'); ?></span>
                        </label>
                        
                        <label class="checkbox-label">
                            <input type="checkbox" name="feature_furnished" />
                            <span><?php _e('Furnished', 'malisafi-mls'); ?></span>
                        </label>
                        
                        <label class="checkbox-label">
                            <input type="checkbox" name="feature_air_conditioning" />
                            <span><?php _e('Air Conditioning', 'malisafi-mls'); ?></span>
                        </label>
                    </div>
                </div>
                
                <!-- GPS Location Section -->
                <div class="form-section">
                    <div class="form-section-header">
                        <h3 class="form-section-title">
                            <span class="dashicons dashicons-location-alt"></span>
                            <?php _e('GPS Coordinates', 'malisafi-mls'); ?>
                        </h3>
                        <p class="form-section-description"><?php _e('Set property location using GPS coordinates', 'malisafi-mls'); ?></p>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group full-width">
                            <button type="button" class="button button-secondary" id="getGpsLocation" style="margin-bottom: 15px;">
                                <span class="dashicons dashicons-location" style="margin-right: 5px; vertical-align: middle;"></span>
                                <?php _e('Get Current Location', 'malisafi-mls'); ?>
                            </button>
                            <small><?php _e('Uses your device\'s GPS to automatically fill coordinates', 'malisafi-mls'); ?></small>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group full-width">
                            <div class="gps-privacy-notice" style="padding: 12px; background: #e8f4f8; border-left: 4px solid #0073aa; margin-bottom: 15px;">
                                <p style="margin: 0 0 8px 0; font-weight: 600; color: #0073aa;">
                                    <span class="dashicons dashicons-shield" style="vertical-align: middle;"></span>
                                    <?php _e('Privacy & Security Protection', 'malisafi-mls'); ?>
                                </p>
                                <p style="margin: 0; font-size: 13px; line-height: 1.5;">
                                    <?php _e('For your security and privacy, the exact GPS coordinates you provide will be automatically offset by 200-400 meters when displayed publicly on the map. This protects the exact location of your property while still showing it in the correct area. <strong>Administrators will see the accurate location.</strong> Please enter the precise coordinates for best results.', 'malisafi-mls'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row two-col">
                        <div class="form-group">
                            <label for="property_latitude"><?php _e('Latitude', 'malisafi-mls'); ?></label>
                            <input type="number" name="property_latitude" id="property_latitude" step="0.000001" placeholder="<?php esc_attr_e('e.g. -1.286389', 'malisafi-mls'); ?>" />
                            <small><?php _e('Manual entry or automatic via GPS button above', 'malisafi-mls'); ?></small>
                        </div>
                        
                        <div class="form-group">
                            <label for="property_longitude"><?php _e('Longitude', 'malisafi-mls'); ?></label>
                            <input type="number" name="property_longitude" id="property_longitude" step="0.000001" placeholder="<?php esc_attr_e('e.g. 36.816666', 'malisafi-mls'); ?>" />
                            <small><?php _e('Manual entry or automatic via GPS button above', 'malisafi-mls'); ?></small>
                        </div>
                    </div>
                </div>
                
                <!-- Images Section -->
                <div class="form-section">
                    <div class="form-section-header">
                        <h3 class="form-section-title">
                            <span class="dashicons dashicons-format-image"></span>
                            <?php _e('Property Images', 'malisafi-mls'); ?>
                        </h3>
                        <p class="form-section-description"><?php _e('Upload high-quality images of your property', 'malisafi-mls'); ?></p>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="property_images"><?php _e('Images', 'malisafi-mls'); ?></label>
                            <input type="file" name="property_images[]" id="property_images" multiple accept="image/*" />
                            <small><?php _e('Upload multiple images (JPG, PNG). The first image will be used as the main photo. Maximum 10 images recommended.', 'malisafi-mls'); ?></small>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="form-actions">
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-yes"></span>
                        <?php _e('Submit Property', 'malisafi-mls'); ?>
                    </button>
                </div>
            </form>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const getGpsButton = document.getElementById('getGpsLocation');
            const latInput = document.getElementById('property_latitude');
            const lngInput = document.getElementById('property_longitude');
            
            if (getGpsButton) {
                getGpsButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Check if geolocation is supported
                    if (!navigator.geolocation) {
                        alert('<?php _e('Geolocation is not supported by your browser', 'malisafi-mls'); ?>');\n                        return;
                    }
                    
                    // Show loading state
                    const originalText = getGpsButton.innerHTML;
                    getGpsButton.innerHTML = '<span class=\"dashicons dashicons-update\" style=\"animation: spin 1s linear infinite; display: inline-block; margin-right: 5px;\"></span><?php _e('Getting location...', 'malisafi-mls'); ?>';\n                    getGpsButton.disabled = true;
                    
                    // Get current position
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            const lat = position.coords.latitude.toFixed(6);
                            const lng = position.coords.longitude.toFixed(6);
                            
                            latInput.value = lat;
                            lngInput.value = lng;
                            
                            // Reset button
                            getGpsButton.innerHTML = originalText;
                            getGpsButton.disabled = false;
                            
                            // Show success message
                            alert('<?php _e('Location found! Coordinates: ', 'malisafi-mls'); ?>' + lat + ', ' + lng);
                        },
                        function(error) {
                            let errorMsg = '<?php _e('Unable to get location', 'malisafi-mls'); ?>';\n                            \n                            if (error.code === 1) {
                                errorMsg = '<?php _e('Permission denied. Please enable location services.', 'malisafi-mls'); ?>';\n                            } else if (error.code === 2) {
                                errorMsg = '<?php _e('Position unavailable. Please try again.', 'malisafi-mls'); ?>';\n                            } else if (error.code === 3) {
                                errorMsg = '<?php _e('Request timeout. Please try again.', 'malisafi-mls'); ?>';\n                            }
                            \n                            alert(errorMsg);
                            \n                            // Reset button
                            getGpsButton.innerHTML = originalText;
                            getGpsButton.disabled = false;
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 10000,
                            maximumAge: 0
                        }
                    );
                });
            }
        });
        </script>
        <style>
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        </style>
                </div><!-- End .malisafi-property-submit -->
            </main><!-- End .agent-main-content -->
        </div><!-- End .malisafi-agent-dashboard-modern -->
        <?php
        return ob_get_clean();
    }
    
    /**
     * Login Form
     */
    public static function login_form($atts) {
        if (is_user_logged_in()) {
            return '<p>' . __('You are already logged in.', 'malisafi-mls') . ' <a href="' . wp_logout_url() . '">' . __('Logout', 'malisafi-mls') . '</a></p>';
        }
        
        // Get register page URL
        $register_url = Page_Manager::get_page_url('register');
        if (!$register_url) {
            $register_url = wp_registration_url();
        }
        
        ob_start();
        ?>
        <div class="malisafi-login-container">
            <div class="malisafi-login-box">
                <div class="malisafi-login-header">
                    <h2><?php _e('Welcome to Malisafi', 'malisafi-mls'); ?></h2>
                    <p><?php _e('Login to access your dashboard', 'malisafi-mls'); ?></p>
                </div>
                
                <div id="malisafi-login-messages"></div>
                
                <form id="malisafi-loginform" name="loginform" method="post">
                    <p>
                        <label for="user_login"><?php _e('Username or Email', 'malisafi-mls'); ?></label>
                        <input type="text" name="log" id="user_login" class="input" value="" size="20" autocomplete="username" required />
                    </p>
                    
                    <p>
                        <label for="user_pass"><?php _e('Password', 'malisafi-mls'); ?></label>
                        <input type="password" name="pwd" id="user_pass" class="input" value="" size="20" autocomplete="current-password" required />
                    </p>
                    
                    <p class="login-remember">
                        <label>
                            <input name="rememberme" type="checkbox" id="rememberme" value="forever" />
                            <?php _e('Remember Me', 'malisafi-mls'); ?>
                        </label>
                    </p>
                    
                    <p class="login-submit">
                        <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary" value="<?php esc_attr_e('Log In', 'malisafi-mls'); ?>" />
                    </p>
                </form>
                
                <div class="malisafi-login-links">
                    <p class="register-link">
                        <?php _e("Don't have an account?", 'malisafi-mls'); ?> 
                        <a href="<?php echo esc_url($register_url); ?>"><?php _e('Register', 'malisafi-mls'); ?></a>
                    </p>
                    <p class="lost-password-link">
                        <a href="<?php echo wp_lostpassword_url(); ?>"><?php _e('Forgot Password?', 'malisafi-mls'); ?></a>
                    </p>
                </div>
            </div>
        </div>
        
        <style>
        .malisafi-login-container {
            max-width: 450px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .malisafi-login-box {
            background: #ffffff;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            border: 1px solid #e0e0e0;
        }
        
        .malisafi-login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .malisafi-login-header h2 {
            color: #1a1a1a;
            font-size: 28px;
            font-weight: 700;
            margin: 0 0 10px;
        }
        
        .malisafi-login-header p {
            color: #4a4a4a;
            font-size: 14px;
            margin: 0;
        }
        
        #malisafi-loginform label {
            display: block;
            color: #1a1a1a;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        #malisafi-loginform input[type="text"],
        #malisafi-loginform input[type="password"] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f5f5f5;
            box-sizing: border-box;
        }
        
        #malisafi-loginform input[type="text"]:focus,
        #malisafi-loginform input[type="password"]:focus {
            border-color: #1a1a1a;
            background: #ffffff;
            outline: none;
            box-shadow: 0 0 0 4px rgba(26, 26, 26, 0.1);
        }
        
        #malisafi-loginform .login-remember {
            margin: 15px 0;
        }
        
        #malisafi-loginform .login-remember label {
            display: inline;
            font-weight: 500;
            color: #4a4a4a;
        }
        
        #malisafi-loginform .login-submit {
            margin-top: 20px;
        }
        
        #malisafi-loginform .login-submit input[type="submit"] {
            width: 100%;
            padding: 14px 32px;
            background: #1a1a1a;
            border: none;
            border-radius: 8px;
            color: #ffffff;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(26, 26, 26, 0.3);
        }
        
        #malisafi-loginform .login-submit input[type="submit"]:hover {
            background: #000000;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(26, 26, 26, 0.4);
        }
        
        .malisafi-login-links {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #e0e0e0;
            text-align: center;
        }
        
        .malisafi-login-links p {
            margin: 10px 0;
            color: #4a4a4a;
            font-size: 14px;
        }
        
        .malisafi-login-links a {
            color: #1a1a1a;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .malisafi-login-links a:hover {
            color: #4a4a4a;
        }
        
        #malisafi-login-messages {
            margin-bottom: 20px;
            border-radius: 8px;
            display: none;
        }
        
        #malisafi-login-messages.show {
            display: block;
        }
        
        #malisafi-login-messages.error {
            background: #fee;
            border: 1px solid #c33;
            color: #c33;
            padding: 12px 16px;
        }
        
        #malisafi-login-messages.success {
            background: #efe;
            border: 1px solid #3c3;
            color: #3c3;
            padding: 12px 16px;
        }
        
        #malisafi-loginform.loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        #malisafi-loginform.loading #wp-submit::after {
            content: "";
            display: inline-block;
            width: 16px;
            height: 16px;
            margin-left: 10px;
            border: 2px solid #ffffff;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            .malisafi-login-box {
                padding: 30px 20px;
            }
            
            .malisafi-login-container {
                padding: 10px;
            }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#malisafi-loginform').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $messages = $('#malisafi-login-messages');
                var $submit = $('#wp-submit');
                
                // Add loading state
                $form.addClass('loading');
                $submit.prop('disabled', true);
                
                // Hide previous messages
                $messages.removeClass('show error success').hide();
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'malisafi_custom_login',
                        username: $('#user_login').val(),
                        password: $('#user_pass').val(),
                        remember: $('#rememberme').is(':checked'),
                        nonce: '<?php echo wp_create_nonce('malisafi_login_nonce'); ?>'
                    },
                    success: function(response) {
                        $form.removeClass('loading');
                        $submit.prop('disabled', false);
                        
                        if (response.success) {
                            $messages
                                .addClass('show success')
                                .html('<strong><?php _e('Success!', 'malisafi-mls'); ?></strong> ' + response.data.message)
                                .fadeIn();
                            
                            // Redirect to dashboard
                            setTimeout(function() {
                                window.location.href = response.data.redirect;
                            }, 1000);
                        } else {
                            $messages
                                .addClass('show error')
                                .html('<strong><?php _e('Error:', 'malisafi-mls'); ?></strong> ' + response.data.message)
                                .fadeIn();
                            
                            // Clear password field
                            $('#user_pass').val('').focus();
                        }
                    },
                    error: function() {
                        $form.removeClass('loading');
                        $submit.prop('disabled', false);
                        $messages
                            .addClass('show error')
                            .html('<strong><?php _e('Error:', 'malisafi-mls'); ?></strong> <?php _e('Connection error. Please try again.', 'malisafi-mls'); ?>')
                            .fadeIn();
                    }
                });
            });
        });
        </script>
        </style>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Register Form
     */
    public static function register_form($atts) {
        if (is_user_logged_in()) {
            return '<p>' . __('You are already logged in.', 'malisafi-mls') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="malisafi-register-form">
            <h2><?php _e('Register', 'malisafi-mls'); ?></h2>
            <form method="post" action="" id="malisafi-register-form">
                <?php wp_nonce_field('malisafi_register', 'malisafi_register_nonce'); ?>
                
                <p>
                    <label for="username"><?php _e('Username', 'malisafi-mls'); ?> *</label>
                    <input type="text" name="username" id="username" required>
                </p>
                
                <p>
                    <label for="email"><?php _e('Email', 'malisafi-mls'); ?> *</label>
                    <input type="email" name="email" id="email" required>
                </p>
                
                <p>
                    <label for="password"><?php _e('Password', 'malisafi-mls'); ?> *</label>
                    <input type="password" name="password" id="password" required>
                </p>
                
                <p>
                    <label for="password_confirm"><?php _e('Confirm Password', 'malisafi-mls'); ?> *</label>
                    <input type="password" name="password_confirm" id="password_confirm" required>
                </p>
                
                <p>
                    <button type="submit" name="malisafi_register" class="button button-primary">
                        <?php _e('Register', 'malisafi-mls'); ?>
                    </button>
                </p>
            </form>
            
            <p class="login-link">
                <a href="<?php echo Page_Manager::get_page_url('login'); ?>">
                    <?php _e('Already have an account? Login', 'malisafi-mls'); ?>
                </a>
            </p>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Account Page
     */
    public static function account_page($atts) {
        $login_check = self::require_login();
        if ($login_check) return $login_check;
        
        $current_user = wp_get_current_user();
        
        ob_start();
        ?>
        <div class="malisafi-account">
            <h1><?php _e('My Account', 'malisafi-mls'); ?></h1>
            
            <div class="account-info">
                <h2><?php _e('Account Information', 'malisafi-mls'); ?></h2>
                <p><strong><?php _e('Username:', 'malisafi-mls'); ?></strong> <?php echo $current_user->user_login; ?></p>
                <p><strong><?php _e('Email:', 'malisafi-mls'); ?></strong> <?php echo $current_user->user_email; ?></p>
                <p><strong><?php _e('Role:', 'malisafi-mls'); ?></strong> <?php echo implode(', ', $current_user->roles); ?></p>
            </div>
            
            <div class="account-actions">
                <h2><?php _e('Quick Links', 'malisafi-mls'); ?></h2>
                <ul>
                    <?php if (current_user_can('agent_basic')): ?>
                        <li><a href="<?php echo admin_url('admin.php?page=malisafi-agent-dashboard'); ?>"><?php _e('Agent Dashboard', 'malisafi-mls'); ?></a></li>
                    <?php elseif (current_user_can('owner')): ?>
                        <li><a href="<?php echo Page_Manager::get_page_url('owner_dashboard'); ?>"><?php _e('Owner Dashboard', 'malisafi-mls'); ?></a></li>
                    <?php elseif (current_user_can('developer')): ?>
                        <li><a href="<?php echo Page_Manager::get_page_url('developer_dashboard'); ?>"><?php _e('Developer Dashboard', 'malisafi-mls'); ?></a></li>
                    <?php else: ?>
                        <li><a href="<?php echo Page_Manager::get_page_url('client_dashboard'); ?>"><?php _e('Client Dashboard', 'malisafi-mls'); ?></a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo wp_logout_url(home_url()); ?>"><?php _e('Logout', 'malisafi-mls'); ?></a></li>
                </ul>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    // Helper methods
    
    private static function get_favorites_count($user_id) {
        $favorites = get_user_meta($user_id, 'malisafi_favorites', true) ?: [];
        return count($favorites);
    }
    
    private static function get_saved_searches_count($user_id) {
        $searches = get_user_meta($user_id, 'malisafi_saved_searches', true) ?: [];
        return count($searches);
    }
    
    private static function get_inquiries_count($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mf_inquiries';
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE client_id = %d",
            $user_id
        )) ?: 0;
    }
    
    private static function get_user_inquiries_count($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mf_inquiries';
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name i
            LEFT JOIN {$wpdb->posts} p ON i.property_id = p.ID
            WHERE p.post_author = %d",
            $user_id
        )) ?: 0;
    }
    
    private static function get_user_properties_count($user_id) {
        global $wpdb;
        $statuses = array('publish','pending','draft');
        $placeholders = implode(',', array_fill(0, count($statuses), '%s'));
        $sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_author = %d AND post_status IN ($placeholders)",
            array_merge(array('malisafi_property', $user_id), $statuses)
        );
        $count = (int) $wpdb->get_var($sql);
        return $count;
    }
    
    private static function get_user_projects_count($user_id) {
        // Placeholder - to be implemented when project CPT is created
        return 0;
    }
    
    private static function get_total_views($user_id) {
        global $wpdb;
        $views_table = $wpdb->prefix . 'malisafi_property_views';
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $views_table v
            LEFT JOIN {$wpdb->posts} p ON v.property_id = p.ID
            WHERE p.post_author = %d",
            $user_id
        ));
        
        return $total ?: 0;
    }
    
    private static function get_recent_viewed_properties($user_id) {
        $viewed = get_user_meta($user_id, 'malisafi_recently_viewed', true) ?: [];
        
        if (empty($viewed)) {
            return '<p>' . __('No recent activity.', 'malisafi-mls') . '</p>';
        }
        
        $args = [
            'post_type' => 'malisafi_property',
            'post__in' => array_slice($viewed, 0, 5),
            'posts_per_page' => 5,
            'orderby' => 'post__in'
        ];
        
        $query = new \WP_Query($args);
        
        if (!$query->have_posts()) {
            return '<p>' . __('No recent activity.', 'malisafi-mls') . '</p>';
        }
        
        $output = '<ul class="recent-properties">';
        while ($query->have_posts()) {
            $query->the_post();
            $output .= '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
        }
        $output .= '</ul>';
        
        wp_reset_postdata();
        
        return $output;
    }
    
    private static function render_property_card($property_id) {
        $price = get_post_meta($property_id, 'price', true);
        $bedrooms = get_post_meta($property_id, 'bedrooms', true);
        $bathrooms = get_post_meta($property_id, 'bathrooms', true);
        
        ob_start();
        ?>
        <div class="property-card">
            <?php if (has_post_thumbnail($property_id)): ?>
                <div class="property-image">
                    <a href="<?php echo get_permalink($property_id); ?>">
                        <?php echo get_the_post_thumbnail($property_id, 'medium'); ?>
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="property-info">
                <h3><a href="<?php echo get_permalink($property_id); ?>"><?php echo get_the_title($property_id); ?></a></h3>
                <p class="price"><?php echo Property_Manager::format_price($price); ?></p>
                <div class="property-meta">
                    <?php if ($bedrooms): ?>
                        <span><?php echo $bedrooms; ?> <?php _e('beds', 'malisafi-mls'); ?></span>
                    <?php endif; ?>
                    <?php if ($bathrooms): ?>
                        <span><?php echo $bathrooms; ?> <?php _e('baths', 'malisafi-mls'); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private static function format_search_criteria($search) {
        $criteria = [];
        
        if (!empty($search['min_price'])) {
            $criteria[] = __('Min Price:', 'malisafi-mls') . ' ' . Property_Manager::format_price($search['min_price']);
        }
        
        if (!empty($search['max_price'])) {
            $criteria[] = __('Max Price:', 'malisafi-mls') . ' ' . Property_Manager::format_price($search['max_price']);
        }
        
        if (!empty($search['bedrooms'])) {
            $criteria[] = $search['bedrooms'] . ' ' . __('bedrooms', 'malisafi-mls');
        }
        
        return implode(' | ', $criteria);
    }
    
    private static function build_search_url($search) {
        $url = Page_Manager::get_page_url('property_search');
        $params = [];
        
        if (!empty($search['min_price'])) $params['min_price'] = $search['min_price'];
        if (!empty($search['max_price'])) $params['max_price'] = $search['max_price'];
        if (!empty($search['bedrooms'])) $params['bedrooms'] = $search['bedrooms'];
        
        return add_query_arg($params, $url);
    }
    
    /**
     * Public Agent Profile View
     */
    public static function agent_profile_public($atts) {
            $atts = shortcode_atts(array(
                'agent_id' => isset($_GET['agent_id']) ? intval($_GET['agent_id']) : 0
            ), $atts);
        
            if (empty($atts['agent_id'])) {
                return '<div class="malisafi-error">' . __('Agent ID is required.', 'malisafi-mls') . '</div>';
            }
    
        
            // Enqueue styles
            wp_enqueue_style(
                'agent-profile-public',
                MALISAFI_MLS_URL . 'assets/css/agent-profile-public.css',
                array(),
                MALISAFI_MLS_VERSION
            );
        
            ob_start();
            include MALISAFI_MLS_PATH . 'templates/agent-profile-public.php';
            return ob_get_clean();
        }
    
    /**
     * AJAX handler for custom login
     */
    public static function ajax_custom_login() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'malisafi_login_nonce')) {
            wp_send_json_error([
                'message' => __('Security check failed. Please refresh the page and try again.', 'malisafi-mls')
            ]);
        }
        
        // Get credentials
        $username = sanitize_text_field($_POST['username']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) && $_POST['remember'] === 'true';
        
        // Validate inputs
        if (empty($username) || empty($password)) {
            wp_send_json_error([
                'message' => __('Please enter both username and password.', 'malisafi-mls')
            ]);
        }
        
        // Attempt authentication
        $user = wp_authenticate($username, $password);
        
        // Check for errors
        if (is_wp_error($user)) {
            $error_code = $user->get_error_code();
            
            // Customize error messages
            switch ($error_code) {
                case 'invalid_username':
                    $message = __('The username you entered does not exist. Please check and try again.', 'malisafi-mls');
                    break;
                case 'incorrect_password':
                case 'invalid_email':
                    $message = __('Incorrect password. Please try again.', 'malisafi-mls');
                    break;
                default:
                    $message = __('Login failed. Please check your credentials and try again.', 'malisafi-mls');
            }
            
            wp_send_json_error(['message' => $message]);
        }
        
        // Log the user in
        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, $remember);
        
        // Determine redirect URL based on user role
        $redirect_url = home_url();
        
        if (in_array('administrator', $user->roles) || in_array('malisafi_moderator', $user->roles)) {
            $redirect_url = admin_url();
        } elseif (in_array('malisafi_agent_basic', $user->roles) || in_array('malisafi_agent_premium', $user->roles)) {
            $redirect_url = Page_Manager::get_page_url('agent_dashboard') ?: home_url();
        } elseif (in_array('malisafi_owner', $user->roles)) {
            $redirect_url = Page_Manager::get_page_url('owner_dashboard') ?: home_url();
        } elseif (in_array('malisafi_developer', $user->roles)) {
            $redirect_url = Page_Manager::get_page_url('developer_dashboard') ?: home_url();
        } elseif (in_array('malisafi_client', $user->roles)) {
            $redirect_url = Page_Manager::get_page_url('client_dashboard') ?: home_url();
        }
        
        wp_send_json_success([
            'message' => sprintf(__('Welcome back, %s!', 'malisafi-mls'), $user->display_name),
            'redirect' => $redirect_url
        ]);
    }
}
