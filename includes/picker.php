<?php

namespace HWDSB\OneDrive\Picker;

use HWDSB\OneDrive as App;

/**
 * Alter block JS properties for File Picker.
 *
 * @param  array $props Block JS props.
 * @return array
 */
add_filter( 'hwdsb_onedrive_block_js_props', function( $props ) {
	// Enqueue the OneDrive JS SDK.
	wp_enqueue_script( 'onedrive-sdk', 'https://js.live.net/v7.2/OneDrive.js', [], '7.2' );

	/*
	 * Use the base network URL for the picker.
	 *
	 * This is mostly for those using multisite and might be using a
	 * different root blog ID.
	 */
	$relative_url = plugins_url( basename( App\DIR ) ) . '/picker.html';
	$relative_url = str_replace( home_url(), '', $relative_url );
	$picker_url   = get_home_url( get_main_site_id(), $relative_url );

	// Set up the rest of the picker props.
	require_once __DIR__ . '/get.php';

	/*
	$access_token = App\Get\access_token();
	if ( is_wp_error( $access_token ) ) {
		$access_token = 0;
	}
	*/

	$user = wp_get_current_user();

	$props['picker'] = [
		'clientId' => App\Get\client_id(),
		'redirect' => $picker_url,
		'token'    => 0,
		//'profile'  => App\Get\user_profile_url(),
		'type'     => App\Get\share_type(),
		'scope'    => App\Get\share_scope(),
		'label'    => esc_html__( 'Or Select From Drive', 'onedrive' ),
		'nonce'    => wp_create_nonce( 'onedrive-picker' ),
		'hint'     => $user ? $user->user_email : 0
	];

	return $props;
}, 5 );

/**
 * OAuth callback receiver.
 *
 * This isn't used at the moment.
 */
add_action( 'init', function() {
	if ( ! is_user_logged_in() || empty( $_POST ) || empty( $_POST['state'] ) || 0 !== strpos( $_POST['state'], 'onedrive-auth-code-' ) ) {
		return;
	}

	$nonce = str_replace( 'onedrive-auth-code-', '', $_POST['state'] );
	if ( ! wp_verify_nonce( $nonce, 'hwdsb-onedrive-auth-code' ) ) {
		wp_die( 'Security verification during OneDrive authorization failed.' );
	}

	// Now fetch token.
	require_once __DIR__ . '/api.php';
	$token = App\API\oauth( 'token', [
		'grant_type' => 'authorization_code',
		'code'       => $_POST['code']
	] );

	$message_type = 'success';
	$message = esc_html__( 'You have successfully authenticated your OneDrive.', 'onedrive' );

	// Save refresh token.
	if ( ! empty( $token ) && empty( $token->error ) ) {
		require_once __DIR__ . '/get.php';

		$token->time = time();
		update_user_meta( get_current_user_id(), App\Get\refresh_token_key(), $token );

	// Something went wrong.
	} else {
		$message_type = 'error';
		$message = sprintf( esc_html__( 'There was a problem authenticating your OneDrive. Error code %s.', 'onedrive' ), $token->error_codes );
	}

	if ( function_exists( 'buddypress' ) ) {
		bp_core_add_message( $message, 'error' !== $message_type ? '' : $message_type  );
	}

	// Send message back to parent and close popup.
	$domain = get_home_url( get_main_site_id(), '/' );
	die( "<!doctype html><html lang='en'><head><meta charset='UTF-8'><title>Popup</title></head><body><strong>{$message}</strong><script>window.opener.postMessage( '{$message_type}', '{$domain}' );window.close();</script></body></html>" );
}, 0 );

/** 
 * Get temp embeddable URL for SharePoint/Office 365 items.
 *
 * OneDrive Personal doesn't require this. This isn't used at the moment.
 */
add_action( 'wp_ajax_hwdsb-onedrive-preview', function() {
	if ( empty( $_POST['id'] ) ) {
		wp_send_json_error( [ 'type' => 'id-empty' ] );
	}

	if ( empty( $_POST['token'] ) ) {
		wp_send_json_error( [ 'type' => 'token-empty' ] );
	}

	require_once __DIR__ . '/api.php';

	$id = $_POST['id'];
	$ping = App\API\graph( "/me/drive/items/{$id}/preview", [ 'method' => 'POST', 'token' => $_POST['token'] ] );
	if ( is_object( $ping ) && ! empty( $ping->getUrl ) ) {
		wp_send_json_success( [ 'url' => $ping->getUrl ] );
	}

	wp_send_json_error( [ 'type' => 'preview-url-fail' ] );
} );

