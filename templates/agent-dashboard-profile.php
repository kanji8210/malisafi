<?php
/**
 * Agent Dashboard - Profile Section
 */
if (!defined('ABSPATH')) exit;
?>
<div class="dashboard-profile">
    <h1><?php _e('My Profile', 'malisafi-mls'); ?></h1>
    <p><?php _e('Update your agent profile information.', 'malisafi-mls'); ?></p>
    <a href="<?php echo admin_url('admin.php?page=malisafi-agent-profile'); ?>" class="button button-primary">
        <?php _e('Edit Profile', 'malisafi-mls'); ?>
    </a>
</div>
