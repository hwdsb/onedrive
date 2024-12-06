<?php
/*
Plugin Name: OneDrive
Version: 0.1-alpha
Description: Use the OneDrive block to embed items with the file's sharing link. For Classic Editor users, the [onedrive] shortcode is available for use.
Author: r-a-y
Text Domain: onedrive
Domain Path: /languages
*/

namespace HWDSB\OneDrive;

const DIR = __DIR__;

defined( 'ABSPATH' ) or die();

/**
 * Shortcode initializer.
 */
function shortcode_init() {
	/**
	 * Filters the shortcode tag.
	 *
	 * @param string $tag Defaults to 'onedrive'.
	 */
	$shortcode_tag = apply_filters( 'hwdsb_onedrive_shortcode_tag', 'onedrive' );

	add_shortcode( $shortcode_tag, __NAMESPACE__ . '\\shortcode' );
}
add_action( 'init', __NAMESPACE__ . '\\shortcode_init' );

/**
 * Gutenberg initializer.
 */
function gutenberg_init() {
	// Register assets.
	wp_register_script( 'hwdsb-onedrive-block', plugins_url( basename( dirname( __FILE__ ) ) ) . '/assets/block.js', array(
		'wp-blocks', 'wp-i18n', 'wp-element'
	), '20200507' );

	wp_register_style( 'hwdsb-onedrive-block',      plugins_url( basename( dirname( __FILE__ ) ) ) . '/assets/block.css', array(), '' );
	wp_register_style( 'hwdsb-onedrive-block-icon', plugins_url( basename( dirname( __FILE__ ) ) ) . '/assets/icon.css', array(), '' );

	// Register block type.
	register_block_type( 'hwdsb/onedrive', array(
		'editor_script'   => 'hwdsb-onedrive-block',
		'editor_style'    => 'hwdsb-onedrive-block',
		'style'           => 'hwdsb-onedrive-block-icon',
		'render_callback' => __NAMESPACE__ . '\\shortcode',
	) );
}
add_action( 'init', __NAMESPACE__ . '\\gutenberg_init' );

/**
 * MS File Picker integration.
 */
