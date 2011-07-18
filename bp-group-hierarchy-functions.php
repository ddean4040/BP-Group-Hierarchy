<?php
/************************************
 * Utility and replacement functions
 ***********************************/

function bp_group_hierarchy_copy_vars($from, &$to, $attribs) {
	foreach($attribs as $var) {
		if(isset($from->$var)) {
			$to->$var = $from->$var;
		}
	}
}

/**
 * Hierarchy-aware replacement for bp_has_groups
 */
function bp_has_groups_hierarchy($args = '') {
	global $groups_template, $bp;

	/***
	 * Set the defaults based on the current page. Any of these will be overridden
	 * if arguments are directly passed into the loop. Custom plugins should always
	 * pass their parameters directly to the loop.
	 */
	$type = 'active';
	$user_id = false;
	$search_terms = false;
	$slug = false;

	/* User filtering */
	if ( !empty( $bp->displayed_user->id ) )
		$user_id = $bp->displayed_user->id;

	/* Type */
	if ( 'my-groups' == $bp->current_action ) {
		if ( 'most-popular' == $order )
			$type = 'popular';
		else if ( 'alphabetically' == $order )
			$type = 'alphabetical';
	} else if ( 'invites' == $bp->current_action ) {
		$type = 'invites';
	} else if ( $bp->groups->current_group->slug ) {
		$type = 'single-group';
		$slug = $bp->groups->current_group->slug;
	}

	if ( isset( $_REQUEST['group-filter-box'] ) || isset( $_REQUEST['s'] ) )
		$search_terms = ( isset( $_REQUEST['group-filter-box'] ) ) ? $_REQUEST['group-filter-box'] : $_REQUEST['s'];

	$defaults = array(
		'type' => $type,
		'page' => 1,
		'per_page' => 20,
		'max' => false,

		'user_id' => $user_id, // Pass a user ID to limit to groups this user has joined
		'slug' => $slug, // Pass a group slug to only return that group
		'search_terms' => $search_terms, // Pass search terms to return only matching groups

		'populate_extras' => true // Get extra meta - is_member, is_banned
	);

	$r = wp_parse_args( $args, $defaults );

	extract( $r );

	if(isset($parent_id)) {
		$type = 'by_parent';
	}

	$groups_template = new BP_Groups_Hierarchy_Template( (int)$user_id, $type, (int)$page, (int)$per_page, (int)$max, $slug, $search_terms, (bool)$populate_extras, (int)$parent_id );

	return apply_filters( 'bp_has_groups', $groups_template->has_groups(), &$groups_template );

}

/**
 * Catch requests for groups by parent and use BP_Groups_Hierarchy::get_by_parent to handle
 */
function bp_group_hierarchy_get_by_hierarchy($args) {

	$defaults = array(
		'type' => 'active', // active, newest, alphabetical, random, popular, most-forum-topics or most-forum-posts
		'user_id' => false, // Pass a user_id to limit to only groups that this user is a member of
		'search_terms' => false, // Limit to groups that match these search terms

		'per_page' => 20, // The number of results to return per page
		'page' => 1, // The page to return if limiting per page
		'populate_extras' => true, // Fetch meta such as is_banned and is_member
	);

	$params = wp_parse_args( $args, $defaults );
	
	extract( $params, EXTR_SKIP );

	if(isset($parent_id)) {
		$groups = BP_Groups_Hierarchy::get_by_parent( $parent_id, $type, $per_page, $page, $user_id, $search_terms, $populate_extras );
	}
	return $groups;
}

if(!function_exists('bp_get_groups_root_slug')) {
	function bp_get_groups_root_slug() {
		global $bp;
		return apply_filters( 'bp_get_groups_root_slug', BP_GROUPS_SLUG );
	}
	
}

?>