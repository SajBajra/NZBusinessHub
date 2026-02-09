=== GeoDirectory WP All Import ===
Contributors: stiofansisland, paoltaia, ayecode
Donate link: https://wpgeodirectory.com
Tags: geodirectory, geodirectory import, import csv, import xml, wordpress csv import, wordpress xml import, migrate, bulk csv import, bulk xml import, wp all import
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 5.6
Stable tag: 2.3.9
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Use the power of WP All Import to import your listings from anywhere with this add-on that integrates WP All Import with GeoDirectory.

== Description ==

Use the power of WP All Import to import your listings from anywhere with this add-on that integrates WP All Import with GeoDirectory.

The plugin has been tested by the WP All Import developers and they approved it.

== Requirements ==
* GeoDirectory 2.3 or greater - https://wordpress.org/plugins/geodirectory/
* WP All Import 3.7.0 or greater - https://wordpress.org/plugins/wp-all-import/

== Installation ==

1. Upload 'geodir-wp-all-import' directory to the '/wp-content/plugins/' directory
2. Activate the plugin "GeoDirectory WP All Import" through the 'Plugins' menu in WordPress

== Changelog ==

= 2.3.9 - 2025-10-09 =
* WP All Import always uses default post type - FIXED

= 2.3.8 - 2025-06-03 =
* It shows always default gd_place fields with new import for WP All Import Pro - FIXED

= 2.3.7 - 2025-05-29 =
* Sanitize address latitude/longitude values - FIXED

= 2.3.6 - 2025-05-20 =
* Allow importing images with url .googleusercontent.com - CHANGED

= 2.3.5 - 2024-11-26 =
* Import listing broken when location manager don't found - FIXED

= 2.3.4 - 2024-11-21 =
* Neighbourhood value not set after import listing - FIXED

= 2.3.3 - 2024-11-14 =
* PHP 8 compatibility changes - CHANGED

= 2.3.2 - 2024-06-24 =
* Allow upload external images which don't have image extension - CHANGED

= 2.3.1 - 2024-06-07 =
* Merge missing category and country name - FIXED

= 2.3 - 2023-08-31 =
* Import data broken when GPS address query is failed - FIXED

= 2.2.2 - 2023-06-01 =
* Auto collect GPS location if they are missing during import - FIXED

= 2.2.1 - 2023-04-19 =
* Missing import id for cron import - FIXED

= 2.2.0 - 2022-06-13 =
* Link posts support added for the mapping - ADDED
* Supports added for recurring events fields - ADDED

= 2.1.0.1 =
* Breaks CPT custom fields settings sections - FIXED

= 2.1.0.0 =
* Update via import updates all the data rather than selected fields - FIXED

= 2.0.0.6-beta =
* PHP notice in importing address fields - FIXED
* Logo file import is not working - FIXED

= 2.0.0.5-beta =
* Import business hours is not working - FIXED

= 2.0.0.4-beta =
* Errors on install - FIXED

= 2.0.0.3-beta =
* Improvements and bug fixes
* Duplicate locations are being imported - FIXED
* File upload fields not added to attachments table - FIXED

= 2.0.0.2-beta =
* Import categories that have names - ADDED
* Call to undefined function is_intval - FIXED

= 2.0.0.1-beta =
* Listing locations are not imported - ADDED
* Unable to detect WP All Import Pro - Fixed

= 2.0.0.0-beta =
* Initial release