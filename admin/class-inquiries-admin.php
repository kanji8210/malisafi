<?php
/**
 * Admin UI for managing inquiries
 *
 * @package MalisafiMLS
 */

if (!defined('ABSPATH')) {
    exit;
}

class Malisafi_Inquiries_Admin {

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_menu'));
        add_action('admin_post_malisafi_inquiry_action', array(__CLASS__, 'handle_action'));
        add_action('wp_ajax_malisafi_inquiry_action', array(__CLASS__, 'ajax_handle_action'));
    }

    public static function add_menu() {
        add_submenu_page(
            'malisafi-dashboard',
            __('Inquiries', 'malisafi-mls'),
            __('Inquiries', 'malisafi-mls'),
            'manage_malisafi_inquiries',
            'malisafi-inquiries',
            array(__CLASS__, 'render_page')
        );
    }

    public static function render_page() {
        // Use WP_List_Table for better UX
        require_once MALISAFI_MLS_PATH . 'admin/class-inquiries-list-table.php';
        $list_table = new Malisafi_Inquiries_List_Table();

        // Process bulk actions
        if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['action']) && check_admin_referer('bulk-inquiries')) {
            $action = $_POST['action'] ?? '';
            $ids = isset($_POST['inquiry']) ? array_map('intval', (array) $_POST['inquiry']) : [];
            global $wpdb;
            $table = $wpdb->prefix . 'mf_inquiries';
            if ($action === 'mark_read' && $ids) {
                foreach ($ids as $id) {
                    $wpdb->update($table, array('status' => 'read', 'updated_at' => current_time('mysql')), array('id' => $id));
                }
            } elseif ($action === 'delete' && $ids) {
                foreach ($ids as $id) {
                    $wpdb->delete($table, array('id' => $id));
                }
            }
        }

        $list_table->prepare_items();
        ?>
        <div class="wrap">
            <h1><?php _e('Property Inquiries', 'malisafi-mls'); ?></h1>

            <form method="get">
                <input type="hidden" name="page" value="malisafi-inquiries" />
                <?php $list_table->search_box(__('Search'), 'inquiry'); ?>

                <label for="status" style="margin-left:10px;"><?php _e('Status:', 'malisafi-mls'); ?></label>
                <select id="status" name="status">
                    <option value=""><?php _e('All', 'malisafi-mls'); ?></option>
                    <option value="new" <?php selected($_REQUEST['status'] ?? '', 'new'); ?>><?php _e('New', 'malisafi-mls'); ?></option>
                    <option value="read" <?php selected($_REQUEST['status'] ?? '', 'read'); ?>><?php _e('Read', 'malisafi-mls'); ?></option>
                    <option value="replied" <?php selected($_REQUEST['status'] ?? '', 'replied'); ?>><?php _e('Replied', 'malisafi-mls'); ?></option>
                    <option value="closed" <?php selected($_REQUEST['status'] ?? '', 'closed'); ?>><?php _e('Closed', 'malisafi-mls'); ?></option>
                </select>
                <?php submit_button(__('Filter'), 'secondary', '', false); ?>
            </form>

            <form method="post">
                <?php wp_nonce_field('bulk-inquiries'); ?>
                <?php $list_table->display(); ?>
            </form>
            <!-- Inline modal for viewing inquiry -->
            <div id="malisafi-inquiry-modal" style="display:none;">
                <div class="malisafi-modal-content" style="background:#fff;padding:20px;max-width:800px;margin:30px auto;border:1px solid #ddd;">
                    <button id="malisafi-inquiry-close" style="float:right;">&times;</button>
                    <h2 id="malisafi-inquiry-title"><?php _e('Inquiry Details', 'malisafi-mls'); ?></h2>
                    <div id="malisafi-inquiry-body"></div>
                </div>
            </div>

            <style>
                #malisafi-inquiry-modal{position:fixed;left:0;top:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:9999}
            </style>

            <script>
            (function(){
                var table = document.getElementById('malisafi-inquiries-table');
                if (!table) return;

                function ajaxAction(inquiryId, actionName, nonce, onSuccess, onError){
                    var data = new FormData();
                    data.append('action', 'malisafi_inquiry_action');
                    data.append('inquiry_id', inquiryId);
                    data.append('inquiry_action', actionName);
                    data.append('_ajax_nonce', nonce);

                    fetch(ajaxurl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: data
                    }).then(function(resp){
                        return resp.json();
                    }).then(function(json){
                        if (json && json.success){
                            onSuccess && onSuccess(json.data);
                        } else {
                            onError && onError(json && json.data ? json.data : 'unknown');
                        }
                    }).catch(function(err){
                        onError && onError(err);
                    });
                }

                table.addEventListener('click', function(e){
                    var t = e.target;
                    if (t.closest('.malisafi-view-inquiry')){
                        e.preventDefault();
                        var id = t.getAttribute('data-id');
                        var full = document.getElementById('malisafi-inquiry-full-' + id);
                        if (full){
                            document.getElementById('malisafi-inquiry-modal-body').innerHTML = full.innerHTML;
                            document.getElementById('malisafi-inquiry-modal').style.display = 'block';
                        }
                    }

                    if (t.closest('.malisafi-action-mark-read')){
                        e.preventDefault();
                        var id = t.getAttribute('data-id');
                        var nonce = t.getAttribute('data-nonce');
                        ajaxAction(id, 'mark_read', nonce, function(){
                            var row = t.closest('tr');
                            if (row){
                                var statusCell = row.querySelector('td.column-status');
                                if (statusCell) statusCell.textContent = 'read';
                            }
                        }, function(err){
                            alert('Error: ' + err);
                        });
                    }

                    if (t.closest('.malisafi-action-delete')){
                        e.preventDefault();
                        if (!confirm('Are you sure?')) return;
                        var id = t.getAttribute('data-id');
                        var nonce = t.getAttribute('data-nonce');
                        ajaxAction(id, 'delete', nonce, function(){
                            var row = t.closest('tr');
                            if (row) row.parentNode.removeChild(row);
                        }, function(err){
                            alert('Error: ' + err);
                        });
                    }

                    if (t.closest('.malisafi-inquiry-modal-close')){
                        e.preventDefault();
                        document.getElementById('malisafi-inquiry-modal').style.display = 'none';
                    }
                });
            })();
            </script>
        </div>

    public static function ajax_handle_action(){
        if (! current_user_can('manage_options')){
            wp_send_json_error('permission');
        }

        $nonce = isset($_POST['_ajax_nonce']) ? sanitize_text_field(wp_unslash($_POST['_ajax_nonce'])) : '';
        if (! wp_verify_nonce($nonce, 'malisafi_inquiry_admin')){
            wp_send_json_error('bad_nonce');
        }

        $inquiry_id = isset($_POST['inquiry_id']) ? intval($_POST['inquiry_id']) : 0;
        $inquiry_action = isset($_POST['inquiry_action']) ? sanitize_text_field(wp_unslash($_POST['inquiry_action'])) : '';

        global $wpdb;
        $table = $wpdb->prefix . 'mf_inquiries';

        if (! $inquiry_id || ! $inquiry_action){
            wp_send_json_error('invalid');
        }

        if ($inquiry_action === 'mark_read'){
            $updated = $wpdb->update($table, array('status' => 'read'), array('id' => $inquiry_id), array('%s'), array('%d'));
            if ($updated !== false){
                wp_send_json_success(array('updated' => $updated));
            }
            wp_send_json_error('db_failed');
        }

        if ($inquiry_action === 'delete'){
            $deleted = $wpdb->delete($table, array('id' => $inquiry_id), array('%d'));
            if ($deleted !== false){
                wp_send_json_success(array('deleted' => $deleted));
            }
            wp_send_json_error('db_failed');
        }

        wp_send_json_error('unknown_action');
    }
        <?php
    }

    public static function handle_action() {
        if (!current_user_can('manage_malisafi_inquiries')) {
            wp_die(__('Unauthorized', 'malisafi-mls'));
        }

        $action = sanitize_text_field($_REQUEST['action'] ?? '');
        $id = intval($_REQUEST['id'] ?? 0);
        if (!wp_verify_nonce($_REQUEST['_wpnonce'] ?? '', 'malisafi_inquiry_admin')) {
            wp_die(__('Invalid nonce', 'malisafi-mls'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'mf_inquiries';
        if ($action === 'mark_read' && $id) {
            $wpdb->update($table, array('status' => 'read', 'updated_at' => current_time('mysql')), array('id' => $id));
        } elseif ($action === 'delete' && $id) {
            $wpdb->delete($table, array('id' => $id));
        }

        wp_redirect(admin_url('admin.php?page=malisafi-inquiries'));
        exit;
    }
}

// Initialize
Malisafi_Inquiries_Admin::init();
