<?php
/**
 * Admin functionality for the plugin
 */
class WP_Link_Tracker_Admin {
    /**
     * Initialize the class.
     */
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('manage_wplinktracker_posts_columns', array($this, 'set_custom_columns'));
        add_action('manage_wplinktracker_posts_custom_column', array($this, 'custom_column_content'), 10, 2);
        add_filter('manage_edit-wplinktracker_sortable_columns', array($this, 'set_sortable_columns'));
        
        // AJAX handlers
        add_action('wp_ajax_wp_link_tracker_create_link', array($this, 'create_link_ajax'));
        add_action('wp_ajax_wp_link_tracker_reset_data', array($this, 'reset_data_ajax'));
        add_action('wp_ajax_wp_link_tracker_get_dashboard_summary', array($this, 'get_dashboard_summary_ajax'));
        add_action('wp_ajax_wp_link_tracker_debug_date_range', array($this, 'debug_date_range_ajax'));
        add_action('wp_ajax_wp_link_tracker_get_clicks_over_time', array($this, 'get_clicks_over_time_ajax'));
        add_action('wp_ajax_wp_link_tracker_get_top_links', array($this, 'get_top_links_ajax'));
        add_action('wp_ajax_wp_link_tracker_get_top_referrers', array($this, 'get_top_referrers_ajax'));
        add_action('wp_ajax_wp_link_tracker_get_device_data', array($this, 'get_device_data_ajax'));
        
