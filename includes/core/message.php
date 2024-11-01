<?php

class WPC_message
{

	protected static $instance = null;

	public static function instance() {
		return null == self::$instance ? new self : self::$instance;
	}

	function __construct() {}

	public function send( $recipient, $message, $_no_rdr = false, $args = array() ) {

		$recipient = (int) $recipient;
		$bail = false;

		if( empty( $args['success_redirect_to'] ) )
			$args['success_redirect_to'] = wpc_get_conversation_permalink( false, $recipient );

		if( empty( $args['fail_redirect_to'] ) )
			$args['fail_redirect_to'] = wpc_get_conversation_permalink( '?done=err-sending', $recipient );

		if( empty( $args['bypassNonce'] ) )
			$args['bypassNonce'] = false;

		if( ! isset( $bypassNonce ) || ! $bypassNonce  ) {
			if( ! wpc_validate_nonce() ) { $bail = true; }
		}

		if( ! wpc_can_contact( $recipient ) )
			$bail = true;

		global $wpdb;
		$table = $wpdb->prefix . WPC_TABLE;
		global $current_user;

		$message = $this->format( $message );

		if( strlen( preg_replace('/\s+/', '', str_replace( array( "&_lt;", "&_gt;" ), '<', $message ) ) ) < apply_filters( 'wpc_min_message_lenght', 2 ) ) {
			$bail = true;
		}

		$message = apply_filters( 'wpc_pre_send_message_content', $message, $recipient );

		$pm_id = $this->pm_id( $recipient )->id;
		$_ins_id = null;

		$bail = apply_filters( "WPC_message_send_bail", $bail, $recipient, $args );

		if( ! $bail ) {

			$_args = array(
				'recipient' => $recipient,
				'PM_ID'		=> $pm_id,
				'message'	=> $message,
				'sender'	=> $current_user->ID
			);

			do_action('wpc_pre_send_message', $_args );

			$wpdb->insert( 
				$table, 
				array( 
					'PM_ID'		=> $pm_id,
					'sender'	=> $current_user->ID,
					'recipient'	=> $recipient,
					'message'	=> $message,
					'date'		=> time()
				)
			);
			$_ins_id = $wpdb->insert_id;
			
			$_args['message_id'] = $_ins_id;
			
			update_option( "dbg3", get_option( "dbg3" ) . ",{$_args['message_id']}" );

			do_action('wpc_post_send_message', $_args );

		}

		if ( is_numeric( $_ins_id ) && ! $bail ) {

			do_action('wpc_after_message_sent', array('id' => $_ins_id, 'pm_id' => $pm_id, 'recipient' => $recipient));

			if( ! is_numeric( get_option( 'wpc_pm_id_' . $recipient . '_' . $current_user->ID ) ) )
				update_option( 'wpc_pm_id_' . $recipient . '_' . $current_user->ID, $pm_id );

			if( ! get_option( "_wpc_has_contacted_{$recipient}_{$current_user->ID}" ) ) {
				update_option( "_wpc_has_contacted_{$recipient}_{$current_user->ID}", 1 );
			}

			$this->archive( $pm_id, true, false, true ); // unarchiving if archived

			if( ! wpc_is_muted( $pm_id, $recipient ) ) { // if conversation is not muted by recipient
				$this->archive( $pm_id, true, $recipient, true ); // unarchiving if archived
				//$this->notify( $_ins_id );
			}

			// deleting the AJAX autosave
			delete_option( '_wpc_autosave_' . $pm_id . '_' . $current_user->ID );

			if( ! $_no_rdr ) {
				wp_redirect( $args['success_redirect_to'] );
				exit;
			}

		} else {

			if( ! $_no_rdr ) {
				wp_redirect( $args['fail_redirect_to'] );
				exit;
			}

		}

		if ( isset( $args['quick_send_get'] ) ) {
			return (object) array(
				'message' => $message,
				'ID' => (int) $_ins_id,
				'date' => time(),
				'PM_ID' => $pm_id,
				'recipient_name' => get_userdata( $recipient )->user_nicename
			);
		}

		return (int) $_ins_id; // for AJAX requests

	}

	public function pm_id( $recipient, $sender = false ) {

		global $wpdb;
		$table = $wpdb->prefix . WPC_TABLE;
		global $current_user;

		$sender = $sender ? $sender : $current_user->ID;
		$sender = (int) $sender;

		$query = $wpdb->get_results( "SELECT `PM_ID` FROM $table WHERE `sender` = '$sender' AND `recipient` = '$recipient' OR `recipient` = '$sender' AND `sender` = '$recipient' ORDER BY `ID` DESC LIMIT 1" );

		$args = new stdClass();

		if( ! empty( $query[0] ) && ! empty( $query[0]->PM_ID ) ) :;

			$args->id = (int) $query[0]->PM_ID;
			$args->exist = true;

		else:

			$option = get_option( 'wpc_pm_id_' . $recipient . '_' . $sender );
			$option2 = get_option( 'wpc_pm_id_' . $sender . '_' . $recipient );

			if( $option > '' || $option2 > '' ) {
				$args->id = $option > '' ? (int) $option : (int) $option2;
			} else {
				$args->id = (int) rand("1000000000","9999999999");
			}

			$args->exist = false;

		endif;

		return $args;



	}

