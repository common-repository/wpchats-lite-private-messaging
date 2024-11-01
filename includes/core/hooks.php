<?php

/**
  *
  *
  */

class WPC_extend
{

	protected static $instance = null;

	public static function instance() {

		return null == self::$instance ? new self : self::$instance;

	}

	function __construct() {
		add_action('init', array( &$this, 'init' ));
	}

	public function init() {

		add_shortcode('wpc_loaded_template', function(){
			return wpc_loaded_template();
		});

		add_filter('the_content', function( $content ) {
			global $post;
			if( $post && ! is_feed() && wpc_settings()->page == $post->ID ) {
				$content = apply_filters( 'wpc_template_content', wpc_loaded_template() );
			}
			return $content;
		}, 999);

		add_filter('wp_title', function( $title ) {
			global $post;
			if( $post && wpc_settings()->page == $post->ID ) {
				$title = str_replace( wpc_settings()->page_title, wpc_title(), $title );
			}
			return $title;
		}, 999);

		add_filter('the_title', function( $title ) {
			global $post;
			if( $post && wpc_settings()->page == $post->ID ) {
				$title = str_replace( wpc_settings()->page_title, wpc_title(), $title );
			}
			return $title;
		}, 999);

		add_filter('wpc_conversation_no_messages_notice', function( $text ) {
			if( wpc_is_search_messages() ) {
				$text = wpc_translate('No message has matched your search query.');
			}
			elseif( ! wpc_get_conversation_id( wpc_get_recipient_id() ) ) {
				$text = wpc_translate('This conversation is empty. Send a message below.');
			}
			return  $text;
		});

		add_action('wpc_before_messages_list', function() {
			if( wpc_is_search_messages() ) {
				echo '<p><em>' . wpc_translate('Showing message results for') . ' "' . wpc_get_search_query() . '" :</em></p>';
			}
		});

		add_filter('wpc_get_conversation_array', function( $args ) {
			if( $q = wpc_get_search_query() && wpc_is_search_messages() ) {
				global $wpdb
				     , $current_user;
				$table = $wpdb->prefix . WPC_TABLE;
				$pm_id = ! empty( $args->last_message->PM_ID ) ? $args->last_message->PM_ID : 0; # !! vfy
				$query = $wpdb->get_results( "SELECT * FROM $table WHERE `PM_ID` = '$pm_id' AND NOT FIND_IN_SET('$current_user->ID', `deleted`) ORDER BY `ID` AND `message` LIKE '%$q%' DESC LIMIT 1" );
				if( ! empty( $query[0] ) && ! empty( $query[0]->PM_ID ) ) {
					$args->last_message = WPC_message::instance()->get_message( $query[0]->ID );
				} else {
					$args = false;
				}
			}
			return $args;
		});

		add_filter('wpc_no_messages_notice', function( $text ) {
			if( wpc_is_search_messages() ) {
				$text = wpc_translate('No conversations have matched your search query.');
			}
			$is_archives = wpc_is_archives();
			if( ! $is_archives ) {

				$base = wpc_messages_base();
				$bases = wpc_get_bases();

				if( false !== wpc_get_archives_list() ) {
				//if( wpc_get_counts()->archives > 0 ) {
					if( wpc_is_search_messages() ) {
						$text .= ' <a href="' . $base . 'archives/?q=' . wpc_get_search_query() . '" class="wpcajx2" data-task="' . wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1&wpc_archives=1&q=' . wpc_get_search_query(1)) . '"}') . '">' . wpc_translate('Search archives') . '</a>';
					} else {
						$text .= ' <a href="' . $base . 'archives/' . '" class="wpcajx2" data-task="' . wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1&wpc_archives=1') . '"}') . '">' . wpc_translate('View archives') . '</a>';
					}
					$text .= ' <a href="' . $base . 'new/' . '" class="wpcfmodal" data-task="' . wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1&wpc_new_message=1') . '", "onExitTitle": "' . wpc_title(1) . '", "onExitHref": "' . $base . $is_archives ? $bases->archives . '/' : '' . '", "onLoadHref": "' . $base . 'new/' . '"}') . '">' . wpc_translate('or compose new message') . '</a>';
				} else {
					if( ! wpc_is_search_messages() )
						$text .= '<div><a href="' . $base . 'new/' . '" class="wpcfmodal" data-task="' . wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1&wpc_new_message=1') . '", "onExitTitle": "' . wpc_title(1) . '", "onExitHref": "' . $base . ( $is_archives ? $bases->archives . '/' : '' ) . '", "onLoadHref": "' . $base . 'new/' . '"}') . '">' . wpc_translate('Compose new message') . '</a></div>';
				}
			} else {
				$text = wpc_translate('You have no archived conversations.');
			}
			if( isset( $_GET['view'] ) && 'unread' == (string) $_GET['view'] ) {
				$text = $is_archives ? wpc_translate('You have no unread archives.') : wpc_translate('You have no unread conversations.');
			}
			return $text;
		});

		add_action('wpc_after_messages_top_header', function() {
			if( wpc_is_search_messages() ) {
				echo '<p>' . wpc_translate('Showing message results for') . ' "' . wpc_get_search_query() . '" :</p>';
			}
		});
		// when editing someone else's profile as admin, make sure not to update their online status as we save
		add_filter('wpc_core_update_user_status_time_int', function( $time, $args) {
			$user_id = ! empty( $args['user'] ) ? (int) $args['user'] : 0;
			if( get_userdata( $user_id ) ) {
				if( wp_get_current_user()->ID !== $user_id ) {
					$time = wpc_get_user_data( $user_id )->last_seen;
				}
			}
			return $time;
		}, 10, 2);

		add_action('wpc_before_profile_edit', function() {
			WPC_users::instance()->update_profile();
		});

		add_action('wpc_pre_ajax_load', function() {
			
			$allow_update_status = apply_filters( 'wpc_allow_update_status', true );
			
			if( $allow_update_status ) {
				wpc_update_user_status();
			}

		});

		add_filter( 'wpc_no_users', function( $text ) {

			$is_search = wpc_is_search();
			$query = wpc_get_search_query();
			
			if( wpc_is_archive_users() ) {
				$text = wpc_translate('There are no users to show.');
				if( $is_search ) {
					$text = wpc_translate('No users have matched your search query') . ' "' . $query . '"';
				}
			}

			elseif( wpc_is_archive_blocked_users() ) {
				$text = wpc_translate('You have no blocked users.');
				if( $is_search ) {
					$text = wpc_translate('No blocked users have matched your search query') . ' "' . $query . '"';
				}
			}

			elseif( wpc_is_archive_online_users() ) {
				$text = wpc_translate('There are no users currently online.');
				if( $is_search ) {
					$text = wpc_translate('No online users have matched your search query') . ' "' . $query . '"';
				}
			}

			return $text;

		});

		add_action('wpc_before_conversation_form', function() {
			
			$pm_id = wpc_get_conversation_id();
			
			if ( isset( $_GET['media'] ) && wpc_is_single_message() && $pm_id ) {

				$autoSave = wpc_conversation_autosave();
				if( $autoSave > '' )
					$autoSave .= ' ';
				else
					$autoSave = '';

				$autoSave .= '[img]' . $_GET['media'] . '[/img]';

				update_option('_wpc_autosave_' . $pm_id . '_' . wp_get_current_user()->ID, esc_attr( $autoSave )  );

				wp_redirect( substr( $_SERVER['REQUEST_URI'], 0, strpos( $_SERVER['REQUEST_URI'], '&media' ) ) );
				exit;

			}

		});

		add_action('wpc_after_message_sent', function($args) {

			if ( ! get_userdata( $args['recipient'] ) || ! wpc_alerts_allowed( $args['recipient'], $args['pm_id'] ) )
				return;

			$array = array(
				'id' => $args['id'],
				'pm_id' => $args['pm_id']
			);
			
			wpc_user_unreads( $args['recipient'], $array );
			wpc_user_unreads_noajax( $args['recipient'], $array );

		});

		add_action('wpc_json_loaded', function( $data ) {

			if( ! is_user_logged_in() || ! wpc_is_admin_ajax() ) {
				return;
			}

			if( empty( $data['notifications']['unread'] ) ) {
				return;
			}

			$messages = array();
			foreach( $data['notifications']['unread'] as $message ) {
				$messages[] = array( 'id' => $message['message_id'] );
			}

			$bail = apply_filters('wpc_trash_read_notifications_bail', false, $messages);

			if( ! $bail ) {
				wpc_user_unreads( wp_get_current_user()->ID, array(), false, $messages );
			}

		}, 10);

		add_action('wpc_before_loop_users', function() {
			if( wpc_is_search() && count( wpc_get_users() ) > 0 ) {
				echo '<p style="margin-top: 5px;">' . wpc_translate('Showing search results for') . ' "' . wpc_get_search_query() . '":</p>';
			}
		});

		add_filter('wpc_single_message_classes', function( $classes, $message ) {

			$content = wpc_output_message($message->message);

			if( is_numeric( strpos( $content, '<img src' ) ) || is_numeric( strpos( $content, '<a href' ) ) || is_numeric( strpos( $content, '<iframe src' ) ) )
			{ 
				$classes .= ' has-media';
			}

			if( is_numeric( strpos( $content, '<img src' ) ))
			{ 
				$classes .= ' has-attachement';
			}

			if( is_numeric( strpos( $content, '<iframe src' ) ))
			{ 
				$classes .= ' has-video';
			}

			if( is_numeric( strpos( $content, '<a href' ) ))
			{ 
				$classes .= ' has-link';
			}

			return $classes;

		}, 10, 2);

		add_action('wpc_before_template_load', function() {
			
			if( isset( $_POST['wpc_fwd_message'] ) ) {

				$message_id = isset( $_POST['message_id'] ) ? (int) $_POST['message_id'] : 0;
				$recipient_id = isset( $_POST['user_id'] ) ? (int) $_POST['user_id'] : 0;

				// verify nonce first
				if( !wpc_validate_nonce() )
					return;

				if( ! $message_id || ! $recipient_id ) {
					echo '<p>' . wpc_translate('Error occured, please try again.') . '</p>';
					return;
				}

				$message = wpc_get_message( $message_id );

				if( ! isset( $message->ID ) || ! isset( $message->PM_ID ) ) {
					echo '<p>' . wpc_translate('Sorry, you can not forward this message to this user.') . '</p>';
					return;
				}

				if( ! wpc_is_part_of_pm( $message->PM_ID, wp_get_current_user()->ID ) ) {
					echo '<p>' . wpc_translate('Sorry, you can not forward this message to this user.') . '</p>';
					return;
				}

				$args = array(
					'success_redirect_to' => wpc_get_conversation_permalink() . '?done=fwd',
					'bypassNonce' => true 
				);

				WPC_message::instance()->send( $recipient_id, $message->message, false, $args );

			}

			return;

		});

		add_action('wpc_fwd_message_template', function() {

			$message = wpc_message_to_forward();
			$get_recipient = wpc_get_recipient();
			$conversation_permalink = wpc_get_conversation_permalink();
			global $current_user;

			ob_start(); ?>

				<p><?php echo wpc_translate('Sorry, you can not forward this message'); ?></p>
				<p><a href="<?php echo $conversation_permalink; ?>" class="wpcajx" data-action="load-conversation" data-slug="<?php echo $get_recipient->user_nicename; ?>">&laquo; <?php echo wpc_translate('back to messages'); ?></a></p>

			<?php $err_inner = ob_get_clean();

			if( ! isset( $message->ID ) || ! isset( $message->PM_ID ) ) {
				echo $err_inner;
				return;
			}

			if( ! wpc_is_part_of_pm( $message->PM_ID, $current_user->ID ) ) {
				echo $err_inner;
				return;
			}

			?>
				<div class="wpc-fwd m-<?php echo $message->ID; ?>">
				<form action="<?php echo $conversation_permalink . ('forward/' . $message->ID . '/'); ?>" method="get" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"action": "' . admin_url('admin-ajax.php?action=wpc_actions&do=fwd&wpc_messages=1&wpc_recipient=' . $get_recipient->user_nicename . '&wpc_forward_message=' . $message->ID) . '", "loadInto": ".wpc-fwd.m-' . $message->ID . '"}'); ?>">
					<input type="text" placeholder="<?php echo wpc_translate('Search users'); ?>" name="q" value="<?php echo str_replace( '"', '&quot;', wpc_get_search_query() ); ?>" autocomplete="off" />
				</form>

			<?php

			$query = stripslashes( wpc_get_search_query() );
			$users = _wpc_get_users();

			$exclude = array();
			foreach( $users as $i => $user ) {
				if( ! wpc_can_contact( $user->ID ) )
					$exclude[] = $user->ID;
			}
			$exclude[] = $message->contact;
			$exclude[] = $current_user->ID;

			//$exclude = array();

			if( $query > '' ) {
				$users = WPC_users::instance()->search( $query, '', $users, $exclude );
			} else {
				$users = WPC_users::instance()->last_contacts(10, $exclude);
			}

			if( ! empty( $users ) ) {

				foreach( $users as $user ) : ?>

					<div class="wpc-md-qu-ico wpc-fwd-u-ico" title="<?php echo wpc_translate('Forward message to this user'); ?>" onclick="!confirm(wpc.conf.fwd)||this.childNodes[1].submit()" data-jq-onclick="!confirm(wpc.conf.fwd)||jQuery('form',this).trigger('submit')">

						<form method="post" style="display: none;" class="wpcajx" data-action="fwd-message">
							<input type="hidden" name="wpc_fwd_message" value="1" />
							<input type="hidden" name="message_id" value="<?php echo $message->ID; ?>" />
							<input type="hidden" name="user_id" value="<?php echo $user->ID; ?>" />
							<input type="hidden" name="user_slug" value="<?php echo $user->user_nicename; ?>" />
							<?php wp_nonce_field('_wpc_nonce', '_wpc_nonce'); ?>
						</form>
						<?php echo get_avatar( $user->ID, apply_filters('wpc_modal_search_users_avatar_size', 33) ); ?>
						<?php echo wpc_get_user_name( $user->ID ); ?>
						<span class="wpc-fwd-select"><?php echo wpc_translate('select'); ?></span>
					</div>

				<?php endforeach;

			} else {
				echo apply_filters('wpc_fwd_message_no_users_found_inner', '<p>' . wpc_translate('No users have matched your search query, please try again.') . '</p>');
			}

			wpc_data_title();

			echo '</div><!-- /.wpc-fwd -->';

		});

