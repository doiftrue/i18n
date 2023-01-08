<?php

0&& ( is_admin() || add_action( 'get_header', 'debug_rewrite', 90 ) );
function debug_rewrite() {
	global $wp_rewrite;
	global $wp_query;

	print_r( $wp_query );
	print_r( $wp_rewrite );
	exit;
}

0&& add_filter( 'query_vars', function( $public_query_vars ) {
	die( print_r( $public_query_vars ) );

	return $public_query_vars;
} );

0&& add_filter( 'pre_get_posts', function( $query ) {
	die( print_r( $query ) );
	unset( $query->query['lang'], $query->query_vars['lang'] );
} );

0&& add_filter( 'request', function( $query ) {
	die( print_r( $query ) );
	unset( $query->query['lang'], $query->query_vars['lang'] );
} );

0&& add_filter( 'pre_handle_404', function( $false, $wp_query ) {
	die( print_r( $wp_query ) );
}, 10, 2 );

