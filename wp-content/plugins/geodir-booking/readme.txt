=== GeoDirectory Booking Engine ===
Contributors: stiofansisland, paoltaia, ayecode
Donate link: https://wpgeodirectory.com
Tags: booking, geodirectory, geodirectory booking
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.2
Stable tag: 2.1.12
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Booking Marketplace with commissions.

== Description ==

== Create a vacation rental marketplace similar to Airbnb, VRBO, or Booking.com with our Booking Marketplace ==
Are you looking for a powerful, feature-packed booking marketplace plugin to transform your Directory? Look no further! GeoDirectory's WordPress Booking Marketplace Plugin offers a seamless booking experience that allows property owners to sell their bookings and lets you earn commissions effortlessly. Take full control of your marketplace with our easy-to-use and highly customizable plugin!

== Top Features: ==

* **Earn as you facilitate bookings:** Take a commission on every booking made through your marketplace and even add an additional service fee to boost your revenue.
* **Flexible payment options:** Decide whether to collect full payment, a deposit, or no payment at all at the time of booking.
* **Multiple cancellation policies:** Offer a variety of cancellation policies that accommodation owners can select from, ensuring a fair and transparent booking experience for both parties.
* **Secure payments with GetPaid:** Exclusively use our reliable GetPaid plugin to collect and process all payments, ensuring a safe and secure transaction every time.
* **Effortless withdrawals for owners:** Payments to property owners are stored in their GetPaid wallet, allowing them to request a withdrawal at any time.
* **Smart booking management:** Set the time limit for pending bookings without payment before they're automatically canceled.
* **Customizable email notifications:** Tailor all email communication to match your brand and provide the necessary information to both property owners and guests.
* **Individual room bookings:** Manage multiple rooms from one listing, with individual pricing and availability!

== Property Owners' Booking Functionality: ==

* **Flexible pricing:** Set a standard nightly price or create custom prices for individual days to optimize revenue.
* **Attract more bookings with discounts:** Offer incentives such as longer stay discounts, last-minute deals, and early bird specials to encourage more bookings.
* **Control your booking rules:** Set minimum and maximum stay lengths and restrict check-in and check-out days as needed.
* **Block off dates:** Easily block specific dates to prevent bookings during times when the property is unavailable.
* **Rooms:** Manage multiple rooms from one listing, all with their own pricing and availability.

== Why choose GeoDirectory's WordPress Booking Marketplace Plugin? ==
Our plugin provides all the essential tools and functionality to create a thriving booking marketplace that's easy to manage, ensuring a seamless experience for both property owners and guests. Don't miss out on this opportunity to enhance your WordPress website with the ultimate booking marketplace plugin. Purchase GeoDirectory's WordPress Booking Marketplace Plugin today and revolutionize the way you do business online!

== Changelog ==

= 2.1.12 - 2026-01-01 =
* Booking confirmed/rejected by author don't triggers confirmed/rejected email to customer - FIXED

= 2.1.11 - 2025-09-11 =
* Email should be sent in user language when WPML is active - FIXED

= 2.1.10 - 2025-09-04 =
* Translation for string with %s can cause JS error - FIXED

= 2.1.9 - 2025-08-28 =
* Booking calendar shows booked slots as available when switched WPML language - FIXED

= 2.1.8 - 2025-08-14 =
* Property Guests field in search is not translatable with WPML - FIXED

= 2.1.7 - 2025-08-07 =
* Sync Calendars > Download calendar shows error in backend - FIXED
* Added email args parameter to make distinct from appointment emails - CHANGED

= 2.1.6 - 2025-07-31 =
* Booking Calendar is not fully visible on small screens - FIXED
* Make owner & customer bookings list responsive on small screens - CHANGED

= 2.1.5 - 2025-06-26 =
* Option added to setup nightly price with 0 amount - ADDED

= 2.1.4 - 2025-06-03 =
* Added email button to the view booking modal alongside SMS and Call option - ADDED
* View Customer booking modal not working on AJAX loaded content - FIXED

= 2.1.3 - 2025-05-08 =
* Calendar shows 1st day of the year available even booking done on that day - FIXED

= 2.1.2 - 2025-04-03 =
* External calendars auto synchronization executes only few items - FIXED

= 2.1.1 - 2025-03-27 =
* External calendars synchronization shows error - FIXED

