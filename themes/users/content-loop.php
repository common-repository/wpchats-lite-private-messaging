<?php

// prevent direct access
defined('ABSPATH') || exit;

?>

<?php require wpc_template_path( 'users/content-header' ); ?>

<div class="wpc-u-archive">

	<?php do_action('wpc_before_loop_users'); ?>
	
	<?php if( count( $get_users = wpc_get_users() ) > 0 ) : ?>

		<?php foreach ( $get_users as $user ) : ?>

			<?php require wpc_template_path( 'users/loop-single' ); ?>

		<?php endforeach; ?>

	<?php else : ?>

		<div class="wpc-no-users"><?php echo apply_filters( 'wpc_no_users', 'There are no users to show' ); ?></div>

	<?php endif; ?>

	<?php wpc_users_pagination(); ?>

	<?php wpc_users_sorting_menu(); ?>

	<select class="wpc-u-switch" onchange="wpcUSwitch(this)" data-q="<?php echo wpc_get_search_query() > '' ? stripslashes(wpc_get_search_query()) : ''; ?>">
		<option>filter</option>
		<option<?php echo wpc_is_archive_users() ? ' selected="selected"' : ''; ?>>all</option>
		<option<?php echo wpc_is_archive_online_users() ? ' selected="selected"' : ''; ?>>online</option>
		<?php if( is_user_logged_in() ) : ?>
			<option<?php echo wpc_is_archive_blocked_users() ? ' selected="selected"' : ''; ?>>blocked</option>
		<?php endif; ?>
	</select>



</div>