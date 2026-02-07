<?php
/**
 * Agency Membership Admin
 *
 * Admin interface for managing agency membership plans and subscriptions
 *
 * @package MalisafiMLS
 * @since 1.0.1
 */

namespace MalisafiMLS;

if (!defined('ABSPATH')) {
    exit;
}

class Agency_Membership_Admin {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));

        // AJAX handlers
        add_action('wp_ajax_malisafi_save_membership_plan', array($this, 'ajax_save_plan'));
        add_action('wp_ajax_malisafi_delete_membership_plan', array($this, 'ajax_delete_plan'));
        add_action('wp_ajax_malisafi_get_plan_data', array($this, 'ajax_get_plan_data'));
        add_action('wp_ajax_malisafi_create_agency_subscription', array($this, 'ajax_create_subscription'));
        add_action('wp_ajax_malisafi_update_subscription_status', array($this, 'ajax_update_subscription_status'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'malisafi-dashboard',
            __('Agency Membership', 'malisafi-mls'),
            __('Agency Membership', 'malisafi-mls'),
            'manage_malisafi_settings',
            'malisafi-agency-membership',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_assets($hook) {
        if ($hook !== 'analytics_page_malisafi-agency-membership') {
            return;
        }

        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_style('wp-jquery-ui-dialog');

        wp_enqueue_style(
            'malisafi-agency-membership-admin',
            MALISAFI_MLS_PLUGIN_URL . 'assets/css/admin-agency-membership.css',
            array(),
            MALISAFI_MLS_VERSION
        );

        wp_enqueue_script(
            'malisafi-agency-membership-admin',
            MALISAFI_MLS_PLUGIN_URL . 'assets/js/admin-agency-membership.js',
            array('jquery', 'jquery-ui-dialog'),
            MALISAFI_MLS_VERSION,
            true
        );

        wp_localize_script('malisafi-agency-membership-admin', 'malisafiAgencyMembership', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('malisafi_agency_membership_nonce'),
            'i18n' => array(
                'confirmDelete' => __('Are you sure you want to delete this plan?', 'malisafi-mls'),
                'confirmDeleteSubscription' => __('Are you sure you want to cancel this subscription?', 'malisafi-mls'),
                'saveSuccess' => __('Plan saved successfully!', 'malisafi-mls'),
                'deleteSuccess' => __('Plan deleted successfully!', 'malisafi-mls'),
                'subscriptionCreated' => __('Subscription created successfully!', 'malisafi-mls'),
                'statusUpdated' => __('Subscription status updated!', 'malisafi-mls'),
                'error' => __('An error occurred. Please try again.', 'malisafi-mls'),
            )
        ));
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'malisafi-mls'));
        }

        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'plans';

        ?>
        <div class="wrap malisafi-agency-membership-admin">
            <h1 class="wp-heading-inline">
                <span class="dashicons dashicons-groups"></span>
                <?php _e('Agency Membership Management', 'malisafi-mls'); ?>
            </h1>

            <button class="page-title-action" id="add-new-plan-btn">
                <?php _e('Add New Plan', 'malisafi-mls'); ?>
            </button>

            <hr class="wp-header-end">

            <nav class="nav-tab-wrapper">
                <a href="?page=malisafi-agency-membership&tab=plans" class="nav-tab <?php echo $active_tab === 'plans' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Membership Plans', 'malisafi-mls'); ?>
                </a>
                <a href="?page=malisafi-agency-membership&tab=subscriptions" class="nav-tab <?php echo $active_tab === 'subscriptions' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Agency Subscriptions', 'malisafi-mls'); ?>
                </a>
            </nav>

            <div class="tab-content">
                <?php if ($active_tab === 'plans'): ?>
                    <?php $this->render_plans_tab(); ?>
                <?php elseif ($active_tab === 'subscriptions'): ?>
                    <?php $this->render_subscriptions_tab(); ?>
                <?php endif; ?>
            </div>

            <!-- Plan Edit Modal -->
            <div id="plan-modal" class="malisafi-modal" style="display:none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 id="modal-title"><?php _e('Add Membership Plan', 'malisafi-mls'); ?></h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form id="plan-form">
                            <input type="hidden" name="plan_id" id="plan_id" value="">

                            <div class="form-row">
                                <label for="plan_name"><?php _e('Plan Name', 'malisafi-mls'); ?> <span class="required">*</span></label>
                                <input type="text" name="plan_name" id="plan_name" required>
                            </div>

                            <div class="form-row">
                                <label for="plan_description"><?php _e('Description', 'malisafi-mls'); ?></label>
                                <textarea name="plan_description" id="plan_description" rows="3"></textarea>
                            </div>

                            <div class="form-row">
                                <label for="price"><?php _e('Price', 'malisafi-mls'); ?> <span class="required">*</span></label>
                                <input type="number" name="price" id="price" step="0.01" min="0" required>
                                <select name="currency" id="currency">
                                    <option value="KES">KES</option>
                                    <option value="USD">USD</option>
                                    <option value="EUR">EUR</option>
                                </select>
                                <select name="billing_interval" id="billing_interval">
                                    <option value="month"><?php _e('per month', 'malisafi-mls'); ?></option>
                                    <option value="year"><?php _e('per year', 'malisafi-mls'); ?></option>
                                </select>
                            </div>

                            <div class="form-row">
                                <label for="max_agents"><?php _e('Max Agents', 'malisafi-mls'); ?> <span class="required">*</span></label>
                                <input type="number" name="max_agents" id="max_agents" min="1" value="5" required>
                            </div>

                            <div class="form-row">
                                <label for="max_properties"><?php _e('Max Properties', 'malisafi-mls'); ?> <span class="required">*</span></label>
                                <input type="number" name="max_properties" id="max_properties" min="1" value="50" required>
                            </div>

                            <div class="form-row">
                                <label for="stripe_price_id"><?php _e('Stripe Price ID', 'malisafi-mls'); ?></label>
                                <input type="text" name="stripe_price_id" id="stripe_price_id" placeholder="price_...">
                            </div>

                            <div class="form-row checkbox-row">
                                <label>
                                    <input type="checkbox" name="is_popular" id="is_popular">
                                    <?php _e('Mark as Popular Plan', 'malisafi-mls'); ?>
                                </label>
                            </div>

                            <div class="form-row">
                                <label for="sort_order"><?php _e('Display Order', 'malisafi-mls'); ?></label>
                                <input type="number" name="sort_order" id="sort_order" min="0" value="0">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="button" id="cancel-plan-btn"><?php _e('Cancel', 'malisafi-mls'); ?></button>
                        <button type="button" class="button button-primary" id="save-plan-btn"><?php _e('Save Plan', 'malisafi-mls'); ?></button>
                    </div>
                </div>
            </div>

            <!-- Subscription Modal -->
            <div id="subscription-modal" class="malisafi-modal" style="display:none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3><?php _e('Create Agency Subscription', 'malisafi-mls'); ?></h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form id="subscription-form">
                            <div class="form-row">
                                <label for="subscription_agency"><?php _e('Select Agency', 'malisafi-mls'); ?> <span class="required">*</span></label>
                                <select name="agency_id" id="subscription_agency" required>
                                    <option value=""><?php _e('Choose agency...', 'malisafi-mls'); ?></option>
                                    <?php
                                    $agencies = $this->get_agencies_without_subscription();
                                    foreach ($agencies as $agency) {
                                        echo '<option value="' . esc_attr($agency->id) . '">' . esc_html($agency->agency_name) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-row">
                                <label for="subscription_plan"><?php _e('Select Plan', 'malisafi-mls'); ?> <span class="required">*</span></label>
                                <select name="plan_id" id="subscription_plan" required>
                                    <option value=""><?php _e('Choose plan...', 'malisafi-mls'); ?></option>
                                    <?php
                                    $plans = Agency_Membership_Manager::get_membership_plans();
                                    foreach ($plans as $plan) {
                                        echo '<option value="' . esc_attr($plan->id) . '">' . esc_html($plan->plan_name) . ' - ' . esc_html($plan->price) . ' ' . esc_html($plan->currency) . '/' . esc_html($plan->billing_interval) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="button" id="cancel-subscription-btn"><?php _e('Cancel', 'malisafi-mls'); ?></button>
                        <button type="button" class="button button-primary" id="create-subscription-btn"><?php _e('Create Subscription', 'malisafi-mls'); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render plans tab
     */
    private function render_plans_tab() {
        $plans = Agency_Membership_Manager::get_membership_plans(false);

        ?>
        <div class="plans-section">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Plan Name', 'malisafi-mls'); ?></th>
                        <th><?php _e('Price', 'malisafi-mls'); ?></th>
                        <th><?php _e('Limits', 'malisafi-mls'); ?></th>
                        <th><?php _e('Status', 'malisafi-mls'); ?></th>
                        <th><?php _e('Actions', 'malisafi-mls'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($plans)): ?>
                        <tr>
                            <td colspan="5"><?php _e('No membership plans found.', 'malisafi-mls'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($plans as $plan): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($plan->plan_name); ?></strong>
                                    <?php if ($plan->is_popular): ?>
                                        <span class="popular-badge"><?php _e('Popular', 'malisafi-mls'); ?></span>
                                    <?php endif; ?>
                                    <br><small><?php echo esc_html($plan->plan_description); ?></small>
                                </td>
                                <td>
                                    <?php echo esc_html($plan->price . ' ' . $plan->currency); ?>
                                    <br><small><?php echo esc_html($plan->billing_interval); ?>ly</small>
                                </td>
                                <td>
                                    <?php echo esc_html($plan->max_agents); ?> agents<br>
                                    <?php echo esc_html($plan->max_properties); ?> properties
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $plan->is_active ? 'active' : 'inactive'; ?>">
                                        <?php echo $plan->is_active ? __('Active', 'malisafi-mls') : __('Inactive', 'malisafi-mls'); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="button button-small edit-plan-btn" data-plan-id="<?php echo esc_attr($plan->id); ?>">
                                        <?php _e('Edit', 'malisafi-mls'); ?>
                                    </button>
                                    <button class="button button-small button-link-delete delete-plan-btn" data-plan-id="<?php echo esc_attr($plan->id); ?>">
                                        <?php _e('Delete', 'malisafi-mls'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render subscriptions tab
     */
    private function render_subscriptions_tab() {
        global $wpdb;

        $subscriptions = $wpdb->get_results(
            "SELECT s.*, a.agency_name, p.plan_name, p.price, p.currency, p.billing_interval
             FROM {$wpdb->prefix}mf_agency_subscriptions s
             JOIN {$wpdb->prefix}mf_agencies a ON s.agency_id = a.id
             JOIN {$wpdb->prefix}mf_agency_membership_plans p ON s.plan_id = p.id
             ORDER BY s.created_at DESC"
        );

        ?>
        <div class="subscriptions-section">
            <button class="button button-primary" id="create-subscription-btn" style="margin-bottom: 15px;">
                <?php _e('Create New Subscription', 'malisafi-mls'); ?>
            </button>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Agency', 'malisafi-mls'); ?></th>
                        <th><?php _e('Plan', 'malisafi-mls'); ?></th>
                        <th><?php _e('Limits', 'malisafi-mls'); ?></th>
                        <th><?php _e('Status', 'malisafi-mls'); ?></th>
                        <th><?php _e('Created', 'malisafi-mls'); ?></th>
                        <th><?php _e('Actions', 'malisafi-mls'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subscriptions)): ?>
                        <tr>
                            <td colspan="6"><?php _e('No subscriptions found.', 'malisafi-mls'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($subscriptions as $sub): ?>
                            <tr>
                                <td><?php echo esc_html($sub->agency_name); ?></td>
                                <td>
                                    <?php echo esc_html($sub->plan_name); ?>
                                    <br><small><?php echo esc_html($sub->price . ' ' . $sub->currency . '/' . $sub->billing_interval); ?></small>
                                </td>
                                <td>
                                    <?php echo esc_html($sub->max_agents); ?> agents<br>
                                    <?php echo esc_html($sub->max_properties); ?> properties
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo esc_attr($sub->status); ?>">
                                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $sub->status))); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($sub->created_at))); ?></td>
                                <td>
                                    <select class="subscription-status" data-subscription-id="<?php echo esc_attr($sub->id); ?>">
                                        <option value="active" <?php selected($sub->status, 'active'); ?>><?php _e('Active', 'malisafi-mls'); ?></option>
                                        <option value="canceled" <?php selected($sub->status, 'canceled'); ?>><?php _e('Canceled', 'malisafi-mls'); ?></option>
                                        <option value="past_due" <?php selected($sub->status, 'past_due'); ?>><?php _e('Past Due', 'malisafi-mls'); ?></option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Get agencies without active subscriptions
     */
    private function get_agencies_without_subscription() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT a.* FROM {$wpdb->prefix}mf_agencies a
             LEFT JOIN {$wpdb->prefix}mf_agency_subscriptions s ON a.id = s.agency_id AND s.status = 'active'
             WHERE s.id IS NULL AND a.is_active = 1
             ORDER BY a.agency_name ASC"
        );
    }

    /**
     * AJAX: Save membership plan
     */
    public function ajax_save_plan() {
        check_ajax_referer('malisafi_agency_membership_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'malisafi-mls'));
        }

        $plan_data = array(
            'id' => intval($_POST['plan_id'] ?? 0),
            'plan_name' => sanitize_text_field($_POST['plan_name']),
            'plan_description' => sanitize_textarea_field($_POST['plan_description'] ?? ''),
            'price' => floatval($_POST['price']),
            'currency' => sanitize_text_field($_POST['currency'] ?? 'KES'),
            'billing_interval' => sanitize_text_field($_POST['billing_interval'] ?? 'month'),
            'max_agents' => intval($_POST['max_agents']),
            'max_properties' => intval($_POST['max_properties']),
            'stripe_price_id' => sanitize_text_field($_POST['stripe_price_id'] ?? ''),
            'is_popular' => isset($_POST['is_popular']),
            'sort_order' => intval($_POST['sort_order'] ?? 0),
        );

        $result = Agency_Membership_Manager::save_membership_plan($plan_data);

        if ($result) {
            wp_send_json_success(__('Plan saved successfully!', 'malisafi-mls'));
        } else {
            wp_send_json_error(__('Failed to save plan.', 'malisafi-mls'));
        }
    }

    /**
     * AJAX: Get plan data for editing
     */
    public function ajax_get_plan_data() {
        check_ajax_referer('malisafi_agency_membership_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'malisafi-mls'));
        }

        $plan_id = intval($_POST['plan_id']);
        $plan = Agency_Membership_Manager::get_membership_plan($plan_id);

        if ($plan) {
            wp_send_json_success($plan);
        } else {
            wp_send_json_error(__('Plan not found.', 'malisafi-mls'));
        }
    }
    public function ajax_delete_plan() {
        check_ajax_referer('malisafi_agency_membership_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'malisafi-mls'));
        }

        $plan_id = intval($_POST['plan_id']);

        $result = Agency_Membership_Manager::delete_membership_plan($plan_id);

        if ($result === true) {
            wp_send_json_success(__('Plan deleted successfully!', 'malisafi-mls'));
        } elseif (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_error(__('Failed to delete plan.', 'malisafi-mls'));
        }
    }

    /**
     * AJAX: Create agency subscription
     */
    public function ajax_create_subscription() {
        check_ajax_referer('malisafi_agency_membership_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'malisafi-mls'));
        }

        $agency_id = intval($_POST['agency_id']);
        $plan_id = intval($_POST['plan_id']);

        $result = Agency_Membership_Manager::create_agency_subscription($agency_id, $plan_id);

        if ($result) {
            wp_send_json_success(__('Subscription created successfully!', 'malisafi-mls'));
        } else {
            wp_send_json_error(__('Failed to create subscription.', 'malisafi-mls'));
        }
    }

    /**
     * AJAX: Update subscription status
     */
    public function ajax_update_subscription_status() {
        check_ajax_referer('malisafi_agency_membership_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'malisafi-mls'));
        }

        $subscription_id = intval($_POST['subscription_id']);
        $status = sanitize_text_field($_POST['status']);

        $result = Agency_Membership_Manager::update_subscription_status($subscription_id, $status);

        if ($result !== false) {
            wp_send_json_success(__('Subscription status updated!', 'malisafi-mls'));
        } else {
            wp_send_json_error(__('Failed to update subscription status.', 'malisafi-mls'));
        }
    }
}

// Initialize
Agency_Membership_Admin::get_instance();