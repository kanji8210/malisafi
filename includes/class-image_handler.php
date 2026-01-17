<?php
/**
 * Image handling utilities
 *
 * @package MalisafiMLS
 */

namespace MalisafiMLS;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Centralized image upload/delete helper
 */
class Image_Handler {
    /** @var array Allowed MIME types */
    private const ALLOWED_TYPES = array('image/jpeg', 'image/png', 'image/webp');

    /** @var int Max upload size (10MB) */
    private const MAX_SIZE = 10485760;

    /** @var array Minimum dimensions for landscape images */
    private const MIN_LANDSCAPE = array('width' => 1200, 'height' => 800);

    /** @var array Minimum dimensions for portrait images */
    private const MIN_PORTRAIT = array('width' => 1600, 'height' => 2000);

    /**
     * Upload a single image
     *
     * @param array $file   Single file array from $_FILES
     * @param array $options Optional overrides: allowed_types, max_size, validate_dimensions, size_map
     * @return array|\WP_Error
     */
    public static function upload_single($file, $options = array()) {
        $opts = self::merge_options($options, array(
            'validate_dimensions' => false,
            'size_map' => array(
                'url' => 'full',
                'thumb' => 'thumbnail',
            ),
        ));

        $validation = self::validate_file($file, $opts);
        if (is_wp_error($validation)) {
            return $validation;
        }

        self::ensure_media_dependencies();

        $upload = wp_handle_upload($file, array('test_form' => false));
        if (isset($upload['error'])) {
            return new \WP_Error('upload_error', $upload['error']);
        }

        $attachment_id = wp_insert_attachment(array(
            'post_mime_type' => $upload['type'],
            'post_title' => sanitize_file_name($file['name']),
            'post_content' => '',
            'post_status' => 'inherit',
        ), $upload['file']);

        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        $metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        wp_update_attachment_metadata($attachment_id, $metadata);

        return self::format_response($attachment_id, $opts['size_map']);
    }

    /**
     * Upload multiple images at once
     *
     * @param array $files   Multi-file array from $_FILES
     * @param array $options Optional overrides: allowed_types, max_size, validate_dimensions, size_map
     * @return array
     */
    public static function upload_multiple($files, $options = array()) {
        $opts = self::merge_options($options, array(
            'validate_dimensions' => true,
            'size_map' => array(
                'url' => 'malisafi_grid',
                'thumb' => 'malisafi_thumb',
            ),
        ));

        $uploaded = array();
        $errors = array();

        $file_count = isset($files['name']) ? count($files['name']) : 0;

        for ($i = 0; $i < $file_count; $i++) {
            $file = array(
                'name' => $files['name'][$i] ?? '',
                'type' => $files['type'][$i] ?? '',
                'tmp_name' => $files['tmp_name'][$i] ?? '',
                'error' => $files['error'][$i] ?? 0,
                'size' => $files['size'][$i] ?? 0,
            );

            $result = self::upload_single($file, $opts);

            if (is_wp_error($result)) {
                $errors[] = $result->get_error_message();
                continue;
            }

            $uploaded[] = $result;
        }

        return array(
            'images' => $uploaded,
            'errors' => $errors,
        );
    }

    /**
     * Delete an image with optional ownership verification
     *
     * @param int   $image_id    Attachment ID
     * @param array $options     Optional: property_id, user_id
     * @return true|\WP_Error
     */
    public static function delete_image($image_id, $options = array()) {
        $image_id = intval($image_id);
        if (!$image_id) {
            return new \WP_Error('invalid_image', __('Invalid image', 'malisafi-mls'));
        }

        $opts = self::merge_options($options, array(
            'property_id' => 0,
            'user_id' => get_current_user_id(),
        ));

        if (!empty($opts['property_id'])) {
            $property = get_post($opts['property_id']);
            if (!$property || intval($property->post_author) !== intval($opts['user_id'])) {
                return new \WP_Error('permission_denied', __('You do not have permission to delete this image.', 'malisafi-mls'));
            }
        }

        $deleted = wp_delete_attachment($image_id, true);
        if ($deleted) {
            return true;
        }

        return new \WP_Error('delete_failed', __('Failed to delete image', 'malisafi-mls'));
    }

