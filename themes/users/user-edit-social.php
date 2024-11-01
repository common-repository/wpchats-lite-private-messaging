<?php
// prevent direct access
defined('ABSPATH') || exit;

$icons = wpc_social_list_icons();
?>

<fieldset class="wpc-epsp">
	
	<h3><?php echo wpc_translate('Social profiles'); ?>:</h3>

	<?php do_action('wpc_profile_edit_before_social_profiles'); ?>

	<?php foreach( wpc_social_list() as $field => $name ) : ?>

		<label class="wpc-ep-<?php echo $field; ?>">
			<?php echo !empty($icons[$field])?"<i class=\"{$icons[$field]}\"></i>":'';?>
			<strong><?php echo $name; ?></strong><br/>
			<input type="text" name="<?php echo $field; ?>" size="50" value="<?php echo wpc_get_user_field( $field, wpc_get_displayed_user_id() ); ?>" />
		</label><br/>

	<?php endforeach; ?>

	<?php do_action('wpc_profile_edit_after_social_profiles'); ?>

</fieldset>