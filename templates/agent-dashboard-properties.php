<?php
/**
 * Agent Dashboard - Properties Section
 */
if (!defined('ABSPATH')) exit;
?>
<div class="dashboard-properties">
    <h1><?php _e('My Properties', 'malisafi-mls'); ?></h1>
    <p><?php _e('Manage all your property listings here.', 'malisafi-mls'); ?></p>
    <div class="properties-actions">
        <a href="<?php echo esc_url(\MalisafiMLS\Page_Manager::get_page_url('agent_add_property')); ?>" class="button button-primary">
            <?php _e('Add New Property', 'malisafi-mls'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=malisafi-properties'); ?>" class="button">
            <?php _e('Go to Properties Management', 'malisafi-mls'); ?>
        </a>
    </div>
</div>
