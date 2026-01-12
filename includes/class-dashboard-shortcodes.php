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
        add_shortcode('malisafi_login', [__CLASS__, 'login_form']);
        add_shortcode('malisafi_register', [__CLASS__, 'register_form']);
        add_shortcode('malisafi_account', [__CLASS__, 'account_page']);
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
     * Agent Dashboard - Redirect to backend
     */
    public static function agent_dashboard($atts) {
        $login_check = self::require_login('agent_basic');
        if ($login_check) return $login_check;
        
        // Redirect to backend agent dashboard
        $backend_url = admin_url('admin.php?page=malisafi-agent-dashboard');
        
        ob_start();
        ?>
        <div class="malisafi-agent-dashboard-redirect">
            <p><?php _e('Redirecting to your agent dashboard...', 'malisafi-mls'); ?></p>
            <script>
                window.location.href = '<?php echo esc_js($backend_url); ?>';
            </script>
            <p><a href="<?php echo esc_url($backend_url); ?>"><?php _e('Click here if not redirected', 'malisafi-mls'); ?></a></p>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Agent Properties
     */
    public static function agent_properties($atts) {
        return self::agent_dashboard($atts);
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
        $login_check = self::require_login('owner');
        if ($login_check) return $login_check;
        
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
        $login_check = self::require_login('owner');
        if ($login_check) return $login_check;
        
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
        $login_check = self::require_login('owner');
        if ($login_check) return $login_check;
        
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
        $login_check = self::require_login('developer');
        if ($login_check) return $login_check;
        
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
        $login_check = self::require_login('developer');
        if ($login_check) return $login_check;
        
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
        $login_check = self::require_login('developer');
        if ($login_check) return $login_check;
        
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

        ob_start();
        ?>
        <div class="malisafi-property-submit">
            <h1><?php _e('Submit Property', 'malisafi-mls'); ?></h1>
            <p class="form-guidance"><?php _e('Please fill in all required fields. Your property will be reviewed before publication.', 'malisafi-mls'); ?></p>
            <?php if (!empty($message)) : ?>
                <div class="notice notice-success"><p><?php echo esc_html($message); ?></p></div>
            <?php endif; ?>
            <?php if (!empty($error)) : ?>
                <div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data" autocomplete="off">
                <?php wp_nonce_field('malisafi_property_submit', 'malisafi_property_submit_nonce'); ?>
                <div class="form-group">
                    <label for="property_title"><?php _e('Title', 'malisafi-mls'); ?> *</label>
                    <input type="text" name="property_title" id="property_title" class="regular-text" required placeholder="e.g. 3 Bedroom Apartment in Kilimani" />
                    <small><?php _e('A clear, descriptive title helps attract buyers.', 'malisafi-mls'); ?></small>
                </div>
                <div class="form-group">
                    <label for="property_price"><?php _e('Price', 'malisafi-mls'); ?> *</label>
                    <input type="number" name="property_price" id="property_price" class="regular-text" required min="0" step="0.01" placeholder="e.g. 12000000" />
                    <small><?php _e('Enter the total price in numbers only.', 'malisafi-mls'); ?></small>
                </div>
                <div class="form-group">
                    <label for="property_currency"><?php _e('Currency', 'malisafi-mls'); ?></label>
                    <select name="property_currency" id="property_currency">
                        <option value="KES">KES (KSh)</option>
                        <option value="USD">USD ($)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="property_bedrooms"><?php _e('Bedrooms', 'malisafi-mls'); ?></label>
                    <input type="number" name="property_bedrooms" id="property_bedrooms" class="regular-text" min="0" placeholder="e.g. 3" />
                </div>
                <div class="form-group">
                    <label for="property_bathrooms"><?php _e('Bathrooms', 'malisafi-mls'); ?></label>
                    <input type="number" name="property_bathrooms" id="property_bathrooms" class="regular-text" min="0" placeholder="e.g. 2" />
                </div>
                <div class="form-group">
                    <label for="property_area"><?php _e('Area (sq ft)', 'malisafi-mls'); ?></label>
                    <input type="number" name="property_area" id="property_area" class="regular-text" min="0" step="0.01" placeholder="e.g. 1200" />
                </div>
                <div class="form-group">
                    <label for="property_county"><?php _e('County', 'malisafi-mls'); ?> *</label>
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
                    <small><?php _e('Required. Select the county in Kenya.', 'malisafi-mls'); ?></small>
                </div>
                <div class="form-group">
                    <label for="property_neighbourhood"><?php _e('Neighbourhood', 'malisafi-mls'); ?></label>
                    <input type="text" name="property_neighbourhood" id="property_neighbourhood" class="regular-text" placeholder="e.g. Kilimani, Lavington" />
                </div>
                <div class="form-group">
                    <label for="property_setting"><?php _e('Setting', 'malisafi-mls'); ?> *</label>
                    <select name="property_setting" id="property_setting" required>
                        <option value=""><?php _e('Select Setting', 'malisafi-mls'); ?></option>
                        <option value="urban"><?php _e('Urban', 'malisafi-mls'); ?></option>
                        <option value="semi-rural"><?php _e('Semi-rural', 'malisafi-mls'); ?></option>
                        <option value="rural"><?php _e('Rural', 'malisafi-mls'); ?></option>
                        <option value="isolated"><?php _e('Isolated', 'malisafi-mls'); ?></option>
                    </select>
                    <small><?php _e('Required. Choose the environment type.', 'malisafi-mls'); ?></small>
                </div>
                <div class="form-group">
                    <label for="property_address"><?php _e('Address', 'malisafi-mls'); ?> *</label>
                    <input type="text" name="property_address" id="property_address" class="regular-text" required placeholder="e.g. 123 Riverside Drive" />
                </div>
                <div class="form-group">
                    <label for="property_zip_code"><?php _e('Zip Code', 'malisafi-mls'); ?></label>
                    <input type="text" name="property_zip_code" id="property_zip_code" class="regular-text" placeholder="e.g. 00100" />
                </div>
                <div class="form-group">
                    <label for="property_year_built"><?php _e('Year Built', 'malisafi-mls'); ?></label>
                    <input type="text" name="property_year_built" id="property_year_built" class="regular-text" placeholder="e.g. 2015" />
                </div>
                <div class="form-group">
                    <label for="property_garage"><?php _e('Garage (number of cars)', 'malisafi-mls'); ?></label>
                    <input type="number" name="property_garage" id="property_garage" class="regular-text" min="0" placeholder="e.g. 2" />
                </div>
                <div class="form-group">
                    <label for="property_description"><?php _e('Description', 'malisafi-mls'); ?></label>
                    <textarea name="property_description" id="property_description" rows="5" placeholder="Describe the property, features, nearby amenities, etc."></textarea>
                </div>
                <div class="form-group">
                    <label for="property_images"><?php _e('Property Images', 'malisafi-mls'); ?></label>
                    <input type="file" name="property_images[]" id="property_images" multiple accept="image/*" />
                    <small><?php _e('Upload high quality images (JPG, PNG, max 10 images). The first image will be used as the main photo.', 'malisafi-mls'); ?></small>
                </div>
                <fieldset class="form-group">
                    <legend><?php _e('Features & Amenities', 'malisafi-mls'); ?></legend>
                    <label><input type="checkbox" name="feature_pool" /> <?php _e('Swimming Pool', 'malisafi-mls'); ?></label>
                    <label><input type="checkbox" name="feature_gym" /> <?php _e('Gym', 'malisafi-mls'); ?></label>
                    <label><input type="checkbox" name="feature_garden" /> <?php _e('Garden', 'malisafi-mls'); ?></label>
                    <label><input type="checkbox" name="feature_balcony" /> <?php _e('Balcony', 'malisafi-mls'); ?></label>
                    <label><input type="checkbox" name="feature_parking" /> <?php _e('Parking', 'malisafi-mls'); ?></label>
                    <label><input type="checkbox" name="feature_security" /> <?php _e('24/7 Security', 'malisafi-mls'); ?></label>
                    <label><input type="checkbox" name="feature_elevator" /> <?php _e('Elevator', 'malisafi-mls'); ?></label>
                    <label><input type="checkbox" name="feature_furnished" /> <?php _e('Furnished', 'malisafi-mls'); ?></label>
                    <label><input type="checkbox" name="feature_air_conditioning" /> <?php _e('Air Conditioning', 'malisafi-mls'); ?></label>
                </fieldset>
                <button type="submit" class="button button-primary"><?php _e('Submit Property', 'malisafi-mls'); ?></button>
            </form>
        </div>
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
        
        ob_start();
        ?>
        <div class="malisafi-login-container">
            <div class="malisafi-login-box">
                <div class="malisafi-login-header">
                    <h2><?php _e('Welcome to Malisafi', 'malisafi-mls'); ?></h2>
                    <p><?php _e('Login to access your dashboard', 'malisafi-mls'); ?></p>
                </div>
                <?php
                wp_login_form([
                    'echo' => true,
                    'redirect' => home_url(),
                    'form_id' => 'malisafi-loginform',
                    'label_username' => __('Username or Email', 'malisafi-mls'),
                    'label_password' => __('Password', 'malisafi-mls'),
                    'label_remember' => __('Remember Me', 'malisafi-mls'),
                    'label_log_in' => __('Log In', 'malisafi-mls'),
                    'remember' => true
                ]);
                ?>
                <div class="malisafi-login-links">
                    <p class="register-link">
                        <?php _e("Don't have an account?", 'malisafi-mls'); ?> 
                        <a href="<?php echo wp_registration_url(); ?>"><?php _e('Register', 'malisafi-mls'); ?></a>
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
        
        @media (max-width: 768px) {
            .malisafi-login-box {
                padding: 30px 20px;
            }
        }
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
}
