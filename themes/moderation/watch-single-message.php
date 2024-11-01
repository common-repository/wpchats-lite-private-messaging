<?php

// prevent direct access
defined('ABSPATH') || exit;

?>

<div class="<?php echo $class; ?>">
	
	<span class="idn" title="identifier in the database">#<?= $m->ID; ?></span>

	<div class="av">
		<a href="<?php echo wpc_get_user_links( $m->sender )->profile; ?>" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_users&wpc_user=' . get_userdata( $m->sender )->user_nicename ) . '"}'); ?>">
			<?php echo get_avatar( $m->sender, 44); ?>
			<br/><?php echo wpc_get_user_name( $m->sender ); ?>
		</a>
	</div>

	<div class="mc">
		<?php echo wpc_output_message( $m->message ); ?>
		<p class="meta">
			- <?php echo wpc_time_diff( $m->date, 'sent', 'ago' ); ?><br/>
			<?php if( $m->deleted > '' ) : ?>- deleted by <?php foreach( array_filter(explode(',', $m->deleted)) as $uid ) { echo wpc_get_user_name($uid,1) . ', '; } ;?><br/><?php endif; ?>
			<?php if( $m->seen > '' ) : ?>- Seen by <?php echo wpc_get_user_name($m->recipient,1); ?><br/><?php endif; ?>

			- <a href="?do=_m-delete&amp;m=<?php echo $m->ID; ?>" onclick="return confirm(wpc.conf.del_m)">delete message</a><br/>
			- <a href="?do=_c-delete&amp;c=<?php echo $m->PM_ID; ?>" onclick="return confirm(wpc.conf.del_c)">delete entire conversation</a><br/>

			<?php if( wp_get_current_user()->ID !== $m->sender && ! user_can($m->sender, 'manage_options') ) : ?>- <a href="?do=_<?php echo wpc_is_banned( $m->sender ) ? 'unban' : 'ban'; ?>&amp;user=<?php echo $m->sender; ?>" onclick="return confirm(wpc.conf.<?php echo wpc_is_banned( $m->sender ) ? 'unban' : 'ban'; ?>_u)"><?php echo wpc_is_banned( $m->sender ) ? 'unban' : 'ban'; ?> <?php echo wpc_get_user_name( $m->sender ); ?></a><br/><?php endif; ?>

		</p>
	</div>

</div>