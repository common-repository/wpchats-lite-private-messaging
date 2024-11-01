<?php

/**
  * class WPC_create_profile_tabs
  * Extend user profile and add more tabs and custom content
  * Documentation will be available on how to use this functionality
  *
  * @since 3.0
  */

class WPC_create_profile_tab
{

	public function __construct( $args ) {
		$this->rand = rand();
		$this->create( $args );
	}

	public function create( $args ) {

		$permission = isset( $args['permissions'] ) ? (string) $args['permissions'] : 'all';

		$user = wpc_get_displayed_user();

		switch( $permission ) {
			case 'all':
				$pass = true;
				break;

			case 'logged_in':
				$pass = is_user_logged_in();
				break;

			case 'me_only':
				$pass = is_user_logged_in() && wp_get_current_user()->ID == $user->ID;
				break;

			default:
				$pass = is_user_logged_in() ? in_array( $permission, wpc_get_user_roles() ) : false;
				break;	
		}

		if( ! $pass ) { return; } // the content can't be served for the current user|visitor

		$args['is_accessible'] = isset( $args['custom_permission'] ) ? $args['custom_permission'] : true;
		$args['is_accessible'] = apply_filters( 'wpc_ptabs_custom_permissions_is_accessible', $args['is_accessible'], $args );

		// no longer needed ( use $args['is_accessible'] )
		unset( $args['custom_permission'] );

		$GLOBALS['wpc_create_profile_tab_args' . $this->rand] = $args;
		$GLOBALS['wpc_custom_tab_contents'][] = $args;


		// adding the tab span
		add_filter('_wpc_profile_tabs_list', function( $tabs ) {
			// after some efforts I could only use dirty ways to pass the data into the method child function
			eval( 'global $wpc_create_profile_tab_args' . $this->rand . ';' );
			eval( '$args = $wpc_create_profile_tab_args' . $this->rand . ';' );

			if( ! $args['is_accessible'] ) {
				return $tabs;
			}

			$user = wpc_get_displayed_user();
			$current = $args['name'] == wpc_profile_custom_tab_name();

			ob_start();
			?>

				<span class="<?php wpc_echo_on( $current, 'current'); ?>">
					<a href="<?php echo wpc_get_user_links($user->ID)->profile . $args['slug'] . '/'; ?>" class="wpcajx2" data-task="<?php echo wpc_quote_url('{"loadURL": "'.admin_url('admin-ajax.php?action=wpc&wpc_users=1&wpc_user='.$user->user_nicename).'&wpc_custom_tab=' . $args['name'] . '"}'); ?>">
					<?php echo $args['title']; ?></a>
				</span>

			<?php

			$tabs[] = array(
				'unique' => $args['name'],
				'content' => ob_get_clean()
			);

			return apply_filters('wpc_profile_tabs_list', $tabs);
		});

		// the rewrite rules
		add_rewrite_rule(
	    	wpc_get_bases()->users . '/([^/]+)/' . $args['slug'] . '/?$',
	    	'index.php?pagename=' . get_post( wpc_settings()->page )->post_name . '&wpc_users=1&wpc_user=$matches[1]&wpc_custom_tab=' . $args['name'],
	    	'top'
	    );

		// filtering the content
		add_filter('wpc_profile_content', function( $html ) {
			
			eval( 'global $wpc_create_profile_tab_args' . $this->rand . ';' );
			eval( '$args = $wpc_create_profile_tab_args' . $this->rand . ';' );

			if( $args['name'] == wpc_profile_custom_tab_name() ) {
				if( ! $args['is_accessible'] ) {
					return wpc_translate('Sorry, this content is not accessible for the moment.');
				}
				ob_start();
				require wpc_template_path( 'users/user-single-custom-tab' );
				$html = ob_get_clean();
			}

			return $html;
		});

		// printing the content
		/*add_action('__wpc_the_profile_custom_tab_content', function() {
			eval( 'global $wpc_create_profile_tab_args' . $this->rand . ';' );
			eval( '$args = $wpc_create_profile_tab_args' . $this->rand . ';' );
			if( $args['name'] !== wpc_profile_custom_tab_name() ) {
				return;
			}

			echo apply_filters( 'wpc_' . $args['name'] . '_tab_content', $args['content'] );
		});*/

		add_filter('wpc_registered_profile_tabs', function( $array ) {
			eval( 'global $wpc_create_profile_tab_args' . $this->rand . ';' );
			eval( '$args = $wpc_create_profile_tab_args' . $this->rand . ';' );
			$array[] = $args;
			return $array;
		});

		add_filter('wpc_custom_tab_container_classes', function( $classes ) {
			eval( 'global $wpc_create_profile_tab_args' . $this->rand . ';' );
			eval( '$args = $wpc_create_profile_tab_args' . $this->rand . ';' );
			if( $args['name'] == wpc_profile_custom_tab_name() ) {
				$classes .= ' name_' . preg_replace('/[^\da-z_]/i', '-', strtolower($args['name']));
				$classes .= ' title_' . preg_replace('/[^\da-z_]/i', '-', strtolower($args['title']));
			}
			return $classes;
		});

		add_action('wp', function() { 
	
			$tabs = wpc_registered_profile_tabs();

			if( ! is_array( $tabs ) || empty( $tabs ) ) {
				return;
			}

			/**
			  * Automatically flushing rewrite rules to make slugs and URLs functional
			  * Everytime, checks whether our existing urls match the ones on the db
			  */

			$rules = '';

			foreach( $tabs as $tab ) {
				$rules .= $tab['name'] . ':' . $tab['slug'];
				$rules .= $tab !== end( $tabs ) ? ',' : ''; 
			}

			if( $rules > '' && ( $rules !== get_option('_wpc_profile_tabs_registered_rules') ) ) {
				wpc_flush_rewrite_rules();
				update_option('_wpc_profile_tabs_registered_rules', $rules);
				/**
				  * because we hooked on wp instead of init (to get all the tabs), new rules
				  * won't be served unless refreshed, so let's redirect.
				  * wp hook in not applicable in ajax unline init.
				  */
				wp_redirect( $_SERVER['REQUEST_URI'] );
				exit;
			}

			$names = $slugs = array();

			foreach( $tabs as $tab ) {
				$names[] = $tab['name'];
				$slugs[] = $tab['slug'];
			}

			if( ! empty( $names ) ) {
				if( count( $names ) > count( array_unique( $names ) ) ) {
					trigger_error("Error: registering 2 or more WpChats profile tabs with the same name. Please use a unique name for each registered profile tab.");
				}
			}

			if( ! empty( $slugs ) ) {
				if( count( $slugs ) > count( array_unique( $slugs ) ) ) {
					trigger_error("Error: registering 2 or more WpChats profile tabs with the same slug. Please use a unique slug for each registered profile tab.");
				}
			}

			return;

		});

	}

}