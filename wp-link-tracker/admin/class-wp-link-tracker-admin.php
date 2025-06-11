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
        
        // Enqueue Chart.js
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js', array(), '3.7.1', true);
        
        // Enqueue our admin script
        wp_enqueue_script(
            'wp-link-tracker-admin',
            WP_LINK_TRACKER_PLUGIN_URL . 'admin/js/wp-link-tracker-admin.js',
            array('jquery', 'chartjs'),
            WP_LINK_TRACKER_VERSION,
            true
        );
        
        // Enqueue our admin styles
        wp_enqueue_style(
            'wp-link-tracker-admin',
            WP_LINK_TRACKER_PLUGIN_URL . 'admin/css/wp-link-tracker-admin.css',
            array(),
            WP_LINK_TRACKER_VERSION
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
     * Render the dashboard page.
     */
    public function render_dashboard_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="wplinktracker-dashboard">
                <div class="wplinktracker-dashboard-header">
                    <div class="wplinktracker-date-range">
                        <label for="wplinktracker-date-range-select"><?php _e('Date Range:', 'wp-link-tracker'); ?></label>
                        <select id="wplinktracker-date-range-select">
                            <option value="7"><?php _e('Last 7 Days', 'wp-link-tracker'); ?></option>
                            <option value="30" selected><?php _e('Last 30 Days', 'wp-link-tracker'); ?></option>
                            <option value="90"><?php _e('Last 90 Days', 'wp-link-tracker'); ?></option>
                            <option value="365"><?php _e('Last Year', 'wp-link-tracker'); ?></option>
                            <option value="custom"><?php _e('Custom Range', 'wp-link-tracker'); ?></option>
                        </select>
                        
                        <div id="wplinktracker-custom-date-range" style="display: none;">
                            <input type="date" id="wplinktracker-date-from" />
                            <span>to</span>
                            <input type="date" id="wplinktracker-date-to" />
                            <button type="button" class="button" id="wplinktracker-apply-date-range"><?php _e('Apply', 'wp-link-tracker'); ?></button>
                        </div>
                    </div>
                    
                    <div class="wplinktracker-refresh">
                        <button type="button" class="button" id="wplinktracker-refresh-dashboard">
                            <span class="dashicons dashicons-update"></span> <?php _e('Refresh', 'wp-link-tracker'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="wplinktracker-dashboard-summary">
                    <div class="wplinktracker-summary-box">
                        <h3><?php _e('Total Clicks', 'wp-link-tracker'); ?></h3>
                        <div class="wplinktracker-summary-value" id="wplinktracker-total-clicks">0</div>
                    </div>
                    
                    <div class="wplinktracker-summary-box">
                        <h3><?php _e('Unique Visitors', 'wp-link-tracker'); ?></h3>
                        <div class="wplinktracker-summary-value" id="wplinktracker-unique-visitors">0</div>
                    </div>
                    
                    <div class="wplinktracker-summary-box">
                        <h3><?php _e('Active Links', 'wp-link-tracker'); ?></h3>
                        <div class="wplinktracker-summary-value" id="wplinktracker-active-links">0</div>
                    </div>
                    
                    <div class="wplinktracker-summary-box">
                        <h3><?php _e('Avg. Conversion Rate', 'wp-link-tracker'); ?></h3>
                        <div class="wplinktracker-summary-value" id="wplinktracker-avg-conversion">0%</div>
                    </div>
                </div>
                
                <div class="wplinktracker-dashboard-charts">
                    <div class="wplinktracker-chart-container">
                        <h3><?php _e('Clicks Over Time', 'wp-link-tracker'); ?></h3>
                        <div style="height: 300px; overflow: hidden;">
                            <canvas id="wplinktracker-clicks-chart"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="wplinktracker-dashboard-tables">
                    <div class="wplinktracker-table-container">
                        <h3><?php _e('Top Performing Links', 'wp-link-tracker'); ?></h3>
                        <div id="wplinktracker-top-links-table" class="wplinktracker-table-content">
                            <p><?php _e('Loading data...', 'wp-link-tracker'); ?></p>
                        </div>
                    </div>
                    
                    <div class="wplinktracker-table-container">
                        <h3><?php _e('Top Referrers', 'wp-link-tracker'); ?></h3>
                        <div id="wplinktracker-top-referrers-table" class="wplinktracker-table-content">
                            <p><?php _e('Loading data...', 'wp-link-tracker'); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="wplinktracker-dashboard-devices">
                    <div class="wplinktracker-chart-container">
                        <h3><?php _e('Device Types', 'wp-link-tracker'); ?></h3>
                        <div style="height: 300px; overflow: hidden;">
                            <canvas id="wplinktracker-devices-chart"></canvas>
                        </div>
                    </div>
                    
                    <div class="wplinktracker-chart-container">
                        <h3><?php _e('Browsers', 'wp-link-tracker'); ?></h3>
                        <div style="height: 300px; overflow: hidden;">
                            <canvas id="wplinktracker-browsers-chart"></canvas>
                        </div>
                    </div>
                    
                    <div class="wplinktracker-chart-container">
                        <h3><?php _e('Operating Systems', 'wp-link-tracker'); ?></h3>
                        <div style="height: 300px; overflow: hidden;">
                            <canvas id="wplinktracker-os-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
            .wplinktracker-dashboard {
                margin-top: 20px;
            }
            .wplinktracker-dashboard-header {
                display: flex;
                justify-content: space-between;
                margin-bottom: 20px;
            }
            .wplinktracker-dashboard-summary {
                display: flex;
                justify-content: space-between;
                margin-bottom: 30px;
            }
            .wplinktracker-summary-box {
                flex: 1;
                margin-right: 15px;
                padding: 20px;
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 4px;
                text-align: center;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .wplinktracker-summary-box:last-child {
                margin-right: 0;
            }
            .wplinktracker-summary-box h3 {
                margin-top: 0;
                color: #23282d;
            }
            .wplinktracker-summary-value {
                font-size: 28px;
                font-weight: bold;
                color: #0073aa;
            }
            .wplinktracker-dashboard-charts,
            .wplinktracker-dashboard-tables,
            .wplinktracker-dashboard-devices {
                display: flex;
                margin-bottom: 30px;
            }
            .wplinktracker-chart-container,
            .wplinktracker-table-container {
                flex: 1;
                margin-right: 15px;
                padding: 20px;
                background: #fff;
                border: 1px solid #ddd;
                border-radius: 4px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .wplinktracker-chart-container:last-child,
            .wplinktracker-table-container:last-child {
                margin-right: 0;
            }
            .wplinktracker-chart-container h3,
            .wplinktracker-table-container h3 {
                margin-top: 0;
                color: #23282d;
            }
            #wplinktracker-custom-date-range {
                margin-top: 10px;
            }
            /* Table content scrolling */
            .wplinktracker-table-content {
                max-height: 300px;
                overflow-y: auto;
            }
            @media (max-width: 782px) {
                .wplinktracker-dashboard-summary,
                .wplinktracker-dashboard-charts,
                .wplinktracker-dashboard-tables,
                .wplinktracker-dashboard-devices {
                    flex-direction: column;
                }
                .wplinktracker-summary-box,
                .wplinktracker-chart-container,
                .wplinktracker-table-container {
                    margin-right: 0;
                    margin-bottom: 15px;
                }
            }
        </style>
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
}
