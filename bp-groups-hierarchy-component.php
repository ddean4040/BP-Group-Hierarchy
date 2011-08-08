<?php

class BP_Groups_Hierarchy_Component extends BP_Groups_Component {
	
	/**
	 * A hierarchy-aware copy of the _setup_globals function from BP_Groups_Component
	 */
	function _setup_globals() {
		global $bp;

		// Define a slug, if necessary
		if ( !defined( 'BP_GROUPS_SLUG' ) )
			define( 'BP_GROUPS_SLUG', $this->id );

		// Global tables for messaging component
		$global_tables = array(
			'table_name'           => $bp->table_prefix . 'bp_groups',
			'table_name_members'   => $bp->table_prefix . 'bp_groups_members',
			'table_name_groupmeta' => $bp->table_prefix . 'bp_groups_groupmeta'
		);

		// All globals for messaging component.
		// Note that global_tables is included in this array.
		$globals = array(
			'slug'                  => BP_GROUPS_SLUG,
			'root_slug'             => isset( $bp->pages->groups->slug ) ? $bp->pages->groups->slug : BP_GROUPS_SLUG,
			'notification_callback' => 'groups_format_notifications',
			'search_string'         => __( 'Search Groups...', 'buddypress' ),
			'global_tables'         => $global_tables
		);

		call_user_func(array(get_parent_class(get_parent_class($this)),'setup_globals'), $globals );
//		$this->_base_setup_globals( $globals );

		/** Single Group Globals **********************************************/

		// Are we viewing a single group?
		if ( bp_is_groups_component() && $group_id = BP_Groups_Hierarchy::group_exists( bp_current_action() ) ) {
			
			$bp->is_single_item  = true;
			$this->current_group = new BP_Groups_Hierarchy( $group_id );

			// When in a single group, the first action is bumped down one because of the
			// group name, so we need to adjust this and set the group name to current_item.
			$bp->current_item   = isset( $bp->current_action )      ? $bp->current_action      : false;
			$bp->current_action = isset( $bp->action_variables[0] ) ? $bp->action_variables[0] : false;
			array_shift( $bp->action_variables );

			// Using "item" not "group" for generic support in other components.
			if ( is_super_admin() )
				bp_update_is_item_admin( true, 'groups' );
			else
				bp_update_is_item_admin( groups_is_user_admin( $bp->loggedin_user->id, $this->current_group->id ), 'groups' );

			// If the user is not an admin, check if they are a moderator
			if ( !bp_is_item_admin() )
				bp_update_is_item_mod  ( groups_is_user_mod  ( $bp->loggedin_user->id, $this->current_group->id ), 'groups' );

			// Is the logged in user a member of the group?
			if ( ( is_user_logged_in() && groups_is_user_member( $bp->loggedin_user->id, $this->current_group->id ) ) )
				$this->current_group->is_user_member = true;
			else
				$this->current_group->is_user_member = false;

			// Should this group be visible to the logged in user?
			if ( 'public' == $this->current_group->status || $this->current_group->is_user_member )
				$this->current_group->is_visible = true;
			else
				$this->current_group->is_visible = false;

			// If this is a private or hidden group, does the user have access?
			if ( 'private' == $this->current_group->status || 'hidden' == $this->current_group->status ) {
				if ( $this->current_group->is_user_member && is_user_logged_in() || is_super_admin() )
					$this->current_group->user_has_access = true;
				else
					$this->current_group->user_has_access = false;
			} else {
				$this->current_group->user_has_access = true;
			}

		// Set current_group to 0 to prevent debug errors
		} else {
			$this->current_group = 0;
		}

		// Illegal group names/slugs
		$this->forbidden_names = apply_filters( 'groups_forbidden_names', array(
			'my-groups',
			'create',
			'invites',
			'send-invites',
			'forum',
			'delete',
			'add',
			'admin',
			'request-membership',
			'members',
			'settings',
			'avatar',
			$this->slug,
			$this->root_slug,
		) );

		// If the user was attempting to access a group, but no group by that name was found, 404
		if ( bp_is_groups_component() && empty( $this->current_group ) && !empty( $bp->current_action ) && !in_array( $bp->current_action, $this->forbidden_names ) ) {
			bp_do_404();
			return;
		}
		
		
		// Group access control
		if ( bp_is_groups_component() && !empty( $this->current_group ) && !empty( $bp->current_action ) && !$this->current_group->user_has_access ) {
			if ( is_user_logged_in() ) {
				// Off-limits to this user. Throw an error and redirect to the
				// group's home page
				bp_core_no_access( array(
					'message'	=> __( 'You do not have access to this group.', 'buddypress' ),
					'root'		=> bp_get_group_permalink( $bp->groups->current_group ),
					'redirect'	=> false
				) );
			} else {
				// Allow the user to log in
				bp_core_no_access();
			}
		}

		// Preconfigured group creation steps
		$this->group_creation_steps = apply_filters( 'groups_create_group_steps', array(
			'group-details'  => array(
				'name'       => __( 'Details',  'buddypress' ),
				'position'   => 0
			),
			'group-settings' => array(
				'name'       => __( 'Settings', 'buddypress' ),
				'position'   => 10
			),
			'group-avatar'   => array(
				'name'       => __( 'Avatar',   'buddypress' ),
				'position'   => 20 ),
		) );

		// If friends component is active, add invitations
		if ( bp_is_active( 'friends' ) ) {
			$this->group_creation_steps['group-invites'] = array(
				'name'     => __( 'Invites', 'buddypress' ),
				'position' => 30
			);
		}

		// Groups statuses
		$this->valid_status = apply_filters( 'groups_valid_status', array(
			'public',
			'private',
			'hidden'
		) );

		// Auto join group when non group member performs group activity
		$this->auto_join = defined( 'BP_DISABLE_AUTO_GROUP_JOIN' );
	}
	
	function _base_setup_globals( $args = '' ) {
		global $bp;

		/** Slugs *************************************************************/

		$defaults = array(
			'slug'                  => '',
			'root_slug'             => '',
			'notification_callback' => '',
			'search_string'         => '',
			'global_tables'         => ''
		);
		$r = wp_parse_args( $args, $defaults );

		// Slug used for permalinks
		$this->slug          = apply_filters( 'bp_' . $this->id . '_slug',          $r['slug']          );

		// Slug used for root directory
		$this->root_slug     = apply_filters( 'bp_' . $this->id . '_root_slug',     $r['root_slug']     );

		// Search string
		$this->search_string = apply_filters( 'bp_' . $this->id . '_search_string', $r['search_string'] );

		// Notifications callback
		$this->notification_callback = apply_filters( 'bp_' . $this->id . '_notification_callback', $r['notification_callback'] );

		// Setup global table names
		if ( !empty( $r['global_tables'] ) )
			foreach ( $r['global_tables'] as $global_name => $table_name )
				$this->$global_name = $table_name;
		
		/** BuddyPress ********************************************************/
		
		// Register this component in the active components array
		$bp->loaded_components[$this->slug] = $this->id;

		// Call action
		do_action( 'bp_' . $this->id . '_setup_globals' );
	}

}

?>