        // Add reset data button to dashboard
        add_action('admin_notices', array($this, 'add_reset_data_notice'));
    }

    /**
     * Add admin menu items.
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=wplinktracker',
            __('Dashboard', 'wp-link-tracker'),
            __('Dashboard', 'wp-link-tracker'),
            'manage_options',
            'wp-link-tracker-dashboard',
            array($this, 'render_dashboard_page')
        );
        
        add_submenu_page(
            'edit.php?post_type=wplinktracker',
            __('Settings', 'wp-link-tracker'),
            __('Settings', 'wp-link-tracker'),
            'manage_options',
            'wp-link-tracker-settings',
            array($this, 'render_settings_page')
        );
        
        // Add Data Count page
        add_submenu_page(
            'edit.php?post_type=wplinktracker',
            __('Data Count', 'wp-link-tracker'),
            __('Data Count', 'wp-link-tracker'),
            'manage_options',
            'wp-link-tracker-data-count',
            array($this, 'render_data_count_page')
        );
    }

    /**
     * Render the data count page.
     */
    public function render_data_count_page() {
        include_once(WP_LINK_TRACKER_PLUGIN_DIR . 'admin/get-data-count.php');
    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_scripts($hook) {
        // Only enqueue on our plugin pages
        if (strpos($hook, 'wp-link-tracker') === false && 
            !($hook === 'post.php' && get_post_type() === 'wplinktracker') &&
            !($hook === 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'wplinktracker')) {
            return;
        }
        
        // Enqueue jQuery UI for datepicker
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
        
        // Enqueue Chart.js for charts
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js',
            array(),
            '3.7.1',
            true
        );
        
        // Enqueue our admin script with a unique version to prevent caching
        $version = WP_LINK_TRACKER_VERSION . '.' . time();
        
        wp_enqueue_script(
            'wp-link-tracker-admin',
            WP_LINK_TRACKER_PLUGIN_URL . 'admin/js/wp-link-tracker-admin.js',
            array('jquery', 'jquery-ui-datepicker', 'chartjs'),
            $version,
            true
        );
        
        // Localize script with nonce and other data
        wp_localize_script(
            'wp-link-tracker-admin',
            'wpLinkTrackerData',
            array(
                'nonce' => wp_create_nonce('wp_link_tracker_dashboard_nonce'),
                'resetNonce' => wp_create_nonce('wp_link_tracker_reset_data_nonce'),
                'ajaxUrl' => admin_url('admin-ajax.php')
            )
        );
        
        // Enqueue our admin styles with a unique version to prevent caching
        wp_enqueue_style(
            'wp-link-tracker-admin',
            WP_LINK_TRACKER_PLUGIN_URL . 'admin/css/wp-link-tracker-admin.css',
            array(),
            $version
        );
    }

    /**
     * Register plugin settings.
     */
    public function register_settings() {
        register_setting('wp_link_tracker_settings', 'wp_link_tracker_settings');
        
        add_settings_section(
            'wp_link_tracker_general_settings',
            __('General Settings', 'wp-link-tracker'),
            array($this, 'render_general_settings_section'),
            'wp_link_tracker_settings'
        );
        
        add_settings_field(
            'link_prefix',
            __('Link Prefix', 'wp-link-tracker'),
            array($this, 'render_link_prefix_field'),
            'wp_link_tracker_settings',
            'wp_link_tracker_general_settings'
        );
    }

    /**
     * Render the general settings section.
     */
    public function render_general_settings_section() {
        echo '<p>' . __('Configure general settings for the WP Link Tracker plugin.', 'wp-link-tracker') . '</p>';
    }

    /**
     * Render the link prefix field.
     */
    public function render_link_prefix_field() {
        $options = get_option('wp_link_tracker_settings');
        $link_prefix = isset($options['link_prefix']) ? $options['link_prefix'] : 'go';
        
        echo '<input type="text" id="link_prefix" name="wp_link_tracker_settings[link_prefix]" value="' . esc_attr($link_prefix) . '" />';
        echo '<p class="description">' . __('The prefix for shortened links. Default is "go".', 'wp-link-tracker') . '</p>';
        echo '<p class="description">' . __('Example: yourdomain.com/go/abc123', 'wp-link-tracker') . '</p>';
    }

    /**
     * Set custom columns for the tracked links list.
     */
    public function set_custom_columns($columns) {
        $new_columns = array();
        
        // Add checkbox and title first
        if (isset($columns['cb'])) {
            $new_columns['cb'] = $columns['cb'];
        }
        
        if (isset($columns['title'])) {
            $new_columns['title'] = $columns['title'];
        }
        
        // Add our custom columns
        $new_columns['destination'] = __('Destination URL', 'wp-link-tracker');
        $new_columns['short_url'] = __('Short URL', 'wp-link-tracker');
        $new_columns['clicks'] = __('Clicks', 'wp-link-tracker');
        $new_columns['unique_visitors'] = __('Unique Visitors', 'wp-link-tracker');
        $new_columns['conversion_rate'] = __('Conversion Rate', 'wp-link-tracker');
        $new_columns['date'] = __('Date', 'wp-link-tracker');
        
        return $new_columns;
    }

    /**
     * Display content for custom columns.
     */
    public function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'destination':
                $destination_url = get_post_meta($post_id, '_wplinktracker_destination_url', true);
                if (!empty($destination_url)) {
                    echo '<a href="' . esc_url($destination_url) . '" target="_blank">' . esc_url($destination_url) . '</a>';
                } else {
                    echo '—';
                }
                break;
                
            case 'short_url':
                $short_code = get_post_meta($post_id, '_wplinktracker_short_code', true);
                if (!empty($short_code)) {
                    $short_url = home_url('go/' . $short_code);
                    echo '<a href="' . esc_url($short_url) . '" target="_blank">' . esc_url($short_url) . '</a>';
                    echo '<br><button type="button" class="button button-small copy-to-clipboard" data-clipboard-text="' . esc_url($short_url) . '">' . __('Copy', 'wp-link-tracker') . '</button>';
                } else {
                    echo '—';
                }
                break;
                
            case 'clicks':
                $total_clicks = get_post_meta($post_id, '_wplinktracker_total_clicks', true);
                echo !empty($total_clicks) ? esc_html($total_clicks) : '0';
                break;
                
            case 'unique_visitors':
                $unique_visitors = get_post_meta($post_id, '_wplinktracker_unique_visitors', true);
                echo !empty($unique_visitors) ? esc_html($unique_visitors) : '0';
                break;
                
            case 'conversion_rate':
                $total_clicks = (int) get_post_meta($post_id, '_wplinktracker_total_clicks', true);
                $unique_visitors = (int) get_post_meta($post_id, '_wplinktracker_unique_visitors', true);
                $conversion_rate = ($unique_visitors > 0) ? round(($total_clicks / $unique_visitors) * 100, 2) . '%' : '0%';
                echo esc_html($conversion_rate);
                break;
        }
    }

    /**
     * Set sortable columns.
     */
    public function set_sortable_columns($columns) {
        $columns['clicks'] = 'clicks';
        $columns['unique_visitors'] = 'unique_visitors';
        $columns['conversion_rate'] = 'conversion_rate';
        return $columns;
    }

    /**
     * Add reset data notice to dashboard.
     */
    public function add_reset_data_notice() {
        $screen = get_current_screen();
        
        // Only show on our dashboard page
        if ($screen->id !== 'wplinktracker_page_wp-link-tracker-dashboard') {
            return;
        }
        
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong><?php _e('Reset Data:', 'wp-link-tracker'); ?></strong> 
                <?php _e('Click the button below to reset all click data to be between May 27, 2025 and June 11, 2025.', 'wp-link-tracker'); ?>
                <button type="button" id="wplinktracker-reset-data" class="button button-primary"><?php _e('Reset Data', 'wp-link-tracker'); ?></button>
                <span id="wplinktracker-reset-status" style="margin-left: 10px; display: none;"></span>
            </p>
        </div>
        <?php
    }

    /**
     * Render the dashboard page.
     */
    public function render_dashboard_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="wplinktracker-dashboard">
                <!-- Date Range Selector -->
                <div class="wplinktracker-date-range">
                    <div class="wplinktracker-date-range-controls">
                        <label for="wplinktracker-date-range-select"><?php _e('Date Range:', 'wp-link-tracker'); ?></label>
                        <select id="wplinktracker-date-range-select">
                            <option value="7"><?php _e('Last 7 Days', 'wp-link-tracker'); ?></option>
                            <option value="30" selected><?php _e('Last 30 Days', 'wp-link-tracker'); ?></option>
                            <option value="90"><?php _e('Last 90 Days', 'wp-link-tracker'); ?></option>
                            <option value="365"><?php _e('Last Year', 'wp-link-tracker'); ?></option>
                            <option value="custom"><?php _e('Custom Range', 'wp-link-tracker'); ?></option>
                        </select>
                        
                        <div id="wplinktracker-custom-date-range" style="display: none;">
                            <label for="wplinktracker-date-from"><?php _e('From:', 'wp-link-tracker'); ?></label>
                            <input type="text" id="wplinktracker-date-from" class="wplinktracker-datepicker">
                            
                            <label for="wplinktracker-date-to"><?php _e('To:', 'wp-link-tracker'); ?></label>
                            <input type="text" id="wplinktracker-date-to" class="wplinktracker-datepicker">
                            
                            <button type="button" id="wplinktracker-apply-date-range" class="button button-primary"><?php _e('Apply', 'wp-link-tracker'); ?></button>
                        </div>
                        
                        <button type="button" id="wplinktracker-refresh-dashboard" class="button"><?php _e('Refresh Data', 'wp-link-tracker'); ?></button>
                        
                        <a href="<?php echo admin_url('edit.php?post_type=wplinktracker&page=wp-link-tracker-data-count'); ?>" class="button"><?php _e('View Data Count', 'wp-link-tracker'); ?></a>
                        
                        <!-- Debug button for date range -->
                        <button type="button" id="wplinktracker-debug-date-range" class="button button-secondary"><?php _e('Debug Date Range', 'wp-link-tracker'); ?></button>
                    </div>
                </div>
                
                <!-- Debug output -->
                <div id="wplinktracker-debug-output" style="display: none; margin-bottom: 20px; padding: 15px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 4px;">
                    <h3><?php _e('Debug Information', 'wp-link-tracker'); ?></h3>
                    <pre id="wplinktracker-debug-content"></pre>
                </div>
                
                <!-- Dashboard Summary -->
                <div class="wplinktracker-summary">
                    <div class="wplinktracker-summary-card">
                        <h3><?php _e('Total Clicks', 'wp-link-tracker'); ?></h3>
                        <div class="wplinktracker-summary-value" id="wplinktracker-total-clicks">Loading...</div>
                    </div>
                    
                    <div class="wplinktracker-summary-card">
                        <h3><?php _e('Unique Visitors', 'wp-link-tracker'); ?></h3>
                        <div class="wplinktracker-summary-value" id="wplinktracker-unique-visitors">Loading...</div>
                    </div>
                    
                    <div class="wplinktracker-summary-card">
                        <h3><?php _e('Active Links', 'wp-link-tracker'); ?></h3>
                        <div class="wplinktracker-summary-value" id="wplinktracker-active-links">Loading...</div>
                    </div>
                    
                    <div class="wplinktracker-summary-card">
                        <h3><?php _e('Conversion Rate', 'wp-link-tracker'); ?></h3>
                        <div class="wplinktracker-summary-value" id="wplinktracker-conversion-rate">Loading...</div>
                    </div>
                </div>
                
                <!-- Clicks Over Time Chart -->
                <div class="wplinktracker-chart-container">
                    <h2><?php _e('Clicks Over Time', 'wp-link-tracker'); ?></h2>
                    <div class="wplinktracker-chart-wrapper">
                        <canvas id="wplinktracker-clicks-chart"></canvas>
                    </div>
                </div>
                
                <!-- Device, Browser, and OS Charts -->
                <div class="wplinktracker-chart-container">
                    <h2><?php _e('Device Analytics', 'wp-link-tracker'); ?></h2>
                    <div class="wplinktracker-device-charts">
                        <div class="wplinktracker-device-chart">
                            <h3><?php _e('Device Types', 'wp-link-tracker'); ?></h3>
                            <div class="wplinktracker-chart-wrapper">
                                <canvas id="wplinktracker-device-chart"></canvas>
                            </div>
                        </div>
                        <div class="wplinktracker-device-chart">
                            <h3><?php _e('Browsers', 'wp-link-tracker'); ?></h3>
                            <div class="wplinktracker-chart-wrapper">
                                <canvas id="wplinktracker-browser-chart"></canvas>
                            </div>
                        </div>
                        <div class="wplinktracker-device-chart">
                            <h3><?php _e('Operating Systems', 'wp-link-tracker'); ?></h3>
                            <div class="wplinktracker-chart-wrapper">
                                <canvas id="wplinktracker-os-chart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Top Links and Referrers -->
                <div class="wplinktracker-data-grid">
                    <!-- Top Links -->
                    <div class="wplinktracker-data-container">
                        <h2><?php _e('Top Performing Links', 'wp-link-tracker'); ?></h2>
                        <div id="wplinktracker-top-links">
                            <p><?php _e('Loading...', 'wp-link-tracker'); ?></p>
                        </div>
                    </div>
                    
                    <!-- Top Referrers -->
                    <div class="wplinktracker-data-container">
                        <h2><?php _e('Top Referrers', 'wp-link-tracker'); ?></h2>
                        <div id="wplinktracker-top-referrers">
                            <p><?php _e('Loading...', 'wp-link-tracker'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('wp_link_tracker_settings');
                do_settings_sections('wp_link_tracker_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Create a new link via AJAX.
     */
    public function create_link_ajax() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_create_link_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check if user has permission
        if (!current_user_can('publish_posts')) {
            wp_send_json_error('Permission denied');
        }
        
        // Check required fields
        if (!isset($_POST['title']) || !isset($_POST['destination_url'])) {
            wp_send_json_error('Missing required fields');
        }
        
        $title = sanitize_text_field($_POST['title']);
        $destination_url = esc_url_raw($_POST['destination_url']);
        $campaign = isset($_POST['campaign']) ? sanitize_text_field($_POST['campaign']) : '';
        
        // Create the post
        $post_id = wp_insert_post(array(
            'post_title' => $title,
            'post_type' => 'wplinktracker',
            'post_status' => 'publish'
        ));
        
        if (is_wp_error($post_id)) {
            wp_send_json_error($post_id->get_error_message());
        }
        
        // Set the destination URL
        update_post_meta($post_id, '_wplinktracker_destination_url', $destination_url);
        
        // Generate short code
        $short_code = $this->generate_short_code();
        update_post_meta($post_id, '_wplinktracker_short_code', $short_code);
        
        // Set campaign if provided
        if (!empty($campaign)) {
            wp_set_object_terms($post_id, $campaign, 'wplinktracker_campaign');
        }
        
        // Get the short URL
        $short_url = home_url('go/' . $short_code);
        
        wp_send_json_success(array(
            'post_id' => $post_id,
            'short_url' => $short_url,
            'shortcode' => '[tracked_link id="' . $post_id . '"]'
        ));
    }

    /**
     * Reset data via AJAX.
     */
    public function reset_data_ajax() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_reset_data_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        
        // Start transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // First, check if the table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            
            if (!$table_exists) {
                throw new Exception("Table $table_name does not exist!");
            }
            
            // Get the current number of records
            $current_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            
            // Define the date range
            $start_date = '2025-05-27';
            $end_date = '2025-06-11';
            
            // Calculate the number of days in the range
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);
            $interval = $start->diff($end);
            $days = $interval->days + 1; // Include both start and end dates
            
            // Clear the table
            $wpdb->query("TRUNCATE TABLE $table_name");
            
            // Get all tracked links
            $links = get_posts(array(
                'post_type' => 'wplinktracker',
                'posts_per_page' => -1,
                'post_status' => 'publish'
            ));
            
            // If no links, create a sample one
            if (empty($links)) {
                $post_id = wp_insert_post(array(
                    'post_title' => 'Sample Link',
                    'post_type' => 'wplinktracker',
                    'post_status' => 'publish'
                ));
                
                update_post_meta($post_id, '_wplinktracker_destination_url', 'https://example.com');
                update_post_meta($post_id, '_wplinktracker_short_code', $this->generate_short_code());
                
                $links = array(get_post($post_id));
            }
            
            // Generate random click data
            $total_clicks = 0;
            $clicks_per_link = array();
            
            // Track clicks by date for debugging
            $clicks_by_date = array();
            
            foreach ($links as $link) {
                $link_id = $link->ID;
                $clicks_per_link[$link_id] = 0;
                
                // Generate between 5 and 20 clicks per link
                $num_clicks = rand(5, 20);
                
                for ($i = 0; $i < $num_clicks; $i++) {
                    // Random date within the range
                    $random_days = rand(0, $days - 1);
                    $click_date = date('Y-m-d H:i:s', strtotime($start_date . " +$random_days days +" . rand(0, 23) . " hours +" . rand(0, 59) . " minutes +" . rand(0, 59) . " seconds"));
                    
                    // Track clicks by date for debugging
                    $date_only = date('Y-m-d', strtotime($click_date));
                    if (!isset($clicks_by_date[$date_only])) {
                        $clicks_by_date[$date_only] = 0;
                    }
                    $clicks_by_date[$date_only]++;
                    
                    // Random visitor ID
                    $visitor_id = md5(uniqid(rand(), true));
                    
                    // Random IP address
                    $ip_address = rand(1, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(0, 255);
                    
                    // Random user agent
                    $user_agents = array(
                        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
                        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
                        'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
                        'Mozilla/5.0 (iPad; CPU OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
                        'Mozilla/5.0 (Linux; Android 11; SM-G991B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.120 Mobile Safari/537.36'
                    );
                    $user_agent = $user_agents[array_rand($user_agents)];
                    
                    // Determine device type, browser, and OS based on user agent
                    $device_type = $this->get_device_type($user_agent);
                    $browser = $this->get_browser($user_agent);
                    $os = $this->get_operating_system($user_agent);
                    
                    // Random referrer
                    $referrers = array(
                        'https://www.google.com/',
                        'https://www.facebook.com/',
                        'https://www.twitter.com/',
                        'https://www.instagram.com/',
                        'https://www.linkedin.com/',
                        '',  // Direct traffic
                        ''   // Direct traffic
                    );
                    $referrer = $referrers[array_rand($referrers)];
                    
                    // Insert click data
                    $wpdb->insert(
                        $table_name,
                        array(
                            'post_id' => $link_id,
                            'visitor_id' => $visitor_id,
                            'ip_address' => $ip_address,
                            'user_agent' => $user_agent,
                            'referrer' => $referrer,
                            'device_type' => $device_type,
                            'browser' => $browser,
                            'os' => $os,
                            'click_time' => $click_date,
                            'utm_source' => '',
                            'utm_medium' => '',
                            'utm_campaign' => '',
                            'utm_term' => '',
                            'utm_content' => '',
                        )
                    );
                    
                    if ($wpdb->last_error) {
                        throw new Exception("Error inserting click data: " . $wpdb->last_error);
                    }
                    
                    $clicks_per_link[$link_id]++;
                    $total_clicks++;
                }
                
                // Update post meta with click counts
                update_post_meta($link_id, '_wplinktracker_total_clicks', $clicks_per_link[$link_id]);
                
                // Count unique visitors
                $unique_visitors_query = $wpdb->prepare(
                    "SELECT COUNT(DISTINCT visitor_id) FROM $table_name WHERE post_id = %d",
                    $link_id
                );
                $unique_visitors = $wpdb->get_var($unique_visitors_query);
                
                update_post_meta($link_id, '_wplinktracker_unique_visitors', $unique_visitors);
            }
            
            // Sort clicks by date for debugging
            ksort($clicks_by_date);
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            wp_send_json_success(array(
                'message' => sprintf(
                    __('Successfully reset data. Generated %d clicks across %d links between %s and %s.', 'wp-link-tracker'),
                    $total_clicks,
                    count($links),
                    date('F j, Y', strtotime($start_date)),
                    date('F j, Y', strtotime($end_date))
                ),
                'clicks_by_date' => $clicks_by_date
            ));
            
        } catch (Exception $e) {
            // Rollback transaction
            $wpdb->query('ROLLBACK');
            
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Get dashboard summary via AJAX.
     */
    public function get_dashboard_summary_ajax() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_dashboard_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        
        // First, check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            error_log("ERROR: Table $table_name does not exist!");
            wp_send_json_success(array(
                'total_clicks' => 0,
                'unique_visitors' => 0,
                'active_links' => 0,
                'conversion_rate' => '0%'
            ));
            return;
        }
        
        // Count total rows in the table for debugging
        $total_rows = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        error_log("Total rows in $table_name: $total_rows");
        
        // Get date range parameters
        $date_range = isset($_POST['date_range']) ? sanitize_text_field($_POST['date_range']) : '30';
        $from_date = isset($_POST['from_date']) ? sanitize_text_field($_POST['from_date']) : '';
        $to_date = isset($_POST['to_date']) ? sanitize_text_field($_POST['to_date']) : '';
        
        // Log the received parameters for debugging
        error_log("Dashboard Summary - Date Range: $date_range, From: $from_date, To: $to_date");
        
        // Determine the date range parameters
        list($start_date, $end_date) = $this->get_date_range_params($date_range, $from_date, $to_date);
        
        // Build the query with date filtering for total clicks
        $total_clicks_query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE click_time BETWEEN %s AND %s",
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        );
        
        // Log the SQL query for debugging
        error_log("Total Clicks SQL: $total_clicks_query");
        
        // Get total clicks with date filtering
        $total_clicks = (int) $wpdb->get_var($total_clicks_query);
        
        // Double-check with a simpler query
        $simple_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        error_log("Simple count of all clicks (no date filter): $simple_count");
        
        // Get unique visitors with date filtering
        $unique_visitors_query = $wpdb->prepare(
            "SELECT COUNT(DISTINCT visitor_id) FROM $table_name WHERE click_time BETWEEN %s AND %s",
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        );
        
        // Log the SQL query for debugging
        error_log("Unique Visitors SQL: $unique_visitors_query");
        
        $unique_visitors = (int) $wpdb->get_var($unique_visitors_query);
        
        // Get active links with date filtering
        $active_links_query = $wpdb->prepare(
            "SELECT COUNT(DISTINCT post_id) FROM $table_name WHERE click_time BETWEEN %s AND %s",
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        );
        
        // Log the SQL query for debugging
        error_log("Active Links SQL: $active_links_query");
        
        $active_links = (int) $wpdb->get_var($active_links_query);
        
        // Calculate conversion rate
        $conversion_rate = ($unique_visitors > 0) ? round(($total_clicks / $unique_visitors) * 100, 2) . '%' : '0%';
        
        // Log the results
        error_log("Total clicks for date range: $total_clicks");
        error_log("Unique visitors for date range: $unique_visitors");
        error_log("Active links for date range: $active_links");
        error_log("Conversion rate for date range: $conversion_rate");
        
        // Send the dashboard summary data
        wp_send_json_success(array(
            'total_clicks' => $total_clicks,
            'unique_visitors' => $unique_visitors,
            'active_links' => $active_links,
            'conversion_rate' => $conversion_rate
        ));
    }

    /**
     * Get clicks over time data via AJAX.
     */
    public function get_clicks_over_time_ajax() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_dashboard_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        
        // First, check if the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            error_log("ERROR: Table $table_name does not exist!");
            wp_send_json_success(array(
                'labels' => array(),
                'data' => array()
            ));
            return;
        }
        
        // Get date range parameters
        $date_range = isset($_POST['date_range']) ? sanitize_text_field($_POST['date_range']) : '30';
        $from_date = isset($_POST['from_date']) ? sanitize_text_field($_POST['from_date']) : '';
        $to_date = isset($_POST['to_date']) ? sanitize_text_field($_POST['to_date']) : '';
        
        // Log the received parameters for debugging
        error_log("Clicks Over Time - Date Range: $date_range, From: $from_date, To: $to_date");
        
        // Determine the date range parameters
        list($start_date, $end_date) = $this->get_date_range_params($date_range, $from_date, $to_date);
        
        // Build the query to get clicks by date
        $clicks_by_date_query = $wpdb->prepare(
            "SELECT DATE(click_time) as date, COUNT(*) as clicks 
            FROM $table_name 
            WHERE click_time BETWEEN %s AND %s 
            GROUP BY DATE(click_time) 
            ORDER BY date ASC",
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        );
        
        // Log the SQL query for debugging
        error_log("Clicks By Date SQL: $clicks_by_date_query");
        
        // Get clicks by date
        $results = $wpdb->get_results($clicks_by_date_query);
        
        // Create date range array
        $dates = array();
        $clicks = array();
        
        // Fill in all dates in the range with zeros
        $current_date = new DateTime($start_date);
        $end_date_obj = new DateTime($end_date);
        
        while ($current_date <= $end_date_obj) {
            $date_str = $current_date->format('Y-m-d');
            $dates[] = $date_str;
            $clicks[$date_str] = 0;
            $current_date->modify('+1 day');
        }
        
        // Fill in actual click data
        foreach ($results as $row) {
            if (isset($clicks[$row->date])) {
                $clicks[$row->date] = (int) $row->clicks;
            }
        }
        
        // Format dates for display (e.g., "May 27")
        $formatted_dates = array();
        foreach ($dates as $date) {
            $formatted_dates[] = date('M j', strtotime($date));
        }
        
        // Send the clicks over time data
        wp_send_json_success(array(
            'labels' => $formatted_dates,
            'data' => array_values($clicks)
        ));
    }

    /**
     * Get top links via AJAX.
     */
    public function get_top_links_ajax() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_dashboard_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        
        // Get date range parameters
        $date_range = isset($_POST['date_range']) ? sanitize_text_field($_POST['date_range']) : '30';
        $from_date = isset($_POST['from_date']) ? sanitize_text_field($_POST['from_date']) : '';
        $to_date = isset($_POST['to_date']) ? sanitize_text_field($_POST['to_date']) : '';
        
        // Log the received parameters for debugging
        error_log("Top Links - Date Range: $date_range, From: $from_date, To: $to_date");
        
        // Determine the date range parameters
        list($start_date, $end_date) = $this->get_date_range_params($date_range, $from_date, $to_date);
        
        // Build the query to get top links
        $top_links_query = $wpdb->prepare(
            "SELECT p.ID, p.post_title, COUNT(*) as clicks, COUNT(DISTINCT visitor_id) as unique_visitors
            FROM $table_name c
            JOIN {$wpdb->posts} p ON c.post_id = p.ID
            WHERE p.post_type = 'wplinktracker'
            AND c.click_time BETWEEN %s AND %s
            GROUP BY p.ID
            ORDER BY clicks DESC
            LIMIT 10",
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        );
        
        // Log the SQL query for debugging
        error_log("Top Links SQL: $top_links_query");
        
        // Get top links
        $results = $wpdb->get_results($top_links_query);
        
        // If no results, check if there are any tracked links without clicks
        if (empty($results)) {
            $links_query = "
                SELECT ID, post_title
                FROM {$wpdb->posts}
                WHERE post_type = 'wplinktracker'
                AND post_status = 'publish'
                LIMIT 10
            ";
            
            $results = $wpdb->get_results($links_query);
            
            // Format links with zero clicks
            foreach ($results as $link) {
                $link->clicks = 0;
                $link->unique_visitors = 0;
            }
        }
        
        // Build HTML table
        $html = '<table class="widefat striped">';
        $html .= '<thead><tr>';
        $html .= '<th>' . __('Link', 'wp-link-tracker') . '</th>';
        $html .= '<th>' . __('Clicks', 'wp-link-tracker') . '</th>';
        $html .= '<th>' . __('Conversion Rate', 'wp-link-tracker') . '</th>';
        $html .= '</tr></thead>';
        $html .= '<tbody>';
        
        if (empty($results)) {
            $html .= '<tr><td colspan="3">' . __('No link data available. Create and share some links to start tracking!', 'wp-link-tracker') . '</td></tr>';
        } else {
            foreach ($results as $link) {
                $conversion_rate = ($link->unique_visitors > 0) ? round(($link->clicks / $link->unique_visitors) * 100, 2) . '%' : '0%';
                
                $html .= '<tr>';
                $html .= '<td><a href="' . admin_url('post.php?post=' . $link->ID . '&action=edit') . '">' . esc_html($link->post_title) . '</a></td>';
                $html .= '<td>' . esc_html($link->clicks) . '</td>';
                $html .= '<td>' . esc_html($conversion_rate) . '</td>';
                $html .= '</tr>';
            }
        }
        
        $html .= '</tbody></table>';
        
        // Send the top links HTML
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
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        
        // Get date range parameters
        $date_range = isset($_POST['date_range']) ? sanitize_text_field($_POST['date_range']) : '30';
        $from_date = isset($_POST['from_date']) ? sanitize_text_field($_POST['from_date']) : '';
        $to_date = isset($_POST['to_date']) ? sanitize_text_field($_POST['to_date']) : '';
        
        // Log the received parameters for debugging
        error_log("Top Referrers - Date Range: $date_range, From: $from_date, To: $to_date");
        
        // Determine the date range parameters
        list($start_date, $end_date) = $this->get_date_range_params($date_range, $from_date, $to_date);
        
        // Build the query to get top referrers
        $top_referrers_query = $wpdb->prepare(
            "SELECT 
                CASE 
                    WHEN referrer = '' THEN 'Direct' 
                    ELSE referrer 
                END as referrer_source,
                COUNT(*) as count
            FROM $table_name
            WHERE click_time BETWEEN %s AND %s
            GROUP BY referrer_source
            ORDER BY count DESC
            LIMIT 10",
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        );
        
        // Log the SQL query for debugging
        error_log("Top Referrers SQL: $top_referrers_query");
        
        // Get top referrers
        $results = $wpdb->get_results($top_referrers_query);
        
        // Build HTML table
        $html = '<table class="widefat striped">';
        $html .= '<thead><tr>';
        $html .= '<th>' . __('Referrer', 'wp-link-tracker') . '</th>';
        $html .= '<th>' . __('Clicks', 'wp-link-tracker') . '</th>';
        $html .= '</tr></thead>';
        $html .= '<tbody>';
        
        if (empty($results)) {
            $html .= '<tr><td colspan="2">' . __('No referrer data available. This will populate as your links receive traffic.', 'wp-link-tracker') . '</td></tr>';
        } else {
            foreach ($results as $referrer) {
                $display_referrer = $referrer->referrer_source;
                
                // Extract domain from URL if it's not 'Direct'
                if ($display_referrer !== 'Direct' && filter_var($display_referrer, FILTER_VALIDATE_URL)) {
                    $parsed_url = parse_url($display_referrer);
                    $display_referrer = isset($parsed_url['host']) ? $parsed_url['host'] : $display_referrer;
                }
                
                $html .= '<tr>';
                $html .= '<td>' . esc_html($display_referrer) . '</td>';
                $html .= '<td>' . esc_html($referrer->count) . '</td>';
                $html .= '</tr>';
            }
        }
        
        $html .= '</tbody></table>';
        
        // Send the top referrers HTML
        wp_send_json_success($html);
    }

    /**
     * Get device data via AJAX.
     */
    public function get_device_data_ajax() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_dashboard_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Get date range parameters
        $date_range = isset($_POST['date_range']) ? sanitize_text_field($_POST['date_range']) : '30';
        $from_date = isset($_POST['from_date']) ? sanitize_text_field($_POST['from_date']) : '';
        $to_date = isset($_POST['to_date']) ? sanitize_text_field($_POST['to_date']) : '';
        
        // Log the received parameters for debugging
        error_log("Device Data - Date Range: $date_range, From: $from_date, To: $to_date");
        
        // Get device data from stats class
        $stats = new WP_Link_Tracker_Stats();
        $device_data = $stats->get_global_device_breakdown($date_range, $from_date, $to_date);
        
        // Send the device data
        wp_send_json_success($device_data);
    }

    /**
     * Debug date range via AJAX.
     */
    public function debug_date_range_ajax() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_link_tracker_dashboard_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'wplinktracker_clicks';
        
        // Get date range parameters
        $date_range = isset($_POST['date_range']) ? sanitize_text_field($_POST['date_range']) : '30';
        $from_date = isset($_POST['from_date']) ? sanitize_text_field($_POST['from_date']) : '';
        $to_date = isset($_POST['to_date']) ? sanitize_text_field($_POST['to_date']) : '';
        
        // Determine the date range parameters
        list($start_date, $end_date) = $this->get_date_range_params($date_range, $from_date, $to_date);
        
        // Build the query with date filtering
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE click_time BETWEEN %s AND %s",
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        );
        
        // Get total clicks with date filtering
        $total_clicks = (int) $wpdb->get_var($query);
        
        // Get clicks by date
        $clicks_by_date_query = $wpdb->prepare(
            "SELECT DATE(click_time) as date, COUNT(*) as count 
            FROM $table_name 
            WHERE click_time BETWEEN %s AND %s 
            GROUP BY DATE(click_time) 
            ORDER BY date ASC",
            $start_date . ' 00:00:00',
            $end_date . ' 23:59:59'
        );
        
        $clicks_by_date = $wpdb->get_results($clicks_by_date_query);
        
        // Get all clicks for comparison
        $all_clicks_query = "SELECT DATE(click_time) as date, COUNT(*) as count 
                            FROM $table_name 
                            GROUP BY DATE(click_time) 
                            ORDER BY date ASC";
        
        $all_clicks = $wpdb->get_results($all_clicks_query);
        
        // Send debug information
        wp_send_json_success(array(
            'date_range' => array(
                'type' => $date_range,
                'from_date' => $from_date,
                'to_date' => $to_date,
                'calculated_start' => $start_date,
                'calculated_end' => $end_date
            ),
            'query' => $query,
            'total_clicks' => $total_clicks,
            'clicks_by_date' => $clicks_by_date,
            'all_clicks' => $all_clicks
        ));
    }

    /**
     * Generate a unique short code.
     */
    private function generate_short_code($length = 6) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $short_code = '';
        
        for ($i = 0; $i < $length; $i++) {
            $short_code .= $characters[rand(0, $charactersLength - 1)];
        }
        
        // Check if the code already exists
        $args = array(
            'post_type' => 'wplinktracker',
            'meta_query' => array(
                array(
                    'key' => '_wplinktracker_short_code',
                    'value' => $short_code,
                    'compare' => '='
                )
            )
        );
        
        $query = new WP_Query($args);
        
        // If the code exists, generate a new one
        if ($query->have_posts()) {
            return $this->generate_short_code($length);
        }
        
        return $short_code;
    }
    
    /**
     * Get the device type from user agent.
     */
    private function get_device_type($user_agent) {
        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $user_agent)) {
            return 'Tablet';
        }
        
        if (preg_match('/Mobile|Android|iP(hone|od)|IEMobile|BlackBerry|Kindle|Silk-Accelerated|(hpw|web)OS|Opera M(obi|ini)/', $user_agent)) {
            return 'Mobile';
        }
        
        return 'Desktop';
    }

    /**
     * Get the browser from user agent.
     */
    private function get_browser($user_agent) {
        if (preg_match('/MSIE/i', $user_agent) || preg_match('/Trident/i', $user_agent)) {
            return 'Internet Explorer';
        } elseif (preg_match('/Firefox/i', $user_agent)) {
            return 'Firefox';
        } elseif (preg_match('/Chrome/i', $user_agent)) {
            if (preg_match('/Edge/i', $user_agent)) {
                return 'Edge';
            } elseif (preg_match('/Edg/i', $user_agent)) {
                return 'Edge';
            } elseif (preg_match('/OPR/i', $user_agent)) {
                return 'Opera';
            } else {
                return 'Chrome';
            }
        } elseif (preg_match('/Safari/i', $user_agent)) {
            return 'Safari';
        } elseif (preg_match('/Opera/i', $user_agent)) {
            return 'Opera';
        } else {
            return 'Unknown';
        }
    }

    /**
     * Get the operating system from user agent.
     */
    private function get_operating_system($user_agent) {
        if (preg_match('/windows|win32|win64/i', $user_agent)) {
            return 'Windows';
        } elseif (preg_match('/macintosh|mac os x/i', $user_agent)) {
            return 'Mac OS';
        } elseif (preg_match('/linux/i', $user_agent)) {
            return 'Linux';
        } elseif (preg_match('/android/i', $user_agent)) {
            return 'Android';
        } elseif (preg_match('/iphone|ipad|ipod/i', $user_agent)) {
            return 'iOS';
        } else {
            return 'Unknown';
        }
    }

    /**
     * Get date range parameters based on input.
     * Returns array with start_date and end_date.
     */
    private function get_date_range_params($date_range, $from_date, $to_date) {
        // Get current date in WordPress timezone
        $current_date = current_time('Y-m-d');
        $end_date = $current_date;
        
        if ($date_range === 'custom' && !empty($from_date) && !empty($to_date)) {
            $start_date = $from_date;
            $end_date = $to_date;
            
            // Log custom date range
            error_log("Custom date range: From $from_date to $to_date");
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
        
        return array($start_date, $end_date);
    }
}
