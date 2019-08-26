=== Paid Memberships Pro - Member History Add On ===
Contributors: strangerstudios
Tags: pmpro, paid memberships pro, members, history
Requires at least: 4
Tested up to: 5.0.3
Stable tag: .3.0.1

Display a history of a user's Membership on the User Profile for admins only.

== Description ==

This plugin adds a Membership History section to the user profile, allowing you to view (in one place) all the membership levels held by the specific user. The history view is visible to admins only.

== Changelog ==
= .3.0.1 =
* BUG/ENHANCEMENT: Now showing any order status in the User's Order History.
* BUG/ENHANCEMENT: Adding button/link to view Member Value full report from Reports Widgets on Memberships > Reports admin page.
* ENHANCEMENT: Adding feature to Edit, Print or Email an order from the User's Order History page for PMPro v2.1+.

= .3 =
* FEATURE: Updated for localization.
* FEATURE: Added Top 10 Member report to the reports dashboard widget.
* BUG FIX/ENHANCEMENT: Filtering out orders in token status or from different gateway environments.
* BUG FIX/ENHANCEMENT: Fixed default member report query for better performance.
* ENHANCEMENT: Added a discount code column to the member history table. (Thanks, Ted Barnett)
* ENHANCEMENT: Added pmpromh_member_history_extra_cols_header and pmpromh_member_history_extra_cols_body hooks for the member history table shown in the edit user profile.
* ENHANCEMENT: Added pmpromh_orders_extra_cols_header and pmpromh_orders_extra_cols_body hooks for the order history table shown in the edit user profile.

= .2.4.2 =
* BUG FIX: Removed the "right/wrong" language around the order totals in the member history table.

= .2.4.1 =
* BUG: Removed debug code that was causing fatal errors.

= .2.4 =
* BUG/ENHANCEMENT: Now showing pending/error/token/refunded (any status) orders in the history again.

= .2.3 =
* BUG: Fixed invalid expiry date in member history for members who have none (again). :)

= .2.2 =
* BUG: Fixed invalid expiry date in member history for members who have none

= .2.1 =
* Fixed typo in href of the Membership Levels History tab. (Thanks, Chris James)
* Removed debug code that could have caused a fatal error.

= .2 =
* Added tabbed view to display history based on "pmpro_membership_orders" or "pmpro_memberships_users" tables (which includes admin changes to a user's membership).

= .1 =
* Initial commit.