<?php

class WPC_admin_screen_settings_transalte_new
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

			if ( $transes = explode( ",", get_option( "_wpc_registered_translations" ) ) ) {
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

			$registered = get_option( "_wpc_registered_translations" );
			if ( $registered ) $registered = array_filter( explode( ",", $registered ) );
			else $registered = array();
			$registered[] = $trans['name'];
			update_option( "_wpc_registered_translations", implode( ",", $registered ) );

			update_option( "wpc_transaltion_" . str_replace( " ", "_", $trans['name'] ), base64_encode(json_encode( $trans )) );

			wp_redirect( "admin.php?page=wpchats-settings&tab=translate&done=new" );
			exit;

		}


	}

	public function screen() {
		
		$this->update();

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
							<input type="text" name="name" placeholder="e.g French, Mandarian, .." size="40" autofocus="autofocus" value="<?php echo !empty($_POST['name'])?$_POST['name']:''; ?>" />
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
							
						<?php foreach ( wpc_translate_objects() as $i => $translate ) : ?>

							<tr>
								<td style="padding:0;"><label for="t-<?php echo $i; ?>"><?php echo $translate; ?></label></td>
								<td style="padding:0;">
									<textarea id="t-<?php echo $i; ?>" name="trans[<?php echo $i; ?>]" style="background-color: #fff;display: inline-block;font-family: Consolas,Monaco,monospace;border: 1px solid #C7C7C7;max-width: 100%;" cols="50" rows="1"><?php if(!empty($_POST['trans'][$i]))echo $_POST['trans'][$i]; ?></textarea>
								</td>
							</tr>

						<?php endforeach; ?>

						</table>

					</td>

				</tr>

				<tr>
					<td>
						<?php wp_nonce_field('_wpc_nonce', '_wpc_nonce'); ?>
						<?php submit_button('Add translations'); ?>
					</td>
				</tr>

			</table>

		</form>


		<?php

	}

}

WPC_admin_screen_settings_transalte_new::instance()->screen();