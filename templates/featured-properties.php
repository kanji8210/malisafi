<?php
/**
 * Featured properties template
 *
 * @package MalisafiMLS
 */

if (!defined('WPINC')) {
    die;
}

// Get shortcode attributes (passed from shortcode handler)
$columns = isset($shortcode_atts['columns']) ? intval($shortcode_atts['columns']) : 3;
$show_excerpt = isset($shortcode_atts['show_excerpt']) ? $shortcode_atts['show_excerpt'] === 'yes' : true;
$show_features = isset($shortcode_atts['show_features']) ? $shortcode_atts['show_features'] === 'yes' : true;

// Column classes
$column_class = 'featured-col-' . $columns;
?>

<div class="malisafi-featured-properties">
    <?php if ($properties->have_posts()) : ?>
        <div class="featured-container <?php echo esc_attr($column_class); ?>">
            <?php while ($properties->have_posts()) : $properties->the_post(); 
                $property_data = \MalisafiMLS\Property_Manager::get_property_data(get_the_ID());
            ?>
                <div class="featured-property">
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="featured-image">
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail('medium'); ?>
                            </a>
                            <?php 
                            // Always show status badge
                            if (!empty($property_data['status'])) {
                                $status_display = ucfirst(str_replace('-', ' ', $property_data['status']));
                                $status_class = 'status-' . sanitize_html_class(strtolower(str_replace(' ', '-', $property_data['status'])));
                            } else {
                                $status_display = 'Status Not Recorded';
                                $status_class = 'status-not-recorded';
                            }
                            ?>
                            <span class="status-badge <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_display); ?></span>
                            <?php if (!empty($property_data['setting'])) : ?>
                                <span class="setting-badge"><?php echo esc_html(ucfirst($property_data['setting'])); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="featured-content">
                        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                        
                        <?php if (!empty($property_data['location'])) : ?>
                            <div class="property-location">
                                <i class="dashicons dashicons-location" style="color: #dc2626 !important;"></i>
                                <?php echo esc_html($property_data['location']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($property_data['price'])) : ?>
                            <div class="price">
                                <?php echo \MalisafiMLS\Property_Manager::format_price($property_data['price']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($show_features) : ?>
                            <div class="features">
                                <?php if (!empty($property_data['bedrooms'])) : ?>
                                    <span>
                                        <span class="dashicons dashicons-admin-multisite"></span>
                                        <?php echo esc_html($property_data['bedrooms']); ?> <?php _e('Beds', 'malisafi-mls'); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($property_data['bathrooms'])) : ?>
                                    <span>
                                        <span class="dashicons dashicons-admin-site"></span>
                                        <?php echo esc_html($property_data['bathrooms']); ?> <?php _e('Baths', 'malisafi-mls'); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($property_data['area'])) : ?>
                                    <span>
                                        <span class="dashicons dashicons-editor-expand"></span>
                                        <?php echo esc_html($property_data['area']); ?> <?php _e('sqm', 'malisafi-mls'); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <?php wp_reset_postdata(); ?>
    <?php else : ?>
        <div class="no-featured-properties">
            <p><?php _e('No featured properties at this time.', 'malisafi-mls'); ?></p>
        </div>
    <?php endif; ?>
</div>