function picker_integration() {
	if ( is_picker_enabled() ) {
		require __DIR__ . '/includes/picker.php';
	}
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\picker_integration' );

/**
 * Enqueues block assets.
 */
function block_assets() {
	// Default props.
	$props = [
		'isPickerEnabled' => is_picker_enabled(),
		'helpUrl'         => 'https://github.com/hwdsb/onedrive/wiki/Sharing-a-file-and-getting-the-link',
		'defaultWidth'    => ! empty( $GLOBALS['content_width'] ) ? $GLOBALS['content_width'] : 640,
		'defaultHeight'   => 300
	];

	/**
	 * Filter to alter the block JS props.
	 *
	 * @param array $props
	 */
	$props = apply_filters( 'hwdsb_onedrive_block_js_props', $props );
	$props['helpUrl'] = esc_url_raw( $props['helpUrl'] );

	wp_localize_script( 'hwdsb-onedrive-block', 'hwdsbOneDriveProps', $props );
}
add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\\block_assets', 11 );

/**
 * Shortcode.
 *
 * @param  array $r Shortcode attributes.
 * @return string
 */
function shortcode( $r ) {
	$r = shortcode_atts( array(
		'link'   => false,

		// type
		'type'   => 'other',

		// sharepoint unique ID
		'uniqueID' => '',

		// dimensions
		'width'  => '',
		'height' => '',

		// size (only for presentations)
		'size' => false,

		// toggle display. only accepts 'icon' for unsupported formats.
		'display' => '',
		'title'   => ''
	), $r );

	// If no link or link is not from OneDrive, stop now!
	if ( ! $r['link'] ||
		( false === strpos( $r['link'], '.sharepoint.com/' ) &&
			false === strpos( $r['link'], '://onedrive.live.com/embed' ) &&
			false === strpos( $r['link'], '://onedrive.live.com/redir' ) &&
			false === strpos( $r['link'], '://1drv.ms/' )
		)
	) {
		return;
	}

	/*
	 * Expand shortlink if necessary.
	 *
	 * Our Gutenberg integration already auto-expands these links, so this is
	 * really only for shortcode users.
	 */
	if ( false !== strpos( $r['link'], '://1drv.ms/' ) ) {
		$cachekey = 'od_1drv_' . md5( $r['link'] );

		// Check cache first.
		$post_id = get_the_ID();
		if ( ! empty( $post_id ) ) {
			$cache = get_post_meta( $post_id, $cachekey, true );

			// Use cache if exists.
			if ( ! empty( $cache ) && '{{unknown}}' !== $cache ) {
				$r['link'] = $cache;

			// Expand.
			} elseif ( '' === $cache ) {
				$location = wp_remote_retrieve_header( wp_remote_head( $r['link'] ), 'Location' );

				// Got the link!
				if ( '' !== $location ) {
					// Cache it.
					update_post_meta( $post_id, $cachekey, $location );

					// Set the link for our shortcode.
					$r['link'] = $location;

				// Cache failures as well.
				} else {
					update_post_meta( $post_id, $cachekey, '{{unknown}}' );
				}
			}
		}
	}

	// See if link is from a personal OneDrive account.
	$is_personal = false;
	if ( 0 === strpos( $r['link'], 'https://onedrive.live.com/' ) ) {
		$is_personal = true;
	}

	// Switch to embed mode for OneDrive Personal items.
	$r['link'] = str_replace( 'redir?', 'embed?', $r['link'] );

	/*
	 * Special sauce for embedding OneDrive Personal items.
	 *
	 * OneDrive Personal - https://onedrive.live.com/redir?resid=XXX&authkey=XXX&ithint=XXX
	 */
	if ( $is_personal ) {
		// Special sauce for embedding!
		$r['link'] = add_query_arg( 'em', 2, $r['link'] );

		// Set the correct type if available from querystring.
		if ( 'other' === $r['type'] ) {
			$qs = parse_url( $r['link'], PHP_URL_QUERY );
			parse_str( $qs, $qs );
			if ( ! empty( $qs['ithint'] ) ) {
				$r['type'] = get_type_from_ext( substr( $qs['ithint'], strpos( $qs['ithint'], ',' ) + 1 ) );
			}
		}
	}

	$type = $r['type'];

	// SharePoint sometimes uses this URL scheme. Try to determine ext.
	if ( ! $is_personal && 'other' === $type && false !== strpos( $r['link'], 'sourcedoc=' ) ) {
		$ext = parse_url( $r['link'], PHP_URL_QUERY );
		parse_str( $ext, $ext );
		if ( ! empty( $ext['file'] ) ) {
			$ext = substr( $ext['file'], strpos( $ext['file'], '.' ) + 1 );
		} elseif ( ! empty( $ext['id'] ) ) {
			$ext = substr( $ext['id'], strpos( $ext['id'], '.' ) + 1 );
		} else {
			$ext = '';
		}

		$type = get_type_from_ext( $ext );

		// This URL format doesn't work for embeds though, so set display to 'icon'.
		$r['display'] = 'icon';
	}

	// Core types.
	if ( 'word' === $type || false !== strpos( $r['link'], '/:w:/' ) ) {
		$type = 'word';

		if ( empty( $r['width'] ) && empty( $r['height'] ) ) {
			$r['width']  = 476;
			$r['height'] = 288;
		}

	} elseif ( 'powerpoint' === $type || false !== strpos( $r['link'], '/:p:/' ) ) {
		$type = 'powerpoint';

		// Dimensions.
		switch ( $r['size'] ) {
			case 'L' :
			case 'large' :
				$r['width']  = 962;
				$r['height'] = 565;
				break;

			case 'XL' :
			case 'extra' :
				$r['width']  = 1186;
				$r['height'] = 691;
				break;

			case 'S' :
			case 'small' :
				$r['width']  = 350;
				$r['height'] = 221;
				break;

			default :
			case 'M' :
			case 'medium' :
				$r['width']  = 610;
				$r['height'] = 367;
				break;
		}

	} elseif ( 'onenote' === $type || false !== strpos( $r['link'], '/:o:/' ) ) {
		$type = 'onenote';

	} elseif ( 'excel' === $type || false !== strpos( $r['link'], '/:x:/' ) ) {
		$type = 'excel';

		// Default width if not set.
		if ( empty( $r['width'] ) && empty( $r['height'] ) ) {
			$r['width']  = 402;
			$r['height'] = 346;
		}
	}

	// Embed audio, video or images.
 	if ( 'audio' === $type || 'video' === $type || 'image' === $type ) {
		// Only works for OneDrive personal accounts.
		if ( $is_personal ) {
			$r['link'] = str_replace( 'embed?', 'download?', $r['link'] );
		}
	}

	// Non-embeddables. Set display to 'icon'.
	switch ( $type ) {
		case 'archive' :
		case 'onenote' :
			$r['display'] = 'icon';
			break;
	}

	$is_sharepoint_unique_id = false;

	// SharePoint.
	if ( ! $is_personal ) {
		// Only word, powerpoint, excel and visio can be embedded.
		if ( 'word' === $type || 'powerpoint' === $type || 'excel' === $type || 'visio' === $type ) {
			/*
			 * Add 'embedview' action for Sharepoint links.
			 *
			 * Requires files to be shared with the "Anyone with the link can view"
			 * setting.
			 */
			if ( false === strpos( $r['link'], 'action=embedview' ) && false === strpos( $r['link'], 'action=' ) ) {
				$r['link'] = add_query_arg( 'action', 'embedview', $r['link'] );
			} else {
				$r['link'] = str_replace( 'action=edit', 'action=embedview', $r['link'] );
			}

		// If have unique ID, use a different URL syntax for certain types.
		} elseif ( true === in_array( $type, [ 'video', 'audio', 'image', 'pdf' ] ) && ! empty( $r['uniqueID'] ) ) {
			$is_sharepoint_unique_id = true;

			$original_link = $r['link'];

			// Need to set to 'other' so iframe is used.
			$type = 'other';

			// Grab the OneDrive user from the link.
			preg_match( '~/personal/(.*)/~', $r['link'], $user );

			$r['link'] = sprintf(
				'https://%1$s/personal/%2$s/_layouts/15/embed.aspx?UniqueId=%3$s&embed=%4$s&referrer=StreamWebApp&referrerScenario=EmbedDialog.Create',
				parse_url( $r['link'], PHP_URL_HOST ),
				$user[1],
				$r['uniqueID'],
				urlencode( '{"ust":true,"hv":"CopyEmbedCode"}' )
			);

		// All other types will use icon display.
		} else {
			$r['display'] = 'icon';
		}
	}

	// Default width for non-images if empty.
	if ( empty( $r['width'] ) && 'image' !== $type ) {
		$r['width'] = $r['width'] ?: '640';
	}

	// Set width.
	if ( ! empty( $r['width'] ) ) {
		$r['width'] = sprintf( ' width="%s"', $r['width'] );
	}

	// Default height for non-images if empty.
	if ( empty( $r['height'] ) && 'image' !== $type ) {
		$r['height'] = $r['height'] ?: '300';
	}

	// Set height.
	if ( ! empty( $r['height'] ) ) {
		$r['height'] = sprintf( ' height="%s"', $r['height'] );
	}

	// Icon.
	if ( 'icon' === $r['display'] ) {
		switch ( $type ) {
			case 'onenote' :
				$ext = 'one';
				break;

			case 'image' :
				$ext = 'photo';
				break;

			case 'archive' :
				$ext = 'zip';
				break;

			default :
				$ext = $type;
				break;
		}

		// Icons that exist on Akamai CDN.
		$exts = [ 'one', 'photo', 'audio', 'video', 'pdf', 'zip', 'rtf', 'html', 'code' ];

		// Fallback to 'txt' icon if ext doesn't exist.
		if ( ! in_array( $ext, $exts ) ) {
			$ext = 'txt';
		}

		$r['icon'] = "https://spoprod-a.akamaihd.net/files/fabric-cdn-prod_20201207.001//assets/item-types/64/{$ext}.png";

		// Some icons are located on a different CDN.
		if ( 'word' === $type || 'powerpoint' === $type || 'excel' === $type || 'visio' === $type ) {
			switch ( $type ) {
				case 'word' :
					$ext = 'Doc';
					break;

				case 'powerpoint' :
					$ext = 'Ppt';
					break;

				case 'excel' :
					$ext = 'Xls';
					break;

				case 'visio' :
					$ext = 'Vsd';
					break;
			}
			$r['icon'] = "https://p.sfx.ms//icons/v2/Large/{$ext}.png";

		}

		// Switch link to download for Personal accounts.
		if ( $is_personal ) {
			$r['link'] = str_replace( 'embed?', 'download?', $r['link'] );

			// Some types do not support downloads, so use web link.
			if ( 'onenote' === $type ) {
				$r['link'] = str_replace( 'download?', 'redir?', $r['link'] );
			}
		}

		if ( empty( $r['title'] ) ) {
			if ( $is_personal && 'onenote' !== $type ) {
				$r['title'] = __( 'Download file', 'onedrive' );
			} else {
				$r['title'] = __( 'View file', 'onedrive' );
			}
		}

		$desc = sprintf( '<strong><a href="%1$s">%2$s</a></strong>', esc_url( $r['link'] ), esc_html( $r['title'] ) );

		// 'default-max-width' is for 2021 theme.
		$output = sprintf( '<div class="onedrive-block-icon default-max-width"><img src="%1$s" width="48" height="48" alt="File icon" /><div class="onedrive-block-icon-description">%2$s</div></div>', $r['icon'], $desc );

	// Audio.
	} elseif ( 'audio' === $type ) {
		$output = sprintf( '<figure class="wp-block-audio"><audio class="onedrive-shortcode" controls><source src="%1$s">%2$s</audio></figure>', esc_url( $r['link'] ), esc_html__( 'Your browser does not support HTML5 audio', 'onedrive' ) );

	// Video.
	} elseif ( 'video' === $type ) {
		$output = sprintf( '<figure class="wp-block-video"><video class="onedrive-shortcode" controls preload="metadata"><source src="%1$s">%2$s</video></figure>', esc_url( $r['link'] ), esc_html__( 'Your browser does not support HTML5 video', 'onedrive' ) );

	// Image.
	} elseif ( 'image' === $type ) {
		$output = '<figure class="wp-block-image"><img class="onedrive-shortcode" alt="" src="' . esc_url( $r['link'] ) . '"' . $r['width'] . $r['height'] . ' /></figure>';

	// Iframe everything else.
	} else {
		// Extra iframe args.
		$extra = ' frameborder="0"';

		$output = '<iframe id="onedrive-' . md5( $r['link'] ) . '" class="onedrive-shortcode" src="' .  esc_url( $r['link'] ) . '"' . $r['width'] . $r['height'] . $extra . '></iframe>';
	}

	/*
	 * Embed sharing link if this is a SharePoint unique ID.
	 *
	 * This is necessary to set the authentication cookies to view the embed.
	 */
	if ( $is_sharepoint_unique_id ) {
		$output = sprintf( '<iframe src="%1$s" width="0" height="0" style="display:none;"></iframe>%2$s', esc_url( $original_link ), $output );
	}

	// Wrap output in <figure> because of Gutenberg.
	$output = sprintf( '<figure class="wp-block-embed wp-block-embed-onedrive"><div class="wp-block-embed__wrapper">%s</div></figure>', $output );

	/**
	 * Filters the shortcode output.
	 *
	 * @param  string $output HTML markup.
	 * @param  array  $r      Shortcode params.
	 * @param  string $type   Type of OneDrive embed.
	 * @return string
	 */
	return apply_filters( 'hwdsb_onedrive_shortcode', $output, $r, $type );
}

/**
 * Get file type based on file extension.
 *
 * @param  string $ext File extension.
 * @return string
 */
function get_type_from_ext( $ext ) {
	switch ( $ext ) {
		case 'pptx' :
			$type = 'powerpoint';
			break;

		case 'docx' :
			$type = 'word';
			break;

		case 'xlsx' :
			$type = 'excel';
			break;

		case 'one' :
			$type = 'onenote';
			break;

		case 'vsd' :
			$type = 'visio';
			break;

		case 'mp3' :
		case 'wav' :
		case 'flac' :
		case 'opus' :
		case 'ogg' :
		case 'aac' :
			$type = 'audio';
			break;

		case 'webm' :
		case 'mp4' :
		case 'ogm' :
		case 'ogv' :
		case 'avi' :
			$type = 'video';
			break;

		case 'png' :
		case 'jpg' :
		case 'jpeg' :
		case 'gif' :
		case 'webp' :
		case 'bmp' :
			$type = 'image';
			break;

		case 'zip' :
		case 'rar' :
		case '7z' :
		case 'tar' :
		case 'gz' :
		case 'bz' :
		case 'bz2' :
		case 'arc' :
			$type = 'archive';
			break;

		case 'pdf' :
			$type = 'pdf';
			break;

		case 'rtf' :
			$type = 'rtf';
			break;

		case 'js' :
		case 'json' :
		case 'css' :
		case 'xml' :
		case 'sh' :
			$type = 'code';
			break;

		case 'html' :
		case 'htm' :
			$type = 'html';
			break;

		default :
			$type = 'other';
			break;
	}

	return $type;
}

/**
 * See if the file picker is enabled.
 *
 * @return bool
 */
function is_picker_enabled() {
	/**
	 * Filter to enable the picker or not.
	 *
	 * @param bool $enabled
	 */
	return apply_filters( 'hwdsb_onedrive_is_picker_enabled', defined( 'HWDSB_ONEDRIVE_CLIENT_ID' ) );
}

/**
 * Link expander AJAX callback.
 *
 * Currently, only OneDrive Personal shortlinks use this.
 */
add_action( 'wp_ajax_hwdsb-onedrive-expand', function() {
	if ( empty( $_POST['url'] ) ) {
		wp_send_json_error( [ 'type' => 'url-empty' ] );
	}

	$expanded = wp_remote_retrieve_header( wp_remote_head( $_POST['url'] ), 'Location' );
	if ( ! empty( $expanded ) ) {
		//ray_log( 'expanded: ' . $expanded );
		wp_send_json_success( [ 'url' => $expanded ] );
	}

	wp_send_json_error( [ 'type' => 'expand-url-fail' ] );
} );

/**
 * Verify picker nonce.
 */
add_action( 'wp_ajax_hwdsb-onedrive-verify-nonce', function() {
	if ( empty( $_POST['_ajax_nonce'] ) ) {
		wp_send_json_error( [ 'type' => 'nonce-empty' ] );
	}

	if ( wp_verify_nonce( $_POST['_ajax_nonce'], 'onedrive-picker' ) ) {
		wp_send_json_success();
	}

	wp_send_json_error( [ 'type' => 'nonce-fail' ] );
} );

/**
 * Adds an "Instructions" link to the plugin action row in the admin area.
 */
add_filter( 'plugin_action_links_onedrive/onedrive.php', function( $actions ) {
	$actions['instructions'] = sprintf( '<a href="%1$s" target="_blank">%2$s</a>',
		'https://github.com/hwdsb/onedrive#how-to-use',
		esc_html__( 'Instructions', 'onedrive' )
	);
	return $actions;
} );