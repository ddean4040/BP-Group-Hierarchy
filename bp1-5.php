<?php
/**
 * Functions for BuddyPress 1.5 compatibility
 */

/**
 * Catch requests for the groups component and find the requested group
 */
function group_hierarchy_override_current_action( $current_action ) {
	global $bp;

	if(defined('BP_VERSION') && floatval(BP_VERSION) > 1.3) {

		$groups_slug = bp_get_groups_hierarchy_root_slug();

		bp_group_hierarchy_debug('Routing requests for BP 1.5');
		bp_group_hierarchy_debug('Current component: ' . $bp->current_component);
		bp_group_hierarchy_debug('Current action: ' . $current_action);
		bp_group_hierarchy_debug('Groups slug: ' . $groups_slug);
	
		if($current_action == '')	return $current_action;
		if($bp->current_component != $groups_slug || in_array($current_action, apply_filters( 'groups_forbidden_names', array( 'my-groups', 'create', 'invites', 'send-invites', 'forum', 'delete', 'add', 'admin', 'request-membership', 'members', 'settings', 'avatar', $groups_slug, '' ) ) ) ) {
			bp_group_hierarchy_debug('Not rewriting current action.');
			return $current_action;
		}
	
		$action_vars = $bp->action_variables;
	
		$group = new BP_Groups_Hierarchy( $current_action );
	
		if(!$group->id && (!isset($bp->current_item) || !$bp->current_item)) {
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
	
		bp_group_hierarchy_debug('Action changed to: ' . $current_action);
	
		$bp->action_variables = $action_vars;
		$bp->current_action = $current_action;
	
	}
	return $current_action;
}
add_filter( 'bp_current_action', 'group_hierarchy_override_current_action' );

function bp_group_hierarchy_override_component_routing() {
	global $bp;
	require_once dirname(__FILE__) . '/bp-groups-hierarchy-component.php';
	$bp->groups = new BP_Groups_Hierarchy_Component();
	$bp->groups->setup_globals();
}

function bp_group_hierarchy_remove_default_globals_setup() {
	global $bp, $wp_filter;

	/** Can't get the right instance of BP_Groups_Component to remove the action; have to do it the hard way */
	foreach($wp_filter['bp_setup_globals'][10] as $filter => $properties) {
		if(is_a($properties['function'][0],'BP_Groups_Component')) {
			unset($wp_filter['bp_setup_globals'][10][$filter]);
		}
	}
	remove_action('bp_setup_globals',array($bp->groups,'setup_globals'));
	add_action('bp_setup_globals','bp_group_hierarchy_override_component_routing');
}
add_action('bp_groups_setup_actions','bp_group_hierarchy_remove_default_globals_setup');

?>