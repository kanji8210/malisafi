<?php
/**
 * Internal chat system for admin/moderator/agents.
 *
 * @package MalisafiMLS
 *
 * @note The allowed chat roles can be extended via the 'malisafi_chat_roles' filter.
 */

namespace MalisafiMLS;

if (!defined('ABSPATH')) {
    exit;
}

class Internal_Chat {

    private static $instance = null;

    private $chat_roles = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function get_chat_roles() {
        if ($this->chat_roles === null) {
            $roles = array(
                'administrator',
                'editor',
                'subscriber',
                'moderator',
                'malisafi_moderator',
                'malisafi_agent',
                'malisafi_agent_basic',
                'malisafi_agent_premium',
            );
            $this->chat_roles = apply_filters('malisafi_chat_roles', $roles);
        }
        return $this->chat_roles;
    }

    private function __construct() {
        add_action('init', array($this, 'ensure_chat_schema'));
        
        // Public REST routes for token-based client access
        add_action('rest_api_init', array($this, 'register_public_routes'));

        // Hook into a scheduled repair action so DB repairs run via WP-Cron (managed)
        add_action('malisafi_repair_schema_hook', array('\MalisafiMLS\Database', 'repair_schema'));

        // Shortcode to render public chat UI (place on a page)
        add_shortcode('malisafi_public_chat', array($this, 'render_public_chat_shortcode'));
        add_action('wp_ajax_malisafi_chat_bootstrap', array($this, 'ajax_bootstrap'));
        add_action('wp_ajax_malisafi_chat_open_thread', array($this, 'ajax_open_thread'));
        add_action('wp_ajax_malisafi_chat_fetch_messages', array($this, 'ajax_fetch_messages'));
        add_action('wp_ajax_malisafi_chat_send_message', array($this, 'ajax_send_message'));
        add_action('wp_ajax_malisafi_chat_mark_read', array($this, 'ajax_mark_read'));
        add_action('wp_ajax_malisafi_chat_fetch_notifications', array($this, 'ajax_fetch_notifications'));
        add_action('wp_ajax_malisafi_chat_store_contact', array($this, 'ajax_store_contact'));
        // Allow anonymous users to store contact messages (public contact form)
        add_action('wp_ajax_nopriv_malisafi_chat_store_contact', array($this, 'ajax_store_contact'));


        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_post_malisafi_chat_assign', array($this, 'handle_admin_assign'));
        add_action('admin_post_malisafi_chat_admin_reply', array($this, 'handle_admin_reply'));
        // Public: allow visitors to start a chat session (create public thread + token)
        add_action('wp_ajax_malisafi_chat_start_public', array($this, 'ajax_start_public_chat'));
        add_action('wp_ajax_nopriv_malisafi_chat_start_public', array($this, 'ajax_start_public_chat'));
        // Allow visitors to explicitly end their public chat session
        add_action('wp_ajax_malisafi_chat_end_public', array($this, 'ajax_end_public_chat'));
        add_action('wp_ajax_nopriv_malisafi_chat_end_public', array($this, 'ajax_end_public_chat'));
    }

    public function ajax_store_contact() {
        // Accept from unlogged users
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';

        // Basic validation
        if (empty($name) || empty($email) || empty($message)) {
            wp_send_json_error(array('message' => __('Please fill all required fields.', 'malisafi-mls')));
        }

        if (!is_email($email)) {
            wp_send_json_error(array('message' => __('Please provide a valid email address.', 'malisafi-mls')));
        }

        // Honeypot check
        if (!empty($_POST['hp_field'])) {
            wp_send_json_error(array('message' => __('Spam detected.', 'malisafi-mls')));
        }

        // Optional: verify reCAPTCHA if configured
        $recaptcha_secret = get_option('malisafi_recaptcha_secret', '');
        if (!empty($recaptcha_secret) && !empty($_POST['g-recaptcha-response'])) {
            $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array(
                'body' => array(
                    'secret' => $recaptcha_secret,
                    'response' => sanitize_text_field($_POST['g-recaptcha-response']),
                    'remoteip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''
                )
            ));
            $body = wp_remote_retrieve_body($response);
            $result = json_decode($body, true);
            if (empty($result['success'])) {
                wp_send_json_error(array('message' => __('Captcha verification failed.', 'malisafi-mls')));
            }
        }

