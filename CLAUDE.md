# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a WordPress plugin called "Media Bulk Download" that allows users to download original images from the WordPress uploads folder organized by year and month. The plugin adds functionality to the WordPress Media Library.

## Architecture

### Core Components

1. **Main Plugin File** (`media-bulk-download.php`): Entry point that initializes the plugin, handles activation/deactivation hooks
2. **Media_Bulk_Download Class** (`includes/class-media-bulk-download.php`): Main plugin orchestrator that sets up hooks, enqueues assets, and coordinates components
3. **MBD_Admin_Page Class** (`includes/class-admin-page.php`): Handles the admin interface and AJAX endpoints for year/month selection
4. **MBD_Download_Handler Class** (`includes/class-download-handler.php`): Manages the creation and download of ZIP files containing selected media

### Key Features

- Admin page accessible via Media menu for bulk downloading images by year/month
- Media Library integration with "Download Month" quick action on individual images
- AJAX-based interface for selecting year/month and initiating downloads
- Temporary file management in `wp-content/uploads/mbd-temp/`

## Development Commands

Since this is a WordPress plugin without build tools:

```bash
# No build/compile steps required - this is a traditional WordPress plugin

# To test the plugin:
# 1. Copy the plugin folder to wp-content/plugins/
# 2. Activate via WordPress admin dashboard

# Check PHP syntax (if PHP CLI is available):
find . -name "*.php" -exec php -l {} \;
```

## WordPress Coding Standards

Follow WordPress PHP coding standards when making modifications:
- Use tabs for indentation
- Prefix all global functions with `mbd_`
- Use WordPress hooks and APIs (e.g., `wp_ajax_*`, `wp_enqueue_script`)
- Escape output with appropriate functions (`esc_url()`, `esc_attr()`, `esc_html()`)
- Verify nonces for security on all AJAX requests

## Plugin Constants

- `MBD_PLUGIN_VERSION`: Plugin version (1.0.0)
- `MBD_PLUGIN_URL`: URL to plugin directory
- `MBD_PLUGIN_PATH`: File system path to plugin directory
- `MBD_PLUGIN_BASENAME`: Plugin basename for hooks

## AJAX Actions

The plugin registers these WordPress AJAX actions:
- `mbd_get_months`: Get available months for a selected year
- `mbd_get_image_count`: Get count of images for selected year/month
- `mbd_download_images`: Create and download ZIP file of images