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

<div class="malisafi-search-form <?php echo esc_attr($atts['style']); ?>">
    <form method="get" action="<?php echo esc_url(home_url('/')); ?>" class="property-search">
        <input type="hidden" name="post_type" value="malisafi_property">
        
        <div class="search-field">
            <label for="search-keyword"><?php _e('Keyword', 'malisafi-mls'); ?></label>
            <input type="text" id="search-keyword" name="s" placeholder="<?php _e('Search properties...', 'malisafi-mls'); ?>" value="<?php echo get_search_query(); ?>">
        </div>
        
        <div class="search-field">
            <label for="search-type"><?php _e('Property Type', 'malisafi-mls'); ?></label>
            <select id="search-type" name="property_type">
                <option value=""><?php _e('All Types', 'malisafi-mls'); ?></option>
                <?php
                $types = get_terms(array(
                    'taxonomy' => 'malisafi_property_type',
                    'hide_empty' => true,
                ));
                foreach ($types as $type) {
                    echo '<option value="' . esc_attr($type->slug) . '">' . esc_html($type->name) . '</option>';
                }
                ?>
            </select>
        </div>
        
        <div class="search-field">
            <label for="search-status"><?php _e('Status', 'malisafi-mls'); ?></label>
            <select id="search-status" name="property_status">
                <option value=""><?php _e('All Status', 'malisafi-mls'); ?></option>
                <?php
                $statuses = get_terms(array(
                    'taxonomy' => 'malisafi_property_status',
                    'hide_empty' => true,
                ));
                foreach ($statuses as $status) {
                    echo '<option value="' . esc_attr($status->slug) . '">' . esc_html($status->name) . '</option>';
                }
                ?>
            </select>
        </div>
        
        <div class="search-field">
            <label for="search-county"><?php _e('County', 'malisafi-mls'); ?></label>
            <select id="search-county" name="county">
                <option value=""><?php _e('All Counties', 'malisafi-mls'); ?></option>
                <?php
                $kenya_counties = array(
                    'Nairobi', 'Mombasa', 'Kisumu', 'Nakuru', 'Eldoret', 'Thika', 'Malindi', 'Kitale',
                    'Garissa', 'Kakamega', 'Machakos', 'Meru', 'Nyeri', 'Kiambu', 'Kajiado', 'Kilifi',
                    'Trans Nzoia', 'Uasin Gishu', 'Bungoma', 'Siaya', 'Kisii', 'Kericho', 'Migori',
                    'Baringo', 'Bomet', 'Busia', 'Elgeyo-Marakwet', 'Embu', 'Homa Bay', 'Isiolo',
                    'Kirinyaga', 'Kwale', 'Laikipia', 'Lamu', 'Makueni', 'Mandera', 'Marsabit',
                    'Murang\'a', 'Nandi', 'Narok', 'Nyandarua', 'Nyamira', 'Samburu', 'Taita-Taveta',
                    'Tana River', 'Tharaka-Nithi', 'Turkana', 'Vihiga', 'Wajir', 'West Pokot'
                );
                sort($kenya_counties);
                foreach ($kenya_counties as $county) {
                    echo '<option value="' . esc_attr($county) . '">' . esc_html($county) . '</option>';
                }
                ?>
            </select>
        </div>
        
        <div class="search-field">
            <label for="search-setting"><?php _e('Area Setting', 'malisafi-mls'); ?></label>
            <select id="search-setting" name="setting">
                <option value=""><?php _e('All Settings', 'malisafi-mls'); ?></option>
                <option value="urban"><?php _e('Urban', 'malisafi-mls'); ?></option>
                <option value="semi-rural"><?php _e('Semi-Rural', 'malisafi-mls'); ?></option>
                <option value="rural"><?php _e('Rural', 'malisafi-mls'); ?></option>
                <option value="isolated"><?php _e('Isolated', 'malisafi-mls'); ?></option>
            </select>
        </div>
        
        <div class="search-field">
            <label for="search-min-price"><?php _e('Min Price', 'malisafi-mls'); ?></label>
            <input type="number" id="search-min-price" name="min_price" placeholder="<?php _e('Min', 'malisafi-mls'); ?>">
        </div>
        
        <div class="search-field">
            <label for="search-max-price"><?php _e('Max Price', 'malisafi-mls'); ?></label>
            <input type="number" id="search-max-price" name="max_price" placeholder="<?php _e('Max', 'malisafi-mls'); ?>">
        </div>
        
        <div class="search-field">
            <label for="search-bedrooms"><?php _e('Bedrooms', 'malisafi-mls'); ?></label>
            <select id="search-bedrooms" name="bedrooms">
                <option value=""><?php _e('Any', 'malisafi-mls'); ?></option>
                <option value="1">1+</option>
                <option value="2">2+</option>
                <option value="3">3+</option>
                <option value="4">4+</option>
                <option value="5">5+</option>
            </select>
        </div>
        
        <div class="search-field">
            <label for="search-bathrooms"><?php _e('Bathrooms', 'malisafi-mls'); ?></label>
            <select id="search-bathrooms" name="bathrooms">
                <option value=""><?php _e('Any', 'malisafi-mls'); ?></option>
                <option value="1">1+</option>
                <option value="2">2+</option>
                <option value="3">3+</option>
                <option value="4">4+</option>
            </select>
        </div>
        
        <div class="search-submit">
            <button type="submit" class="search-button"><?php _e('Search Properties', 'malisafi-mls'); ?></button>
        </div>
    </form>
</div>
