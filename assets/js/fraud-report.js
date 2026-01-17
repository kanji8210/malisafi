/**
 * Fraud Report Form JavaScript
 * 
 * @package MalisafiMLS
 * @since 1.0.1
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initFraudReportForm();
    });

    function initFraudReportForm() {
        // Agent Autocomplete
        $('#agent_search').autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: malisafiFraudReport.ajaxUrl,
                    dataType: 'json',
                    data: {
                        action: 'malisafi_search_agents',
                        term: request.term
                    },
                    success: function(data) {
                        if (data.length === 0) {
                            response([{
                                label: malisafiFraudReport.i18n.noResults,
                                value: ''
                            }]);
                        } else {
                            response(data);
                        }
                    }
                });
            },
            minLength: 2,
            select: function(event, ui) {
                if (ui.item.id) {
                    $('#agent_id').val(ui.item.id);
                    displaySelectedItem('agent', ui.item.label, ui.item.id);
                }
                return false;
            },
            focus: function(event, ui) {
                $('#agent_search').val(ui.item.label);
                return false;
            }
        });

        // Property Autocomplete
        $('#property_search').autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: malisafiFraudReport.ajaxUrl,
                    dataType: 'json',
                    data: {
                        action: 'malisafi_search_properties',
                        term: request.term
                    },
                    success: function(data) {
                        if (data.length === 0) {
                            response([{
                                label: malisafiFraudReport.i18n.noResults,
                                value: ''
                            }]);
                        } else {
                            response(data);
                        }
                    }
                });
            },
            minLength: 2,
            select: function(event, ui) {
                if (ui.item.id) {
                    $('#property_id').val(ui.item.id);
                    displaySelectedItem('property', ui.item.label, ui.item.id);
                }
                return false;
            },
            focus: function(event, ui) {
                $('#property_search').val(ui.item.label);
                return false;
            }
        });

        // Character counter for reason field
        $('#reason').on('input', function() {
            const count = $(this).val().length;
            $('#char-count').text(count);
            
            if (count > 450) {
                $('#char-count').css('color', '#dc3545');
            } else {
                $('#char-count').css('color', '#999');
            }
        });

        // Form submission
        $('#malisafi-fraud-report-form').on('submit', function(e) {
            e.preventDefault();
            
            const form = $(this);
            const submitBtn = form.find('.btn-submit');
            const responseDiv = $('#form-response');
            
            // Validation
            const agentId = $('#agent_id').val();
            const propertyId = $('#property_id').val();
            
            if (!agentId && !propertyId) {
                showResponse('error', 'Please select at least one: Agent or Property');
                return;
            }
            
            // Disable submit button
            submitBtn.prop('disabled', true);
            submitBtn.addClass('loading');
            submitBtn.find('.btn-text').text(malisafiFraudReport.i18n.submitting);
            
            // Prepare data
            const formData = {
                action: 'malisafi_submit_fraud_report',
                nonce: malisafiFraudReport.nonce,
                report_type: $('#report_type').val(),
                agent_id: agentId,
                property_id: propertyId,
                reason: $('#reason').val(),
                details: $('#details').val(),
                reporter_email: $('#reporter_email').val()
            };
            
            // Submit via AJAX
            $.ajax({
                url: malisafiFraudReport.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        showResponse('success', response.data.message);
                        form[0].reset();
                        $('#agent_id, #property_id').val('');
                        $('.selected-item').remove();
                        $('#char-count').text('0');
                        
                        // Scroll to message
                        $('html, body').animate({
                            scrollTop: responseDiv.offset().top - 100
                        }, 500);
                    } else {
                        showResponse('error', response.data.message);
                    }
                },
                error: function() {
                    showResponse('error', malisafiFraudReport.i18n.error);
                },
                complete: function() {
                    submitBtn.prop('disabled', false);
                    submitBtn.removeClass('loading');
                    submitBtn.find('.btn-text').text('Submit Report');
                }
            });
        });

        // Clear autocomplete on input clear
        $('#agent_search').on('input', function() {
            if ($(this).val().length === 0) {
                $('#agent_id').val('');
                $('.selected-item[data-type="agent"]').remove();
            }
        });

        $('#property_search').on('input', function() {
            if ($(this).val().length === 0) {
                $('#property_id').val('');
                $('.selected-item[data-type="property"]').remove();
            }
        });
    }

    /**
     * Display selected item
     */
    function displaySelectedItem(type, label, id) {
        const container = type === 'agent' ? '#agent_search' : '#property_search';
        const existing = $('.selected-item[data-type="' + type + '"]');
        
        if (existing.length > 0) {
            existing.remove();
        }
        
        const selectedHtml = '<div class="selected-item" data-type="' + type + '" data-id="' + id + '">' +
            '<span class="item-label">' + label + '</span>' +
            '<span class="remove-item" title="Remove">×</span>' +
            '</div>';
        
        $(container).after(selectedHtml);
        
        // Remove item on click
        $('.selected-item .remove-item').on('click', function() {
            const item = $(this).closest('.selected-item');
            const itemType = item.data('type');
            
            item.remove();
            
            if (itemType === 'agent') {
                $('#agent_id').val('');
                $('#agent_search').val('');
            } else {
                $('#property_id').val('');
                $('#property_search').val('');
            }
        });
    }

    /**
     * Show response message
     */
    function showResponse(type, message) {
        const responseDiv = $('#form-response');
        
        responseDiv
            .removeClass('success error')
            .addClass(type)
            .html(message)
            .fadeIn();
        
        setTimeout(function() {
            if (type === 'success') {
                responseDiv.fadeOut();
            }
        }, 5000);
    }

})(jQuery);
