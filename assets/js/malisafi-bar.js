/**
 * Malisafi Bar JavaScript
 * Handles user dropdown toggle and interactions
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Add body class for bar offset
        if ($('.malisafi-bar').length) {
            $('body').addClass('malisafi-bar-active');
        }
        
        // User dropdown toggle
        $('.malisafi-bar-user-toggle').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const $toggle = $(this);
            const $dropdown = $('.malisafi-bar-user-dropdown');
            const isExpanded = $toggle.attr('aria-expanded') === 'true';
            
            if (isExpanded) {
                closeDropdown();
            } else {
                openDropdown();
            }
        });
        
        // Close dropdown when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.malisafi-bar-user').length) {
                closeDropdown();
            }
        });
        
        // Close dropdown on ESC key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                closeDropdown();
            }
        });
        
        // Close dropdown when clicking dropdown item
        $('.malisafi-bar-dropdown-item').on('click', function() {
            closeDropdown();
        });
        
        function openDropdown() {
            $('.malisafi-bar-user-toggle').attr('aria-expanded', 'true');
            $('.malisafi-bar-user-dropdown').removeAttr('hidden');
        }
        
        function closeDropdown() {
            $('.malisafi-bar-user-toggle').attr('aria-expanded', 'false');
            $('.malisafi-bar-user-dropdown').attr('hidden', '');
        }
    });
    
})(jQuery);
