/**
 * Agent Management Admin JavaScript
 *
 * @package MalisafiMLS
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Approve agent
        $('.approve-agent').on('click', function() {
            const agentId = $(this).data('agent-id');
            const $row = $(this).closest('tr');
            
            if (!confirm('Are you sure you want to approve this agent?')) {
                return;
            }
            
            $.ajax({
                url: malisafiAgentManagement.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'malisafi_approve_agent',
                    nonce: malisafiAgentManagement.nonce,
                    agent_id: agentId
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                        });
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        });
        
        // Reject agent
        $('.reject-agent').on('click', function() {
            const agentId = $(this).data('agent-id');
            const $row = $(this).closest('tr');
            
            if (!confirm('Are you sure you want to reject this agent?')) {
                return;
            }
            
            $.ajax({
                url: malisafiAgentManagement.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'malisafi_reject_agent',
                    nonce: malisafiAgentManagement.nonce,
                    agent_id: agentId
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                        });
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        });
        
        // Suspend agent
        $('.suspend-agent').on('click', function() {
            const agentId = $(this).data('agent-id');
            const $row = $(this).closest('tr');
            
            if (!confirm('Are you sure you want to suspend this agent?')) {
                return;
            }
            
            $.ajax({
                url: malisafiAgentManagement.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'malisafi_suspend_agent',
                    nonce: malisafiAgentManagement.nonce,
                    agent_id: agentId
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                        });
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                }
            });
        });
        
        // View details
        $('.view-details').on('click', function(e) {
            e.preventDefault();
            const agentId = $(this).data('agent-id');
            
            // For now, show a modal with agent details from the row
            const $row = $(this).closest('tr');
            // Implementation to show modal with full details would go here
            
            alert('View details functionality - to be implemented with modal');
        });
        
        // Close modal
        $('.agent-modal-close, .agent-modal-overlay').on('click', function() {
            $('.agent-modal').fadeOut();
        });
        
    });
    
})(jQuery);
