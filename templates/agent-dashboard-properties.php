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
    </div>

    <?php
    $query = new \WP_Query([
        'post_type' => 'malisafi_property',
        'author' => get_current_user_id(),
        'posts_per_page' => 20,
        'post_status' => array('publish', 'pending', 'draft')
    ]);

    $edit_base = \MalisafiMLS\Page_Manager::get_page_url('agent_add_property');
    ?>

    <?php if ($query->have_posts()) : ?>
        <div class="properties-list">
            <?php while ($query->have_posts()) : $query->the_post(); ?>
                <?php
                $edit_url = $edit_base ? add_query_arg('property_id', get_the_ID(), $edit_base) : '';
                $status = get_post_status();
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
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else : ?>
        <p><?php _e('No properties found yet.', 'malisafi-mls'); ?></p>
    <?php endif; ?>

    <?php wp_reset_postdata(); ?>
</div>
