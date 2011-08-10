<?php

/**
 * Hierarchy-aware extension for Groups template class
 */
class BP_Groups_Hierarchy_Template extends BP_Groups_Template {

	var $vars = array();

	function bp_groups_hierarchy_template( ) {
		$args = func_get_args();
		if(is_array($args) && count($args) > 1) {
			list(
				$params['user_id'],
				$params['type'],
				$params['page'],
				$params['per_page'],
				$params['max'],
				$params['slug'],
				$params['search_terms'],
				$params['populate_extras'],
				$params['parent_id']
			) = $args;
			$this->params = $params;
			
			/** add extra args that BP 1.5 expects */
			if(floatval(BP_VERSION) > 1.3) {
				array_push($args, '');
				array_push($args, '');
			}
						
			call_user_func_array(array('parent','bp_groups_template'),$args);
			$this->synchronize();
		} else {
			$this->params = array();
		}
	}

	/**
	 * Since we don't always have access to the params passed to BP_Groups_Template
	 * we have to wait until after constructor has run to fill in details
	 */
	function synchronize() {
		global $bp;
		
		if(isset($this->params) && array_key_exists('parent_id',$this->params)) {
	
			/**
			 * Fill in requests by parent_id for tree traversal on admin side
			 */
			$this->groups = bp_group_hierarchy_get_by_hierarchy($this->params);
			$this->groups = $this->groups['groups'];
			$this->group_count = count($this->groups);
			$this->total_group_count = count($this->groups);
			
		} else if($this->single_group && $bp->groups->current_group) {
			/**
			 * Groups with multi-level slugs are missed by the parent.
			 * Fill them in from $bp->groups->current_group
			 */
			$this->groups = array(
				(object)array(
					'group_id'	=> $bp->groups->current_group->id
				)
			);
			$this->group_count = 1;
		}
		
	}

	function the_group() {
		global $group;

		$this->in_the_loop = true;
		$this->group = $this->next_group();

		if ( $this->single_group )
			$this->group = new BP_Groups_Hierarchy( $this->group->group_id );
		else {
			if ( $this->group )
				wp_cache_set( 'groups_group_nouserdata_' . $this->group->id, $this->group, 'bp' );
		}

		if ( 0 == $this->current_group ) // loop has just started
			do_action('loop_start');
	}

	function __isset($varName) {
		return array_key_exists($varName,$this->vars);
	}
	
	function __set($varName, $value) {
		$this->vars[$varName] = $value;
	}
	
	function __get($varName) {
		return $this->vars[$varName];
	}

}

/****************************************
 * Functions for use by theme developers
 ****************************************/

/**
 * Get the fully-qualified name of the group (including all parents)
 */
function bp_group_hierarchy_full_name() {
	echo bp_group_hierarchy_get_full_name();
}
function bp_get_group_hierarchy_full_name( $separator = '|', $group = false ) {
	_deprecated_function( __FUNCTION__, '1.1.8', 'bp_group_hierarchy_get_full_name()' );
	return bp_group_hierarchy_get_full_name( $separator, $group );
}
function bp_group_hierarchy_get_full_name( $separator = '|', $group = false ) {
	global $groups_template;
	
	if ( !$group ) {
		/** need a copy since we're going to walk up the tree */
		$group = $groups_template->group;
	}
	$group_name = $group->name;
	
	while($group->parent_id != 0) {
		$group = new BP_Groups_Hierarchy($group->parent_id);
		$group_name = $group->name . ' ' . $separator . ' ' . $group_name;
	}
	
	return $group_name;
}

/**
 * Get breadcrumbs for a theme
 */
 function bp_group_hierarchy_breadcrumbs() {
 	echo bp_group_hierarchy_get_breadcrumbs();
 }
function bp_group_hierarchy_get_breadcrumbs( $separator = '|', $group = false ) {
	global $groups_template;
	
	$groups_slug = bp_get_groups_hierarchy_root_slug();
	
	if ( !$group ) {
		/** need a copy since we're going to walk up the tree */
		$group = $groups_template->group;
	}
	$group_name = '<a href="/' . $groups_slug . '/' . $group->slug . '" title="' . $group->name . '">' . $group->name . '</a>';
	
	while($group->parent_id != 0) {
		$group = new BP_Groups_Hierarchy($group->parent_id);
		$group_name = '<a href="/' . $groups_slug . '/' . $group->slug . '" title="' . $group->name . '">' . $group->name . '</a> ' . $separator . ' ' . $group_name;
	}
	
	return $group_name;
}
 

/**
 * Get the number of subgroups
 */
function bp_group_hierarchy_has_subgroups( $group = null ) {
	global $groups_template;
	
	if ( !$group ) {
		$group =& $groups_template->group;
	}

	return count(BP_Groups_Hierarchy::has_children( $group->id ));
}

function bp_group_hierarchy_has_parent( $group = null ) {
	global $groups_template;
	if ( !$group ) {
		$group =& $groups_template->group;
	}
	
	return $group->parent_id != 0;
}

/**
 * Return an array of the selected group's ancestors
 * For top-level groups, this array is empty
 * You can count the elements in the array to find the depth
 */
function bp_group_hierarchy_get_parents( $group = null ) {
	global $groups_template;
	
	if ( !$group ) {
		/** need a copy since we're going to walk up the tree */
		$group = $groups_template->group;
	}
	
	$parents = array();
	
	while($group->parent_id != 0) {
		$parents[] = $group->parent_id;
		$group = new BP_Groups_Hierarchy($group->parent_id);
	}
	
	return $parents;
}

?>