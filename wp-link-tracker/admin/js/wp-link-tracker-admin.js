/**
 * Admin JavaScript for WP Link Tracker
 */
(function($) {
    'use strict';

    // Chart instances
    let clicksChart = null;
    let deviceChart = null;
    let browserChart = null;
    let osChart = null;

    // Chart colors
    const chartColors = {
        device: ['#4e73df', '#1cc88a', '#36b9cc'],
        browser: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
        os: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b']
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Copy to clipboard functionality
        $(document).on('click', '.copy-to-clipboard', function() {
            var text = $(this).data('clipboard-text');
            navigator.clipboard.writeText(text).then(function() {
                // Show success message
                var $button = $(this);
                var originalText = $button.text();
                $button.text('Copied!');
                setTimeout(function() {
                    $button.text(originalText);
                }, 2000);
            }.bind(this)).catch(function(err) {
                console.error('Could not copy text: ', err);
            });
        });

        // Initialize dashboard if we're on the dashboard page
        if ($('#wplinktracker-total-clicks').length) {
            initDashboard();
        }
        
        // Initialize reset data button
        $('#wplinktracker-reset-data').on('click', function() {
            if (confirm('Are you sure you want to reset all click data? This action cannot be undone.')) {
                resetData();
            }
        });
        
        // Initialize debug date range button
        $('#wplinktracker-debug-date-range').on('click', function() {
            debugDateRange();
        });
    });

    /**
     * Initialize dashboard
     */
    function initDashboard() {
        console.log('Initializing dashboard...');
        
        // Initialize datepickers with explicit options
        $('.wplinktracker-datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
            showButtonPanel: true
        });
        
        // Set current date for date pickers
        var today = new Date();
        var thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(today.getDate() - 30);
        
        $('#wplinktracker-date-to').datepicker('setDate', today);
        $('#wplinktracker-date-from').datepicker('setDate', thirtyDaysAgo);
        
        // Initialize charts
        initClicksChart();
        initDeviceCharts();
        
        // Fetch dashboard data immediately
        fetchDashboardData();

        // Handle date range changes
        $('#wplinktracker-date-range-select').on('change', function() {
            var value = $(this).val();
            console.log('Date range changed to:', value);
            
            if (value === 'custom') {
                $('#wplinktracker-custom-date-range').show();
            } else {
                $('#wplinktracker-custom-date-range').hide();
                fetchDashboardData();
            }
        });

        // Handle refresh button
        $('#wplinktracker-refresh-dashboard').on('click', function() {
            console.log('Refresh button clicked');
            fetchDashboardData();
        });

        // Handle custom date range apply button
        $('#wplinktracker-apply-date-range').on('click', function() {
            var fromDate = $('#wplinktracker-date-from').val();
            var toDate = $('#wplinktracker-date-to').val();
            
            console.log('Apply date range:', fromDate, 'to', toDate);
            
            if (!fromDate || !toDate) {
                alert('Please select both from and to dates.');
                return;
            }
            
            fetchDashboardData();
        });
    }

    /**
     * Initialize clicks chart
     */
    function initClicksChart() {
        var ctx = document.getElementById('wplinktracker-clicks-chart').getContext('2d');
        
        // Create initial empty chart
        clicksChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Clicks',
                    data: [],
                    backgroundColor: 'rgba(0, 115, 170, 0.2)',
                    borderColor: 'rgba(0, 115, 170, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(0, 115, 170, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 1,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            title: function(tooltipItems) {
                                return tooltipItems[0].label;
                            },
                            label: function(context) {
                                return 'Clicks: ' + context.raw;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }

    /**
     * Initialize device, browser, and OS charts
     */
    function initDeviceCharts() {
        // Device types chart
        var deviceCtx = document.getElementById('wplinktracker-device-chart').getContext('2d');
        deviceChart = new Chart(deviceCtx, {
            type: 'pie',
            data: {
                labels: ['Desktop', 'Mobile', 'Tablet'],
                datasets: [{
                    data: [0, 0, 0],
                    backgroundColor: chartColors.device,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var label = context.label || '';
                                var value = context.raw || 0;
                                var total = context.dataset.data.reduce((a, b) => a + b, 0);
                                var percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
        
        // Browser chart
        var browserCtx = document.getElementById('wplinktracker-browser-chart').getContext('2d');
        browserChart = new Chart(browserCtx, {
            type: 'pie',
            data: {
                labels: ['Chrome', 'Firefox', 'Safari', 'Edge', 'Other'],
                datasets: [{
                    data: [0, 0, 0, 0, 0],
                    backgroundColor: chartColors.browser,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var label = context.label || '';
                                var value = context.raw || 0;
                                var total = context.dataset.data.reduce((a, b) => a + b, 0);
                                var percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
        
        // OS chart
        var osCtx = document.getElementById('wplinktracker-os-chart').getContext('2d');
        osChart = new Chart(osCtx, {
            type: 'pie',
            data: {
                labels: ['Windows', 'macOS', 'iOS', 'Android', 'Linux'],
                datasets: [{
                    data: [0, 0, 0, 0, 0],
                    backgroundColor: chartColors.os,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var label = context.label || '';
                                var value = context.raw || 0;
                                var total = context.dataset.data.reduce((a, b) => a + b, 0);
                                var percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Update clicks chart with new data
     */
    function updateClicksChart(labels, data) {
        if (!clicksChart) {
            console.error('Chart not initialized');
            return;
        }
        
        clicksChart.data.labels = labels;
        clicksChart.data.datasets[0].data = data;
        clicksChart.update();
    }

    /**
     * Update device charts with new data
     */
    function updateDeviceCharts(deviceData) {
        if (!deviceChart || !browserChart || !osChart) {
            console.error('Device charts not initialized');
            return;
        }
        
        // Update device types chart
        deviceChart.data.labels = deviceData.device_types.labels;
        deviceChart.data.datasets[0].data = deviceData.device_types.data;
        deviceChart.update();
        
        // Update browser chart
        browserChart.data.labels = deviceData.browsers.labels;
        browserChart.data.datasets[0].data = deviceData.browsers.data;
        browserChart.update();
        
        // Update OS chart
        osChart.data.labels = deviceData.operating_systems.labels;
        osChart.data.datasets[0].data = deviceData.operating_systems.data;
        osChart.update();
    }

    /**
     * Fetch dashboard data
     */
    function fetchDashboardData() {
        // Show loading indicators
        $('#wplinktracker-total-clicks').text('Loading...');
        $('#wplinktracker-unique-visitors').text('Loading...');
        $('#wplinktracker-active-links').text('Loading...');
        $('#wplinktracker-conversion-rate').text('Loading...');
        $('#wplinktracker-top-links').html('<p>Loading...</p>');
        $('#wplinktracker-top-referrers').html('<p>Loading...</p>');
        
        // Get date range parameters
        var dateRange = $('#wplinktracker-date-range-select').val();
        var fromDate = $('#wplinktracker-date-from').val();
        var toDate = $('#wplinktracker-date-to').val();
        
        console.log('Fetching dashboard data with date range:', dateRange, 'from:', fromDate, 'to:', toDate);
        
        // Fetch dashboard summary
        $.ajax({
            url: wpLinkTrackerData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wp_link_tracker_get_dashboard_summary',
                nonce: wpLinkTrackerData.nonce,
                date_range: dateRange,
                from_date: fromDate,
                to_date: toDate
            },
            success: function(response) {
                console.log('Dashboard summary response:', response);
                
                if (response.success && response.data) {
                    // Update summary cards with data
                    $('#wplinktracker-total-clicks').text(response.data.total_clicks);
                    $('#wplinktracker-unique-visitors').text(response.data.unique_visitors);
                    $('#wplinktracker-active-links').text(response.data.active_links);
                    $('#wplinktracker-conversion-rate').text(response.data.conversion_rate);
                    
                    // Add animation effect to highlight updated values
                    $('.wplinktracker-summary-card').addClass('updated');
                    setTimeout(function() {
                        $('.wplinktracker-summary-card').removeClass('updated');
                    }, 1000);
                } else {
                    console.error('Error loading dashboard summary:', response);
                    $('#wplinktracker-total-clicks').text('Error');
                    $('#wplinktracker-unique-visitors').text('Error');
                    $('#wplinktracker-active-links').text('Error');
                    $('#wplinktracker-conversion-rate').text('Error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error loading dashboard summary:', status, error);
                $('#wplinktracker-total-clicks').text('Error: ' + error);
                $('#wplinktracker-unique-visitors').text('Error');
                $('#wplinktracker-active-links').text('Error');
                $('#wplinktracker-conversion-rate').text('Error');
            }
        });
        
        // Fetch clicks over time data
        $.ajax({
            url: wpLinkTrackerData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wp_link_tracker_get_clicks_over_time',
                nonce: wpLinkTrackerData.nonce,
                date_range: dateRange,
                from_date: fromDate,
                to_date: toDate
            },
            success: function(response) {
                console.log('Clicks over time response:', response);
                
                if (response.success && response.data) {
                    // Format data for chart
                    var labels = [];
                    var data = [];
                    
                    // Check if data is in the expected format
                    if (Array.isArray(response.data)) {
                        // Handle array of objects format
                        response.data.forEach(function(item) {
                            labels.push(formatDate(item.date));
                            data.push(item.clicks);
                        });
                    } else if (response.data.labels && response.data.data) {
                        // Handle object with labels and data arrays
                        labels = response.data.labels;
                        data = response.data.data;
                    } else {
                        console.error('Unexpected data format:', response.data);
                    }
                    
                    // Update chart with data
                    updateClicksChart(labels, data);
                } else {
                    console.error('Error loading clicks over time:', response);
                    // Clear chart on error
                    updateClicksChart([], []);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error loading clicks over time:', status, error);
                // Clear chart on error
                updateClicksChart([], []);
            }
        });
        
        // Fetch device data
        $.ajax({
            url: wpLinkTrackerData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wp_link_tracker_get_device_data',
                nonce: wpLinkTrackerData.nonce,
                date_range: dateRange,
                from_date: fromDate,
                to_date: toDate
            },
            success: function(response) {
                console.log('Device data response:', response);
                
                if (response.success && response.data) {
                    // Update device charts with data
                    updateDeviceCharts(response.data);
                } else {
                    console.error('Error loading device data:', response);
                    // Reset charts on error
                    updateDeviceCharts({
                        device_types: { labels: ['Desktop', 'Mobile', 'Tablet'], data: [0, 0, 0] },
                        browsers: { labels: ['Chrome', 'Firefox', 'Safari', 'Edge', 'Other'], data: [0, 0, 0, 0, 0] },
                        operating_systems: { labels: ['Windows', 'macOS', 'iOS', 'Android', 'Linux'], data: [0, 0, 0, 0, 0] }
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error loading device data:', status, error);
                // Reset charts on error
                updateDeviceCharts({
                    device_types: { labels: ['Desktop', 'Mobile', 'Tablet'], data: [0, 0, 0] },
                    browsers: { labels: ['Chrome', 'Firefox', 'Safari', 'Edge', 'Other'], data: [0, 0, 0, 0, 0] },
                    operating_systems: { labels: ['Windows', 'macOS', 'iOS', 'Android', 'Linux'], data: [0, 0, 0, 0, 0] }
                });
            }
        });
        
        // Fetch top links data
        $.ajax({
            url: wpLinkTrackerData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wp_link_tracker_get_top_links',
                nonce: wpLinkTrackerData.nonce,
                date_range: dateRange,
                from_date: fromDate,
                to_date: toDate
            },
            success: function(response) {
                console.log('Top links response:', response);
                
                if (response.success) {
                    // Update top links container with HTML
                    $('#wplinktracker-top-links').html(response.data);
                } else {
                    console.error('Error loading top links:', response);
                    $('#wplinktracker-top-links').html('<p>Error loading top links data.</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error loading top links:', status, error);
                $('#wplinktracker-top-links').html('<p>Error: ' + error + '</p>');
            }
        });
        
        // Fetch top referrers data
        $.ajax({
            url: wpLinkTrackerData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wp_link_tracker_get_top_referrers',
                nonce: wpLinkTrackerData.nonce,
                date_range: dateRange,
                from_date: fromDate,
                to_date: toDate
            },
            success: function(response) {
                console.log('Top referrers response:', response);
                
                if (response.success) {
                    // Update top referrers container with HTML
                    $('#wplinktracker-top-referrers').html(response.data);
                } else {
                    console.error('Error loading top referrers:', response);
                    $('#wplinktracker-top-referrers').html('<p>Error loading top referrers data.</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error loading top referrers:', status, error);
                $('#wplinktracker-top-referrers').html('<p>Error: ' + error + '</p>');
            }
        });
    }
    
    /**
     * Format date for display
     */
    function formatDate(dateStr) {
        var date = new Date(dateStr);
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return months[date.getMonth()] + ' ' + date.getDate();
    }
    
    /**
     * Reset data
     */
    function resetData() {
        // Show loading indicator
        var $status = $('#wplinktracker-reset-status');
        $status.text('Resetting data...').show();
        
        $.ajax({
            url: wpLinkTrackerData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wp_link_tracker_reset_data',
                nonce: wpLinkTrackerData.resetNonce
            },
            success: function(response) {
                console.log('Reset data response:', response);
                
                if (response.success) {
                    $status.text(response.data.message).css('color', 'green');
                    
                    // Display clicks by date if available
                    if (response.data.clicks_by_date) {
                        console.log('Clicks by date:', response.data.clicks_by_date);
                    }
                    
                    // Refresh dashboard data
                    fetchDashboardData();
                    
                    // Hide status after 5 seconds
                    setTimeout(function() {
                        $status.fadeOut();
                    }, 5000);
                } else {
                    $status.text('Error: ' + (response.data ? response.data.message : 'Unknown error')).css('color', 'red');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error resetting data:', status, error);
                $status.text('Error: ' + error).css('color', 'red');
            }
        });
    }
    
    /**
     * Debug date range
     */
    function debugDateRange() {
        // Get date range parameters
        var dateRange = $('#wplinktracker-date-range-select').val();
        var fromDate = $('#wplinktracker-date-from').val();
        var toDate = $('#wplinktracker-date-to').val();
        
        console.log('Debugging date range:', dateRange, 'from:', fromDate, 'to:', toDate);
        
        // Show loading indicator
        $('#wplinktracker-debug-content').text('Loading debug information...');
        $('#wplinktracker-debug-output').show();
        
        // Fetch debug information
        $.ajax({
            url: wpLinkTrackerData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wp_link_tracker_debug_date_range',
                nonce: wpLinkTrackerData.nonce,
                date_range: dateRange,
                from_date: fromDate,
                to_date: toDate
            },
            success: function(response) {
                console.log('Debug date range response:', response);
                
                if (response.success && response.data) {
                    // Format debug information
                    var debugInfo = '';
                    
                    // Date range information
                    debugInfo += 'Date Range Parameters:\n';
                    debugInfo += '- Type: ' + response.data.date_range.type + '\n';
                    debugInfo += '- From Date: ' + response.data.date_range.from_date + '\n';
                    debugInfo += '- To Date: ' + response.data.date_range.to_date + '\n';
                    debugInfo += '- Calculated Start: ' + response.data.date_range.calculated_start + '\n';
                    debugInfo += '- Calculated End: ' + response.data.date_range.calculated_end + '\n\n';
                    
                    // Query
                    debugInfo += 'SQL Query:\n' + response.data.query + '\n\n';
                    
                    // Total clicks
                    debugInfo += 'Total Clicks: ' + response.data.total_clicks + '\n\n';
                    
                    // Clicks by date for the selected range
                    debugInfo += 'Clicks by Date (Selected Range):\n';
                    if (response.data.clicks_by_date.length === 0) {
                        debugInfo += '- No clicks found in this date range\n';
                    } else {
                        response.data.clicks_by_date.forEach(function(item) {
                            debugInfo += '- ' + item.date + ': ' + item.count + ' clicks\n';
                        });
                    }
                    debugInfo += '\n';
                    
                    // All clicks for comparison
                    debugInfo += 'All Clicks (All Dates):\n';
                    if (response.data.all_clicks.length === 0) {
                        debugInfo += '- No clicks found in the database\n';
                    } else {
                        response.data.all_clicks.forEach(function(item) {
                            debugInfo += '- ' + item.date + ': ' + item.count + ' clicks\n';
                        });
                    }
                    
                    // Display debug information
                    $('#wplinktracker-debug-content').text(debugInfo);
                } else {
                    $('#wplinktracker-debug-content').text('Error loading debug information');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error debugging date range:', status, error);
                $('#wplinktracker-debug-content').text('Error: ' + error);
            }
        });
    }

})(jQuery);
