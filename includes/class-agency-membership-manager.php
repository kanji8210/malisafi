<?php
/**
 * Agency Membership Manager
 *
 * Handles agency membership plans, subscriptions, and limits enforcement
 *
 * @package MalisafiMLS
 * @since 1.0.1
 */

namespace MalisafiMLS;

if (!defined('ABSPATH')) {
    exit;
}

class Agency_Membership_Manager {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Hook into property/agent management to enforce limits
        add_action('pre_post_update', array($this, 'enforce_property_limits'), 10, 2);
        add_action('user_register', array($this, 'check_agency_agent_limits'), 10, 1);
        add_action('set_user_role', array($this, 'check_agency_agent_limits'), 10, 3);
    }

    /**
     * Create or update membership plan
     */
    public static function save_membership_plan($plan_data) {
        global $wpdb;

        $table = $wpdb->prefix . 'mf_agency_membership_plans';

        $data = array(
            'plan_name' => sanitize_text_field($plan_data['plan_name']),
            'plan_description' => sanitize_textarea_field($plan_data['plan_description'] ?? ''),
            'stripe_price_id' => sanitize_text_field($plan_data['stripe_price_id'] ?? ''),
            'price' => floatval($plan_data['price']),
            'currency' => sanitize_text_field($plan_data['currency'] ?? 'KES'),
            'billing_interval' => $plan_data['billing_interval'] ?? 'month',
            'max_agents' => intval($plan_data['max_agents'] ?? 5),
            'max_properties' => intval($plan_data['max_properties'] ?? 50),
            'features' => is_array($plan_data['features']) ? json_encode($plan_data['features']) : $plan_data['features'],
            'is_active' => isset($plan_data['is_active']) ? 1 : 1,
            'is_popular' => isset($plan_data['is_popular']) ? 1 : 0,
            'sort_order' => intval($plan_data['sort_order'] ?? 0),
        );

        if (isset($plan_data['id']) && $plan_data['id']) {
            // Update existing plan
            $result = $wpdb->update($table, $data, array('id' => $plan_data['id']));
            return $result !== false;
        } else {
            // Create new plan
            $result = $wpdb->insert($table, $data);
            return $result !== false ? $wpdb->insert_id : false;
        }
    }

    /**
     * Get all membership plans
     */
    public static function get_membership_plans($active_only = true) {
        global $wpdb;

        $table = $wpdb->prefix . 'mf_agency_membership_plans';
        $where = $active_only ? 'WHERE is_active = 1' : '';

        $sql = "SELECT * FROM {$table} {$where} ORDER BY sort_order ASC, price ASC";

        $results = $wpdb->get_results($sql);

        // Decode features JSON
        foreach ($results as &$plan) {
            if ($plan->features) {
                $plan->features = json_decode($plan->features, true);
            }
        }

        return $results;
    }

    /**
     * Get membership plan by ID
     */
    public static function get_membership_plan($plan_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'mf_agency_membership_plans';
        $plan = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $plan_id));

        if ($plan && $plan->features) {
            $plan->features = json_decode($plan->features, true);
        }

        return $plan;
    }

    /**
     * Create agency subscription
     */
    public static function create_agency_subscription($agency_id, $plan_id, $stripe_subscription_id = null) {
        global $wpdb;

        $plan = self::get_membership_plan($plan_id);
        if (!$plan) {
            return new WP_Error('invalid_plan', __('Membership plan not found.', 'malisafi-mls'));
        }

        $table = $wpdb->prefix . 'mf_agency_subscriptions';

        $data = array(
            'agency_id' => $agency_id,
            'plan_id' => $plan_id,
            'stripe_subscription_id' => $stripe_subscription_id,
            'status' => 'active',
            'max_agents' => $plan->max_agents,
            'max_properties' => $plan->max_properties,
        );

        $result = $wpdb->insert($table, $data);
        return $result !== false ? $wpdb->insert_id : false;
    }

    /**
     * Get agency subscription
     */
    public static function get_agency_subscription($agency_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'mf_agency_subscriptions';
        $agencies_table = $wpdb->prefix . 'mf_agencies';
        $plans_table = $wpdb->prefix . 'mf_agency_membership_plans';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT s.*, a.agency_name, p.plan_name, p.price, p.currency, p.billing_interval
             FROM {$table} s
             JOIN {$agencies_table} a ON s.agency_id = a.id
             JOIN {$plans_table} p ON s.plan_id = p.id
             WHERE s.agency_id = %d AND s.status = 'active'
             ORDER BY s.created_at DESC
             LIMIT 1",
            $agency_id
        ));
    }

    /**
     * Update subscription status
     */
    public static function update_subscription_status($subscription_id, $status, $stripe_data = array()) {
        global $wpdb;

        $table = $wpdb->prefix . 'mf_agency_subscriptions';

        $data = array('status' => $status);

        if (isset($stripe_data['current_period_start'])) {
            $data['current_period_start'] = date('Y-m-d H:i:s', $stripe_data['current_period_start']);
        }

        if (isset($stripe_data['current_period_end'])) {
            $data['current_period_end'] = date('Y-m-d H:i:s', $stripe_data['current_period_end']);
        }

        if (isset($stripe_data['cancel_at_period_end'])) {
            $data['cancel_at_period_end'] = $stripe_data['cancel_at_period_end'];
        }

        return $wpdb->update($table, $data, array('id' => $subscription_id));
    }

    /**
     * Check if agency can add more agents
     */
    public static function can_add_agent($agency_id) {
        $subscription = self::get_agency_subscription($agency_id);

        if (!$subscription) {
            return new WP_Error('no_subscription', __('Agency has no active subscription.', 'malisafi-mls'));
        }

        global $wpdb;
        $agent_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mf_agency_agents
             WHERE agency_id = %d AND is_active = 1",
            $agency_id
        ));

        return $agent_count < $subscription->max_agents;
    }

    /**
     * Check if agency can add more properties
     */
    public static function can_add_property($agency_id) {
        $subscription = self::get_agency_subscription($agency_id);

        if (!$subscription) {
            return new WP_Error('no_subscription', __('Agency has no active subscription.', 'malisafi-mls'));
        }

        // Get all properties by agency agents
        global $wpdb;
        $agent_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT agent_id FROM {$wpdb->prefix}mf_agency_agents
             WHERE agency_id = %d AND is_active = 1",
            $agency_id
        ));

        if (empty($agent_ids)) {
            return $subscription->max_properties > 0;
        }

        $placeholders = str_repeat('%d,', count($agent_ids) - 1) . '%d';
        $property_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts}
             WHERE post_author IN ($placeholders)
             AND post_type = 'malisafi_property'
             AND post_status IN ('publish', 'pending')",
            $agent_ids
        ));

        return $property_count < $subscription->max_properties;
    }

    /**
     * Enforce property limits before publishing
     */
    public function enforce_property_limits($post_id, $data) {
        if ($data['post_type'] !== 'malisafi_property' || $data['post_status'] !== 'publish') {
            return;
        }

        $author_id = $data['post_author'];

        // Check if author is part of an agency
        $agency = Agency_Manager::get_agent_agency($author_id);
        if (!$agency) {
            return; // Not an agency agent, no limits
        }

        if (!self::can_add_property($agency->id)) {
            // Prevent publishing by changing status to draft
            $data['post_status'] = 'draft';
            wp_update_post($data);

            // Add admin notice
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>' .
                     __('Property limit reached for your agency. Please upgrade your membership plan.', 'malisafi-mls') .
                     '</p></div>';
            });
        }
    }

    /**
     * Check agent limits when adding users to agency
     */
    public function check_agency_agent_limits($user_id, $role = null, $old_roles = null) {
        // This would be called when adding agents to agency
        // Implementation depends on how agents are added to agencies
    }

    /**
     * Get agency usage statistics
     */
    public static function get_agency_usage($agency_id) {
        $subscription = self::get_agency_subscription($agency_id);

        if (!$subscription) {
            return false;
        }

        global $wpdb;

        // Count agents
        $agent_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mf_agency_agents
             WHERE agency_id = %d AND is_active = 1",
            $agency_id
        ));

        // Count properties
        $agent_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT agent_id FROM {$wpdb->prefix}mf_agency_agents
             WHERE agency_id = %d AND is_active = 1",
            $agency_id
        ));

        $property_count = 0;
        if (!empty($agent_ids)) {
            $placeholders = str_repeat('%d,', count($agent_ids) - 1) . '%d';
            $property_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts}
                 WHERE post_author IN ($placeholders)
                 AND post_type = 'malisafi_property'
                 AND post_status IN ('publish', 'pending')",
                $agent_ids
            ));
        }

        return array(
            'subscription' => $subscription,
            'agents_used' => $agent_count,
            'agents_limit' => $subscription->max_agents,
            'properties_used' => $property_count,
            'properties_limit' => $subscription->max_properties,
            'agents_remaining' => max(0, $subscription->max_agents - $agent_count),
            'properties_remaining' => max(0, $subscription->max_properties - $property_count),
        );
    }

    /**
     * Delete membership plan
     */
    public static function delete_membership_plan($plan_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'mf_agency_membership_plans';

        // Check if plan is in use
        $in_use = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mf_agency_subscriptions
             WHERE plan_id = %d AND status = 'active'",
            $plan_id
        ));

        if ($in_use > 0) {
            return new WP_Error('plan_in_use', __('Cannot delete plan that has active subscriptions.', 'malisafi-mls'));
        }

        return $wpdb->delete($table, array('id' => $plan_id), array('%d'));
    }
}

// Initialize
Agency_Membership_Manager::get_instance();