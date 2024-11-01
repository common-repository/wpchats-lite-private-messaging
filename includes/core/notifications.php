<?php

class WPC_notifications
{

	protected static $instance = null;

	public static function update( $key = 0, $args = array() ) {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->_update( $key, $args );
	}

	public static function delete( $key, $user_id = 0 ) {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->_delete( $key, $user_id );
	}

	public static function get( $user_id, $meta_key ) {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->_get( $user_id, $meta_key );
	}

	public static function get_by_user( $user_id = 0, $unread = false ) {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->_get_by_user( $user_id, $unread );
	}

	public static function new_message_notify( $user_id, $pm_id, $sender ) {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->_new_message_notify( $user_id, $pm_id, $sender );
	}

	public static function mark_read( $user_id, $meta_key ) {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->_mark_read( $user_id, $meta_key );
	}

	public static function new_mod_notify( $user_id ) {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->_new_mod_notify( $user_id );
	}

	public static function new_flagged_message( $message_id, $user_id, $user_to_notify ) {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->_new_flagged_message( $message_id, $user_id, $user_to_notify );
	}

	public static function new_flagged_conversation( $pm_id, $user_id, $user_to_notify ) {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->_new_flagged_conversation( $pm_id, $user_id, $user_to_notify );
	}

	public static function formats() {
		return 0; // PRO feature
	}

	/**
	  * Update a given notification
	  * creates option if not exists, else updates the existing one
	  *
	  * @since 3.0
	  * @param $key int notification key (optional)
	  * @return str notification meta name
	  */

	public function _update( $key = 0, $args ) {
		
		return 0; // PRO feature

	}

	public function _get( $user_id, $key ) {
		return 0; // PRO feature
	} 

	/**
	  * Delete a given notification from the db
	  *
	  * @since 3.0
	  * @param $key int notification key
	  * @param $user_id int user ID
	  * @return null
	  */

	public function _delete( $key, $user_id = 0 ) {
		return 0; // PRO feature
	}

	public function add_to_umeta( $user_id, $meta_key, $remove = false ) {
		return 0; // PRO feature
	}

	public function remove_from_umeta( $user_id, $meta_key ) {
		return 0; // PRO feature
	}

	public function _mark_read( $user_id, $meta_key ) {
		return 0; // PRO feature
	}

	public function _get_by_user( $user_id = 0, $unread = false ) {
		return 0; // PRO feature
	}

	public function _new_message_notify( $user_id, $pm_id, $sender ) { // $user_id: recipient

		return 0; // PRO feature

	}

	public function _new_mod_notify( $user_id ) {
				
		return 0; // PRO feature

	}

	public function _new_flagged_message( $message_id, $user_id, $user_to_notify ) {

		return 0; // PRO feature

	}

	public function _new_flagged_conversation( $pm_id, $user_id, $user_to_notify ) {

		return 0; // PRO feature

	}

}