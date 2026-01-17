<?php
/**
 * Agent Dashboard - Leads Section
 */
if (!defined('ABSPATH')) exit;
?>
<div class="dashboard-leads">
    <h1><?php _e('My Leads', 'malisafi-mls'); ?></h1>
    <p><?php _e('View and manage your property inquiries and leads.', 'malisafi-mls'); ?></p>
    <a href="<?php echo admin_url('admin.php?page=malisafi-agent-leads'); ?>" class="button button-primary">
        <?php _e('View All Leads', 'malisafi-mls'); ?>
    </a>
</div>
