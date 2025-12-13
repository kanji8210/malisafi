<?php
/**
 * Internationalization functionality
 *
 * @package MalisafiMLS
 */

namespace MalisafiMLS;

/**
 * I18n class
 */
class I18n {
    
    /**
     * Load plugin text domain
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'malisafi-mls',
            false,
            dirname(MALISAFI_MLS_BASENAME) . '/languages/'
        );
    }
}
