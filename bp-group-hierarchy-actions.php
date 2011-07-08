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
?>