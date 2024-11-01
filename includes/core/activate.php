<?php

class WPC_activate
{

	protected static $instance = null;

	public static function instance() {
		return null == self::$instance ? new self : self::$instance;
	}

	public function __construct() {

		register_activation_hook( WPC_FILE, function() {

			// schedule crons upon plugin activation
			if( ! wp_next_scheduled( 'wpc_notifications_check' ) ) {  
				wp_schedule_event( time(), 'every_few_minutes', 'wpc_notifications_check' );  
			}
			if( ! wp_next_scheduled( 'wpc_notifications_check_daily' ) ) {  
				wp_schedule_event( time(), 'daily', 'wpc_notifications_check_daily' );  
			}
			if( ! wp_next_scheduled( 'wpc_collect_transients_garbage' ) ) { 
				wp_schedule_event( time(), 'wpc_weekly', 'wpc_collect_transients_garbage' );  
			}

			global $wpdb;
			$table = $wpdb->prefix . WPC_TABLE; 
			$charset_collate = $wpdb->get_charset_collate();
			$sql = "CREATE TABLE IF NOT EXISTS $table (
			  `ID` bigint(20) NOT NULL AUTO_INCREMENT,
			  `PM_ID` bigint(20) NOT NULL,
			  `recipient` bigint(20) NOT NULL,
			  `sender` bigint(20) NOT NULL,
			  `message` LONGTEXT NOT NULL,
			  `date` bigint(20) NOT NULL,
			  `seen` bigint(20),
			  `deleted` varchar(10) DEFAULT '',
			  UNIQUE (`ID`)
			) $charset_collate;";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );

			$db_ver = get_option('wpc_db_version');

			if( '0.2.1' !== $db_ver ) { // upgrading the database table if exists
				
				$query = $wpdb->get_results( "DESCRIBE $table" );

				if( ! empty( $query ) ) :;

					foreach ( $query as $col ) {

						if( empty( $col->Field ) )
							continue;

						if ( 'id' == $col->Field ) {
							$wpdb->query( "ALTER TABLE $table CHANGE `id` `ID` bigint(20) NOT NULL AUTO_INCREMENT" );
						}

						elseif ( 'chat_id' == $col->Field ) {
							$wpdb->query( "ALTER TABLE $table CHANGE `chat_id` `PM_ID` bigint(20) NOT NULL" );
						}

						elseif ( 'from' == $col->Field ) {
							$wpdb->query( "ALTER TABLE $table CHANGE `from` `sender` bigint(20) NOT NULL" );
						}

						elseif ( 'to' == $col->Field ) {
							$wpdb->query( "ALTER TABLE $table CHANGE `to` `recipient` bigint(20) NOT NULL" );
						}

						elseif ( 'body' == $col->Field ) {
							$wpdb->query( "ALTER TABLE $table CHANGE `body` `message` LONGTEXT NOT NULL" );
						}

						elseif ( 'time' == $col->Field ) {
							$wpdb->query( "ALTER TABLE $table CHANGE `time` `date` bigint(20) NOT NULL" );
						}

					}
					
				endif;

			}

			update_option('wpc_db_version', '0.2.1');

			$_parent_post = (int) get_option('_wpc_page');

			if( ! get_post( $_parent_post ) ) {

				$args = array(
					'post_title' 	=> 'WpChats',
					'post_name' 	=> 'wpchats',
					'post_type' 	=> 'page',
					'post_status' 	=> 'publish',
					'post_content'  => '[wpc_loaded_template]',
				);
				$post_id = wp_insert_post( $args );

				update_option('_wpc_page', $post_id);

			}
			
			update_option('_wpc_needs_flush', '1');
			update_option('_wpc_activate_ver', WPC_VER);

		});

		register_deactivation_hook( WPC_FILE, function() {
			// unschedule crons upon plugin deactivation
			wp_unschedule_event( wp_next_scheduled( 'wpc_notifications_check' ), 'wpc_notifications_check' );
			wp_unschedule_event( wp_next_scheduled( 'wpc_notifications_check_daily' ), 'wpc_notifications_check_daily' );
			wp_unschedule_event( wp_next_scheduled( 'wpc_collect_transients_garbage' ), 'wpc_collect_transients_garbage' );
		});

	}

}

WPC_activate::instance();