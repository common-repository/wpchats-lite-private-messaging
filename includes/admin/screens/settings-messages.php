<?php

class WPC_admin_screen_settings_messages
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

		if( isset( $_POST['submit'] ) ) {

			if( ! isset( $_POST['_wpc_nonce'] ) || ! wp_verify_nonce( $_POST['_wpc_nonce'], '_wpc_nonce' ) ) {
				echo '<div id="error" class="error notice is-dismissible"><p>Error occured, please try again.</p></div>';
				return;
			}

			$flush = false;

			if( isset( $_POST['slugs']['messages'] ) && wpc_get_bases()->messages !== $_POST['slugs']['messages'] ) {
				$flush = true;
			}

			if( isset( $_POST['slugs']['archives'] ) && wpc_get_bases()->archives !== $_POST['slugs']['archives'] ) {
				$flush = true;
			}

			$meta = get_option('_wpc_settings');

			if( $meta > '' ) {
				$meta = html_entity_decode( stripslashes($meta) );
				$meta = json_decode($meta);
			} else {
				$meta = new stdClass();
			}

			if( empty( $meta->slugs ) )
				$meta->slugs = new stdClass();
			if( empty( $meta->pagination ) )
				$meta->pagination = new stdClass();
			if( empty( $meta->uploads ) )
				$meta->uploads = new stdClass();

			if( isset( $_POST['slugs']['messages'] ) ) {
				if( strlen( sanitize_text_field( $_POST['slugs']['messages'] ) ) > 0 )
					$meta->slugs->messages = sanitize_text_field( $_POST['slugs']['messages'] );
				else
					$meta->slugs->messages = wpc_settings(true)->slugs->messages;
			}

			if( isset( $_POST['slugs']['archives'] ) ) {
				if( strlen( sanitize_text_field( $_POST['slugs']['archives'] ) ) > 0 )
					$meta->slugs->archives = sanitize_text_field( $_POST['slugs']['archives'] );
				else
					$meta->slugs->archives = wpc_settings(true)->slugs->archives;
			}

			if( isset( $_POST['pagination']['messages'] ) ) {
				if( (int) $_POST['pagination']['messages'] > 0 )
					$meta->pagination->messages = (int) $_POST['pagination']['messages'];
				else
					$meta->pagination->messages = wpc_settings(true)->pagination->messages;
			}

			if( isset( $_POST['pagination']['conversations'] ) ) {
				if( (int) $_POST['pagination']['conversations'] > 0 )
					$meta->pagination->conversations = (int) $_POST['pagination']['conversations'];
				else
					$meta->pagination->conversations = wpc_settings(true)->pagination->conversations;
			}

			if( isset( $_POST['uploads']['enable'] ) )
				$meta->uploads->enable = true;
			else
				$meta->uploads->enable = false;

			if( isset( $_POST['uploads']['allowed'] ) )
				$meta->uploads->allowed = (object) explode(',', $_POST['uploads']['allowed']);

			if( isset( $_POST['uploads']['max_size_kb'] ) ) {
				if( (int) $_POST['uploads']['max_size_kb'] >= 100 )
					$meta->uploads->max_size_kb = (int) $_POST['uploads']['max_size_kb'];
				else
					$meta->uploads->max_size_kb = wpc_settings(true)->uploads->max_size_kb;
			}

			if( isset( $_POST['uploads']['meta_data'] ) )
				$meta->uploads->meta_data = true;
			else
				$meta->uploads->meta_data = false;

			update_option( '_wpc_settings', esc_attr( json_encode( $meta ) ) );
			echo '<div id="update" class="updated notice is-dismissible"><p>Settings saved successfully!</p></div>';

			if( $flush ) {
				update_option( "_wpc_needs_flush", "1" );
			}

			wp_redirect( "admin.php?page=wpchats-settings&updated" );
			exit;

		}


	}

	public function screen() {
		
		$wpc_settings = wpc_settings();
		$this->update();

		?>

			<form method="post" class="wpc-form">
							
				<table>

					<tr>
						
						<td valign="top">
							<h3>Slugs</h3>
							<i>Custom slug for the messages and archives bases</i>
						</td>
						<td>
							<label>
								<p><strong>Messages:</strong></p>
								<input type="text" name="slugs[messages]" value="<?php echo $wpc_settings->slugs->messages; ?>" /><br/>
								<em>Messages will be at <?php echo home_url('/'); ?><strong><?php echo $wpc_settings->slugs->messages; ?></strong>/</em>
							</label>

							<p></p>

							<label>
								<p><strong>Archives:</strong></p>
								<input type="text" name="slugs[archives]" value="<?php echo $wpc_settings->slugs->archives; ?>" /><br/>
								<em>Archives will be at <?php echo home_url('/' . $wpc_settings->slugs->messages . '/'); ?><strong><?php echo $wpc_settings->slugs->archives; ?></strong>/</em>
							</label>
						</td>

					</tr>
					
					<tr>
						
						<td valign="top">
							<h3>Pagination</h3>
							<i>How many messages to show per conversation, and how many conversations to show in the messages/archives page</i>
						</td>
						<td>
							<label>
								<p><strong>Messages:</strong></p>
								<input type="number" name="pagination[messages]" value="<?php echo $wpc_settings->pagination->messages; ?>" />
							</label>

							<p></p>

							<label>
								<p><strong>Conversations:</strong></p>
								<input type="number" name="pagination[conversations]" value="<?php echo $wpc_settings->pagination->conversations; ?>" />
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

WPC_admin_screen_settings_messages::instance()->screen();