<?php

class WPC_admin_screen_uploads
{
	protected static $instance = null;

	public static function instance() {

		return null == self::$instance ? new self : self::$instance;

	}

	public function update() {

		if( isset( $_POST['_action'] ) && "delete" == $_POST['_action'] ) {
			
			if( ! isset( $_POST['_wpc_nonce'] ) || ! wp_verify_nonce( $_POST['_wpc_nonce'], '_wpc_nonce' ) ) {
				echo '<div id="error" class="error notice is-dismissible"><p>Error occured, please try again.</p></div>';
				return;
			}

			$id = isset( $_POST['_id'] ) ? (int) $_POST['_id'] : 0;
			$meta = (object) wpc_get_attachement( $id );

			if( ! ( get_option('_wpc_upload_'.$id) ) || empty( $meta ) ) {
				echo '<div id="error" class="error notice is-dismissible"><p>Error occured, please try again.</p></div>';
				return;
			}

			if( file_exists( $meta->path ) ) { unlink( $meta->path ); }
			delete_option('_wpc_upload_'.$id);
			echo '<div class="updated notice is-dismissible"><p>Upload deleted successfully.</p></div>';

			do_action( "wpc_admin_post_delete_upload", $meta );

		}

	}

	public function screen() {

		$this->update();

		$action = isset( $_POST['_action'] ) ? (string) $_POST['_action'] : false;

		if( 'view' !== $action ) :;

			$list = wpc_list_uploads();

			if ( isset( $_REQUEST['author'] ) ) {

				if ( $author = get_userdata( (int) $_REQUEST['author'] ) ) {

					foreach ( $list as $i => $item ) {
						if ( empty( $item->author ) || $item->author !== $author->ID ) {
							unset( $list[$i] );
						}
					}

				}

			}

			$count_uploads = count( $list );
			$base_url = 'admin.php?page=wpchats-uploads';

			if ( ! empty( $author->ID ) ) {
				$base_url .= "&author={$author->ID}";
			}

			$pagi = wpc_paginate( $list, 10, true );
			$list = wpc_paginate( $list, 10 );

			?>

				<?php if ( ! empty( $author ) ) : ?>

					<p>Listing <?php echo $count_uploads; ?> uploads by <a href="<?php echo wpc_admin_user_link( $author->ID ); ?>"><?php echo wpc_get_user_name( $author->ID ); ?></a>:</p>

					<p><a href="admin.php?page=wpchats-uploads" class="button">&lsaquo; Back</a></p>

				<?php endif; ?>

				<?php if( count( $list ) > 0 ) { ?>

					<?php foreach( $list as $upload ) : ?>

						<div class="wpc_upload">

							<label for="view-<?php echo $upload->id; ?>"><img src="<?php echo $upload->url; ?>" alt="user uploads" style="max-width: 100%;" /></label>

							<?php if( ! file_exists( $upload->path ) ) : ?>
								<br/><em>This upload does not exist.</em>
							<?php endif; ?>

							<div class="meta">
								<span>By <a href="admin.php?page=wpchats-uploads&amp;author=<?php echo $upload->author; ?>"><?php echo wpc_get_user_name( $upload->author, true ); ?></a> - <?php echo apply_filters( "wpc_admin_upload_type_text", $upload->type ); ?> - <?php echo wpc_time_diff($upload->time, '', 'ago'); ?></span>
							</div>

							<div class="actions">
								<span>
									<form method="post" style="display: inline;">
										<input type="hidden" name="_action" value="view" />
										<input type="hidden" name="_id" value="<?php echo $upload->id; ?>" />
										<input type="submit" value="view" class="button" id="view-<?php echo $upload->id; ?>" />
									</form>
								</span>
								<span>
									<form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this upload??')">
										<input type="hidden" name="_action" value="delete" />
										<input type="hidden" name="_id" value="<?php echo $upload->id; ?>" />
										<?php wp_nonce_field('_wpc_nonce', '_wpc_nonce'); ?>
										<input type="submit" value="delete" class="button" />
									</form>
								</span>
							</div>

						</div>

					<?php endforeach; ?>

					<?php wpc_admin_the_pagination( $pagi, $base_url ); ?>

				<?php } else { ?>

					<p>There are no uploads to show for the moment, please check back soon.</p>

				<?php } ?>

			<?php

		else :;

			$id = isset( $_POST['_id'] ) ? (int) $_POST['_id'] : 0;
			$upload = (object) wpc_get_attachement($id);

			if( ! empty( $upload ) ) { ?>

				<p><form method="post" style="display: inline;"><input type="submit" value="&laquo; Back to uploads" class="button" /></form></p>

				<div class="wpc_upload">
					
					<img src="<?php echo $upload->url; ?>" alt="user uploads" style="max-height: inherit;max-width: 100%;"/>

					<?php if( ! file_exists( $upload->path ) ) : ?>
						<br/><em>This upload does not exist.</em>
					<?php endif; ?>

					<div class="meta">
						<span>By <a href="<?php echo wpc_admin_user_link( $upload->author ); ?>"><?php echo wpc_get_user_name( $upload->author, true ); ?></a> - <?php echo apply_filters( "wpc_admin_upload_type_text", $upload->type ); ?> - <?php echo wpc_time_diff($upload->time, '', 'ago'); ?></span>
					</div>

					<div class="actions">
						<span>
							<form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this upload??')">
								<input type="hidden" name="_action" value="delete" />
								<input type="hidden" name="_id" value="<?php echo $upload->id; ?>" />
								<?php wp_nonce_field('_wpc_nonce', '_wpc_nonce'); ?>
								<input type="submit" value="delete" class="button" />
							</form>
						</span>
					</div>

				</div>

			<?php } else { ?>

				<p>This upload does exist.</p>

			<?php }

		endif;
	}

}

WPC_admin_screen_uploads::instance()->screen();