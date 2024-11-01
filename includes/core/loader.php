<?php

class WPC_init
{

	protected static $instance = null;

	public static function instance() {

		return null == self::$instance ? new self : self::$instance;

	}

	function __construct() {

		// Gets file path or URI, favoring theme/child-theme files
		function wpc_file_path( $file, $return_path = false ) {
			$child_base = get_stylesheet_directory() . '/' . WPC_DIR_NAME . '/';
			if( file_exists( $child_base . $file ) ) {
				$base = $return_path ? get_stylesheet_directory() : get_stylesheet_directory_uri();
				return $base . '/' . WPC_DIR_NAME . '/' . $file;
			} else {
				$base = $return_path ? WPC_PATH : WPC_URL;
				return $base . $file;
			}
		}

		add_filter( 'cron_schedules', function( $schedules ) {
			$interval = (int) get_option( "_wpc_qcron_interval" ) > 0 ? (int) get_option( "_wpc_qcron_interval" ) : 900;
			$schedules['every_few_minutes'] = array(
			    'interval' 	=> $interval,
			    'display' 	=> sprintf( "Once Every %s Minutes", $interval/60 )
		    );
		    $schedules['wpc_weekly'] = array(
			    'interval' 	=> WEEK_IN_SECONDS,
			    'display' 	=> "Once a Week"
		    );
		    return $schedules;
		});

		require_once WPC_PATH . 'includes/core/functions.php';
		require_once WPC_PATH . 'includes/core/globals.php';
		require_once WPC_PATH . 'includes/admin/loader.php';
		require_once WPC_PATH . 'includes/core/activate.php';
		require_once WPC_PATH . 'includes/core/cache.php';
		require_once WPC_PATH . 'includes/core/message.php';
		require_once WPC_PATH . 'includes/core/ajax.php';
		require_once WPC_PATH . 'includes/core/users.php';
		require_once WPC_PATH . 'includes/core/hooks.php';
		require_once WPC_PATH . 'includes/core/notifications.php';
		require_once WPC_PATH . 'includes/core/create-profile-tab.php';
		require_once WPC_PATH . 'includes/core/stats.php';
		require_once WPC_PATH . 'includes/core/widgets.php';
		require_once WPC_PATH . 'includes/core/mailing.php';
		require_once WPC_PATH . 'includes/core/cron.php';
		require_once WPC_PATH . 'includes/core/roles.php';

		require_once wpc_file_path( 'includes/extend/functions.php', 1 );


		# require_once WPC_PATH . 'includes/core/shortcodes.php';

		do_action('wpc_pre_init');

	}

