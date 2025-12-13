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
                formattedPrice = 'KES ' + parseFloat(property.price).toLocaleString('en-KE');
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
    });

})(jQuery);
