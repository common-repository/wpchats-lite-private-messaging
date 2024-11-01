<?php

// prevent direct access
defined('ABSPATH') || exit;

if( ! wpc_is_mod() ) exit;
$reports = wpc_get_reports();
$user_links = wpc_get_user_links();

if( get_query_var( 'wpc_reported_id', wpc_get_query_var( 'wpc_reported_id', 0 ) ) ) {

	$id = (int) get_query_var( 'wpc_reported_id', wpc_get_query_var( 'wpc_reported_id', 0 ) );
	$found = null;

	foreach( $reports->messages as $message ) {
		if( ! empty( $found ) ) { break; }
		if( $id == $message->id ) {
			$found = array(
				'type' => 'message',
				'report' => $message
			);
		}
	}

	if( ! $found ) {
		foreach( $reports->conversations as $conversation ) {
			if( ! empty( $found ) ) { break; }
			if( $id == $conversation->id ) {
				$found = array(
					'type' => 'conversation',
					'report' => $conversation
				);
			}
		}
	}

	if( ! empty( $found ) && ! empty( $found['type'] ) ) {

		?>
		
		<p style="margin-bottom: 1.5em;">
			<a href="<?php echo $user_links->mod; ?>" class="wpc-btn wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_mod=1') . '"}'); ?>">&laquo; <?php echo wpc_translate('moderation panel'); ?></a>
			<?php if( wpc_current_user_can('ban') ) : ?><a href="<?php echo $user_links->mod; ?>moderators/banned/" class="wpc-btn wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_mod=1&wpc_mod_banned=1') . '"}'); ?>"><?php echo wpc_translate('Banned users'); ?></a><?php endif; ?>
			<a href="<?php echo $user_links->mod; ?>moderators/" class="wpc-btn wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_mod=1&wpc_mods=1') . '"}'); ?>"><?php echo wpc_translate('Moderators'); ?></a>
			<a href="<?php echo $user_links->users->all; ?>" class="wpc-btn wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_users=1') . '"}'); ?>"><?php echo wpc_translate('Browse users'); ?></a>
			<a href="<?php echo $user_links->messages; ?>" class="wpc-btn wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1') . '"}'); ?>"><?php echo wpc_translate('View messages'); ?></a>
		</p>

		<table class="wpc-report">
	
			<tr>
				<th><?php echo wpc_translate('Information'); ?></th>
				<th><?php echo wpc_translate('Actions'); ?></th>
			</tr>

		<?php $report = $found['report'];
		require wpc_template_path( 'moderation/loop-single-' . $found['type'] ); ?>

		</table>

		<?php return;
	}

}

?>

<div class="wpc-mod-archives">

	<p style="margin-bottom: 1.5em;">
		<a href="<?php echo $user_links->mod; ?>moderators/" class="wpc-btn wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_mod=1&wpc_mods=1') . '"}'); ?>"><?php echo wpc_translate('Moderators'); ?></a>
		<?php if( wpc_current_user_can('ban') ) : ?><a href="<?php echo $user_links->mod; ?>moderators/banned/" class="wpc-btn wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_mod=1&wpc_mod_banned=1') . '"}'); ?>"><?php echo wpc_translate('Banned users'); ?></a><?php endif; ?>
		<a href="<?php echo $user_links->users->all; ?>" class="wpc-btn wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_users=1') . '"}'); ?>"><?php echo wpc_translate('Browse users'); ?></a>
		<a href="<?php echo $user_links->messages; ?>" class="wpc-btn wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1') . '"}'); ?>"><?php echo wpc_translate('View messages'); ?></a>
	</p>

	<h4>Messages</h4>

	<?php if( count( $reports->messages ) > 0 ) : ?>

		<p><?php echo str_replace( '[count]', count( $reports->messages ), wpc_translate('You have [count] messages currently awaiting moderation:') ); ?></p>

			<table class="wpc-report">
	
				<tr>
					<th><?php echo wpc_translate('Information'); ?></th>
					<th><?php echo wpc_translate('Actions'); ?></th>
				</tr>

				<?php foreach( $reports->messages as $report ) : ?>

					<?php require wpc_template_path( 'moderation/loop-single-message' ); ?>

				<?php endforeach; ?>

			</table>

	<?php else : ?>

		<p><?php echo wpc_translate('There are no reported messages for the moment.'); ?></p>

	<?php endif; ?>

	<h4><?php echo wpc_translate('Conversations'); ?></h4>

	<?php if( count( $reports->conversations ) > 0 ) : ?>

		<p><?php echo str_replace( '[count]', count( $reports->conversations ), wpc_translate('You have [count] messages currently awaiting moderation:') ); ?></p>

		<table class="wpc-report">
	
				<tr>
					<th><?php echo wpc_translate('Information'); ?></th>
					<th><?php echo wpc_translate('Actions'); ?></th>
				</tr>

				<?php foreach( $reports->conversations as $report ) : ?>

					<?php require wpc_template_path( 'moderation/loop-single-conversation' ); ?>

				<?php endforeach; ?>

			</table>

	<?php else : ?>

		<p><?php echo wpc_translate('There are no reported conversations for the moment.'); ?></p>

	<?php endif; ?>

</div>