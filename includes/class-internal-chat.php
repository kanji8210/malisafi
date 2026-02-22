<?php
/**
 * Internal chat system for admin/moderator/agents.
 *
 * @package MalisafiMLS
 */

namespace MalisafiMLS;

if (!defined('ABSPATH')) {
    exit;
}

class Internal_Chat {

    private static $instance = null;

    private $chat_roles = array(
        'administrator',
        'moderator',
        'malisafi_moderator',
        'malisafi_agent',
        'malisafi_agent_basic',
        'malisafi_agent_premium',
    );

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'ensure_chat_schema'));

        add_action('wp_ajax_malisafi_chat_bootstrap', array($this, 'ajax_bootstrap'));
        add_action('wp_ajax_malisafi_chat_open_thread', array($this, 'ajax_open_thread'));
        add_action('wp_ajax_malisafi_chat_fetch_messages', array($this, 'ajax_fetch_messages'));
        add_action('wp_ajax_malisafi_chat_send_message', array($this, 'ajax_send_message'));
        add_action('wp_ajax_malisafi_chat_mark_read', array($this, 'ajax_mark_read'));
        add_action('wp_ajax_malisafi_chat_fetch_notifications', array($this, 'ajax_fetch_notifications'));

        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_footer', array($this, 'render_floating_widget'));
        add_action('admin_footer', array($this, 'render_admin_floating_widget'));

        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_post_malisafi_chat_assign', array($this, 'handle_admin_assign'));
        add_action('admin_post_malisafi_chat_admin_reply', array($this, 'handle_admin_reply'));
    }

    public function ensure_chat_schema() {
        if (get_option('malisafi_mls_chat_schema_ready') !== '1') {
            Database::update_schema();
            update_option('malisafi_mls_chat_schema_ready', '1');
        }

        Database::repair_schema();
    }

    public function enqueue_assets() {
        if (is_admin()) {
            return;
        }

        // Always enqueue floating chat assets for frontend
        wp_enqueue_style('malisafi-chat-modal', MALISAFI_MLS_URL . 'assets/css/chat-modal.css', array('malisafi-mls-variables'), MALISAFI_MLS_VERSION);
        wp_enqueue_style('malisafi-floating-chat', MALISAFI_MLS_URL . 'assets/css/floating-chat.css', array('malisafi-mls-variables'), MALISAFI_MLS_VERSION);
        wp_enqueue_script('malisafi-floating-chat', MALISAFI_MLS_URL . 'assets/js/floating-chat.js', array('jquery'), MALISAFI_MLS_VERSION, true);

        // Find admin and moderator user IDs for chat
        $admin_id = 0;
        $moderator_id = 0;
        $admins = get_users(array('role' => 'administrator', 'number' => 1, 'orderby' => 'ID', 'order' => 'ASC', 'fields' => array('ID')));
        if (!empty($admins) && !empty($admins[0]->ID)) {
            $admin_id = intval($admins[0]->ID);
        }
        $moderators = get_users(array('role__in' => array('malisafi_moderator', 'moderator'), 'number' => 1, 'orderby' => 'ID', 'order' => 'ASC', 'fields' => array('ID')));
        if (!empty($moderators) && !empty($moderators[0]->ID)) {
            $moderator_id = intval($moderators[0]->ID);
        }

        wp_localize_script('malisafi-floating-chat', 'malisafiChat', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('malisafi_chat_nonce'),
            'adminId' => $admin_id,
            'moderatorId' => $moderator_id,
            'i18n' => array(
                'typeMessage' => __('Type a message...', 'malisafi-mls'),
                'send' => __('Send', 'malisafi-mls'),
                'unableOpen' => __('Unable to open chat right now.', 'malisafi-mls'),
                'loadFailed' => __('Unable to load chat data right now.', 'malisafi-mls'),
                'sendFailed' => __('Unable to send message right now.', 'malisafi-mls'),
            ),
        ));

        // Existing chat assets for shortcode/floating widget
        $is_shortcode_chat_page = $this->is_shortcode_chat_page();
        $is_floating_widget = !$is_shortcode_chat_page && $this->should_render_floating_widget();
        if ($is_shortcode_chat_page || $is_floating_widget) {
            $this->enqueue_chat_assets($is_floating_widget);
        }
    }

    public function enqueue_admin_assets() {
        if (!$this->should_render_floating_widget()) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && $screen->id === 'toplevel_page_malisafi-dashboard_page_malisafi-chat-management') {
            return;
        }

        $this->enqueue_chat_assets(true);
    }

    private function enqueue_chat_assets($is_floating_widget) {
        $initial_target_user_id = isset($_GET['chat_with']) ? absint($_GET['chat_with']) : 0;
        $style_dependencies = array();

        if (function_exists('wp_style_is') && wp_style_is('malisafi-mls-variables', 'registered')) {
            $style_dependencies[] = 'malisafi-mls-variables';
        }

        wp_enqueue_style(
            'malisafi-internal-chat',
            MALISAFI_MLS_URL . 'assets/css/internal-chat.css',
            $style_dependencies,
            MALISAFI_MLS_VERSION
        );

        wp_enqueue_script(
            'malisafi-internal-chat',
            MALISAFI_MLS_URL . 'assets/js/internal-chat.js',
            array('jquery'),
            MALISAFI_MLS_VERSION,
            true
        );

        wp_localize_script('malisafi-internal-chat', 'malisafiChat', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('malisafi_chat_nonce'),
            'pollInterval' => 10000,
            'initialTargetUserId' => $initial_target_user_id,
            'isFloatingWidget' => (bool) $is_floating_widget,
            'i18n' => array(
                'loading' => __('Loading chats...', 'malisafi-mls'),
                'noThreads' => __('No conversations yet.', 'malisafi-mls'),
                'noContacts' => __('No contacts available.', 'malisafi-mls'),
                'chooseContact' => __('Choose contact...', 'malisafi-mls'),
                'selectContact' => __('Please select a contact to start chat.', 'malisafi-mls'),
                'typeMessage' => __('Type a message...', 'malisafi-mls'),
                'send' => __('Send', 'malisafi-mls'),
                'selectConversation' => __('Select a conversation to start chatting.', 'malisafi-mls'),
                'unableOpen' => __('Unable to open chat right now.', 'malisafi-mls'),
                'refreshRetry' => __('Please refresh and try again.', 'malisafi-mls'),
                'sendFailed' => __('Unable to send message right now.', 'malisafi-mls'),
                'loadFailed' => __('Unable to load chat data right now.', 'malisafi-mls'),
            ),
        ));
    }

    public static function render_shortcode() {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Please log in to access internal chat.', 'malisafi-mls') . '</p>';
        }

        $instance = self::get_instance();
        if (!$instance->is_chat_user(get_current_user_id())) {
            return '<p>' . esc_html__('Your account does not have chat access.', 'malisafi-mls') . '</p>';
        }

        ob_start();
        include MALISAFI_MLS_PATH . 'templates/internal-chat.php';
        return ob_get_clean();
    }

    public function render_floating_widget() {
        if (is_admin() || !$this->should_render_floating_widget() || $this->is_shortcode_chat_page()) {
            return;
        }

        $is_floating_widget = true;
        include MALISAFI_MLS_PATH . 'templates/internal-chat.php';
    }

    public function render_admin_floating_widget() {
        if (!is_admin() || !$this->should_render_floating_widget()) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && $screen->id === 'malisafi-dashboard_page_malisafi-chat-management') {
            return;
        }

        $is_floating_widget = true;
        include MALISAFI_MLS_PATH . 'templates/internal-chat.php';
    }

    public function register_admin_menu() {
        if (!current_user_can('manage_malisafi_settings') && !current_user_can('manage_options')) {
            return;
        }

        $capability = current_user_can('manage_malisafi_settings') ? 'manage_malisafi_settings' : 'manage_options';

        add_submenu_page(
            'malisafi-dashboard',
            __('Chat Management', 'malisafi-mls'),
            __('Chats', 'malisafi-mls'),
            $capability,
            'malisafi-chat-management',
            array($this, 'render_admin_chat_management')
        );
    }

    public function render_admin_chat_management() {
        if (!current_user_can('manage_malisafi_settings') && !current_user_can('manage_options')) {
            wp_die(__('You do not have permission to manage chats.', 'malisafi-mls'));
        }

        $thread_id = isset($_GET['thread_id']) ? absint($_GET['thread_id']) : 0;
        $manager_id = get_current_user_id();
        $threads = $this->get_admin_threads();

        $selected_thread = null;
        if ($thread_id > 0) {
            foreach ($threads as $thread) {
                if ((int) $thread['id'] === $thread_id) {
                    $selected_thread = $thread;
                    break;
                }
            }
        }

        if (!$selected_thread && !empty($threads)) {
            $selected_thread = $threads[0];
            $thread_id = (int) $selected_thread['id'];
        }

        if ($thread_id > 0) {
            $this->ensure_participant($thread_id, $manager_id);
        }

        $messages = $thread_id > 0 ? $this->get_thread_messages($thread_id, $manager_id, 0) : array();
        $assignees = $this->get_assignable_users();

        include MALISAFI_MLS_PATH . 'admin/templates/chat-management.php';
    }

    public function handle_admin_assign() {
        if (!current_user_can('manage_malisafi_settings') && !current_user_can('manage_options')) {
            wp_die(__('Permission denied.', 'malisafi-mls'));
        }

        check_admin_referer('malisafi_chat_assign', 'malisafi_chat_assign_nonce');

        $thread_id = isset($_POST['thread_id']) ? absint($_POST['thread_id']) : 0;
        $assigned_to = isset($_POST['assigned_to']) ? absint($_POST['assigned_to']) : 0;

        if ($thread_id > 0) {
            $this->assign_thread($thread_id, $assigned_to);
        }

        wp_safe_redirect(admin_url('admin.php?page=malisafi-chat-management&thread_id=' . $thread_id . '&chat_updated=1'));
        exit;
    }

    public function handle_admin_reply() {
        if (!current_user_can('manage_malisafi_settings') && !current_user_can('manage_options')) {
            wp_die(__('Permission denied.', 'malisafi-mls'));
        }

        check_admin_referer('malisafi_chat_admin_reply', 'malisafi_chat_admin_reply_nonce');

        $thread_id = isset($_POST['thread_id']) ? absint($_POST['thread_id']) : 0;
        $message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';
        $sender_id = get_current_user_id();

        if ($thread_id > 0 && !empty($message)) {
            $this->ensure_participant($thread_id, $sender_id);
            $this->persist_message($thread_id, $sender_id, $message);
        }

        wp_safe_redirect(admin_url('admin.php?page=malisafi-chat-management&thread_id=' . $thread_id . '&chat_updated=1'));
        exit;
    }

    public function ajax_bootstrap() {
        $user_id = $this->verify_chat_request();
        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
        }

        $contacts = $this->get_allowed_contacts($user_id);
        $threads = $this->get_user_threads($user_id);

        wp_send_json_success(array(
            'currentUser' => array(
                'id' => $user_id,
                'name' => wp_get_current_user()->display_name,
                'role' => $this->get_user_primary_role($user_id),
            ),
            'contacts' => $contacts,
            'threads' => $threads,
            'unreadTotal' => $this->get_unread_total($user_id),
        ));
    }

    public function ajax_open_thread() {
        $user_id = $this->verify_chat_request();
        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
        }

        $target_user_id = isset($_POST['target_user_id']) ? absint($_POST['target_user_id']) : 0;
        if ($target_user_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid chat recipient.', 'malisafi-mls')));
        }

        if (!$this->can_users_chat($user_id, $target_user_id)) {
            wp_send_json_error(array('message' => __('You are not allowed to chat with this user.', 'malisafi-mls')));
        }

        $thread_id = $this->find_or_create_direct_thread($user_id, $target_user_id);
        if (!$thread_id) {
            wp_send_json_error(array('message' => __('Could not open chat thread.', 'malisafi-mls')));
        }

        $messages = $this->get_thread_messages($thread_id, $user_id, 0);
        $this->mark_thread_read($thread_id, $user_id);

        wp_send_json_success(array(
            'threadId' => $thread_id,
            'messages' => $messages,
            'threads' => $this->get_user_threads($user_id),
            'unreadTotal' => $this->get_unread_total($user_id),
        ));
    }

    public function ajax_fetch_messages() {
        $user_id = $this->verify_chat_request();
        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
        }

        $thread_id = isset($_POST['thread_id']) ? absint($_POST['thread_id']) : 0;
        $last_message_id = isset($_POST['last_message_id']) ? absint($_POST['last_message_id']) : 0;

        if ($thread_id <= 0 || !$this->is_thread_participant($thread_id, $user_id)) {
            wp_send_json_error(array('message' => __('Invalid conversation.', 'malisafi-mls')));
        }

        $messages = $this->get_thread_messages($thread_id, $user_id, $last_message_id);

        wp_send_json_success(array(
            'messages' => $messages,
        ));
    }

    public function ajax_send_message() {
        $user_id = $this->verify_chat_request();
        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
        }

        $thread_id = isset($_POST['thread_id']) ? absint($_POST['thread_id']) : 0;
        $message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';

        // If thread_id is missing, try to create a direct thread if target_user_id is provided
        if ($thread_id <= 0) {
            $target_user_id = isset($_POST['target_user_id']) ? absint($_POST['target_user_id']) : 0;
            if ($target_user_id > 0 && $this->can_users_chat($user_id, $target_user_id)) {
                $thread_id = $this->find_or_create_direct_thread($user_id, $target_user_id);
            }
        }

        if ($thread_id <= 0 || !$this->is_thread_participant($thread_id, $user_id)) {
            wp_send_json_error(array('message' => __('Invalid conversation.', 'malisafi-mls')));
        }

        if (empty($message)) {
            wp_send_json_error(array('message' => __('Message cannot be empty.', 'malisafi-mls')));
        }

        if (mb_strlen($message) > 2000) {
            wp_send_json_error(array('message' => __('Message is too long.', 'malisafi-mls')));
        }

        $message_id = $this->persist_message($thread_id, $user_id, $message);
        if ($message_id <= 0) {
            wp_send_json_error(array('message' => __('Failed to send message.', 'malisafi-mls')));
        }

        $new_message = $this->get_thread_messages($thread_id, $user_id, $message_id - 1);

        wp_send_json_success(array(
            'message' => !empty($new_message) ? $new_message[0] : null,
            'threads' => $this->get_user_threads($user_id),
            'unreadTotal' => $this->get_unread_total($user_id),
        ));
    }

    public function ajax_mark_read() {
        $user_id = $this->verify_chat_request();
        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
        }

        $thread_id = isset($_POST['thread_id']) ? absint($_POST['thread_id']) : 0;
        if ($thread_id <= 0 || !$this->is_thread_participant($thread_id, $user_id)) {
            wp_send_json_error(array('message' => __('Invalid conversation.', 'malisafi-mls')));
        }

        $this->mark_thread_read($thread_id, $user_id);

        wp_send_json_success(array(
            'unreadTotal' => $this->get_unread_total($user_id),
            'threads' => $this->get_user_threads($user_id),
        ));
    }

    public function ajax_fetch_notifications() {
        $user_id = $this->verify_chat_request();
        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
        }

        wp_send_json_success(array(
            'unreadTotal' => $this->get_unread_total($user_id),
            'threads' => $this->get_user_threads($user_id),
        ));
    }

    private function is_shortcode_chat_page() {
        $post = get_post();
        if (!is_a($post, 'WP_Post')) {
            return false;
        }

        return has_shortcode($post->post_content, 'malisafi_internal_chat');
    }

    private function should_render_floating_widget() {
        if (!is_user_logged_in()) {
            return false;
        }

        $user_id = get_current_user_id();
        if (!$this->is_chat_user($user_id)) {
            return false;
        }

        return true;
    }

    private function get_assignable_users() {
        $users = get_users(array(
            'role__in' => array('administrator', 'malisafi_moderator', 'malisafi_agent_basic', 'malisafi_agent_premium'),
            'orderby' => 'display_name',
            'order' => 'ASC',
            'fields' => array('ID', 'display_name', 'user_email'),
        ));

        $items = array();
        // Add 'Select role' as the first element
        $items[] = array(
            'id' => 0,
            'name' => __('Select role', 'malisafi-mls'),
            'role' => '',
            'email' => '',
        );

        foreach ($users as $user) {
            $items[] = array(
                'id' => (int) $user->ID,
                'name' => $user->display_name,
                'role' => $this->get_user_primary_role((int) $user->ID),
                'email' => $user->user_email,
            );
        }

        return $items;
    }

    private function get_admin_threads() {
        global $wpdb;

        $threads_table = $wpdb->prefix . 'mf_chat_threads';
        $messages_table = $wpdb->prefix . 'mf_chat_messages';
        $participants_table = $wpdb->prefix . 'mf_chat_participants';
        $users_table = $wpdb->users;

        $rows = $wpdb->get_results(
            "SELECT t.id,
                    t.thread_type,
                    t.assigned_to,
                    t.status,
                    t.last_message_at,
                    m.message AS last_message,
                    m.created_at AS last_message_created,
                    au.display_name AS assigned_to_name
             FROM {$threads_table} t
             LEFT JOIN {$messages_table} m ON m.id = t.last_message_id
             LEFT JOIN {$users_table} au ON au.ID = t.assigned_to
             ORDER BY COALESCE(t.last_message_at, t.created_at) DESC",
            ARRAY_A
        );

        if (empty($rows)) {
            return array();
        }

        $threads = array();
        foreach ($rows as $row) {
            $thread_id = (int) $row['id'];
            $participants = $wpdb->get_results($wpdb->prepare(
                "SELECT u.ID, u.display_name, p.role_slug
                 FROM {$participants_table} p
                 INNER JOIN {$users_table} u ON u.ID = p.user_id
                 WHERE p.thread_id = %d
                 ORDER BY u.display_name ASC",
                $thread_id
            ), ARRAY_A);

            $participant_names = array();
            foreach ($participants as $participant) {
                $participant_names[] = $participant['display_name'];
            }

            $threads[] = array(
                'id' => $thread_id,
                'type' => $row['thread_type'],
                'status' => $row['status'],
                'title' => !empty($participant_names) ? implode(', ', $participant_names) : __('Conversation', 'malisafi-mls'),
                'participants' => $participants,
                'assignedTo' => !empty($row['assigned_to']) ? (int) $row['assigned_to'] : 0,
                'assignedToName' => !empty($row['assigned_to_name']) ? $row['assigned_to_name'] : '',
                'lastMessage' => !empty($row['last_message']) ? wp_trim_words($row['last_message'], 14, '…') : '',
                'lastMessageAt' => !empty($row['last_message_created']) ? mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $row['last_message_created']) : '',
            );
        }

        return $threads;
    }

    private function assign_thread($thread_id, $assigned_to) {
        global $wpdb;

        $threads_table = $wpdb->prefix . 'mf_chat_threads';
        $assigned_to = absint($assigned_to);

        if ($assigned_to > 0 && !$this->is_chat_user($assigned_to)) {
            return;
        }

        if ($assigned_to > 0) {
            $wpdb->update(
                $threads_table,
                array('assigned_to' => $assigned_to),
                array('id' => $thread_id),
                array('%d'),
                array('%d')
            );
        } else {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$threads_table} SET assigned_to = NULL WHERE id = %d",
                $thread_id
            ));
        }

        if ($assigned_to > 0) {
            $this->ensure_participant($thread_id, $assigned_to);
        }
    }

    private function ensure_participant($thread_id, $user_id) {
        if ($this->is_thread_participant($thread_id, $user_id)) {
            return;
        }

        global $wpdb;
        $participants_table = $wpdb->prefix . 'mf_chat_participants';

        $wpdb->insert(
            $participants_table,
            array(
                'thread_id' => $thread_id,
                'user_id' => $user_id,
                'role_slug' => $this->get_user_primary_role($user_id),
            ),
            array('%d', '%d', '%s')
        );
    }

    private function persist_message($thread_id, $sender_id, $message) {
        global $wpdb;

        $messages_table = $wpdb->prefix . 'mf_chat_messages';
        $threads_table = $wpdb->prefix . 'mf_chat_threads';

        $inserted = $wpdb->insert(
            $messages_table,
            array(
                'thread_id' => $thread_id,
                'sender_id' => $sender_id,
                'message' => $message,
            ),
            array('%d', '%d', '%s')
        );

        if (!$inserted) {
            return 0;
        }

        $message_id = (int) $wpdb->insert_id;

        $wpdb->update(
            $threads_table,
            array(
                'last_message_id' => $message_id,
                'last_message_at' => current_time('mysql'),
            ),
            array('id' => $thread_id),
            array('%d', '%s'),
            array('%d')
        );

        $this->create_notifications_for_thread($thread_id, $message_id, $sender_id);

        return $message_id;
    }

    private function verify_chat_request() {
        if (!is_user_logged_in()) {
            return new \WP_Error('not_logged_in', __('You must be logged in.', 'malisafi-mls'));
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'malisafi_chat_nonce')) {
            return new \WP_Error('invalid_nonce', __('Invalid security token.', 'malisafi-mls'));
        }

        $user_id = get_current_user_id();
        if (!$this->is_chat_user($user_id)) {
            return new \WP_Error('forbidden', __('You are not allowed to use chat.', 'malisafi-mls'));
        }

        return $user_id;
    }

    private function is_chat_user($user_id) {
        $user = get_userdata($user_id);
        if (!$user || empty($user->roles)) {
            return false;
        }

        foreach ($user->roles as $role) {
            if (in_array($role, $this->chat_roles, true)) {
                return true;
            }
        }

        return false;
    }

    private function get_user_primary_role($user_id) {
        $user = get_userdata($user_id);
        if (!$user || empty($user->roles)) {
            return '';
        }

        return (string) $user->roles[0];
    }

    private function is_agent_role($role_slug) {
        return in_array($role_slug, array('malisafi_agent', 'malisafi_agent_basic', 'malisafi_agent_premium'), true);
    }

    private function is_admin_or_moderator_role($role_slug) {
        return in_array($role_slug, array('administrator', 'moderator', 'malisafi_moderator'), true);
    }

    private function can_users_chat($user_id, $target_user_id) {
        if ($user_id === $target_user_id) {
            return false;
        }

        $target_user = get_userdata($target_user_id);
        if (!$target_user) {
            return false;
        }

        $user_role = $this->get_user_primary_role($user_id);
        $target_role = $this->get_user_primary_role($target_user_id);

        if ($this->is_admin_or_moderator_role($user_role)) {
            return true;
        }

        if (!$this->is_chat_user($user_id) || !$this->is_chat_user($target_user_id)) {
            return false;
        }

        if ($this->is_agent_role($user_role) && $this->is_agent_role($target_role)) {
            return false;
        }

        return true;
    }

    private function get_allowed_contacts($user_id) {
        $user_role = $this->get_user_primary_role($user_id);

        if ($this->is_agent_role($user_role)) {
            $allowed_roles = array('administrator', 'malisafi_moderator');
        } elseif ($this->is_admin_or_moderator_role($user_role)) {
            $allowed_roles = array('administrator', 'moderator', 'malisafi_moderator', 'malisafi_agent', 'malisafi_agent_basic', 'malisafi_agent_premium', 'malisafi_owner', 'malisafi_developer', 'malisafi_agency');
        } else {
            $allowed_roles = array('administrator', 'moderator', 'malisafi_moderator', 'malisafi_agent', 'malisafi_agent_basic', 'malisafi_agent_premium');
        }

        $users = get_users(array(
            'role__in' => $allowed_roles,
            'orderby' => 'display_name',
            'order' => 'ASC',
            'fields' => array('ID', 'display_name', 'user_email'),
        ));

        $contacts = array();
        foreach ($users as $contact) {
            $contact_id = (int) $contact->ID;
            if ($contact_id === $user_id) {
                continue;
            }

            if (!$this->can_users_chat($user_id, $contact_id)) {
                continue;
            }

            $contacts[] = array(
                'id' => $contact_id,
                'name' => $contact->display_name,
                'email' => $contact->user_email,
                'role' => $this->get_user_primary_role($contact_id),
            );
        }

        return $contacts;
    }

    private function find_or_create_direct_thread($user_id, $target_user_id) {
        global $wpdb;

        $threads_table = $wpdb->prefix . 'mf_chat_threads';
        $participants_table = $wpdb->prefix . 'mf_chat_participants';

        $thread_id = $wpdb->get_var($wpdb->prepare(
            "SELECT t.id
             FROM {$threads_table} t
             INNER JOIN {$participants_table} p1 ON p1.thread_id = t.id AND p1.user_id = %d
             INNER JOIN {$participants_table} p2 ON p2.thread_id = t.id AND p2.user_id = %d
             WHERE t.thread_type = 'direct'
             LIMIT 1",
            $user_id,
            $target_user_id
        ));

        if (!empty($thread_id)) {
            return (int) $thread_id;
        }

        $created = $wpdb->insert(
            $threads_table,
            array(
                'thread_type' => 'direct',
                'created_by' => $user_id,
                'last_message_at' => current_time('mysql'),
            ),
            array('%s', '%d', '%s')
        );

        if (!$created) {
            return 0;
        }

        $new_thread_id = (int) $wpdb->insert_id;

        $wpdb->insert(
            $participants_table,
            array(
                'thread_id' => $new_thread_id,
                'user_id' => $user_id,
                'role_slug' => $this->get_user_primary_role($user_id),
            ),
            array('%d', '%d', '%s')
        );

        $wpdb->insert(
            $participants_table,
            array(
                'thread_id' => $new_thread_id,
                'user_id' => $target_user_id,
                'role_slug' => $this->get_user_primary_role($target_user_id),
            ),
            array('%d', '%d', '%s')
        );

        return $new_thread_id;
    }

    private function is_thread_participant($thread_id, $user_id) {
        global $wpdb;
        $participants_table = $wpdb->prefix . 'mf_chat_participants';

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$participants_table} WHERE thread_id = %d AND user_id = %d LIMIT 1",
            $thread_id,
            $user_id
        ));

        return !empty($exists);
    }

    private function get_thread_messages($thread_id, $viewer_id, $after_message_id = 0) {
        global $wpdb;

        $messages_table = $wpdb->prefix . 'mf_chat_messages';
        $users_table = $wpdb->users;

        $query = $wpdb->prepare(
            "SELECT m.id, m.thread_id, m.sender_id, m.message, m.created_at, u.display_name AS sender_name
             FROM {$messages_table} m
             INNER JOIN {$users_table} u ON u.ID = m.sender_id
             WHERE m.thread_id = %d AND m.id > %d
             ORDER BY m.id ASC",
            $thread_id,
            $after_message_id
        );

        $rows = $wpdb->get_results($query, ARRAY_A);
        if (empty($rows)) {
            return array();
        }

        $messages = array();
        foreach ($rows as $row) {
            $messages[] = array(
                'id' => (int) $row['id'],
                'threadId' => (int) $row['thread_id'],
                'senderId' => (int) $row['sender_id'],
                'senderName' => $row['sender_name'],
                'message' => $row['message'],
                'isOwn' => ((int) $row['sender_id'] === (int) $viewer_id),
                'createdAt' => mysql2date('c', $row['created_at']),
                'createdAtHuman' => mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $row['created_at']),
            );
        }

        return $messages;
    }

    private function get_user_threads($user_id) {
        global $wpdb;

        $threads_table = $wpdb->prefix . 'mf_chat_threads';
        $participants_table = $wpdb->prefix . 'mf_chat_participants';
        $messages_table = $wpdb->prefix . 'mf_chat_messages';
        $notifications_table = $wpdb->prefix . 'mf_chat_notifications';
        $users_table = $wpdb->users;

        $query = $wpdb->prepare(
            "SELECT t.id AS thread_id,
                    t.last_message_at,
                    m.message AS last_message,
                    m.sender_id AS last_sender_id,
                    m.created_at AS last_message_created,
                    m.id AS last_message_id,
                    (
                        SELECT COUNT(n.id)
                        FROM {$notifications_table} n
                        WHERE n.thread_id = t.id AND n.user_id = %d AND n.is_read = 0
                    ) AS unread_count
             FROM {$threads_table} t
             INNER JOIN {$participants_table} p ON p.thread_id = t.id
             LEFT JOIN {$messages_table} m ON m.id = t.last_message_id
             WHERE p.user_id = %d
             ORDER BY COALESCE(t.last_message_at, t.created_at) DESC",
            $user_id,
            $user_id
        );

        $rows = $wpdb->get_results($query, ARRAY_A);
        if (empty($rows)) {
            return array();
        }

        $threads = array();
        foreach ($rows as $row) {
            $thread_id = (int) $row['thread_id'];
            $participants = $wpdb->get_results($wpdb->prepare(
                "SELECT p.user_id, u.display_name, p.role_slug
                 FROM {$participants_table} p
                 INNER JOIN {$users_table} u ON u.ID = p.user_id
                 WHERE p.thread_id = %d AND p.user_id != %d",
                $thread_id,
                $user_id
            ), ARRAY_A);

            $participant_names = array();
            $participant_ids = array();
            foreach ($participants as $participant) {
                $participant_ids[] = (int) $participant['user_id'];
                $participant_names[] = $participant['display_name'];
            }

            $threads[] = array(
                'id' => $thread_id,
                'participantIds' => $participant_ids,
                'title' => !empty($participant_names) ? implode(', ', $participant_names) : __('Conversation', 'malisafi-mls'),
                'lastMessage' => $row['last_message'] ? wp_trim_words($row['last_message'], 14, '…') : '',
                'lastMessageAt' => !empty($row['last_message_created']) ? mysql2date('c', $row['last_message_created']) : null,
                'unreadCount' => (int) $row['unread_count'],
            );
        }

        return $threads;
    }

    private function create_notifications_for_thread($thread_id, $message_id, $sender_id) {
        global $wpdb;

        $participants_table = $wpdb->prefix . 'mf_chat_participants';
        $notifications_table = $wpdb->prefix . 'mf_chat_notifications';

        $participant_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT user_id FROM {$participants_table} WHERE thread_id = %d AND user_id != %d",
            $thread_id,
            $sender_id
        ));

        if (empty($participant_ids)) {
            return;
        }

        foreach ($participant_ids as $participant_id) {
            $wpdb->insert(
                $notifications_table,
                array(
                    'user_id' => (int) $participant_id,
                    'thread_id' => $thread_id,
                    'message_id' => $message_id,
                    'is_read' => 0,
                ),
                array('%d', '%d', '%d', '%d')
            );
        }
    }

    private function mark_thread_read($thread_id, $user_id) {
        global $wpdb;

        $notifications_table = $wpdb->prefix . 'mf_chat_notifications';
        $participants_table = $wpdb->prefix . 'mf_chat_participants';
        $messages_table = $wpdb->prefix . 'mf_chat_messages';

        $wpdb->query($wpdb->prepare(
            "UPDATE {$notifications_table}
             SET is_read = 1, read_at = %s
             WHERE thread_id = %d AND user_id = %d AND is_read = 0",
            current_time('mysql'),
            $thread_id,
            $user_id
        ));

        $last_message_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$messages_table} WHERE thread_id = %d ORDER BY id DESC LIMIT 1",
            $thread_id
        ));

        if ($last_message_id > 0) {
            $wpdb->update(
                $participants_table,
                array(
                    'last_read_message_id' => $last_message_id,
                    'last_read_at' => current_time('mysql'),
                ),
                array(
                    'thread_id' => $thread_id,
                    'user_id' => $user_id,
                ),
                array('%d', '%s'),
                array('%d', '%d')
            );
        }
    }

    private function get_unread_total($user_id) {
        global $wpdb;
        $notifications_table = $wpdb->prefix . 'mf_chat_notifications';

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(id) FROM {$notifications_table} WHERE user_id = %d AND is_read = 0",
            $user_id
        ));
    }
}
