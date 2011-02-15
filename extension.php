<?php

class BP_Groups_Hierarchy_Extension extends BP_Group_Extension {
	
	var $visibility = 'public';
	
	function bp_groups_hierarchy_extension() {
		
		global $bp;
		
		$this->name = __( 'Group Hierarchy', 'bp-group-hierarchy' );
		$this->nav_item_name = __( 'Member Groups', 'bp-group-hierarchy' );
		$this->slug = 'hierarchy';
		
		if(isset($_COOKIE['bp_new_group_parent_id'])) {
			$bp->group_hierarchy->new_group_parent_id = $_COOKIE['bp_new_group_parent_id'];
			setcookie( 'bp_new_group_parent_id', false, time() - 1000, COOKIEPATH );
			add_action( 'bp_after_group_details_creation_step', array( &$this, 'add_parent_selection' ) );
		}
		$this->create_step_position = 6;
		$this->nav_item_position = 61;

		/** workaround for buddypress bug #2701 */
		if(!$bp->is_item_admin && !is_super_admin()) {
			$this->enable_edit_item = false;	
		}
				
		$this->subgroup_permission_options = array(
			'anyone'		=> __('Anyone','bp-group-hierarchy'),
			'group_members'	=> __('only Group Members','bp-group-hierarchy'),
			'group_admins'	=> __('only Group Admins','bp-group-hierarchy')
		);
		
		if($bp->groups->current_group) {
			$bp->groups->current_group->can_create_subitems = bp_group_hierarchy_can_create_subgroups();
		}
		
		$this->enable_nav_item = $this->enable_nav_item();
				
	}
	
	function get_default_permission_option() {
		return 'group_members';
	}
	
	function enable_nav_item() {
		global $bp;
		
		// Only display the nav item for admins, those who can create subgroups, or everyone if the group has subgroups
		if ($bp->is_item_admin || $bp->groups->current_group->can_create_subitems || BP_Groups_Hierarchy::has_children( $bp->groups->current_group->id )) {
			return true;
		}
		return false;
	}
	
	function add_parent_selection() {
		global $bp;
		if(!bp_is_group_creation_step( 'group-details' )) {
			return false;
		}
		
		$parent_group = new BP_Groups_Hierarchy( $bp->group_hierarchy->new_group_parent_id );
		
		?>
		<label for="group-parent_id"><?php _e( 'Parent Group', 'bp-group-hierarchy' ); ?></label>
		<input type="hidden" name="group-parent_id" id="group-parent_id" value="<?php echo $parent_group->id ?>" />
		<?php echo $parent_group->name ?>
		<?php		
	}
	
	function create_screen() {
		
		global $bp;

		if(!bp_is_group_creation_step( $this->slug )) {
			return false;
		}
		
		$this_group = new BP_Groups_Hierarchy( $bp->groups->new_group_id );

		$groups = BP_Groups_Hierarchy::get_active();
		$exclude_groups = array($bp->groups->new_group_id);
		
		$display_groups = array();
		foreach($groups['groups'] as $group) {
			if(!in_array($group->id,$exclude_groups)) {
				$display_groups[] = $group;
			}
		}
		
		/* deprecated */
		$display_groups = apply_filters( 'bp_group_hierarchy_display_groups', $display_groups );
		
		$display_groups = apply_filters( 'bp_group_hierarchy_available_parent_groups', $display_groups );
		
		?>
		<label for="parent_id"><?php _e( 'Parent Group', 'bp-group-hierarchy' ); ?></label>
		<select name="parent_id" id="parent_id">
			<option value="0"><?php _e( 'Site Root', 'bp-group-hierarchy' ); ?></option>
			<?php foreach($display_groups as $group) { ?>
				<option value="<?php echo $group->id ?>"<?php if($group->id == $this_group->parent_id) echo ' selected'; ?>><?php echo $group->name; ?></option>
			<?php } ?>
		</select>
		<?php

		$subgroup_permission_options = apply_filters( 'bp_group_hierarchy_subgroup_permissions', $this->subgroup_permission_options );
		
		$current_subgroup_permission = groups_get_groupmeta( $bp->groups->current_group->id, 'bp_group_hierarchy_subgroup_creators' );
		if($current_subgroup_permission == '')
			$current_subgroup_permission = $this->get_default_permission_option();
		
		$permission_select = '<select name="allow_children_by" id="allow_children_by">';
		foreach($subgroup_permission_options as $option => $text) {
			$permission_select .= '<option value="' . $option . '"' . (($option == $current_subgroup_permission) ? ' selected' : '') . '>' . $text . '</option>' . "\n";
		}
		$permission_select .= '</select>';
		?>
		<p>
			<label for="allow_children_by"><?php _e( 'Member Groups', 'bp-group-hierarchy' ); ?></label>
			<?php printf( __( 'Allow %1$s to create %2$s', 'bp-group-hierarchy' ), $permission_select, __( 'Member Groups', 'bp-group-hierarchy' ) ); ?>
		</p>
		<?php
		wp_nonce_field( 'groups_create_save_' . $this->slug );
	}
	