	/*public function notify( $message_id ) {

		return;

		if( ! $this->get_message( $message_id ) )
			return;

		$_m = $this->get_message( $message_id );

		ob_start();
		require wpc_template_path( 'email/email-template' );
		$html = ob_get_clean();

		$recipient = $_m->recipient;
		$sender = $_m->sender;

		if( ! wpc_can_notify( $recipient ) )
			return;

		$replace = array(

			'{site_name}',
			'{site_description}',
			'{site_link}',
			'{sender_name}',
			'{user_name}',
			'{message_link}',
			'{message_id}'

		);

		$replaceWith = array(

			get_bloginfo('name'),
			get_bloginfo('description'),
			home_url(),
			get_userdata($sender)->user_nicename,
			get_userdata($recipient)->user_nicename,
			wpc_messages_base( get_userdata($sender)->user_nicename . '/', $recipient ),
			$message_id

		);

		$html = preg_replace_callback(
			"(\{message_big_link\}(.*?)\{/message_big_link\})is",
			function($m) {
				return '<a href="{message_link}" style="color: #fff;border-radius: 3px;-webkit-border-radius: 3px;display:block;text-decoration:none;text-align:center;font-weight:normal;background: #B4B9B6;margin: 17px 0px 16px;padding:20px;">' . $m[1] . '</a>';
			},
			wpc_nl2p( $html )
		);
		$html = preg_replace("/<p[^>]*><\\/p[^>]*>/", '', $html);

		$_email = get_userdata( $recipient )->user_email;
		
		/**
		  * You can overwrite the recipient email address to which we are sending the notification
		  * Example you can set a field where users can add their preferred email address
		  * rather than the one they registered with, and then use it for notifications
		  *~/
		$_email = apply_filters('wpc_user_notification_email', $_email, $recipient );
		$_subject = wpc_settings()->notifications->subject;
		$_subject = str_replace( $replace, $replaceWith, $_subject );
		$_body = str_replace( $replace, $replaceWith, $html );
		
		/**
		  * You can use this action hook to perform other actions
		  * right before performing the email notifications
		  * for instance if you don't want to notify this recipient,
		  * you would just use 
		  * wp_redirect( wpc_messages_base(false, get_userdata($recipient)->user_nicename) );
		  * followed by exit;
		  *~/
		do_action('wpc_beofre_notify_user', $recipient, $message_id);

		add_filter( 'wp_mail_content_type', array( $this, 'wpc_set_html_mail_content_type' ) );
		 
		wp_mail( $_email, $_subject, $_body );

		remove_filter( 'wp_mail_content_type', array( $this, 'wpc_set_html_mail_content_type' ) );
		 
	}*/

	public function wpc_set_html_mail_content_type() {
	    return 'text/html';
	}

	public function get_message( $message_id ) {

		$from_global = 'WPC_message_get_message_' . $message_id;
		eval( "global $$from_global;" );
		if ( ! empty( $$from_global ) ) {
			return $$from_global;
		}

		global $wpdb;
		$table = $wpdb->prefix . WPC_TABLE;
		global $current_user;

		$message_id = (int) $message_id;

		$query = WPC_cache::get_message( $message_id );/*$wpdb->get_results( "SELECT * FROM $table WHERE `ID` = '$message_id' LIMIT 1" );*/
		$args = new stdClass();

		if( ! empty( $query[0] ) && ! empty( $query[0]->PM_ID ) ) :;

			$args->ID = (int) $query[0]->ID;
			$args->PM_ID = (int) $query[0]->PM_ID;
			$args->sender = (int) $query[0]->sender;
			$args->sender_name = ! empty( get_userdata( $args->sender )->user_nicename ) ? get_userdata( $args->sender )->user_nicename : false;
			$args->recipient = (int) $query[0]->recipient;
			$args->recipient_name = ! empty( get_userdata( $args->recipient )->user_nicename ) ? get_userdata( $args->recipient )->user_nicename : false;
			$args->message = (string) stripslashes( $query[0]->message );
			$args->date = (int) $query[0]->date;
			$args->seen = ! is_null( $query[0]->seen ) ? (int) $query[0]->seen : false;
			$args->deleted = ! is_null( $query[0]->deleted ) ? (string) $query[0]->deleted : false;
			$args->contact = $current_user->ID == $args->sender ? $args->recipient : $args->sender;

		else :;

			$args = false;

		endif;

		$args = apply_filters( "WPC_message_get_message", $args );

		$GLOBALS['WPC_message_get_message_' . $message_id] = $args;

		return $args;

	}

