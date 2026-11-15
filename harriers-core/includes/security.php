<?php
/**
 * Light security hardening. Each item is a common WordPress hardening step
 * that a scan would otherwise flag. Any of them can be switched off with the
 * filter named in its comment, e.g. add_filter( 'hs_disable_xmlrpc', '__return_false' );
 *
 * Not done here (set these at the host or with a dedicated plugin):
 *   - a Content-Security-Policy header
 *   - HTTPS redirect and HSTS
 *   - login rate limiting / two-factor authentication
 *   - a web application firewall
 */

defined( 'ABSPATH' ) || exit;

/* 1. Don't list user accounts (login names) to the public over the REST API. Filter: hs_hide_rest_users */
add_filter( 'rest_endpoints', function ( $endpoints ) {
	if ( is_user_logged_in() || ! apply_filters( 'hs_hide_rest_users', true ) ) {
		return $endpoints;
	}
	foreach ( array_keys( $endpoints ) as $route ) {
		if ( 0 === strpos( $route, '/wp/v2/users' ) ) {
			unset( $endpoints[ $route ] );
		}
	}
	return $endpoints;
} );

/* 2. Stop ?author=1 style lookups and author archives revealing login names. Filter: hs_block_author_scans */
add_action( 'template_redirect', function () {
	if ( is_user_logged_in() || ! apply_filters( 'hs_block_author_scans', true ) ) {
		return;
	}
	if ( is_author() || isset( $_GET['author'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only check, no action taken on input.
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}, 1 ); // Before WordPress's own canonical redirect, which would reveal /author/<login>/.
add_filter( 'oembed_response_data', function ( $data ) {
	unset( $data['author_name'], $data['author_url'] );
	return $data;
} );

/* 3. Turn off XML-RPC, an old remote-publishing interface the club doesn't use and a common brute-force target.
   Filter: hs_disable_xmlrpc (return false if you use the Jetpack plugin or the WordPress mobile app with an old setup). */
if ( apply_filters( 'hs_disable_xmlrpc', true ) ) {
	add_filter( 'xmlrpc_enabled', '__return_false' );
	add_filter( 'wp_headers', function ( $headers ) {
		unset( $headers['X-Pingback'] );
		return $headers;
	} );
}

/* 4. Don't advertise the WordPress version. */
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

/* 5. Safe default response headers. Filter: hs_security_headers (array) */
add_action( 'send_headers', function () {
	if ( headers_sent() ) {
		return;
	}
	$headers = apply_filters( 'hs_security_headers', array(
		'X-Content-Type-Options' => 'nosniff',
		'X-Frame-Options'        => 'SAMEORIGIN',
		'Referrer-Policy'        => 'strict-origin-when-cross-origin',
		'Permissions-Policy'     => 'camera=(), microphone=(), geolocation=(), payment=()',
	) );
	foreach ( $headers as $name => $value ) {
		header( $name . ': ' . $value );
	}
} );

/* 6. Generic login error, so a failed login doesn't confirm whether a username exists. */
add_filter( 'login_errors', function () {
	return 'The username or password is incorrect.';
} );
