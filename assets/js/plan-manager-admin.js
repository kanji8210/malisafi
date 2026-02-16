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
        
    });
    
})(jQuery);
