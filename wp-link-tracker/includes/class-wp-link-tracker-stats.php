<?php
/**
 * Handles statistics and reporting
 */
class WP_Link_Tracker_Stats {
    /**
     * Initialize the class.
     */
    public function init() {
        add_action('wp_ajax_wp_link_tracker_get_stats', array($this, 'get_stats_ajax'));
        add_action('wp_ajax_wp_link_tracker_get_top_links', array($this, 'get_top_links_ajax'));
        add_action('wp_ajax_wp_link_tracker_get_top_referrers', array($this, 'get_top_referrers_ajax'));
        add_action('wp_ajax_wp_link_tracker_get_dashboard_summary', array($this, 'get_dashboard_summary_ajax'));
        add_action('wp_ajax_wp_link_tracker_get_clicks_over_time', array($this, 'get_clicks_over_time_ajax'));
        add_action('wp_ajax_wp_link_tracker_get_device_data', array($this, 'get_device_data_ajax'));
    }

    /**
     * Get statistics for a link via AJAX.
     */
    public function get_stats_ajax() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_stats_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check post ID
        if (!isset($_POST['post_id']) || !is_numeric($_POST['post_id'])) {
            wp_send_json_error('Invalid post ID');
        }
        
        $post_id = intval($_POST['post_id']);
        
        // Check if the post exists and is a tracked link
        $post = get_post($post_id);
        if (!$post || 'wplinktracker' !== $post->post_type) {
            wp_send_json_error('Invalid tracked link');
        }
        
        // Get statistics
        $stats = $this->get_link_stats($post_id);
        