        // Rate-limit by IP to prevent spam (4 submissions per 5 minutes)
        $ip = isset($_SERVER['REMOTE_ADDR']) ? preg_replace('/[^0-9a-fA-F:\.\:]/', '', $_SERVER['REMOTE_ADDR']) : '';
        $ip_key = 'malisafi_contact_ip_' . md5($ip ?: 'unknown');
        $limit_count = (int) get_transient($ip_key);
        $limit_max = 4;
        if ($limit_count >= $limit_max) {
            wp_send_json_error(array('message' => __('Too many requests. Please wait and try again.', 'malisafi-mls')));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'mf_chat_contacts';
        $inserted = $wpdb->insert($table, array(
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'message' => $message,
            'created_at' => current_time('mysql'),
        ), array('%s', '%s', '%s', '%s', '%s'));

        if (!$inserted) {
            wp_send_json_error(array('message' => __('Failed to save contact.', 'malisafi-mls')));
        }

        // Increment rate-limit counter (expires in 5 minutes)
        $limit_count++;
        set_transient($ip_key, $limit_count, 5 * MINUTE_IN_SECONDS);

        $contact_id = (int) $wpdb->insert_id;

        // Create a lightweight thread for this public contact so admins can manage/assign it
        $threads_table = $wpdb->prefix . 'mf_chat_threads';
        $messages_table = $wpdb->prefix . 'mf_chat_messages';

        $created_thread = $wpdb->insert(
            $threads_table,
            array(
                'thread_type' => 'public',
                'created_by' => 0,
                'last_message_at' => current_time('mysql'),
            ),
            array('%s', '%d', '%s')
        );

        $thread_id = $created_thread ? (int) $wpdb->insert_id : 0;

        if ($thread_id > 0 && !empty($message)) {
            // Insert initial message as anonymous (sender_id = 0)
            $wpdb->insert(
                $messages_table,
                array(
                    'thread_id' => $thread_id,
                    'sender_id' => 0,
                    'message' => $message,
                ),
                array('%d', '%d', '%s')
            );

            $initial_message_id = (int) $wpdb->insert_id;
            // Update thread last message id/time
            $wpdb->update(
                $threads_table,
                array('last_message_id' => $initial_message_id, 'last_message_at' => current_time('mysql')),
                array('id' => $thread_id),
                array('%d', '%s'),
                array('%d')
            );
        }

        // Generate a public token so the client can view/respond to this thread on-site
        if ($thread_id > 0) {
            $token = wp_generate_password(24, false, false);
            $public_entry = array(
                'thread_id' => $thread_id,
                'contact' => $lead_data,
                'token' => $token,
            );
            // Store token mapping
            update_option('malisafi_chat_public_token_' . $token, $public_entry, false);
            // Also store a reverse mapping for convenience
            update_option('malisafi_chat_thread_contact_' . $thread_id, $public_entry, false);
        }

        // Fire CRM integration hook so external systems can create a lead
        $lead_data = array(
            'id' => $contact_id,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'message' => $message,
            'created_at' => current_time('mysql'),
        );
        /**
         * Action: malisafi_new_lead
         *
         * Allows CRM integration or lead creation handlers to process a new public contact.
         * Handlers receive one parameter: $lead_data (array)
         */
        do_action('malisafi_new_lead', $lead_data);

        // Store thread->contact mapping for assignment notifications
        if (!empty($thread_id)) {
            update_option('malisafi_chat_thread_contact_' . $thread_id, $lead_data, false);
        }

        // Notify admins/moderators about new public contact so they can create/assign a thread
        $notify_roles = array('administrator', 'malisafi_moderator');
        $admins = get_users(array('role__in' => $notify_roles, 'fields' => array('user_email', 'display_name')));
        if (!empty($admins)) {
            $subject = sprintf(__('New contact from %s', 'malisafi-mls'), $name);
            $manage_url = admin_url('admin.php?page=malisafi-chat-management');
            $body = sprintf("%s\n\n%s: %s\n%s: %s\n\n%s\n\n%s",
                __('A new public contact has been submitted via the site.', 'malisafi-mls'),
                __('Name', 'malisafi-mls'), $name,
                __('Email', 'malisafi-mls'), $email,
                __('Message', 'malisafi-mls'),
                $manage_url
            );
            foreach ($admins as $a) {
                if (!empty($a->user_email)) {
                    wp_mail($a->user_email, $subject, $body);
                }
            }
        }

        wp_send_json_success(array('message' => __('Contact saved.', 'malisafi-mls'), 'contact_id' => $contact_id));
    }