	public function conversations( $return_all = false, $everything = false ) {

		global $wpdb;
		$table = $wpdb->prefix . WPC_TABLE;
		global $current_user;

		if( isset( $_GET['view'] ) ) {
			if( 'unread' == (string) $_GET['view'] ) {
				$query = WPC_cache::conversations('unread');
			}
		} elseif( wpc_is_search() ) {
			$query = WPC_cache::conversations('search');
		} else {
			$query = WPC_cache::conversations('all');
		}

		if( empty( $query ) && wpc_is_search() ) {
			$q_users = WPC_users::instance()->search( wpc_get_search_query(), false, WPC_users::instance()->last_contacts( -1 ), array(), true );
			if( ! empty( $q_users ) ) {
				$query = array();
				foreach( $q_users as $user ) {
					if( wpc_get_conversation_id( $user->ID ) ) {
						$query[] = (object) array( 'PM_ID' => wpc_get_conversation_id( $user->ID ) );
					}
				}
			}
		}

		$array = array();

		$return_all_index = false;
		if( 'index' == $return_all ) {
			$return_all = false;
			$return_all_index = true;
		}

		if( $return_all ) {
			foreach( $query as $q )
				$array[] = (int) $q->PM_ID;

			return array_filter( array_unique( $array ) );
		}

		if( ! empty( $query ) ) :;

			if( $everything ) {
				foreach( $query as $q ) {
					$array[] = (int) $q->PM_ID;
				}
				return array_filter( array_unique( $array )  );
			}

			if( wpc_is_archives() ) {
				foreach( $query as $q )
					if( wpc_is_archived( $q->PM_ID ) ) $array[] = (int) $q->PM_ID;
			} else {
				foreach( $query as $q )
					if( ! wpc_is_archived( $q->PM_ID ) ) $array[] = (int) $q->PM_ID;
			}

		endif;

		$array = array_filter( array_unique( $array )  );

		if( $return_all_index )
			return $array;

		//return $this->paginate( array_filter( array_unique( $array ) ) );

		return wpc_paginate( $array, wpc_settings()->pagination->conversations );

	}

	public function get_conversation( $pm_id, $exists = false, $esc_filters = false ) {

		if ( ! $exists && ! $esc_filters ) {
			$from_global = 'WPC_message_get_conversation_' . $pm_id;
			eval( "global $$from_global;" );
			if ( ! empty( $$from_global ) ) {
				return $$from_global;
			}
		}

		global $wpdb;
		$table = $wpdb->prefix . WPC_TABLE;
		global $current_user;
		$pm_id = (int) $pm_id;
		$query = WPC_cache::get_conversation( $pm_id ); /*$wpdb->get_results( "SELECT * FROM $table WHERE `PM_ID` = '$pm_id' AND NOT FIND_IN_SET('$current_user->ID', `deleted`) ORDER BY `ID` DESC LIMIT 1" );*/
		$args = new stdClass();

		if( ! empty( $query[0] ) && ! empty( $query[0]->PM_ID ) ) :;

			if( $exists ) { return true; }
			$args->contact = $current_user->ID !== $this->get_message( $query[0]->ID )->sender ? $this->get_message( $query[0]->ID )->sender : $this->get_message( $query[0]->ID )->recipient;
			$args->contact_slug = get_userdata( $args->contact ) ? get_userdata( $args->contact )->user_nicename : false;
			$args->last_message = $this->get_message( $query[0]->ID );

		else :;

			if( $exists ) { return false; }
			$args = false;

		endif;

		$args = apply_filters('wpc_get_conversation_array', $args);

		if ( ! $exists && ! $esc_filters ) {
			$GLOBALS['WPC_message_get_conversation_' . $pm_id] = $args;
		}

		if( $esc_filters ) { return $args; }
		else { return $args; }

	}

	public function snippet_classes( $pm_id ) {

		global $current_user;
		$_pm = $this->get_conversation( $pm_id, false, true );

		$classes = 'message-snippet';
		$classes .= ! $_pm->last_message->seen ? ' unread' : ' read';
		$classes .= $current_user->ID == $_pm->last_message->sender ? ' sent' : ' received';

		return $classes;


	}

	public function message_classes( $ID ) {

		global $current_user;

		$classes = 'single-message';
		$classes .= $current_user->ID == $this->get_message( $ID )->sender ? ' mine' : ' their';
		$classes = apply_filters( 'wpc_single_message_classes', $classes, $this->get_message($ID) );

		return $classes;

	}

