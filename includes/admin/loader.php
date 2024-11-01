<?php


class WPC_admin_init
{

	protected static $instance = null;

	public static function instance() {

		return null == self::$instance ? new self : self::$instance;

	}

	public function init() {

		require 'functions.php';

		add_action( 'admin_menu', function() {

			add_menu_page(
				'WpChats',
				'WpChats',
				'manage_options',
				'wpchats',
				array( &$this, 'screen' ),
				'dashicons-groups'
			);

			add_submenu_page(
				'wpchats',
				'Settings &lsaquo; WpChats',
				'Settings',
				'manage_options',
				'wpchats-settings',
				array( &$this, 'settings_screen' )
			);

			add_submenu_page(
				'wpchats',
				'Emoji &lsaquo; WpChats',
				'Emoji',
				'manage_options',
				'wpchats-emoji',
				array( &$this, 'emoji_screen' )
			);

			add_submenu_page(
				'wpchats',
				'Users &lsaquo; WpChats',
				'Users',
				'manage_options',
				'wpchats-users',
				array( &$this, 'users_screen' )
			);

		});

		add_action('admin_footer', function() {
			?>
				<script>
					jQuery(document).ready(function($) {
						var wpcParent = $('a[href*="admin.php?page=wpchats"]').closest('li.menu-top');
						$('li.wp-first-item a', wpcParent).text('Overview');
					});
				</script>
			<?php
		});

		add_action('admin_enqueue_scripts', function() {
			wp_enqueue_script('wpc-admin-js', WPC_URL . 'assets/js/global.admin.js', false, null );
			wp_enqueue_style('wpc-admin-css', WPC_URL . 'assets/css/admin.css' );
			wp_enqueue_script('jquery');
			wp_enqueue_script('chartjs', '//cdnjs.cloudflare.com/ajax/libs/Chart.js/2.1.3/Chart.min.js', array('jquery'), null );
			wp_enqueue_media();
		});

		/**
		  * Pushing admin custom emoji to the emoticons list 
		  */

		add_filter('wpc_emoji_list', function($list) {

			$adminList = wpc_admin_custom_emoji_list();

			if( ! ( $adminList > '' ) )
				return $list;

			foreach( $adminList as $emoticon ) {

				$list[count($list)] = (object) array(
					'url' => $emoticon->url,
					'symbol' => $emoticon->symbol,
					'description' => $emoticon->description
				);

			}

			return $list;

		});

		add_filter('wpc_settings', function($settings) {

			$meta = get_option('_wpc_settings');

			if( $meta > '' ) {

				$meta = html_entity_decode( stripslashes($meta) );
				$meta = json_decode($meta);

				if( ! empty( $meta->slugs ) ) {
					if( ! empty( $meta->slugs->messages ) )
						$settings->slugs->messages = (string) $meta->slugs->messages;
					if( ! empty( $meta->slugs->archives ) )
						$settings->slugs->archives = (string) $meta->slugs->archives;
					if( ! empty( $meta->slugs->users ) )
						$settings->slugs->users = (string) $meta->slugs->users;
					if( ! empty( $meta->slugs->mod ) )
						$settings->slugs->mod = (string) $meta->slugs->mod;
				}

				if( ! empty( $meta->page ) ) { // validated upon saving
					$settings->page = (int) $meta->page;
				}

				if( isset( $meta->blocking ) ) {
					$settings->blocking = (bool) $meta->blocking;
				}

				if( ! empty( $meta->pagination ) ) {
					if( ! empty( $meta->pagination->conversations ) )
						$settings->pagination->conversations = (int) $meta->pagination->conversations;
					if( ! empty( $meta->pagination->messages ) )
						$settings->pagination->messages = (int) $meta->pagination->messages;
					if( ! empty( $meta->pagination->users ) )
						$settings->pagination->users = (int) $meta->pagination->users;
				}

				if( ! empty( $meta->uploads ) ) {
					if( isset( $meta->uploads->enable ) )
						$settings->uploads->enable = $meta->uploads->enable;
					if( isset( $meta->uploads->meta_data ) )
						$settings->uploads->meta_data = $meta->uploads->meta_data;
					if( isset( $meta->uploads->allowed ) ) {
						foreach ( $meta->uploads->allowed as $i => $ext ) {
							$meta->uploads->allowed->$i = str_replace( " ", "", $ext );
						}
						$meta->uploads->allowed = array_filter( array_unique( (array) $meta->uploads->allowed ) );
						if( ! empty( $meta->uploads->allowed ) )
							$settings->uploads->allowed = (array) $meta->uploads->allowed;
						else
							$settings->uploads->allowed = (array) wpc_settings(true)->uploads->allowed;
					}
					if( isset( $meta->uploads->max_size_kb ) ) {
						$settings->uploads->max_size_kb = $meta->uploads->max_size_kb;
						$settings->uploads->max_size = abs( (int) $meta->uploads->max_size_kb * 1000 );
					}
 				}

 				if( isset( $meta->isTyping_allowed ) ) {
					$settings->isTyping_allowed = (bool) $meta->isTyping_allowed;
				}

			}

			return $settings;

		});

		add_filter('wpc_admin_upload_type_text', function( $type ) {
			
			switch( $type ) {

				case 'message_attachement':
					return "message attachements";
					break;

				case 'cover_photo':
					return "cover photos";
					break;

				default:
					return $type;
					break;
			}

		});

		add_action('wpc_admin_post_delete_upload', function( $data ) { 
			
			if( ! isset( $data->author, $data->url, $data->type ) ) {
				return;
			}

			if( "cover_photo" === $data->type ) {
				global $wpdb;
				$table = $wpdb->prefix . 'usermeta';
				$url = $data->url . '?' . $data->id;
				$query = $wpdb->get_results( "SELECT `user_id` FROM $table WHERE `meta_value` = '$url' LIMIT 1" );
				if( ! empty( $query[0] ) && ! empty( $query[0]->user_id ) ) {
					delete_user_meta( (int) $query[0]->user_id, "_wpc_cover_photo" );
				}
			}

			return;

		});

		add_action('admin_init', function() {
			if( wpc_is_admin_ajax() ) { return; }
			remove_filter('wpc_settings', 'wpc_filter_wpc_settings_uploads_enable');
		});

		add_filter( "wpc_roles_list", function( $roles ) { 
			
			if( /*false !==*/ $custom_roles = get_option( "wpc_admin_custom_roles" ) ) {			
				$custom_roles = json_decode( $custom_roles, true );
				foreach( $custom_roles as $role ) {
					if( isset( $roles[$role['name']] ) ) {
						continue;
					}
					$roles[$role['name']] = empty($role['caps'])||!is_array($role['caps']) ? array() : $role['caps'];
				}
			}

			if( /*false !==*/ $custom_caps = get_option( "wpc_admin_roles_caps" ) ) {
				$custom_caps = json_decode( $custom_caps, true );
				foreach( $custom_caps as $_role => $_caps ) {
					if( "wpc_admin" == (string) $_role ) { continue; }
					if( isset( $roles[$_role] ) ) {
						$roles[$_role] = (array) $_caps;
					}
				}
			}

			return $roles;

		});

		add_filter( "wpc_settings", function( $settings ){

			if ( $data = json_decode( get_option("_wpc_settings_other"), true ) ) {

				if( isset( $data['notifications'] ) && is_bool( $data['notifications'] ) ) {
					$settings->notifications = (bool) $data['notifications'];
				} else {
					$settings->notifications = true;
				}

				if( isset( $data['stats'] ) && is_bool( $data['stats'] ) ) {
					$settings->stats = (bool) $data['stats'];
				} else {
					$settings->stats = true;
				}

				if( isset( $data['menu'] ) ) {

					if( isset( $data['menu']['locations'] ) ) {
						$settings->nav_menu->locations = (array) $data['menu']['locations'];
					} else {
						$settings->nav_menu->locations = array();
					}

					if( !empty( $data['menu']['items'] ) ) {
						if( ! empty( $data['menu']['items']['messages'] ) ) {
							$settings->nav_menu->messages->enable = true;
							$settings->nav_menu->messages->inner = strval( $data['menu']['items']['messages'] );
						} else {
							$settings->nav_menu->messages->enable = false;
						}
						if( ! empty( $data['menu']['items']['users'] ) ) {
							$settings->nav_menu->users->enable = true;
							$settings->nav_menu->users->inner = strval( $data['menu']['items']['users'] );
						} else {
							$settings->nav_menu->users->enable = false;
						}
					} else {
						$settings->nav_menu->messages->enable = false;
						$settings->nav_menu->users->enable = false;
					}
				}

				$settings->RTL = isset($data['rtl']) ? (bool) $data['rtl'] : false;
				$settings->caching = isset($data['caching']) ? (bool) $data['caching'] : true;
				$settings->copy = isset($data['copy']) ? (bool) $data['copy'] : true;

			}

			return $settings;

		});

		add_filter("wpc_settings", function($settings) { 
	
			if ( $meta = json_decode( get_option( "_wpc_settings_users" ), true ) ) {

				if ( ! empty( $meta['slugs']['users'] ) ) {
					$settings->slugs->users = (string) $meta['slugs']['users'];
				}

				if ( ! empty( $meta['slugs']['mod'] ) ) {
					$settings->slugs->mod = (string) $meta['slugs']['mod'];
				}

				if ( ! empty(  $meta['pagination'] ) ) {
					$settings->pagination->users = (int) $meta['pagination'];
				}

				if ( isset( $meta['preferences'] ) ) {
					$settings->preferences = (bool) $meta['preferences'];
				}

				if ( ! empty( $meta['on_cant_view_profile'] ) ) {
					$settings->on_cant_view_profile = (string) $meta['on_cant_view_profile'];
				}

			}

			if ( /*false !==*/ $meta = json_decode( base64_decode( get_option( "_wpc_settings_ajax" ) ), true ) ) {
				if ( ! empty( $meta['preloader'] ) ) {
					$settings->ajax->preloader = stripslashes( $meta['preloader'] );
				}

				if ( isset( $meta['autosave'] ) ) {
					$settings->ajax->autosave = (bool) $meta['autosave'];
				}

				if ( isset( $meta['interval'] ) && ! empty(  $meta['interval'] ) ) {
					$settings->ajax->interval = (int) $meta['interval'];
				}

				if ( ! empty( $meta['title_selector'] ) ) {
					$settings->ajax->title_selector = (string) $meta['title_selector'];
				}
			}

			return $settings;

		});

		add_filter("wpc_social_list", "wpc_admin_toggle_social_profiles", 999);
		function wpc_admin_toggle_social_profiles($list) {
			if ( /*false !==*/ $meta = json_decode( get_option( "_wpc_settings_users" ), true ) ) {
				if ( ! empty( $list ) ) {
					if ( empty( $meta['social'] ) ) $meta['social'] = array();
					foreach ( $list as $id => $name ) {
						if ( ! in_array( $id, $meta['social'] ) ) {
							unset( $list[$id] );
						}
					}
				}
			}
			return $list;
		}

		add_filter("wpc_social_list", function( $list ) { 
			if ( is_admin() && ! wpc_is_admin_ajax() ) return $list;
			if ( $meta = json_decode( get_option( "_wpc_settings_users" ), true ) ) {
				if ( empty( $meta['social_more'] ) ) return $list;
				foreach ( $meta['social_more'] as $data ) {
					$list[$data['name']] = $data['name'];
				}
			}
			return $list;
		}, 1000);

		add_filter("wpc_social_list_icons", function( $list ) { 	
			if ( $meta = json_decode( get_option( "_wpc_settings_users" ), true ) ) {
				if ( empty( $meta['social_more'] ) ) return $list;
				foreach ( $meta['social_more'] as $data ) {
					$list[$data['name']] = $data['icon'];
				}
			}

			return $list;
		});

		add_filter( "plugin_action_links_".plugin_basename(WPC_FILE), function($links) {
    		array_push( $links, '<a href="admin.php?page=wpchats">' . __( 'Overview' ) . '</a>' );
    		array_push( $links, '<a href="admin.php?page=wpchats-settings">' . __( 'Settings' ) . '</a>' );
    		array_push( $links, '<a href="https://plugin.wpchats.io" style="color:#8BC34A">' . __( 'Get PRO' ) . '</a>' );
		  	return $links;
		});

		add_action('admin_init', function() {
			$groups = wpc_profile_edit_groups();
			$rules = '';
			if( ! $groups || empty( $groups ) ) { return; }
			foreach( $groups as $group ) {
				$rules .= $group['name'] . ':' . $group['slug'];
				$rules .= $group !== end( $groups ) ? ',' : '';
			}
			if( $rules !== get_option('_wpc_profile_edit_groups_rewrite_rules') ) {
				wpc_flush_rewrite_rules();
				update_option( '_wpc_profile_edit_groups_rewrite_rules', $rules );
			}
		});

	}