		add_filter('wpc_sidebar_contact_icon_classes', function($classes, $user, $unread_count) {

			$classes .= ' usr-' . $user->ID;
			//if( (int) $unread_count > 0 ) { $classes .= ' wpc-shake1'; }
			if( wpc_get_recipient_id() == $user->ID ) { $classes .= ' current'; }

			return $classes;

		}, 10, 3);

		add_action('wpc_client_feedback_html', function( $html ) {

			$done = isset( $_GET['done'] ) ? (string) $_GET['done'] : false;
			$message = null;
			$class = false;

			switch( $done ) :;

				case 'fwd':
					$message = wpc_translate('Message forwarded successfully');
					break;

				case 'remove-mod':
					$message = wpc_translate('Moderator removed successfully');
					break;

				case 'err-remove-mod':
					$message = wpc_translate('Error occured while removing moderator');
					$class = 'err';
					break;

				case 'add-mod':
					$message = wpc_translate('Moderator added successfully');
					break;

				case 'm-report':
					$message = wpc_translate('Message flagged successfully');
					break;

				case 'm-report-delete':
				case 'report-delete':
					$message = wpc_translate('Report deleted successfully');
					break;

				case 'mute':
				case 'unmute':
					$message = wpc_translate('Conversation muted/unmuted successfully');
					break;

				case 'unread':
					$message = wpc_translate('Conversation marked unread successfully.');
					break;

				case 'block':
					$message = wpc_translate('User blocked successfully.');
					break;

				case 'unblock':
					$message = wpc_translate('User unblocked successfully.');
					break;

				case 'archive':
					$message = wpc_translate('Conversation archived successfully.');
					break;

				case 'unarchive':
					$message = wpc_translate('Conversation unarchived successfully.');
					break;

				case 'delete':
					if( wpc_is_single_message() ) {
						$message = wpc_translate('Message deleted successfully.');
					} else {
						$message = wpc_translate('Conversation deleted successfully.');
					}
					break;

				case 'm-delete':
					$message = wpc_translate('Message deleted successfully.');
					break;

				case 'c-delete':
					$message = wpc_translate('Conversation deleted successfully.');
					break;

				case 'err-block':
					$message = wpc_translate('Error while blocking or unblocking this user. Please try again.');
					$class = 'err';
					break;

				case 'err-delete':
					$message = wpc_translate('Error deleting message(s). Please try again.');
					$class = 'err';
					break;

				case 'err-sending':
					$message = wpc_translate('Error sending message. Please try again.');
					$class = 'err';
					break;

				case 'err-unread':
					$message = wpc_translate('Error marking conversation unread.');
					$class = 'err';
					break;

				case 'ban':
					$message = wpc_translate('User banned successfully.');
					break;

				case 'unban':
					$message = wpc_translate('User unbanned successfully.');
					break;

				case 'err-ban':
				case 'err-unban':
					$message = wpc_translate('Error occured while banning/unbanning user.');
					$class = 'err';
					break;

				case 'edit-profile':
					$message = wpc_translate('profile updated successfully.');
					break;

			endswitch;

			$done = apply_filters('wpc_feedback_done', array(
				'message' => $message,
				'class' => $class
			), $done);

			if( isset( $done['message'] ) ) {
				ob_start();
				?>
					<div class="wpc-feedback" class="<?php echo $class; ?>">
						<p><?php echo $done['message']; ?></p>
						<span>x</span>
					</div>
				<?php
				$html = ob_get_clean();
			}

			return $html;

		});

