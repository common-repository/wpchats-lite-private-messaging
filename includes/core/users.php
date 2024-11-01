<?php

class WPC_users
{

	protected static $instance = null;

	public static function instance() {

		return null == self::$instance ? new self : self::$instance;

	}

	public function update_status() { update_user_meta( wp_get_current_user()->ID, '_wpc_last_seen', time() ); }

	public function get_users( $exclude_hidden = false ) {

		if ( ! $exclude_hidden ) {
			global $WPC_users_get_all_users;
			if ( ! empty( $WPC_users_get_all_users ) ) {
				return $WPC_users_get_all_users;
			}
		} else {
			global $WPC_users_get_avail_users;
			if ( ! empty( $WPC_users_get_avail_users ) ) {
				return $WPC_users_get_avail_users;
			}
		}

		$users = get_users();
		$settings = wpc_settings();
		if ( $exclude_hidden ) :;
		foreach( $users as $i => $user ) {
			if( "exclude_from_users" == $settings->on_cant_view_profile ) {
				if( ! wpc_can_view_profile( $user->ID ) ) {
					unset( $users[$i] );
				}
			}
		}
		endif;

		$users = apply_filters( "WPC_users_get_users", $users, $exclude_hidden );

		if ( ! $exclude_hidden ) {
			$GLOBALS['WPC_users_get_all_users'] = $users;
		} else {
			$GLOBALS['WPC_users_get_avail_users'] = $users;			
		}

		return $users;
	}

	public function search( $query, $batch = '', $target = array(), $exclude = array(), $limit_name = false ) {

		$users = _wpc_get_users(1);
		$query = $query > '' ? stripslashes( $query ) : ' ';

		if( ! is_array( $exclude ) ) {
			$int = (int) $exclude;
			$exclude = array();
			$exclude[] = $int;
		}

		foreach( $users as $i => $user ) :;

			if( in_array($user->ID, $exclude) )
				unset( $users[$i] ); // excluded

			$data = wpc_get_user_search_data( $user );

			if( strstr( $data, strtolower( $query ) ) ) {
				// match found
			} else {
				unset( $users[$i] );
			}

		endforeach;

		if( ! empty( $target ) ) :;

			foreach( $target as $i => $user ) :;

				if( in_array($user->ID, $exclude) )
					unset( $target[$i] ); // excluded

				$data = wpc_get_user_search_data( $user, $limit_name );

				if( strstr( $data, strtolower( $query ) ) ) {
					// match found
				} else {
					unset( $target[$i] );
				}

			endforeach;

			return $target;

		else :;

			return array();

		endif;

		switch( $batch ) :;

			case 'online':
				
				foreach( $users as $i => $user ) :;

					if( ! wpc_is_online( $user->ID ) )
						unset( $users[$i] );

				endforeach;

				break;

			case 'blocked':
				
				foreach( $users as $i => $user ) :;

					if( ! wpc_is_blocked( $user->ID ) ) {
						unset( $users[$i] );
					}

				endforeach;

				break;


		endswitch;

		return $users;

	}

	public function get( $return_all = false ) {

		# USERS: if is_users | is_blocked | is_q[within] | is_online
		# USER: simple

		$_users = array();
		$avail_users = _wpc_get_users(1);

		if ( wpc_is_archive_users() ) {

			$_users = $avail_users;

		}
		elseif ( wpc_is_archive_blocked_users() ) {

			if( count( wpc_get_user_blocked_list() ) > 0 ) {

				foreach( wpc_get_user_blocked_list() as $user_id )
					$_users[] = get_userdata( $user_id );

			}

		}
		elseif ( wpc_is_archive_online_users() ) {

			foreach( $avail_users as $user ) {
				if( wpc_is_online( $user->ID ) )
					$_users[] = get_userdata( $user->ID );
			}

		}

		if( wpc_get_search_query() > '' ) {

			$query = stripslashes( wpc_get_search_query() );
			$_users = $this->search( $query, '', $_users );

		}

		if( isset( $_GET['sort'] ) || wpc_is_archive_online_users() ) {

			$sort = isset( $_GET['sort'] ) ? (string) $_GET['sort'] : false;

			if( wpc_is_archive_online_users() )
				$sort = 'activity';

			switch( $sort ) {

				case 'activity':
					
					$return = array();

					foreach( $_users as $user ) {
						$last_seen = wpc_get_user_last_seen( $user->ID, true )->integer;
						$ob = new stdClass();
						$ob->sec = abs( time() - $last_seen );
						$ob->ID = $user->ID;
						$ob->int = $last_seen;

						$return[] = (array) $ob;
					}

					sort($return);
					$_users = array();

					foreach( $return as $user ) {
						$_users[] = get_userdata( $user['ID'] );
					}

					break;

			}

		}

		if( $return_all )
			return $_users;

		$shuffle = false;

		if( ! isset( $_GET['sort'] ) ) {
			$shuffle = true;
		}

		return wpc_paginate( $_users, wpc_settings()->pagination->users, false, $shuffle );

	}