	function create_screen_save() {
		global $bp;
		
		check_admin_referer( 'groups_create_save_' . $this->slug );
		
		/** save the selected parent_id */
		$parent_id = (int)$_POST['parent_id'];
		
		if(bp_group_hierarchy_can_create_subgroups( $bp->loggedin_user->id, $parent_id )) {
			$bp->groups->current_group = new BP_Groups_Hierarchy( $bp->groups->new_group_id );
	
			$bp->groups->current_group->parent_id = $parent_id;
			$bp->groups->current_group->save();
		}

		/** save the selected subgroup permission setting */
		$permission_options = apply_filters( 'bp_group_hierarchy_subgroup_permission_options', $this->subgroup_permission_options );
		if(array_key_exists( $_POST['allow_children_by'], $permission_options )) {
			$allow_children_by = $_POST['allow_children_by'];
		} else {
			$allow_children_by = $this->get_default_permission_option();
		}
		
		groups_update_groupmeta( $bp->groups->current_group->id, 'bp_group_hierarchy_subgroup_creators', $allow_children_by );
		
	}
	
	function edit_screen() {

		global $bp;

		if(!bp_is_group_admin_screen( $this->slug )) {
			return false;
		}
		
		if( !is_super_admin() ) {
			?>
			<div id="message">
				<p><?php _e('Only a site administrator can edit the group hierarchy.', 'bp-group-hierarchy' ); ?></p>
			</div>
			<?php
			return false;
		}
		
		$groups = BP_Groups_Hierarchy::get_active();
		$exclude_groups = BP_Groups_Hierarchy::get_by_parent( $bp->groups->current_group->id );
		
		if(count($exclude_groups['groups']) > 0) {
			foreach($exclude_groups['groups'] as $key => $exclude_group) {
				$exclude_groups['groups'][$key] = $exclude_group->id;
			}
			$exclude_groups = $exclude_groups['groups'];
		} else {
			$exclude_groups = array();
		}
		$exclude_groups[] = $bp->groups->current_group->id;
		
		$display_groups = array();
		foreach($groups['groups'] as $group) {
			if(!in_array($group->id,$exclude_groups)) {
				$display_groups[] = $group;
			}
		}
		
		/* deprecated */
		$display_groups = apply_filters( 'bp_group_hierarchy_display_groups', $display_groups );
		
		$display_groups = apply_filters( 'bp_group_hierarchy_available_parent_groups', $display_groups );
		
		?>
		<label for="parent_id"><?php _e( 'Parent Group', 'bp-group-hierarchy' ); ?></label>
		<select name="parent_id" id="parent_id">
			<option value="0"><?php _e( 'Site Root', 'bp-group-hierarchy' ); ?></option>
			<?php foreach($display_groups as $group) { ?>
				<option value="<?php echo $group->id ?>"<?php if($group->id == $bp->groups->current_group->parent_id) echo ' selected'; ?>><?php echo $group->name; ?></option>
			<?php } ?>
		</select>
		<?php
		
		$subgroup_permission_options = apply_filters( 'bp_group_hierarchy_subgroup_permission_options', $this->subgroup_permission_options );
		
		$current_subgroup_permission = groups_get_groupmeta( $bp->groups->current_group->id, 'bp_group_hierarchy_subgroup_creators' );
		if($current_subgroup_permission == '')
			$current_subgroup_permission = $this->get_default_permission_option();
		
		$permission_select = '<select name="allow_children_by" id="allow_children_by">';
		foreach($subgroup_permission_options as $option => $text) {
			$permission_select .= '<option value="' . $option . '"' . (($option == $current_subgroup_permission) ? ' selected' : '') . '>' . $text . '</option>' . "\n";
		}
		$permission_select .= '</select>';
		?>
		<p>
			<label for="allow_children_by"><?php _e( 'Member Groups', 'bp-group-hierarchy' ); ?></label>
			<?php printf( __( 'Allow %1$s to create %2$s', 'bp-group-hierarchy' ), $permission_select, __( 'Member Groups', 'bp-group-hierarchy' ) ); ?>
		</p>
		<p>
			<input type="submit" class="button" id="save" name="save" value="<?php _e( 'Save Changes', 'bp-group-hierarchy' ); ?>" />
		</p>
		<?php
		wp_nonce_field( 'groups_edit_save_' . $this->slug );
	}
	
