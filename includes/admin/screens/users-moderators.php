<?php

class WPC_admin_screen_users_moderators
{
	protected static $instance = null;

	public static function instance() {
		return null == self::$instance ? new self : self::$instance;
	}

	public function screen() {
		
		$list = wpc_moderators_list();
		$users = array();
		foreach ( $list as $user_id ) {
			$users[] = get_userdata($user_id );
		}

		if( empty( $users ) ) :;

			?>

				<div style="display: table; margin: 0 auto; padding: 2em; font-weight: 600; font-style: italic;">
					<p>You don't have any moderators for the moment.<br/><a href=<?php echo wpc_get_user_links()->mod; ?>moderators/add/>Add moderators?</a></p>
				</div>

			<?php

			return;

		endif;

		$base_url = 'admin.php?page=wpchats-users&tab=moderators';

		if( wpc_get_search_query() > '' ) {
			$users = WPC_users::instance()->search( wpc_get_search_query(), '', $users );
			$base_url .= '&q=' . wpc_get_search_query();
		}

		$pagi = wpc_paginate( $users, 2, true );
		$users = wpc_paginate( $users, 2 );


		?>


			<div style="padding: 1em; clear: both; display: table; margin-left: auto;">
				<form method="get" action="admin.php">
					<input type="hidden" name="page" value="wpchats-users" />
					<input type="hidden" name="tab" value="moderators" />
					<input type="text" name="q" placeholder="search .." value="<?php echo wpc_get_search_query(); ?>" />
				</form>
			</div>

			<?php if ( wpc_get_search_query() > '' ) : ?>
				<em style="display: block;margin: 1em 0;">Showing search results for "<?php echo wpc_get_search_query(); ?>":</em>
			<?php endif; ?>

			<?php if ( empty( $users ) ) : ?>
				<em>No users found.</em>
			<?php endif; ?>

			<?php foreach( $users as $user ) : ?>

				<?php require WPC_INC_PATH . 'admin/templates/users-loop-single.php'; ?>

			<?php endforeach; ?>

			<?php wpc_admin_the_pagination( $pagi, $base_url ); ?>

		<?php

	}

}

WPC_admin_screen_users_moderators::instance()->screen();