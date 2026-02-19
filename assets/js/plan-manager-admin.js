/**
 * Plan Manager Admin Scripts
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Assign plan to user
        $('.malisafi-assign-plan-btn').on('click', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var userId = $btn.data('user-id');
            var $form = $('#malisafi-plan-assignment-form-' + userId);
            var planType = $form.find('select[name="plan_type"]').val();
            var duration = $form.find('input[name="duration"]').val() || 12;
            
            if (!planType) {
                alert('Please select a plan type.');
                return;
            }
            
            if (!confirm(malisafiPlanManager.i18n.confirm_assign)) {
                return;
            }
            
            $btn.prop('disabled', true).text('Assigning...');
            
            $.ajax({
                url: malisafiPlanManager.ajax_url,
                type: 'POST',
                data: {
                    action: 'malisafi_assign_plan',
                    nonce: malisafiPlanManager.nonce,
                    user_id: userId,
                    plan_type: planType,
                    duration: duration
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message || malisafiPlanManager.i18n.error);
                        $btn.prop('disabled', false).text('Assign Plan');
                    }
                },
                error: function() {
                    alert(malisafiPlanManager.i18n.error);
                    $btn.prop('disabled', false).text('Assign Plan');
                }
            });
        });
        
        // Remove plan from user
        $('.malisafi-remove-plan-btn').on('click', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var userId = $btn.data('user-id');
            
            if (!confirm(malisafiPlanManager.i18n.confirm_remove)) {
                return;
            }
            
            $btn.prop('disabled', true).text('Removing...');
            
            $.ajax({
                url: malisafiPlanManager.ajax_url,
                type: 'POST',
                data: {
                    action: 'malisafi_remove_plan',
                    nonce: malisafiPlanManager.nonce,
                    user_id: userId
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message || malisafiPlanManager.i18n.error);
                        $btn.prop('disabled', false).text('Remove Plan');
                    }
                },
                error: function() {
                    alert(malisafiPlanManager.i18n.error);
                    $btn.prop('disabled', false).text('Remove Plan');
                }
            });
        });
        
        // Toggle plan assignment form
        $('.toggle-plan-form').on('click', function(e) {
            e.preventDefault();
            var userId = $(this).data('user-id');
            $('#malisafi-plan-assignment-form-' + userId).slideToggle();
        });
        
        // Toggle extend subscription form
        $('.toggle-extend-form').on('click', function(e) {
            e.preventDefault();
            var userId = $(this).data('user-id');
            $('#malisafi-extend-subscription-form-' + userId).slideToggle();
        });
        
        // Toggle edit dates form
        $('.toggle-dates-form').on('click', function(e) {
            e.preventDefault();
            var userId = $(this).data('user-id');
            $('#malisafi-edit-dates-form-' + userId).slideToggle();
        });
        
        // Delete subscription permanently
        $('.malisafi-delete-subscription-btn').on('click', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var userId = $btn.data('user-id');
            
            if (!confirm('Are you sure you want to PERMANENTLY DELETE this subscription?\n\nThis action cannot be undone and will:\n- Remove the subscription record from the database\n- Remove user limits\n- This is different from canceling (which keeps the record)')) {
                return;
            }
            
            // Double confirmation for safety
            if (!confirm('FINAL WARNING: This will permanently delete all subscription data. Continue?')) {
                return;
            }
            
            $btn.prop('disabled', true).text('Deleting...');
            
            $.ajax({
                url: malisafiPlanManager.ajax_url,
                type: 'POST',
                data: {
                    action: 'malisafi_delete_subscription',
                    nonce: malisafiPlanManager.nonce,
                    user_id: userId
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message || malisafiPlanManager.i18n.error);
                        $btn.prop('disabled', false).text('Delete Permanently');
                    }
                },
                error: function() {
                    alert(malisafiPlanManager.i18n.error);
                    $btn.prop('disabled', false).text('Delete Permanently');
                }
            });
        });
        
        // Extend subscription
        $('.malisafi-extend-subscription-btn').on('click', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var userId = $btn.data('user-id');
            var $form = $('#malisafi-extend-subscription-form-' + userId);
            var months = $form.find('input[name="extend_months"]').val() || 1;
            
            if (months < 1) {
                alert('Please enter a valid number of months (minimum 1).');
                return;
            }
            
            if (!confirm('Extend this subscription by ' + months + ' month(s)?')) {
                return;
            }
            
            $btn.prop('disabled', true).text('Extending...');
            
            $.ajax({
                url: malisafiPlanManager.ajax_url,
                type: 'POST',
                data: {
                    action: 'malisafi_extend_subscription',
                    nonce: malisafiPlanManager.nonce,
                    user_id: userId,
                    months: months
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message + '\nNew end date: ' + response.data.new_end_date);
                        location.reload();
                    } else {
                        alert(response.data.message || malisafiPlanManager.i18n.error);
                        $btn.prop('disabled', false).text('Extend Subscription');
                    }
                },
                error: function() {
                    alert(malisafiPlanManager.i18n.error);
                    $btn.prop('disabled', false).text('Extend Subscription');
                }
            });
        });
        
        // Update subscription dates
        $('.malisafi-update-dates-btn').on('click', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var userId = $btn.data('user-id');
            var $form = $('#malisafi-edit-dates-form-' + userId);
            var startDate = $form.find('input[name="edit_start_date"]').val();
            var endDate = $form.find('input[name="edit_end_date"]').val();
            
            if (!startDate && !endDate) {
                alert('Please select at least one date to update.');
                return;
            }
            
            if (!confirm('Update subscription dates?')) {
                return;
            }
            
            $btn.prop('disabled', true).text('Updating...');
            
            $.ajax({
                url: malisafiPlanManager.ajax_url,
                type: 'POST',
                data: {
                    action: 'malisafi_update_subscription_dates',
                    nonce: malisafiPlanManager.nonce,
                    user_id: userId,
                    start_date: startDate,
                    end_date: endDate
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message || malisafiPlanManager.i18n.error);
                        $btn.prop('disabled', false).text('Update Dates');
                    }
                },
                error: function() {
                    alert(malisafiPlanManager.i18n.error);
                    $btn.prop('disabled', false).text('Update Dates');
                }
            });
        });
        
    });
    
})(jQuery);
