<?php

namespace HWDSB\OneDrive\Get;

use HWDSB\OneDrive as App;

/**
 * Get the client ID for OneDrive.
 *
 * @return string
 */
function client_id() {
	return apply_filters( 'hwdsb_onedrive_get_client_id', HWDSB_ONEDRIVE_CLIENT_ID );
}

/**
 * Get the client secret for OneDrive.
 *
 * @return string
 */
function client_secret() {
	return apply_filters( 'hwdsb_onedrive_get_client_secret', HWDSB_ONEDRIVE_CLIENT_SECRET );
}

/**
 * Get the scope of link to create for OneDrive.
 *
 * Defaults to 'anonymous'. For those using SharePoint or OneDrive for
 * Business, you might want to set this to 'organization' if you want
 * to only share links within your organization.
 *
 * @return string
 */
function share_scope() {
	return apply_filters( 'hwdsb_onedrive_get_share_scope', 'anonymous' );
}

/**
 * Get the type of sharing link to create for OneDrive.
 *
 * Defaults to 'embed'. Embed only works for OneDrive Personal. For those
 * using SharePoint or OneDrive for Business, you will need to set this
 * to 'view'.
 *
 * @return string
 */
function share_type() {
	$type = 'embed';
	if ( defined( 'HWDSB_ONEDRIVE_SHARE_TYPE' ) ) {
		$type = HWDSB_ONEDRIVE_SHARE_TYPE;
	}
	return apply_filters( 'hwdsb_onedrive_get_share_type', $type );
}

/* THE FOLLOWING ARE NOT USED AT THE MOMENT ******************************/

/**
 * Get access token for OneDrive.
 *
 * @return string|WP_Error Access token string on success, WP_Error on failure.
 */
function access_token() {
	$refresh = refresh_token();
	if ( empty( $refresh ) || ! empty( $refresh->error ) ) {
		if ( ! empty( $refresh->error ) ) {
			return new \WP_Error( 'onedrive_' . $refresh->error, $refresh->error_description );
		}
		return new \WP_Error( 'onedrive_no_existing_token' );
	}

	// Token expired or is within 5 minutes of expiring, so get new token.
	if ( $refresh->time + $refresh->expires_in <= time() + 300 ) {
		// Ping MS OAuth for new token.
		require_once __DIR__ . '/api.php';

		$scope = apply_filters( 'hwdsb_onedrive_access_token_scope', 'profile openid user.read files.readwrite.all' );

		$token = App\API\oauth( 'token', [
			'client_secret' => client_secret(),
			'refresh_token' => $refresh->refresh_token,
			'grant_type'    => 'refresh_token',
			'scope'         => $scope
		] );

		// Got new token!
		if ( ! empty( $token ) && is_object( $token ) && ! empty( $token->access_token ) ) {
			// Add timestamp to token so we can check expiry later.
			$token->time = time();

			// Update token.
			update_user_meta( get_current_user_id(), refresh_token_key(), $token );

			// Return access token.
			return $token->access_token;

		// User probably revoked OneDrive access to the app.
		} else {
			// Remove older access token data.
			delete_user_meta( get_current_user_id(), refresh_token_key() );

			// Return error.
			return new \WP_Error( 'onedrive_invalid_refresh_token_during_fetch' );
		}
	} else {
		return $refresh->access_token;
	}
}

/**
 * Get refresh token data for OneDrive.
 *
 * @return object
 */
function refresh_token() {
	return apply_filters( 'hwdsb_onedrive_get_refresh_token', get_user_meta( get_current_user_id(), refresh_token_key(), true ) );
}

/**
 * Get refresh token key to be used with user meta.
 *
 * @return string
 */
function refresh_token_key() {
	return apply_filters( 'hwdsb_onedrive_get_refresh_token_key', 'hwdsb_onedrive_refresh_token' );
}

/**
 * Get the user profile URL.
 *
 * If BuddyPress is available, we prefer that. Otherwise, will fallback
 * to the user's admin profile URL.
 *
 * @return string
 */
function user_profile_url() {
	if ( function_exists( 'buddypress' ) && bp_is_active( 'settings' ) ) {
		$url = trailingslashit( bp_loggedin_user_domain() . bp_get_settings_slug() );
	} else {
		$url = get_admin_url( get_current_site_id(), 'profile.php' );
	}

	/**
	 * Filters the user profile URL.
	 *
	 * @param string $url User profile URL.
	 * @return string
	 */
	return apply_filters( 'hwdsb_onedrive_get_user_profile_url', $url . '#onedrive-auth' );
}
