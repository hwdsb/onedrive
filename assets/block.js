( function( blocks, i18n, element, _ ) {
	const el = element.createElement,
		Component = element.Component,
		current = new URL( window.location.href );

	let redirect;
	if ( hwdsbOneDriveProps.isPickerEnabled ) {
		redirect = new URL( hwdsbOneDriveProps.picker.redirect );
	}

	// https://stackoverflow.com/a/21976486
	const isTrue = function( value ){
		if (typeof(value) === 'string'){
			value = value.trim().toLowerCase();
		}
		switch(value){
			case true:
			case "true":
			case 1:
			case "1":
			case "on":
			case "yes":
				return true;
			default:
				return false;
		}
	}

	/*
	 * Icon from LibreICONS
	 * https://github.com/DiemenDesign/LibreICONS (MIT License)
	 */
	const getIcon = function() {
		return el('svg', { width: 24, height: 24, viewBox: "0 0 14 14", 'aria-hidden': false, focusable: false, role: 'img', xmlns: "http://www.w3.org/2000/svg" }, el('path', { d: "M 4.8903798,10.506112 C 4.1643214,10.324728 3.7598553,9.7477377 3.7582916,8.8908537 c -5.212e-4,-0.27364 0.019285,-0.404987 0.087565,-0.581159 0.1673113,-0.43157 0.6113901,-0.757332 1.1946344,-0.877212 0.2903191,-0.05942 0.3799688,-0.123529 0.3799688,-0.272077 0,-0.04639 0.034401,-0.185033 0.076619,-0.30804 0.1918087,-0.557705 0.5472802,-1.023154 0.927249,-1.213399 0.3976908,-0.199105 0.5978388,-0.24393 1.0789238,-0.241324 0.682797,0.0036 1.023675,0.151675 1.500069,0.652045 l 0.262173,0.275204 0.234549,-0.08131 c 1.1362584,-0.393 2.2688674,0.276246 2.3600804,1.394782 l 0.02502,0.305956 0.223603,0.08027 c 0.639015,0.228815 0.939237,0.709379 0.88503,1.416152 -0.03544,0.462322 -0.251749,0.8313453 -0.59419,1.0142933 l -0.161056,0.086 -3.5760854,0.0068 c -2.7478682,0.0052 -3.6214316,-0.0047 -3.7725852,-0.04222 l 0,0 z M 2.2092282,10.012518 C 1.7854769,9.9119217 1.336186,9.5371657 1.1256134,9.1092447 1.0062542,8.8663567 0.99999955,8.8303927 0.99999955,8.4009077 c 0,-0.408636 0.0104241,-0.47431 0.10528635,-0.677064 0.2006694,-0.427921 0.5848079,-0.737004 1.0669357,-0.858448 0.1016377,-0.02554 0.1975421,-0.06672 0.2126574,-0.09069 0.015115,-0.02398 0.031794,-0.156887 0.037528,-0.29501 0.033358,-0.855842 0.5952323,-1.610046 1.379146,-1.852413 0.4237512,-0.130826 0.9559161,-0.09851 1.4166738,0.08548 0.1459414,0.05838 0.1297836,0.07089 0.4388666,-0.334102 0.1829479,-0.23976 0.5519711,-0.537898 0.8542782,-0.689573 0.326283,-0.163663 0.665597,-0.239239 1.071105,-0.238197 1.134173,0.0026 2.111459,0.711986 2.4726644,1.794558 0.115711,0.346089 0.109456,0.442515 -0.02658,0.445642 -0.05942,10e-4 -0.2298584,0.03388 -0.3784054,0.07245 l -0.270513,0.07036 -0.246537,-0.246536 C 8.4372786,4.8915367 7.3020626,4.7414257 6.3372865,5.2172987 5.9515843,5.4075437 5.64198,5.6801417 5.4079526,6.0356127 5.2411625,6.2889257 5.028505,6.7621927 5.028505,6.8794667 c 0,0.08339 -0.067237,0.125093 -0.3554715,0.219433 -0.891806,0.292404 -1.4119828,0.967904 -1.4119828,1.832086 0,0.314816 0.08131,0.699476 0.1933723,0.919952 0.042219,0.08339 0.066195,0.1620993 0.052643,0.1756513 -0.034401,0.0344 -1.143555,0.02293 -1.2973146,-0.01355 l 0,0 z" } ) );
	}

	const validateLink = function( link ) {
		if ( ! link ) {
			return false;
		}

		if ( -1 === link.indexOf( '.sharepoint.com/' ) &&
			-1 === link.indexOf( '://onedrive.live.com/embed' ) &&
			-1 === link.indexOf( '://onedrive.live.com/redir' ) &&
			-1 === link.indexOf( '://1drv.ms/' ) &&
			-1 === link.indexOf( '/transform/thumbnail?' )
		) {
			return false;
		}

		// Length check.
		if ( -1 !== link.indexOf( '://1drv.ms/' ) ) {
			if ( link.length < 40 ) {
				return false;
			}
		} else if ( link.length < 70 ) {
			return false;
		}

		return link;
	}

	const expandLink = async function( link ) {
	    try {
			// If not a shortlink, return.
			if ( -1 === link.indexOf( '://1drv.ms' ) ) {
				return link;
			}

			let ping = await wp.ajax.post( 'hwdsb-onedrive-expand', {
				url: link
			} );

			if ( ping.hasOwnProperty( 'url' ) ) {
				link = ping.url;
			}

	        return link;
	    } catch (error) {
	        console.error(error);
	        return false;
	    }
	}

	// Unused at this time.
	const getPreviewLink = async function( id, token ) {
	    try {
			let ping = await wp.ajax.post( 'hwdsb-onedrive-preview', {
				id: id,
				token: token
			} );

			if ( ping.hasOwnProperty( 'url' ) ) {
				return ping.url;
			}

	        return false;
	    } catch (error) {
	        console.error(error);
	        return false;
	    }
	}

	// Unused at this time.
	const getThumb = async function( id, token ) {
	    try {
			let ping = await wp.ajax.post( 'hwdsb-onedrive-thumb', {
				id: id,
				token: token
			} );

			if ( ping.hasOwnProperty( 'url' ) ) {
				return ping.url;
			}

	        return false;
	    } catch (error) {
	        console.error(error);
	        return false;
	    }
	}

	const getTypeFromExt = function( ext ) {
		let type;

		switch ( ext ) {
			case 'pptx' :
				type = 'powerpoint';
				break;
	
			case 'docx' :
				type = 'word';
				break;
	
			case 'xlsx' :
				type = 'excel';
				break;
	
			case 'one' :
				type = 'onenote';
				break;
	
			case 'vsd' :
				type = 'visio';
				break;
	
			case 'mp3' :
			case 'wav' :
			case 'flac' :
			case 'opus' :
			case 'ogg' :
			case 'aac' :
				type = 'audio';
				break;
	
			case 'webm' :
			case 'mp4' :
			case 'ogm' :
			case 'ogv' :
			case 'avi' :
				type = 'video';
				break;
	
			case 'png' :
			case 'jpg' :
			case 'jpeg' :
			case 'gif' :
			case 'webp' :
			case 'bmp' :
				type = 'image';
				break;
	
			case 'zip' :
			case 'rar' :
			case '7z' :
			case 'tar' :
			case 'gz' :
			case 'bz' :
			case 'bz2' :
			case 'arc' :
				type = 'archive';
				break;
	
			case 'pdf' :
				type = 'pdf';
				break;
	
			case 'rtf' :
				type = 'rtf';
				break;
	
			case 'js' :
			case 'json' :
			case 'css' :
			case 'xml' :
			case 'sh' :
				type = 'code';
				break;
	
			case 'html' :
			case 'htm' :
				type = 'html';
				break;

			default :
				type = 'other';
				break;
		}
	
		return type;
	}

	const getType = function( attr ) {
		let link = validateLink( attr.link ),
			type = 'other',
			url = '';

		if ( ! link ) {
			return false;
		}

		// Set the correct type if available from querystring.
		if ( -1 !== link.indexOf( '://onedrive.live.com' ) ) {
			url = new URL( link ).searchParams;
			if ( url.get( 'ithint' ) ) {
				url = url.get( 'ithint' );
				type = getTypeFromExt( url.substr( url.indexOf( ',' ) + 1 ) );
			}
		}

		if ( -1 !== link.indexOf( '/:w:/' ) ) {
			type = 'word';
		} else if ( -1 !== link.indexOf( '/:p:/' ) ) {
			type = 'powerpoint';
		} else if ( -1 !== link.indexOf( '/:o:/' ) ) {
			type = 'onenote';
		} else if ( -1 !== link.indexOf( '/:x:/' ) ) {
			type = 'excel';
		} else if ( attr.hasOwnProperty( 'type' ) && attr.type ) {
			type = attr.type;
		}

		if ( 'other' === type ) {
			url = new URL( link ).searchParams;
			if ( url.get( 'file' ) ) {
				url = url.get( 'file' );
				type = getTypeFromExt( url.substr( url.indexOf( '.' ) + 1 ) );
			} else if ( url.get( 'id' ) ) {
				url = url.get( 'id' );
				type = getTypeFromExt( url.substr( url.indexOf( '.' ) + 1 ) );
			} else {
				type = '';
			}

			type = getTypeFromExt( type );
		}

		return type;
	}

	const getTypeFromMime = function( mime ) {
		switch ( mime ) {
			case 'application/msword' :
			case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' :
			case 'application/vnd.oasis.opendocument.text' :
			//case 'application/x-abiword' :
				return 'word';
				break;

			case 'application/vnd.ms-powerpoint' :
			case 'application/vnd.openxmlformats-officedocument.presentationml.presentation' :
			case 'application/vnd.oasis.opendocument.presentation' :
				return 'powerpoint';
				break;

			case 'application/vnd.ms-excel' :
			case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' :
			case 'application/vnd.oasis.opendocument.spreadsheet' :
				return 'excel';
				break;

			case 'application/vnd.visio' :
				return 'visio';
				break;

			case 'application/pdf' :
				return 'pdf';
				break;

			case 'application/rtf' :
				return 'rtf';
				break;

			case 'text/plain' :
				return 'txt';
				break;

			case 'text/html' :
			case 'application/xhtml+xml' :
				return 'html';
				break;

			case 'text/css' :
			case 'text/javascript' :
			case 'application/xml' :
			case 'text/xml' :
			case 'application/json' :
			case 'application/x-sh' :
				return 'code';
				break;

			case 'application/zip' :
			case 'application/x-7z-compressed' :
			case 'application/gzip' :
			case 'application/vnd.rar' :
			case 'application/x-tar' :
			case 'application/x-bzip' :
			case 'application/x-bzip2' :
			case 'application/x-freearc' :
				return 'archive';
				break;

			// Catch-alls.
			default :
				if ( 0 === mime.indexOf( 'audio/' ) ) {
					return 'audio';

				} else if ( 0 === mime.indexOf( 'video/' ) ) {
					return 'video';

				} else if ( 0 === mime.indexOf( 'image/' ) ) {
					return 'image';
				}

				return 'other';
				break;
		}
	}

	const renderOutput = function( attr ) {
		let link = validateLink( attr.link ),
			invalid = false,
			width = '100%',
			height = '300',
			id = '',
			type = '',
			dl = '',
			output = [];

		if ( ! link ) {
			return '';
		}

		if ( attr.hasOwnProperty( 'width' ) && attr.width ) {
			width = attr.width;
		}

		if ( attr.hasOwnProperty( 'height' ) && attr.height ) {
			height = attr.height;
		}

		type = getType( attr );

		// add query args depending on doc type
		switch ( type ) {
			case 'word' :

				break;

			case 'powerpoint' :
				let size;

				if ( attr.hasOwnProperty( 'size' ) && attr.size ) {
					size = attr.size;
				}

				// dimensions
				switch ( size ) {
					case 'S' :
					case 'small' :
						width = 350;
						height = 221;

						break;

					case 'L' :
					case 'large' :
						width = 962;
						height = 565;

						break;

					case 'XL' :
					case 'extra' :
						width = 1186;
						height = 691;

						break;

					case 'M' :
					case 'medium' :
					default :
						width = 610;
						height = 367;

						break;
				}

				break;

			case 'excel' :
				break;

			case 'video' :
			case 'audio' :
			case 'image' :
				// Only works for OneDrive personal accounts.
				if ( -1 !== link.indexOf( '://onedrive.live.com/' ) || -1 !== link.indexOf( '/transform/thumbnail?' ) ) {
					link = link.replace( 'embed?', 'download?' );
				}

				break;

			// Non-embeddables. Set display to 'icon'.
			case 'archive' :
			case 'onenote' :
				attr.display = 'icon';
				break;

			case 'other' :
				if ( -1 !== link.indexOf( '/transform/thumbnail?' ) ) {
					type = 'image';
					width = '';
					height = '';
				}
				break;
		}

		// Switch to embed mode for OneDrive Personal items.
		if ( -1 !== link.indexOf( '://onedrive.live.com' ) ) {
			link = link.replace( 'redir?', 'embed?' );

			if ( -1 === link.indexOf( 'em=2' ) ) {
				link += '&em=2';
			}
		}

		// SharePoint; link must've been shared with "anyone with link" prior to this.
		if ( -1 !== link.indexOf( '.sharepoint.com/' ) ) {
			// Only switch to embed view for core types.
			if ( 'word' === type || 'powerpoint' === type || 'excel' === type || 'visio' === type ) {
				if ( -1 === link.indexOf( 'action=embedview' ) && -1 === link.indexOf( 'action=' ) ) {
					if ( -1 === link.indexOf( '?' ) ) {
						link += '?';
					} else {
						link += '&';
					}
	
					link += 'action=embedview';
				} else {
					link = link.replace( 'action=edit', 'action=embedview' );
				}

			} else {
				attr.display = 'icon';
			}
		}

		// @todo do a better job here.
		if ( invalid ) {
			return '';

		// embed time!
		} else {
			// Icon-only.
			if ( attr.hasOwnProperty( 'display' )  && 'icon' === attr.display ) {
				let ext, exts, icon, desc = '';

				switch ( type ) {
					case 'onenote' :
						ext = 'one';
						break;

					case 'image' :
						ext = 'photo';
						break;

					case 'archive' :
						ext = 'zip';
						break;

					default :
						ext = type;
						break;
				}

				// Icons that exist on Akamai CDN.
				exts = [ 'one', 'photo', 'video', 'audio', 'pdf', 'zip', 'rtf', 'html', 'code' ];

				// Fallback to 'txt' icon if ext doesn't exist.
				if ( ! exts.includes( ext ) ) {
					ext = 'txt';
				}

				icon = "https://spoprod-a.akamaihd.net/files/fabric-cdn-prod_20201207.001//assets/item-types/64/" + ext + ".png";

				// Some icons are located on a different CDN.
				if ( 'word' === type || 'powerpoint' === type || 'excel' === type || 'visio' === type ) {
					switch ( $type ) {
						case 'word' :
							ext = 'Doc';
							break;

						case 'powerpoint' :
							ext = 'Ppt';
							break;

						case 'excel' :
							ext = 'Xls';
							break;

						case 'visio' :
							ext = 'Vsd';
							break;
					}

					icon = "https://p.sfx.ms//icons/v2/Large/" + ext + ".png";

					if ( ! attr.hasOwnProperty( 'title' ) ) {
						attr.title = i18n.__( 'Download file' );
					}

				} else if ( ! attr.hasOwnProperty( 'title' ) ) {
					attr.title = i18n.__( 'View file' );
				}

				// Switch link to download for Personal accounts.
				if ( -1 !== link.indexOf( '://onedrive.live.com' ) ) {
					link = link.replace( 'embed?', 'download?' );
				}

				output.push( el( 'div', {
						key: 'onedrive-' + attr.id,
						className: 'onedrive-block-icon default-max-width',
					},
						el( 'img', {
							src: icon,
							width: 48,
							height: 48,
							alt: i18n.__( 'File icon' )
						} ),
						el( 'div', {
							className: 'onedrive-block-icon-description'
						},
							el( 'strong', {},
								el( 'a', {href: link},
									attr.title
								)
							)
						)
					)
				);

			// Audio.
			} else if ( 'audio' === type ) {
				output.push( el( 'figure', {
						key: 'onedrive-' + attr.id,
						className: 'wp-block-audio',
					},
						el( 'audio', {
							className: 'onedrive-shortcode',
							controls: true,
						},
							el( 'source', {
								src: link
							} ),
							el( 'p', {}, i18n.__( 'Your browser does not support HTML5 audio' ) )
						)
					)
				);

			// Video.
			} else if ( 'video' === type ) {
				output.push( el( 'figure', {
						key: 'onedrive-' + attr.id,
						className: 'wp-block-video',
					},
						el( 'video', {
							className: 'onedrive-shortcode',
							controls: true,
							preload: 'metadata',
						},
							el( 'source', {
								src: link
							} ),
							el( 'p', {}, i18n.__( 'Your browser does not support HTML5 video' ) )
						)
					)
				);

			// Image
			} else if ( 'image' === type ) {
				output.push( el( 'figure', {
						key: 'onedrive-' + attr.id,
						className: 'wp-block-image',
					},
						el( 'img', {
							className: 'onedrive-shortcode',
							src: link,
							width: width,
							height: height,
							alt: ''
						} )
				) );

			// Iframe
			} else {
				output.push( el( 'iframe', {
					key: 'onedrive-' + attr.id,
					className: 'onedrive-shortcode',
					src: link,
					width: width,
					height: height,
					marginWidth: 0,
					marginHeight: 0,
					frameBorder: 0,
					allowFullScreen: ''
				} ) );
			}

			return output;
		}
	};

	i18n.setLocaleData( { '': {} }, 'onedrive' );

	blocks.registerBlockType( 'hwdsb/onedrive', {
		title: i18n.__( 'OneDrive' ),
		icon: getIcon(),
		category: 'embed',
		attributes: {
			link: {
				type: 'string'
			},
			height: {
				type: 'number'
			},
			width: {
				type: 'number'
			},
			type: {
				type: 'string'
			},
			title: {
				type: 'string'
			}
		},
		edit: function( props ) {
			var attr = props.attributes,
				sidebarControls,
				blockControls,
				extraField = '',
				downloadField = '',
				pickerButton = '',
				id, type;

			selectDriveItem = async function( files ) {
				console.log( files );
				let expand = await expandLink( files.value[0].permissions[0].link.webUrl );
				if ( ! expand ) {
					return;
				}

				let fileDetails = {
					id: files.value[0].id,
					link: expand
				};

				let type = 'other';
				if ( files.value[0].hasOwnProperty( 'file' ) ) {
					type = getTypeFromMime( files.value[0].file.mimeType );
				} else if ( files.value[0].hasOwnProperty( 'package' ) ) {
					type = files.value[0].package.type.toLowerCase();
				}

				// For images, if we have the width and height, use it.
				if ( 'image' === type ) {
					if ( files.value[0].hasOwnProperty( 'image' ) && files.value[0].image.hasOwnProperty( 'width' ) ) {
						fileDetails.width = files.value[0].image.width;
					}

					if ( files.value[0].hasOwnProperty( 'image' ) && files.value[0].image.hasOwnProperty( 'height' ) ) {
						fileDetails.height = files.value[0].image.height;
					}

				// For powerpoint, set default size to medium.
				// @todo add filter?
				} else if ( 'powerpoint' === type ) {
					fileDetails.size = 'medium';

				// Set default height for Word and PDFs.
				// @todo add filter?
				} else if ( 'word' === type || 'pdf' === type ) {
					fileDetails.height = 500;
				}

				// SharePoint.
				if ( -1 !== expand.indexOf( '.sharepoint.com/' ) ) {
					/* remove for now since this requires requerying with updated access token.
					// Embeds do not work for non-core types, so try to get thumb.
					if ( 'image' === type || 'other' === type ) {
						let thumb = await getThumb( files.value[0].id, files.accessToken );
						console.log( thumb );
						if ( thumb ) {
							fileDetails.link = thumb;
						}
					}
					*/

					// For non-core types, display an icon as other types do not embed properly.
					if ( 'word' !== type && 'powerpoint' !== type && 'excel' !== type && 'visio' !== type ) {
						fileDetails.display = 'icon';
					}
				}

				fileDetails.type = type;

				if ( fileDetails.hasOwnProperty( 'display' ) && files.value[0].hasOwnProperty( 'name' ) ) {
					fileDetails.title = files.value[0].name;
				}

				return props.setAttributes( fileDetails );
			};

			// set up the file picker button if enabled.
			if ( hwdsbOneDriveProps.isPickerEnabled ) {
				// iframe approach to handle subdomain issues
				if ( current.hostname !== redirect.hostname ) {
					pickerButton = el( IframePicker, {
						onSelect: selectDriveItem
					});

				// Same hostname, so use button.
				} else {
					pickerButton = el(
						wp.components.Button, {
							className: 'button button-large is-primary spacer',
							onClick: function() {
								let advanced = {
									redirectUri: hwdsbOneDriveProps.picker.redirect,
									createLinkParameters: {
										type: hwdsbOneDriveProps.picker.type,
										scope: hwdsbOneDriveProps.picker.scope
									}
								};

								if ( hwdsbOneDriveProps.picker.hint ) {
									advanced.loginHint = hwdsbOneDriveProps.picker.hint;
								}

								let odOptions = {
									clientId: hwdsbOneDriveProps.picker.clientId,
									action: "share",
									advanced: advanced,
									success: async function(files) {
										await selectDriveItem( files );
									},
									cancel: function() {},
									error: function(error) {}
								}
								OneDrive.open( odOptions );
							}
						},
						wp.i18n.__( 'Or Select From Drive' )
					);
				}

				/* commenting this out for now until access tokens work properly
				if ( ! hwdsbOneDriveProps.picker.token ) {
					pickerButton = el( 'div', {
						className: 'components-placeholder__instructions'
						},
							el( 'p', { className: 'spacer' }, i18n.__( 'You can also select a file directly from your drive by authenticating to OneDrive.' ) ),
							el( wp.components.ExternalLink, {
								className: 'button-secondary mini-spacer',
								href: hwdsbOneDriveProps.picker.profile,
								target: '_blank',
								children: i18n.__( 'Set up OneDrive' )
							})
					);
				}
				*/
			}

			// Link added.
			if ( attr.link ) {
				type = getType( attr );

				if ( 'powerpoint' === type ) {
					extraField = el( wp.components.SelectControl, {
						className: 'components-panel__body is-opened onedrive-sidebar-item',
						label: i18n.__( 'Size' ),
						value: attr.size,
						options: [
							{ value: 'small',  label: i18n.__( 'Small - 350 x 221' ) },
							{ value: 'medium', label: i18n.__( 'Medium - 610 x 367' ) },
							{ value: 'large',  label: i18n.__( 'Large - 962 x 565' ) },
							{ value: 'extra',  label: i18n.__( 'Extra Large - 1186 x 691' ) },
						],
						onChange: function( newVal ) {
							props.setAttributes({
								size: newVal
							});
						},
					} );

				} else if ( 'word' === type ) {
					/*
					extraField = el( wp.components.CheckboxControl, {
						className: 'components-panel__body is-opened onedrive-sidebar-item',
						label: i18n.__( 'Show Doc Header/Footer' ),
						checked: 1 === attr.seamless ? false : true,
						onChange: function( newVal ) {
							props.setAttributes({
								seamless: true === newVal ? 0 : 1
							});
						},
					} );
					*/
				} else if ( 'other' === type ) {
					extraField = el( wp.components.SelectControl, {
						className: 'components-panel__body is-opened onedrive-sidebar-item',
						label: i18n.__( 'Type' ),
						value: attr.type,
						options: [
							{ value: 'other', label: i18n.__( 'Other (PDF, etc.)' ) },
							{ value: 'image', label: i18n.__( 'Image' ) },
							{ value: 'audio', label: i18n.__( 'Audio' ) },
							{ value: 'video', label: i18n.__( 'Video' ) }
						],
						onChange: function( newVal ) {
							props.setAttributes({
								type: newVal
							});
						},
					} );

				}

				// Sidebar controls.
				sidebarControls = el( wp.blockEditor.InspectorControls, { key: 'onedrive-controls-' + attr.id },
					// Dimensions.
					el( 'div', {
						className: 'onedrive-sidebar-dimensions block-library-image__dimensions components-panel__body is-opened block-editor-image-size-control',
					},
						el( 'p', {
							className: 'block-library-image__dimensions__row onedrive-sidebar-dimensions-label'
						}, i18n.__( 'Dimensions' ) ),
						el( 'div', {
							className: 'block-library-image__dimensions__row block-editor-image-size-control__row',
						},
							el( wp.components.TextControl, {
								className: 'block-library-image__dimensions__width components-base-control block-editor-image-size-control__width',
								label: i18n.__( 'Width' ),
								value: attr.width,
								onChange: function( newVal ) {
									props.setAttributes({
										width: newVal.replace(/\D+/,'') * 1
									});
								},
					                } ),
							el( wp.components.TextControl, {
								className: 'block-library-image__dimensions__height components-base-control block-editor-image-size-control__height',
								label: i18n.__( 'Height' ),
								value: attr.height,
								onChange: function( newVal ) {
									props.setAttributes({
										height: newVal.replace(/\D+/,'') * 1
									});
								},
							} ),
						)
					),
					extraField,
					downloadField
				);

				// Block controls.
				blockControls = el( wp.blockEditor.BlockControls, { key: 'onedrive-block-controls-' + attr.id,
					controls: [{
						icon: 'trash',
						title: i18n.__( 'Reset' ),
						onClick: function( event ) {
							// Reset variables to start fresh.
							props.setAttributes({
								link: undefined,
								type: undefined,
								width: undefined,
								height: undefined,
								size: undefined,
								id: undefined,
								display: undefined,
								title: undefined
							});
						},
					}]
				} );

				return [
					renderOutput( attr ),
					sidebarControls,
					blockControls
				];
			}

			// No item selected.
			return (
				el( 'div', { className: props.className + ' is-large components-placeholder' },
					el( 'div', {
						className: 'components-placeholder__label',
					},
						el( 'span', {
							className: 'block-editor-block-icon',
						}, getIcon() ),
						i18n.__( 'OneDrive' )
					),
					el( 'div', {
						className: 'components-placeholder__instructions',
					}, i18n.__( 'Enter a shared OneDrive link:' ) ),
					wp.element.createElement( OneDriveURLInput, {
						//className: props.className,
						value: props.attributes.link,
						onChange: function( url ) {
							var settings = {
								link: url,
								width: hwdsbOneDriveProps.defaultWidth,
								height: hwdsbOneDriveProps.defaultHeight
							};

							// Set some defaults for specific types.
							if ( 'powerpoint' === getType( settings ) ) {
								settings.width = '';
								settings.height = '';
							}

							props.setAttributes( settings );
						}
					} ),
					el( 'div', { className: 'components-placeholder__learn-more components-placeholder__fieldset' },
						el( wp.components.ExternalLink, {
							href: hwdsbOneDriveProps.helpUrl,
							children: i18n.__( 'Find out how to find your shared OneDrive link' )
						})
					),
					pickerButton
				)
			);
		},
		save: function( props ) {
			return renderOutput( props.attributes );
		},
	} );

	// Custom URLInput component
	// Inspired by wp.editor.URLInput and https://gist.github.com/krambertech/76afec49d7508e89e028fce14894724c
	class OneDriveURLInput extends Component {
		constructor(props) {
			super( props );

			this.onChange = this.onChange.bind( this );
			this.onKeyDown = this.onKeyDown.bind( this );

			this.state = {
				value: props.value
			};
		}

		componentDidMount() {
			this.timer = null;
		}

		componentWillUnmount() {
			clearTimeout(this.timer);
		}

		async onChange( event ) {
			let inputValue = event.target.value;

			clearTimeout(this.timer);

			if ( ! validateLink( inputValue ) ) {
				return;
			}

			// Expand link if applicable.
			if ( -1 !== inputValue.indexOf( '://1drv.ms/' ) ) {
				let expand = await expandLink( inputValue );
				if ( ! expand ) {
					return;
				}
				inputValue = expand;
			}

			this.setState({ value: inputValue });

			this.timer = setTimeout(function() {
				this.triggerChange();
			}.bind(this), 2000);
		}

		onKeyDown(e) {
			// 13 = Enter
			if (e.keyCode === 13) {
				clearTimeout(this.timer);
				this.triggerChange();
			}
		}

		triggerChange() {
			const { value } = this.state;

			this.props.onChange(value);
		}

		render() {
			return (
				el( 'div', { className: 'components-placeholder__fieldset' },
					el( 'input', {
						type: 'url',
						'aria-label': i18n.__( 'URL' ),
						required: '',
						value: this.state.value,
						onChange: this.onChange,
						placeholder: i18n.__( 'Type or Paste URL. Hit Enter to submit.' ),
						className: 'components-placeholder__input',
						onKeyDown: this.onKeyDown
					})
				)
			);
		}
	}

	/*
	 * House OneDrive File Picker in custom iframe component.
	 *
	 * We use postmessage to pass info from the OneDrive File Picker popup back
	 * to the iframe and back to React. This is to address cross-domain issues for
	 * multisite subdomain installs and mapped domains.
	 */
	class IframePicker extends Component {
		constructor(props) {
			super( props );

			this.postMessage = this.postMessage.bind( this );
			this.verifyNonce = this.verifyNonce.bind( this );
		}

		componentDidMount() {
			window.addEventListener('message', this.postMessage);
		}

		componentWillUnmount() {
			window.removeEventListener('message', this.postMessage);
		}

		async postMessage(e) {
console.log( e );
			if ( e.origin !== redirect.origin || ! e.data.value || ! e.data.apiEndpoint || -1 === e.currentTarget.location.href.indexOf( '/wp-admin/' ) ) {
				return;
			}

			// Check if this is from MS Graph.
			if ( 0 !== e.data.apiEndpoint.indexOf( 'https://graph.microsoft.com/' ) ) {
				return;
			}

			// Do a better job here with error responses.
			if ( e.data.errorCode ) {
				return;
			}

			// Verify nonce.
			let verify = await verifyNonce( e.data.nonce );
			if ( ! verify ) {
				return;
			}

			const { onSelect } = this.props;
			onSelect( e.data );
		}

		async verifyNonce( nonce ) {
			try {			
				let ping = await wp.ajax.post( 'hwdsb-onedrive-verify-nonce', {
					'_ajax_nonce': nonce
				} );
				
				return true;
			} catch (error) {
				console.error(error);
				return false;
			}
		}

		render() {
			let src = redirect;

			src.searchParams.set( 't', hwdsbOneDriveProps.picker.type );
			src.searchParams.set( 's', hwdsbOneDriveProps.picker.scope );
			src.searchParams.set( 'l', hwdsbOneDriveProps.picker.label );
			src.searchParams.set( 'n', hwdsbOneDriveProps.picker.nonce );
			src.searchParams.set( 'c', hwdsbOneDriveProps.picker.clientId );
			src.searchParams.set( 'o', current.origin );

			if ( hwdsbOneDriveProps.picker.hint ) {
				src.searchParams.set( 'h', hwdsbOneDriveProps.picker.hint );
			}

			return (
				el( 'iframe', {
					src: src,
					className: 'mini-spacer',
					width: '100%',
					height: 36,
					marginWidth: 0,
					marginHeight: 0,
					frameBorder: 0
				} )
			);
		}
	}
} )(
	window.wp.blocks,
	window.wp.i18n,
	window.wp.element,
	window._,
);
