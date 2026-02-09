=== GeoDirectory List Manager ===
Contributors: stiofansisland, paoltaia, ayecode
Donate link: https://wpgeodirectory.com
Tags: geodirectory, geodirectory list, list manager, listing lists, post 2 post
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 5.6
Stable tag: 2.3.9
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Allows users to create and save their own special lists of listings.

== Description ==

= Inspired by the collections section of Yelp =
With List Manager, users can create and save their own special lists of places. For example, "Must try Italian Restaurants", "Places to visit in Scotland" or "Summer Events 2020".

Create as many lists as you want, customize the list title, description, and set it to be public or private. Just like Yelp, users can browse lists set as public. Private lists remain hidden. However, they can still be shared with friends via a unique URL.

= Bookmarks, Itineraries, Collections =
GeoDirectory list manager allows users to create bookmarks, itineraries, collections, and so on. That is to say, there are so many different ways to utilize the add-on depending on your directory needs.

One of our most underrated add-ons, list manager, expands on the "favorites" concept by allowing the creation of any type of favourites list. Moreover, the concept of lists spans across all areas of a directory. As such, with our Custom Post Types add-on, multiple CPTs can be added to the one list.

The add to list button is available as a shortcode, widget, or Gutenberg block. Additionally, the output of the lists is a new page template created with either shortcodes or blocks.

= Post 2 Post plugin is required to create the relationship between posts. =

= Minimum Requirements =

* WordPress 5.0 or later
* GeoDirectory 2.3 or later
* Posts 2 Posts 1.7 or later

== Changelog ==

= 2.3.9 - 2025-07-24 =
* GD > List Loop widget option added to manage archive item template - ADDED

= 2.3.8 - 2025-06-12 =
* UsersWP Dashboard compatibility changes - CHANGED

= 2.3.7 - 2025-05-01 =
* PHP notice on UsersWP Lists tab - FIXED

= 2.3.6 - 2025-04-17 =
* Function _load_textdomain_just_in_time was called incorrectly - FIXED

= 2.3.5 - 2025-01-29 =
* Show edit/delete list option in UWP profile tabs - ADDED

= 2.3.4 - 2025-01-16 =
* Button text not changed on save to list or remove from list - FIXED

= 2.3.3 - 2023-12-12 =
* Breaks single list page if Elementor not found - FIXED

= 2.3.2 - 2023-11-09 =
* List template Elementor compatibility - ADDED

= 2.3.1 - 2023-11-06 =
* Option added to customize list template via page - ADDED

= 2.3 - 2023-03-16 =
* Changes for AUI Bootstrap 5 compatibility - ADDED

= 2.2.1 =
* Options added in Lists settings to customize items with Elementor skin to show list archive page - ADDED

= 2.2 =
* Fix error gd_list_manager_vars.addPopup.close is not a function - FIXED

= 2.1.1.3 =
* Prevent the block/widget class loading when not required - CHANGED

= 2.1.1.2 =
* List save saved as a private even ticked public - FIXED

= 2.1.1.1 =
* List save button not working with bootstrap style - FIXED

= 2.1.1.0 =
* Changes for AyeCode UI compatibility - CHANGED
* Removed profile list tab and added to the UsersWP core - CHANGED

= 2.1.0.5 =
* Post 2 Post can show activate instead of install action - FIXED

= 2.1.0.4 =
* Only show tab if content available. - CHANGED
* Profile page goes to 502 error on some sites - FIXED

= 2.1.0.3 =
* Update save list function to account for new class in post badge output - FIXED

= 2.1.0.2 =
* Language files missing from download package - FIXED
* Unable to translate some strings - FIXED

= 2.1.0.0 =
* Complete overhaul of the code - INFO
* Major Functionality changes and URL changes - CHANGED
* New GD > List Save widget added that can add any listing to a list - ADDED
* Listings no longer have to have been reviewed to be added to a list - ADDED
* Lists can be made non-public (not listed but still viewable via direct link) - ADDED
* Add list page no longer required, adding now done via ajax lightbox - CHANGED
* CPT wording and slug can all be changed to allow full customisation - ADDED
* Post2Post required notice now has links to either install or activate the plugin - ADDED

= 2.0.0.0-beta =
Update and improve code - CHANGED
Solved deactivation hook. - CHANGED
Compatible with GDV2 - CHANGED
Convert to OOPs - CHANGED
Compatible with Buddypress plugin - CHANGED
Compatible with UsersWP plugin - CHANGED
Lists add to author page. -ADDED

= 1.0.3 =
Sometimes drag & drop list not working - FIXED

= 1.0.2 =
Show lists as profile tabs if UsersWP plugin is active - ADDED
GDPR compliance updates - ADDED

= 1.0.1 =
Update script updated to use WP Easy Updates - CHANGED

= 1.0.0 =
Fix issue for plugin installation via WP-CLI - FIXED

= 0.0.5 =
Option added to remove plugin data on plugin delete - ADDED
Max 100 listings can be displayed in a page - CHANGED

= 0.0.4 =
Validating and sanitizing changes - FIXED

= 0.0.3 =
GD update script sometimes not verifying SSL cert - FIXED
New update licence system implemented - CHANGED

= 0.0.2 =
GD auto update script improved efficiency and security(https) - CHANGED
case conversion functions replaced with custom function to support unicode languages - CHANGED

= 0.0.1 =
Initial release