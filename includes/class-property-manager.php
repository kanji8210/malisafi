<?php
/**
 * Property Manager - handles property queries and operations
 *
 * @package MalisafiMLS
 */

namespace MalisafiMLS;

/**
 * Property_Manager class
 */
class Property_Manager {
    
    /**
     * Get properties with filters
     *
     * @param array $args Query arguments
     * @return \WP_Query
     */
    public static function get_properties($args = array()) {
        $defaults = array(
            'post_type' => 'malisafi_property',
            'post_status' => 'publish',
            'posts_per_page' => get_option('malisafi_mls_properties_per_page', 12),
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Handle meta query for property details
        if (!empty($args['min_price']) || !empty($args['max_price']) || 
            !empty($args['min_bedrooms']) || !empty($args['min_bathrooms']) ||
            !empty($args['min_area']) || !empty($args['max_area'])) {
            
            $meta_query = array('relation' => 'AND');
            
            if (!empty($args['min_price'])) {
                $meta_query[] = array(
                    'key' => '_malisafi_price',
                    'value' => $args['min_price'],
                    'type' => 'NUMERIC',
                    'compare' => '>=',
                );
            }
            
            if (!empty($args['max_price'])) {
                $meta_query[] = array(
                    'key' => '_malisafi_price',
                    'value' => $args['max_price'],
                    'type' => 'NUMERIC',
                    'compare' => '<=',
                );
            }
            
            if (!empty($args['min_bedrooms'])) {
                $meta_query[] = array(
                    'key' => '_malisafi_bedrooms',
                    'value' => $args['min_bedrooms'],
                    'type' => 'NUMERIC',
                    'compare' => '>=',
                );
            }
            
            if (!empty($args['min_bathrooms'])) {
                $meta_query[] = array(
                    'key' => '_malisafi_bathrooms',
                    'value' => $args['min_bathrooms'],
                    'type' => 'NUMERIC',
                    'compare' => '>=',
                );
            }
            
            if (!empty($args['min_area'])) {
                $meta_query[] = array(
                    'key' => '_malisafi_area',
                    'value' => $args['min_area'],
                    'type' => 'NUMERIC',
                    'compare' => '>=',
                );
            }
            
            if (!empty($args['max_area'])) {
                $meta_query[] = array(
                    'key' => '_malisafi_area',
                    'value' => $args['max_area'],
                    'type' => 'NUMERIC',
                    'compare' => '<=',
                );
            }
            
            if (!empty($args['county'])) {
                $meta_query[] = array(
                    'key' => '_malisafi_county',
                    'value' => $args['county'],
                    'compare' => '=',
                );
            }
            
            if (!empty($args['setting'])) {
                $meta_query[] = array(
                    'key' => '_malisafi_setting',
                    'value' => $args['setting'],
                    'compare' => '=',
                );
            }
            
            $args['meta_query'] = $meta_query;
        } elseif (!empty($args['county']) || !empty($args['setting'])) {
            // Handle county/setting search even without price/area filters
            $meta_query = array('relation' => 'AND');
            
            if (!empty($args['county'])) {
                $meta_query[] = array(
                    'key' => '_malisafi_county',
                    'value' => $args['county'],
                    'compare' => '=',
                );
            }
            
            if (!empty($args['setting'])) {
                $meta_query[] = array(
                    'key' => '_malisafi_setting',
                    'value' => $args['setting'],
                    'compare' => '=',
                );
            }
            
            $args['meta_query'] = $meta_query;
        }
        
        // Handle taxonomy queries
        if (!empty($args['property_type']) || !empty($args['property_status']) || !empty($args['location'])) {
            $tax_query = array('relation' => 'AND');
            
            if (!empty($args['property_type'])) {
                $tax_query[] = array(
                    'taxonomy' => 'malisafi_property_type',
                    'field' => 'slug',
                    'terms' => $args['property_type'],
                );
            }
            
            if (!empty($args['property_status'])) {
                $tax_query[] = array(
                    'taxonomy' => 'malisafi_property_status',
                    'field' => 'slug',
                    'terms' => $args['property_status'],
                );
            }
            
            if (!empty($args['location'])) {
                $tax_query[] = array(
                    'taxonomy' => 'malisafi_property_location',
                    'field' => 'slug',
                    'terms' => $args['location'],
                );
            }
            
            $args['tax_query'] = $tax_query;
        }
        
        // Featured properties
        if (!empty($args['featured'])) {
            if (!isset($args['meta_query'])) {
                $args['meta_query'] = array();
            }
            $args['meta_query'][] = array(
                'key' => '_malisafi_featured',
                'value' => '1',
                'compare' => '=',
            );
        }
        
        return new \WP_Query($args);
    }
    
    /**
     * Get featured properties
     *
     * @param int $count Number of properties
     * @param array $extra_args Additional query arguments
     * @return \WP_Query
     */
    public static function get_featured_properties($count = 6, $extra_args = array()) {
        $args = array(
            'featured' => true,
            'posts_per_page' => $count,
        );
        
        // Merge with extra args if provided
        if (!empty($extra_args)) {
            $args = array_merge($args, $extra_args);
        }
        
        return self::get_properties($args);
    }
    
    /**
     * Get property meta
     *
     * @param int $property_id Property post ID
     * @return array
     */
    public static function get_property_data($property_id) {
        // Get numeric values and ensure they're properly typed
        $price = get_post_meta($property_id, '_malisafi_price', true);
        $area = get_post_meta($property_id, '_malisafi_area', true);
        $lot_size = get_post_meta($property_id, '_malisafi_lot_size', true);
        $year_built = get_post_meta($property_id, '_malisafi_year_built', true);
        
        return array(
            'id' => $property_id,
            'title' => get_the_title($property_id),
            'description' => get_the_content(null, false, $property_id),
            'price' => !empty($price) ? floatval($price) : 0,
            'bedrooms' => get_post_meta($property_id, '_malisafi_bedrooms', true),
            'bathrooms' => get_post_meta($property_id, '_malisafi_bathrooms', true),
            'area' => !empty($area) ? floatval($area) : 0,
            'lot_size' => !empty($lot_size) ? floatval($lot_size) : 0,
            'year_built' => !empty($year_built) ? intval($year_built) : 0,
            'garage' => get_post_meta($property_id, '_malisafi_garage', true),
            'property_id' => get_post_meta($property_id, '_malisafi_property_id', true),
            'address' => get_post_meta($property_id, '_malisafi_address', true),
            'city' => get_post_meta($property_id, '_malisafi_city', true),
            'county' => get_post_meta($property_id, '_malisafi_county', true),
            'neighbourhood' => get_post_meta($property_id, '_malisafi_neighbourhood', true),
            'setting' => get_post_meta($property_id, '_malisafi_setting', true),
            'state' => get_post_meta($property_id, '_malisafi_state', true),
            'zip' => get_post_meta($property_id, '_malisafi_zip', true),
            'country' => get_post_meta($property_id, '_malisafi_country', true),
            'latitude' => get_post_meta($property_id, '_malisafi_latitude', true),
            'longitude' => get_post_meta($property_id, '_malisafi_longitude', true),
            'agent_name' => get_post_meta($property_id, '_malisafi_agent_name', true),
            'agent_email' => get_post_meta($property_id, '_malisafi_agent_email', true),
            'agent_phone' => get_post_meta($property_id, '_malisafi_agent_phone', true),
            'featured' => get_post_meta($property_id, '_malisafi_featured', true),
            'thumbnail' => get_the_post_thumbnail_url($property_id, 'large'),
            'gallery' => self::get_property_gallery($property_id),
        );
    }
    
    /**
     * Get property gallery images
     *
     * @param int $property_id Property post ID
     * @return array
     */
    public static function get_property_gallery($property_id) {
        $gallery = get_post_meta($property_id, '_malisafi_gallery', true);
        
        if (empty($gallery)) {
            return array();
        }
        
        $images = array();
        $gallery_ids = explode(',', $gallery);
        
        foreach ($gallery_ids as $image_id) {
            $images[] = array(
                'id' => $image_id,
                'url' => wp_get_attachment_url($image_id),
                'thumbnail' => wp_get_attachment_image_url($image_id, 'thumbnail'),
                'medium' => wp_get_attachment_image_url($image_id, 'medium'),
                'large' => wp_get_attachment_image_url($image_id, 'large'),
            );
        }
        
        return $images;
    }
    
    /**
     * Format price
     *
     * @param float $price Price value
     * @return string
     */
    public static function format_price($price) {
        // Ensure price is a valid number
        $price = !empty($price) ? floatval($price) : 0;
        
        $currency = get_option('malisafi_mls_currency', 'USD');
        $symbol = get_option('malisafi_mls_currency_symbol', '$');
        $position = get_option('malisafi_mls_currency_position', 'before');
        $thousands = get_option('malisafi_mls_thousand_separator', ',');
        $decimals = get_option('malisafi_mls_decimal_separator', '.');
        $decimal_places = get_option('malisafi_mls_price_decimals', 0);
        
        $formatted_price = number_format($price, $decimal_places, $decimals, $thousands);
        
        if ($position === 'before') {
            return $symbol . $formatted_price;
        } else {
            return $formatted_price . $symbol;
        }
    }
    
    /**
     * Track property view
     *
     * @param int $property_id Property post ID
     */
    public static function track_view($property_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'malisafi_property_views';
        $user_id = get_current_user_id();
        $ip_address = $_SERVER['REMOTE_ADDR'];
        
        $wpdb->insert(
            $table_name,
            array(
                'property_id' => $property_id,
                'user_id' => $user_id > 0 ? $user_id : null,
                'ip_address' => $ip_address,
                'viewed_at' => current_time('mysql'),
            ),
            array('%d', '%d', '%s', '%s')
        );
    }
    
    /**
     * Get property view count
     *
     * @param int $property_id Property post ID
     * @return int
     */
    public static function get_view_count($property_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'malisafi_property_views';
        
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE property_id = %d",
                $property_id
            )
        );
    }
}
