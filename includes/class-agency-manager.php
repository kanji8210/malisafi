<?php
/**
 * Agency Management Class
 *
 * Handles agency profiles, agent assignments, and agency operations
 *
 * @package MalisafiMLS
 * @since 1.0.1
 */

namespace MalisafiMLS;

if (!defined('ABSPATH')) {
    exit;
}

class Agency_Manager {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Hook into user profile updates to handle agency associations
        add_action('profile_update', array($this, 'handle_user_role_change'), 10, 2);
        add_action('set_user_role', array($this, 'handle_user_role_change'), 10, 2);
    }

    /**
     * Create or update agency profile
     */
    public static function save_agency_profile($user_id, $agency_data) {
        global $wpdb;

        $table = $wpdb->prefix . 'mf_agencies';

        $data = array(
            'user_id' => $user_id,
            'agency_name' => sanitize_text_field($agency_data['agency_name']),
            'agency_description' => sanitize_textarea_field($agency_data['agency_description'] ?? ''),
            'agency_logo' => esc_url_raw($agency_data['agency_logo'] ?? ''),
            'agency_website' => esc_url_raw($agency_data['agency_website'] ?? ''),
            'agency_email' => sanitize_email($agency_data['agency_email'] ?? ''),
            'agency_phone' => sanitize_text_field($agency_data['agency_phone'] ?? ''),
            'agency_address' => sanitize_textarea_field($agency_data['agency_address'] ?? ''),
            'license_number' => sanitize_text_field($agency_data['license_number'] ?? ''),
            'established_year' => intval($agency_data['established_year'] ?? 0),
            'social_facebook' => esc_url_raw($agency_data['social_facebook'] ?? ''),
            'social_twitter' => esc_url_raw($agency_data['social_twitter'] ?? ''),
            'social_linkedin' => esc_url_raw($agency_data['social_linkedin'] ?? ''),
            'social_instagram' => esc_url_raw($agency_data['social_instagram'] ?? ''),
            'is_verified' => isset($agency_data['is_verified']) ? 1 : 0,
            'is_active' => isset($agency_data['is_active']) ? 1 : 1,
        );

        // Check if agency profile already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE user_id = %d",
            $user_id
        ));

        if ($existing) {
            $result = $wpdb->update($table, $data, array('user_id' => $user_id));
            return $result !== false;
        } else {
            $result = $wpdb->insert($table, $data);
            return $result !== false;
        }
    }

    /**
     * Update agency profile
     */
    public static function update_agency($agency_id, $agency_data) {
        global $wpdb;

        $table = $wpdb->prefix . 'mf_agencies';

        $data = array(
            'agency_name' => sanitize_text_field($agency_data['agency_name']),
            'agency_description' => sanitize_textarea_field($agency_data['agency_description'] ?? ''),
            'agency_logo' => esc_url_raw($agency_data['agency_logo'] ?? ''),
            'agency_website' => esc_url_raw($agency_data['agency_website'] ?? ''),
            'agency_email' => sanitize_email($agency_data['agency_email']),
            'agency_phone' => sanitize_text_field($agency_data['agency_phone'] ?? ''),
            'agency_address' => sanitize_textarea_field($agency_data['agency_address'] ?? ''),
            'is_active' => isset($agency_data['is_active']) ? 1 : 0,
            'updated_at' => current_time('mysql'),
        );

        $where = array('id' => $agency_id);

        $result = $wpdb->update($table, $data, $where);
        return $result !== false;
    }

    /**
     * Delete agency
     */
    public static function delete_agency($agency_id) {
        global $wpdb;

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Remove all agents from agency
            $wpdb->delete(
                $wpdb->prefix . 'mf_agency_agents',
                array('agency_id' => $agency_id)
            );

            // Delete agency profile
            $result = $wpdb->delete(
                $wpdb->prefix . 'mf_agencies',
                array('id' => $agency_id)
            );

            if ($result === false) {
                throw new Exception('Failed to delete agency');
            }

            $wpdb->query('COMMIT');
            return true;

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return false;
        }
    }

    /**
     * Get agency profile by user ID
     */
    public static function get_agency_profile($user_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'mf_agencies';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d AND is_active = 1",
            $user_id
        ));
    }

    /**
     * Add agent to agency
     */
    public static function add_agent_to_agency($agency_user_id, $agent_user_id, $role = 'agent', $commission_split = 0.00) {
        global $wpdb;

        // Verify agency exists and user is agency owner
        $agency = self::get_agency_profile($agency_user_id);
        if (!$agency) {
            return new WP_Error('invalid_agency', __('Agency profile not found.', 'malisafi-mls'));
        }

        // Check if agent is already in another agency
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}mf_agency_agents
             WHERE agent_id = %d AND is_active = 1",
            $agent_user_id
        ));

        if ($existing) {
            return new WP_Error('agent_already_assigned', __('Agent is already assigned to an agency.', 'malisafi-mls'));
        }

        $table = $wpdb->prefix . 'mf_agency_agents';

        $result = $wpdb->insert($table, array(
            'agency_id' => $agency->id,
            'agent_id' => $agent_user_id,
            'role_in_agency' => $role,
            'commission_split' => $commission_split,
            'is_active' => 1
        ));

        return $result !== false;
    }

    /**
     * Remove agent from agency
     */
    public static function remove_agent_from_agency($agency_user_id, $agent_user_id) {
        global $wpdb;

        $agency = self::get_agency_profile($agency_user_id);
        if (!$agency) {
            return false;
        }

        $table = $wpdb->prefix . 'mf_agency_agents';

        $result = $wpdb->update(
            $table,
            array('is_active' => 0, 'left_at' => current_time('mysql')),
            array('agency_id' => $agency->id, 'agent_id' => $agent_user_id)
        );

        return $result !== false;
    }

    /**
     * Get agents for an agency
     */
    public static function get_agency_agents($agency_user_id) {
        global $wpdb;

        $agency = self::get_agency_profile($agency_user_id);
        if (!$agency) {
            return array();
        }

        $table = $wpdb->prefix . 'mf_agency_agents';
        $users_table = $wpdb->users;

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT aa.*, u.display_name, u.user_email
             FROM {$table} aa
             JOIN {$users_table} u ON aa.agent_id = u.ID
             WHERE aa.agency_id = %d AND aa.is_active = 1
             ORDER BY aa.joined_at DESC",
            $agency->id
        ));

        return $results;
    }

    /**
     * Get agency for an agent
     */
    public static function get_agent_agency($agent_user_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'mf_agency_agents';
        $agencies_table = $wpdb->prefix . 'mf_agencies';
        $users_table = $wpdb->users;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT a.*, u.display_name as owner_name, u.user_email as owner_email
             FROM {$table} aa
             JOIN {$agencies_table} a ON aa.agency_id = a.id
             JOIN {$users_table} u ON a.user_id = u.ID
             WHERE aa.agent_id = %d AND aa.is_active = 1 AND a.is_active = 1
             LIMIT 1",
            $agent_user_id
        ));
    }

    /**
     * Handle user role changes - clean up agency associations if needed
     */
    public function handle_user_role_change($user_id, $old_user_data = null) {
        $user = get_userdata($user_id);
        $current_roles = $user->roles;

        // If user is no longer an agency, deactivate their agency profile
        if (!in_array('malisafi_agency', $current_roles)) {
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'mf_agencies',
                array('is_active' => 0),
                array('user_id' => $user_id)
            );

            // Also deactivate all agent associations
            $wpdb->update(
                $wpdb->prefix . 'mf_agency_agents',
                array('is_active' => 0, 'left_at' => current_time('mysql')),
                array('agency_id' => $user_id)
            );
        }
    }

    /**
     * Check if user can manage agency
     */
    public static function user_can_manage_agency($user_id) {
        return current_user_can('manage_agency_agents') || user_can($user_id, 'manage_agency_agents');
    }

    /**
     * Get agency statistics
     */
    public static function get_agency_stats($agency_user_id) {
        global $wpdb;

        $agency = self::get_agency_profile($agency_user_id);
        if (!$agency) {
            return false;
        }

        // Get agent count
        $agent_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mf_agency_agents
             WHERE agency_id = %d AND is_active = 1",
            $agency->id
        ));

        // Get property count (properties by agency agents)
        $agent_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT agent_id FROM {$wpdb->prefix}mf_agency_agents
             WHERE agency_id = %d AND is_active = 1",
            $agency->id
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
            'agency' => $agency,
            'agent_count' => $agent_count,
            'property_count' => $property_count,
            'agents' => self::get_agency_agents($agency_user_id)
        );
    }
}

// Initialize
Agency_Manager::get_instance();