	public function totals( $pm_id = 0, $include_deleted = false ) {
		//global $wpdb;
		//$table = $wpdb->prefix . 'mychats';
		$pm_id = (int) $pm_id;
		if( $pm_id ) {
			
			$query = WPC_cache::conversation_totals( $pm_id );

			if( $include_deleted ) {
				//$query = $wpdb->get_results("SELECT COUNT(*) AS total FROM $table WHERE `PM_ID` = '$pm_id'");
				$query = !empty( $query['data'] ) ? $query['data'] : array();
			} else {
				//global $current_user;
				//$query = $wpdb->get_results("SELECT COUNT(*) AS total FROM $table WHERE `PM_ID` = '$pm_id' AND NOT FIND_IN_SET('$current_user->ID', `deleted`)");
				$query = !empty( $query['all_data'] ) ? $query['all_data'] : array();
			}
		}
		else {
			//$query = $wpdb->get_results("SELECT COUNT(*) AS total FROM $table");
			$query = WPC_cache::all_conversation_totals();
		}

		return isset( $query[0]->total ) ? (int) $query[0]->total : null;

	}

	public function messages( $pm_id, $count = false, $all = false ) {

		//global $wpdb
		global $current_user, $wpc;
		//$table = $wpdb->prefix . WPC_TABLE;
		$pm_id = (int) $pm_id;
		$pagi = wpc_messages_pagi( $pm_id );

		/* preior caching
		$stmt = "SELECT * FROM $table WHERE `PM_ID` = '$pm_id' AND NOT FIND_IN_SET('$current_user->ID', `deleted`) ORDER BY `ID` DESC LIMIT $pagi->start,$pagi->offset";

		if( wpc_is_search() ) {
			$q = sanitize_text_field( $_GET['q'] );
			if( $q > '' ) { $stmt = "SELECT * FROM $table WHERE `PM_ID` = '$pm_id' AND `message` LIKE '%$q%' AND NOT FIND_IN_SET('$current_user->ID', `deleted`) ORDER BY `ID` DESC LIMIT $pagi->start,$pagi->offset"; }
		}

		$query = $wpdb->get_results( $stmt );

		*/

		/* caching */

		/*global $wpc;

		if ( ! empty( $wpc['current_conversation_messages'][$pm_id] ) ) {
			$query = $wpc['current_conversation_messages'][$pm_id];
		} else */
		$query = WPC_cache::get_conversation_messages( $pm_id );

		if( wpc_is_search() ) {
			$query = $this->filter_matched( $query );
		}

		if ( ! empty( $query ) ) $query = array_slice( $query, $pagi->start, $pagi->offset );

		$args = new stdClass();

		$array = array();

		if( ! empty( $query ) ) :;

			foreach( $query as $q ) {
				$_array = array_keys(explode(',', $q->deleted), $current_user->ID);
				if( ! empty( $_array ) )
					continue;

				$q->sender = (int) $q->sender;
				$q->sender_name = ! empty( get_userdata( $q->sender )->user_nicename ) ? get_userdata( $q->sender )->user_nicename : false;
				$q->recipient = (int) $q->recipient;
				$q->recipient_name = ! empty( get_userdata( $q->recipient )->user_nicename ) ? get_userdata( $q->recipient )->user_nicename : false;
				$q->message = (string) stripslashes( $q->message );
				$q->date = (int) $q->date;
				$q->seen = ! is_null( $q->seen ) ? (int) $q->seen : false;
				$q->deleted = ! is_null( $q->deleted ) ? (string) $q->deleted : false;
				$q->contact = $current_user->ID == $q->sender ? $q->recipient : $q->sender;
				$array[] = $q;
			}

		endif;

		//if( ! wpc_is_search_messages() )

		if ( $wpc['recipient']->ID !== $current_user->ID )
			$this->mark_read( $pm_id );

		if ( $array && ! wpc_is_search() && ( empty( $pagi->curr ) || $pagi->curr < 2 ) ) {
			foreach ( $array as $data ) {
				if ( $data->recipient == $current_user->ID ) {
					$meta = get_user_meta( $current_user->ID, "_wpc_json_ls_{$data->PM_ID}", 1 );
					if ( ! empty( $meta['id'] ) || ( ! empty( $meta['id'] ) && $meta['id'] < (int) $data->ID ) ) {
						update_user_meta( $current_user->ID, "_wpc_json_ls_{$data->PM_ID}", array( "id" => $data->ID, "int" => time() ) );
					}
					break;
				}
			}
		}

		if( $count )
			return (int) count( $array );

		do_action( 'wpc_pre_serve_conversation_messages', $array );

		if( $all )
			return $array;

		return $array;

		//$array = array_filter( array_reverse( $array ) );

		return wpc_paginate( $array, wpc_settings()->pagination->messages );

	}

