=== Glance That ===

Contributors: UaMV
Donate link: http://vandercar.net/wp
Tags: cpt, custom, post type, glance, dashboard, admin, dashicons
Requires at least: 3.1
Tested up to: 4.4
Stable tag: 2.4
License: GPLv2 or later

Adds content control to At a Glance on the Dashboard

== Description ==

Glance That allows users to customize the content viewable in At a Glance on the WordPress Dashboard. Users can add/remove items from At a Glance, view items even if zero published posts exist, assign custom dashicons for their display, and sort the order of displayed items using drag & drop. Currently, the following items are supported ...

* Custom post types
* Revisions (admins only)
* Media
* Plugins
* Users
* Gravity Forms

Additionally, Glance That allows you to view the number of items matching the following statuses:

* Scheduled
* Pending
* Draft
* Private
* Trash
* Unattached (Media)
* Spam (Comments)
* Active, Inactive (Plugins & Gravity Forms)
* Updates (Plugins)

= Constants =

Statuses can be hid by adding the following to wp-config.php
`define( 'GT_SHOW_ALL_STATUS', FALSE );`

Items with a zero published count can be hid by adding
`define( 'GT_SHOW_ZERO_COUNT', FALSE );`

Restrict modification of visible glances by adding
`define( 'GT_EDIT_GLANCES', 'capability_required_to_edit' );`

= Filters =

The `gt_default_glances` filter can be used to define glances that should be visible to a user upon first visiting their dashboard.
`apply_filters( 'gt_default_glances', array $default_glances, int $user_ID );`
where the $default_glances parameter is prepopulated as
`
// Define standard defaults
$gt_default_glances = array(
	'post' => array( 'icon' => 'f109', 'sort' => 1 ),
	'page' => array( 'icon' => 'f105', 'sort' => 2 ),
	'comment' => array( 'icon' => 'f101', 'sort' => 3 ),
	);
`

== Installation ==

1. Upload the `glance-that` directory to `/wp-content/plugins/`
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

Silence is golden.

== Screenshots ==

1. At a Glance
2. Glance That Form

== Changelog ==

= 2.4 =
* Supports new dashicons in WP 4.3

= 2.3 =
* Allows restriction of the editability of glances

= 2.2 =
* Supports new dashicons

= 2.1 =
* Allows filtering of default glances with gt_default_glances
* Changes GT_SHOW_ALL to GT_SHOW_ALL_STATUS

= 2.0 =
* Fix for PHP Warning when users meta had not yet been set

= 1.9 =
* Adds support for glancing Gravity Forms
* Adds move cursor for drag & drop
* Fix for mismatched icons

= 1.8 =
* Adds sorting via drag & drop!
* Allows control over native items
* Allows viewability of items with zero count

= 1.7 =
* Fix for thrown notices in some situations

= 1.6 =
* Adds support for glancing plugins

= 1.5 =
* Fix to highlight pending items

= 1.4 =
* Adds post status viewing
* Supports additional dashicons
* Adds dynamic form for add/remove

= 1.3 =
* CSS fix for hidden content in certain instances

= 1.2 =
* Auto-finds icons for registered post types

= 1.1 =
* Readme fix

= 1.0 =
* Initial Release

== Upgrade Notice ==

= 2.4 =
* Supports new dashicons in WP 4.3

= 2.3 =
* You can now restrict users without a specific capability from editing their visible glances.

= 2.2 =
* Supporting new dashicons and tagging WP 4.1 compatibility

= 1.8 =
* New features include sorting and control of native items. Please, note that native items (posts, pages, comments) will need to be manually added after this update.

= 1.4 =
* Adds new features
