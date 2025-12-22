/**
 * Minimalist Property Filters JavaScript
 * Handles button selection, filtering, and AJAX requests
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        if (!$('.malisafi-minimalist-wrapper').length) {
            return;
        }

        // State management
        let currentFilters = {
            status: '',
            property_type: '',
            county: '',
            search: ''
        };
        let currentPage = 1;
        let currentSort = 'date-desc';
        let isLoading = false;

        // ==========================
        // Status Button Selection
        // ==========================
        $('.status-btn').on('click', function() {
            if (isLoading) return;
            
            // Update active state
            $('.status-btn').removeClass('active');
            $(this).addClass('active');
            
            // Update filter
            currentFilters.status = $(this).data('status');
            currentPage = 1;
            
            // Trigger filter
            filterProperties();
        });

        // ==========================
        // Dropdown Changes
        // ==========================
        $('.filter-select').on('change', function() {
            if (isLoading) return;
            
            const filterType = $(this).data('filter');
            currentFilters[filterType] = $(this).val();
            currentPage = 1;
            
            filterProperties();
        });

        // ==========================
        // Search Input (debounced)
        // ==========================
        let searchTimeout;
        $('.filter-search-input').on('input', function() {
            if (isLoading) return;
            
            clearTimeout(searchTimeout);
            const searchValue = $(this).val();
            
            searchTimeout = setTimeout(function() {
                currentFilters.search = searchValue;
                currentPage = 1;
                filterProperties();
            }, 500); // Wait 500ms after typing stops
        });

        // ==========================
        // Search Button Click
        // ==========================
        $('.filter-search-btn').on('click', function() {
            if (isLoading) return;
            
            currentFilters.search = $('.filter-search-input').val();
            currentPage = 1;
            filterProperties();
        });

        // Pagination removed - handled on results page

        // ==========================
        // Redirect to Filtered Results
        // ==========================
        function filterProperties() {
            if (isLoading) return;
            
            // Get results page URL from wrapper
            const resultsUrl = $('.malisafi-minimalist-wrapper').data('results-url');
            if (!resultsUrl) {
                console.error('Results URL not configured');
                return;
            }
            
            // Build URL with query parameters
            const params = new URLSearchParams();
            
            if (currentFilters.status) {
                params.append('status', currentFilters.status);
            }
            if (currentFilters.property_type) {
                params.append('type', currentFilters.property_type);
            }
            if (currentFilters.county) {
                params.append('county', currentFilters.county);
            }
            if (currentFilters.search) {
                params.append('search', currentFilters.search);
            }
            if (currentSort && currentSort !== 'date-desc') {
                params.append('sort', currentSort);
            }
            
            // Build final URL
            const finalUrl = resultsUrl + (params.toString() ? '?' + params.toString() : '');
            
            // Redirect to results page
            window.location.href = finalUrl;
        }

        // ==========================
        // Keyboard Shortcuts
        // ==========================
        $(document).on('keypress', '.filter-search-input', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                $('.filter-search-btn').trigger('click');
            }
        });

        // ==========================
        // Clear Filters (optional)
        // ==========================
        $(document).on('click', '.clear-filters', function() {
            // Reset all filters
            currentFilters = {
                status: '',
                property_type: '',
                county: '',
                search: ''
            };

            // Reset UI
            $('.status-btn').removeClass('active');
            $('.status-btn[data-status=""]').addClass('active');
            $('.filter-select').val('');
            $('.filter-search-input').val('');
        });

    });

})(jQuery);
