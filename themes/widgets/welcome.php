<?php

// prevent direct access
defined('ABSPATH') || exit;

$user = wp_get_current_user();
$stats = wpc_get_stats( $user->ID );

?>

<div class="wpc-welcome-widget">

	<ul>
		
		<li class="top wpc-avatar-item">
			Welcome, <a href="<?php echo wpc_get_user_links( $user->ID )->profile; ?>" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "'.admin_url('admin-ajax.php?action=wpc&wpc_users=1&wpc_user='.$user->user_nicename).'"}'); ?>"><?php echo wpc_get_user_name( $user->ID, 1 ); ?></a>
			<a href="<?php echo wpc_get_user_links($user->ID)->profile; ?>" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "'.admin_url('admin-ajax.php?action=wpc&wpc_users=1&wpc_user='.$user->user_nicename).'"}'); ?>"><?php echo get_avatar($user->ID, 25); ?></a>
		</li>

		<li class="wpc-messages-item">
			<a href="<?php echo wpc_messages_base(); ?>" class="block wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "'.admin_url('admin-ajax.php?action=wpc&wpc_messages=1').'"}'); ?>"><?php echo wpc_translate('Messages') . ( isset( $stats->unread_conversations, $stats->unread_conversations->count ) && $stats->unread_conversations->count > 0 ? " (+{$stats->unread_conversations->count})" : ''); ?></a>
		</li>

		<li class="wpc-archives-item">
			<a href="<?php echo wpc_messages_base('archives/'); ?>" class="block wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "'.admin_url('admin-ajax.php?action=wpc&wpc_messages=1&wpc_archives=1').'"}'); ?>"><?php echo wpc_translate('Archives'); ?></a>	
		</li>

		<li class="wpc-notifications-item">
			<a href="<?php echo wpc_get_user_links($user->ID)->notifications; ?>" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "'.admin_url('admin-ajax.php?action=wpc&wpc_users=1&wpc_user='.$user->user_nicename).'&wpc_user_notifications=1"}'); ?>"><?php echo wpc_translate('Notifications') . ( $stats->notifications->unread > 0 ? " (+{$stats->notifications->unread})" : '' ); ?></a>
		</li>

		<li class="wpc-users-item">
			<a href="<?php echo wpc_get_user_links( $user->ID )->users->all; ?>" class="block wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "'.admin_url('admin-ajax.php?action=wpc&wpc_users=1').'"}'); ?>"><?php echo wpc_translate('Browse users'); ?></a>	
		</li>

		<li class="wpc-profile-item">
			<a href="<?php echo wpc_get_user_links( $user->ID )->profile; ?>" class="block wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "'.admin_url('admin-ajax.php?action=wpc&wpc_users=1&wpc_user='.$user->user_nicename).'"}'); ?>"><?php echo wpc_translate('My profile'); ?></a>	
		</li>
		
		<?php if( wpc_current_user_can( "edit_own_profile" ) ) : ?>
			<li class="wpc-pedit-item">
				<a href="<?php echo wpc_get_user_links( $user->ID )->edit; ?>" class="block wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "'.admin_url('admin-ajax.php?action=wpc&wpc_users=1&wpc_user='.$user->user_nicename.'&wpc_edit_user=1').'"}'); ?>"><?php echo wpc_translate('Edit profile'); ?></a>	
			</li>
		<?php endif; ?>

		<?php if( wpc_is_mod( $user->ID ) ) : ?>
			<li class="wpc-mod-panel-item">
				<a href="<?php echo wpc_get_user_links()->mod; ?>" class="block wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "'.admin_url('admin-ajax.php?action=wpc&wpc_mod=1').'"}'); ?>"><?php echo wpc_translate('Moderation panel'); ?></a>	
			</li>
		<?php endif; ?>

		<?php do_action( 'wpc_after_welcome_widget_items', $user, $stats ); ?>

		<li class="wpc-logout-item">
			<a href="<?php echo wp_logout_url(); ?>" class="block wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "'.wp_logout_url().'", "onSuccess": "eval(window.location.reload())", "noHistory": "1"}'); ?>"><?php echo wpc_translate('Logout'); ?></a>	
		</li>

	</ul>

</div>