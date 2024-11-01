<?php

defined('ABSPATH') ? null : exit( 'Silence is golden.' );

if ( ! class_exists( 'WPC_user_roles' ) ):;
class WPC_user_roles
{

	protected static $instance = null;

	public $roles;

	private $core_roles = array(
		"wpc_admin",
		"wpc_user",
		"wpc_moderator"
	);
	
	private $default_caps = array(
		"upload_attachements",
		"report_messages",
		"edit_reports",
		"block_users",
		"mark_unread",
		"edit_own_profile"
	);

	private $default_limitations = array(
		'messages' => -1,
		'uploads' => -1,
		'reports' => -1
	);

	private $assign_to = array(
		'wpc_admin' => array( 'roles' => array( 'administrator' ) ),
		'wpc_user' => array( 'any' => true ),
		'wpc_moderator' => false
	);

	public function __construct() {

		$this->roles = array();

		foreach ( $this->core_roles as $name ) {
			$this->roles[$name] = array();
		}

		foreach( $this->default_caps as $cap ) {
			foreach ( $this->roles as $name => $role ) {
				$this->roles[$name]['caps'][] = $cap;
			}
		}

		// admins
		$this->roles['wpc_admin']['caps'][] = "edit_mods";
		$this->roles['wpc_admin']['caps'][] = "watch_conversations";
		$this->roles['wpc_admin']['caps'][] = "ban";
		$this->roles['wpc_admin']['caps'][] = "edit_notifications";
		$this->roles['wpc_admin']['caps'][] = "edit_profiles";
		$this->roles['wpc_admin']['caps'][] = "read_all_stats";

		// moderators
		$this->roles['wpc_moderator']['caps'][] = "ban";

		foreach ( $this->roles as $name => $role_data ) {
			$this->roles[$name]['limitations'] = array();
			foreach ( $this->default_limitations as $prop => $limit ) {
				$this->roles[$name]['limitations'][$prop] = $limit;
			}
			$this->roles[$name]['assign_to'] = array();
			/*if ( ! empty( $this->assign_to[$name] ) ) {
				$this->roles[$name]['assign_to'][] = $this->assign_to[$name];
			}*/
		}

		$this->roles = (object) $this->filter();

	}

	private function filter() {

		$roles_meta = get_option( "_WPC_user_roles" );

		if( $roles_meta ) {
			$roles_meta = is_array( $roles_meta ) ? $roles_meta : json_decode( $roles_meta, true );
		} else {
			$roles_meta = array();
		}

		foreach ( $this->roles as $name => $data ) {
			if( ! empty( $roles_meta[$name]['caps'] ) ) {
				$this->roles[$name]['caps'] = $roles_meta[$name]['caps'];
			}
			if( ! empty( $roles_meta[$name]['limitations'] ) ) {
				$this->roles[$name]['limitations'] = wp_parse_args( $roles_meta[$name]['limitations'], $this->roles[$name]['limitations'] );
			}
			if( isset( $roles_meta[$name]['assign_to'] ) ) {
				$this->roles[$name]['assign_to'] = wp_parse_args( $roles_meta[$name]['assign_to'], $this->roles[$name]['assign_to'] );
			} else {
				switch( $name ) {
					case 'wpc_admin':
						$this->roles[$name]['assign_to']['roles'] = array( 'administrator' );
						break;
					case 'wpc_user':
						$this->roles[$name]['assign_to']['any'] = true;
						break;
					case 'wpc_moderator':
						$this->roles[$name]['assign_to']['roles'] = array( 'bbp_moderator' );
						break;
				}
			}
		}

		if( ! empty( $roles_meta ) && is_array( $roles_meta ) ) {
			$this->roles = wp_parse_args( $this->roles, $roles_meta );
		}

		return (object) apply_filters( "wpc_users_roles", $this->roles );

	}

	public static function update( $name, $caps = array(), $limitations = array(), $old_name = '', $assign_to = array() ) {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->_update( $name, $caps, $limitations, $old_name, $assign_to );
	}
	
