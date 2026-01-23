/**
 * Malisafi Property Map
 * 
 * Interactive map display with property markers
 * Uses Leaflet.js with OpenStreetMap tiles
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Check if map container exists
        if ($('#malisafi-property-map').length === 0) {
            return;
        }

        // Check if map data is available
        if (typeof malisafiMapData === 'undefined' || !malisafiMapData.properties || malisafiMapData.properties.length === 0) {
            console.warn('No property data available for map');
            return;
        }

        // Initialize map
        var map = L.map('malisafi-property-map').setView(
            [malisafiMapData.center.lat, malisafiMapData.center.lng],
            malisafiMapData.zoom
        );

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);

        // Add geocoding control for address search (Kenya-specific)
        if (typeof L.Control.Geocoder !== 'undefined') {
            var geocoder = L.Control.geocoder({
                defaultMarkGeocode: false,
                geocoder: L.Control.Geocoder.nominatim({
                    geocodingQueryParams: {
                        countrycodes: 'ke',  // Kenya-specific
                        limit: 5
                    }
                }),
                placeholder: 'Search address in Kenya...',
                errorMessage: 'No results found'
            }).on('markgeocode', function(e) {
                var bbox = e.geocode.bbox;
                var poly = L.polygon([
                    bbox.getSouthEast(),
                    bbox.getNorthEast(),
                    bbox.getNorthWest(),
                    bbox.getSouthWest()
                ]);
                map.fitBounds(poly.getBounds());
                
                // Add temporary marker
                L.marker(e.geocode.center, {
                    icon: L.divIcon({
                        className: 'search-result-marker',
                        html: '<div class="marker-pin search-marker"><svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg></div>',
                        iconSize: [30, 42],
                        iconAnchor: [15, 42]
                    })
                }).addTo(map)
                    .bindPopup('<strong>' + e.geocode.name + '</strong>')
                    .openPopup();
            }).addTo(map);
        }

        // Custom icon for property markers
        var propertyIcon = L.divIcon({
            className: 'malisafi-marker',
            html: '<div class="marker-pin"><svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg></div>',
            iconSize: [30, 42],
            iconAnchor: [15, 42],
            popupAnchor: [0, -42]
        });

        // Prepare markers
        var markers = [];
        
        malisafiMapData.properties.forEach(function(property) {
            // Create marker
            var marker = L.marker([property.lat, property.lng], {
                icon: propertyIcon,
                title: property.title
            });

            // Format price
            var formattedPrice = 'N/A';
            if (property.price) {
                var currencySymbol = property.currency === 'KES' ? 'KSh' : '$';
                formattedPrice = currencySymbol + ' ' + parseFloat(property.price).toLocaleString('en-KE');
            }

            // Create popup content
            var popupContent = '<div class="property-map-popup">';
            
            if (property.image) {
                popupContent += '<div class="popup-image"><img src="' + property.image + '" alt="' + property.title + '"></div>';
            }
            
            popupContent += '<div class="popup-content">';
            popupContent += '<h4 class="popup-title"><a href="' + property.url + '">' + property.title + '</a></h4>';
            
            if (property.type || property.status) {
                popupContent += '<div class="popup-meta">';
                if (property.type) {
                    popupContent += '<span class="popup-type">' + property.type + '</span>';
                }
                if (property.status) {
                    popupContent += '<span class="popup-status">' + property.status + '</span>';
                }
                popupContent += '</div>';
            }
            
            popupContent += '<div class="popup-price">' + formattedPrice + '</div>';
            popupContent += '<a href="' + property.url + '" class="popup-link">View Details</a>';
            popupContent += '</div></div>';

            marker.bindPopup(popupContent, {
                maxWidth: 300,
                minWidth: 250
            });

            markers.push(marker);
        });

        // Add markers to map with clustering if enabled
        if (malisafiMapData.cluster && typeof L.markerClusterGroup !== 'undefined') {
            var markerCluster = L.markerClusterGroup({
                spiderfyOnMaxZoom: true,
                showCoverageOnHover: false,
                zoomToBoundsOnClick: true,
                maxClusterRadius: 50
            });
            
            markers.forEach(function(marker) {
                markerCluster.addLayer(marker);
            });
            
            map.addLayer(markerCluster);
        } else {
            // Add markers directly without clustering
            markers.forEach(function(marker) {
                marker.addTo(map);
            });
        }

        // Fit map to show all markers
        if (markers.length > 1) {
            var group = new L.featureGroup(markers);
            map.fitBounds(group.getBounds().pad(0.1));
        }

        // Fullscreen functionality
        $('#fullscreen-map-btn').on('click', function() {
            var mapContainer = $('#malisafi-property-map');
            var btn = $(this);
            
            if (!document.fullscreenElement) {
                // Enter fullscreen
                if (mapContainer[0].requestFullscreen) {
                    mapContainer[0].requestFullscreen();
                } else if (mapContainer[0].webkitRequestFullscreen) {
                    mapContainer[0].webkitRequestFullscreen();
                } else if (mapContainer[0].msRequestFullscreen) {
                    mapContainer[0].msRequestFullscreen();
                }
                
                btn.find('.dashicons').removeClass('dashicons-fullscreen-alt').addClass('dashicons-fullscreen-exit-alt');
                btn.find('.btn-text').text('Exit Fullscreen');
                
                // Fix map display after entering fullscreen
                setTimeout(function() {
                    map.invalidateSize();
                }, 100);
            } else {
                // Exit fullscreen
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }
                
                btn.find('.dashicons').removeClass('dashicons-fullscreen-exit-alt').addClass('dashicons-fullscreen-alt');
                btn.find('.btn-text').text('Fullscreen');
            }
        });

        // Near Me functionality - Geolocation
        $('#near-me-btn').on('click', function() {
            var btn = $(this);
            
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by your browser');
                return;
            }
            
            // Show loading state
            btn.prop('disabled', true);
            btn.find('.dashicons').removeClass('dashicons-location').addClass('dashicons-update');
            btn.find('.btn-text').text('Locating...');
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    // Success callback
                    var userLat = position.coords.latitude;
                    var userLng = position.coords.longitude;
                    
                    // Center map on user location
                    map.setView([userLat, userLng], 13);
                    
                    // Add user location marker
                    var userMarker = L.marker([userLat, userLng], {
                        icon: L.divIcon({
                            className: 'user-location-marker',
                            html: '<div class="marker-pin user-marker"><svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3" fill="white"/></svg></div>',
                            iconSize: [30, 30],
                            iconAnchor: [15, 15]
                        })
                    }).addTo(map)
                        .bindPopup('<strong>Your Location</strong>')
                        .openPopup();
                    
                    // Reset button
                    btn.prop('disabled', false);
                    btn.find('.dashicons').removeClass('dashicons-update').addClass('dashicons-location');
                    btn.find('.btn-text').text('Near Me');
                },
                function(error) {
                    // Error callback
                    var errorMsg = 'Unable to get your location';
                    
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMsg = 'Location permission denied. Please enable location access.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMsg = 'Location information unavailable.';
                            break;
                        case error.TIMEOUT:
                            errorMsg = 'Location request timed out.';
                            break;
                    }
                    
                    alert(errorMsg);
                    
                    // Reset button
                    btn.prop('disabled', false);
                    btn.find('.dashicons').removeClass('dashicons-update').addClass('dashicons-location');
                    btn.find('.btn-text').text('Near Me');
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        });

        // Listen for fullscreen change events
        $(document).on('fullscreenchange webkitfullscreenchange mozfullscreenchange msfullscreenchange', function() {
            if (!document.fullscreenElement) {
                $('#fullscreen-map-btn .dashicons').removeClass('dashicons-fullscreen-exit-alt').addClass('dashicons-fullscreen-alt');
                $('#fullscreen-map-btn .btn-text').text('Fullscreen');
                
                // Fix map display after exiting fullscreen
                setTimeout(function() {
                    map.invalidateSize();
                }, 100);
            }
        });
    });

})(jQuery);