    public function ensure_chat_schema() {
        if (get_option('malisafi_mls_chat_schema_ready') !== '1') {
            Database::update_schema();
            update_option('malisafi_mls_chat_schema_ready', '1');
        }

        // Schedule repair_schema via WP-Cron rather than running it inline.
        // Only schedule once and only when an admin is present (to avoid scheduling during public requests).
        if (is_admin() && current_user_can('manage_options')) {
            if (!wp_next_scheduled('malisafi_repair_schema_hook')) {
                wp_schedule_single_event(time() + 30, 'malisafi_repair_schema_hook');
            }
        }
    }

    public function enqueue_assets() {
        if (is_admin()) {
            return;
        }
        // Enqueue public visitor widget on all front-end pages (bottom-left floating chat)
        wp_enqueue_style(
            'malisafi-public-chat-widget',
            MALISAFI_MLS_URL . 'assets/css/public-chat-widget.css',
            array(),
            MALISAFI_MLS_VERSION
        );
        wp_enqueue_script(
            'malisafi-public-chat-widget',
            MALISAFI_MLS_URL . 'assets/js/public-chat-widget.js',
            array('jquery'),
            MALISAFI_MLS_VERSION,
            true
        );
        // If not explicitly enabled, silence console output in production
        wp_add_inline_script('malisafi-public-chat-widget', "(function(){if(!window.malisafiDebug){try{console.log=function(){},console.debug=function(){},console.info=function(){},console.warn=function(){},console.error=function(){}}catch(e){} }})();");
        wp_localize_script('malisafi-public-chat-widget', 'malisafiPublicChat', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'restBase' => esc_url_raw(rest_url('malisafi/v1/public-chat/')),
            'recaptchaSiteKey' => get_option('malisafi_recaptcha_site_key', ''),
            'pollInterval' => 5000,
                'i18n' => array(
                // Minimal/neutral public widget labels — keep short to avoid stacked header text
                'startChat' => __('Start chat', 'malisafi-mls'),
                'sending' => __('Sending...', 'malisafi-mls'),
                'send' => __('Send', 'malisafi-mls'),
                'emptyMessage' => __('Please enter a message.', 'malisafi-mls'),
                'end' => __('End', 'malisafi-mls'),
                'chatEnded' => __('This chat has ended. You can start a new chat.', 'malisafi-mls'),
                'welcome' => __('Hi %s — thanks for joining! How can we help today?', 'malisafi-mls'),
                'agentOnline' => '',
                'agentOffline' => '',
            ),
        ));

        // Only enqueue internal chat assets when the chat shortcode or the
        // internal floating widget (for logged-in chat users) should render.
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

        // Silence console output for internal chat scripts unless debugging enabled
        wp_add_inline_script('malisafi-internal-chat', "(function(){if(!window.malisafiDebug){try{console.log=function(){},console.debug=function(){},console.info=function(){},console.warn=function(){},console.error=function(){}}catch(e){} }})();");

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

