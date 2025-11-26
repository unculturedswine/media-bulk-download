<?php
/**
 * Plugin Name: Media Bulk Download
 * Plugin URI: https://sqrdmedia.com
 * Description: Download original images from WordPress uploads folder by year and month. Find the Tool in the Media menu.
 * Version: 1.0.0
 * Author: Joel Jenkins
 * Author URI: https://joeljenkins.me
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: media-bulk-download
 * Domain Path: /languages
 */

// Prevent direct file access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MBD_PLUGIN_VERSION', '1.0.0');
define('MBD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MBD_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MBD_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once MBD_PLUGIN_PATH . 'includes/class-media-bulk-download.php';
require_once MBD_PLUGIN_PATH . 'includes/class-admin-page.php';
require_once MBD_PLUGIN_PATH . 'includes/class-download-handler.php';

// Initialize the plugin
function mbd_init() {
    $plugin = new Media_Bulk_Download();
    $plugin->init();
}
add_action('plugins_loaded', 'mbd_init');

// Activation hook
register_activation_hook(__FILE__, 'mbd_activate');
function mbd_activate() {
    // Create temp directory for zip files if needed
    $upload_dir = wp_upload_dir();
    $temp_dir = $upload_dir['basedir'] . '/mbd-temp';
    if (!file_exists($temp_dir)) {
        wp_mkdir_p($temp_dir);
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'mbd_deactivate');
function mbd_deactivate() {
    // Clean up temp files
    $upload_dir = wp_upload_dir();
    $temp_dir = $upload_dir['basedir'] . '/mbd-temp';
    if (file_exists($temp_dir)) {
        mbd_cleanup_temp_dir($temp_dir);
    }
}

// Helper function to clean up temp directory
function mbd_cleanup_temp_dir($dir) {
    if (is_dir($dir)) {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                mbd_cleanup_temp_dir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}