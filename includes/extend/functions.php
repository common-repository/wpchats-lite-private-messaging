<?php

add_action('wpc_after_user_profile', function( $user_id ) {

	$post_type = 'post';
	$max_items = 10;
	// user posts
	$posts = get_transient( "wpc_wp_query_profile_items_{$post_type}_{$user_id}_transient" );
	if ( false === $posts ) {
		$args = array(
		    'author' => $user_id,
		    'posts_per_page' => $max_items,
		    'post_type' => $post_type
	    );
	    $posts = get_posts( $args );
	    set_transient( "wpc_wp_query_profile_items_{$post_type}_{$user_id}_transient", $posts, 60*60*24 );
	}

	// user bbPress topics if bbPress is there
	$bbpress = in_array( 'bbpress/bbpress.php', (array) get_option( 'active_plugins' ) );
	if( $bbpress ) {
		$topics = get_transient( "wpc_wp_query_profile_items_topic_{$user_id}_transient" );
		if ( false === $topics ) {
			$args = array(
			    'author' => $user_id,
			    'posts_per_page' => $max_items,
			    'post_type' => 'topic'
		    );
		    $topics = get_posts( $args );
		    set_transient( "wpc_wp_query_profile_items_topic_{$user_id}_transient", $topics, 60*60*24 );
		}
	}

    // In the markup I had to use p w/ <br/> you could use <ol> <li .. </ol> and remove $i
    ?>

   		<div id="user-posts">

   			<h4>My recent posts:</h4>

	   		<?php if( ! empty( $posts ) ) : ?>

		   		<p><?php foreach( $posts as $i => $post ) :; $i++ ?>
			    	
			    	<?php echo sprintf(
			    		'%3$d. <a href="%1$s" title="%2$s">%2$s</a> &mdash; <em>%4$s</em><br/>',
			    		get_the_permalink( $post->ID ),
			    		get_the_title( $post->ID ),
			    		$i,
			    		sprintf( wpc_translate( "%s ago" ), wpc_time_diff( strtotime( $post->post_date ) ) )
			    	); ?>

			    <?php endforeach; ?></p>

			    <p><a href="<?php echo get_author_posts_url( $user_id ); ?>">More posts by <?php echo wpc_get_user_name( $user_id, 1 ); ?> &raquo;</a></p>

		    <?php else : ?>

		    	<p>This user has not published any posts yet.</p>

		    <?php endif; ?>

		</div>

		<?php if( $bbpress ) { ?>

		<div id="user-topics">
			
   			<h4>My recent topics:</h4>

			<?php if( ! empty( $topics ) ) : ?>

		   		<p><?php foreach( $topics as $i => $post ) :; $i++ ?>
			    	
			    	<?php echo sprintf(
			    		'%3$d. <a href="%1$s" title="%2$s">%2$s</a> &mdash; <em>%4$s</em><br/>',
			    		get_the_permalink( $post->ID ),
			    		get_the_title( $post->ID ),
			    		$i,
			    		sprintf( wpc_translate( "%s ago" ), wpc_time_diff( strtotime( $post->post_date ) ) )
			    	); ?>

			    <?php endforeach; ?></p>

			    <p><a href="<?php echo bbp_user_topics_created_url( $user_id ); ?>">More topics by <?php echo wpc_get_user_name( $user_id, 1 ); ?> &raquo;</a></p>

		    <?php else : ?>

		    	<p>This user has not published any topics yet.</p>

		    <?php endif; ?>

		</div>

		<?php } ?>

    <?php

});

add_action( 'transition_post_status', function( $new_status, $old_status, $post ) { 
    if ( $new_status == 'publish' && $old_status == 'auto-draft' ) {
        delete_transient( "wpc_wp_query_profile_items_{$post->post_type}_{$user_id}_transient" );
    }
    return;
}, 10, 3);

add_filter('wpc_social_list', function( $array ) {
	$array['facebook'] = 'Facebook';
	$array['instagram'] = 'Instagram';
	$array['youtube'] = 'YouTube';
	$array['soundcloud'] = 'SoundCloud';
	$array['pinterest'] = 'Pinterest';
	$array['email'] = 'Email';
	return $array;
});

add_filter( "wpc_get_user_social", function( $list ) {

	foreach( $list as $i => $item ) {
		
		if( ! empty( $item['name'] ) ) {
			switch( strtolower( $item['name'] ) ) {

				case 'email':
					if( mb_strpos( $list[$i]['value'], "@" ) ) {
						$list[$i]['value'] = "mailto:{$list[$i]['value']}";
					} else {
						if( ! is_numeric( mb_strpos( $list[$i]['value'], "http" ) ) ) {
							$list[$i]['value'] = "http://{$list[$i]['value']}";
						}
					}
					break;
				
				default:
					break;
			}
		}

	}

	return $list;

});

add_action('wpc_update_user_profile', function() {
	if( ! isset( $_REQUEST['_wpc_updating_basic-info'] ) ) { return; }
	$user_id = wpc_get_displayed_user_id();
	if( ! empty( $_POST['date_of_birth'] ) ) {
		update_user_meta( $user_id, 'wpc_date_of_birth', sanitize_text_field( $_POST['date_of_birth'] ) );
	}
});

