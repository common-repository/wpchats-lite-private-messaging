<?php
// prevent direct access
defined('ABSPATH') || exit;

$user_id = wpc_get_displayed_user_id();
$settings = wpc_notification_settings();

?>
<fieldset class="wpc-epnf">
	
	<?php do_action('wpc_profile_edit_before_notifications'); ?>

	<p>
		
		<strong style="display: block;">Send me an email notifications about:</strong>
		
		<label style="display: block;">
			<input type="checkbox" name="notifications[email][messages]" <?php checked($settings->mail->messages); ?>/> <?php echo wpc_translate('Newly received messages'); ?>
		</label>

		<label style="display: block;">
			<strong style="display: block;"><?php echo wpc_translate('Send me notifications to'); ?></strong>
			<input type="email" size="50" style="max-width:392px" placeholder="john@smith.com" name="notifications[email][email]" value="<?php echo $settings->mail->email; ?>" /> 
		</label>

	</p>

	<?php do_action('wpc_profile_edit_after_notifications'); ?>

</fieldset>