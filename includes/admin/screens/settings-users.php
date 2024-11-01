<?php

class WPC_admin_screen_settings_users
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

			$settings = $settings['slugs'] = $settings['social'] = $settings['social_more'] = array();

			if ( ! empty( $_POST['slugs']['users'] ) ) {
				if ( wpc_get_bases()->users !== $_POST['slugs']['users'] ) {
					update_option( "_wpc_needs_flush", 1 );
				}
				$settings['slugs']['users'] = sanitize_text_field( $_POST['slugs']['users'] );
			}

			if ( ! empty( $_POST['slugs']['mod'] ) ) {
				if ( wpc_get_bases()->mod !== $_POST['slugs']['mod'] ) {
					update_option( "_wpc_needs_flush", 1 );
				}
				$settings['slugs']['mod'] = sanitize_text_field( $_POST['slugs']['mod'] );
			}

			if ( ! empty( $_POST['pagination'] ) ) {
				$settings['pagination'] = (int) $_POST['pagination'];
			}

			$settings['preferences'] = isset( $_POST['preferences']['toggle'] );

			if ( ! empty( $_POST['preferences']['on_cant_view'] ) ) {
				$settings['on_cant_view_profile'] = sanitize_text_field( $_POST['preferences']['on_cant_view'] );
			}

			if ( ! empty( $_POST['social'] ) ) {
				foreach ( $_POST['social'] as $id => $on ) {
					$settings['social'][] = $id;
				}
			}

			if ( ! empty( $_POST['social_more'] ) ) {

				$social_list = wpc_admin_get_all_social_profiles();

				foreach ( $_POST['social_more'] as $social ) {

					if ( ! sanitize_text_field( $social['name'] ) ) continue;

					if ( ! empty( $social_list[$social['name']] ) ) {
						echo "<div id=\"error\" class=\"error notice is-dismissible\"><p>\"{$social['name']}\" already exists, please provide a unique name.</p></div>";
						continue;
					}

					if ( ! empty( $settings['social_more'] ) ) :;
					foreach ( $settings['social_more'] as $data ) {
						if ( ! empty( $data['name'] ) && $data['name'] == $social['name'] ) {
							continue 2;
						}
					}
					endif;

					$settings['social_more'][] = array(
						"name" => sanitize_text_field( $social['name'] ),
						"icon" => ! empty( $social['icon'] ) ? sanitize_text_field( $social['icon'] ) : false
					);

				}
			}

			update_option( "_wpc_settings_users", json_encode( $settings ) );

			echo '<div id="update" class="updated notice is-dismissible"><p>Settings saved successfully!</p></div>';

			wp_redirect( "admin.php?page=wpchats-settings&tab=users&updated" );
			exit;

		}


	}

	public function screen() {
		
		$this->update();
		$social_list = wpc_social_list();
		$wpc_settings = wpc_settings();
		$custom_settings = json_decode( get_option( "_wpc_settings_users" ), true );

		?>

			<form method="post" class="wpc-form">
							
				<table>

					<tr>
						
						<td valign="top">
							<h3>Slugs</h3>
							<i>Custom slug and bases</i>
						</td>
						<td>
							<label>
								<p><strong>Users:</strong></p>
								<input type="text" name="slugs[users]" value="<?php echo $wpc_settings->slugs->users; ?>" /><br/>
								<em>Users will be at <?php echo home_url('/'); ?><strong><?php echo $wpc_settings->slugs->users; ?></strong>/</em>
							</label>
						</td>

					</tr>

					<tr>
						
						<td valign="top">
							<h3>Pagination</h3>
							<i>How many users to show per page</i>
						</td>
						<td>
							<label>
								<input type="number" name="pagination" value="<?php echo $wpc_settings->pagination->users; ?>" /><br/>
							</label>
						</td>

					</tr>

					<tr>
						
						<td valign="top">
							<h3>Social profiles</h3>
							<i>Edit user social profiles</i>
						</td>
						<td>
							
							<?php foreach( wpc_admin_get_all_social_profiles() as $id => $name ) : ?>

								<label>
									<input type="checkbox" name="social[<?php echo $id; ?>]" <?php checked( !empty( $social_list[$id] ) ); ?> /><?php echo $name; ?>
								</label><br/>

							<?php endforeach; ?>

							<p><strong>Add more</strong></p>

							<p>
							<?php if ( ! empty( $custom_settings['social_more'] ) ) : $i = 1; ?>

								<?php foreach ( $custom_settings['social_more'] as $data ) : ?>

									<input type="text" name="social_more[<?php echo $i; ?>][name]" placeholder="name (required)" value="<?php echo $data['name']; ?>" />
									<input type="text" name="social_more[<?php echo $i; ?>][icon]" placeholder="font icon class" size="30" value="<?php echo $data['icon']; ?>" />
									<br/><?php $i++; ?>

								<?php endforeach; ?>

							<?php else : $i = 1;  endif; ?>

							<input type="text" name="social_more[<?php echo $i; ?>][name]" placeholder="name (required)" />
							<input type="text" name="social_more[<?php echo $i; ?>][icon]" placeholder="font icon class" size="30" />
							<br/><?php $i++; ?>
							<input type="text" name="social_more[<?php echo $i; ?>][name]" placeholder="name (required)" />
							<input type="text" name="social_more[<?php echo $i; ?>][icon]" placeholder="font icon class" size="30" />
							</p>

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

WPC_admin_screen_settings_users::instance()->screen();