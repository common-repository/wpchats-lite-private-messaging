<?php
// prevent direct access
defined('ABSPATH') || exit;

?>
<fieldset class="wpc-epbi">
	
	<?php do_action('wpc_profile_edit_before_basic_info'); ?>
	
	<h3><?php echo wpc_translate('Basic info'); ?>:</h3>

	<label class="wpc-epnm">
		<strong><?php echo wpc_translate('Name'); ?></strong><br/>
		<input type="text" name="_name" value="<?php echo wpc_get_user_field( 'name', wpc_get_displayed_user_id(), wpc_get_user_name( wpc_get_displayed_user_id() ) ); ?>" size="50" />
	</label>
	<br/>
	<label class="wpc-epab">
		<strong><?php echo wpc_translate('About (bio)'); ?></strong><br/>
		<textarea cols="50" name="bio" rows="5"><?php echo wpc_get_user_field( 'bio', wpc_get_displayed_user_id() ); ?></textarea>
	</label>
	<br/>

	<?php do_action('wpc_profile_edit_after_basic_info'); ?>

</fieldset>