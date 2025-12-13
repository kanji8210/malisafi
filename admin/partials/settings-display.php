<?php
/**
 * Settings page template
 *
 * @package MalisafiMLS
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors(); ?>
    
    <h2 class="nav-tab-wrapper">
        <a href="<?php echo esc_url(admin_url('admin.php?page=malisafi-settings&tab=general')); ?>" class="nav-tab <?php echo !isset($_GET['tab']) || $_GET['tab'] === 'general' ? 'nav-tab-active' : ''; ?>">
            <?php _e('General', 'malisafi-mls'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=malisafi-settings&tab=features')); ?>" class="nav-tab <?php echo isset($_GET['tab']) && $_GET['tab'] === 'features' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Features', 'malisafi-mls'); ?>
        </a>
    </h2>
    
    <?php
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
    ?>
    
    <form method="post" action="options.php">
        <?php
        if ($active_tab === 'general') {
            settings_fields('malisafi_mls_general');
            do_settings_sections('malisafi_mls_general');
            ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="malisafi_mls_currency"><?php _e('Currency', 'malisafi-mls'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="malisafi_mls_currency" name="malisafi_mls_currency" value="<?php echo esc_attr(get_option('malisafi_mls_currency', 'USD')); ?>" class="regular-text">
                        <p class="description"><?php _e('Enter currency code (e.g., USD, EUR, GBP)', 'malisafi-mls'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="malisafi_mls_currency_symbol"><?php _e('Currency Symbol', 'malisafi-mls'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="malisafi_mls_currency_symbol" name="malisafi_mls_currency_symbol" value="<?php echo esc_attr(get_option('malisafi_mls_currency_symbol', '$')); ?>" class="small-text">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="malisafi_mls_currency_position"><?php _e('Currency Position', 'malisafi-mls'); ?></label>
                    </th>
                    <td>
                        <select id="malisafi_mls_currency_position" name="malisafi_mls_currency_position">
                            <option value="before" <?php selected(get_option('malisafi_mls_currency_position', 'before'), 'before'); ?>><?php _e('Before ($100)', 'malisafi-mls'); ?></option>
                            <option value="after" <?php selected(get_option('malisafi_mls_currency_position', 'before'), 'after'); ?>><?php _e('After (100$)', 'malisafi-mls'); ?></option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="malisafi_mls_thousand_separator"><?php _e('Thousand Separator', 'malisafi-mls'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="malisafi_mls_thousand_separator" name="malisafi_mls_thousand_separator" value="<?php echo esc_attr(get_option('malisafi_mls_thousand_separator', ',')); ?>" class="small-text">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="malisafi_mls_decimal_separator"><?php _e('Decimal Separator', 'malisafi-mls'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="malisafi_mls_decimal_separator" name="malisafi_mls_decimal_separator" value="<?php echo esc_attr(get_option('malisafi_mls_decimal_separator', '.')); ?>" class="small-text">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="malisafi_mls_price_decimals"><?php _e('Price Decimals', 'malisafi-mls'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="malisafi_mls_price_decimals" name="malisafi_mls_price_decimals" value="<?php echo esc_attr(get_option('malisafi_mls_price_decimals', 0)); ?>" min="0" max="2" class="small-text">
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="malisafi_mls_area_unit"><?php _e('Area Unit', 'malisafi-mls'); ?></label>
                    </th>
                    <td>
                        <select id="malisafi_mls_area_unit" name="malisafi_mls_area_unit">
                            <option value="sqft" <?php selected(get_option('malisafi_mls_area_unit', 'sqft'), 'sqft'); ?>><?php _e('Square Feet (sq ft)', 'malisafi-mls'); ?></option>
                            <option value="sqm" <?php selected(get_option('malisafi_mls_area_unit', 'sqft'), 'sqm'); ?>><?php _e('Square Meters (sq m)', 'malisafi-mls'); ?></option>
                        </select>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="malisafi_mls_properties_per_page"><?php _e('Properties Per Page', 'malisafi-mls'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="malisafi_mls_properties_per_page" name="malisafi_mls_properties_per_page" value="<?php echo esc_attr(get_option('malisafi_mls_properties_per_page', 12)); ?>" min="1" class="small-text">
                    </td>
                </tr>
            </table>
            
        <?php } elseif ($active_tab === 'features') {
            settings_fields('malisafi_mls_features');
            do_settings_sections('malisafi_mls_features');
            ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Front-end Submission', 'malisafi-mls'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="malisafi_mls_enable_front_end_submission" value="1" <?php checked(get_option('malisafi_mls_enable_front_end_submission', false), 1); ?>>
                            <?php _e('Allow users to submit properties from the front-end', 'malisafi-mls'); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="malisafi_mls_submission_page"><?php _e('Submission Page', 'malisafi-mls'); ?></label>
                    </th>
                    <td>
                        <?php
                        $selected = intval(get_option('malisafi_mls_submission_page', 0));
                        wp_dropdown_pages(array(
                            'name' => 'malisafi_mls_submission_page',
                            'selected' => $selected,
                            'show_option_none' => __('— Select —', 'malisafi-mls'),
                        ));
                        ?>
                        <p class="description"><?php _e('Choose the page users will use to submit properties.', 'malisafi-mls'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="malisafi_mls_google_maps_api_key"><?php _e('Google Maps API Key', 'malisafi-mls'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="malisafi_mls_google_maps_api_key" name="malisafi_mls_google_maps_api_key" value="<?php echo esc_attr(get_option('malisafi_mls_google_maps_api_key', '')); ?>" class="regular-text">
                        <p class="description"><?php _e('Required for map functionality. Get your API key from Google Cloud Console.', 'malisafi-mls'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Favorite Properties', 'malisafi-mls'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="malisafi_mls_enable_favorite_properties" value="1" <?php checked(get_option('malisafi_mls_enable_favorite_properties', true), 1); ?>>
                            <?php _e('Enable favorite/wishlist functionality', 'malisafi-mls'); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="malisafi_mls_favorites_page"><?php _e('Favorites Page', 'malisafi-mls'); ?></label>
                    </th>
                    <td>
                        <?php
                        $selected = intval(get_option('malisafi_mls_favorites_page', 0));
                        wp_dropdown_pages(array(
                            'name' => 'malisafi_mls_favorites_page',
                            'selected' => $selected,
                            'show_option_none' => __('— Select —', 'malisafi-mls'),
                        ));
                        ?>
                        <p class="description"><?php _e('Choose the page where users can view their favorites.', 'malisafi-mls'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Property Comparison', 'malisafi-mls'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="malisafi_mls_enable_property_comparison" value="1" <?php checked(get_option('malisafi_mls_enable_property_comparison', true), 1); ?>>
                            <?php _e('Enable property comparison feature', 'malisafi-mls'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Agent Profiles', 'malisafi-mls'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="malisafi_mls_enable_agent_profiles" value="1" <?php checked(get_option('malisafi_mls_enable_agent_profiles', true), 1); ?>>
                            <?php _e('Enable agent profile pages', 'malisafi-mls'); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="malisafi_mls_agent_profile_page"><?php _e('Agent Profile Page', 'malisafi-mls'); ?></label>
                    </th>
                    <td>
                        <?php
                        $selected = intval(get_option('malisafi_mls_agent_profile_page', 0));
                        wp_dropdown_pages(array(
                            'name' => 'malisafi_mls_agent_profile_page',
                            'selected' => $selected,
                            'show_option_none' => __('— Select —', 'malisafi-mls'),
                        ));
                        ?>
                        <p class="description"><?php _e('Choose the page used as the agent profile template.', 'malisafi-mls'); ?></p>
                    </td>
                </tr>
            </table>
            
        <?php } ?>
        
        <?php submit_button(); ?>
    </form>
</div>
