<?php
/**
 * Featured Properties Handler
 * Manages featured property promotions and payments
 *
 * @package MalisafiMLS
 */

namespace MalisafiMLS;

/**
 * Featured_Properties class
 */
class Featured_Properties {
    
    /**
     * Featured property cost in KSH
     */
    const FEATURED_COST = 500;
    
    /**
     * Featured property duration in days
     */
    const FEATURED_DURATION = 30;
    
    /**
     * Check if current user can manage featured properties
     * Admins and Moderators can manage featured status
     */
    private function can_manage_featured() {
        return current_user_can('manage_options') || current_user_can('moderate_properties');
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        // Admin column to show featured status
        add_filter('manage_malisafi_property_posts_columns', array($this, 'add_featured_column'));
        add_action('manage_malisafi_property_posts_custom_column', array($this, 'display_featured_column'), 10, 2);
        
        // Add meta box for admin
        add_action('add_meta_boxes', array($this, 'add_featured_meta_box'));
        add_action('save_post_malisafi_property', array($this, 'save_featured_meta'), 10, 2);
        
        // AJAX handlers
        add_action('wp_ajax_malisafi_request_featured', array($this, 'ajax_request_featured'));
        add_action('wp_ajax_malisafi_process_featured_payment', array($this, 'ajax_process_featured_payment'));
        add_action('wp_ajax_malisafi_admin_toggle_featured', array($this, 'ajax_admin_toggle_featured'));
        
        // Cron to expire featured properties
        add_action('malisafi_check_featured_expiry', array($this, 'check_featured_expiry'));
        
        if (!wp_next_scheduled('malisafi_check_featured_expiry')) {
            wp_schedule_event(time(), 'daily', 'malisafi_check_featured_expiry');
        }
        
        // Agent dashboard integration
        add_action('wp_footer', array($this, 'featured_promotion_modal'));
    }
    
