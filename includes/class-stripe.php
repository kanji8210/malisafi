<?php
/**
 * Stripe Integration Handler
 *
 * @package MalisafiMLS
 */

/**
 * Malisafi_Stripe class
 */
class Malisafi_Stripe {
    
    /**
     * Stripe API instance
     */
    private static $stripe_api = null;
    
    /**
     * Initialize Stripe
     */
    public static function init() {
        // Check if Stripe library exists before loading
        if (!self::is_stripe_library_available()) {
            // Add admin notice
            add_action('admin_notices', array(__CLASS__, 'stripe_library_missing_notice'));
            return;
        }
        
        // Load Stripe library
        self::load_stripe_library();
        
        // AJAX handlers
        add_action('wp_ajax_malisafi_create_checkout', array(__CLASS__, 'ajax_create_checkout'));
        add_action('wp_ajax_malisafi_create_portal', array(__CLASS__, 'ajax_create_portal'));
        add_action('wp_ajax_malisafi_cancel_subscription', array(__CLASS__, 'ajax_cancel_subscription'));
        
        // Webhook handler
        add_action('rest_api_init', array(__CLASS__, 'register_webhook_endpoint'));
        
        // Cron for subscription checks
        add_action('malisafi_check_subscriptions', array(__CLASS__, 'check_subscription_status'));
        
        if (!wp_next_scheduled('malisafi_check_subscriptions')) {
            wp_schedule_event(time(), 'daily', 'malisafi_check_subscriptions');
        }
    }
    
    /**
     * Check if Stripe library is available
     */
    private static function is_stripe_library_available() {
        $stripe_init_path = MALISAFI_MLS_PATH . 'vendor/stripe/stripe-php/init.php';
        return file_exists($stripe_init_path) || class_exists('\\Stripe\\Stripe');
    }
    
    /**
     * Display admin notice when Stripe library is missing
     */
    public static function stripe_library_missing_notice() {
        $plugin_dir = plugin_dir_path(dirname(__FILE__));
        ?>
        <div class="notice notice-warning is-dismissible">
            <h3><?php _e('Malisafi MLS - Stripe Library Required', 'malisafi-mls'); ?></h3>
            <p><?php _e('The Stripe PHP library is not installed. Subscription features will not work until you install it.', 'malisafi-mls'); ?></p>
            <h4><?php _e('Installation Options:', 'malisafi-mls'); ?></h4>
            <ol>
                <li>
                    <strong><?php _e('With Composer (Recommended):', 'malisafi-mls'); ?></strong>
                    <pre style="background: #f0f0f0; padding: 10px; margin: 10px 0;">cd <?php echo esc_html($plugin_dir); ?>
composer install</pre>
                </li>
                <li>
                    <strong><?php _e('With PowerShell Script:', 'malisafi-mls'); ?></strong>
                    <pre style="background: #f0f0f0; padding: 10px; margin: 10px 0;">cd <?php echo esc_html($plugin_dir); ?>
.\install-stripe.ps1</pre>
                </li>
                <li>
                    <strong><?php _e('Manual Installation:', 'malisafi-mls'); ?></strong>
                    <ul style="margin-left: 20px;">
                        <li><?php _e('Download from:', 'malisafi-mls'); ?> <a href="https://github.com/stripe/stripe-php/releases" target="_blank">https://github.com/stripe/stripe-php/releases</a></li>
                        <li><?php _e('Extract to:', 'malisafi-mls'); ?> <code><?php echo esc_html($plugin_dir); ?>vendor/stripe/stripe-php/</code></li>
                    </ul>
                </li>
            </ol>
            <p>
                <strong><?php _e('Documentation:', 'malisafi-mls'); ?></strong>
                <a href="<?php echo esc_url(plugins_url('QUICK_START.md', dirname(__FILE__))); ?>" target="_blank">QUICK_START.md</a> |
                <a href="<?php echo esc_url(plugins_url('STRIPE_SETUP_GUIDE.md', dirname(__FILE__))); ?>" target="_blank">STRIPE_SETUP_GUIDE.md</a>
            </p>
        </div>
        <?php
    }
    