	public function _update( $name, $caps = array(), $limitations = array(), $old_name = '', $assign_to = array() ) {

		if( ! $name ) {
			return;
		}

		$roles_meta = get_option( "_WPC_user_roles" );

		if( $roles_meta ) {
			$roles_meta = json_decode( $roles_meta, true );
		} else {
			$roles_meta = array();
		}

		if( ! empty( $roles_meta ) ) {
			$roles = $roles_meta;
		} else {
			$roles = $this->roles;
		}

		$roles = (array) $roles;

		if( $old_name && (string) $name !== (string) $old_name && ! in_array( $old_name, $this->core_roles ) ) {
										
			unset( $roles[$old_name] );

			$roles[$name] = array();

			if( ! empty( $caps ) && is_array( $caps ) ) {
				$roles[$name]['caps'] = (array) $caps;
			}

			if( ! empty( $limits ) && is_array( $limits ) ) {
				$roles[$name]['limitations'] = (array) $limits;
			}

			$default_args = array(
				'caps' => $this->default_caps,
				'limitations' => wp_parse_args(
					$limitations,
					$this->default_limitations
				),
				'assign_to' => $assign_to
			);

			$roles[$name] = wp_parse_args( $roles[$name], $default_args );
					
		} else {

			$roles[$name] = array(
				'caps' => is_array( $caps ) ? $caps : $this->default_caps,
				'limitations' => wp_parse_args(
					$limitations,
					$this->default_limitations
				),
				'assign_to' => $assign_to
			);

		}

		update_option( "_WPC_user_roles", json_encode( $roles ) );

		return true;

	}
	
	public static function delete( $name ) {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->_delete( $name );
	}

	public function _delete( $name ) {


		$roles_meta = get_option( "_WPC_user_roles" );

		if( $roles_meta ) {
			$roles_meta = json_decode( $roles_meta, true );
		} else {
			$roles_meta = array();
		}

		if( ! empty( $roles_meta ) ) {
			
			if( ! isset( $roles_meta[$name] ) ) {
				return false;
			}

			unset( $roles_meta[$name] );

			if( ! empty( $roles_meta ) ) {
				update_option( "_WPC_user_roles", json_encode( $roles_meta ) );
			} else {
				delete_option( "_WPC_user_roles" );
			}

			return true;

		}

		return;

	}
	
	public static function get( $name = '' ) {
		$class = null == self::$instance ? new self : self::$instance;
		return $name ? $class->_get( $name ) : $class->_get_all();
	}

	public function _get( $name ) {
		$this->roles = (array) $this->roles;
		return isset( $this->roles[$name] ) ? $this->roles[$name] : array();
	}

	public function _get_all() { return $this->roles; }

	public static function user_roles( $user_id = 0 ) {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->_user_roles( $user_id ) ;
	}

	public function _user_roles( $user_id = 0 ) {

		global $current_user;
		if( ! $user_id && is_user_logged_in() ) {
			$user_id = $current_user->ID;
			global $WPC_roles_current_user_roles;
			if ( isset( $WPC_roles_current_user_roles ) ) {
				return $WPC_roles_current_user_roles;
			}
		}

		$user_data = get_userdata( $user_id );
		if( ! $user_data ) { return array(); }
		$user_roles = array();
		$this->roles = (array) $this->roles;

		foreach ( $this->roles as $role => $data ) {
			if( ! empty( $data['assign_to'] ) ) {
				if( ! empty( $data['assign_to']['roles'] ) ) {
					foreach ( (array) $data['assign_to']['roles'] as $rolename ) {
						if( in_array($rolename, $user_data->roles) ) {
							$user_roles[$role] = $this->roles[$role];
						}
					}
				}
				if ( ! empty( $data['assign_to']['users'] ) ) {
					foreach ( (array) $data['assign_to']['users'] as $_user_id ) {
						if ( $_user_id == $user_id ) {
							$user_roles[$role] = $this->roles[$role];
						}
					}
				}
				if ( ! empty( $data['assign_to']['any'] ) ) {
					$user_roles[$role] = $this->roles[$role];
				}
			}
		}

		if( $mod_list = get_option( "_wpc_moderators_list" ) ) {
			if ( in_array( $user_id, explode( ",", $mod_list ) ) ) {
				if ( empty( $user_roles['wpc_moderator'] ) ) {
					$user_roles['wpc_moderator'] = $this->roles['wpc_moderator'];
				}
			}
		}

		if( in_array( "administrator", $user_data->roles) ) {
			if ( empty( $user_roles['wpc_admin'] ) ) $user_roles['wpc_admin'] = $this->roles['wpc_admin'];
			if ( empty( $user_roles['wpc_moderator'] ) ) $user_roles['wpc_moderator'] = $this->roles['wpc_moderator'];
		}

		if ( empty( $user_roles['wpc_user'] ) ) {
			$user_roles['wpc_user'] = $this->roles['wpc_user'];
		}

		$user_roles = apply_filters( "wpc_user_roles", $user_roles, $user_id );

		if ( $user_id == $current_user->ID ) {
			$GLOBALS['WPC_roles_current_user_roles'] = $user_roles;
		}

		return $user_roles;

	}

