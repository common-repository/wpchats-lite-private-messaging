<?php

class WPC_admin_screen_settings_other
{
	protected static $instance = null;

	public static function instance() {

		return null == self::$instance ? new self : self::$instance;

	}

	public function update() {

		if ( isset( $_GET['updated'] ) ) {
			
			?>
				<div id="update" class="updated notice is-dismissible"><p>Changes saved successfully.</p></div>
				<script type="text/javascript">var u=window.location.href;window.history.pushState(null,null,u.substring(0,u.indexOf('&updated')));</script>
			<?php
		}

		if ( isset( $_GET['flush_cache'] ) && wpc_validate_nonce() ) {
			global $wpdb;
			$table = $wpdb->prefix . 'options';
			$query = $wpdb->get_results( "SELECT `option_name` FROM $table WHERE `option_name` LIKE '%_transient_wpc%'" );
			if ( ! empty( $query ) ) {
				foreach ( $query as $item ) {
					delete_transient( mb_substr( $item->option_name, mb_strlen('_transient_')) );
				}
			}
			?>
				<div id="update" class="updated notice is-dismissible"><p>Cache flushed successfully.</p></div>
				<script type="text/javascript">var u=window.location.href;window.history.pushState(null,null,u.substring(0,u.indexOf('&flush_cache')));</script>
			<?php
		}

		if( isset( $_POST['submit'] ) ) {

			if( ! isset( $_POST['_wpc_nonce'] ) || ! wp_verify_nonce( $_POST['_wpc_nonce'], '_wpc_nonce' ) ) {
				echo '<div id="error" class="error notice is-dismissible"><p>Error occured, please try again.</p></div>';
				return;
			}

			$settings = array();

			if( isset( $_POST['notifications']['toggle'] ) ) {
				$settings['notifications'] = true;
			} else {
				$settings['notifications'] = false;
			}

			if( isset( $_POST['stats']['toggle'] ) ) {
				$settings['stats'] = true;
			} else {
				$settings['stats'] = false;
			}

			if ( wpc_settings()->stats !== $settings['stats'] || wpc_settings()->notifications !== $settings['notifications'] ) {
				update_option( "_wpc_needs_flush", 1 );
			}

			if( ! empty( $_POST['menus']['name'] ) ) {
				foreach ( $_POST['menus']['name'] as $name => $on ) {
					$settings['menu']['locations'][] = sanitize_text_field( $name );
				}
			}

			if( isset( $_POST['menus']['items']['messages']['toggle'] ) ) {
				$settings['menu']['items']['messages'] = !empty( $_POST['menus']['items']['messages']['inner'] ) ? sanitize_text_field($_POST['menus']['items']['messages']['inner']) : wpc_translate('Messages');
			} else {
				$settings['menu']['items']['messages'] = false;
			}

			if( isset( $_POST['menus']['items']['users']['toggle'] ) ) {
				$settings['menu']['items']['users'] = !empty( $_POST['menus']['items']['users']['inner'] ) ? sanitize_text_field($_POST['menus']['items']['users']['inner']) : wpc_translate('Browse users');
			} else {
				$settings['menu']['items']['users'] = false;
			}

			$settings['rtl'] = isset( $_POST['rtl'] );
			$settings['caching'] = isset( $_POST['caching'] );
			$settings['copy'] = isset( $_POST['copy'] );

			update_option( "_wpc_settings_other", json_encode( $settings ) );
			
			echo '<div id="update" class="updated notice is-dismissible"><p>Settings saved successfully!</p></div>';

			wp_redirect( "admin.php?page=wpchats-settings&tab=other&updated" );
			exit;

		}


	}

	public function screen() {
		
		$settings = wpc_settings();
		$this->update();
		$get_nav_menu_locations = get_nav_menu_locations();

		?>

			<form method="post" class="wpc-form">
							
				<table>

					<?php if( ! empty( $get_nav_menu_locations ) ) : ?>
					<tr>
						
						<td valign="top">
							<h3>Menu links</h3>
							<i>Add wpchats links to the navigation menu.</i>
						</td>

						<td>

							<p><strong>Select a menu to filter</strong></p>
							<?php foreach ( $get_nav_menu_locations as $name => $id ) : ?>
								<label><input type="checkbox" name="menus[name][<?php echo $name; ?>]" <?php checked( !empty($settings->nav_menu->locations) && in_array($name, $settings->nav_menu->locations) ); ?> />
								<?php echo $name; ?></label><br/>
							<?php endforeach; ?>

							<p><strong>Items to add</strong></p>
							<p>
								
								<input type="checkbox" name="menus[items][messages][toggle]" title="toggle" <?php checked( !empty($settings->nav_menu->messages->enable) ); ?>>
								<input type="text" name="menus[items][messages][inner]" value="<?php echo $settings->nav_menu->messages->inner; ?>" placeholder="Messages item text" />
								
								<br/>

								<input type="checkbox" name="menus[items][users][toggle]" title="toggle" <?php checked( !empty($settings->nav_menu->users->enable) ); ?>>
								<input type="text" name="menus[items][users][inner]" value="<?php echo $settings->nav_menu->users->inner; ?>" placeholder="Users item text" />
							
							</p>

						</td>

					</tr>
					<?php endif; ?>

					<tr>
						
						<td valign="top">
							<h3>Caching</h3>
							<i>Enable or disable caching</i>
							<p><span class="button" onclick="!confirm('Are you sure?')||window.location.replace('<?php echo wpc_nonce_url('admin.php?page=wpchats-settings&tab=other&flush_cache=1'); ?>')">Flush cache now</span></p>
						</td>
						<td>
							<label>
								<input type="checkbox" name="caching" <?php checked( ! empty( $settings->caching ) ); ?> />
								Enable caching
							</label>
						</td>

					</tr>

					<tr>
						
						<td valign="top">
							<h3>RTL</h3>
							<i>Toggle RTL (right-to-left) display</i>
						</td>
						<td>
							<label>
								<input type="checkbox" name="rtl" <?php checked( ! empty( $settings->RTL ) ); ?> />
								Enable RTL
							</label>
						</td>

					</tr>

					<tr>
						
						<td>
							<?php wp_nonce_field('_wpc_nonce', '_wpc_nonce'); ?>
							<?php submit_button(); ?>
						</td>

					</tr>

				</table>

		<?php

	}

}

WPC_admin_screen_settings_other::instance()->screen();