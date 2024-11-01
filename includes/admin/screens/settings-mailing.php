<?php

class WPC_admin_screen_settings_mailing
{
	protected static $instance = null;

	public static function instance() {

		return null == self::$instance ? new self : self::$instance;

	}

	public function update() {

		if( isset( $_POST['submit'] ) ) {

			function ____wpc_get_val( $data ) {
				if( empty( $data ) ) return '';
				return str_replace( array("'", "\""), array("&apos;", "&quot;"), stripslashes($data) );
			}

			
			$data = $_REQUEST;

			$settings = $settings['subject'] = $settings['body'] = array();


			$settings['html'] = ! empty( $data['mailing']['html'] );

			$settings['subject']['message'] = ____wpc_get_val( $data['mailing']['subject']['messages']['messages'] );
			$settings['subject']['mod_welcome'] = ____wpc_get_val( $data['mailing']['subject']['mod']['welcome'] );
			$settings['subject']['mod_instant'] = ____wpc_get_val( $data['mailing']['subject']['mod']['instant'] );
			$settings['subject']['mod_summary'] = ____wpc_get_val( $data['mailing']['subject']['mod']['summary'] );

			$settings['body']['message'] = ____wpc_get_val( $data['mailing_body_messages'] );
			$settings['body']['mod_welcome'] = ____wpc_get_val( $data['mailing_body_mod_welcome'] );
			$settings['body']['mod_instant'] = ____wpc_get_val( $data['mailing_body_mod_instant'] );
			$settings['body']['mod_summary'] = ____wpc_get_val( $data['mailing_body_mod_summary'] );

			update_option( "_wpc_settings_mailing", base64_encode( json_encode( $settings ) ) );

			if( ! wpc_validate_nonce() ) {
				echo '<div id="error" class="error notice is-dismissible"><p>Error occured, please try again.</p></div>';
				return;
			}
			
			echo '<div id="update" class="updated notice is-dismissible"><p>Settings saved successfully!</p></div>';

		}


	}