	public function screen() {
		
		?>

			<div class="wrap">
				
				<h2>WpChats overview</h2>
				<?php require 'screens/overview.php'; ?>

				<p style="border-top: 1px dashed #ddd; padding-top: 1em; margin-top: 1.5em;"><strong>Need help?</strong></p>

				<li><a href="https://plugin.wpchats.io/faq/">FAQ</a></li>
				<li><a href="https://plugin.wpchats.io/tutorials/">WpChats Tutorials</a></li>
				<li><a href="https://plugin.wpchats.io/contact/">Contact Us</a></li>
				<li><a href="https://plugin.wpchats.io">Support</a></li>

				<p><strong>WpChats PRO</strong></p>

				<p>Unlock more features, from attachement uploads to custom user roles and limitations..<br/>Find out more on <a href="https://plugin.wpchats.io">plugin.wpchats.io &rsaquo;</a></p>

				<p><strong>Feedback</strong></p>

				<p>Liked the plugin? your ratings and reviews are always appreciated! <a href="https://wordpress.org/support/view/plugin-reviews/wpchats-lite-private-messaging?rate=5#postform">&star;&star;&star;&star;&star; Rate WpChats Lite &rsaquo;</a></p>

			</div>
		
		<?php
	}

	public function settings_screen() {
		
		?>

			<div class="wrap">
				
				<h2><?php echo apply_filters('_wpc_admin_settings_heading_title', 'WpChats &rsaquo; settings'); ?></h2>
				<?php require 'screens/settings.php'; ?>

				<p style="border-top: 1px dashed #ddd; padding-top: 1em; margin-top: 1.5em;"><strong>Need help?</strong></p>

				<li><a href="https://plugin.wpchats.io/faq/">FAQ</a></li>
				<li><a href="https://plugin.wpchats.io/tutorials/">WpChats Tutorials</a></li>
				<li><a href="https://plugin.wpchats.io/contact/">Contact Us</a></li>
				<li><a href="https://plugin.wpchats.io">Support</a></li>

				<p><strong>WpChats PRO</strong></p>

				<p>Unlock more features, from attachement uploads to custom user roles and limitations..<br/>Find out more on <a href="https://plugin.wpchats.io">plugin.wpchats.io &rsaquo;</a></p>

				<p><strong>Feedback</strong></p>

				<p>Liked the plugin? your ratings and reviews are always appreciated! <a href="https://wordpress.org/support/view/plugin-reviews/wpchats-lite-private-messaging?rate=5#postform">&star;&star;&star;&star;&star; Rate WpChats Lite &rsaquo;</a></p>

			</div>
		
		<?php
	}

