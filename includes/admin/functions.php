<?php

class WPC_admin_functions
{
	protected static $instance = null;

	public static function instance() {

		return null == self::$instance ? new self : self::$instance;

	}

	public function __construct() {
		$this->init();

		add_filter('_wpc_admin_users_heading_title', function( $html ) { 
			
			if( isset( $_REQUEST['tab'] ) ) {
				if( "moderators" == $_REQUEST['tab'] ) {
					$html .= " &rsaquo; moderators";
				} elseif ( "banned" == $_REQUEST['tab'] ) {
					$html .= " &rsaquo; banned";
				}
			}

			else if ( isset( $_REQUEST['user'] ) && $user = get_userdata( $_REQUEST['user'] ) ) {
				$html .= " &rsaquo; " . wpc_get_user_name( $user->ID );
			}

			return $html;

		});

		add_filter('_wpc_admin_settings_heading_title', function( $html ) { 
			if( isset( $_REQUEST['tab'] ) ) {
				if( "roles" == $_REQUEST['tab'] ) {
					$html .= " &rsaquo; roles";
					if( isset( $_REQUEST['wpc_edit_role'] ) ) {
						$html .= " &rsaquo; edit";
					} elseif ( isset( $_REQUEST['wpc_new_role'] ) ) {
						$html .= " &rsaquo; new";
					}
				} elseif( "mailing" == $_REQUEST['tab'] ) {			
					$html .= " &rsaquo; mailing";
				} elseif( "users" == $_REQUEST['tab'] ) {			
					$html .= " &rsaquo; users";
				}
			} else {			
				$html .= " &rsaquo; messages";
			}
			return $html;
		});

	}

