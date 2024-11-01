<?php


if( ! isset( $user ) && isset( $user_id ) ) {
	$user = get_userdata( $user_id );
}

if( ! is_object( $user ) ) {
	return;
}

?>

<div class="wpc-s-u">
	
	<div class="avatar-c">
		
		<a href="<?php echo wpc_admin_user_link( $user->ID ); ?>">
			<?php echo get_avatar( $user->ID, 55 ); ?>
			<span><?php echo wpc_get_user_name( $user->ID ); ?></span>
		</a>
		&mdash; <em class="wpc-u-status <?php echo wpc_is_online( $user->ID ) ? 'online' : 'offline'; ?>"><?php echo wpc_get_user_activity( $user->ID ); ?></em>
		<div class="bio"><?php echo wpc_get_user_short_bio( $user->ID ); ?></div>

	</div>

	<div class="body-c">

		<div class="actions">

			<?php if( wp_get_current_user()->ID !== $user->ID ) : ?>

				<a href="<?php echo wpc_admin_user_link( $user->ID ); ?>"><span class="dashicons dashicons-admin-tools"></span>Edit user</a>

			<?php endif; ?>

		<a href="<?php echo wpc_get_user_links( $user->ID )->profile; ?>" target="_new"><span class="dashicons dashicons-admin-users"></span>View profile</a>
		<a href="<?php echo wpc_get_user_links( $user->ID )->edit; ?>" target="_new"><span class="dashicons dashicons-admin-tools"></span>Edit profile</a>

		</div>
	
	</div>


</div>