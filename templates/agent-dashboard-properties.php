<?php
/**
 * Agent Dashboard - Properties Section
 */
if (!defined('ABSPATH')) exit;
?>
<div class="dashboard-properties">
    <h1><?php _e('My Properties', 'malisafi-mls'); ?></h1>
    <p><?php _e('Manage all your property listings here.', 'malisafi-mls'); ?></p>
    <?php if (isset($_GET['mf_deleted'])) : ?>
        <div class="malisafi-notice success">
            <p><?php _e('Property deleted.', 'malisafi-mls'); ?></p>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['mf_restored'])) : ?>
        <div class="malisafi-notice success">
            <p><?php _e('Property restored.', 'malisafi-mls'); ?></p>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['mf_deleted_permanently'])) : ?>
        <div class="malisafi-notice success">
            <p><?php _e('Property permanently deleted.', 'malisafi-mls'); ?></p>
        </div>
    <?php endif; ?>
    <div class="properties-actions">
        <a href="<?php echo esc_url(\MalisafiMLS\Page_Manager::get_page_url('agent_add_property')); ?>" class="button button-primary">
            <?php _e('Add New Property', 'malisafi-mls'); ?>
        </a>
    </div>

    <?php
    $query = new \WP_Query([
        'post_type' => 'malisafi_property',
        'author' => get_current_user_id(),
        'posts_per_page' => 20,
        'post_status' => array('publish', 'pending', 'draft')
    ]);

    $trash_query = new \WP_Query([
        'post_type' => 'malisafi_property',
        'author' => get_current_user_id(),
        'posts_per_page' => 20,
        'post_status' => array('trash')
    ]);

    $edit_base = \MalisafiMLS\Page_Manager::get_page_url('agent_add_property');
    
    // Fallback: If agent_add_property page doesn't exist, use the general submit property page
    if (!$edit_base || $edit_base === home_url('/')) {
        $edit_base = \MalisafiMLS\Page_Manager::get_page_url('submit_property');
    }
    
    // Final fallback: If no page exists, create a direct link to the shortcode handler
    if (!$edit_base || $edit_base === home_url('/')) {
        $edit_base = home_url('/?malisafi_action=submit_property');
    }
    ?>

    <?php if ($query->have_posts()) : ?>
        <div class="properties-list">
            <?php while ($query->have_posts()) : $query->the_post(); ?>
                <?php
                $edit_url = $edit_base ? add_query_arg('property_id', get_the_ID(), $edit_base) : '';
                $status = get_post_status();
                $redirect_url = home_url('/agent-dashboard/?section=properties');
                $delete_url = wp_nonce_url(
                    admin_url('admin-post.php?action=malisafi_delete_property&property_id=' . get_the_ID() . '&redirect=' . rawurlencode($redirect_url)),
                    'malisafi_delete_property_' . get_the_ID()
                );
                ?>
                <div class="property-item">
                    <div class="property-info">
                        <h3><?php the_title(); ?></h3>
                        <div class="property-meta">
                            <span class="status status-<?php echo esc_attr($status); ?>">
                                <?php echo esc_html(ucfirst($status)); ?>
                            </span>
                            <span class="date">
                                <?php echo esc_html(get_the_date()); ?>
                            </span>
                        </div>
                    </div>
                    <div class="property-actions">
                        <?php if ($edit_url) : ?>
                            <a class="button button-secondary" href="<?php echo esc_url($edit_url); ?>">
                                <?php _e('Edit', 'malisafi-mls'); ?>
                            </a>
                        <?php endif; ?>
                        <a class="button" href="<?php echo esc_url(get_permalink()); ?>">
                            <?php _e('View', 'malisafi-mls'); ?>
                        </a>
                        <a class="button button-link-delete" href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete this property?', 'malisafi-mls')); ?>');">
                            <?php _e('Delete', 'malisafi-mls'); ?>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else : ?>
        <p><?php _e('No properties found yet.', 'malisafi-mls'); ?></p>
    <?php endif; ?>

    <?php wp_reset_postdata(); ?>

    <div class="properties-trash">
        <h2><?php _e('Trash', 'malisafi-mls'); ?></h2>
        <?php if ($trash_query->have_posts()) : ?>
            <div class="properties-list">
                <?php while ($trash_query->have_posts()) : $trash_query->the_post(); ?>
                    <?php
                    $restore_url = wp_nonce_url(
                        admin_url('admin-post.php?action=malisafi_restore_property&property_id=' . get_the_ID() . '&redirect=' . rawurlencode($redirect_url)),
                        'malisafi_restore_property_' . get_the_ID()
                    );
                    $delete_perm_url = wp_nonce_url(
                        admin_url('admin-post.php?action=malisafi_delete_property_permanently&property_id=' . get_the_ID() . '&redirect=' . rawurlencode($redirect_url)),
                        'malisafi_delete_property_permanently_' . get_the_ID()
                    );
                    $delete_at = wp_next_scheduled('malisafi_delete_trashed_property', array(get_the_ID()));
                    $days_left = $delete_at ? max(0, ceil(($delete_at - time()) / DAY_IN_SECONDS)) : 0;
                    ?>
                    <div class="property-item">
                        <div class="property-info">
                            <h3><?php the_title(); ?></h3>
                            <div class="property-meta">
                                <span class="status status-trash"><?php _e('Trashed', 'malisafi-mls'); ?></span>
                                <span class="date"><?php echo esc_html(get_the_date()); ?></span>
                                <?php if ($delete_at) : ?>
                                    <span class="date">
                                        <?php echo esc_html(sprintf(__('Deletes in %d day(s)', 'malisafi-mls'), $days_left)); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="property-actions">
                            <a class="button button-secondary" href="<?php echo esc_url($restore_url); ?>">
                                <?php _e('Restore', 'malisafi-mls'); ?>
                            </a>
                            <a class="button button-link-delete" href="<?php echo esc_url($delete_perm_url); ?>" onclick="return confirm('<?php echo esc_js(__('Permanently delete this property? This cannot be undone.', 'malisafi-mls')); ?>');">
                                <?php _e('Delete Permanently', 'malisafi-mls'); ?>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else : ?>
            <p><?php _e('Trash is empty.', 'malisafi-mls'); ?></p>
        <?php endif; ?>
        <?php wp_reset_postdata(); ?>
    </div>
</div>
