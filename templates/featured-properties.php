<?php
/**
 * Featured properties template
 *
 * @package MalisafiMLS
 */

if (!defined('WPINC')) {
    die;
}
?>

<div class="malisafi-featured-properties">
    <h2 class="section-title"><?php _e('Featured Properties', 'malisafi-mls'); ?></h2>
    
    <?php if ($properties->have_posts()) : ?>
        <div class="featured-container">
            <?php while ($properties->have_posts()) : $properties->the_post(); 
                $property_data = \MalisafiMLS\Property_Manager::get_property_data(get_the_ID());
            ?>
                <div class="featured-property">
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="featured-image">
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail('medium'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="featured-content">
                        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                        
                        <?php if (!empty($property_data['price'])) : ?>
                            <div class="price">
                                <?php echo \MalisafiMLS\Property_Manager::format_price($property_data['price']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="features">
                            <?php if (!empty($property_data['bedrooms'])) : ?>
                                <span><?php echo esc_html($property_data['bedrooms']); ?> <?php _e('Beds', 'malisafi-mls'); ?></span>
                            <?php endif; ?>
                            
                            <?php if (!empty($property_data['bathrooms'])) : ?>
                                <span><?php echo esc_html($property_data['bathrooms']); ?> <?php _e('Baths', 'malisafi-mls'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <?php wp_reset_postdata(); ?>
    <?php else : ?>
        <p><?php _e('No featured properties at this time.', 'malisafi-mls'); ?></p>
    <?php endif; ?>
</div>
