<?php
/**
 * Plugin Name: WP Link Tracker
 * Plugin URI: https://example.com/wp-link-tracker
 * Description: A powerful link shortener and tracker for WordPress, similar to ClickMagic.
 * Version: 1.0.0
 * Author: xBesh
 * Author URI: https://example.com
 * Text Domain: wp-link-tracker
 * Domain Path: /languages
 * License: GPL v2 or later
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WP_LINK_TRACKER_VERSION', '1.0.0');
define('WP_LINK_TRACKER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_LINK_TRACKER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files - Check if files exist before requiring them
$required_files = array(
    'includes/class-wp-link-tracker.php',
    'includes/class-wp-link-tracker-post-type.php',
    'includes/class-wp-link-tracker-shortcode.php',
    'includes/class-wp-link-tracker-redirect.php',
    'includes/class-wp-link-tracker-stats.php',
    'admin/class-wp-link-tracker-admin.php'
);

$missing_files = array();
foreach ($required_files as $file) {
    $file_path = WP_LINK_TRACKER_PLUGIN_DIR . $file;
    if (file_exists($file_path)) {
        require_once $file_path;
    } else {
        $missing_files[] = $file;
    }
}

// If any files are missing, show admin notice and exit
if (!empty($missing_files)) {
    add_action('admin_notices', function() use ($missing_files) {
        ?>
        <div class="notice notice-error">
            <p><strong>WP Link Tracker Error:</strong> The following required files are missing:</p>
            <ul>
                <?php foreach ($missing_files as $file): ?>
                    <li><?php echo esc_html($file); ?></li>
                <?php endforeach; ?>
            </ul>
            <p>Please reinstall the plugin or contact support.</p>
        </div>
        <?php
    });
    return; // Stop plugin execution
}

// Initialize the plugin
function wp_link_tracker_init() {
    $plugin = new WP_Link_Tracker();
    $plugin->run();
}
wp_link_tracker_init();

// Activation hook
register_activation_hook(__FILE__, 'wp_link_tracker_activate');
function wp_link_tracker_activate() {
    // Create database tables
    if (file_exists(WP_LINK_TRACKER_PLUGIN_DIR . 'includes/class-wp-link-tracker-activator.php')) {
        require_once WP_LINK_TRACKER_PLUGIN_DIR . 'includes/class-wp-link-tracker-activator.php';
        WP_Link_Tracker_Activator::activate();
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'wp_link_tracker_deactivate');
function wp_link_tracker_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}
