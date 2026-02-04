<?php
/**
 * Fraud Report AJAX Handlers
 *
 * @package MalisafiMLS
 * @since 1.0.1
 */

namespace MalisafiMLS;

use MalisafiMLS\Analytics\Analytics_Advanced;

if (!defined('ABSPATH')) {
    exit;
}

class Fraud_Report_Ajax {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_ajax_malisafi_submit_fraud_report', array($this, 'submit_report'));
        add_action('wp_ajax_nopriv_malisafi_submit_fraud_report', array($this, 'submit_report'));

        add_action('wp_ajax_malisafi_search_agents', array($this, 'search_agents'));
        add_action('wp_ajax_nopriv_malisafi_search_agents', array($this, 'search_agents'));

        add_action('wp_ajax_malisafi_search_properties', array($this, 'search_properties'));
        add_action('wp_ajax_nopriv_malisafi_search_properties', array($this, 'search_properties'));

        add_action('wp_ajax_malisafi_create_manual_suspicion', array($this, 'create_manual_suspicion'));
        add_action('wp_ajax_malisafi_update_report_status', array($this, 'update_report_status'));
        add_action('wp_ajax_malisafi_get_fraud_reports', array($this, 'get_fraud_reports'));
        add_action('wp_ajax_malisafi_get_report_details', array($this, 'get_report_details'));
        add_action('wp_ajax_malisafi_get_agent_name', array($this, 'get_agent_name'));
        add_action('wp_ajax_malisafi_get_property_title', array($this, 'get_property_title'));
        add_action('wp_ajax_malisafi_link_report_to_suspicion', array($this, 'link_report_to_suspicion'));
    }

    private function verify_frontend_nonce() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'malisafi_fraud_report_nonce')) {
            wp_send_json_error(array('message' => __('Invalid security token.', 'malisafi-mls')));
        }
    }

    private function verify_admin_request() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'malisafi_admin_nonce')) {
            wp_send_json_error(array('message' => __('Invalid security token.', 'malisafi-mls')));
        }

        if (!current_user_can('moderate_malisafi_properties')) {
            wp_send_json_error(array('message' => __('Unauthorized request.', 'malisafi-mls')));
        }
    }

    private function get_allowed_report_types() {
        return array(
            'fake_listing',
            'duplicate_property',
            'misleading_info',
            'fake_agent',
            'price_scam',
            'fake_photos',
            'contact_fraud',
            'identity_theft',
            'spam',
            'other'
        );
    }

    private function get_allowed_detection_types() {
        return array(
            'duplicate_listing',
            'rapid_edits',
            'suspicious_ip',
            'fake_images',
            'price_manipulation',
            'spam_content',
            'multiple_accounts',
            'stolen_content'
        );
    }

    public function search_agents() {
        $term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';

        if (strlen($term) < 2) {
            wp_send_json(array());
        }

        $query = new \WP_User_Query(array(
            'number' => 10,
            'search' => '*' . $term . '*',
            'search_columns' => array('display_name', 'user_login', 'user_email'),
            'role__in' => array('malisafi_agent_basic', 'malisafi_agent_premium')
        ));

        $results = array();
        foreach ($query->get_results() as $user) {
            $results[] = array(
                'id' => $user->ID,
                'label' => $user->display_name . ' (' . $user->user_email . ')',
                'value' => $user->display_name
            );
        }

        wp_send_json($results);
    }

    public function search_properties() {
        $term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';

        if (strlen($term) < 2) {
            wp_send_json(array());
        }

        $query = new \WP_Query(array(
            'post_type' => 'malisafi_property',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            's' => $term
        ));

        $results = array();
        while ($query->have_posts()) {
            $query->the_post();
            $results[] = array(
                'id' => get_the_ID(),
                'label' => get_the_title(),
                'value' => get_the_title()
            );
        }
        wp_reset_postdata();

        wp_send_json($results);
    }

    public function submit_report() {
        $this->verify_frontend_nonce();

        $report_type = isset($_POST['report_type']) ? sanitize_text_field($_POST['report_type']) : '';
        $agent_id = isset($_POST['agent_id']) ? intval($_POST['agent_id']) : 0;
        $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
        $reason = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : '';
        $details = isset($_POST['details']) ? sanitize_textarea_field($_POST['details']) : '';
        $reporter_email = isset($_POST['reporter_email']) ? sanitize_email($_POST['reporter_email']) : '';

        if (is_user_logged_in() && empty($reporter_email)) {
            $reporter_email = wp_get_current_user()->user_email;
        }

        if (empty($report_type) || !in_array($report_type, $this->get_allowed_report_types(), true)) {
            wp_send_json_error(array('message' => __('Please select a valid fraud type.', 'malisafi-mls')));
        }

        if (!$agent_id && !$property_id) {
            wp_send_json_error(array('message' => __('Please select at least one: Agent or Property.', 'malisafi-mls')));
        }

        if (empty($reason) || empty($details)) {
            wp_send_json_error(array('message' => __('Please fill in all required fields.', 'malisafi-mls')));
        }

        if (empty($reporter_email) || !is_email($reporter_email)) {
            wp_send_json_error(array('message' => __('Please provide a valid email address.', 'malisafi-mls')));
        }

        if ($agent_id && !get_userdata($agent_id)) {
            wp_send_json_error(array('message' => __('Selected agent could not be found.', 'malisafi-mls')));
        }

        if ($property_id && get_post_type($property_id) !== 'malisafi_property') {
            wp_send_json_error(array('message' => __('Selected property could not be found.', 'malisafi-mls')));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'mf_fraud_reports';
        $ip_address = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';

        if ($ip_address) {
            $count = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE ip_address = %s AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)",
                $ip_address
            ));

            if ($count >= 3) {
                wp_send_json_error(array('message' => __('You have reached the daily limit for fraud reports. Please try again tomorrow.', 'malisafi-mls')));
            }
        }

        $report_id = Analytics_Advanced::create_fraud_report(array(
            'reporter_email' => $reporter_email,
            'report_type' => $report_type,
            'agent_id' => $agent_id ?: null,
            'property_id' => $property_id ?: null,
            'reason' => $reason,
            'details' => $details,
            'ip_address' => $ip_address
        ));

        if (!$report_id) {
            wp_send_json_error(array('message' => __('Failed to submit report. Please try again.', 'malisafi-mls')));
        }

        $admin_email = get_option('admin_email');
        $subject = __('New Fraud Report Submitted', 'malisafi-mls');
        $message = "A new fraud report has been submitted.\n\n";
        $message .= "Report ID: {$report_id}\n";
        $message .= "Report Type: {$report_type}\n";
        $message .= "Reason: {$reason}\n";
        $message .= "Details: {$details}\n";
        $message .= "Reporter Email: {$reporter_email}\n";

        if ($agent_id) {
            $agent = get_userdata($agent_id);
            $message .= "Agent: " . ($agent ? $agent->display_name : $agent_id) . "\n";
        }

        if ($property_id) {
            $message .= "Property: " . get_the_title($property_id) . "\n";
            $message .= "Property URL: " . get_permalink($property_id) . "\n";
        }

        $message .= "IP Address: {$ip_address}\n";

        wp_mail($admin_email, $subject, $message);

        wp_send_json_success(array('message' => __('Report submitted successfully!', 'malisafi-mls')));
    }

    public function create_manual_suspicion() {
        $this->verify_admin_request();

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
        $fraud_type = isset($_POST['fraud_type']) ? sanitize_text_field($_POST['fraud_type']) : '';
        $confidence = isset($_POST['confidence_score']) ? intval($_POST['confidence_score']) : 0;
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

        if ($fraud_type === 'identity_fraud') {
            $fraud_type = 'stolen_content';
        }

        if (empty($fraud_type) || !in_array($fraud_type, $this->get_allowed_detection_types(), true)) {
            wp_send_json_error(array('message' => __('Please select a valid fraud type.', 'malisafi-mls')));
        }

        if (!$user_id && !$property_id) {
            wp_send_json_error(array('message' => __('Please link the suspicion to an agent or property.', 'malisafi-mls')));
        }

        if ($confidence < 1 || $confidence > 100) {
            wp_send_json_error(array('message' => __('Please provide a valid confidence score.', 'malisafi-mls')));
        }

        if (empty($notes)) {
            wp_send_json_error(array('message' => __('Please provide investigation notes.', 'malisafi-mls')));
        }

        global $wpdb;

        $inserted = Analytics_Advanced::log_fraud_detection(
            $user_id ?: null,
            $property_id ?: null,
            $fraud_type,
            $confidence,
            array(
                'notes' => $notes,
                'manual' => true,
                'created_by' => get_current_user_id()
            )
        );

        if (!$inserted) {
            wp_send_json_error(array('message' => __('Failed to create fraud suspicion.', 'malisafi-mls')));
        }

        $detection_id = (int) $wpdb->insert_id;

        wp_send_json_success(array('detection_id' => $detection_id));
    }

    public function update_report_status() {
        $this->verify_admin_request();

        $report_id = isset($_POST['report_id']) ? intval($_POST['report_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $admin_notes = isset($_POST['admin_notes']) ? sanitize_textarea_field($_POST['admin_notes']) : '';

        $allowed_statuses = array('new', 'under_review', 'resolved', 'dismissed');
        if (!$report_id || !in_array($status, $allowed_statuses, true)) {
            wp_send_json_error(array('message' => __('Invalid report status.', 'malisafi-mls')));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'mf_fraud_reports';

        $data = array(
            'status' => $status,
            'admin_notes' => $admin_notes
        );
        $format = array('%s', '%s');

        if ($status !== 'new') {
            $data['reviewed_by'] = get_current_user_id();
            $data['reviewed_at'] = current_time('mysql');
            $format[] = '%d';
            $format[] = '%s';
        }

        $updated = $wpdb->update(
            $table,
            $data,
            array('id' => $report_id),
            $format,
            array('%d')
        );

        if ($updated === false) {
            wp_send_json_error(array('message' => __('Failed to update report status.', 'malisafi-mls')));
        }

        wp_send_json_success(array('message' => __('Report status updated.', 'malisafi-mls')));
    }

    public function get_fraud_reports() {
        $this->verify_admin_request();

        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $type = isset($_POST['report_type']) ? sanitize_text_field($_POST['report_type']) : '';
        $limit = isset($_POST['limit']) ? min(100, max(1, intval($_POST['limit']))) : 50;

        global $wpdb;
        $table = $wpdb->prefix . 'mf_fraud_reports';
        $where = array('1=1');

        if ($status) {
            $where[] = $wpdb->prepare('status = %s', $status);
        }
        if ($type) {
            $where[] = $wpdb->prepare('report_type = %s', $type);
        }

        $where_clause = implode(' AND ', $where);

        $reports = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d",
            $limit
        ), ARRAY_A);

        wp_send_json_success(array('reports' => $reports));
    }

    public function get_report_details() {
        $this->verify_admin_request();

        $report_id = isset($_POST['report_id']) ? intval($_POST['report_id']) : 0;
        if (!$report_id) {
            wp_send_json_error(array('message' => __('Invalid report ID.', 'malisafi-mls')));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'mf_fraud_reports';

        $report = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $report_id
        ), ARRAY_A);

        if (!$report) {
            wp_send_json_error(array('message' => __('Report not found.', 'malisafi-mls')));
        }

        $reporter = $report['reporter_email'];
        if (!empty($report['reporter_user_id'])) {
            $user = get_userdata((int) $report['reporter_user_id']);
            if ($user) {
                $reporter = $user->display_name . ' (' . $user->user_email . ')';
            }
        }

        $agent_name = '';
        if (!empty($report['agent_id'])) {
            $agent = get_userdata((int) $report['agent_id']);
            $agent_name = $agent ? $agent->display_name : '';
        }

        $property_title = '';
        if (!empty($report['property_id'])) {
            $property_title = get_the_title((int) $report['property_id']);
        }

        $reviewed_by = '';
        if (!empty($report['reviewed_by'])) {
            $reviewer = get_userdata((int) $report['reviewed_by']);
            $reviewed_by = $reviewer ? $reviewer->display_name : '';
        }

        wp_send_json_success(array(
            'id' => (int) $report['id'],
            'report_type' => $report['report_type'],
            'reporter' => $reporter,
            'agent' => $agent_name,
            'property' => $property_title,
            'reason' => $report['reason'],
            'details' => $report['details'],
            'status' => $report['status'],
            'created_at' => $report['created_at'],
            'reviewed_at' => $report['reviewed_at'],
            'reviewed_by' => $reviewed_by,
            'admin_notes' => $report['admin_notes']
        ));
    }

    public function get_agent_name() {
        $this->verify_admin_request();

        $agent_id = isset($_POST['agent_id']) ? intval($_POST['agent_id']) : 0;
        if (!$agent_id) {
            wp_send_json_error(array('message' => __('Invalid agent ID.', 'malisafi-mls')));
        }

        $agent = get_userdata($agent_id);
        if (!$agent) {
            wp_send_json_error(array('message' => __('Agent not found.', 'malisafi-mls')));
        }

        wp_send_json_success(array('name' => $agent->display_name));
    }

    public function get_property_title() {
        $this->verify_admin_request();

        $property_id = isset($_POST['property_id']) ? intval($_POST['property_id']) : 0;
        if (!$property_id) {
            wp_send_json_error(array('message' => __('Invalid property ID.', 'malisafi-mls')));
        }

        $title = get_the_title($property_id);
        if (empty($title)) {
            wp_send_json_error(array('message' => __('Property not found.', 'malisafi-mls')));
        }

        wp_send_json_success(array('title' => $title));
    }

    public function link_report_to_suspicion() {
        $this->verify_admin_request();

        $report_id = isset($_POST['report_id']) ? intval($_POST['report_id']) : 0;
        $detection_id = isset($_POST['detection_id']) ? intval($_POST['detection_id']) : 0;

        if (!$report_id || !$detection_id) {
            wp_send_json_error(array('message' => __('Invalid report link request.', 'malisafi-mls')));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'mf_fraud_reports';

        $updated = $wpdb->update(
            $table,
            array(
                'created_suspicion_id' => $detection_id,
                'status' => 'resolved',
                'reviewed_by' => get_current_user_id(),
                'reviewed_at' => current_time('mysql')
            ),
            array('id' => $report_id),
            array('%d', '%s', '%d', '%s'),
            array('%d')
        );

        if ($updated === false) {
            wp_send_json_error(array('message' => __('Failed to link report to suspicion.', 'malisafi-mls')));
        }

        wp_send_json_success(array('message' => __('Report linked to suspicion.', 'malisafi-mls')));
    }
}

Fraud_Report_Ajax::get_instance();