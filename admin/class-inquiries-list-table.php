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
            'status'    => __('Status', 'malisafi-mls'),
            'created_at'=> __('Date', 'malisafi-mls')
        ];
        return $columns;
    }

    protected function column_cb($item) {
        return sprintf('<input type="checkbox" name="inquiry[]" value="%d" />', $item->id);
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
        switch ($column_name) {
            case 'id':
                $id = intval($item->id);
                $actions = array();
                $actions['view'] = sprintf('<a href="#" class="malisafi-view-inquiry" data-id="%d">%s</a>', $id, __('View', 'malisafi-mls'));
                $actions['mark_read'] = sprintf('<a href="#" class="malisafi-action-mark-read" data-id="%d" data-nonce="%s">%s</a>', $id, wp_create_nonce('malisafi_inquiry_admin'), __('Mark Read', 'malisafi-mls'));
                $actions['delete'] = sprintf('<a href="#" class="malisafi-action-delete" data-id="%d" data-nonce="%s">%s</a>', $id, wp_create_nonce('malisafi_inquiry_admin'), __('Delete', 'malisafi-mls'));

                return $id . '<br/>' . $this->row_actions($actions);
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
                // Hidden full message for modal
                $hidden = sprintf('<div id="malisafi-inquiry-full-%d" class="malisafi-inquiry-full" style="display:none;">%s</div>', intval($item->id), $full);
                return $trim . $hidden;
            case 'status':
                return esc_html($item->status);
            case 'created_at':
                return esc_html($item->created_at);
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
