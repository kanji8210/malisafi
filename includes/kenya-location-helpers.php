<?php
/**
 * Kenya Location Helpers
 * 
 * Helper functions for Kenya-specific location data
 *
 * @package MalisafiMLS
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get all Kenya counties
 *
 * @return array
 */
function malisafi_get_kenya_counties() {
    $counties = array(
        'Nairobi', 'Mombasa', 'Kisumu', 'Nakuru', 'Eldoret', 'Thika', 'Malindi', 'Kitale',
        'Garissa', 'Kakamega', 'Machakos', 'Meru', 'Nyeri', 'Kiambu', 'Kajiado', 'Kilifi',
        'Trans Nzoia', 'Uasin Gishu', 'Bungoma', 'Siaya', 'Kisii', 'Kericho', 'Migori',
        'Baringo', 'Bomet', 'Busia', 'Elgeyo-Marakwet', 'Embu', 'Homa Bay', 'Isiolo',
        'Kirinyaga', 'Kwale', 'Laikipia', 'Lamu', 'Makueni', 'Mandera', 'Marsabit',
        'Murang\'a', 'Nandi', 'Narok', 'Nyandarua', 'Nyamira', 'Samburu', 'Taita-Taveta',
        'Tana River', 'Tharaka-Nithi', 'Turkana', 'Vihiga', 'Wajir', 'West Pokot'
    );
    sort($counties);
    return $counties;
}

/**
 * Get area settings
 *
 * @return array
 */
function malisafi_get_area_settings() {
    return array(
        'urban' => __('Urban', 'malisafi-mls'),
        'semi-rural' => __('Semi-Rural', 'malisafi-mls'),
        'rural' => __('Rural', 'malisafi-mls'),
        'isolated' => __('Isolated', 'malisafi-mls')
    );
}

/**
 * Get area setting label
 *
 * @param string $setting Setting key
 * @return string
 */
function malisafi_get_setting_label($setting) {
    $settings = malisafi_get_area_settings();
    return isset($settings[$setting]) ? $settings[$setting] : $setting;
}

/**
 * Get property location display string
 *
 * @param int $property_id Property ID
 * @param string $format Format: 'full', 'short', 'county-only'
 * @return string
 */
function malisafi_get_property_location($property_id, $format = 'full') {
    $neighbourhood = get_post_meta($property_id, '_malisafi_neighbourhood', true);
    $city = get_post_meta($property_id, '_malisafi_city', true);
    $county = get_post_meta($property_id, '_malisafi_county', true);
    $setting = get_post_meta($property_id, '_malisafi_setting', true);
    
    $location_parts = array();
    
    switch ($format) {
        case 'county-only':
            return $county ? $county : __('Kenya', 'malisafi-mls');
            
        case 'short':
            if ($neighbourhood) {
                $location_parts[] = $neighbourhood;
            }
            if ($city) {
                $location_parts[] = $city;
            }
            break;
            
        case 'full':
        default:
            if ($neighbourhood) {
                $location_parts[] = $neighbourhood;
            }
            if ($city) {
                $location_parts[] = $city;
            }
            if ($county) {
                $location_parts[] = $county;
            }
            if ($setting) {
                $location_parts[] = '(' . malisafi_get_setting_label($setting) . ')';
            }
            break;
    }
    
    return !empty($location_parts) ? implode(', ', $location_parts) : __('Kenya', 'malisafi-mls');
}

/**
 * Get popular neighbourhoods by county
 *
 * @param string $county County name
 * @return array
 */
function malisafi_get_popular_neighbourhoods($county = '') {
    $neighbourhoods = array(
        'Nairobi' => array(
            'Karen', 'Westlands', 'Kilimani', 'Lavington', 'Parklands', 'Kileleshwa',
            'South B', 'South C', 'Embakasi', 'Kasarani', 'Kahawa West', 'Runda',
            'Muthaiga', 'Kitisuru', 'Spring Valley', 'Upperhill', 'Kilimani', 'Ngara',
            'Eastleigh', 'Donholm', 'Umoja', 'Buruburu', 'Athi River', 'Syokimau'
        ),
        'Mombasa' => array(
            'Nyali', 'Bamburi', 'Shanzu', 'Kizingo', 'Old Town', 'Likoni',
            'Changamwe', 'Kisauni', 'Mvita', 'Buxton'
        ),
        'Kisumu' => array(
            'Milimani', 'Mamboleo', 'Tom Mboya', 'Kondele', 'Nyalenda', 'Migosi'
        ),
        'Nakuru' => array(
            'Milimani', 'Section 58', 'Naka', 'Lanet', 'Free Area', 'Pipeline'
        ),
        'Kiambu' => array(
            'Ruaka', 'Ruiru', 'Kikuyu', 'Limuru', 'Kiambu Town', 'Thika Road', 'Juja'
        ),
    );
    
    if ($county && isset($neighbourhoods[$county])) {
        return $neighbourhoods[$county];
    }
    
    return $neighbourhoods;
}
