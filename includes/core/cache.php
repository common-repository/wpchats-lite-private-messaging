<?php

class WPC_cache
{

	protected static $instance = null;
	public $expiration
	     , $pm_items_per_transient;
	private $enable;

	public static function instance() {
		return null == self::$instance ? new self : self::$instance;
	}

	public function __construct() {
		$this->expiration = apply_filters( "WPC_cache_expiration", 30 * DAY_IN_SECONDS );
		$this->pm_items_per_transient = apply_filters( "WPC_cache_items_per_batch", 100 ); // 1500 or 2000
		$this->enable = ! empty( wpc_settings()->caching );
	}

	public static function get_conversation_messages( $pm_id, $return_if_cached = false ) {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->_get_conversation_messages( $pm_id, $return_if_cached );
	}

	public function _get_conversation_messages( $pm_id, $return_if_cached = false ) {

		if ( ! $this->enable ) {
			global $wpdb;
			$table = $wpdb->prefix . WPC_TABLE;
	        $data = $wpdb->get_results( "SELECT * FROM $table WHERE `PM_ID` = '$pm_id' ORDER BY `ID` DESC", ARRAY_A );
	        if( ! empty( $data ) ) {
				foreach ( $data as $i => $item ) {
					$data[$i] = new stdClass();
					$data[$i] = (object) $item;
				}
			}
	        return $data;
		}

		$items_count = (int) get_option( "_wpc_cached_items_{$pm_id}" );

		if( $items_count > 0 ) {

			$data_batches = array();

			for ( $i = 0; $i < $items_count; $i++ ) {

				if( false === $current_batch = get_transient("wpc_get_conversation_{$pm_id}_{$i}_messages") ) {

					if( $return_if_cached ) { return false; }
	
					global $wpdb;
					$table = $wpdb->prefix . WPC_TABLE;
			        $data = $wpdb->get_results( "SELECT * FROM $table WHERE `PM_ID` = '$pm_id' ORDER BY `ID` DESC", ARRAY_A );
					$data_chunks = array_chunk($data, $this->pm_items_per_transient);
			        update_option( "_wpc_cached_items_{$pm_id}", (int) count( $data_chunks ) );
			        if( (int) count( $data_chunks ) > 0 ) {
			        	foreach ( $data_chunks as $i => $item ) {
					        set_transient("wpc_get_conversation_{$pm_id}_{$i}_messages", $item, $this->expiration);
			        	}
			        } else {
						set_transient("wpc_get_conversation_{$pm_id}_0_messages", $item, $this->expiration);
					}
					if( ! empty( $data ) ) {
						foreach ( $data as $i => $item ) {
							$data[$i] = new stdClass();
							$data[$i] = (object) $item;
						}
					}
					return $data;
					break;

				}

				foreach ( $current_batch as $current_batch_data ) {
					array_push( $data_batches, $current_batch_data );
				}

			}

			$data = $data_batches;

		} else {

			if  ( false === $data = get_transient("wpc_get_conversation_{$pm_id}_0_messages") ) {

				if( $return_if_cached ) { return false; }

				global $wpdb;
				$table = $wpdb->prefix . WPC_TABLE;
		        $data = $wpdb->get_results( "SELECT * FROM $table WHERE `PM_ID` = '$pm_id' ORDER BY `ID` DESC", ARRAY_A );
				$data_chunks = array_chunk($data, $this->pm_items_per_transient);
		        update_option( "_wpc_cached_items_{$pm_id}", (int) count( $data_chunks ) );
		        if( (int) count( $data_chunks ) > 0 ) {
		        	foreach ( $data_chunks as $i => $item ) {
				        set_transient("wpc_get_conversation_{$pm_id}_{$i}_messages", $item, $this->expiration);
		        	}
		        } else {
					set_transient("wpc_get_conversation_{$pm_id}_0_messages", $item, $this->expiration);
				}
			
			}

		}
		
		if( ! empty( $data ) ) {
			foreach ( $data as $i => $item ) {
				$data[$i] = new stdClass();
				$data[$i] = (object) $item;
			}
		}

		return $data;

	}

	public static function flush_conversation_messages( $pm_id ) {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->_flush_conversation_messages( $pm_id );
	}

