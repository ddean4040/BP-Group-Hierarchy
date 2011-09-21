<?php

/* Register widgets for groups component */
add_action('widgets_init', 'bp_group_hierarchy_init_widgets' );

function bp_group_hierarchy_init_widgets() {
	register_widget('BP_Toplevel_Groups_Widget');
//	register_widget('BP_Group_Tree_Widget');
}
add_action( 'bp_register_widgets', 'groups_register_widgets' );

/*** TOPLEVEL GROUPS WIDGET *****************/
class BP_Toplevel_Groups_Widget extends WP_Widget {
	function bp_toplevel_groups_widget() {
		parent::WP_Widget( false, $name = __( 'Toplevel Groups', 'bp-group-hierarchy' ) );
	}

	function widget($args, $instance) {
		global $bp;

	    extract( $args );

		echo $before_widget;
		echo $before_title
		   . $instance['title']
		   . $after_title; ?>

		<?php if ( bp_has_groups_hierarchy( 'type=by_parent&per_page=' . $instance['max_groups'] . '&max=' . $instance['max_groups'] . '&parent_id=0' ) ) : ?>

			<ul id="toplevel-groups-list" class="item-list">
				<?php while ( bp_groups() ) : bp_the_group(); ?>
					<li>
						<div class="item-avatar">
							<a href="<?php bp_group_permalink() ?>"><?php bp_group_avatar_thumb() ?></a>
						</div>

						<div class="item">
							<div class="item-title"><a href="<?php bp_group_permalink() ?>" title="<?php echo strip_tags(bp_get_group_description_excerpt()) ?>"><?php bp_group_name() ?></a></div>
							<?php if(floatval(BP_VERSION) > 1.3) { ?>
							<div class="item-meta"><span class="activity"><?php printf( __( 'active %s', 'buddypress' ), bp_get_group_last_active() ); ?></span></div>
							<?php } else { ?>
							<div class="item-meta"><span class="activity"><?php printf( __( 'active %s ago', 'buddypress' ), bp_get_group_last_active() ) ?></span></div>
							<?php } ?>
							<?php if($instance['show_desc']) { ?>
							<div class="item-desc"><?php bp_group_description_excerpt() ?></div>
							<?php } ?>
						</div>
					</li>

				<?php endwhile; ?>
			</ul>
			<?php wp_nonce_field( 'groups_widget_groups_list', '_wpnonce-groups' ); ?>
			<input type="hidden" name="toplevel_groups_widget_max" id="toplevel_groups_widget_max" value="<?php echo esc_attr( $instance['max_groups'] ); ?>" />

		<?php else: ?>

			<div class="widget-error">
				<?php _e('There are no groups to display.', 'buddypress') ?>
			</div>

		<?php endif; ?>

		<?php echo $after_widget; ?>
	<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['max_groups'] = strip_tags( $new_instance['max_groups'] );
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['show_desc'] = isset($new_instance['show_desc']) ? true : false;

		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'max_groups' => 5, 'title'	=> __('Groups') ) );
		$max_groups = strip_tags( $instance['max_groups'] );
		$title = strip_tags( $instance['title'] );
		$show_desc = $instance['show_desc'] ? true : false;
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'buddypress'); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></label>
			<label for="<?php echo $this->get_field_id( 'max_groups' ); ?>"><?php _e('Max groups to show:', 'buddypress'); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'max_groups' ); ?>" name="<?php echo $this->get_field_name( 'max_groups' ); ?>" type="text" value="<?php echo esc_attr( $max_groups ); ?>" style="width: 30%" /></label>
			<br /><label for="<?php echo $this->get_field_id( 'show_desc' ); ?>"><input type="checkbox" id="<?php echo $this->get_field_id( 'show_desc' ); ?>" name="<?php echo $this->get_field_name( 'show_desc' ); ?>"<?php if($show_desc) echo ' checked'; ?> /> <?php _e('Show descriptions:', 'bp-group-hierarchy'); ?></label>
		</p>
	<?php
	}
}
?>
