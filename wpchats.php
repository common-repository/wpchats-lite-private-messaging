<?php

/*
Plugin Name: WpChats Lite
Plugin URI: https://plugin.wpchats.io
Description: WordPress live chat and instant messaging plugin with user profiles
Author: Samuel Elh
Version: 3.0.1
Author URI: http://samelh.com
*/

if( ! function_exists('WpChats') ) {

	function WpChats() {

		defined( 'WPC_URL' )		|| define( 'WPC_URL', plugin_dir_url(__FILE__) );
		defined( 'WPC_PATH' )  		|| define( 'WPC_PATH', plugin_dir_path(__FILE__) );
		defined( 'WPC_INC_PATH' )  	|| define( 'WPC_INC_PATH', plugin_dir_path(__FILE__) . 'includes/' );
		defined( 'WPC_FILE' )		|| define( 'WPC_FILE', __FILE__ );
		defined( 'WPC_TABLE' )		|| define( 'WPC_TABLE', 'mychats' );
		defined( 'WPC_VER' )		|| define( 'WPC_VER', '3.0.1' );
		defined( 'WPC_DIR_NAME' )	|| define( 'WPC_DIR_NAME', str_replace( '/wpchats.php', '', plugin_basename( __FILE__ ) ) );

		# load the loader class 
		require 'includes/core/loader.php';

		do_action('wpchats_loaded');

	}

}

WpChats();