	public function emoji_screen() {
		
		?>

			<div class="wrap">
				
				<h2>WpChats &rsaquo; Emoji</h2>

				<?php require 'screens/emoji.php'; ?>

				<p style="border-top: 1px dashed #ddd; padding-top: 1em; margin-top: 1.5em;"><strong>Need help?</strong></p>

				<li><a href="https://plugin.wpchats.io/faq/">FAQ</a></li>
				<li><a href="https://plugin.wpchats.io/tutorials/">WpChats Tutorials</a></li>
				<li><a href="https://plugin.wpchats.io/contact/">Contact Us</a></li>
				<li><a href="https://plugin.wpchats.io">Support</a></li>

				<p><strong>WpChats PRO</strong></p>

				<p>Unlock more features, from attachement uploads to custom user roles and limitations..<br/>Find out more on <a href="https://plugin.wpchats.io">plugin.wpchats.io &rsaquo;</a></p>

				<p><strong>Feedback</strong></p>

				<p>Liked the plugin? your ratings and reviews are always appreciated! <a href="https://wordpress.org/support/view/plugin-reviews/wpchats-lite-private-messaging?rate=5#postform">&star;&star;&star;&star;&star; Rate WpChats Lite &rsaquo;</a></p>

			</div>
		
		<?php
	}

