<?php

class WPC_admin_screen_settings_transalte_delete
{
	protected static $instance = null;

	public static function instance() {

		return null == self::$instance ? new self : self::$instance;

	}

	public function update() {}

	public function screen() {

		if ( ! wpc_validate_nonce() ) {
			wp_redirect( "admin.php?page=wpchats-settings&tab=translate" );
			exit;
		}
		
		if ( ! empty( $_REQUEST['wpc_delete'] ) ) {

			if ( $reg = explode( ",", get_option("_wpc_registered_translations") ) ) {
				$reg = array_filter( array_unique( $reg ) );
				foreach ( $reg as $i => $name ) {
					if ( $name == $_REQUEST['wpc_delete'] ) {
						unset( $reg[$i] );
						break;
					}
				}
				if ( ! empty( $reg ) ) {
					update_option( "_wpc_registered_translations", implode( ",", $reg ) );
				} else delete_option( "_wpc_registered_translations" );
			}
			delete_option( "wpc_transaltion_" . str_replace( " ", "_", $_REQUEST['wpc_delete'] ) );
			if ( $_REQUEST['wpc_delete'] == get_option( "wpc_active_translation" ) ) {
				delete_option( "wpc_active_translation" );
			}
			wp_redirect( "admin.php?page=wpchats-settings&tab=translate&done=delete" );
			exit;

		}

	}

}

WPC_admin_screen_settings_transalte_delete::instance()->screen();