    /**
     * Load Stripe PHP library
     */
    private static function load_stripe_library() {
        if (!class_exists('\\Stripe\\Stripe')) {
            $stripe_init_path = MALISAFI_MLS_PATH . 'vendor/stripe/stripe-php/init.php';
            if (file_exists($stripe_init_path)) {
                require_once $stripe_init_path;
            } else {
                return false;
            }
        }
        
        $secret_key = self::get_secret_key();
        if ($secret_key) {
            \Stripe\Stripe::setApiKey($secret_key);
        }
        
        return true;
    }
    
    /**
     * Get Stripe publishable key
     */
    public static function get_publishable_key() {
        $mode = get_option('malisafi_stripe_mode', 'test');
        
        if ($mode === 'live') {
            return get_option('malisafi_stripe_live_publishable_key', '');
        }
        
        return get_option('malisafi_stripe_test_publishable_key', '');
    }
    
    /**
     * Get Stripe secret key
     */
    private static function get_secret_key() {
        $mode = get_option('malisafi_stripe_mode', 'test');
        
        if ($mode === 'live') {
            return get_option('malisafi_stripe_live_secret_key', '');
        }
        
        return get_option('malisafi_stripe_test_secret_key', '');
    }
    
    /**
     * Check if Stripe is configured
     */
    public static function is_configured() {
        $publishable = self::get_publishable_key();
        $secret = self::get_secret_key();
        
        return !empty($publishable) && !empty($secret);
    }
    
    /**
     * Get subscription plans
     */
    /**
     * Get currency symbol from currency code
     * 
     * @param string $currency Currency code (USD, EUR, GBP, etc.)
     * @return string Currency symbol
     */
    public static function get_currency_symbol($currency) {
        $symbols = array(
            'USD' => '$',
            'KES' => 'KSh',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => 'CA$',
            'AUD' => 'A$',
            'JPY' => '¥',
            'CNY' => '¥',
            'INR' => '₹',
            'BRL' => 'R$',
            'MXN' => 'MX$',
            'CHF' => 'CHF',
            'SEK' => 'kr',
            'NOK' => 'kr',
            'DKK' => 'kr',
            'PLN' => 'zł',
            'RUB' => '₽',
            'ZAR' => 'R',
            'TRY' => '₺',
            'AED' => 'د.إ',
            'SAR' => '﷼',
        );
        
        return isset($symbols[$currency]) ? $symbols[$currency] : $currency;
    }

    /**
     * Format price with currency
     * 
     * @param float $price Price amount
     * @param string $currency Currency code
     * @return string Formatted price with symbol
     */
    public static function format_price($price, $currency = 'USD') {
        $symbol = self::get_currency_symbol($currency);
        
        // Currencies that don't use decimal places
        $no_decimal_currencies = array('KES', 'JPY', 'CNY');
        
        if (in_array($currency, $no_decimal_currencies)) {
            return $symbol . number_format($price, 0);
        }
        
        return $symbol . number_format($price, 2);
    }

