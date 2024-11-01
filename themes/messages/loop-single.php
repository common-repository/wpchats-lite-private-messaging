<?php

// Prevent direct access
defined('ABSPATH') || exit;
$converastion = wpc_get_conversation( $pm_id );
$user_id = $converastion->contact;
$unread_count = (int) wpc_count_unreads_in_pm(0, $user_id);

?>

<?php do_action('wpc_before_snippet', $user_id); ?>

<div class="<?php wpc_message_snippet_classes( $pm_id ); ?>" id="pm-<?php echo $pm_id; ?>" data-pm-id="<?php echo $pm_id; ?>" data-last-message="<?php echo $converastion->last_message->ID; ?>">
	
	<?php do_action('wpc_before_snippet_content', $user_id); ?>

	<div class="avatar-cont">
		<?php do_action('wpc_before_snippet_avatar', $user_id); ?>
		<?php echo get_avatar( $user_id, apply_filters('wpc_message_avatar_size', 50) ); ?>

		<?php if( isset( wpc_get_user_data( $user_id )->last_seen ) ) : ?>
			<span class="wpc-u-status <?php echo wpc_is_online( $user_id ) ? 'online' : 'offline'; ?>"><?php echo wpc_get_user_activity( $user_id, 'online', '' ); ?></span>
		<?php endif; ?>

		<?php do_action('wpc_after_snippet_avatar', $user_id); ?>
	</div>

	<div class="content-cont">
		
		<?php do_action('wpc_before_snippet_message_details', $user_id); ?>

		<div class="contact-date">
			
			<span>
				<?php echo wpc_get_user_name( $user_id ); ?>
				<?php if( $unread_count > 0 ) : ?>
					<span class="count" data-count="<?php echo $unread_count; ?>">(<?php echo $unread_count; ?>)</span>
				<?php else : ?>
					<span class="count" data-count="0" style="display: none;">(0)</span>
				<?php endif; ?>
			</span>
			<span><?php wpc_time_int_span( $converastion->last_message->date, false, 'ago' ); ?></span>

		</div>

		<div class="content-excerpt">

			<span class="wpc-snippet-author">
				<span><?php echo get_avatar( $converastion->last_message->sender, apply_filters('wpc_message_snippet_author_avatar_size', 20) ); ?></span>
				<span><?php echo wpc_get_user_name( $converastion->last_message->sender, 1 ); ?></span>
			</span>

			<?php do_action('wpc_before_snippet_message_excerpt', $user_id); ?>
			
			<span class="content-text">"<?php echo wpc_message_snippet_excerpt( $converastion->last_message->ID ); ?>"</span>
			<span class="seen-notice"><i class="wpcico wpcico-ok"></i></span>
			
			<?php do_action('wpc_after_snippet_message_excerpt', $user_id); ?>

		</div>
	
		<?php do_action('wpc_after_snippet_message_details', $user_id); ?>

	</div>

	<a href="<?php echo wpc_messages_base( get_userdata($user_id)->user_nicename . '/' . ( wpc_get_search_query() > '' ? '?q=' . wpc_get_search_query(1) : '' ) ); ?>" class="wpcajx2 read" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1&wpc_recipient=' . get_userdata($user_id)->user_nicename . ( wpc_get_search_query() > '' ? '&q=' . wpc_get_search_query(1) : '' ) ) . '"}'); ?>"></a>

	<?php do_action('wpc_after_snippet_content', $user_id); ?>

</div>


<?php do_action('wpc_after_snippet', $user_id); ?>