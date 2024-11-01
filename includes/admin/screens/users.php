<?php

class WPC_admin_screen_users
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

		$counts = array();
		$counts['mod'] = count( wpc_moderators_list() );
		$counts['banned'] = count( wpc_banned_list() );

		require 'users-users.php';

	}

}

WPC_admin_screen_users::instance()->screen();