		add_action('wpc_pre_ajax_load', function() {

			if( wpc_is_moderation() && ! wpc_is_mod() ) {
				exit('0');
			}

		});

		add_action('wpc_before_template_load', function() {
			$is_single = wpc_is_single_conversation();
			$is_ajax = wpc_is_admin_ajax();
			if( $is_single && $is_ajax && isset( $_REQUEST['do'] ) ) {
				if( "unread" == strval( $_REQUEST['do'] ) ) {
					if( wpc_can_mark_unread() ) {
						echo WPC_message::instance()->mark_read( 0, 1 );
						exit;
					} else {
						exit('0');
					}
				}
				elseif( "archive" == strval( $_REQUEST['do'] ) ) {
					echo WPC_message::instance()->archive( 0 );
					exit;
				}
				elseif( "unarchive" == strval( $_REQUEST['do'] ) ) {
					echo WPC_message::instance()->archive( 0, 1 );
					exit;
				}
			}

			if( $is_single && wpc_is_mute_conversation() ) {

				if( ! isset( $_REQUEST['wpc_do_mute_conversation'] ) ) {
					return;
				}

				$pm_id = wpc_get_conversation_id();

				if( ! wpc_validate_nonce() || ! $pm_id ) {
					if( $is_ajax ) exit('0');
					else return;
				}

				$duration = isset( $_REQUEST['wpc_duration'] ) ? sanitize_text_field( $_REQUEST['wpc_duration'] ) : false;

				$values = array( '30m', '4h', '24h', '7d', '1m', '1y', '0' );

				if( ! in_array($duration, $values) ) {
					if( $is_ajax ) exit('0');
					else return;
				}

				$unmute = "0" == $duration;
				global $current_user;

				if( $unmute )
					delete_user_meta( $current_user->ID, "wpc_mute_$pm_id" );
				else
					update_user_meta( $current_user->ID, "wpc_mute_$pm_id", $duration . '@' . time() );

				if( $is_ajax ) {
					exit('1');
				} else {
					wp_redirect( wpc_get_conversation_permalink( '?done=' . ( $unmute ? 'unmute' : 'mute' ) ) );
					exit;
				}

			}

		});