	public function users_screen() {
		
		?>

			<div class="wrap">
				
				<div class="wpc-users">
					
					<h2><?php echo apply_filters('_wpc_admin_users_heading_title', 'WpChats &rsaquo; Users'); ?></h2>

					<?php require 'screens/users.php'; ?>

					<p style="border-top: 1px dashed #ddd; padding-top: 1em; margin-top: 1.5em;"><strong>Need help?</strong></p>

				<li><a href="https://plugin.wpchats.io/faq/">FAQ</a></li>
				<li><a href="https://plugin.wpchats.io/tutorials/">WpChats Tutorials</a></li>
				<li><a href="https://plugin.wpchats.io/contact/">Contact Us</a></li>
				<li><a href="https://plugin.wpchats.io">Support</a></li>

				<p><strong>WpChats PRO</strong></p>

				<p>Unlock more features, from attachement uploads to custom user roles and limitations..<br/>Find out more on <a href="https://plugin.wpchats.io">plugin.wpchats.io &rsaquo;</a></p>

				<p><strong>Feedback</strong></p>

				<p>Liked the plugin? your ratings and reviews are always appreciated! <a href="https://wordpress.org/support/view/plugin-reviews/wpchats-lite-private-messaging?rate=5#postform">&star;&star;&star;&star;&star; Rate WpChats Lite &rsaquo;</a></p>

				</div>

			</div>
		
		<?php
	}


}

WPC_admin_init::instance()->init();