<?php
namespace MalisafiMLS;

if (!defined('ABSPATH')) exit;

class Reference_ID {
    /**
     * Ensure a reference ID exists for a property
     */
    public static function ensure($post_id, $post) {
        // Only for our post type and not autosaves/revisions
        if ($post->post_type !== 'malisafi_property') {
            return;
        }
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        // Skip on bulk/quick edits without capability
        if (! current_user_can('edit_post', $post_id)) {
            return;
        }
        $existing = get_post_meta($post_id, '_malisafi_reference_id', true);
        if (empty($existing)) {
            $ref = 'PROP-' . gmdate('Ymd') . '-' . $post_id;
            update_post_meta($post_id, '_malisafi_reference_id', $ref);
        }
    }
}
