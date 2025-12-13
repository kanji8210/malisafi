/**
 * Public JavaScript for Malisafi MLS
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Initialize public functionality
        initPublic();
        
        /**
         * Initialize public features
         */
        function initPublic() {
            // Property favorites
            initFavorites();
            
            // Property comparison
            initComparison();
            
            // AJAX search
            initAjaxSearch();
            
            // Property maps
            initMaps();
        }
        
        /**
         * Initialize favorites functionality
         */
        function initFavorites() {
            $(document).on('click', '.add-to-favorites', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var propertyId = $button.data('property-id');
                
                $.ajax({
                    url: malisafiMLS.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'malisafi_toggle_favorite',
                        property_id: propertyId,
                        nonce: malisafiMLS.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            $button.toggleClass('is-favorite');
                            $button.text(response.data.is_favorite ? 'Remove from Favorites' : 'Add to Favorites');
                        }
                    }
                });
            });
        }
        
        /**
         * Initialize comparison functionality
         */
        function initComparison() {
            $(document).on('click', '.add-to-compare', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var propertyId = $button.data('property-id');
                
                var compareList = JSON.parse(localStorage.getItem('malisafi_compare') || '[]');
                
                if (compareList.includes(propertyId)) {
                    compareList = compareList.filter(id => id !== propertyId);
                    $button.text('Add to Compare');
                } else {
                    if (compareList.length >= 4) {
                        alert('You can only compare up to 4 properties at once.');
                        return;
                    }
                    compareList.push(propertyId);
                    $button.text('Remove from Compare');
                }
                
                localStorage.setItem('malisafi_compare', JSON.stringify(compareList));
                updateCompareCount();
            });
        }
        
        /**
         * Update comparison count
         */
        function updateCompareCount() {
            var compareList = JSON.parse(localStorage.getItem('malisafi_compare') || '[]');
            $('.compare-count').text(compareList.length);
        }
        
        /**
         * Initialize AJAX search
         */
        function initAjaxSearch() {
            var searchTimeout;
            
            $('.property-search input[name="s"]').on('keyup', function() {
                clearTimeout(searchTimeout);
                
                var searchTerm = $(this).val();
                
                if (searchTerm.length < 3) {
                    return;
                }
                
                searchTimeout = setTimeout(function() {
                    $.ajax({
                        url: malisafiMLS.ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'malisafi_search_properties',
                            search: searchTerm,
                            nonce: malisafiMLS.nonce
                        },
                        success: function(response) {
                            // Display search suggestions
                            if (response.success && response.data.suggestions) {
                                displaySearchSuggestions(response.data.suggestions);
                            }
                        }
                    });
                }, 500);
            });
        }
        
        /**
         * Display search suggestions
         */
        function displaySearchSuggestions(suggestions) {
            var $suggestionsBox = $('.search-suggestions');
            
            if (!$suggestionsBox.length) {
                $suggestionsBox = $('<div class="search-suggestions"></div>');
                $('.property-search').append($suggestionsBox);
            }
            
            $suggestionsBox.empty();
            
            if (suggestions.length === 0) {
                $suggestionsBox.hide();
                return;
            }
            
            suggestions.forEach(function(property) {
                var $suggestion = $('<a href="' + property.url + '" class="suggestion-item">' +
                    '<span class="suggestion-title">' + property.title + '</span>' +
                    '<span class="suggestion-price">' + property.price + '</span>' +
                    '</a>');
                $suggestionsBox.append($suggestion);
            });
            
            $suggestionsBox.show();
        }
        
        /**
         * Initialize Google Maps
         */
        function initMaps() {
            if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
                return;
            }
            
            $('.property-map').each(function() {
                var $map = $(this);
                var lat = parseFloat($map.data('lat'));
                var lng = parseFloat($map.data('lng'));
                
                if (!lat || !lng) {
                    return;
                }
                
                var mapOptions = {
                    center: { lat: lat, lng: lng },
                    zoom: 15
                };
                
                var map = new google.maps.Map($map[0], mapOptions);
                
                new google.maps.Marker({
                    position: { lat: lat, lng: lng },
                    map: map,
                    title: $map.data('title')
                });
            });
        }
        
        // Update compare count on page load
        updateCompareCount();
        
    });

})(jQuery);