	public static function user_caps( $user_id ) {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->_user_caps( $user_id );
	}

	public function _user_caps( $user_id ) {

		global $current_user;
		if ( $user_id == $current_user->ID ) {
			global $WPC_roles_current_user_caps;
			if ( isset( $WPC_roles_current_user_caps ) ) {
				return $WPC_roles_current_user_caps;
			}
		}

		$roles = $this->_user_roles( $user_id );
		$capabilities = array();
		if( ! empty( $roles ) ):;
		foreach ( $roles as $name => $data ) {
			if( empty( $data['caps'] ) ) { continue; }
			foreach( $data['caps'] as $cap ) {
				$capabilities[] = $cap;
			}
		}
		endif;

		$capabilities = array_filter( array_unique( $capabilities ) );
		$capabilities = apply_filters( "wpc_user_caps", $capabilities, $user_id );

		if ( $user_id == $current_user->ID ) {
			$GLOBALS['WPC_roles_current_user_caps'] = $capabilities;
		}

		return $capabilities;

	}

	public static function user_limitations( $user_id ) {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->_user_limitations( $user_id );
	}

	public function _user_limitations( $user_id ) {

		$roles = $this->_user_roles( $user_id );
		$limitations = array();
		if( ! empty( $roles ) ):;
		foreach ( $roles as $name => $data ) {
			if( empty( $data['limitations'] ) ) { continue; }
			foreach( $data['limitations'] as $prop => $limit ) {
				$limitations[$prop] = $limit;
			}
		}
		endif;

		//$limitations = array_filter( array_unique( $limitations ) );
		return apply_filters( "wpc_user_limitations", $limitations, $user_id );

	}

	public static function default_limitations() {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->default_limitations;
	}

	public static function user_roles_data( $user_id ) {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->_user_roles_data( $user_id );
	}

	public function _user_roles_data( $user_id = 0 ) {

		global $current_user;

		if( ! $user_id ) {
			$user_id = $current_user->ID;
			global $wpc_current_user_roles_data;
			if ( isset( $wpc_current_user_roles_data ) ) {
				return $wpc_current_user_roles_data;
			}

		}

		$roles = $this->_user_roles( $user_id );
		$args = array();
		$args['caps'] = $args['limitations'] = array();

		foreach ( $roles as $role => $data ) {
			$args['caps'] = wp_parse_args( $args['caps'], $data['caps'] );
			if( ! empty( $data['limitations'] ) ) :;
			foreach ( $data['limitations'] as $prop => $val ) {
				if( ! empty( $args['limitations'][$prop] ) ) {
					if ( !empty($roles["wpc_admin"]) && "wpc_admin" !== $role ) continue;
					if( $val > -1 ) {
						if( (int) $args['limitations'][$prop] > -1 ) {
							$args['limitations'][$prop] += (int) $val;
						} else {
							$args['limitations'][$prop] = (int) $val;
						}
					}
				} else {
					$args['limitations'][$prop] = (int) $val;
				}
			}
			endif;
		}

		$args['caps'] = array_filter( array_unique( $args['caps'] ) );
		$agrs['limitations'] = array_filter( array_unique( $args['limitations'] ) );
		$args = apply_filters( "wpc_user_roles_data", (object) $args, $user_id );

		if ( $user_id == $current_user->ID ) {
			$GLOBALS['wpc_current_user_roles_data'] = $args;
		}

		return $args;

	}

	public static function get_users_by_role( $rolename ) {
		$class = null == self::$instance ? new self : self::$instance;
		return $class->_get_users_by_role( $rolename );
	}

	public function _get_users_by_role( $rolename ) {

		$users = _wpc_get_users();

		foreach ( $users as $i => $user ) {
			if( empty( $this::user_roles( $user->ID )[$rolename] ) ) {
				unset( $users[$i] );
			}
		}

		return $users;

	}

}


endif;