<?php
// prevent direct access
defined('ABSPATH') || exit;

$user_links = wpc_get_user_links();

if( wpc_is_archive_blocked_users() ) {
	$cmodal_data = array(
		'exit_title' => wpc_translate('Users') . ' &rsaquo; ' . wpc_translate('blocked'),
		'exit_href' => $user_links->users->blocked
	);
}
elseif( wpc_is_archive_online_users() ) {
	$cmodal_data = array(
		'exit_title' => wpc_translate('Users') . ' &rsaquo; ' . wpc_translate('online'),
		'exit_href' => $user_links->users->online
	);
}
else {
	$cmodal_data = array(
		'exit_title' => wpc_translate('Users'),
		'exit_href' => $user_links->users->all
	);
}

$user_links = wpc_get_user_links( $user->ID );

?>

<div class="<?php wpc_user_snippet_classes( $user->ID ); ?>">
	
	<div class="user-info" itemprop="about" itemscope itemtype="http://schema.org/Person">

		<div class="user-snippet-intro">
			
			<a href="<?php echo $user_links->profile; ?>" class="wpcajx" data-action="view-profile" data-slug="<?php echo $user->user_nicename; ?>"><?php echo str_replace( 'class=', 'itemprop="image" class=', get_avatar( $user->ID, 66 ) ); ?></a>
				
		</div>

		<div class="user-snippet-details">
		
			<div class="user-nicename">
				<?php do_action('wpc_before_loop_user_name', $user->ID); ?>
				<a href="<?php echo $user_links->profile; ?>" class="wpcajx" data-action="view-profile" data-slug="<?php echo $user->user_nicename; ?>">
					<strong itemprop="name"><?php echo wpc_get_user_name( $user->ID ); ?></strong>
				</a>
				<?php do_action('wpc_after_loop_user_name_link', $user->ID); ?>
				&mdash; <em class="wpc-u-status <?php echo wpc_is_online( $user->ID ) ? 'online' : 'offline'; ?>"><?php echo wpc_get_user_activity( $user->ID ); ?></em>
				<?php do_action('wpc_after_loop_user_name', $user->ID); ?>
			</div>


			<?php if( $bio = wpc_get_user_short_bio( $user->ID ) ) : ?>
				<p class="user-bio" itemprop="description"><?php echo $bio; ?></p>
			<?php else : ?>
				<p><?php echo apply_filters( 'wpc_user_no_bio_inner_text', '<em>' . wpc_translate('This user has not provided information about them.') . '</em>' ); ?></p>
			<?php endif; ?>

		</div>

	</div>

	<div class="user-snippet-links">
			
		<ul>

			<?php do_action( 'wpc_before_user_snippet_buttons', $user ); ?>
			
			<li><a href="<?php echo $user_links->profile; ?>" class="wpc-btn wpcajx" data-action="view-profile" data-slug="<?php echo $user->user_nicename; ?>"><?php echo wpc_translate('Profile'); ?></a></li>
			
			<?php if( is_user_logged_in() ) { ?>

				<?php if( wp_get_current_user()->ID !== $user->ID ) : ?>

					<li><?php wpc_contact_user_modal_link( $user, $cmodal_data ); ?></li>
					
					<?php if( wpc_is_user_blocked( $user->ID ) ) : ?>
						
						<li><a href="<?php echo $user_links->unblock; ?>" class="wpc-btn wpcajx" data-action="unblock" data-user="<?php echo $user->ID; ?>"><?php echo wpc_translate('Unblock'); ?></a></li>
					
					<?php else : ?>
						
						<li><a href="<?php echo $user_links->block; ?>" class="wpc-btn wpcajx" data-action="block" data-user="<?php echo $user->ID; ?>"><?php echo wpc_translate('Block'); ?></a></li>
					
					<?php endif; ?>

				<?php else : ?>

					<a href="<?php echo $user_links->messages; ?>" class="wpc-btn wpcajx" data-action="load-messages"><?php echo wpc_translate('Messages'); ?><?php echo $stats = wpc_get_stats($user->ID)->unread > 0 ? '<span class="wpc-count">' . $stats->unread . '</span>' : ''; ?></a>

				<?php endif; ?>

			<?php } ?>	

			<?php do_action( 'wpc_after_user_snippet_buttons', $user ); ?>

		</ul>

	</div>

</div>