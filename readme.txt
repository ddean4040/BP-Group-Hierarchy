=== BP Group Hierarchy ===
Contributors: ddean
Tags: buddypress, groups, subgroups, hierarchy
Requires at least: 3.0
Tested up to: 3.1
Stable tag: 1.0.3

Allows BuddyPress groups to have subgroups.

== Description ==

Break free from the tyranny of a flat group list!

This plugin allows group creators to place a new group under an existing group.  There is currently no limit to the depth of the group hierarchy.

Every group and subgroup is a normal BuddyPress group and can have members and a forum, use group extensions, etc.

= Notes =

Basic testing has revealed no problems with group extensions.  However, since this is an early release, it is **NOT** recommended for production sites.

== Installation ==

1. Extract the plugin archive 
1. Upload plugin files to your `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Does privacy or status propagate from group to subgroup? =

No. The plugin creates a hierarchy of group URLs, but does not put restrictions on the subgroup.

= Are group members automatically added to a subgroup? =

No. I don't know how you will want to use subgroups, so no assumptions have been made.

== Changelog ==

= 1.0.3 =
* Fixed bug when using custom group slug reported by avahaf

= 1.0.2 =
* Fixed group invite bug reported by cezar

= 1.0.1 =
* Fixed forum permalink bug reported by mtblewis
* Added check_slug_stem function for wildcard searches
* More documentation

= 1.0 =
* Initial release

== Upgrade Notice ==

= 1.0.3 =
Fixed custom group slug bug
Users with custom BP_GROUPS_SLUG should upgrade immediately

= 1.0.2 =
Fixed group invite bug

= 1.0.1 =
Fixed forum topic permalink bug

== Known Issues ==

Currently known issues:

* PHP 5 only
* No caching - yet
* No translations file - yet
* No administrative interface for viewing entire group tree - yet
* Few sanity checks when moving groups (but only super admins can move groups)
