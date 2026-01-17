/**
 * Fraud Reports Admin Page JavaScript
 * 
 * @package MalisafiMLS
 * @since 1.0.1
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initFraudReportsPage();
    });

    function initFraudReportsPage() {
        // Initialize modal dialogs
        initReportDetailsModal();
        initManualSuspicionModal();
        
        // Event handlers
        setupEventHandlers();
        
        // Autocomplete for manual suspicion form
        setupAutocomplete();
        
        // Confidence score slider
        setupConfidenceSlider();
    }

    /**
     * Initialize Report Details Modal
     */
    function initReportDetailsModal() {
        $('#report-details-modal').dialog({
            autoOpen: false,
            modal: true,
            width: 650,
            maxHeight: 600,
            title: 'Report Details',
            buttons: {
                'Close': function() {
                    $(this).dialog('close');
                }
            }
        });
    }

    /**
     * Initialize Manual Suspicion Modal
     */
    function initManualSuspicionModal() {
        $('#manual-suspicion-modal').dialog({
            autoOpen: false,
            modal: true,
            width: 600,
            maxHeight: 700,
            title: 'Create Manual Suspicion',
            close: function() {
                $('#manual-suspicion-form')[0].reset();
                $('#suspicion_user_id, #suspicion_property_id, #report_id').val('');
                $('#suspicion-response').hide();
            }
        });
    }

    /**
     * Setup event handlers
     */
    function setupEventHandlers() {
        // View report details
        $(document).on('click', '.view-details', function() {
            const reportId = $(this).data('report-id');
            loadReportDetails(reportId);
        });

        // Create suspicion from report
        $(document).on('click', '.create-suspicion', function() {
            const reportId = $(this).data('report-id');
            const agentId = $(this).data('agent-id');
            const propertyId = $(this).data('property-id');
            
            openCreateSuspicionModal(reportId, agentId, propertyId);
        });

        // Create manual suspicion (toolbar button)
        $('#create-manual-suspicion-btn').on('click', function() {
            openCreateSuspicionModal();
        });

        // Submit manual suspicion form
        $('#manual-suspicion-form').on('submit', function(e) {
            e.preventDefault();
            submitManualSuspicion();
        });
    }

    /**
     * Setup autocomplete for agents and properties
     */
    function setupAutocomplete() {
        // Agent autocomplete
        $('#suspicion_agent_search').autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: malisafiFraudAdmin.ajaxUrl,
                    dataType: 'json',
                    data: {
                        action: 'malisafi_search_agents',
                        term: request.term
                    },
                    success: function(data) {
                        response(data);
                    }
                });
            },
            minLength: 2,
            select: function(event, ui) {
                if (ui.item.id) {
                    $('#suspicion_user_id').val(ui.item.id);
                }
                return false;
            }
        });

        // Property autocomplete
        $('#suspicion_property_search').autocomplete({
            source: function(request, response) {
                $.ajax({
                    url: malisafiFraudAdmin.ajaxUrl,
                    dataType: 'json',
                    data: {
                        action: 'malisafi_search_properties',
                        term: request.term
                    },
                    success: function(data) {
                        response(data);
                    }
                });
            },
            minLength: 2,
            select: function(event, ui) {
                if (ui.item.id) {
                    $('#suspicion_property_id').val(ui.item.id);
                }
                return false;
            }
        });
    }

    /**
     * Setup confidence score slider
     */
    function setupConfidenceSlider() {
        $('#confidence_score').on('input', function() {
            const value = $(this).val();
            $('#confidence-value').text(value);
            
            const label = $('#confidence-label');
            if (value < 40) {
                label.text('Low Risk').removeClass('medium-risk high-risk').addClass('low-risk');
            } else if (value < 75) {
                label.text('Medium Risk').removeClass('low-risk high-risk').addClass('medium-risk');
            } else {
                label.text('High Risk').removeClass('low-risk medium-risk').addClass('high-risk');
            }
        });
    }

    /**
     * Load report details
     */
    function loadReportDetails(reportId) {
        $.ajax({
            url: malisafiFraudAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'malisafi_get_report_details',
                nonce: malisafiFraudAdmin.nonce,
                report_id: reportId
            },
            beforeSend: function() {
                $('#report-details-content').html('<p>Loading...</p>');
                $('#report-details-modal').dialog('open');
            },
            success: function(response) {
                if (response.success) {
                    displayReportDetails(response.data);
                } else {
                    $('#report-details-content').html('<p class="error">' + response.data.message + '</p>');
                }
            },
            error: function() {
                $('#report-details-content').html('<p class="error">Failed to load report details.</p>');
            }
        });
    }

    /**
     * Display report details
     */
    function displayReportDetails(report) {
        let html = '';
        
        html += '<div class="report-detail-row">';
        html += '<div class="report-detail-label">Report Type:</div>';
        html += '<div class="report-detail-value">' + formatReportType(report.report_type) + '</div>';
        html += '</div>';

        html += '<div class="report-detail-row">';
        html += '<div class="report-detail-label">Reporter:</div>';
        html += '<div class="report-detail-value">' + report.reporter + '</div>';
        html += '</div>';

        if (report.agent) {
            html += '<div class="report-detail-row">';
            html += '<div class="report-detail-label">Agent:</div>';
            html += '<div class="report-detail-value">' + report.agent + '</div>';
            html += '</div>';
        }

        if (report.property) {
            html += '<div class="report-detail-row">';
            html += '<div class="report-detail-label">Property:</div>';
            html += '<div class="report-detail-value">' + report.property + '</div>';
            html += '</div>';
        }

        html += '<div class="report-detail-row">';
        html += '<div class="report-detail-label">Reason:</div>';
        html += '<div class="report-detail-value">' + report.reason + '</div>';
        html += '</div>';

        html += '<div class="report-detail-row">';
        html += '<div class="report-detail-label">Details:</div>';
        html += '<div class="report-detail-value">' + nl2br(report.details) + '</div>';
        html += '</div>';

        html += '<div class="report-detail-row">';
        html += '<div class="report-detail-label">Status:</div>';
        html += '<div class="report-detail-value">' + formatStatus(report.status) + '</div>';
        html += '</div>';

        html += '<div class="report-detail-row">';
        html += '<div class="report-detail-label">Submitted:</div>';
        html += '<div class="report-detail-value">' + report.created_at + '</div>';
        html += '</div>';

        if (report.reviewed_at) {
            html += '<div class="report-detail-row">';
            html += '<div class="report-detail-label">Reviewed:</div>';
            html += '<div class="report-detail-value">' + report.reviewed_at + ' by ' + report.reviewed_by + '</div>';
            html += '</div>';
        }

        if (report.admin_notes) {
            html += '<div class="report-detail-row">';
            html += '<div class="report-detail-label">Admin Notes:</div>';
            html += '<div class="report-detail-value">' + nl2br(report.admin_notes) + '</div>';
            html += '</div>';
        }

        // Action buttons
        html += '<div class="modal-actions">';
        
        if (report.status === 'new' || report.status === 'under_review') {
            html += '<button class="button button-primary" onclick="updateReportStatus(' + report.id + ', \'under_review\')">Mark as Under Review</button>';
            html += '<button class="button button-primary" onclick="updateReportStatus(' + report.id + ', \'resolved\')">Mark as Resolved</button>';
            html += '<button class="button button-danger" onclick="updateReportStatus(' + report.id + ', \'dismissed\')">Dismiss</button>';
        }
        
        html += '</div>';

        $('#report-details-content').html(html);
    }

    /**
     * Open create suspicion modal
     */
    function openCreateSuspicionModal(reportId, agentId, propertyId) {
        $('#report_id').val(reportId || '');
        
        if (agentId) {
            $('#suspicion_user_id').val(agentId);
            // Load agent name
            $.ajax({
                url: malisafiFraudAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'malisafi_get_agent_name',
                    nonce: malisafiFraudAdmin.nonce,
                    agent_id: agentId
                },
                success: function(response) {
                    if (response.success) {
                        $('#suspicion_agent_search').val(response.data.name);
                    }
                }
            });
        }
        
        if (propertyId) {
            $('#suspicion_property_id').val(propertyId);
            // Load property title
            $.ajax({
                url: malisafiFraudAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'malisafi_get_property_title',
                    nonce: malisafiFraudAdmin.nonce,
                    property_id: propertyId
                },
                success: function(response) {
                    if (response.success) {
                        $('#suspicion_property_search').val(response.data.title);
                    }
                }
            });
        }
        
        $('#manual-suspicion-modal').dialog('open');
    }

    /**
     * Submit manual suspicion
     */
    function submitManualSuspicion() {
        const formData = {
            action: 'malisafi_create_manual_suspicion',
            nonce: malisafiFraudAdmin.nonce,
            user_id: $('#suspicion_user_id').val(),
            property_id: $('#suspicion_property_id').val(),
            fraud_type: $('#fraud_type').val(),
            confidence_score: $('#confidence_score').val(),
            notes: $('#notes').val()
        };

        $.ajax({
            url: malisafiFraudAdmin.ajaxUrl,
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $('#manual-suspicion-form button[type="submit"]')
                    .prop('disabled', true)
                    .append('<span class="loading-spinner"></span>');
            },
            success: function(response) {
                if (response.success) {
                    showNotice('success', malisafiFraudAdmin.i18n.createSuspicionSuccess);
                    
                    // Update report if linked
                    const reportId = $('#report_id').val();
                    if (reportId) {
                        updateReportSuspicionLink(reportId, response.data.detection_id);
                    }
                    
                    setTimeout(function() {
                        $('#manual-suspicion-modal').dialog('close');
                        location.reload();
                    }, 1500);
                } else {
                    showNotice('error', response.data.message || malisafiFraudAdmin.i18n.createSuspicionError);
                }
            },
            error: function() {
                showNotice('error', malisafiFraudAdmin.i18n.createSuspicionError);
            },
            complete: function() {
                $('#manual-suspicion-form button[type="submit"]')
                    .prop('disabled', false)
                    .find('.loading-spinner').remove();
            }
        });
    }

    /**
     * Update report status
     */
    window.updateReportStatus = function(reportId, newStatus) {
        const confirmMsg = newStatus === 'dismissed' ? 
            malisafiFraudAdmin.i18n.confirmDismiss : 
            malisafiFraudAdmin.i18n.confirmResolve;
        
        if (newStatus === 'dismissed' && !confirm(confirmMsg)) {
            return;
        }

        const adminNotes = prompt('Add notes (optional):');

        $.ajax({
            url: malisafiFraudAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'malisafi_update_report_status',
                nonce: malisafiFraudAdmin.nonce,
                report_id: reportId,
                status: newStatus,
                admin_notes: adminNotes || ''
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(malisafiFraudAdmin.i18n.updateStatusError);
                }
            },
            error: function() {
                alert(malisafiFraudAdmin.i18n.updateStatusError);
            }
        });
    };

    /**
     * Helper: Format report type
     */
    function formatReportType(type) {
        const types = {
            'fake_listing': 'Fake Listing',
            'duplicate_property': 'Duplicate Property',
            'misleading_info': 'Misleading Information',
            'fake_agent': 'Fake Agent',
            'price_scam': 'Price Scam',
            'fake_photos': 'Fake Photos',
            'contact_fraud': 'Contact Fraud',
            'identity_theft': 'Identity Theft',
            'spam': 'Spam',
            'other': 'Other'
        };
        return types[type] || type;
    }

    /**
     * Helper: Format status
     */
    function formatStatus(status) {
        const statuses = {
            'new': '<span class="status-badge status-new">New</span>',
            'under_review': '<span class="status-badge status-review">Under Review</span>',
            'resolved': '<span class="status-badge status-resolved">Resolved</span>',
            'dismissed': '<span class="status-badge status-dismissed">Dismissed</span>'
        };
        return statuses[status] || status;
    }

    /**
     * Helper: Convert newlines to <br>
     */
    function nl2br(str) {
        return (str + '').replace(/\n/g, '<br>');
    }

    /**
     * Show notice message
     */
    function showNotice(type, message) {
        const notice = $('#suspicion-response');
        notice
            .removeClass('notice-success notice-error')
            .addClass('notice-' + type)
            .html('<p>' + message + '</p>')
            .fadeIn();
    }

    /**
     * Update report-suspicion link
     */
    function updateReportSuspicionLink(reportId, detectionId) {
        $.ajax({
            url: malisafiFraudAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'malisafi_link_report_to_suspicion',
                nonce: malisafiFraudAdmin.nonce,
                report_id: reportId,
                detection_id: detectionId
            }
        });
    }

})(jQuery);