    public static function get_plans() {
        // If admin has defined plans via the Plans screen, use them.
        $stored = get_option('malisafi_mls_plans', false);
        if ($stored && is_array($stored)) {
            return $stored;
        }

        // Fallback to hard-coded defaults
        return array(
            'agent_basic' => array(
                'name' => __('Agent Basic', 'malisafi-mls'),
                'price' => 29.99,
                'currency' => 'USD',
                'interval' => 'month',
                'features' => array(
                    __('5 property listings', 'malisafi-mls'),
                    __('Basic analytics', 'malisafi-mls'),
                    __('Email support', 'malisafi-mls'),
                ),
                'stripe_price_id' => get_option('malisafi_stripe_price_agent_basic', '')
            ),
            'agent_premium' => array(
                'name' => __('Agent Premium', 'malisafi-mls'),
                'price' => 99.99,
                'currency' => 'USD',
                'interval' => 'month',
                'features' => array(
                    __('Unlimited property listings', 'malisafi-mls'),
                    __('Featured listings', 'malisafi-mls'),
                    __('Advanced analytics', 'malisafi-mls'),
                    __('Priority support', 'malisafi-mls'),
                    __('Boost properties', 'malisafi-mls'),
                ),
                'stripe_price_id' => get_option('malisafi_stripe_price_agent_premium', '')
            ),
            'owner_basic' => array(
                'name' => __('Property Owner', 'malisafi-mls'),
                'price' => 19.99,
                'currency' => 'USD',
                'interval' => 'month',
                'features' => array(
                    __('3 property listings', 'malisafi-mls'),
                    __('Basic analytics', 'malisafi-mls'),
                    __('Email support', 'malisafi-mls'),
                ),
                'stripe_price_id' => get_option('malisafi_stripe_price_owner_basic', '')
            ),
            'developer' => array(
                'name' => __('Developer', 'malisafi-mls'),
                'price' => 199.99,
                'currency' => 'USD',
                'interval' => 'month',
                'features' => array(
                    __('Unlimited projects', 'malisafi-mls'),
                    __('Bulk import', 'malisafi-mls'),
                    __('Advanced analytics', 'malisafi-mls'),
                    __('Dedicated support', 'malisafi-mls'),
                    __('API access', 'malisafi-mls'),
                ),
                'stripe_price_id' => get_option('malisafi_stripe_price_developer', '')
            ),
        );
    }
    
    /**
     * Create Stripe customer
     */
    public static function create_customer($user_id) {
        if (!self::is_configured()) {
            return false;
        }
        
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        try {
            $customer = \Stripe\Customer::create(array(
                'email' => $user->user_email,
                'name' => $user->display_name,
                'metadata' => array(
                    'user_id' => $user_id,
                    'username' => $user->user_login
                )
            ));
            
            update_user_meta($user_id, '_malisafi_stripe_customer_id', $customer->id);
            
            return $customer->id;
        } catch (Exception $e) {
            error_log('Stripe customer creation error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get or create Stripe customer ID
     */
    public static function get_customer_id($user_id) {
        $customer_id = get_user_meta($user_id, '_malisafi_stripe_customer_id', true);
        
        if (empty($customer_id)) {
            $customer_id = self::create_customer($user_id);
        }
        
        return $customer_id;
    }
    
    /**
     * AJAX: Create checkout session
     */
    public static function ajax_create_checkout() {
        check_ajax_referer('malisafi_stripe_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Please login to continue.', 'malisafi-mls')));
        }
        
        $plan = sanitize_text_field($_POST['plan']);
        $plans = self::get_plans();
        
        if (!isset($plans[$plan])) {
            wp_send_json_error(array('message' => __('Invalid plan selected.', 'malisafi-mls')));
        }
        
        $plan_data = $plans[$plan];
        
        if (empty($plan_data['stripe_price_id'])) {
            wp_send_json_error(array('message' => __('Plan not configured. Please contact administrator.', 'malisafi-mls')));
        }
        
        $user_id = get_current_user_id();
        $customer_id = self::get_customer_id($user_id);
        
        if (!$customer_id) {
            wp_send_json_error(array('message' => __('Failed to create customer.', 'malisafi-mls')));
        }
        
        try {
            $session = \Stripe\Checkout\Session::create(array(
                'customer' => $customer_id,
                'payment_method_types' => array('card'),
                'line_items' => array(array(
                    'price' => $plan_data['stripe_price_id'],
                    'quantity' => 1,
                )),
                'mode' => 'subscription',
                'success_url' => add_query_arg('session_id', '{CHECKOUT_SESSION_ID}', home_url('/subscription-success/')),
                'cancel_url' => home_url('/subscription-cancelled/'),
                'metadata' => array(
                    'user_id' => $user_id,
                    'plan' => $plan
                )
            ));
            
            wp_send_json_success(array(
                'session_id' => $session->id
            ));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * AJAX: Create customer portal session
     */
    public static function ajax_create_portal() {
        check_ajax_referer('malisafi_stripe_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Please login to continue.', 'malisafi-mls')));
        }
        
        $user_id = get_current_user_id();
        $customer_id = self::get_customer_id($user_id);
        
        if (!$customer_id) {
            wp_send_json_error(array('message' => __('No customer found.', 'malisafi-mls')));
        }
        
