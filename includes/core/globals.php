<?php

$GLOBALS['wpc'] = array();

add_action('init', function() {
	if( is_404() ) { return; }
	$user = wpc_get_displayed_user();
	if( empty( $user ) ) {
		$uri = parse_url( $_SERVER['REQUEST_URI'] );
		$uri = ! empty( $uri['path'] ) ? $uri['path'] : false;
		$base = wpc_get_bases()->users;
		if( ! $uri || ! mb_strpos( $uri, $base ) ) { return; }
		$uri = explode( $base, $uri );
		$uri = array_filter( array_unique( $uri ) );
		$uri = end( $uri );
		$uri = explode( '/', $uri );
		$uri = array_filter( array_unique( $uri ) );
		reset($uri);
		if( empty( $uri[key($uri)] ) ) { return; }
		$user = get_user_by( 'slug', (string) $uri[key($uri)] );
	}
	$GLOBALS['wpc']['displayed_user'] = $user;
});

add_action("init", function() { 
	if( is_404() ) { return; }
	$user = wpc_get_recipient();
	if( empty( $user ) ) {
		$uri = parse_url( $_SERVER['REQUEST_URI'] );
		$uri = ! empty( $uri['path'] ) ? $uri['path'] : false;
		$base = wpc_get_bases()->messages;
		if( ! $uri || ! mb_strpos( $uri, $base ) ) { return; }
		$uri = explode( $base, $uri );
		$uri = array_filter( array_unique( $uri ) );
		$uri = end( $uri );
		$uri = explode( '/', $uri );
		$uri = array_filter( array_unique( $uri ) );
		reset($uri);
		if( empty( $uri[key($uri)] ) ) { return; }
		$user = get_user_by( 'slug', (string) $uri[key($uri)] );
	}
	$GLOBALS['wpc']['recipient'] = $user;
});

add_action("init", function() { 
	if ( false !== $active_trans = get_option( "wpc_active_translation" ) ) {
		if ( false !== $trans = json_decode( base64_decode( get_option( "wpc_transaltion_" . str_replace( " ", "_", $active_trans ) ) ), true ) ) {
			$GLOBALS['wpc_translate'] = $trans;
		}
	}
});
add_action("init", function() { 
	$GLOBALS['wpc_current_user_stats'] = wpc_get_stats();
	//$GLOBALS['wpc_displayed_user_stats'] = wpc_get_stats();
});