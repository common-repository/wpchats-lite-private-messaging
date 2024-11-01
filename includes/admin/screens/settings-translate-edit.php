<?php

class WPC_admin_screen_settings_transalte_edit
{
	protected static $instance = null;

	public static function instance() {

		return null == self::$instance ? new self : self::$instance;

	}

	public function update() {

		if( isset( $_POST['submit'] ) ) {

			if( ! wpc_validate_nonce() ) {
				echo '<div id="error" class="error notice is-dismissible"><p>Error occured, please try again.</p></div>';
				return;
			}

			if ( ! empty( $_POST['name'] ) ) {
				$trans['name'] = sanitize_text_field( $_POST['name'] );
			} else {
				$trans['name'] = "translation";
			}

			$transes = explode( ",", get_option( "_wpc_registered_translations" ) );

			if ( ! empty( $transes ) ) {

				foreach ( $transes as $i => $name ) {
					if ( $name == $_REQUEST['wpc_edit'] ) {
						unset( $transes[$i] );
					}
				}

				if ( in_array( $trans['name'], $transes ) ) {
					for ( $n = 1; $n < 100; $n++ ) {
						if ( ! in_array( "{$trans['name']} $n", $transes ) ) {
							$trans['name'] = "{$trans['name']} $n";
							break;
						}
					}
				}
			}

			$trans['objects'] = array();
			foreach ( wpc_translate_objects() as $i => $translate ) {
				if ( ! empty( $_POST['trans'][$i] ) ) {
					$trans['objects'][$translate] = sanitize_text_field( $_POST['trans'][$i] );
				}
			}

			$transes[] = $trans['name'];
			update_option( "_wpc_registered_translations", implode( ",", $transes ) );
			delete_option( "wpc_transaltion_" . str_replace( " ", "_", $_REQUEST['wpc_edit'] ) );
			update_option( "wpc_transaltion_" . str_replace( " ", "_", $trans['name'] ), base64_encode(json_encode( $trans )) );

			wp_redirect( "admin.php?page=wpchats-settings&tab=translate&done=update" );
			exit;

		}


	}

	public function screen() {
		
		$this->update();

		if ( ! $edit = json_decode( base64_decode(get_option( "wpc_transaltion_" . str_replace( " ", "_", $_REQUEST['wpc_edit'] ) )), true ) ) {
			echo '<div id="error" class="error notice is-dismissible"><p>Error occured, could not locate translation to edit.</p></div>';
			return;
		}

		?>

		<form method="post" class="wpc-form">
							
			<table>

				<tr>
					
					<td valign="top">
						<h3>Name</h3>
						<i>Name your translation</i>
					</td>
					<td>
						<label>
							<input type="text" name="name" placeholder="e.g French, Mandarian, .." size="40" value="<?php echo !empty($_POST['name'])?$_POST['name']:$edit['name']; ?>" />
						</label>
					</td>

				</tr>


				<tr>
					
					<td valign="top">
						<h3>Translations</h3>
						<i>Update translations<br/><br/>You may note some texts that are from emoticons alternative text, which are optional for translation.
						</i>
					</td>
					<td>
						
						<table class="widefat striped" style="padding:3px">
							
						<?php foreach ( wpc_translate_objects() as $i => $translate ) :;
						
						$value = null;
						if ( !empty($_POST['trans'][$i]) )
							$value = $_POST['trans'][$i];
						elseif ( ! empty( $edit['objects'][$translate] ) && ! isset( $_POST['trans'][$i] ) )
							$value = $edit['objects'][$translate];

						?>

							<tr>
								<td style="padding:0;"><label for="t-<?php echo $i; ?>"><?php echo $translate; ?></label></td>
								<td style="padding:0;">
									<textarea id="t-<?php echo $i; ?>" name="trans[<?php echo $i; ?>]" style="background-color: #fff;display: inline-block;font-family: Consolas,Monaco,monospace;border: 1px solid #C7C7C7;max-width: 100%;" cols="50" rows="1"><?php echo $value; ?></textarea>
								</td>
							</tr>

						<?php endforeach; ?>

						</table>

					</td>

				</tr>

				<tr>
					<td>
						<input type="hidden" name="wpc_edit" value="<?php echo $_REQUEST['wpc_edit']; ?>">
						<?php wp_nonce_field('_wpc_nonce', '_wpc_nonce'); ?>
						<?php submit_button('Update tanslations'); ?>
					</td>
				</tr>

			</table>

		</form>


		<?php

	}

}

WPC_admin_screen_settings_transalte_edit::instance()->screen();