	function edit_screen_save() {
		global $bp;
		
		if( !isset($_POST['save']) ) {
			return false;
		}
		
		check_admin_referer( 'groups_edit_save_' . $this->slug );

		/** save the selected subgroup permission setting */
		$permission_options = apply_filters( 'bp_group_hierarchy_subgroup_permission_options', $this->subgroup_permission_options );
		if(array_key_exists( $_POST['allow_children_by'], $permission_options )) {
			$allow_children_by = $_POST['allow_children_by'];
		} else if(groups_get_groupmeta( $bp->groups->current_group->id, 'bp_group_hierarchy_subgroup_creators' ) != '') {
			$allow_children_by = groups_get_groupmeta( $bp->groups->current_group->id, 'bp_group_hierarchy_subgroup_creators' );
		} else {
			$allow_children_by = $this->get_default_permission_option();
		}
		
		groups_update_groupmeta( $bp->groups->current_group->id, 'bp_group_hierarchy_subgroup_creators', $allow_children_by );

		
		/** save changed parent_id */
		$parent_id = (int)$_POST['parent_id'];
		
		if( bp_group_hierarchy_can_create_subgroups( $bp->loggedin_user->id, $bp->groups->current_group->id ) ) {
			$bp->groups->current_group->parent_id = $parent_id;
			$success = $bp->groups->current_group->save();
		}
		
		if( !$success ) {
			bp_core_add_message( __( 'There was an error saving; please try again.', 'bp-group-hierarchy' ), 'error' );
		} else {
			bp_core_add_message( __( 'Group hierarchy settings successfully.', 'bp-group-hierarchy' ) );
		}
		
		bp_core_redirect( bp_get_group_admin_permalink( $bp->groups->current_group ) );
		
	}
	
	function display() {
		global $bp;
		$subgroups = new BP_Groups_Hierarchy_Template();
		$subgroups->params = array(
			'type'		=> 'by_parent',
			'parent_id'	=> $bp->groups->current_group->id
		);
		$subgroups->synchronize();
		
		?>
		
		<?php if($bp->is_item_admin || $bp->groups->current_group->can_create_subitems) { ?>
		<div class="generic-button group-button">
			<a title="<?php printf( __( 'Create a %s', 'bp-group-hierarchy' ),__( 'Member Group', 'bp-group-hierarchy' ) ) ?>" href="<?php echo $bp->root_domain . '/' . $bp->groups->slug . '/' . 'create' .'/?parent_id=' . $bp->groups->current_group->id ?>"><?php printf( __( 'Create a %s', 'bp-group-hierarchy' ),__( 'Member Group', 'bp-group-hierarchy' ) ) ?></a>
		</div>
		<?php } ?>
		<ul id="groups-list" class="item-list">
		<?php if($subgroups->group_count > 0) : ?>
			<?php foreach($subgroups->groups as $subgroup) : ?>
			<?php if($subgroup->status == 'hidden' && !( groups_is_user_member( $bp->loggedin_user->id, $subgroup->id ) || groups_is_user_admin( $bp->loggedin_user->id, $bp->groups->current_group->id ) ) ) continue; ?>
			<li>
				<div class="item-avatar">
					<a href="<?php echo bp_get_group_permalink( $subgroup ) ?>"><?php echo bp_group_hierarchy_get_avatar_by_group( 'type=thumb&width=50&height=50', $subgroup ) ?></a>
				</div>
	
				<div class="item">
					<div class="item-title"><a href="<?php echo bp_get_group_permalink( $subgroup ) ?>"><?php echo bp_get_group_name( $subgroup ) ?></a></div>
					<div class="item-meta"><span class="activity"><?php printf( __( 'active %s ago', 'buddypress' ), bp_get_group_last_active( $subgroup ) ) ?></span></div>
	
					<div class="item-desc"><?php echo bp_get_group_description_excerpt( $subgroup ) ?></div>
	
					<?php do_action( 'bp_directory_groups_item' ) ?>
	
				</div>
	
				<div class="action">
	
					<?php do_action( 'bp_directory_groups_actions' ) ?>
	
					<div class="meta">
	
						<?php echo bp_get_group_type( $subgroup ) ?> / <?php echo bp_group_hierarchy_get_group_member_count_by_group( $subgroup ) ?>
	
					</div>
	
				</div>
	
				<div class="clear"></div>
			</li>
	
			<?php endforeach; ?>
		<?php endif; ?>
		</ul>
	<?php
	}
}

