<?php
/**
 * Properties grid template
 *
 * @package MalisafiMLS
 **/

if (!defined('WPINC')) {
    die;
}
?>

<div class="malisafi-properties-grid">
    <?php if ($properties->have_posts()) : ?>
        <div class="properties-container">
            <?php while ($properties->have_posts()) : $properties->the_post(); 
                $property_data = \MalisafiMLS\Property_Manager::get_property_data(get_the_ID());
            ?>
                <div class="property-card">
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="property-image">
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail('large'); ?>
                            </a>
                            <?php if ($property_data['featured'] === '1') : ?>
                                <span class="featured-badge"><?php _e('Featured', 'malisafi-mls'); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($property_data['setting'])) : ?>
                                <span class="setting-badge setting-<?php echo esc_attr($property_data['setting']); ?>">
                                    <?php echo esc_html(malisafi_get_setting_label($property_data['setting'])); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="property-content">
                        <h3 class="property-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>
                        
                        <?php if (!empty($property_data['price'])) : ?>
                            <div class="property-price">
                                <?php echo \MalisafiMLS\Property_Manager::format_price($property_data['price']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="property-location">
                            <?php 
                            $location_parts = array();
                            if (!empty($property_data['neighbourhood'])) {
                                $location_parts[] = $property_data['neighbourhood'];
                            }
                            if (!empty($property_data['city'])) {
                                $location_parts[] = $property_data['city'];
                            }
                            if (!empty($property_data['county'])) {
                                $location_parts[] = $property_data['county'];
                            }
                            if (!empty($location_parts)) : ?>
                                <i class="dashicons dashicons-location"></i>
                                <?php echo esc_html(implode(', ', $location_parts)); ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="property-features">
                            <?php if (!empty($property_data['bedrooms'])) : ?>
                                <span class="feature">
                                    <i class="dashicons dashicons-admin-home"></i>
                                    <?php echo esc_html($property_data['bedrooms']); ?> <?php _e('Beds', 'malisafi-mls'); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($property_data['bathrooms'])) : ?>
                                <span class="feature">
                                    <i class="dashicons dashicons-admin-home"></i>
                                    <?php echo esc_html($property_data['bathrooms']); ?> <?php _e('Baths', 'malisafi-mls'); ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if (!empty($property_data['area'])) : ?>
                                <span class="feature">
                                    <i class="dashicons dashicons-grid-view"></i>
                                    <?php echo esc_html(number_format($property_data['area'])); ?> <?php echo get_option('malisafi_mls_area_unit', 'sqft'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="property-excerpt">
                            <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
                        </div>
                        
                        <a href="<?php the_permalink(); ?>" class="view-details-btn">
                            <?php _e('View Details', 'malisafi-mls'); ?>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <div class="malisafi-pagination">
            <?php
            echo paginate_links(array(
                'total' => $properties->max_num_pages,
                'prev_text' => __('&laquo; Previous', 'malisafi-mls'),
                'next_text' => __('Next &raquo;', 'malisafi-mls'),
            ));
            ?>
        </div>
        
        <?php wp_reset_postdata(); ?>
    <?php else : ?>
        <p class="no-properties"><?php _e('No properties found.', 'malisafi-mls'); ?></p>
    <?php endif; ?>
</div>
