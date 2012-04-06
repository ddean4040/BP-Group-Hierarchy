<?php
/*
Plugin Name: BP Group Hierarchy
Plugin URI: http://www.generalthreat.com/projects/buddypress-group-hierarchy/
Description: Allows BuddyPress groups to belong to other groups
Version: 1.6-compatible
Revision Date: 04/06/2012
Requires at least: PHP 5, WP 3.0, BuddyPress 1.5
Tested up to: WP 3.3.1 , BuddyPress 1.6
License: Example: GNU General Public License 2.0 (GPL) http://www.gnu.org/licenses/gpl.html
Author: David Dean
Author URI: http://www.generalthreat.com/
Site Wide Only: true
Network: true
*/

define ( 'BP_GROUP_HIERARCHY_IS_INSTALLED', 1 );
define ( 'BP_GROUP_HIERARCHY_VERSION', '1.3.1' );
define ( 'BP_GROUP_HIERARCHY_DB_VERSION', '1' );
if( ! defined( 'BP_GROUP_HIERARCHY_SLUG' ) )
	define ( 'BP_GROUP_HIERARCHY_SLUG', 'hierarchy' );

/** load localization files if present */
if( file_exists( dirname( __FILE__ ) . '/languages/' . dirname(plugin_basename(__FILE__)) . '-' . get_locale() . '.mo' ) ) {
	load_plugin_textdomain( 'bp-group-hierarchy', false, dirname(plugin_basename(__FILE__)) . '/languages' );
} else if ( file_exists( dirname( __FILE__ ) . '/languages/' . get_locale() . '.mo' ) ) {
	_doing_it_wrong( 'load_textdomain', 'Please rename your translation files to use the ' . dirname(plugin_basename(__FILE__)) . '-' . get_locale() . '.mo' . ' format', '1.2.7' );
	load_textdomain( 'bp-group-hierarchy', dirname( __FILE__ ) . '/languages/' . get_locale() . '.mo' );
}

require ( dirname( __FILE__ ) . '/bp-group-hierarchy-filters.php' );
require ( dirname( __FILE__ ) . '/bp-group-hierarchy-actions.php' );
require ( dirname( __FILE__ ) . '/bp-group-hierarchy-widgets.php' );

require ( dirname( __FILE__ ) . '/bp1-5.php' );

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
				parent_id BIGINT(20) NOT NULL DEFAULT 0,
				KEY parent_id (parent_id),
			) {$charset_collate};
	 	   ";

	if( ! get_site_option( 'bp-group-hierarchy-db-version' ) || get_site_option( 'bp-group-hierarchy-db-version' ) < BP_GROUP_HIERARCHY_DB_VERSION || ! bp_group_hierarchy_verify_install() ) {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta($sql);
	}
	
	if( bp_group_hierarchy_verify_install( true ) ) {
		update_site_option( 'bp-group-hierarchy-db-version', BP_GROUP_HIERARCHY_DB_VERSION );
	} else {
		die('Could not create the required column.  Please enable debugging for more details.');
	}
}

register_activation_hook( __FILE__, 'bp_group_hierarchy_install' );

/**
 * Try to DESCRIBE the groups table to see whether the column exists / was added
 * @param bool $debug_column Whether to report that the required column wasn't found - this is normal pre-install
 */
function bp_group_hierarchy_verify_install( $debug_column = false ) {

	global $wpdb, $bp;

	/** Manually confirm that parent_id column exists */
	$parent_id_exists = true;
	$columns = $wpdb->get_results( 'DESCRIBE ' . $bp->groups->table_name );
	
	if( $columns ) {
		$parent_id_exists = false;
		foreach( $columns as $column ) {
			if( $column->Field == 'parent_id') {
				$parent_id_exists = true;
				break;
			}
		}
		
		if( ! $parent_id_exists && $debug_column ) {
			bp_group_hierarchy_debug( 'Required column was not found - last MySQL error was: ' . $wpdb->last_error );
			return $parent_id_exists;
		}
		
	} else {
		bp_group_hierarchy_debug( 'Could not DESCRIBE table - last MySQL error was: ' . $wpdb->last_error );
		return false;
	}
	
	return $parent_id_exists;
	
}

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
add_action( 'bp_setup_globals', 'bp_group_hierarchy_setup_globals' );

/**
 * Activate group extension
 */
function bp_group_hierarchy_init() {
	
	/** Enable logging with WP Debug Logger */
	$GLOBALS['wp_log_plugins'][] = 'bp_group_hierarchy';
	
	require ( dirname( __FILE__ ) . '/bp-group-hierarchy-functions.php' );
	require ( dirname( __FILE__ ) . '/extension.php' );
	
}
add_action( 'bp_include', 'bp_group_hierarchy_init' );

/**
 * Add hook for intercepting requests before they're routed by normal BP processes
 */
function bp_group_hierarchy_override_routing() {

	require_once ( dirname( __FILE__ ) . '/bp-group-hierarchy-classes.php' );
	require_once ( dirname( __FILE__ ) . '/bp-group-hierarchy-template.php' );

	if( is_admin() )	return;
	
	do_action( 'bp_group_hierarchy_route_requests' );
}
// must be lower than 8 to fire before bp_setup_nav() in BP 1.2
add_action( 'bp_loaded', 'bp_group_hierarchy_override_routing', 7 );


function bp_group_hierarchy_debug( $message ) {

	if( ! defined( 'WP_DEBUG') || ! WP_DEBUG )	return;

	if(defined( 'WP_DEBUG_LOG') && WP_DEBUG_LOG ) {
		$GLOBALS['wp_log']['bp_group_hierarchy'][] = 'BP Group Hierarchy - ' .  $message;
		error_log('BP Group Hierarchy - ' .  $message);
	}

	if( defined('WP_DEBUG_DISPLAY') && false !== WP_DEBUG_DISPLAY) {
		echo '<div class="log">BP Group Hierarchy - ' . $message . "</div>\n";
	}
	
}

?>