    /**
     * Validate a file before upload
     *
     * @param array $file   File array
     * @param array $opts   Options
     * @return true|\WP_Error
     */
    private static function validate_file($file, $opts) {
        if (empty($file['name'])) {
            return new \WP_Error('no_file', __('No file uploaded', 'malisafi-mls'));
        }

        if (!empty($file['error'])) {
            return new \WP_Error('upload_error', sprintf(__('Upload error (%s)', 'malisafi-mls'), $file['error']));
        }

        $allowed_types = $opts['allowed_types'] ?? self::ALLOWED_TYPES;
        if (!empty($allowed_types) && !in_array($file['type'], $allowed_types, true)) {
            return new \WP_Error('invalid_type', sprintf(__('Invalid file type for %s', 'malisafi-mls'), $file['name']));
        }

        $max_size = isset($opts['max_size']) ? intval($opts['max_size']) : self::MAX_SIZE;
        if (!empty($max_size) && intval($file['size']) > $max_size) {
            return new \WP_Error(
                'file_too_large',
                sprintf(__('File %s is too large (max 10MB)', 'malisafi-mls'), $file['name'])
            );
        }

        if (!empty($opts['validate_dimensions'])) {
            $dimension_check = self::validate_dimensions($file['tmp_name'], $file['name']);
            if (is_wp_error($dimension_check)) {
                return $dimension_check;
            }
        }

        return true;
    }

    /**
     * Validate minimum dimensions and orientation rules
     *
     * @param string $file_path Path to uploaded temp file
     * @param string $file_name Original filename for messaging
     * @return true|\WP_Error
     */
    private static function validate_dimensions($file_path, $file_name) {
        $img_info = @getimagesize($file_path);
        if (!$img_info) {
            return new \WP_Error(
                'invalid_dimensions',
                sprintf(__('Could not read image dimensions for %s', 'malisafi-mls'), $file_name)
            );
        }

        $width = isset($img_info[0]) ? (int) $img_info[0] : 0;
        $height = isset($img_info[1]) ? (int) $img_info[1] : 0;
        $is_landscape = $width > $height;

        if ($is_landscape) {
            if ($width < self::MIN_LANDSCAPE['width'] || $height < self::MIN_LANDSCAPE['height']) {
                return new \WP_Error(
                    'image_too_small',
                    sprintf(
                        __('Image %s is too small. Minimum %dx%d for landscape.', 'malisafi-mls'),
                        $file_name,
                        self::MIN_LANDSCAPE['width'],
                        self::MIN_LANDSCAPE['height']
                    )
                );
            }
        } else {
            if ($width < self::MIN_PORTRAIT['width'] || $height < self::MIN_PORTRAIT['height']) {
                return new \WP_Error(
                    'image_too_small',
                    sprintf(
                        __('Portrait image %s must be at least %dx%d.', 'malisafi-mls'),
                        $file_name,
                        self::MIN_PORTRAIT['width'],
                        self::MIN_PORTRAIT['height']
                    )
                );
            }
        }

        return true;
    }

    /**
     * Build formatted response with requested sizes
     *
     * @param int   $attachment_id Attachment ID
     * @param array $size_map      Array of keys => size names
     * @return array
     */
    private static function format_response($attachment_id, $size_map) {
        $response = array('id' => $attachment_id);

        foreach ($size_map as $key => $size_name) {
            $response[$key] = wp_get_attachment_image_url($attachment_id, $size_name);

            if (empty($response[$key])) {
                // Fallback to original file if requested size missing
                $response[$key] = wp_get_attachment_url($attachment_id);
            }
        }

        return $response;
    }

    /**
     * Merge caller options with defaults
     *
     * @param array $options  Caller options
     * @param array $defaults Default values
     * @return array
     */
    private static function merge_options($options, $defaults) {
        if (!is_array($options)) {
            $options = array();
        }

        return array_merge($defaults, $options);
    }

    /**
     * Ensure required WordPress media files are loaded
     */
    private static function ensure_media_dependencies() {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
    }
}
