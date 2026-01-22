/**
 * Modern Agent Dashboard JavaScript
 * Handles sidebar toggle and interactivity
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Sidebar Toggle
        const sidebar = $('#agentSidebar');
        const toggleBtn = $('#sidebarToggle');
        const sidebarState = localStorage.getItem('agentSidebarCollapsed');

        // Restore saved state
        if (sidebarState === 'true') {
            sidebar.addClass('collapsed');
        }

        // Toggle handler
        toggleBtn.on('click', function(e) {
            e.preventDefault();
            sidebar.toggleClass('collapsed');
            
            // Save state
            const isCollapsed = sidebar.hasClass('collapsed');
            localStorage.setItem('agentSidebarCollapsed', isCollapsed);
            
            // Update aria-label
            $(this).attr('aria-label', isCollapsed ? 'Expand Sidebar' : 'Collapse Sidebar');
        });

        // Mobile: Close sidebar when clicking nav item
        if ($(window).width() <= 768) {
            $('.nav-item').on('click', function() {
                sidebar.addClass('collapsed');
                localStorage.setItem('agentSidebarCollapsed', 'true');
            });
        }

        // Smooth scroll for same-page anchors
        $('a[href^="#"]').on('click', function(e) {
            const target = $(this.getAttribute('href'));
            if (target.length) {
                e.preventDefault();
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 20
                }, 500);
            }
        });

        // Add loading state to buttons
        $('.action-card, .button').on('click', function() {
            if (!$(this).hasClass('no-loading')) {
                $(this).addClass('loading');
            }
        });

        // Animated counters for stat cards
        function animateCounter($element) {
            const target = parseInt($element.text().replace(/,/g, ''));
            if (isNaN(target)) return;

            const duration = 1000;
            const steps = 50;
            const increment = target / steps;
            let current = 0;
            let step = 0;

            const timer = setInterval(function() {
                current += increment;
                step++;
                
                $element.text(Math.floor(current).toLocaleString());
                
                if (step >= steps) {
                    clearInterval(timer);
                    $element.text(target.toLocaleString());
                }
            }, duration / steps);
        }

        // Viewport detection helper - MUST be defined BEFORE use
        $.fn.isInViewport = function() {
            const elementTop = $(this).offset().top;
            const elementBottom = elementTop + $(this).outerHeight();
            const viewportTop = $(window).scrollTop();
            const viewportBottom = viewportTop + $(window).height();
            return elementBottom > viewportTop && elementTop < viewportBottom;
        };

        // Trigger counter animations on load
        if ($('.stat-value').length) {
            $('.stat-value').each(function() {
                if ($(this).isInViewport()) {
                    animateCounter($(this));
                }
            });
        }

        // Lazy load stats on scroll
        $(window).on('scroll', function() {
            $('.stat-value').each(function() {
                if ($(this).isInViewport() && !$(this).hasClass('animated')) {
                    $(this).addClass('animated');
                    animateCounter($(this));
                }
            });
        });

        // Confirmation dialogs
        $('[data-confirm]').on('click', function(e) {
            const message = $(this).data('confirm');
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });

        // Auto-hide notifications after 5 seconds
        setTimeout(function() {
            $('.notice, .updated, .error').fadeOut(400);
        }, 5000);

        // Search/filter functionality if exists
        $('#dashboardSearch').on('input', function() {
            const query = $(this).val().toLowerCase();
            $('.property-item').each(function() {
                const title = $(this).find('h3').text().toLowerCase();
                $(this).toggle(title.includes(query));
            });
        });

        // Tooltips for touch devices
        if ('ontouchstart' in window) {
            $('.nav-item[data-tooltip]').on('touchstart', function(e) {
                if (sidebar.hasClass('collapsed')) {
                    e.preventDefault();
                    $(this).addClass('show-tooltip');
                    setTimeout(() => {
                        $(this).removeClass('show-tooltip');
                    }, 2000);
                }
            });
        }
    });

})(jQuery);
