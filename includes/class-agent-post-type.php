<?php
/**
 * Agent Custom Post Type
 *
 * @package MalisafiMLS
 */

namespace MalisafiMLS;

/**
 * Agent_Post_Type class
 */
class Agent_Post_Type {
    
    /**
     * Initialize the agent post type
     */
    public function __construct() {
        add_action('init', array($this, 'register_agent_post_type'));
        add_action('add_meta_boxes', array($this, 'add_agent_meta_boxes'));
        add_action('save_post_malisafi_agent', array($this, 'save_agent_meta'), 10, 2);
        add_filter('manage_malisafi_agent_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_malisafi_agent_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
    }
    
    /**
     * Register agent custom post type
     */
    public function register_agent_post_type() {
        $labels = array(
            'name' => _x('Agents', 'Post Type General Name', 'malisafi-mls'),
            'singular_name' => _x('Agent', 'Post Type Singular Name', 'malisafi-mls'),
            'menu_name' => __('Agents', 'malisafi-mls'),
            'name_admin_bar' => __('Agent', 'malisafi-mls'),
            'archives' => __('Agent Archives', 'malisafi-mls'),
            'attributes' => __('Agent Attributes', 'malisafi-mls'),
            'all_items' => __('All Agents', 'malisafi-mls'),
            'add_new_item' => __('Add New Agent', 'malisafi-mls'),
            'add_new' => __('Add New', 'malisafi-mls'),
            'new_item' => __('New Agent', 'malisafi-mls'),
            'edit_item' => __('Edit Agent', 'malisafi-mls'),
            'update_item' => __('Update Agent', 'malisafi-mls'),
            'view_item' => __('View Agent', 'malisafi-mls'),
            'view_items' => __('View Agents', 'malisafi-mls'),
            'search_items' => __('Search Agent', 'malisafi-mls'),
        );
        
        $args = array(
            'label' => __('Agent', 'malisafi-mls'),
            'description' => __('Real Estate Agents', 'malisafi-mls'),
            'labels' => $labels,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => 'malisafi-dashboard',
            'menu_position' => 26,
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => true,
            'can_export' => true,
            'has_archive' => true,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'capability_type' => 'post',
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'agent'),
        );
        
        register_post_type('malisafi_agent', $args);
        
        // Register Agent Specialties taxonomy
        $specialty_labels = array(
            'name' => _x('Specialties', 'Taxonomy General Name', 'malisafi-mls'),
            'singular_name' => _x('Specialty', 'Taxonomy Singular Name', 'malisafi-mls'),
            'menu_name' => __('Specialties', 'malisafi-mls'),
        );
        
        register_taxonomy('malisafi_agent_specialty', array('malisafi_agent'), array(
            'labels' => $specialty_labels,
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'agent-specialty'),
        ));
    }
    
    /**
     * Add meta boxes
     */
    public function add_agent_meta_boxes() {
        add_meta_box(
            'agent_contact_info',
            __('Contact Information', 'malisafi-mls'),
            array($this, 'render_contact_info_metabox'),
            'malisafi_agent',
            'normal',
            'high'
        );
        
        add_meta_box(
            'agent_professional_info',
            __('Professional Information', 'malisafi-mls'),
            array($this, 'render_professional_info_metabox'),
            'malisafi_agent',
            'normal',
            'high'
        );
        
        add_meta_box(
            'agent_social_media',
            __('Social Media', 'malisafi-mls'),
            array($this, 'render_social_media_metabox'),
            'malisafi_agent',
            'side',
            'default'
        );
        
        add_meta_box(
            'agent_settings',
            __('Agent Settings', 'malisafi-mls'),
            array($this, 'render_settings_metabox'),
            'malisafi_agent',
            'side',
            'default'
        );
        
        add_meta_box(
            'agent_statistics',
            __('Statistics', 'malisafi-mls'),
            array($this, 'render_statistics_metabox'),
            'malisafi_agent',
            'side',
            'low'
        );
    }
    
    /**
     * Render contact info metabox
     */
    public function render_contact_info_metabox($post) {
        wp_nonce_field('agent_meta_nonce', 'agent_meta_nonce');
        
        $email = get_post_meta($post->ID, '_agent_email', true);
        $phone = get_post_meta($post->ID, '_agent_phone', true);
        $mobile = get_post_meta($post->ID, '_agent_mobile', true);
        $whatsapp = get_post_meta($post->ID, '_agent_whatsapp', true);
        $office_address = get_post_meta($post->ID, '_agent_office_address', true);
        $website = get_post_meta($post->ID, '_agent_website', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="agent_email"><?php _e('Email', 'malisafi-mls'); ?> <span style="color:red;">*</span></label></th>
                <td><input type="email" id="agent_email" name="agent_email" value="<?php echo esc_attr($email); ?>" class="regular-text" required /></td>
            </tr>
            <tr>
                <th><label for="agent_phone"><?php _e('Office Phone', 'malisafi-mls'); ?></label></th>
                <td><input type="tel" id="agent_phone" name="agent_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="agent_mobile"><?php _e('Mobile Phone', 'malisafi-mls'); ?> <span style="color:red;">*</span></label></th>
                <td><input type="tel" id="agent_mobile" name="agent_mobile" value="<?php echo esc_attr($mobile); ?>" class="regular-text" required /></td>
            </tr>
            <tr>
                <th><label for="agent_whatsapp"><?php _e('WhatsApp Number', 'malisafi-mls'); ?></label></th>
                <td><input type="tel" id="agent_whatsapp" name="agent_whatsapp" value="<?php echo esc_attr($whatsapp); ?>" class="regular-text" placeholder="+254..." /></td>
            </tr>
            <tr>
                <th><label for="agent_office_address"><?php _e('Office Address', 'malisafi-mls'); ?></label></th>
                <td><textarea id="agent_office_address" name="agent_office_address" rows="3" class="large-text"><?php echo esc_textarea($office_address); ?></textarea></td>
            </tr>
            <tr>
                <th><label for="agent_website"><?php _e('Website', 'malisafi-mls'); ?></label></th>
                <td><input type="url" id="agent_website" name="agent_website" value="<?php echo esc_attr($website); ?>" class="regular-text" /></td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render professional info metabox
     */
    public function render_professional_info_metabox($post) {
        $license_number = get_post_meta($post->ID, '_agent_license_number', true);
        $experience_years = get_post_meta($post->ID, '_agent_experience_years', true);
        $languages = get_post_meta($post->ID, '_agent_languages', true);
        $service_areas = get_post_meta($post->ID, '_agent_service_areas', true);
        $commission_rate = get_post_meta($post->ID, '_agent_commission_rate', true);
        $agency_name = get_post_meta($post->ID, '_agent_agency_name', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="agent_license_number"><?php _e('License Number', 'malisafi-mls'); ?></label></th>
                <td><input type="text" id="agent_license_number" name="agent_license_number" value="<?php echo esc_attr($license_number); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="agent_agency_name"><?php _e('Agency Name', 'malisafi-mls'); ?></label></th>
                <td><input type="text" id="agent_agency_name" name="agent_agency_name" value="<?php echo esc_attr($agency_name); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="agent_experience_years"><?php _e('Years of Experience', 'malisafi-mls'); ?></label></th>
                <td><input type="number" id="agent_experience_years" name="agent_experience_years" value="<?php echo esc_attr($experience_years); ?>" min="0" max="50" /></td>
            </tr>
            <tr>
                <th><label for="agent_languages"><?php _e('Languages Spoken', 'malisafi-mls'); ?></label></th>
                <td><input type="text" id="agent_languages" name="agent_languages" value="<?php echo esc_attr($languages); ?>" class="regular-text" placeholder="English, Swahili, French..." /></td>
            </tr>
            <tr>
                <th><label for="agent_service_areas"><?php _e('Service Areas', 'malisafi-mls'); ?></label></th>
                <td><textarea id="agent_service_areas" name="agent_service_areas" rows="3" class="large-text" placeholder="Nairobi, Westlands, Karen..."><?php echo esc_textarea($service_areas); ?></textarea></td>
            </tr>
            <tr>
                <th><label for="agent_commission_rate"><?php _e('Commission Rate (%)', 'malisafi-mls'); ?></label></th>
                <td><input type="number" id="agent_commission_rate" name="agent_commission_rate" value="<?php echo esc_attr($commission_rate); ?>" min="0" max="100" step="0.1" /> %</td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render social media metabox
     */
    public function render_social_media_metabox($post) {
        $facebook = get_post_meta($post->ID, '_agent_facebook', true);
        $twitter = get_post_meta($post->ID, '_agent_twitter', true);
        $linkedin = get_post_meta($post->ID, '_agent_linkedin', true);
        $instagram = get_post_meta($post->ID, '_agent_instagram', true);
        $youtube = get_post_meta($post->ID, '_agent_youtube', true);
        ?>
        <p>
            <label><strong><?php _e('Facebook', 'malisafi-mls'); ?></strong></label><br/>
            <input type="url" name="agent_facebook" value="<?php echo esc_attr($facebook); ?>" class="widefat" placeholder="https://facebook.com/..." />
        </p>
        <p>
            <label><strong><?php _e('Twitter', 'malisafi-mls'); ?></strong></label><br/>
            <input type="url" name="agent_twitter" value="<?php echo esc_attr($twitter); ?>" class="widefat" placeholder="https://twitter.com/..." />
        </p>
        <p>
            <label><strong><?php _e('LinkedIn', 'malisafi-mls'); ?></strong></label><br/>
            <input type="url" name="agent_linkedin" value="<?php echo esc_attr($linkedin); ?>" class="widefat" placeholder="https://linkedin.com/in/..." />
        </p>
        <p>
            <label><strong><?php _e('Instagram', 'malisafi-mls'); ?></strong></label><br/>
            <input type="url" name="agent_instagram" value="<?php echo esc_attr($instagram); ?>" class="widefat" placeholder="https://instagram.com/..." />
        </p>
        <p>
            <label><strong><?php _e('YouTube', 'malisafi-mls'); ?></strong></label><br/>
            <input type="url" name="agent_youtube" value="<?php echo esc_attr($youtube); ?>" class="widefat" placeholder="https://youtube.com/@..." />
        </p>
        <?php
    }
    
    /**
     * Render settings metabox
     */
    public function render_settings_metabox($post) {
        $status = get_post_meta($post->ID, '_agent_status', true);
        $featured = get_post_meta($post->ID, '_agent_featured', true);
        $user_id = get_post_meta($post->ID, '_agent_user_id', true);
        ?>
        <p>
            <label><strong><?php _e('Agent Status', 'malisafi-mls'); ?></strong></label><br/>
            <select name="agent_status" class="widefat">
                <option value="active" <?php selected($status, 'active'); ?>><?php _e('Active', 'malisafi-mls'); ?></option>
                <option value="inactive" <?php selected($status, 'inactive'); ?>><?php _e('Inactive', 'malisafi-mls'); ?></option>
                <option value="on_vacation" <?php selected($status, 'on_vacation'); ?>><?php _e('On Vacation', 'malisafi-mls'); ?></option>
                <option value="suspended" <?php selected($status, 'suspended'); ?>><?php _e('Suspended', 'malisafi-mls'); ?></option>
            </select>
        </p>
        <p>
            <label>
                <input type="checkbox" name="agent_featured" value="1" <?php checked($featured, '1'); ?> />
                <?php _e('Featured Agent', 'malisafi-mls'); ?>
            </label>
        </p>
        <p>
            <label><strong><?php _e('Linked WordPress User', 'malisafi-mls'); ?></strong></label><br/>
            <?php
            wp_dropdown_users(array(
                'name' => 'agent_user_id',
                'selected' => $user_id,
                'show_option_none' => __('Select User', 'malisafi-mls'),
                'class' => 'widefat',
                'role__in' => array('malisafi_agent_basic', 'malisafi_agent_premium', 'administrator')
            ));
            ?>
            <small><?php _e('Link this agent profile to a WordPress user account', 'malisafi-mls'); ?></small>
        </p>
        <?php
    }
    
    /**
     * Render statistics metabox
     */
    public function render_statistics_metabox($post) {
        global $wpdb;
        
        // Count properties by agent
        $total_properties = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_property_agent_id' AND meta_value = %d",
            $post->ID
        ));
        
        // Count active listings
        $active_listings = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT pm.post_id) 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_property_agent_id' 
            AND pm.meta_value = %d
            AND p.post_status = 'publish'",
            $post->ID
        ));
        
        $total_views = get_post_meta($post->ID, '_agent_total_views', true) ?: 0;
        $total_leads = get_post_meta($post->ID, '_agent_total_leads', true) ?: 0;
        ?>
        <div style="padding: 10px;">
            <p><strong><?php _e('Total Properties:', 'malisafi-mls'); ?></strong> <?php echo intval($total_properties); ?></p>
            <p><strong><?php _e('Active Listings:', 'malisafi-mls'); ?></strong> <?php echo intval($active_listings); ?></p>
            <p><strong><?php _e('Total Views:', 'malisafi-mls'); ?></strong> <?php echo intval($total_views); ?></p>
            <p><strong><?php _e('Total Leads:', 'malisafi-mls'); ?></strong> <?php echo intval($total_leads); ?></p>
        </div>
        <?php
    }
    
    /**
     * Save agent meta
     */
    public function save_agent_meta($post_id, $post) {
        // Verify nonce
        if (!isset($_POST['agent_meta_nonce']) || !wp_verify_nonce($_POST['agent_meta_nonce'], 'agent_meta_nonce')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Contact Information
        $contact_fields = array(
            'agent_email', 'agent_phone', 'agent_mobile', 'agent_whatsapp',
            'agent_office_address', 'agent_website'
        );
        
        foreach ($contact_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Professional Information
        $professional_fields = array(
            'agent_license_number', 'agent_experience_years', 'agent_languages',
            'agent_service_areas', 'agent_commission_rate', 'agent_agency_name'
        );
        
        foreach ($professional_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Social Media
        $social_fields = array(
            'agent_facebook', 'agent_twitter', 'agent_linkedin',
            'agent_instagram', 'agent_youtube'
        );
        
        foreach ($social_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, esc_url_raw($_POST[$field]));
            }
        }
        
        // Settings
        if (isset($_POST['agent_status'])) {
            update_post_meta($post_id, '_agent_status', sanitize_text_field($_POST['agent_status']));
        }
        
        if (isset($_POST['agent_featured'])) {
            update_post_meta($post_id, '_agent_featured', '1');
        } else {
            delete_post_meta($post_id, '_agent_featured');
        }
        
        if (isset($_POST['agent_user_id'])) {
            update_post_meta($post_id, '_agent_user_id', intval($_POST['agent_user_id']));
        }
    }
    
    /**
     * Set custom columns
     */
    public function set_custom_columns($columns) {
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['thumbnail'] = __('Photo', 'malisafi-mls');
        $new_columns['title'] = $columns['title'];
        $new_columns['contact'] = __('Contact', 'malisafi-mls');
        $new_columns['properties'] = __('Properties', 'malisafi-mls');
        $new_columns['status'] = __('Status', 'malisafi-mls');
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }
    
    /**
     * Custom column content
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'thumbnail':
                if (has_post_thumbnail($post_id)) {
                    echo get_the_post_thumbnail($post_id, array(50, 50));
                } else {
                    echo '<span class="dashicons dashicons-businessman" style="font-size: 50px; color: #ccc;"></span>';
                }
                break;
                
            case 'contact':
                $email = get_post_meta($post_id, '_agent_email', true);
                $mobile = get_post_meta($post_id, '_agent_mobile', true);
                if ($email) {
                    echo '<a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a><br/>';
                }
                if ($mobile) {
                    echo '<strong>M:</strong> ' . esc_html($mobile);
                }
                break;
                
            case 'properties':
                global $wpdb;
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_property_agent_id' AND meta_value = %d",
                    $post_id
                ));
                echo intval($count) . ' ' . __('properties', 'malisafi-mls');
                break;
                
            case 'status':
                $status = get_post_meta($post_id, '_agent_status', true);
                $statuses = array(
                    'active' => '<span style="color: green;">● ' . __('Active', 'malisafi-mls') . '</span>',
                    'inactive' => '<span style="color: gray;">● ' . __('Inactive', 'malisafi-mls') . '</span>',
                    'on_vacation' => '<span style="color: orange;">● ' . __('On Vacation', 'malisafi-mls') . '</span>',
                    'suspended' => '<span style="color: red;">● ' . __('Suspended', 'malisafi-mls') . '</span>',
                );
                echo isset($statuses[$status]) ? $statuses[$status] : $statuses['active'];
                
                if (get_post_meta($post_id, '_agent_featured', true)) {
                    echo '<br/><span style="color: gold;">★ ' . __('Featured', 'malisafi-mls') . '</span>';
                }
                break;
        }
    }
}
