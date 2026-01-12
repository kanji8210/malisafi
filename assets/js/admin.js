/**
 * Admin JavaScript for Malisafi MLS
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Initialize admin functionality
        initAdmin();

        // Initialize property edit form gallery (defined below)
        if (typeof initPropertyEditGallery === 'function') {
            initPropertyEditGallery();
        }
        
        /**
         * Initialize admin features
         */
        function initAdmin() {
            // Media uploader for property gallery
            initMediaUploader();
            
            // Ajax handlers
            initAjaxHandlers();
        }
        
        /**
         * Initialize media uploader
         */
        function initMediaUploader() {
            var mediaUploader;
            
            $(document).on('click', '.upload-property-gallery', function(e) {
                e.preventDefault();
                
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                
                mediaUploader = wp.media({
                    title: 'Select Property Images',
                    button: {
                        text: 'Add to Gallery'
                    },
                    multiple: true
                });
                
                mediaUploader.on('select', function() {
                    var attachments = mediaUploader.state().get('selection').toJSON();
                    var ids = [];
                    
                    $.each(attachments, function(index, attachment) {
                        ids.push(attachment.id);
                    });
                    
                    $('#malisafi_gallery').val(ids.join(','));
                });
                
                mediaUploader.open();
            });
        }
        
        /**
         * Initialize AJAX handlers
         */
        function initAjaxHandlers() {
            // Import properties
            $('#import-properties-form').on('submit', function(e) {
                var formData = new FormData(this);
                formData.append('action', 'malisafi_import_properties');
                formData.append('nonce', malisafiMLS.nonce);
                
                $.ajax({
                    url: malisafiMLS.ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message);
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    }
                });
            });
        }

        /**
         * Initialize property edit gallery (for admin custom property form)
         */
        function initPropertyEditGallery() {
            var $btn = $('#select-property-images');
            if ($btn.length === 0) return;

            var imageIds = [];
            var $container = $('#property-images-container');
            var $hidden = $('#property-images-input');

            // Prefill from DOM
            $container.find('.property-image-item').each(function(){
                var id = $(this).data('id');
                if (id) imageIds.push(id);
            });
            updateHidden();
            reapplyFeatured();

            $btn.on('click', function(e){
                e.preventDefault();
                if (typeof wp === 'undefined' || !wp.media) { 
                    alert('Media library not available.'); 
                    return; 
                }
                var frame = wp.media({
                    title: (malisafi_admin && malisafi_admin.strings && malisafi_admin.strings.media_select_title) ? malisafi_admin.strings.media_select_title : 'Select Property Images',
                    button: { text: (malisafi_admin && malisafi_admin.strings && malisafi_admin.strings.media_select_button) ? malisafi_admin.strings.media_select_button : 'Use Images' },
                    multiple: true
                });
                frame.on('select', function(){
                    var selection = frame.state().get('selection');
                    selection.each(function(attachment){
                        var data = attachment.toJSON();
                        if (imageIds.indexOf(data.id) === -1) {
                            if (imageIds.length >= 15) { return; }
                            imageIds.push(data.id);
                            var thumbUrl = (data.sizes && data.sizes.thumbnail) ? data.sizes.thumbnail.url : data.url;
                            var $item = $('<div class="property-image-item" data-id="'+data.id+'" style="position:relative;width:120px;height:120px;border:1px solid #ddd;border-radius:4px;overflow:hidden;">' +
                                '<img src="'+thumbUrl+'" style="width:100%;height:100%;object-fit:cover;" />' +
                                '<button type="button" class="remove-image button-link" style="position:absolute;top:4px;right:4px;color:#dc2626;">&times;</button>' +
                                '</div>');
                            $container.append($item);
                        }
                    });
                    updateHidden();
                    reapplyFeatured();
                });
                frame.open();
            });

            $(document).on('click', '.remove-image', function(){
                var $wrap = $(this).closest('.property-image-item');
                var id = $wrap.data('id');
                imageIds = imageIds.filter(function(x){ return x !== id; });
                $wrap.remove();
                updateHidden();
                reapplyFeatured();
            });

            if ($.fn.sortable) {
                $container.sortable({
                    items: '.property-image-item',
                    update: function(){
                        imageIds = [];
                        $container.find('.property-image-item').each(function(){
                            imageIds.push($(this).data('id'));
                        });
                        updateHidden();
                        reapplyFeatured();
                    }
                });
            }

            function updateHidden(){
                $hidden.val(imageIds.join(','));
            }
            function reapplyFeatured(){
                $container.find('.main-badge').remove();
                $container.find('.property-image-item').first().append('<span class="main-badge" style="position:absolute;left:4px;top:4px;background:#2563eb;color:#fff;font-size:11px;padding:2px 6px;border-radius:3px;">Featured</span>');
            }
        }
        
    });

})(jQuery);
