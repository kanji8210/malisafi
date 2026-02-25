/**
 * Malisafi Analytics Charts
 * 
 * Handles Chart.js visualization for analytics dashboard
 * 
 * @package MalisafiMLS
 * @since 1.0.0
 */

(function(){
    // Silence console output unless debugging enabled for Malisafi.
    var debugEnabled = false;
    try {
        if (typeof malisafi_ajax !== 'undefined' && malisafi_ajax && malisafi_ajax.debug) {
            debugEnabled = true;
        } else if (typeof malisafiPublicChat !== 'undefined' && malisafiPublicChat && malisafiPublicChat.debug) {
            debugEnabled = true;
        } else if (window.malisafiDebug) {
            debugEnabled = true;
        }
    } catch (e) {}
    if (!debugEnabled) {
        try {
            console.log = function(){};
            console.debug = function(){};
            console.info = function(){};
            console.warn = function(){};
            console.error = function(){};
        } catch (e) {}
    } else {
        window.malisafiDebug = true;
    }
})();

(function($) {
    'use strict';

    // Wait for DOM and Chart.js to be ready
    $(document).ready(function() {
        if (typeof Chart === 'undefined') {
            console.error('Chart.js library not loaded');
            return;
        }

        // Default chart options
        const defaultOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    cornerRadius: 4,
                    titleFont: {
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        size: 13
                    }
                }
            }
        };

        // Malisafi color palette
        const colors = {
            primary: '#737d5d',
            secondary: '#8b9575',
            accent: '#d4d8c8',
            success: '#28a745',
            warning: '#ffc107',
            danger: '#dc3545',
            info: '#17a2b8'
        };

        /**
         * Create a bar chart
         */
        window.createBarChart = function(canvasId, labels, data, label, color = colors.primary) {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return null;

            return new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: label,
                        data: data,
                        backgroundColor: color,
                        borderColor: color,
                        borderWidth: 1
                    }]
                },
                options: {
                    ...defaultOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        };

        /**
         * Create a line chart
         */
        window.createLineChart = function(canvasId, labels, datasets) {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return null;

            // Format datasets with default colors
            const formattedDatasets = datasets.map((dataset, index) => {
                const colorKeys = Object.keys(colors);
                const color = colors[colorKeys[index % colorKeys.length]];
                
                return {
                    label: dataset.label,
                    data: dataset.data,
                    borderColor: color,
                    backgroundColor: color + '20', // 20 = 12.5% opacity in hex
                    tension: 0.4,
                    fill: true,
                    ...dataset
                };
            });

            return new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: formattedDatasets
                },
                options: {
                    ...defaultOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        };

        /**
         * Create a doughnut chart
         */
        window.createDoughnutChart = function(canvasId, labels, data, backgroundColors = null) {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return null;

            // Use default colors if not provided
            const bgColors = backgroundColors || [
                colors.primary,
                colors.secondary,
                colors.accent,
                colors.success,
                colors.warning,
                colors.danger,
                colors.info
            ];

            return new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: bgColors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    ...defaultOptions,
                    cutout: '60%'
                }
            });
        };

        /**
         * Create a pie chart
         */
        window.createPieChart = function(canvasId, labels, data, backgroundColors = null) {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return null;

            const bgColors = backgroundColors || [
                colors.primary,
                colors.secondary,
                colors.accent,
                colors.success,
                colors.warning,
                colors.danger,
                colors.info
            ];

            return new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: bgColors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: defaultOptions
            });
        };

        /**
         * Create a horizontal bar chart
         */
        window.createHorizontalBarChart = function(canvasId, labels, data, label, color = colors.primary) {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return null;

            return new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: label,
                        data: data,
                        backgroundColor: color,
                        borderColor: color,
                        borderWidth: 1
                    }]
                },
                options: {
                    ...defaultOptions,
                    indexAxis: 'y',
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        };

        /**
         * Update chart data
         */
        window.updateChartData = function(chart, labels, data) {
            if (!chart) return;
            
            chart.data.labels = labels;
            if (Array.isArray(data[0])) {
                // Multiple datasets
                data.forEach((dataset, index) => {
                    if (chart.data.datasets[index]) {
                        chart.data.datasets[index].data = dataset;
                    }
                });
            } else {
                // Single dataset
                chart.data.datasets[0].data = data;
            }
            chart.update();
        };

        /**
         * Destroy chart safely
         */
        window.destroyChart = function(chart) {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        };

        // Expose colors for external use
        window.malisafiChartColors = colors;

        console.log('✅ Malisafi Analytics Charts initialized');
    });

})(jQuery);
