=== GeoDirectory Social Importer ===
Contributors: stiofansisland, paoltaia, ayecode
Donate link: https://wpgeodirectory.com
Tags: facebook import, geodir social importer, google my business, gmb import, social importer, tripadvisor import, yelp import
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 5.6
Stable tag: 2.3.8
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Quickly import page information from Facebook, Yelp, TripAdvisor or Google My Business sites just by entering the page URL.

== Description ==

= Import listings from social sites in seconds! =

Using Social Importer, users can quickly import their information from Facebook, Yelp or TripAdvisor just by entering the page URL. As a result, users can add their listings and events in seconds, meaning less abandonment and more $ for you.

= Less setup, less fuss, more listings =

Over the last few years, Facebook and TripAdvisor's APIs have become more and more restrictive and harder to setup. Consequently, this lead to us creating our unique feature to parse the URLs with no need for any API setup.

= What can Social Importer do? =

Just by entering a page URL from either Facebook, Yelp, TripAdvisor or Google My Business you can import all the most essential details straight into the add listing form.

= What Social Importer won't do =

Social Importer cannot create bulk imports. Once you have input your URL, you get a one-time import. There is no systematic update.

== Changelog ==

= 2.3.8 - 2025-07-10 =
* Fix _load_textdomain_just_in_time notice - FIXED

= 2.3.7 - 2025-03-13 =
* Business locations list not updated when business account changed - FIXED

= 2.3.6 - 2025-01-02 =
* Import from TripAdvisor is not working - FIXED

= 2.3.5 - 2024-09-03 =
* Autoload option affects performance on large site - FIXED

= 2.3.4 - 2023-11-11 =
* Label type Top breaks import GMB fields- FIXED

= 2.3.3 - 2023-07-12 =
* Phone value not imported with Yelp import - FIXED

= 2.3.2 - 2023-06-19 =
* PHP deprecated notice "Creation of dynamic property" - FIXED

= 2.3.1 - 2023-05-26 =
* Yelp import don't show correct business hours - FIXED

= 2.3 - 2023-03-16 =
* Changes for AUI Bootstrap 5 compatibility - ADDED

= 2.2.4 - 2022-11-15 =
* Google My Business OAuth 2.0 authorization compatibility changes - CHANGED

= 2.2.3 - 2022-10-06 =
* Set proper title to featured media imported from Yelp & Tripadvisor - CHANGED

= 2.2.2 - 2022-07-14 =
* Deprecated Google My Business API replaced with new API - FIXED

= 2.2.1 - 2022-04-27 =
* Facebook page import now working via 3rd party service - FIXED/CHANGED
* Toast notice shows on import success - ADDED

= 2.2 (2022-02-22) =
* Changes to support GeoDirectory v2.2 new settings UI - CHANGED

= 2.1.1.0 =
* Address coordinates not imported from TripAdvisor - FIXED
* Import from GMB & post to GMB features added for Google My Business - ADDED

= 2.1.0.3 =
* In some cases facebook title and description not pulled in - FIXED

= 2.1.0.2 =
* Change Facebook app scope permissions - CHANGED

= 2.1.0.1 =
* Import from Facebook shows wrong address - FIXED

= 2.1.0.0 =
* Changes for AyeCode UI compatibility - CHANGED
* Lazy Load map feature added - ADDED

= 2.0.1.9 =
* Some timezones adds one day difference in event dates on import FB event - FIXED

= 2.0.1.8 =
* Business Hours not imported properly for multiple time slots on same day - FIXED
* Sometimes import description, phone from Tripadvisor not working - FIXED 

= 2.0.1.7 =
* Check duplicate value for post imported by social importer - FIXED

= 2.0.1.6 =
* Import from Facebook page sometimes does not grabs description - FIXED
* Import not working when url contains cyrillic letters - FIXED
* Fails to import event dates from Facebook Event - FIXED

= 2.0.1.5 =
* Auto save creates multiple attachment entries for featured image - FIXED
* Some language strings are not translatable - FIXED

= 2.0.1.4 =
* Import from TripAdvisor grabs incorrect photos - FIXED

= 2.0.1.3 =
* Post to facebook not working after v2 update - FIXED
* WP Error breaks ajax response during import - FIXED

= 2.0.1.2 =
* It imports low quality images from TripAdvisor - FIXED

= 2.0.1.1 =
* Social importer shows timeout when import from Facebook - FIXED

= 2.0.1.0 =
* Facebook image import tries to use better quality images where it can - FIXED
* Image import system updated to work with new GD core image format - CHANGED
* TripAdvisor images not importing due to TA changes - FIXED

= 2.0.0.11 =
* Default language file not loading for text domain issue - FIXED
* Error on publish free listing on frontend - FIXED

= 2.0.0.10 =
* Depreciated facebook permissions removed from app requirements - FIXED
* Title shows incorrect characters in title for non-English characters - FIXED

= 2.0.0.9 =
Event imports can throw html error - FIXED

= 2.0.0.8 =
Server caching of images can cause old deleted imported yelp images to show in a new yelp import - FIXED

= 2.0.0.7 =
Facebook event end date can be prefixed with a space - FIXED
Facebook events zipcode and lat/lng not populating properly - FIXED
Facebook events dates can be set in wrong format - FIXED

= 2.0.0.6 =
When importing from Yelp add listing form shows deleted images from previous import - FIXED
If import url has space at start or end then nothing is imported - FIXED
Some FB descriptions can have issues with HTML imported - FIXED
Imported images can leave original imported image even if attachment deleted - FIXED

= 2.0.0.5-beta =
JavaScript error can break facebook import - FIXED
Image limit not working with import FB page - FIXED
Import description does not work with advanced editor - FIXED

= 2.0.0.3-beta =
Update and improve code - CHANGED
Resolved Event date blank - FIXED
Solved Facebook video Display issues. - FIXED
Added Facebook page logo in Images. - ADDED
Solved Tripadvisor address issues. FIXED
Solved Tripadvisor import issues. FIXED

= 2.0.0.2-beta =
Solved Import video and images issues - FIXED
Check Facebook event is public and available for import - ADDED
Import Facebook events time - CHANGED

= 2.0.0.1-beta =
Facebook Description from about page - CHANGED
Check PHP XML status on GD->Status page- ADDED
Import Facebook page logo - ADDED
Import Facebook videos - ADDED
Import Events - ADDED

= 2.0.0.0-beta =
Initial beta release

== Upgrade Notice ==