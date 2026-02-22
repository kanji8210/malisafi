<?php
/**
 * Internal Chat Template
 *
 * @package MalisafiMLS
 */

defined('ABSPATH') || exit;

$is_floating_widget = isset($is_floating_widget) ? (bool) $is_floating_widget : false;
$container_classes  = 'malisafi-internal-chat';
if ($is_floating_widget) {
    $container_classes .= ' is-floating-widget is-collapsed';
}
?>

<div class="<?php echo esc_attr($container_classes); ?>" id="malisafi-internal-chat"
     data-floating="<?php echo $is_floating_widget ? '1' : '0'; ?>"
     <?php if ($is_floating_widget) : ?>
     role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Internal Chat', 'malisafi-mls'); ?>"
     <?php endif; ?>>

    <?php if ($is_floating_widget) : ?>
        <button type="button" class="malisafi-chat-toggle" id="malisafi-chat-toggle"
                aria-expanded="false" aria-label="<?php esc_attr_e('Toggle chat', 'malisafi-mls'); ?>">
            <span class="malisafi-chat-toggle-icon" aria-hidden="true">💬</span>
            <span class="malisafi-chat-toggle-label"><?php esc_html_e('Chat', 'malisafi-mls'); ?></span>
            <span class="malisafi-chat-unread-badge" id="malisafi-chat-unread-total">0</span>
        </button>
    <?php endif; ?>

    <aside class="malisafi-chat-sidebar" aria-label="<?php esc_attr_e('Conversations', 'malisafi-mls'); ?>">
        <div class="malisafi-chat-header">
            <h3><?php esc_html_e('Internal Chat', 'malisafi-mls'); ?></h3>
            <?php if (!$is_floating_widget) : ?>
                <span class="malisafi-chat-unread-badge malisafi-chat-unread-badge-header">0</span>
            <?php endif; ?>
        </div>

        <div class="malisafi-chat-section">
            <h4><?php esc_html_e('Conversations', 'malisafi-mls'); ?></h4>
            <div id="malisafi-chat-threads" class="malisafi-chat-list" role="listbox" aria-label="<?php esc_attr_e('Conversation list', 'malisafi-mls'); ?>">
                <!-- Conversations will be loaded here dynamically -->
                <div class="malisafi-chat-empty"><?php esc_html_e('No conversations yet. Start one by messaging a colleague.', 'malisafi-mls'); ?></div>
            </div>
        </div>
    </aside>

    <main class="malisafi-chat-main" aria-label="<?php esc_attr_e('Chat messages', 'malisafi-mls'); ?>">
        <div class="malisafi-chat-main-header-wrap">
            <div class="malisafi-chat-main-header" id="malisafi-chat-main-header">
                <?php esc_html_e('Select a conversation to start chatting.', 'malisafi-mls'); ?>
            </div>
            <?php if ($is_floating_widget) : ?>
                <button type="button" class="malisafi-chat-close" id="malisafi-chat-close"
                        aria-label="<?php esc_attr_e('Close chat', 'malisafi-mls'); ?>">×</button>
            <?php endif; ?>
        </div>

        <div id="malisafi-chat-messages" class="malisafi-chat-messages" role="log" aria-live="polite">
            <!-- Messages will appear here -->
            <div class="malisafi-chat-empty"><?php esc_html_e('No messages yet. Say hello!', 'malisafi-mls'); ?></div>
        </div>

        <div id="malisafi-chat-status" class="malisafi-chat-status" aria-live="polite"></div>

        <form id="malisafi-chat-form" class="malisafi-chat-form" autocomplete="off">
            <?php wp_nonce_field('malisafi_chat_send', 'chat_nonce'); ?>
            <textarea id="malisafi-chat-input" rows="2" maxlength="2000"
                      placeholder="<?php esc_attr_e('Type a message... (Enter to send, Shift+Enter for new line)', 'malisafi-mls'); ?>"></textarea>
            <button type="submit" id="malisafi-chat-send-btn" disabled><?php esc_html_e('Send', 'malisafi-mls'); ?></button>
        </form>
    </main>
</div>