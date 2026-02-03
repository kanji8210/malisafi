<?php
if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) :
    the_post();
    $project_id = get_the_ID();
    $project_type = get_post_meta($project_id, '_malisafi_project_type', true);
    $project_category = get_post_meta($project_id, '_malisafi_project_category', true);
    $project_subcategory = get_post_meta($project_id, '_malisafi_project_subcategory', true);
    $timeline = get_post_meta($project_id, '_malisafi_project_timeline', true);
    $milestones = get_post_meta($project_id, '_malisafi_project_milestones', true);
    if (!is_array($milestones)) {
        $milestones = $milestones ? (array) $milestones : array();
    }
    $investor_highlights = get_post_meta($project_id, '_malisafi_project_investor_highlights', true);
    $client_highlights = get_post_meta($project_id, '_malisafi_project_client_highlights', true);
    $brochure_id = (int) get_post_meta($project_id, '_malisafi_project_brochure_id', true);
    $brochure_url = $brochure_id ? wp_get_attachment_url($brochure_id) : '';
    $linked_properties = get_post_meta($project_id, '_malisafi_project_linked_properties', true);
    $snapshot = get_post_meta($project_id, '_malisafi_project_properties_snapshot', true);

    if (!is_array($linked_properties)) {
        $linked_properties = $linked_properties ? (array) $linked_properties : array();
    }

    if (!is_array($snapshot)) {
        $snapshot = array();
    }
    ?>
    <div class="malisafi-project-single">
        <div class="project-header">
            <h1><?php the_title(); ?></h1>
            <p><?php echo esc_html($project_type); ?><?php echo $project_category ? ' · ' . esc_html($project_category) : ''; ?><?php echo $project_subcategory ? ' · ' . esc_html($project_subcategory) : ''; ?></p>
        </div>

        <div class="project-content">
            <?php the_content(); ?>
        </div>

        <div class="project-highlights">
            <div>
                <h3><?php _e('Timeline', 'malisafi-mls'); ?></h3>
                <p><?php echo nl2br(esc_html($timeline)); ?></p>
            </div>
            <?php if (!empty($milestones)) : ?>
                <div>
                    <h3><?php _e('Milestones', 'malisafi-mls'); ?></h3>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php _e('Date', 'malisafi-mls'); ?></th>
                                <th><?php _e('Title', 'malisafi-mls'); ?></th>
                                <th><?php _e('Status', 'malisafi-mls'); ?></th>
                                <th><?php _e('Progress', 'malisafi-mls'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($milestones as $milestone) : ?>
                                <?php if (is_array($milestone)) : ?>
                                    <tr>
                                        <td><?php echo esc_html(isset($milestone['date']) ? $milestone['date'] : ''); ?></td>
                                        <td><?php echo esc_html(isset($milestone['title']) ? $milestone['title'] : ''); ?></td>
                                        <td><?php echo esc_html(isset($milestone['status']) ? $milestone['status'] : ''); ?></td>
                                        <td><?php echo esc_html(isset($milestone['percent']) ? $milestone['percent'] : ''); ?></td>
                                    </tr>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="4"><?php echo esc_html($milestone); ?></td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            <div>
                <h3><?php _e('Investor Highlights', 'malisafi-mls'); ?></h3>
                <p><?php echo nl2br(esc_html($investor_highlights)); ?></p>
            </div>
            <div>
                <h3><?php _e('Client Highlights', 'malisafi-mls'); ?></h3>
                <p><?php echo nl2br(esc_html($client_highlights)); ?></p>
            </div>
        </div>

        <?php if ($brochure_url) : ?>
            <div class="project-brochure">
                <a class="button button-primary" href="<?php echo esc_url($brochure_url); ?>" target="_blank" rel="noopener noreferrer">
                    <?php _e('Download Brochure', 'malisafi-mls'); ?>
                </a>
            </div>
        <?php endif; ?>

        <?php if (!empty($linked_properties)) : ?>
            <div class="project-linked-properties">
                <h2><?php _e('Linked Properties', 'malisafi-mls'); ?></h2>
                <div class="malisafi-properties-grid">
                    <?php foreach ($linked_properties as $property_id) : ?>
                        <?php $property_id = (int) $property_id; ?>
                        <div class="property-card">
                            <a href="<?php echo esc_url(get_permalink($property_id)); ?>">
                                <?php if (has_post_thumbnail($property_id)) : ?>
                                    <?php echo get_the_post_thumbnail($property_id, 'malisafi_grid'); ?>
                                <?php endif; ?>
                                <h3><?php echo esc_html(get_the_title($property_id)); ?></h3>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endwhile; ?>

<?php
get_footer();<?php
if (!defined('ABSPATH')) {
    exit;
}

return;