	public function _flush_conversation_messages( $pm_id ) {

		if ( ! $this->enable ) {
	        return;
		}

		$items_count = (int) get_option( "_wpc_cached_items_{$pm_id}" );

		if( $items_count > 0 ) {
			$cached_data = $this->get_conversation_messages( $pm_id, 1 );
			if( $cached_data ) {
				foreach ( $cached_data as $data ) {
					delete_transient( "wpc_get_message_{$data->ID}" );
				}
			}
			for ( $i = 0; $i < $items_count; $i++ ) {
				delete_transient( "wpc_get_conversation_{$pm_id}_{$i}_messages" );
			}
		} else {
			delete_transient( "wpc_get_conversation_{$pm_id}_0_messages" );			
		}
		delete_option( "_wpc_cached_items_{$pm_id}" );

		// flush conversations
		$this->conversations( 0, 1 );
		$this->get_conversation( $pm_id, 1 );
		$this->all_conversation_totals( 1 );
		$this->conversation_totals( $pm_id, 1 );

		$pm = wpc_get_conversation( $pm_id );
		if ( $last_message = $pm->last_message ) {
			if ( ! empty( $last_message->sender ) ) {
				$this->get_all_user_messages( $last_message->sender, 1 );
			}
			if ( ! empty( $last_message->recipient ) ) {
				$this->get_all_user_messages( $last_message->recipient, 1 );
			}
		}

	}

	public static function get_message( $message_id ) {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->_get_message( $message_id );
	}

	public function _get_message( $message_id ) {

		if ( ! $this->enable ) {
			global $wpdb;
			$table = $wpdb->prefix . WPC_TABLE;
			$message_id = (int) $message_id;
	        $data = $wpdb->get_results( "SELECT * FROM $table WHERE `ID` = '$message_id' LIMIT 1" );
	        return $data;
		}

		if  ( false === $data = get_transient("wpc_get_message_{$message_id}") ) {
			global $wpdb;
			$table = $wpdb->prefix . WPC_TABLE;
			$message_id = (int) $message_id;
	        $data = $wpdb->get_results( "SELECT * FROM $table WHERE `ID` = '$message_id' LIMIT 1" );
			set_transient("wpc_get_message_{$message_id}", $data, $this->expiration);
		}

		return $data;

	}

	public static function get_conversation( $pm_id, $flush = false ) {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->_get_conversation( $pm_id, $flush );
	}

	public function _get_conversation( $pm_id, $flush = false ) {

		if ( ! $this->enable ) {
			if ( ! $flush ) {
				global $wpdb
			     , $current_user;
				$table = $wpdb->prefix . WPC_TABLE;
				$pm_id = (int) $pm_id;

				$data = $wpdb->get_results( "SELECT * FROM $table WHERE `PM_ID` = '$pm_id' AND NOT FIND_IN_SET('$current_user->ID', `deleted`) ORDER BY `ID` DESC LIMIT 1" );
				return $data;
			}
			return;
		}

		if ( $flush ) {
			delete_transient("wpc_get_conversation_{$pm_id}");
			return;
		}

		if ( false === $data = get_transient("wpc_get_conversation_{$pm_id}") ) {
			global $wpdb
			     , $current_user;
			$table = $wpdb->prefix . WPC_TABLE;
			$pm_id = (int) $pm_id;

			$data = $wpdb->get_results( "SELECT * FROM $table WHERE `PM_ID` = '$pm_id' AND NOT FIND_IN_SET('$current_user->ID', `deleted`) ORDER BY `ID` DESC LIMIT 1" );
			set_transient("wpc_get_conversation_{$pm_id}", $data, $this->expiration);
		}

		return $data;

	}

	public static function conversation_totals( $pm_id, $flush = false ) {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->_conversation_totals( $pm_id, $flush );
	}
	public function _conversation_totals( $pm_id, $flush = false ) {

		if ( ! $this->enable ) {
			if ( ! $flush ) {
				global $wpdb
				     , $current_user;
				$table = $wpdb->prefix . WPC_TABLE;
				$pm_id = (int) $pm_id;
				$query1 = $wpdb->get_results("SELECT COUNT(*) AS total FROM $table WHERE `PM_ID` = '$pm_id' AND NOT FIND_IN_SET('$current_user->ID', `deleted`)");
				$query2 = $wpdb->get_results("SELECT COUNT(*) AS total FROM $table WHERE `PM_ID` = '$pm_id'");
				return array(
					"data" => $query1,
					"all_data" => $query2
				);
			}
			return;
		}

		if ( $flush ) {
			delete_transient("wpc_get_conversation_{$pm_id}_totals");
			return;
		}

		if  ( false === $data = get_transient("wpc_get_conversation_{$pm_id}_totals") ) {
			global $wpdb
			     , $current_user;
			$table = $wpdb->prefix . WPC_TABLE;
			$pm_id = (int) $pm_id;

			$query1 = $wpdb->get_results("SELECT COUNT(*) AS total FROM $table WHERE `PM_ID` = '$pm_id' AND NOT FIND_IN_SET('$current_user->ID', `deleted`)");
			$query2 = $wpdb->get_results("SELECT COUNT(*) AS total FROM $table WHERE `PM_ID` = '$pm_id'");

			$data = array(
				"data" => $query1,
				"all_data" => $query2
			);

			set_transient("wpc_get_conversation_{$pm_id}_totals", $data, $this->expiration);
		}

		return $data;

	}

