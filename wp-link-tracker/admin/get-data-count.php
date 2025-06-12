<?php
/**
 * Data Count Page
 * 
 * This page displays the actual count of records in the database for debugging purposes.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get database data
global $wpdb;
$table_name = $wpdb->prefix . 'wplinktracker_clicks';

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

// Get total clicks
$total_clicks = 0;
if ($table_exists) {
    $total_clicks = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
}

// Get unique visitors
$unique_visitors = 0;
if ($table_exists) {
    $unique_visitors = $wpdb->get_var("SELECT COUNT(DISTINCT visitor_id) FROM $table_name");
}

// Get links with clicks
$links_with_clicks = 0;
if ($table_exists) {
    $links_with_clicks = $wpdb->get_var("SELECT COUNT(DISTINCT post_id) FROM $table_name");
}

// Get total links
$total_links = wp_count_posts('wplinktracker');
$total_links = $total_links->publish;

// Get date range of data
$earliest_date = '';
$latest_date = '';
if ($table_exists && $total_clicks > 0) {
    $earliest_date = $wpdb->get_var("SELECT MIN(click_time) FROM $table_name");
    $latest_date = $wpdb->get_var("SELECT MAX(click_time) FROM $table_name");
}

// Get clicks by date
$clicks_by_date = array();
if ($table_exists && $total_clicks > 0) {
    $clicks_by_date_results = $wpdb->get_results("
        SELECT DATE(click_time) as date, COUNT(*) as count
        FROM $table_name
        GROUP BY DATE(click_time)
        ORDER BY date ASC
    ");
    
    foreach ($clicks_by_date_results as $row) {
        $clicks_by_date[$row->date] = $row->count;
    }
}

// Get clicks by link
$clicks_by_link = array();
if ($table_exists && $total_clicks > 0) {
    $clicks_by_link_results = $wpdb->get_results("
        SELECT p.ID, p.post_title, COUNT(*) as count
        FROM $table_name c
        JOIN {$wpdb->posts} p ON c.post_id = p.ID
        GROUP BY p.ID
        ORDER BY count DESC
    ");
}

?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="notice notice-info">
        <p><?php _e('This page shows the actual count of records in the database for debugging purposes.', 'wp-link-tracker'); ?></p>
    </div>
    
    <h2><?php _e('Database Status', 'wp-link-tracker'); ?></h2>
    <table class="widefat striped">
        <tr>
            <th><?php _e('Table Exists', 'wp-link-tracker'); ?></th>
            <td><?php echo $table_exists ? '<span style="color:green;">✓ Yes</span>' : '<span style="color:red;">✗ No</span>'; ?></td>
        </tr>
        <tr>
            <th><?php _e('Total Clicks', 'wp-link-tracker'); ?></th>
            <td><?php echo esc_html($total_clicks); ?></td>
        </tr>
        <tr>
            <th><?php _e('Unique Visitors', 'wp-link-tracker'); ?></th>
            <td><?php echo esc_html($unique_visitors); ?></td>
        </tr>
        <tr>
            <th><?php _e('Links with Clicks', 'wp-link-tracker'); ?></th>
            <td><?php echo esc_html($links_with_clicks); ?> / <?php echo esc_html($total_links); ?></td>
        </tr>
        <tr>
            <th><?php _e('Date Range', 'wp-link-tracker'); ?></th>
            <td>
                <?php if (!empty($earliest_date) && !empty($latest_date)): ?>
                    <?php echo date('F j, Y', strtotime($earliest_date)); ?> - <?php echo date('F j, Y', strtotime($latest_date)); ?>
                <?php else: ?>
                    <?php _e('No data', 'wp-link-tracker'); ?>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    
    <?php if (!empty($clicks_by_date)): ?>
        <h2><?php _e('Clicks by Date', 'wp-link-tracker'); ?></h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php _e('Date', 'wp-link-tracker'); ?></th>
                    <th><?php _e('Clicks', 'wp-link-tracker'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clicks_by_date as $date => $count): ?>
                    <tr>
                        <td><?php echo date('F j, Y', strtotime($date)); ?></td>
                        <td><?php echo esc_html($count); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <?php if (!empty($clicks_by_link)): ?>
        <h2><?php _e('Clicks by Link', 'wp-link-tracker'); ?></h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php _e('Link', 'wp-link-tracker'); ?></th>
                    <th><?php _e('Clicks', 'wp-link-tracker'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clicks_by_link_results as $link): ?>
                    <tr>
                        <td>
                            <a href="<?php echo admin_url('post.php?post=' . $link->ID . '&action=edit'); ?>">
                                <?php echo esc_html($link->post_title); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($link->count); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    
    <h2><?php _e('Database Queries', 'wp-link-tracker'); ?></h2>
    <div class="wplinktracker-queries">
        <h3><?php _e('Total Clicks Query', 'wp-link-tracker'); ?></h3>
        <pre>SELECT COUNT(*) FROM <?php echo $table_name; ?></pre>
        
        <h3><?php _e('Unique Visitors Query', 'wp-link-tracker'); ?></h3>
        <pre>SELECT COUNT(DISTINCT visitor_id) FROM <?php echo $table_name; ?></pre>
        
        <h3><?php _e('Links with Clicks Query', 'wp-link-tracker'); ?></h3>
        <pre>SELECT COUNT(DISTINCT post_id) FROM <?php echo $table_name; ?></pre>
        
        <h3><?php _e('Date Range Query', 'wp-link-tracker'); ?></h3>
        <pre>SELECT MIN(click_time), MAX(click_time) FROM <?php echo $table_name; ?></pre>
    </div>
    
    <style>
        .wplinktracker-queries {
            margin-top: 20px;
        }
        .wplinktracker-queries pre {
            background: #f5f5f5;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</div>
