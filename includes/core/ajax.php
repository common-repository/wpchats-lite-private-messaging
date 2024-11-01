<?php

class WPC_ajax
{

	protected static $instance = null;

	public static function instance() {

		return null == self::$instance ? new self : self::$instance;

	}

	function __construct() {

		add_action( 'wp_ajax_wpc_send', array( &$this, 'wpc_wp_ajax_wpc_send' ) );
		add_action( 'wp_ajax_wpc_json', array( &$this, 'wpc_wp_ajax_wpc_json' ) );
		add_action( 'wp_ajax_nopriv_wpc_json', array( &$this, 'wpc_wp_ajax_wpc_json' ) );
		add_action( 'wp_ajax_wpc', array( &$this, 'wpc_wp_ajax_wpc' ) );
		add_action( 'wp_ajax_nopriv_wpc', array( &$this, 'wpc_wp_ajax_wpc' ) );
		add_action( 'wp_ajax_wpc_actions', array( &$this, 'wpc_wp_ajax_wpc_actions' ) );
		add_action( 'wp_ajax_wpc_time', array( &$this, 'wpc_wp_ajax_wpc_time' ) );
		add_action( 'wp_ajax_nopriv_wpc_time', array( &$this, 'wpc_wp_ajax_wpc_time' ) );
		add_action( 'wp_ajax_wpc_upload', array( &$this, 'wpc_wp_ajax_wpc_upload' ) );
		add_action( 'wp_ajax_nopriv_wpc_upload', array( &$this, 'wpc_wp_ajax_wpc_upload' ) );
		add_action( 'wp_ajax_wpc_get_users', array( &$this, 'wpc_wp_ajax_get_users' ) );
		add_action( 'wp_ajax_nopriv_wpc_get_users', array( &$this, 'wpc_wp_ajax_get_users' ) );
		add_action( 'wp_ajax_wpc_lazy_load', array( &$this, 'wpc_wp_ajax_lazy_load' ) );
		add_action( 'wp_ajax_nopriv_wpc_lazy_load', array( &$this, 'wpc_wp_ajax_lazy_load' ) );

		do_action( 'wpc_pre_ajax_load' );
		
	}
	
	public function wpc_wp_ajax_wpc_send() {
		do_action( 'wpc_pre_ajax_load' );
		echo WPC_ajax::instance()->_send();
		die();
	}

	public function wpc_wp_ajax_wpc_json() {
		do_action( 'wpc_pre_ajax_load' );
		header('Content-Type: text/plain; charset=utf-8');
		echo WPC_ajax::instance()->_json();
		die();
	}
	
	public function wpc_wp_ajax_wpc() {
		do_action( 'wpc_pre_ajax_load' );
		echo wpc_load_template();
		die();
	}
	
	public function wpc_wp_ajax_wpc_actions() {
		do_action( 'wpc_pre_ajax_load' );
		echo WPC_ajax::instance()->_actions();
		die();
	}

	public function wpc_wp_ajax_wpc_time() {
		do_action( 'wpc_pre_ajax_load' );
		//header("Content-type: application/json; charset=utf-8");
		//echo json_encode(json_decode( WPC_ajax::instance()->_time() ));
		echo WPC_ajax::instance()->_time();
		die();
	}

	public function wpc_wp_ajax_wpc_upload() {
		do_action( 'wpc_pre_ajax_load' );
		echo WPC_ajax::instance()->_upload();
		die();
	}

	public function wpc_wp_ajax_get_users() {
		do_action( 'wpc_pre_ajax_load' );
		echo WPC_ajax::instance()->_get_users();
		die();
	}

	public function wpc_wp_ajax_lazy_load() {
		do_action( 'wpc_pre_ajax_load' );
		echo WPC_ajax::instance()->lazy_load();
		die();
	}

