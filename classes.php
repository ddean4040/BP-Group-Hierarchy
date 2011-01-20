<?php

/**
 * Hierarchy-aware extension for Groups class
 */
class BP_Groups_Hierarchy extends BP_Groups_Group {

	var $vars = null;
	
	function bp_groups_hierarchy( $id, $parent_id = 0 ) {
		
		global $bp, $wpdb;

		if(!isset($bp->table_prefix)) {
			bp_core_setup_globals();
		}
		if(!isset($bp->groups)) {
			groups_setup_globals();
		}
		
		if(!is_numeric($id)) {
			$id = $this->check_slug( $id, $parent_id );
		}
		
		if ( $id ) {
			$this->id = $id;
			$this->populate();
		}
	}
	
	function populate() {
		global $wpdb, $bp;

		parent::populate();
		if ( $group = $wpdb->get_row( $wpdb->prepare( "SELECT g.* FROM {$bp->groups->table_name} g WHERE g.id = %d", $this->id ) ) ) {
			$this->parent_id = $group->parent_id;
			$this->true_slug = $this->slug;
			$this->path = $this->buildPath();
			$this->slug = $this->path;
		}
	}
	
	function buildPath() {
		
		$path = $this->true_slug;
		if($this->parent_id == 0) {
			return $path;
		}
		
		$parent = (object)array('parent_id'=>$this->parent_id);
		do {
			$parent = new BP_Groups_Hierarchy($parent->parent_id);
			$path = $parent->true_slug . '/' . $path;
		}
		while($parent->parent_id != 0);
		
		return $path;
	}
	
	function save() {
		
		global $bp, $wpdb;
		
		$this->slug = $this->true_slug;
		parent::save();
		
		if($this->id) {
			$sql = $wpdb->prepare(
				"UPDATE {$bp->groups->table_name} SET
					parent_id = %d
				WHERE
					id = %d
				",
				$this->parent_id,
				$this->id
			);
			
			if ( false === $wpdb->query($sql) ) {
				return false;
			}

			if ( !$this->id ) {
				$this->id = $wpdb->insert_id;
			}
			
			$this->path = $this->buildPath();
			$this->slug = $this->path;
			
			return true;
		}
		return false;
	}
	
	function has_children( $id = null) {
		global $bp, $wpdb;
		if(is_null($id)) {
			if(!isset($this->id) || $this->id == 0)	return false;
			$id = $this->id;
		}
		return $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT g.id) FROM {$bp->groups->table_name} g WHERE g.parent_id=%d",$id));
	}
	
	/**
	 * Is the passed group a child of the current object?
	 * @param int ChildGroupID ID of suspected child group
	 */
	function is_child( $group_id ) {
		return $wpdb->get_var($wpdb->prepare("SELECT COUNT(g.id) FROM {$bp->groups->table_name} g WHERE g.parent_id=%d AND g.id = %d",$this->id, $group_id));
	}
	
	function check_slug( $slug, $parent_id ) {
		global $wpdb, $bp;

		if ( !$slug )
			return false;

		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bp->groups->table_name} WHERE slug = %s AND parent_id = %d", $slug, $parent_id ) );
		
	}
	
	function check_slug_stem( $path ) {
		
		global $bp, $wpdb;
		
		if(strpos( $path, '/' )) {
			$path = explode('/',$path);
			$path = $path[count($path)-1];
		}
		if(strlen($path) == 0)	return array();
		
		$slug = esc_sql(like_escape(stripslashes($path)));
		return $wpdb->get_col( "SELECT slug FROM {$bp->groups->table_name} WHERE slug LIKE '$slug%'" );
		
	}
	
	function group_exists( $path, $parent_id = 0 ) {
		
		if(strpos( $path, '/' )) {
			$path = explode('/',$path);
			$parent = $parent_id;
			foreach($path as $slug) {
				if($parent = self::check_slug( $slug, $parent )) {
					// Nothing to see here - keep descending into the path
				} else {
					return false;
				}
			}
			return $parent;
		} else {
			return self::check_slug( $path, $parent_id );
		}
		
	}
	
	function get_id_from_slug( $slug, $parent_id = 0 ) {
		return self::group_exists( $slug, $parent_id );
	}
	
	/**
	 * Get the full path for a group
	 */
	function get_path( $group_id ) {
		$group = new BP_Groups_Hierarchy( $group_id );
		if($group) {
			return $group->path;
		}
		return false;
	}
	
	function get_by_parent( $parent_id, $limit = null, $page = null, $user_id = false, $search_terms = false, $populate_extras = true ) {
		global $wpdb, $bp;

		if ( !is_super_admin() )
			$hidden_sql = $wpdb->prepare( " AND status != 'hidden'");

		if ( $limit && $page ) {
			$pag_sql = $wpdb->prepare( " LIMIT %d, %d", intval( ( $page - 1 ) * $limit), intval( $limit ) );
			$total_groups = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT g.id) FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name} g WHERE g.id = gm1.group_id AND g.id = gm2.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND g.parent_id = $parent_id {$hidden_sql} {$search_sql}" ) );
		}

		$paged_groups = $wpdb->get_results( $wpdb->prepare( "SELECT g.*, gm1.meta_value as total_member_count, gm2.meta_value as last_activity FROM {$bp->groups->table_name_groupmeta} gm1, {$bp->groups->table_name_groupmeta} gm2, {$bp->groups->table_name} g WHERE g.id = gm1.group_id AND g.id = gm2.group_id AND gm2.meta_key = 'last_activity' AND gm1.meta_key = 'total_member_count' AND g.parent_id = $parent_id {$hidden_sql} {$search_sql} ORDER BY g.name ASC {$pag_sql}"  ) );

		foreach ( (array)$paged_groups as $key => $group ) {
			$paged_groups[$key] = new BP_Groups_Hierarchy( $group->id );
		}

		if ( !empty( $populate_extras ) ) {
			foreach ( (array)$paged_groups as $group ) $group_ids[] = $group->id;
			$group_ids = $wpdb->escape( join( ',', (array)$group_ids ) );
			$paged_groups = BP_Groups_Group::get_group_extras( &$paged_groups, $group_ids, 'newest' );
		}

		return array( 'groups' => $paged_groups, 'total' => $total_groups );
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
			call_user_func_array(array(parent,'bp_groups_template'),$args);
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
				wp_cache_set( 'groups_group_nouserdata_' . $group->group_id, $this->group, 'bp' );
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
?>