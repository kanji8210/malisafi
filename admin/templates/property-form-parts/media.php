<!-- Media Section -->
<div class="postbox">
    <h2 class="hndle"><?php _e('Property Images & Media', 'malisafi-mls'); ?></h2>
    <div class="inside">
        <table class="form-table">
            <tr>
                <th><label><?php _e('Property Images', 'malisafi-mls'); ?></label></th>
                <td>
                    <div class="media-info-box" style="background: #f0f6fc; border-left: 4px solid #1a1a1a; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
                        <h4 style="margin: 0 0 10px 0; color: #1a1a1a;">
                            <span class="dashicons dashicons-info" style="color: #1a1a1a;"></span>
                            <?php _e('Image Upload Instructions', 'malisafi-mls'); ?>
                        </h4>
                        <div style="font-size: 13px; line-height: 1.6;">
                            <p style="margin: 0 0 10px 0;"><strong><?php _e('Featured Image (First Image):', 'malisafi-mls'); ?></strong></p>
                            <ul style="margin: 0 0 15px 20px;">
                                <li><?php _e('The first image you upload will be the <strong>Featured Image</strong>', 'malisafi-mls'); ?></li>
                                <li><?php _e('This image appears in search results, property listings, and as the main property photo', 'malisafi-mls'); ?></li>
                                <li><?php _e('<strong>Recommended dimensions:</strong> 1200 x 800 pixels (3:2 ratio)', 'malisafi-mls'); ?></li>
                                <li><?php _e('Choose a high-quality exterior or most appealing view of the property', 'malisafi-mls'); ?></li>
                            </ul>
                            <p style="margin: 0 0 10px 0;"><strong><?php _e('Additional Images (Gallery):', 'malisafi-mls'); ?></strong></p>
                            <ul style="margin: 0 0 10px 20px;">
                                <li><?php _e('Upload multiple images showing different rooms and features', 'malisafi-mls'); ?></li>
                                <li><?php _e('Images will appear in the order you upload them', 'malisafi-mls'); ?></li>
                                <li><?php _e('You can drag to reorder images after upload', 'malisafi-mls'); ?></li>
                                <li><?php _e('<strong>Recommended:</strong> 8-15 high-quality images per property', 'malisafi-mls'); ?></li>
                            </ul>
                            <p style="margin: 0; color: #4a4a4a; font-style: italic;">
                                <span class="dashicons dashicons-lightbulb" style="color: #1a1a1a;"></span>
                                <?php _e('Tip: High-quality images increase property views by up to 60% and generate more inquiries.', 'malisafi-mls'); ?>
                            </p>
                        </div>
                    </div>
                    
                    <div id="property-images-container" class="property-images-grid">
                        <?php
                        if ($is_edit) {
                            $attachments = get_posts(array(
                                'post_type' => 'attachment',
                                'posts_per_page' => -1,
                                'post_parent' => $property_id,
                                'post_mime_type' => 'image'
                            ));
                            
                            foreach ($attachments as $attachment) {
                                $image_url = wp_get_attachment_image_url($attachment->ID, 'thumbnail');
                                echo '<div class="property-image-item" data-id="' . $attachment->ID . '">';
                                echo '<img src="' . esc_url($image_url) . '" alt="">';
                                echo '<button type="button" class="remove-image" title="' . __('Remove', 'malisafi-mls') . '">&times;</button>';
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>
                    <button type="button" id="upload-images-btn" class="button button-primary" style="background: #1a1a1a; border-color: #1a1a1a; font-size: 14px; padding: 8px 20px; height: auto;">
                        <span class="dashicons dashicons-upload" style="margin-top: 4px;"></span>
                        <?php _e('Upload Property Images', 'malisafi-mls'); ?>
                    </button>
                    <input type="hidden" name="property_images" id="property-images-input" value="">
                    <p class="description" style="margin-top: 15px; font-weight: 500; color: #1a1a1a;">
                        <?php _e('Click the button above to select and upload images from your computer.', 'malisafi-mls'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Property Features', 'malisafi-mls'); ?></label></th>
                <td>
                    <?php
                    $property_features = get_terms(array('taxonomy' => 'malisafi_property_features', 'hide_empty' => false));
                    $selected_features = $is_edit ? wp_get_post_terms($property_id, 'malisafi_property_features', array('fields' => 'ids')) : array();
                    
                    if (!empty($property_features)) {
                        echo '<div class="features-checkboxes">';
                        foreach ($property_features as $feature) {
                            $checked = in_array($feature->term_id, $selected_features) ? 'checked' : '';
                            echo '<label style="display: inline-block; margin-right: 15px; margin-bottom: 10px;">';
                            echo '<input type="checkbox" name="property_features[]" value="' . esc_attr($feature->term_id) . '" ' . $checked . '> ';
                            echo esc_html($feature->name);
                            echo '</label>';
                        }
                        echo '</div>';
                    } else {
                        echo '<p class="description">' . __('No features available. Please add them in the Features taxonomy.', 'malisafi-mls') . '</p>';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th><label for="property_video_url"><?php _e('Video URL', 'malisafi-mls'); ?></label></th>
                <td>
                    <input type="url" name="property_video_url" id="property_video_url" class="large-text" 
                           value="<?php echo $is_edit ? esc_url(get_post_meta($property_id, '_malisafi_video_url', true)) : ''; ?>" 
                           placeholder="https://youtube.com/watch?v=...">
                    <p class="description"><?php _e('YouTube or Vimeo URL', 'malisafi-mls'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="property_virtual_tour"><?php _e('Virtual Tour URL', 'malisafi-mls'); ?></label></th>
                <td>
                    <input type="url" name="property_virtual_tour" id="property_virtual_tour" class="large-text" 
                           value="<?php echo $is_edit ? esc_url(get_post_meta($property_id, '_malisafi_virtual_tour', true)) : ''; ?>" 
                           placeholder="https://...">
                    <p class="description"><?php _e('Link to 360° virtual tour or Matterport', 'malisafi-mls'); ?></p>
                </td>
            </tr>
        </table>
    </div>
</div>

<style>
.property-images-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 10px;
    margin-bottom: 15px;
}
.property-image-item {
    position: relative;
    border: 2px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
    aspect-ratio: 1;
}
.property-image-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.property-image-item .remove-image {
    position: absolute;
    top: 5px;
    right: 5px;
    background: rgba(220, 38, 38, 0.9);
    color: white;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    cursor: pointer;
    font-size: 18px;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}
.property-image-item .remove-image:hover {
    background: #dc2626;
}
.features-checkboxes {
    max-height: 200px;
    overflow-y: auto;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}
</style>

<script>
jQuery(document).ready(function($) {
    var mediaUploader;
    var imageIds = [];
    
    // Collect existing image IDs
    $('#property-images-container .property-image-item').each(function() {
        imageIds.push($(this).data('id'));
    });
    updateImageInput();
    
    $('#upload-images-btn').on('click', function(e) {
        e.preventDefault();
        
        // Check if wp.media is available
        if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
            alert('<?php _e('WordPress Media Library is not loaded. Please refresh the page and try again.', 'malisafi-mls'); ?>');
            console.error('wp.media is not defined. Make sure wp_enqueue_media() is called.');
            return;
        }
        
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        
        mediaUploader = wp.media({
            title: '<?php _e('Select Property Images', 'malisafi-mls'); ?>',
            button: {
                text: '<?php _e('Add Images to Property', 'malisafi-mls'); ?>'
            },
            multiple: true,
            library: {
                type: 'image'
            }
        });
        
        mediaUploader.on('select', function() {
            var attachments = mediaUploader.state().get('selection').toJSON();
            
            attachments.forEach(function(attachment) {
                if (imageIds.indexOf(attachment.id) === -1) {
                    imageIds.push(attachment.id);
                    
                    // Use thumbnail if available, otherwise use full size
                    var thumbUrl = attachment.sizes && attachment.sizes.thumbnail 
                        ? attachment.sizes.thumbnail.url 
                        : (attachment.sizes && attachment.sizes.medium 
                            ? attachment.sizes.medium.url 
                            : attachment.url);
                    
                    var imageItem = $('<div class="property-image-item" data-id="' + attachment.id + '">' +
                        '<img src="' + thumbUrl + '" alt="">' +
                        '<button type="button" class="remove-image" title="<?php _e('Remove', 'malisafi-mls'); ?>">&times;</button>' +
                        '</div>');
                    
                    $('#property-images-container').append(imageItem);
                }
            });
            
            updateImageInput();
        });
        
        mediaUploader.open();
    });
    
    $(document).on('click', '.remove-image', function() {
        var imageItem = $(this).closest('.property-image-item');
        var imageId = imageItem.data('id');
        
        imageIds = imageIds.filter(function(id) {
            return id !== imageId;
        });
        
        imageItem.remove();
        updateImageInput();
    });
    
    function updateImageInput() {
        $('#property-images-input').val(imageIds.join(','));
    }
});
</script>
