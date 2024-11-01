<?php

/**
  * class WPC_functions
  * The main functions used in WpChats
  * @since 3.0
  */

class WPC_functions
{

	protected static $instance = null;

	/**
	  * Loads the class
	  */

	public static function instance() {

		return null == self::$instance ? new self : self::$instance;

	}

	/**
	  * class constructor
	  */

	function __construct() {
		add_action('init', array( &$this, 'init' ));
	}

	public function init() {

		/**
		  * check if we are in the messages screen (single conversation|archives|..)
		  *
		  * @since 3.0
		  * @return bool (true|false)
		  */

		function wpc_is_messages() {
			return '1' == get_query_var( 'wpc_messages', wpc_get_query_var( 'wpc_messages', false ) );
		}

		/**
		  * checks whether we are in a single conversation
		  *
		  * @since 3.0
		  * @return bool (true|false)
		  */

		function wpc_is_single_message() {
			$_excluded = array( wpc_settings()->slugs->archives, 'page' );
			/** in other words, 'archives' and 'page' can not be user nicenames, meaning 
			  * that you can't contact a user if their user nicename is 'archives', just
			  * because it is reserved for the archives directory
			  * You can use array_push to push more strings to ignore into (array) $_excluded
			  */
			$_excluded = apply_filters( 'wpc_sub_page_ignored_possible_user_slugs', $_excluded );
			$_var = get_query_var( 'wpc_recipient', wpc_get_query_var('wpc_recipient', false) );
			$_array = array_keys($_excluded, $_var);
			return $_var && empty( $_array );
		}

		/**
		  * gets the current conversation recipient user data
		  *
		  * @since 3.0
		  * @return object|false
		  */

		function wpc_get_recipient() {
			
			global $_wpc_get_recipient;

			if ( ! empty( $_wpc_get_recipient ) ) {
				return $_wpc_get_recipient;
			}

			global $wpc;
			$object = false;

			if ( ! empty( $wpc['recipient'] ) ) {
				return $wpc['recipient'];
			}

			if( wpc_is_single_message() ) {
				$object = get_user_by( 'slug', get_query_var( 'wpc_recipient', wpc_get_query_var('wpc_recipient', false) ) );
			}

			$GLOBALS['_wpc_get_recipient'] = $object;

			return $object;

		}

		/**
		  * gets the recipient user ID
		  *
		  * @since 3.0
		  * @return int|false
		  */

		function wpc_get_recipient_id() {
			return is_object( $recipient = wpc_get_recipient() ) ? $recipient->ID : false;
		}

		/**
		  * loads the templates for messages and users
		  *
		  * @since 3.0
		  * @return string (the HTML)
		  */

		function wpc_load_template() {

			do_action('wpc_before_template_load');

			echo '<div class="wpc">';
			echo '<div class="wpc-content">';

			ob_start();

			?>
				<div class="wpc-feedback" style="display:none">
					<?php do_action('wpc_client_feedback'); ?>
					<span>&times;</span>
				</div>
				<div class="sidebar_clear"></div>
			<?php

			echo apply_filters('wpc_client_feedback_html', ob_get_clean());

			if( wpc_is_messages() ) {

				if( wpc_is_admin_ajax() && ! is_user_logged_in() )
					return 0;
				
				if( wpc_is_single_conversation() ) {

					if( wpc_get_recipient_id() == wp_get_current_user()->ID && wpc_is_admin_ajax() )
						return 0;

					if ( wpc_is_forward_message() )
						include wpc_template_path( 'messages/forward-messages' );
					else
						include wpc_template_path( 'messages/content-messages' );

				} else {

					if( wpc_is_new_message() ) {
						include wpc_template_path( 'messages/content-new' );
					} else {

						include wpc_template_path( 'messages/loop-messages' );

						/*if( wpc_is_admin_ajax() ) {

							include wpc_template_path( 'messages/loop-messages' );

						} else {

							$uri = admin_url( "admin-ajax.php?action=wpc&wpc_messages=1");

							if( wpc_is_archives() ) {
								$uri .= "&wpc_archives=1";
							}

							if ( ! empty( wpc_get_search_query() ) ) {
								$uri .= "&q=" . wpc_get_search_query();
							}

							if ( ! empty( $_GET['view'] ) && "unread" == $_GET['view'] ) {
								$uri .= "&view=unread";
							}
							
							?><div class="wpc_lload" data-load="<?php echo $uri; ?>"></div><?php

						}*/
					}

				}

			}

			elseif( wpc_is_users() ) {

				if( wpc_is_single_user() || wpc_is_404_user() ) {

					if( wpc_is_404_user() ) {

						echo '<div class="' . apply_filters( 'wpc_users_classes', 'wpc-users not-found' ) . '">';
						include wpc_template_path( 'users/404' );
						echo '</div>';

					} else {

						ob_start();

						if( wpc_is_user_edit() ) {

							do_action('wpc_before_load_user_edit');

							echo '<div class="' . apply_filters( 'wpc_users_classes', 'wpc-users single-user edit group-' . wpc_profile_edit_current_group_name() ) . '">';
							include wpc_template_path( 'users/user-edit' );
							echo '</div>';

						} elseif( wpc_is_user_notifications() ) {

							echo '<div class="' . apply_filters( 'wpc_users_classes', 'wpc-users single-user notifications' ) . '">';
							include wpc_template_path( 'users/user-notifications' );
							echo '</div>';

						} else {

							echo '<div class="' . apply_filters( 'wpc_users_classes', 'wpc-users profile' ) . '">';
							include wpc_template_path( 'users/user-single' );
							echo '</div>';
							
						}

						echo apply_filters('wpc_profile_content', ob_get_clean());

					}

				} else {

					if( wpc_is_archive_users() )
						echo '<div class="' . apply_filters( 'wpc_users_classes', 'wpc-users all' ) . '">';

					elseif( wpc_is_archive_blocked_users() )
						echo '<div class="' . apply_filters( 'wpc_users_classes', 'wpc-users blocked' ) . '">';

					elseif( wpc_is_archive_online_users() )
						echo '<div class="' . apply_filters( 'wpc_users_classes', 'wpc-users online' ) . '">';

					include wpc_template_path( 'users/content-loop' );

					echo '</div>';

				}

			}

			// PRO feature
			elseif ( wpc_is_moderation() ) {

				do_action('wpc_moderation_headers');

				echo '<div class="wpc-mod">';

				include wpc_template_path( 'moderation/watch-conversations' );

				echo '</div>';

			}

			echo '</div><!-- /.wpc-content -->';
			echo '<div id="wpc-title" data-title="' . stripslashes( str_replace( '"', '&quot;', wpc_title() ) ) . '" style="display:none"></div>';
			do_action('wpc_after_template_load');
			echo '</div>';

		}

		/**
		  * Returns the markup of wpc_load_template function which prints it
		  *
		  * @since 3.0
		  * @return str the content
		  */

		function wpc_loaded_template() {
			ob_start();
			wpc_load_template();
			return ob_get_clean();
		}

		/**
		  * Returns the base URL of the messages area
		  *
		  * @since 3.0
		  * @param $sub str after the URL
		  * @return str the url
		  */

		function wpc_messages_base( $sub = '' ) {
			$base = home_url('/' . wpc_get_bases()->messages . '/' . $sub);
			return apply_filters( "wpc_messages_base", $base, $sub );
		}

		/**
		  * Echos the conversation form
		  *
		  * @since 3.0
		  * @param $recipient int user ID for the contact
		  */

		function wpc_conversation_form( $recipient = false ) {

			$bail_data = wpc_can_contact( $recipient, 1 );

			if ( empty( $bail_data["can_contact"] ) ) {
				$bail = true;
			} else $bail = false;

			if( ! $bail )
				WPC_message::instance()->_send();

			do_action('wpc_before_conversation_form', $recipient);

			$pm_id = wpc_get_conversation_id($recipient);
			$autosave = wpc_conversation_autosave();

			ob_start();

				?>
					
					<form action="<?php echo wpc_get_conversation_permalink( false, $recipient ); ?>" method="post" class="wpcinput<?php echo $bail ? ' disabled' : '' ?>" id="_wpc_mform" data-pm-id="<?php echo $pm_id ? $pm_id : ''; ?>">
						<textarea name="_wpc_message" <?php echo $bail ? 'disabled="disabled" ' : ' ' ?> placeholder="<?php echo wpc_translate('Write a message..'); ?>"><?php echo $autosave; ?></textarea>
						<?php wp_nonce_field( '_wpc_nonce', '_wpc_nonce' ); ?>
						<input type="hidden" name="_wpc_r" value="<?php echo wpc_get_recipient_id(); ?>" />
						<input type="hidden" name="action" value="wpc_send" />
						<input type="submit" name="_wpc_send" value="<?php echo wpc_translate('Send'); ?>" id="_wpc_send_button" <?php echo $bail ? 'disabled="disabled" ' : ' ' ?>/>
						<span class="autosave-note"><?php echo $autosave > '' ? 'auto-saved' : ''; ?></span>
						<span class="wpc-add-emo" data-target=".wpc-emo-container" data-input="#_wpc_mform textarea" title="<?php echo wpc_translate('add emoji'); ?>">emoji</span>
						
						<span class="wpc-add-img" title="<?php echo wpc_translate('add image'); ?>" data-input="#_wpc_mform textarea" data-target=".wpc-img-cont.main">image</span>

						<?php if ( $bail ) : ?>
							<span class="wpc-cant-contact">
								<?php if ( ! empty( $bail_data["notice"] ) ) : ?>
									<p><?php echo $bail_data["notice"]; ?></p>
								<?php else : ?>
									<p><?php echo apply_filters('wpc_cant_contact_notice', wpc_translate('Sorry, you can not contact this user for the moment.')); ?></p>
								<?php endif; ?>
							</span>
							<?php do_action( "wpc_conversation_form_after_cant_contact_notice", $recipient, $pm_id ); ?>
						<?php endif; ?>

						<?php do_action( "wpc_after_conversation_form_elements", $pm_id, $recipient ); ?>

					</form>
					<!-- when no ajax, just serve the form and add the URL into the area and load -->

				<?php

			echo apply_filters( "wpc_conversation_form", ob_get_clean(), $recipient );

			do_action('wpc_after_conversation_form', $recipient);

		}

		function wpc_get_conversation_permalink( $sub = '', $recipient = false, $pm_id = 0 ) {

			$wpc_messages_base = wpc_messages_base();

			if( $recipient ) {
				return $wpc_messages_base . ( get_userdata($recipient)->user_nicename ) . '/' . $sub;
			}

			else if ( $pm_id ) {
				$pm = wpc_get_conversation( $pm_id );
				if( isset( $pm->last_message ) ) {
					return $wpc_messages_base . ( $pm->last_message->recipient_name . '/' . $sub );
				}
			}

			else if ( /*false !== */$get_recipient = wpc_get_recipient() ) {
				return $wpc_messages_base . ( $get_recipient->user_nicename ) . '/' . $sub;
			}

			return false;

		}

		function wpc_can_contact( $recipient = false, $return_notice = false ) {
			
			$bail = false;
			$current_user = wp_get_current_user();

			if( ! $recipient && /*false !== */$get_recipient = wpc_get_recipient() )
				$recipient = $get_recipient->ID;

			if( ! get_userdata( $recipient ) || $recipient == $current_user->ID )
				$bail = true;

			if( wpc_is_user_blocked( $recipient ) || wpc_is_user_blocked_by( $recipient ) )
				$bail = true;

			if( $recipient && ! get_userdata( $recipient ) )
				$bail = true;

			if( wpc_is_banned( $recipient ) || wpc_is_banned( $current_user->ID ) ) {
				$bail = true;
			}

			if( ! $bail ) {

				$preferences = wpc_get_user_preferences( $recipient );

				switch( $preferences['contact'] ) {

					case 'anyone':
						$bail = false;
						break;

					case 'contacts':
						$bail = ! wpc_has_contacted( $recipient, $current_user->ID ) && ! user_can( $current_user->ID, "manage_options" );
						break;

					case 'nobody':
						$bail = ! user_can( $current_user->ID, "manage_options" );
						break;

				}

			}

			/**
			  * You can filter this boolean to set it to true|false as you want
			  * An example use is that let's say you don't want users to contact admins
			  * then we are setting $bail to true if $recipient is an admin
			  * to check if is admin use in_array('administrator', get_userdata($recipient)->roles )
			  */
			$bail = apply_filters('wpc_can_contact', $bail, $recipient);

			if ( is_array( $bail ) ) {
				$bail_data = $bail;
				$bail = $bail_data["bail"];
			}

			if ( $return_notice ) {
				return array(
					"can_contact" => ! $bail,
					"notice" => isset( $bail_data['notice'] ) ? $bail_data['notice'] : false
				);
			}

			return ! $bail;

		}

		function wpc_my_conversations( $return_all = false, $return_everything = false ) {
			
			if( $return_everything )
				return WPC_message::instance()->conversations( false, true );

			if( wpc_is_archives() )
				return WPC_message::instance()->archives( $return_all );
			else
				return WPC_message::instance()->conversations( $return_all );

		}

		function wpc_template_path( $name ) {

			$base = get_stylesheet_directory() . '/' . WPC_DIR_NAME . '/themes/';

			#! $base = apply_filters('wpc_template_path_directory', $base);

			$child_file = $base . $name . '.php';
			$core_file = WPC_PATH . 'themes/' . $name . '.php';

			return file_exists( $child_file ) ? $child_file : $core_file;

		}

		function wpc_message_snippet_classes( $pm_id ) {
			echo WPC_message::instance()->snippet_classes( $pm_id );
		}
		function wpc_message_classes( $ID ) {
			echo WPC_message::instance()->message_classes( $ID );
		}

		function wpc_get_conversation( $pm_id, $exists = false ) {
			return WPC_message::instance()->get_conversation( $pm_id, $exists );
		}

		function wpc_get_message( $ID ) {
			return WPC_message::instance()->get_message( $ID );
		}

		function wpc_message_snippet_excerpt( $ID ) {

			$message_body = wpc_get_message( $ID )->message;
			$lenght = apply_filters( 'wpc_message_snippet_excerpt_lenght', 150 );

			$message_body = preg_replace_callback(
				"(\[img\](.*?)\[/img\])is",
				function($m) {
					return '<em>['.wpc_translate('attachement').']</em>';
				},
				$message_body
			);

			$message = substr( $message_body, 0, $lenght );
			$message .= strlen( $message_body ) > $lenght ? '...' : '';

			$message = stripslashes( $message );

			$message = str_replace(
				array( '&_lt;', '&_gt;', '&amp;_lt;', '&amp;_gt;' ),
				array( '<', '>', '<', '>' ),
				$message
			);
			$message = str_replace( wpc_emoji_list(true)->symbols, wpc_emoji_list(true)->strings, $message );

			return apply_filters( 'wpc_message_snippet_excerpt_content', $message, $ID );

		}

		function wpc_time_diff( $target, $before = '', $after = '' ) {

				if( !isset( $target ) )
					return false;
				$target = new DateTime( date("Y-m-d H:i:s", $target) );
				$now = new DateTime( date("Y-m-d H:i:s", time()) );

				$delta = $now->diff($target);

				$quantities = array(
				    'year' => $delta->y,
				    'month' => $delta->m,
				    'day' => $delta->d,
				    'hour' => $delta->h,
				    'minute' => $delta->i,
				    'second' => $delta->s
				    );
				$str = '';
				foreach($quantities as $unit => $value) {
				    if($value == 0) continue;
				    if($value != 1) {
				        $unit .= 's';
				    }
				    $str .= $value . ' ' . wpc_translate($unit);
				    $str .=  ', ';
				    break;
				}
				$str = $str == '' ? wpc_translate('a moment') : substr($str, 0, -2);
			
				if( $before ) $before .= ' ';
				if( $after ) $after = ' ' . $after;

				$str = $before . $str .  $after;

				return apply_filters( 'wpc_time_diff_string', $str, $target, $before, $after );

		}


		function wpc_get_messages( $pm_id = false, $count = false, $all = false ) {

			if( ! $pm_id )
				$pm_id = wpc_get_conversation_id();

			return WPC_message::instance()->messages( $pm_id, $count, $all );

		}

		function wpc_get_conversation_id( $recipient = false ) {

			if( ! $recipient )
				$recipient = wpc_get_recipient_id();

			$pm = WPC_message::instance()->pm_id( $recipient );
			return ! empty( $pm->exist ) && $pm->exist ? $pm->id : false;

		}

		function wpc_output_message( $string, $ajax = false ) {
			return wpc_format_message( $string, $ajax );
		}

		function wpc_format_message( $string, $ajax = false ) {

			$string = stripslashes($string);

			$string = preg_replace_callback(
				"(\[img\](.*?)\[/img\])is",
				function($m) {
					$src = $m[1];
					$after_atts = '';
					$id = (int) substr($m[1], -10);
					$meta = wpc_get_attachement($id);
					if( $id && ! $meta ) {
						return '<p class="attachement-unavailable"><em>' . wpc_translate('This attachement is no longer available') . '.</em></p>';
					}
					if( $id > 0 && ! empty( $meta ) ) {
						$id = (int) substr($m[1], -10);
						//$src = substr( $i, 0, strpos($i, substr($i, -11) ) );
						$src = str_replace( array( '?'.$id, '&amp;'.$id ), '', $m[1] );
						$after_atts .= ' id="attachment-'.$id.'"';
					}
					$title = explode('/', $src);
					$title = ! empty( $title ) ? end($title) : wpc_translate('attachment');
					if( ! empty( $meta ) ) {
						if( ! file_exists( $meta['path'] ) ) {
							return '<p class="attachement-unavailable"><em>' . wpc_translate('This attachement is no longer available') . '.</em></p>';
						}
					}
					return '<img src="'. str_replace( 'http', 'httpee', $src ) .'" alt="' . $title . ' ('.wpc_translate('attachment').')" title="' . $title . '" width="auto" ' . $after_atts . '/>';
				},
				$string
			);

			$reg_exUrl = "/(http|https)\:\/\/(www.youtu|youtu)([a-zA-Z]|)+\.[a-zA-Z]{2,3}(\/\S*)?/";
		    preg_match_all($reg_exUrl, $string, $matches);
		    $usedPatterns = array();
		    foreach($matches[0] as $pattern){
		        if(!array_key_exists($pattern, $usedPatterns)){
		            $usedPatterns[$pattern]=true;
		            $parts = parse_url($pattern);
					if( ! empty( $parts['query'] ) )
						parse_str($parts['query'], $query);
					if( !empty( $query['v'] ) ) {
						$videoID = $query['v'];
					} else {
						$videoID = array_filter( explode( '/', $pattern ) );
						$videoID = end( $videoID );
					}
		            $string = str_replace( $pattern, '<iframe src="//www.youtube.com/embed/'.$videoID.'?rel=0" width="400" height="300" frameborder="0"></iframe>', $string );   
		        }
		    }

		    $reg_exUrl = "/(http|https)\:\/\/(www.vimeo|vimeo)+\.[a-zA-Z]{2,3}(\/\S*)?/";
		    preg_match_all($reg_exUrl, $string, $matches);
		    $usedPatterns = array();
		    foreach($matches[0] as $pattern){
		        if(!array_key_exists($pattern, $usedPatterns)){
		            $usedPatterns[$pattern]=true;
		            $videoID = array_filter( explode( '/', $pattern ) );
					$videoID = end( $videoID );
		            $string = str_replace( $pattern, '<iframe src="//player.vimeo.com/video/'.$videoID.'" width="400" height="300" frameborder="0" allowfullscreen></iframe>', $string );   

		        }
		    }

		    $reg_exUrl = "/(http|https)\:\/\/(www.dailymotion|dailymotion)+\.[a-zA-Z]{2,3}(\/\S*)?/";
		    preg_match_all($reg_exUrl, $string, $matches);
		    $usedPatterns = array();
		    foreach($matches[0] as $pattern){
		        if(!array_key_exists($pattern, $usedPatterns)){
		            $usedPatterns[$pattern]=true;
		            $videoID = array_filter( explode( '/', $pattern ) );
					$videoID = array_filter( explode( '_', end( $videoID ) ) );
		            if ( ! empty( $videoID[0] ) ) $string = str_replace( $pattern, '<iframe src="//www.dailymotion.com/embed/video/'.$videoID[0].'?api=true" width="400" height="300" frameborder="0"></iframe>', $string );   

		        }
		    }

		    $reg_exUrl = "/(http|https)\:\/\/(www.pastebin|pastebin)+\.[a-zA-Z]{2,3}(\/\S*)?/";
		    preg_match_all($reg_exUrl, $string, $matches);
		    $usedPatterns = array();
		    foreach($matches[0] as $pattern){
		        if(!array_key_exists($pattern, $usedPatterns)){
		            $usedPatterns[$pattern]=true;
		            $ID = array_filter( explode( '/', $pattern ) );
		            if ( $ID ) $ID = array_filter( array_unique( $ID ) );
		            if ( count( $ID ) <= 3 ) :;
					$ID = end( $ID );
		            $string = str_replace( $pattern, '<iframe src="//pastebin.com/embed_iframe/' . $ID . '" style="border:none;width:100%"></iframe>', $string );
					endif;

		        }
		    }

		    $reg_exUrl = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
		    preg_match_all($reg_exUrl, $string, $matches);
		    $usedPatterns = array();
		    foreach($matches[0] as $pattern){
		        if(!array_key_exists($pattern, $usedPatterns)){
		            $usedPatterns[$pattern]=true;
		            $string = str_replace( $pattern, "<a href=\"".$pattern."\" rel=\"nofollow\" target=\"_blank\">$pattern</a> ", $string );   
		        }
		    }

		    $string = str_replace( 'httpee', 'http', $string ); // fix image src back after link parsing is done
			//$string = wpc_nl2p( $string ); // converting line breaks to paragraphs
			//$string = preg_replace("/<p[^>]*><\\/p[^>]*>/", '', $string); // removing empty paragraphs
			if( ! $ajax ) {
				$string = str_replace( array( "&amp;_lt;", "&amp;_gt;" ), array( "&amp;lt;", "&amp;gt;" ), $string );
			}
			$string = html_entity_decode( $string );
			// Parse emoticons
			$emo_list = wpc_emoji_list(true);
			$string = str_replace( $emo_list->symbols, $emo_list->strings, $string );
			// auto paragraphs and line breaks
			$string = wpautop( $string );

			// format inline code
			$string = preg_replace("/`(.*?)`/s", "<pre><code>$1</code></pre>", $string);

			return apply_filters( 'wpc_the_message', $string );

		}

		function wpc_last_message_from_me( $pm_id = false ) {

			if( !$pm_id ) $pm_id = wpc_get_conversation_id();

			$object = WPC_message::instance()->get_conversation( $pm_id );
			
			$bool = false;

			if( ! empty( $object->last_message ) && ! empty( $object->last_message->sender ) ) :;

				global $current_user;
				$bool = $current_user->ID == $object->last_message->sender;

			endif;

			return $bool;

		}

		function wpc_get_single_seen_diff( $pm_id = false, $before = false, $after = false ) {

			if( !$pm_id ) $pm_id = wpc_get_conversation_id();

			$object = WPC_message::instance()->get_conversation( $pm_id );

			if( ! empty( $object->last_message ) && ! empty( $object->last_message->seen ) ) :;

				if( $object->last_message->seen ) {

					return wpc_time_diff( $object->last_message->seen, $before, $after );

				}

			endif;

		}

		function wpc_single_seen_notice( $pm_id = false ) {

			if( !$pm_id ) $pm_id = wpc_get_conversation_id();

			$pm = WPC_message::instance()->get_conversation( $pm_id );

			$time_int = ! empty( $pm->last_message->seen ) ? (int) $pm->last_message->seen : 0;

			ob_start();

			?>
			<?php if( $time_int && wpc_last_message_from_me( $pm_id ) && ! wpc_is_search() ) : ?>

				<p class="wpc-seen-notice">
					<i class="wpcico wpcico-ok"></i>
					<span class="wpc-time-int" data-int="<?php echo $time_int; ?>" data-before="<?php echo wpc_translate('Read'); ?>" data-after="<?php echo wpc_translate('ago'); ?>"><?php echo sprintf( wpc_translate("Read %s ago"), wpc_time_diff( $time_int ) ); ?></span>
				</p>

			<?php else : ?>

				<p class="wpc-seen-notice" style="display:none">
					<i class="wpcico wpcico-ok"></i>
					<span class="wpc-time-int" data-int="" data-before="<?php echo wpc_translate("Read"); ?>" data-after="<?php echo wpc_translate("ago"); ?>"><?php echo wpc_translate("Read %s ago"); ?></span>
				</p>

			<?php endif;

			echo apply_filters('wpc_conversation_seen_notice', ob_get_clean(), $pm_id);

		}

		function wpc_is_single_conversation() {
			return wpc_is_single_message();
		}

		function wpc_conversation_search_form() {

			if( wpc_is_single_conversation() ) {
				$postReq = '&wpc_recipient=' . wpc_get_recipient()->user_nicename;
			} else if( wpc_is_archives() ) {
				$postReq = '&wpc_archives=1';
			} else {
				$postReq = '';
			}

			ob_start();

				?>
					<form method="get" action="<?php echo wpc_get_conversation_permalink(); ?>" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"action": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1' . $postReq) . '", "pushUriValues": "1"}'); ?>">
						<input type="text" name="q" placeholder="<?php echo wpc_translate('search'); ?>" value="<?php echo stripslashes(wpc_get_search_query()); ?>" />
					</form>
				<?php

			echo apply_filters( "wpc_conversation_search_form", ob_get_clean() );

		} 

		function wpc_is_search_messages() {
			return wpc_is_messages() && isset( $_GET['q'] );
		}

		function wpc_get_user_blocked_list( $user_id = 0 ) {
			if( ! $user_id && ! is_user_logged_in() ) { return array(); }
			if( ! $user_id ) { $user_id = wp_get_current_user()->ID; }
			$meta = get_user_meta( $user_id, '_wpc_data', TRUE );
			$return = array();
			if( $meta > '' ) {
				$ob = json_decode( html_entity_decode($meta), false );
				if( ! empty( $ob->blocked ) ) {
					$object = explode( ',', $ob->blocked );
					if( ! empty( $object ) ) {
						foreach( array_filter( array_unique( $object ) ) as $_user_id ) { $return[] = (int) $_user_id; }
					}
				}
			}
			return $return;
		}

		function wpc_is_blocked( $target_user, $current_user = 0 ) {

			if( ! is_user_logged_in() )
				return;

			if( ! $current_user )
				$current_user = wp_get_current_user()->ID;

			$list = wpc_get_user_blocked_list( $current_user );

			return in_array($target_user, $list);

		}

		function wpc_can_notify( $user_id = false ) {

			global $current_user;

			if( ! $user_id )
				$user_id = $current_user->ID;

			$_data = wpc_get_user_data( $user_id );

			if( ! empty( $_data->empty ) )
				return true; // ON by default

			return ! empty( $_data->notify ) && 1 == (int) $_data->notify;

		}

		function wpc_is_user_blocked( $user_id = false, $_current_user = false ) {

			global $current_user;

			if( ! $user_id )
				$user_id = wpc_get_recipient_id();

			if( ! $_current_user )
				$_current_user = $current_user->ID;

			$_array = array_keys( wpc_get_user_blocked_list( $_current_user ), $user_id );

			return ! empty( $_array );

		}

		function wpc_is_user_blocked_by( $user_id = false, $_current_user = false ) {

			global $current_user;

			if( ! $user_id && wpc_get_recipient() )
				$user_id = wpc_get_recipient()->ID;

			if( ! $_current_user )
				$_current_user = $current_user->ID;

			$_array = array_keys( wpc_get_user_blocked_list( $user_id ), $_current_user );

			return ! empty( $_array );

		}


		function wpc_block_link( $user_id = false ) {

			if( ! $user_id && /*false !== */$get_recipient = wpc_get_recipient() )
				$user_id = $get_recipient->ID;

			if( ! $user_id )
				return;

			if( ! wpc_is_user_blocked( $user_id ) ) :;

			ob_start();

			?>

				<a href="<?php echo wpc_get_conversation_permalink( '?do=block' ); ?>" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc_actions&do=block&user=' . $get_recipient->ID ) . '","confirm": "wpc.conf.block_u","success": "html=1", "onSuccessLoad": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1&wpc_recipient=' . $get_recipient->user_nicename . '&done=block' ) . '", "noHistory": "1"}'); ?>" onclick="return confirm(wpc.conf.block_u)"><?php echo wpc_translate('block'); ?></a>

			<?php else : ?>

				<a href="<?php echo wpc_get_conversation_permalink( '?do=unblock' ); ?>" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc_actions&do=unblock&user=' . $get_recipient->ID ) . '","success": "html=1", "onSuccessLoad": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1&wpc_recipient=' . $get_recipient->user_nicename . '&done=block' ) . '", "noHistory": "1"}'); ?>"><?php echo wpc_translate('unblock'); ?></a>

