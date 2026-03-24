<?php
namespace MalisafiMLS;

/**
 * Property Access Control
 * Ensures users can only view/edit their own properties
 *
 * @package MalisafiMLS
 */
class Property_Access_Control {
    
    /**
     * Initialize
     */
    public static function init() {
        // Filter property queries to show only user's own properties
        add_action('pre_get_posts', array(__CLASS__, 'filter_property_query'));
        
        // Check property edit permissions
        add_filter('user_has_cap', array(__CLASS__, 'check_property_edit_cap'), 10, 4);
        
        // Modify property list columns
        add_filter('manage_malisafi_property_posts_columns', array(__CLASS__, 'property_columns'));
        add_action('manage_malisafi_property_posts_custom_column', array(__CLASS__, 'property_column_content'), 10, 2);
        
        // Add filters to admin property list
        add_action('restrict_manage_posts', array(__CLASS__, 'add_property_filters'));
        add_filter('parse_query', array(__CLASS__, 'filter_by_status'));
    }
    
    /**
     * Filter property queries for non-admins
     */
    public static function filter_property_query($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        global $pagenow, $typenow;
        
        if ($typenow !== 'malisafi_property' || $pagenow !== 'edit.php') {
            return;
        }
        
        // Admins and moderators see all properties
        if (current_user_can('administrator') || current_user_can('malisafi_moderate_properties')) {
            return;
        }
        
        // Others see only their own properties
        $query->set('author', get_current_user_id());
    }
    
    /**
     * Check if user can edit specific property
     */
    public static function check_property_edit_cap($allcaps, $caps, $args, $user) {
        if (!isset($args[0]) || !in_array($args[0], array('edit_post', 'delete_post'))) {
            return $allcaps;
        }
        
        if (!isset($args[2])) {
            return $allcaps;
        }
        
        $property_id = $args[2];
        $property = get_post($property_id);
        
        if (!$property || $property->post_type !== 'malisafi_property') {
            return $allcaps;
        }
        
        // Admins can edit anything
        if (isset($allcaps['administrator']) && $allcaps['administrator']) {
            return $allcaps;
        }
        
        // Moderators can edit for moderation
        if (isset($allcaps['malisafi_moderate_properties']) && $allcaps['malisafi_moderate_properties']) {
            $allcaps['edit_post'] = true;
            return $allcaps;
        }
        
        // Users can only edit their own properties
        if ($property->post_author == $user->ID) {
            $allcaps['edit_post'] = true;
            $allcaps['delete_post'] = true;
        } else {
            $allcaps['edit_post'] = false;
            $allcaps['delete_post'] = false;
        }
        
        return $allcaps;
    }
    
    /**
     * Add custom columns to property list
     */
    public static function property_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['property_image'] = __('Image', 'malisafi-mls');
                $new_columns['property_price'] = __('Price', 'malisafi-mls');
                $new_columns['property_type'] = __('Type', 'malisafi-mls');
                $new_columns['property_location'] = __('Location', 'malisafi-mls');
                $new_columns['property_views'] = __('Views', 'malisafi-mls');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Display custom column content
     */
    public static function property_column_content($column, $post_id) {
        switch ($column) {
            case 'property_image':
                if (has_post_thumbnail($post_id)) {
                    echo get_the_post_thumbnail($post_id, array(60, 60));
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
                break;
                
            case 'property_price':
                $price = get_post_meta($post_id, '_malisafi_price', true);
                $currency = get_post_meta($post_id, '_malisafi_currency', true) ?: 'KES';
                if ($price) {
                    echo '<strong>' . esc_html($currency . ' ' . number_format($price)) . '</strong>';
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
                break;
                
            case 'property_type':
                $terms = get_the_terms($post_id, 'malisafi_property_type');
                if ($terms && !is_wp_error($terms)) {
                    $types = array_map(function($term) {
                        return $term->name;
                    }, $terms);
                    echo esc_html(implode(', ', $types));
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
                break;
                
            case 'property_location':
                $county = get_post_meta($post_id, '_malisafi_county', true);
                $city = get_post_meta($post_id, '_malisafi_city', true);
                if ($county || $city) {
                    echo esc_html(($city ? $city . ', ' : '') . $county);
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
                break;

            case 'property_views':
                global $wpdb;
                $views = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT views_count FROM {$wpdb->prefix}mf_properties WHERE post_id = %d",
                    $post_id
                ));
                echo '<span style="display:flex;align-items:center;gap:4px;">';
                echo '<span class="dashicons dashicons-visibility" style="font-size:14px;color:#1e5277;"></span>';
                echo '<strong>' . number_format($views) . '</strong>';
                echo '</span>';
                break;
        }
    }
    
    /**
     * Add filter dropdowns to property list
     */
    public static function add_property_filters() {
        global $typenow;
        
        if ($typenow !== 'malisafi_property') {
            return;
        }
        
        // Property type filter
        $terms = get_terms(array(
            'taxonomy' => 'malisafi_property_type',
            'hide_empty' => false
        ));
        
        if (!empty($terms)) {
            $selected = isset($_GET['property_type_filter']) ? sanitize_key(wp_unslash($_GET['property_type_filter'])) : '';
            echo '<select name="property_type_filter">';
            echo '<option value="">' . __('All Types', 'malisafi-mls') . '</option>';
            foreach ($terms as $term) {
                printf(
                    '<option value="%s"%s>%s</option>',
                    esc_attr($term->slug),
                    selected($selected, $term->slug, false),
                    esc_html($term->name)
                );
            }
            echo '</select>';
        }
        
        // County filter
        global $wpdb;
        $counties = $wpdb->get_col("
            SELECT DISTINCT meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_malisafi_county' 
            AND meta_value != '' 
            ORDER BY meta_value
        ");
        
        if (!empty($counties)) {
            $selected = isset($_GET['county_filter']) ? sanitize_text_field(wp_unslash($_GET['county_filter'])) : '';
            echo '<select name="county_filter">';
            echo '<option value="">' . __('All Counties', 'malisafi-mls') . '</option>';
            foreach ($counties as $county) {
                printf(
                    '<option value="%s"%s>%s</option>',
                    esc_attr($county),
                    selected($selected, $county, false),
                    esc_html($county)
                );
            }
            echo '</select>';
        }
    }
    
    /**
     * Filter properties by custom filters
     */
    public static function filter_by_status($query) {
        global $pagenow, $typenow;
        
        if ($pagenow !== 'edit.php' || $typenow !== 'malisafi_property') {
            return $query;
        }
        
        // Filter by property type
        $property_type_filter = isset($_GET['property_type_filter']) ? sanitize_key(wp_unslash($_GET['property_type_filter'])) : '';
        if ($property_type_filter !== '') {
            $query->query_vars['tax_query'] = array(
                array(
                    'taxonomy' => 'malisafi_property_type',
                    'field' => 'slug',
                    'terms' => $property_type_filter
                )
            );
        }
        
        // Filter by county
        $county_filter = isset($_GET['county_filter']) ? sanitize_text_field(wp_unslash($_GET['county_filter'])) : '';
        if ($county_filter !== '') {
            $query->query_vars['meta_query'] = array(
                array(
                    'key' => '_malisafi_county',
                    'value' => $county_filter,
                    'compare' => '='
                )
            );
        }
        
        return $query;
    }
}
