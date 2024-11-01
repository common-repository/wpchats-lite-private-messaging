<?php

// prevent direct access
defined('ABSPATH') || exit;

$user = wpc_get_displayed_user();
$groups = wpc_profile_edit_groups();
$current_group = wpc_profile_edit_current_group();
$user_links = wpc_get_user_links( $user->ID );
$formAction = $user_links->edit . ( ! empty( $current_group->slug ) ? $current_group->slug . '/' : '' );

if( ! wpc_current_user_can("edit_own_profile") ) {
	return;
}

?>

<?php do_action('wpc_before_profile_edit'); ?>

<?php require wpc_template_path( 'users/content-header' ); ?>

<div class="wpc-u-edit wpc-u-content">

	<p style="margin-bottom:1.5em"><a href="<?php echo $user_links->users->all; ?>" class="wpc-btn wpcajx" data-action="load-users" data-users="all">&laquo; <?php echo wpc_translate('Back to users'); ?></a></p>

	<?php wpc_user_profile_tabs( $user ); ?>

	<ul class="wpc-pe-gr-tabs">
		<?php foreach ( $groups as $group ) : ?>
			<li class="<?php echo $group['name']; ?><?php wpc_echo_on($group['name']==wpc_profile_edit_current_group_name(), ' current'); ?>" title="<?php echo $group['title']; ?>">
				<a href="<?php echo $user_links->edit . $group['slug']; ?>/" class="wpcajx2" data-task="<?php echo wpc_quote_url( '{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_users=1&wpc_user=' . $user->user_nicename . '&wpc_edit_user=1&wpc_edit_user_group=' . $group['name']) . '"}' ); ?>">
					<?php echo $group['title']; ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>

	<form method="post" action="<?php echo $formAction; ?>" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"action": "' . admin_url('admin-ajax.php?action=wpc_actions&do=profile-update&wpc_user=' . $user->user_nicename ) . '", "noLoad": "1", "onSuccessCall": "wpc_append_feedback(\'' . wpc_translate('profile updated successfully.') . '\')"}'); ?>">

		<?php wpc_profile_edit_group_content(); ?>
			
		<?php do_action('wpc_profile_edit_before_save_button'); ?>

		<fieldset class="wpc-epsv">
			<p>
				<input type="submit" name="submit" value="Update profile" />
				<a href="<?php echo $user_links->profile; ?>" class="wpcajx" data-action="view-profile" data-slug="<?php echo $user->user_nicename; ?>"><?php echo wpc_translate('Cancel'); ?></a>
			</p>
		</fieldset>

		<?php wp_nonce_field( '_wpc_nonce', '_wpc_nonce' ); ?>

	</form>

</div>

<?php do_action('wpc_after_profile_edit'); ?>