	public function _send() {

		$_r = isset( $_POST['_wpc_r'] ) ? (int) $_POST['_wpc_r'] : 0;
		$_m = isset( $_POST['_wpc_message'] ) ? (string) $_POST['_wpc_message'] : '';

		if( empty( $_POST ) || ! ( $_r > 0 ) || ! ( $_m > '' ) )
			return 0;

		/**
		  * Avoid the non-ending delay resulted when server has received many requests which caused
		  * it to delay, or when an error is occured and the message is still pending send
		  */
		set_time_limit( apply_filters( "wpc_ajax_send_time_limit", 20 ) );
		// Hiding the timeout error log for a valid AJAX response
		error_reporting(0);

		$message = WPC_message::instance()->send( $_r, $_m, true, array( 'quick_send_get' => 1 ) );

		if( empty( $message ) )
			return 0;

		return '{
			"message": {
				"content": ' . json_encode( wpc_output_message( $message->message, true ) ) . ',
				"original_content": ' . json_encode( $message->message ) . ',
				"id": "' . $message->ID . '",
				"date": "' . $message->date . '",
				"recipient_slug": "' . $message->recipient_name . '",
				"pm_id": "' . $message->PM_ID . '"
			}
		}';

	}

	public function _json() {

		//$startime = microtime(1);

		$current_user = wp_get_current_user();
		$_unreads = $_unreads_excerpts = array();
		//$unreads = wpc_user_unreads( $current_user->ID );


		/*if ( isset( $_GET['dbg'] ) ) {
		header("Content-type: text/html");
		}*/

		$meta = wpc_json_alt_get_unreads( $current_user->ID );
		if ( empty( $meta ) ) $meta = array();
		
		$unreads_alt = $unreads_excerpt_alt = $_unreads_excerpt_alt = array();

		foreach ( $meta as $i => $data ) {
		
			foreach ( $data as $id => $time ) {
				$unreads_alt[] = array(
					'id' => $id,
					'pm_id' => $i
				);
				$_unreads_excerpt_alt[$i] = $id;
			}

		}

		foreach ( $_unreads_excerpt_alt as $i => $id ) {
			$unreads_excerpt_alt[] = array(
				'id' => $id,
				'pm_id' => $i
			);
		}

		if( ! empty( $unreads_alt ) ) {
			/*$_pms = $_ele = array();
			foreach( $unreads as $unread ) {
				if( ! isset( $_pms[$unread->pm_id] ) ) { $_pms[$unread->pm_id] = 0; }
				$_pms[$unread->pm_id] += 1;
			}
			$unreads = (array) $unreads;*/
			

			foreach( $unreads_alt as $i => $unread ) {
				$unread = (object) $unread;
				$data = wpc_get_message($unread->id);
				$item = array();
				// message ID
				$item['message_id'] = $unread->id;
				// conversation ID
				$item['pm_id'] = $unread->pm_id;
				// excerpt
				$item['excerpt'] = wpc_message_snippet_excerpt($unread->id);
				// content
				$item['content'] = wpc_output_message($data->message);
				// date
				$item['date'] = $data->date;
				// date diff
				$item['date_diff'] = wpc_time_diff($data->date);
				// sender name
				$item['sender'] = _wpc_messages_validate_user_name_($wpc_get_user_name = wpc_get_user_name($data->sender));
				// sender ID
				$item['sender_id'] = $data->sender;
				// sender avatar
				$item['sender_avatar'] = _wpc_avatar_src($data->sender);
				// sender link
				$item['sender_link'] = wpc_get_user_links($data->sender)->profile;
				$item['sender_slug'] = get_userdata($data->sender)->user_nicename;
				// unread count in this PM
				$item['unread_count'] = wpc_count_unreads_in_pm($unread->pm_id);
				
				$item['sender_name'] = $wpc_get_user_name;
				$item['sender_short_name'] = wpc_get_user_name($data->sender, 1);

				$item['sender_int'] = $last_seen = wpc_get_user_last_seen($data->sender, true)->integer;
				$item['sender_online'] = wpc_is_online($data->sender);
				$item['sender_status_inner'] = wpc_time_diff( $last_seen );
				// count unreads in conv // to be maintained
				$item['counts'] = $_pms[$unread->pm_id];
				$_unreads[] = $item;
				if ( ! empty( $_REQUEST['current'] ) && $unread->pm_id == (int) $_REQUEST['current'] ) {
					update_user_meta( $current_user->ID, "_wpc_json_ls_{$unread->pm_id}", array( "id" => $unread->id, "int" => time() ) );
				}
			}

			foreach( $unreads_excerpt_alt as $unread ) {
				$unread = (object) $unread;
				/*if ( ! empty( $_ele[$unread->pm_id] ) ) {
					continue;
				}
				$_ele[$unread->pm_id] = $unread->id;*/
				$data = wpc_get_message($unread->id);
				$item = array();
				// message ID
				$item['message_id'] = $unread->id;
				// conversation ID
				$item['pm_id'] = $unread->pm_id;
				// excerpt
				$item['excerpt'] = wpc_message_snippet_excerpt($unread->id);
				// content
				$item['content'] = wpc_output_message($data->message);
				// date
				$item['date'] = $data->date;
				// date diff
				$item['date_diff'] = wpc_time_diff($data->date);
				// sender name
				$item['sender'] = _wpc_messages_validate_user_name_($wpc_get_user_name = wpc_get_user_name($data->sender));
				// sender ID
				$item['sender_id'] = $data->sender;
				// sender avatar
				$item['sender_avatar'] = _wpc_avatar_src($data->sender);
				// sender link
				$item['sender_link'] = wpc_get_user_links($data->sender)->profile;
				$item['sender_slug'] = get_userdata($data->sender)->user_nicename;
				// unread count in this PM
				$item['unread_count'] = wpc_count_unreads_in_pm($unread->pm_id);
				$item['sender_name'] = $wpc_get_user_name;
				$item['sender_short_name'] = wpc_get_user_name($data->sender, 1);
				$item['sender_int'] = $last_seen = wpc_get_user_last_seen($data->sender, true)->integer;
				$item['sender_online'] = wpc_is_online($data->sender);
				$item['sender_status_inner'] = wpc_time_diff($last_seen);
				// count unreads in conv // to be maintained
				$item['counts'] = $_pms[$unread->pm_id];
				$_unreads_excerpts[] = $item;
				//$_unreads_excerpts .= $unread !== end( $unreads ) ? ',' : '';
			}
		}

		// online users
		$_online_users = array();
		foreach( _wpc_get_users() as $user ) {
			if( wpc_is_online( $user->ID ) ) {
				$_online_users[] = array(
					'id' => $user->ID,
					'avatar' => _wpc_avatar_src( $user->ID ),
					'name' => _wpc_messages_validate_user_name_( $wpc_get_user_name = wpc_get_user_name( $user->ID ) ),
					'full_name' => $wpc_get_user_name,
					'slug' => $user->user_nicename,
					'link' => wpc_get_user_links($user->ID)->profile,
					'int' => wpc_get_user_last_seen( $user->ID, true )->integer
				);
			}
		}
		// 
		$_online_users_ob = array();
		foreach( $_online_users as $user ) {
			$user = (object) $user;
			$item = array();
			$item['id'] = $user->id;
			$item['avatar'] = $user->avatar;
			$item['name'] = $user->name;
			$item['link'] = $user->link;
			$item['int'] = $user->int;
			$item['slug'] = $user->slug;
			$item['full_name'] = $user->full_name;
			$_online_users_ob[] = $item;
		}
		// coming-soon feature
		/*$_pms = wpc_my_conversations(false, true);
		$_reads = array();
		$_isTyping = array();
		foreach( $_pms as $pm ) {
			$data = WPC_message::instance()->json_last_message( $pm );
			if( $data->seen && $data->recipient !== wp_get_current_user()->ID ) {
				$_reads[] = array(
					'pm_id' => $data->PM_ID,
					'int' => $data->seen
				);
			}
			if( '1' == get_option( '_wpc_isTyping_' . $data->PM_ID . '_' . wp_get_current_user()->ID ) ) {
				$_isTyping[] = array( 'pm_id' => $data->PM_ID );
			}
		}*/

		$mark_read_items = array();
		foreach( $_unreads as $item ) {
			$mark_read_items[] = $item['message_id'];
		}

		if( ! empty( $_REQUEST['current'] ) ) {
			if ( wpc_get_recipient_id() !== $current_user->ID )
				WPC_message::instance()->mark_read( (int) $_REQUEST['current'], false, false, $mark_read_items );
		}

		// index icon hide when switch messages
		// index sender avatar and name etc..

		$_reads = array();
		if( ! empty( $_REQUEST['pms'] ) ) {
			$pms = explode( ",", $_REQUEST['pms'] );
			$pms = array_filter( array_unique( $pms ) );
			foreach( $pms as $pm_id ) {

				if ( ! empty( $_REQUEST['last_item'] ) && ! empty( $_REQUEST['current'] ) ) {
					$last_seen = get_user_meta( wpc_get_recipient_id(), "_wpc_json_ls_{$pm_id}", 1);
					if ( ! empty( $last_seen['id'] ) && (int) $_REQUEST['last_item'] == $last_seen['id'] && $pm_id == (int) $_REQUEST['current'] ) {
						$_reads[] = array(
							'pm_id' => $pm_id,
							'int' => $last_seen['int'],
							'diff' => wpc_time_diff( $last_seen['int'] )
						);
					}
				} else {
					$m = WPC_message::instance()->json_last_message( $pm_id, 1 );
					if( $m->recipient !== $current_user->ID && $m->seen ) {
						$_reads[] = array(
							'pm_id' => $pm_id,
							'int' => $m->seen,
							'diff' => wpc_time_diff( $m->seen )
						);
					}

				}
			}
		}

		$json = array();
		$json['notifications'] = array();
		$json['notifications']['unread'] = $_unreads;
		$json['notifications']['unreadExcerpts'] = $_unreads_excerpts;
		//$json['isTyping'] = $_isTyping;
		$json['online'] = array();
		$json['online']['count'] = count( $_online_users );
		$json['online']['users'] = $_online_users_ob;
		$json['reads'] = $_reads;

		/*if ( isset( $_GET['dbg'] ) ) {
			echo var_dump( microtime(1) - $startime );
		}*/

		// once user was notified about new messages, remove from unread notifications, hook into to do other things
		do_action('wpc_json_loaded', $json);

		return json_encode( $json );

	}

	public function _actions() {

		$_action = isset( $_REQUEST['do'] ) ? (string) $_REQUEST['do'] : '';

		switch( $_action ) {

			case 'block':
				
				if( ! isset( $_GET['user'] ) || ( isset( $_GET['user'] ) && ! get_userdata( $_GET['user'] ) ) )
					return 0;

				if( wpc_is_blocking_allowed() ) :;
					$args = array(
						'doing' 	=> 'block',
						'target'	=> (int) $_GET['user'],
						'no_rdr'	=> true
					);
					WPC_message::instance()->block( $args );
					return 1;
				endif;
				break;

			case 'unblock':
				
				if( ! isset( $_GET['user'] ) || ( isset( $_GET['user'] ) && ! get_userdata( $_GET['user'] ) ) )
					return 0;

				if( wpc_is_blocking_allowed() ) :;

					$args = array(
						'doing' 	=> 'unblock',
						'target'	=> (int) $_GET['user'],
						'no_rdr'	=> true
					);
					WPC_message::instance()->block( $args );

					return 1;

				endif;

				break;


			case 'delete':
				
				$_all = ! isset( $_GET['m'] );

				if( $_all ) {
					$done = WPC_message::instance()->delete(false, wpc_get_conversation_id(), true);
				} else {
					$done = WPC_message::instance()->delete( (int) $_GET['m'], false, true );
				}

				return intval($done);

				break;

			case 'report':

				$wpc_get_message_to_report = wpc_get_message_to_report();

				if( isset( $_GET['wpc_delete_report'] ) && '1' == $_GET['wpc_delete_report'] ) {

					if( ! empty( $wpc_get_message_to_report ) )
						return WPC_message::instance()->report( $wpc_get_message_to_report->ID, false, true, true );
					else
						return WPC_message::instance()->report( false, wpc_get_conversation_id(), true, true );

				} else {

					if( ! empty( $wpc_get_message_to_report ) )
						return WPC_message::instance()->report( $wpc_get_message_to_report->ID, false, true );
					else
						return WPC_message::instance()->report( false, wpc_get_conversation_id(), true );

				}

				break;

			case 'moderation':
				return $this->moderation();

			case 'autosave':
				return $this->auto_save();

			case 'isTyping':
				
				$done = isset( $_GET['done'] );
				$pm = isset( $_GET['pm'] ) ? (int) $_GET['pm'] : false;

				if( ! $pm || ! wpc_settings()->isTyping_allowed )
					return 0;

				if( $done ) {
					delete_option( '_wpc_isTyping_' . $pm . '_' . wp_get_current_user()->ID );
				} else {
					update_option( '_wpc_isTyping_' . $pm . '_' . wp_get_current_user()->ID, '1' );
				}


				break;

			case 'profile-update':
				echo WPC_users::instance()->update_profile( true );
				break;

			case 'fwd':

				do_action('wpc_fwd_message_template');
				break;

			case 'fwd-message':

				$message_id = isset( $_GET['mid'] ) ? (int) $_GET['mid'] : 0;
				$recipient_id = isset( $_GET['uid'] ) ? (int) $_GET['uid'] : 0;

				// verify nonce first
				if( !wpc_validate_nonce() )
					return;

				if( ! $message_id || ! $recipient_id ) {
					echo 0;
					return;
				}

				$message = wpc_get_message( $message_id );

				if( ! isset( $message->ID ) || ! isset( $message->PM_ID ) ) {
					echo 0;
					return;
				}

				if( ! wpc_is_part_of_pm( $message->PM_ID, wp_get_current_user()->ID ) ) {
					echo 0;
					return;
				}

				$args = array(
					'success_redirect_to' => wpc_get_conversation_permalink() . '?done=fwd',
					'bypassNonce' => true 
				);

				echo WPC_message::instance()->send( $recipient_id, $message->message, true, $args );
				break;

		}

	}

	/**
	  * Used to update the time differences in the markup based on time integers
	  * attached in HTML attribute with AJAX
	  *
	  * @since 3.0
	  * @return string|0 encoded object of differences or 0
	  */

	public function _time() {

		if( isset( $_REQUEST['ints'] ) ) {
			$object = array();
			$array = array_filter( array_unique( $_REQUEST['ints'] ) );
			foreach( $array as $int ) {
				$object[] = array( 'int' => (int) $int, 'message' => wpc_time_diff( $int ) );
			}
			return json_encode( array( "diffs" => $object ) );
		} else {
			return 0;
		}

	}

	public function moderation() {

		$_action = isset( $_GET['_action'] ) ? (string) $_GET['_action'] : false;
		$_target = isset( $_GET['_id'] ) ? (int) $_GET['_id'] : 0;
		$_by = isset( $_GET['_by'] ) ? (int) $_GET['_by'] : 0;

		switch ( $_action ) {
			
			case 'delete':
				
				if( ! empty( wpc_get_report_meta( $_target, false, $_by )->created ) && $_by > 0 ) { // making sure it is reported already
					WPC_message::instance()->mod_actions( 'm-delete', $_target );
					delete_option( '_wpc_report_' . $_target . '_' . $_by );
					delete_option( '_wpc_report_' . $_target . '_' . $_by . '_meta' );
					do_action('wpc_moderation_post_delete_reported_item', $_target, $_by);
					return 1;
				} else {
					return 0;
				}

				break;

			case 'delete-conversation':
					
				if( ! empty( wpc_get_report_meta( $_target, false, $_by )->created ) && $_by > 0 ) { // making sure it is reported already
					WPC_message::instance()->mod_actions( 'c-delete', $_target );
					delete_option( '_wpc_report_' . $_target . '_' . $_by );
					delete_option( '_wpc_report_' . $_target . '_' . $_by . '_meta' );
					do_action('wpc_moderation_post_delete_reported_item', $_target, $_by);
					return 1;
				} else {
					return 0;
				}
				break;

			case 'delete-report':
				
				if( ! empty( wpc_get_report_meta( $_target, false, $_by )->created ) && $_by > 0 ) {
					delete_option( '_wpc_report_' . $_target . '_' . $_by );
					delete_option( '_wpc_report_' . $_target . '_' . $_by . '_meta' );
					do_action('wpc_moderation_post_delete_reported_item', $_target, $_by);
					return 1;
				} else {
					return 0;
				}
				break;

			case 'ban':
				
				$_v = WPC_users::instance()->actions( 'ban', $_target );
				return $_v ? 1 : 0;
				break;

			case 'unban':
				
				$_v = WPC_users::instance()->actions( 'unban', $_target );
				return $_v ? 1 : 0;
				break;

		}

	}

	public function _upload() {
		return 0; // PRO feature
	}

	public function auto_save() {

		$pm_id = isset( $_REQUEST['pm_id'] ) ? (int) $_REQUEST['pm_id'] : 0;
		$auto_save = isset( $_REQUEST['text'] ) ? (string) $_REQUEST['text'] : '';

		if( ! wpc_is_part_of_pm( $pm_id, wp_get_current_user()->ID ) )
			return 0;

		$auto_save = WPC_message::format( $auto_save, false );

		if( strlen( $auto_save ) <= 0 ) {
			delete_option( '_wpc_autosave_' . $pm_id . '_' . wp_get_current_user()->ID );
			return 0;
		}

		update_option( '_wpc_autosave_' . $pm_id . '_' . wp_get_current_user()->ID, esc_attr( $auto_save ) );

		return $auto_save;

	}

	public function _get_users() {

		$filter = isset( $_REQUEST['filter'] ) ? $_REQUEST['filter'] : 'all';
		$incData = isset( $_REQUEST['data'] ) ? $_REQUEST['data'] : '';
		if( "all" == $incData ) { $incData = 'link,avatar,online_status,is_online,bio'; }
		$incData = array_filter( array_unique( explode( ',', $incData ) ) );
		$limit = isset( $_REQUEST['limit'] ) ? $_REQUEST['limit'] : false;

		if( $limit ) {
			$offset = true == strpos( $limit, ',' ) ? (int) explode(',', $limit)[1] : (int) $limit;
			$start = true == strpos( $limit, ',' ) ? (int) explode(',', $limit)[0] : 0;
			$pagi['curr'] = $start < $offset ? 1 : ( is_float( abs( $start / $offset ) ) ? (int) abs( ( $start / $offset ) + 1 ) : abs( $start / $offset ) );
			$pagi['start'] = $start;
			$pagi['offset'] = $offset;
		} else { $pagi = array(); }
		
		$pagi = (object) $pagi;

		$all_users = _wpc_get_users(1);

		switch( $filter ) {

			case 'online':
				$users = array();
				foreach ( $all_users as $user ) { if( wpc_is_online( $user->ID ) ) { $users[] = $user; } }
				break;

			case 'blocked':
				$users = array();
				foreach ( wpc_get_user_blocked_list() as $user_id ) { $users[] = get_userdata( $user_id ); }
				break;

			default:
				$users = $all_users;
				break;

		}

		$users = apply_filters('wpc_ajax_get_users_users_initial_list', $users);

		$exclude = isset( $_REQUEST['exclude'] ) ? $_REQUEST['exclude'] : false;

		switch ($exclude) {
			case 'cant_contact':
				foreach( $users as $i => $user ) {
					if( wpc_can_contact( $user->ID ) ) { unset( $users[$i] ); }
				}
				break;
			
			default:
				break;
		}

		if( isset( $_REQUEST['q'] ) && sanitize_text_field( $_REQUEST['q'] ) > '' ) {
			$query = sanitize_text_field( $_REQUEST['q'] );
			foreach( $users as $i => $user ) {
				$data = wpc_get_user_search_data( $user );
				if( strstr( $data, strtolower( $query ) ) ) {
					// match found
				} else {
					unset( $users[$i] );
				}
			}
		}

		// sorting
		if( isset( $_REQUEST['sort'] ) && "last_seen" == $_REQUEST['sort'] ) {
			$user_data = array();
			$list = array();
			foreach( $users as $user ) {
				$user_data[$user->ID] = $user;
				$list[] = array( 'int' => wpc_get_user_last_seen( $user->ID, true )->integer, 'uid' => $user->ID );
			}
			rsort($list);
			$new_list = array();
			foreach( $list as $data ) {
				$new_list[] = $user_data[$data['uid']];
			}
			$users = $new_list;
		}

		$pagi->total_rec = count( $users );
		$pagi->total = abs( $pagi->total_rec / $offset );
		if( is_float($pagi->total) ) {
			$pagi->total = abs( (int) $pagi->total + 1 );
		}
		if( ! $pagi->total ) { $pagi->total = 1; }
		$pagi->nxt = abs( ( $pagi->start + 1 ) / $pagi->offset ) < $pagi->total ? $pagi->start + 1 : false;
		if( ! $pagi->offset || abs( ( $pagi->start + 1 ) * $pagi->offset ) >= $pagi->total_rec ) { $pagi->nxt = false; }

		$pagi->prv = $pagi->start > 0 ? $pagi->start - 1 : false;

		// paginating
		$users_pre_pagi = $users;

		if( ! empty( $pagi ) ) { $users = array_slice($users, $pagi->start, $pagi->offset); }

		if( empty( $users ) && count( $users_pre_pagi ) > 0 ) {
			$previous_start = ( $start - $pagi->offset > -1 ? $start - $pagi->offset : ( $start > 0 ? 0 : false ) );
			foreach( array_reverse(range(0, 99)) as $i ) {				
				$users = array_slice($users_pre_pagi, $i, $pagi->offset);
				if( count( $users ) > 0 ) {
					$pagi->total_rec += $pagi->start - $i;
					break;
				}
			}
		}
		$data = array();

		foreach ( $users as $user ) {

			$array = array(
				'ID' => $user->ID,
				'nicename' => $user->user_nicename,
				'display_name' => $user->display_name,
				'wpc_name' => array(
					'full' => wpc_get_user_name( $user->ID ),
					'short' => wpc_get_user_name( $user->ID, 1 )
				)
			);

			foreach( $incData as $name ) {
				switch ( $name ) {
					case 'avatar':
						$array['avatar'] = _wpc_avatar_src( $user->ID );
						break;

					case 'link':
						$array['link'] = wpc_get_user_links( $user->ID )->profile;
						break;

					case 'online_status':
						$array['online_status'] = htmlentities( str_replace('"', '&quot;', wpc_get_user_activity( $user->ID, wpc_translate('online'), wpc_translate('ago') )) );
						break;

					case 'is_online':
						$array['is_online'] = wpc_is_online($user->ID);
						break;

					case 'bio':
						$bio = wpc_get_user_bio($user->ID,1);
						$array['bio'] = $bio > '' ? nl2br( str_replace( "'", '&quot;', htmlentities( str_replace('"', '&quot;', $bio) ) ) ) : false;
						break;
					
					default:
						break;
				}
			}

			$array = apply_filters( 'wpc_ajax_get_users_data', $array, $user );
			$data[] = $array;

		}

		$data = apply_filters('wpc_ajax_get_users', $data);

		$dataContent = array(
			'contents' => $data,
			'pagination' => array(
				'total' => isset( $pagi->total_rec ) ? $pagi->total_rec : false,
				'total_pages' => isset( $pagi->total ) ? $pagi->total : false,
				'offset' => isset( $pagi->offset ) ? $pagi->offset : false,
				'next' => isset( $pagi->nxt ) ? $pagi->nxt : false,
				'previous_page' => isset( $pagi->prv ) ? $pagi->prv : false,
				'current' => $pagi->nxt ? $pagi->nxt : ( is_numeric( $pagi->prv ) ? abs( $pagi->prv + 2 ) : false ),
				'next_start' => ( $start + $pagi->offset > -1 && $start + $pagi->offset <= $pagi->total_rec ? $start + $pagi->offset : false ),
				'previous_start' => ( $start - $pagi->offset > -1 ? $start - $pagi->offset : ( $start > 0 ? 0 : false ) ),
			)
		);
		
		header("Content-type: application/json; charset=utf-8");
		echo json_encode($dataContent);

	}

	public function lazy_load() {

		$part = ! empty( $_REQUEST['part'] ) ? $_REQUEST['part'] : false;

		if ( ! $part ) {
			return 0;
		} 

		switch ( $part ) {
			case 'widgets':
				$this->lazy_load_widgets();
				break;
		}

	}

	public function lazy_load_widgets() {

		$widget = ! empty( $_REQUEST['widget'] ) ? $_REQUEST['widget'] : false;

		if ( ! $widget ) {
			return;
		}

		switch ( $widget ) {

			case 'moderated':
				include_once wpc_template_path( 'widgets/moderated' );
				break;

			case 'welcome':
				include_once wpc_template_path( 'widgets/welcome' );
				break;

			case 'notifications':
				include_once wpc_template_path( 'widgets/notifications' );
				break;

			case 'contacts':
				include_once wpc_template_path( 'widgets/contacts' );
				break;

			case 'search':
				include_once wpc_template_path( 'widgets/search' );
				break;

		}

	}


}

WPC_ajax::instance();