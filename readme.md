# Site Index

Bulk download original images from your WordPress uploads folder organized by year and month. Select any time period and download a ZIP of your media files. Perfect for creating backups, migrating content, or archiving media without messy database exports.

---

=== Media Bulk Download ===
Contributors: Kinda Useful Plugins
Tags: media, download, backup, images, bulk
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Download original images from WordPress uploads folder by year and month.

== Description ==

Media Bulk Download allows you to quickly download all original images from your WordPress uploads folder, organized by year and month. Perfect for backups, migrations, or archiving media files.

Features:

* Browse uploads by year and month
* Download all images from a specific month as a ZIP file
* Quick action in Media Library to download individual months
* Downloads original files, not resized versions
* Clean admin interface integrated with WordPress Media menu
* Automatic cleanup of temporary files

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Media > Bulk Download
4. Select a year and month to download

== Frequently Asked Questions ==

= Does this download all image sizes? =

No, this downloads only the original uploaded images, not the resized versions WordPress generates.

= Where are the ZIP files stored? =

Temporary ZIP files are created in wp-content/uploads/mbd-temp/ and cleaned up automatically.

= Is there a size limit? =

The plugin handles large downloads, but your server's PHP memory and execution time limits may affect very large months.

== Changelog ==

= 1.0.0 =
* Initial release

== License ==

This plugin is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or (at your option) any later version.

This plugin is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
