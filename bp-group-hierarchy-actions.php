<?php

/**
 * Before deleting a group, move all its child groups to its immediate parent.
 */
function bp_group_hierarchy_rescue_child_groups( &$parent_group ) {

	$parent_group_id = $parent_group->id;

	if($child_groups = BP_Groups_Hierarchy::has_children( $parent_group_id )) {
		
		$group = new BP_Groups_Hierarchy($parent_group_id);
		if($group) {
			$new_parent_group_id = $group->parent_id;
		} else {
			$new_parent_group_id = 0;
		}
		
		foreach($child_groups as $group_id) {
			$child_group = new BP_Groups_Hierarchy($group_id);
			$child_group->parent_id = $new_parent_group_id;
			$child_group->save();
		}
	}
}

add_action( 'bp_groups_delete_group', 'bp_group_hierarchy_rescue_child_groups' );

/**
 * Trigger actions for integrators based on parent / child activity
 */
add_action( 'groups_join_group', 'bp_group_hierarchy_notify_relatives_of_join' );
add_action( 'groups_leave_group', 'bp_group_hierarchy_notify_relatives_of_leave' );
add_action( 'bp_activity_add', 'bp_group_hierarchy_notify_relatives_of_activity' );

/**
 * Notify parent and children when a user joins a group
 */
function bp_group_hierarchy_notify_relatives_of_join( $group_id, $user_id ) {
	
	$group = array('id'	=> $group_id);
	
	/** Notify children */
	if($children = bp_group_hierarchy_get_subgroups((object)$group)) {
		foreach($children as $subgroup) {
			do_action('groups_hierarchy_join_parent_group', $subgroup, $user_id );
		}
	}
	
	/** Notify parent */
	if($parents = bp_group_hierarchy_get_parents((object)$group) && count($parents) > 0) {
		do_action('groups_hierarchy_join_child_group', (int)$parents[0], $group_id, $user_id );
	}
}

/**
 * Notify parent and children when a user leaves a group
 */
function bp_group_hierarchy_notify_relatives_of_leave( $group_id, $user_id ) {
	
	$group = array('id'	=> $group_id);
	
	/** Notify children */
	if($children = bp_group_hierarchy_get_subgroups((object)$group)) {
		foreach($children as $subgroup) {
			do_action('groups_hierarchy_leave_parent_group', $subgroup, $user_id );
		}
	}
	
	/** Notify parent */
	if($parents = bp_group_hierarchy_get_parents((object)$group) && count($parents) > 0) {
		do_action('groups_hierarchy_leave_child_group', (int)$parents[0], $group_id, $user_id );
	}
}

/**
 * Notify parent and children of activity in a group
 */
function bp_group_hierarchy_notify_relatives_of_activity( $params) {
	
	global $bp;
	
	if($params['component'] == $bp->groups->id) {
	
		$group_id = $params['item_id'];
		$group = new BP_Groups_Hierarchy( $group_id );
		
		/** Notify children */
		if($children = bp_group_hierarchy_get_subgroups((object)$group)) {
			foreach($children as $subgroup) {
				do_action('groups_hierarchy_parent_group_activity_add', $subgroup, $params );
			}
		}
		
		/** Notify parent */
		if($parents = bp_group_hierarchy_get_parents((object)$group) && count($parents) > 0) {
			do_action('groups_hierarchy_child_group_activity_add', (int)$parents[0], $group_id, $params );
		}
	}
}


/**
 * This is deprecated and will be removed SOON
 */
function bp_group_hierarchy_propagate_activity($params) {
	
	_deprecated_function( __FUNCTION__, '1.2.9');
	
	global $bp;
	
	if($params['component'] == $bp->groups->id) {
		
		$group_id = $params['item_id'];
		$group = new BP_Groups_Hierarchy( $group_id );
		
		/** Only propagate the activity of valid, public subgroups */
		if($group && $group->status == 'public' && $group->parent_id != 0) {
			
			/**	Build a new activity notice for the parent group - this process will recurse up through the group tree */
			$item_id = $group->parent_id;
			
			bp_activity_add( 
				array( 
					'id' => $params['id'], 
					'user_id' => $params['user_id'], 
					'action' => $params['action'], 
					'content' => $params['content'], 
					'primary_link' => $params['primary_link'], 
					'component' => $params['component'], 
					'type' => $params['type'], 
					'item_id' => $item_id, 
					'secondary_item_id' => $params['secondary_item_id'], 
					'recorded_time' => $params['recorded_time'], 
					'hide_sitewide' => $params['hide_sitewide']
					// would love to hide_sitewide, but this actually hides from the group's own activity display
				)
			);
			
			/** Update the last activity time for parent group(s) */
			groups_update_last_activity( $item_id );
			
		}
	}
}

if(defined('BP_GROUP_HIERARCHY_ENABLE_ACTIVITY_PROPAGATION') && BP_GROUP_HIERARCHY_ENABLE_ACTIVITY_PROPAGATION) {
	add_action( 'bp_activity_add', 'bp_group_hierarchy_propagate_activity' );
}

?>