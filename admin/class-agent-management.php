<?php
/**
 * Agent Management Admin Page
 *
 * @package MalisafiMLS
 */

class Malisafi_Agent_Management {
    
    /**
     * Initialize
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
        add_action('wp_ajax_malisafi_approve_agent', array(__CLASS__, 'ajax_approve_agent'));
        add_action('wp_ajax_malisafi_reject_agent', array(__CLASS__, 'ajax_reject_agent'));
        add_action('wp_ajax_malisafi_suspend_agent', array(__CLASS__, 'ajax_suspend_agent'));
    }
    
    /**
     * Add admin menu
     */
    public static function add_admin_menu() {
        add_menu_page(
            __('Agent Management', 'malisafi-mls'),
            __('Agent Management', 'malisafi-mls'),
            'manage_options',
            'malisafi-agent-management',
            array(__CLASS__, 'render_page'),
            'dashicons-businessperson',
            30
        );
    }
    
    /**
     * Enqueue scripts
     */
    public static function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_malisafi-agent-management') {
            return;
        }
        
        wp_enqueue_style(
            'malisafi-agent-management',
            MALISAFI_MLS_URL . 'assets/css/admin-agent-management.css',
            array(),
            MALISAFI_MLS_VERSION
        );
        
        wp_enqueue_script(
            'malisafi-agent-management',
            MALISAFI_MLS_URL . 'assets/js/admin-agent-management.js',
            array('jquery'),
            MALISAFI_MLS_VERSION,
            true
        );
        
        wp_localize_script('malisafi-agent-management', 'malisafiAgentManagement', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('malisafi_agent_management'),
        ));
    }
    
    /**
     * Render admin page
     */
    public static function render_page() {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'pending';
        ?>
        <div class="wrap malisafi-agent-management">
            <h1><?php _e('Agent Management', 'malisafi-mls'); ?></h1>
            
            <nav class="nav-tab-wrapper">
                <a href="?page=malisafi-agent-management&tab=pending" 
                   class="nav-tab <?php echo $tab === 'pending' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Pending Approval', 'malisafi-mls'); ?>
                    <?php 
                    $pending_count = self::get_agents_count('pending');
                    if ($pending_count > 0) {
                        echo '<span class="count">(' . $pending_count . ')</span>';
                    }
                    ?>
                </a>
                <a href="?page=malisafi-agent-management&tab=approved" 
                   class="nav-tab <?php echo $tab === 'approved' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Approved Agents', 'malisafi-mls'); ?>
                </a>
                <a href="?page=malisafi-agent-management&tab=rejected" 
                   class="nav-tab <?php echo $tab === 'rejected' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Rejected', 'malisafi-mls'); ?>
                </a>
                <a href="?page=malisafi-agent-management&tab=suspended" 
                   class="nav-tab <?php echo $tab === 'suspended' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Suspended', 'malisafi-mls'); ?>
                </a>
            </nav>
            
            <div class="tab-content">
                <?php
                switch ($tab) {
                    case 'pending':
                        self::render_pending_agents();
                        break;
                    case 'approved':
                        self::render_approved_agents();
                        break;
                    case 'rejected':
                        self::render_rejected_agents();
                        break;
                    case 'suspended':
                        self::render_suspended_agents();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get agents count by status
     */
    private static function get_agents_count($status) {
        $args = array(
            'meta_query' => array(
                array(
                    'key' => 'agent_status',
                    'value' => $status,
                ),
            ),
        );
        
        $user_query = new WP_User_Query($args);
        return $user_query->get_total();
    }
    
    /**
     * Render pending agents
     */
    private static function render_pending_agents() {
        $args = array(
            'meta_query' => array(
                array(
                    'key' => 'agent_status',
                    'value' => 'pending',
                ),
            ),
        );
        
        $agents = get_users($args);
        
        if (empty($agents)) {
            echo '<div class="notice notice-info"><p>' . __('No pending agent applications.', 'malisafi-mls') . '</p></div>';
            return;
        }
        
        self::render_agents_table($agents, 'pending');
    }
    
    /**
     * Render approved agents
     */
    private static function render_approved_agents() {
        $args = array(
            'meta_query' => array(
                array(
                    'key' => 'agent_status',
                    'value' => 'approved',
                ),
            ),
        );
        
        $agents = get_users($args);
        
        if (empty($agents)) {
            echo '<div class="notice notice-info"><p>' . __('No approved agents yet.', 'malisafi-mls') . '</p></div>';
            return;
        }
        
        self::render_agents_table($agents, 'approved');
    }
    
    /**
     * Render rejected agents
     */
    private static function render_rejected_agents() {
        $args = array(
            'meta_query' => array(
                array(
                    'key' => 'agent_status',
                    'value' => 'rejected',
                ),
            ),
        );
        
        $agents = get_users($args);
        
        if (empty($agents)) {
            echo '<div class="notice notice-info"><p>' . __('No rejected agents.', 'malisafi-mls') . '</p></div>';
            return;
        }
        
        self::render_agents_table($agents, 'rejected');
    }
    
    /**
     * Render suspended agents
     */
    private static function render_suspended_agents() {
        $args = array(
            'meta_query' => array(
                array(
                    'key' => 'agent_status',
                    'value' => 'suspended',
                ),
            ),
        );
        
        $agents = get_users($args);
        
        if (empty($agents)) {
            echo '<div class="notice notice-info"><p>' . __('No suspended agents.', 'malisafi-mls') . '</p></div>';
            return;
        }
        
        self::render_agents_table($agents, 'suspended');
    }
    
    /**
     * Render agents table
     */
    private static function render_agents_table($agents, $status) {
        ?>
        <table class="wp-list-table widefat fixed striped agents-table">
            <thead>
                <tr>
                    <th><?php _e('Agent', 'malisafi-mls'); ?></th>
                    <th><?php _e('Contact', 'malisafi-mls'); ?></th>
                    <th><?php _e('Agency & License', 'malisafi-mls'); ?></th>
                    <th><?php _e('Location', 'malisafi-mls'); ?></th>
                    <th><?php _e('Experience', 'malisafi-mls'); ?></th>
                    <th><?php _e('Specializations', 'malisafi-mls'); ?></th>
                    <th><?php _e('Registered', 'malisafi-mls'); ?></th>
                    <th><?php _e('Actions', 'malisafi-mls'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($agents as $agent) : ?>
                    <?php
                    $agent_data = array(
                        'id' => $agent->ID,
                        'name' => get_user_meta($agent->ID, 'first_name', true) . ' ' . get_user_meta($agent->ID, 'last_name', true),
                        'email' => $agent->user_email,
                        'phone' => get_user_meta($agent->ID, 'phone', true),
                        'agency' => get_user_meta($agent->ID, 'agency_name', true),
                        'license' => get_user_meta($agent->ID, 'license_number', true),
                        'county' => get_user_meta($agent->ID, 'agent_county', true),
                        'city' => get_user_meta($agent->ID, 'city', true),
                        'experience' => get_user_meta($agent->ID, 'years_experience', true),
                        'specializations' => get_user_meta($agent->ID, 'specializations', true),
                        'bio' => get_user_meta($agent->ID, 'agent_bio', true),
                        'national_id' => get_user_meta($agent->ID, 'national_id', true),
                        'business_address' => get_user_meta($agent->ID, 'business_address', true),
                        'registered' => get_user_meta($agent->ID, 'agent_registered_date', true),
                        'website' => get_user_meta($agent->ID, 'website', true),
                        'whatsapp' => get_user_meta($agent->ID, 'whatsapp', true),
                    );
                    ?>
                    <tr data-agent-id="<?php echo esc_attr($agent->ID); ?>">
                        <td>
                            <strong><?php echo esc_html($agent_data['name']); ?></strong>
                            <div class="row-actions">
                                <span><a href="#" class="view-details" data-agent-id="<?php echo esc_attr($agent->ID); ?>"><?php _e('View Details', 'malisafi-mls'); ?></a></span>
                                <?php
                                // Lien vers le profil public de l'agent (post_type malisafi_agent)
                                $agent_post_id = get_user_meta($agent->ID, 'agent_post_id', true);
                                if ($agent_post_id) {
                                    $profile_url = get_permalink($agent_post_id);
                                    echo ' | <span><a href="' . esc_url($profile_url) . '" target="_blank">' . __('View Profile', 'malisafi-mls') . '</a></span>';
                                    echo ' | <span><a href="' . esc_url($profile_url) . '#rate-agent-modal" class="rate-direct-link" target="_blank">' . __('Rate', 'malisafi-mls') . '</a></span>';
                                }
                                ?>
                            </div>
                        </td>
                        <td>
                            <div><?php echo esc_html($agent_data['email']); ?></div>
                            <div><?php echo esc_html($agent_data['phone']); ?></div>
                        </td>
                        <td>
                            <div><strong><?php echo esc_html($agent_data['agency']); ?></strong></div>
                            <div><small><?php _e('Lic:', 'malisafi-mls'); ?> <?php echo esc_html($agent_data['license']); ?></small></div>
                        </td>
                        <td>
                            <div><?php echo esc_html($agent_data['county']); ?></div>
                            <div><small><?php echo esc_html($agent_data['city']); ?></small></div>
                        </td>
                        <td><?php echo esc_html($agent_data['experience']); ?></td>
                        <td>
                            <?php
                            if (is_array($agent_data['specializations'])) {
                                echo '<div class="specializations">';
                                foreach ($agent_data['specializations'] as $spec) {
                                    echo '<span class="badge">' . esc_html(ucfirst($spec)) . '</span> ';
                                }
                                echo '</div>';
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($agent_data['registered']))); ?></td>
                        <td>
                            <?php if ($status === 'pending') : ?>
                                <button class="button button-primary approve-agent" data-agent-id="<?php echo esc_attr($agent->ID); ?>">
                                    <?php _e('Approve', 'malisafi-mls'); ?>
                                </button>
                                <button class="button button-secondary reject-agent" data-agent-id="<?php echo esc_attr($agent->ID); ?>">
                                    <?php _e('Reject', 'malisafi-mls'); ?>
                                </button>
                            <?php elseif ($status === 'approved') : ?>
                                <button class="button button-secondary suspend-agent" data-agent-id="<?php echo esc_attr($agent->ID); ?>">
                                    <?php _e('Suspend', 'malisafi-mls'); ?>
                                </button>
                            <?php elseif ($status === 'suspended') : ?>
                                <button class="button button-primary approve-agent" data-agent-id="<?php echo esc_attr($agent->ID); ?>">
                                    <?php _e('Reactivate', 'malisafi-mls'); ?>
                                </button>
                            <?php elseif ($status === 'rejected') : ?>
                                <button class="button button-primary approve-agent" data-agent-id="<?php echo esc_attr($agent->ID); ?>">
                                    <?php _e('Approve', 'malisafi-mls'); ?>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Agent Details Modal -->
        <div id="agent-details-modal" class="agent-modal" style="display: none;">
            <div class="agent-modal-overlay"></div>
            <div class="agent-modal-content">
                <span class="agent-modal-close">&times;</span>
                <div id="agent-details-content"></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX: Approve agent
     */
    public static function ajax_approve_agent() {
        check_ajax_referer('malisafi_agent_management', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'malisafi-mls')));
        }
        
        $agent_id = intval($_POST['agent_id']);
        
        // Update user meta
        update_user_meta($agent_id, 'agent_status', 'approved');
        update_user_meta($agent_id, 'agent_approved_date', current_time('mysql'));
        
        // Update agent post status
        $agent_post_id = get_user_meta($agent_id, 'agent_post_id', true);
        if ($agent_post_id) {
            wp_update_post(array(
                'ID' => $agent_post_id,
                'post_status' => 'publish',
            ));
        }
        
        // Send approval email
        $user = get_userdata($agent_id);
        $subject = sprintf(__('[%s] Your Agent Application Has Been Approved!', 'malisafi-mls'), get_bloginfo('name'));
        $message = sprintf(
            __("Congratulations!\n\nYour agent application has been approved. You can now start listing properties and managing your agent profile.\n\nLogin to your dashboard: %s", 'malisafi-mls'),
            home_url('/agent-dashboard')
        );
        
        wp_mail($user->user_email, $subject, $message);
        
        wp_send_json_success(array('message' => __('Agent approved successfully.', 'malisafi-mls')));
    }
    
    /**
     * AJAX: Reject agent
     */
    public static function ajax_reject_agent() {
        check_ajax_referer('malisafi_agent_management', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'malisafi-mls')));
        }
        
        $agent_id = intval($_POST['agent_id']);
        
        // Update user meta
        update_user_meta($agent_id, 'agent_status', 'rejected');
        update_user_meta($agent_id, 'agent_rejected_date', current_time('mysql'));
        
        // Send rejection email
        $user = get_userdata($agent_id);
        $subject = sprintf(__('[%s] Agent Application Update', 'malisafi-mls'), get_bloginfo('name'));
        $message = __("Thank you for your interest in becoming an agent on our platform.\n\nUnfortunately, we are unable to approve your application at this time. If you have questions, please contact our support team.", 'malisafi-mls');
        
        wp_mail($user->user_email, $subject, $message);
        
        wp_send_json_success(array('message' => __('Agent rejected.', 'malisafi-mls')));
    }
    
    /**
     * AJAX: Suspend agent
     */
    public static function ajax_suspend_agent() {
        check_ajax_referer('malisafi_agent_management', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'malisafi-mls')));
        }
        
        $agent_id = intval($_POST['agent_id']);
        
        // Update user meta
        update_user_meta($agent_id, 'agent_status', 'suspended');
        update_user_meta($agent_id, 'agent_suspended_date', current_time('mysql'));
        
        // Update agent post status
        $agent_post_id = get_user_meta($agent_id, 'agent_post_id', true);
        if ($agent_post_id) {
            wp_update_post(array(
                'ID' => $agent_post_id,
                'post_status' => 'draft',
            ));
        }
        
        // Send suspension email
        $user = get_userdata($agent_id);
        $subject = sprintf(__('[%s] Account Suspended', 'malisafi-mls'), get_bloginfo('name'));
        $message = __("Your agent account has been suspended. Please contact support for more information.", 'malisafi-mls');
        
        wp_mail($user->user_email, $subject, $message);
        
        wp_send_json_success(array('message' => __('Agent suspended.', 'malisafi-mls')));
    }
}
