<?php

// prevent direct access
defined('ABSPATH') || exit;

?>

<?php do_action('wpc_before_404_users_content'); ?>

<?php require wpc_template_path( 'users/content-header' ); ?>

<div class="wpc-u-404">
	
	<p><?php echo wpc_translate('This user was not found. Perhaps try a search?'); ?></p>

	<?php echo wpc_get_users_search_form( get_query_var('wpc_user', wpc_get_query_var('wpc_user', '')), '<button style="width:100%;margin-top:2px">' . wpc_translate('submit') . '</button>' ); ?>

	<p><?php echo wpc_translate('Or'); ?>, <a href="<?php echo wpc_get_user_links()->users->all; ?>" class="wpcajx2 wpc-btn" data-task="<?php echo wpc_quote_url('{"loadURL": "'.admin_url('admin-ajax.php?action=wpc&wpc_users=1').'"}'); ?>"><?php echo wpc_translate('browse users'); ?></a></p>

	<script type="text/javascript"><?php if(!wpc_is_admin_ajax()){echo 'window.onload=function(){';}?>var input=document.querySelector('.wpc-u-404 input[type="text"]');null!==input&&input.focus();<?php if(!wpc_is_admin_ajax()){echo '}';}?></script>

</div>

<?php do_action('wpc_before_404_users_content'); ?>