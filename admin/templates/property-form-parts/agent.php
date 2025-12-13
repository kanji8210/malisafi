<!-- Agent Information Section -->
<div class="postbox">
    <h2 class="hndle"><?php _e('Agent/Contact Information', 'malisafi-mls'); ?></h2>
    <div class="inside">
        <table class="form-table">
            <tr>
                <th><label for="agent_name"><?php _e('Agent Name', 'malisafi-mls'); ?></label></th>
                <td>
                    <?php
                    $current_user = wp_get_current_user();
                    $default_name = $current_user->display_name;
                    ?>
                    <input type="text" name="agent_name" id="agent_name" class="regular-text" 
                           value="<?php echo $is_edit ? esc_attr(get_post_meta($property_id, '_malisafi_agent_name', true)) : esc_attr($default_name); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="agent_email"><?php _e('Agent Email', 'malisafi-mls'); ?></label></th>
                <td>
                    <?php
                    $default_email = $current_user->user_email;
                    ?>
                    <input type="email" name="agent_email" id="agent_email" class="regular-text" 
                           value="<?php echo $is_edit ? esc_attr(get_post_meta($property_id, '_malisafi_agent_email', true)) : esc_attr($default_email); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="agent_phone"><?php _e('Agent Phone', 'malisafi-mls'); ?></label></th>
                <td>
                    <?php
                    $default_phone = get_user_meta($current_user->ID, 'phone', true);
                    ?>
                    <input type="tel" name="agent_phone" id="agent_phone" class="regular-text" 
                           value="<?php echo $is_edit ? esc_attr(get_post_meta($property_id, '_malisafi_agent_phone', true)) : esc_attr($default_phone); ?>">
                </td>
            </tr>
        </table>
        <p class="description">
            <?php _e('This information will be displayed as contact details for this property.', 'malisafi-mls'); ?>
        </p>
    </div>
</div>
