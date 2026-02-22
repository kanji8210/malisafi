/**
 * Enqueue chat assets on admin pages.
 */
function malisafi_enqueue_chat_assets() {
    $screen = get_current_screen();
    // Enqueue on all admin pages
    wp_enqueue_style('malisafi-chat-css', plugin_dir_url(__FILE__) . 'assets/css/malisafi-chat.css', array(), '1.0.0');
    wp_enqueue_script('malisafi-chat-js', plugin_dir_url(__FILE__) . 'assets/js/malisafi-chat.js', array('jquery'), '1.0.0', true);

    // Prepare i18n strings
    $i18n = array(
        'noThreads'          => __('No conversations yet.', 'malisafi-mls'),
        'selectConversation' => __('Select a conversation', 'malisafi-mls'),
        'loadFailed'         => __('Unable to load chat data right now.', 'malisafi-mls'),
        'unableOpen'         => __('Unable to open chat right now.', 'malisafi-mls'),
        'sendFailed'         => __('Unable to send message right now.', 'malisafi-mls'),
        'refreshRetry'       => __('Please refresh and try again.', 'malisafi-mls'),
    );

    // Determine if floating widget is enabled (you may set this via plugin settings)
    $is_floating_widget = true; // or get_option('malisafi_chat_floating', true);

    // Initial target user ID (e.g., from URL or elsewhere)
    $initial_target = isset($_GET['chat_with']) ? intval($_GET['chat_with']) : 0;

    wp_localize_script('malisafi-chat-js', 'malisafiChat', array(
        'ajaxurl'             => admin_url('admin-ajax.php'),
        'nonce'               => wp_create_nonce('malisafi_chat_nonce'),
        'i18n'                => $i18n,
        'pollInterval'        => 10000, // 10 seconds
        'isFloatingWidget'    => $is_floating_widget,
        'initialTargetUserId' => $initial_target,
    ));
}
add_action('admin_enqueue_scripts', 'malisafi_enqueue_chat_assets');