        try {
            $session = \Stripe\BillingPortal\Session::create(array(
                'customer' => $customer_id,
                'return_url' => home_url('/my-account/')
            ));
            
            wp_send_json_success(array(
                'url' => $session->url
            ));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * AJAX: Cancel subscription
     */
    public static function ajax_cancel_subscription() {
        check_ajax_referer('malisafi_stripe_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Please login to continue.', 'malisafi-mls')));
        }
        
        $user_id = get_current_user_id();
        $subscription_id = get_user_meta($user_id, '_malisafi_stripe_subscription_id', true);
        
        if (empty($subscription_id)) {
            wp_send_json_error(array('message' => __('No active subscription found.', 'malisafi-mls')));
        }
        
        try {
            $subscription = \Stripe\Subscription::retrieve($subscription_id);
            $subscription->cancel();
            
            self::update_subscription_status($user_id, 'canceled');
            
            wp_send_json_success(array('message' => __('Subscription cancelled successfully.', 'malisafi-mls')));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * Register webhook endpoint
     */
    public static function register_webhook_endpoint() {
        register_rest_route('malisafi/v1', '/stripe-webhook', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'handle_webhook'),
            'permission_callback' => '__return_true'
        ));
    }
    
    /**
     * Handle Stripe webhook
     */
    public static function handle_webhook($request) {
        $payload = $request->get_body();
        $sig_header = $request->get_header('stripe_signature');
        $webhook_secret = get_option('malisafi_stripe_webhook_secret', '');
        
        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $webhook_secret);
        } catch (Exception $e) {
            return new WP_REST_Response(array('error' => $e->getMessage()), 400);
        }
        
        // Handle different event types
        switch ($event->type) {
            case 'checkout.session.completed':
                self::handle_checkout_completed($event->data->object);
                break;
                
            case 'customer.subscription.updated':
                self::handle_subscription_updated($event->data->object);
                break;
                
            case 'customer.subscription.deleted':
                self::handle_subscription_deleted($event->data->object);
                break;
                
            case 'invoice.payment_succeeded':
                self::handle_payment_succeeded($event->data->object);
                break;
                
            case 'invoice.payment_failed':
                self::handle_payment_failed($event->data->object);
                break;
        }
        
        return new WP_REST_Response(array('received' => true), 200);
    }
    
    /**
     * Handle checkout session completed
     */
    private static function handle_checkout_completed($session) {
        $user_id = $session->metadata->user_id;
        $plan = $session->metadata->plan;
        
        if (!$user_id || !$plan) {
            return;
        }
        
        // Get subscription
        $subscription = \Stripe\Subscription::retrieve($session->subscription);
        
        // Save subscription data
        self::save_subscription($user_id, $subscription, $plan);
        
        // Update user role
        self::update_user_role($user_id, $plan);
        
        // Update user limits
        self::update_user_limits($user_id, $plan);
    }
    
    /**
     * Handle subscription updated
     */
    private static function handle_subscription_updated($subscription) {
        $customer_id = $subscription->customer;
        $user_id = self::get_user_id_by_customer($customer_id);
        
        if (!$user_id) {
            return;
        }
        
        self::update_subscription_status($user_id, $subscription->status);
    }
    
    /**
     * Handle subscription deleted
     */
    private static function handle_subscription_deleted($subscription) {
        $customer_id = $subscription->customer;
        $user_id = self::get_user_id_by_customer($customer_id);
        
        if (!$user_id) {
            return;
        }
        
        self::update_subscription_status($user_id, 'canceled');
        
        // Downgrade to basic role
        $user = get_user_by('id', $user_id);
        $user->set_role('malisafi_client');
    }
    
    /**
     * Handle payment succeeded
     */
    private static function handle_payment_succeeded($invoice) {
        $customer_id = $invoice->customer;
        $user_id = self::get_user_id_by_customer($customer_id);
        
        if (!$user_id) {
            return;
        }
        
        // Log payment
        global $wpdb;
        $table = $wpdb->prefix . 'mf_subscriptions';
        
        $wpdb->update(
            $table,
            array('current_period_start' => date('Y-m-d H:i:s', $invoice->period_start)),
            array('user_id' => $user_id),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * Handle payment failed
     */
    private static function handle_payment_failed($invoice) {
        $customer_id = $invoice->customer;
        $user_id = self::get_user_id_by_customer($customer_id);
        
        if (!$user_id) {
            return;
        }
        
        // Send email notification
        $user = get_user_by('id', $user_id);
        wp_mail(
            $user->user_email,
            __('Payment Failed - Malisafi MLS', 'malisafi-mls'),
            __('Your recent payment has failed. Please update your payment method.', 'malisafi-mls')
        );
    }
    
    /**
     * Save subscription data
     */
    private static function save_subscription($user_id, $subscription, $plan) {
        global $wpdb;
        $table = $wpdb->prefix . 'mf_subscriptions';
        
        $wpdb->replace(
            $table,
            array(
                'user_id' => $user_id,
                'plan_type' => $plan,
                'status' => $subscription->status,
                'stripe_subscription_id' => $subscription->id,
                'current_period_start' => date('Y-m-d H:i:s', $subscription->current_period_start),
                'current_period_end' => date('Y-m-d H:i:s', $subscription->current_period_end),
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        update_user_meta($user_id, '_malisafi_stripe_subscription_id', $subscription->id);
    }
    
    /**
     * Update subscription status
     */
    private static function update_subscription_status($user_id, $status) {
        global $wpdb;
        $table = $wpdb->prefix . 'mf_subscriptions';
        
        $wpdb->update(
            $table,
            array('status' => $status),
            array('user_id' => $user_id),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * Update user role based on plan
     */
    private static function update_user_role($user_id, $plan) {
        $user = get_user_by('id', $user_id);
        
        if (!$user) {
            return;
        }
        
        $role_map = array(
            'agent_basic' => 'malisafi_agent_basic',
            'agent_premium' => 'malisafi_agent_premium',
            'owner_basic' => 'malisafi_owner',
            'developer' => 'malisafi_developer'
        );
        
        if (isset($role_map[$plan])) {
            $user->set_role($role_map[$plan]);
        }
    }
    
    /**
     * Update user limits
     */
    private static function update_user_limits($user_id, $plan) {
        global $wpdb;
        $table = $wpdb->prefix . 'mf_user_limits';
        
        $limits = array(
            'agent_basic' => array('max_listings' => 5, 'featured_listings' => 0, 'can_boost' => 0),
            'agent_premium' => array('max_listings' => -1, 'featured_listings' => 5, 'can_boost' => 1),
            'owner_basic' => array('max_listings' => 3, 'featured_listings' => 0, 'can_boost' => 0),
            'developer' => array('max_listings' => -1, 'featured_listings' => 10, 'can_boost' => 1),
        );
        
        if (isset($limits[$plan])) {
            $wpdb->replace(
                $table,
                array_merge(
                    array('user_id' => $user_id),
                    $limits[$plan],
                    array('analytics_access' => 1)
                ),
                array('%d', '%d', '%d', '%d', '%d')
            );
        }
    }
    
    /**
     * Get user ID by Stripe customer ID
     */
    private static function get_user_id_by_customer($customer_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = '_malisafi_stripe_customer_id' AND meta_value = %s",
            $customer_id
        ));
    }
    
    /**
     * Check subscription status (cron job)
     */
    public static function check_subscription_status() {
        global $wpdb;
        $table = $wpdb->prefix . 'mf_subscriptions';
        
        // Get expired subscriptions
        $expired = $wpdb->get_results(
            "SELECT * FROM {$table} WHERE status = 'active' AND current_period_end < NOW()"
        );
        
        foreach ($expired as $subscription) {
            // Check with Stripe
            try {
                $stripe_sub = \Stripe\Subscription::retrieve($subscription->stripe_subscription_id);
                self::update_subscription_status($subscription->user_id, $stripe_sub->status);
            } catch (Exception $e) {
                error_log('Failed to check subscription: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Get user subscription
     */
    public static function get_user_subscription($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'mf_subscriptions';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d",
            $user_id
        ));
    }
}
