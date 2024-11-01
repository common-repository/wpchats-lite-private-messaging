<?php

if ( ! $last_message = $pm->last_message ) return;

$totals = WPC_cache::conversation_totals( $last_message->PM_ID );

if ( ! $count = $totals['all_data'][0]->total ) {
	$count = 0;
}

?>

<tr>
	<td><a href="<?php echo wpc_admin_user_link( $last_message->sender ); ?>"><?php echo get_avatar($last_message->sender,15); ?>&nbsp;<?php echo wpc_get_user_name( $last_message->sender ); ?></a></td>
	<td><a href="<?php echo wpc_admin_user_link( $last_message->recipient ); ?>"><?php echo get_avatar($last_message->recipient,15); ?>&nbsp;<?php echo wpc_get_user_name( $last_message->recipient ); ?></a></td>
	<td><?php echo $count; ?></td>
	<th style="max-width:300px"><?php echo mb_strlen( $last_message->message ) > 250 ? mb_substr($last_message->message, 0, 250) . " ..." : $last_message->message; ?><em style="color:#c1c1c1"> &mdash; <?php echo wpc_time_diff( $last_message->date, "sent", "ago" ); ?></em></td>
	<td><a href="<?php echo wpc_get_user_links()->mod; ?>messages/<?php echo get_userdata($last_message->sender)->user_nicename; ?>@<?php echo get_userdata($last_message->recipient)->user_nicename; ?>/" title="view conversation and access more actions" target="_new">view</td>
</tr>