<?php

class WPC_stats
{

	protected static $instance = null;

	public static function add( $args ) {
		$class = null == self::$instance ? new self : self::$instance;
		return 0; // PRO feature
	}

	public static function get( $user_id = 0, $date = 0, $elements = array() ) {
		$class = null == self::$instance ? new self : self::$instance;
		return 0; // PRO feature
	}

	public static function get_main( $month = 0, $year = 0 ) {
		$class = null == self::$instance ? new self : self::$instance;
		return 0; // PRO feature
	}
	public function add_to_main( $args, $date = 0 ) {
		return 0; // PRO feature
	}

}