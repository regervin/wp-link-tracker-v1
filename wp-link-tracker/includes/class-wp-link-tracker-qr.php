<?php
/**
 * QR Code Generator for tracked links
 */
class WP_Link_Tracker_QR {
    /**
     * Initialize the class.
     */
    public function init() {
        // Add QR code meta box
        add_action('add_meta_boxes', array($this, 'add_qr_meta_box'));
        
        // Add AJAX handler for QR code generation
        add_action('wp_ajax_wp_link_tracker_generate_qr', array($this, 'generate_qr_ajax'));
        
        // Add QR code column to the links list
        add_filter('manage_wplinktracker_posts_columns', array($this, 'add_qr_column'));
        add_action('manage_wplinktracker_posts_custom_column', array($this, 'qr_column_content'), 10, 2);
    }

    /**
     * Add QR code meta box.
     */
    public function add_qr_meta_box() {
        add_meta_box(
            'wplinktracker_qr_code',
            __('QR Code', 'wp-link-tracker'),
            array($this, 'render_qr_meta_box'),
            'wplinktracker',
            'side',
            'default'
        );
    }

    /**
     * Render the QR code meta box.
     */
    public function render_qr_meta_box($post) {
        $short_code = get_post_meta($post->ID, '_wplinktracker_short_code', true);
        
        if (empty($short_code)) {
            echo '<p>' . __('Save the link first to generate a QR code.', 'wp-link-tracker') . '</p>';
            return;
        }