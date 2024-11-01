<?php

class WPC_admin_screen_settings
{
	protected static $instance = null;

	public static function instance() {

		return null == self::$instance ? new self : self::$instance;

	}

	public function update() {

		// var_dump( $_POST );

	}

	public function screen() {
		
		$this->update();

		?>

			<h2 class="nav-tab-wrapper">
				
				<a class="nav-tab<?php wpc_admin_settings_tab_active('messages'); ?>" href="admin.php?page=wpchats-settings">
					<span>Messages</span>
				</a>

				<a class="nav-tab<?php wpc_admin_settings_tab_active('users'); ?>" href="admin.php?page=wpchats-settings&amp;tab=users">
					<span>Users</span>
				</a>

				<a class="nav-tab<?php wpc_admin_settings_tab_active('ajax'); ?>" href="admin.php?page=wpchats-settings&amp;tab=ajax">
					<span>Ajax</span>
				</a>

				<a class="nav-tab<?php wpc_admin_settings_tab_active('mailing'); ?>" href="admin.php?page=wpchats-settings&amp;tab=mailing">
					<span>Mailing</span>
				</a>

				<a class="nav-tab<?php wpc_admin_settings_tab_active('other'); ?>" href="admin.php?page=wpchats-settings&amp;tab=other">
					<span>Other</span>
				</a>

				<a class="nav-tab<?php wpc_admin_settings_tab_active('translate'); ?>" href="admin.php?page=wpchats-settings&amp;tab=translate">
					<span>Translate</span>
				</a>

			</h2>

		<?php

		if( isset($_GET["tab"]) && in_array( $_GET["tab"], array( "users", "ajax", "mailing", "other", "roles", "translate" ) ) ) {

			switch( (string) $_GET["tab"] ) {

				case 'users':
					require 'settings-users.php';
					break;

				case 'ajax':
					require 'settings-ajax.php';
					break;

				case 'mailing':
					require 'settings-mailing.php';
					break;

				case 'other':
					require 'settings-other.php';
					break;

				case 'translate':
					require 'settings-translate.php';
					break;

			}

		} else {
			if( ! isset($_GET["tab"]) || ( isset($_GET["tab"]) && ! in_array( $_GET["tab"], array( "users", "ajax", "mailing", "other", "roles", "translate" ) ) ) )
				require 'settings-messages.php';
		}

	}

}

WPC_admin_screen_settings::instance()->screen();