add_action('wpc_profile_edit_after_basic_info', function() {
	$dob = get_user_meta( wpc_get_displayed_user_id(), 'wpc_date_of_birth', 1 );
	?>
		<label><strong>Date of birth:</strong><br/>
			<input type="date" name="date_of_birth" style="width:250px!important;" value="<?php echo $dob; ?>" />
		</label><br/>
	<?php
});

add_action('wpc_user_profile_before_buttons', function( $user_id ) {
	$dob = get_user_meta( $user_id, 'wpc_date_of_birth', 1 );
	if( ! $dob ) { return; }
	$dob = wpc_time_diff( strtotime( $dob ) );
	if( ! mb_strpos($dob, wpc_translate('year')) ) { return; }
	$dob = (int) $dob;
	if( ! $dob ) { return; }
	?>
		<span class="wpc-date-of-birth"><?php echo sprintf( "%d y/o", $dob ); ?></span>
	<?php
});

add_action('wpc_after_loop_user_name_link', function( $user_id ) {
	$dob = get_user_meta( $user_id, 'wpc_date_of_birth', 1 );
	if( ! $dob ) { return; }
	$dob = wpc_time_diff( strtotime( $dob ) );
	if( ! mb_strpos($dob, wpc_translate('year')) ) { return; }
	$dob = (int) $dob;
	if( ! $dob ) { return; }
	echo sprintf( "%d y/o", $dob );
});



/*****
add_action('wpc_update_user_profile', function() {

	if( ! isset( $_REQUEST['_wpc_updating_basic-info'] ) ) {
		return; // let's mind our tab :D
	}

	$user_id = wpc_get_displayed_user_id();

	if( isset( $_POST['cell_num'] ) ) {
		update_user_meta( $user_id, 'cell_num', sanitize_text_field( $_POST['cell_num'] ) );
	}

});

add_action('wpc_profile_edit_after_basic_info', function() {
	
	$cell_num = get_user_meta( wpc_get_displayed_user_id(), 'cell_num', TRUE );

	?>

		<label><strong>Your cell num:</strong><br/>
			<input type="number" name="cell_num" style="width:250px!important;" value="<?php echo $cell_num > '' ? $cell_num : ''; ?>" />
		</label><br/>

	<?php

});

add_action('wpc_after_user_profile', function() {
	
	$user_id = wpc_get_displayed_user_id();

	$cell_num = get_user_meta( $user_id, 'cell_num', TRUE );

	if( ! ( (int) $cell_num > 0 ) )
		return;

	?>
		<div id="cell-num">
			<h4>My cell number:</h4>
			<div>+212 <?php echo $cell_num; ?></div>
		</div>

	<?php

});


add_filter('wpc_profile_edit_groups', function( $groups ) {

	$age_value = get_user_meta( wpc_get_displayed_user_id(), 'my_age', TRUE );
	$address_value = get_user_meta( wpc_get_displayed_user_id(), 'my_address', TRUE );

	ob_start();

	?>

	<p>In this tab you will be able to update other settings extending through a plugin.</p>

	<label>
		<strong>Your age:</strong><br/>
		<input type="number" name="my_age" value="<?php echo $age_value; ?>" />
	</label>
	<br/>
	<label>
		<strong>Your home address:</strong><br/>
		<input type="text" name="my_address" value="<?php echo $address_value; ?>" />
	</label>

	<?php

	$html = ob_get_clean();

	$groups[] =  array(
		'name' => 'other',
		'slug' => 'other',
		'title' => 'Other settings',
		'html' => $html
	);

	/**
	  * Resorting groups
	  * Allows you to set the primary group to show on the main edit profile page
	  *

	$first = $groups[0];
	$second = $groups[1];

	$groups[0] = $second;
	$groups[1] = $first;

	  */

	/****return $groups;

});

add_action('wpc_update_user_profile', function( $user_id, $request ) {

	if( ! isset( $_REQUEST['_wpc_updating_other'] ) ) {
		return; // let's mind our tab :D
	}

	if( isset( $_POST['my_age'] ) ) {
		update_user_meta( $user_id, 'my_age', sanitize_text_field( $_POST['my_age'] ) );
	}

	if( isset( $_POST['my_address'] ) ) {
		update_user_meta( $user_id, 'my_address', sanitize_text_field( $_POST['my_address'] ) );
	}

}, 10, 2);

add_action('wpc_after_user_profile', function( $user_id ) {

	$age_value = get_user_meta( wpc_get_displayed_user_id(), 'my_age', TRUE );
	$address_value = get_user_meta( wpc_get_displayed_user_id(), 'my_address', TRUE );

	?>

	<?php if ( ! empty( $age_value ) ) : ?>
		<div>
			<h4>Age</h4>
			<p>My age is <?= $age_value; ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $address_value ) ) : ?>
		<div>
			<h4>Address</h4>
			<p>My address is "<?php echo $address_value; ?>"</p>
		</div>
	<?php endif; ?>

	<?php


});*/


/**
  * Adding couple profile tabs for our users
  * uncomment the following lines of code to
  * add your first wpchats custom profile tab!
  */

/* !! remove this line to uncomment, and next
add_action('init', function() {

	$args = array(
		'slug'		=> 'meow',
		'name' 		=> 'meow',
		'title' 	=> 'test tab',
		'content'	=> '<h1>test tab!</h1><p>mlem mlem mlem mlem mlem mlem mlem mlem mlem mlem mlem mlem ..</p>'
	);
	wpc_create_profile_tab( $args );

});
!! remove this line to uncomment */