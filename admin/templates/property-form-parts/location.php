<!-- Location Section -->
<div class="postbox">
    <h2 class="hndle"><?php _e('Location', 'malisafi-mls'); ?></h2>
    <div class="inside">
        <table class="form-table">
            <tr>
                <th><label for="property_country"><?php _e('Country', 'malisafi-mls'); ?> <span class="required">*</span></label></th>
                <td>
                    <input type="text" name="property_country" id="property_country" class="regular-text" 
                           value="Kenya" readonly style="background-color: #f0f0f1;">
                </td>
            </tr>
            <tr>
                <th><label for="property_county"><?php _e('County', 'malisafi-mls'); ?> <span class="required">*</span></label></th>
                <td>
                    <?php
                    $current_county = $is_edit ? get_post_meta($property_id, '_malisafi_county', true) : '';
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
                    ?>
                    <select name="property_county" id="property_county" class="regular-text" required>
                        <option value=""><?php _e('Select County', 'malisafi-mls'); ?></option>
                        <?php foreach ($kenya_counties as $county) : ?>
                            <option value="<?php echo esc_attr($county); ?>" <?php selected($current_county, $county); ?>>
                                <?php echo esc_html($county); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="property_city"><?php _e('City/Town', 'malisafi-mls'); ?> <span class="required">*</span></label></th>
                <td>
                    <input type="text" name="property_city" id="property_city" class="regular-text" 
                           value="<?php echo $is_edit ? esc_attr(get_post_meta($property_id, '_malisafi_city', true)) : ''; ?>" 
                           placeholder="<?php _e('e.g., Nairobi, Mombasa, Kisumu', 'malisafi-mls'); ?>" required>
                    <p class="description"><?php _e('Enter the main city or town', 'malisafi-mls'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="property_neighbourhood"><?php _e('Neighbourhood/Estate', 'malisafi-mls'); ?></label></th>
                <td>
                    <input type="text" name="property_neighbourhood" id="property_neighbourhood" class="regular-text" 
                           value="<?php echo $is_edit ? esc_attr(get_post_meta($property_id, '_malisafi_neighbourhood', true)) : ''; ?>"
                           placeholder="<?php _e('e.g., Karen, Westlands, Kilimani', 'malisafi-mls'); ?>">
                    <p class="description"><?php _e('Specific neighbourhood, estate, or area name', 'malisafi-mls'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="property_setting"><?php _e('Area Setting', 'malisafi-mls'); ?> <span class="required">*</span></label></th>
                <td>
                    <?php
                    $current_setting = $is_edit ? get_post_meta($property_id, '_malisafi_setting', true) : '';
                    $area_settings = array(
                        'urban' => __('Urban', 'malisafi-mls'),
                        'semi-rural' => __('Semi-Rural', 'malisafi-mls'),
                        'rural' => __('Rural', 'malisafi-mls'),
                        'isolated' => __('Isolated', 'malisafi-mls')
                    );
                    ?>
                    <select name="property_setting" id="property_setting" class="regular-text" required>
                        <option value=""><?php _e('Select Area Setting', 'malisafi-mls'); ?></option>
                        <?php foreach ($area_settings as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($current_setting, $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <strong><?php _e('Urban:', 'malisafi-mls'); ?></strong> <?php _e('City centers, high-density areas', 'malisafi-mls'); ?><br>
                        <strong><?php _e('Semi-Rural:', 'malisafi-mls'); ?></strong> <?php _e('Suburban areas, town outskirts', 'malisafi-mls'); ?><br>
                        <strong><?php _e('Rural:', 'malisafi-mls'); ?></strong> <?php _e('Countryside, farming areas', 'malisafi-mls'); ?><br>
                        <strong><?php _e('Isolated:', 'malisafi-mls'); ?></strong> <?php _e('Remote locations, minimal services', 'malisafi-mls'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="property_address"><?php _e('Street Address', 'malisafi-mls'); ?></label></th>
                <td>
                    <input type="text" name="property_address" id="property_address" class="large-text" 
                           value="<?php echo $is_edit ? esc_attr(get_post_meta($property_id, '_malisafi_address', true)) : ''; ?>"
                           placeholder="<?php _e('e.g., Plot 123, Moi Avenue', 'malisafi-mls'); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="property_zip"><?php _e('Postal Code', 'malisafi-mls'); ?></label></th>
                <td>
                    <input type="text" name="property_zip" id="property_zip" class="regular-text" 
                           value="<?php echo $is_edit ? esc_attr(get_post_meta($property_id, '_malisafi_zip', true)) : ''; ?>"
                           placeholder="<?php _e('e.g., 00100', 'malisafi-mls'); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="property_location"><?php _e('Location Category', 'malisafi-mls'); ?></label></th>
                <td>
                    <?php
                    $property_locations = get_terms(array('taxonomy' => 'malisafi_property_location', 'hide_empty' => false));
                    $selected_locations = $is_edit ? wp_get_post_terms($property_id, 'malisafi_property_location', array('fields' => 'ids')) : array();
                    ?>
                    <select name="property_location[]" id="property_location" class="regular-text">
                        <option value=""><?php _e('Select Location', 'malisafi-mls'); ?></option>
                        <?php foreach ($property_locations as $location) : ?>
                            <option value="<?php echo esc_attr($location->term_id); ?>" <?php selected(in_array($location->term_id, $selected_locations)); ?>>
                                <?php echo esc_html($location->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Coordinates', 'malisafi-mls'); ?></label></th>
                <td>
                    <input type="text" name="property_latitude" id="property_latitude" placeholder="<?php _e('Latitude', 'malisafi-mls'); ?>" class="regular-text" 
                           value="<?php echo $is_edit ? esc_attr(get_post_meta($property_id, '_malisafi_latitude', true)) : ''; ?>">
                    <input type="text" name="property_longitude" id="property_longitude" placeholder="<?php _e('Longitude', 'malisafi-mls'); ?>" class="regular-text" 
                           value="<?php echo $is_edit ? esc_attr(get_post_meta($property_id, '_malisafi_longitude', true)) : ''; ?>">
                    <p class="description"><?php _e('Optional: For map display', 'malisafi-mls'); ?></p>
                </td>
            </tr>
        </table>
    </div>
</div>
