<?php

// Prevent direct access
defined('ABSPATH') || exit;
$conversations = wpc_my_conversations();
$messages_base = wpc_messages_base();
$is_archives = wpc_is_archives();
$bases = wpc_get_bases();

?>

<?php do_action( 'wpc_before_messages_template' ); ?>

<div class="wpc-archive">
	
	<?php do_action( 'wpc_before_messages_top_header' ); ?>

	<div class="wpc-c-header">

		<div class="wpc-c-header-left">
			
		<form action="<?php echo $messages_base . ( $is_archives ? $bases->archives . '/' : '' ); ?>" method="get" style="float:none">

			<select name="view" class="wpc_mths wpc-stop-jQ-event" onchange="!this.value>''||this.parentElement.submit()">
				<option value=""><?php echo wpc_translate('view'); ?></option>
				<option <?php echo isset( $_GET['view'] ) && 'unread' == $_GET['view'] ? ' selected="selected"' : ''; ?> value="unread"><?php echo wpc_translate('unread'); ?></option>
				<?php if( $is_archives ) : ?>
					<option value="conversations"><?php echo wpc_translate('messages'); ?></option>					
				<?php else : ?>
					<option value="archives"><?php echo wpc_translate('archives'); ?></option>					
				<?php endif; ?>
				<option value="users"><?php echo wpc_translate('users'); ?></option>					
			</select>

		</form>

			&middot; <a href="<?php echo $messages_base . ( $is_archives ? $bases->archives . '/' : '' ); ?>" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1' . ( $is_archives ? '&wpc_archives=1' : '' ) ) . '"}'); ?>"><?php echo wpc_translate('refresh'); ?></a>

		</div>

		<?php wpc_conversation_search_form(); ?>

	</div>

	<?php do_action( 'wpc_after_messages_top_header' ); ?>

	<?php if( count( $conversations ) > 0 ) : ?>

		<div class="wpc-conversations">

			<?php foreach( $conversations as $pm_id ) : ?>

				<?php require wpc_template_path( 'messages/loop-single' ); ?>

			<?php endforeach; ?>

		</div>

		<?php wpc_conversations_pagination(); ?>

	<?php else : ?>

		<?php do_action( 'wpc_messages_no_conversations' ); ?>

		<p class="no-messages">
			<?php echo apply_filters( 'wpc_no_messages_notice', 'There are no conversations to show.' ); ?>
		</p>

	<?php endif; ?>

	<div class="wpc-c-footer" style="padding-top:5px;">

		<a href="<?php echo $messages_base . 'new/'; ?>" class="wpcfmodal wpc-btn" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1&wpc_new_message=1') . '", "onExitTitle": "' . wpc_title(1) . '", "onExitHref": "' . $messages_base . ( $is_archives ? $bases->archives . '/' : '' ) . '", "onLoadHref": "' . $messages_base . 'new/' . '"}'); ?>">
			+ <?php echo wpc_translate('Compose'); ?>
		</a>

		<?php if( wpc_is_mod() ) : ?>
			&nbsp;
			<a href="<?php echo wpc_get_user_links()->mod; ?>" class="wpcajx wpc-btn" data-action="load-moderation" title="moderation panel">
				<?php echo wpc_translate('mod. panel'); ?>
				<?php if( $stat = wpc_get_stats()->reports ) : ?>
					<span class="_wpc-reports-count wpc-count"><?php echo $stat; ?></span>
				<?php endif; ?>
			</a>
		<?php endif; ?>

	</div>

</div>

<?php do_action( 'wpc_after_messages_template' ); ?>