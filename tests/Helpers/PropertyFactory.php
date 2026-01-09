<?php
/**
 * Property Factory Helper for Tests
 *
 * @package MalisafiMLS\Tests
 */

namespace MalisafiMLS\Tests\Helpers;

/**
 * Property Factory class
 */
class PropertyFactory {
    
    /**
     * Create a test property with default or custom data
     *
     * @param array $args Property arguments
     * @return int Property ID
     */
    public static function create($args = []) {
        $defaults = [
            'post_type' => 'malisafi_property',
            'post_title' => 'Test Property ' . rand(1000, 9999),
            'post_content' => 'This is a test property description.',
            'post_status' => 'publish',
            'meta' => [
                '_malisafi_price' => 1500000,
                '_malisafi_bedrooms' => 3,
                '_malisafi_bathrooms' => 2,
                '_malisafi_property_type' => 'apartment',
                '_malisafi_size' => 120,
                '_malisafi_featured' => 0,
                '_malisafi_views' => 0,
                '_malisafi_inquiries' => 0
            ],
            'taxonomies' => [
                'malisafi_location' => ['Nairobi'],
                'malisafi_property_type' => ['apartment']
            ]
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Extract meta and taxonomies
        $meta = $args['meta'];
        $taxonomies = $args['taxonomies'];
        unset($args['meta'], $args['taxonomies']);
        
        // Create property
        $property_id = wp_insert_post($args);
        
        if (is_wp_error($property_id)) {
            return 0;
        }
        
        // Add metadata
        foreach ($meta as $key => $value) {
            update_post_meta($property_id, $key, $value);
        }
        
        // Add taxonomies
        foreach ($taxonomies as $taxonomy => $terms) {
            wp_set_object_terms($property_id, $terms, $taxonomy);
        }
        
        return $property_id;
    }
    
    /**
     * Create a featured property
     *
     * @param array $args Additional arguments
     * @return int Property ID
     */
    public static function create_featured($args = []) {
        $args['meta']['_malisafi_featured'] = 1;
        return self::create($args);
    }
    
    /**
     * Create multiple properties
     *
     * @param int $count Number of properties
     * @param array $args Property arguments
     * @return array Property IDs
     */
    public static function create_many($count = 5, $args = []) {
        $properties = [];
        
        for ($i = 0; $i < $count; $i++) {
            $properties[] = self::create($args);
        }
        
        return $properties;
    }
    
    /**
     * Create property with specific price range
     *
     * @param int $min_price Minimum price
     * @param int $max_price Maximum price
     * @param array $args Additional arguments
     * @return int Property ID
     */
    public static function create_with_price($min_price, $max_price, $args = []) {
        $price = rand($min_price, $max_price);
        $args['meta']['_malisafi_price'] = $price;
        return self::create($args);
    }
    
    /**
     * Create property in specific location
     *
     * @param string $location Location name
     * @param array $args Additional arguments
     * @return int Property ID
     */
    public static function create_in_location($location, $args = []) {
        $args['taxonomies']['malisafi_location'] = [$location];
        return self::create($args);
    }
}
