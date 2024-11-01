<?php

// prevent direct access
defined('ABSPATH') || exit;

if( isset( $_REQUEST['done'] ) && isset( $_REQUEST['id'] ) ) {
	do_action('wpc_compose_message_template_head', (int) $_REQUEST['id']);
	return;
}
$base_new = wpc_messages_base('new/');

?>

<div class="wpc-compose">

	<?php if( ! isset( $_REQUEST['recipient'] ) || ( isset( $_REQUEST['recipient'] ) && ! wpc_can_contact( (int) $_REQUEST['recipient'] ) ) ) : ?>

		<?php if ( isset( $_REQUEST['recipient'] ) ) { ?><script>alert('<?php echo false !== ( $bail_notice = wpc_can_contact( (int) $_REQUEST['recipient'], 1 )['notice'] ) ? $bail_notice : wpc_translate("You can not contact this user."); ?>')</script><?php } ?>

		<p><strong><?php echo wpc_translate('Select a recipient'); ?>:</strong></p>

		<form method="post" action="<?php echo $base_new; ?>" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"action": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1&wpc_new_message=1') . '", "loadIntoModal": "1"}'); ?>">
			<input role="wpcFocus" type="text" placeholder="<?php echo wpc_translate('Search users'); ?>" name="q" value="<?php echo str_replace( '"', '&quot;', wpc_get_search_query() ); ?>" autocomplete="off" />
			<?php if( isset( $_REQUEST['message'] ) ) : ?>
				<input type="hidden" name="message" value="<?php echo _wpc_str($_REQUEST['message']); ?>" />
			<?php endif; ?>
		</form>

		<form method="post" action="<?php echo $base_new; ?>" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"action": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1&wpc_new_message=1') . '", "loadIntoModal": "1", "focus": "textarea[name=\'message\']"}'); ?>">
			<?php do_action('wpc_compose_message_template_get_users'); ?>
			<a href="<?php echo wpc_get_user_links()->users->all; ?>" class="wpcajx2 wpc-btn hide-on-not-modal" data-task="<?php echo wpc_quote_url('{"loadURL": "'.admin_url('admin-ajax.php?action=wpc&wpc_users=1').'"}'); ?>" style="float:left;margin-top:5px"><?php echo wpc_translate('Cancel'); ?></a>
		</form>

	<?php else : ?>

		<form method="post" action="<?php echo $base_new; ?>" class="wpcajx2 wpcinput" data-task="<?php echo wpc_quote_url('{"action": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1&wpc_new_message=1') . '", "success": "html=D", "onSuccessLoad": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1&wpc_new_message=1&done=_&id={{html}}') . '", "failAlert": "wpc.feedback.err_sending", "loadIntoModal": "1", "focus": "textarea[name=\'message\']"}'); ?>" data-pm-id="<?php echo wpc_get_conversation_id( !empty($_REQUEST['recipient'])?$_REQUEST['recipient']:0 ); ?>">
			<?php do_action('wpc_compose_message_template_write_message'); ?>
		</form>

		<form method="post" action="<?php echo $base_new; ?>" class="wpcajx2" style="display:none" data-task="<?php echo wpc_quote_url('{"action": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1&wpc_new_message=1') . '", "loadIntoModal": "1", "focus": ".wpc-compose input[name=\'recipient\']"}'); ?>" id="wpcbr">
			<textarea name="message"><?php if(isset($_REQUEST['message']))echo _wpc_str($_REQUEST['message']);?></textarea>
			<?php if( isset( $_REQUEST['recipient'] ) ) : ?>
				<input type="hidden" name="_recipient" value="<?php echo _wpc_str($_REQUEST['recipient']); ?>" />
			<?php endif; ?>
		</form>

	<?php endif; ?>

</div>
<?php wpc_data_title(); ?>