/**
 * Frontend Property Moderation Scripts
 */

(function($) {
    'use strict';
    
    // Report Property Modal
    var reportModal = {
        init: function() {
            this.createModal();
            this.bindEvents();
        },
        
        createModal: function() {
            if ($('#malisafi-report-modal').length) return;
            
            var modalHtml = '<div id="malisafi-report-modal" class="malisafi-modal" style="display: none;">' +
                '<div class="modal-overlay"></div>' +
                '<div class="modal-dialog">' +
                    '<div class="modal-content">' +
                        '<button type="button" class="modal-close">&times;</button>' +
                        '<h3>' + malisafiModeration.i18n.reportProperty + '</h3>' +
                        '<form id="malisafi-report-form">' +
                            '<div class="form-group">' +
                                '<label for="report-reason">' + malisafiModeration.i18n.reason + ' *</label>' +
                                '<select id="report-reason" name="reason" required>' +
                                    '<option value="">' + malisafiModeration.i18n.selectReason + '</option>';
            
            $.each(malisafiModeration.reportReasons, function(value, label) {
                modalHtml += '<option value="' + value + '">' + label + '</option>';
            });
            
            modalHtml += '</select>' +
                            '</div>' +
                            '<div class="form-group">' +
                                '<label for="report-details">' + malisafiModeration.i18n.additionalDetails + '</label>' +
                                '<textarea id="report-details" name="details" rows="4" placeholder="' + malisafiModeration.i18n.detailsPlaceholder + '"></textarea>' +
                            '</div>' +
                            '<div class="form-actions">' +
                                '<button type="submit" class="btn btn-primary">' + malisafiModeration.i18n.submitReport + '</button>' +
                                '<button type="button" class="btn btn-secondary cancel-report">' + malisafiModeration.i18n.cancel + '</button>' +
                            '</div>' +
                            '<div class="report-message" style="display: none;"></div>' +
                        '</form>' +
                    '</div>' +
                '</div>' +
            '</div>';
            
            $('body').append(modalHtml);
        },
        
        bindEvents: function() {
            var self = this;
            
            // Open modal
            $(document).on('click', '.report-property-btn', function(e) {
                e.preventDefault();
                
                if (!malisafiModeration.isLoggedIn) {
                    alert(malisafiModeration.i18n.loginRequired);
                    return;
                }
                
                var propertyId = $(this).data('property-id');
                $('#malisafi-report-modal').data('property-id', propertyId).fadeIn(200);
                $('body').addClass('modal-open');
            });
            
            // Close modal
            $(document).on('click', '.modal-close, .cancel-report, .modal-overlay', function(e) {
                e.preventDefault();
                self.closeModal();
            });
            
            // Submit report
            $(document).on('submit', '#malisafi-report-form', function(e) {
                e.preventDefault();
                self.submitReport();
            });
        },
        
        submitReport: function() {
            var $form = $('#malisafi-report-form');
            var $submitBtn = $form.find('button[type="submit"]');
            var $message = $('.report-message');
            var propertyId = $('#malisafi-report-modal').data('property-id');
            
            var reason = $('#report-reason').val();
            var details = $('#report-details').val();
            
            if (!reason) {
                this.showMessage('error', malisafiModeration.i18n.selectReasonError);
                return;
            }
            
            // Disable submit button
            $submitBtn.prop('disabled', true).text(malisafiModeration.i18n.submitting);
            $message.hide();
            
            $.ajax({
                url: malisafiModeration.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'malisafi_report_property',
                    property_id: propertyId,
                    reason: reason,
                    details: details,
                    nonce: malisafiModeration.nonce
                },
                success: function(response) {
                    if (response.success) {
                        reportModal.showMessage('success', response.data.message);
                        
                        // Reset form and close after 2 seconds
                        setTimeout(function() {
                            reportModal.closeModal();
                            $form[0].reset();
                        }, 2000);
                    } else {
                        reportModal.showMessage('error', response.data.message);
                    }
                },
                error: function() {
                    reportModal.showMessage('error', malisafiModeration.i18n.errorOccurred);
                },
                complete: function() {
                    $submitBtn.prop('disabled', false).text(malisafiModeration.i18n.submitReport);
                }
            });
        },
        
        closeModal: function() {
            $('#malisafi-report-modal').fadeOut(200);
            $('body').removeClass('modal-open');
            $('#malisafi-report-form')[0].reset();
            $('.report-message').hide();
        },
        
        showMessage: function(type, message) {
            var $message = $('.report-message');
            $message.removeClass('success error')
                    .addClass(type)
                    .html(message)
                    .fadeIn(200);
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        reportModal.init();
    });
    
})(jQuery);