		add_action('wp', function() {

			if( ! wpc_current_user_can('watch_conversations') || ! isset( $_GET['do'] ) )
				return;

			$task = strval( $_GET['do'] );
			$url = substr( $_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '?do') );

			switch( $task ) {

				case '_m-delete':
					
					WPC_message::instance()->mod_actions( 'm-delete', (int) @$_GET['m'] );
					wp_redirect( $url . '?done=m-delete' );
					exit;

					break;

				case '_c-delete':
					
					WPC_message::instance()->mod_actions( 'c-delete', (int) @$_GET['c'] );
					wp_redirect( $url . '?done=c-delete' );
					exit;

					break;

				case '_ban':
					WPC_users::instance()->actions( 'ban', (int) @$_GET['user'] );
					wp_redirect( $url . '?done=ban' );
					exit;
					break;

				case '_unban':
					WPC_users::instance()->actions( 'unban', (int) @$_GET['user'] );
					wp_redirect( $url . '?done=unban' );
					exit;
					break;

			}

		});

		add_action('wpc_mod_watch_messages', function() {

			if ( ! wpc_current_user_can( "watch_conversations" ) ) return;

			$user_a = get_query_var('wpc_mod_user_a', wpc_get_query_var('wpc_mod_user_a', '')); 
			$user_b = get_query_var('wpc_mod_user_b', wpc_get_query_var('wpc_mod_user_b', ''));

			$user_a = get_user_by('slug', $user_a);
			$user_b = get_user_by('slug', $user_b);
			$user_links = wpc_get_user_links();

			if( ! wpc_current_user_can('watch_conversations') || ! $user_a || ! $user_b ) {
				echo '<p>' . wpc_translate('Something went wrong..') . '</p>';
				echo '<p><a href="' . $user_links->mod . '" class="wpcajx2" data-task="' . wpc_quote_url('{"loadURL": "' . admin_url("admin-ajax.php?action=wpc&wpc_mod=1") . '"}') . '">&lsaquo; ' . wpc_translate('Back to moderation panel') . '</a></p>';
				return;
			}

			$meta = get_option( 'wpc_pm_id_' . $user_a->ID . '_' . $user_b->ID );
			$meta2 = get_option( 'wpc_pm_id_' . $user_b->ID . '_' . $user_a->ID );

			if( (int) $meta > 0 ) {
				$pm_id = (int) $meta;
			} elseif( (int) $meta2 > 0 ) {
				$pm_id = (int) $meta2;
			} else {
				$pm_id = 0;
			}

			if( ! $pm_id ) {
				echo '<p>' . wpc_translate('Something went wrong..') . '</p>';
				echo '<p><a href="' . $user_links->mod . '" class="wpcajx2" data-task="' . wpc_quote_url('{"loadURL": "' . admin_url("admin-ajax.php?action=wpc&wpc_mod=1") . '"}') . '">&lsaquo; ' . wpc_translate('Back to moderation panel') . '</a></p>';
				return;
			}

			global $wpdb;
			$table = $wpdb->prefix . WPC_TABLE;
			$offset = isset( $_REQUEST['offset'] ) ? (int) $_REQUEST['offset'] : 0;
			if( (int) $offset < 1 ) $offset = 10;
			$start = 0;
			$curr = isset( $_GET['_page'] ) ? (int) $_GET['_page'] : 0;
			if( (int) $curr < 1 ) { $curr = 1; }
			if( 1 == (int) $curr ) { $start = 0; }
			elseif ( (int) $curr > 0 ) { $start = abs( ( $curr * $offset ) - $offset ); }
			$curOff = abs( $curr * $offset );$stmt = "SELECT * FROM $table WHERE `PM_ID` = '$pm_id' ORDER BY `ID` DESC LIMIT $start,$offset";
			$total = count( $wpdb->get_results("SELECT `ID` FROM $table WHERE `PM_ID` = '$pm_id'") );
			$maxP = abs( $total / $offset );
			if( is_float( $maxP ) ) { $maxP = (int) abs( $maxP + 1 ); }
			if( wpc_get_search_query() > '' ) {
				$q = wpc_get_search_query();
				$stmt = "SELECT * FROM $table WHERE `PM_ID` = '$pm_id' AND `message` LIKE '%$q%' ORDER BY `ID` DESC LIMIT $start,$offset";
			}
			$messages = $wpdb->get_results($stmt);
			if( empty( $messages ) ) {
				if( wpc_is_search() ) {
					echo '<p>' . wpc_translate('No messages have matched your search query') . '</p>';
					echo '<a href="' . wpc_mod_watch_conversation_permalink() . '" class="wpcajx2" data-task="' . wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_mod=1&wpc_mod_messages=1&wpc_mod_user_a=' . $user_a->user_nicename . '&wpc_mod_user_b=' . $user_b->user_nicename) . '"}') . '">&lsaquo; ' .  wpc_translate('back') .'</a>';

				}
				else {
					echo '<p>' . wpc_translate('No messages found in this conversation') . '</p>';
					echo '<p><a href="' . $user_links->mod . '" class="wpcajx2" data-task="' . wpc_quote_url('{"loadURL": "' . admin_url("admin-ajax.php?action=wpc&wpc_mod=1") . '"}') . '">&lsaquo; ' . wpc_translate('Back to moderation panel') . '</a></p>';
				}
				return;
			}
			?>

			<div>
				<form action="<?php echo wpc_mod_watch_conversation_permalink(); ?>" method="get" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"action": "' . admin_url('admin-ajax.php?action=wpc&wpc_mod=1&wpc_mod_messages=1&wpc_mod_user_a=' . $user_a->user_nicename . '&wpc_mod_user_b=' . $user_b->user_nicename) . '", "noHistory": "1"}'); ?>">
					<input type="text" placeholder="<?php echo wpc_translate('search messages'); ?>" name="q" value="<?php echo wpc_get_search_query(1); ?>" />
					<input type="submit" value="<?php echo wpc_translate('search'); ?>" />
				</form>
			</div>

			<?php if( wpc_is_search() ) : ?>
				<p><?php echo str_replace('[query]', wpc_get_search_query(), wpc_translate('Search results for "[query]"')); ?></p>
			<?php endif; ?>

			<?php foreach( $messages as $m ) :

				$class = $m->sender == $user_a->ID ? 'user_a' : 'user_b';
				include wpc_template_path( 'moderation/watch-single-message' );

			endforeach;

			if( abs( $total * $offset ) > $curOff ) {

				?>

					<div class="pi">
						<?php if( abs( $curr - 1 ) ) : ?><a href="?_page=<?php echo abs( $curr - 1 ) . ( wpc_get_search_query() > '' ? '&q=' . wpc_get_search_query(1) : '' ); ?>" class="prv">&laquo; Previous</a><?php endif; ?>
						<?php if( abs( $curr + 1 ) <= $maxP ) : ?><a href="?_page=<?php echo abs( $curr + 1 ) . ( wpc_get_search_query() > '' ? '&q=' . wpc_get_search_query(1) : '' ); ?>" class="nxt">Next &raquo;</a><?php endif; ?>
					</div>

				<?php

			}

			?>

			<p style="margin-top: 1.5em;">
				<a href="<?php echo $user_links->mod; ?>" class="wpc-btn wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url("admin-ajax.php?action=wpc&wpc_mod=1") . '"}'); ?>">&lsaquo; <?php echo wpc_translate('Back to moderation panel'); ?></a>
				<a href="<?php echo $user_links->mod; ?>moderators/" class="wpc-btn wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_mod=1&wpc_mods=1') . '"}'); ?>"><?php echo wpc_translate('Moderators'); ?></a>
				<a href="<?php echo $user_links->users->all; ?>" class="wpc-btn wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_users=1') . '"}'); ?>"><?php echo wpc_translate('Browse users'); ?></a>
				<a href="<?php echo $user_links->messages; ?>" class="wpc-btn wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1') . '"}'); ?>"><?php echo wpc_translate('View messages'); ?></a>
			</p>

			<?php

		});

		add_filter('wpc_custom_css', function( $css ) {

			if( ! wpc_current_user_can('report_messages') )
				$css .= "\n" . '.wpc .message-meta a[href*="/report/"], .wpc .wpc-top-header a[href*="/report/"] {display: none!important}';

			if( wpc_is_mod() )
				$css .= "\n" . '.wpc_mod_wc .av,.wpc_mod_wc .mc,.wpc_mod_wc form input[type=submit]{display:inline-block}.wpc_mod_wc>div{position:relative;border:1px solid #ddd;padding:1em;margin-bottom:4px;clear:both;overflow:hidden}.wpc_mod_wc .user_b .av{float:right}.wpc_mod_wc .user_b .mc{float:left}.wpc_mod_wc .av{max-width:30%;clear:both;text-align:center}.wpc_mod_wc .mc{max-width:70%;border:1px solid #F3F3F3;padding:1em;float:right;background:#FDFDFD;text-align:left}.wpc_mod_wc .user_b .av img{float:right}.wpc_mod_wc .av img{border-radius:100%}.wpc_mod_wc .meta{border-top:1px solid #E6E6E6;padding-top:6px}.wpc_mod_wc .pi{border:1px solid #ddd;background:#F9F9F9}.wpc_mod_wc .pi a{color:#555}.wpc_mod_wc .pi .prv{float:left}.wpc_mod_wc .pi .nxt{float:right}.wpc_mod_wc form input[type=text]{max-width:50%;display:inline-block}span.idn{position:absolute;bottom:0;right:3px}';

			return $css;

		});

		/* code re-verified until here, coffee run out :/ */

		add_action('wpc_compose_message_template_get_users', function() {

			$query = wpc_get_search_query();
			$exclude = array();
			$users = _wpc_get_users();
			foreach( $users as $i => $user ) {
				if( ! wpc_can_contact( $user->ID ) ) {
					$exclude[] = $user->ID;
				}
			}

			if( $query > '' ) {
				$users = WPC_users::instance()->search( $query, '', $users, $exclude );
			} else {
				$users = WPC_users::instance()->last_contacts( 10, $exclude );
			}

			if( ! empty( $users ) ) {

				foreach( $users as $user ) :;
					$checked = isset( $_REQUEST['_recipient'] ) && $user->ID == (int) $_REQUEST['_recipient'];
				?>

					<label class="wpc-label<?php if($checked){echo' active';} ?>">
						<input type="radio" onchange="wpcCheckParentLabel(this)" name="recipient" value="<?php echo $user->ID; ?>" <?php checked( $checked ); ?>/>
						<?php echo get_avatar( $user->ID, apply_filters('wpc_modal_search_users_avatar_size', 33) ); ?>
						<?php echo wpc_get_user_name( $user->ID ); ?>
					</label>

				<?php endforeach;

			}

			?>
				<?php if( isset( $_REQUEST['message'] ) ) : ?>
					<input type="hidden" name="message" value="<?php echo _wpc_str($_REQUEST['message']); ?>" />
				<?php endif; ?>
				<input type="hidden" name="next" value="1" />
				<input type="submit" name="next" value="<?php echo wpc_translate('next'); ?> &rsaquo;" style="float:right;margin-top:1em" />

			<?php

		});

		add_action('wpc_compose_message_template_write_message', function() {

			$message = isset( $_REQUEST['message'] ) ? sanitize_text_field( $_REQUEST['message'] ) : '';
			$message = stripslashes( $message );
			$recipient = (int) $_REQUEST['recipient'];

			if( ! $message ) {
				$message = wpc_conversation_autosave( wpc_get_conversation_id( $recipient ) );
			}

			?>
				<p><strong><?php echo wpc_translate('Recipient'); ?>:</strong></p>
				<div class="rcpnt">
					<?php echo get_avatar( $recipient, apply_filters('wpc_modal_search_users_avatar_size', 33) ); ?>
					<?php echo wpc_get_user_name( $recipient ); ?>
					<em style="float:right"><?php echo wpc_get_user_activity( $recipient ); ?></em>
				</div>
				<p><strong><?php echo wpc_translate('Message body'); ?>:</strong></p>
				<textarea name="message" role="wpcFocus" placeholder="<?php echo wpc_translate('Write message..'); ?>" style="width:100%" rows="5" onchange="if(window.jQuery)jQuery('#wpcbr textarea').val(jQuery(this).val())"><?php echo nl2br($message); ?></textarea>
				<input type="hidden" name="recipient" value="<?php echo $recipient; ?>" />
				<?php wp_nonce_field('_wpc_nonce', '_wpc_nonce'); ?>
				<input type="submit" name="send" value="<?php echo wpc_translate('send now'); ?> &rsaquo;" style="float:right;margin-top:1em" />
				<?php if( ! isset( $_REQUEST['_no_back'] ) ) : ?>
				<input type="submit" onclick="if(window.jQuery){jQuery('#wpcbr textarea').trigger('submit')}else{document.getElementById('#wpcbr').submit()}return false;" value="&lsaquo; <?php echo wpc_translate('back (select recipient)'); ?>" style="float:left;margin-top:1em" />
				<?php endif; ?>

			<?php

		});

		add_action('wpc_before_template_load', function() {

			if( ! wpc_is_new_message() )
				return;

			if( isset( $_REQUEST['next'] ) && ! isset( $_REQUEST['recipient'] ) ) {
				add_filter('wpc_client_feedback_html', function() {
					return '<div class="wpc-feedback err"><p>' . wpc_translate('Please select a recipient!') . '</p><span>x</span></div>';
				});
				return;
			}
			
			$recipient = isset( $_REQUEST['recipient'] ) ? (int) $_REQUEST['recipient'] : 0;

			if( isset( $_REQUEST['send'] ) && ! wpc_can_contact( $recipient ) ) {
				add_filter('wpc_client_feedback_html', function() {
					return '<div class="wpc-feedback err"><p>' . wpc_translate('Please select a recipient!') . '</p><span>x</span></div>';
				});
				return;
			}

			$message = isset( $_REQUEST['message'] ) ? WPC_message::format( $_REQUEST['message'] ) : '';

			if( isset( $_REQUEST['send'] ) && strlen( preg_replace('/\s+/', '', $message) ) < apply_filters( 'wpc_min_message_lenght', 2 ) ) {
				add_filter('wpc_client_feedback_html', function() {
					return '<div class="wpc-feedback err"><p>' . wpc_translate('Please type a message!') . '</p><span>x</span></div>';
				});
				return;
			}

			$ins = WPC_message::instance()->send( $recipient, $message, 1 );

			if( (int) $ins > 0 ) {
				if ( ! wpc_is_admin_ajax() ) { wp_redirect( wpc_messages_base('new/?done=_&id=' . $ins) ); exit; }
				else { echo $ins; exit; }
			}

			return $ins;

		});

		add_action('wpc_compose_message_template_head', function( $id ) {

			$m = wpc_get_message( $id );

			if( empty( $m ) ) {
				if ( ! wpc_is_admin_ajax() ) { wp_redirect( wpc_messages_base('new/') ); exit; }
				else { return '0'; exit; }
			}

			?>

			<div class="wpc-compose success">

				<strong><?php echo str_replace( '[name]', wpc_get_user_name( $m->recipient, 1 ),  wpc_translate('Your message to [name] was successfully sent!')); ?></strong>

				<p>
					<li>
						<a href="<?php echo wpc_get_conversation_permalink( '', $m->recipient ); ?>" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1&wpc_recipient=' . $m->recipient_name) . '"}'); ?>">
							<?php echo wpc_translate('View this message'); ?>
						</a>
					</li>
					
					<li>
						<a href="<?php echo wpc_get_user_links( $m->recipient )->profile; ?>" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_users=1&wpc_user=' . $m->recipient_name) . '"}'); ?>">
							<?php echo str_replace( '[name]', wpc_get_user_name( $m->recipient, 1 ),  wpc_translate('View [name]\'s profile')); ?>
						</a>
					</li>

					<?php if( wpc_is_modal() ) : ?>

						<li>
							<a href="<?php echo wpc_messages_base( 'new/' ); ?>" class="wpcfmodal" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1&wpc_new_message=1') . '", "onExitTitle": "' . wpc_title(1) . '", "onExitHref": "' . wpc_messages_base( wpc_is_archives() ? wpc_get_bases()->archives . '/' : '' ) . '", "onLoadHref": "' . wpc_messages_base('new/') . '"}'); ?>">
								<?php echo wpc_translate('Compose another message'); ?>
							</a>
						</li>

					<?php else : ?>

						<li>
							<a href="<?php echo wpc_messages_base( 'new/' ); ?>" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1&wpc_new_message=1') . '"}'); ?>">
								<?php echo wpc_translate('Compose another message'); ?>
							</a>
						</li>

					<?php endif; ?>
				</p>

				<?php if( wpc_is_modal() ) : ?>
					<button class="wm-close" style="float:right"><?php echo wpc_translate('done'); ?></button>
				<?php endif; ?>

			</div>

			<?php

		});

		add_action('wpc_post_send_message', function( $args ) {
			if ( empty( wpc_settings()->notifications ) ) return;
			WPC_notifications::new_message_notify( $args['recipient'], $args['PM_ID'], $args['sender'] );
		});

		/*add_action('init', function() {

		//add_action('wpc_post_mark_read', function( $data ) {

			$pm_id = wpc_get_conversation_id();
			if( ! $pm_id ) { return; }

			/*if( is_array( $data ) ) {
				foreach( $data as $id ) {
					$m = wpc_get_message( $id );
					$pm_id = ! empty( $m->PM_ID ) ? $m->PM_ID : 0;
					if( ! empty( $pm_id ) ) { break; }
				}
			}*//*

			$user = wp_get_current_user();
			$meta_name = '_wpc_unrd_notif_' . $user->ID . '_' . $pm_id;
			$meta = get_option( $meta_name );

			if( is_array( $meta ) ) {
				foreach ( $meta as $key ) { WPC_notifications::delete( $key, $user->ID ); }
				delete_option( $meta_name );
			}

			return;

		}, 10, 1);*/

		add_action('wpc_update_user_profile', function( $user_id, $request ) {

			if( ! isset( $request['_wpc_updating_notifications'] ) ) {
				return; // let's mind our tab :D
			}

			$notification = $request['notifications'];
			$nf_settings = array();
			$nf_settings['site'] = array();
			$nf_settings['mail'] = array();
			$nf_settings['site']['messages'] = isset( $notification['site']['messages'] );
			$nf_settings['site']['moderation'] = isset( $notification['site']['moderation'] );
			$nf_settings['mail']['messages'] = isset( $notification['email']['messages'] );
			$nf_settings['mail']['moderation'] = isset( $notification['email']['moderation'] );
			$nf_settings['mail']['email'] = isset( $notification['email']['email'] ) ? sanitize_text_field( $notification['email']['email'] ) : false;

			if( wpc_is_mod() ) {
				$setting = isset( $notification['email']['mod_mail'] ) ? sanitize_text_field( $notification['email']['mod_mail'] ) : false;
				if( $setting > '' ) {
					$nf_settings['mail']['mod_mail'] = in_array( $setting, array( 'nothing', 'instant', 'summary' ) ) ? $setting : 'instant';
				} else { $nf_settings['mail']['mod_mail'] = 'instant'; }
			}

			update_user_meta( $user_id, '_wpc_notification_settings', json_encode( $nf_settings ) );
			return;

		}, 10, 2);

		add_filter('wpc_title', function($title) {
			if( ! wpc_is_custom_profile_tab() ) { return $title; }
			$tabs = wpc_registered_profile_tabs();
			foreach( $tabs as $tab ) {
				if( isset( $tab['title'] ) && $tab['name'] == wpc_profile_custom_tab_name() ) {
					$title = wpc_get_user_name( wpc_get_recipient_id() ) . ' &rsaquo; ' . $tab['title'];
				}
			}
			return $title;
		});

		

		add_filter('wp_nav_menu_items', function( $items, $args ) {

			$settings = wpc_settings()->nav_menu;

			if( in_array( $args->theme_location, $settings->locations) ) {
 
 				global $wpc_current_user_stats;
				$stats = $wpc_current_user_stats;

				if( ! empty( $settings->users->enable ) ):;

				ob_start();

				?>

					<li class="menu-item menu-item-wpc-users<?php if(wpc_is_users()) echo ' current-menu-item current_page_item';?>"><a href="<?php echo wpc_get_user_links()->users->all; ?>" class="wpcajx2" data-task="{&quot;loadURL&quot;: &quot;<?php echo admin_url('admin-ajax.php?action=wpc&wpc_users=1');?>&quot;}">
						<?php echo $settings->users->inner; ?>
					</a></li>

				<?php

				$items .= ob_get_clean();

				endif;

				if( is_user_logged_in() && ! empty( $settings->messages->enable ) ) :;

				ob_start();

				?>

					<li class="menu-item menu-item-wpc-messages<?php if(wpc_is_messages()) echo ' current-menu-item current_page_item';?>"><a href="<?php echo wpc_get_user_links()->messages; ?>" class="wpcajx2" data-task="{&quot;loadURL&quot;: &quot;<?php echo admin_url('admin-ajax.php?action=wpc&wpc_messages=1');?>&quot;}">
						<?php echo $settings->messages->inner; ?>
						<?php if( isset( $stats->unread_conversations, $stats->unread_conversations->count ) && $stats->unread_conversations->count > 0 ) : ?>
							(<?php echo $stats->unread_conversations->count; ?>)
						<?php endif; ?>
					</a></li>

				<?php

				$items .= ob_get_clean();

				endif;

			}

			return $items;
		 
		}, 10, 2);

		add_action('wpc_post_mark_read', function( $data ) {
			$user_id = wp_get_current_user()->ID;
			$mid = $pm_id = 0;
			if( is_array( $data ) ) {
				$mark_read = array();
				foreach( $data as $id ) {
					$mark_read[] = array( 'id' => $id );
					if( ! $pm_id ) {
						$pm_id = /*false !== */$message_data = wpc_get_message( $id )->PM_ID ? $message_data->PM_ID : 0;
					} else continue;
				}
			} else {
				$mark_read = array( array( 'id' => 0, 'pm_id' => $data ) );
			}
			wpc_user_unreads( $user_id, array(), false, $mark_read );
			wpc_user_unreads_noajax( $user_id, array(), false, array( array( 'pm_id' => $pm_id ) ) );
		}, 10);


		add_action('wpc_post_mark_unread', function( $ID, $pm_id ) {
			$user_id = wp_get_current_user()->ID;
			wpc_user_unreads_noajax( $user_id, array( array( 'id' => $ID, 'pm_id' => $pm_id ) ) );
		}, 10, 2);

		add_filter('wpc_messages_pagination', function( $html ) { 
			if( ! wpc_get_conversation_id( wpc_get_recipient_id() ) ) {
				return;
			}
			return $html;
		});

		add_action('wpc_pre_load_profile_notifications', function( $user ) {

			$exit = ! is_user_logged_in() || ! $user;

			if( ! $exit ) {
				$exit = wp_get_current_user()->ID !== $user->ID;
			}

			if( $exit ) {
				$exit = ! wpc_current_user_can('edit_notifications');
			}

			if( $exit ) {
				if( wpc_is_admin_ajax() ) {
					echo '0';
					exit;
				} else {
					wp_redirect( wpc_get_user_links( wpc_get_displayed_user()->ID )->profile );
					exit;
				}
			}

			return;

		});

		add_filter( 'wpc_users_classes', function( $classes ) {
			$user_id = wpc_get_displayed_user_id();
			if( $user_id ) {
				$classes .= wpc_user_has_cover( $user_id ) ? ' has-cover' : ' no-cover';
				$classes .= wpc_is_blocked( $user_id ) ? ' blocked' : '';
				$classes .= wpc_is_banned( $user_id ) ? ' banned' : '';
			}
			return $classes;
		});

		add_action('wpc_post_send_message', function( $args ) {
			wpc_user_unreads_noajax( $args['sender'], array(), 0, array( 'pm_id' => $args['PM_ID'] ) );
		});

		add_filter("wpc_profile_tabs_list", function( $list ) { 
			foreach( $list as $i => $item ) {
				if( ! empty( $item["unique"] ) && "edit" == $item["unique"] && ! wpc_current_user_can( "edit_own_profile" ) ) {
					unset( $list[$i] );
					break;
				}
			}
			return $list;
		});

		add_action('wpc_post_send_message', function( $args ) {
			$preferences = wpc_notification_settings( $args['recipient'] );
			if( ! $preferences->mail->messages ) {
				return; // notifications off by user.
			}
			if( ! wpc_mail_is_online( $args['recipient'] ) ) {
				WPC_mailing::instance()->unreads( $args['recipient'], array( 'pm_id'=> $args['PM_ID'], 'id' => $args['message_id'] ) );
				wpc_add_stats( array( 'name' => 'email_notifications', 'user_id' => $args['recipient'], 'add_to_main' => true ) );
			}
			return;
		});


		add_filter("wpc_email_content", function( $email ) { 
			$settings = wpc_mailing_settings();
			if( empty( $settings ) || empty( $settings->html ) ) {
				return $email;
			}
			$email = str_replace(
				array( "&amp;apos;", "&apos;", "&amp;quot;", "&quot;" ),
				array( "'", "'", "\"", "\"" ),
				$email
			);
			$email = wpautop( html_entity_decode( $email ) );
			return $email;
		});

		add_filter("WPC_mailing_format_message", function( $message, $original ) { 
			$settings = wpc_mailing_settings();
			if( empty( $settings ) || empty( $settings->html ) ) {
				return $message;
			}
			$message = wpc_format_message( $message );
			return $message;
		}, 10, 2);


		add_filter("wp_mail_content_type", "wpc_mail_set_contenttype");
		function wpc_mail_set_contenttype( $content_type ) {
			$settings = wpc_mailing_settings();
			if( empty( $settings ) || empty( $settings->html ) ) {
				return $content_type;
			}
			return "text/html";
		}

		add_filter("wpc_mail_link_text", function( $text ) { 
			$settings = wpc_mailing_settings();
			if( empty( $settings ) || empty( $settings->html ) ) {
				return $text;
			}
		    preg_match_all("/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/", $text, $matches);
		    $usedPatterns = array();
		    foreach($matches[0] as $pattern){
		        if(!array_key_exists($pattern, $usedPatterns)){
		            $usedPatterns[$pattern]=true;
		            $text = str_replace( $pattern, "<a href=\"".$pattern."\" rel=\"nofollow\">$pattern</a> ", $text );   
		        }
		    }
		    return $text;
		});

		add_filter( "wpc_get_user_social", function( $list ) {

			$icons = wpc_social_list_icons();

			foreach( $list as $i => $item ) {

				if( ! empty( $item['name'] ) ) {
					switch( strtolower( $item['name'] ) ) {

						case 'twitter':
							$list[$i]['icon'] = 'wpcico wpcico-twitter';
							break;

						case 'facebook':
							$list[$i]['icon'] = 'wpcico wpcico-facebook-official';
							break;

						case 'google+':
							$list[$i]['icon'] = 'wpcico wpcico-gplus-squared';
							break;

						case 'linkedin':
							$list[$i]['icon'] = 'wpcico wpcico-linkedin-squared';
							break;

						case 'website':
							$list[$i]['icon'] = 'wpcico wpcico-mouse';
							break;

						case 'instagram':
							$list[$i]['icon'] = 'wpcico wpcico-instagram';
							break;

						case 'youtube':
							$list[$i]['icon'] = 'wpcico wpcico-youtube-squared';
							break;

						case 'soundcloud':
							$list[$i]['icon'] = 'wpcico wpcico-soundcloud-circled';
							break;

						case 'pinterest':
							$list[$i]['icon'] = 'wpcico wpcico-pinterest-squared';
							break;

						case 'email':
							$list[$i]['icon'] = 'wpcico wpcico-mail-alt';
							break;
						
						default:
							if ( ! empty( $icons[$item['name']] ) ) $list[$i]['icon'] = $icons[$item['name']];
							break;
					}

					if("email" !== strtolower( $list[$i]['name'] )) {
						if( ! is_numeric( mb_strpos( $list[$i]['value'], "http" ) ) ) {
							$list[$i]['value'] = "http://{$list[$i]['value']}";
						}
					}
				}

			}

			return $list;

		}, 0);

		add_filter("wpc_user_profile_social_links", function( $content ) { 
			
			$user_social = wpc_get_user_social();
			?>

			<?php if( ! empty( $user_social ) ) : ?><?php ob_start(); ?>

				<div style="clear:both;overflow:hidden;display:table"></div>

				<div class="user-social">
				
					<?php foreach( $user_social as $field ) : ?>

						<a href="<?php echo $field['value']; ?>" target="_blank" rel="nofollow" title="<?php echo $field['name']; ?>" class="wpc-social-<?php echo $field['name']; ?>" <?php echo 'Website' == $field['name'] ? 'itemprop="url"' : ''; ?>>
							<?php echo ! empty( $field['icon'] ) ? "<i class=\"{$field['icon']}\"></i>" : "<span>{$field['name']}</span>"; ?>
						</a>

					<?php endforeach; ?>

				</div>

				<?php return ob_get_clean(); ?>

			<?php endif; ?>

			<?php

			return $content;

		});

		add_filter('page_link', function( $link, $postID ){

			if( $postID == wpc_settings()->page ) {

				$part = '';
				
				if( wpc_is_messages() ) {
					
					if( wpc_is_single_conversation() ) {

						$part = wpc_get_bases()->messages . '/' . wpc_get_recipient()->user_nicename . '/';

					} else {

						$part = wpc_get_bases()->messages . '/';

						if( wpc_is_archives() ) {
							wpc_get_bases()->archives . '/';
						}

					}

				}

				elseif( wpc_is_users() ) {

					$part = wpc_get_bases()->users . '/';

					if( wpc_is_single_user() ) {

						if( wpc_is_user_edit() ) {

							$part = wpc_get_bases()->users . '/' . wpc_get_displayed_user()->user_nicename . '/edit/';

						} elseif( wpc_is_user_notifications() ) {

							$part = wpc_get_bases()->users . '/' . wpc_get_displayed_user()->user_nicename . '/notifications/';

						} else {

							if( wpc_is_custom_profile_tab() && ! empty( wpc_get_profile_tab()->slug ) ) {
								$part = wpc_get_bases()->users . '/' . wpc_get_profile_tab()->slug . '/';
							} else {
								$part = wpc_get_bases()->users . '/' . wpc_get_displayed_user()->user_nicename . '/';
							}

						}

					} else {

						if( wpc_is_archive_users() ) {
							$part = wpc_get_bases()->users . '/';
						}

						elseif( wpc_is_archive_blocked_users() ) {
							$part .= 'blocked/';
						}

						elseif( wpc_is_archive_online_users() ) {
							$part .= 'online/';
						}

					}

				}

				elseif ( wpc_is_moderation() ) {

					$part = wpc_get_bases()->mod;

				}

				if( $part ) {
					$link = home_url( $part );
				}

			}

			return $link;

		}, 10, 2);

		add_action('wp', 'wpc_remove_rel_canonical');
		function wpc_remove_rel_canonical() {
			remove_action('wp_head', 'rel_canonical');
		}

		add_filter("wpc_custom_tab_content", function( $content, $tab_data, $user ) { 
			
			if( ! wpc_can_view_profile( $user->ID ) && in_array( wpc_settings()->on_cant_view_profile, array("hide_profile_content","hide_profile_information", "exclude_from_users") ) ) {
				return '<div class="profile-inaccessible"> <p><i class="wpcico wpcico-block"></i> ' . apply_filters( "wpc_cant_view_profile_notice", wpc_translate("Sorry, this user's privacy settings do not allow you to view their profile"), $user->ID ) . '</p> </div>';
			}

			return $content;

		}, 10, 3);

		/* cache */

		add_action("wpc_post_delete_item", function( $item ) {
			if ( 10 !== mb_strlen( $item ) ) {
				$m = wpc_get_message( $item );
				$item = ! empty( $m->PM_ID ) ? $m->PM_ID : 0;
			}
			if ( $item ) WPC_cache::flush_conversation_messages( $item );
		});

		add_action('wpc_post_send_message', function( $args ) {
			WPC_cache::flush_conversation_messages( $args['PM_ID'] );
		});

		add_action('wpc_post_mark_read', function( $data ) {
			if( is_array( $data ) ) {
				if ( ! empty( $data[0] ) ) {
					$pm_id = wpc_get_message( $id )->PM_ID;
				} else $pm_id = 0;
			} else {
				$pm_id = (int) $data;
			}
			WPC_cache::flush_conversation_messages( $pm_id );
		});

		add_action('wpc_post_mark_unread', function( $ID, $pm_id ) {
			WPC_cache::flush_conversation_messages( $pm_id );
			delete_user_meta( wp_get_current_user()->ID, "_wpc_json_ls_{$pm_id}" );
		}, 10, 2);

		/* end cache */

		add_action('wpc_post_send_message', function( $args ) {
					
			$meta = get_user_meta( $recipient = $args['recipient'], "_wpc_json_items", 1 );

			if ( ! $meta ) $meta = array();
			if ( empty( $meta[$args['PM_ID']] ) ) $meta[$args['PM_ID']] = array();

			$meta[$args['PM_ID']][$args['message_id']] = time();

			update_user_meta( $recipient, "_wpc_json_items", $meta );

		});


		add_action('wpc_json_loaded', function( $data ) {

			if( ! is_user_logged_in() || ! wpc_is_admin_ajax() ) {
				return;
			}

			if( empty( $data['notifications']['unread'] ) ) {
				return;
			}

			$meta = get_user_meta( $current_user = wp_get_current_user()->ID, "_wpc_json_items", 1 );
			if ( ! $meta ) $meta = array();

			foreach( $data['notifications']['unread'] as $message ) {
				if ( ! empty( $meta[$message['pm_id']] ) ) {
					foreach ( $meta[$message['pm_id']] as $id => $time ) {
						if ( $message['message_id'] == $id ) {
							unset( $meta[$message['pm_id']][$id] );
							continue;
						}
					}
				}
			}

			$meta = array_filter( $meta );

			if ( ! empty( $meta ) ) {
				update_user_meta( $current_user, "_wpc_json_items", (array) $meta );
			} else delete_user_meta( $current_user, "_wpc_json_items" );
			

		}, 10);

		add_filter("body_class", function( $class ) { 
			if ( ! empty( wpc_settings()->RTL ) ) {
				$class[] = "wpc-rtl";
			} return $class;
		});

		add_action('wpc_after_template_load', function() {
			if ( empty( wpc_settings()->copy ) ) return;
			?>
				<div role="wpc-credits"><?php echo wpc_translate('Powered by'); ?> <a href="http://plugin.wpchats.io" title="<?php echo wpc_translate('WordPress live chat and instant messaging plugin with user profiles'); ?>" target="_blank">WpChats Lite</a></div>
			<?php
		});

		add_filter("wpc_ajax_disable_multiple_send", function( $bool ) { 
			return false !== get_option( "wpc_ajax_disable_multiple_send" );
		});
		
		add_action("wpc_post_send_mail", function() {
			update_option("_wpc_overview_stats_sent_nmails",((int)get_option( "_wpc_overview_stats_sent_nmails" ))+1);
		});

		add_action("wp_footer", function() {
			?><script type="text/javascript">
			  window.onload = function() {
			  	if ( Object.prototype.toString.call(window.HTMLElement).indexOf('Constructor') > 0 ) {
			  		document.head.innerHTML += '<style type="text/css" media="all">.wpc-u-snippet .user-snippet-details{max-width:75%;}</style>';
			  	}
			  }
			</script><?php
		});

		add_shortcode('wpc-messages', function(){
			if ( is_page() ) {
				wp_redirect( wpc_messages_base() );
				exit;
			} return;
		});

		add_shortcode('wpc-users', function(){
			if ( is_page() ) {
				wp_redirect( wpc_get_user_links()->users->all );
				exit;
			} return;
		});

	}

}

WPC_extend::instance();