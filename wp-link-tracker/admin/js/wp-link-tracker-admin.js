/**
 * Admin JavaScript for WP Link Tracker
 */
(function($) {
    'use strict';

    // Initialize charts when document is ready
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

        // Initialize dashboard charts if we're on the dashboard page
        if ($('#wplinktracker-clicks-chart').length) {
            initDashboardCharts();
        }
    });

    /**
     * Initialize dashboard charts
     */
    function initDashboardCharts() {
        // Sample data for demonstration
        var clicksData = {
            labels: ['Jan 1', 'Jan 2', 'Jan 3', 'Jan 4', 'Jan 5', 'Jan 6', 'Jan 7'],
            datasets: [{
                label: 'Clicks',
                data: [65, 59, 80, 81, 56, 55, 40],
                backgroundColor: 'rgba(0, 115, 170, 0.2)',
                borderColor: 'rgba(0, 115, 170, 1)',
                borderWidth: 1
            }]
        };

        var devicesData = {
            labels: ['Desktop', 'Mobile', 'Tablet'],
            datasets: [{
                data: [65, 30, 5],
                backgroundColor: [
                    'rgba(0, 115, 170, 0.7)',
                    'rgba(220, 50, 50, 0.7)',
                    'rgba(70, 180, 80, 0.7)'
                ]
            }]
        };

        var browsersData = {
            labels: ['Chrome', 'Firefox', 'Safari', 'Edge', 'Other'],
            datasets: [{
                data: [45, 20, 15, 10, 10],
                backgroundColor: [
                    'rgba(0, 115, 170, 0.7)',
                    'rgba(220, 50, 50, 0.7)',
                    'rgba(70, 180, 80, 0.7)',
                    'rgba(220, 170, 50, 0.7)',
                    'rgba(150, 150, 150, 0.7)'
                ]
            }]
        };

        var osData = {
            labels: ['Windows', 'macOS', 'iOS', 'Android', 'Linux'],
            datasets: [{
                data: [40, 25, 15, 15, 5],
                backgroundColor: [
                    'rgba(0, 115, 170, 0.7)',
                    'rgba(220, 50, 50, 0.7)',
                    'rgba(70, 180, 80, 0.7)',
                    'rgba(220, 170, 50, 0.7)',
                    'rgba(150, 150, 150, 0.7)'
                ]
            }]
        };

        // Create charts
        var clicksChart = new Chart(
            document.getElementById('wplinktracker-clicks-chart'),
            {
                type: 'line',
                data: clicksData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            }
        );

        var devicesChart = new Chart(
            document.getElementById('wplinktracker-devices-chart'),
            {
                type: 'pie',
                data: devicesData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            }
        );

        var browsersChart = new Chart(
            document.getElementById('wplinktracker-browsers-chart'),
            {
                type: 'pie',
                data: browsersData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            }
        );

        var osChart = new Chart(
            document.getElementById('wplinktracker-os-chart'),
            {
                type: 'pie',
                data: osData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            }
        );

        // Sample data for tables
        var topLinksHtml = `
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Link</th>
                        <th>Clicks</th>
                        <th>Conversion Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><a href="#">Product Promotion</a></td>
                        <td>245</td>
                        <td>12.5%</td>
                    </tr>
                    <tr>
                        <td><a href="#">Newsletter Signup</a></td>
                        <td>189</td>
                        <td>8.2%</td>
                    </tr>
                    <tr>
                        <td><a href="#">Special Offer</a></td>
                        <td>156</td>
                        <td>15.3%</td>
                    </tr>
                    <tr>
                        <td><a href="#">Blog Post</a></td>
                        <td>132</td>
                        <td>5.7%</td>
                    </tr>
                    <tr>
                        <td><a href="#">Social Media Campaign</a></td>
                        <td>98</td>
                        <td>9.1%</td>
                    </tr>
                </tbody>
            </table>
        `;

        var topReferrersHtml = `
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Referrer</th>
                        <th>Clicks</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>facebook.com</td>
                        <td>187</td>
                    </tr>
                    <tr>
                        <td>twitter.com</td>
                        <td>143</td>
                    </tr>
                    <tr>
                        <td>instagram.com</td>
                        <td>112</td>
                    </tr>
                    <tr>
                        <td>linkedin.com</td>
                        <td>89</td>
                    </tr>
                    <tr>
                        <td>Direct</td>
                        <td>76</td>
                    </tr>
                </tbody>
            </table>
        `;

        // Update tables
        $('#wplinktracker-top-links-table').html(topLinksHtml);
        $('#wplinktracker-top-referrers-table').html(topReferrersHtml);

        // Update summary values
        $('#wplinktracker-total-clicks').text('820');
        $('#wplinktracker-unique-visitors').text('542');
        $('#wplinktracker-active-links').text('15');
        $('#wplinktracker-avg-conversion').text('10.2%');

        // Handle date range changes
        $('#wplinktracker-date-range-select').on('change', function() {
            var value = $(this).val();
            
            if (value === 'custom') {
                $('#wplinktracker-custom-date-range').show();
            } else {
                $('#wplinktracker-custom-date-range').hide();
                // Here you would fetch data for the selected range
                // For demonstration, we'll just show a message
                alert('Date range changed to: ' + value + ' days');
            }
        });

        // Handle refresh button
        $('#wplinktracker-refresh-dashboard').on('click', function() {
            // Here you would refresh the data
            // For demonstration, we'll just show a message
            alert('Dashboard refreshed!');
        });

        // Handle custom date range
        $('#wplinktracker-apply-date-range').on('click', function() {
            var fromDate = $('#wplinktracker-date-from').val();
            var toDate = $('#wplinktracker-date-to').val();
            
            if (!fromDate || !toDate) {
                alert('Please select both from and to dates.');
                return;
            }
            
            // Here you would fetch data for the custom range
            // For demonstration, we'll just show a message
            alert('Custom date range applied: ' + fromDate + ' to ' + toDate);
        });
    }

    /**
     * Function to draw clicks chart for individual link stats
     */
    window.drawClicksChart = function(data) {
        if (!data || !data.length) {
            return;
        }
        
        var ctx = document.getElementById('wplinktracker-clicks-chart');
        if (!ctx) {
            return;
        }
        
        var labels = [];
        var values = [];
        
        data.forEach(function(item) {
            labels.push(item.date);
            values.push(item.clicks);
        });
        
        var clicksChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Clicks',
                    data: values,
                    backgroundColor: 'rgba(0, 115, 170, 0.2)',
                    borderColor: 'rgba(0, 115, 170, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    };

})(jQuery);