	public function screen() {
		
		$this->update();

		$settings = wpc_mailing_settings();
		
		?>

			<style type="text/css">table.wpc-shortcodes { border: 0; } table.wpc-shortcodes td {} table.wpc-shortcodes tr {} table.wpc-shortcodes th { text-align: left; } table.wpc-shortcodes th, table.wpc-shortcodes td { border: 1px solid #ddd; padding: 1em; }</style>

			<form method="post" class="wpc-form">
							
				<table>

					<tr><td style="padding:0"><h2>1. Content-TYPE</h2></td></tr>

					<tr><td style="padding:0"><label><input type="checkbox" name="mailing[html]" <?php checked($settings->html); ?> />Enable HTML emails <em style="cursor:help" title="If unchecked, plain-text emails will be sent">(?)</em></label></td></tr>

					<tr><td style="padding:0"><h2>2. Email content</h2></td></tr>


					<tr>
							
						<td valign="top"><h2>Accepted shortcodes:</h2></td>

						<td>

							<table class="wpc-shortcodes">
								<tr>
									<th>shortcode</th> <th>description</th> <th>usage <em style="cursor:help" title="The groups where this shortcode can be used (below)">(?)</em></th>
								</tr>
								<tr><td><code>[sender-name]</code></td> <td>displays the sender display name</td> <td>2.1, 2.2.1</td> </tr>
								<tr><td><code>[sender-link]</code></td> <td>link to the sender's profile</td> <td>2.1, 2.2.1</td> </tr>
								<tr><td><code>[sender-avatar]</code></td> <td>returns the avatar image source URI of the sender</td> <td>2.1, 2.2.1</td> </tr>

								<tr><td><code>[user-name]</code></td> <td>displays this user (email recipient) name</td> <td>all groups</td> </tr>
								<tr><td><code>[user-link]</code></td> <td>links to the email recipient's profile</td> <td>all groups</td> </tr>
								<tr><td><code>[user-avatar]</code></td> <td>returns the email recipient's avatar img src URI</td> <td>all groups</td> </tr>
								<tr><td><code>[user-settings-link]</code></td> <td>links to the email recipient's profile edit page</td> <td>all groups</td> </tr>
								<tr><td><code>[user-settings-notifications-link]</code></td> <td>links to the email recipient's profile edit notifications settings page</td> <td>all groups</td> </tr>
								<tr><td><code>[user-unread-messages-count]</code></td> <td>returns the count of unread messages this user has</td> <td>all groups</td> </tr>

								<tr><td><code>[message-excerpt]</code></td> <td>prints the message body excerpt of <?php echo apply_filters( 'wpc_message_snippet_excerpt_lenght', 150 ); ?> characters lentgh</td> <td>2.1</td> </tr>
								<tr><td><code>[message-content]</code></td> <td>prints the full message body</td> <td>2.1</td> </tr>
								<tr><td><code>[message-date]</code></td> <td>prints the message date</td> <td>2.1</td> </tr>
								<tr><td><code>[message-link]</code></td> <td>links to the conversation</td> <td>2.1</td> </tr>
								<tr><td><code>[message-unread-count]</code></td> <td>returns the unread messages count from this conversation</td> <td>2.1</td> </tr>

								<tr><td><code>[site-name]</code></td> <td>prints the blog name</td> <td>all groups</td> </tr>
								<tr><td><code>[site-description]</code></td> <td>prints the blog description</td> <td>all groups</td> </tr>
								<tr><td><code>[site-url]</code></td> <td>prints the home URL</td> <td>all groups</td> </tr>

								<tr><td><code>[moderated-item]</code></td> <td>outputs the moderated message/conversation info.</td> <td>2.2.2</td> </tr>
								<tr><td><code>[moderated-items]</code></td> <td>outputs the moderated messages/conversations info.</td> <td>2.2.3</td> </tr>
								<tr><td><code>[moderation-panel-link]</code></td> <td>prints a link to the moderation panel</td> <td>2.2</td> </tr>
							</table>
						</td>

					</tr>

					<tr><td style="padding:0"><h2>2. 1. Messages</h2></td><td><p>Notify users about new messages when offline</p></td></tr>

					<tr>
						
						<td valign="top" style="padding:0">
							<h4>Subject</h4>
							<i>A subject text for the notification email</i>
						</td>
						<td>
							<label>
								<input type="text" value="<?php echo $settings->subject->message; ?>" size="80" name="mailing[subject][messages][messages]" />
							</label>
						</td>

					</tr>

					<tr>
						
						<td valign="top">
							<h4>Body</h4>
							<i>The email body</i>
						</td>
						<td>
							<?php wp_editor( $settings->body->message, 'mailing_body_messages' ); ?>
						</td>

					</tr>

					<tr><td style="padding:0"><h2>2. 2. Moderation</h2></td></tr>
					<tr><td style="padding:0"><h4>2. 2. 1. Welcoming email</h4></td><td><p>Notifying and welcoming new moderators</p></td></tr>

					<tr>
						
						<td valign="top" style="padding:0">
							<h4>Subject</h4>
							<i>A subject text for the notification email</i>
						</td>
						<td>
							<label>
								<input type="text" value="<?php echo $settings->subject->mod_welcome; ?>" size="80" name="mailing[subject][mod][welcome]" />
							</label>
						</td>

					</tr>

					<tr>
						
						<td valign="top">
							<h4>Body</h4>
							<i>The email body</i>
						</td>
						<td>
							<?php wp_editor( $settings->body->mod_welcome, 'mailing_body_mod_welcome' ); ?>
						</td>

					</tr>

					<tr><td style="padding:0"><h4>2. 2. 2. Instant reports</h4></td><td><p>Notify moderators about new flagged items</p></td></tr>

					<tr>
						
						<td valign="top" style="padding:0">
							<h4>Subject</h4>
							<i>A subject text for the notification email</i>
						</td>
						<td>
							<label>
								<input type="text" value="<?php echo $settings->subject->mod_instant; ?>" size="80" name="mailing[subject][mod][instant]" />
							</label>
						</td>

					</tr>

					<tr>
						
						<td valign="top">
							<h4>Body</h4>
							<i>The email body</i>
						</td>
						<td>
							<?php wp_editor( $settings->body->mod_instant, 'mailing_body_mod_instant' ); ?>
						</td>

					</tr>

					<tr><td style="padding:0"><h4>2. 2. 3. Daily summaries reports</h4></td><td><p>Notify moderators with daily summaries about flagged items</p></td></tr>

					<tr>
						
						<td valign="top" style="padding:0">
							<h4>Subject</h4>
							<i>A subject text for the notification email</i>
						</td>
						<td>
							<label>
								<input type="text" value="<?php echo $settings->subject->mod_summary; ?>" size="80" name="mailing[subject][mod][summary]" />
							</label>
						</td>

					</tr>

					<tr>
						
						<td valign="top">
							<h4>Body</h4>
							<i>The email body</i>
						</td>
						<td>
							<?php wp_editor( $settings->body->mod_summary, 'mailing_body_mod_summary' ); ?>
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

WPC_admin_screen_settings_mailing::instance()->screen();