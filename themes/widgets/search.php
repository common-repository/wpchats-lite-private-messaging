<?php
// prevent direct access
defined('ABSPATH') || exit;

if( wpc_is_archives() ) {
	$action = wpc_messages_base( wpc_get_bases()->archives . '/' );
	$ajax_action = admin_url('admin-ajax.php?action=wpc&wpc_messages=1&wpc_archives=1');
} elseif ( wpc_is_users() ) {
	$action = wpc_get_user_links()->users->all;
	$ajax_action = admin_url('admin-ajax.php?action=wpc&wpc_users=1');
} else {
	$action = wpc_messages_base();
	$ajax_action = admin_url('admin-ajax.php?action=wpc&wpc_messages=1');
} ?>

<div class="wpc-search-widget">

	<form method="get" action="<?php echo $action; ?>" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"action": "' . $ajax_action . '", "pushUriValues": "1"}'); ?>">

		<?php do_action('wpc_widget_search_before_form_fields'); ?>

		<input type="text" name="q" placeholder="<?php echo wpc_translate('keywords'); ?>" value="<?php echo stripslashes(wpc_get_search_query()); ?>" />
		
		<select>
		
			<?php if( is_user_logged_in() ) : ?>
			<option data-action="<?php echo wpc_quote_url('{"action": "' . wpc_messages_base() . '", "ajax_action": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1') . '"}'); ?>"><?php echo wpc_translate('Messages'); ?></option>
		
			<option data-action="<?php echo wpc_quote_url('{"action": "' . wpc_messages_base(wpc_get_bases()->archives . '/' ) . '", "ajax_action": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1&wpc_archives=1') . '"}'); ?>" <?php echo selected(wpc_is_archives()); ?>><?php echo wpc_translate('Archives'); ?></option>
			<?php endif; ?>
		
			<option data-action="<?php echo wpc_quote_url('{"action": "' . wpc_get_user_links()->users->all . '", "ajax_action": "' . admin_url('admin-ajax.php?action=wpc&wpc_users=1') . '"}'); ?>" <?php echo selected(wpc_is_users()); ?>><?php echo wpc_translate('Users'); ?></option>

			<?php do_action('wpc_widget_search_after_options'); ?>
		
		</select>

		<input type="submit" value="<?php echo wpc_translate('search'); ?>">

	</form>

</div>