			<?php endif;

			echo ob_get_clean();

		}

		function wpc_update_user_data( $args ) {

			$user_id = $args['user'];

			if( ! get_userdata( $user_id ) )
				return;

			if( isset( $args['notify'] ) ) {
				if ( is_int( $args['notify'] ) )
					$_notify = (int) $args['notify'];
				elseif ( is_bool( $args['notify'] ) )
					$_notify = $args['notify'] ? 1 : 0;
			} else {
				$_notify = wpc_can_notify( $user_id ) ? 1 : 0;
			}
			$_blocked = isset( $args['blocked'] ) ? $args['blocked'] : implode( ',', wpc_get_user_blocked_list( $user_id ) );
			$_archives = isset( $args['archives'] ) ? $args['archives'] : implode(',', wpc_get_archives_list( $user_id ));

			$_time_int = apply_filters('wpc_core_update_user_status_time_int', time(), $args);
			$object = '{ "blocked": "' . $_blocked . '", "notify": "' . $_notify . '", "archives": "' . $_archives . '", "last_seen": "' . $_time_int . '"  }';
			update_user_meta( $user_id, '_wpc_data', esc_attr($object) );

		}

		function wpc_is_archived( $pm_id = false, $user_id = false ) {

			if( ! $pm_id )
				$pm_id = wpc_get_conversation_id();

			if( ! $pm_id )
				return;

			global $current_user;

			if( ! $user_id )
				$user_id = $current_user->ID;

			$_array = array_keys( wpc_get_archives_list( $user_id ), $pm_id);

			return ! empty( $_array );

		}

		function wpc_get_archives_list( $user_id = 0 ) {
			if ( ! $user_id ) $user_id = wp_get_current_user()->ID;
			$meta = get_user_meta( $user_id, '_wpc_data', TRUE );
			if( '' !== $meta ) {
				$ob = json_decode( html_entity_decode($meta), false );
				if( ! empty( $ob->archives ) ) {
					return explode( ',', $ob->archives );
				} else {
					return array();
				}
			} else {
				return array();
			}

		}

		function wpc_archive_pm( $pm_id, $unarchive = false , $user_id = false ) {
			return WPC_message::instance()->archive( $pm_id, $unarchive, $user_id );
		}

		function wpc_is_archives() {
			return '1' == get_query_var( 'wpc_archives', wpc_get_query_var('wpc_archives', false) );
		}

		function wpc_get_archive_link( $pm_id = false ) {

			if( ! $pm_id && /*false !== */$get_pm_id = wpc_get_conversation_id() )
				$pm_id = $get_pm_id;

			$wpc_get_recipient = wpc_get_recipient();
			$wpc_get_conversation_permalink = wpc_get_conversation_permalink();

			if( wpc_is_archived( $pm_id ) ) {

				?>

					<a href="<?php echo $wpc_get_conversation_permalink . ('?do=unarchive'); ?>" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1&wpc_recipient=' . $wpc_get_recipient->user_nicename . '&do=unarchive') . '", "success": "html=1", "onSuccessLoad": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1&done=unarchive') . '", "pushUri": "' . wpc_messages_base() . '" }'); ?>"><?php echo wpc_translate('unarchive') ?></a>
				
				<?php

			} else {

				?>

					<a href="<?php echo $wpc_get_conversation_permalink . ('?do=archive'); ?>" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1&wpc_recipient=' . $wpc_get_recipient->user_nicename . '&do=archive') . '", "success": "html=1", "onSuccessLoad": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1&done=archive') . '", "pushUri": "' . wpc_messages_base() . '" }'); ?>"><?php echo wpc_translate('archive') ?></a>
				
				<?php

			}

			return;

		}

		function wpc_archive_link( $pm_id = false ) {
			echo wpc_get_archive_link( $pm_id );
		}

		function wpc_get_mute_link( $pm_id ) {
			$pm_permalink = wpc_get_conversation_permalink();
			$wpc_get_recipient = wpc_get_recipient();

			if( wpc_is_muted( $pm_id ) ) {
				
				?>
					<a href="<?php echo $pm_permalink . ( 'mute/' ); ?>" class="wpcfmodal" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1&wpc_recipient=' . $wpc_get_recipient->user_nicename . '&wpc_mute_conversation=1') . '", "onExitTitle": "' . wpc_title(1) . '", "onExitHref": "' . $pm_permalink . '", "onLoadHref": "' . $pm_permalink . ('mute/') . '"}'); ?>"><?php echo wpc_translate('unmute'); ?></a>

				<?php

			} else {
				?>
					<a href="<?php echo $pm_permalink . ( 'mute/' ); ?>" class="wpcfmodal" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1&wpc_recipient=' . $wpc_get_recipient->user_nicename . '&wpc_mute_conversation=1') . '", "onExitTitle": "' . wpc_title(1) . '", "onExitHref": "' . $pm_permalink . '", "onLoadHref": "' . $pm_permalink . ('mute/') . '"}'); ?>"><?php echo wpc_translate('mute'); ?></a>
				<?php
			}
		}

		function wpc_mute_link( $pm_id = false ) {
			return false; // PRO feature
		}

		function wpc_is_muted( $pm_id = 0, $user_id = 0, $return_value = false ) {
			return 0; // PRO feature			
		}

		function wpc_get_search_query( $encode = false ) {
			$query = isset( $_REQUEST['q'] ) ? sanitize_text_field($_REQUEST['q']) : '';
			$query = $encode ? urlencode( stripslashes( $query ) ) : str_replace( array( '\"', '"' ), '&quot;', $query );
			return $query;
		}

		function wpc_update_user_status() {
			return WPC_users::instance()->update_status();
		}

		function wpc_get_user_last_seen( $user_id = false, $return_args = false, $before = false, $after = false ) {

			global $current_user;

			if( ! $user_id ) { $user_id = $current_user->ID; }

			$meta = get_user_meta( $user_id, '_wpc_last_seen', TRUE );
			$return = array();

			if( (int) $meta > 0 ) { $last_seen = (int) $meta; }
			else { $last_seen = false; }

			$args = array(
				'before' => 'last seen',
				'after' => 'ago'
			);

			$args = apply_filters('wpc_user_last_seen_args', $args);

			$before = $before ? $before : $args['before'];
			$after = $after ? $after : $args['after'];

			$_diff = str_replace(
				"[time]",
				wpc_time_diff( $last_seen ),
				wpc_translate("$before [time] $after")
			);

			if( $return_args )
				return (object) array(
					'integer' => $last_seen,
					'difference' => $last_seen ? wpc_time_diff( $last_seen ) : false,
					'full_difference' => $last_seen ? $_diff : false,
					'before' => wpc_translate($before),
					'after' => wpc_translate($after)
				);

			if( $last_seen ) {
				return $_diff;
			} else {
				return wpc_translate('Not recently active');
			}

		}

		function wpc_online_interval() {
			return apply_filters( 'wpc_online_interval', 20 );
		}

		function wpc_is_online( $user_id ) {
			$_int = wpc_get_user_last_seen( $user_id, true )->integer;
			if( $_int ) {
				$_diff = time() - $_int;
				return $_diff <= wpc_online_interval();
			}
			return false;
		}

		function wpc_get_counts( $user_id = false, $pm_id = false ) {
			return WPC_message::instance()->counts( $user_id, $pm_id );
		}

		/* code revision stopped here */

		function wpc_is_blocking_allowed() {
			$bool = wpc_settings()->blocking && wpc_current_user_can('block_users');
			return apply_filters( 'wpc_is_blocking_allowed', $bool );
		}
		function wpc_can_mark_unread( $pm_id = 0 ) {

			if( ! $pm_id )
				$pm_id = wpc_get_conversation_id();

			$bool = false;
			$pm_data = wpc_get_conversation( $pm_id );
			$last_message = $pm_data->last_message;

			$bool = ! empty( $pm_data ) && ! empty( $last_message ) && $pm_data->last_message->sender !== wp_get_current_user()->ID;

			if( ! wpc_current_user_can('mark_unread') )
				$bool = false;
			
			return apply_filters( 'wpc_can_mark_unread', $bool, $pm_id );
		}

		function wpc_get_bases() {

			$settings = wpc_settings();

			return (object) array(
				'messages' 	=> $settings->slugs->messages,
				'archives' 	=> $settings->slugs->archives,
				'users' 	=> $settings->slugs->users,
				'mod' 		=> $settings->slugs->mod
			);

		}

		function wpc_get_user_data( $user_id = false ) {

			if( ! $user_id )
				$user_id = wp_get_current_user()->ID;

			$meta = get_user_meta( $user_id, '_wpc_data', TRUE );

			$return = new stdClass();

			if( $meta > '' ) {

				$ob = json_decode( html_entity_decode($meta), false );
				if( ! is_object( $ob ) ) { $ob = new stdClass(); }
				$ob->notify = empty( $ob->notify ) ? 1 : (int) $ob->notify;
				$return = $ob;

			} else {

				$return = new stdClass();
				$return->blocked = false;
				$return->notify = 1;
				$return->archives = false;
				$return->last_seen = false;
				$return->empty = true;
			}

			if( ! isset( $return->last_seen ) || ! ( (int) $return->last_seen > 0 ) ) {
				$return->last_seen = wpc_get_user_last_seen( $user_id, true )->integer;
			}

			return (object) $return;
			
		}

		function wpc_nl2p( $string ) {
			# credit: http://stackoverflow.com/q/7409512/#7409591
			$string = str_replace(array('<p>', '</p>', '<br>', '<br />'), '', $string);
			$string = '<p>'.preg_replace( array("/([\n]{2,})/i", "/([\r\n]{3,})/i","/([^>])\n([^<])/i"), array("</p>\n<p>", "</p>\n<p>", '$1<br/>$2'), trim($string)).'</p>'; 
		    return str_replace( '<br/>', "</p>\n<p>", $string );
		}

		function wpc_get_user_profile_url( $user_id = false ) {
			return wpc_get_user_links( $user_id )->profile;
		}

		function wpc_contact_link( $recipient, $current_user = false, $sub = '' ) {

			if( ! $current_user )
				$current_user = wp_get_current_user()->ID;

			if( $recipient == $current_user || (int) $current_user == 0 )
				return false;

			return wpc_messages_base( get_userdata($recipient)->user_nicename . '/' . $sub );

		}

		function wpc_settings($defaults = false) {

			global $wpc_settings;
			if ( ! empty( $wpc_settings ) ) {
				return $wpc_settings;
			}

			$settings = new stdClass();

			$slugs = array(
				'messages' 	=> 'wpc-messages',
				'users' 	=> 'wpc-users',
				'mod' 		=> 'wpc-moderation',
				'archives' 	=> 'archives'
			);

			$uploads = array(
				'enable' => true,
				'meta_data' => false,
				'allowed' => array( 'jpg', 'png', 'bmp', 'gif' ),
				'max_size_kb' => 1000,
				'max_size' => abs( 1000 * 1000 ) // def: 1 megabytes (this is in bytes)
				//                  |--- customizable
			);

			$pagination = array( 'conversations' => 10, 'messages' => 10, 'users' => 3 );

			$settings->slugs = (object) $slugs;
			$settings->page = (int) get_option('_wpc_page');
			$settings->page_title = (string) get_post( get_option('_wpc_page') )->post_title;
			$settings->blocking = true;
			$settings->help_text = 'nada';
			$settings->pagination = (object) $pagination;
			$settings->notifications = true; //(object) array( 'subject' => '' );
			$settings->uploads = (object) $uploads;
			$settings->isTyping_allowed = false; // coming soon
			$settings->last_core_emoji = 54;
			$settings->on_cant_view_profile = 'exclude_from_users';
			$settings->nav_menu = (object) array(
				'locations' => array(),
				'messages' => (object) array( 'enable' => true, 'inner' => wpc_translate( "Messages" ) ),
				'users' => (object) array( 'enable' => true, 'inner' => wpc_translate( "Browse users" ) )
			);

			if( ! empty( array_keys( get_nav_menu_locations() )[0] ) ) {
				$settings->nav_menu->locations[] = (string) array_keys( get_nav_menu_locations() )[0];
			}$settings->stats = true;
			$settings->preferences = true;
			$settings->charts_style = 'line';
			$settings->caching = true;

			$settings->ajax = (object) array(
				'interval' => 5000,
				'autosave' => true,
				'preloader' => '<div class="wpc-loading"><p>' . wpc_translate('loading') . '<span>.</span></p></div>',
				'title_selector' => 'article .entry-title'
			);
			$settings->copy = true;

			if( $defaults ) { return $settings; }

			$GLOBALS['wpc_settings'] = apply_filters('wpc_settings', $settings);

			return apply_filters('wpc_settings', $settings);

		}

		function _wpc_footer_js_object() {

			$path_to_assets = WPC_URL . 'assets/';
			$path_to_child_assets = get_stylesheet_directory_uri() . '/' . 'assets/';
			$child_base = get_stylesheet_directory() . '/' . WPC_DIR_NAME . '/assets/';
			$path_to_assets = file_exists( $child_base ) ? $path_to_child_assets : $path_to_assets;
			$settings = wpc_settings();
			$current_user = wp_get_current_user();

			?>
			<script type="text/javascript">				
				/* <![CDATA[ */
				var wpc = {
					"admin": {
						"path": "<?php echo admin_url('/'); ?>",
						"ajax": "<?php echo admin_url('admin-ajax.php'); ?>"
					},
					"cur_user": {
						"name": "<?php echo _wpc_messages_validate_user_name_( wpc_get_user_name( $current_user->ID ) ); ?>",
						"nice_name": "<?php echo $current_user->user_nicename; ?>",
						"avatar": "<?php echo _wpc_avatar_src( $current_user->ID ); ?>",
						"link": "<?php echo $current_user->profile; ?>",
						"id": "<?php echo $current_user->ID; ?>"
					},
					"settings": {
						"ajax": {
							"autosave": "<?php echo $settings->ajax->autosave; ?>",
							"json_interval": "<?php echo $settings->ajax->interval; ?>",
							"dynamic_title_selector": "<?php echo $settings->ajax->title_selector; ?>",
							"time_update_interval": "<?php echo apply_filters('wpc_ajax_time_update_interval', 60000); ?>",
							"messages_count_before_title_tab": "(%d)", // (1) tab title
							"preloader": "<?php echo str_replace('"','\"',$settings->ajax->preloader); ?>",
							"path_to_users": "<?php echo admin_url('admin-ajax.php?action=wpc_get_users'); ?>",
							"disable_multiple_send": "<?php echo apply_filters( 'wpc_ajax_disable_multiple_send', true ); ?>"
						},
						"title": "<?php echo str_replace( '"', '\"', get_the_title( $settings->page ) ); ?>",
						"path_to_users": "<?php echo wpc_get_user_links()->users->all; ?>",
						"path_to_messages": "<?php echo wpc_get_user_links()->messages; ?>",
						"isTyping_allowed": "<?php echo $settings->isTyping_allowed ? '1' : '0'; ?>",
						"path_to_assets": "<?php echo $path_to_assets; ?>"
					},
					"conf": {
						"del_m": "<?php echo wpc_translate('Are you sure you want to delete this message? This can\'t be undone.'); ?>",
						"del_r": "<?php echo wpc_translate('Are you sure you want to delete this report?'); ?>",
						"ban_u": "<?php echo wpc_translate('Are you sure you want to ban this user?'); ?>",
						"unban_u": "<?php echo wpc_translate('Are you sure you want to unban this user?'); ?>",
						"del_c": "<?php echo wpc_translate('Are you sure you want to delete this conversation? This can\'t be undone.'); ?>",
						"block_u": "<?php echo wpc_translate('Are you sure you want to block this user?'); ?>",
						"fwd": "<?php echo wpc_translate('Are you sure you want to forward this message to this user?'); ?>",
						"add_mod": "<?php echo wpc_translate('Are you sure you want to add this user as moderator?'); ?>",
						"remove_mod": "<?php echo wpc_translate('Are you sure you want to remove this user as moderator?'); ?>",
						"submit_rep": "<?php echo wpc_translate('Are you sure you want to submit this report?'); ?>"
					},
					"feedback": {
						"err_general": "<?php echo wpc_translate('Error occured. Please try again'); ?>",
						"err_upload": "<?php echo wpc_translate('Error uploading image. Please verify your image extension and/or size and try again.'); ?>",
						"err_drag": "<?php echo wpc_translate('Error occured. Only images can be dragged into upload area.'); ?>",
						"err_sending": "<?php echo wpc_translate('Error occured, could not send message'); ?>",
						"empty_rep": "<?php echo wpc_translate('Please type out a report to submit.'); ?>",
						"err_load": "<?php echo wpc_translate('Could not load data. Please try again.'); ?>",
						"err_fwd": "<?php echo wpc_translate('Could not forward this message. Please try again.'); ?>",
						"remove_mod": "<?php echo wpc_translate('This user was successfully removed as moderator.'); ?>",
						"cant_mark_unread": "<?php echo wpc_translate('Sorry, you can not mark this conversation unread.'); ?>",
						"cover_rem": "<?php echo wpc_translate('Cover photo removed, now save your profile edit.'); ?>"
					},
					"translate": {
						"Forward message": "<?php echo wpc_translate('Forward message'); ?>",
						"loading": "<?php echo wpc_translate('loading'); ?>",
						"auto-saving ..": "<?php echo wpc_translate('auto-saving ..'); ?>",
						"auto-saved": "<?php echo wpc_translate('auto-saved'); ?>",
						"Your message is not auto-saved..": "<?php echo wpc_translate('Your message is not auto-saved..'); ?>",
						"New message": "<?php echo wpc_translate('New message'); ?>",
						"just now": "<?php echo wpc_translate('just now'); ?>",
						"load more": "<?php echo wpc_translate('load more'); ?>",
						"compose": "<?php echo wpc_translate('compose'); ?>",
						"or": "<?php echo wpc_translate('or'); ?>",
						"view conversation": "<?php echo wpc_translate('view conversation'); ?>",
						"Send message": "<?php echo wpc_translate('Send message'); ?>",
						"No users were found": "<?php echo wpc_translate('No users were found'); ?>",
						"previous page": "<?php echo wpc_translate('previous page'); ?>",
						"next page": "<?php echo wpc_translate('next page'); ?>",
						"page": "<?php echo wpc_translate('page'); ?>",
						"select image": "<?php echo wpc_translate('select image'); ?>",
						"New message from %s": "<?php echo wpc_translate('New message from %s'); ?>",
						"view": "<?php echo wpc_translate('view'); ?>",
						"%s ago": "<?php echo wpc_translate('%s ago'); ?>"
					},
					"bases": {
						<?php foreach( wpc_get_bases() as $name => $base ) { echo "\"$name\": \"$base\","; } ?>
					},
					"preferences": {
						"audio_notifications": "1" // add to notifications tab
					},
					counter: {
						set: function( data ) {
							if( "string" !== typeof WPCUCT ) {
								window.WPCUCT = '';
							}
							if( ! data.pm_id ) { return; }
							if( ! data.count ) { data.count = 1; }
							for( i = 1; i <= data.count; i++ ) {
								WPCUCT += data.pm_id + ',';
							}
							window.WPCUCT = WPCUCT;
						},
						get: function( data ) {
							if( "string" !== typeof WPCUCT ) { return 0; }
							if( ! data.pm_id ) { return 0; }
							var r = new RegExp(data.pm_id, 'g');
							return WPCUCT.match(r).length;
						},
						get_all: function() {
							if( "string" !== typeof WPCUCT ) { return 0; }
							count = 0;
							for( i in l = WPCUCT.split(',') ) {
								if( l[i] > '' ) count += 1;
							}
							return count;
						},
						unset: function( data ) {
							if( "string" !== typeof WPCUCT || ! data.pm_id ) { return; }
							var r = new RegExp(data.pm_id, 'g');
							window.WPCUCT = WPCUCT.replace(r,'');
						}
					},
					create_event: function(name, data) {
						if( "object" !== typeof data ) { data = []; }
						var e = document.createEvent('Event');
						e.initEvent(name, true, true);
						e.data = data;
						document.dispatchEvent(e);
					}
				}
			/* ]]> */
			</script>
			<?php
		}
		function wpc_get_user_links( $user_id = false ) {

			$from_global = "wpc_get_user_{$user_id}_links";
			eval( "global $$from_global;" );
			if ( ! empty( $$from_global ) ) {
				return $$from_global;
			}

			$wpc_get_bases = wpc_get_bases();
			$messages_base = wpc_messages_base();
			$home_url = home_url('/');
			$_profiles = $home_url . ( $wpc_get_bases->users . '/' );
			global $current_user;

			if( ! $user_id ) {

				if( ! is_user_logged_in() ) {

					$_links = (object) array(
						'block' => '',
						'unblock' => '',
						'message' => '',
						'profile' => '',
						'notifications' => '',
						'edit' => '',
						'users' => (object) array(
							'all' => $_profiles,
							'online' => $_profiles . 'online/',
							'blocked' => $_profiles . 'blocked/'				
						),
						'messages' => $messages_base,
						'archives' => $messages_base . ( $wpc_get_bases->archives ),
						'mod' => $home_url . ( $wpc_get_bases->mod . '/' )
					);
					$GLOBALS['wpc_get_user_' . $user_id . '_links'] = $_links;
					return $_links;
				}

				$user_id = wp_get_current_user()->ID;

			}

			if ( $user_id == $current_user->ID ) {
				global $wpc_get_current_user_links;
				if ( ! empty( $wpc_get_current_user_links ) ) {
					$GLOBALS['wpc_get_user_' . $user_id . '_links'] = $_links;
					return apply_filters( "wpc_get_user_links", $wpc_get_current_user_links, $user_id );
				}
			}

			$_profile = $_profiles . get_userdata( $user_id )->user_nicename . '/';
			$contact_link = wpc_contact_link($user_id);
			
			$links = array(
				'block' => $contact_link . '?do=block',
				'unblock' => $contact_link . '?do=unblock',
				'message' => $contact_link,
				'profile' => $_profile,
				'notifications' => $_profile . 'notifications/',
				'edit' => $_profile . 'edit/',
				'users' => (object) array(
					'all' => $_profiles,
					'online' => $_profiles . 'online/',
					'blocked' => $_profiles . 'blocked/'				
				),
				'messages' => $messages_base,
				'archives' => $messages_base . ( $wpc_get_bases->archives ),
				'mod' => $home_url . ( $wpc_get_bases->mod . '/' )
			);
			$links['profile_tabs'] = array();

			$tabs = wpc_registered_profile_tabs();
			if( ! empty( $tabs ) ) {
				foreach( $tabs as $tab ) {
					$links['profile_tabs'][$tab['name']] = $_profile . $tab['slug'] . '/';
				}
				$links['profile_tabs'] = (object) $links['profile_tabs'];
			}

			$links = apply_filters( "wpc_get_user_links", (object) $links, $user_id );
			$GLOBALS['wpc_get_user_' . $user_id . '_links'] = $links;

			return $links;

		}

		function _wpc_avatar_src( $user_id ) {
			$xpath = new DOMXPath(@DOMDocument::loadHTML( get_avatar( $user_id ) ));
			return $xpath->evaluate("string(//img/@src)");
		}
		function wpc_is_single_user() { return get_user_by( 'slug', get_query_var( 'wpc_user', wpc_get_query_var('wpc_user') ) ) > ''; }
		function wpc_is_404_user() { return get_query_var( 'wpc_user', wpc_get_query_var('wpc_user') ) > '' && ! wpc_is_single_user(); }
		function wpc_is_users() { return '1' == get_query_var( 'wpc_users', wpc_get_query_var('wpc_users') ) || wpc_is_single_user(); }
		function wpc_is_archive_users() { return '1' == get_query_var( 'wpc_users', wpc_get_query_var('wpc_users') ) && ! wpc_is_archive_blocked_users() && ! wpc_is_archive_online_users(); }
		function wpc_is_archive_blocked_users() { return '1' == get_query_var( 'wpc_blocked_users', wpc_get_query_var('wpc_blocked_users') ); }
		function wpc_is_archive_online_users() { return '1' == get_query_var( 'wpc_online_users', wpc_get_query_var('wpc_online_users') ); }
		function wpc_get_displayed_user() { return WPC_users::instance()->get_displayed_user(); }
		function wpc_get_displayed_user_id() { return isset( wpc_get_displayed_user()->ID ) && (int) wpc_get_displayed_user()->ID > 0 ? wpc_get_displayed_user()->ID : false; }
		function wpc_get_users($return_all = false) { return WPC_users::instance()->get( $return_all ); }
		function wpc_is_my_profile() { return wpc_is_single_user() && wpc_get_displayed_user_id() == wp_get_current_user()->ID; }
		function wpc_is_single_user_view() {
			$bool = wpc_is_single_user() && ! wpc_is_user_notifications() && ! wpc_is_user_edit() && ! wpc_is_custom_profile_tab();
			return apply_filters( 'wpc_is_single_user_view', $bool );
		}

		function wpc_get_user_short_bio( $user_id, $lenght = false ) {

			if( ! $lenght )
				$lenght = 150;

			$meta = wpc_get_user_bio( $user_id );

			if( $meta ) {
				$after = strlen( $meta ) > $lenght;
				$meta = substr( $meta, 0, $lenght );
				$meta .= $after ? ' ...' : '';
			}

			$meta = wpc_fetch_emoji( $meta );

			return $meta;

		}

		function wpc_get_user_bio( $user_id, $parseMarkup = false ) {

			$meta = wpc_get_user_field( 'bio', $user_id );

			if( $parseMarkup ) { $meta = wpc_output_message( $meta ); }

			return $meta > '' ? $meta : false;
		}
		function wpc_get_user_activity( $user_id = false, $before = false, $after = false ) {

			if( ! $user_id )
				$user_id = wp_get_current_user()->ID;

			$_a = wpc_get_user_last_seen( $user_id, true );

			if( ! $before )
				$before = $_a->before;

			if( ! $after )
				$after = $_a->after;

			if( wpc_is_online( $user_id ) ) {
				return '<span class="wpc-time-int user-'. $user_id .'" data-int="" data-before="' . $before . '" data-after="' . $after . '">' . apply_filters( 'wpc_user_activity_online_now', wpc_translate('online now') ) . '</span>';
			} else {
				if ( /*false !== */$diff = wpc_get_user_last_seen( $user_id, 1 )->difference )
					return '<span class="wpc-time-int user-'. $user_id .'" data-int="' . $_a->integer . '" data-before="' . $before . '" data-after="' . $after . '">' . sprintf( wpc_translate("Online %s ago"), $diff ) . '</span>';
				else
					return '<span class="wpc-time-int user-'. $user_id .'" data-int="' . $_a->integer . '" data-before="' . $before . '" data-after="' . $after . '">' . sprintf( wpc_translate("Offline"), $diff ) . '</span>';
			}

		}

		function wpc_is_user_edit() { return "1" == get_query_var( 'wpc_edit_user', wpc_get_query_var('wpc_edit_user') ); }
		function wpc_is_user_notifications() { return "1" == get_query_var( 'wpc_user_notifications', wpc_get_query_var('wpc_user_notifications') ); }

		function wpc_get_user_field( $field, $user_id = false, $default = false ) {

			if( ! $user_id )
				$user_id = wp_get_current_user()->ID;

			$meta = get_user_meta( $user_id, "_wpc_info_$field", TRUE );

			if( $meta > '' )
				return $meta;
			
			return $default;

		}

		function wpc_get_user_social( $user_id = false ) {

			if( ! $user_id )
				$user_id = wpc_get_displayed_user_id();

			$data = array();

			$list = wpc_social_list();

			foreach( $list as $field => $name ) {

				if( wpc_get_user_field( $field, $user_id ) ) {

					$data[] = array(
						'name' => $name,
						'value' => wpc_get_user_field( $field, $user_id )
					);

				}

			}

			return apply_filters( "wpc_get_user_social", $data, $user_id );

		}

		function wpc_social_list() {
			
			$_social = array( 
				'twitter' => wpc_translate('Twitter'),
				'facebook' => wpc_translate('Facebook'),
				'google' => wpc_translate('Google+'),
				'linkedin' => wpc_translate('Linkedin'),
				'website' => wpc_translate('Website')
			);

			return apply_filters( 'wpc_social_list', $_social );

		}

		function wpc_get_user_name( $user_id = false, $shorter = false ) {

			if( ! $user_id )
				$user_id = wpc_get_displayed_user_id();

			$meta = wpc_get_user_field( 'name', $user_id );

			if( $shorter )
				$meta = _wpc_messages_validate_user_name_($meta);

			if( ! get_userdata( $user_id ) )
				return wpc_translate('Anonymous');

			return $meta > '' ? $meta : get_userdata( $user_id )->user_nicename;

		}

		function wpc_get_query_var( $param, $default = '' ) {
			return isset( $_GET[ $param ] ) ? $_GET[ $param ] : $default;
		}

		function wpc_title($json = false) {

			global $wpc_title;

			if ( ! empty( $wpc_title ) ) {
				return $wpc_title;
			}

			$title = wpc_settings()->page_title;

			if( wpc_is_messages() ) {
				
				if( wpc_is_single_conversation() ) {

					if( wpc_is_report_message() ) {
						
						if( wpc_get_message_to_report() ) {
							$title = wpc_translate('Report message') . ' &rsaquo; ' . wpc_get_user_name( wpc_get_recipient_id() );
						} else {
							$title = wpc_translate('Report conversation') . ' &rsaquo; ' . wpc_get_user_name( wpc_get_recipient_id() );
						}

						$title = apply_filters( "wpc_title_report_messages", $title );

					} elseif ( wpc_is_mute_conversation() ) {
						$title = wpc_get_user_name( wpc_get_recipient_id() ) . ' &rsaquo; ' . wpc_translate('Mute conversation');
						$title = apply_filters( "wpc_title_mute_conversation", $title );
					} else {

						$title = wpc_translate('Messages') . ' &rsaquo; ' . wpc_get_user_name( wpc_get_recipient_id() );

						if( wpc_is_search() ) {
							$title = wpc_translate('Search results for') . ' "' . wpc_get_search_query() . '"';
							$title = apply_filters( "wpc_title_search_messages", $title );
						}

						if( wpc_is_forward_message() ) {
							$title = wpc_translate('Forward message');
							if( wpc_is_search() ) {
								$title .= ' | ' . wpc_translate('Search results for') . ' "' . wpc_get_search_query() . '"';
							}
							$title = apply_filters( "wpc_title_forward_message", $title );
						}

						$title = apply_filters( "wpc_title_conversation", $title, wpc_get_user_name( wpc_get_recipient_id() ) );
					
					}

				} else {

					if( wpc_is_messages() || wpc_is_archives() ) {

						$title = wpc_translate('Messages');

						if( wpc_is_new_message() ) {
							$title .= " &rsaquo; " . wpc_translate('compose');
							$title = apply_filters( "wpc_title_compose_message", $title );
						}

						if( wpc_is_archives() ) {
							$title = wpc_translate('Messages') . ' &rsaquo; ' . wpc_translate('archives');
							$title = apply_filters( "wpc_title_archives", $title );
						}

						if( wpc_is_search() ) {
							$title = wpc_translate('Search results for') . ' "' . wpc_get_search_query() . '"';
							$title = apply_filters( "wpc_title_search_messages", $title );
						}

						$title = apply_filters( "wpc_title_search_conversations", $title );

					}

				}

				$title = apply_filters( "wpc_title_messages", $title );

			}

			elseif( wpc_is_users() ) {

				if( wpc_is_single_user() ) {

					if( wpc_is_user_edit() ) {

						$title = wpc_get_user_name( wpc_get_displayed_user_id() ) . ' &rsaquo; ' . wpc_translate('Edit profile');
						$title = apply_filters( "wpc_title_user_edit_profile", $title );

					} elseif( wpc_is_user_notifications() ) {
						$title = wpc_get_displayed_user_id() == wp_get_current_user()->ID ? wpc_translate('My notifications') : wpc_get_user_name( wpc_get_displayed_user_id() ) . ' &rsaquo; ' . wpc_translate('Notifications');
						$title = apply_filters( "wpc_title_user_notifications", $title );
					} else {

						if( wpc_get_displayed_user_id() == wp_get_current_user()->ID ) {
							$title = wpc_translate('My profile');
							$title = apply_filters( "wpc_title_my_profile", $title );
						} else {
							$title = str_replace( '[user]', wpc_get_user_name( wpc_get_displayed_user_id() ), wpc_translate('[user] &rsaquo; profile') );
							$title = apply_filters( "wpc_title_user", $title, wpc_get_user_name( wpc_get_displayed_user_id() ) );
						}
					
					}

				} else {

					if( wpc_is_archive_users() ) {
						$title = wpc_translate('Users');
						$title = apply_filters( "wpc_title_users_all", $title );
					}

					elseif( wpc_is_archive_blocked_users() ) {
						$title = wpc_translate('Users') . ' &rsaquo; ' . wpc_translate('blocked');
						$title = apply_filters( "wpc_title_users_blocked", $title );
					}

					elseif( wpc_is_archive_online_users() ) {
						$title = wpc_translate('Users') . ' &rsaquo; ' . wpc_translate('online now');
						$title = apply_filters( "wpc_title_users_online", $title );
					}

					if( wpc_is_search() ) {
						$title = wpc_translate('Search results for') . ' "' . wpc_get_search_query() . '"';
						$title = apply_filters( "wpc_title_users_search", $title );
					}

					if( wpc_is_404_user() ) {
						$title = wpc_translate('404 - User not found');
						$title = apply_filters( "wpc_title_users_404", $title );
					}

					if( get_query_var( 'wpc_paged', wpc_get_query_var( 'wpc_paged', false ) ) > '' ) {
						$title .= ' | ' . wpc_translate('page') . ' ' .   get_query_var( 'wpc_paged', wpc_get_query_var( 'wpc_paged', 1 ) );
					}

				}

				$title = apply_filters( "wpc_title_users", $title );

			}

			elseif ( wpc_is_moderation() ) {
				$title = wpc_translate('Moderation panel');
				
				if( wpc_is_moderation_moderators() ) {
					$title .= ' &rsaquo; ' . wpc_translate('moderators');
					if( '1' == get_query_var( 'wpc_add_mods', wpc_get_query_var( 'wpc_add_mods', 0 ) ) )
						$title .= ' &rsaquo; ' . wpc_translate('add new');
					$title = apply_filters( "wpc_title_moderation_moderators", $title );
				} elseif( wpc_is_mod_watch_messages() ) {
					$title .= ' &rsaquo; ' . wpc_translate('conversations');
					$title = apply_filters( "wpc_title_moderation_watch_conversation", $title );
				} elseif( wpc_is_mod_banned_users() ) {
					$title .= ' &rsaquo; ' . wpc_translate('banned users');
					$title = apply_filters( "wpc_title_moderation_banned", $title );					
				}
				$title = apply_filters( "wpc_title_moderation", $title );
			}

			if( $json ) $title = str_replace( '"', '&amp;quot;', $title );

			$title = apply_filters( 'wpc_title', $title );

			$GLOBALS['wpc_title'] = $title;

			return $title;

		}

		function wpc_is_search() {
			return wpc_get_search_query() > '';
		}

		function wpc_get_users_search_form( $value = false, $after = '' ) {

			$_placeholder = wpc_translate('search');

			$task = '{"action": "' . admin_url('admin-ajax.php?action=wpc&wpc_users=1');

			if ( wpc_is_archive_users() ) {

				$_action = wpc_get_user_links()->users->all;
				$_filter = 'all';
				$_placeholder .= ' ' . wpc_translate('users');

			}
			elseif ( wpc_is_archive_blocked_users() ) {

				$task .= '&wpc_blocked_users=1';

				$_action = wpc_get_user_links()->users->blocked;
				$_filter = 'blocked';
				$_placeholder .= ' ' . wpc_translate('blocked users');
				

			}
			elseif ( wpc_is_archive_online_users() ) {

				$task .= '&wpc_online_users=1';

				$_action = wpc_get_user_links()->users->online;
				$_filter = 'online';
				$_placeholder .= ' ' . wpc_translate('online users');

			}

			$task .= '"}';

			if( ! $value )
				$value = stripslashes(wpc_get_search_query());

			?>
				<form method="get" action="<?php echo isset( $_action ) ? $_action : ''; ?>" class="wpcajx2" data-task="<?php echo wpc_quote_url($task); ?>">
					<input type="text" name="q" placeholder="<?php echo $_placeholder; ?>" value="<?php echo $value; ?>" />
					<?php if( $after ) { echo $after; } ?>
				</form>
			<?php

		}

		function wpc_get_stats( $user_id = 0 ) {

			global $current_user;
			
			if( ! $user_id && is_user_logged_in() ) {
				$user_id = $current_user->ID;
			}

			if ( $user_id == $current_user->ID ) {
				global $wpc_get_current_user_stats;
				if ( ! empty( $wpc_get_current_user_stats ) ) {
					return $wpc_get_current_user_stats;
				}
			}

			$_online_count = 0;
			$_blocked_count = count( wpc_get_user_blocked_list( $user_id ) );
			$unreads = (array) wpc_user_unreads_noajax( $user_id );

			foreach( _wpc_get_users() as $user ) {
				$_online_count += wpc_is_online( $user->ID ) ? 1 : 0;
			}

			//$reports = wpc_get_reports(); // can be a heavy load so.

			$counts = array(
				'users' => (object) array(
					'online' => $_online_count,
					'blocked' => $_blocked_count
				),
				'reports' => (int) get_option("_wpc_reports_count"), //abs( count( $reports->messages ) + count( $reports->conversations ) ),
				'unread' => (int) count( $unreads )
			);

			$unread_conversations = array();

			foreach( $unreads as $m ) {
				if( is_array( $m ) && isset( $m[0]['pm_id'] ) )
					$unread_conversations[] = $m[0]['pm_id'];
				elseif( isset( $m['pm_id'] ) )
					$unread_conversations[] = $m['pm_id'];
			}

			foreach( $unread_conversations as $i => $m ) {
				$m = wpc_get_conversation( $m );
				if( empty( $m ) || empty( $m->last_message ) || $user_id == $m->last_message->sender || ! empty( $m->last_message->seen ) ) {
					unset( $unread_conversations[$i] );
					continue;
				}
			}

			$unread_conversations_unique = array_filter( array_unique( $unread_conversations ) ); 

			$counts['unread_conversations'] = (object) array(
				'count' => count( $unread_conversations_unique ),
				'contents' => (object) $unread_conversations_unique,
				'dup_contents' => (object) $unread_conversations
			);
			$notifications = wpc_get_user_notifications($user_id, 'unread');
			$counts['notifications'] = (object) array(
				'count' => (int) count( wpc_get_user_notifications($user_id) ),
				'unread' => (int) count( $notifications )
			);

			$counts = apply_filters( "wpc_get_stats", (object) $counts, $user_id );

			if ( $user_id == $current_user->ID ) {
				$GLOBALS['wpc_get_current_user_stats'] = $counts;
			}

			return $counts;

		}

		function wpc_unreads_in_pm( $pm_id, $user_id = 0 ) {

			if ( ! $user_id ) {
				$user_id = wp_get_current_user()->ID;
			}

			if ( ! $user_id || ! $pm_id ) {
				return;
			}

			$unreads = (array) wpc_user_unreads_noajax( $user_id );

			foreach( $unreads as $i => $data ) {
				if( empty( $data['pm_id'] ) || $data['pm_id'] !== $pm_id ) {
					unset( $unreads[$i] );
				}
			}

			return $unreads;

		}

		function wpc_user_snippet_classes( $user_id ) {
			
			$classes = 'wpc-u-snippet';
			$classes .= wpc_is_online( $user_id ) ? ' online' : ' offline';
			$classes .= wpc_is_user_blocked( $user_id ) ? ' blocked' : '';
			if( wp_get_current_user()->ID !== $user_id )
				$classes .= wpc_can_contact( $user_id ) ? ' can-contact' : ' cant-contact';
			$classes .= wp_get_current_user()->ID == $user_id ? ' me' : '';
			$classes .= ' user-' . $user_id;

			echo $classes;

		}

		function wpc_is_report_message() {
			return '1' == get_query_var( 'wpc_report', wpc_get_query_var( 'wpc_report', '' ) );
		}

		function wpc_is_forward_message() {
			return (int) get_query_var( 'wpc_forward_message', wpc_get_query_var( 'wpc_forward_message', '' ) ) > 0;
		}		

		function wpc_get_message_to_report() {
			return WPC_message::instance()->message_to_report();
		}

		function wpc_get_report( $message_id = false, $pm_id = false, $user_id = false ) {

			$meta = false;

			if ( ! $user_id )
				$user_id = wp_get_current_user()->ID;

			if ( $pm_id ) {
				$meta = get_option( '_wpc_report_' . $pm_id . '_' . $user_id );				
			}

			$message_to_report = wpc_get_message_to_report();

			if ( ! $message_id && ! empty( $message_to_report ) )
				$message_id = $message_to_report->ID;

			if ( $message_id && ! $meta ) {
				$meta = get_option( '_wpc_report_' . $message_id . '_' . $user_id );
			}

			if( $meta > '' ) {
				$meta = html_entity_decode( stripslashes( $meta ) );
				return $meta;
			}

			return false;

		}

		function wpc_get_report_meta( $message_id = false, $pm_id = false, $user_id = false ) {

			if ( ! $user_id )
				$user_id = wp_get_current_user()->ID;

			if( $message_id ) {

				$meta = get_option( '_wpc_report_' . $message_id . '_' . $user_id . '_meta' );

				$meta = $meta > '' ? explode( '_', $meta ) : false;
				$meta_last = end( $meta );
				return (object) array(
					'created' => $meta ? (int) $meta[0] : false,
					'last_update' => $meta && ! empty( $meta_last ) ? (int) $meta_last : false
				);

			}

			elseif( $pm_id ) {

				$meta = get_option( '_wpc_report_' . $pm_id . '_' . $user_id . '_meta' );

				$meta = $meta > '' ? explode( '_', $meta ) : false;
				$meta_last = end( $meta );

				return (object) array(
					'created' => $meta ? (int) $meta[0] : false,
					'last_update' => $meta && ! empty( $meta_last ) ? (int) $meta_last : false
				);

			}

		}

		function wpc_get_reports( $user_id = 0 ) { return WPC_message::instance()->get_reports( $user_id ); }
		function wpc_is_moderation() { return '1' == get_query_var( 'wpc_mod', wpc_get_query_var( 'wpc_mod', 0 ) ); }
		function wpc_is_moderation_moderators() { return '1' == get_query_var( 'wpc_mods', wpc_get_query_var( 'wpc_mods', 0 ) ); }
		
		function wpc_link_to_user_profile( $user_id ) {
			?>
				<a href="<?php echo wpc_get_user_links( $user_id )->profile; ?>" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "'.admin_url('admin-ajax.php?action=wpc&wpc_users=1&wpc_user=').get_userdata( $user_id )->user_nicename.'"}'); ?>"><?php echo wpc_get_user_name( $user_id ); ?></a>
			<?php
		}

		function wpc_time_int_span( $int, $before = '', $after = '', $get = false ) {

			$link = '<span class="wpc-time-int" data-int="[int]" data-before="[before]" data-after="[after]">[diff]</span>';

			$link = str_replace(
				array( '[int]', '[before]', '[after]', '[diff]' ),
				array( $int, $before, $after, wpc_time_diff( $int, $before, $after ) ),
				$link
			);

			if( $get ) return $link;

			echo $link;

		}

		function wpc_banned_list() {
			return array(); // PRO feature
		}

		function wpc_is_banned( $user_id ) {
			return false; // PRO feature
		}

		function wpc_is_mod( $user_id = false ) {return current_user_can("manage_options"); /*PRO feature*/}

		/**
		  * Returns a list of site moderators (their user IDs)
		  * Can be used to add/remove one or more moderators
		  *
		  * @since 3.0
		  * @param $args array arguments for adding/removing moderator(s)
		  * @return array list of moderators
		  */

		function wpc_moderators_list( $args = array(), $include_admins = false ) {
			return array(); // pro feature
		}

		function _wpc_messages_validate_user_name_( $str ) {
			$original_str = $str; 
			if( strpos( $str, ' ' ) > 0 )
				$str = substr( $str, 0, strpos( $str, ' ' ) );
			if( strlen( $str ) > 10 ) {
				$str = substr( $str, 0, 10 );
				$str .= '..';
			}
			return apply_filters( '_wpc_messages_validate_user_name_', $str, $original_str );
		}

		/**
		  * Returns an array of chat emoticons along with their associated meta
		  *
		  * @since 3.0
		  * @param $return_elements bool (optional) for returning emoticons meta
		  * @return array emoticons list
		  */

		function wpc_emoji_list( $return_elements = false ) {

			$list = array();

			$descriptions = explode( ',', 'smiley face,big smile,frown,cry,tongue out,angel,angel,confused,wink,surprised,squinting,angry,kiss,heart,kiki,glasses,sunglasses,grumpy,pacman,unsure,curly lips,thumb up,blush,disappointed,gift heart,heart smiley,mocking,sad,smiling face,triumph,alien symbol,cold sweat,dizzy,happy blushing,kiss,purple devil,satisfied,smirking,unamused,astonished,cry,eyes wide open,mad,red angry,scared,tears of joy,wink,cry tears,fear,heart eyes,medic,relieved,sleepy,terrified,wink' );

			$urls = explode( ',', 'smiley-face.png,big-smile.png,frown.png,cry.png,tongue-out.png,angel.png,devil.png,confused.png,wink.png,surprised.png,squinting.png,angry.png,kiss.png,heart.png,kiki.png,glasses.png,sunglasses.png,grumpy.png,pacman.png,unsure.png,curly-lips.png,thumb-up.png,blush.png,disappointed.png,gift-heart.png,heart-smiley.png,mocking.png,sad.png,smiling-face.png,triumph.png,alien-symbol.png,cold-sweat.png,dizzy.png,happy-blushing.png,kiss-2.png,purple-devil.png,satisfied.png,smirking.png,unamused.png,astonished.png,cry-2.png,eyes-wide-open.png,mad.png,red-angry.png,scared.png,tears-of-joy.png,wink-2.png,cry-tears.png,fear.png,heart-eyes.png,medic.png,relieved.png,sleepy.png,terrified.png,wink-3.png' );

			$symbols = explode( ',', ':),:D,:(,:"(,:P,O-),3-),o.O,;),:O,-_-,}_{,:*,{3,^_^,8-),8|,}-(,:v,:-/,:3,(y),:blush:,:disappointed:,:gift-heart:,:heart-smiley:,:mocking:,:sad:,:smiling-face:,:triumph:,:alien-symbol:,:cold-sweat:,:dizzy:,:happy-blushing:,:kiss-2:,:purple-devil:,:satisfied:,:smirking:,:unamused:,:astonished:,:cry-2:,:eyes-wide-open:,:mad:,:red-angry:,:scared:,:tears-of-joy:,:wink-2:,:cry-tears:,:fear:,:heart-eyes:,:medic:,:relieved:,:sleepy:,:terrified:,:wink-3:' );

			foreach( $descriptions as $key => $desc ) { // translating the core emoji descriptions
				unset($descriptions[$key]);
				$descriptions[$key] = wpc_translate($desc);
			}

			foreach( $urls as $i => $url )
				$urls[$i] = WPC_URL . 'assets/emoji/' . $url;

			foreach( $symbols as $i => $symbol )
				$list[$i] = (object) array(
					'url' => $urls[$i],
					'symbol' => $symbol,
					'description' => $descriptions[$i]
				);

			$list = apply_filters( 'wpc_emoji_list', $list );

			if( $return_elements ) {
				
				$elements = array();

				foreach( $list as $i => $emo ) {
					$elements['descriptions'][$i] = $emo->description;
					$elements['urls'][$i] = $emo->url;
					$elements['symbols'][$i] = $emo->symbol;
					$elements['strings'][$i] = '<img src="'.$emo->url.'" alt="'.$emo->description.'" title="'.$emo->symbol.'" class="wpc-emoticon" />';
				}

				return (object) $elements;

			}

			return $list;

		}

		/**
		  * Fetches a string and replaces emoticon symbols found with their emoticon images
		  *
		  * @since 3.0
		  * @param $string str the string to filter
		  * @return str filtered string
		  */

		function wpc_fetch_emoji( $string ) {
			$emo_list = wpc_emoji_list(true);
			return str_replace( $emo_list->symbols, $emo_list->strings, $string );
		}

		/**
		  * Uploads a file and returns the upload URL
		  *
		  * @since 3.0
		  * @param $file array file data
		  * @return str file url | false
		  */

		function wpc_upload_user_file( $file = array(), $doing = false ) {
			return false; // PRO feature
		}

		function wpc_conversation_autosave($pm_id = false) {

			if( ! $pm_id ) {
				$pm_id = wpc_get_conversation_id();
			}

			$message = '';

			$auto_save = get_option( '_wpc_autosave_' . $pm_id . '_' . wp_get_current_user()->ID );

			if( $auto_save > '' ) {
				$message = html_entity_decode( stripcslashes( $auto_save ) );
			}

			if( wpc_get_recipient_id() && ! wpc_can_contact() ) {
				$message = '';
			}

			$message = str_replace( array( '&_lt;', '&_gt;' ), array( '<', '>' ), $message );

			return apply_filters( 'wpc_conversation_autosave', $message, $pm_id );

		}

		function wpc_is_part_of_pm( $pm_id, $user_id ) {

			$pm = wpc_get_conversation( (int) $pm_id );

			if( empty( $pm ) )
				return false;

			if( empty( $pm->last_message ) )
				return false;

			if( ! get_userdata( $user_id ) )
				return false;

			return $pm->last_message->sender == (int) $user_id || $pm->last_message->recipient == (int) $user_id;

		}

		function wpc_paginate( $array = array(), $per_page = 10, $return_args = false, $shuffle = false, $tag = 'wpc_paged' ) {

			$current = (int) get_query_var($tag, wpc_get_query_var($tag, false));
			$current = $current <= 0 ? 0 : abs($current - 1);

			$last_page = abs( count( $array ) / $per_page );
			if( is_float( $last_page ) )
				$last_page = abs(  (int) $last_page + 1 );

			if( $current > abs( $last_page - 1 ) )
				$current = abs( $last_page - 1 );

			$offset = abs($per_page * $current);

			$next = abs( $current + 2 ) <= $last_page ? abs( $current + 2 ) : false;
			$previous = $current > 0 ? abs( $current ) : false;

			$current_page = abs( $current + 1 );

			if( $return_args )
				return (object) array(
					'available' => $last_page > 1,
					'offset' => $offset,
					'current' => $current,
					'previous' => $previous,
					'current_page' => $current_page,
					'next' => $next,
					'last_page' => $last_page
				);

			$array = array_slice( $array, $offset, $per_page );

			if( $shuffle ) {
				shuffle($array);
			}

			return $array;

		}

		function wpc_users_pagination() {

			if( count( wpc_get_users(true) ) <= count( wpc_get_users() ) )
				return;

			$pagi_data = wpc_paginate( wpc_get_users(true), wpc_settings()->pagination->users, true );
			$user_links = wpc_get_user_links();

			$current_page = $pagi_data->current + 1;
			$last_page = $pagi_data->last_page;
			$href = $user_links->users->all;
			$addi_tars = 'data-action="paginate-users" data-users="';

			if ( wpc_is_archive_users() ) {
				$href = $user_links->users->all;
				$addi_tars .= 'all';
			}
			if ( wpc_is_archive_blocked_users() ) {
				$href = $user_links->users->blocked;
				$addi_tars .= 'blocked';
			}
			elseif ( wpc_is_archive_online_users() ) {
				$href = $user_links->users->online;
				$addi_tars .= 'online';
			}

			$addi_tars .= '" data-q="';

			$href .= 'page/';
			$after = '/';

			if( wpc_get_search_query() > '' ) {
				$after .= '?q=' . str_replace( '"', '&quot;', wpc_get_search_query() );
				$addi_tars .= str_replace( '"', '&quot;', wpc_get_search_query() );
			}
			
			$addi_tars .= '" data-sort="';

			if( isset( $_GET['sort'] ) ) {
				$addi_tars .= (string) $_GET['sort'];
				$after .= wpc_get_search_query() > '' ? '&sort=' : '?sort=';
				$after .= (string) $_GET['sort'];
			}

			$addi_tars .= '"';
			
			$html = abs( $current_page - 1 ) > 0 ? '<a href="' . $href . abs( $current_page - 1 ) . $after . '" class="wpcajx" ' . $addi_tars . ' data-page="' . abs( $current_page - 1 ) . '" title="' . wpc_translate('page') . ' ' . abs( $current_page - 1 ) . '">' . abs( $current_page - 1 ) . '</a> ' : '';
			$html .= '<span class="current">' . $current_page . '</span>';
			$html .= abs( $current_page + 1 ) <= $last_page ? ' <a href="' . $href . abs( $current_page + 1 ) . $after . '" class="wpcajx" ' . $addi_tars . ' data-page="' . abs( $current_page + 1 ) . '" title="' . wpc_translate('page') . ' ' . abs( $current_page + 1 ) . '">' . abs( $current_page + 1 ) . '</a>' : '';

			if( 1 == $current_page && 3 < $last_page ) {
				$html .= ' <a href="' . $href . '3' . $after . '" class="wpcajx" ' . $addi_tars . ' data-page="3" title="' . wpc_translate('page') . ' 3">3</a> ';
			}

			if( $current_page >= 3 ) {
				if( $current_page > 3 )	
					$html = '<a href="' . $href . '1' . $after . '" class="wpcajx" ' . $addi_tars . ' data-page="1" title="' . wpc_translate('page') . ' 1">1</a> <span class="dots">...</span> ' . $html;
				else
					$html = '<a href="' . $href . '1' . $after . '" class="wpcajx" ' . $addi_tars . ' data-page="1" title="' . wpc_translate('page') . ' 1">1</a> ' . $html;
			}

			if( abs( $current_page + 2 ) <= $last_page ) {

				if( abs( $current_page + 2 ) == $last_page || ( 3 == abs( $current_page + 2 ) && 4 == $last_page ) )
					$html .= ' <a href="' . $href . $last_page . $after . '" class="wpcajx" ' . $addi_tars . ' data-page="' . $last_page . '" title="' . wpc_translate('page') . ' ' . $last_page . '">' . $last_page . '</a>';
				else
					$html .= ' <span class="dots">...</span> <a href="' . $href . $last_page . $after . '" class="wpcajx" ' . $addi_tars . ' data-page="' . $last_page . '" title="' . wpc_translate('page') . ' ' . $last_page . '">' . $last_page . '</a>';

			}

			$html = '<div class="wpc-pagination users">' .  $html . '</div>';

			echo $html;

		}

		function wpc_conversations_pagination() {

			if( count( wpc_my_conversations(true) ) <= count( wpc_my_conversations() ) )
				return;

			$pagi_data = wpc_paginate( wpc_my_conversations(true), wpc_settings()->pagination->conversations, true );

			$current_page = $pagi_data->current + 1;
			$last_page = $pagi_data->last_page;
			$href = wpc_get_user_links()->messages;
			#$addi_tars = 'data-action="paginate-conversations" data-messages="';

			#$addi_tars .= '" data-q="';
			$href .= wpc_is_archives() ? wpc_get_bases()->archives . '/' : '';
			$href .= 'page/';
			$after = '/';
			$dataTask = '{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1');
			$dataTask .= wpc_is_archives() ? '&wpc_archives=1' : '';

			if( wpc_get_search_query() > '' ) {
				$after .= '?q=' . str_replace( '"', '&quot;', wpc_get_search_query() );
				#$addi_tars .= str_replace( '"', '&quot;', wpc_get_search_query() );
				$dataTask .= "&q=" . str_replace( '"', '&quot;', wpc_get_search_query() );
			}

			$dataTask .= '&wpc_paged=[af]"}';
			$dataTask = 'class="wpcajx2" data-task="' . wpc_quote_url($dataTask) . '"';
			
			#$addi_tars .= '" data-sort=""';

			$html = abs( $current_page - 1 ) > 0 ? '<a href="' . $href . abs( $current_page - 1 ) . $after . '" ' . str_replace('[af]', abs( $current_page - 1 ), $dataTask) . ' title="' . wpc_translate('page') . ' ' . abs( $current_page - 1 ) . '">' . abs( $current_page - 1 ) . '</a> ' : '';
			$html .= '<span class="current">' . $current_page . '</span>';
			$html .= abs( $current_page + 1 ) <= $last_page ? ' <a href="' . $href . abs( $current_page + 1 ) . $after . '" ' . str_replace('[af]', abs( $current_page + 1 ), $dataTask) . ' title="' . wpc_translate('page') . ' ' . abs( $current_page + 1 ) . '">' . abs( $current_page + 1 ) . '</a>' : '';

			if( 1 == $current_page && 3 < $last_page ) {
				$html .= ' <a href="' . $href . '3' . $after . '" ' . str_replace('[af]', 3, $dataTask) . ' title="' . wpc_translate('page') . ' 3">3</a> ';
			}

			if( $current_page >= 3 ) {
				if( $current_page > 3 )	
					$html = '<a href="' . $href . '1' . $after . '" ' . str_replace('[af]', 1, $dataTask) . ' title="' . wpc_translate('page') . ' 1">1</a> <span class="dots">...</span> ' . $html;
				else
					$html = '<a href="' . $href . '1' . $after . '" ' . str_replace('[af]', 1, $dataTask) . ' title="' . wpc_translate('page') . ' 1">1</a> ' . $html;
			}

			if( abs( $current_page + 2 ) <= $last_page ) {

				if( abs( $current_page + 2 ) == $last_page || ( 3 == abs( $current_page + 2 ) && 4 == $last_page ) )
					$html .= ' <a href="' . $href . $last_page . $after . '" ' . str_replace('[af]', $last_page, $dataTask) . ' title="' . wpc_translate('page') . ' ' . $last_page . '">' . $last_page . '</a>';
				else
					$html .= ' <span class="dots">...</span> <a href="' . $href . $last_page . $after . '" ' . str_replace('[af]', $last_page, $dataTask) . ' title="' . wpc_translate('page') . ' ' . $last_page . '">' . $last_page . '</a>';

			}

			$html = '<div class="wpc-pagination messages">' .  $html . '</div>';

			echo $html;

		}

		function wpc_messages_pagination() {

			$pagi_data = wpc_messages_pagi();

			if( ! $pagi_data->next )
				return;

			$href = wpc_get_conversation_permalink( 'page/' );
			$href .= $pagi_data->next . '/';

			if( wpc_get_search_query() > '' )
				$href .= '?q=' . wpc_get_search_query();

			$html = '<div class="wpc-load-more">';
			$html .= '<a href="' . $href . '" class="wpcajx" data-action="paginate-messages" data-slug="' . wpc_get_recipient()->user_nicename . '" data-page="' . $pagi_data->next . '" data-q="' . str_replace( '"', '&quot;', wpc_get_search_query() ) . '" data-last="' . $pagi_data->last . '">' . wpc_translate('next page') . '</a>';
			$html .= '</div>';

			echo apply_filters( 'wpc_messages_pagination', $html );

		}

		function wpc_users_sorting_menu() {

			$sort = isset( $_GET['sort'] ) ? (string) $_GET['sort'] : false;
			$users = '';

			if ( wpc_is_archive_users() )
				$users = 'all';
			if ( wpc_is_archive_blocked_users() )
				$users = 'blocked';
			elseif ( wpc_is_archive_online_users() )
				$users = 'online';

			?>

				<form method="get" onchange="this.submit()" class="wpcajx" data-action="sort-users" data-users="<?php echo $users; ?>" id="wpc-u-sort">

					<select class="wpc-u-sort" name="sort">
						<option value="">sort by</option>
						<option value="activity"<?php echo "activity" == $sort ? ' selected="selected"' : ''; ?>><?php echo wpc_translate('activity'); ?></option>
					</select>

					<?php if( wpc_get_search_query() > '' ) : ?>
						<input type="hidden" name="q" value="<?php echo str_replace( '"', '&quot;', wpc_get_search_query() ); ?>" />
					<?php endif; ?>

				</form>

			<?php

		}

		function wpc_is_admin_ajax() {
			global $wpc_is_admin_ajax;

			if ( isset( $wpc_is_admin_ajax ) ) {
				return $wpc_is_admin_ajax;
			}

			$bool = is_admin() && is_numeric( strpos( $_SERVER['REQUEST_URI'], '/wp-admin/admin-ajax.php' ) );

			$GLOBALS['wpc_is_admin_ajax'] = $bool;

			return $bool;
		}

		/**
		  * Used in AJAX
		  *
		  * @since 3.0
		  * @param $user_id int the targeted user
		  * @param $array_to_push array of
		  */

		function wpc_user_unreads_noajax( $user_id, $array_to_push = array(), $reset = false, $delete = array() ) {
			return wpc_user_unreads_master( '_wpc_unreads_noajax', $user_id, $array_to_push, $reset, $delete );
		}

		function wpc_user_unreads( $user_id, $array_to_push = array(), $reset = false, $delete = array() ) {
			return;
			return wpc_user_unreads_master( '_wpc_unreads', $user_id, $array_to_push, $reset, $delete );
		}

		function wpc_user_unreads_master( $tag, $user_id, $array_to_push = array(), $reset = false, $delete = array() ) {
			$meta = get_user_meta( $user_id, $tag, TRUE );
			
			if( $meta ) {
				$meta = array_filter( array_unique( explode( ',', $meta ) ) );
			}

			if( $reset ) {
				delete_user_meta( $user_id, $tag );
				return;
			}

			if( ! is_array( $meta ) ) {
				$meta = array();
			}

			if( $array_to_push ) {
				if( empty( $array_to_push[0] ) ) {
					$array_to_push = array( $array_to_push );
				}
				$_array_to_push = $array_to_push; 
				$array_to_push = '';
				foreach( $_array_to_push as $element ) {
					$array_to_push .= implode( ":", $element ) . ',';
				}
				if( count( $array_to_push ) > 0 ) {
					$is_duplicate = false;
					if( ! is_array( $array_to_push ) ) {
						$array_to_push = explode( ',', $array_to_push );
					}
					$array_to_push = array_filter( $array_to_push );
					foreach( $array_to_push as $int ) {
						$meta[] = $int;
					}
					$meta = array_filter( array_unique( $meta ) );
					update_user_meta( $user_id, $tag, implode( ",", $meta ) );
				}
				return;
			}

			if ( count( $delete ) > 0 ) {

				if( ! isset( $delete[0] ) || ! is_array( $delete[0] ) ) {
					$delete = array( $delete );
				}

				foreach( $delete as $item ) {

					if( empty( $item['id'] ) && empty( $item['pm_id'] ) ) {
						continue;
					}

					foreach( $meta as $i => $data ) {

						$data = explode( ':', $data );
						$end = end( $data );
						$data = array(
							'id' => ! empty( $data[0] ) ? (int) $data[0] : 0,
							'pm_id' => ! empty( $end ) ? (int) $end : 0
						);
						
						if( ! empty( $item['id'] ) ) {
							if( $item['id'] == $data['id'] ) {
								unset( $meta[$i] );
							}
						}
						elseif ( ! empty( $item['pm_id'] ) ) {
							if( $item['pm_id'] == $data['pm_id'] ) {
								unset( $meta[$i] );
							}
						}
						else { continue; }

					}

				}

				$meta = array_filter( array_unique( $meta ) );

				if( ! empty( $meta ) ) {
					update_user_meta( $user_id, $tag, implode( ",", $meta ) );
				} else {
					delete_user_meta( $user_id, $tag );
				}

				return;

			}

			$meta_data = array();

			if( $meta ) {
				foreach( $meta as $item ) {
					$data = explode( ':', $item );
					$end = end( $data );
					$data = array(
						'id' => ! empty( $data[0] ) ? (int) $data[0] : 0,
						'pm_id' => ! empty( $end ) ? (int) $end : 0
					);
					$meta_data[] = $data;
				}
			} else {
				delete_user_meta( $user_id, $tag );
			}

			return $meta_data;
		}

		function wpc_alerts_allowed( $user_id, $pm_id ) { // NA
			return true;
		}

		function wpc_get_attachement( $id ) {
			$meta = get_option( "_wpc_upload_{$id}" );
			if( $meta > '' ) {
				$meta = str_replace('\\', '///', $meta);
				$meta = json_decode( $meta, true );
				$meta['path'] = str_replace('///', '\\', $meta['path']);
			} else {
				$meta = array();
			}
			return $meta;
		}

		function wpc_list_uploads( $user_id = false ) {
			return WPC_users::instance()->uploads_list( $user_id );
		}

		function wpc_message_to_forward() {

			if( ! wpc_is_forward_message() )
				return;

			$id = (int) get_query_var( 'wpc_forward_message', wpc_get_query_var( 'wpc_forward_message', '' ) );

			return wpc_get_message($id);

		}

		function wpc_recent_contacts( $user_id = false, $limit = 10 ) {
			return WPC_message::recently_contacted( $user_id, $limit );
		}

		function wpc_translate( $string ) {

			global $wpc_translate;
			return ! empty( $wpc_translate['objects'][$string] ) ? $wpc_translate['objects'][$string] : $string;

			/* keeping those strings
			$meta = get_option('_wpc_translate');
			if( $meta ) {
				$meta = json_decode( $meta, true );
			}
			if( empty( $meta ) || ! is_array( $meta ) ) {
				$meta = array();
			}
			array_push($meta, $string);
			$meta = array_filter( array_unique( $meta ) );
			update_option('_wpc_translate', json_encode($meta));

			global $wpc_translate;
			return ! empty( $wpc_translate['objects'][$string] ) ? $wpc_translate['objects'][$string] : $string;*/

		}

		/**
		  * Used while searching users for specific query without performing database query (LIKE or so)
		  *
		  * @since 3.0
		  * @param $user object user data
		  * @return string user data to search
		  */

		function wpc_get_user_search_data( $user, $limit_name = false ) {

			$data = strtolower($user->user_nicename);
			$data .= ' ' . strtolower($user->display_name);

			if( ! $limit_name ) {
				$data .= ' ' . strtolower(wpc_get_user_bio( $user->ID ));
			}

			$data .= ' ' . strtolower(wpc_get_user_name( $user->ID ));

			return apply_filters('wpc_user_search_target_data', $data, $user->ID);

		}

		function wpc_last_contacts( $limit = -1, $exclude = array() ) {
			return WPC_users::instance()->last_contacts( $limit, $exclude );
		}

		function wpc_user_can( $user_id, $action ) {
			return WPC_users::instance()->user_can( $user_id, $action );
		}

		function wpc_current_user_can( $action ) {
			if( ! is_user_logged_in() ) { return false; }
			return wpc_user_can( wp_get_current_user()->ID, $action );
		}

		/*function wpc_get_user_roles( $user_id = 0 ) {
			return WPC_users::instance()->roles( $user_id, $return_all );	
		}*/

		function wpc_get_user_roles( $user_id = 0 ) {
			return array(); // PRO feature	
		}

		function wpc_get_user_roles_data( $user_id = 0 ) {
			return array(); // PRO feature	
		}

		function wpc_get_user_caps( $user_id = 0 ) {
			return array(); // PRO feature	
		}

		function wpc_get_user_limitations( $user_id = 0 ) {
			return array(); // PRO feature	
		}

		function wpc_roles_list() {
			return array(); // PRO feature	
		}

		function wpc_quote_url( $url ) { return str_replace('"', '&quot;', $url); }

		function wpc_conversation_action_links( $pm_id = 0 ) {

			if( ! $pm_id )
				$pm_id = wpc_get_conversation_id();

			$pushUri = wpc_get_conversation_permalink();

			if( wpc_get_search_query() > '' ) {
				$refAf = '&q=' . wpc_get_search_query(1);
				$pushUri .= '?q=' . wpc_get_search_query(1);
			} else {
				$refAf = '';
			}
			$get_recipient = wpc_get_recipient();
			global $current_user;

			?>

			<span class="wpc_btm">
				<a href="<?php echo wpc_messages_base(); ?>" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1') . '"}'); ?>">&lsaquo; <?php echo wpc_translate('messages'); ?></a>
			</span>

			<?php if( wpc_current_user_can('mark_unread') ) : ?>
				<span>
					<a href="<?php echo wpc_get_conversation_permalink( '?do=unread' ); ?>" onclick="jQuery('.single-pm').addClass('marking_unread');" class="wpcajx2 wckevts" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1&wpc_recipient=' . $get_recipient->user_nicename . '&do=unread') . '", "success": "html=1", "onSuccessLoad": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1&done=unread') . '", "failAlert": "wpc.feedback.cant_mark_unread", "pushUri": "' . wpc_messages_base() . '" }'); ?>"><?php echo wpc_translate('mark unread'); ?></a>
				</span>
			<?php endif; ?>

			<span><?php wpc_archive_link(); ?></span>
			<span><a href="<?php echo wpc_get_conversation_permalink( '?do=delete' ); ?>" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc_actions&do=delete&wpc_messages=1&wpc_recipient=' . $get_recipient->user_nicename ) . '","confirm": "wpc.conf.del_c","success": "html=1", "onSuccessLoad": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1&done=delete' ) . '"}'); ?>" onclick="return confirm(wpc.conf.del_c)"><?php echo wpc_translate('delete'); ?></a></span>
			
			<?php if( wpc_is_blocking_allowed() ) : ?>
				<span><?php wpc_block_link(); ?></span>
			<?php endif; ?>
			
			<span><a href="javascript: window.location.reload()" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_messages=1&wpc_recipient=' . $get_recipient->user_nicename . $refAf ) . '","pushUri": "' . $pushUri . '"}'); ?>"><?php echo wpc_translate('refresh'); ?></a></span>
			<span><a href="<?php echo wpc_get_user_links($current_user->ID)->profile; ?>" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "' . admin_url('admin-ajax.php?action=wpc&wpc_users=1&wpc_user=' . $current_user->user_nicename ) . '"}'); ?>" title="<?php echo wpc_translate('my profile'); ?>"><?php echo wpc_translate('profile'); ?></a></span>			
			<?php

		}

		function wpc_is_mute_conversation() {
			return false; // PRO feature
		}

		function wpc_validate_nonce( $tag = '_wpc_nonce' ) {
			$nonce = isset( $_REQUEST[$tag] ) ? $_REQUEST[$tag] : null;

			if( ! isset( $nonce ) )
				return;

			return wp_verify_nonce( $nonce, $tag );
		}

		function wpc_data_title() {
			?>
				<div id="wpc-title" data-title="<?php echo stripslashes( str_replace( '"', '&quot;', wpc_title() ) ); ?>" style="display:none"></div>
			<?php
		}

		function wpc_is_mod_watch_messages() { return "1" == get_query_var( 'wpc_mod_messages', wpc_get_query_var('wpc_mod_messages') ); }

		function wpc_is_mod_banned_users() {
			return 0; // PRO feature
		}

		function wpc_mod_watch_conversation_permalink( $user_a = 0, $user_b = 0 ) {
			if( ! $user_a ) {
				$user_a = get_user_by( 'slug', get_query_var( 'wpc_mod_user_a', wpc_get_query_var( 'wpc_mod_user_a', 0 ) ) );
			}
			if( ! $user_b ) {
				$user_b = get_user_by( 'slug', get_query_var( 'wpc_mod_user_b', wpc_get_query_var( 'wpc_mod_user_b', 0 ) ) );
			}
			if( $user_a && $user_b ) {
				return wpc_get_user_links()->mod . '/messages/' . $user_a->user_nicename . '@' . $user_b->user_nicename . '/'; 
			}
			return;
		}

		function wpc_is_new_message() { return "1" == get_query_var( 'wpc_new_message', wpc_get_query_var('wpc_new_message') ); }

		function _wpc_str( $str, $esc_apos = false, $sanitize = false ) {

			$str = str_replace(
				array( '"', "\"" ),
				"&quot;",
				$str
			);

			if( $esc_apos ) {
				$str = str_replace(
					array( '\'', "'" ),
					"&apos;",
					$str
				);
			}

			if( $sanitize ) { $str = sanitize_text_field( $str ); }

			$str = stripslashes( $str );

			$str = apply_filters( '_wpc_str', $str );

			return $str;

		}

		function wpc_is_modal() { return isset( $_REQUEST['_wpc_modal'] ); }

		function wpc_messages_pagi( $pm_id = 0 ) {
			
			if( ! $pm_id ) { $pm_id = wpc_get_conversation_id(); }
			
			$pagi = new stdClass();
			$pagi->curr = (int) get_query_var( 'wpc_paged', wpc_get_query_var( 'wpc_paged', 0 ) );
			if( $pagi->curr < 1 ) { $pagi->curr = 1; }
			$pagi->offset = (int) wpc_settings()->pagination->messages;
			$pagi->total = WPC_message::instance()->totals( $pm_id );
			$pagi->last = abs( $pagi->total / $pagi->offset );
			if( is_float( $pagi->last ) ) { $pagi->last = (int) abs( $pagi->last + 1 ); }
			if( $pagi->curr > $pagi->last ) { $pagi->curr = $pagi->last; }
			$pagi->start = abs( ( $pagi->curr * $pagi->offset ) - $pagi->offset );

			$pagi->previous = $pagi->curr > 1 ? abs( $pagi->curr - 1 ) : false;
			$pagi->next = abs( $pagi->curr + 1 ) <= $pagi->last ? abs( $pagi->curr + 1 ) : false;
			
			return $pagi;
		
		}

		function wpc_echo_on( $cond, $str ) { if ( $cond ) { echo $str; } }

		function wpc_profile_tabs_list( $user = 0 ) {

			if( ! $user && wpc_get_displayed_user() ) {
				$user = wpc_get_displayed_user();
			}

			$settings = wpc_settings();
			$links = wpc_get_user_links($user->ID);

			$list = array();

			ob_start();

			?>
				<span class="<?php wpc_echo_on( wpc_is_single_user_view(), 'current'); ?>">
					<a href="<?php echo $links->profile; ?>" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "'.admin_url('admin-ajax.php?action=wpc&wpc_users=1&wpc_user='.$user->user_nicename).'"}'); ?>"><?php echo wpc_translate('Profile'); ?></a>
				</span>
			<?php

			$list[] = array(
				'unique' => 'profile',
				'content' => ob_get_clean()
			);

			if( ( wp_get_current_user()->ID == $user->ID || wpc_current_user_can('edit_users') ) && wpc_current_user_can('edit_own_profile') ) :;

			ob_start();

			?>
				<span class="<?php wpc_echo_on( wpc_is_user_edit(), 'current'); ?>">
					<a href="<?php echo $links->edit; ?>" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "'.admin_url('admin-ajax.php?action=wpc&wpc_users=1&wpc_user='.$user->user_nicename.'&wpc_edit_user=1').'"}'); ?>"><?php echo wpc_translate('Edit profile'); ?></a>
				</span>
			<?php

			$list[] = array(
				'unique' => 'edit',
				'content' => ob_get_clean()
			);;

			endif;

			return apply_filters('_wpc_profile_tabs_list', $list);

		}

		function wpc_user_profile_tabs( $user ) {

			$list = wpc_profile_tabs_list( $user );
			?>

			<div class="wpc-p-tabs">
				<?php foreach( $list as $li ) : ?>
					<?php echo $li['content']; ?>
				<?php endforeach; ?>
				<?php do_action('wpc_additional_profile_tabs'); ?>
			</div>

			<?php
		}

		function wpc_nonce_url( $url ) { return wp_nonce_url( $url, '_wpc_nonce', '_wpc_nonce' ); }

		function wpc_notification_settings( $user_id = 0 ) { return WPC_users::instance()->notification_settings( $user_id ); }

		function wpc_profile_edit_groups() {
			return WPC_users::instance()->profile_edit_groups();
		}
		function wpc_profile_edit_current_group_name() {
			return get_query_var( 'wpc_edit_user_group', wpc_get_query_var('wpc_edit_user_group', false) );
		}

		function wpc_profile_edit_current_group() {

			$current_name = wpc_profile_edit_current_group_name();
			$groups = wpc_profile_edit_groups();

			if( ! wpc_is_user_edit() || empty( $groups ) ) {
				return array();
			}

			$current = array();

			foreach( $groups as $group ) {
				if( $group['name'] == $current_name ) {
					$current = $group;
				}
			}

			if( empty( $current ) && isset( $groups[0] ) ) {
				$current = $groups[0];
			}

			return (object) $current;

		}

		function wpc_profile_edit_group_content() {
			return WPC_users::instance()->profile_edit_group_content();
		}
		function wpc_profile_groups() {
			return WPC_users::instance()->profile_groups();
		}

		function wpc_profile_custom_tab_name() {
			return get_query_var( 'wpc_custom_tab', wpc_get_query_var('wpc_custom_tab') );
		}

		function wpc_create_profile_tab( $args ) {
			new WPC_create_profile_tab($args);
			return false;
		}

		function wpc_registered_profile_tabs() {
			return apply_filters('wpc_registered_profile_tabs', array());
		}

		function wpc_is_custom_profile_tab() {
			return wpc_profile_custom_tab_name() > '';
		}

		function wpc_count_unreads_in_pm( $pm_id = 0, $recipient = 0, $current_user = 0 ) {

			if( ! $pm_id && ! $recipient && wpc_get_displayed_user_id() ) {
				$recipient = wpc_get_displayed_user_id();
			}

			if( ! $pm_id && $recipient ) {
				$pm_id = wpc_get_conversation_id( $recipient );
			}


			if( ! $pm_id ) {
				$pm_id = wpc_get_conversation_id();
			}

			if( ! $pm_id ) {
				return;
			}

			$found = (array) wpc_get_stats( $current_user )->unread_conversations->dup_contents;

			foreach( $found as $i => $p ) {
				if( $p !== $pm_id ) {
					unset( $found[$i] );
				}
			}

			return count( $found );
		}

		function wpc_json_encode_strip_wrap_quotes( $string ) {
			$string = json_encode( $string );
			$string = '"' == substr( $string, 0, 1 ) ? substr( $string, 1 ) : $string;
			$string = '"' == substr( $string, -1 ) ? substr( $string, 0, -1 ) : $string;
			return $string;
		}

		function wpc_flush_rewrite_rules() {
			flush_rewrite_rules();
			delete_option("_wpc_needs_flush");
		}

		function wpc_get_profile_tab() {
			global $wpc_custom_tab_contents;

			if( ! is_array( $wpc_custom_tab_contents ) || empty( $wpc_custom_tab_contents ) ) {
				return;
			}

			$data = array();

			foreach( $wpc_custom_tab_contents as $args ) {
				if( isset( $args['name'] ) && $args['name'] == wpc_profile_custom_tab_name() ) {
					$data = (object) $args;
				}
			}

			return $data;

		}

		function wpc_get_user_notifications( $user_id = 0, $status = 'all' ) {
			return array(); // PRO feature
		}

		function wpc_add_stats( $args ) {
			return; // PRO feature
		}

		function wpc_user_stats_chart( $user_id ) {
			return 0; // PRO feature
		}

		function wpc_stats_graph_label_name( $name ) {
			return $name; // PRO feature
		}

		function wpc_contact_user_modal_link( $user, $modal_data = array() ) {

			$modal_data = (object) $modal_data;

			if( ! isset( $modal_data->exit_href ) ) {
				$modal_data->exit_href = wpc_get_user_links($user->ID)->profile;
			}

			if( ! isset( $modal_data->exit_title ) ) {
				$modal_data->exit_title = wpc_title(1);
			}

			if( ! isset( $modal_data->link_text ) ) {
				$modal_data->link_text = 'Send message';
			}

			if( ! isset( $modal_data->include_count ) ) {
				$modal_data->include_count = true;
			}

			$count = wpc_count_unreads_in_pm(0, $user->ID);

			?>	

				<a href="<?php echo wpc_get_user_links($user->ID)->message; ?>" class="wpcfmodal wpc-btn" data-task="{&quot;content&quot;: &quot;&lt;p class=\&quot;wpc_quick_m\&quot;&gt;&lt;a href=\&quot;<?php echo wpc_messages_base('new/'); ?>\&quot; class=\&quot;wpcfmodal wpc-btn\&quot; data-task=\&quot;{&amp;quot;loadURL&amp;quot;: &amp;quot;<?php echo admin_url(); ?>admin-ajax.php?action=wpc&amp;wpc_messages=1&amp;wpc_new_message=1&amp;recipient=<?php echo $user->ID; ?>&amp;_no_back&amp;quot;, &amp;quot;onExitTitle&amp;quot;: &amp;quot;<?php echo $modal_data->exit_title; ?>&amp;quot;, &amp;quot;onExitHref&amp;quot;: &amp;quot;<?php echo $modal_data->exit_href; ?>&amp;quot;, &amp;quot;onLoadHref&amp;quot;: &amp;quot;<?php echo wpc_messages_base('new/'); ?>&amp;quot;}\&quot;&gt;<?php echo wpc_translate('compose'); ?>&lt;/a&gt; <?php echo wpc_translate('or'); ?> &lt;a href=\&quot;<?php echo wpc_get_user_links($user->ID)->message; ?>\&quot; class=\&quot;wpc-btn wpcajx2\&quot; data-task=\&quot;{&amp;quot;loadURL&amp;quot;: &amp;quot;<?php echo admin_url(); ?>admin-ajax.php?action=wpc&amp;wpc_messages=1&amp;wpc_new_message=1&amp;wpc_recipient=<?php echo $user->user_nicename; ?>&amp;quot;}\&quot; onclick=\&quot;jQuery(&apos;.wpc-modal&apos;).hide()\&quot;&gt;<?php echo wpc_translate('view conversation'); ?>&lt;/a&gt;&lt;/p&gt;&quot;}"><?php echo wpc_translate($modal_data->link_text); echo $count && $modal_data->include_count > 0 ? '<span class="wpc-count">' . $count . '</span>' : ''; ?></a>

			<?php

		}

		function wpc_users_widget_tab_elements() {

			$tabs = array(
				'none' => array( 'name' => wpc_translate('All'), 'icon' => 'wpcico wpcico-users-1' ),
				'online' => array( 'name' => wpc_translate('Online'), 'icon' => 'wpcico wpcico-ok' )
			);

			if( is_user_logged_in() ) {
				$tabs['blocked'] = array( 'name' => wpc_translate('Blocked'), 'icon' => 'wpcico wpcico-block' );
			}

			return apply_filters( 'wpc_users_widget_tab_elements', $tabs );

		}

		function wpc_user_has_cover( $user_id = 0 ) {
			return false; // PRO feature
		}

		function wpc_get_user_cover( $user_id = 0 ) {
			return; // PRO feature
		}

		function wpc_mailing_settings() {
			$meta = get_option( "_wpc_settings_mailing" );
			if( $meta ) { $meta = json_decode( base64_decode( $meta ), true ); }
			$mailing_settings = $mailing_settings['subject'] = $mailing_settings['body'] = array();
			$mailing_settings['html'] = ! empty( $meta['html'] );
			$mailing_settings['subject']['message'] = ! empty( $meta['subject']['message'] ) ? sanitize_text_field( $meta['subject']['message'] ) : wpc_translate('[sender-name] has sent you a new message on [site-name]');
			$mailing_settings['subject']['mod_welcome'] = ! empty( $meta['subject']['mod_welcome'] ) ? sanitize_text_field( $meta['subject']['mod_welcome'] ) : wpc_translate('You have been a chat moderator on [site-name]');
			$mailing_settings['subject']['mod_instant'] = ! empty( $meta['subject']['mod_instant'] ) ? sanitize_text_field( $meta['subject']['mod_instant'] ) : wpc_translate('You have a new flagged item on [site-name]');
			$mailing_settings['subject']['mod_summary'] = ! empty( $meta['subject']['mod_summary'] ) ? sanitize_text_field( $meta['subject']['mod_summary'] ) : wpc_translate('Daily summaries of flagged items on [site-name]');
			$mailing_settings['body']['message'] = ! empty( $meta['body']['message'] ) ? html_entity_decode( $meta['body']['message'] ) : "Dear [user-name],\n\n[sender-name] has sent you new message(s) on [site-name]:\n\n\"[message-excerpt]\"\n\nView and reply to this message:\n[message-link]\n\nEdit your notification settings:\n[user-settings-notifications-link]\n\nP.S: You have [message-unread-count] unread message(s) from this conversation.";
			$mailing_settings['body']['mod_welcome'] = ! empty( $meta['body']['mod_welcome'] ) ? html_entity_decode( $meta['body']['mod_welcome'] ) : "Dear [user-name],\n\nYou have been made a moderator on [site-name]!\n\nYou can now moderate flagged messages and conversations through your moderation panel.\n\nVisit your moderation panel:\n[moderation-panel-link]\n\nEdit your notification settings:\n[user-settings-notifications-link]";
			$mailing_settings['body']['mod_instant'] = ! empty( $meta['body']['mod_instant'] ) ? html_entity_decode( $meta['body']['mod_instant'] ) : "Dear [user-name],\n\nYou have new flagged messages on [site-name]:\n\n--\n\n[moderated-item]\n\n--\n\nVisit your moderation panel for further information and tools:\n[moderation-panel-link]\n\n--\nYou are receiving this email because you are a chat moderator at [site-name], to edit your notification settings:
[user-settings-notifications-link]";
			$mailing_settings['body']['mod_summary'] = ! empty( $meta['body']['mod_summary'] ) ? html_entity_decode( $meta['body']['mod_summary'] ) : "Dear [user-name],\n\nYou have new flagged messages/conversations on [site-name]:\n\n[moderated-items]\n\nVisit your moderation panel for further information and tools:\n[moderation-panel-link]\n\n--\nYou are receiving this email because you are a chat moderator at [site-name], to edit your notification settings:\n[user-settings-notifications-link]";
			$mailing_settings['subject'] = (object) $mailing_settings['subject'];
			$mailing_settings['body'] = (object) $mailing_settings['body'];			
			return (object) $mailing_settings;
		}

		function wpc_mail_is_online( $user_id ) {
			$int = wpc_get_user_last_seen( $user_id, true )->integer;
			if( $int ) {
				if( ( time() - (int) $int ) >= apply_filters( 'wpc_mail_is_online_seconds', 300 ) ) { return false; }
			} else return false;
			return true;
		}

		function wpc_social_list_icons() {
			$list = wpc_social_list();
			foreach( $list as $i => $item ) {
				switch( $i ) {
					case 'twitter':
						$list[$i] = 'wpcico wpcico-twitter';
						break;
					case 'facebook':
						$list[$i] = 'wpcico wpcico-facebook-official';
						break;
					case 'google':
						$list[$i] = 'wpcico wpcico-gplus-squared';
						break;
					case 'linkedin':
						$list[$i] = 'wpcico wpcico-linkedin-squared';
						break;
					case 'website':
						$list[$i] = 'wpcico wpcico-mouse';
						break;
					case 'instagram':
						$list[$i] = 'wpcico wpcico-instagram';
						break;
					case 'youtube':
						$list[$i] = 'wpcico wpcico-youtube-squared';
						break;
					case 'soundcloud':
						$list[$i] = 'wpcico wpcico-soundcloud-circled';
						break;
					case 'pinterest':
						$list[$i] = 'wpcico wpcico-pinterest-squared';
						break;
					case 'email':
						$list[$i] = 'wpcico wpcico-mail-alt';
						break;
					default:
						break;
				}
			}
			return apply_filters( "wpc_social_list_icons", $list );
		}

		function wpc_roles_caps_info( $get_cap = '' ) {
			return array(); // PRO feature	
		}

		function wpc_get_user_preferences( $user_id = 0 ) {

			// PRO feature	
			return apply_filters( "wpc_get_user_preferences_disabled", array( 'contact' => 'anyone', 'view' => 'anyone' ), $user_id );

		}

		function wpc_can_view_profile( $user_id = 0, $current_user = 0 ) {
			return true; // PRO feature
		}

		function wpc_has_contacted( $target_user, $current_user = 0 ) {
			return WPC_message::instance()->has_contacted( $target_user, $current_user );
		}

		function _wpc_get_users( $exclude_hidden = false ) {
			return WPC_users::instance()->get_users( $exclude_hidden );
		}

		function wpc_string_contains( $needle, $haystack ) { # ref: stackoverflow.com/a/11434475
			$input = $needle;
			$value = $haystack;

			//$input = preg_replace('/ /', '% ', $input, 10);

			// Mapping of wildcards to their PCRE equivalents
			$wildcards = array( '%' => '.*?', '_' => '.');

			// Escape character for preventing wildcard functionality on a wildcard
			$escape = '!';

			// Shouldn't have to modify much below this

			$delimiter = '/'; // regex delimiter

			// Quote the escape characters and the wildcard characters
			$quoted_escape = preg_quote( $escape);
			$quoted_wildcards = array_map( function( $el) { return preg_quote( $el); }, array_keys( $wildcards));

			// Form the dynamic regex for the wildcards by replacing the "fake" wildcards with PRCE ones
			$temp_regex = '((?:' . $quoted_escape . ')?)(' . implode( '|', $quoted_wildcards) . ')';

			// Escape the regex delimiter if it's present within the regex
			$wildcard_replacement_regex = $delimiter . str_replace( $delimiter, '\\' . $delimiter, $temp_regex) . $delimiter;

			// Do the actual replacement
			$regex = preg_replace_callback( $wildcard_replacement_regex, function( $matches) use( $wildcards) { return !empty( $matches[1]) ? preg_quote( $matches[2]) : $wildcards[$matches[2]]; }, preg_quote( $input)); 

			// Finally, test the regex against the input $value, escaping the delimiter if it's present
			preg_match( $delimiter . str_replace( $delimiter, '\\' . $delimiter, $regex) . $delimiter .'i', $value, $matches);

			// Output is in $matches[0] if there was a match

			return !empty( $matches[0] );
		}

		function wpc_user_usage( $data = array() ) {
			return; // PRO feature
		}

		function wpc_json_alt_get_unreads( $user_id = 0 ) {
			if ( ! $user_id ) $user_id = wp_get_current_user()->ID;
			$meta = get_user_meta( $user_id, "_wpc_json_items", 1 );
			return $meta;
		}

	}


}

WPC_functions::instance();