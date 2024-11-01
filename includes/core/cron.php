<?php

/**
  * class WPC_cron
  * The main schedule events used in WpChats
  * @since 3.0
  */

class WPC_cron
{

	protected static $instance = null;

	/**
	  * Loads the class
	  */

	public static function instance() {
		return null == self::$instance ? new self : self::$instance;
	}

	public function __construct(){
		add_action('init', array( &$this, 'init' ));
	}

	public function init() {

		add_action( 'wpc_notifications_check', array( &$this, 'message_notifications' ) );
		add_action( 'wpc_collect_transients_garbage', array( &$this, 'collect_transients_garbage' ) );

	}

	public function message_notifications() {

		$_wpc_cron_msg_notif_users = get_option( "_wpc_cron_msg_notif_users" );
		$_wpc_cron_msg_notif_users = explode( ",", $_wpc_cron_msg_notif_users );
		$_wpc_cron_msg_notif_users = array_filter( array_unique( $_wpc_cron_msg_notif_users ) );

		foreach( $_wpc_cron_msg_notif_users as $i => $user_id ) {
			$user_id = (int) $user_id;

			$unreads = WPC_mailing::instance()->unreads( $user_id );

			if( empty( $unreads ) ) { continue; }

			foreach( $unreads as $pm_id => $messages ) {
				if( empty( $messages[0] ) ) {
					continue;
				}
				// notify the user
				WPC_mailing::instance()->messages_notify( (int) $messages[0] );
				// unset from to-notify users list
				WPC_mailing::instance()->unreads( $user_id, array(), $pm_id );
			}

			unset( $_wpc_cron_msg_notif_users[$i] );
		}

		if( ! empty( $_wpc_cron_msg_notif_users ) ) {
			update_option( "_wpc_cron_msg_notif_users", implode( ",", $_wpc_cron_msg_notif_users ) );
		} else {
			delete_option( "_wpc_cron_msg_notif_users" );
		}

	}

	public function collect_transients_garbage() {

		$allow_operation = apply_filters( "_wpc_cron_delete_expired_transients", true );

		if ( ! $allow_operation ) return;

		global $wpdb;
		$table = $wpdb->prefix . 'options';

		$query = $wpdb->get_results( "SELECT `option_name` FROM $table WHERE `option_name` LIKE '%_transient_wpc%'" );

		if ( ! empty( $query ) ) {
			foreach ( $query as $item ) {
				$timeout = (int) get_option( str_replace( "_transient_wpc", "_transient_timeout_wpc", $item->option_name ) );
				if ( $timeout && ( $timeout - time() < 0 ) ) {
					delete_transient( mb_substr( $item->option_name, mb_strlen('_transient_')) );
				}
			}
		}


	}

}

WPC_cron::instance();