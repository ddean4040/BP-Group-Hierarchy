<?php

/**
 *	Override group retrieval for groups template
 *  @return int|bool number of matching groups or FALSE if none
 */
function bp_group_hierarchy_override_template($has_groups) {
	
	global $bp, $groups_template;

	if(!$has_groups)	return false;
	
	$groups_hierarchy_template = new BP_Groups_Hierarchy_Template();

	bp_group_hierarchy_copy_vars(
		$groups_template,
		$groups_hierarchy_template, 
		array(
			'group',
			'group_count',
			'groups',
			'single_group',
			'total_group_count',
			'pag_links',
			'pag_num',
			'pag_page'
		)
	);

	$groups_hierarchy_template->synchronize();

	foreach($groups_hierarchy_template->groups as $key => $group) {
		if(isset($group->id)) {
			$groups_hierarchy_template->groups[$key] = new BP_Groups_Hierarchy($group->id);
		} else {
//			$groups_hierarchy_template->groups[$key] = new BP_Groups_Hierarchy($group->group_id);	
		}
	}
	$groups_template = $groups_hierarchy_template;
	
	return $has_groups;
}
add_filter( 'bp_has_groups', 'bp_group_hierarchy_override_template', 10, 2 );


/**
 * Fix forum topic permalinks for subgroups
 */
function bp_group_hierarchy_fixup_forum_paths( $topics ) {
	
	// replace each simple slug with its full path
	if(is_array($topics)) {
		foreach($topics as $key => $topic) {
	
			$group_id = BP_Groups_Group::group_exists($topic->object_slug);
			if($group_id) {
				$topics[$key]->object_slug = BP_Groups_Hierarchy::get_path( $group_id );
			}
		}
	}
	return $topics;
	
}
add_filter( 'bp_forums_get_forum_topics', 'bp_group_hierarchy_fixup_forum_paths', 10, 2 );

/**
 * Fix forum topic action links (Edit, Delete, Close, Sticky, etc.)
 */
function bp_group_hierarchy_fixup_forum_links( $has_topics ) {
	global $forum_template;
	
	$group_id = BP_Groups_Group::group_exists( $forum_template->topic->object_slug );
	$forum_template->topic->object_slug = BP_Groups_Hierarchy::get_path( $group_id );
	
	return $has_topics;
	
}
add_filter( 'bp_has_topic_posts', 'bp_group_hierarchy_fixup_forum_links', 10, 2 );

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

	switch ( $type ) {
		case 'by_parent':
			$groups = BP_Groups_Hierarchy::get_by_parent( $parent_id, $per_page, $page, $user_id, $search_terms, $populate_extras );
			break;
	}
	
	return $groups;
}


/**
 * Override the group slug in permalinks with a group's full path
 * NOTE: this may obviate some of the other functions; they will disappear over time if that turns out to be the case
 */
function bp_group_hierarchy_fixup_permalink( $permalink ) {
	
	global $bp;
	
	$group_slug = substr( $permalink, strlen( $bp->root_domain . '/' . $bp->groups->slug . '/' ), -1 );
	
	if(strpos($group_slug,'/'))	return $permalink;
	
	$group_id = BP_Groups_Group::get_id_from_slug( $group_slug );
	
	if( !is_null($group_id) ) {
		$group_path = BP_Groups_Hierarchy::get_path( $group_id );
		return str_replace($group_slug,$group_path,$permalink);
	}
	return $permalink;
	
}
add_filter( 'bp_get_group_permalink', 'bp_group_hierarchy_fixup_permalink' );

/**
 * Group-specific copy of avatar retrieval function
 * NO LONGER USED BY EXTENSION
 */
function bp_group_hierarchy_get_avatar_by_group( $args = '', $group = false ) {
	global $bp, $groups_template;
	
	$defaults = array(
		'type' => 'full',
		'width' => false,
		'height' => false,
		'class' => 'avatar',
		'id' => false,
		'alt' => __( 'Group avatar', 'buddypress' )
	);
	
	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );
	
	if ( !$group )
		$group =& $groups_template->group;
	
	
	/* Fetch the avatar from the folder, if not provide backwards compat. */
	if ( !$avatar = bp_core_fetch_avatar( array( 'item_id' => $group->id, 'object' => 'group', 'type' => $type, 'avatar_dir' => 'group-avatars', 'alt' => $alt, 'css_id' => $id, 'class' => $class, 'width' => $width, 'height' => $height ) ) )
		$avatar = '<img src="' . esc_attr( $group->avatar_thumb ) . '" class="avatar" alt="' . esc_attr( $group->name ) . '" />';
	
	return apply_filters( 'bp_get_group_avatar', $avatar );
}

/**
 * Group-specific copy of member count retrieval function
 * NO LONGER USED BY EXTENSION
 */
function bp_group_hierarchy_get_group_member_count_by_group( $group = false ) {
	global $groups_template;

	if ( !$group )
		$group =& $groups_template->group;	

	if ( 1 == (int) $group->total_member_count )
		return apply_filters( 'bp_get_group_member_count', sprintf( __( '%s member', 'buddypress' ), bp_core_number_format( $group->total_member_count ) ) );
	else
		return apply_filters( 'bp_get_group_member_count', sprintf( __( '%s members', 'buddypress' ), bp_core_number_format( $group->total_member_count ) ) );
}
?>