	public function _send() {

		if( isset( $_POST['_wpc_send'] ) ) :;

			$_message = isset( $_POST['_wpc_message'] ) ? $_POST['_wpc_message'] : false;
		
			$_recipient = ! empty( wpc_get_recipient()->ID ) ? wpc_get_recipient()->ID : false;

			$this->send( $_recipient, $_message );

		endif;

	}

	public function mark_read( $pm_id = 0, $unread = false, $rdr = false, $mark_read_items = array() ) {

		global $current_user;
		$bail = false;

		if( $unread && ! wpc_can_mark_unread( $pm_id ) ) // !!!! review
			return;

		if( ! $pm_id ) { $pm_id = wpc_get_conversation_id(); }

		$_pm = $this->get_conversation( $pm_id );

		if( empty( $_pm->last_message->ID ) ) { return; }

		$ID = $_pm->last_message->ID;

		if( ! $this->get_message( $ID ) || $current_user->ID == $_pm->last_message->sender  )
			$bail = true;

		if( $_pm->last_message->seen && ! $unread )
			$bail = true;

		$bail = apply_filters('wpc_marking_unread_bail', $bail, $pm_id, $unread, $rdr );

		if( ! $bail ) :;

			$status = $unread ? null : time();

			global $wpdb;
			$table = $wpdb->prefix . WPC_TABLE;

			if( $unread ) {

				$wpdb->update( 
					$table, 
					array( 
						'seen' => null
					),
					array(
						'ID' => $ID
					)
				);

				do_action('wpc_post_mark_unread', $ID, $pm_id);

				if( wpc_is_admin_ajax() ) {
					return '1';
				}

			} else {

				if( ! empty( $mark_read_items ) ) {

					foreach( $mark_read_items as $ID ) {
						$wpdb->update( 
							$table, 
							array( 
								'seen' => time()
							),
							array(
								'ID' => $ID,
								'seen' => null
							)
						);
					}
	
					do_action('wpc_post_mark_read', $mark_read_items);

				} else {

					$wpdb->update( 
						$table, 
						array( 
							'seen' => time()
						),
						array(
							'PM_ID' => $pm_id,
							'seen' => null
						)
					);
	
					do_action('wpc_post_mark_read', $pm_id);
	
				}

			}

		endif;

		if( $rdr ) {
			$_sub = wpc_is_archived( $pm_id ) ? wpc_get_bases()->archives . '/' : '';
			$_sub .= ! $bail ? '?done=unread' : '?done=err-unread';
			wp_redirect( wpc_messages_base( $_sub ) );
			exit;
		}

		if( wpc_is_admin_ajax() ) {
			return '0';
		}


	}

	public function block( $args ) {

		if( ! wpc_is_blocking_allowed() ) {
			return;
		}

		global $current_user;

		$_doing = ! empty( $args['doing'] ) && ( 'unblock' == $args['doing'] || 'block' == $args['doing'] ) ? (string) $args['doing'] : false;
		$_target = ! empty( $args['target'] ) && get_userdata( $args['target'] ) ? (int) $args['target'] : false;
		$_redirect = ! empty( $args['redirect'] ) ? (string) $args['redirect'] : false;
		$_rdr = ! isset( $args['no_rdr'] );

		if( ! $_redirect )
			$_redirect = wpc_get_conversation_permalink( false, $_target );

		$_redirect .= is_numeric( strpos( $_redirect, '?' ) ) ? '&done=' : '?done=';

		$bail = $_doing && $_target;
		$bail = ! $bail;
		$_archives = implode(',', wpc_get_archives_list( $current_user->ID ));

		$bail = apply_filters('wpc_current_user_can_block', $bail, $_target);

		if( ! $bail ) :;

			switch( $_doing ) {

				case 'block':
					
					$array = wpc_get_user_blocked_list();
					$_array = array_keys($array, $_target);

					if( empty( $_array ) ) :;

						array_push( $array, $_target );
						$array = array_filter( array_unique( $array ) );
						$args = array(
							'user'		=> $current_user->ID,
							'blocked'	=> implode( ',', $array ),
							'notify'	=> wpc_can_notify(),
							'archives'	=> $_archives
						);
						if( $current_user->ID !== $_target ) {
							wpc_update_user_data( $args );
							do_action('wpc_post_block_user', $_target, $current_user->ID); // blocked-user, current-user:me
						}

					endif;

					if ( $_rdr ) {
						wp_redirect( $_redirect . 'block' );
						exit;
					}

					break;

				case 'unblock':

					$array = wpc_get_user_blocked_list();
					$_array = array_keys($array, $_target);
					if( ! empty( $_array ) ) :;

						foreach ( array_keys($array, $_target) as $key ) {
						    unset($array[$key]);
						}

						$array = array_filter( array_unique( $array ) );
						$args = array(
							'user'		=> $current_user->ID,
							'blocked'	=> implode( ',', $array ),
							'notify'	=> wpc_can_notify(),
							'archives'	=> $_archives
						);
						if( $current_user->ID !== $_target ) {
							wpc_update_user_data( $args );
							do_action('wpc_post_unblock_user', $_target, $current_user->ID); // blocked-user, current-user:me
						}

					endif;

					if ( $_rdr ) {
						wp_redirect( $_redirect . 'unblock' );
						exit;
					}

					break;

			}

		else :;

			if ( $_rdr ) {
				wp_redirect( $_redirect . 'err-block' );
				exit;
			}

		endif;

	}

