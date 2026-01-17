<?php
/**
 * Shortcodes for frontend
 *
 * @package MalisafiMLS
 */

/**
 * Malisafi_Shortcodes class
 */
class Malisafi_Shortcodes {
    
    /**
     * Initialize shortcodes
     */
    public static function init() {
        add_shortcode('malisafi_pricing', array(__CLASS__, 'pricing_page'));
        add_shortcode('malisafi_subscription_status', array(__CLASS__, 'subscription_status'));
        add_shortcode('malisafi_submit_property', array(__CLASS__, 'submit_property_form'));
        add_shortcode('malisafi_properties_modern', array(__CLASS__, 'properties_with_filters'));
        add_shortcode('malisafi_properties', array(__CLASS__, 'properties_with_filters')); // Alias for modern filters
        add_shortcode('malisafi_properties_minimalist', array(__CLASS__, 'properties_minimalist_filters')); // Minimalist filters
        add_shortcode('malisafi_registration', array(__CLASS__, 'registration_form'));
        add_shortcode('malisafi_register', array(__CLASS__, 'registration_form')); // Alias
        add_shortcode('malisafi_property_map', array(__CLASS__, 'property_map')); // Property map view
        add_shortcode('malisafi_city_list', array(__CLASS__, 'city_list')); // City list with search links
        add_shortcode('malisafi_agent', array(__CLASS__, 'agent_profile')); // Single agent profile
        add_shortcode('malisafi_agents', array(__CLASS__, 'agents_list')); // Agents listing
        add_shortcode('malisafi_add_property', array(__CLASS__, 'add_property_page')); // Add property with permission checks
    }
    
    /**
     * Pricing page shortcode
     *
     * @return string
     */
    public static function pricing_page() {
        ob_start();
        
        // Enqueue necessary scripts
        wp_enqueue_script('jquery');
        
        include MALISAFI_MLS_PATH . 'templates/pricing-page.php';
        
        return ob_get_clean();
    }
    
    /**
     * Subscription status shortcode
     *
     * @return string
     */
    public static function subscription_status() {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your subscription status.', 'malisafi-mls') . '</p>';
        }
        
        $user_id = get_current_user_id();
        $subscription = Malisafi_Stripe::get_user_subscription($user_id);
        
        ob_start();
        ?>
        <div class="malisafi-subscription-status">
            <?php if ($subscription && $subscription->status === 'active') : ?>
                <?php
                $plans = Malisafi_Stripe::get_plans();
                $plan_info = isset($plans[$subscription->plan_type]) ? $plans[$subscription->plan_type] : null;
                ?>
                <div class="subscription-active">
                    <h3><?php _e('Active Subscription', 'malisafi-mls'); ?></h3>
                    <div class="subscription-details">
                        <p><strong><?php _e('Plan:', 'malisafi-mls'); ?></strong> 
                            <?php echo $plan_info ? esc_html($plan_info['name']) : esc_html($subscription->plan_type); ?>
                        </p>
                        <p><strong><?php _e('Status:', 'malisafi-mls'); ?></strong> 
                            <span class="status-badge active"><?php echo esc_html(ucfirst($subscription->status)); ?></span>
                        </p>
                        <p><strong><?php _e('Next Billing Date:', 'malisafi-mls'); ?></strong> 
                            <?php echo date_i18n(get_option('date_format'), strtotime($subscription->current_period_end)); ?>
                        </p>
                        <?php if ($plan_info) : ?>
                            <p><strong><?php _e('Amount:', 'malisafi-mls'); ?></strong> 
                                <?php 
                                    $currency = isset($plan_info['currency']) ? $plan_info['currency'] : 'USD';
                                    echo esc_html(Malisafi_Stripe::format_price($plan_info['price'], $currency)); 
                                ?>/<?php echo esc_html($plan_info['interval']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="subscription-actions">
                        <button class="btn btn-primary manage-subscription">
                            <?php _e('Manage Subscription', 'malisafi-mls'); ?>
                        </button>
                        <button class="btn btn-secondary cancel-subscription">
                            <?php _e('Cancel Subscription', 'malisafi-mls'); ?>
                        </button>
                    </div>
                </div>
            <?php else : ?>
                <div class="subscription-inactive">
                    <h3><?php _e('No Active Subscription', 'malisafi-mls'); ?></h3>
                    <p><?php _e('You don\'t have an active subscription. Choose a plan to get started!', 'malisafi-mls'); ?></p>
                    <a href="<?php echo esc_url(home_url('/pricing')); ?>" class="btn btn-primary">
                        <?php _e('View Plans', 'malisafi-mls'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
        .malisafi-subscription-status {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 30px;
            margin: 20px 0;
        }
        
        .subscription-details {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        
        .subscription-details p {
            margin: 10px 0;
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-badge.active {
            background: #00a32a;
            color: white;
        }
        
        .subscription-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #2271b1;
            color: white;
        }
        
        .btn-primary:hover {
            background: #135e96;
        }
        
        .btn-secondary {
            background: #d63638;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #b32d2e;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('.manage-subscription').on('click', function() {
                const button = $(this);
                button.prop('disabled', true).text('<?php _e('Loading...', 'malisafi-mls'); ?>');
                
                $.ajax({
                    url: malisafiAjax.ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'malisafi_create_portal',
                        nonce: malisafiAjax.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.url) {
                            window.location.href = response.data.url;
                        } else {
                            alert(response.data.message || '<?php _e('An error occurred.', 'malisafi-mls'); ?>');
                            button.prop('disabled', false).text('<?php _e('Manage Subscription', 'malisafi-mls'); ?>');
                        }
                    }
                });
            });
            