= 2.1.0 - 2025-01-02 =
* PHP notice on email preview - FIXED
* Minimum and Maximum stay restrictions now prevent selection of unpermitted dates. - FIXED.
* No Check-in/No Check-out day restrictions properly validate and grey out restricted days - FIXED.
* Early bird and last-minute discounts are now mutually exclusive - FIXED (e.g., early bird + long stay) but not both early bird and last-minute discounts - FIXED.
* Discount logic updated to apply the highest applicable discount for long-term stays (e.g., weekly, biweekly, monthly) and to correctly combine up to two discounts, such as early bird and long stay - CHANGED.
* Pets field now allows numeric input with maximum limit instead of checkbox - CHANGED
* PHP notice on email preview - FIXED
* Hide listings from bookings setup dropdown which have booking not enabled - FIXED
* Sometimes installer broken due to GetPaid plugin load order - FIXED
* View Booking modal not working on AJAX loaded content - FIXED

= 2.0.16 - 2024-08-27 =
* Ical synchronizer not unblocking dates - FIXED
* Minor bug fixes - FIXED

= 2.0.15 - 2024-08-21 =
* Updated the calendar to show detailed booked and imported bookings - ADDED
* Booking total on search results don't include discounts - FIXED
* Calculations issues with last-minute discounts and booking total discounts. - FIXED
* Extra guests calculation logic - FIXED
* Complete past bookings cron job error - FIXED
* Minor bug fixes - FIXED

= 2.0.14 - 2024-08-12 =
* Fix unknown column gdbooking issue - FIXED
* Booking form fees amount not added to total booking amount - FIXED
* Not able to save cleaning & pet fees in decimal - FIXED
* Pre-saved customer phone with space prevents reserve booking on own listing - FIXED

= 2.0.13 - 2024-07-31 =
* Allow booking owners to filter iCal imported bookings - ADDED
* Ical import skipping bookings with intersecting days - FIXED 

= 2.0.12 - 2024-07-25 =
* Booking calendar conflicts with Elementor calender - FIXED

= 2.0.11 (2024-07-15) =
* Fixed booking form calendar issues caused by Elementor library conflict - FIXED
* Show the applied discount on the booking form - FIXED
* Discounts not applying the way they are expected in some cases - FIXED
* ONE discount type to be applies at one time - CHANGED
* Owner remove discount link not working -FIXED

= 2.0.10 (2024-07-04) =
* Owner selecting multiple dates not working on windows - FIXED
* iCal still importing dates individually due to bug - FIXED

= 2.0.9 (2024-06-27) =
* Allow booking owners to select multiple dates using drag or multi-selection with Ctrl/Command key - ADDED
* Added listing owner option to delete bookings - ADDED
* Added booking request functionality for listing owners to accept or decline reservations (based on `instant book` custom field) - ADDED
* Several user reported small bug fixes - FIXED
* GD searched dates will now be used for single view price calculations - ADDED
* GD search page will now display the nightly price meta field as dynamic and show total booking price for searched dates - ADDED
* ical imports were importer as single bookings - CHANGED
* Booking block now states when using an average nightly price calculation - FIXED
* iCal imports can cause unwanted invoice/booking emails - FIXED

= 2.0.8 (2024-06-17) =
* Hide room listing from GD autocomplete search - FIXED
* Prevent email notifications for rooms listings - FIXED

= 2.0.7 (2024-06-17) =
* Fixed wonky pricing calculation issue - FIXED
* Added admin option to add bookings - ADDED
* Added admin option to edit bookings - ADDED
* Added admin option to delete bookings - ADDED
* Added listing owner option to manually add bookings for their listings - ADDED
* Added listing owner option to edit bookings - ADDED
* Added listing owner option to delete bookings - ADDED
* Minor bug fixes - FIXED

= 2.0.6 (2024-04-03) =
* Updated the booking form layout to a modern design - CHANGED
* Added a booking guests field for adults, children, infants and pets - ADDED
* Enhanced booking form to include extra charges for extra guests fee, cleaning fee and pets fee - ADDED
* Added iCal export and import for booking sync with external platforms - ADDED
* Minor bug fixes - FIXED

= 2.0.5 (2024-01-31) =
* Allow charging tax only on fees only - ADDED
* PHP deprecated notice - FIXED
* Added filter to change status of free payments - ADDED
* Widget/Blocks renamed for better understanding - CHANGED
* Room booking custom field added to allow multiple rooms per listing - ADDED
* Give Feedback notice added to settings pages - ADDED
* Widget/Blocks given style options - ADDED
* Optimized query of owner bookings table - CHANGED

= 2.0.4-beta (2023-04-25) =
* Booking widgets now require booking to be specifically set on the listing - CHANGED
* Main search bar input shows "Guests" as value instead of placeholder - FIXED

= 2.0.3-beta (2023-04-20) =
* Block themes can break some JS HTML template for booking form - FIXED

= 2.0.2-beta (2023-04-19) =
* First public release
