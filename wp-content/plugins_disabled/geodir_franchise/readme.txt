=== GeoDirectory Franchise Manager ===
Contributors: stiofansisland, paoltaia, ayecode
Donate link: https://wpgeodirectory.com
Tags: business chain, chains of business, franchise, franchise manager, geodir franchise
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 5.6
Stable tag: 2.3.8
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

The Franchise Manager allows users to submit listings for chains of businesses or franchises faster and allows directory owners to monetize those listings in a smarter way.

== Description ==

The Franchise Manager allows users to submit listings for chains of businesses or franchises faster and allows directory owners to monetize those listings in a smarter way.

After entering the main listing for a new Chain, fields of the Add Listing form can be locked. This way you can pre-populate most fields but allow the address, telephone number and email address to be entered separately for all branches of the chain. When the main listing is edited, locked fields are edited for all branches too.

== Installation ==

1. Upload 'geodir_franchise' directory to the '/wp-content/plugins/' directory
2. Activate the plugin "GeoDirectory Franchise Manager" through the 'Plugins' menu in WordPress
3. Go to WordPress Admin -> GeoDirectory -> Settings -> Franchise Manager and customize behaviour as needed
4. For detailed setup instructions, visit the official [Documentation](https://wpgeodirectory.com/docs/franchise-manager-add-on/?utm_source=docs&utm_medium=installation_tab&utm_content=documentation&utm_campaign=readme) page.

== Changelog =

= 2.3.8 - 2025-04-10 =
* Locked file field value not merged from main listing on franchise listing saved - FIXED

= 2.3.7 - 2025-03-13 =
* PHP notice on GetPaid checkout form after update - FIXED

= 2.3.6 - 2025-03-13 =
* GD Map option added to filter results by franchises - ADDED

= 2.3.5 - 2024-11-21 =
* Free listing with paid franchise feature is not working - FIXED

= 2.3.4 - 2024-05-03 =
* Sometimes shows PHP notice when no locked fields saved for post - FIXED

= 2.3.3- 2023-10-05 =
* PHP undefined index notice - FIXED

= 2.3.2 - 2023-08-31 =
* Restructure caching functionality - CHANGED

= 2.3.1 - 2023-08-10 =
* Use pretty permalinks for add franchise link - FIXED

= 2.3 - 2023-03-16 =
* Load add listing JS in footer in backend add listing page - FIXED
* Changes for AUI Bootstrap 5 compatibility - ADDED

= 2.2.2 (2022-10-06) =
* Load add listing JS in footer - FIXED

= 2.2.1 (2022-08-09) =
* Franchise product created for WooCommerce looses subscription data - FIXED

= 2.2 (2022-02-22) =
* Changes to support GeoDirectory v2.2 new settings UI - CHANGED

= 2.1.1.0 =
* Allow to lock post_title field from locked fields - ADDED
* Action added in backend edit listing page to add new franchise - ADDED
* Allow to customize the franchise labels for different CPT - CHANGED

= 2.1.0.5 =
* Franchise Of filter shows error on when Elementor template page - FIXED
* Allow to lock comments from locked fields - ADDED

= 2.1.0.4 =
* Franchise cost not applied to GetPaid invoice on add franchise - FIXED

= 2.1.0.3 =
* Upgrade main listing don't updates package & expire dates of franchises - FIXED
* Franchises are not auto claimed on claim main listing - FIXED

= 2.1.0.2 =
* It removes categories from franchises when main listing is saved from backend - FIXED

= 2.1.0.1 
* Skip invoices for free franchise - CHANGED

= 2.1.0.0 =
* Changes for AyeCode UI compatibility - CHANGED

= 2.0.0.9 =
* Default value setting is not working for franchise field - FIXED

= 2.0.0.8 =
* Allow to setup separate add franchise page for each CPT - CHANGED

= 2.0.0.7 =
* Link posts changes related to locked fields - CHANGED

= 2.0.0.6 =
* Disable franchise via package does not hides "Lock franchise fields" field - FIXED
* Locked fields options shows field enabled for admin use only - FIXED
* Franchise permalink is not working when category is locked - FIXED

= 2.0.0.5 =
* Category field not updated for franchise on parent post updated - FIXED
* Save main listing creates duplicate media for franchise listings - FIXED
* Locking editor field breaks editor tools on other html fields FIXED

= 2.0.0.4 =
* Minor PHP notice fix - FIXED

= 2.0.0.3 =
* ​Some strings are not translatable - FIXED​
* Problem in saving locked field for non-admin users - FIXED

= 2.0.0.2 =
* Disable location filter for franchises tab by default - CHANGED

= 2.0.0.1 =
* No issues reported in beta, releasing non beta version - INFO

= 2.0.0.0-beta =

== Upgrade Notice ==

= 2.0.0.0-beta =
* 2.0.0.0-beta is a major update. Make a full site backup, update your theme and extensions before upgrading.