/** 
 * Get thumbnail for OneDrive item.
 *
 * Only for SharePoint/Office 365 items. Not used at the moment.
 */
add_action( 'wp_ajax_hwdsb-onedrive-thumb', function() {
	if ( empty( $_POST['id'] ) ) {
		wp_send_json_error( [ 'type' => 'id-empty' ] );
	}

	if ( empty( $_POST['token'] ) ) {
		wp_send_json_error( [ 'type' => 'token-empty' ] );
	}

	require_once __DIR__ . '/api.php';

	$id = $_POST['id'];
	$ping = App\API\graph( "/me/drive/items/{$id}/thumbnails?select=large", [ 'token' => $_POST['token'] ] );
	if ( is_object( $ping ) && ! empty( $ping->value ) ) {
		wp_send_json_success( [ 'url' => $ping->value[0]->large->url ] );
	}

	wp_send_json_error( [ 'type' => 'thumb-url-fail' ] );
} );

/**
 * User settings content.
 *
 * Displayed on either the logged-in user's:
 *  1. BuddyPress settings page - "Settings > General"
 *  2. Admin dashboard profile page - "Users > My Profile" (TODO)
 *
 * Not used at the moment because MS File Picker doesn't allow us to pass
 * our access token to the JS API.
 */
function user_settings_content() {
	require_once __DIR__ . '/api.php';
	require_once __DIR__ . '/get.php';

	$scope = apply_filters( 'hwdsb_onedrive_access_token_scope', 'offline_access profile openid user.read files.readwrite.all' );

	$auth_url = App\API\oauth( 'authorize', [
		'method' => 'endpoint',
		'scope'  => $scope,
		'state'  => sprintf( 'onedrive-auth-code-%s', wp_create_nonce( 'hwdsb-onedrive-auth-code' ) ),
		'response_type' => 'code',
		'response_mode' => 'form_post',
	] );

?>

	<?php if ( is_wp_error( App\Get\access_token() ) ) : ?>

		<div id="onedrive-auth">
			<a href="<?php echo $auth_url; ?>" onclick="popupWindow(this.href,'oneDriveAuth',360,500); return false;"><img src="https://docs.microsoft.com/en-us/azure/active-directory/develop/media/howto-add-branding-in-azure-ad-apps/ms-symbollockup_signin_light.png" width="215" height="41" alt="<?php esc_html_e( 'Sign-in with Microsoft', 'onedrive' ); ?>" /></a>
			<p class="description"><?php _e( "We'll open a new page to help you connect to your OneDrive account.", 'onedrive' ); ?></p>

			<p class="description"><?php printf( __( 'Authenticating will allow you to easily embed items from your drive when writing posts with the OneDrive block.', 'onedrive' ), '<strong>', '</strong>' ); ?></p>
		</div>

		<script>
window.addEventListener('message',function(e) {
	console.log(e);
	if ( e.currentTarget.location.href !== '<?php echo bp_loggedin_user_domain() . 'settings/'; ?>' ) return;
	window.location.reload();
}, false);

function popupWindow(url, windowName, w, h) {
    return window.open(url, windowName, `toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=${w}, height=${h}`);
}
		</script>

	<?php else : ?>

		<div id="onedrive-auth">
			<p><?php _e( 'You have allowed us to access your OneDrive.', 'onedrive' ); ?></p>

			<p><?php printf( __( 'To embed items from your OneDrive, use the OneDrive block when writing your post in the editor.', 'onedrive' ) ); ?></p>

			<p><?php _e( 'To disallow access to your OneDrive, remove access by managing your Microsoft apps below:', 'onedrive' ); ?></p>

			<a href="https://account.live.com/consent/Manage" class="button button-secondary"><?php _e( 'Manage Microsoft apps', 'onedrive' ); ?></a>
		</div>

	<?php endif; ?>

<?php
}

/**
 * Add markup to BuddyPress "Settings > General" page
 *
 * Not used at the moment because MS File Picker doesn't allow us to pass
 * our access token to the JS API.
 */
function buddypress_user_settings() {
?>

	<div id="onedrive-settings">
		<h3><?php _e( 'OneDrive', 'onedrive' ); ?></h3>

		<?php user_settings_content(); ?>
	</div>

<?php
}
//add_action( 'bp_core_general_settings_after_submit', __NAMESPACE__ . '\\buddypress_user_settings', 0 );