        // Rate limiting: max 5 messages per minute per user
        $limit = 5;
        $window = 60; // seconds
        $now = time();
        $meta_key = '_malisafi_chat_msg_times';
        $msg_times = get_user_meta($user_id, $meta_key, true);
        if (!is_array($msg_times)) {
            $msg_times = array();
        }
        // Remove old timestamps
        $msg_times = array_filter($msg_times, function($t) use ($now, $window) { return ($t > $now - $window); });
        if (count($msg_times) >= $limit) {
            wp_send_json_error(array('message' => __('You are sending messages too quickly. Please wait a moment.', 'malisafi-mls')));
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

        // Add this message timestamp and save
        $msg_times[] = $now;
        update_user_meta($user_id, $meta_key, $msg_times);

        $message_id = $this->persist_message($thread_id, $user_id, $message);
        if ($message_id <= 0) {
            wp_send_json_error(array('message' => __('Failed to send message.', 'malisafi-mls')));
        }

        wp_send_json_success(array(
            'message_id' => $message_id,
            'thread_id' => $thread_id,
        ));
    }

    public function register_public_routes() {
        register_rest_route('malisafi/v1', '/public-chat/(?P<token>[A-Za-z0-9_-]+)', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'public_get_messages'),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods' => 'POST',
                'callback' => array($this, 'public_post_message'),
                'permission_callback' => '__return_true',
            ),
        ));
    }

    private function find_thread_for_token($token) {
        global $wpdb;
        $threads_table = $wpdb->prefix . 'mf_chat_threads';
        // Prefer DB-backed token column
        $row = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$threads_table} WHERE public_token = %s LIMIT 1", $token));
        if ($row && !empty($row->id)) {
            return (int) $row->id;
        }

        // Fallback to old option mapping
        $entry = get_option('malisafi_chat_public_token_' . $token, false);
        if (!$entry || empty($entry['thread_id'])) {
            return 0;
        }
        return (int) $entry['thread_id'];
    }

    public function public_get_messages($request) {
        $token = $request->get_param('token');
        $after = isset($request['after_id']) ? absint($request['after_id']) : 0;
        $thread_id = $this->find_thread_for_token($token);
        if ($thread_id <= 0) {
            return new \WP_REST_Response(array('message' => __('Invalid token.', 'malisafi-mls')), 404);
        }
        $messages = $this->get_thread_messages($thread_id, 0, $after);
        // Fetch thread status, public_contact and assigned agent if available
        global $wpdb;
        $threads_table = $wpdb->prefix . 'mf_chat_threads';
        $row = $wpdb->get_row($wpdb->prepare("SELECT status, public_contact, assigned_to FROM {$threads_table} WHERE id = %d", $thread_id), ARRAY_A);
        $status = $row && isset($row['status']) ? $row['status'] : 'active';
        $public_contact = array();
        if ($row && !empty($row['public_contact'])) {
            $public_contact = json_decode($row['public_contact'], true) ?: array();
        }

        $agent = array('id' => 0, 'name' => '', 'avatar' => '', 'typing' => false);
        if ($row && !empty($row['assigned_to'])) {
            $assigned = absint($row['assigned_to']);
            if ($assigned > 0) {
                $u = get_userdata($assigned);
                if ($u) {
                    $agent['id'] = $assigned;
                    $agent['name'] = $u->display_name;
                    $agent['avatar'] = get_avatar_url($assigned, array('size' => 64));
                    // Typing status could be set by other mechanisms; default false
                    $agent['typing'] = false;
                }
            }
        }

        return rest_ensure_response(array('messages' => $messages, 'status' => $status, 'public_contact' => $public_contact, 'agent' => $agent));
    }

    public function public_post_message($request) {
        $token = $request->get_param('token');
        $thread_id = $this->find_thread_for_token($token);
        if ($thread_id <= 0) {
            return new \WP_REST_Response(array('message' => __('Invalid token.', 'malisafi-mls')), 404);
        }

        // Check thread status - disallow posting to archived/ended threads
        global $wpdb;
        $threads_table = $wpdb->prefix . 'mf_chat_threads';
        $thread_status = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$threads_table} WHERE id = %d", $thread_id));
        if ($thread_status && $thread_status !== 'active') {
            return new \WP_REST_Response(array('message' => __('This chat has been ended. Please start a new chat.', 'malisafi-mls'), 'ended' => true), 410);
        }

        $params = $request->get_json_params();
        $message = isset($params['message']) ? sanitize_textarea_field($params['message']) : '';
        $name = isset($params['name']) ? sanitize_text_field($params['name']) : '';
        $email = isset($params['email']) ? sanitize_email($params['email']) : '';

        if (empty($message)) {
            return new \WP_REST_Response(array('message' => __('Message cannot be empty.', 'malisafi-mls')), 400);
        }

        // Simple IP rate limit for public messages
        $ip = isset($_SERVER['REMOTE_ADDR']) ? preg_replace('/[^0-9a-fA-F:\\.\\:]/', '', $_SERVER['REMOTE_ADDR']) : '';
        $key = 'malisafi_public_msg_' . md5($ip ?: 'unknown_' . $thread_id);
        $count = (int) get_transient($key);
        if ($count >= 10) {
            return new \WP_REST_Response(array('message' => __('Too many messages. Please wait.', 'malisafi-mls')), 429);
        }
        $count++;
        set_transient($key, $count, 60);

        global $wpdb;
        $messages_table = $wpdb->prefix . 'mf_chat_messages';
        $wpdb->insert($messages_table, array(
            'thread_id' => $thread_id,
            'sender_id' => 0,
            'message' => $message,
        ), array('%d', '%d', '%s'));

        $msg_id = (int) $wpdb->insert_id;
        // Update thread last_message
        $threads_table = $wpdb->prefix . 'mf_chat_threads';
        $wpdb->update($threads_table, array('last_message_id' => $msg_id, 'last_message_at' => current_time('mysql')), array('id' => $thread_id), array('%d','%s'), array('%d'));

        // Optionally update stored contact name/email
        if (!empty($name) || !empty($email)) {
            global $wpdb;
            $threads_table = $wpdb->prefix . 'mf_chat_threads';
            $col_exists = $wpdb->get_row("SHOW COLUMNS FROM {$threads_table} LIKE 'public_contact'");
            if ($col_exists) {
                $entry_json = $wpdb->get_var($wpdb->prepare("SELECT public_contact FROM {$threads_table} WHERE id = %d", $thread_id));
                $entry = $entry_json ? json_decode($entry_json, true) : array();
                if (!is_array($entry)) $entry = array();
                if (!empty($name)) $entry['name'] = $name;
                if (!empty($email)) $entry['email'] = $email;
                $wpdb->update($threads_table, array('public_contact' => wp_json_encode($entry)), array('id' => $thread_id), array('%s'), array('%d'));
            } else {
                $entry = get_option('malisafi_chat_thread_contact_' . $thread_id, false);
                if ($entry && is_array($entry)) {
                    if (!empty($name)) $entry['contact']['name'] = $name;
                    if (!empty($email)) $entry['contact']['email'] = $email;
                    update_option('malisafi_chat_thread_contact_' . $thread_id, $entry, false);
                    // keep token mapping consistent
                    if (!empty($entry['token'])) {
                        update_option('malisafi_chat_public_token_' . $entry['token'], $entry, false);
                    }
                }
            }
        }

        // Trigger notifications to participants
        $this->create_notifications_for_thread($thread_id, $msg_id, 0);

        return rest_ensure_response(array('message_id' => $msg_id));
    }

    /**
     * AJAX: Start a public chat session for a visitor (creates a public thread and returns a token/link)
     */
    public function ajax_start_public_chat() {
        // Allow both logged-in and anonymous visitors
        global $wpdb;

        // Honeypot check
        if (!empty($_POST['hp_field'])) {
            wp_send_json_error(array('message' => __('Spam detected.', 'malisafi-mls')));
        }

        // Optional: verify reCAPTCHA if configured
        $recaptcha_secret = get_option('malisafi_recaptcha_secret', '');
        if (!empty($recaptcha_secret) && !empty($_POST['g-recaptcha-response'])) {
            $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array(
                'body' => array(
                    'secret' => $recaptcha_secret,
                    'response' => sanitize_text_field($_POST['g-recaptcha-response']),
                    'remoteip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''
                )
            ));
            $body = wp_remote_retrieve_body($response);
            $result = json_decode($body, true);
            if (empty($result['success'])) {
                wp_send_json_error(array('message' => __('Captcha verification failed.', 'malisafi-mls')));
            }
        }

        $threads_table = $wpdb->prefix . 'mf_chat_threads';
        $inserted = $wpdb->insert(
            $threads_table,
            array(
                'thread_type' => 'public',
                'created_by' => 0,
                'last_message_at' => current_time('mysql'),
            ),
            array('%s', '%d', '%s')
        );

        if (!$inserted) {
            wp_send_json_error(array('message' => __('Unable to start chat. Please try again later.', 'malisafi-mls')));
        }

        $thread_id = (int) $wpdb->insert_id;
        $token = wp_generate_password(24, false, false);

        // Accept optional contact info during start (name required by UI)
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';

        $contact = array();
        if (!empty($name)) $contact['name'] = $name;
        if (!empty($email)) $contact['email'] = $email;
        if (!empty($phone)) $contact['phone'] = $phone;

        // If DB column exists, store token and contact JSON directly on thread row
        $threads_table = $wpdb->prefix . 'mf_chat_threads';
        $col_exists = $wpdb->get_row("SHOW COLUMNS FROM {$threads_table} LIKE 'public_token'");
        if ($col_exists) {
            $wpdb->update($threads_table, array('public_token' => $token, 'public_contact' => wp_json_encode($contact)), array('id' => $thread_id), array('%s','%s'), array('%d'));
        } else {
            // Fallback: store as options for older installations
            $entry = array(
                'thread_id' => $thread_id,
                'contact' => $contact,
                'token' => $token,
            );
            update_option('malisafi_chat_public_token_' . $token, $entry, false);
            update_option('malisafi_chat_thread_contact_' . $thread_id, $entry, false);
        }

        $public_link = add_query_arg('malisafi_chat_token', $token, home_url('/'));

        wp_send_json_success(array('thread_id' => $thread_id, 'token' => $token, 'public_link' => $public_link));
    }

    /**
     * AJAX: End a public chat session (visitor-initiated)
     */
    public function ajax_end_public_chat() {
        // Allow both logged-in and anonymous visitors
        $token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : (isset($_REQUEST['token']) ? sanitize_text_field($_REQUEST['token']) : '');
        if (empty($token)) {
            wp_send_json_error(array('message' => __('Missing token.', 'malisafi-mls')));
        }

        $thread_id = $this->find_thread_for_token($token);
        if ($thread_id <= 0) {
            wp_send_json_error(array('message' => __('Invalid token.', 'malisafi-mls')));
        }

        global $wpdb;
        $threads_table = $wpdb->prefix . 'mf_chat_threads';
        $updated = $wpdb->update($threads_table, array('status' => 'ended'), array('id' => $thread_id), array('%s'), array('%d'));

        if ($updated === false) {
            wp_send_json_error(array('message' => __('Unable to end chat. Please try again later.', 'malisafi-mls')));
        }

        // Optionally notify admins about user-ended chat
        do_action('malisafi_public_chat_ended_by_user', $thread_id);

        wp_send_json_success(array('message' => __('Chat ended.', 'malisafi-mls')));
    }

    public function render_public_chat_shortcode($atts = array()) {
        $token = isset($_GET['malisafi_chat_token']) ? sanitize_text_field($_GET['malisafi_chat_token']) : '';
        ob_start();
        ?>
        <div class="malisafi-public-chat" data-token="<?php echo esc_attr($token); ?>">
            <div id="malisafi-public-chat-messages">Loading messages...</div>
            <form id="malisafi-public-chat-form">
                <input type="text" id="malisafi-public-name" placeholder="Your name" />
                <input type="email" id="malisafi-public-email" placeholder="Your email" />
                <textarea id="malisafi-public-message" rows="3" placeholder="Type your message..."></textarea>
                <button type="submit"><?php esc_html_e('Send', 'malisafi-mls'); ?></button>
            </form>
        </div>
        <script>
        (function(){
            var container = document.querySelector('.malisafi-public-chat');
            if(!container) return;
            var token = container.getAttribute('data-token');
            if(!token) { container.innerHTML = '<p><?php echo esc_js(__('Invalid chat token.', 'malisafi-mls')); ?></p>'; return; }

            var messagesEl = document.getElementById('malisafi-public-chat-messages');
            var form = document.getElementById('malisafi-public-chat-form');

            function fetchMessages(){
                fetch('<?php echo esc_js(esc_url_raw(rest_url('malisafi/v1/public-chat/'))); ?>' + token)
                .then(function(r){ return r.json(); })
                .then(function(data){
                    if(data.messages){
                        messagesEl.innerHTML = data.messages.map(function(m){ return '<div class="msg"><strong>'+escapeHtml(m.senderName||'Client')+':</strong> '+escapeHtml(m.message)+'</div>'; }).join('');
                    }
                });
            }

            function escapeHtml(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

            form.addEventListener('submit', function(e){
                e.preventDefault();
                var name = document.getElementById('malisafi-public-name').value;
                var email = document.getElementById('malisafi-public-email').value;
                var message = document.getElementById('malisafi-public-message').value;
                fetch('<?php echo esc_js(esc_url_raw(rest_url('malisafi/v1/public-chat/'))); ?>' + token, {
                    method: 'POST',
                    headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({name:name,email:email,message:message})
                }).then(function(r){ return r.json(); }).then(function(resp){ document.getElementById('malisafi-public-message').value=''; fetchMessages(); });
            });

            fetchMessages();
            setInterval(fetchMessages, 5000);
        })();
        </script>
        <?php
        return ob_get_clean();
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

    /**
     * Public: fetch messages for public chat (anonymous)
     */
    

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

        // If this thread was created from a public contact, notify the contact of the assignment
        $contact_data = get_option('malisafi_chat_thread_contact_' . $thread_id, false);
        if ($contact_data && !empty($contact_data['email']) && $assigned_to > 0) {
            $assignee = get_userdata($assigned_to);
            $assignee_name = $assignee ? $assignee->display_name : '';
            $assignee_email = $assignee ? $assignee->user_email : '';

            $subject = sprintf(__('You have been assigned a representative - %s', 'malisafi-mls'), get_bloginfo('name'));
            $body = sprintf("%s\n\n%s: %s\n%s: %s\n\n%s",
                __('A representative has been assigned to your inquiry. You can reply to them via email or wait for their message.', 'malisafi-mls'),
                __('Assigned to', 'malisafi-mls'), $assignee_name,
                __('Email', 'malisafi-mls'), $assignee_email,
                __('If you have further questions, please reply to this email.', 'malisafi-mls')
            );

            wp_mail($contact_data['email'], $subject, $body);
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
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Malisafi Chat] verify_chat_request: not_logged_in - POST: ' . print_r($_POST, true));
            }
            return new \WP_Error('not_logged_in', __('You must be logged in.', 'malisafi-mls'));
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'malisafi_chat_nonce')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Malisafi Chat] verify_chat_request: invalid_nonce - POST: ' . print_r($_POST, true));
            }
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
        $chat_roles = $this->get_chat_roles();
        foreach ($user->roles as $role) {
            if (in_array($role, $chat_roles, true)) {
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
            "SELECT m.id, m.thread_id, m.sender_id, m.message, m.created_at, COALESCE(u.display_name, '') AS sender_name
             FROM {$messages_table} m
             LEFT JOIN {$users_table} u ON u.ID = m.sender_id
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
                'thread_id' => (int) $row['thread_id'],
                'sender_id' => (int) $row['sender_id'],
                'senderName' => $row['sender_name'],
                'message' => $row['message'],
                'isOwn' => ((int) $row['sender_id'] === (int) $viewer_id),
                'created_at' => mysql2date('c', $row['created_at']),
                'created_at_human' => mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $row['created_at']),
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
