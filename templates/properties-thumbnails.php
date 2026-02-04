<?php
/**
 * Thumbnails-only properties grid template
 *
 * @package MalisafiMLS
 */

if (!defined('WPINC')) {
    die;
}

$columns = isset($shortcode_atts['columns']) ? intval($shortcode_atts['columns']) : 3;
$columns = max(1, min(6, $columns));
?>

<div class="malisafi-thumbnails-grid" style="--mls-thumb-columns: <?php echo esc_attr($columns); ?>;">
    <?php if ($properties->have_posts()) : ?>
        <?php while ($properties->have_posts()) : $properties->the_post(); ?>
            <?php include MALISAFI_MLS_PATH . 'templates/property-card-modern.php'; ?>
        <?php endwhile; ?>
        <?php wp_reset_postdata(); ?>
    <?php else : ?>
        <p class="no-properties"><?php _e('No properties found.', 'malisafi-mls'); ?></p>
    <?php endif; ?>
</div>
