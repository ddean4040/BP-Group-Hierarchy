<?php

class BP_Groups_Hierarchy_Extension extends BP_Group_Extension {
	
	var $visibility = 'public';
	
	function bp_groups_hierarchy_extension() {
		
		global $bp;
		
		$this->name = __( 'Parent Group', 'bp-group-hierarchy' );
		$this->nav_item_name = __( 'Member Groups', 'bp-group-hierarchy' );
		$this->slug = 'hierarchy';
		
		$this->create_step_position = 6;
		$this->nav_item_position = 61;

		/** workaround for buddypress bug #2701 */
		if(!$bp->is_item_admin && !is_super_admin()) {
			$this->enable_edit_item = false;	
		}
		
		$this->enable_nav_item = $this->enable_nav_item();
	}
	
	function enable_nav_item() {
		global $bp;
		
		// Only display the nav item if the group has child groups
		if (BP_Groups_Hierarchy::has_children( $bp->groups->current_group->id )) {
			return true;
		}
		return false;
	}
	
	function create_screen() {
		
		global $bp;

		if(!bp_is_group_creation_step( $this->slug )) {
			return false;
		}
		
		$this_group = new BP_Groups_Hierarchy( $bp->groups->new_group_id );

		$groups = BP_Groups_Group::get_active();

		$exclude_groups = array($bp->groups->new_group_id);
		
		$display_groups = array();
		foreach($groups['groups'] as $group) {
			if(!in_array($group->id,$exclude_groups)) {
				$display_groups[] = $group;
			}
		}
		
		$display_groups = apply_filters( 'bp_group_hierarchy_display_groups', $display_groups );
		
		?>
		<label for="parent_id"><?php _e( 'Parent Group', 'bp-group-hierarchy' ); ?></label>
		<select name="parent_id" id="parent_id">
			<option value="0"><?php _e( 'Site Root', 'bp-group-hierarchy' ); ?></option>
			<?php foreach($display_groups as $group) { ?>
				<option value="<?php echo $group->id ?>"<?php if($group->id == $this_group->parent_id) echo ' selected'; ?>><?php echo $group->name; ?></option>
			<?php } ?>
		</select>
		<?php
		wp_nonce_field( 'groups_create_save_' . $this->slug );
	}
	
	function create_screen_save() {
		global $bp;
		
		check_admin_referer( 'groups_create_save_' . $this->slug );
		
		/** save the selected parent_id */
		$parent_id = (int)$_POST['parent_id'];
		
		$bp->groups->current_group = new BP_Groups_Hierarchy( $bp->groups->new_group_id );
		$bp->groups->current_group->parent_id = $parent_id;
		$bp->groups->current_group->save();
		
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
		
		$groups = BP_Groups_Group::get_active();
		
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
		
		$display_groups = apply_filters( 'bp_group_hierarchy_display_groups', $display_groups );
		
		?>
		<label for="parent_id"><?php _e( 'Parent Group', 'bp-group-hierarchy' ); ?></label>
		<select name="parent_id" id="parent_id">
			<option value="0"><?php _e( 'Site Root', 'bp-group-hierarchy' ); ?></option>
			<?php foreach($display_groups as $group) { ?>
				<option value="<?php echo $group->id ?>"<?php if($group->id == $bp->groups->current_group->parent_id) echo ' selected'; ?>><?php echo $group->name; ?></option>
			<?php } ?>
		</select>
		<input type="submit" class="button" name="save" value="<?php _e( 'Change Parent Group', 'bp-group-hierarchy' ); ?>" />
		<?php
		wp_nonce_field( 'groups_edit_save_' . $this->slug );
	}
	
	function edit_screen_save() {
		global $bp;
		
		if( !isset($_POST['save']) ) {
			return false;
		}
		
		check_admin_referer( 'groups_edit_save_' . $this->slug );
		
		/** save changed parent_id */
		
		$parent_id = (int)$_POST['parent_id'];
		
		$bp->groups->current_group->parent_id = $parent_id;
		$success = $bp->groups->current_group->save();
		
		if( !$success ) {
			bp_core_add_message( __( 'There was an error saving; please try again.', 'bp-group-hierarchy' ), 'error' );
		} else {
			bp_core_add_message( __( 'Group parent saved successfully.', 'bp-group-hierarchy' ) );
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
		
		<ul id="groups-list" class="item-list">
		<?php if($subgroups->group_count > 0) : ?>
			<?php foreach($subgroups->groups as $subgroup) : ?>
			<?php if($subgroup->status != 'public') continue; ?>
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

?>