	public function get_displayed_user() {

		$var = get_query_var( 'wpc_user', wpc_get_query_var('wpc_user') );
		$user = get_user_by( 'slug', $var );
		$user = is_object( $user ) ? $user : false;

		if( empty( $user ) ) {
			global $wpc;
			if( ! empty( $wpc['displayed_user'] ) ) {
				$user = $wpc['displayed_user'];
			}
		}

		$user = apply_filters( 'wpc_get_displayed_user', $user );
		return $user;

	}

	public function update_profile( $ajax = false ) {

		if( ! wpc_validate_nonce() ) {
			if( $ajax ) { return 0; }
			return;
		}

		$user_id = wpc_get_displayed_user_id();

		if( ! get_userdata( $user_id ) ) {

			if( $ajax ) { return 0; }

			wp_redirect( wpc_get_user_links($user_id)->edit );
			exit;

		}

		if( $user_id !== wp_get_current_user()->ID && ! wpc_current_user_can('edit_profiles') ) { // data manupliated, error [for ajax]
			if( $ajax ) { return 0; }
			return;
		}

		if( isset( $_POST['_name'] ) ) {
			if( $_POST['_name'] > '' )
				update_user_meta( $user_id, '_wpc_info_name', sanitize_text_field( $_POST['_name'] ) );
			else
				delete_user_meta( $user_id, '_wpc_info_name' );
		}

		if( isset( $_POST['bio'] ) ) {
			if( $_POST['bio'] > '' )
				update_user_meta( $user_id, '_wpc_info_bio', str_replace( '--newline--', "\n", sanitize_text_field( str_replace("\n", '--newline--', $_POST['bio'] ) ) ) );
			else
				delete_user_meta( $user_id, '_wpc_info_bio' );
		}
		
		foreach( wpc_social_list() as $field => $name ) {
			if( isset( $_POST[$field] ) ) {
				if( $_POST[$field] > '' )
					update_user_meta( $user_id, '_wpc_info_' . $field, sanitize_text_field( $_POST[$field] ) );
				else
					delete_user_meta( $user_id, '_wpc_info_' . $field );
			}
		}

		do_action('wpc_update_user_profile', $user_id, $_REQUEST);

		if( isset( $_POST['submit'] ) ) {

			if( $ajax ) { return 1; }

			wp_redirect( '?done=edit-profile' );
			exit;

		}

	}

