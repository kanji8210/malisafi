<?php
/**
 * Agency Management Admin
 *
 * Admin interface for managing agencies and their agents
 *
 * @package MalisafiMLS
 * @since 1.0.1
 */

namespace MalisafiMLS;

if (!defined('ABSPATH')) {
    exit;
}

class Agency_Management_Admin {

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
        add_action('wp_ajax_malisafi_save_agency', array($this, 'ajax_save_agency'));
        add_action('wp_ajax_malisafi_get_agency_data', array($this, 'ajax_get_agency_data'));
        add_action('wp_ajax_malisafi_delete_agency', array($this, 'ajax_delete_agency'));
        add_action('wp_ajax_malisafi_add_agent_to_agency', array($this, 'ajax_add_agent_to_agency'));
        add_action('wp_ajax_malisafi_remove_agent_from_agency', array($this, 'ajax_remove_agent_from_agency'));
        add_action('wp_ajax_malisafi_get_available_agents', array($this, 'ajax_get_available_agents'));
        add_action('wp_ajax_malisafi_get_agency_agents', array($this, 'ajax_get_agency_agents'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'malisafi-dashboard',
            __('Agency Management', 'malisafi-mls'),
            __('Agencies', 'malisafi-mls'),
            'manage_malisafi_settings',
            'malisafi-agency-management',
            array($this, 'render_admin_page')
        );

        add_submenu_page(
            'malisafi-dashboard',
            __('Add New Agency', 'malisafi-mls'),
            __('Add New Agency', 'malisafi-mls'),
            'manage_malisafi_settings',
            'malisafi-agency-management-new',
            array($this, 'render_new_agency_page')
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, 'malisafi-agency-management') === false) {
            return;
        }

        wp_enqueue_media(); // For logo upload
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_style('wp-jquery-ui-dialog');

        wp_enqueue_style(
            'malisafi-agency-management-admin',
            MALISAFI_MLS_PLUGIN_URL . 'assets/css/admin-agency-management.css',
            array(),
            MALISAFI_MLS_VERSION
        );

        wp_enqueue_script(
            'malisafi-agency-management-admin',
            MALISAFI_MLS_PLUGIN_URL . 'assets/js/admin-agency-management.js',
            array('jquery', 'jquery-ui-dialog', 'jquery-ui-autocomplete'),
            MALISAFI_MLS_VERSION,
            true
        );

