<?php

namespace HWDSB\OneDrive\API;

use HWDSB\OneDrive as App;

/**
 * Call Microsoft OAuth2 API.
 *
 * @param string $path Relative path to endpoint.
 * @param array  $r    Various arguments.
 */
function oauth( $path = '', $r = [] ) {
	require_once __DIR__ . '/get.php';

	$endpoint = 'https://login.microsoftonline.com/common/oauth2/v2.0/';
	$r = array_merge( [
		'method'       => 'POST',
		'client_id'    => App\Get\client_id(),
		'redirect_uri' => network_site_url( '/' ),
	], $r );

	if ( 'token' === $path ) {
		$r['client_secret'] = App\Get\client_secret();
	}

	$method = strtolower( $r['method'] );
	unset( $r['method'] );

	$endpoint = $endpoint . $path;

	switch ( $method ) {
		case 'endpoint' :
		case 'get' :
			$endpoint = add_query_arg( $r, $endpoint );
			$r = [];

			if ( 'endpoint' === $method ) {
				return $endpoint;
			}

			break;

		case 'post' :
			$r = [ 'body' => $r ];

			break;
	}

	// Only support POST and GET at this time.
	$caller = "wp_remote_{$method}";
	if ( ! function_exists( $caller ) ) {
		return false;
	}

	$call = $caller( $endpoint, $r );
	$call = wp_remote_retrieve_body( $call );

	// JSON decode if JSON.
	if ( is_string( $call ) && 0 === strpos( $call, '{' ) && '}' === substr( $call, -1 ) ) {
		$call = json_decode( $call );
	}

	return $call;
}

/**
 * Call Microsoft Graph API.
 *
 * @param string $path Relative path to endpoint. Must begin with forward slash.
 * @param array  $r    Various arguments.
 */
function graph( $path = '', $r = [] ) {
	$endpoint = 'https://graph.microsoft.com/v1.0';
	$r = array_merge( [
		'method' => 'GET',
		'token'  => '',
	], $r );

	$method = strtolower( $r['method'] );
	$token  = $r['token'];
	unset( $r['method'], $r['token'] );

	$endpoint = $endpoint . $path;

	switch ( $method ) {
		case 'endpoint' :
		case 'get' :
			$endpoint = add_query_arg( $r, $endpoint );
			$r = [];

			if ( 'endpoint' === $method ) {
				return $endpoint;
			}

			break;

		case 'post' :
			$r = [ 'body' => $r ];

			break;
	}

	// Get our access token.
	if ( empty( $token ) ) {
		require_once __DIR__ . '/get.php';
		$token = App\Get\access_token();
	}

	if ( empty( $token ) || is_wp_error( $token ) ) {
		//ray_log( 'onedrive refresh token error: ' . print_r( $token, true ) );
		return new \WP_Error( 'onedrive_refresh_token_required' );
	}

	// Add our access token to the headers.
	$r['headers'] = [
		'Authorization' => sprintf( 'bearer %s', $token )
	];

	// Only support POST and GET at this time.
	$caller = "wp_remote_{$method}";
	if ( ! function_exists( $caller ) ) {
		return false;
	}

	$call = $caller( $endpoint, $r );
	$call = wp_remote_retrieve_body( $call );

	// JSON decode if JSON.
	if ( is_string( $call ) && 0 === strpos( $call, '{' ) && '}' === substr( $call, -1 ) ) {
		$call = json_decode( $call );
	}

	return $call;
}
