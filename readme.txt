=== BP Group Hierarchy ===
Contributors: ddean
Tags: buddypress, groups, subgroups, hierarchy, parent group
Requires at least: 3.0
Tested up to: 3.2
Stable tag: 1.1.4

Allows BuddyPress groups to have subgroups.

== Description ==

Break free from the tyranny of a flat group list!

This plugin allows group creators to place a new group under an existing group.  There is currently no limit to the depth of the group hierarchy.

Every group and subgroup is a normal BuddyPress group and can have members and a forum, use group extensions, etc.

= Notes =

Basic testing has revealed no problems with group extensions.  As always, test plugins before deploying to production sites.

== Installation ==

1. Extract the plugin archive 
1. Upload plugin files to your `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Does privacy or status propagate from group to subgroup? =

No. The plugin creates a hierarchy of group URLs, but does not put restrictions on the subgroup.

= Are group members automatically added to a subgroup? =

No. I don't know how you will want to use subgroups, so no assumptions have been made.

= If I restrict new groups to member or admins, can a subgroup be made with more lenient restrictions? =

Yes. Restrictions affect only the group to which they are applied.  Subgroups can themselves be more or less restrictive.

= Do activity stream messages propagate up (from child to parent) or down (from parent to child)?

No. Unfortunately, there is no easy way to have a group subscribe to another group's activity.
This will require either creating and managing duplicate activity items for each affected group, or creating a mapping of
additional group IDs for a group to poll when building the activity stream.


== Screenshots ==

1. Group Tree tab on main Groups page
2. Member Groups item on individual group pages
3. Hierarchy options when creating new groups

== Changelog ==

= 1.1.4 =
* Added: 'Nobody' permission - allows only site admins to create child groups (req'd by flynn)
* Changed: ID of widget panel to avoid interference with normal Groups widget
* Changed: Made default values for labels more consistent
* Fixed: Made group tree more resilient to invalid bp->groups->current_group data

= 1.1.3 =
* Added: support for searching and sorting when using only the Group Tree
* Fixed: Group Tree issue when there are more than per_page groups

= 1.1.2 =
* Fixed: Forum bug from the last update that affected the main Forums screen

= 1.1.1 =
* Added: Browse the entire hierarchy on the Group Tree page
* Added: Templates for listing groups and subgroups

= 1.1.0 =
* Added: top-level groups widget
* Changed: groups admins can edit subgroup creation permissions
* Changed: handling of parent group in group creation to avoid PHP errors
* Fixed: wrong URL on Group Tree tab - still requires AJAX loading, but getting closer

= 1.0.9 =
* Added: Ability to show number of child groups on the 'Member Groups' tab

= 1.0.8 =
* Added: Group Tree to extension for viewing groups by hierarchy
* Added: Admin options for Member Groups and Group Tree
* Changed: Create a Member Group button to hopefully resolve empty group slug issues

= 1.0.7 =
* Changed: extension brings the Member Groups tab into the BuddyPress loop
* Changed: behavior of check_slug method for self-sufficiency
* Fixed: Join and Leave Group buttons on Member Groups tab refer to parent group - thanks, Deadpan110

= 1.0.6 =
* Fixed: bug that caused forum topics to not display reported by cezar

= 1.0.5 =
* Added: Group creators can now restrict subgroups to group members or group admins (with hooks for other types of restrictions)
* Added: Create a Member Group button on Member Groups tab for more streamlined use
* Changed: Reveal Member Groups tab to those allowed to create subgroups
* Changed: Default permissions now allow only group members to create subgroups
* Fixed: Private member groups were not being shown on that tab - thanks, Deadpan110

= 1.0.4 =
* Added get_group_extras fixup for Group Forum Extras and others
* Fixed notification bug reported by cezar

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

= 1.1.4 =
Increased compatibility with other group plugins, plus other minor changes

= 1.1.3 =
Fixed a bug when site has a large number of groups. All users should upgrade.

= 1.1.2 =
Fixed a bug with main forum list. All users should upgrade.

= 1.1.1 =
Browse the entire hierarchy from the Group Tree.

= 1.1.0 =
Added options for group admins and a top groups widget.

= 1.0.9 =
Changed Member Groups tab option.

= 1.0.8 =
Added admin options. 
May resolve empty group slug issue.

= 1.0.7 =
Fixed a bug affecting the Member Groups tab.
All users should upgrade immediately.

= 1.0.6 =
Fixed a bug that caused forum topics to not display
All users should upgrade immediately

= 1.0.5 =
Fixed an issue that hid private member groups
Added ability to restrict subgroups to member or admins

= 1.0.4 =
Fixed notification link bug
Users who want to use Group Forum Extras should upgrade

= 1.0.3 =
Fixed custom group slug bug
Users with custom BP_GROUPS_SLUG should upgrade immediately

= 1.0.2 =
Fixed group invite bug

= 1.0.1 =
Fixed forum topic permalink bug

== Known Issues ==

Currently known issues:

* Tabs on Groups page may revert to an "unselected" state when navigating the tree or hiding the normal group list
* HTML title of Groups Directory page is just the site name when you hide the normal group list
* PHP 5 only
* No administrative interface for viewing entire group tree - yet
