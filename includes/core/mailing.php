<?php

/**
  * class WPC_mailing
  * The main mailing functions used in WpChats
  * @since 3.0
  */

class WPC_mailing
{

	protected static $instance = null;

	/**
	  * Loads the class
	  */

	public static function instance() {
		return null == self::$instance ? new self : self::$instance;
	}

	public function unreads( $user_id, $add = array(), $delete_pm_id = 0 ) {

		$meta = get_user_meta( $user_id, '_wpc_mailing_unreads', 1 );

		$meta = array_filter( array_unique( explode( ",", $meta ) ) );

		if( ! empty( $add ) ) {

			if( empty( $add[0] ) ) {
				$add = array( $add );
			}

			foreach( $add as $data ) {
				$meta[] = "{$data['id']}:{$data['pm_id']}";
			}

			$meta = array_filter( array_unique( $meta ) );

			if( empty( $meta ) ) {
				delete_user_meta( $user_id, '_wpc_mailing_unreads', implode( ",", $meta ) );
			} else {
				update_user_meta( $user_id, '_wpc_mailing_unreads', implode( ",", $meta ) );
			}

			$_wpc_cron_msg_notif_users = get_option( "_wpc_cron_msg_notif_users" );
			$_wpc_cron_msg_notif_users = explode( ",", $_wpc_cron_msg_notif_users );
			$_wpc_cron_msg_notif_users[] = $user_id;
			$_wpc_cron_msg_notif_users = array_filter( array_unique( $_wpc_cron_msg_notif_users ) );

			if( ! empty( $_wpc_cron_msg_notif_users ) ) {
				update_option( "_wpc_cron_msg_notif_users", implode( ",", $_wpc_cron_msg_notif_users ) );
			} else {
				delete_option( "_wpc_cron_msg_notif_users" );
			}

			return true;

		}

		elseif( $delete_pm_id ) {

			foreach( $meta as $i => $item ) {

				$pm_id = (int) mb_substr( $item, ( mb_strpos( $item, ":" ) + 1 ) );

				if( $pm_id == $delete_pm_id ) {
					unset( $meta[$i] );
				}
			}

			if( empty( $meta ) ) {
				delete_user_meta( $user_id, '_wpc_mailing_unreads', implode( ",", $meta ) );
			} else {
				update_user_meta( $user_id, '_wpc_mailing_unreads', implode( ",", $meta ) );
			}

			return true;
		}

		$data = array();

		foreach( $meta as $i => $item ) {
			$item = array_filter( explode( ":", $item ) );
			$data[] = array(
				'id' => isset( $item[0] ) ? (int) $item[0] : 0,
				'pm_id' => isset( $item[1] ) ? (int) $item[1] : 0
			);
		}

		rsort( $data );

		$unreads = array();

		foreach( $data as $item ) {

			if( empty( $unreads[$item['pm_id']] ) ) {
				$unreads[$item['pm_id']] = array();
				$unreads[$item['pm_id']][] = $item['id'];
			} else {
				$unreads[$item['pm_id']][] = $item['id'];
			}

		}

		return $unreads;
 
	}

	public function messages_notify( $message_id ) {

		$message = wpc_get_message( $message_id );

		if( empty( $message->PM_ID ) ) {
			return;
		}

		$settings = wpc_mailing_settings();
		$email = $settings->body->message;
		$subject = $settings->subject->message;

		$replace = array(
			'[sender-name]',
			'[sender-link]',
			'[sender-avatar]',
			'[user-name]',
			'[user-link]',
			'[user-avatar]',
			'[user-settings-link]',
			'[user-settings-notifications-link]',
			'[user-unread-messages-count]',
			'[message-excerpt]',
			'[message-content]',
			'[message-date]',
			'[message-link]',
			'[message-unread-count]',
			'[site-name]',
			'[site-description]',
			'[site-url]'
		);

		$replaceWith = array(
			'[sender-name]' => wpc_get_user_name( $message->sender, 1 ),
			'[sender-link]' => apply_filters( "wpc_mail_link_text", wpc_get_user_links( $message->sender )->profile ),
			'[sender-avatar]' => _wpc_avatar_src( $message->sender ),
			'[user-name]' => wpc_get_user_name( $message->recipient, 1 ),
			'[user-link]' => apply_filters( "wpc_mail_link_text", wpc_get_user_links( $message->recipient )->profile ),
			'[user-avatar]' => _wpc_avatar_src( $message->recipient ),
			'[user-settings-link]' => apply_filters( "wpc_mail_link_text", wpc_get_user_links( $message->recipient )->edit ),
			'[user-settings-notifications-link]' => apply_filters( "wpc_mail_link_text", wpc_get_user_links( $message->recipient )->edit . 'notifications/' ),
			'[user-unread-messages-count]' => count( wpc_user_unreads_noajax( $message->recipient ) ),
			'[message-excerpt]' => $this->format_message( $message->message, apply_filters( "wpc_message_snippet_excerpt_lenght", 150 ) ),
			'[message-content]' => $this->format_message( $message->message ),
			'[message-date]' => date( apply_filters( "WPC_mailing_message_date_format", "Y-m-d H:i:s" ), $message->date ),
			'[message-link]' => apply_filters( "wpc_mail_link_text", wpc_contact_link( $message->sender, $message->recipient ) ),
			'[message-unread-count]' => count( wpc_unreads_in_pm( $message->PM_ID, $message->recipient ) ),
			'[site-name]' => get_bloginfo( 'name' ),
			'[site-description]' => get_bloginfo( 'description' ),
			'[site-url]' => apply_filters( "wpc_mail_link_text", home_url() )
		);

		$email = str_replace( $replace, $replaceWith, $email );
		$subject = str_replace( $replace, $replaceWith, $subject );
		$email = apply_filters( "wpc_email_content", $email );
		$subject = apply_filters( "wpc_email_subject_content", $subject );
		$preferences = wpc_notification_settings( $message->recipient );

		if( ! $preferences->mail->messages ) {
			return; // just making sure
		}

		do_action( "wpc_pre_send_mail", array(
			'email' 	=> $email,
			'subject' 	=> $subject,
			'address' 	=> $preferences->mail->email,
			'user_id' 	=> $message->recipient
		));

		wp_mail(
			$preferences->mail->email,
			$subject,
			$email
		);

		do_action( "wpc_post_send_mail", array(
			'email' 	=> $email,
			'subject' 	=> $subject,
			'address' 	=> $preferences->mail->email,
			'user_id' 	=> $message->recipient
		));

	}

	public function format_message( $string, $limit = 0 ) {
		$original_string = $string;
		$string = stripslashes($string);
		$string = str_replace( array( "&amp;_lt;", "&amp;_gt;" ), array( "&amp;lt;", "&amp;gt;" ), $string );
		$string = html_entity_decode( $string );
		if( (int) $limit > 0 ) {
			$maxed = mb_strlen( $string ) > (int) $limit;
			$string = mb_substr( $string, 0, (int) $limit );
			if( $maxed ) $string .= " ..";
		}
		return apply_filters( 'WPC_mailing_format_message', $string, $original_string );
	}

	public function mod_welcome( $user_to_notify, $made_by ) {
		return; // PRO feature
	}


	public function mod_instant() {
		return; // PRO feature
	}

	public function moderated_item( $option ) {
		return; // PRO feature
	}

	public function mod_summary() {
		return; // PRO feature
	}

}