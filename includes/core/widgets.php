<?php 


/*

Welcome widget
Notifications widget
Search users widget
Users widget w/ tabs for online/blocked/all and sorting ~

Profile load widget


*/

class wpc_widgets_welcome extends WP_Widget {
	function __construct() {
		parent::__construct(
			'wpc_widgets_welcome', 
			'WPC Welcome',
			array( 'description' => 'Welcome widget with links and user information' ) 
		);
	}
	public function widget( $args, $instance ) {
		
		if( ! is_user_logged_in() ) { return; }

		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $args['before_widget'];
		echo ! empty( $title ) ? $args['before_title'] . $title . $args['after_title'] : '';

		//include_once wpc_template_path( 'widgets/welcome' );
		?>
		<div class="wpc_lload" data-load="<?php echo admin_url( "admin-ajax.php?action=wpc_lazy_load&part=widgets&widget=welcome"); ?>"></div>
		<?php

		echo $args['after_widget'];
		
	}

	public function form( $instance ) {
		$title = isset( $instance[ 'title' ] ) ? $instance[ 'title' ] : '';
		?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>" style="font-weight:bold;"><?php _e( 'Widget Title:' ); ?></label> 
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
			</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ! empty( $new_instance['title'] ) ? strip_tags( $new_instance['title'] ) : '';
		return $instance;
	}
}

class wpc_widgets_search extends WP_Widget {
	function __construct() {
		parent::__construct(
			'wpc_widgets_search', 
			'WPC search form',
			array( 'description' => 'A search form to quickly search users and messages' ) 
		);
	}
	public function widget( $args, $instance ) {
		
		# if( ! is_user_logged_in() ) { return; }

		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $args['before_widget'];
		echo ! empty( $title ) ? $args['before_title'] . $title . $args['after_title'] : '';

		//include_once wpc_template_path( 'widgets/search' );

		?>
		<div class="wpc_lload" data-load="<?php echo admin_url( "admin-ajax.php?action=wpc_lazy_load&part=widgets&widget=search"); ?>"></div>
		<?php

		echo $args['after_widget'];
		
	}

	public function form( $instance ) {
		$title = isset( $instance[ 'title' ] ) ? $instance[ 'title' ] : '';

		?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>" style="font-weight:bold;"><?php _e( 'Widget Title:' ); ?></label> 
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
			</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ! empty( $new_instance['title'] ) ? strip_tags( $new_instance['title'] ) : '';
		return $instance;
	}
}