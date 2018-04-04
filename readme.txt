=== Glance That ===

Contributors: UaMV
Donate link: http://typewheel.xyz/give
Tags: cpt, custom, post type, post status, glance, dashboard, admin, dashicons
Requires at least: 3.1
Tested up to: 4.9.2
Stable tag: 3.9
License: GPLv2 or later

Adds content control to At a Glance on the Dashboard

== Description ==

Glance That allows users to customize the content viewable in At a Glance on the WordPress Dashboard. Users can add/remove items from At a Glance, view statuses of posts, assign custom dashicons for their display, sort the order of displayed items using drag & drop, and quick link to the Add New content screens. Display of items respects user capabilities. Administrators can apply a glance configuration site-wide to all users or to new users. Currently, the following items are supported ...

* Custom post types
* Revisions (admins only)
* Media
* Plugins
* Themes
* Users
* Gravity Forms
* Formidable Forms
* Give Forms
* Sliced Invoices & Estimates
* ProjectHuddle Sites & Mockups

Additionally, Glance That allows you to toggle view of the number of items matching the following statuses:

* Mine
* Scheduled
* Pending
* Draft
* Private
* Archived (via [Archived Post Status](https://wordpress.org/plugins/archived-post-status/))
* Trash
* Unattached (Media)
* Spam (Comments)
* Active, Inactive (Plugins & Gravity Forms)
* Updates (Plugins & Themes)
* Favorites, Recently Active, Must-Use, Drop-Ins (Plugins)

If you've defined custom post state icons via [Post State Tags](https://wordpress.org/plugins/post-state-tags/), then Glance that will inherit these.

= Constants =

Add the following constants to `wp-config.php` to control Glance That capabilities

Restrict modification of visible glances (default: read) by adding

`define( 'GT_EDIT_GLANCES', 'capability_required_to_edit' );`

Restrict application of default glances (default: edit_dashboard) by adding

`define( 'GT_ADMIN_GLANCES', 'capability_required_to_admin' );`

= Filters =

The `gt_labels` filter can be used to custom labels for glances.

`apply_filters( 'gt_labels', str $label, str $glance, int $count );`

The constants defined are becoming more plentiful, so in the interest of possibly removing these in a future version, v3.0 adds corresponding filters for nearly all.

`
gt_show_mine
gt_show_zero_count
gt_show_add_new
gt_show_all_status
gt_show_zero_count_status
gt_show_mustuse
gt_show_dropins
gt_show_all_dashicons
gt_show_notices
`

== Installation ==

1. Upload the `glance-that` directory to `/wp-content/plugins/`
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

Silence is golden.

== Screenshots ==

1. Glance That: At a Glance

== Changelog ==

= 3.9 =
* Add link to Mine posts list w/ filter
* Remove obnoxious admin notices

= 3.8 =
* Updating Typewheel notice library

= 3.7 =
* Support Sliced Invoices & Estimates
* Support ProjectHuddle
* Add activation notice and tutorial
* Add delayed notices for review and donation

= 3.6 =
* Fix: Allow sorting on last glance item
* Readme: Removes documentation of some constants and filters

= 3.5 =
* Fixes bug preventing access to Glance That settings in some browsers.

= 3.4 =
* Allow application of current glance configuration to all existing and new users.
* Change UI of adding/removing glances
* Few small bug fixes

= 3.3 =
* Fix layout bug when no posts or no pages.
* Adds status for available theme updates

= 3.2 =
* Adds `gt_labels` filter
* Removes `gt_label` and  `gt_label_singular` filters
* Adds support for themes

= 3.1 =
* Allows toggling of status visibility
* Add glance label filters: gt_glance_label and gt_glance_label_singular
* Support Give forms as glances
* Update status links for Gravity Form items

= 3.0 =
* Adds 'Add New' icon to items for which content can be created
* Adds filters for most constants

= 2.9 =
* Adds user favorite plugins glance

= 2.8 =
* Ajaxify the addition & removal of glances
* Fix for documentation on GT_SHOW_ALL_DASHICONS
* Adds title attribute to links
* Minor CSS fix in displaying glances
* By default, hide statuses with zero count
* Adds GT_SHOW_ZERO_COUNT_STATUS constant
* Adds recently activated plugins
* Adds option to show must-use and drop-in plugins with GT_SHOW_MUSTUSE and GT_SHOW_DROPINS

= 2.7 =
* Supports Formidable Forms
* Use Gravity Form & Formidable Form icons
* Possibly fixed an issue with Post State Tags integration

= 2.6 =
* Supports `archive` post status
* Integrates with Post State Tags icon selection

= 2.5 =
* Supports new dashicons
* Adds option to display all dashicons by default

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
= 3.8 =
* Updating Typewheel Notice library

= 3.7 =
* Supports Sliced Invoices & Estimates
* Supports ProjectHuddle
* Adds activation notice and tutorial
* Adds delayed notices for review and donation

= 3.6 =
* Fix: Allow sorting on last glance item
* Readme: Removes documentation of some constants and filters

= 3.5 =
* Fixes bug preventing access to Glance That settings in some browsers.

= 3.4 =
* Allow application of current glance configuration to all existing and new users.
* Change UI of adding/removing glances
* Few small bug fixes

= 3.3 =
* Fixes layout bug when no posts or no pages.
* Adds status for available theme updates

= 3.2 =
* Modifies label filters and adds glancing of themes.

= 3.0 =
* Adds 'Add New' quick links and filtering of constants

= 2.8 =
* Glance That now utilizes ajax for easier editing of glances. There is also support for a few additional statuses and some minor style enhancements.

= 2.7 =
* Supports Formidable Forms. Note that v3.0 may remove support for custom icon selection.

= 2.6 =
* Supports `archive` post status & integrates with Post State Tags icon selection

= 2.5 =
* Supports new dashicons and adds constant to display all dashicons by default

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