	public function actions( $action, $user_id ) {

		if ( ! wpc_current_user_can( "ban" ) ) return;

		$user_id = (int) $user_id;

		switch ($action) {
			
			case 'ban':
				
				if( ! wpc_current_user_can('ban') || user_can( $user_id, 'manage_options' ) || ! get_userdata( $user_id ) )
					return false;

				$meta = get_option( '_wpc_banned_list' );

				if( $meta > '' ) {
					$meta = explode( ',', $meta );
				} else {
					$meta = array();
				}

				if( ! in_array( $user_id, $meta ) ) {
					do_action('wpc_pre_ban_user', $user_id);
					array_push($meta, $user_id);
					$meta = array_filter( array_unique( $meta ) );
					update_option( '_wpc_banned_list', implode( ',', $meta ) );
					do_action('wpc_post_ban_user', $user_id);
					return true;
				}

				return false;

				break;

			case 'unban':
				
				if( ! wpc_current_user_can('ban') || user_can( $user_id, 'manage_options' ) || ! get_userdata( $user_id ) )
					return false;

				$meta = get_option( '_wpc_banned_list' );

				if( $meta > '' ) {
					$meta = explode( ',', $meta );
				} else {
					$meta = array();
				}

				if( in_array( $user_id, $meta ) ) {
					do_action('wpc_pre_unban_user', $user_id);
					unset( $meta[array_search( $user_id, $meta )] );
					$meta = array_filter( array_unique( $meta ) );
					if( ! empty( $meta ) )
						update_option( '_wpc_banned_list', implode( ',', $meta ) );
					else
						delete_option( '_wpc_banned_list' );
					do_action('wpc_post_unban_user', $user_id);
					return true;
				}

				return false;

				break;

		}

	}

	/*public function roles_list() {

			$list = array();
			
			$defaults = array(
				"upload_attachements",
				"report_messages",
				"edit_reports",
				"block_users",
				"mark_unread",
				"edit_own_profile"
			);

			$list['wpc_user'] = $list['wpc_moderator'] = $list['wpc_admin'] = array();

			foreach( $defaults as $cap ) { $list['wpc_user'][] = $cap; }
			foreach( $defaults as $cap ) { $list['wpc_admin'][] = $cap; }
			foreach( $defaults as $cap ) { $list['wpc_moderator'][] = $cap; }
			
			$list['wpc_admin'][] = "edit_mods";
			$list['wpc_admin'][] = "watch_conversations";
			$list['wpc_admin'][] = "ban";
			//$list['wpc_admin'][] = "edit_banned";
			$list['wpc_admin'][] = "edit_notifications";
			$list['wpc_admin'][] = "edit_profiles";
			$list['wpc_admin'][] = "read_all_stats";

			$list['wpc_moderator'][] = "ban";
			//$list['moderator'][] = "edit_banned";
			
			return apply_filters( "wpc_roles_list", $list );
	}

	public function roles( $user_id = 0 ) {
		$user_data = get_userdata( $user_id );

		if( ! $user_data && ! is_user_logged_in() )
			return;

		if( ! $user_data )
			$user_id = wp_get_current_user()->ID;

		$user_data = get_userdata( $user_id );
		$list = $this->roles_list();

		//$roles = get_user_meta( $user_id, 'wpc_user_roles', TRUE );

		$roles = array();
		foreach( $list['wpc_user'] as $role ) {
			$roles[] = $role;
		}

		if( in_array( 'administrator', (array) $user_data->roles ) ) {
			
			foreach( $list['wpc_admin'] as $role ) {
				$roles[] = $role;
			}

		} elseif( wpc_is_mod( $user_id ) ) {
			foreach( $list['wpc_moderator'] as $role ) {
				$roles[] = $role;
			}
		}
		

		//$roles = apply_filters( 'wpc_user_roles', $roles, $user_id );
		$roles = array_filter( array_unique( $roles ) ); // filtering away duplicates and empty values

		return $roles;	
	}*/

	public function user_can( $user_id, $action ) {

		$caps = WPC_user_roles::user_roles_data( $user_id )->caps;

		switch( $action ) {

			case 'upload_images':
				return in_array( 'upload_attachements', $caps);
				break;

			case 'add_mod':
			case 'remove_mod':
				return in_array( 'edit_mods', $caps );
				break;

			case 'ban':
				return wpc_is_mod( $user_id ) && in_array( 'ban', $caps);
				break;

			case 'edit_users':
				return in_array( 'edit_profiles', $caps );
				break;

			default:
				return in_array( $action, $caps );
				break;
		}

		return;
	}

