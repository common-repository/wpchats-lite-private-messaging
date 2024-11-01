<?php

// Prevent direct access
defined('ABSPATH') || exit;
$get_recipient = wpc_get_recipient();

?>

<?php do_action( 'wpc_before_conversation_content', $get_recipient->ID ); ?>

<div class="single-pm" data-pm-id="<?php echo wpc_get_conversation_id(); ?>" data-recipient-slug="<?php echo $get_recipient->user_nicename; ?>">

	<div class="wpc-c-head">
		<?php do_action('wpc_before_conversation_head_content'); ?>
		<div class="wpc-u-avatar">
			<a href="<?php echo wpc_get_user_profile_url( $get_recipient->ID ); ?>" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "'.admin_url('admin-ajax.php?action=wpc&wpc_users=1&wpc_user=' . $get_recipient->user_nicename ).'"}'); ?>">
				<?php echo get_avatar( $get_recipient->ID, apply_filters( 'wpc_conversation_recipient_avatar_size', 30 ) ); ?>
				<span><?php echo wpc_get_user_name($get_recipient->ID); ?></span>
				<?php do_action('wpc_after_conversation_head_user_details'); ?>
			</a>
		&middot; <?php echo wpc_get_user_activity( $get_recipient->ID ); ?>
		</div>
		<div class="wpc-c-actions">
			<?php do_action('wpc_before_conversation_actions'); ?>
			<span class="csf"><?php wpc_conversation_search_form(); ?></span>
			<div style=" margin-top: 7px; "></div>
			<?php wpc_conversation_action_links(); ?>

			<?php do_action('wpc_after_conversation_actions'); ?>
		</div>
		<?php do_action('wpc_after_conversation_head_content'); ?>
	</div>

	<div class="wpc-messages">

		<?php wpc_messages_pagination(); ?>

		<?php do_action('wpc_before_messages_list'); ?>

		<?php if( count( $all_messages = wpc_get_messages() ) > 0 ) : ?>

			<div class="wpc_contents">

				<?php foreach( array_reverse( $all_messages ) as $message ) : ?>

					<?php require wpc_template_path( 'messages/content-single-message' ); ?>

				<?php endforeach; ?>

			</div>

		<?php else : ?>
	
			<?php do_action( 'wpc_conversations_no_messages' ); ?>

			<p class="no-messages">
				<?php echo apply_filters( 'wpc_conversation_no_messages_notice', wpc_translate('There are no messages to show.') ); ?>
			</p>

			<div class="wpc_contents"></div>

		<?php endif; ?>

		<?php do_action('wpc_after_messages_list'); ?>

	</div>

	<?php wpc_single_seen_notice(); ?>

	<div class="wpc-input">
		
		<?php wpc_conversation_form(); ?>

	</div>

</div>

<?php do_action( 'wpc_after_conversation_content', $get_recipient->ID ); ?>