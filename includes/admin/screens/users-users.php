<?php

class WPC_admin_screen_users_users
{
	protected static $instance = null;

	public static function instance() {

		return null == self::$instance ? new self : self::$instance;

	}

	public function update() {}

	public function screen() {
		
		$this->update();

		$users = get_users();
		$base_url = 'admin.php?page=wpchats-users';

		if( wpc_get_search_query() > '' ) {
			$users = WPC_users::instance()->search( wpc_get_search_query(), '', $users );
			$base_url .= '&q=' . wpc_get_search_query();
		}

		$pagi = wpc_paginate( $users, 10, true );
		$users = wpc_paginate( $users, 10 );


		?>


			<div style="padding: 1em; clear: both; display: table; margin-left: auto;">
				<form method="get" action="admin.php">
					<input type="hidden" name="page" value="wpchats-users" />
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

WPC_admin_screen_users_users::instance()->screen();