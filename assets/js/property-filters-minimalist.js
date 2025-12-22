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

        // ==========================
        // Sort Change
        // ==========================
        $('.sort-select').on('change', function() {
            if (isLoading) return;
            
            currentSort = $(this).val();
            currentPage = 1;
            filterProperties();
        });

        // ==========================
        // Pagination
        // ==========================
        $('.prev-page').on('click', function() {
            if (isLoading || currentPage <= 1) return;
            
            currentPage--;
            filterProperties();
        });

        $('.next-page').on('click', function() {
            if (isLoading) return;
            
            const totalPages = parseInt($('.total-pages').text()) || 1;
            if (currentPage >= totalPages) return;
            
            currentPage++;
            filterProperties();
        });

        // ==========================
        // Filter Properties AJAX
        // ==========================
        function filterProperties() {
            if (isLoading) return;
            
            isLoading = true;
            
            // Show loading state
            $('.malisafi-properties-grid').addClass('loading');
            $('.loading-overlay').fadeIn(200);

            $.ajax({
                url: malisafiFilters.ajaxurl,
                type: 'POST',
                data: {
                    action: 'malisafi_filter_properties',
                    nonce: malisafiFilters.nonce,
                    filters: currentFilters,
                    sort: currentSort,
                    page: currentPage
                },
                success: function(response) {
                    if (response.success) {
                        updateResults(response.data);
                    } else {
                        console.error('Filter error:', response.data);
                        showError(response.data.message || 'An error occurred');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    showError('Failed to load properties. Please try again.');
                },
                complete: function() {
                    isLoading = false;
                    $('.malisafi-properties-grid').removeClass('loading');
                    $('.loading-overlay').fadeOut(200);
                }
            });
        }

        // ==========================
        // Update Results
        // ==========================
        function updateResults(data) {
            // Update properties grid
            if (data.html) {
                $('.malisafi-properties-grid').html(data.html);
            }

            // Update count
            if (data.total !== undefined) {
                $('.count-number').text(data.total);
            }

            // Update pagination
            if (data.pagination) {
                updatePagination(data.pagination);
            }

            // Scroll to top
            $('html, body').animate({
                scrollTop: $('.malisafi-minimalist-wrapper').offset().top - 100
            }, 400);
        }

        // ==========================
        // Update Pagination
        // ==========================
        function updatePagination(pagination) {
            $('.current-page').text(pagination.current);
            $('.total-pages').text(pagination.total);

            // Enable/disable buttons
            if (pagination.current <= 1) {
                $('.prev-page').prop('disabled', true);
            } else {
                $('.prev-page').prop('disabled', false);
            }

            if (pagination.current >= pagination.total) {
                $('.next-page').prop('disabled', true);
            } else {
                $('.next-page').prop('disabled', false);
            }
        }

        // ==========================
        // Show Error
        // ==========================
        function showError(message) {
            const errorHtml = `
                <div class="no-properties">
                    <div class="no-properties-icon">
                        <span class="dashicons dashicons-warning"></span>
                    </div>
                    <h3>Error</h3>
                    <p>${message}</p>
                </div>
            `;
            $('.malisafi-properties-grid').html(errorHtml);
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
            currentPage = 1;
            currentSort = 'date-desc';

            // Reset UI
            $('.status-btn').removeClass('active');
            $('.status-btn[data-status=""]').addClass('active');
            $('.filter-select').val('');
            $('.filter-search-input').val('');
            $('.sort-select').val('date-desc');

            // Reload
            filterProperties();
        });

    });

})(jQuery);
