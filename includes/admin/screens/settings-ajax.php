<?php

class WPC_admin_screen_settings_ajax
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

			if( ! wpc_validate_nonce() ) {
				echo '<div id="error" class="error notice is-dismissible"><p>Error occured, please try again.</p></div>';
				return;
			}

			$settings = array();

			$settings['autosave'] = isset( $_POST['ajax']['autosave'] );

			if ( ! empty( $_POST['ajax']['interval'] ) ) {
				$settings['interval'] = (int) $_POST['ajax']['interval'];
			} else $settings['interval'] = false;

			if ( ! empty( $_POST['ajax']['preloader'] ) ) {
				$settings['preloader'] = (string) $_POST['ajax']['preloader'];
			} else $settings['preloader'] = false;

			if ( ! empty( $_POST['ajax']['title_selector'] ) ) {
				$settings['title_selector'] = sanitize_text_field( $_POST['ajax']['title_selector'] );
			} else $settings['title_selector'] = false;

			if ( isset( $_POST['ajax']['mutli_send'] ) ) delete_option( "wpc_ajax_disable_multiple_send", 1 );
			else update_option( "wpc_ajax_disable_multiple_send", 1 );

			update_option( "_wpc_settings_ajax", base64_encode( json_encode( $settings ) ) );
			
			echo '<div id="update" class="updated notice is-dismissible"><p>Settings saved successfully!</p></div>';

			wp_redirect( "admin.php?page=wpchats-settings&tab=ajax&updated" );
			exit;

		}


	}

	public function screen() {
		
		$this->update();
		$wpc_settings = wpc_settings();

		?>

			<form method="post" class="wpc-form">
							
				<table>

					<tr>
						
						<td valign="top">
							<h3>Auto-save</h3>
							<i>Toggle auto-save</i>
						</td>
						<td>
							<label>
								<input type="checkbox" name="ajax[autosave]" <?php checked(!empty($wpc_settings->ajax->autosave)); ?> />
								Enable auto-save
							</label>
						</td>

					</tr>

					<tr>
						
						<td valign="top">
							<h3>Interval timing</h3>
							<i>In milliseconds, set the JSON check interval timing. (1 second = 1000 milliseconds)</i>
						</td>
						<td>
							<label>
								<input type="number" name="ajax[interval]" value="<?php echo $wpc_settings->ajax->interval; ?>" />
							</label>
						</td>

					</tr>

					<tr>
						
						<td valign="top">
							<h3>Preloader</h3>
							<i>The main AJAX preloader markup</i>
						</td>
						<td>
							<label>
								<textarea name="ajax[preloader]" cols="40" rows="3"><?php echo $wpc_settings->ajax->preloader; ?></textarea>
							</label>
						</td>

					</tr>

					<tr>
						
						<td valign="top">
							<h3>Multiple send requests</h3>
							<i>Allow or disallow mutliple send requests. If allowed, users can send multiple messages at a time BUT this might overload your server and result in bad experience. Else users will have to send messages one by one (wait 'til a message is sent, then send another)</i>
						</td>
						<td>
							<label>
								<input type="checkbox" name="ajax[mutli_send]" <?php checked(!apply_filters( 'wpc_ajax_disable_multiple_send', true )); ?> />
								Enable
							</label>
						</td>

					</tr>

					<tr>
						
						<td valign="top">
							<h3>Page title element</h3>
							<i>When a request is loaded, the tab title is updated automatically. Use this field to set the HTML element selector for the page title (e.g h1 if heading), and it will be updated dynamically. Multiple selectors should be comma-separated</i>
						</td>
						<td>
							<label>
								<input type="text" name="ajax[title_selector]" value="<?php echo $wpc_settings->ajax->title_selector; ?>" placeholder="selector(s)" />
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

WPC_admin_screen_settings_ajax::instance()->screen();