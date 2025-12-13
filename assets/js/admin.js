/**
 * Admin JavaScript for Malisafi MLS
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Initialize admin functionality
        initAdmin();
        
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
        
    });

})(jQuery);
