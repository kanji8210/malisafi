<?php
/**
 * Chat Management Template
 *
 * @package MalisafiMLS
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap malisafi-chat-management">
    <h1><?php esc_html_e('Chat Management', 'malisafi-mls'); ?></h1>

    <?php if (isset($_GET['chat_updated'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Chat updated successfully.', 'malisafi-mls'); ?></p>
        </div>
    <?php endif; ?>

    <div class="malisafi-chat-admin-grid">
        <div class="malisafi-chat-admin-list">
            <h2><?php esc_html_e('Conversations', 'malisafi-mls'); ?></h2>
            <?php if (empty($threads)) : ?>
                <p><?php esc_html_e('No conversations found.', 'malisafi-mls'); ?></p>
            <?php else : ?>
                <?php foreach ($threads as $thread) : ?>
                    <?php $is_active = ((int) $thread['id'] === (int) $thread_id); ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=malisafi-chat-management&thread_id=' . (int) $thread['id'])); ?>" class="malisafi-chat-thread-item <?php echo $is_active ? 'is-active' : ''; ?>">
                        <strong><?php echo esc_html($thread['title']); ?></strong>
                        <div class="thread-meta">
                            <?php if (!empty($thread['assignedToName'])) : ?>
                                <span><?php printf(esc_html__('Assigned: %s', 'malisafi-mls'), esc_html($thread['assignedToName'])); ?></span>
                            <?php else : ?>
                                <span><?php esc_html_e('Unassigned', 'malisafi-mls'); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($thread['lastMessageAt'])) : ?>
                                <span><?php echo esc_html($thread['lastMessageAt']); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($thread['lastMessage'])) : ?>
                            <div class="thread-last"><?php echo esc_html($thread['lastMessage']); ?></div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="malisafi-chat-admin-main">
            <?php if ($thread_id > 0) : ?>
                <h2><?php echo !empty($selected_thread['title']) ? esc_html($selected_thread['title']) : esc_html__('Conversation', 'malisafi-mls'); ?></h2>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="chat-assign-form">
                    <input type="hidden" name="action" value="malisafi_chat_assign" />
                    <input type="hidden" name="thread_id" value="<?php echo (int) $thread_id; ?>" />
                    <?php wp_nonce_field('malisafi_chat_assign', 'malisafi_chat_assign_nonce'); ?>

                    <label for="assigned_to"><strong><?php esc_html_e('Assign conversation', 'malisafi-mls'); ?></strong></label>
                    <select id="assigned_to" name="assigned_to">
                        <option value="0"><?php esc_html_e('Unassigned', 'malisafi-mls'); ?></option>
                        <?php foreach ($assignees as $assignee) : ?>
                            <option value="<?php echo (int) $assignee['id']; ?>" <?php selected((int) $selected_thread['assignedTo'], (int) $assignee['id']); ?>>
                                <?php echo esc_html($assignee['name'] . ' (' . $assignee['role'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="button button-secondary"><?php esc_html_e('Assign', 'malisafi-mls'); ?></button>
                </form>

                <div class="chat-message-list">
                    <?php if (empty($messages)) : ?>
                        <p><?php esc_html_e('No messages in this conversation yet.', 'malisafi-mls'); ?></p>
                    <?php else : ?>
                        <?php foreach ($messages as $message) : ?>
                            <div class="chat-message-item">
                                <div class="chat-message-meta">
                                    <strong><?php echo esc_html($message['senderName']); ?></strong>
                                    <span><?php echo esc_html($message['createdAtHuman']); ?></span>
                                </div>
                                <div class="chat-message-text"><?php echo esc_html($message['message']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="chat-reply-form">
                    <input type="hidden" name="action" value="malisafi_chat_admin_reply" />
                    <input type="hidden" name="thread_id" value="<?php echo (int) $thread_id; ?>" />
                    <?php wp_nonce_field('malisafi_chat_admin_reply', 'malisafi_chat_admin_reply_nonce'); ?>

                    <label for="chat-reply-message"><strong><?php esc_html_e('Reply', 'malisafi-mls'); ?></strong></label>
                    <textarea id="chat-reply-message" name="message" rows="4" maxlength="2000" required></textarea>
                    <button type="submit" class="button button-primary"><?php esc_html_e('Send Reply', 'malisafi-mls'); ?></button>
                </form>
            <?php else : ?>
                <p><?php esc_html_e('Select a conversation to manage.', 'malisafi-mls'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.malisafi-chat-admin-grid {
    display: grid;
    grid-template-columns: 340px 1fr;
    gap: 20px;
    margin-top: 20px;
}

.malisafi-chat-admin-list,
.malisafi-chat-admin-main {
    background: #fff;
    border: 1px solid #dcdcde;
    border-radius: 8px;
    padding: 16px;
}

.malisafi-chat-thread-item {
    display: block;
    border: 1px solid #dcdcde;
    border-radius: 6px;
    padding: 10px;
    margin-bottom: 10px;
    color: #1d2327;
    text-decoration: none;
}

.malisafi-chat-thread-item.is-active {
    border-color: #2271b1;
    background: #f0f6fc;
}

.thread-meta {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    color: #646970;
    font-size: 12px;
    margin: 6px 0;
}

.thread-last {
    color: #3c434a;
    font-size: 12px;
}

.chat-assign-form,
.chat-reply-form {
    margin-bottom: 16px;
}

.chat-assign-form select,
.chat-reply-form textarea {
    width: 100%;
    margin: 8px 0;
}

.chat-message-list {
    border: 1px solid #dcdcde;
    border-radius: 6px;
    background: #fff;
    max-height: 430px;
    overflow: auto;
    margin-bottom: 16px;
    padding: 10px;
}

.chat-message-item {
    border-bottom: 1px solid #f0f0f1;
    padding: 8px 0;
}

.chat-message-item:last-child {
    border-bottom: none;
}

.chat-message-meta {
    display: flex;
    justify-content: space-between;
    color: #646970;
    font-size: 12px;
    margin-bottom: 4px;
}

@media (max-width: 960px) {
    .malisafi-chat-admin-grid {
        grid-template-columns: 1fr;
    }
}
</style>