	public static function all_conversation_totals( $flush = false ) {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->_all_conversation_totals( $flush );
	}
	public function _all_conversation_totals( $flush = false ) {

		if ( ! $this->enable ) {
			if ( ! $flush ) {
				global $wpdb
				     , $current_user;
				$table = $wpdb->prefix . WPC_TABLE;
				$data = $wpdb->get_results("SELECT COUNT(*) AS total FROM $table");
				return $data;
			}
			return;
		}

		if ( $flush ) {
			delete_transient("wpc_get_all_conversations_totals");
			return;
		}

		if  ( false === $data = get_transient("wpc_get_all_conversations_totals") ) {
			global $wpdb
			     , $current_user;
			$table = $wpdb->prefix . WPC_TABLE;
			$data = $wpdb->get_results("SELECT COUNT(*) AS total FROM $table");

			set_transient("wpc_get_all_conversations_totals", $data, $this->expiration);
		}

		return $data;

	}

	/*public static function get_pm_id( $sender, $recipient ) {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->_get_pm_id( $sender, $recipient );
	}

	public function _get_pm_id( $sender, $recipient ) {

		if  ( false === $data = get_transient("wpc_get_pm_id_{$sender}_{$recipient}") ) {
			global $wpdb
			     , $current_user;
			$table = $wpdb->prefix . WPC_TABLE;
			$data = $wpdb->get_results( "SELECT `PM_ID` FROM $table WHERE `sender` = '$sender' AND `recipient` = '$recipient' OR `recipient` = '$sender' AND `sender` = '$recipient' ORDER BY `ID` DESC LIMIT 1" );
			set_transient("wpc_get_pm_id_{$sender}_{$recipient}", $data, $this->expiration);
		}

		return $data;

	}*/

	public static function conversations( $batch, $flush = false ) {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->_conversations( $batch, $flush );
	}

