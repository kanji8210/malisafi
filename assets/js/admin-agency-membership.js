/**
 * Agency Membership Admin JavaScript
 *
 * Handles AJAX interactions for agency membership management
 */

(function($) {
    'use strict';

    var MalisafiAgencyMembership = {

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Plan management
            $('#add-new-plan-btn').on('click', this.showPlanModal.bind(this));
            $('.edit-plan-btn').on('click', this.editPlan.bind(this));
            $('.delete-plan-btn').on('click', this.deletePlan.bind(this));
            $('#save-plan-btn').on('click', this.savePlan.bind(this));
            $('#cancel-plan-btn, .modal-close').on('click', this.closeModal.bind(this));

            // Subscription management
            $('#create-subscription-btn').on('click', this.showSubscriptionModal.bind(this));
            $('#create-subscription-btn').on('click', this.createSubscription.bind(this));
            $('#cancel-subscription-btn').on('click', this.closeModal.bind(this));
            $('.subscription-status').on('change', this.updateSubscriptionStatus.bind(this));

            // Modal overlay click
            $('.malisafi-modal').on('click', function(e) {
                if (e.target === this) {
                    MalisafiAgencyMembership.closeModal();
                }
            });
        },

        showPlanModal: function() {
            this.resetPlanForm();
            $('#modal-title').text(malisafiAgencyMembership.i18n.addPlan || 'Add Membership Plan');
            $('#plan-modal').show();
            $('#plan_name').focus();
        },

        editPlan: function(e) {
            e.preventDefault();
            var planId = $(e.target).data('plan-id');

            // Load plan data via AJAX
            $.ajax({
                url: malisafiAgencyMembership.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'malisafi_get_plan_data',
                    plan_id: planId,
                    nonce: malisafiAgencyMembership.nonce
                },
                success: function(response) {
                    if (response.success) {
                        MalisafiAgencyMembership.populatePlanForm(response.data);
                        $('#modal-title').text(malisafiAgencyMembership.i18n.editPlan || 'Edit Membership Plan');
                        $('#plan-modal').show();
                    } else {
                        alert(response.data || malisafiAgencyMembership.i18n.error);
                    }
                },
                error: function() {
                    alert(malisafiAgencyMembership.i18n.error);
                }
            });
        },

        populatePlanForm: function(plan) {
            $('#plan_id').val(plan.id);
            $('#plan_name').val(plan.plan_name);
            $('#plan_description').val(plan.plan_description);
            $('#price').val(plan.price);
            $('#currency').val(plan.currency);
            $('#billing_interval').val(plan.billing_interval);
            $('#max_agents').val(plan.max_agents);
            $('#max_properties').val(plan.max_properties);
            $('#stripe_price_id').val(plan.stripe_price_id);
            $('#is_popular').prop('checked', plan.is_popular == 1);
            $('#sort_order').val(plan.sort_order);
        },

        resetPlanForm: function() {
            $('#plan-form')[0].reset();
            $('#plan_id').val('');
            $('#is_popular').prop('checked', false);
        },

        savePlan: function() {
            var formData = new FormData(document.getElementById('plan-form'));
            formData.append('action', 'malisafi_save_membership_plan');
            formData.append('nonce', malisafiAgencyMembership.nonce);

            // Convert checkbox to boolean
            if ($('#is_popular').is(':checked')) {
                formData.append('is_popular', '1');
            }

            $('#save-plan-btn').prop('disabled', true).text('Saving...');

            $.ajax({
                url: malisafiAgencyMembership.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        alert(malisafiAgencyMembership.i18n.saveSuccess);
                        MalisafiAgencyMembership.closeModal();
                        location.reload();
                    } else {
                        alert(response.data || malisafiAgencyMembership.i18n.error);
                    }
                },
                error: function() {
                    alert(malisafiAgencyMembership.i18n.error);
                },
                complete: function() {
                    $('#save-plan-btn').prop('disabled', false).text('Save Plan');
                }
            });
        },

        deletePlan: function(e) {
            e.preventDefault();
            var planId = $(e.target).data('plan-id');

            if (!confirm(malisafiAgencyMembership.i18n.confirmDelete)) {
                return;
            }

            $.ajax({
                url: malisafiAgencyMembership.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'malisafi_delete_membership_plan',
                    plan_id: planId,
                    nonce: malisafiAgencyMembership.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(malisafiAgencyMembership.i18n.deleteSuccess);
                        location.reload();
                    } else {
                        alert(response.data || malisafiAgencyMembership.i18n.error);
                    }
                },
                error: function() {
                    alert(malisafiAgencyMembership.i18n.error);
                }
            });
        },

        showSubscriptionModal: function() {
            $('#subscription-modal').show();
        },

        createSubscription: function() {
            var formData = new FormData(document.getElementById('subscription-form'));
            formData.append('action', 'malisafi_create_agency_subscription');
            formData.append('nonce', malisafiAgencyMembership.nonce);

            $('#create-subscription-btn').prop('disabled', true).text('Creating...');

            $.ajax({
                url: malisafiAgencyMembership.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        alert(malisafiAgencyMembership.i18n.subscriptionCreated);
                        MalisafiAgencyMembership.closeModal();
                        location.reload();
                    } else {
                        alert(response.data || malisafiAgencyMembership.i18n.error);
                    }
                },
                error: function() {
                    alert(malisafiAgencyMembership.i18n.error);
                },
                complete: function() {
                    $('#create-subscription-btn').prop('disabled', false).text('Create Subscription');
                }
            });
        },

        updateSubscriptionStatus: function(e) {
            var subscriptionId = $(e.target).data('subscription-id');
            var status = $(e.target).val();

            $.ajax({
                url: malisafiAgencyMembership.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'malisafi_update_subscription_status',
                    subscription_id: subscriptionId,
                    status: status,
                    nonce: malisafiAgencyMembership.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message briefly
                        var $select = $(e.target);
                        $select.css('background-color', '#d4edda');
                        setTimeout(function() {
                            $select.css('background-color', '');
                        }, 1000);
                    } else {
                        alert(response.data || malisafiAgencyMembership.i18n.error);
                        // Revert selection on error
                        location.reload();
                    }
                },
                error: function() {
                    alert(malisafiAgencyMembership.i18n.error);
                    location.reload();
                }
            });
        },

        closeModal: function() {
            $('.malisafi-modal').hide();
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        MalisafiAgencyMembership.init();
    });

})(jQuery);