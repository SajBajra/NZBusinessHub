=== GetPaid Advertising ===
Contributors: stiofansisland, paoltaia, ayecode
Donate link: https://wpgetpaid.com
Tags: ads, ad manager, adsense, advertising, advert, banner, campaign, package
Requires at least: 5.0
Requires PHP: 5.6
Tested up to: 6.9
Stable tag: 1.2.5
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

GetPaid Advertising allows to manage ads and insert them anywhere on the WordPress website.

== Description ==

The Advertising add-on for GetPaid, allows you to easily manage ads and insert them anywhere on your WordPress website.

It allows you to sell ads (Hosted Ads) or simply display ads from 3rd party networks like Google AdSense and get paid for their impressions on your website.

It is also fully integrated with [GeoDirectory](https://wordpress.org/plugins/geodirectory/) and its [Pricing Manager](https://wpgeodirectory.com/downloads/pricing-manager/) add-on.

== Installation ==

1. Upload 'gpa' directory to the '/wp-content/plugins/' directory
2. Activate the plugin "GetPaid Advertising" through the 'Plugins' menu in WordPress
3. Go to WordPress Admin -> Advertising -> Settings and customize behaviour as needed
4. For detailed setup instructions, visit the official [Documentation](https://wpgetpaid.com/documentation/article/category/advertising/?utm_source=docs&utm_medium=installation_tab&utm_content=documentation&utm_campaign=readme) page.

== Changelog =

= 1.2.5 - 2026-01-12 =
* Frontend dashboard edit ad don't show zone selected - FIXED
* Problem with cropping high-resolution ad image - FIXED

= 1.2.4 - 2025-12-03 =
* Prevent the creation of unnecessary listing ads with a draft status - FIXED

= 1.2.3 - 2025-11-13 =
* Prevent adding more ads to the zone when max ads limit is reached - CHANGED

= 1.2.2 - 2025-06-26 =
* Adds Ajax Ad Zone ads rotation feature to refresh ads without page reload - ADDED

= 1.2.1 - 2025-04-10 =
* Listing ad don't show on CPT + Location page - FIXED

= 1.2.0 - 2025-02-19 =
* Show cpt title instead of cpt in listings dropdown - CHANGED
* GD Post types option added to restrict adding ads for specific post types - ADDED
* Loads incorrect textdomain - FIXED
* Customer data not assigned correctly to the GetPaid invoice - FIXED

= 1.1.3 =
* Some strings are using incorrect textdomain - FIXED
* Pricing type title should show translatable title - FIXED

= 1.1.2 =
* Allow sending emails to admin when an ad is pending review - ADDED

= 1.1.1 =
* Show image preview in admin ad edit - ADDED
* Allow translating zone price description - ADDED

= 1.1.0 =
* Listing select not working on some Elementor setups - FIXED
* Show all listings on the ad edit form if the current user has less than 10 listings - CHANGED
* Frontend listing select should only show ads from the current user - FIXED
* Do not show secondary advertise here links if there are not ads - FIXED
* Reset ad type dropdown when user selects a new zone - CHANGED

= 1.0.17 =
* Show ads by location filter is not working - FIXED
* text is not translatable in zone content - FIXED
* Advertising Target URL bug if link has ampersand "&" - FIXED

= 1.0.16 =
* Fix: Terms filter only works on archive pages.

= 1.0.15 =
* Non-listing ads not filtered by normal location in non-near search.

= 1.0.14 =
* Fix: Users had to reselect the ad type when editing an ad on the frontend.

= 1.0.13 =
* Change: Location filters now work with GD near search (for cities).
* New: Non-listing ads now allow targeting specific locations.

= 1.0.12 =
* Changes for AUI Bootstrap 5 compatibility - ADDED
* Shows warning when archive urls on details page disabled - FIXED
* Hide zone wrapper when no inner content to show in the zone - CHANGED
* Change: Apply term conditional logic to GD search pages too.

= 1.0.11 =
* [ad] text is not translatable in post title - FIXED
* Error shown on zone editor if no packages are selected - FIXED

= 1.0.10 =
* Ajax search listings - CHANGED

= 1.0.9 =
* Use GD template structure for ads - ADDED
* Do not generate an invoice for free ads - CHANGED
* Ads overview table is now configurable - CHANGED

= 1.0.8 =
* Fix translation issue for few strings - ADDED
* New REST API - ADDED
* Do not filter content on elementor pages - CHANGED

= 1.0.7 =
* Ability to create admin only zones - ADDED
* Allow advertisers to specify quantities when advertising - ADDED
* Change name of invoiced item - CHANGED
* Filter to disable UsersWP integration - ADDED
* BuddyBoss theme conflict - FIXED

= 1.0.6 =
* Logged out users asked to re-select a zone after they log in - FIXED.
* Hide zone selector when a user visits the "new ad" page by clicking on the "Advertise Here" link - CHANGED.
* UsersWP integration - ADDED
* Ads dashboard now shows user's ads by default - CHANGED
* Ability to limit the number of active ads allowed per zone - ADDED
* Restrict or allow per location by REGION, not only cities - CHANGED
* Ability to restrict ad types per zone - ADDED

= 1.0.5 =
* Order listing options in alphabetical order - CHANGED.
* Ads not expired after impressions reached - FIXED.
* Zone location restrictions not respected - FIXED.
* Do not display dashboard navigation on the "Add New Ad" page - CHANGED.

= 1.0.4 =
* Ability to link invoices when creating an ad in the backend - ADDED
* GetPaid currency not reflected in new-ad pages - FIXED

= 1.0.3 =
* Kadence theme has a bug making the dashboard links not work - FIXED

= 1.0.2 =
* First public release - RELEASE

= 1.0.0-beta =
* Initial release.