	public function _conversations( $batch = 'all', $flush = false ) {
		
		if ( ! $this->enable ) {
			if ( ! $flush ) {
				global $wpdb
				     , $current_user;

				$table = $wpdb->prefix . WPC_TABLE;

				switch( $batch ) {

					case 'all':
						$data = $wpdb->get_results("SELECT `PM_ID` FROM $table WHERE ( `sender` = '$current_user->ID' OR `recipient` = '$current_user->ID' ) AND NOT FIND_IN_SET('$current_user->ID', `deleted`) ORDER BY `ID` DESC");
						if ( ! empty( $data ) ):;
						$data_array = array();
						foreach ( $data as $i => $meta ) {
							if ( in_array( $meta->PM_ID, $data_array ) ) {
								unset( $data[$i] );
							} else $data_array[] = $meta->PM_ID;
						}
						endif;
						break;
					
					case 'unread':
						$data = $wpdb->get_results("SELECT `PM_ID` FROM $table WHERE ( `recipient` = '$current_user->ID' ) AND NOT FIND_IN_SET('$current_user->ID', `deleted`) AND `seen` IS NULL ORDER BY `ID` DESC");							
						if ( ! empty( $data ) ):;
						$data_array = array();
						foreach ( $data as $i => $meta ) {
							if ( in_array( $meta->PM_ID, $data_array ) ) {
								unset( $data[$i] );
							} else $data_array[] = $meta->PM_ID;
						}
						endif;
						break;

					case 'search': // not cached
						$q = wpc_get_search_query();
						$data = $wpdb->get_results("SELECT `PM_ID` FROM $table WHERE ( `sender` = '$current_user->ID' OR `recipient` = '$current_user->ID' ) AND NOT FIND_IN_SET('$current_user->ID', `deleted`) AND `message` LIKE '%$q%' ORDER BY `ID` DESC");
						break;

				}
				return $data;
			}
			return;
		}

		global $current_user;

		if ( $flush ) {
			delete_transient( "wpc_get_all_conversations_ids_{$current_user->ID}" );
			delete_transient( "wpc_get_all_conversations_unread_{$current_user->ID}" );
			return;
		}

		global $wpdb;

		$table = $wpdb->prefix . WPC_TABLE;

		switch( $batch ) {

			case 'all':
				if  ( false === $data = get_transient("wpc_get_all_conversations_ids_{$current_user->ID}") ) {
					
					$data = $wpdb->get_results("SELECT `PM_ID` FROM $table WHERE ( `sender` = '$current_user->ID' OR `recipient` = '$current_user->ID' ) AND NOT FIND_IN_SET('$current_user->ID', `deleted`) ORDER BY `ID` DESC");

					if ( ! empty( $data ) ):;
					$data_array = array();
					foreach ( $data as $i => $meta ) {
						if ( in_array( $meta->PM_ID, $data_array ) ) {
							unset( $data[$i] );
						} else $data_array[] = $meta->PM_ID;
					}
					endif;

					set_transient("wpc_get_all_conversations_ids_{$current_user->ID}", $data, $this->expiration);
				}
				break;
			
			case 'unread':
				if  ( false === $data = get_transient("wpc_get_all_conversations_unread_{$current_user->ID}") ) {
					$data = $wpdb->get_results("SELECT `PM_ID` FROM $table WHERE ( `recipient` = '$current_user->ID' ) AND NOT FIND_IN_SET('$current_user->ID', `deleted`) AND `seen` IS NULL ORDER BY `ID` DESC");
					
					if ( ! empty( $data ) ):;
					$data_array = array();
					foreach ( $data as $i => $meta ) {
						if ( in_array( $meta->PM_ID, $data_array ) ) {
							unset( $data[$i] );
						} else $data_array[] = $meta->PM_ID;
					}
					endif;

					set_transient("wpc_get_all_conversations_unread_{$current_user->ID}", $data, $this->expiration);
				}
				break;

			case 'search': // not cached
				$q = wpc_get_search_query();
				$data = $wpdb->get_results("SELECT `PM_ID` FROM $table WHERE ( `sender` = '$current_user->ID' OR `recipient` = '$current_user->ID' ) AND NOT FIND_IN_SET('$current_user->ID', `deleted`) AND `message` LIKE '%$q%' ORDER BY `ID` DESC");
				break;

		}

		return $data; 

	}

	public static function get_all_user_messages( $user_id, $flush = false ) {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->_get_all_user_messages($user_id, $flush);
	}
	public function _get_all_user_messages( $user_id, $flush = false ) {

		if ( ! $this->enable ) {
			if ( ! $flush ) {
				global $wpdb;
				$table = $wpdb->prefix . WPC_TABLE;
				$data = $wpdb->get_results( "SELECT `ID`,`sender`,`recipient` FROM $table WHERE `recipient` = '$user_id' OR `sender` = '$user_id'" );
				return $data;
			}
			return;
		}

		if ( $flush ) {
			delete_transient("wpc_get_all_user_{$user_id}_messages");
			return;
		}

		if  ( false === $data = get_transient("wpc_get_all_user_{$user_id}_messages") ) {
			global $wpdb;
			$table = $wpdb->prefix . WPC_TABLE;
			$data = $wpdb->get_results( "SELECT `ID`,`sender`,`recipient` FROM $table WHERE `recipient` = '$user_id' OR `sender` = '$user_id'" );
			set_transient("wpc_get_all_user_{$user_id}_messages", $data, $this->expiration);
		}

		return $data;

	}

	public static function get_reports() {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->_get_reports();
	}
	public function _get_reports() {

		// not cached
		//if ( ! $this->enable ) {
			global $wpdb;
			$table = $wpdb->prefix . "options";
			$data = $wpdb->get_results( "SELECT option_name, option_value, option_id FROM $table WHERE option_name LIKE '_wpc_report_%' ORDER BY option_id DESC" );
			return $data;
		//}

		if  ( false === $data = get_transient("wpc_get_all_reports") ) {
			global $wpdb;
			$table = $wpdb->prefix . "options";
			$data = $wpdb->get_results( "SELECT option_name, option_value, option_id FROM $table WHERE option_name LIKE '_wpc_report_%' ORDER BY option_id DESC" );
			set_transient("wpc_get_all_reports", $data, $this->expiration);
		}

		return $data;

	}

}