	public function delete( $ID = false, $pm_id = false, $no_rdr = false ) {

		global $current_user;

		if( ! $ID && isset( $_GET['m'] ) )
			$ID = (int) $_GET['m'];

		if( $ID ) {

			$_message = $this->get_message( $ID );

			if( ! empty( $_message ) ) {

				$is_deleted = $this->_delete( $_message->ID );

				if( true === $is_deleted ) {

					do_action( "wpc_post_delete_item", $_message->ID );

					if( ! $no_rdr ) {
						wp_redirect( wpc_get_conversation_permalink( '?done=delete', $_message->contact ) );
						exit;
					} else {
						return true;
					}

				} else {

					if( ! $no_rdr ) {
						wp_redirect( wpc_get_conversation_permalink( '?done=err-delete', $_message->contact ) );
						exit;
					} else {
						return false;
					}

				}

			}

		} else {

			if( $pm_id ) {

				global $wpdb;
				$table = $wpdb->prefix . WPC_TABLE;
				$pm_id = (int) $pm_id; //$$$$$$$

				//$query = $wpdb->get_results( "SELECT `ID` FROM $table WHERE `PM_ID` = '$pm_id' AND NOT FIND_IN_SET('$current_user->ID', `deleted`)" );
				$query = $this->_filter_deleted( WPC_cache::get_conversation_messages( $pm_id ) );

				$array = array();

				if( ! empty( $query ) ) {

					foreach( $query as $q ) {
						$this->_delete( $q->ID );
					}

					do_action( "wpc_post_delete_item", $pm_id );

					if( ! $no_rdr ) {
						wp_redirect( wpc_messages_base( '?done=delete' ) );
						exit;
					} else {
						return true;
					}

				} else {

					if( ! $no_rdr ) {
						wp_redirect( wpc_messages_base( '?done=err-delete' ) );
						exit;
					} else {
						return false;
					}

				}

			}

		}

	}

	public function _delete( $message_id ) {

		global $current_user;

		if( $this->get_message( $message_id ) ) {

			$_message = $this->get_message( $message_id );
			$_deleted = explode( ',', $_message->deleted );
			$_array = array_keys($_deleted, $current_user->ID);

			if( empty( $_array ) ) {

				array_push( $_deleted, $current_user->ID );

				global $wpdb;
				$table = $wpdb->prefix . WPC_TABLE;

				$_deleted_val = implode(',', array_filter( array_unique( $_deleted ) ) );

				$sql = "UPDATE $table SET `deleted` = '$_deleted_val' WHERE `ID` = '$_message->ID' LIMIT 1";
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta( $sql );

				return true;

			}

		} else {
			return false;
		}

	}

	public function archive( $pm_id = false, $unarchive = false, $user_id = false, $no_rdr = false ) {

		global $current_user;

		if( ! $user_id )
			$user_id = $current_user->ID;

		if( ! $pm_id && wpc_get_conversation_id() )
			$pm_id = wpc_get_conversation_id();

		if( ! $this->get_conversation( $pm_id, true ) )
			return;

		$meta = get_user_meta( $user_id, '_wpc_data', TRUE );
		$ob = json_decode( html_entity_decode($meta), false );	

		if( ! $unarchive ) {

			if( ! wpc_is_archived( $pm_id, $user_id ) ) {

				//if( ! empty( $ob->archives ) ) {
					$array = explode( ',', $ob->archives );
					array_push( $array, $pm_id );
					$array = array_filter( array_unique( $array ) );
					$args = array(
						'user'		=> $user_id,
						'blocked'	=> implode( ',', wpc_get_user_blocked_list( $user_id ) ),
						'notify'	=> wpc_can_notify( $user_id ),
						'archives'	=> implode( ',', $array )
					);
					wpc_update_user_data( $args );

					if( wpc_is_admin_ajax() ) { return '1'; }

					if( ! $no_rdr ) {
						wp_redirect( wpc_messages_base( '?done=archive' ) );
						exit;
					}	
				//}
			}

		} else {

			if( wpc_is_archived( $pm_id, $user_id ) ) {

				$array = explode( ',', $ob->archives );
				foreach ( array_keys($array, $pm_id) as $key ) {
				    unset($array[$key]);
				}
				$array = array_filter( array_unique( $array ) );
				$args = array(
					'user'		=> $user_id,
					'blocked'	=> implode( ',', wpc_get_user_blocked_list( $user_id ) ),
					'notify'	=> wpc_can_notify( $user_id ),
					'archives'	=> implode( ',', $array )
				);
				wpc_update_user_data( $args );

				if( wpc_is_admin_ajax() ) { return '1'; }

				if( ! $no_rdr ) {
					wp_redirect( wpc_messages_base( '?done=unarchive' ) );
					exit;
				}

			}

		}

		if( wpc_is_admin_ajax() ) { return '0'; }
			
		if( ! $no_rdr ) {
			wp_redirect( wpc_get_conversation_permalink('?done=err-archive') );
			exit;
		}

	}