	public function uploads_list( $user_id = false ) {
		
		global $wpdb;
		$table = $wpdb->prefix . 'options';

		$query = $wpdb->get_results("SELECT `option_value` FROM $table WHERE `option_name` LIKE '%_wpc_upload_%' ORDER BY `option_id` DESC");

		$list = array();

		foreach( $query as $q ) {
			$meta = $q->option_value;
			$meta = str_replace('\\', '///', $meta);
			$meta = json_decode( $meta, true );
			$meta['path'] = str_replace('///', '\\', $meta['path']);
			$meta['author'] = (int) $meta['author'];
			$meta['time'] = (int) $meta['time'];
			$meta['id'] = (int) $meta['id'];

			if( (int) $user_id > 0 ) {
				if( (int) $user_id == $meta['author'] ) {
					$list[] = (object) $meta;
				}
			} else {
				$list[] = (object) $meta;
			}
			
		}

		return $list;

	}

	public function last_contacts( $limit = 10, $exclude = array() ) {

		if( ! is_array( $exclude ) ) {
			$int = (int) $exclude;
			$exclude = array();
			$exclude[] = $int;
		}

		$exclude[] = wp_get_current_user()->ID;

		global $wpc_my_contacts;

		$users = array();

		if ( ! empty( $wpc_my_contacts ) ) {
			$users = $wpc_my_contacts;
		} else {

			foreach( get_users() as $i => $user ) {

				if( in_array($user->ID, $exclude) ) {
					continue; // excluded
				}

				$pm = wpc_get_conversation( wpc_get_conversation_id( $user->ID ) );
				
				if( empty( $pm ) || empty( $pm->last_message ) || wp_get_current_user()->ID == $user->ID ) {
					continue;
				}

				$users[] = array(
					'int' => $pm->last_message->date,
					'id' => $user->ID
				);

			}

			rsort($users);

			$GLOBALS['wpc_my_contacts'] = $users;

		}

		if( ! ( $limit > 0 ) )
			$limit = count( $users );

		foreach( $users as $i => $user ) {
			if( $i >= $limit )
				break;

			$users[$i] = get_userdata( $user['id'] );
		}

		foreach( $users as $i => $user ) {
			if( ! is_object($user) )
				unset( $users[$i] );
		}

		return $users;

	}

	public function notification_settings( $user_id = 0 ) {

		if( ! $user_id && wpc_get_displayed_user_id() ) {
			$user_id = wpc_get_displayed_user_id();
		}

		if( ! $user_id ) { return; }

		$meta = get_user_meta( $user_id, '_wpc_notification_settings', TRUE);

		if( $meta > '' ) { $meta = json_decode( $meta ); }

		$settings = array();
		$settings['site']['messages'] = true;
		$settings['site']['moderation'] = true;
		$settings['mail']['messages'] = true;
		$settings['mail']['moderation'] = true;
		$settings['mail']['email'] = get_userdata( $user_id )->user_email;
		$settings['mail']['mod_mail'] = 'instant';

		if( is_object( $meta ) ) {

			if( ! isset( $meta->site ) || ! is_object( $meta->site ) ) { $meta->site = new stdClass(); }
			if( ! isset( $meta->mail ) || ! is_object( $meta->mail ) ) { $meta->mail = new stdClass(); }
			if( ! isset( $meta->site->messages ) || ! is_bool( $meta->site->messages ) ) {
				$meta->site->messages = $settings['site']['messages'];
			}
			if( ! isset( $meta->site->moderation ) || ! is_bool( $meta->site->moderation ) ) {
				$meta->site->moderation = $settings['site']['moderation'];
			}
			if( ! isset( $meta->mail->messages ) || ! is_bool( $meta->mail->messages ) ) {
				$meta->mail->messages = $settings['mail']['messages'];
			}
			if( ! isset( $meta->mail->moderation ) || ! is_bool( $meta->mail->moderation  ) ) {
				$meta->mail->moderation = $settings['mail']['moderation'];
			}
			if( ! isset( $meta->mail->email ) || ! strpos( $meta->mail->email, '@' ) || strlen( $meta->mail->email ) < 6 ) {
				$meta->mail->email = $settings['mail']['email'];
			}
			if( ! isset( $meta->mail->mod_mail ) || ! in_array( $meta->mail->mod_mail, array( 'nothing', 'instant', 'summary' ) ) ) {
				$meta->mail->mod_mail = $settings['mail']['mod_mail'];
			}
			if( "nothing" == $meta->mail->mod_mail ) {
				$meta->mail->moderation = false;
			}

		} else {
			$meta = (object) $settings;
			$meta->site = (object) $meta->site;
			$meta->mail = (object) $meta->mail;
		}

		return $meta;

	}

