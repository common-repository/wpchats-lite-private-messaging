<?php

// Prevent direct access
defined('ABSPATH') || exit;

$current_message = $message;
$permalink = wpc_get_conversation_permalink();

?>


<div class="<?php wpc_message_classes( $current_message->ID ); ?>" id="message-<?php echo $current_message->ID; ?>">
			
	<div class="avatar-container">

		<a href="<?php echo wpc_get_user_profile_url( $current_message->sender ); ?>" class="wpcajx" data-action="view-profile" data-slug="<?php echo get_userdata($current_message->sender)->user_nicename; ?>">
			<?php echo get_avatar( $current_message->sender, apply_filters('wpc_in_message_avatar_size', 35) ); ?>
			<span><?php echo _wpc_messages_validate_user_name_( wpc_get_user_name( $current_message->sender ) ); ?></span>
		</a>

		<?php do_action('wpc_after_single_message_avatar', $current_message->ID ); ?>

	</div>

	<div class="message-content">
		
		<?php do_action('wpc_before_single_message_content', $current_message->ID ); ?>

		<div class="message-content-text">

			<?php echo wpc_output_message( $current_message->message ); ?>

		</div>
		
		<div class="message-meta">
			
			<span class="wpc-time-int" data-int="<?php echo $current_message->date; ?>" data-after="ago">
				<?php echo str_replace( "[time]", wpc_time_diff( $current_message->date ), wpc_translate("[time] ago") ); ?>
			</span>
			
			<div class="wpc-more"> &middot;

				<a href="<?php echo "{$permalink}forward/{$current_message->ID}/"; ?>" class="wpcfmodal" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url("admin-ajax.php?action=wpc_actions&do=fwd&wpc_messages=1&wpc_recipient=" . wpc_get_recipient()->user_nicename . "&wpc_forward_message=" . $current_message->ID) . '", "onExitTitle": "' . wpc_title(1) . '", "onLoadHref": "' . "{$permalink}forward/{$current_message->ID}/" . '", "onExitHref": "' . $permalink . '", "focus": "input[name=\'q\']"}'); ?>"><?php echo wpc_translate('forward'); ?></a> &middot;

				<a href="<?php echo "{$permalink}?do=delete&m={$current_message->ID}"; ?>" onclick="return confirm(wpc.conf.del_m)" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url("admin-ajax.php?action=wpc_actions&do=delete&m=$current_message->ID&wpc_messages=1&wpc_recipient=".get_userdata( $current_message->contact )->user_nicename) . '","confirm": "wpc.conf.del_m","success": "html=1","onSuccess": "remove","remove": "#message-' . $current_message->ID . '","failAlert": "wpc.feedback.err_general","noHistory": "1","noPreLoad": "1"}'); ?>"><?php echo wpc_translate('delete'); ?></a>

			</div>

			<?php do_action('wpc_after_single_message_meta', $current_message->ID ); ?>

		</div>

		<?php do_action('wpc_after_single_message_content', $current_message->ID ); ?>

	</div>

</div>