bp_register_group_extension( 'BP_Groups_Hierarchy_Extension' );

/**
 * Store the ID of the group the user selected as the parent for group creation
 */
function bp_group_hierarchy_set_parent_id_cookie() {
	global $current_component, $current_action, $action_variables, $bp;

	if($current_component == BP_GROUPS_SLUG && $current_action == 'create' && isset($_REQUEST['parent_id']) && $_REQUEST['parent_id'] != 0) {
		setcookie( 'bp_new_group_parent_id', (int)$_REQUEST['parent_id'], time() + 1000, COOKIEPATH );
	}
}
add_action( 'bp_group_hierarchy_route_requests', 'bp_group_hierarchy_set_parent_id_cookie' );

/**
 * Save the parent id passed from the group creation screen
 */
function bp_group_hierarchy_create_group($group_id, $member, $group ) {
	
	global $bp;
	if(isset($_POST['group-parent_id'])) {
		
		$my_group = new BP_Groups_Hierarchy( $group_id );
		$my_group->parent_id = (int)$_POST['group-parent_id'];
		$my_group->save();
		
	}
	
}
add_action( 'groups_create_group', 'bp_group_hierarchy_create_group', 10, 3 );

/**
 * Check whether the user is allowed to create subgroups of the selected group
 * @param int UserID ID of the user whose access is being checked (or current user if omitted)
 * @param int GroupID ID of the group being checked (or group beign displayed if omitted)
 * @return bool TRUE if permitted, FALSE otherwise
 */
function bp_group_hierarchy_can_create_subgroups( $user_id = null, $group_id = null ) {
	global $bp;

	if(is_null($user_id)) {
		$user_id = $bp->loggedin_user->id;
	}
	if(is_null($group_id)) {
		$group_id = $bp->groups->current_group->id;
	}

	if(is_super_admin()) {
		return true;
	}

	$subgroup_permission = groups_get_groupmeta( $group_id, 'bp_group_hierarchy_subgroup_creators');
	if($subgroup_permission == '') {
		$subgroup_permission = BP_Groups_Hierarchy_Extension::get_default_permission_option();
	}
	switch($subgroup_permission) {
		case 'anyone':
			return true;
			break;
		case 'group_members':
			if(groups_is_user_member( $user_id, $group_id )) {
				return true;
			}
			break;
		case 'group_admins':
			if(groups_is_user_admin( $user_id, $group_id )) {
				return true;
			}
			break;
		default:
			if(
				has_filter('bp_group_hierarchy_enforce_subgroup_permission_' . $subgroup_permission ) && 
				apply_filters( 'bp_group_hierarchy_enforce_subgroup_permission_' . $subgroup_permission, false, $user_id, $group_id )) 
			{
				return true;
			}
			break;
	}
	return false;
}

/**
 * Enforce subgroup creation restrictions in parent group selection boxes
 */
function bp_group_hierarchy_enforce_subgroup_permissions( $groups ) {
	
	global $bp;
	
	/** super admins can add subgroups to any group */
	if(is_super_admin()) {
		return $groups;
	}
	
	if($allowed_groups = wp_cache_get( 'subgroup_creation_permitted_' . $bp->loggedin_user->id, 'bp_group_hierarchy' )) {
		return $allowed_groups;
	}
	
	$allowed_groups = array();
	foreach($groups as $group) {

		if(bp_group_hierarchy_can_create_subgroups( $bp->loggedin_user->id, $group->id )) {
			$allowed_groups[] = $group;
		}

	}
	wp_cache_set( 'subgroup_creation_permitted_' . $bp->loggedin_user->id, $allowed_groups, 'bp_group_hierarchy' );
	return $allowed_groups;
}
add_filter( 'bp_group_hierarchy_available_parent_groups', 'bp_group_hierarchy_enforce_subgroup_permissions' );

?>