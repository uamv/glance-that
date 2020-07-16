=== Glance That ===

Contributors: uamv
Donate link: https://typewheel.xyz/give?ref=Glance%20That
Tags: cpt, custom, post type, post status, glance, dashboard, admin, dashicons
Requires PHP: 5.6
Requires at least: 3.1
Tested up to: 5.5
Stable tag: 4.8
License: GPLv2 or later

Adds content control to At a Glance on the Dashboard

== Description ==

Glance That allows users to customize the content viewable in At a Glance on the WordPress Dashboard. Add/remove items from At a Glance, view status of items, assign custom dashicons, sort the order of glanced items, and quick link to both the Add New content screens and front end post archives. Display of items respects user capabilities. Administrators can apply a glance configuration site-wide to all users or only to new users. Currently, the following items are supported ...

* Custom post types
* Revisions (admins only)
* Media
* Plugins
* Themes
* Users
* Site Health Status
* Data Export Requests
* Data Removal Requests
* Gravity Forms
* Gravity View
* Formidable Forms
* Ninja Forms
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
* Confirmed, Failed, Complete (User Data Requests)
* Spam (Comments)
* Active, Inactive (Plugins & Gravity Forms)
* Updates (Plugins & Themes)
* Favorites, Recently Active, Must-Use, Drop-Ins (Plugins)
* Paused Plugins & Themes (when in recovery mode)
* Good Items, Recommended Improvements, Critical Issues (Site Health)

If you've defined custom post state icons via [Post State Tags](https://wordpress.org/plugins/post-state-tags/), then Glance that will inherit these.

= Filters =

A slew of filters are available to fine tune integration with your site:

`gt_glance_selection` lets you limit available glances.

`gt_labels` customizes glance labels.

`gt_option_icons` customizes default icon for a specific glance when selected from the dropdown.

`gt_dashicons` limits the available icons in the icon picker

`gt_view_at_a_glance` allows users to view the At a Glance metabox. By default, WP limits to those with `edit_posts` capability.

The following allow you to show/hide various portions of Glance That:
`
gt_show_mine
gt_show_zero_count
gt_show_add_new
gt_show_all_status
gt_show_zero_count_status
gt_show_mustuse
gt_show_dropins
gt_show_archive
gt_show_applause
gt_show_settings
`

== Installation ==

1. Upload the `glance-that` directory to `/wp-content/plugins/`
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

Silence is golden.

== Screenshots ==

1. Glance That: At a Glance
1. Settings Tray, Statuses & Management Form
1. Recovery Mode
1. Icon Picker

== Changelog ==

= 4.8 =
* Fix styling w/ WP 5.5
* Add new dashicons from WP 5.5

= 4.7 =
* Add `gt_glance_selection` filter
* Update site health to match WP 5.3 treatment

= 4.6 =
* Toggle & style other items added to subsection
* Fix fatal error when including plugin API

= 4.5 =
* Fix identification of user_request-remove_personal_data
* Add glance for site health status in WP 5.2
* Add WP version check on some glances

= 4.4 =
* Support multisite sites
* Add paused plugins & themes while in recovery mode
* Add new dashicons from WP 5.2
* Add new searchable icon picker & removed `gt_show_all_dashicons` filter
* Remove many GLOBAL variables
* Add `gt_show_settings` filter
* Ability to toggle visibility of WP info
* Improved styling
* Include visibility when sending settings to other users
* Fix Post State Tag integration

= 4.3 =
* Add reusable block post type

= 4.2 =
* Allows other capabilities to view At a Glances
* Switch `gt_show_mine` to default `false`.

= 4.1 =
* Add link to front-end post archive if it exists
* Add `gt_show_archive` filter w/ default true
* Add some missing notices
* Add icon support for WP Show Posts
* Support Ninja Forms

= 4.0 =
* Add `gt_option_icons` filter
* Filter out unnecessary types from ACF, WP
* Add WP user_request CPT w/ request types (export & erasure)
* Support GravityView icon
* Add inline applause actions & remove admin notices

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
