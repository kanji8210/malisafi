<?php
/**
 * Search form template
 *
 * @package MalisafiMLS
 */

if (!defined('WPINC')) {
    die;
}
?>

<?php
$results_url = '';
if (class_exists('MalisafiMLS\\Page_Manager') && method_exists('MalisafiMLS\\Page_Manager', 'get_page_url')) {
    $results_url = \MalisafiMLS\Page_Manager::get_page_url('advanced_filters');
}
if (!$results_url) {
    $results_url = home_url('/advanced_filters');
}
?>

<div class="malisafi-search-form <?php echo esc_attr($atts['style']); ?>">
    <form method="get" action="<?php echo esc_url($results_url); ?>" class="property-search">
        
        <div class="search-field">
            <label for="search-keyword"><?php _e('Keyword', 'malisafi-mls'); ?></label>
            <input type="text" id="search-keyword" name="search" placeholder="<?php _e('Search properties...', 'malisafi-mls'); ?>" value="<?php echo isset($_GET['search']) ? esc_attr(wp_unslash($_GET['search'])) : ''; ?>">
        </div>
        
        <div class="search-field">
            <label for="search-type"><?php _e('Property Type', 'malisafi-mls'); ?></label>
            <select id="search-type" name="property_type">
                <option value=""><?php _e('All Types', 'malisafi-mls'); ?></option>
                <?php
                $selected_type = isset($_GET['property_type']) ? sanitize_text_field(wp_unslash($_GET['property_type'])) : '';
                $types = get_terms(array(
                    'taxonomy' => 'malisafi_property_type',
                    'hide_empty' => true,
                ));
                foreach ($types as $type) {
                    echo '<option value="' . esc_attr($type->slug) . '"' . selected($selected_type, $type->slug, false) . '>' . esc_html($type->name) . '</option>';
                }
                ?>
            </select>
        </div>
        
        <div class="search-field">
            <label for="search-status"><?php _e('Status', 'malisafi-mls'); ?></label>
            <select id="search-status" name="status">
                <option value=""><?php _e('All Status', 'malisafi-mls'); ?></option>
                <?php
                $selected_status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
                $statuses = get_terms(array(
                    'taxonomy' => 'malisafi_property_status',
                    'hide_empty' => true,
                ));
                foreach ($statuses as $status) {
                    echo '<option value="' . esc_attr($status->slug) . '"' . selected($selected_status, $status->slug, false) . '>' . esc_html($status->name) . '</option>';
                }
                ?>
            </select>
        </div>
        
        <div class="search-field">
            <label for="search-location"><?php _e('Location', 'malisafi-mls'); ?></label>
            <select id="search-location" name="location">
                <option value=""><?php _e('All Locations', 'malisafi-mls'); ?></option>
                <?php
                $selected_location = isset($_GET['location']) ? sanitize_text_field(wp_unslash($_GET['location'])) : '';
                $locations = get_terms(array(
                    'taxonomy' => 'malisafi_property_location',
                    'hide_empty' => true,
                ));
                if ($locations && !is_wp_error($locations)) {
                    foreach ($locations as $location) {
                        echo '<option value="' . esc_attr($location->slug) . '"' . selected($selected_location, $location->slug, false) . '>' . esc_html($location->name) . '</option>';
                    }
                }
                ?>
            </select>
        </div>
        
        <div class="search-field">
            <label for="search-min-price"><?php _e('Min Price', 'malisafi-mls'); ?></label>
            <input type="number" id="search-min-price" name="price_min" placeholder="<?php _e('Min', 'malisafi-mls'); ?>" value="<?php echo isset($_GET['price_min']) ? esc_attr(wp_unslash($_GET['price_min'])) : ''; ?>">
        </div>
        
        <div class="search-field">
            <label for="search-max-price"><?php _e('Max Price', 'malisafi-mls'); ?></label>
            <input type="number" id="search-max-price" name="price_max" placeholder="<?php _e('Max', 'malisafi-mls'); ?>" value="<?php echo isset($_GET['price_max']) ? esc_attr(wp_unslash($_GET['price_max'])) : ''; ?>">
        </div>
        
        <div class="search-field">
            <label for="search-bedrooms"><?php _e('Bedrooms', 'malisafi-mls'); ?></label>
            <select id="search-bedrooms" name="bedrooms">
                <option value=""><?php _e('Any', 'malisafi-mls'); ?></option>
                <?php $selected_bedrooms = isset($_GET['bedrooms']) ? sanitize_text_field(wp_unslash($_GET['bedrooms'])) : ''; ?>
                <option value="1" <?php selected($selected_bedrooms, '1'); ?>>1+</option>
                <option value="2" <?php selected($selected_bedrooms, '2'); ?>>2+</option>
                <option value="3" <?php selected($selected_bedrooms, '3'); ?>>3+</option>
                <option value="4" <?php selected($selected_bedrooms, '4'); ?>>4+</option>
                <option value="5" <?php selected($selected_bedrooms, '5'); ?>>5+</option>
            </select>
        </div>
        
        <div class="search-field">
            <label for="search-bathrooms"><?php _e('Bathrooms', 'malisafi-mls'); ?></label>
            <select id="search-bathrooms" name="bathrooms">
                <option value=""><?php _e('Any', 'malisafi-mls'); ?></option>
                <?php $selected_bathrooms = isset($_GET['bathrooms']) ? sanitize_text_field(wp_unslash($_GET['bathrooms'])) : ''; ?>
                <option value="1" <?php selected($selected_bathrooms, '1'); ?>>1+</option>
                <option value="2" <?php selected($selected_bathrooms, '2'); ?>>2+</option>
                <option value="3" <?php selected($selected_bathrooms, '3'); ?>>3+</option>
                <option value="4" <?php selected($selected_bathrooms, '4'); ?>>4+</option>
            </select>
        </div>
        
        <div class="search-submit">
            <button type="submit" class="search-button"><?php _e('Search Properties', 'malisafi-mls'); ?></button>
        </div>
    </form>
</div>
