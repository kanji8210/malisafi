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
        <?php while ($properties->have_posts()) : $properties->the_post(); 
                $property_data = \MalisafiMLS\Property_Manager::get_property_data(get_the_ID());
            ?>
                <div class="property-card">
                    <?php if (has_post_thumbnail()) : ?>

                        <div class="property-image">
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail('malisafi_grid'); ?>
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
                    
                    <div class="property-content">
                        <h4 class="property-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h4>
                        
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
                                <i class="dashicons dashicons-location" style="color: #dc2626 !important;"></i>
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
                            
                            <?php 
                            $size = get_post_meta(get_the_ID(), '_malisafi_size', true);
                            $size_unit = get_post_meta(get_the_ID(), '_malisafi_size_unit', true) ?: 'sqm';
                            if (!empty($size)) : ?>
                                <span class="feature">
                                    <i class="dashicons dashicons-grid-view"></i>
                                    <?php 
                                    $unit_label = $size_unit === 'sqft' ? 'Sq Ft' : ($size_unit === 'sqm' ? 'Sq M' : strtoupper($size_unit));
                                    echo esc_html(number_format((float)$size) . ' ' . $unit_label);
                                    ?>
                                </span>
                            <?php endif; ?>
                        </div>
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
        
        <?php wp_reset_postdata(); ?>
    <?php else : ?>
        <p class="no-properties"><?php _e('No properties found.', 'malisafi-mls'); ?></p>
    <?php endif; ?>
</div>
