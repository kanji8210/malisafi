<?php
/**
 * Fraud Report AJAX Handlers
 *
 * Handles AJAX requests for fraud reporting system
 *
 * @package MalisafiMLS
 * @subpackage Analytics
 * @since 1.0.1
 */

use MalisafiMLS\Analytics\Analytics_Advanced;

if (!defined('ABSPATH')) {
    exit;
}

class Malisafi_Fraud_Report_Ajax {
    
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Submit fraud report (public + logged in)
        add_action('wp_ajax_malisafi_submit_fraud_report', array($this, 'submit_fraud_report'));
        add_action('wp_ajax_nopriv_malisafi_submit_fraud_report', array($this, 'submit_fraud_report'));
        
        // Autocomplete agents (public + logged in)
        add_action('wp_ajax_malisafi_search_agents', array($this, 'search_agents'));
        add_action('wp_ajax_nopriv_malisafi_search_agents', array($this, 'search_agents'));
        
        // Autocomplete properties (public + logged in)
        add_action('wp_ajax_malisafi_search_properties', array($this, 'search_properties'));
        add_action('wp_ajax_nopriv_malisafi_search_properties', array($this, 'search_properties'));
        
        // Create manual suspicion (admin/moderator only)
        add_action('wp_ajax_malisafi_create_manual_suspicion', array($this, 'create_manual_suspicion'));
        
        // Update report status (admin/moderator only)
        add_action('wp_ajax_malisafi_update_report_status', array($this, 'update_report_status'));
        
        // Get fraud reports (admin/moderator only)
        add_action('wp_ajax_malisafi_get_fraud_reports', array($this, 'get_fraud_reports'));
        
        // Get report details (admin/moderator only)
        add_action('wp_ajax_malisafi_get_report_details', array($this, 'get_report_details'));
        
        // Get agent name (admin/moderator only)
        add_action('wp_ajax_malisafi_get_agent_name', array($this, 'get_agent_name'));
        
        // Get property title (admin/moderator only)
        add_action('wp_ajax_malisafi_get_property_title', array($this, 'get_property_title'));
        