        wp_localize_script('malisafi-agency-management-admin', 'malisafiAgencyManagement', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('malisafi_agency_management_nonce'),
            'i18n' => array(
                'confirmDelete' => __('Are you sure you want to delete this agency? This action cannot be undone.', 'malisafi-mls'),
                'confirmRemoveAgent' => __('Are you sure you want to remove this agent from the agency?', 'malisafi-mls'),
                'saveSuccess' => __('Agency saved successfully!', 'malisafi-mls'),
                'deleteSuccess' => __('Agency deleted successfully!', 'malisafi-mls'),
                'agentAdded' => __('Agent added to agency successfully!', 'malisafi-mls'),
                'agentRemoved' => __('Agent removed from agency successfully!', 'malisafi-mls'),
                'error' => __('An error occurred. Please try again.', 'malisafi-mls'),
                'selectLogo' => __('Select Logo', 'malisafi-mls'),
                'changeLogo' => __('Change Logo', 'malisafi-mls'),
                'noAgentsFound' => __('No available agents found.', 'malisafi-mls'),
            )
        ));
    }

    /**
     * Render main admin page
     */
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'malisafi-mls'));
        }

        $agencies = $this->get_agencies_with_stats();

        ?>
        <div class="wrap malisafi-agency-management-admin">
            <h1 class="wp-heading-inline">
                <span class="dashicons dashicons-groups"></span>
                <?php _e('Agency Management', 'malisafi-mls'); ?>
            </h1>

            <a href="?page=malisafi-agency-management-new" class="page-title-action">
                <?php _e('Add New Agency', 'malisafi-mls'); ?>
            </a>

            <hr class="wp-header-end">

            <div class="agencies-overview">
                <div class="overview-card">
                    <h3><?php _e('Total Agencies', 'malisafi-mls'); ?></h3>
                    <div class="metric"><?php echo count($agencies); ?></div>
                </div>
                <div class="overview-card">
                    <h3><?php _e('Active Agencies', 'malisafi-mls'); ?></h3>
                    <div class="metric"><?php echo count(array_filter($agencies, function($a) { return $a->is_active; })); ?></div>
                </div>
                <div class="overview-card">
                    <h3><?php _e('Total Agents', 'malisafi-mls'); ?></h3>
                    <div class="metric"><?php echo array_sum(array_column($agencies, 'agent_count')); ?></div>
                </div>
                <div class="overview-card">
                    <h3><?php _e('Total Properties', 'malisafi-mls'); ?></h3>
                    <div class="metric"><?php echo array_sum(array_column($agencies, 'property_count')); ?></div>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Agency', 'malisafi-mls'); ?></th>
                        <th><?php _e('Contact', 'malisafi-mls'); ?></th>
                        <th><?php _e('Agents', 'malisafi-mls'); ?></th>
                        <th><?php _e('Properties', 'malisafi-mls'); ?></th>
                        <th><?php _e('Status', 'malisafi-mls'); ?></th>
                        <th><?php _e('Actions', 'malisafi-mls'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($agencies)): ?>
                        <tr>
                            <td colspan="6"><?php _e('No agencies found.', 'malisafi-mls'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($agencies as $agency): ?>
                            <tr>
                                <td>
                                    <div class="agency-info">
                                        <?php if ($agency->agency_logo): ?>
                                            <img src="<?php echo esc_url($agency->agency_logo); ?>" alt="" class="agency-logo-thumb">
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo esc_html($agency->agency_name); ?></strong>
                                            <br><small><?php echo esc_html($agency->agency_email); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php echo esc_html($agency->agency_phone); ?>
                                    <?php if ($agency->agency_website): ?>
                                        <br><a href="<?php echo esc_url($agency->agency_website); ?>" target="_blank" rel="noopener"><?php _e('Website', 'malisafi-mls'); ?></a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="agent-count"><?php echo intval($agency->agent_count); ?></span>
                                    <button class="button button-small manage-agents-btn" data-agency-id="<?php echo esc_attr($agency->id); ?>" data-agency-name="<?php echo esc_attr($agency->agency_name); ?>">
                                        <?php _e('Manage', 'malisafi-mls'); ?>
                                    </button>
                                </td>
                                <td><?php echo intval($agency->property_count); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $agency->is_active ? 'active' : 'inactive'; ?>">
                                        <?php echo $agency->is_active ? __('Active', 'malisafi-mls') : __('Inactive', 'malisafi-mls'); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="button button-small edit-agency-btn" data-agency-id="<?php echo esc_attr($agency->id); ?>">
                                        <?php _e('Edit', 'malisafi-mls'); ?>
                                    </button>
                                    <button class="button button-small button-link-delete delete-agency-btn" data-agency-id="<?php echo esc_attr($agency->id); ?>">
                                        <?php _e('Delete', 'malisafi-mls'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Agency Edit Modal -->
        <div id="agency-modal" class="malisafi-modal" style="display:none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 id="modal-title"><?php _e('Edit Agency', 'malisafi-mls'); ?></h3>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="agency-form">
                        <input type="hidden" name="agency_id" id="agency_id" value="">

                        <div class="form-row">
                            <label for="agency_name"><?php _e('Agency Name', 'malisafi-mls'); ?> <span class="required">*</span></label>
                            <input type="text" name="agency_name" id="agency_name" required>
                        </div>

                        <div class="form-row">
                            <label for="agency_description"><?php _e('Description', 'malisafi-mls'); ?></label>
                            <textarea name="agency_description" id="agency_description" rows="3"></textarea>
                        </div>

                        <div class="form-row">
                            <label for="agency_logo"><?php _e('Logo', 'malisafi-mls'); ?></label>
                            <div class="logo-upload-container">
                                <div id="logo-preview" class="logo-preview"></div>
                                <button type="button" id="upload-logo-btn" class="button"><?php _e('Select Logo', 'malisafi-mls'); ?></button>
                                <input type="hidden" name="agency_logo" id="agency_logo">
                            </div>
                        </div>

                        <div class="form-row">
                            <label for="agency_website"><?php _e('Website', 'malisafi-mls'); ?></label>
                            <input type="url" name="agency_website" id="agency_website" placeholder="https://">
                        </div>

                        <div class="form-row">
                            <label for="agency_email"><?php _e('Email', 'malisafi-mls'); ?> <span class="required">*</span></label>
                            <input type="email" name="agency_email" id="agency_email" required>
                        </div>

                        <div class="form-row">
                            <label for="agency_phone"><?php _e('Phone', 'malisafi-mls'); ?></label>
                            <input type="tel" name="agency_phone" id="agency_phone">
                        </div>

                        <div class="form-row">
                            <label for="agency_address"><?php _e('Address', 'malisafi-mls'); ?></label>
                            <textarea name="agency_address" id="agency_address" rows="3"></textarea>
                        </div>

                        <div class="form-row checkbox-row">
                            <label>
                                <input type="checkbox" name="is_active" id="is_active" checked>
                                <?php _e('Active Agency', 'malisafi-mls'); ?>
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="button" id="cancel-agency-btn"><?php _e('Cancel', 'malisafi-mls'); ?></button>
                    <button type="button" class="button button-primary" id="save-agency-btn"><?php _e('Save Agency', 'malisafi-mls'); ?></button>
                </div>
            </div>
        </div>

        <!-- Agents Management Modal -->
        <div id="agents-modal" class="malisafi-modal" style="display:none;">
            <div class="modal-content large-modal">
                <div class="modal-header">
                    <h3 id="agents-modal-title"><?php _e('Manage Agents', 'malisafi-mls'); ?></h3>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="agents-management">
                        <div class="current-agents-section">
                            <h4><?php _e('Current Agents', 'malisafi-mls'); ?></h4>
                            <div id="current-agents-list" class="agents-list"></div>
                        </div>

                        <div class="add-agent-section">
                            <h4><?php _e('Add Agent', 'malisafi-mls'); ?></h4>
                            <div class="form-row">
                                <label for="agent_search"><?php _e('Search Agent', 'malisafi-mls'); ?></label>
                                <input type="text" id="agent_search" placeholder="<?php _e('Type agent name or email...', 'malisafi-mls'); ?>">
                                <input type="hidden" id="selected_agent_id">
                            </div>
                            <div class="form-row">
                                <label for="agent_role"><?php _e('Role in Agency', 'malisafi-mls'); ?></label>
                                <select id="agent_role">
                                    <option value="agent"><?php _e('Agent', 'malisafi-mls'); ?></option>
                                    <option value="manager"><?php _e('Manager', 'malisafi-mls'); ?></option>
                                    <option value="senior_agent"><?php _e('Senior Agent', 'malisafi-mls'); ?></option>
                                </select>
                            </div>
                            <div class="form-row">
                                <label for="commission_split"><?php _e('Commission Split (%)', 'malisafi-mls'); ?></label>
                                <input type="number" id="commission_split" min="0" max="100" step="0.01" value="0.00">
                            </div>
                            <button type="button" class="button button-primary" id="add-agent-btn" disabled><?php _e('Add Agent', 'malisafi-mls'); ?></button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="button" id="close-agents-modal-btn"><?php _e('Close', 'malisafi-mls'); ?></button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render new agency page
     */
    public function render_new_agency_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'malisafi-mls'));
        }

        ?>
        <div class="wrap malisafi-agency-management-admin">
            <h1>
                <span class="dashicons dashicons-groups"></span>
                <?php _e('Add New Agency', 'malisafi-mls'); ?>
            </h1>

            <form id="new-agency-form" method="post">
                <?php wp_nonce_field('malisafi_new_agency', 'agency_nonce'); ?>

                <div class="form-row">
                    <label for="agency_name"><?php _e('Agency Name', 'malisafi-mls'); ?> <span class="required">*</span></label>
                    <input type="text" name="agency_name" id="agency_name" required>
                </div>

                <div class="form-row">
                    <label for="agency_description"><?php _e('Description', 'malisafi-mls'); ?></label>
                    <textarea name="agency_description" id="agency_description" rows="3"></textarea>
                </div>

                <div class="form-row">
                    <label for="agency_logo"><?php _e('Logo', 'malisafi-mls'); ?></label>
                    <div class="logo-upload-container">
                        <div id="logo-preview" class="logo-preview"></div>
                        <button type="button" id="upload-logo-btn" class="button"><?php _e('Select Logo', 'malisafi-mls'); ?></button>
                        <input type="hidden" name="agency_logo" id="agency_logo">
                    </div>
                </div>

                <div class="form-row">
                    <label for="agency_website"><?php _e('Website', 'malisafi-mls'); ?></label>
                    <input type="url" name="agency_website" id="agency_website" placeholder="https://">
                </div>

                <div class="form-row">
                    <label for="agency_email"><?php _e('Email', 'malisafi-mls'); ?> <span class="required">*</span></label>
                    <input type="email" name="agency_email" id="agency_email" required>
                </div>

                <div class="form-row">
                    <label for="agency_phone"><?php _e('Phone', 'malisafi-mls'); ?></label>
                    <input type="tel" name="agency_phone" id="agency_phone">
                </div>

                <div class="form-row">
                    <label for="agency_address"><?php _e('Address', 'malisafi-mls'); ?></label>
                    <textarea name="agency_address" id="agency_address" rows="3"></textarea>
                </div>

                <div class="form-row checkbox-row">
                    <label>
                        <input type="checkbox" name="is_active" id="is_active" checked>
                        <?php _e('Active Agency', 'malisafi-mls'); ?>
                    </label>
                </div>

                <div class="form-row">
                    <input type="submit" class="button button-primary" value="<?php _e('Create Agency', 'malisafi-mls'); ?>">
                    <a href="?page=malisafi-agency-management" class="button"><?php _e('Cancel', 'malisafi-mls'); ?></a>
                </div>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Logo upload functionality
            $('#upload-logo-btn').on('click', function(e) {
                e.preventDefault();
                var frame = wp.media({
                    title: '<?php _e('Select Agency Logo', 'malisafi-mls'); ?>',
                    button: { text: '<?php _e('Use this logo', 'malisafi-mls'); ?>' },
                    multiple: false
                });

                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#agency_logo').val(attachment.url);
                    $('#logo-preview').html('<img src="' + attachment.url + '" style="max-width: 100px; max-height: 100px;">');
                    $('#upload-logo-btn').text('<?php _e('Change Logo', 'malisafi-mls'); ?>');
                });

                frame.open();
            });

            // Form submission
            $('#new-agency-form').on('submit', function(e) {
                e.preventDefault();

                var formData = new FormData(this);
                formData.append('action', 'malisafi_save_agency');
                formData.append('nonce', '<?php echo wp_create_nonce('malisafi_agency_management_nonce'); ?>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            window.location.href = '?page=malisafi-agency-management&message=created';
                        } else {
                            alert(response.data || '<?php _e('Error saving agency', 'malisafi-mls'); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php _e('Error saving agency', 'malisafi-mls'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Get agencies with stats
     */
    private function get_agencies_with_stats() {
        global $wpdb;

        $agencies_table = $wpdb->prefix . 'mf_agencies';
        $agents_table = $wpdb->prefix . 'mf_agency_agents';
        $properties_table = $wpdb->prefix . 'posts';

        $query = "
            SELECT a.*,
                   COALESCE(agent_counts.agent_count, 0) as agent_count,
                   COALESCE(property_counts.property_count, 0) as property_count
            FROM {$agencies_table} a
            LEFT JOIN (
                SELECT agency_id, COUNT(DISTINCT agent_id) as agent_count
                FROM {$agents_table}
                GROUP BY agency_id
            ) agent_counts ON a.id = agent_counts.agency_id
            LEFT JOIN (
                SELECT aa.agency_id, COUNT(DISTINCT p.ID) as property_count
                FROM {$agents_table} aa
                JOIN {$properties_table} p ON p.post_author = aa.agent_id
                WHERE p.post_type = 'malisafi_property' AND p.post_status IN ('publish', 'pending')
                GROUP BY aa.agency_id
            ) property_counts ON a.id = property_counts.agency_id
            ORDER BY a.agency_name ASC
        ";

        return $wpdb->get_results($query);
    }

    /**
     * AJAX: Save agency
     */
    public function ajax_save_agency() {
        check_ajax_referer('malisafi_agency_management_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'malisafi-mls'));
        }

        $agency_data = array(
            'agency_name' => sanitize_text_field($_POST['agency_name']),
            'agency_description' => sanitize_textarea_field($_POST['agency_description'] ?? ''),
            'agency_logo' => esc_url_raw($_POST['agency_logo'] ?? ''),
            'agency_website' => esc_url_raw($_POST['agency_website'] ?? ''),
            'agency_email' => sanitize_email($_POST['agency_email']),
            'agency_phone' => sanitize_text_field($_POST['agency_phone'] ?? ''),
            'agency_address' => sanitize_textarea_field($_POST['agency_address'] ?? ''),
            'is_active' => isset($_POST['is_active']),
        );

        $agency_id = intval($_POST['agency_id'] ?? 0);

        if ($agency_id > 0) {
            // Update existing agency
            $result = Agency_Manager::update_agency($agency_id, $agency_data);
        } else {
            // Create new agency - need to create user first
            $user_id = $this->create_agency_user($agency_data);
            if (is_wp_error($user_id)) {
                wp_send_json_error($user_id->get_error_message());
            }
            $result = Agency_Manager::save_agency_profile($user_id, $agency_data);
        }

        if ($result) {
            wp_send_json_success(__('Agency saved successfully!', 'malisafi-mls'));
        } else {
            wp_send_json_error(__('Failed to save agency.', 'malisafi-mls'));
        }
    }

    /**
     * AJAX: Get agency data
     */
    public function ajax_get_agency_data() {
        check_ajax_referer('malisafi_agency_management_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'malisafi-mls'));
        }

        $agency_id = intval($_POST['agency_id']);
        global $wpdb;

        $agency = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}mf_agencies WHERE id = %d",
            $agency_id
        ));

        if ($agency) {
            wp_send_json_success($agency);
        } else {
            wp_send_json_error(__('Agency not found.', 'malisafi-mls'));
        }
    }

    /**
     * AJAX: Delete agency
     */
    public function ajax_delete_agency() {
        check_ajax_referer('malisafi_agency_management_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'malisafi-mls'));
        }

        $agency_id = intval($_POST['agency_id']);

        $result = Agency_Manager::delete_agency($agency_id);

        if ($result) {
            wp_send_json_success(__('Agency deleted successfully!', 'malisafi-mls'));
        } else {
            wp_send_json_error(__('Failed to delete agency.', 'malisafi-mls'));
        }
    }

    /**
     * AJAX: Add agent to agency
     */
    public function ajax_add_agent_to_agency() {
        check_ajax_referer('malisafi_agency_management_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'malisafi-mls'));
        }

        $agency_id = intval($_POST['agency_id']);
        $agent_user_id = intval($_POST['agent_user_id']);
        $role = sanitize_text_field($_POST['role'] ?? 'agent');
        $commission_split = floatval($_POST['commission_split'] ?? 0.00);

        $result = Agency_Manager::add_agent_to_agency($agency_id, $agent_user_id, $role, $commission_split);

        if ($result) {
            wp_send_json_success(__('Agent added to agency successfully!', 'malisafi-mls'));
        } else {
            wp_send_json_error(__('Failed to add agent to agency.', 'malisafi-mls'));
        }
    }

    /**
     * AJAX: Remove agent from agency
     */
    public function ajax_remove_agent_from_agency() {
        check_ajax_referer('malisafi_agency_management_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'malisafi-mls'));
        }

        $agency_id = intval($_POST['agency_id']);
        $agent_user_id = intval($_POST['agent_user_id']);

        $result = Agency_Manager::remove_agent_from_agency($agency_id, $agent_user_id);

        if ($result) {
            wp_send_json_success(__('Agent removed from agency successfully!', 'malisafi-mls'));
        } else {
            wp_send_json_error(__('Failed to remove agent from agency.', 'malisafi-mls'));
        }
    }

    /**
     * AJAX: Get available agents
     */
    public function ajax_get_available_agents() {
        check_ajax_referer('malisafi_agency_management_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'malisafi-mls'));
        }

        $search = sanitize_text_field($_POST['search'] ?? '');
        $agency_id = intval($_POST['agency_id'] ?? 0);

        global $wpdb;

        $query = "
            SELECT u.ID, u.display_name, u.user_email
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = '{$wpdb->prefix}capabilities'
            WHERE um.meta_value LIKE '%\"malisafi_agent\"%'
            AND u.ID NOT IN (
                SELECT agent_id FROM {$wpdb->prefix}mf_agency_agents WHERE agency_id = %d
            )
        ";

        $params = array($agency_id);

        if (!empty($search)) {
            $query .= " AND (u.display_name LIKE %s OR u.user_email LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($search) . '%';
            $params[] = '%' . $wpdb->esc_like($search) . '%';
        }

        $query .= " ORDER BY u.display_name ASC LIMIT 10";

        $agents = $wpdb->get_results($wpdb->prepare($query, $params));

        wp_send_json_success($agents);
    }

    /**
     * AJAX: Get agency agents
     */
    public function ajax_get_agency_agents() {
        check_ajax_referer('malisafi_agency_management_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'malisafi-mls'));
        }

        $agency_id = intval($_POST['agency_id']);

        global $wpdb;

        $agents = $wpdb->get_results($wpdb->prepare(
            "SELECT aa.*, u.display_name, u.user_email, um.meta_value as avatar
             FROM {$wpdb->prefix}mf_agency_agents aa
             JOIN {$wpdb->users} u ON aa.agent_id = u.ID
             LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'avatar'
             WHERE aa.agency_id = %d
             ORDER BY u.display_name ASC",
            $agency_id
        ));

        wp_send_json_success($agents);
    }

    private function create_agency_user($agency_data) {
        // Generate username from agency name
        $username = sanitize_title($agency_data['agency_name']);
        $username = str_replace('-', '_', $username);

        // Ensure unique username
        $original_username = $username;
        $counter = 1;
        while (username_exists($username)) {
            $username = $original_username . '_' . $counter;
            $counter++;
        }

        // Create user
        $user_id = wp_create_user($username, wp_generate_password(), $agency_data['agency_email']);

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        // Set user role
        wp_update_user(array(
            'ID' => $user_id,
            'role' => 'malisafi_agency',
            'display_name' => $agency_data['agency_name'],
            'first_name' => $agency_data['agency_name'],
        ));

        return $user_id;
    }
}

// Initialize
Agency_Management_Admin::get_instance();