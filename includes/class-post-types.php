<?php
namespace MalisafiMLS;

/**
 * Custom post types and taxonomies
 *
 * @package MalisafiMLS
 */
class Post_Types {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Enqueue media uploader
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Register custom image sizes for consistent landscape presentation
        add_action('after_setup_theme', array($this, 'register_image_sizes'));
        
        // Add template filter for single property page
        add_filter('single_template', array($this, 'load_single_property_template'));
        add_filter('single_template', array($this, 'load_single_project_template'));
    }
    
    /**
     * Register image sizes
     * Ensures featured and gallery images are consistently landscape and cropped
     */
    public function register_image_sizes() {
        if (function_exists('add_image_size')) {
            add_image_size('malisafi_landscape', 1200, 800, true); // Featured/full display
            add_image_size('malisafi_grid', 600, 400, true);       // Gallery grid and cards
            add_image_size('malisafi_thumb', 300, 200, true);      // Small thumbnails
        }
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type, $post;
        
        // Show warning in classic editor but don't block it
        if (("post.php" === $hook || "post-new.php" === $hook) && 'malisafi_property' === $post_type) {
            add_action('admin_notices', function() use ($post) {
                $custom_form_url = add_query_arg(
                    array(
                        'page' => 'malisafi-properties',
                        'action' => 'add'
                    ),
                    admin_url('admin.php')
                );
                
                if ($post && $post->ID) {
                    $custom_form_url = add_query_arg('property_id', $post->ID, $custom_form_url);
                }
                ?>
                <div class="notice notice-warning" style="border-left-color: #f56e28;">
                    <h3 style="margin-top: 10px;">⚠️ <?php _e('Classic Editor Not Recommended', 'malisafi-mls'); ?></h3>
                    <p>
                        <strong><?php _e('We strongly recommend using our custom property form instead of the classic editor.', 'malisafi-mls'); ?></strong>
                    </p>
                    <p><?php _e('The custom form includes:', 'malisafi-mls'); ?></p>
                    <ul style="list-style: disc; margin-left: 20px;">
                        <li><?php _e('All property fields in one place', 'malisafi-mls'); ?></li>
                        <li><?php _e('Better organization and user experience', 'malisafi-mls'); ?></li>
                        <li><?php _e('Validation to prevent errors', 'malisafi-mls'); ?></li>
                        <li><?php _e('Image upload and management', 'malisafi-mls'); ?></li>
                        <li><?php _e('Features and amenities selection', 'malisafi-mls'); ?></li>
                    </ul>
                    <p>
                        <a href="<?php echo esc_url($custom_form_url); ?>" class="button button-primary" style="margin-right: 10px;">
                            <?php _e('Use Custom Property Form', 'malisafi-mls'); ?> →
                        </a>
                        <button type="button" class="button button-secondary" onclick="this.closest('.notice').style.display='none';">
                            <?php _e('I understand, continue with classic editor', 'malisafi-mls'); ?>
                        </button>
                    </p>
                </div>
                <?php
            });
        }
    }
    
    /**
     * Register property custom post type
     */
    public function register_property_post_type() {
        $labels = array(
            'name' => _x('Properties', 'Post Type General Name', 'malisafi-mls'),
            'singular_name' => _x('Property', 'Post Type Singular Name', 'malisafi-mls'),
            'menu_name' => __('Properties', 'malisafi-mls'),
            'name_admin_bar' => __('Property', 'malisafi-mls'),
            'archives' => __('Property Archives', 'malisafi-mls'),
            'attributes' => __('Property Attributes', 'malisafi-mls'),
            'parent_item_colon' => __('Parent Property:', 'malisafi-mls'),
            'all_items' => __('All Properties', 'malisafi-mls'),
            'add_new_item' => __('Add New Property', 'malisafi-mls'),
            'add_new' => __('Add New', 'malisafi-mls'),
            'new_item' => __('New Property', 'malisafi-mls'),
            'edit_item' => __('Edit Property', 'malisafi-mls'),
            'update_item' => __('Update Property', 'malisafi-mls'),
            'view_item' => __('View Property', 'malisafi-mls'),
            'view_items' => __('View Properties', 'malisafi-mls'),
            'search_items' => __('Search Property', 'malisafi-mls'),
        );
        
        $args = array(
            'label' => __('Property', 'malisafi-mls'),
            'description' => __('Real Estate Properties', 'malisafi-mls'),
            'labels' => $labels,
            'supports' => array('title', 'thumbnail', 'excerpt', 'comments', 'author'),
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 5,
            'menu_icon' => 'dashicons-admin-home',
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => true,
            'can_export' => true,
            'has_archive' => true,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'capability_type' => array('property', 'properties'),
            'map_meta_cap' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'property'),
        );
        
        register_post_type('malisafi_property', $args);
    }

    /**
     * Register project custom post type
     */
    public function register_project_post_type() {
        $labels = array(
            'name' => _x('Projects', 'Post Type General Name', 'malisafi-mls'),
            'singular_name' => _x('Project', 'Post Type Singular Name', 'malisafi-mls'),
            'menu_name' => __('Projects', 'malisafi-mls'),
            'name_admin_bar' => __('Project', 'malisafi-mls'),
            'archives' => __('Project Archives', 'malisafi-mls'),
            'attributes' => __('Project Attributes', 'malisafi-mls'),
            'parent_item_colon' => __('Parent Project:', 'malisafi-mls'),
            'all_items' => __('All Projects', 'malisafi-mls'),
            'add_new_item' => __('Add New Project', 'malisafi-mls'),
            'add_new' => __('Add New', 'malisafi-mls'),
            'new_item' => __('New Project', 'malisafi-mls'),
            'edit_item' => __('Edit Project', 'malisafi-mls'),
            'update_item' => __('Update Project', 'malisafi-mls'),
            'view_item' => __('View Project', 'malisafi-mls'),
            'view_items' => __('View Projects', 'malisafi-mls'),
            'search_items' => __('Search Project', 'malisafi-mls'),
        );

        $args = array(
            'label' => __('Project', 'malisafi-mls'),
            'description' => __('Development Projects', 'malisafi-mls'),
            'labels' => $labels,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'author'),
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 6,
            'menu_icon' => 'dashicons-building',
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => true,
            'can_export' => true,
            'has_archive' => true,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'capability_type' => array('project', 'projects'),
            'map_meta_cap' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'project'),
        );

        register_post_type('malisafi_project', $args);
    }

    
    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        // Property Type taxonomy
        $type_labels = array(
            'name' => _x('Property Types', 'Taxonomy General Name', 'malisafi-mls'),
            'singular_name' => _x('Property Type', 'Taxonomy Singular Name', 'malisafi-mls'),
            'menu_name' => __('Property Types', 'malisafi-mls'),
            'all_items' => __('All Types', 'malisafi-mls'),
            'parent_item' => __('Parent Type', 'malisafi-mls'),
            'parent_item_colon' => __('Parent Type:', 'malisafi-mls'),
            'new_item_name' => __('New Type Name', 'malisafi-mls'),
            'add_new_item' => __('Add New Type', 'malisafi-mls'),
            'edit_item' => __('Edit Type', 'malisafi-mls'),
            'update_item' => __('Update Type', 'malisafi-mls'),
            'view_item' => __('View Type', 'malisafi-mls'),
            'search_items' => __('Search Types', 'malisafi-mls'),
        );
        
        register_taxonomy('malisafi_property_type', array('malisafi_property'), array(
            'labels' => $type_labels,
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'property-type'),
        ));
        
        // Property Status taxonomy
        $status_labels = array(
            'name' => _x('Property Status', 'Taxonomy General Name', 'malisafi-mls'),
            'singular_name' => _x('Status', 'Taxonomy Singular Name', 'malisafi-mls'),
            'menu_name' => __('Status', 'malisafi-mls'),
        );
        
        register_taxonomy('malisafi_property_status', array('malisafi_property'), array(
            'labels' => $status_labels,
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'property-status'),
        ));
        
        // Location taxonomy
        $location_labels = array(
            'name' => _x('Locations', 'Taxonomy General Name', 'malisafi-mls'),
            'singular_name' => _x('Location', 'Taxonomy Singular Name', 'malisafi-mls'),
            'menu_name' => __('Locations', 'malisafi-mls'),
        );
        
        register_taxonomy('malisafi_property_location', array('malisafi_property'), array(
            'labels' => $location_labels,
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'location'),
        ));
        
        // Features taxonomy
        $features_labels = array(
            'name' => _x('Features', 'Taxonomy General Name', 'malisafi-mls'),
            'singular_name' => _x('Feature', 'Taxonomy Singular Name', 'malisafi-mls'),
            'menu_name' => __('Features', 'malisafi-mls'),
        );
        
        register_taxonomy('malisafi_property_features', array('malisafi_property'), array(
            'labels' => $features_labels,
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => false,
            'show_in_rest' => true,
            'rewrite' => array('slug' => 'feature'),
        ));
    }

    
    /**
     * Add property meta boxes
     */
    public function add_property_meta_boxes() {
        add_meta_box(
            'malisafi_property_details',
            __('Property Details', 'malisafi-mls'),
            array($this, 'render_property_details_meta_box'),
            'malisafi_property',
            'normal',
            'high'
        );
        
        add_meta_box(
            'malisafi_property_pricing',
            __('Pricing Information', 'malisafi-mls'),
            array($this, 'render_pricing_meta_box'),
            'malisafi_property',
            'side',
            'high'
        );
        
        add_meta_box(
            'malisafi_property_location',
            __('Location Details', 'malisafi-mls'),
            array($this, 'render_location_meta_box'),
            'malisafi_property',
            'normal',
            'default'
        );
        
        add_meta_box(
            'malisafi_property_agent',
            __('Agent Information', 'malisafi-mls'),
            array($this, 'render_agent_meta_box'),
            'malisafi_property',
            'side',
            'default'
        );
        
        add_meta_box(
            'malisafi_property_gallery',
            __('Property Photos Gallery', 'malisafi-mls'),
            array($this, 'render_gallery_meta_box'),
            'malisafi_property',
            'normal',
            'default'
        );
    }

    /**
     * Add project meta boxes
     */
    public function add_project_meta_boxes() {
        add_meta_box(
            'malisafi_project_details',
            __('Project Details', 'malisafi-mls'),
            array($this, 'render_project_details_meta_box'),
            'malisafi_project',
            'normal',
            'high'
        );

        add_meta_box(
            'malisafi_project_location',
            __('Location Details', 'malisafi-mls'),
            array($this, 'render_project_location_meta_box'),
            'malisafi_project',
            'normal',
            'default'
        );

        add_meta_box(
            'malisafi_project_links',
            __('Linked Properties (Debug Panel)', 'malisafi-mls'),
            array($this, 'render_project_links_meta_box'),
            'malisafi_project',
            'side',
            'default'
        );
    }

    /**
     * Render project details meta box
     */
    public function render_project_details_meta_box($post) {
        wp_nonce_field('malisafi_project_details', 'malisafi_project_details_nonce');

        $project_type = get_post_meta($post->ID, '_malisafi_project_type', true);
        $project_category = get_post_meta($post->ID, '_malisafi_project_category', true);
        $project_subcategory = get_post_meta($post->ID, '_malisafi_project_subcategory', true);
        $timeline = get_post_meta($post->ID, '_malisafi_project_timeline', true);
        $milestones = get_post_meta($post->ID, '_malisafi_project_milestones', true);
        if (!is_array($milestones)) {
            $milestones = $milestones ? (array) $milestones : array();
        }
        $milestones_text = self::format_milestones_for_textarea($milestones);
        $investor_highlights = get_post_meta($post->ID, '_malisafi_project_investor_highlights', true);
        $client_highlights = get_post_meta($post->ID, '_malisafi_project_client_highlights', true);
        $auto_sync = get_post_meta($post->ID, '_malisafi_project_auto_sync', true);
        $brochure_id = (int) get_post_meta($post->ID, '_malisafi_project_brochure_id', true);
        $brochure_url = $brochure_id ? wp_get_attachment_url($brochure_id) : '';
        ?>
        <div class="malisafi-meta-fields">
            <p>
                <label for="malisafi_project_type"><?php esc_html_e('Project Type:', 'malisafi-mls'); ?></label>
                <input type="text" id="malisafi_project_type" name="malisafi_project_type" value="<?php echo esc_attr($project_type); ?>" class="widefat">
            </p>
            <p>
                <label for="malisafi_project_category"><?php esc_html_e('Category:', 'malisafi-mls'); ?></label>
                <input type="text" id="malisafi_project_category" name="malisafi_project_category" value="<?php echo esc_attr($project_category); ?>" class="widefat">
            </p>
            <p>
                <label for="malisafi_project_subcategory"><?php esc_html_e('Subcategory:', 'malisafi-mls'); ?></label>
                <input type="text" id="malisafi_project_subcategory" name="malisafi_project_subcategory" value="<?php echo esc_attr($project_subcategory); ?>" class="widefat">
            </p>
            <p>
                <label for="malisafi_project_timeline"><?php esc_html_e('Timeline & Milestones:', 'malisafi-mls'); ?></label>
                <textarea id="malisafi_project_timeline" name="malisafi_project_timeline" rows="4" class="widefat"><?php echo esc_textarea($timeline); ?></textarea>
            </p>
            <p>
                <label for="malisafi_project_milestones"><?php esc_html_e('Milestones (one per line):', 'malisafi-mls'); ?></label>
                <textarea id="malisafi_project_milestones" name="malisafi_project_milestones" rows="4" class="widefat" placeholder="<?php esc_attr_e('YYYY-MM-DD | Title | Status | %', 'malisafi-mls'); ?>"><?php echo esc_textarea($milestones_text); ?></textarea>
                <small><?php esc_html_e('Format: YYYY-MM-DD | Intitulé | Statut | %', 'malisafi-mls'); ?></small>
            </p>
            <p>
                <label for="malisafi_project_investor_highlights"><?php esc_html_e('Investor Highlights:', 'malisafi-mls'); ?></label>
                <textarea id="malisafi_project_investor_highlights" name="malisafi_project_investor_highlights" rows="4" class="widefat"><?php echo esc_textarea($investor_highlights); ?></textarea>
            </p>
            <p>
                <label for="malisafi_project_client_highlights"><?php esc_html_e('Client Highlights:', 'malisafi-mls'); ?></label>
                <textarea id="malisafi_project_client_highlights" name="malisafi_project_client_highlights" rows="4" class="widefat"><?php echo esc_textarea($client_highlights); ?></textarea>
            </p>
            <p>
                <label for="malisafi_project_brochure"><?php esc_html_e('Brochure (PDF):', 'malisafi-mls'); ?></label>
                <input type="file" id="malisafi_project_brochure" name="malisafi_project_brochure" accept="application/pdf" />
                <?php if ($brochure_url) : ?>
                    <br />
                    <a href="<?php echo esc_url($brochure_url); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('View current brochure', 'malisafi-mls'); ?></a>
                <?php endif; ?>
            </p>
            <p>
                <label>
                    <input type="checkbox" name="malisafi_project_auto_sync" value="1" <?php checked($auto_sync, '1'); ?> />
                    <?php esc_html_e('Auto-sync linked property details', 'malisafi-mls'); ?>
                </label>
            </p>
        </div>
        <?php
    }

    /**
     * Render project location meta box
     */
    public function render_project_location_meta_box($post) {
        $county = get_post_meta($post->ID, '_malisafi_county', true);
        $neighbourhood = get_post_meta($post->ID, '_malisafi_neighbourhood', true);
        $setting = get_post_meta($post->ID, '_malisafi_setting', true);
        $city = get_post_meta($post->ID, '_malisafi_city', true);
        $latitude = get_post_meta($post->ID, '_malisafi_latitude', true);
        $longitude = get_post_meta($post->ID, '_malisafi_longitude', true);
        ?>
        <div class="malisafi-meta-fields">
            <p>
                <label for="malisafi_project_county"><?php esc_html_e('County:', 'malisafi-mls'); ?></label>
                <select id="malisafi_project_county" name="malisafi_project_county" class="widefat">
                    <option value=""><?php esc_html_e('Select County', 'malisafi-mls'); ?></option>
                    <?php if (function_exists('malisafi_get_kenya_counties')) : ?>
                        <?php foreach (malisafi_get_kenya_counties() as $county_name) : ?>
                            <option value="<?php echo esc_attr($county_name); ?>" <?php selected($county, $county_name); ?>><?php echo esc_html($county_name); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </p>
            <p>
                <label for="malisafi_project_city"><?php esc_html_e('City/Town:', 'malisafi-mls'); ?></label>
                <input type="text" id="malisafi_project_city" name="malisafi_project_city" value="<?php echo esc_attr($city); ?>" class="widefat">
            </p>
            <p>
                <label for="malisafi_project_neighbourhood"><?php esc_html_e('Neighbourhood/Estate:', 'malisafi-mls'); ?></label>
                <input type="text" id="malisafi_project_neighbourhood" name="malisafi_project_neighbourhood" value="<?php echo esc_attr($neighbourhood); ?>" class="widefat">
            </p>
            <p>
                <label for="malisafi_project_setting"><?php esc_html_e('Setting:', 'malisafi-mls'); ?></label>
                <select id="malisafi_project_setting" name="malisafi_project_setting" class="widefat">
                    <option value=""><?php esc_html_e('Select Setting', 'malisafi-mls'); ?></option>
                    <option value="urban" <?php selected($setting, 'urban'); ?>><?php esc_html_e('Urban', 'malisafi-mls'); ?></option>
                    <option value="semi-rural" <?php selected($setting, 'semi-rural'); ?>><?php esc_html_e('Semi-rural', 'malisafi-mls'); ?></option>
                    <option value="rural" <?php selected($setting, 'rural'); ?>><?php esc_html_e('Rural', 'malisafi-mls'); ?></option>
                    <option value="isolated" <?php selected($setting, 'isolated'); ?>><?php esc_html_e('Isolated', 'malisafi-mls'); ?></option>
                </select>
            </p>
            <p>
                <label for="malisafi_project_latitude"><?php esc_html_e('Latitude:', 'malisafi-mls'); ?></label>
                <input type="number" step="0.000001" id="malisafi_project_latitude" name="malisafi_project_latitude" value="<?php echo esc_attr($latitude); ?>" class="widefat">
            </p>
            <p>
                <label for="malisafi_project_longitude"><?php esc_html_e('Longitude:', 'malisafi-mls'); ?></label>
                <input type="number" step="0.000001" id="malisafi_project_longitude" name="malisafi_project_longitude" value="<?php echo esc_attr($longitude); ?>" class="widefat">
            </p>
        </div>
        <?php
    }

    /**
     * Render project links meta box
     */
    public function render_project_links_meta_box($post) {
        $linked_properties = get_post_meta($post->ID, '_malisafi_project_linked_properties', true);
        if (!is_array($linked_properties)) {
            $linked_properties = $linked_properties ? (array) $linked_properties : array();
        }

        $properties_query = new \WP_Query(array(
            'post_type' => 'malisafi_property',
            'posts_per_page' => -1,
            'post_status' => array('publish', 'pending', 'draft')
        ));
        ?>
        <p><?php esc_html_e('Linked properties are synced to the project profile.', 'malisafi-mls'); ?></p>
        <label for="malisafi_project_linked_properties"><?php esc_html_e('Linked Properties', 'malisafi-mls'); ?></label>
        <select id="malisafi_project_linked_properties" name="malisafi_project_linked_properties[]" multiple class="widefat" size="8">
            <?php if ($properties_query->have_posts()) : ?>
                <?php while ($properties_query->have_posts()) : $properties_query->the_post(); ?>
                    <option value="<?php echo esc_attr(get_the_ID()); ?>" <?php selected(in_array(get_the_ID(), $linked_properties, true)); ?>>
                        <?php echo esc_html(get_the_title()); ?>
                    </option>
                <?php endwhile; ?>
            <?php endif; ?>
        </select>
        <?php
        wp_reset_postdata();

        if (!empty($linked_properties)) :
            ?>
            <div style="margin-top: 10px;">
                <strong><?php esc_html_e('Linked Properties Debug:', 'malisafi-mls'); ?></strong>
                <ul>
                    <?php foreach ($linked_properties as $property_id) : ?>
                        <?php $property_id = (int) $property_id; ?>
                        <li>
                            <a href="<?php echo esc_url(get_edit_post_link($property_id)); ?>">
                                <?php echo esc_html(get_the_title($property_id)); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php
    }

    /**
     * Save project meta
     */
    public function save_project_meta($post_id, $post) {
        if (!isset($_POST['malisafi_project_details_nonce']) || !wp_verify_nonce($_POST['malisafi_project_details_nonce'], 'malisafi_project_details')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if ($post->post_type !== 'malisafi_project') {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $project_type = isset($_POST['malisafi_project_type']) ? sanitize_text_field($_POST['malisafi_project_type']) : '';
        $project_category = isset($_POST['malisafi_project_category']) ? sanitize_text_field($_POST['malisafi_project_category']) : '';
        $project_subcategory = isset($_POST['malisafi_project_subcategory']) ? sanitize_text_field($_POST['malisafi_project_subcategory']) : '';
        $timeline = isset($_POST['malisafi_project_timeline']) ? sanitize_textarea_field($_POST['malisafi_project_timeline']) : '';
        $milestones_raw = isset($_POST['malisafi_project_milestones']) ? sanitize_textarea_field($_POST['malisafi_project_milestones']) : '';
        $milestones = self::parse_milestones($milestones_raw);
        $investor_highlights = isset($_POST['malisafi_project_investor_highlights']) ? sanitize_textarea_field($_POST['malisafi_project_investor_highlights']) : '';
        $client_highlights = isset($_POST['malisafi_project_client_highlights']) ? sanitize_textarea_field($_POST['malisafi_project_client_highlights']) : '';
        $auto_sync = isset($_POST['malisafi_project_auto_sync']) ? '1' : '0';

        update_post_meta($post_id, '_malisafi_project_type', $project_type);
        update_post_meta($post_id, '_malisafi_project_category', $project_category);
        update_post_meta($post_id, '_malisafi_project_subcategory', $project_subcategory);
        update_post_meta($post_id, '_malisafi_project_timeline', $timeline);
        update_post_meta($post_id, '_malisafi_project_milestones', $milestones);
        update_post_meta($post_id, '_malisafi_project_investor_highlights', $investor_highlights);
        update_post_meta($post_id, '_malisafi_project_client_highlights', $client_highlights);
        update_post_meta($post_id, '_malisafi_project_auto_sync', $auto_sync);

        $county = isset($_POST['malisafi_project_county']) ? sanitize_text_field($_POST['malisafi_project_county']) : '';
        $neighbourhood = isset($_POST['malisafi_project_neighbourhood']) ? sanitize_text_field($_POST['malisafi_project_neighbourhood']) : '';
        $setting = isset($_POST['malisafi_project_setting']) ? sanitize_text_field($_POST['malisafi_project_setting']) : '';
        $city = isset($_POST['malisafi_project_city']) ? sanitize_text_field($_POST['malisafi_project_city']) : '';
        $latitude = isset($_POST['malisafi_project_latitude']) ? sanitize_text_field($_POST['malisafi_project_latitude']) : '';
        $longitude = isset($_POST['malisafi_project_longitude']) ? sanitize_text_field($_POST['malisafi_project_longitude']) : '';

        update_post_meta($post_id, '_malisafi_country', 'Kenya');
        update_post_meta($post_id, '_malisafi_county', $county);
        update_post_meta($post_id, '_malisafi_neighbourhood', $neighbourhood);
        update_post_meta($post_id, '_malisafi_setting', $setting);
        update_post_meta($post_id, '_malisafi_city', $city);
        update_post_meta($post_id, '_malisafi_latitude', $latitude);
        update_post_meta($post_id, '_malisafi_longitude', $longitude);

        $linked_properties = isset($_POST['malisafi_project_linked_properties']) && is_array($_POST['malisafi_project_linked_properties'])
            ? array_map('intval', $_POST['malisafi_project_linked_properties'])
            : array();

        self::sync_project_linked_properties($post_id, $linked_properties, $auto_sync === '1');

        if (!empty($_FILES['malisafi_project_brochure']['name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';

            $attachment_id = media_handle_upload('malisafi_project_brochure', $post_id);
            if (!is_wp_error($attachment_id)) {
                update_post_meta($post_id, '_malisafi_project_brochure_id', $attachment_id);
            }
        }
    }

    /**
     * Sync linked properties for a project
     */
    public static function sync_project_linked_properties($project_id, array $linked_ids, $auto_sync = true) {
        $linked_ids = array_values(array_unique(array_filter($linked_ids)));

        $previous_linked = get_post_meta($project_id, '_malisafi_project_linked_properties', true);
        if (!is_array($previous_linked)) {
            $previous_linked = $previous_linked ? (array) $previous_linked : array();
        }

        update_post_meta($project_id, '_malisafi_project_linked_properties', $linked_ids);

        $removed = array_diff($previous_linked, $linked_ids);
        $added = array_diff($linked_ids, $previous_linked);

        foreach ($removed as $property_id) {
            $property_id = (int) $property_id;
            $projects = get_post_meta($property_id, '_malisafi_project_ids', true);
            if (!is_array($projects)) {
                $projects = $projects ? (array) $projects : array();
            }
            $projects = array_diff($projects, array($project_id));
            update_post_meta($property_id, '_malisafi_project_ids', array_values($projects));
        }

        foreach ($added as $property_id) {
            $property_id = (int) $property_id;
            $projects = get_post_meta($property_id, '_malisafi_project_ids', true);
            if (!is_array($projects)) {
                $projects = $projects ? (array) $projects : array();
            }
            $projects[] = $project_id;
            update_post_meta($property_id, '_malisafi_project_ids', array_values(array_unique($projects)));
        }

        if ($auto_sync) {
            $snapshot = array();
            $prices = array();
            foreach ($linked_ids as $property_id) {
                $property_id = (int) $property_id;
                $price = get_post_meta($property_id, '_malisafi_price', true);
                $snapshot[] = array(
                    'id' => $property_id,
                    'title' => get_the_title($property_id),
                    'price' => $price,
                    'currency' => get_post_meta($property_id, '_malisafi_currency', true),
                    'bedrooms' => get_post_meta($property_id, '_malisafi_bedrooms', true),
                    'bathrooms' => get_post_meta($property_id, '_malisafi_bathrooms', true),
                    'area' => get_post_meta($property_id, '_malisafi_area', true),
                    'thumbnail_id' => get_post_thumbnail_id($property_id),
                    'gallery_ids' => get_post_meta($property_id, '_malisafi_gallery_ids', true),
                );
                if ($price !== '') {
                    $prices[] = (float) $price;
                }
            }
            update_post_meta($project_id, '_malisafi_project_properties_snapshot', $snapshot);
            update_post_meta($project_id, '_malisafi_project_units_count', count($linked_ids));
            if (!empty($prices)) {
                update_post_meta($project_id, '_malisafi_project_min_price', min($prices));
                update_post_meta($project_id, '_malisafi_project_max_price', max($prices));
            }
        }
    }

    /**
     * Parse milestones from text input
     */
    private static function parse_milestones($raw) {
        $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $raw)));
        $items = array();

        foreach ($lines as $line) {
            $parts = array_map('trim', explode('|', $line));
            $date = isset($parts[0]) ? $parts[0] : '';
            $title = isset($parts[1]) ? $parts[1] : '';
            $status = isset($parts[2]) ? $parts[2] : '';
            $percent = isset($parts[3]) ? $parts[3] : '';

            if ($title === '' && count($parts) === 1) {
                $title = $parts[0];
                $date = '';
            }

            $items[] = array(
                'date' => $date,
                'title' => $title,
                'status' => $status,
                'percent' => $percent,
                'raw' => $line
            );
        }

        return $items;
    }

    /**
     * Format milestones for textarea
     */
    private static function format_milestones_for_textarea($milestones) {
        $lines = array();
        foreach ($milestones as $milestone) {
            if (is_array($milestone) && (isset($milestone['date']) || isset($milestone['title']))) {
                $date = isset($milestone['date']) ? $milestone['date'] : '';
                $title = isset($milestone['title']) ? $milestone['title'] : '';
                $status = isset($milestone['status']) ? $milestone['status'] : '';
                $percent = isset($milestone['percent']) ? $milestone['percent'] : '';
                $line = trim($date . ' | ' . $title . ' | ' . $status . ' | ' . $percent);
                $lines[] = trim($line, " | ");
            } else {
                $lines[] = (string) $milestone;
            }
        }

        return implode("\n", array_filter($lines));
    }
    
    /**
     * Render property details meta box
     */
    public function render_property_details_meta_box($post) {
        wp_nonce_field('malisafi_property_details', 'malisafi_property_details_nonce');
        
        $bedrooms = get_post_meta($post->ID, '_malisafi_bedrooms', true);
        $bathrooms = get_post_meta($post->ID, '_malisafi_bathrooms', true);
        $area = get_post_meta($post->ID, '_malisafi_area', true);
        $lot_size = get_post_meta($post->ID, '_malisafi_lot_size', true);
        $year_built = get_post_meta($post->ID, '_malisafi_year_built', true);
        $garage = get_post_meta($post->ID, '_malisafi_garage', true);
        $property_id = get_post_meta($post->ID, '_malisafi_property_id', true);
        ?>
        <div class="malisafi-meta-fields">
            <p>
                <label for="malisafi_property_id"><?php esc_html_e('Property ID (MLS #):', 'malisafi-mls'); ?></label>
                <input type="text" id="malisafi_property_id" name="malisafi_property_id" value="<?php echo esc_attr($property_id); ?>" class="widefat">
            </p>
            <p>
                <label for="malisafi_bedrooms"><?php esc_html_e('Bedrooms:', 'malisafi-mls'); ?></label>
                <input type="number" id="malisafi_bedrooms" name="malisafi_bedrooms" value="<?php echo esc_attr($bedrooms); ?>" min="0" step="1">
            </p>
            <p>
                <label for="malisafi_bathrooms"><?php esc_html_e('Bathrooms:', 'malisafi-mls'); ?></label>
                <input type="number" id="malisafi_bathrooms" name="malisafi_bathrooms" value="<?php echo esc_attr($bathrooms); ?>" min="0" step="0.5">
            </p>
            <p>
                <label for="malisafi_area"><?php esc_html_e('Area (sq ft):', 'malisafi-mls'); ?></label>
                <input type="number" id="malisafi_area" name="malisafi_area" value="<?php echo esc_attr($area); ?>" min="0">
            </p>
            <p>
                <label for="malisafi_lot_size"><?php esc_html_e('Lot Size (sq ft):', 'malisafi-mls'); ?></label>
                <input type="number" id="malisafi_lot_size" name="malisafi_lot_size" value="<?php echo esc_attr($lot_size); ?>" min="0">
            </p>
            <p>
                <label for="malisafi_year_built"><?php esc_html_e('Year Built:', 'malisafi-mls'); ?></label>
                <input type="number" id="malisafi_year_built" name="malisafi_year_built" value="<?php echo esc_attr($year_built); ?>" min="1800" max="<?php echo date('Y') + 5; ?>">
            </p>
            <p>
                <label for="malisafi_garage"><?php esc_html_e('Garage Spaces:', 'malisafi-mls'); ?></label>
                <input type="number" id="malisafi_garage" name="malisafi_garage" value="<?php echo esc_attr($garage); ?>" min="0">
            </p>
        </div>
        <?php
    }
    
    /**
     * Render pricing meta box
     */
    public function render_pricing_meta_box($post) {
        wp_nonce_field('malisafi_property_pricing', 'malisafi_property_pricing_nonce');
        
        $price = get_post_meta($post->ID, '_malisafi_price', true);
        $currency = get_post_meta($post->ID, '_malisafi_currency', true);
        if (empty($currency)) {
            $currency = 'USD'; // Default currency
        }
        $is_featured = get_post_meta($post->ID, '_malisafi_featured', true);
        ?>
        <div style="display: flex; gap: 15px; align-items: flex-start;">
            <p style="flex: 1;">
                <label for="malisafi_price"><?php esc_html_e('Price:', 'malisafi-mls'); ?></label>
                <input type="number" id="malisafi_price" name="malisafi_price" value="<?php echo esc_attr($price); ?>" min="0" class="widefat" step="0.01">
            </p>
            <p style="flex: 0 0 120px;">
                <label for="malisafi_currency"><?php esc_html_e('Currency:', 'malisafi-mls'); ?></label>
                <select id="malisafi_currency" name="malisafi_currency" class="widefat">
                    <option value="USD" <?php selected($currency, 'USD'); ?>><?php esc_html_e('USD ($)', 'malisafi-mls'); ?></option>
                    <option value="KES" <?php selected($currency, 'KES'); ?>><?php esc_html_e('KES (KSh)', 'malisafi-mls'); ?></option>
                </select>
            </p>
        </div>
        <p>
            <label>
                <input type="checkbox" name="malisafi_featured" value="1" <?php checked($is_featured, '1'); ?>>
                <?php esc_html_e('Featured Property', 'malisafi-mls'); ?>
            </label>
        </p>
        <?php
    }
    
    /**
     * Render location meta box
     */
    public function render_location_meta_box($post) {
        wp_nonce_field('malisafi_property_location', 'malisafi_property_location_nonce');
        
        $address = get_post_meta($post->ID, '_malisafi_address', true);
        $city = get_post_meta($post->ID, '_malisafi_city', true);
        $state = get_post_meta($post->ID, '_malisafi_state', true);
        $zip = get_post_meta($post->ID, '_malisafi_zip', true);
        $country = get_post_meta($post->ID, '_malisafi_country', true);
        $latitude = get_post_meta($post->ID, '_malisafi_latitude', true);
        $longitude = get_post_meta($post->ID, '_malisafi_longitude', true);
        ?>
        <div class="notice notice-info inline" style="margin: 10px 0; padding: 10px;">
            <p><strong><span class="dashicons dashicons-info" style="color: #2271b1;"></span> <?php esc_html_e('Privacy Notice:', 'malisafi-mls'); ?></strong></p>
            <p><?php esc_html_e('The exact street address is optional and will not be shown to clients on the public website. Only the city and general area will be displayed for privacy and security reasons.', 'malisafi-mls'); ?></p>
        </div>
        <p>
            <label for="malisafi_address"><?php esc_html_e('Street Address (Optional - For Internal Use Only):', 'malisafi-mls'); ?></label>
            <input type="text" id="malisafi_address" name="malisafi_address" value="<?php echo esc_attr($address); ?>" class="widefat" placeholder="<?php esc_attr_e('e.g., 123 Main Street', 'malisafi-mls'); ?>">
            <small class="description"><?php esc_html_e('This address will remain private and is only for your records.', 'malisafi-mls'); ?></small>
        </p>
        <p>
            <label for="malisafi_city"><?php esc_html_e('City:', 'malisafi-mls'); ?></label>
            <input type="text" id="malisafi_city" name="malisafi_city" value="<?php echo esc_attr($city); ?>" class="widefat">
        </p>
        <p>
            <label for="malisafi_state"><?php esc_html_e('State/Province:', 'malisafi-mls'); ?></label>
            <input type="text" id="malisafi_state" name="malisafi_state" value="<?php echo esc_attr($state); ?>" class="widefat">
        </p>
        <p>
            <label for="malisafi_zip"><?php esc_html_e('ZIP/Postal Code:', 'malisafi-mls'); ?></label>
            <input type="text" id="malisafi_zip" name="malisafi_zip" value="<?php echo esc_attr($zip); ?>" class="widefat">
        </p>
        <p>
            <label for="malisafi_country"><?php esc_html_e('Country:', 'malisafi-mls'); ?></label>
            <input type="text" id="malisafi_country" name="malisafi_country" value="<?php echo esc_attr($country); ?>" class="widefat">
        </p>
        <hr>
        <p>
            <label for="malisafi_latitude"><?php esc_html_e('Latitude:', 'malisafi-mls'); ?></label>
            <input type="text" id="malisafi_latitude" name="malisafi_latitude" value="<?php echo esc_attr($latitude); ?>" class="widefat">
        </p>
        <p>
            <label for="malisafi_longitude"><?php esc_html_e('Longitude:', 'malisafi-mls'); ?></label>
            <input type="text" id="malisafi_longitude" name="malisafi_longitude" value="<?php echo esc_attr($longitude); ?>" class="widefat">
        </p>
        <?php
    }
    
    /**
     * Render agent meta box
     */
    public function render_agent_meta_box($post) {
        wp_nonce_field('malisafi_property_agent', 'malisafi_property_agent_nonce');
        
        $agent_id = get_post_meta($post->ID, '_malisafi_agent_id', true);
        $agent_name = get_post_meta($post->ID, '_malisafi_agent_name', true);
        $agent_email = get_post_meta($post->ID, '_malisafi_agent_email', true);
        $agent_phone = get_post_meta($post->ID, '_malisafi_agent_phone', true);
        
        // Get all agents
        $agents = get_posts(array(
            'post_type' => 'malisafi_agent',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_status' => 'publish'
        ));
        ?>
        <p>
            <label for="property_agent_id"><strong><?php esc_html_e('Assigned Agent:', 'malisafi-mls'); ?></strong></label>
            <select name="property_agent_id" id="property_agent_id" class="widefat">
                <option value=""><?php esc_html_e('Select Agent Profile...', 'malisafi-mls'); ?></option>
                <?php foreach ($agents as $agent): ?>
                    <option value="<?php echo esc_attr($agent->ID); ?>" <?php selected($agent_id, $agent->ID); ?>>
                        <?php echo esc_html($agent->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small><?php esc_html_e('Link this property to an agent profile', 'malisafi-mls'); ?></small>
        </p>
        
        <hr>
        <p><em><?php esc_html_e('Or enter agent information manually (legacy):', 'malisafi-mls'); ?></em></p>
        
        <p>
            <label for="malisafi_agent_name"><?php esc_html_e('Agent Name:', 'malisafi-mls'); ?></label>
            <input type="text" id="malisafi_agent_name" name="malisafi_agent_name" value="<?php echo esc_attr($agent_name); ?>" class="widefat">
        </p>
        <p>
            <label for="malisafi_agent_email"><?php esc_html_e('Agent Email:', 'malisafi-mls'); ?></label>
            <input type="email" id="malisafi_agent_email" name="malisafi_agent_email" value="<?php echo esc_attr($agent_email); ?>" class="widefat">
        </p>
        <p>
            <label for="malisafi_agent_phone"><?php esc_html_e('Agent Phone:', 'malisafi-mls'); ?></label>
            <input type="tel" id="malisafi_agent_phone" name="malisafi_agent_phone" value="<?php echo esc_attr($agent_phone); ?>" class="widefat">
        </p>
        <?php
    }
    
    /**
     * Render gallery meta box
     */
    public function render_gallery_meta_box($post) {
        wp_nonce_field('malisafi_property_gallery', 'malisafi_property_gallery_nonce');
        
        $gallery_ids = get_post_meta($post->ID, '_malisafi_gallery_ids', true);
        $gallery_ids = !empty($gallery_ids) ? explode(',', $gallery_ids) : array();
        ?>
        <div class="malisafi-gallery-container">
            <div class="notice notice-info inline" style="margin: 10px 0 15px 0; padding: 10px;">
                <p><strong><span class="dashicons dashicons-camera"></span> <?php esc_html_e('Photo Gallery', 'malisafi-mls'); ?></strong></p>
                <p><?php esc_html_e('Upload multiple photos of the property. The first photo will be used as the featured image. Recommended size: 1200x800px or larger.', 'malisafi-mls'); ?></p>
            </div>
            
            <div class="malisafi-gallery-images" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
                <?php if (!empty($gallery_ids)) : ?>
                    <?php foreach ($gallery_ids as $image_id) : ?>
                        <?php if (wp_attachment_is_image($image_id)) : ?>
                            <div class="malisafi-gallery-image" data-image-id="<?php echo esc_attr($image_id); ?>" style="position: relative; width: 150px; height: 150px; border: 2px solid #ddd; border-radius: 4px; overflow: hidden;">
                                <?php echo wp_get_attachment_image($image_id, 'thumbnail', false, array('style' => 'width: 100%; height: 100%; object-fit: cover;')); ?>
                                <button type="button" class="malisafi-remove-image" style="position: absolute; top: 5px; right: 5px; background: #dc3232; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: bold;">×</button>
                                <input type="hidden" name="malisafi_gallery_ids[]" value="<?php echo esc_attr($image_id); ?>">
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <p>
                <button type="button" class="button button-primary button-large malisafi-upload-gallery" style="margin-right: 10px;">
                    <span class="dashicons dashicons-upload" style="margin-top: 3px;"></span>
                    <?php esc_html_e('Add Photos', 'malisafi-mls'); ?>
                </button>
                <button type="button" class="button button-secondary malisafi-clear-gallery">
                    <span class="dashicons dashicons-trash"></span>
                    <?php esc_html_e('Clear All', 'malisafi-mls'); ?>
                </button>
            </p>
            
            <p class="description">
                <?php esc_html_e('Click "Add Photos" to select multiple images. Drag images to reorder them.', 'malisafi-mls'); ?>
            </p>
        </div>
        
        <style>
            .malisafi-gallery-image {
                transition: all 0.3s;
            }
            .malisafi-gallery-image:hover {
                transform: scale(1.05);
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            }
            .malisafi-remove-image {
                opacity: 0;
                transition: opacity 0.3s;
            }
            .malisafi-gallery-image:hover .malisafi-remove-image {
                opacity: 1;
            }
            .malisafi-gallery-images {
                min-height: 50px;
                background: #f9f9f9;
                padding: 10px;
                border: 2px dashed #ddd;
                border-radius: 4px;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Check if wp.media is available
            if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
                console.error('WordPress Media Library not loaded');
                $('.malisafi-upload-gallery').prop('disabled', true).text('<?php esc_html_e('Media Library not available', 'malisafi-mls'); ?>');
                return;
            }
            
            // Upload gallery
            var fileFrame;
            $('.malisafi-upload-gallery').on('click', function(e) {
                e.preventDefault();
                
                if (fileFrame) {
                    fileFrame.open();
                    return;
                }
                
                fileFrame = wp.media({
                    title: '<?php esc_html_e('Select Property Photos', 'malisafi-mls'); ?>',
                    button: {
                        text: '<?php esc_html_e('Add to Gallery', 'malisafi-mls'); ?>'
                    },
                    multiple: true,
                    library: {
                        type: 'image'
                    }
                });
                
                fileFrame.on('select', function() {
                    var attachments = fileFrame.state().get('selection').toJSON();
                    var container = $('.malisafi-gallery-images');
                    
                    attachments.forEach(function(attachment) {
                        if (attachment.type === 'image') {
                            // Check if image already exists
                            if ($('.malisafi-gallery-image[data-image-id="' + attachment.id + '"]').length > 0) {
                                return;
                            }
                            
                            var thumbUrl = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
                            var imageHtml = '<div class="malisafi-gallery-image" data-image-id="' + attachment.id + '" style="position: relative; width: 150px; height: 150px; border: 2px solid #ddd; border-radius: 4px; overflow: hidden;">' +
                                '<img src="' + thumbUrl + '" style="width: 100%; height: 100%; object-fit: cover;">' +
                                '<button type="button" class="malisafi-remove-image" style="position: absolute; top: 5px; right: 5px; background: #dc3232; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: bold;">×</button>' +
                                '<input type="hidden" name="malisafi_gallery_ids[]" value="' + attachment.id + '">' +
                                '</div>';
                            container.append(imageHtml);
                        }
                    });
                    
                    // Set first image as featured if no featured image exists
                    if (!$('#set-post-thumbnail img').length && attachments.length > 0) {
                        wp.media.featuredImage.set(attachments[0].id);
                    }
                });
                
                fileFrame.open();
            });
            
            // Remove image
            $(document).on('click', '.malisafi-remove-image', function(e) {
                e.preventDefault();
                if (confirm('<?php esc_html_e('Remove this photo from gallery?', 'malisafi-mls'); ?>')) {
                    $(this).closest('.malisafi-gallery-image').fadeOut(300, function() {
                        $(this).remove();
                    });
                }
            });
            
            // Clear all
            $('.malisafi-clear-gallery').on('click', function(e) {
                e.preventDefault();
                if (confirm('<?php esc_html_e('Remove all photos from gallery? This cannot be undone.', 'malisafi-mls'); ?>')) {
                    $('.malisafi-gallery-images').empty();
                }
            });
            
            // Make gallery sortable
            $('.malisafi-gallery-images').sortable({
                items: '.malisafi-gallery-image',
                cursor: 'move',
                opacity: 0.7,
                placeholder: 'sortable-placeholder',
                update: function(event, ui) {
                    // Gallery order updated
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Save property meta
     */
    public function save_property_meta($post_id, $post) {
        // Check if it's an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check post type
        if ('malisafi_property' !== $post->post_type) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check nonces
        $nonces = array(
            'malisafi_property_details' => 'malisafi_property_details_nonce',
            'malisafi_property_pricing' => 'malisafi_property_pricing_nonce',
            'malisafi_property_location' => 'malisafi_property_location_nonce',
            'malisafi_property_agent' => 'malisafi_property_agent_nonce',
            'malisafi_property_gallery' => 'malisafi_property_gallery_nonce'
        );
        
        foreach ($nonces as $action => $nonce_name) {
            if (!isset($_POST[$nonce_name]) || !wp_verify_nonce($_POST[$nonce_name], $action)) {
                continue;
            }
            
            switch ($action) {
                case 'malisafi_property_details':
                    $fields = array('property_id', 'bedrooms', 'bathrooms', 'area', 'lot_size', 'year_built', 'garage');
                    foreach ($fields as $field) {
                        if (isset($_POST['malisafi_' . $field])) {
                            update_post_meta($post_id, '_malisafi_' . $field, sanitize_text_field($_POST['malisafi_' . $field]));
                        }
                    }
                    break;
                    
                case 'malisafi_property_pricing':
                    if (isset($_POST['malisafi_price'])) {
                        update_post_meta($post_id, '_malisafi_price', sanitize_text_field($_POST['malisafi_price']));
                    }
                    if (isset($_POST['malisafi_currency'])) {
                        update_post_meta($post_id, '_malisafi_currency', sanitize_text_field($_POST['malisafi_currency']));
                    }
                    update_post_meta($post_id, '_malisafi_featured', isset($_POST['malisafi_featured']) ? '1' : '0');
                    break;
                    
                case 'malisafi_property_location':
                    $location_fields = array('address', 'city', 'state', 'zip', 'country', 'latitude', 'longitude');
                    foreach ($location_fields as $field) {
                        if (isset($_POST['malisafi_' . $field])) {
                            update_post_meta($post_id, '_malisafi_' . $field, sanitize_text_field($_POST['malisafi_' . $field]));
                        }
                    }
                    break;
                    
                case 'malisafi_property_agent':
                    // Save agent profile link
                    if (isset($_POST['property_agent_id'])) {
                        $agent_id = intval($_POST['property_agent_id']);
                        if ($agent_id > 0) {
                            update_post_meta($post_id, '_malisafi_agent_id', $agent_id);
                        } else {
                            delete_post_meta($post_id, '_malisafi_agent_id');
                        }
                    }
                    
                    // Save legacy agent fields
                    $agent_fields = array('agent_name', 'agent_email', 'agent_phone');
                    foreach ($agent_fields as $field) {
                        if (isset($_POST['malisafi_' . $field])) {
                            update_post_meta($post_id, '_malisafi_' . $field, sanitize_text_field($_POST['malisafi_' . $field]));
                        }
                    }
                    break;
                    
                case 'malisafi_property_gallery':
                    if (isset($_POST['malisafi_gallery_ids']) && is_array($_POST['malisafi_gallery_ids'])) {
                        $gallery_ids = array_map('intval', $_POST['malisafi_gallery_ids']);
                        update_post_meta($post_id, '_malisafi_gallery_ids', implode(',', $gallery_ids));
                        
                        // Set first image as featured image if no featured image exists
                        if (!empty($gallery_ids) && !has_post_thumbnail($post_id)) {
                            set_post_thumbnail($post_id, $gallery_ids[0]);
                        }
                    } else {
                        delete_post_meta($post_id, '_malisafi_gallery_ids');
                    }
                    break;
            }
        }
    }
    
    /**
     * Load custom template for single property page
     */
    public function load_single_property_template($template) {
        global $post;
        
        if ($post && 'malisafi_property' === $post->post_type) {
            $plugin_template = MALISAFI_MLS_PATH . 'templates/single-property.php';
            
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        return $template;
    }

    /**
     * Load custom template for single project page
     */
    public function load_single_project_template($template) {
        global $post;

        if ($post && 'malisafi_project' === $post->post_type) {
            $plugin_template = MALISAFI_MLS_PATH . 'templates/single-project.php';

            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        return $template;
    }
}