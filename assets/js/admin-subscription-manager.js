/**
 * Advanced Subscription Manager - Admin JavaScript
 * Handles AJAX operations, bulk actions, and UI interactions
 *
 * @package MalisafiMLS
 */

(function($) {
    'use strict';

    const SubManager = {
        init: function() {
            this.bindEvents();
            this.initDatePickers();
        },

        bindEvents: function() {
            // Select all checkbox
            $('#cb-select-all').on('change', this.toggleSelectAll);
            
            // Individual checkboxes
            $('input[name="subscription_ids[]"]').on('change', this.updateSelectedCount);
            
            // Quick actions
            $('.cancel-subscription').on('click', this.cancelSubscription);
            $('.reactivate-subscription').on('click', this.reactivateSubscription);
            $('.extend-subscription').on('click', this.extendSubscription);
            
            // Bulk action form
            $('#bulk-action-form').on('submit', this.handleBulkAction);
            
            // Search form
            $('#subscription-search-form').on('submit', this.handleSearch);
        },

        initDatePickers: function() {
            if ($.fn.datepicker) {
                $('input[type="date"]').each(function() {
                    if (!$(this).attr('readonly')) {
                        // jQuery UI Datepicker fallback for browsers without native support
                        $(this).datepicker({
                            dateFormat: 'yy-mm-dd',
                            changeMonth: true,
                            changeYear: true
                        });
                    }
                });
            }
        },

        toggleSelectAll: function() {
            const isChecked = $(this).prop('checked');
            $('input[name="subscription_ids[]"]').prop('checked', isChecked);
            SubManager.updateSelectedCount();
        },

        updateSelectedCount: function() {
            const count = $('input[name="subscription_ids[]"]:checked').length;
            const $bulkSelector = $('#bulk-action-selector');
            
            if (count > 0) {
                $bulkSelector.prop('disabled', false);
                $('.bulkactions .button').prop('disabled', false);
            } else {
                $bulkSelector.prop('disabled', true);
                $('.bulkactions .button').prop('disabled', true);
            }
            
            // Update count display if exists
            $('.selected-count').text(count);
        },

        cancelSubscription: function(e) {
            e.preventDefault();
            const subscriptionId = $(this).data('id');
            
            if (!confirm(malisafiSubManager.strings.confirmDelete)) {
                return;
            }
            
            SubManager.performAction('cancel', { subscription_id: subscriptionId });
        },

        reactivateSubscription: function(e) {
            e.preventDefault();
            const subscriptionId = $(this).data('id');
            
            if (!confirm('Are you sure you want to reactivate this subscription?')) {
                return;
            }
            
            SubManager.performAction('reactivate', { subscription_id: subscriptionId });
        },

        extendSubscription: function(e) {
            e.preventDefault();
            const subscriptionId = $(this).data('id');
            const days = prompt('Extend by how many days?', '30');
            
            if (!days || isNaN(days)) {
                return;
            }
            
            SubManager.performAction('extend', { 
                subscription_id: subscriptionId,
                extend_by: parseInt(days),
                extend_unit: 'days'
            });
        },

        handleBulkAction: function(e) {
            const action = $('#bulk-action-selector').val();
            
            if (!action) {
                e.preventDefault();
                alert('Please select an action');
                return;
            }
            
            const selectedIds = [];
            $('input[name="subscription_ids[]"]:checked').each(function() {
                selectedIds.push($(this).val());
            });
            
            if (selectedIds.length === 0) {
                e.preventDefault();
                alert('Please select at least one subscription');
                return;
            }
            
            if (!confirm(malisafiSubManager.strings.confirmBulkAction + '\n\nSelected: ' + selectedIds.length)) {
                e.preventDefault();
                return;
            }
            
            // Allow form to submit normally or handle via AJAX here
        },

        performAction: function(action, data) {
            const $button = $('<div class="malisafi-loading">Processing...</div>');
            $('body').append($button);
            
            $.ajax({
                url: malisafiSubManager.ajaxurl,
                type: 'POST',
                data: {
                    action: 'malisafi_subscription_action',
                    nonce: malisafiSubManager.nonce,
                    action_type: action,
                    ...data
                },
                success: function(response) {
                    $button.remove();
                    
                    if (response.success) {
                        alert(malisafiSubManager.strings.success);
                        location.reload();
                    } else {
                        alert(response.data.message || malisafiSubManager.strings.error);
                    }
                },
                error: function() {
                    $button.remove();
                    alert(malisafiSubManager.strings.error);
                }
            });
        },

        handleSearch: function(e) {
            // Allow form to submit normally
            // Could be enhanced with AJAX live search
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        SubManager.init();
    });

})(jQuery);
