/**
 * Modern Property Filters JavaScript
 * Handles AJAX filtering, sorting, and view switching
 */

(function($) {
    'use strict';

    class PropertyFilters {
        constructor() {
            this.container = $('.malisafi-properties-wrapper');
            this.filtersForm = $('.malisafi-filters-sidebar');
            this.propertiesGrid = $('.properties-grid');
            this.resultsCount = $('.results-count');
            this.activeFiltersContainer = $('.active-filters');
            
            this.filters = {
                search: '',
                property_type: '',
                status: '',
                bedrooms: '',
                bathrooms: '',
                price_min: '',
                price_max: '',
                area_min: '',
                area_max: '',
                location: '',
                features: [],
                sort: 'date_desc'
            };
            
            this.currentView = 'grid';
            this.currentPage = 1;
            this.isLoading = false;
            
            this.init();
        }

        init() {
            this.bindEvents();
            this.loadUrlFilters();
            this.loadSavedFilters();
        }

        bindEvents() {
            // Search input with debounce
            let searchTimeout;
            $(document).on('input', '.filter-search input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.filters.search = $(e.target).val();
                    this.applyFilters();
                }, 500);
            });

            // Select changes
            $(document).on('change', '.filter-select', (e) => {
                const $select = $(e.target);
                const filterName = $select.data('filter');
                this.filters[filterName] = $select.val();
                this.applyFilters();
            });

            // Price range inputs
            $(document).on('change', '.price-input-wrapper input', (e) => {
                const $input = $(e.target);
                const filterName = $input.data('filter');
                this.filters[filterName] = $input.val();
            });

            // Area range inputs
            $(document).on('change', '.area-input-wrapper input', (e) => {
                const $input = $(e.target);
                const filterName = $input.data('filter');
                this.filters[filterName] = $input.val();
            });

            // Checkboxes
            $(document).on('change', '.filter-checkbox input[type="checkbox"]', (e) => {
                const $checkbox = $(e.target);
                const value = $checkbox.val();
                const filterName = $checkbox.data('filter');

                if (!this.filters[filterName]) {
                    this.filters[filterName] = [];
                }

                if ($checkbox.is(':checked')) {
                    if (!this.filters[filterName].includes(value)) {
                        this.filters[filterName].push(value);
                    }
                } else {
                    this.filters[filterName] = this.filters[filterName].filter(v => v !== value);
                }
            });

            // Range sliders
            $(document).on('input', '.range-slider', (e) => {
                const $slider = $(e.target);
                const filterName = $slider.data('filter');
                this.filters[filterName] = $slider.val();
                $slider.siblings('.range-value').find('span').text($slider.val());
            });

            // Apply filters button
            $(document).on('click', '.filter-apply', () => {
                this.applyFilters();
            });

            // Clear filters button
            $(document).on('click', '.filter-clear', () => {
                this.clearFilters();
            });

            // Reset button
            $(document).on('click', '.filter-reset', () => {
                this.clearFilters();
            });

            // Sort dropdown
            $(document).on('change', '.results-sort select', (e) => {
                this.filters.sort = $(e.target).val();
                this.applyFilters();
            });

            // View toggle
            $(document).on('click', '.view-toggle button', (e) => {
                const $btn = $(e.target).closest('button');
                const view = $btn.data('view');
                this.switchView(view);
            });

            // Remove filter chip
            $(document).on('click', '.filter-chip .remove', (e) => {
                const $chip = $(e.target).closest('.filter-chip');
                const filterName = $chip.data('filter');
                const filterValue = $chip.data('value');
                this.removeFilter(filterName, filterValue);
            });

            // Load More button
            $(document).on('click', '.load-more-button', (e) => {
                const $btn = $(e.target).closest('.load-more-button');
                if ($btn.is(':disabled') || this.isLoading) return;
                
                const nextPage = parseInt($btn.data('page'));
                const maxPages = parseInt($btn.data('max-pages'));
                
                this.loadMoreProperties(nextPage, maxPages, $btn);
            });

            // Property card click
            $(document).on('click', '.property-card-modern', function(e) {
                if ($(e.target).closest('.property-favorite, .property-favorite-inline, .property-report, .property-report-inline').length) {
                    return; // Don't navigate if clicking action buttons
                }
                const url = $(this).data('url');
                if (url) {
                    window.location.href = url;
                }
            });

            // Favorite button (both overlay and inline)
            $(document).on('click', '.property-favorite, .property-favorite-inline', function(e) {
                e.stopPropagation();
                
                // Check if user is logged in
                if (!malisafiFilters.isLoggedIn) {
                    alert('Please login or create an account to add properties to favorites.');
                    return;
                }
                
                const $btn = $(this);
                const propertyId = $btn.data('property-id');
                const isFavorited = $btn.hasClass('favorited');
                
                // Find both buttons (overlay and inline) for this property
                const $allBtns = $('.property-favorite[data-property-id="' + propertyId + '"], .property-favorite-inline[data-property-id="' + propertyId + '"]');
                
                // Toggle immediately for better UX
                $allBtns.toggleClass('favorited');
                
                // AJAX call to save favorite
                $.ajax({
                    url: malisafiFilters.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'malisafi_toggle_favorite',
                        nonce: malisafiFilters.nonce,
                        property_id: propertyId
                    },
                    success: function(response) {
                        if (!response.success) {
                            // Revert on error
                            $allBtns.toggleClass('favorited');
                            alert(response.data.message || 'Failed to update favorites');
                        }
                    },
                    error: function() {
                        // Revert on error
                        $allBtns.toggleClass('favorited');
                        alert('Failed to update favorites. Please try again.');
                    }
                });
            });

            // Report button (both overlay and inline)
            $(document).on('click', '.property-report, .property-report-inline', function(e) {
                e.stopPropagation();
                
                const $btn = $(this);
                
                // Check if already reported
                if ($btn.hasClass('reported')) {
                    alert('You have already reported this property.');
                    return;
                }
                
                // Check if user is logged in
                if (!malisafiFilters.isLoggedIn) {
                    alert('You must be logged in to report a property. Please login or create an account.');
                    return;
                }
                
                const propertyId = $(this).data('property-id');
                
                // Trigger the report modal (if property-moderation.js is loaded)
                if (typeof reportModal !== 'undefined' && reportModal.init) {
                    $('#malisafi-report-modal').data('property-id', propertyId).fadeIn(200);
                    $('body').addClass('modal-open');
                } else {
                    // Fallback if modal not available
                    const confirmReport = confirm('Do you want to report this property?');
                    if (confirmReport) {
                        window.location.href = malisafiFilters.homeUrl + '/report-property/?property_id=' + propertyId;
                    }
                }
            });
        }

        applyFilters(resetPage = true) {
            if (this.isLoading) return;
            
            if (resetPage) {
                this.currentPage = 1;
            }
            
            this.isLoading = true;
            this.showLoading();
            this.updateActiveFilters();
            this.saveFilters();

            const data = {
                action: 'malisafi_filter_properties',
                nonce: malisafiFilters.nonce,
                filters: this.filters,
                page: this.currentPage,
                per_page: 21
            };

            $.ajax({
                url: malisafiFilters.ajaxurl,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.updateResults(response.data);
                    } else {
                        this.showError(response.data.message);
                    }
                },
                error: (xhr, status, error) => {
                    this.showError('An error occurred while filtering properties.');
                    console.error('Filter error:', error);
                },
                complete: () => {
                    this.isLoading = false;
                    this.hideLoading();
                }
            });
        }

        updateResults(data) {
            // Update properties grid
            this.propertiesGrid.html(data.html);
            
            // Update results count
            const countText = data.total === 1 
                ? `<strong>1</strong> property found`
                : `<strong>${data.total}</strong> properties found`;
            this.resultsCount.html(countText);
            
            // Update Load More button
            const currentCount = $('.property-card-modern').length;
            $('.current-count').text(currentCount);
            $('.total-count').text(data.total);
            
            if (data.max_pages && data.max_pages > 1) {
                const $loadMore = $('.load-more-button');
                if ($loadMore.length === 0) {
                    // Create load more button
                    const loadMoreHtml = `
                        <div class="properties-load-more">
                            <button class="load-more-button" data-page="2" data-max-pages="${data.max_pages}">
                                <span class="dashicons dashicons-update"></span>
                                Load More Properties
                            </button>
                            <div class="load-more-info">
                                Showing <span class="current-count">${currentCount}</span> of <span class="total-count">${data.total}</span> properties
                            </div>
                        </div>
                    `;
                    $('.malisafi-properties-content').append(loadMoreHtml);
                } else {
                    $loadMore.data('page', 2).data('max-pages', data.max_pages);
                }
            } else {
                $('.properties-load-more').remove();
            }
            
            // Show/hide no results
            if (data.total === 0) {
                this.showNoResults();
            }
        }
        
        loadMoreProperties(page, maxPages, $btn) {
            this.isLoading = true;
            
            // Update button state
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Loading...');
            
            const data = {
                action: 'malisafi_filter_properties',
                nonce: malisafiFilters.nonce,
                filters: this.filters,
                page: page,
                per_page: 21
            };
            
            $.ajax({
                url: malisafiFilters.ajaxurl,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        // Append new properties
                        this.propertiesGrid.append(response.data.html);
                        
                        // Update counts
                        const currentCount = $('.property-card-modern').length;
                        $('.current-count').text(currentCount);
                        
                        // Update button
                        if (page >= maxPages) {
                            // No more properties
                            $('.properties-load-more').fadeOut(300, function() {
                                $(this).remove();
                            });
                        } else {
                            // Update button for next page
                            $btn.data('page', page + 1)
                                .prop('disabled', false)
                                .html('<span class="dashicons dashicons-update"></span> Load More Properties');
                        }
                    } else {
                        $btn.prop('disabled', false)
                            .html('<span class="dashicons dashicons-update"></span> Load More Properties');
                        alert(response.data.message || 'Error loading properties');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Load more error:', error);
                    $btn.prop('disabled', false)
                        .html('<span class="dashicons dashicons-update"></span> Load More Properties');
                    alert('An error occurred while loading more properties.');
                },
                complete: () => {
                    this.isLoading = false;
                }
            });
        }

        showNoResults() {
            const html = `
                <div class="no-results">
                    <span class="dashicons dashicons-search"></span>
                    <h3>No properties found</h3>
                    <p>Try adjusting your filters to find what you're looking for.</p>
                    <button class="filter-reset">Clear all filters</button>
                </div>
            `;
            this.propertiesGrid.html(html);
        }

        showLoading() {
            this.filtersForm.addClass('loading');
            this.propertiesGrid.html(`
                <div class="properties-loading">
                    <div class="spinner"></div>
                </div>
            `);
        }

        hideLoading() {
            this.filtersForm.removeClass('loading');
        }

        showError(message) {
            this.propertiesGrid.html(`
                <div class="no-results">
                    <span class="dashicons dashicons-warning"></span>
                    <h3>Error</h3>
                    <p>${message}</p>
                </div>
            `);
        }

        clearFilters() {
            // Reset all filter values
            this.filters = {
                search: '',
                property_type: '',
                status: '',
                bedrooms: '',
                bathrooms: '',
                price_min: '',
                price_max: '',
                area_min: '',
                area_max: '',
                location: '',
                features: [],
                sort: 'date_desc'
            };
            
            // Reset form elements
            $('.filter-search input').val('');
            $('.filter-select').val('');
            $('.price-input-wrapper input').val('');
            $('.area-input-wrapper input').val('');
            $('.filter-checkbox input[type="checkbox"]').prop('checked', false);
            $('.range-slider').val(0);
            $('.range-value span').text('0');
            
            // Clear active filters
            this.activeFiltersContainer.addClass('hidden').empty();
            
            // Apply cleared filters
            this.applyFilters();
        }

        removeFilter(filterName, filterValue) {
            if (Array.isArray(this.filters[filterName])) {
                this.filters[filterName] = this.filters[filterName].filter(v => v !== filterValue);
                // Uncheck the corresponding checkbox
                $(`.filter-checkbox input[value="${filterValue}"]`).prop('checked', false);
            } else {
                this.filters[filterName] = '';
                // Clear the corresponding input
                $(`.filter-select[data-filter="${filterName}"]`).val('');
                $(`.filter-search input, .price-input-wrapper input, .area-input-wrapper input`)
                    .filter(`[data-filter="${filterName}"]`).val('');
            }
            
            this.applyFilters();
        }

        updateActiveFilters() {
            const chips = [];
            
            // Search
            if (this.filters.search) {
                chips.push({
                    label: `Search: ${this.filters.search}`,
                    filter: 'search',
                    value: this.filters.search
                });
            }
            
            // Property Type
            if (this.filters.property_type) {
                const label = $(`.filter-select[data-filter="property_type"] option[value="${this.filters.property_type}"]`).text();
                chips.push({
                    label: `Type: ${label}`,
                    filter: 'property_type',
                    value: this.filters.property_type
                });
            }
            
            // Status
            if (this.filters.status) {
                const label = $(`.filter-select[data-filter="status"] option[value="${this.filters.status}"]`).text();
                chips.push({
                    label: `Status: ${label}`,
                    filter: 'status',
                    value: this.filters.status
                });
            }
            
            // Bedrooms
            if (this.filters.bedrooms) {
                chips.push({
                    label: `Bedrooms: ${this.filters.bedrooms}+`,
                    filter: 'bedrooms',
                    value: this.filters.bedrooms
                });
            }
            
            // Bathrooms
            if (this.filters.bathrooms) {
                chips.push({
                    label: `Bathrooms: ${this.filters.bathrooms}+`,
                    filter: 'bathrooms',
                    value: this.filters.bathrooms
                });
            }
            
            // Price Range
            if (this.filters.price_min || this.filters.price_max) {
                const min = this.filters.price_min ? `$${parseInt(this.filters.price_min).toLocaleString()}` : 'Any';
                const max = this.filters.price_max ? `$${parseInt(this.filters.price_max).toLocaleString()}` : 'Any';
                chips.push({
                    label: `Price: ${min} - ${max}`,
                    filter: 'price',
                    value: 'range'
                });
            }
            
            // Area Range
            if (this.filters.area_min || this.filters.area_max) {
                const min = this.filters.area_min || 'Any';
                const max = this.filters.area_max || 'Any';
                chips.push({
                    label: `Area: ${min} - ${max} sq ft`,
                    filter: 'area',
                    value: 'range'
                });
            }
            
            // Location
            if (this.filters.location) {
                const label = $(`.filter-select[data-filter="location"] option[value="${this.filters.location}"]`).text() || this.filters.location;
                chips.push({
                    label: `Location: ${label}`,
                    filter: 'location',
                    value: this.filters.location
                });
            }
            
            // Features
            if (this.filters.features && this.filters.features.length > 0) {
                this.filters.features.forEach(feature => {
                    const label = $(`.filter-checkbox input[value="${feature}"]`).siblings('label').text().trim();
                    chips.push({
                        label: label,
                        filter: 'features',
                        value: feature
                    });
                });
            }
            
            // Render chips
            if (chips.length > 0) {
                const html = chips.map(chip => `
                    <div class="filter-chip" data-filter="${chip.filter}" data-value="${chip.value}">
                        <span>${chip.label}</span>
                        <span class="remove">
                            <span class="dashicons dashicons-no-alt"></span>
                        </span>
                    </div>
                `).join('');
                
                this.activeFiltersContainer.html(html).removeClass('hidden');
            } else {
                this.activeFiltersContainer.addClass('hidden').empty();
            }
        }

        switchView(view) {
            this.currentView = view;
            
            // Update button states
            $('.view-toggle button').removeClass('active');
            $(`.view-toggle button[data-view="${view}"]`).addClass('active');
            
            // Update grid class
            if (view === 'list') {
                this.propertiesGrid.addClass('list-view');
            } else {
                this.propertiesGrid.removeClass('list-view');
            }
            
            // Save preference
            localStorage.setItem('malisafi_view_preference', view);
        }

        saveFilters() {
            localStorage.setItem('malisafi_filters', JSON.stringify(this.filters));
        }

        loadSavedFilters() {
            // Load view preference
            const savedView = localStorage.getItem('malisafi_view_preference');
            if (savedView) {
                this.switchView(savedView);
            }
            
            // Optionally load saved filters (disabled by default)
            // const savedFilters = localStorage.getItem('malisafi_filters');
            // if (savedFilters) {
            //     this.filters = JSON.parse(savedFilters);
            //     this.populateFiltersForm();
            //     this.applyFilters();
            // }
        }

        loadUrlFilters() {
            const params = new URLSearchParams(window.location.search);
            if (!params || params.toString() === '') {
                return;
            }

            let hasFilters = false;

            const searchValue = params.get('search') || params.get('s');
            if (searchValue) {
                this.filters.search = searchValue;
                hasFilters = true;
            }

            const propertyType = params.get('property_type') || params.get('type');
            if (propertyType) {
                this.filters.property_type = propertyType;
                hasFilters = true;
            }

            const status = params.get('status') || params.get('property_status');
            if (status) {
                this.filters.status = status;
                hasFilters = true;
            }

            const priceMin = params.get('price_min') || params.get('min_price');
            if (priceMin) {
                this.filters.price_min = priceMin;
                hasFilters = true;
            }

            const priceMax = params.get('price_max') || params.get('max_price');
            if (priceMax) {
                this.filters.price_max = priceMax;
                hasFilters = true;
            }

            const bedrooms = params.get('bedrooms');
            if (bedrooms) {
                this.filters.bedrooms = bedrooms;
                hasFilters = true;
            }

            const bathrooms = params.get('bathrooms');
            if (bathrooms) {
                this.filters.bathrooms = bathrooms;
                hasFilters = true;
            }

            const location = params.get('location');
            if (location) {
                this.filters.location = location;
                hasFilters = true;
            }

            const sort = params.get('sort');
            if (sort) {
                this.filters.sort = sort;
                hasFilters = true;
            }

            if (hasFilters) {
                this.populateFiltersForm();
                this.applyFilters();
            }
        }

        populateFiltersForm() {
            // Populate form with saved filter values
            if (this.filters.search) {
                $('.filter-search input').val(this.filters.search);
            }
            
            if (this.filters.property_type) {
                $('.filter-select[data-filter="property_type"]').val(this.filters.property_type);
            }
            
            if (this.filters.status) {
                $('.filter-select[data-filter="status"]').val(this.filters.status);
            }
            
            if (this.filters.bedrooms) {
                $('.filter-select[data-filter="bedrooms"]').val(this.filters.bedrooms);
            }
            
            if (this.filters.bathrooms) {
                $('.filter-select[data-filter="bathrooms"]').val(this.filters.bathrooms);
            }
            
            if (this.filters.price_min) {
                $('.price-input-wrapper input[data-filter="price_min"]').val(this.filters.price_min);
            }
            
            if (this.filters.price_max) {
                $('.price-input-wrapper input[data-filter="price_max"]').val(this.filters.price_max);
            }

            if (this.filters.location) {
                $('.filter-select[data-filter="location"]').val(this.filters.location);
            }

            if (this.filters.sort) {
                $('.results-sort select').val(this.filters.sort);
            }
            
            if (this.filters.features && this.filters.features.length > 0) {
                this.filters.features.forEach(feature => {
                    $(`.filter-checkbox input[value="${feature}"]`).prop('checked', true);
                });
            }
        }
    }

    // Initialize on document ready
    $(document).ready(function() {
        if ($('.malisafi-properties-wrapper').length) {
            new PropertyFilters();
        }

        const $sidebar = $('.malisafi-filters-sidebar');
        if ($sidebar.length) {
            const isMobile = window.matchMedia('(max-width: 900px)').matches;
            if (isMobile) {
                $sidebar.addClass('is-collapsed');
            }
        }

        $(document).on('click', '.filters-toggle', function() {
            const $btn = $(this);
            const $panel = $btn.closest('.malisafi-filters-sidebar');
            const isMobile = window.matchMedia('(max-width: 900px)').matches;
            const $icon = $btn.find('.dashicons');

            if (isMobile) {
                $panel.toggleClass('is-open');
                const isOpen = $panel.hasClass('is-open');
                $btn.attr('aria-expanded', isOpen ? 'true' : 'false');
                $btn.find('.filters-toggle-text').text(isOpen ? 'Hide' : 'Show');
                if ($icon.length) {
                    $icon.toggleClass('dashicons-arrow-down-alt2', !isOpen)
                        .toggleClass('dashicons-arrow-up-alt2', isOpen);
                }
            } else {
                $panel.toggleClass('is-collapsed');
                const isCollapsed = $panel.hasClass('is-collapsed');
                $btn.attr('aria-expanded', isCollapsed ? 'false' : 'true');
                $btn.find('.filters-toggle-text').text(isCollapsed ? 'Show' : 'Hide');
                if ($icon.length) {
                    $icon.toggleClass('dashicons-arrow-down-alt2', isCollapsed)
                        .toggleClass('dashicons-arrow-up-alt2', !isCollapsed);
                }
            }
        });
    });

})(jQuery);
