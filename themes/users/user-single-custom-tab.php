<?php

// prevent direct access
defined('ABSPATH') || exit;

$user = wpc_get_displayed_user();
$tab_data = wpc_get_profile_tab();

?>

<?php do_action('wpc_before_profile_custom_tab_content'); ?>

<?php require wpc_template_path( 'users/content-header' ); ?>

<div class="<?php echo apply_filters('wpc_custom_tab_container_classes', 'wpc-u-content wpc-custom-tab'); ?>">

	<p style="margin-bottom:1.5em"><a href="<?php echo wpc_get_user_links()->users->all; ?>" class="wpc-btn wpcajx" data-action="load-users" data-users="all">&laquo; <?php echo wpc_translate('Back to users'); ?></a></p>

	<?php wpc_user_profile_tabs( $user ); ?>

	<div class="wpc-custom-tab-content">
		
		<?php if( ! empty( $tab_data ) && isset( $tab_data->content ) ) : ?>

			<?php echo apply_filters('wpc_custom_tab_content', $tab_data->content, $tab_data, $user); ?>

		<?php else : ?>
		
			<p>Nothing to show here!</p>
		
		<?php endif; ?>
	
	</div>

</div>