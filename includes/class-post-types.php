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
        
        // Add template filter for single property page
        add_filter('single_template', array($this, 'load_single_property_template'));
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;
        
        if (("post.php" === $hook || "post-new.php" === $hook) && 'malisafi_property' === $post_type) {
            // Prevent access to classic editor for malisafi_property
            $redirect_url = add_query_arg('mls_no_editor', '1', admin_url('edit.php?post_type=malisafi_property'));
            wp_redirect($redirect_url);
            exit;
        }

        // Show admin notice if redirected
        if (isset($_GET['mls_no_editor']) && '1' === $_GET['mls_no_editor']) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning is-dismissible"><p><strong>Malisafi MLS :</strong> L\'édition classique est désactivée pour les propriétés. Utilisez le formulaire dédié du plugin pour créer ou modifier une propriété.</p></div>';
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
        
        $agent_id = get_post_meta($post->ID, '_property_agent_id', true);
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
                            update_post_meta($post_id, '_property_agent_id', $agent_id);
                        } else {
                            delete_post_meta($post_id, '_property_agent_id');
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
}