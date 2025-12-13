/**
 * Malisafi City List
 * 
 * Interactive city search and filtering
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Check if city list exists
        if ($('.malisafi-city-list-container').length === 0) {
            return;
        }

        const $container = $('.malisafi-city-list-container');
        const $searchInput = $('#city-search-input');
        const $cityItems = $('.city-item');
        const $noResults = $('.city-no-results');
        const $totalCities = $('.total-cities');
        const totalCount = $cityItems.length;

        /**
         * Filter cities based on search input
         */
        function filterCities(searchTerm) {
            searchTerm = searchTerm.toLowerCase().trim();
            let visibleCount = 0;

            if (searchTerm === '') {
                // Show all cities
                $cityItems.show();
                visibleCount = totalCount;
                $noResults.hide();
            } else {
                // Filter cities
                $cityItems.each(function() {
                    const $item = $(this);
                    const cityName = $item.data('city-name');

                    if (cityName.includes(searchTerm)) {
                        $item.show();
                        visibleCount++;
                    } else {
                        $item.hide();
                    }
                });

                // Show no results message if needed
                if (visibleCount === 0) {
                    $noResults.show();
                } else {
                    $noResults.hide();
                }
            }

            // Update count
            $totalCities.text(visibleCount);
        }

        /**
         * Search input event handlers
         */
        $searchInput.on('input', function() {
            const searchTerm = $(this).val();
            filterCities(searchTerm);
        });

        // Clear search on Escape key
        $searchInput.on('keydown', function(e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                $(this).val('');
                filterCities('');
            }
        });

        /**
         * City item click tracking
         */
        $('.city-link').on('click', function(e) {
            const cityName = $(this).closest('.city-item').data('city-name');
            
            // Track city click (optional analytics)
            if (typeof gtag !== 'undefined') {
                gtag('event', 'city_click', {
                    'city_name': cityName
                });
            }

            // Add loading state
            $(this).addClass('loading');
        });

        /**
         * Add hover effect for city items
         */
        $('.city-item').on('mouseenter', function() {
            $(this).addClass('hovered');
        }).on('mouseleave', function() {
            $(this).removeClass('hovered');
        });

        /**
         * Keyboard navigation for city items
         */
        $searchInput.on('keydown', function(e) {
            const $visibleItems = $('.city-item:visible');
            
            if ($visibleItems.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                $visibleItems.first().find('.city-link').focus();
            }
        });

        $('.city-link').on('keydown', function(e) {
            const $currentItem = $(this).closest('.city-item');
            const $allVisibleItems = $('.city-item:visible');
            const currentIndex = $allVisibleItems.index($currentItem);

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                const $nextItem = $allVisibleItems.eq(currentIndex + 1);
                if ($nextItem.length) {
                    $nextItem.find('.city-link').focus();
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (currentIndex > 0) {
                    const $prevItem = $allVisibleItems.eq(currentIndex - 1);
                    $prevItem.find('.city-link').focus();
                } else {
                    $searchInput.focus();
                }
            }
        });

        /**
         * Responsive grid adjustment
         */
        function adjustGrid() {
            const $cityList = $('.city-list-grid');
            if ($cityList.length === 0) return;

            const containerWidth = $container.width();
            let columns = parseInt($cityList.data('columns')) || 4;

            // Adjust columns based on screen width
            if (containerWidth < 480) {
                columns = 1;
            } else if (containerWidth < 768) {
                columns = 2;
            } else if (containerWidth < 1024) {
                columns = 3;
            }

            $cityList.css('--columns', columns);
        }

        // Adjust on load and resize
        adjustGrid();
        $(window).on('resize', debounce(adjustGrid, 250));

        /**
         * Debounce helper
         */
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        /**
         * Smooth scroll to top on city click (optional)
         */
        $('.city-link').on('click', function(e) {
            // If on same page, smooth scroll to top
            if (window.location.pathname === malisafiCityList.searchPageUrl) {
                e.preventDefault();
                const url = $(this).attr('href');
                
                $('html, body').animate({ scrollTop: 0 }, 400, function() {
                    window.location.href = url;
                });
            }
        });

        /**
         * Initialize animations
         */
        function initAnimations() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            entry.target.classList.add('animate-in');
                        }, index * 50);
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1
            });

            $cityItems.each(function() {
                observer.observe(this);
            });
        }

        // Initialize animations if supported
        if ('IntersectionObserver' in window) {
            initAnimations();
        } else {
            $cityItems.addClass('animate-in');
        }

    });

})(jQuery);