	function init() {

		add_action('init', function() {

			// setting up a page for the content if not there
			if( ! $post = get_post( wpc_settings()->page ) || "publish" !== $post->post_status ) {
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

			# archives

			add_rewrite_rule(
		    	wpc_get_bases()->messages . '/' . wpc_get_bases()->archives . '/?$',
		    	'index.php?pagename=' . get_post( wpc_settings()->page )->post_name . '&wpc_messages=1&wpc_archives=1',
		    	'top'
		    );

		    add_rewrite_rule(
		    	wpc_get_bases()->messages . '/' . wpc_get_bases()->archives . '/page/([0-9]+)/?$',
		    	'index.php?pagename=' . get_post( wpc_settings()->page )->post_name . '&wpc_messages=1&wpc_archives=1&wpc_paged=$matches[1]',
		    	'top'
		    );

		    # messages

		    add_rewrite_rule(
		    	wpc_get_bases()->messages . '/?$',
		    	'index.php?pagename=' . get_post( wpc_settings()->page )->post_name . '&wpc_messages=1',
		    	'top'
		    );

		    add_rewrite_rule(
		    	wpc_get_bases()->messages . '/new/?$',
		    	'index.php?pagename=' . get_post( wpc_settings()->page )->post_name . '&wpc_messages=1&wpc_new_message=1',
		    	'top'
		    );

		    add_rewrite_rule(
		    	wpc_get_bases()->messages . '/([^/]+)/?$',
		    	'index.php?pagename=' . get_post( wpc_settings()->page )->post_name . '&wpc_messages=1&wpc_recipient=$matches[1]',
		    	'top'
		    );

		    add_rewrite_rule(
		    	wpc_get_bases()->messages . '/([^/]+)/report/?$',
		    	'index.php?pagename=' . get_post( wpc_settings()->page )->post_name . '&wpc_messages=1&wpc_recipient=$matches[1]&wpc_report=1',
		    	'top'
		    );

		    add_rewrite_rule(
		    	wpc_get_bases()->messages . '/([^/]+)/report/([0-9]+)/?$',
		    	'index.php?pagename=' . get_post( wpc_settings()->page )->post_name . '&wpc_messages=1&wpc_recipient=$matches[1]&wpc_report=1&wpc_report_message=$matches[2]',
		    	'top'
		    );

		    add_rewrite_rule(
		    	wpc_get_bases()->messages . '/([^/]+)/forward/([0-9]+)/?$',
		    	'index.php?pagename=' . get_post( wpc_settings()->page )->post_name . '&wpc_messages=1&wpc_recipient=$matches[1]&wpc_forward_message=$matches[2]',
		    	'top'
		    );

		    add_rewrite_rule(
		    	wpc_get_bases()->messages . '/([^/]+)/page/([0-9]+)/?$',
		    	'index.php?pagename=' . get_post( wpc_settings()->page )->post_name . '&wpc_messages=1&wpc_recipient=$matches[1]&wpc_paged=$matches[2]',
		    	'top'
		    );

		    add_rewrite_rule(
		    	wpc_get_bases()->messages . '/page/([0-9]+)/?$',
		    	'index.php?pagename=' . get_post( wpc_settings()->page )->post_name . '&wpc_messages=1&wpc_paged=$matches[1]',
		    	'top'
		    );

		    add_rewrite_rule(
		    	wpc_get_bases()->messages . '/([^/]+)/mute/?$',
		    	'index.php?pagename=' . get_post( wpc_settings()->page )->post_name . '&wpc_messages=1&wpc_recipient=$matches[1]&wpc_mute_conversation=1',
		    	'top'
		    );

		    # users

		    add_rewrite_rule(
		    	wpc_get_bases()->users . '/?$',
		    	'index.php?pagename=' . get_post( wpc_settings()->page )->post_name . '&wpc_users=1',
		    	'top'
		    );

		    add_rewrite_rule(
		    	wpc_get_bases()->users . '/online/?$',
		    	'index.php?pagename=' . get_post( wpc_settings()->page )->post_name . '&wpc_users=1&wpc_online_users=1',
		    	'top'
		    );

		    add_rewrite_rule(
		    	wpc_get_bases()->users . '/blocked/?$',
		    	'index.php?pagename=' . get_post( wpc_settings()->page )->post_name . '&wpc_users=1&wpc_blocked_users=1',
		    	'top'
		    );

		    add_rewrite_rule(
		    	wpc_get_bases()->users . '/page/([0-9]+)/?$',
		    	'index.php?pagename=' . get_post( wpc_settings()->page )->post_name . '&wpc_users=1&wpc_paged=$matches[1]',
		    	'top'
		    );

		    add_rewrite_rule(
		    	wpc_get_bases()->users . '/online/page/([0-9]+)/?$',
		    	'index.php?pagename=' . get_post( wpc_settings()->page )->post_name . '&wpc_users=1&wpc_online_users=1&wpc_paged=$matches[1]',
		    	'top'
		    );

		    add_rewrite_rule(
		    	wpc_get_bases()->users . '/blocked/page/([0-9]+)/?$',
		    	'index.php?pagename=' . get_post( wpc_settings()->page )->post_name . '&wpc_users=1&wpc_blocked_users=1&wpc_paged=$matches[1]',
		    	'top'
		    );

		    add_rewrite_rule(
		    	wpc_get_bases()->users . '/([^/]+)/?$',
		    	'index.php?pagename=' . get_post( wpc_settings()->page )->post_name . '&wpc_users=1&wpc_user=$matches[1]',
		    	'top'
		    );


		    add_rewrite_rule(
		    	wpc_get_bases()->users . '/([^/]+)/edit/?$',
		    	'index.php?pagename=' . get_post( wpc_settings()->page )->post_name . '&wpc_users=1&wpc_user=$matches[1]&wpc_edit_user=1',
		    	'top'
		    );

		    foreach ( wpc_profile_edit_groups() as $group ) {

				add_rewrite_rule(
			    	wpc_get_bases()->users . '/([^/]+)/edit/' . $group['slug'] . '/?$',
			    	'index.php?pagename=' . get_post( wpc_settings()->page )->post_name . '&wpc_users=1&wpc_user=$matches[1]&wpc_edit_user=1&wpc_edit_user_group=' . $group['name'],
			    	'top'
			    );

			}

		   	add_rewrite_rule(
		    	wpc_get_bases()->users . '/([^/]+)/notifications/?$',
		    	'index.php?pagename=' . get_post( wpc_settings()->page )->post_name . '&wpc_users=1&wpc_user=$matches[1]&wpc_user_notifications=1',
		    	'top'
		    );

		    # moderation

		    add_rewrite_rule(
		    	wpc_get_bases()->mod . '/?$',
		    	'index.php?pagename=' . get_post( wpc_settings()->page )->post_name . '&wpc_mod=1',
		    	'top'
		    );

		    add_rewrite_rule(
		    	wpc_get_bases()->mod . '/([0-9]+)/?$',
		    	'index.php?pagename=' . get_post( wpc_settings()->page )->post_name . '&wpc_mod=1&wpc_reported_id=$matches[1]',
		    	'top'
		    );

		    add_rewrite_rule(
		    	wpc_get_bases()->mod . '/moderators/?$',
		    	'index.php?pagename=' . get_post( wpc_settings()->page )->post_name . '&wpc_mod=1&wpc_mods=1',
		    	'top'
		    );

		    add_rewrite_rule(
		    	wpc_get_bases()->mod . '/banned/?$',
		    	'index.php?pagename=' . get_post( wpc_settings()->page )->post_name . '&wpc_mod=1&wpc_mod_banned=1',
		    	'top'
		    );

		    add_rewrite_rule(
		    	wpc_get_bases()->mod . '/moderators/add/?$',
		    	'index.php?pagename=' . get_post( wpc_settings()->page )->post_name . '&wpc_mod=1&wpc_mods=1&wpc_add_mods=1',
		    	'top'
		    );

		    add_rewrite_rule(
		    	wpc_get_bases()->mod . '/messages/([^/]+)@([^/]+)/?$',
		    	'index.php?pagename=' . get_post( wpc_settings()->page )->post_name . '&wpc_mod=1&wpc_mod_messages=1&wpc_mod_user_a=$matches[1]&wpc_mod_user_b=$matches[2]',
		    	'top'
		    );

		    # short links

		    add_rewrite_rule(
		    	'~wpc/([^/]+)/?$',
		    	'index.php?_wpc=$matches[1]',
		    	'top'
		    );

		   	if( get_option("_wpc_needs_flush") ) {
				wpc_flush_rewrite_rules();
			}

		});

		add_filter('query_vars', function($vars) {
		    $vars[] = "wpc_messages";
		    $vars[] = "wpc_recipient";
		    $vars[] = "wpc_paged";
		    $vars[] = "wpc_archives";
		    $vars[] = "wpc_users";
		    $vars[] = "wpc_user";
		    $vars[] = "wpc_edit_user";
		    $vars[] = "wpc_blocked_users";
		    $vars[] = "wpc_online_users";
		    $vars[] = "wpc_report";
		    $vars[] = "wpc_report_message";
		    $vars[] = "wpc_mod";
		    $vars[] = "wpc_mods";
		    $vars[] = "wpc_mod_banned";
		    $vars[] = "wpc_add_mods";
		    $vars[] = "_wpc";
		    $vars[] = "wpc_forward_message";
		    $vars[] = "wpc_mute_conversation";
		    $vars[] = "wpc_mod_messages";
		    $vars[] = "wpc_mod_user_a";
		    $vars[] = "wpc_mod_user_b";
		    $vars[] = "wpc_new_message";
		    $vars[] = "wpc_user_notifications";
		    $vars[] = "wpc_reported_id";
		    $vars[] = "wpc_edit_user_group";
		    $vars[] = "wpc_custom_tab";
			return $vars;
		});

		add_action('wp', function() {

			if( is_page( wpc_settings()->page ) && ! is_feed() ) {
				if( ! wpc_is_messages() && ! wpc_is_users() && ! wpc_is_moderation() ) {
					wp_redirect( wpc_get_user_links()->users->all );
					exit;
				}
			}

			global $current_user;

			if( wpc_is_single_message() ) :;

				if( ! is_object( wpc_get_recipient() ) || is_object( wpc_get_recipient() ) && $current_user->ID == wpc_get_recipient()->ID ) {
					wp_redirect( wpc_messages_base() );
					exit;
				}

				if( isset( $_GET['do'] ) ) {

					$_do = (string) $_GET['do'];

					switch( $_do ) {

						case 'block':
							if( wpc_is_blocking_allowed() ) :;
								$args = array(
									'doing' 	=> 'block',
									'target'	=> wpc_get_recipient()->ID
								);
								WPC_message::instance()->block( $args );
							endif;
							break;

						case 'unblock':
							
							if( wpc_is_blocking_allowed() ) :;
								$args = array(
									'doing' 	=> 'unblock',
									'target'	=> wpc_get_recipient()->ID
								);
								WPC_message::instance()->block( $args );
							endif;							
							break;

						case 'unread':
							
							if( wpc_can_mark_unread() ) :;
								WPC_message::instance()->mark_read( false, true, true );							
							endif;
							break;

						case 'delete':
							
							$_message = isset( $_GET['m'] ) ? (int) $_GET['m'] : false;
							if( $_message )
								WPC_message::instance()->delete( $_message );
							if( ! $_message )
								WPC_message::instance()->delete(false, wpc_get_conversation_id());
							break;

						case 'archive':
							WPC_message::instance()->archive( wpc_get_conversation_id() );
							break;

						case 'unarchive':
							WPC_message::instance()->archive( wpc_get_conversation_id(), true );
							break;

					}

				}

			endif;

			if( wpc_is_messages() ) :;

				if( isset( $_GET['q'] ) && $q = strlen( sanitize_text_field( $_GET['q'] ) ) <= 0 ) {
					$url = $_SERVER['REQUEST_URI'];
					$url = substr( $url, 0, strpos( $url, 'q=' ) - 1 );
					wp_redirect( $url );
					exit;
				}

				if( isset( $_GET['view'] ) ) {
					if( "archives" == $_GET['view'] ) {
						wp_redirect( wpc_get_user_links()->archives );
						exit;
					} elseif( "conversations" == $_GET['view'] ) {
						wp_redirect( wpc_get_user_links()->messages );
						exit;
					}
				}

				if( ! is_user_logged_in() ) {
					$url = apply_filters('bbpmp_messages_logged_out_redirect_url', wp_login_url( $_SERVER['REQUEST_URI'] ));
					wp_redirect( $url );
					exit;
				}

			endif;

			/**
			  * Update user status 
			  * You can go offline by disabling this process ( add_filter('wpc_allow_update_status', '__retrun_false'); )
			  */

			$allow_update_status = apply_filters( 'wpc_allow_update_status', true );
			if( $allow_update_status ) { wpc_update_user_status(); }

			# users

			if( wpc_is_user_edit() ) {

				if( ! is_user_logged_in() ) {
					wp_redirect( wp_login_url( $_SERVER['REQUEST_URI'] ) );
					exit;
				}

				if( $current_user->ID !== wpc_get_displayed_user_id() && ! wpc_current_user_can('edit_profiles') || wpc_is_404_user() ) {
					wp_redirect( wpc_get_user_links( wpc_get_displayed_user_id() )->profile );
					exit;
				}

			}

			if( wpc_is_user_notifications() ) {
				if( $current_user->ID !== wpc_get_displayed_user_id() && ! current_user_can('manage_options') ) {
					wp_redirect( wpc_get_user_links( wpc_get_displayed_user_id() )->profile );
					exit;
				}
			}

			if( wpc_is_archive_blocked_users() ) {

				if( ! is_user_logged_in() ) {
					wp_redirect( wpc_get_user_links()->users->all );
					exit;
				}

			}

			if( wpc_is_moderation() ) {

				if( ! is_user_logged_in() || ! wpc_is_mod( wp_get_current_user()->ID ) ) {
					wp_redirect( wpc_get_user_links()->users->all );
					exit;
				}

			}

			if( wpc_is_report_message() ) {
				if( ! wpc_current_user_can('report_messages') ) {
					wp_redirect( wpc_get_user_links()->messages );
					exit;
				}
			}

			if( get_query_var( '_wpc' ) > '' ) {
				$_q = get_query_var( '_wpc' );

				if( strlen( (int) $_q ) == 10 && "m-" !== substr( $_q, 0, 2 ) ) {
					$pm = wpc_get_conversation( (int) $_q );
					if( ! empty( $pm ) && ! empty( $pm->contact ) ) {
						wp_redirect( wpc_get_conversation_permalink( '', $pm->contact ) );
						exit;
					}
				}

				elseif ( 'u-' == substr( $_q, 0, 2 ) ) {
					$user = get_userdata( (int) substr( $_q, 2 ) );
					if( is_object( $user ) ) {
						wp_redirect( wpc_get_user_links( $user->ID )->profile );
						exit;
					}
				}

				elseif ( "m-" == substr( $_q, 0, 2 ) ) {
					$m = wpc_get_message( (int) preg_replace('/\D/', '', $_q ) ); 
					if( ! empty( $m ) && isset( $m->PM_ID ) ) {
						wp_redirect( wpc_get_conversation_permalink( '', false, $m->PM_ID ) );
						exit;
					}
				}

				elseif( 'mod' == (string) $_q ) {
					wp_redirect( wpc_get_user_links()->mod );
					exit;
				}

				else {
					// user by slug

					if( "me" == (string) $_q ) {
						wp_redirect( wpc_get_user_links( $current_user->ID )->profile );
						exit;
					}

					$user = get_user_by( 'slug', (string) $_q );
					if( is_object( $user ) ) {
						wp_redirect( wpc_get_user_links( $user->ID )->profile );
						exit;
					}

				}

			}

		});

		/**
		  * Loading widgets
		  * from widgets.php file
		  */
		add_action( 'widgets_init', function() {
			
			$widgets = array( 'wpc_widgets_welcome', 'wpc_widgets_search' );

			foreach ( $widgets as $widget ) { register_widget( $widget ); }

		});

		add_action( 'wp_enqueue_scripts', function() {

			$base = get_stylesheet_directory_uri() . '/' . WPC_DIR_NAME . '/assets/';
			$_base = get_stylesheet_directory() . '/' . WPC_DIR_NAME . '/assets/';

			$child_css = $base . 'css/style.css';
			$core_css = WPC_URL . 'assets/css/style.css';
			$css_file = file_exists( $_base . 'css/style.css' ) ? $child_css : $core_css;

			$child_js = $base . 'js/functions.js';
			$core_js = WPC_URL . 'assets/js/functions.js';
			$js_file = file_exists( $_base . 'js/functions.js' ) ? $child_js : $core_js;

			$child_glbl_js = $base . 'js/global.js';
			$core_glbl_js = WPC_URL . 'assets/js/global.js';
			$glbl_js_file = file_exists( $_base . 'js/global.js' ) ? $child_glbl_js : $core_glbl_js;

			$child_main_js = $base . 'js/main.js';
			$core_main_js = WPC_URL . 'assets/js/main.js';
			$main_js_file = file_exists( $_base . 'js/main.js' ) ? $child_main_js : $core_main_js;

			$child_css_users = $base . 'css/users.css';
			$core_css_users = WPC_URL . 'assets/css/users.css';
			$css_file_users = file_exists( $_base . 'css/users.css' ) ? $child_css_users : $core_css_users;

			$child_css_icons = $base . 'font/fontello/css/wpcico.css';
			$core_css_icons = WPC_URL . 'assets/font/fontello/css/wpcico.css';
			$css_file_icons = file_exists( $_base . 'font/fontello/css/wpcico.css' ) ? $child_css_icons : $core_css_icons;

			$child_widgets_js = $base . 'js/widgets.js';
			$core_widgets_js = WPC_URL . 'assets/js/widgets.js';
			$widgets_js_file = file_exists( $_base . 'js/widgets.js' ) ? $child_widgets_js : $core_widgets_js;

			wp_enqueue_script( 'jquery' );
			wp_enqueue_style( 'wpc', $css_file );
			wp_enqueue_style( 'wpc-users', $css_file_users );
			wp_enqueue_style( 'wpc-icons', $css_file_icons );
			wp_enqueue_script( 'wpc-functions', $js_file, array(), WPC_VER, true );
			wp_enqueue_script( 'wpc-global', $glbl_js_file, array(), WPC_VER, true );
			wp_enqueue_script( 'wpc-widgets', $widgets_js_file, array(), WPC_VER, true );

			if ( ! empty( wpc_settings()->stats ) )
				wp_enqueue_script( 'chartjs', '//cdnjs.cloudflare.com/ajax/libs/Chart.js/2.1.3/Chart.min.js', array('jquery'), null );

			/*wp_localize_script( 'wpc-global', 'ajaxwpc_send', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' )
			));
			wp_localize_script( 'wpc-global', 'ajaxwpc_json', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' )
			));
			wp_localize_script( 'wpc-global', 'ajaxwpc', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' )
			));
			wp_localize_script( 'wpc-global', 'ajaxwpc_actions', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' )
			));
			wp_localize_script( 'wpc-global', 'ajaxwpc_title', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' )
			));
			wp_localize_script( 'wpc-global', 'ajaxwpc_time', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' )
			));
			wp_localize_script( 'wpc-global', 'ajaxwpc_upload', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' )
			));*/

		});

		// solving possible redirect issues
		add_action('init', function() {ob_start();});

		add_action( 'wp_footer', function() {

			$upload_action = admin_url( 'admin-ajax.php?action=wpc_upload&rdr=1' );
			$upload_action .= wpc_get_recipient() ? '&slug=' . wpc_get_recipient()->user_nicename : '';

			?>

				<div class="wpc-emo-container wpc-ec-main wpc-tooltip-cont" style="display: none;">
					<input placeholder="filter" />
					<?php foreach( wpc_emoji_list() as $emoticon ) : ?>
						<img src="<?php echo $emoticon->url; ?>" alt="<?php echo $emoticon->description; ?>" title="<?php echo str_replace( '"', '&quot;', $emoticon->symbol ); ?>" />
					<?php endforeach; ?>
				</div>
				
				<div class="wpc-img-cont main wpc-tooltip-cont" style="display: none;">
					<span><?php echo wpc_translate('add from URL'); ?></span>
					<form id="url" data-input="#_wpc_mform textarea">
						<input type="text" placeholder="<?php echo wpc_translate('image URL'); ?>" />
					</form>
				</div>


				<div class="wpcajx-unread-count" data-unread-count="" data-tar-unread-count="" style="display: none;"></div> 

				<?php echo _wpc_footer_js_object(); ?>

			<?php

		});

		add_action('wp_head', function() {
			echo '<style type="text/css" media="all">' . apply_filters('wpc_custom_css', '/* WpChats custom CSS */') . '</style>';
		});

		add_filter('wpc_custom_tab_content', 'do_shortcode');

	}

}

WPC_init::instance()->init();