	public function archives( $return_all = false ) {

		$from_global = 'WPC_message_archives_' . $return_all;
		eval( "global $$from_global;" );
		if ( isset( $$from_global ) ) {
			return $$from_global;
		}

		global $current_user;
		
		$_conversations = $this->conversations( $return_all );
		$array = array();

		if( ! empty( $_conversations ) ) :;

			foreach( $_conversations as $_pm_id )
				if( wpc_is_archived( $_pm_id ) ) $array[] = $_pm_id;

		endif;

		$GLOBALS['WPC_message_archives_' . $return_all] = $array;

		return $array;


	}

	/*public function paginate( $array ) {

		if( empty( $array ) )
			return array();

		$current_page = wpc_pagination(true)->current_page;
		$res_per_pg = wpc_pagination(true)->messages_per_page;

		return array_slice( $array, ( $current_page * $res_per_pg ) - $res_per_pg, $res_per_pg );	

	}*/

	public function counts( $user_id = false, $pm_id = false ) { // not in use

		global $current_user
		     , $wpdb;

		$table = $wpdb->prefix . WPC_TABLE;

		if( ! $user_id )
			$user_id = $current_user->ID;

		# archives

		$_archives = wpc_get_archives_list( $user_id );

		if( ! empty( $_archives ) ) {
			foreach( $_archives as $_key => $_pm_id )
				if( ! $this->get_conversation( $_pm_id ) ) unset( $_archives[$_key] );
		}

		# unreads

		$_unreads = array();
		$_conversation_arr = $this->conversations( true );

		if( ! empty( $_conversation_arr ) ) {

			foreach( (array) $this->conversations( true ) as $_pm_id ) {
				if( $this->get_conversation( $_pm_id )->last_message && ! $this->get_conversation( $_pm_id )->last_message->seen ) {
					if( $user_id == $this->get_conversation( $_pm_id )->last_message->recipient ) {
						if( wpc_is_archives() ) {
							if( wpc_is_archived($_pm_id) )
								$_unreads[] = $_pm_id;
						} else {
							$_unreads[] = $_pm_id;
						}
					}
				}
			}

		}

		$_return = array();
		$_return['archives'] = count( $_archives );
		$_return['unreads'] = count( $_unreads );
		$_return['blocked'] = count( wpc_get_user_blocked_list( $user_id ) );

		if( $pm_id ) {

			//$query = $wpdb->get_results( "SELECT `ID` FROM $table WHERE `PM_ID` = '$pm_id' AND `recipient` = '$user_id' AND NOT FIND_IN_SET('$user_id', `deleted`) AND `seen` IS NULL" );

			$query = $this->_filter_deleted( WPC_cache::get_conversation_messages( $pm_id ), $user_id );

			$_return['unread_cnt'] = count( $query );

		}

		# messages

		//$query = $wpdb->get_results( "SELECT `ID`,`sender`,`recipient` FROM $table WHERE `recipient` = '$user_id' OR `sender` = '$user_id'" );
		
		$query = WPC_cache::get_all_user_messages( $user_id );

		$_sent = 0;
		$_received = 0;
		$_count_all = 0;

		if( ! empty($query ) ) {

			foreach( array_filter( $query ) as $q ) {

				if( $user_id == $q->sender  )
					$_sent += 1;
				else
					$_received += 1;

				$_count_all += 1;

			}

		}

		$_return['all'] = $_count_all;
		$_return['sent'] = $_sent;
		$_return['received'] = $_received;

		return (object) $_return;

	}

	public function report( $message_id = false, $pm_id = false, $no_rdr = false, $delete = false ) {
		return; // PRO feature
	}

	public function message_to_report() {
		return; // PRO feature
	}

	public function get_reports( $user_id = 0 ) {
		return array(); // PRO feature
	}

