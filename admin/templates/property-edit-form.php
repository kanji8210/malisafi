<?php
/**
 * Template pour le formulaire de création/édition de propriété (squelette)
 * @package MalisafiMLS
 */
if (!defined('ABSPATH')) exit;
?>
<div class="malisafi-property-edit-form">
    <form method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('malisafi_property_edit', 'malisafi_property_edit_nonce'); ?>
        <input type="hidden" name="property_id" value="<?php echo esc_attr($property_id); ?>" />
        <table class="form-table">
            <tr>
                <th><label for="property_title"><?php _e('Title', 'malisafi-mls'); ?></label></th>
                <td><input type="text" name="property_title" id="property_title" value="<?php echo esc_attr($property_title ?? ''); ?>" class="regular-text" required /></td>
            </tr>
            <tr>
                <th><label for="property_gps"><?php _e('GPS Coordinates', 'malisafi-mls'); ?></label></th>
                <td>
                    <input type="text" name="property_gps" id="property_gps" value="<?php echo esc_attr($property_gps ?? ''); ?>" class="regular-text" placeholder="-1.2921, 36.8219" />
                    <button type="button" class="button" onclick="malisafiGetLocation()">
                        <?php _e('Use my current location', 'malisafi-mls'); ?>
                    </button>
                    <p class="description">
                        <?php _e('You can use your current location for the map. For privacy, you may alter the last digit before saving so only an approximate location is recorded.', 'malisafi-mls'); ?>
                    </p>
                </td>
            </tr>
            <!-- TODO: Ajouter les autres champs requis (type, status, county, prix, etc.) -->
        </table>
        <p class="submit">
            <button type="submit" class="button button-primary"><?php echo $property_id ? __('Update Property', 'malisafi-mls') : __('Create Property', 'malisafi-mls'); ?></button>
        </p>
        <script>
        function malisafiGetLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    var lat = position.coords.latitude.toFixed(6);
                    var lng = position.coords.longitude.toFixed(6);
                    document.getElementById('property_gps').value = lat + ', ' + lng;
                }, function(error) {
                    alert('Unable to retrieve your location.');
                });
            } else {
                alert('Geolocation is not supported by your browser.');
            }
        }
        </script>
        </p>
    </form>
</div>
