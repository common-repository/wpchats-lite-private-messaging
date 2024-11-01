<?php
// prevent direct access
defined('ABSPATH') || exit;

$user = wpc_get_displayed_user();
$current_user = wp_get_current_user();
$user_links = wpc_get_user_links( $user->ID );

?>

<?php require wpc_template_path( 'users/content-header' ); ?>

<?php do_action('wpc_pre_load_profile_content', $user); ?>

<div class="wpc-u-single wpc-u-content" itemprop="about" itemscope itemtype="http://schema.org/Person">
	
	<p style="margin-bottom:1.5em"><a href="<?php echo $user_links->users->all; ?>" class="wpc-btn wpcajx" data-action="load-users" data-users="all">&laquo; <?php echo wpc_translate('Back to users'); ?></a></p>

	<?php wpc_user_profile_tabs( $user ); ?>
	
	<div class="wpc-u-intro<?php wpc_echo_on(wpc_user_has_cover(), ' has-cover'); ?>" <?php if(wpc_user_has_cover()){echo ' style="background: url(\'' . wpc_get_user_cover() . '\') center center no-repeat;background-size: cover;"';}?>>
		
		<div class="wpc-avatar-cont">
			<a href="<?php echo $user_links->profile; ?>" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "'.admin_url('admin-ajax.php?action=wpc&wpc_users=1&wpc_user='.$user->user_nicename).'"}'); ?>">

				<?php echo str_replace( 'class=', 'itemprop="image" class=', get_avatar( $user->ID, 140 ) ); ?>
			</a>
		</div>
		
		<div class="user-info">
			
			<span><h2><a href="<?php echo $user_links->profile; ?>" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "'.admin_url('admin-ajax.php?action=wpc&wpc_users=1&wpc_user='.$user->user_nicename).'"}'); ?>"><span itemprop="name"><?php echo wpc_get_user_name( $user->ID ); ?></span></a></h2></span>

			<?php if( wpc_is_mod( $user->ID ) ) : ?><span><?php echo wpc_translate('Moderator'); ?></span><?php endif; ?>

			<span class="wpc-u-status <?php echo wpc_is_online( $user->ID ) ? 'online' : 'offline'; ?>">
				<?php echo wpc_get_user_activity( $user->ID ); ?>
			</span>

			<?php do_action( 'wpc_user_profile_before_buttons', $user->ID ); ?>

			<span>

				<?php if( is_user_logged_in() && $current_user->ID !== $user->ID ) : ?>
					
					<?php wpc_contact_user_modal_link( $user ); ?>

					<?php if( wpc_is_blocking_allowed() ) { ?>

					<?php if( wpc_is_user_blocked( $user->ID ) ) : ?>
						&nbsp;<a href="<?php echo $user_links->unblock; ?>" class="wpc-btn wpcajx" data-action="unblock" data-user="<?php echo $user->ID; ?>"><?php echo wpc_translate('Unblock'); ?></a>
					<?php else : ?>
						&nbsp;<a href="<?php echo $user_links->block; ?>" class="wpc-btn wpcajx" data-action="block" data-user="<?php echo $user->ID; ?>"><?php echo wpc_translate('Block'); ?></a>
					<?php endif; ?>

					<?php } ?>

				<?php elseif ( is_user_logged_in() ) : ?>

					<a href="<?php echo $user_links->messages; ?>" class="wpc-btn wpcajx" data-action="load-messages"><?php echo wpc_translate('Messages'); ?><?php echo $stat = wpc_get_stats($user->ID)->unread_conversations->count > 0 ? '<span class="wpc-count">' . $stat . '</span>' : ''; ?></a>

				<?php else : ?>

					<a href="<?php echo wpc_messages_base( $user->user_nicename . '/' ); ?>" class="wpc-btn"><?php echo wpc_translate('Send message'); ?></a>

				<?php endif; ?>

				<?php if( ( $current_user->ID == $user->ID && wpc_current_user_can("edit_own_profile") ) || wpc_current_user_can('edit_profiles') ) : ?>
					
					<a href="<?php echo $user_links->edit; ?>" class="wpc-btn wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "'.admin_url('admin-ajax.php?action=wpc&wpc_users=1&wpc_user='.$user->user_nicename).'&wpc_edit_user=1"}'); ?>"><?php echo wpc_translate('Edit profile'); ?></a>

				<?php endif; ?>

			</span>

			<?php do_action( 'wpc_user_profile_after_buttons', $user->ID ); ?>

		</div>

		<?php $user_social = wpc_get_user_social(); ?>

		<?php if( ! empty( $user_social ) ) : ?>

			<?php ob_start(); ?>

			<div style="clear:both;overflow:hidden;display:table"></div>

			<div class="user-social">
			
				<?php foreach( $user_social as $field ) : ?>

					<a href="<?php echo $field['value']; ?>" target="_blank" rel="nofollow" title="<?php echo $field['name'] . ' (' . $field['value'] . ')'; ?>" class="wpc-social-<?php echo $field['name']; ?>" <?php echo 'Website' == $field['name'] ? 'itemprop="url"' : ''; ?>>
						<?php echo $field['name']; ?>
					</a>

					<?php if( $field !== end( $user_social ) ) : ?>
						&nbsp;
					<?php endif; ?>

				<?php endforeach; ?>

			</div>

			<?php echo apply_filters( "wpc_user_profile_social_links", ob_get_clean() ); ?>

		<?php endif; ?>

	</div>

	<div class="user-bio">
		
		<h4><?php echo wpc_translate('About me'); ?>:</h4>

		<?php if( $bio = wpc_get_user_bio( $user->ID ) ) : ?>

			<div itemprop="description"><?php echo wpc_output_message($bio); ?></div>

		<?php else : ?>
			
			<div>
				<p><?php echo apply_filters( 'wpc_user_no_bio_inner_text', '<em>This user has not provided information about them.</em>' ); ?></p>
			</div>
		
		<?php endif; ?>

	</div>

	<?php do_action('wpc_after_user_profile', $user->ID); ?>

</div>