            $('.cancel-subscription').on('click', function() {
                if (!confirm('<?php _e('Are you sure you want to cancel your subscription?', 'malisafi-mls'); ?>')) {
                    return;
                }
                
                const button = $(this);
                button.prop('disabled', true).text('<?php _e('Cancelling...', 'malisafi-mls'); ?>');
                
                $.ajax({
                    url: malisafiAjax.ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'malisafi_cancel_subscription',
                        nonce: malisafiAjax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            alert(response.data.message || '<?php _e('An error occurred.', 'malisafi-mls'); ?>');
                            button.prop('disabled', false).text('<?php _e('Cancel Subscription', 'malisafi-mls'); ?>');
                        }
                    }
                });
            });
        });
        </script>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Submit property form shortcode
     *
     * @return string
     */
    public static function submit_property_form() {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to submit a property.', 'malisafi-mls') . '</p>';
        }
        
        ob_start();
        
        // Check user limits
        $user_id = get_current_user_id();
        global $wpdb;
        $limits_table = $wpdb->prefix . 'mf_user_limits';
        $limits = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$limits_table} WHERE user_id = %d",
            $user_id
        ));
        
        if ($limits) {
            global $wpdb;
$current_listings = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'",
    'malisafi_property'
));
            $can_submit = $limits->max_listings == -1 || $current_listings < $limits->max_listings;
            
            if (!$can_submit) {
                echo '<div class="notice notice-warning">';
                echo '<p>' . __('You have reached your listing limit. Please upgrade your plan to submit more properties.', 'malisafi-mls') . '</p>';
                echo '<a href="' . esc_url(home_url('/pricing')) . '" class="button button-primary">' . __('Upgrade Plan', 'malisafi-mls') . '</a>';
                echo '</div>';
                return ob_get_clean();
            }
        }
        
        // Include property submission form
        echo '<div class="malisafi-property-submit-form">';
        echo '<h2>' . __('Submit a Property', 'malisafi-mls') . '</h2>';
        echo '<p>' . __('Property submission form will be displayed here.', 'malisafi-mls') . '</p>';
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Modern properties listing with filters
     * Shortcode: [malisafi_properties_modern] or [malisafi_properties]
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public static function properties_with_filters($atts = array()) {
        // Extract shortcode attributes with defaults
        $atts = shortcode_atts(array(
            'type'      => '',
            'status'    => '',
            'location'  => '',
            'featured'  => '',
            'count'     => 21,
            'offset'    => 0,
            'orderby'   => 'date',
            'order'     => 'DESC',
        ), $atts, 'malisafi_properties');
        
        ob_start();
        
        // Include the modern properties template
        include MALISAFI_MLS_PATH . 'templates/properties-filters.php';
        
        return ob_get_clean();
    }
    
    /**
     * Minimalist properties listing with filters
     * Shortcode: [malisafi_properties_minimalist]
     * 
     * Clean design with:
     * - Row 1: Status buttons (For Rent, For Sale, Short Stay)
     * - Row 2: Property type dropdown, City dropdown, Search input
     *
     * @return string
     */
    public static function properties_minimalist_filters() {
        ob_start();
        
        // Include the minimalist properties template
        include MALISAFI_MLS_PATH . 'templates/properties-filters-minimalist.php';
        
        return ob_get_clean();
    }
    
    /**
     * Registration form shortcode
     * Shortcode: [malisafi_registration] or [malisafi_register]
     *
     * @return string
     */
    public static function registration_form() {
        // If user is already logged in, redirect to dashboard
        if (is_user_logged_in()) {
            return '<div class="malisafi-notice">
                <p>' . __('You are already logged in.', 'malisafi-mls') . ' 
                <a href="' . home_url('/dashboard') . '">' . __('Go to Dashboard', 'malisafi-mls') . '</a></p>
            </div>';
        }
        
        ob_start();
        
        // Include the registration form template
        include MALISAFI_MLS_PATH . 'templates/registration-form.php';
        
        return ob_get_clean();
    }
    
    /**
     * Property map view shortcode
     * Shortcode: [malisafi_property_map]
     * 
     * Parameters:
     * - type: Property type filter
     * - status: Property status filter
     * - location: Location filter (taxonomy term)
     * - count: Number of properties (default: all)
     * - height: Map height in pixels (default: 600)
     * - zoom: Initial zoom level (default: 12)
     * - cluster: Enable marker clustering (default: yes)
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public static function property_map($atts) {
        $atts = shortcode_atts(array(
            'type' => '',
            'status' => '',
            'location' => '',
            'count' => -1,
            'height' => 600,
            'zoom' => 12,
            'cluster' => 'yes'
        ), $atts);
        
        ob_start();
        
        // Include the property map template
        include MALISAFI_MLS_PATH . 'templates/property-map.php';
        
        return ob_get_clean();
    }
    
    /**
     * City list shortcode
     * Shortcode: [malisafi_city_list]
     * 
     * Displays a list/grid of cities with property counts
     * Clicking a city redirects to search page with city preselected
     * 
     * Parameters:
     * - layout: Display layout (grid, list - default: grid)
     * - columns: Number of columns for grid (2, 3, 4, 5 - default: 4)
     * - orderby: Order by (name, count - default: count)
     * - order: Sort order (ASC, DESC - default: DESC)
     * - show_count: Show property count (yes, no - default: yes)
     * - show_image: Show city image (yes, no - default: yes)
     * - search_page: URL of search page (default: /properties)
     * - min_count: Minimum properties to show city (default: 1)
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public static function city_list($atts) {
        $atts = shortcode_atts(array(
            'layout' => 'grid',
            'columns' => 4,
            'orderby' => 'count',
            'order' => 'DESC',
            'show_count' => 'yes',
            'show_image' => 'yes',
            'search_page' => '/properties',
            'min_count' => 1
        ), $atts);
        
        ob_start();
        
        // Include the city list template
        include MALISAFI_MLS_PATH . 'templates/city-list.php';
        
        return ob_get_clean();
    }
    
    /**
     * Agent profile shortcode
     * Shortcode: [malisafi_agent id="123"]
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    public static function agent_profile($atts) {
        $atts = shortcode_atts(array(
            'id' => 0
        ), $atts);
        
        // Enqueue agent profile assets
        wp_enqueue_style(
            'malisafi-agent-profile',
            MALISAFI_MLS_URL . 'assets/css/agent-profile.css',
            array('malisafi-mls-variables'),
            MALISAFI_MLS_VERSION
        );
        
        wp_enqueue_script(
            'malisafi-agent-actions',
            MALISAFI_MLS_URL . 'assets/js/agent-actions.js',
            array('jquery'),
            MALISAFI_MLS_VERSION,
            true
        );
        
        ob_start();
        
        // Include the agent profile template
        include MALISAFI_MLS_PATH . 'templates/agent-profile.php';
        
        return ob_get_clean();
    }
    
    /**
     * Agents list shortcode
     * Shortcode: [malisafi_agents]
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    public static function agents_list($atts) {
        $atts = shortcode_atts(array(
            'count' => -1,
            'orderby' => 'name',
            'order' => 'ASC',
            'layout' => 'grid',
            'columns' => 3
        ), $atts);
        
        // Enqueue agent profile CSS for list cards
        wp_enqueue_style(
            'malisafi-agent-profile',
            MALISAFI_MLS_URL . 'assets/css/agent-profile.css',
            array('malisafi-mls-variables'),
            MALISAFI_MLS_VERSION
        );
        
        ob_start();
        
        // Include the agents list template
        include MALISAFI_MLS_PATH . 'templates/agents-list.php';
        
        return ob_get_clean();
    }
    
    /**
     * Add Property page shortcode
     * Shortcode: [malisafi_add_property]
     * 
     * @param array $atts Shortcode attributes
     * @return string
     */
    public static function add_property_page($atts) {
        $atts = shortcode_atts(array(
            'redirect' => ''
        ), $atts);
        
        // Enqueue modern styles
        wp_enqueue_style(
            'malisafi-add-property',
            MALISAFI_MLS_URL . 'assets/css/add-property-page.css',
            array('malisafi-mls-variables'),
            MALISAFI_MLS_VERSION
        );
        
        ob_start();
        
        // Include the add property page template
        include MALISAFI_MLS_PATH . 'templates/add-property-page.php';
        
        return ob_get_clean();
    }
}

// Initialize shortcodes
Malisafi_Shortcodes::init();