        wp_send_json_success($stats);
    }

    /**
     * Get top performing links via AJAX.
     */
    public function get_top_links_ajax() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_dashboard_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Get date range
        $date_range = isset($_POST['date_range']) ? sanitize_text_field($_POST['date_range']) : '30';
        $from_date = isset($_POST['from_date']) ? sanitize_text_field($_POST['from_date']) : '';
        $to_date = isset($_POST['to_date']) ? sanitize_text_field($_POST['to_date']) : '';
        
        // Log the received parameters for debugging
        error_log('Top Links - Date Range: ' . $date_range . ', From: ' . $from_date . ', To: ' . $to_date);
        
        // Get top links
        $top_links = $this->get_top_links($date_range, $from_date, $to_date);
        $html = $this->build_top_links_table($top_links);
        
        wp_send_json_success($html);
    }

    /**
     * Get top referrers via AJAX.
     */
    public function get_top_referrers_ajax() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_dashboard_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Get date range
        $date_range = isset($_POST['date_range']) ? sanitize_text_field($_POST['date_range']) : '30';
        $from_date = isset($_POST['from_date']) ? sanitize_text_field($_POST['from_date']) : '';
        $to_date = isset($_POST['to_date']) ? sanitize_text_field($_POST['to_date']) : '';
        
        // Log the received parameters for debugging
        error_log('Top Referrers - Date Range: ' . $date_range . ', From: ' . $from_date . ', To: ' . $to_date);
        
        // Get top referrers
        $top_referrers = $this->get_top_referrers_global($date_range, $from_date, $to_date);
        $html = $this->build_referrers_table($top_referrers);
        
        wp_send_json_success($html);
    }

    /**
     * Get dashboard summary via AJAX.
     */
    public function get_dashboard_summary_ajax() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_dashboard_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Get date range parameters
        $date_range = isset($_POST['date_range']) ? sanitize_text_field($_POST['date_range']) : '30';
        $from_date = isset($_POST['from_date']) ? sanitize_text_field($_POST['from_date']) : '';
        $to_date = isset($_POST['to_date']) ? sanitize_text_field($_POST['to_date']) : '';
        
        // Log the received parameters for debugging
        error_log('Dashboard Summary - Date Range: ' . $date_range . ', From: ' . $from_date . ', To: ' . $to_date);
        
        // Get dashboard summary
        $summary = $this->get_dashboard_summary($date_range, $from_date, $to_date);
        
        // Log the summary data for debugging
        error_log('Dashboard Summary Data: ' . print_r($summary, true));
        
        wp_send_json_success($summary);
    }

    /**
     * Get clicks over time via AJAX.
     */
    public function get_clicks_over_time_ajax() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_dashboard_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Get date range
        $date_range = isset($_POST['date_range']) ? sanitize_text_field($_POST['date_range']) : '30';
        $from_date = isset($_POST['from_date']) ? sanitize_text_field($_POST['from_date']) : '';
        $to_date = isset($_POST['to_date']) ? sanitize_text_field($_POST['to_date']) : '';
        
        // Log the received parameters for debugging
        error_log('Clicks Over Time - Date Range: ' . $date_range . ', From: ' . $from_date . ', To: ' . $to_date);
        
        // Get clicks over time
        $clicks_data = $this->get_global_clicks_over_time($date_range, $from_date, $to_date);
        
        wp_send_json_success($clicks_data);
    }

    /**
     * Get device data via AJAX.
     */
    public function get_device_data_ajax() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_dashboard_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Get date range
        $date_range = isset($_POST['date_range']) ? sanitize_text_field($_POST['date_range']) : '30';
        $from_date = isset($_POST['from_date']) ? sanitize_text_field($_POST['from_date']) : '';
        $to_date = isset($_POST['to_date']) ? sanitize_text_field($_POST['to_date']) : '';
        
        // Log the received parameters for debugging
        error_log('Device Data - Date Range: ' . $date_range . ', From: ' . $from_date . ', To: ' . $to_date);
        
        // Get device data
        $device_data = $this->get_global_device_breakdown($date_range, $from_date, $to_date);
        
        wp_send_json_success($device_data);
    }

    /**
     * Get statistics for a link.
     */
    public function get_link_stats($post_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        
        // Get total clicks directly from database instead of meta
        $total_clicks_query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE post_id = %d",
            $post_id
        );
        $total_clicks = (int) $wpdb->get_var($total_clicks_query);
        
        // Get unique visitors directly from database
        $unique_visitors_query = $wpdb->prepare(
            "SELECT COUNT(DISTINCT visitor_id) FROM $table_name WHERE post_id = %d",
            $post_id
        );
        $unique_visitors = (int) $wpdb->get_var($unique_visitors_query);
        
        // Calculate conversion rate
        $conversion_rate = ($unique_visitors > 0) ? round(($total_clicks / $unique_visitors) * 100, 2) . '%' : '0%';
        
        // Get clicks over time (last 30 days)
        $clicks_data = $this->get_clicks_over_time($post_id, 30);
        
        // Get top referrers
        $referrers = $this->get_top_referrers($post_id);
        $referrers_table = $this->build_referrers_table($referrers);
        
        // Get device breakdown
        $devices = $this->get_device_breakdown($post_id);
        $devices_table = $this->build_devices_table($devices);
        
        return array(
            'total_clicks' => $total_clicks,
            'unique_visitors' => $unique_visitors,
            'conversion_rate' => $conversion_rate,
            'clicks_data' => $clicks_data,
            'referrers_table' => $referrers_table,
            'devices_table' => $devices_table
        );
    }

    /**
     * Get clicks over time for a specific link.
     */
    private function get_clicks_over_time($post_id, $days = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        
        // Get current date in WordPress timezone
        $current_date = current_time('Y-m-d');
        
        // Query to get actual click data for the specified date range
        $query = $wpdb->prepare(
            "SELECT DATE(click_time) as date, COUNT(*) as clicks
            FROM $table_name
            WHERE post_id = %d
            AND click_time >= DATE_SUB(%s, INTERVAL %d DAY)
            GROUP BY DATE(click_time)
            ORDER BY date ASC",
            $post_id, $current_date, $days
        );
        
        // Log the SQL query for debugging
        error_log('Clicks Over Time SQL: ' . $query);
        
        $results = $wpdb->get_results($query);
        
        // Fill in missing dates with the current date range
        $data = array();
        $date_obj = new DateTime($current_date);
        $date_obj->modify('-' . ($days - 1) . ' days'); // Start from (days-1) days ago
        
        for ($i = 0; $i < $days; $i++) {
            $date = $date_obj->format('Y-m-d');
            $data[$date] = 0;
            $date_obj->modify('+1 day');
        }
        
        // Populate with actual data
        foreach ($results as $row) {
            if (isset($data[$row->date])) {
                $data[$row->date] = (int) $row->clicks;
            }
        }
        
        // Format for chart
        $formatted_data = array();
        foreach ($data as $date => $clicks) {
            $formatted_data[] = array(
                'date' => $date,
                'clicks' => $clicks
            );
        }
        
        return $formatted_data;
    }

    /**
     * Get clicks over time for all links.
     */
    private function get_global_clicks_over_time($date_range = '30', $from_date = '', $to_date = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        
        // Determine the date range parameters
        list($start_date, $end_date, $days) = $this->get_date_range_params($date_range, $from_date, $to_date);
        
        // Log the calculated date range for debugging
        error_log('Global Clicks - Start Date: ' . $start_date . ', End Date: ' . $end_date . ', Days: ' . $days);
        
        // Query to get actual click data for the specified date range
        $query = $wpdb->prepare(
            "SELECT DATE(click_time) as date, COUNT(*) as clicks
            FROM $table_name
            WHERE click_time BETWEEN %s AND %s
            GROUP BY DATE(click_time)
            ORDER BY date ASC",
            $start_date, $end_date . ' 23:59:59'
        );
        
        // Log the SQL query for debugging
        error_log('Global Clicks SQL: ' . $query);
        
        $results = $wpdb->get_results($query);
        
        // Fill in missing dates with zeros
        $data = array();
        $date_obj = new DateTime($start_date);
        $end_date_obj = new DateTime($end_date);
        
        while ($date_obj <= $end_date_obj) {
            $date = $date_obj->format('Y-m-d');
            $data[$date] = 0;
            $date_obj->modify('+1 day');
        }
        
        // Populate with actual data
        foreach ($results as $row) {
            if (isset($data[$row->date])) {
                $data[$row->date] = (int) $row->clicks;
            }
        }
        
        // Format for chart
        $formatted_data = array();
        foreach ($data as $date => $clicks) {
            $formatted_data[] = array(
                'date' => $date,
                'clicks' => $clicks
            );
        }
        
        return $formatted_data;
    }

    /**
     * Get top performing links.
     */
    private function get_top_links($date_range = '30', $from_date = '', $to_date = '', $limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        
        // Determine the date range parameters
        list($start_date, $end_date, $days) = $this->get_date_range_params($date_range, $from_date, $to_date);
        
        // Log the calculated date range for debugging
        error_log('Top Links - Start Date: ' . $start_date . ', End Date: ' . $end_date . ', Days: ' . $days);
        
        // Get top links by clicks
        $query = $wpdb->prepare(
            "SELECT p.ID, p.post_title, COUNT(*) as clicks, COUNT(DISTINCT visitor_id) as unique_visitors
            FROM $table_name c
            JOIN {$wpdb->posts} p ON c.post_id = p.ID
            WHERE p.post_type = 'wplinktracker'
            AND c.click_time BETWEEN %s AND %s
            GROUP BY p.ID
            ORDER BY clicks DESC
            LIMIT %d",
            $start_date, $end_date . ' 23:59:59', $limit
        );
        
        // Log the SQL query for debugging
        error_log('Top Links SQL: ' . $query);
        
        $results = $wpdb->get_results($query);
        
        // If no results, check if there are any tracked links without clicks
        if (empty($results)) {
            $links_query = "
                SELECT ID, post_title
                FROM {$wpdb->posts}
                WHERE post_type = 'wplinktracker'
                AND post_status = 'publish'
                LIMIT %d
            ";
            
            $links = $wpdb->get_results($wpdb->prepare($links_query, $limit));
            
            // Format links with zero clicks
            foreach ($links as $link) {
                $link->clicks = 0;
                $link->unique_visitors = 0;
            }
            
            return $links;
        }
        
        return $results;
    }

    /**
     * Get top referrers for a specific link.
     */
    private function get_top_referrers($post_id, $limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT referrer, COUNT(*) as count
            FROM $table_name
            WHERE post_id = %d AND referrer != ''
            GROUP BY referrer
            ORDER BY count DESC
            LIMIT %d",
            $post_id, $limit
        ));
        
        // If no results, return empty array
        if (empty($results)) {
            return array();
        }
        
        return $results;
    }

    /**
     * Get top referrers across all links.
     */
    private function get_top_referrers_global($date_range = '30', $from_date = '', $to_date = '', $limit = 10) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        
        // Determine the date range parameters
        list($start_date, $end_date, $days) = $this->get_date_range_params($date_range, $from_date, $to_date);
        
        // Log the calculated date range for debugging
        error_log('Top Referrers - Start Date: ' . $start_date . ', End Date: ' . $end_date . ', Days: ' . $days);
        
        $query = $wpdb->prepare(
            "SELECT referrer, COUNT(*) as count
            FROM $table_name
            WHERE referrer != ''
            AND click_time BETWEEN %s AND %s
            GROUP BY referrer
            ORDER BY count DESC
            LIMIT %d",
            $start_date, $end_date . ' 23:59:59', $limit
        );
        
        // Log the SQL query for debugging
        error_log('Top Referrers SQL: ' . $query);
        
        $results = $wpdb->get_results($query);
        
        // If no results, return empty array
        if (empty($results)) {
            return array();
        }
        
        return $results;
    }

    /**
     * Get dashboard summary data.
     */
    private function get_dashboard_summary($date_range = '30', $from_date = '', $to_date = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        
        // Determine the date range parameters
        list($start_date, $end_date, $days) = $this->get_date_range_params($date_range, $from_date, $to_date);
        
        // Log the calculated date range for debugging
        error_log('Dashboard Summary - Start Date: ' . $start_date . ', End Date: ' . $end_date . ', Days: ' . $days);
        
        // First, check if the table exists and has data
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            error_log("ERROR: Table $table_name does not exist!");
            return array(
                'total_clicks' => 0,
                'unique_visitors' => 0,
                'active_links' => 0,
                'conversion_rate' => '0%'
            );
        }
        
        // Count total rows in the table for debugging
        $total_rows = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        error_log("Total rows in $table_name: $total_rows");
        
        // Get total clicks - Direct query with explicit date filtering
        $total_clicks_query = $wpdb->prepare(
            "SELECT COUNT(*) 
            FROM $table_name 
            WHERE click_time BETWEEN %s AND %s",
            $start_date, $end_date . ' 23:59:59'
        );
        
        // Log the SQL query for debugging
        error_log('Total Clicks SQL: ' . $total_clicks_query);
        
        $total_clicks = (int) $wpdb->get_var($total_clicks_query);
        
        // Double-check with a simpler query
        $simple_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        error_log("Simple count of all clicks (no date filter): $simple_count");
        
        // Get unique visitors - Direct query with explicit date filtering
        $unique_visitors_query = $wpdb->prepare(
            "SELECT COUNT(DISTINCT visitor_id) 
            FROM $table_name 
            WHERE click_time BETWEEN %s AND %s",
            $start_date, $end_date . ' 23:59:59'
        );
        
        // Log the SQL query for debugging
        error_log('Unique Visitors SQL: ' . $unique_visitors_query);
        
        $unique_visitors = (int) $wpdb->get_var($unique_visitors_query);
        
        // Get active links - Direct query with explicit date filtering
        $active_links_query = $wpdb->prepare(
            "SELECT COUNT(DISTINCT post_id) 
            FROM $table_name 
            WHERE click_time BETWEEN %s AND %s",
            $start_date, $end_date . ' 23:59:59'
        );
        
        // Log the SQL query for debugging
        error_log('Active Links SQL: ' . $active_links_query);
        
        $active_links = (int) $wpdb->get_var($active_links_query);
        
        // Calculate average conversion rate
        $conversion_rate = ($unique_visitors > 0) ? round(($total_clicks / $unique_visitors) * 100, 2) . '%' : '0%';
        
        // If no data, get total number of tracked links
        if (empty($active_links)) {
            $active_links = $wpdb->get_var("
                SELECT COUNT(*)
                FROM {$wpdb->posts}
                WHERE post_type = 'wplinktracker'
                AND post_status = 'publish'
            ");
        }
        
        // Ensure we have numeric values
        $total_clicks = !empty($total_clicks) ? intval($total_clicks) : 0;
        $unique_visitors = !empty($unique_visitors) ? intval($unique_visitors) : 0;
        $active_links = !empty($active_links) ? intval($active_links) : 0;
        
        // Log the final summary data
        error_log("Final Summary Data - Clicks: $total_clicks, Visitors: $unique_visitors, Links: $active_links, Conversion: $conversion_rate");
        
        return array(
            'total_clicks' => $total_clicks,
            'unique_visitors' => $unique_visitors,
            'active_links' => $active_links,
            'conversion_rate' => $conversion_rate
        );
    }

    /**
     * Get device breakdown for a specific link.
     */
    private function get_device_breakdown($post_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        
        // Get device types
        $device_types = $wpdb->get_results($wpdb->prepare(
            "SELECT device_type, COUNT(*) as count
            FROM $table_name
            WHERE post_id = %d
            GROUP BY device_type
            ORDER BY count DESC",
            $post_id
        ));
        
        // Get browsers
        $browsers = $wpdb->get_results($wpdb->prepare(
            "SELECT browser, COUNT(*) as count
            FROM $table_name
            WHERE post_id = %d
            GROUP BY browser
            ORDER BY count DESC
            LIMIT 5",
            $post_id
        ));
        
        // Get operating systems
        $operating_systems = $wpdb->get_results($wpdb->prepare(
            "SELECT os, COUNT(*) as count
            FROM $table_name
            WHERE post_id = %d
            GROUP BY os
            ORDER BY count DESC
            LIMIT 5",
            $post_id
        ));
        
        return array(
            'device_types' => $device_types,
            'browsers' => $browsers,
            'operating_systems' => $operating_systems
        );
    }

    /**
     * Get global device breakdown across all links.
     */
    private function get_global_device_breakdown($date_range = '30', $from_date = '', $to_date = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        
        // Determine the date range parameters
        list($start_date, $end_date, $days) = $this->get_date_range_params($date_range, $from_date, $to_date);
        
        // Log the calculated date range for debugging
        error_log('Device Breakdown - Start Date: ' . $start_date . ', End Date: ' . $end_date . ', Days: ' . $days);
        
        // Get device types
        $device_types_query = $wpdb->prepare(
            "SELECT device_type, COUNT(*) as count
            FROM $table_name
            WHERE click_time BETWEEN %s AND %s
            GROUP BY device_type
            ORDER BY count DESC",
            $start_date, $end_date . ' 23:59:59'
        );
        
        // Log the SQL query for debugging
        error_log('Device Types SQL: ' . $device_types_query);
        
        $device_types_results = $wpdb->get_results($device_types_query);
        
        // Format device types for chart
        $device_types = array(
            'labels' => array(),
            'data' => array()
        );
        
        // If no device data, provide default categories
        if (empty($device_types_results)) {
            $device_types['labels'] = array('Desktop', 'Mobile', 'Tablet');
            $device_types['data'] = array(0, 0, 0);
        } else {
            foreach ($device_types_results as $row) {
                $device_types['labels'][] = empty($row->device_type) ? 'Unknown' : $row->device_type;
                $device_types['data'][] = (int) $row->count;
            }
        }
        
        // Get browsers
        $browsers_query = $wpdb->prepare(
            "SELECT browser, COUNT(*) as count
            FROM $table_name
            WHERE click_time BETWEEN %s AND %s
            GROUP BY browser
            ORDER BY count DESC
            LIMIT 5",
            $start_date, $end_date . ' 23:59:59'
        );
        
        // Log the SQL query for debugging
        error_log('Browsers SQL: ' . $browsers_query);
        
        $browsers_results = $wpdb->get_results($browsers_query);
        
        // Format browsers for chart
        $browsers = array(
            'labels' => array(),
            'data' => array()
        );
        
        // If no browser data, provide default categories
        if (empty($browsers_results)) {
            $browsers['labels'] = array('Chrome', 'Firefox', 'Safari', 'Edge', 'Other');
            $browsers['data'] = array(0, 0, 0, 0, 0);
        } else {
            foreach ($browsers_results as $row) {
                $browsers['labels'][] = empty($row->browser) ? 'Unknown' : $row->browser;
                $browsers['data'][] = (int) $row->count;
            }
        }
        
        // Get operating systems
        $os_query = $wpdb->prepare(
            "SELECT os, COUNT(*) as count
            FROM $table_name
            WHERE click_time BETWEEN %s AND %s
            GROUP BY os
            ORDER BY count DESC
            LIMIT 5",
            $start_date, $end_date . ' 23:59:59'
        );
        
        // Log the SQL query for debugging
        error_log('OS SQL: ' . $os_query);
        
        $os_results = $wpdb->get_results($os_query);
        
        // Format operating systems for chart
        $operating_systems = array(
            'labels' => array(),
            'data' => array()
        );
        
        // If no OS data, provide default categories
        if (empty($os_results)) {
            $operating_systems['labels'] = array('Windows', 'macOS', 'iOS', 'Android', 'Linux');
            $operating_systems['data'] = array(0, 0, 0, 0, 0);
        } else {
            foreach ($os_results as $row) {
                $operating_systems['labels'][] = empty($row->os) ? 'Unknown' : $row->os;
                $operating_systems['data'][] = (int) $row->count;
            }
        }
        
        return array(
            'device_types' => $device_types,
            'browsers' => $browsers,
            'operating_systems' => $operating_systems
        );
    }

    /**
     * Get date range parameters based on input.
     * Returns array with start_date, end_date, and days.
     */
    private function get_date_range_params($date_range, $from_date, $to_date) {
        // Get current date in WordPress timezone
        $current_date = current_time('Y-m-d');
        $end_date = $current_date;
        
        if ($date_range === 'custom' && !empty($from_date) && !empty($to_date)) {
            $start_date = $from_date;
            $end_date = $to_date;
            
            // Calculate days between dates
            $from = new DateTime($from_date);
            $to = new DateTime($to_date);
            $interval = $from->diff($to);
            $days = $interval->days + 1; // Include both start and end dates
            
            // Log custom date range
            error_log("Custom date range: From $from_date to $to_date ($days days)");
        } else {
            // Handle numeric date ranges (7, 30, 90, 365)
            $days = intval($date_range);
            if ($days <= 0) {
                $days = 30; // Default to 30 days
            }
            
            // Calculate start date based on days
            $start_date = date('Y-m-d', strtotime("-$days days", strtotime($current_date)));
            
            // Log standard date range
            error_log("Standard date range: Last $days days (from $start_date to $end_date)");
        }
        
        return array($start_date, $end_date, $days);
    }

    /**
     * Build top links table HTML.
     */
    private function build_top_links_table($links) {
        if (empty($links)) {
            return '<p>' . __('No link data available. Create and share some links to start tracking!', 'wp-link-tracker') . '</p>';
        }
        
        $html = '<table class="widefat striped">';
        $html .= '<thead><tr>';
        $html .= '<th>' . __('Link', 'wp-link-tracker') . '</th>';
        $html .= '<th>' . __('Clicks', 'wp-link-tracker') . '</th>';
        $html .= '<th>' . __('Conversion Rate', 'wp-link-tracker') . '</th>';
        $html .= '</tr></thead>';
        $html .= '<tbody>';
        
        foreach ($links as $link) {
            $conversion_rate = ($link->unique_visitors > 0) ? round(($link->clicks / $link->unique_visitors) * 100, 2) . '%' : '0%';
            
            $html .= '<tr>';
            $html .= '<td><a href="' . admin_url('post.php?post=' . $link->ID . '&action=edit') . '">' . esc_html($link->post_title) . '</a></td>';
            $html .= '<td>' . esc_html($link->clicks) . '</td>';
            $html .= '<td>' . esc_html($conversion_rate) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        
        return $html;
    }

    /**
     * Build referrers table HTML.
     */
    private function build_referrers_table($referrers) {
        if (empty($referrers)) {
            return '<p>' . __('No referrer data available. This will populate as your links receive traffic.', 'wp-link-tracker') . '</p>';
        }
        
        $html = '<table class="widefat striped">';
        $html .= '<thead><tr>';
        $html .= '<th>' . __('Referrer', 'wp-link-tracker') . '</th>';
        $html .= '<th>' . __('Clicks', 'wp-link-tracker') . '</th>';
        $html .= '</tr></thead>';
        $html .= '<tbody>';
        
        foreach ($referrers as $referrer) {
            $display_referrer = empty($referrer->referrer) ? __('Direct', 'wp-link-tracker') : esc_html(parse_url($referrer->referrer, PHP_URL_HOST));
            
            $html .= '<tr>';
            $html .= '<td>' . $display_referrer . '</td>';
            $html .= '<td>' . esc_html($referrer->count) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        
        return $html;
    }

    /**
     * Build devices table HTML.
     */
    private function build_devices_table($devices) {
        $html = '<div class="wplinktracker-devices-grid">';
        
        // Device types
        $html .= '<div class="wplinktracker-device-section">';
        $html .= '<h4>' . __('Device Types', 'wp-link-tracker') . '</h4>';
        
        if (empty($devices['device_types'])) {
            $html .= '<p>' . __('No device data available yet.', 'wp-link-tracker') . '</p>';
        } else {
            $html .= '<div style="max-height: 300px; overflow-y: auto;">';
            $html .= '<table class="widefat striped">';
            $html .= '<thead><tr>';
            $html .= '<th>' . __('Device', 'wp-link-tracker') . '</th>';
            $html .= '<th>' . __('Clicks', 'wp-link-tracker') . '</th>';
            $html .= '</tr></thead>';
            $html .= '<tbody>';
            
            foreach ($devices['device_types'] as $device) {
                $html .= '<tr>';
                $html .= '<td>' . esc_html($device->device_type) . '</td>';
                $html .= '<td>' . esc_html($device->count) . '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        // Browsers
        $html .= '<div class="wplinktracker-device-section">';
        $html .= '<h4>' . __('Browsers', 'wp-link-tracker') . '</h4>';
        
        if (empty($devices['browsers'])) {
            $html .= '<p>' . __('No browser data available yet.', 'wp-link-tracker') . '</p>';
        } else {
            $html .= '<div style="max-height: 300px; overflow-y: auto;">';
            $html .= '<table class="widefat striped">';
            $html .= '<thead><tr>';
            $html .= '<th>' . __('Browser', 'wp-link-tracker') . '</th>';
            $html .= '<th>' . __('Clicks', 'wp-link-tracker') . '</th>';
            $html .= '</tr></thead>';
            $html .= '<tbody>';
            
            foreach ($devices['browsers'] as $browser) {
                $html .= '<tr>';
                $html .= '<td>' . esc_html($browser->browser) . '</td>';
                $html .= '<td>' . esc_html($browser->count) . '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        // Operating systems
        $html .= '<div class="wplinktracker-device-section">';
        $html .= '<h4>' . __('Operating Systems', 'wp-link-tracker') . '</h4>';
        
        if (empty($devices['operating_systems'])) {
            $html .= '<p>' . __('No OS data available yet.', 'wp-link-tracker') . '</p>';
        } else {
            $html .= '<div style="max-height: 300px; overflow-y: auto;">';
            $html .= '<table class="widefat striped">';
            $html .= '<thead><tr>';
            $html .= '<th>' . __('OS', 'wp-link-tracker') . '</th>';
            $html .= '<th>' . __('Clicks', 'wp-link-tracker') . '</th>';
            $html .= '</tr></thead>';
            $html .= '<tbody>';
            
            foreach ($devices['operating_systems'] as $os) {
                $html .= '<tr>';
                $html .= '<td>' . esc_html($os->os) . '</td>';
                $html .= '<td>' . esc_html($os->count) . '</td>';
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        $html .= '</div>';
        
        $html .= '<style>
            .wplinktracker-devices-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                grid-gap: 15px;
            }
            .wplinktracker-device-section {
                background: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 10px;
            }
            @media (max-width: 782px) {
                .wplinktracker-devices-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>';
        
        return $html;
    }
}