        // Link report to suspicion (admin/moderator only)
        add_action('wp_ajax_malisafi_link_report_to_suspicion', array($this, 'link_report_to_suspicion'));
    }

    /**
     * Submit fraud report
     */
    public function submit_fraud_report() {
        check_ajax_referer('malisafi_fraud_report_nonce', 'nonce');

        $report_type = sanitize_text_field($_POST['report_type']);
        $agent_id = isset($_POST['agent_id']) ? intval($_POST['agent_id']) : null;
        $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : null;
        $reason = sanitize_text_field($_POST['reason']);
        $details = sanitize_textarea_field($_POST['details']);
        $reporter_email = sanitize_email($_POST['reporter_email']);

        // Validation
        if (empty($report_type) || empty($reason) || empty($details)) {
            wp_send_json_error(array('message' => __('All fields are required.', 'malisafi-mls')));
        }

        if (!$agent_id && !$property_id) {
            wp_send_json_error(array('message' => __('Please select an agent or property.', 'malisafi-mls')));
        }

        // Email validation for non-logged-in users
        if (!is_user_logged_in() && !is_email($reporter_email)) {
            wp_send_json_error(array('message' => __('Please provide a valid email address.', 'malisafi-mls')));
        }

        // Rate limiting check (max 3 reports per IP per day)
        $ip_address = $_SERVER['REMOTE_ADDR'];
        global $wpdb;
        $recent_reports = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mf_fraud_reports 
             WHERE ip_address = %s AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)",
            $ip_address
        ));

        if ($recent_reports >= 3) {
            wp_send_json_error(array('message' => __('You have reached the maximum number of reports for today.', 'malisafi-mls')));
        }

        // Create report
        $data = array(
            'report_type' => $report_type,
            'agent_id' => $agent_id,
            'property_id' => $property_id,
            'reason' => $reason,
            'details' => $details,
            'reporter_email' => $reporter_email
        );

        $report_id = Analytics_Advanced::create_fraud_report($data);

        if ($report_id) {
            // Send email notification to admin
            $admin_email = get_option('admin_email');
            $subject = __('New Fraud Report Submitted', 'malisafi-mls');
            $message = sprintf(
                __('A new fraud report has been submitted.\n\nType: %s\nReason: %s\n\nView report: %s', 'malisafi-mls'),
                $report_type,
                $reason,
                admin_url('admin.php?page=malisafi-analytics-fraud-reports')
            );
            wp_mail($admin_email, $subject, $message);

            wp_send_json_success(array(
                'message' => __('Report submitted successfully. Thank you for helping us maintain a safe platform.', 'malisafi-mls'),
                'report_id' => $report_id
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to submit report. Please try again.', 'malisafi-mls')));
        }
    }

    /**
     * Search agents (autocomplete)
     */
    public function search_agents() {
        $term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';

        if (strlen($term) < 2) {
            wp_send_json([]);
        }

        $args = array(
            'post_type' => 'malisafi_agent',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            's' => $term
        );

        $agents = get_posts($args);
        $results = array();

        foreach ($agents as $agent) {
            $results[] = array(
                'id' => $agent->ID,
                'label' => $agent->post_title,
                'value' => $agent->post_title
            );
        }

        wp_send_json($results);
    }

    /**
     * Search properties (autocomplete)
     */
    public function search_properties() {
        $term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';

        if (strlen($term) < 2) {
            wp_send_json([]);
        }

        $args = array(
            'post_type' => 'malisafi_property',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            's' => $term
        );

        $properties = get_posts($args);
        $results = array();

        foreach ($properties as $property) {
            $address = get_post_meta($property->ID, '_malisafi_address', true);
            $price = get_post_meta($property->ID, '_malisafi_price', true);
            
            $label = $property->post_title;
            if ($address) {
                $label .= ' - ' . $address;
            }
            if ($price) {
                $label .= ' (KES ' . number_format($price) . ')';
            }

            $results[] = array(
                'id' => $property->ID,
                'label' => $label,
                'value' => $property->post_title
            );
        }

        wp_send_json($results);
    }

    /**
     * Create manual suspicion (admin/moderator only)
     */
    public function create_manual_suspicion() {
        check_ajax_referer('malisafi_admin_nonce', 'nonce');

        if (!current_user_can('moderate_malisafi_properties')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'malisafi-mls')));
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;
        $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : null;
        $fraud_type = sanitize_text_field($_POST['fraud_type']);
        $confidence_score = intval($_POST['confidence_score']);
        $notes = sanitize_textarea_field($_POST['notes']);

        if (empty($fraud_type) || $confidence_score < 1 || $confidence_score > 100) {
            wp_send_json_error(array('message' => __('Invalid data provided.', 'malisafi-mls')));
        }

        $detection_id = Analytics_Advanced::log_fraud_detection(
            $user_id,
            $property_id,
            $fraud_type,
            $confidence_score,
            array(
                'manual' => true,
                'created_by' => get_current_user_id(),
                'notes' => $notes
            )
        );

        if ($detection_id) {
            wp_send_json_success(array(
                'message' => __('Suspicion created successfully.', 'malisafi-mls'),
                'detection_id' => $detection_id
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to create suspicion.', 'malisafi-mls')));
        }
    }

    /**
     * Update report status (admin/moderator only)
     */
    public function update_report_status() {
        check_ajax_referer('malisafi_admin_nonce', 'nonce');

        if (!current_user_can('moderate_malisafi_properties')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'malisafi-mls')));
        }

        $report_id = intval($_POST['report_id']);
        $new_status = sanitize_text_field($_POST['status']);
        $admin_notes = sanitize_textarea_field($_POST['admin_notes']);

        global $wpdb;
        $updated = $wpdb->update(
            $wpdb->prefix . 'mf_fraud_reports',
            array(
                'status' => $new_status,
                'reviewed_by' => get_current_user_id(),
                'reviewed_at' => current_time('mysql'),
                'admin_notes' => $admin_notes
            ),
            array('id' => $report_id),
            array('%s', '%d', '%s', '%s'),
            array('%d')
        );

        if ($updated) {
            wp_send_json_success(array('message' => __('Report status updated.', 'malisafi-mls')));
        } else {
            wp_send_json_error(array('message' => __('Failed to update report.', 'malisafi-mls')));
        }
    }

    /**
     * Get fraud reports (admin/moderator only)
     */
    public function get_fraud_reports() {
        check_ajax_referer('malisafi_admin_nonce', 'nonce');

        if (!current_user_can('moderate_malisafi_properties')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'malisafi-mls')));
        }

        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $report_type = isset($_GET['report_type']) ? sanitize_text_field($_GET['report_type']) : '';
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $per_page = 20;

        global $wpdb;
        
        $where = array('1=1');
        if ($status) {
            $where[] = $wpdb->prepare('status = %s', $status);
        }
        if ($report_type) {
            $where[] = $wpdb->prepare('report_type = %s', $report_type);
        }

        $where_clause = implode(' AND ', $where);
        $offset = ($page - 1) * $per_page;

        $reports = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}mf_fraud_reports 
             WHERE {$where_clause}
             ORDER BY created_at DESC
             LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mf_fraud_reports WHERE {$where_clause}");

        wp_send_json_success(array(
            'reports' => $reports,
            'total' => $total,
            'pages' => ceil($total / $per_page)
        ));
    }
    
    /**
     * Get report details (admin/moderator only)
     */
    public function get_report_details() {
        check_ajax_referer('malisafi_admin_nonce', 'nonce');

        if (!current_user_can('moderate_malisafi_properties')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'malisafi-mls')));
        }

        $report_id = intval($_POST['report_id']);

        global $wpdb;
        $report = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}mf_fraud_reports WHERE id = %d",
            $report_id
        ));

        if (!$report) {
            wp_send_json_error(array('message' => __('Report not found', 'malisafi-mls')));
        }

        // Format data for display
        $data = array(
            'id' => $report->id,
            'report_type' => $report->report_type,
            'reason' => $report->reason,
            'details' => $report->details,
            'status' => $report->status,
            'created_at' => date_i18n('F j, Y g:i A', strtotime($report->created_at)),
            'admin_notes' => $report->admin_notes
        );

        // Reporter info
        if ($report->reporter_user_id) {
            $user = get_userdata($report->reporter_user_id);
            $data['reporter'] = $user->display_name . ' (' . $user->user_email . ')';
        } else {
            $data['reporter'] = $report->reporter_email;
        }

        // Agent info
        if ($report->agent_id) {
            $agent = get_post($report->agent_id);
            if ($agent) {
                $data['agent'] = '<a href="' . get_edit_post_link($report->agent_id) . '">' . $agent->post_title . '</a>';
            }
        }

        // Property info
        if ($report->property_id) {
            $property = get_post($report->property_id);
            if ($property) {
                $data['property'] = '<a href="' . get_edit_post_link($report->property_id) . '">' . $property->post_title . '</a>';
            }
        }

        // Review info
        if ($report->reviewed_at) {
            $reviewer = get_userdata($report->reviewed_by);
            $data['reviewed_at'] = date_i18n('F j, Y g:i A', strtotime($report->reviewed_at));
            $data['reviewed_by'] = $reviewer ? $reviewer->display_name : 'Unknown';
        }

        wp_send_json_success($data);
    }
    
    /**
     * Get agent name (admin/moderator only)
     */
    public function get_agent_name() {
        check_ajax_referer('malisafi_admin_nonce', 'nonce');

        if (!current_user_can('moderate_malisafi_properties')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'malisafi-mls')));
        }

        $agent_id = intval($_POST['agent_id']);
        $agent = get_post($agent_id);

        if ($agent) {
            wp_send_json_success(array('name' => $agent->post_title));
        } else {
            wp_send_json_error(array('message' => __('Agent not found', 'malisafi-mls')));
        }
    }
    
    /**
     * Get property title (admin/moderator only)
     */
    public function get_property_title() {
        check_ajax_referer('malisafi_admin_nonce', 'nonce');

        if (!current_user_can('moderate_malisafi_properties')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'malisafi-mls')));
        }

        $property_id = intval($_POST['property_id']);
        $property = get_post($property_id);

        if ($property) {
            wp_send_json_success(array('title' => $property->post_title));
        } else {
            wp_send_json_error(array('message' => __('Property not found', 'malisafi-mls')));
        }
    }
    
    /**
     * Link report to suspicion (admin/moderator only)
     */
    public function link_report_to_suspicion() {
        check_ajax_referer('malisafi_admin_nonce', 'nonce');

        if (!current_user_can('moderate_malisafi_properties')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'malisafi-mls')));
        }

        $report_id = intval($_POST['report_id']);
        $detection_id = intval($_POST['detection_id']);

        global $wpdb;
        $updated = $wpdb->update(
            $wpdb->prefix . 'mf_fraud_reports',
            array('created_suspicion_id' => $detection_id),
            array('id' => $report_id),
            array('%d'),
            array('%d')
        );

        if ($updated) {
            wp_send_json_success();
        } else {
            wp_send_json_error(array('message' => __('Failed to link report', 'malisafi-mls')));
        }
    }
}

// Initialize
Malisafi_Fraud_Report_Ajax::get_instance();
