<?php
/*
Plugin Name: BP Group Hierarchy
Plugin URI: http://www.jerseyconnect.net/development/buddypress-group-hierarchy/
Description: Allows BuddyPress groups to belong to other groups
Version: 1.2.0-testing
Revision Date: 08/08/2011
Requires at least: PHP 5, WP 3.0, BuddyPress 1.2
Tested up to: WP 3.2.1 , BuddyPress 1.3-bleeding
License: Example: GNU General Public License 2.0 (GPL) http://www.gnu.org/licenses/gpl.html
Author: David Dean
Author URI: http://www.jerseyconnect.net/development/
Site Wide Only: true
Network: true
*/

define ( 'BP_GROUP_HIERARCHY_IS_INSTALLED', 1 );
define ( 'BP_GROUP_HIERARCHY_VERSION', '1.1.9' );
define ( 'BP_GROUP_HIERARCHY_DB_VERSION', '1' );
define ( 'BP_GROUP_HIERARCHY_SLUG', 'hierarchy' );

//load localization files if present
if ( file_exists( dirname( __FILE__ ) . '/languages/' . get_locale() . '.mo' ) )
	load_textdomain( 'bp-group-hierarchy', dirname( __FILE__ ) . '/languages/' . get_locale() . '.mo' );

require ( dirname( __FILE__ ) . '/bp-group-hierarchy-filters.php' );
require ( dirname( __FILE__ ) . '/bp-group-hierarchy-actions.php' );
require ( dirname( __FILE__ ) . '/bp-group-hierarchy-widgets.php' );

require ( dirname( __FILE__ ) . '/bp1-2.php' );
require ( dirname( __FILE__ ) . '/bp1-3.php' );

/*************************************************************************
*********************SETUP AND INSTALLATION*******************************
*************************************************************************/

/**
 * Install and/or upgrade the database
 */
function bp_group_hierarchy_install() {
	global $wpdb, $bp;

	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	
	$sql[] = "CREATE TABLE {$bp->groups->table_name} (
				`id` bigint(20) NOT NULL AUTO_INCREMENT,
				`parent_id` bigint(20) NOT NULL DEFAULT 0,
				KEY `parent_id` (`parent_id`),
			) {$charset_collate};
	 	   ";

	require_once( ABSPATH . 'wp-admin/upgrade-functions.php' );
	dbDelta($sql);
	
	update_site_option( 'bp-group-hierarchy-db-version', BP_GROUP_HIERARCHY_DB_VERSION );
}

register_activation_hook( __FILE__, 'bp_group_hierarchy_install' );

/**
 * Set up global variables
 */
function bp_group_hierarchy_setup_globals() {
	global $bp, $wpdb;

	/* For internal identification */
	$bp->group_hierarchy->id = 'group_hierarchy';
	$bp->group_hierarchy->table_name = $wpdb->base_prefix . 'bp_group_hierarchy';
	$bp->group_hierarchy->format_notification_function = 'bp_group_hierarchy_format_notifications';
	$bp->group_hierarchy->slug = BP_GROUP_HIERARCHY_SLUG;
	
	/* Register this in the active components array */
	$bp->active_components[$bp->group_hierarchy->slug] = $bp->group_hierarchy->id;
	
	do_action('bp_group_hierarchy_globals_loaded');
}
add_action( 'plugins_loaded', 'bp_group_hierarchy_setup_globals', 10 );
add_action( 'admin_menu', 'bp_group_hierarchy_setup_globals', 2 );

/**
 * Activate group extension
 */
function bp_group_hierarchy_init() {
	
	require ( dirname( __FILE__ ) . '/extension.php' );
	require ( dirname( __FILE__ ) . '/bp-group-hierarchy-functions.php' );
	
}
add_action( 'bp_include', 'bp_group_hierarchy_init' );

/** Cover both BP 1.2 and BP 1.3/5 group slug formats */
function bp_get_groups_hierarchy_root_slug() {
	if(defined('BP_GROUPS_SLUG')) {
		return apply_filters( 'bp_get_groups_root_slug', BP_GROUPS_SLUG );
	} else if(function_exists('bp_get_groups_root_slug')) {
		return bp_get_groups_root_slug();
	}
}

?>