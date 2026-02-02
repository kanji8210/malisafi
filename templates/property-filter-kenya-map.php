<?php
/**
 * Kenya Map Property Filter (SVG)
 * Shortcode: [malisafi_kenya_map_filter]
 */

if (!defined('ABSPATH')) {
    exit;
}

$results_url = isset($atts['results_url']) ? $atts['results_url'] : home_url('/properties');
$results_url = $results_url ? $results_url : home_url('/properties');

$counties = function_exists('malisafi_get_kenya_counties') ? malisafi_get_kenya_counties() : array();
$svg_path = MALISAFI_MLS_PATH . 'assets/svg/kenya-counties.svg';
$has_svg = file_exists($svg_path);
?>

<div class="malisafi-kenya-map-filter">
    <div class="malisafi-kenya-map" data-has-svg="<?php echo $has_svg ? '1' : '0'; ?>">
        <?php
        if ($has_svg) {
            echo file_get_contents($svg_path);
        } else {
            echo '<div class="malisafi-kenya-helper">' . esc_html__('SVG map file not found. Please add assets/svg/kenya-counties.svg', 'malisafi-mls') . '</div>';
        }
        ?>
    </div>

    <form class="malisafi-kenya-filter-form" method="get" action="<?php echo esc_url($results_url); ?>">
        <div class="malisafi-kenya-search-row">
            <label class="screen-reader-text" for="mf-search"><?php _e('Search', 'malisafi-mls'); ?></label>
            <input type="search" id="mf-search" name="s" placeholder="<?php esc_attr_e('Search properties...', 'malisafi-mls'); ?>" />
            <button type="submit" class="search-btn"><?php _e('Search', 'malisafi-mls'); ?></button>
        </div>

        <div class="malisafi-kenya-filter-row">
            <div>
                <label for="mf-county"><?php _e('County', 'malisafi-mls'); ?></label>
                <select id="mf-county" name="county">
                    <option value=""><?php _e('Select County...', 'malisafi-mls'); ?></option>
                    <?php foreach ($counties as $county) : ?>
                        <option value="<?php echo esc_attr($county); ?>"><?php echo esc_html($county); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="mf-subcounty"><?php _e('Sub-county', 'malisafi-mls'); ?></label>
                <select id="mf-subcounty" name="subcounty">
                    <option value=""><?php _e('Select Sub-county...', 'malisafi-mls'); ?></option>
                </select>
                <div class="malisafi-kenya-helper"><?php _e('Select a county to load sub-counties.', 'malisafi-mls'); ?></div>
            </div>
        </div>

        <div class="price-grid">
            <div>
                <label for="mf-price-min"><?php _e('Minimum Price', 'malisafi-mls'); ?></label>
                <input type="number" id="mf-price-min" name="price_min" min="0" step="0.01" placeholder="0" />
            </div>
            <div>
                <label for="mf-price-max"><?php _e('Maximum Price', 'malisafi-mls'); ?></label>
                <input type="number" id="mf-price-max" name="price_max" min="0" step="0.01" placeholder="10000000" />
            </div>
        </div>

        <div>
            <label for="mf-status"><?php _e('Property Status', 'malisafi-mls'); ?></label>
            <select id="mf-status" name="status">
                <option value=""><?php _e('Select Status...', 'malisafi-mls'); ?></option>
                <option value="rent"><?php _e('Rent', 'malisafi-mls'); ?></option>
                <option value="sale"><?php _e('Sale', 'malisafi-mls'); ?></option>
            </select>
        </div>

        <div class="malisafi-kenya-filter-actions">
            <button type="submit"><?php _e('Search', 'malisafi-mls'); ?></button>
            <button type="button" class="reset-btn"><?php _e('Reset', 'malisafi-mls'); ?></button>
        </div>
    </form>
</div>