    /**
     * Add featured column to properties list
     */
    public function add_featured_column($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['featured'] = __('Featured', 'malisafi-mls');
            }
        }
        return $new_columns;
    }
    
    /**
     * Display featured column content
     */
    public function display_featured_column($column, $post_id) {
        if ($column === 'featured') {
            $is_featured = get_post_meta($post_id, '_malisafi_featured', true);
            $expires = get_post_meta($post_id, '_malisafi_featured_expires', true);
            
            if ($is_featured === '1') {
                $days_left = '';
                if ($expires) {
                    $diff = strtotime($expires) - time();
                    $days = floor($diff / (60 * 60 * 24));
                    $days_left = sprintf(__(' (%d days left)', 'malisafi-mls'), max(0, $days));
                }
                echo '<span style="color: #737d5d; font-weight: bold;">★ ' . __('Featured', 'malisafi-mls') . $days_left . '</span>';
                
                // Quick toggle for admin and moderator
                if ($this->can_manage_featured()) {
                    echo '<br><a href="#" class="mls-toggle-featured" data-property-id="' . $post_id . '" data-action="remove">' . __('Remove', 'malisafi-mls') . '</a>';
                }
            } else {
                echo '—';
                
                // Quick toggle for admin and moderator
                if ($this->can_manage_featured()) {
                    echo '<br><a href="#" class="mls-toggle-featured" data-property-id="' . $post_id . '" data-action="add">' . __('Make Featured', 'malisafi-mls') . '</a>';
                }
            }
        }
    }
    
    /**
     * Add featured meta box for admin
     */
    public function add_featured_meta_box() {
        add_meta_box(
            'malisafi_featured_meta',
            __('Featured Property', 'malisafi-mls'),
            array($this, 'render_featured_meta_box'),
            'malisafi_property',
            'side',
            'high'
        );
    }
    
    /**
     * Render featured meta box
     */
    public function render_featured_meta_box($post) {
        wp_nonce_field('malisafi_featured_meta', 'malisafi_featured_nonce');
        
        $is_featured = get_post_meta($post->ID, '_malisafi_featured', true);
        $featured_date = get_post_meta($post->ID, '_malisafi_featured_date', true);
        $expires = get_post_meta($post->ID, '_malisafi_featured_expires', true);
        $payment_id = get_post_meta($post->ID, '_malisafi_featured_payment_id', true);
        
        ?>
        <div class="malisafi-featured-controls">
            <p>
                <label>
                    <input type="checkbox" name="malisafi_featured" value="1" <?php checked($is_featured, '1'); ?> />
                    <strong><?php _e('Mark as Featured', 'malisafi-mls'); ?></strong>
                </label>
            </p>
            
            <?php if ($this->can_manage_featured()): ?>
                <p>
                    <label><?php _e('Featured Until:', 'malisafi-mls'); ?></label><br>
                    <input type="date" name="malisafi_featured_expires" 
                           value="<?php echo esc_attr($expires ? date('Y-m-d', strtotime($expires)) : ''); ?>" 
                           class="widefat" />
                    <span class="description"><?php _e('Leave empty for no expiration', 'malisafi-mls'); ?></span>
                </p>
            <?php endif; ?>
            
            <?php if ($is_featured === '1'): ?>
                <div class="mls-featured-info" style="background: #f0f0f0; padding: 10px; margin-top: 10px;">
                    <?php if ($featured_date): ?>
                        <p><strong><?php _e('Featured Since:', 'malisafi-mls'); ?></strong><br>
                        <?php echo date_i18n(get_option('date_format'), strtotime($featured_date)); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($expires): ?>
                        <p><strong><?php _e('Expires:', 'malisafi-mls'); ?></strong><br>
                        <?php 
                        echo date_i18n(get_option('date_format'), strtotime($expires));
                        $diff = strtotime($expires) - time();
                        $days = floor($diff / (60 * 60 * 24));
                        if ($days > 0) {
                            echo ' <span style="color: #737d5d;">(' . sprintf(__('%d days left', 'malisafi-mls'), $days) . ')</span>';
                        } else {
                            echo ' <span style="color: #c16b6b;">(' . __('Expired', 'malisafi-mls') . ')</span>';
                        }
                        ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if ($payment_id): ?>
                        <p><strong><?php _e('Payment ID:', 'malisafi-mls'); ?></strong><br>
                        <code><?php echo esc_html($payment_id); ?></code></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($this->can_manage_featured()): ?>
                <p class="description">
                    <?php _e('As admin/moderator, you can feature properties without payment.', 'malisafi-mls'); ?>
                </p>
            <?php endif; ?>
            
            <p class="description">
                <?php printf(__('Cost for agents: KSH %s for %d days', 'malisafi-mls'), number_format(self::FEATURED_COST), self::FEATURED_DURATION); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Save featured meta
     */
    public function save_featured_meta($post_id, $post) {
        // Verify nonce
        if (!isset($_POST['malisafi_featured_nonce']) || 
            !wp_verify_nonce($_POST['malisafi_featured_nonce'], 'malisafi_featured_meta')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions (admin or property owner)
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $old_featured = get_post_meta($post_id, '_malisafi_featured', true);
        $new_featured = isset($_POST['malisafi_featured']) ? '1' : '0';
        
        // Update featured status
        update_post_meta($post_id, '_malisafi_featured', $new_featured);
        
        // If newly featured and admin is setting it
        if ($new_featured === '1' && $old_featured !== '1' && $this->can_manage_featured()) {
            // Set featured date
            update_post_meta($post_id, '_malisafi_featured_date', current_time('mysql'));
            
            // Set expiration if provided
            if (!empty($_POST['malisafi_featured_expires'])) {
                update_post_meta($post_id, '_malisafi_featured_expires', sanitize_text_field($_POST['malisafi_featured_expires']));
            } else {
                // Default 30 days
                $expires = date('Y-m-d', strtotime('+' . self::FEATURED_DURATION . ' days'));
                update_post_meta($post_id, '_malisafi_featured_expires', $expires);
            }
            
            update_post_meta($post_id, '_malisafi_featured_payment_id', 'admin-' . time());
        }
        
        // If admin updated expiration date
        if ($this->can_manage_featured() && !empty($_POST['malisafi_featured_expires'])) {
            update_post_meta($post_id, '_malisafi_featured_expires', sanitize_text_field($_POST['malisafi_featured_expires']));
        }
        
        // If unfeatured, clear related meta
        if ($new_featured === '0' && $old_featured === '1') {
            delete_post_meta($post_id, '_malisafi_featured_date');
            delete_post_meta($post_id, '_malisafi_featured_expires');
        }
    }
    
    /**
     * AJAX: Request featured status (for agents)
     */
    public function ajax_request_featured() {
        check_ajax_referer('malisafi-featured-nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in.', 'malisafi-mls')));
        }
        
        $property_id = intval($_POST['property_id']);
        
        // Verify property exists and user owns it
        $property = get_post($property_id);
        if (!$property || $property->post_type !== 'malisafi_property') {
            wp_send_json_error(array('message' => __('Invalid property.', 'malisafi-mls')));
        }
        
        if ($property->post_author != get_current_user_id() && !$this->can_manage_featured()) {
            wp_send_json_error(array('message' => __('You do not own this property.', 'malisafi-mls')));
        }
        
        // Check if already featured
        $is_featured = get_post_meta($property_id, '_malisafi_featured', true);
        if ($is_featured === '1') {
            wp_send_json_error(array('message' => __('This property is already featured.', 'malisafi-mls')));
        }
        
        // Return payment info
        wp_send_json_success(array(
            'cost' => self::FEATURED_COST,
            'duration' => self::FEATURED_DURATION,
            'property_id' => $property_id,
            'property_title' => get_the_title($property_id)
        ));
    }
    
    /**
     * AJAX: Process featured payment
     */
    public function ajax_process_featured_payment() {
        check_ajax_referer('malisafi-featured-nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in.', 'malisafi-mls')));
        }
        
        $property_id = intval($_POST['property_id']);
        $payment_method = sanitize_text_field($_POST['payment_method']); // 'mpesa' or 'stripe'
        
        // Verify property
        $property = get_post($property_id);
        if (!$property || $property->post_type !== 'malisafi_property') {
            wp_send_json_error(array('message' => __('Invalid property.', 'malisafi-mls')));
        }
        
        if ($property->post_author != get_current_user_id() && !$this->can_manage_featured()) {
            wp_send_json_error(array('message' => __('You do not own this property.', 'malisafi-mls')));
        }
        
        // Process payment based on method
        if ($payment_method === 'mpesa') {
            // TODO: Integrate M-Pesa payment
            // For now, return pending status
            wp_send_json_success(array(
                'status' => 'pending',
                'message' => __('M-Pesa payment integration coming soon. Contact admin to feature this property.', 'malisafi-mls')
            ));
        } elseif ($payment_method === 'stripe') {
            // Create Stripe checkout session
            $session = $this->create_stripe_checkout($property_id);
            
            if (is_wp_error($session)) {
                wp_send_json_error(array('message' => $session->get_error_message()));
            }
            
            wp_send_json_success(array(
                'status' => 'redirect',
                'checkout_url' => $session->url
            ));
        } else {
            wp_send_json_error(array('message' => __('Invalid payment method.', 'malisafi-mls')));
        }
    }
    
    /**
     * Create Stripe checkout session for featured property
     */
    private function create_stripe_checkout($property_id) {
        if (!class_exists('\\Stripe\\Stripe')) {
            return new \WP_Error('stripe_missing', __('Stripe is not configured.', 'malisafi-mls'));
        }
        
        try {
            // Increase execution time for Stripe operations
            $original_time_limit = ini_get('max_execution_time');
            if ($original_time_limit < 180) {
                @set_time_limit(180);
            }
            
            $stripe_key = get_option('malisafi_stripe_secret_key');
            if (empty($stripe_key)) {
                return new \WP_Error('stripe_key', __('Stripe API key not configured.', 'malisafi-mls'));
            }
            
            \Stripe\Stripe::setApiKey($stripe_key);
            
            // Set timeout for API requests
            \Stripe\Stripe::setMaxNetworkRetries(2);
            \Stripe\ApiRequestor::setHttpClient(new \Stripe\HttpClient\CurlClient([
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_TIMEOUT => 80
            ]));
            
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'kes',
                        'product_data' => [
                            'name' => sprintf(__('Featured Listing - %s', 'malisafi-mls'), get_the_title($property_id)),
                            'description' => sprintf(__('Feature your property for %d days', 'malisafi-mls'), self::FEATURED_DURATION),
                        ],
                        'unit_amount' => self::FEATURED_COST * 100, // Amount in cents
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => add_query_arg('featured_success', $property_id, home_url('/agent-dashboard/')),
                'cancel_url' => add_query_arg('featured_cancelled', $property_id, home_url('/agent-dashboard/')),
                'metadata' => [
                    'property_id' => $property_id,
                    'user_id' => get_current_user_id(),
                    'type' => 'featured_property'
                ]
            ]);
            
            // Restore original time limit
            if (isset($original_time_limit)) {
                @set_time_limit($original_time_limit);
            }
            
            return $session;
            
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            // Network error - timeout, DNS failure, etc.
            error_log('Stripe API Connection Error: ' . $e->getMessage());
            return new \WP_Error('stripe_connection', __('Connection to payment gateway failed. Please try again.', 'malisafi-mls'));
        } catch (\Stripe\Exception\RateLimitException $e) {
            // Too many requests
            error_log('Stripe Rate Limit Error: ' . $e->getMessage());
            return new \WP_Error('stripe_rate_limit', __('Too many requests. Please wait a moment and try again.', 'malisafi-mls'));
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // Invalid parameters
            error_log('Stripe Invalid Request: ' . $e->getMessage());
            return new \WP_Error('stripe_invalid', __('Payment request invalid. Please contact support.', 'malisafi-mls'));
        } catch (\Stripe\Exception\AuthenticationException $e) {
            // Authentication failed
            error_log('Stripe Authentication Error: ' . $e->getMessage());
            return new \WP_Error('stripe_auth', __('Payment gateway authentication failed. Please contact administrator.', 'malisafi-mls'));
        } catch (\Exception $e) {
            // Generic error
            error_log('Stripe Error: ' . $e->getMessage());
            return new \WP_Error('stripe_error', $e->getMessage());
        } finally {
            // Always restore time limit
            if (isset($original_time_limit)) {
                @set_time_limit($original_time_limit);
            }
        }
    }
    
    /**
     * AJAX: Admin toggle featured (quick action)
     */
    public function ajax_admin_toggle_featured() {
        check_ajax_referer('malisafi-admin-nonce', 'nonce');
        
        if (!$this->can_manage_featured()) {
            wp_send_json_error(array('message' => __('Unauthorized.', 'malisafi-mls')));
        }
        
        $property_id = intval($_POST['property_id']);
        $action = sanitize_text_field($_POST['action_type']); // 'add' or 'remove'
        
        if ($action === 'add') {
            update_post_meta($property_id, '_malisafi_featured', '1');
            update_post_meta($property_id, '_malisafi_featured_date', current_time('mysql'));
            $expires = date('Y-m-d', strtotime('+' . self::FEATURED_DURATION . ' days'));
            update_post_meta($property_id, '_malisafi_featured_expires', $expires);
            update_post_meta($property_id, '_malisafi_featured_payment_id', 'admin-' . time());
            
            wp_send_json_success(array('message' => __('Property marked as featured.', 'malisafi-mls')));
        } else {
            update_post_meta($property_id, '_malisafi_featured', '0');
            delete_post_meta($property_id, '_malisafi_featured_date');
            delete_post_meta($property_id, '_malisafi_featured_expires');
            
            wp_send_json_success(array('message' => __('Property removed from featured.', 'malisafi-mls')));
        }
    }
    
    /**
     * Check and expire featured properties
     */
    public function check_featured_expiry() {
        global $wpdb;
        
        $today = current_time('Y-m-d');
        
        // Get all featured properties
        $featured_properties = $wpdb->get_results($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
             WHERE meta_key = '_malisafi_featured' 
             AND meta_value = '1'"
        ));
        
        foreach ($featured_properties as $item) {
            $expires = get_post_meta($item->post_id, '_malisafi_featured_expires', true);
            
            if ($expires && $expires < $today) {
                // Expire the featured status
                update_post_meta($item->post_id, '_malisafi_featured', '0');
                delete_post_meta($item->post_id, '_malisafi_featured_date');
                delete_post_meta($item->post_id, '_malisafi_featured_expires');
                
                // Notify property owner
                $this->notify_owner_expiry($item->post_id);
            }
        }
    }
    
    /**
     * Notify property owner about expiry
     */
    private function notify_owner_expiry($property_id) {
        $property = get_post($property_id);
        $owner = get_user_by('id', $property->post_author);
        
        if (!$owner) {
            return;
        }
        
        $subject = sprintf(__('Featured Listing Expired - %s', 'malisafi-mls'), get_bloginfo('name'));
        $message = sprintf(
            __("Hello %s,\n\nYour featured listing for '%s' has expired.\n\nTo feature it again, please visit your agent dashboard and renew the featured status.\n\nThank you!", 'malisafi-mls'),
            $owner->display_name,
            get_the_title($property_id)
        );
        
        wp_mail($owner->user_email, $subject, $message);
    }
    
    /**
     * Featured promotion modal for agents
     */
    public function featured_promotion_modal() {
        if (!is_user_logged_in() || !current_user_can('edit_properties')) {
            return;
        }
        
        ?>
        <div id="malisafi-featured-modal" class="malisafi-modal" style="display: none;">
            <div class="modal-content" style="max-width: 500px;">
                <div class="modal-header">
                    <h3><?php _e('Feature Your Property', 'malisafi-mls'); ?></h3>
                    <button class="modal-close" onclick="document.getElementById('malisafi-featured-modal').style.display='none'">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="featured-modal-content">
                        <p><?php _e('Make your property stand out by featuring it on the homepage and search results!', 'malisafi-mls'); ?></p>
                        
                        <div style="background: var(--mls-bg-secondary); padding: 20px; border-radius: 8px; margin: 20px 0;">
                            <h4 style="margin-top: 0;"><?php _e('Benefits:', 'malisafi-mls'); ?></h4>
                            <ul style="margin: 10px 0; padding-left: 20px;">
                                <li><?php _e('Top placement in search results', 'malisafi-mls'); ?></li>
                                <li><?php _e('Featured badge on property card', 'malisafi-mls'); ?></li>
                                <li><?php _e('Increased visibility and inquiries', 'malisafi-mls'); ?></li>
                                <li><?php printf(__('Active for %d days', 'malisafi-mls'), self::FEATURED_DURATION); ?></li>
                            </ul>
                            
                            <div style="text-align: center; margin: 20px 0;">
                                <div style="font-size: 32px; font-weight: bold; color: var(--mls-accent);">
                                    KSH <?php echo number_format(self::FEATURED_COST); ?>
                                </div>
                                <div style="color: var(--mls-text-secondary);">
                                    <?php printf(__('for %d days', 'malisafi-mls'), self::FEATURED_DURATION); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="button-secondary" onclick="document.getElementById('malisafi-featured-modal').style.display='none'">
                                <?php _e('Cancel', 'malisafi-mls'); ?>
                            </button>
                            <button type="button" class="button-primary" id="mls-proceed-featured-payment">
                                <?php _e('Proceed to Payment', 'malisafi-mls'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            var currentPropertyId = 0;
            
            // Open modal when clicking "Feature This Property" button
            $(document).on('click', '.mls-request-featured', function(e) {
                e.preventDefault();
                currentPropertyId = $(this).data('property-id');
                $('#malisafi-featured-modal').fadeIn();
            });
            
            // Process payment
            $('#mls-proceed-featured-payment').on('click', function() {
                if (!currentPropertyId) return;
                
                $(this).prop('disabled', true).text('<?php _e('Processing...', 'malisafi-mls'); ?>');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'malisafi_process_featured_payment',
                        nonce: '<?php echo wp_create_nonce('malisafi-featured-nonce'); ?>',
                        property_id: currentPropertyId,
                        payment_method: 'stripe' // Default to Stripe
                    },
                    success: function(response) {
                        if (response.success) {
                            if (response.data.status === 'redirect' && response.data.checkout_url) {
                                window.location.href = response.data.checkout_url;
                            } else {
                                alert(response.data.message);
                                location.reload();
                            }
                        } else {
                            alert(response.data.message);
                            $('#mls-proceed-featured-payment').prop('disabled', false).text('<?php _e('Proceed to Payment', 'malisafi-mls'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('An error occurred. Please try again.', 'malisafi-mls'); ?>');
                        $('#mls-proceed-featured-payment').prop('disabled', false).text('<?php _e('Proceed to Payment', 'malisafi-mls'); ?>');
                    }
                });
            });
            
            // Admin quick toggle
            $(document).on('click', '.mls-toggle-featured', function(e) {
                e.preventDefault();
                var propertyId = $(this).data('property-id');
                var actionType = $(this).data('action');
                
                if (!confirm('<?php _e('Are you sure?', 'malisafi-mls'); ?>')) return;
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'malisafi_admin_toggle_featured',
                        nonce: '<?php echo wp_create_nonce('malisafi-admin-nonce'); ?>',
                        property_id: propertyId,
                        action_type: actionType
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data.message);
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }
}
