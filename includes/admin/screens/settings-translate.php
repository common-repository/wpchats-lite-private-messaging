<?php

class WPC_admin_screen_settings_transalte
{
	protected static $instance = null;

	public static function instance() {

		return null == self::$instance ? new self : self::$instance;

	}

	public function update() {

		if( isset( $_POST['submit'] ) ) {

			if( ! isset( $_POST['_wpc_nonce'] ) || ! wp_verify_nonce( $_POST['_wpc_nonce'], '_wpc_nonce' ) ) {
				echo '<div id="error" class="error notice is-dismissible"><p>Error occured, please try again.</p></div>';
				return;
			}

			if ( ! empty( $_POST['translations']['active'] ) ) {
				update_option( "wpc_active_translation", sanitize_text_field( $_POST['translations']['active'] ) );
			} else {
				delete_option( "wpc_active_translation" );
			}
			
			echo '<div id="update" class="updated notice is-dismissible"><p>Settings saved successfully!</p></div>';

		} elseif ( isset( $_GET['done'] ) ) {

			switch ( $_GET['done'] ) {
				case 'new':
					echo '<div id="update" class="updated notice is-dismissible"><p>New translations added successfully!</p></div>';
					break;
				case 'update':
					echo '<div id="update" class="updated notice is-dismissible"><p>Translations updated successfully!</p></div>';
					break;
				case 'delete':
					echo '<div id="update" class="updated notice is-dismissible"><p>Translations deleted successfully!</p></div>';
					break;
			}

			echo '<script type="text/javascript">var u=window.location.href;window.history.pushState(null,null,u.substring(0,u.indexOf(\'&done\')));</script>';


		}


	}

	public function screen() {

		if ( isset( $_REQUEST['wpc_new'] ) ) {
			require 'settings-translate-new.php';
		} elseif ( ! empty( $_REQUEST['wpc_edit'] ) ) {
			require 'settings-translate-edit.php';
		} elseif ( ! empty( $_REQUEST['wpc_delete'] ) ) {
			require 'settings-translate-delete.php';
		} else {

			$this->update();
			$translations = get_option( "_wpc_registered_translations" );
			if ( $translations ) $translations = array_filter( explode( ",", $translations ) );
			else $translations = array();

			$active = get_option( "wpc_active_translation" );

			?>

				<p><a href="admin.php?page=wpchats-settings&amp;tab=translate&amp;wpc_new=1" class="button">+ New translation</a></p>

				<form method="post" id="trans-form">
								
					<table class="widefat striped">

						<tr style="background: #ececec;">
							<th style="text-decoration:underline"></th>
							<th style="text-decoration:underline">name</th>
							<th style="text-decoration:underline">status</th>
							<th style="text-decoration:underline">actions</th>
						</tr>

						<?php if ( ! empty( $translations ) ) : ?>
						<?php foreach ( $translations as $i => $translation ) : ?>
							<tr>
								<td><input type="radio" name="translations[active]" oncontextmenu="this.checked=!this.checked;return false;" title="activate this translation. right click to uncheck" style="cursor:help" value="<?php echo $translation; ?>" <?php checked( $translation === $active ); ?> /></td>
								<td><?php echo $translation; ?></td>
								<td><?php echo $translation === $active ? 'active' : 'inactive'; ?></td>
								<td>
									<a href=javascript:; onclick="jQuery('#edit-<?php echo $i; ?>').trigger('submit')">edit</a> / <a href=javascript:; onclick="jQuery('#delete-<?php echo $i; ?>').trigger('submit')">delete</a>
								</td>
							</tr>
						<?php endforeach; ?>
						<?php else: ?>
							<tr>
								<td>You don't have any translations yet.</td>
							</tr>
						<?php endif; ?>


						<tr style="background: #ececec;">
							
							<td><?php wp_nonce_field('_wpc_nonce', '_wpc_nonce'); ?>
							<input type="submit" name="submit" value="Save" class="button button-primary" /></td>
							<th style="text-decoration:underline">name</th>
							<th style="text-decoration:underline">status</th>
							<th style="text-decoration:underline">actions</th>

						</tr>


					</table>

					<!--<?php wp_nonce_field('_wpc_nonce', '_wpc_nonce'); ?>
					<?php submit_button('Save translations'); ?>-->

				</form>

				<div style="display:none">
					<?php foreach ( $translations as $i => $translation ) : ?>
						<form action="admin.php?page=wpchats-settings&amp;tab=translate" method="post" id="edit-<?php echo $i; ?>">
							<input type="hidden" name="wpc_edit" value="<?php echo $translation; ?>">
						</form>
						<form action="admin.php?page=wpchats-settings&amp;tab=translate" method="post" id="delete-<?php echo $i; ?>" onsubmit="return confirm('Are you sure you want to delete this translation forever?')">
							<input type="hidden" name="wpc_delete" value="<?php echo $translation; ?>">
							<?php wp_nonce_field('_wpc_nonce', '_wpc_nonce'); ?>
						</form>
					<?php endforeach; ?>
				</div>

			<?php

		}

	}

}

WPC_admin_screen_settings_transalte::instance()->screen();