	public function profile_edit_groups() {

		$groups = array();

		ob_start();
		require wpc_template_path( 'users/user-edit-basicinfo' );
		$basicInfoHtml = ob_get_clean();

		ob_start();
		require wpc_template_path( 'users/user-edit-notifications' );
		$notificationsHtml = ob_get_clean();

		ob_start();
		require wpc_template_path( 'users/user-edit-social' );
		$socialHtml = ob_get_clean();

		$groups[] = array(
			'name' => 'basic-info',
			'slug' => 'basic',
			'title' => wpc_translate('Basic info'),
			'html' => $basicInfoHtml
		);

		$groups[] = array(
			'name' => 'notifications',
			'slug' => 'notifications',
			'title' => wpc_translate('Notifications'),
			'html' => $notificationsHtml
		);

		$groups[] = array(
			'name' => 'social',
			'slug' => 'social',
			'title' => wpc_translate('Social'),
			'html' => $socialHtml
		);

		// hook into this to add your custom tabs and/or edit existing ones
		$groups = apply_filters( 'wpc_profile_edit_groups', $groups );

		/**
		  * If you have a template file to include rather than adding the html to the array,
		  * make sure to save it in assets/users/user-edit-{group name}.php and it will be
		  * called and you don't have to set $group['html'] while filtering groups.		  * 
		  */

		foreach( $groups as $i => $group ) {
			if( empty( $group['html'] ) ) {
				$path = wpc_template_path( 'users/user-edit-' . $group['name'] );
				if( file_exists( $path ) ) {
					$groups[$i]['require'] = $path;
				}
			}
		}

		return $groups;

	}

	public function profile_edit_group_content() {

		$groups = $this->profile_edit_groups();

		$group = wpc_profile_edit_current_group_name() ? wpc_profile_edit_current_group_name() : $groups[0]['name'];

		foreach( $groups as $gr ) {
			if( $group == $gr['name'] ) {
				$group = $gr;
				$found = true; 
			}
		}

		if( ! $found ) { $group = $groups[0]; } // not a registered group, grab 1st one then

		if( ! is_array( $group ) ) { return; }

		do_action('wpc_update_user_profile_' . $group['name']);

		if( ! empty( $group['html'] ) ) {
			echo $group['html'];
		}
		elseif ( ! empty( $group['require'] ) ) {
			require $group['require'];
		}


		echo '<input type="hidden" name="_wpc_updating_' . preg_replace('/[^\da-z_-]/i', '', $group['name'] ) . '" value="1" />';

	}

	public function profile_groups() {

		$profile_groups = array();

		ob_start();
		require wpc_template_path( 'users/user-single' );
		$profileHtml = ob_get_clean();

		ob_start();
		require wpc_template_path( 'users/user-notifications' );
		$notificationsHtml = ob_get_clean();

		ob_start();
		require wpc_template_path( 'users/user-edit' );
		$editProfileHtml = ob_get_clean();

		$profile_groups[] = array(
			'name' => 'profile',
			'slug' => 'profile',
			'title' => wpc_translate('Profile'),
			'html' => $profileHtml
		);

		$profile_groups[] = array(
			'name' => 'notifications',
			'slug' => 'notifications',
			'title' => wpc_translate('Notifications'),
			'html' => $notificationsHtml
		);

		$profile_groups[] = array(
			'name' => 'edit',
			'slug' => 'edit',
			'title' => wpc_translate('Edit profile'),
			'html' => $editProfileHtml
		);

		// hook into this to add your custom tabs and/or edit existing ones
		$profile_groups = apply_filters( 'wpc_profile_groups', $profile_groups );

		return $profile_groups;

	}

