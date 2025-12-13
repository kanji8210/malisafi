/**
 * Agent Dashboard JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Switch agent view functionality
        $('#view_as_agent').on('change', function() {
            var agentId = $(this).val();
            
            if (!agentId) {
                return;
            }
            
            // Show loading indicator
            var $btn = $(this).closest('form').find('button[type="submit"]');
            var originalText = $btn.text();
            $btn.text('Switching...').prop('disabled', true);
            
            // AJAX request to switch agent view
            $.ajax({
                url: malisafiAgent.ajaxurl,
                type: 'POST',
                data: {
                    action: 'switch_agent_view',
                    nonce: malisafiAgent.nonce,
                    agent_id: agentId
                },
                success: function(response) {
                    if (response.success) {
                        // Reload page to show new agent view
                        window.location.reload();
                    } else {
                        alert(response.data.message || 'Failed to switch agent view');
                        $btn.text(originalText).prop('disabled', false);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                    $btn.text(originalText).prop('disabled', false);
                }
            });
        });
        
        // Confirm before exiting agent view
        $('.exit-agent-view').on('click', function(e) {
            if (!confirm('Exit agent view and return to admin dashboard?')) {
                e.preventDefault();
            }
        });
        
        // Animate stat cards on page load
        $('.stat-card').each(function(index) {
            $(this).css({
                'opacity': 0,
                'transform': 'translateY(20px)'
            }).delay(index * 100).animate({
                'opacity': 1
            }, 500).css('transform', 'translateY(0)');
        });
        
        // Enhanced table interactions
        $('.widefat tbody tr').hover(
            function() {
                $(this).css('background-color', '#f0f0f1');
            },
            function() {
                $(this).css('background-color', '');
            }
        );
        
        // Quick actions button effects
        $('.quick-action-btn').on('click', function(e) {
            $(this).css('transform', 'scale(0.95)');
            setTimeout(function() {
                $(this).css('transform', '');
            }.bind(this), 100);
        });
        
        // Auto-hide success messages after 5 seconds
        setTimeout(function() {
            $('.notice.notice-success').fadeOut();
        }, 5000);
        
        // Lead status update (if inline editing is enabled)
        $('.lead-status-select').on('change', function() {
            var $select = $(this);
            var leadId = $select.data('lead-id');
            var newStatus = $select.val();
            
            $.ajax({
                url: malisafiAgent.ajaxurl,
                type: 'POST',
                data: {
                    action: 'update_lead_status',
                    nonce: malisafiAgent.nonce,
                    lead_id: leadId,
                    status: newStatus
                },
                success: function(response) {
                    if (response.success) {
                        // Show success indicator
                        $select.css('border-color', '#46b450');
                        setTimeout(function() {
                            $select.css('border-color', '');
                        }, 2000);
                    }
                }
            });
        });
        
        // Copy contact information to clipboard
        $('.copy-contact').on('click', function(e) {
            e.preventDefault();
            var text = $(this).data('contact');
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    alert('Contact information copied to clipboard!');
                });
            } else {
                // Fallback for older browsers
                var $temp = $('<input>');
                $('body').append($temp);
                $temp.val(text).select();
                document.execCommand('copy');
                $temp.remove();
                alert('Contact information copied to clipboard!');
            }
        });
        
        // Filter properties by status
        $('#property-status-filter').on('change', function() {
            var status = $(this).val();
            var $rows = $('.property-row');
            
            if (status === 'all') {
                $rows.show();
            } else {
                $rows.hide();
                $rows.filter('[data-status="' + status + '"]').show();
            }
        });
        
        // Real-time search in tables
        $('#table-search').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('.searchable-table tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });
        
        // Smooth scroll to sections
        $('a[href^="#"]').on('click', function(e) {
            var target = $(this.hash);
            if (target.length) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: target.offset().top - 32 // Account for admin bar
                }, 500);
            }
        });
        
        // Initialize tooltips if available
        if (typeof $.fn.tooltip !== 'undefined') {
            $('[data-toggle="tooltip"]').tooltip();
        }
        
        // Responsive table wrapper
        $('.wp-list-table').wrap('<div class="table-responsive"></div>');
        
        // Print property list
        $('#print-properties').on('click', function(e) {
            e.preventDefault();
            window.print();
        });
        
        // Export data (if implemented)
        $('#export-data').on('click', function(e) {
            e.preventDefault();
            var type = $(this).data('export-type');
            var url = malisafiAgent.ajaxurl + '?action=export_agent_data&type=' + type + '&nonce=' + malisafiAgent.nonce;
            window.location.href = url;
        });
    });
    
    // Print styles
    window.addEventListener('beforeprint', function() {
        $('.quick-actions-grid, .admin-tools-card, .page-title-action').hide();
    });
    
    window.addEventListener('afterprint', function() {
        $('.quick-actions-grid, .admin-tools-card, .page-title-action').show();
    });
    
})(jQuery);
