<?php
// prevent direct access
defined('ABSPATH') || exit;

global $wpc_current_user_stats;
?>

<div class="wpc-u-head">

	<span class="wpc-u-quick-nav">
		
		<a href="<?php echo wpc_get_user_links()->users->online; ?>" class="wpcajx" data-action="load-users" data-users="online">
			<?php echo wpc_translate('online'); ?>
			<span class="_wpc-online-count wpc-counts"><?php echo $wpc_current_user_stats->users->online; ?></span>
		</a>

		<?php if( is_user_logged_in() ) : ?>
			&middot;
			<a href="<?php echo wpc_get_user_links()->users->blocked; ?>" class="wpcajx" data-action="load-users" data-users="blocked">
				<?php echo wpc_translate('blocked'); ?>
				<span class="_wpc-blocked-count wpc-counts"><?php echo $wpc_current_user_stats->users->blocked; ?></span>
			</a>
			&middot;
			<a href="<?php echo wpc_get_user_links()->profile; ?>" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_users=1&wpc_user=' . wp_get_current_user()->user_nicename) . '"}'); ?>" <?php wpc_echo_on(wpc_is_my_profile(),'style="font-weight:600"'); ?>>
				<?php echo wpc_translate('my profile'); ?>
			</a>
		<?php endif; ?>

		<?php if( wpc_is_mod() ) : ?>
			&middot;
			<a href="<?php echo wpc_get_user_links()->mod; ?>" class="wpcajx" data-action="load-moderation" title="<?php echo wpc_translate('moderation panel'); ?>">
				<?php echo wpc_translate('mod. panel'); ?>
				<?php if( $wpc_current_user_stats->reports > 0 ) : ?>
					<span class="_wpc-reports-count wpc-counts"><?php echo $wpc_current_user_stats->reports; ?></span>
				<?php endif; ?>
			</a>
		<?php endif; ?>

	</span>
	
	<?php echo wpc_get_users_search_form(); ?>
	
</div>