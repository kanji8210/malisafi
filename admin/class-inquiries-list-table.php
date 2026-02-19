<?php
/**
 * Inquiries List Table
 *
 * @package MalisafiMLS
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Malisafi_Inquiries_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => 'inquiry',
            'plural'   => 'inquiries',
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        $columns = [
            'cb'        => '<input type="checkbox" />',
            'id'        => __('ID', 'malisafi-mls'),
            'property'  => __('Property', 'malisafi-mls'),
            'client'    => __('Client', 'malisafi-mls'),
            'contact'   => __('Contact', 'malisafi-mls'),
            'message'   => __('Message', 'malisafi-mls'),
            'agent'     => __('Agent', 'malisafi-mls'),
            'email_status' => __('Email', 'malisafi-mls'),
            'status'    => __('Status', 'malisafi-mls'),
            'created_at'=> __('Date', 'malisafi-mls')
        ];
        return $columns;
    }

    protected function column_cb($item) {
        $id = isset($item->inquiry_id) ? $item->inquiry_id : (isset($item->id) ? $item->id : 0);
        return sprintf('<input type="checkbox" name="inquiry[]" value="%d" />', $id);
    }

    public function get_sortable_columns() {
        return [
            'id' => ['id', true],
            'created_at' => ['created_at', false]
        ];
    }

    public function prepare_items() {
        global $wpdb;
        $table = $wpdb->prefix . 'mf_inquiries';

        $per_page = 25;
        $current_page = $this->get_pagenum();

        $where = "";
        $vars = [];

        // Search
        $s = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        if ($s !== '') {
            $where .= " WHERE (client_email LIKE %s OR client_phone LIKE %s OR message LIKE %s)";
            $like = '%' . $wpdb->esc_like($s) . '%';
            $vars[] = $like; $vars[] = $like; $vars[] = $like;
        }

        // Status filter
        $status = isset($_REQUEST['status']) ? sanitize_text_field($_REQUEST['status']) : '';
        if ($status !== '') {
            $where .= $where === '' ? ' WHERE ' : ' AND ';
            $where .= ' status = %s';
            $vars[] = $status;
        }

        // Email status filter
        $email_filter = isset($_REQUEST['email_filter']) ? sanitize_text_field($_REQUEST['email_filter']) : '';
        if ($email_filter === 'failed') {
            $where .= $where === '' ? ' WHERE ' : ' AND ';
            $where .= ' email_sent = 0';
        } elseif ($email_filter === 'sent') {
            $where .= $where === '' ? ' WHERE ' : ' AND ';
            $where .= ' email_sent = 1';
        }

        // Count
        if ($where === '') {
            $total = intval($wpdb->get_var("SELECT COUNT(*) FROM $table"));
            $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, ($current_page -1) * $per_page));
        } else {
            $sql_count = "SELECT COUNT(*) FROM $table" . $where;
            $total = intval($wpdb->get_var($wpdb->prepare($sql_count, $vars)));
            $sql = "SELECT * FROM $table" . $where . " ORDER BY created_at DESC LIMIT %d OFFSET %d";
            $vars[] = $per_page; $vars[] = ($current_page -1) * $per_page;
            $items = $wpdb->get_results($wpdb->prepare($sql, $vars));
        }

        $this->items = $items;

        $this->set_pagination_args([
            'total_items' => $total,
            'per_page'    => $per_page
        ]);
    }

    public function column_default($item, $column_name) {
        // Get the correct ID field
        $item_id = isset($item->inquiry_id) ? $item->inquiry_id : (isset($item->id) ? $item->id : 0);
        
        switch ($column_name) {
            case 'id':
                $actions = array();
                $actions['view'] = sprintf('<a href="#" class="malisafi-view-inquiry" data-id="%d">%s</a>', $item_id, __('View', 'malisafi-mls'));
                $actions['mark_read'] = sprintf('<a href="#" class="malisafi-action-mark-read" data-id="%d" data-nonce="%s">%s</a>', $item_id, wp_create_nonce('malisafi_inquiry_admin'), __('Mark Read', 'malisafi-mls'));
                $actions['delete'] = sprintf('<a href="#" class="malisafi-action-delete" data-id="%d" data-nonce="%s">%s</a>', $item_id, wp_create_nonce('malisafi_inquiry_admin'), __('Delete', 'malisafi-mls'));

                return $item_id . '<br/>' . $this->row_actions($actions);
            case 'property':
                $post = get_post($item->property_id);
                if ($post) {
                    return sprintf('<a href="%s" target="_blank">%s</a>', get_edit_post_link($post->ID), esc_html($post->post_title));
                }
                return intval($item->property_id);
            case 'client':
                return esc_html($item->client_name ? $item->client_name : $item->client_email);
            case 'contact':
                return esc_html($item->client_email) . '<br/>' . esc_html($item->client_phone);
            case 'message':
                $trim = esc_html(wp_trim_words($item->message, 15));
                $full = esc_html($item->message);
                $item_id = isset($item->inquiry_id) ? $item->inquiry_id : (isset($item->id) ? $item->id : 0);
                // Hidden full message for modal
                $hidden = sprintf('<div id="malisafi-inquiry-full-%d" class="malisafi-inquiry-full" style="display:none;">%s</div>', $item_id, $full);
                return $trim . $hidden;
            case 'agent':
                if ($item->agent_id) {
                    $agent = get_userdata($item->agent_id);
                    if ($agent) {
                        return esc_html($agent->display_name);
                    }
                }
                return '—';
            case 'email_status':
                $email_sent = isset($item->email_sent) ? (bool)$item->email_sent : true;
                if ($email_sent) {
                    $title = __('Email notification sent successfully', 'malisafi-mls');
                    if (!empty($item->email_recipient)) {
                        $title .= ' to ' . esc_attr($item->email_recipient);
                    }
                    return sprintf('<span class="dashicons dashicons-yes-alt" style="color:#46b450;" title="%s"></span>', $title);
                } else {
                    return '<span class="dashicons dashicons-dismiss" style="color:#dc3232;" title="' . __('Email failed to send', 'malisafi-mls') . '"></span>';
                }
            case 'status':
                $status_labels = [
                    'new' => ['label' => __('New', 'malisafi-mls'), 'color' => '#2271b1'],
                    'read' => ['label' => __('Read', 'malisafi-mls'), 'color' => '#72aee6'],
                    'replied' => ['label' => __('Replied', 'malisafi-mls'), 'color' => '#00a32a'],
                    'closed' => ['label' => __('Closed', 'malisafi-mls'), 'color' => '#646970'],
                    'email_failed' => ['label' => __('Email Failed', 'malisafi-mls'), 'color' => '#d63638']
                ];
                $status_info = isset($status_labels[$item->status]) ? $status_labels[$item->status] : ['label' => esc_html($item->status), 'color' => '#646970'];
                return sprintf(
                    '<span style="display:inline-block;padding:3px 8px;background:%s;color:#fff;border-radius:3px;font-size:11px;font-weight:600;">%s</span>',
                    $status_info['color'],
                    $status_info['label']
                );
            case 'created_at':
                $date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->created_at));
                $time_ago = human_time_diff(strtotime($item->created_at), current_time('timestamp'));
                return sprintf('%s<br><small style="color:#646970;">%s ago</small>', $date, $time_ago);
            default:
                return '';
        }
    }

    public function get_bulk_actions() {
        return [
            'mark_read' => __('Mark Read', 'malisafi-mls'),
            'delete'    => __('Delete', 'malisafi-mls')
        ];
    }
}
