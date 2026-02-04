<?php
namespace MalisafiMLS;

/**
 * Project Submission System
 *
 * @package MalisafiMLS
 */
class Project_Submission {
    /**
     * Initialize
     */
    public static function init() {
        add_shortcode('malisafi_project_submit', array(__CLASS__, 'render_submission_form'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }

    /**
     * Enqueue assets
     */
    public static function enqueue_assets() {
        if (!is_page()) {
            return;
        }

        $post = get_post();
        if (!$post || !has_shortcode($post->post_content, 'malisafi_project_submit')) {
            return;
        }

        wp_enqueue_style(
            'malisafi-project-submit-form',
            MALISAFI_MLS_URL . 'assets/css/project-submit-form.css',
            array(),
            MALISAFI_MLS_VERSION
        );

        wp_enqueue_script(
            'malisafi-project-submit-form',
            MALISAFI_MLS_URL . 'assets/js/project-submit-form.js',
            array(),
            MALISAFI_MLS_VERSION,
            true
        );

        wp_localize_script('malisafi-project-submit-form', 'malisafiProjectGps', array(
            'i18n' => array(
                'locating' => __('Locating your position...', 'malisafi-mls'),
                'success' => __('Coordinates updated from your location.', 'malisafi-mls'),
                'parsed' => __('Coordinates extracted from the link.', 'malisafi-mls'),
                'invalid' => __('Could not detect coordinates. Paste a Google Maps link or use “lat, lng”.', 'malisafi-mls'),
                'geoUnsupported' => __('Geolocation is not supported in this browser.', 'malisafi-mls'),
                'permissionDenied' => __('Location permission was denied.', 'malisafi-mls'),
                'unavailable' => __('Location is currently unavailable.', 'malisafi-mls'),
                'timeout' => __('Location request timed out. Please try again.', 'malisafi-mls')
            )
        ));
    }

    /**
     * Render project submission form
     */
    public static function render_submission_form($atts) {
        if (!is_user_logged_in()) {
            $login_url = Page_Manager::get_page_url('login');
            if (!$login_url) {
                $login_url = wp_login_url();
            }
            return '<div class="malisafi-access-denied"><p>' . sprintf(
                __('Please <a href="%s">log in</a> to access this page.', 'malisafi-mls'),
                esc_url($login_url)
            ) . '</p></div>';
        }

        if (!current_user_can('edit_projects')) {
            return '<div class="malisafi-access-denied"><p>' . __('You do not have permission to create projects.', 'malisafi-mls') . '</p></div>';
        }

        $message = '';
        $error = '';
        $redirect_url = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['malisafi_project_submit_nonce'])) {
            if (!wp_verify_nonce($_POST['malisafi_project_submit_nonce'], 'malisafi_project_submit')) {
                $error = __('Security check failed. Please refresh the page and try again.', 'malisafi-mls');
            } else {
                $project_name = isset($_POST['project_name']) ? sanitize_text_field($_POST['project_name']) : '';
                $project_type = isset($_POST['project_type']) ? sanitize_text_field($_POST['project_type']) : '';
                $project_category = isset($_POST['project_category']) ? sanitize_text_field($_POST['project_category']) : '';
                $project_subcategory = isset($_POST['project_subcategory']) ? sanitize_text_field($_POST['project_subcategory']) : '';
                $project_city = isset($_POST['project_city']) ? sanitize_text_field($_POST['project_city']) : '';
                $project_neighbourhood = isset($_POST['project_neighbourhood']) ? sanitize_text_field($_POST['project_neighbourhood']) : '';
                $project_county = isset($_POST['project_county']) ? sanitize_text_field($_POST['project_county']) : '';
                $project_setting = isset($_POST['project_setting']) ? sanitize_text_field($_POST['project_setting']) : '';
                $project_latitude = isset($_POST['project_latitude']) ? sanitize_text_field($_POST['project_latitude']) : '';
                $project_longitude = isset($_POST['project_longitude']) ? sanitize_text_field($_POST['project_longitude']) : '';
                $project_description = isset($_POST['project_description']) ? sanitize_textarea_field($_POST['project_description']) : '';
                $project_timeline = isset($_POST['project_timeline']) ? sanitize_textarea_field($_POST['project_timeline']) : '';
                $investor_highlights = isset($_POST['investor_highlights']) ? sanitize_textarea_field($_POST['investor_highlights']) : '';
                $client_highlights = isset($_POST['client_highlights']) ? sanitize_textarea_field($_POST['client_highlights']) : '';
                $milestones_raw = isset($_POST['project_milestones']) ? sanitize_textarea_field($_POST['project_milestones']) : '';
                $milestones = self::parse_milestones($milestones_raw);
                $auto_sync = !empty($_POST['auto_sync_features']) ? '1' : '0';

                $linked_properties = isset($_POST['linked_properties']) && is_array($_POST['linked_properties'])
                    ? array_map('intval', $_POST['linked_properties'])
                    : array();

                if (empty($project_name) || empty($project_type) || empty($project_category) || empty($project_county) || empty($project_setting)) {
                    $error = __('Please fill all required fields.', 'malisafi-mls');
                } else {
                    $post_status = current_user_can('publish_projects') ? 'publish' : 'pending';
                    $post_data = array(
                        'post_title' => $project_name,
                        'post_type' => 'malisafi_project',
                        'post_status' => $post_status,
                        'post_content' => $project_description,
                        'post_author' => get_current_user_id(),
                    );

                    $project_id = wp_insert_post($post_data, true);
                    if (is_wp_error($project_id)) {
                        $error = $project_id->get_error_message();
                    } else {
                        update_post_meta($project_id, '_malisafi_project_type', $project_type);
                        update_post_meta($project_id, '_malisafi_project_category', $project_category);
                        update_post_meta($project_id, '_malisafi_project_subcategory', $project_subcategory);
                        update_post_meta($project_id, '_malisafi_project_timeline', $project_timeline);
                        update_post_meta($project_id, '_malisafi_project_investor_highlights', $investor_highlights);
                        update_post_meta($project_id, '_malisafi_project_client_highlights', $client_highlights);
                        update_post_meta($project_id, '_malisafi_project_milestones', $milestones);
                        update_post_meta($project_id, '_malisafi_project_auto_sync', $auto_sync);

                        update_post_meta($project_id, '_malisafi_country', 'Kenya');
                        update_post_meta($project_id, '_malisafi_county', $project_county);
                        update_post_meta($project_id, '_malisafi_neighbourhood', $project_neighbourhood);
                        update_post_meta($project_id, '_malisafi_setting', $project_setting);
                        update_post_meta($project_id, '_malisafi_city', $project_city);
                        update_post_meta($project_id, '_malisafi_latitude', $project_latitude);
                        update_post_meta($project_id, '_malisafi_longitude', $project_longitude);

                        if (class_exists('MalisafiMLS\\Post_Types') && method_exists('MalisafiMLS\\Post_Types', 'sync_project_linked_properties')) {
                            \MalisafiMLS\Post_Types::sync_project_linked_properties($project_id, $linked_properties, $auto_sync === '1');
                        }

                        if (!empty($_FILES['project_brochure']['name'])) {
                            require_once ABSPATH . 'wp-admin/includes/file.php';
                            require_once ABSPATH . 'wp-admin/includes/media.php';
                            require_once ABSPATH . 'wp-admin/includes/image.php';
                            $attachment_id = media_handle_upload('project_brochure', $project_id);
                            if (!is_wp_error($attachment_id)) {
                                update_post_meta($project_id, '_malisafi_project_brochure_id', $attachment_id);
                            }
                        }

                        $message = __('Project submitted successfully! It will be reviewed by a moderator.', 'malisafi-mls');
                        $redirect_url = Page_Manager::get_page_url('developer_dashboard');
                        if (!$redirect_url) {
                            $redirect_url = home_url('/');
                        }
                        echo '<script>setTimeout(function(){ window.location.href = "' . esc_url($redirect_url) . '"; }, 1800);</script>';
                    }
                }
            }
        }

        $project_types = array('Residential', 'Commercial', 'Mixed-use', 'Hospitality', 'Industrial');
        $project_categories = array('Luxury Apartments', 'Affordable Housing', 'Villas', 'Office Towers', 'Retail Mall', 'Student Housing', 'Serviced Apartments', 'Mixed Use Hub');
        $project_subcategories = array('Smart Homes', 'Serviced Apartments', 'Co-working Spaces', 'Eco-Friendly', 'Gated Community', 'Branded Residences', 'Flexible Offices');

        $properties_args = array(
            'post_type' => 'malisafi_property',
            'posts_per_page' => -1,
            'post_status' => array('publish', 'pending', 'draft')
        );

        if (!current_user_can('edit_others_projects')) {
            $properties_args['author'] = get_current_user_id();
        }

        $properties_query = new \WP_Query($properties_args);

        ob_start();
        ?>
        <div class="malisafi-project-submit">
            <?php if (!empty($message)) : ?>
                <div class="notice notice-success"><p><?php echo esc_html($message); ?></p></div>
            <?php endif; ?>
            <?php if (!empty($error)) : ?>
                <div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" autocomplete="off">
                <?php wp_nonce_field('malisafi_project_submit', 'malisafi_project_submit_nonce'); ?>

                <div class="form-section">
                    <div class="form-section-header">
                        <h3 class="form-section-title"><?php _e('Create New Project', 'malisafi-mls'); ?></h3>
                        <p class="form-section-description"><?php _e('Provide core details about your development project.', 'malisafi-mls'); ?></p>
                    </div>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="project_name"><?php _e('Project Name', 'malisafi-mls'); ?> <span class="required">*</span></label>
                            <input type="text" name="project_name" id="project_name" required placeholder="<?php esc_attr_e('e.g. Sunset Apartments', 'malisafi-mls'); ?>" />
                        </div>
                    </div>

                    <div class="form-row three-col">
                        <div class="form-group">
                            <label for="project_type"><?php _e('Project Type', 'malisafi-mls'); ?> <span class="required">*</span></label>
                            <select name="project_type" id="project_type" required>
                                <option value=""><?php _e('Select Type', 'malisafi-mls'); ?></option>
                                <?php foreach ($project_types as $type) : ?>
                                    <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($type); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="project_category"><?php _e('Category', 'malisafi-mls'); ?> <span class="required">*</span></label>
                            <select name="project_category" id="project_category" required>
                                <option value=""><?php _e('Select Category', 'malisafi-mls'); ?></option>
                                <?php foreach ($project_categories as $category) : ?>
                                    <option value="<?php echo esc_attr($category); ?>"><?php echo esc_html($category); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="project_subcategory"><?php _e('Subcategory', 'malisafi-mls'); ?></label>
                            <select name="project_subcategory" id="project_subcategory">
                                <option value=""><?php _e('Select Subcategory', 'malisafi-mls'); ?></option>
                                <?php foreach ($project_subcategories as $subcategory) : ?>
                                    <option value="<?php echo esc_attr($subcategory); ?>"><?php echo esc_html($subcategory); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-header">
                        <h3 class="form-section-title"><?php _e('Location', 'malisafi-mls'); ?></h3>
                        <p class="form-section-description"><?php _e('Use Kenya-specific location fields for accuracy.', 'malisafi-mls'); ?></p>
                    </div>

                    <div class="form-row three-col">
                        <div class="form-group">
                            <label for="project_county"><?php _e('County', 'malisafi-mls'); ?> <span class="required">*</span></label>
                            <select name="project_county" id="project_county" required>
                                <option value=""><?php _e('Select County', 'malisafi-mls'); ?></option>
                                <?php
                                if (function_exists('malisafi_get_kenya_counties')) {
                                    foreach (malisafi_get_kenya_counties() as $county) {
                                        echo '<option value="' . esc_attr($county) . '">' . esc_html($county) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="project_city"><?php _e('City/Town', 'malisafi-mls'); ?></label>
                            <input type="text" name="project_city" id="project_city" placeholder="<?php esc_attr_e('e.g. Nairobi', 'malisafi-mls'); ?>" />
                        </div>
                        <div class="form-group">
                            <label for="project_neighbourhood"><?php _e('Neighbourhood/Estate', 'malisafi-mls'); ?></label>
                            <input type="text" name="project_neighbourhood" id="project_neighbourhood" placeholder="<?php esc_attr_e('e.g. Westlands', 'malisafi-mls'); ?>" />
                        </div>
                    </div>

                    <div class="form-row two-col">
                        <div class="form-group">
                            <label for="project_setting"><?php _e('Setting', 'malisafi-mls'); ?> <span class="required">*</span></label>
                            <select name="project_setting" id="project_setting" required>
                                <option value=""><?php _e('Select Setting', 'malisafi-mls'); ?></option>
                                <option value="urban"><?php _e('Urban', 'malisafi-mls'); ?></option>
                                <option value="semi-rural"><?php _e('Semi-rural', 'malisafi-mls'); ?></option>
                                <option value="rural"><?php _e('Rural', 'malisafi-mls'); ?></option>
                                <option value="isolated"><?php _e('Isolated', 'malisafi-mls'); ?></option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="project_latitude"><?php _e('Latitude', 'malisafi-mls'); ?></label>
                            <input type="number" name="project_latitude" id="project_latitude" step="0.000001" placeholder="<?php esc_attr_e('e.g. -1.286389', 'malisafi-mls'); ?>" />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="project_longitude"><?php _e('Longitude', 'malisafi-mls'); ?></label>
                            <input type="number" name="project_longitude" id="project_longitude" step="0.000001" placeholder="<?php esc_attr_e('e.g. 36.816666', 'malisafi-mls'); ?>" />
                        </div>
                    </div>

                    <div class="form-row two-col">
                        <div class="form-group">
                            <label for="project_gps_source"><?php _e('Coordinate Helper', 'malisafi-mls'); ?></label>
                            <input type="text" name="project_gps_source" id="project_gps_source" placeholder="<?php esc_attr_e('Paste Google Maps link or -1.2921, 36.8219', 'malisafi-mls'); ?>" />
                            <small><?php _e('We will extract coordinates and fill the latitude/longitude fields.', 'malisafi-mls'); ?></small>
                        </div>
                        <div class="form-group gps-actions">
                            <label class="gps-actions-label"><?php _e('Use My Location', 'malisafi-mls'); ?></label>
                            <button type="button" id="project_get_location" class="button button-secondary">📍 <?php _e('Use My Location', 'malisafi-mls'); ?></button>
                            <div id="project_gps_status" class="gps-status" role="status" aria-live="polite"></div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-header">
                        <h3 class="form-section-title"><?php _e('Project Overview', 'malisafi-mls'); ?></h3>
                    </div>
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="project_description"><?php _e('Description', 'malisafi-mls'); ?></label>
                            <textarea name="project_description" id="project_description" rows="4" placeholder="<?php esc_attr_e('Brief overview of project vision, amenities, and highlights', 'malisafi-mls'); ?>"></textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="project_timeline"><?php _e('Timeline & Milestones', 'malisafi-mls'); ?></label>
                            <textarea name="project_timeline" id="project_timeline" rows="3" placeholder="<?php esc_attr_e('Construction start, expected completion, delivery milestones', 'malisafi-mls'); ?>"></textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="project_milestones"><?php _e('Milestones (one per line)', 'malisafi-mls'); ?></label>
                            <textarea name="project_milestones" id="project_milestones" rows="3" placeholder="<?php esc_attr_e('YYYY-MM-DD | Title | Status | %', 'malisafi-mls'); ?>"></textarea>
                            <small><?php _e('Format: YYYY-MM-DD | Intitulé | Statut | %', 'malisafi-mls'); ?></small>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-header">
                        <h3 class="form-section-title"><?php _e('Investor & Client Highlights', 'malisafi-mls'); ?></h3>
                    </div>
                    <div class="form-row two-col">
                        <div class="form-group">
                            <label for="investor_highlights"><?php _e('Investor Highlights', 'malisafi-mls'); ?></label>
                            <textarea name="investor_highlights" id="investor_highlights" rows="4" placeholder="<?php esc_attr_e('ROI %, rental yield, appreciation potential', 'malisafi-mls'); ?>"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="client_highlights"><?php _e('Client Highlights', 'malisafi-mls'); ?></label>
                            <textarea name="client_highlights" id="client_highlights" rows="4" placeholder="<?php esc_attr_e('Payment plans, amenities, lifestyle features', 'malisafi-mls'); ?>"></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-header">
                        <h3 class="form-section-title"><?php _e('Project Brochure', 'malisafi-mls'); ?></h3>
                        <p class="form-section-description"><?php _e('Upload a PDF brochure for investors and clients.', 'malisafi-mls'); ?></p>
                    </div>
                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="project_brochure"><?php _e('Brochure (PDF)', 'malisafi-mls'); ?></label>
                            <input type="file" name="project_brochure" id="project_brochure" accept="application/pdf" />
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-header">
                        <h3 class="form-section-title"><?php _e('Link Properties', 'malisafi-mls'); ?></h3>
                        <p class="form-section-description"><?php _e('Select properties in the same city or neighbourhood and link multiple units.', 'malisafi-mls'); ?></p>
                    </div>

                    <div class="form-row two-col">
                        <div class="form-group">
                            <label for="property_filter_county"><?php _e('Filter by County', 'malisafi-mls'); ?></label>
                            <select id="property_filter_county">
                                <option value=""><?php _e('All Counties', 'malisafi-mls'); ?></option>
                                <?php
                                if (function_exists('malisafi_get_kenya_counties')) {
                                    foreach (malisafi_get_kenya_counties() as $county) {
                                        echo '<option value="' . esc_attr($county) . '">' . esc_html($county) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="property_filter_search"><?php _e('Search by Location', 'malisafi-mls'); ?></label>
                            <input type="text" id="property_filter_search" placeholder="<?php esc_attr_e('Type city or neighbourhood', 'malisafi-mls'); ?>" />
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group full-width">
                            <label for="linked_properties"><?php _e('Link Multiple Units', 'malisafi-mls'); ?></label>
                            <select id="linked_properties" name="linked_properties[]" multiple size="8">
                                <?php if ($properties_query->have_posts()) : ?>
                                    <?php while ($properties_query->have_posts()) : $properties_query->the_post(); ?>
                                        <?php
                                        $property_id = get_the_ID();
                                        $property_county = get_post_meta($property_id, '_malisafi_county', true);
                                        $property_neighbourhood = get_post_meta($property_id, '_malisafi_neighbourhood', true);
                                        $property_city = get_post_meta($property_id, '_malisafi_city', true);
                                        ?>
                                        <option value="<?php echo esc_attr($property_id); ?>"
                                            data-county="<?php echo esc_attr($property_county); ?>"
                                            data-neighbourhood="<?php echo esc_attr($property_neighbourhood); ?>"
                                            data-city="<?php echo esc_attr($property_city); ?>">
                                            <?php echo esc_html(get_the_title()); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                            <small><?php _e('Hold Ctrl/Command to select multiple units.', 'malisafi-mls'); ?></small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="auto_sync_features" value="1" checked />
                                <?php _e('Auto-sync property details (price, size, images) into project profile', 'malisafi-mls'); ?>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-yes"></span>
                        <?php _e('Submit Project', 'malisafi-mls'); ?>
                    </button>
                </div>
            </form>
        </div>

        <script>
        (function() {
            var countyFilter = document.getElementById('property_filter_county');
            var searchFilter = document.getElementById('property_filter_search');
            var propertiesSelect = document.getElementById('linked_properties');

            if (!countyFilter || !searchFilter || !propertiesSelect) {
                return;
            }

            function normalize(text) {
                return (text || '').toLowerCase();
            }

            function filterOptions() {
                var county = normalize(countyFilter.value);
                var search = normalize(searchFilter.value);

                Array.prototype.forEach.call(propertiesSelect.options, function(option) {
                    var optionCounty = normalize(option.getAttribute('data-county'));
                    var optionCity = normalize(option.getAttribute('data-city'));
                    var optionNeighbourhood = normalize(option.getAttribute('data-neighbourhood'));

                    var matchesCounty = !county || optionCounty === county;
                    var matchesSearch = !search || optionCity.indexOf(search) !== -1 || optionNeighbourhood.indexOf(search) !== -1;

                    option.hidden = !(matchesCounty && matchesSearch);
                });
            }

            countyFilter.addEventListener('change', filterOptions);
            searchFilter.addEventListener('input', filterOptions);
        })();
        </script>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

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
}
