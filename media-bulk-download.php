<?php
/**
 * Plugin Name: Media Bulk Download
 * Plugin URI: https://plugins.joeljenkins.me
 * Description: Bulk download original images from your WordPress uploads folder organized by year and month. Select any time period and download a ZIP of your media files. Perfect for creating backups, migrating content, or archiving media without messy database exports.
 * Version: 1.0.0
 * Author: Kinda Useful Plugins
 * Author URI: https://plugins.joeljenkins.me
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

// Auto-updates from Kinda Useful Plugins
require_once MBD_PLUGIN_PATH . 'includes/class-plugin-updater.php';
new Kinda_Useful_Plugin_Updater(
    'https://plugins.joeljenkins.me',
    __FILE__,
    'media-bulk-download'
);

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