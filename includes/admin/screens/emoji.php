<?php

class WPC_admin_screen_emoji
{
	protected static $instance = null;

	public static function instance() {

		return null == self::$instance ? new self : self::$instance;

	}

	public function update() {

		if ( isset( $_POST['add'] ) ) {

			if( ! isset( $_POST['_wpc_nonce'] ) || !wp_verify_nonce( $_POST['_wpc_nonce'], '_wpc_nonce' ) ) {
				echo '<div id="error" class="error notice is-dismissible"><p>Error occured, please try again.</p></div>';
				return;
			}

			$url = isset( $_POST['url'] ) ? sanitize_text_field( $_POST['url'] ) : '';
			$symbol = isset( $_POST['symbol'] ) ? $_POST['symbol'] : '';
			$description = isset( $_POST['description'] ) ? sanitize_text_field( $_POST['description'] ) : '';
			$symbol = sanitize_text_field( str_replace(array( '<','>' ), '', $symbol) );

			if( $url > '' && $symbol > '' && $description > '' ) {

				$list = wpc_emoji_list(true);

				if( in_array( $url, $list->urls ) ) {
					echo '<div id="error" class="error notice is-dismissible"><p>This emoticon is already added.</p></div>';
					return;
				}

				elseif( in_array( $symbol, $list->symbols ) ) {
					echo '<div id="error" class="error notice is-dismissible"><p>This symbol already exists.</p></div>';
					return;
				}

				$meta = get_option('_wpc_custom_emoji');

				if( $meta > '' ) {
					$meta = html_entity_decode( stripslashes( $meta ) );
					$meta = str_replace("\'", "'", $meta);
					$meta = json_decode( $meta );
				} else {
					$meta = array();
				}

				array_push( $meta, (object) array(
					'url' => $url,
					'symbol' => $symbol,
					'description' => $description
				));

				update_option('_wpc_custom_emoji', json_encode( $meta ) ); // esc_attr will cause a lot of extra strings, we'll trust json_encode on this one

				echo '<div id="update" class="updated notice is-dismissible"><p>Custom emoticon added successfully!</p></div>';
				echo '<script type="text/javascript">window.onload=function(){for(var n=document.querySelectorAll(".wpcInput"),o=n.length-1;o>=0;o--)n[o].value=""};</script>';

				return;

			} else {
				echo '<div id="error" class="error notice is-dismissible"><p>Error occured, please make sure to fill out all fields and avoid using existing data.</p></div>';
				return;
			}

		}

		elseif ( isset( $_POST['do'] ) && "delete" == $_POST['do'] ) {
			
			if( ! isset( $_POST['key'] ) || ! isset( $_POST['_wpc_nonce'] ) || !wp_verify_nonce( $_POST['_wpc_nonce'], '_wpc_nonce' ) ) {
				echo '<div id="error" class="error notice is-dismissible"><p>Error occured, please try again.</p></div>';
				return;
			}

			wpc_admin_delete_emoji( (int) $_POST['key'] );
			echo '<div id="update" class="updated notice is-dismissible"><p>Custom emoticon deleted successfully.</p></div>';

		}

		elseif ( isset( $_POST['do'] ) && "edit" == $_POST['do'] && isset( $_POST['update'] ) ) {
			
			if( ! isset( $_POST['position'] ) || ! isset( $_POST['original_symbol'] ) || ! isset( $_POST['key'] ) || ! isset( $_POST['_wpc_nonce'] ) || !wp_verify_nonce( $_POST['_wpc_nonce'], '_wpc_nonce' ) ) {
				echo '<div id="error" class="error notice is-dismissible"><p>Error occured, please try again.</p></div>';
				return;
			}

			$_list = wpc_admin_custom_emoji_list();

			$url = isset( $_POST['url'] ) ? sanitize_text_field( $_POST['url'] ) : '';
			$symbol = isset( $_POST['symbol'] ) ? $_POST['symbol'] : '';
			$description = isset( $_POST['description'] ) ? sanitize_text_field( $_POST['description'] ) : '';
			$symbol = sanitize_text_field( str_replace(array( '<','>' ), '', $symbol) );
			$position = (int) $_POST['position'];
			$original_symbol = (string) $_POST['original_symbol'];

			if( $url > '' && $symbol > '' && $description > '' ) {

				$_list[$_POST['key']] = (object) array(
					'url' => $url,
					'symbol' => $symbol,
					'description' => $description

				);

				$list = wpc_emoji_list(true);

				if( $position > 0 ) {
					$list->urls[$position] = 'x';
					$list->symbols[$position] = 'x';
				}

				if( in_array( $url, $list->urls ) ) {
					echo '<div id="error" class="error notice is-dismissible"><p>This emoticon is already added.</p></div>';
					return;
				}

				elseif( in_array( $symbol, $list->symbols ) ) {
					echo '<div id="error" class="error notice is-dismissible"><p>This symbol already exists.</p></div>';
					return;
				}

				$meta = wpc_admin_custom_emoji_list();

				foreach( $meta as $key => $emoticon ) {
					if( $emoticon->symbol == $original_symbol ) {
						$meta[$key] = (object) array(
							'url' => $url,
							'symbol' => $symbol,
							'description' => $description
						);
					}
				}

				update_option('_wpc_custom_emoji', json_encode( array_reverse( $meta ) ) ); 

				echo '<div id="update" class="updated notice is-dismissible"><p>Custom emoticon updated successfully! <a href="admin.php?page=wpchats-emoji">&laquo; Go back</a></p></div>';
				echo '<script type="text/javascript">window.onload=function(){document.getElementById("wpc-emo-update").disabled=!0,setTimeout(function(){window.location.href="admin.php?page=wpchats-emoji"},2e3)};</script>';

				return;

			} else {
				echo '<div id="error" class="error notice is-dismissible"><p>Error occured, please make sure to fill out all fields and avoid using existing data.</p></div>';
				return;
			}

		}

	}

