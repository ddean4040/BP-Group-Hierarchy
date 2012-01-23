<?php

/**
 *	Override group retrieval for groups_template,
 *	replacing every BP_Groups_Group with a BP_Groups_Hierarchy object
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

/**
 * Override the group slug in permalinks with a group's full path
 */
function bp_group_hierarchy_fixup_permalink( $permalink ) {
	
	global $bp;
	
	$group_slug = substr( $permalink, strlen( $bp->root_domain . '/' . bp_get_groups_hierarchy_root_slug() . '/' ), -1 );
	
	if(strpos($group_slug,'/'))	return $permalink;
	
	$group_id = BP_Groups_Group::get_id_from_slug( $group_slug );
	
	if( !is_null($group_id) ) {
		$group_path = BP_Groups_Hierarchy::get_path( $group_id );
		return str_replace( '/' . $group_slug . '/', '/' . $group_path . '/', $permalink );
	}
	return $permalink;
	
}
add_filter( 'bp_get_group_permalink', 'bp_group_hierarchy_fixup_permalink' );

?>