	public function init() {

		function wpc_admin_custom_emoji_list() {

			$meta = get_option('_wpc_custom_emoji');

			if( ! ( $meta > '' ) )
				return array();

			$meta = html_entity_decode( stripslashes( $meta ) );
			$meta = str_replace("\'", "'", $meta);
			$meta = json_decode( $meta );
			
			$list = array();

			foreach( $meta as $emoticon ) {
				$list[] = $emoticon;
			}

			return array_reverse( $list );

		}

		function wpc_admin_delete_emoji( $key ) {

			$list = wpc_admin_custom_emoji_list();

			foreach( $list as $_key => $emoticon ) {
				if( $key == $_key )
					unset( $list[$key] );
			}

			$meta = '';

			foreach( $list as $emoticon ) {
				$meta .= json_encode( $emoticon );
				if( $emoticon !== end( $list ) )
					$meta .= ',';
			}

			update_option('_wpc_custom_emoji', "[$meta]" );

		}

		function wpc_admin_get_emoticon_array_key( $symbol ) {
			$found = false;
			foreach( wpc_emoji_list(true)->symbols as $key => $_symbol ) {
				if( (string) $_symbol == (string) $symbol ) {
					$found = $key;
				}
			}
			return (int) $found;
		}

		function wpc_admin_settings_tab_active( $tab = '' ) {

			if( isset($_GET["tab"]) && in_array( $tab, array( "users", "ajax", "mailing", "other", "roles", "translate" ) ) ) {

				switch( (string) $_GET["tab"] ) {

					case 'users':
						if( "users" == $tab ) echo " nav-tab-active";
						break;

					case 'ajax':
						if( "ajax" == $tab ) echo " nav-tab-active";
						break;

					case 'mailing':
						if( "mailing" == $tab ) echo " nav-tab-active";
						break;

					case 'other':
						if( "other" == $tab ) echo " nav-tab-active";
						break;

					case 'roles':
						if( "roles" == $tab ) echo " nav-tab-active";
						break;

					case 'translate':
						if( "translate" == $tab ) echo " nav-tab-active";
						break;

				}

			} else {
				if( ! isset($_GET["tab"]) && "messages" == $tab || ( isset($_GET["tab"]) && ! in_array( $_GET["tab"], array( "users", "ajax", "mailing", "other", "roles", "translate" ) ) ) )
					echo " nav-tab-active";
			}

		}

		function wpc_admin_active_active( $name = '' ) {
			if( isset($_GET["tab"]) && $name == $_GET["tab"] ) { echo " nav-tab-active"; }
			elseif ( '' == @$_GET["tab"] && '' == $name ) {echo " nav-tab-active";}
		}

		function wpc_admin_user_link( $user_id ) {
			return admin_url( 'user-edit.php?user_id=' . $user_id );
		}

		function wpc_admin_the_pagination( $pagi, $base_url ) {

			if( ! $pagi || ! $pagi->available ) { return; }
			$pre = strpos( $base_url, '?' ) ? '&' : '?';


			?>

			<div class="wpc-pagi">

				<?php if( (int) $pagi->last_page > 1 ) : ?>

					<div>
					
						<?php if ( $pagi->current_page > 2 ) : ?>
							<a href="<?php echo $base_url; ?>" title="First page">&laquo; first</a>
						<?php endif; ?>

						<?php if ( $pagi->previous ) : ?>	
							<a href="<?php echo $base_url . ( $pagi->previous > 1 ? "{$pre}wpc_paged={$pagi->previous}" : '' ); ?>" title="Previous page (<?php echo $pagi->previous; ?>)">&lsaquo; previous</a>
						<?php endif; ?>

					</div>
					<div>

						<?php if ( $pagi->next ) : ?>
							<a href="<?php echo $base_url . $pre . 'wpc_paged=' . $pagi->next; ?>" title="Next page">next &rsaquo; </a>
						<?php endif; ?>

						<?php if ( $pagi->current_page < ( $pagi->last_page - 1 ) ) : ?>
							<a href="<?php echo $base_url . $pre . 'wpc_paged=' . $pagi->last_page; ?>" title="Last page">last &raquo;</a>
						<?php endif; ?>

					</div>

				<?php endif; ?>

			</div>

			<?php

		}

		function wpc_admin_get_all_social_profiles() {
			remove_filter( "wpc_social_list", "wpc_admin_toggle_social_profiles", 999 );
			$list = wpc_social_list();
			add_filter( "wpc_social_list", "wpc_admin_toggle_social_profiles", 999 );
			return $list;
		}

		function wpc_translate_objects() {
			return array("Messages", "Browse users", "Basic info", "Name", "Anonymous", "About (bio)", "Site notifications", "Enable notifications for", "Newly received messages", "Newly reported messages/conversations", "Email notifications", "Send me", "instant emails", "daily summaries", "nothing", "whenever an item is flagged for moderation", "Send me notifications to", "Twitter", "Facebook", "Google+", "Linkedin", "Website", "Social profiles", "Notifications", "Social", "Cover photo", "Preferences", "years", "last seen [time] ago", "day", "days", "hours", "a moment", "All", "Online", "Blocked", "search", "smiley face", "big smile", "frown", "cry", "tongue out", "angel", "confused", "wink", "surprised", "squinting", "angry", "kiss", "heart", "kiki", "glasses", "sunglasses", "grumpy", "pacman", "unsure", "curly lips", "thumb up", "blush", "disappointed", "gift heart", "heart smiley", "mocking", "sad", "smiling face", "triumph", "alien symbol", "cold sweat", "dizzy", "happy blushing", "purple devil", "satisfied", "smirking", "unamused", "astonished", "eyes wide open", "mad", "red angry", "scared", "tears of joy", "cry tears", "fear", "heart eyes", "medic", "relieved", "sleepy", "terrified", "Upload image", "Upload", "or add from URL", "image URL", "loading", "Are you sure you want to delete this message? This can't be undone.", "Are you sure you want to delete this report?", "Are you sure you want to ban this user?", "Are you sure you want to unban this user?", "Are you sure you want to delete this conversation? This can't be undone.", "Are you sure you want to block this user?", "Are you sure you want to forward this message to this user?", "Are you sure you want to add this user as moderator?", "Are you sure you want to remove this user as moderator?", "Are you sure you want to submit this report?", "Error occured. Please try again", "Error uploading image. Please verify your image extension and/or size and try again.", "Error occured. Only images can be dragged into upload area.", "Error occured, could not send message", "Please type out a report to submit.", "Could not load data. Please try again.", "Could not forward this message. Please try again.", "This user was successfully removed as moderator.", "Sorry, you can not mark this conversation unread.", "Cover photo removed, now save your profile edit.", "show sidebar", "Forward message", "auto-saving ..", "auto-saved", "Your message is not auto-saved..", "New message", "just now", "load more", "compose", "or", "view conversation", "Send message", "No users were found", "previous page", "next page", "page", "select image", "New message from %s", "view", "%s ago", "online", "ago", "online [time] ago", "My profile", "seconds", "blocked", "my profile", "moderation panel", "mod. panel", "users", "Back to users", "Profile", "Edit profile", "Moderator", "edit cover", "About me", "month", "months", "Powered by", "WordPress live chat and instant messaging plugin with user profiles", "second", "profile updated successfully.", "Cancel", "upload", "remove", "Allowed extensions are %s, and max. upload size is %s MB", "Showing search results for", "Users", "Not recently active", "This user has not provided information about them.", "Block", "activity", "Search results for", "[user] › profile", "ban", "messages", "mark unread", "archive", "mute", "delete", "report", "block", "refresh", "[time] ago", "forward", "Read", "Read %s ago", "Write a message..", "Send", "add emoji", "add image", "Report messages", "About this message", "Sender", "Recipient", "Date", "Message body", "Explain briefly the reason you're reporting this message", "Message flagged successfully.", "Report deleted successfully.", "Submit report", "Report message", "Report conversation", "About this conversation", "Messages between", "me", "and", "Last report update", "Explain briefly the reason you're reporting this conversation", "Conversation flagged successfully.", "Delete report", "Sorry, you can not forward this message", "back to messages", "Search users", "Forward message to this user", "select", "unread", "archives", "attachement", "Compose", "Select a recipient", "next", "year", "blocked users", "You have no blocked users.", "No blocked users have matched your search query", "online users", "online now", "There are no users currently online.", "No online users have matched your search query", "There are no users to show.", "No users have matched your search query", "This month so far", "mark read", "toggle all", "My notifications", "Showing message results for", "Online %s ago", "last seen", "Your moderation panel", "minutes", "attachment", "[user_link][user_name][/user_link] has sent you [link]a new message[/link]", "[user_link][user_name][/user_link] has flagged [link]a new message[/link]", "[user_link][user_name][/user_link] has flagged [link]a new conversation[/link]", "You have been a moderator by [user_link][user_name][/user_link]", "minute", "hour", "Sorry, this user's privacy settings do not allow you to view their profile", "add from URL", "You have reached your limits of uploads, please try again later.", "unmute", "Mute conversation", "Mute conversation for:", "30 minutes", "4 hours", "24 hours", "7 days", "1 month", "1 year", "Or, unmute conversation:", "Unmute", "Submit", "Moderation panel", "Moderators", "Banned users", "View messages", "You have [count] messages currently awaiting moderation:", "Information", "Actions", "About this report:", "Reported by:", "Feedback:", "Reported at:", "About the reported message:", "Message sender:", "Message recipient:", "Message sent:", "Message body:", "delete report", "Last report modification:", "Conversations", "About the reported conversation:", "Conversation between", "Last message:", "sent [time] ago", "You have no archived conversations.", "Message deleted successfully.", "There are no reported messages for the moment.", "There are no reported conversations for the moment.", "Nothing to see here for the moment. Please check back soon.", "Offline", "Who can contact me", "Anyone", "People I have contacted", "Nobody", "Who can view my profile", "Registered users", "profile", "404 - User not found", "This user was not found. Perhaps try a search?", "submit", "Or", "browse users", "There are no messages to show.", "No message has matched your search query.", "Write message..", "send now", "Newly reported messages/conversations", "Error uploading image. Please verify your image extension and/or size and try again.");
		}

	}

}

WPC_admin_functions::instance();