	public function screen() {

		$this->update();

		?>

			<?php if( ! isset( $_POST['do'] ) || ( isset( $_POST['do'] ) && "edit" !== $_POST['do'] ) ) : ?>

				<h3>My custom emoticons:</h3>

				<?php if( count( wpc_admin_custom_emoji_list() ) > 0 ) : ?>

					<table class="widefat striped">

						<tr style="background: #ececec;">
							<th style="text-decoration:underline;">emoticon</th>
							<th style="text-decoration:underline;">symbol (code)</th>
							<th style="text-decoration:underline;">description</th>
							<th style="text-decoration:underline;">actions</th>
						</tr>

						<?php foreach( wpc_admin_custom_emoji_list() as $key => $emoticon ) : ?>

							<tr>
								<td><img src="<?php echo $emoticon->url; ?>" width="50" alt="<?php echo $emoticon->description; ?>" /></td>
								<td><?php echo $emoticon->symbol; ?></td>
								<td><?php echo $emoticon->description; ?></td>
								<td>
									<form method="post" style="display: inline;">
										<input type="hidden" name="do" value="edit" />
										<input type="hidden" name="key" value="<?php echo $key; ?>" />
										<input type="hidden" name="position" value="<?php echo wpc_admin_get_emoticon_array_key($emoticon->symbol); ?>" />
										<input type="hidden" name="url" value="<?php echo $emoticon->url; ?>" />
										<input type="hidden" name="symbol" value="<?php echo $emoticon->symbol; ?>" />
										<input type="hidden" name="original_symbol" value="<?php echo $emoticon->symbol; ?>" />
										<input type="hidden" name="description" value="<?php echo $emoticon->description; ?>" />
										<?php wp_nonce_field('_wpc_nonce', '_wpc_nonce'); ?>
										<a href="javascript:;" onclick="this.parentNode.submit()">edit</a>
									</form>/
									<form method="post" style="display: inline;">
										<input type="hidden" name="do" value="delete" />
										<input type="hidden" name="key" value="<?php echo $key; ?>" />
										<?php wp_nonce_field('_wpc_nonce', '_wpc_nonce'); ?>
										<a href="javascript:;" onclick="if(confirm('Are you sure?'))this.parentNode.submit()">delete</a>
									</form>
								</td>
							</tr>

						<?php endforeach; ?>
						
						<tr style="background: #ececec;">
							<th style="text-decoration:underline;">emoticon</th>
							<th style="text-decoration:underline;">symbol (code)</th>
							<th style="text-decoration:underline;">description</th>
							<th style="text-decoration:underline;">actions</th>
						</tr>

					</table>

					<p></p>

				<?php else : ?>

					<p>You don't have any for the moment.</p>
					<hr/>

				<?php endif; ?>

				<h3>Add new emoticon:</h3>
				
				<form method="post">
					
					<p>
						<input type="url" placeholder="Emoticon URL (image)" size="40" name="url" id="wpc-emo-url" value="<?php if(isset($_POST['url']))echo stripslashes($_POST['url']); ?>" class="wpcInput" />
						<span class="button wpc-media-uploader" data-target="#wpc-emo-url">Upload</span><br/>
						<em>Enter an image URL or use the media uploader to upload it, a small-size image preferably</em>
					</p>

					<p>
						<input type="text" placeholder="Symbol (emoticon code)" size="40" name="symbol" value="<?php if(isset($_POST['symbol']))echo stripslashes($_POST['symbol']); ?>" class="wpcInput" /><br/>
						<em>Enter a symbol for this emoticon, it will be converted from text into emoji when formatting messages ( example :) (that's taken BTW) )</em>
					</p>

					<p>
						<input type="text" placeholder="Description" size="40" name="description" value="<?php if(isset($_POST['description']))echo stripslashes($_POST['description']); ?>" class="wpcInput" /><br/>
						<em>Give a little description for this emoji, like 'flying pig', 'crying panda', 'ungry cat'..</em>
					</p>

					<?php wp_nonce_field('_wpc_nonce', '_wpc_nonce'); ?>

					<input type="submit" class="button button-primary" name="add" value="Add emoticon" />

				</form>

			<?php else : ?>

				<?php if( "edit" == $_POST['do'] ) : ?>

					<h3>Edit emoticon</h3>

					<form method="post">
					
						<p>
						<input type="url" placeholder="Emoticon URL (image)" size="40" name="url" id="wpc-emo-url" value="<?php if(isset($_POST['url']))echo stripslashes($_POST['url']); ?>" class="wpcInput" />
							<span class="button wpc-media-uploader" data-target="#wpc-emo-url">Upload</span><br/>
							<em>Enter an image URL or use the media uploader to upload it, a small-size image preferably</em>
						</p>

						<p>
						<input type="text" placeholder="Symbol (emoticon code)" size="40" name="symbol" value="<?php if(isset($_POST['symbol']))echo stripslashes($_POST['symbol']); ?>" class="wpcInput" /><br/>
							<em>Enter a symbol for this emoticon, it will be converted from text into emoji when formatting messages ( example :) (that's taken BTW) )</em>
						</p>

						<p>
							<input type="text" placeholder="Description" size="40" name="description" value="<?php if(isset($_POST['description']))echo stripslashes($_POST['description']); ?>" class="wpcInput" /><br/>
							<em>Give a little description for this emoji, like 'flying pig', 'crying panda', 'ungry cat'..</em>
						</p>

						<input type="hidden" name="do" value="edit" />
						<input type="hidden" name="key" value="<?php if(isset($_POST['key']))echo $_POST['key']; ?>" />
						<input type="hidden" name="position" value="<?php if(isset($_POST['position']))echo $_POST['position']; ?>" />
						<input type="hidden" name="original_symbol" value="<?php if(isset($_POST['original_symbol']))echo $_POST['original_symbol']; ?>" />
						<?php wp_nonce_field('_wpc_nonce', '_wpc_nonce'); ?>

						<input type="submit" class="button button-primary" id="wpc-emo-update" name="update" value="Add emoticon" />
						<a href="admin.php?page=wpchats-emoji" class="button">Cancel</a>

					</form>

				<?php endif; ?>

			<?php endif; ?>

		<?php
	}

}

WPC_admin_screen_emoji::instance()->screen();