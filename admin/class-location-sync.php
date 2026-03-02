<?php
/**
 * Location sync/import admin tool
 *
 * @package MalisafiMLS
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Simple admin tool to import counties and subcounties from JSON
 */
class Malisafi_Location_Sync {

    /**
     * Initialize hooks
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_pages'));
    }

    /**
     * Add submenu pages under the Malisafi dashboard
     */
    public static function add_admin_pages() {
        // Link to taxonomy management UI
        add_submenu_page(
            'malisafi-dashboard',
            __('Locations', 'malisafi-mls'),
            __('Locations', 'malisafi-mls'),
            'manage_malisafi_settings',
            'edit-tags.php?taxonomy=malisafi_property_location&post_type=malisafi_property'
        );

        // Import/sync page
        add_submenu_page(
            'malisafi-dashboard',
            __('Import Locations', 'malisafi-mls'),
            __('Import Locations', 'malisafi-mls'),
            'manage_malisafi_settings',
            'malisafi-import-locations',
            array(__CLASS__, 'render_import_page')
        );
    }

    /**
     * Render the import page and handle POST import
     */
    public static function render_import_page() {
        if (!current_user_can('manage_malisafi_settings')) {
            wp_die(__('You do not have permission to manage locations.', 'malisafi-mls'));
        }

        $message = '';
        if (!empty($_POST['malisafi_import_locations']) && check_admin_referer('malisafi_import_locations_action', 'malisafi_import_locations_nonce')) {
            $result = self::sync_from_json();
            $message = sprintf(
                /* translators: 1=counties 2=subcounties 3=updated */
                __('Imported: %1$d counties, %2$d subcounties (%3$d parents updated).', 'malisafi-mls'),
                intval($result['counties_added']),
                intval($result['subcounties_added']),
                intval($result['subcounties_parent_updated'])
            );
        }

        echo '<div class="wrap">';
        echo '<h1>' . __('Import Kenya Locations', 'malisafi-mls') . '</h1>';
        if ($message) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
        }

        echo '<p>' . __('This tool will import counties and subcounties from the bundled JSON file and populate the Location taxonomy so administrators can edit them in the UI.', 'malisafi-mls') . '</p>';

        echo '<form method="post">';
        wp_nonce_field('malisafi_import_locations_action', 'malisafi_import_locations_nonce');
        submit_button(__('Import / Sync Locations', 'malisafi-mls'), 'primary', 'malisafi_import_locations');
        echo '</form>';

        echo '<p>' . sprintf(__('This tool attempts to fetch the JSON from the remote URL (%2$s) first. If the remote fetch fails it will fall back to the bundled file (%1$s).', 'malisafi-mls'), MALISAFI_MLS_PATH . 'data/kenya-subcounties.json', 'https://maliprime.com/wp-content/plugins/malisafi/data/kenya-subcounties.json') . '</p>';
        echo '</div>';
    }

    /**
     * Read JSON and populate taxonomy
     *
     * @return array
     */
    public static function sync_from_json() {
        $json_path = MALISAFI_MLS_PATH . 'data/kenya-subcounties.json';
        $remote_url = 'https://maliprime.com/wp-content/plugins/malisafi/data/kenya-subcounties.json';
        $result = array(
            'counties_added' => 0,
            'subcounties_added' => 0,
            'subcounties_parent_updated' => 0,
        );

        $raw = '';
        // Try remote first
        if (function_exists('wp_remote_get')) {
            $resp = wp_remote_get($remote_url, array('timeout' => 10));
            if (!is_wp_error($resp) && intval(wp_remote_retrieve_response_code($resp)) === 200) {
                $raw = wp_remote_retrieve_body($resp);
            }
        } else {
            $raw = @file_get_contents($remote_url);
        }

        // If remote failed, try bundled file
        if (empty($raw) && file_exists($json_path)) {
            $raw = file_get_contents($json_path);
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return $result;
        }

        foreach ($data as $county_name => $subcounties) {
            $county_name = trim($county_name);
            if ($county_name === '') {
                continue;
            }

            $county_term = get_term_by('name', $county_name, 'malisafi_property_location');
            if (!$county_term || is_wp_error($county_term)) {
                $insert = wp_insert_term($county_name, 'malisafi_property_location');
                if (!is_wp_error($insert) && isset($insert['term_id'])) {
                    $county_id = (int) $insert['term_id'];
                    $result['counties_added']++;
                } else {
                    // if insert failed, try to get term again
                    $county_term = get_term_by('name', $county_name, 'malisafi_property_location');
                    $county_id = $county_term ? (int) $county_term->term_id : 0;
                }
            } else {
                $county_id = (int) $county_term->term_id;
            }

            if (!$county_id) {
                continue;
            }

            if (!is_array($subcounties)) {
                continue;
            }

            foreach ($subcounties as $sub) {
                $sub_name = is_array($sub) && isset($sub['name']) ? trim($sub['name']) : (is_string($sub) ? trim($sub) : '');
                if ($sub_name === '') {
                    continue;
                }

                $existing = get_term_by('name', $sub_name, 'malisafi_property_location');
                if ($existing && !is_wp_error($existing)) {
                    // If parent differs, update parent
                    if ((int) $existing->parent !== $county_id) {
                        wp_update_term($existing->term_id, 'malisafi_property_location', array('parent' => $county_id));
                        $result['subcounties_parent_updated']++;
                    }
                    continue;
                }

                $ins = wp_insert_term($sub_name, 'malisafi_property_location', array('parent' => $county_id));
                if (!is_wp_error($ins) && isset($ins['term_id'])) {
                    $result['subcounties_added']++;
                }
            }
        }

        return $result;
    }
}
