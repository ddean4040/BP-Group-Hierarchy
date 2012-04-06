<?php
/**
 * Functions for BuddyPress 1.5 compatibility
 */

/**
 * Catch requests for the groups component and find the requested group
 */
function group_hierarchy_override_current_action( $current_action ) {
	global $bp;
	
	/** Only process once - hopefully this won't have any side effects */
	remove_action( 'bp_current_action', 'group_hierarchy_override_current_action' );
	
	if( is_admin() ) return $current_action;
	
	if(defined('BP_VERSION') && floatval(BP_VERSION) > 1.3) {
		
		$groups_slug = bp_get_groups_root_slug();

		bp_group_hierarchy_debug('Routing requests for BP 1.5');
		bp_group_hierarchy_debug('Current component: ' . $bp->current_component);
		bp_group_hierarchy_debug('Current action: ' . $current_action);
		bp_group_hierarchy_debug('Groups slug: ' . $groups_slug);
		bp_group_hierarchy_debug('Are we on a user profile page?: ' . ( empty($bp->displayed_user->id) ? 'N' : 'Y' ));
	
		if($current_action == '')	return $current_action;
		if(!bp_is_groups_component() || ! empty($bp->displayed_user->id) || in_array($current_action, apply_filters( 'groups_forbidden_names', array( 'my-groups', 'create', 'invites', 'send-invites', 'forum', 'delete', 'add', 'admin', 'request-membership', 'members', 'settings', 'avatar', $groups_slug, '' ) ) ) ) {
			bp_group_hierarchy_debug('Not rewriting current action.');
			return $current_action;
		}
		
		$action_vars = $bp->action_variables;
		
		$old_action = $current_action;
		
		$group = new BP_Groups_Hierarchy( $current_action );
	
		if( ! $group->id && ( ! isset($bp->current_item) || !$bp->current_item) ) {
			$current_action = '';
			bp_group_hierarchy_debug('Redirecting to groups root.');
			bp_core_redirect( $bp->root_domain . '/' . $groups_slug . '/');
		}
		if($group->has_children()) {
			$parent = $group;
			foreach($bp->action_variables as $action_var) {
				$subgroup_id = $parent->check_slug($action_var, $parent->id);
				if($subgroup_id) {
					$action_var = array_shift($action_vars);
					$subgroup = new BP_Groups_Hierarchy( $subgroup_id );
					$current_action = $subgroup->slug;
					$parent = $subgroup;
				} else {
					// once we find something that isn't a group, we're done
					break;
				}
			}
		}
	
		bp_group_hierarchy_debug('Action changed to: ' . $current_action . ' from ' . $old_action);
	
		$bp->action_variables = $action_vars;
		$bp->current_action = $current_action;
	
	}
	return $current_action;
}
add_filter( 'bp_current_action', 'group_hierarchy_override_current_action' );

?>