	public function mod_actions( $action, $target ) {

		global $wpdb;
		$table = $wpdb->prefix . WPC_TABLE;

		$target = (int) $target;

		switch ( $action ) {

			case 'm-delete':
				
				if( $this->get_message( $target ) ) {
					$wpdb->query($wpdb->prepare("DELETE FROM $table WHERE `id` = %d LIMIT 1",$target));
					WPC_cache::conversations(0,1);
					do_action( "wpc_mod_post_delete_item", $target );
					do_action( "wpc_post_delete_item", $target );
					return 1;
				}
				return false;

				break;

			case 'c-delete':
				
				if( $this->get_conversation( $target ) || ( wpc_is_mod( wp_get_current_user()->ID ) && wpc_current_user_can('watch_conversations') ) ) {
					$wpdb->query($wpdb->prepare("DELETE FROM $table WHERE `PM_ID` = %d",$target));
					WPC_cache::conversations(0,1);
					do_action( "wpc_mod_post_delete_item", $target );
					do_action( "wpc_post_delete_item", $target );
					return 1;
				}
				return false;

				break;

		}

	}

	public function json_last_message( $pm_id, $bypass_cache = false ) {

		if ( $bypass_cache ) :;

		global $wpdb
		     , $current_user;
		$table = $wpdb->prefix . WPC_TABLE;

		$stmt = "SELECT `ID` FROM $table WHERE `PM_ID` = '$pm_id' AND NOT FIND_IN_SET('$current_user->ID', `deleted`) ORDER BY `ID` DESC LIMIT 1";
		$q = $wpdb->get_results($stmt);

		else:;

		$q = $this->_filter_deleted( WPC_cache::get_conversation_messages( $pm_id ) );

		endif;

		if( ! empty( $q ) ) {
			return $this->get_message( (int) $q[0]->ID );
		}

		return false;

	}

	public function recently_contacted( $user_id = false, $limit = 10 ) {

		if( ! $user_id && is_user_logged_in() )
			$user_id = wp_get_current_user()->ID;

	}

	public static function format( $message ) {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->_format( $message );
	}


	public function _format( $message, $encode_tags = true ) {
		$message = preg_replace('#(<br */?>\s*)+#i', "\n", $message);
		if( $encode_tags ) $message = str_replace( array( "<", ">" ), array( "&_lt;", "&_gt;" ), $message );
		$message = esc_attr( strip_tags( $message, '' ) );
		return apply_filters( 'wpc_format_message_before_sent', $message );
	}

	public function has_contacted( $target_user, $current_user = 0 ) {

		if( ! $current_user ) {
			$current_user = wp_get_current_user()->ID;
		}
		if( ! get_userdata( $target_user ) || ! get_userdata( $current_user ) ) { return; }

		//global $wpdb;
		//$table = $wpdb->prefix . WPC_TABLE;
		$target_user = (int) $target_user;
		$current_user = (int) $current_user;

		return false !== get_option( "_wpc_has_contacted_{$current_user}_{$target_user}" ) || false !== get_option( "_wpc_has_contacted_{$target_user}_{$current_user}" );

		//$q = $wpdb->get_results( "SELECT COUNT(*) AS 'count' FROM $table WHERE `sender` = '$current_user' AND `recipient` = '$target_user' LIMIT 1" );

		//$q = array();


		//return ! empty( $q[0] ) && ! empty( $q[0]->count ) && (int) $q[0]->count > 0;

	}

	public static function filter_deleted( $data, $user_id = 0 ) {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->_filter_deleted( $data, $user_id );
	}
	public function _filter_deleted( $data, $user_id = 0 ) {
		
		if( ! $user_id ) { $user_id = wp_get_current_user()->ID; }

		if( ! empty( $data[0] ) ) {

			foreach ( (array) $data as $i => $item ) {
				$item = (array) $item;
				if( ! empty( $item['deleted'] ) ) {
					$explode = explode( ",", $item['deleted'] );
					if ( in_array( $user_id, $explode) ) {
						unset( $data[$i] );
					}
				}

			}

		} else {

			if( is_array( $data ) ) {

				if( ! empty( $data['deleted'] ) ) {
					$explode = explode( ",", $data['deleted'] );
					if ( in_array( $user_id, $explode) ) {
						return array();
					}
				}

			} elseif( is_object( $data ) ) {
				if( ! empty( $data->deleted ) ) {
					$explode = explode( ",", $data->deleted );
					if ( in_array( $user_id, $explode) ) {
						return array();
					}
				}
			}

		}

		return $data;
	
	}

	public static function filter_matched( $data, $query = '' ) {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->_filter_matched( $data, $query );
	}
	public function _filter_matched( $data, $query = '' ) {

		if ( ! $query ) {
			$query = wpc_get_search_query();
		}

		if ( ! empty( $query ) && ! empty( $data ) ) :;
		foreach ( (array) $data as $i => $item ) {

			$item = (array) $item;
			if( ! empty( $item['message'] ) ) {
				if( ! wpc_string_contains( $query, $item['message'] ) ) {
					unset( $data[$i] );
				}
			}

		}
		endif;

		return $data;

	}

}