	public function user_chart( $user_id ) {
		$days = array();
		$month = date('m');
		$year = date('Y');
		for($d=1; $d<=31; $d++) {
		    $time = mktime(12, 0, 0, $month, $d, $year);          
		    if (date('m', $time) == $month) {
		    	if ( $d > date('d') ) { break; }
		        $days[(int)date('d',$time)] = date('d-m-Y', $time);
		    }
		}

		$stats = new stdClass();
		$stats->stats = (array) WPC_stats::get( $user_id, 0, apply_filters('wpc_user_stats_chart_elements', array(), $user_id) );;

		if( empty( $stats->stats ) ) {
			echo '<div style="display:table;margin:0 auto;padding:2em 0;font-style:italic"><p>' . wpc_translate('Nothing to see here for the moment. Please check back soon.') . '</p></div>';
			return;
		}

		$stats->labels = array();

		foreach( $stats->stats as $i => $data ) {
			if( ! is_array( $data ) ) { continue; }
			foreach ( $data as $name => $val ) {
				$stats->labels[] = $name;
			}
		}

		$stats->labels_data = array();

		foreach( $days as $int => $day ) {
			if( $int < 10 ) { $int = "0{$int}"; }
			if( isset( $stats->stats[$int] ) ) {
				foreach ( $stats->stats[$int] as $name => $val ) {
					if( ! isset( $stats->labels_data[$name] ) || ! is_array( $stats->labels_data[$name] ) ) {
						$stats->labels_data[$name] = array();
					}
					$stats->labels_data[$name][$int] = $val; 
				}
			}
		}

		foreach( $stats->labels_data as $label => $data ) {
			foreach( $days as $int => $day ) {
				if( $int < 10 ) { $int = "0{$int}"; }
				if( ! isset( $data[$int] ) ) { $stats->labels_data[$label][$int] = 0; }
			}
			ksort( $stats->labels_data[$label] );
		}

		$stats->colors = array("#CC1600","#CDDC39","#009688","#B0171F","#9C27B0","#00EE76","#03A9F4","#0000FF","#673AB7","#9E9E9E","#FFEB3B","#8B8878","#F44336","#E91E63","#FF8000","#FF9800","#CC1600","#4CAF50","#ABABAB","#795548","#3F51B5","#C00000","#8B1A1A","#FF3E96","#FFFF00","#607D8B","FF5722");

		//shuffle( $stats->colors );

		$stats->colors = apply_filters('wpc_user_stats_chart_label_colors', $stats->colors);

		$colors = $stats->colors;
		$stats->colors = array();
		$i = 0;
		foreach( $stats->labels as $label ) {
			$stats->colors[$label] = isset( $colors[$i] ) ? $colors[$i] : '#' . rand(100,999);;
			$i++;
		}
		$stats->colors = (object) $stats->colors;

		?>

			<div style="max-height:400px;width: 100%"><canvas id="wpc_user-<?php echo $user_id; ?>_stats" width="400" height="400"></canvas></div>
			<strong style="display:block;text-align:center;font-style:italic;font-weight:600"><?php echo wpc_translate('This month so far'); ?></strong>
			<script>
				jQuery(document).ready(function($){
					var ctx = document.getElementById("wpc_user-<?php echo $user_id; ?>_stats");
					var wpcUserChart = new Chart(ctx, {
					    type: '<?php echo wpc_settings()->charts_style; ?>',
					    data: {
					        labels: [<?php foreach($days as $int=>$day) echo "\"$int\"," ?>],
					        datasets: [<?php foreach( $stats->labels_data as $label => $data ) {
								?>
								{
						            label: '<?php echo wpc_stats_graph_label_name($label); ?>',
						            data: [<?php echo implode(',', $data); ?>],
						            fill: false,
						            <?php echo 'line' == wpc_settings()->charts_style ? 'borderColor' : 'backgroundColor'; ?>: "<?php echo $stats->colors->$label; ?>"
					        	},
								<?php
							}?>]
					    },
					    options: {
					        scales: {
					            yAxes: [{
					                ticks: {
					                    beginAtZero:true
					                }
					            }]
					        },
					        responsive: true,
					        maintainAspectRatio: false
					    }
					});
				});
			</script>
			<!-- Copyright (C) 2016 Chartjs.org -->

		<?php

	}

}