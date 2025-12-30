/**
 * Property Filters Modern - Mobile-Responsive Filter System
 * Handles mobile toggle, advanced filters, filter count, and AJAX filtering
 */

(function($) {
    'use strict';

    class ModernPropertyFilters {
        constructor() {
            this.filterToggle = $('#mobileFilterToggle');
            this.searchForm = $('#propertySearchForm');
            this.advancedFiltersToggle = $('#advancedFiltersToggle');
            this.filterCount = $('#filterCount');
            this.clearFiltersBtn = $('#clearFilters');
            this.propertiesGrid = $('#propertiesGrid');
            this.sortSelect = $('#sortBy');
            this.perPageSelect = $('#resultsPerPage');
            this.statusSelect = $('#status');
            
            this.filters = {
                property_type: '',
                location: '',
                price_min: '',
                price_max: '',
                bedrooms: '',
                bathrooms: '',
                area_min: '',
                status: '',
                features: '',
                sort: 'date_desc',
                per_page: 12
            };
            
            this.currentPage = 1;
            this.isLoading = false;
            this.isMobile = false;
            
            this.init();
        }

        init() {
            this.checkMobile();
            this.setDefaultStatus();
            this.bindEvents();
            this.updateFilterCount();
            
            // Listen for window resize
            $(window).on('resize', () => {
                this.checkMobile();
            });
        }
        
        checkMobile() {
            const wasMobile = this.isMobile;
            this.isMobile = window.innerWidth <= 768;
            
            // If switched from desktop to mobile or vice versa
            if (wasMobile !== this.isMobile) {
                this.setDefaultStatus();
            }
        }
        
        setDefaultStatus() {
            // On mobile, set status to "for-sale" by default if not already set
            if (this.isMobile && this.statusSelect.length) {
                const currentValue = this.statusSelect.val();
                if (!currentValue || currentValue === '') {
                    this.statusSelect.val('for-sale');
                    this.filters.status = 'for-sale';
                }
            }
        }

        bindEvents() {
            // Mobile filter toggle (not used on mobile in new layout, but kept for compatibility)
            if (this.filterToggle.length && this.searchForm.length) {
                this.filterToggle.on('click', () => {
                    this.searchForm.toggleClass('expanded collapsed');
                    this.filterToggle.toggleClass('active');
                });
            }
            
            // Advanced filters toggle
            if (this.advancedFiltersToggle.length) {
                this.advancedFiltersToggle.on('click', () => {
                    this.searchForm.toggleClass('expanded');
                    this.advancedFiltersToggle.toggleClass('active');
                    
                    const isExpanded = this.searchForm.hasClass('expanded');
                    const toggleText = this.advancedFiltersToggle.find('.toggle-text');
                    const icon = this.advancedFiltersToggle.find('.dashicons');
                    
                    if (isExpanded) {
                        toggleText.text('Show Less Filters');
                        icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
                    } else {
                        toggleText.text('Show More Filters');
                        icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
                    }
                });
            }
            
            // Clear all filters
            if (this.clearFiltersBtn.length) {
                this.clearFiltersBtn.on('click', () => {
                    this.clearAllFilters();
                });
            }
            
            // Form submission
            this.searchForm.on('submit', (e) => {
                e.preventDefault();
                this.collectFilters();
                this.applyFilters();
            });
            
            // Filter changes
            this.searchForm.on('change', 'select, input', () => {
                this.updateFilterCount();
                this.collectFilters();
                this.applyFilters();
            });
            
            // Sort change
            this.sortSelect.on('change', () => {
                this.filters.sort = this.sortSelect.val();
                this.applyFilters();
            });
            
            // Per page change
            this.perPageSelect.on('change', () => {
                this.filters.per_page = this.perPageSelect.val();
                this.applyFilters();
            });
            
            // Load More button
            $(document).on('click', '.load-more-button', (e) => {
                e.preventDefault();
                const $btn = $(e.currentTarget);
                if ($btn.is(':disabled') || this.isLoading) return;
                
                const nextPage = parseInt($btn.data('page'));
                const maxPages = parseInt($btn.data('max-pages'));
                
                this.loadMoreProperties(nextPage, maxPages, $btn);
            });
            
            // Favorite button
            $(document).on('click', '.property-favorite-inline', function(e) {
                e.stopPropagation();
                
                if (!malisafiFilters || !malisafiFilters.isLoggedIn) {
                    alert('Please login to add properties to favorites.');
                    return;
                }
                
                const $btn = $(this);
                const propertyId = $btn.data('property-id');
                
                $.ajax({
                    url: malisafiFilters.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'malisafi_toggle_favorite',
                        nonce: malisafiFilters.nonce,
                        property_id: propertyId
                    },
                    success: function(response) {
                        if (response.success) {
                            $btn.toggleClass('favorited');
                            const newTitle = $btn.hasClass('favorited') 
                                ? 'Remove from favorites' 
                                : 'Add to favorites';
                            $btn.attr('title', newTitle);
                        }
                    }
                });
            });
            
            // Report button
            $(document).on('click', '.property-report-inline', function(e) {
                e.stopPropagation();
                
                const $btn = $(this);
                if ($btn.hasClass('reported')) return;
                
                const propertyId = $btn.data('property-id');
                const reason = prompt('Please provide a reason for reporting this property:');
                
                if (!reason) return;
                
                $.ajax({
                    url: malisafiFilters.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'malisafi_report_property',
                        nonce: malisafiFilters.nonce,
                        property_id: propertyId,
                        reason: reason
                    },
                    success: function(response) {
                        if (response.success) {
                            $btn.addClass('reported');
                            $btn.attr('title', 'Already reported');
                            alert('Property reported successfully.');
                        }
                    }
                });
            });
        }

        updateFilterCount() {
            if (!this.filterCount.length) return;
            
            const inputs = this.searchForm.find('select, input[type="number"]');
            let activeFilters = 0;
            
            inputs.each(function() {
                if ($(this).val() && $(this).attr('type') !== 'submit') {
                    activeFilters++;
                }
            });
            
            this.filterCount.text(activeFilters);
            this.filterCount.toggle(activeFilters > 0);
        }

        collectFilters() {
            this.searchForm.find('[data-filter]').each((i, el) => {
                const $el = $(el);
                const filterName = $el.data('filter');
                const value = $el.val();
                
                if (filterName && this.filters.hasOwnProperty(filterName)) {
                    this.filters[filterName] = value;
                }
            });
        }

        clearAllFilters() {
            this.searchForm.find('select, input[type="number"]').each(function() {
                if ($(this).attr('type') !== 'submit') {
                    $(this).val('');
                }
            });
            
            // Reset filters object
            Object.keys(this.filters).forEach(key => {
                if (key !== 'sort' && key !== 'per_page') {
                    this.filters[key] = '';
                }
            });
            
            this.updateFilterCount();
            this.applyFilters();
        }

        applyFilters() {
            if (this.isLoading) return;
            
            this.isLoading = true;
            this.propertiesGrid.css('opacity', '0.5');
            
            $.ajax({
                url: malisafiFilters.ajaxurl,
                type: 'POST',
                data: {
                    action: 'malisafi_filter_properties',
                    nonce: malisafiFilters.nonce,
                    filters: this.filters,
                    page: 1
                },
                success: (response) => {
                    if (response.success) {
                        this.propertiesGrid.html(response.data.html);
                        this.updateResultsCount(response.data.found_posts);
                        this.updateLoadMore(response.data);
                    }
                },
                complete: () => {
                    this.isLoading = false;
                    this.propertiesGrid.css('opacity', '1');
                    this.currentPage = 1;
                }
            });
        }

        loadMoreProperties(nextPage, maxPages, $btn) {
            this.isLoading = true;
            $btn.prop('disabled', true).find('.dashicons').addClass('spin');
            
            $.ajax({
                url: malisafiFilters.ajaxurl,
                type: 'POST',
                data: {
                    action: 'malisafi_filter_properties',
                    nonce: malisafiFilters.nonce,
                    filters: this.filters,
                    page: nextPage
                },
                success: (response) => {
                    if (response.success) {
                        this.propertiesGrid.append(response.data.html);
                        this.updateCurrentCount();
                        
                        if (nextPage >= maxPages) {
                            $btn.parent().fadeOut();
                        } else {
                            $btn.data('page', nextPage + 1);
                        }
                    }
                },
                complete: () => {
                    this.isLoading = false;
                    $btn.prop('disabled', false).find('.dashicons').removeClass('spin');
                }
            });
        }

        updateResultsCount(count) {
            $('#totalResults').text(count);
            const text = count === 1 ? 'property' : 'properties';
            $('#resultsText').text(text + ' found');
        }

        updateCurrentCount() {
            const currentCount = this.propertiesGrid.find('.property-card-modern').length;
            $('.current-count').text(currentCount);
        }

        updateLoadMore(data) {
            const $loadMore = $('.properties-load-more');
            
            if (data.max_pages > 1) {
                $loadMore.show().find('.load-more-button').data('max-pages', data.max_pages).data('page', 2);
                this.updateCurrentCount();
                $('.total-count').text(data.found_posts);
            } else {
                $loadMore.hide();
            }
        }
    }

    // Initialize on DOM ready
    $(document).ready(function() {
        if ($('#propertySearchForm').length) {
            new ModernPropertyFilters();
        }
    });

})(jQuery);
