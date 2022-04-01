<?php
/**
 * Noptin.com Helper class.
 *
 * @package Noptin\noptin.com
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Noptin_COM_Helper Class
 *
 * The main entry-point for all things related to the Helper.
 *
 * @since 1.5.0
 *
 */
class Noptin_COM_Helper {

	/**
	 * Contains an array of local noptin extensions.
	 *
	 * @param array
	 */
	private $local_plugins = null;

	/**
	 * Loads the helper class, runs on init.
	 */
	public static function load() {

		add_action( 'noptin_helper_output', array( __CLASS__, 'render_helper_output' ) );
		add_action( 'current_screen', array( __CLASS__, 'current_screen' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );

		do_action( 'noptin_com_helper_loaded' );
	}

	/**
	 * Render the helper section content based on context.
	 */
	public static function render_helper_output() {
		$auth           = Noptin_COM::get( 'auth' );
		$auth_user_data = Noptin_COM::get( 'auth_user_data' );

		// Return success/error notices.
		$notices        = self::_get_return_notices();

		// No active connection.
		if ( empty( $auth['access_token'] ) ) {
			$connect_url = add_query_arg(
				array(
					'page'                  => 'noptin-addons',
					'section'               => 'helper',
					'noptin-helper-connect' => 1,
					'noptin-helper-nonce'   => wp_create_nonce( 'connect' ),
				),
				admin_url( 'admin.php' )
			);

			include self::get_view_filename( 'html-oauth-start.php' );
			return;
		}

		$disconnect_url = add_query_arg(
			array(
				'page'                     => 'noptin-addons',
				'section'                  => 'helper',
				'noptin-helper-disconnect' => 1,
				'noptin-helper-nonce'      => wp_create_nonce( 'disconnect' ),
			),
			admin_url( 'admin.php' )
		);

		$refresh_url    = add_query_arg(
			array(
				'page'                  => 'noptin-addons',
				'section'               => 'helper',
				'noptin-helper-refresh' => 1,
				'noptin-helper-nonce'   => wp_create_nonce( 'refresh' ),
			),
			admin_url( 'admin.php' )
		);

		// Installed plugins, with or without an active subscription.
		$noptin_plugins        = self::get_local_noptin_plugins();
		$_licenses             = self::get_licenses();
		$licenses              = array();
		$updates               = Noptin_COM_Updater::get_update_data();
		$licenses_product_ids  = array();
		$has_active_membership = false;

		foreach ( $_licenses as $_license ) {

			$_license = new Noptin_COM_License( $_license );

			if ( ! $_license->exists() ) {
				continue;
			}

			if ( $_license->is_active() && $_license->is_activated_on_site() ) {

				if ( $_license->is_membership() && $_license->is_active() ) {
					$has_active_membership = true;
				} else {
					$licenses_product_ids[] = $_license->get_product_id();
				}

			}

			$license                      = $_license;
			$license->extra_data['local'] = array(
				'installed' => false,
				'active'    => false,
				'version'   => null,
			);

			$license['update_url'] = admin_url( 'update-core.php' );
			$local                 = wp_list_filter( $noptin_plugins, array( '_product_id' => $license->get_product_id() ) );

			if ( ! empty( $local ) ) {
				$local                                     = array_shift( $local );
				$license->extra_data['local']['installed'] = true;
				$license->extra_data['local']['version']   = $local['Version'];

				if ( 'plugin' == $local['_type'] ) {
					if ( is_plugin_active( $local['_filename'] ) ) {
						$license->extra_data['local']['active'] = true;
					} else if ( is_multisite() && is_plugin_active_for_network( $local['_filename'] ) ) {
						$license->extra_data['local']['active'] = true;
					}

					// A magic update_url.
					$license['update_url'] = wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $local['_filename'], 'upgrade-plugin_' . $local['_filename'] );

				}

			}

			$license['has_update'] = false;
			if ( $license->extra_data['local']['installed'] && ! empty( $updates[ $license->get_product_id() ] ) ) {
				$license['has_update'] = version_compare( $updates[ $license->get_product_id() ]['version'], $license->extra_data['local']['version'], '>' );
			}

			$license['download_primary'] = true;
			$license_actions             = array();

			if ( $license['has_update'] && $license->is_active() ) {
				$action = array(
					/* translators: %s: version number */
					'message'      => sprintf( __( 'Version %s is <strong>available</strong>.', 'newsletter-optin-box' ), esc_html( $updates[ $license->get_product_id() ]['version'] ) ),
					'button_label' => __( 'Update', 'newsletter-optin-box' ),
					'button_url'   => $license['update_url'],
					'status'       => 'update-available',
					'icon'         => 'dashicons-update',
				);

				// License is not active on this site.
				if ( ! $license->is_activated_on_site() ) {
					$action['message']     .= ' ' . __( 'To enable this update you need to <strong>activate</strong> this license.', 'newsletter-optin-box' );
					$action['button_label'] = null;
					$action['button_url']   = null;
				}

				$license_actions[] = $action;
			}

			if ( ! $license->extra_data['local']['installed'] && ! $license->is_membership() && $license->is_active() && $license->is_activated_on_site() ) {

				$action = array(
					'message'      => __( 'This license key is active but the extension is not installed on your site.', 'newsletter-optin-box' ),
					'button_label' => __( 'Install Extension', 'newsletter-optin-box' ),
					'button_url'   => wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=noptin-product-with-id-' . $license->get_product_id() ), 'install-plugin_noptin-product-with-id-' . $license->get_product_id() ),
					'status'       => 'update-available',
					'icon'         => 'dashicons-update',
				);

				$license_actions[] = $action;
			}

			if ( $license['has_update'] && ! $license->is_active() ) {
				$action = array(
					/* translators: %s: version number */
					'message' => sprintf( __( 'Version %s is <strong>available</strong>.', 'newsletter-optin-box' ), esc_html( $updates[ $license['product_id'] ]['version'] ) ),
					'status'  => 'expired',
					'icon'    => 'dashicons-info',
				);

				$action['message']     .= ' ' . __( 'To enable this update you need to <strong>purchase</strong> a new license.', 'newsletter-optin-box' );
				$action['button_label'] = __( 'Purchase', 'newsletter-optin-box' );
				$action['button_url']   = $license->get_product_url( true );

				$license_actions[] = $action;
			} else if ( ! $license->is_active() ) {
				$action = array(
					'message'      => sprintf( __( 'This license key has expired. Please <strong>renew</strong> to receive updates and support.', 'newsletter-optin-box' ) ),
					'button_label' => __( 'Renew', 'newsletter-optin-box' ),
					'button_url'   => $license->get_product_url( true ),
					'status'       => 'expired',
					'icon'         => 'dashicons-info',
				);

				$license_actions[] = $action;
			}

			// Mark the first action primary.
			foreach ( $license_actions as $key => $action ) {
				if ( ! empty( $action['button_label'] ) ) {
					$license_actions[ $key ]['primary'] = true;
					break;
				}
			}

			$license['actions'] = $license_actions;
			$licenses[]         = $license;
		}

		// Installed products without a license.
		$no_licenses = array();

		if ( ! $has_active_membership ) {

			foreach ( $noptin_plugins as $filename => $data ) {

				// Abort if it has an active license key.
				if ( in_array( $data['_product_id'], $licenses_product_ids ) ) {
					continue;
				}

				$data['_product_url'] = '#';
				$data['_has_update']  = false;

				if ( ! empty( $data['PluginURI'] ) ) {
					$data['_product_url'] = $data['PluginURI'];
				}

				if ( ! empty( $updates[ $data['_product_id'] ] ) ) {
					$data['_has_update'] = version_compare( $updates[ $data['_product_id'] ]['version'], $data['Version'], '>' );

					if ( ! empty( $updates[ $data['_product_id'] ]['url'] ) ) {
						$data['_product_url'] = $updates[ $data['_product_id'] ]['url'];
					}

				}

				$data['_actions'] = array();

				if ( $data['_has_update'] ) {
					$action = array(
						/* translators: %s: version number */
						'message'      => sprintf( __( 'Version %s is <strong>available</strong>. To enable this update you need to <strong>purchase</strong> a new license key.', 'newsletter-optin-box' ), esc_html( $updates[ $data['_product_id'] ]['version'] ) ),
						'button_label' => __( 'Purchase', 'newsletter-optin-box' ),
						'button_url'   => $data['_product_url'],
						'status'       => 'expired',
						'icon'         => 'dashicons-info',
					);

					$data['_actions'][] = $action;
				} else {
					$action = array(
						'message'      => __( 'To receive updates and support for this extension, you need to <strong>purchase</strong> a new license key.', 'newsletter-optin-box' ),
						'button_label' => __( 'Purchase', 'newsletter-optin-box' ),
						'button_url'   => $data['_product_url'],
						'status'       => 'expired',
						'icon'         => 'dashicons-info',
					);

					$data['_actions'][] = $action;
				}

				$no_licenses[ $filename ] = $data;
			}

		}

		// Sort alphabetically.
		uasort( $licenses, array( __CLASS__, '_sort_by_product_name' ) );
		uasort( $no_licenses, array( __CLASS__, '_sort_by_name' ) );

		// We have an active connection.
		include self::get_view_filename( 'html-main.php' );
	}

	/**
	 * Get an absolute path to the requested helper view.
	 *
	 * @param string $view The requested view file.
	 *
	 * @return string The absolute path to the view file.
	 */
	public static function get_view_filename( $view ) {
		return dirname( __FILE__ ) . "/views/$view";
	}

	/**
	 * Enqueue admin scripts and styles.
	 */
	public static function admin_enqueue_scripts() {
		$screen       = get_current_screen();
		$screen_id    = $screen ? $screen->id : '';
		$is_helper    = false;

		if ( 'noptin-newsletter_page_noptin-addons' === $screen_id && isset( $_GET['section'] ) && 'helper' === $_GET['section'] ) {
			$is_helper = true;
			$version   = filemtime( noptin()->plugin_path . 'includes/assets/css/noptin-helper.css' );
			wp_enqueue_style( 'noptin-helper', noptin()->plugin_url . 'includes/assets/css/noptin-helper.css', array(), $version );
		}

		if ( 'noptin-newsletter_page_noptin-addons' === $screen_id & ! $is_helper ) {
			$version = filemtime( noptin()->plugin_path . 'includes/assets/css/addons-page.css' );
			wp_enqueue_style( 'noptin-addons-page', noptin()->plugin_url . 'includes/assets/css/addons-page.css', array(), $version );
		}

		if ( 'noptin-newsletter_page_noptin-addons' === $screen_id ) {
			$version = filemtime( noptin()->plugin_path . 'includes/assets/js/dist/helper.js' );
			wp_enqueue_script( 'noptin-helper', noptin()->plugin_url . 'includes/assets/js/dist/helper.js', array( 'jquery' ), $version, true );

			$params       = array(
				'license_deactivation_error' => __( 'Error deactivating your license key', 'newsletter-optin-box' ),
				'license_deactivated'        => __( 'Your license has been deactivated', 'newsletter-optin-box' ),
				'license_activation_error'   => __( 'Error activating your license key', 'newsletter-optin-box' ),
				'license_activated'          => __( 'Your license has been activated', 'newsletter-optin-box' ),
				'close'                      => __( 'Close', 'newsletter-optin-box' ),
				'activate'                   => __( 'Activate', 'newsletter-optin-box' ),
				'deactivate'                 => __( 'De-activate', 'newsletter-optin-box' ),
				'deactivate_warning'         => __( 'You will nolonger receive product updates and support for this website', 'newsletter-optin-box' ),
				'cancel'                     => __( 'Cancel', 'newsletter-optin-box' ),
				'activate_license'           => __( 'Activate license key', 'newsletter-optin-box' ),
				'deactivate_license'         => __( 'De-activate license key', 'newsletter-optin-box' ),
				'license_key'                => __( 'Enter license key', 'newsletter-optin-box' ),
				'license_activate_url'       => esc_url( rest_url( 'noptin-com-site/v1/licenses/activate' ) ),
				'license_deactivate_url'     => esc_url( rest_url( 'noptin-com-site/v1/licenses/deactivate' ) ),
				'rest_nonce'                 => wp_create_nonce( 'wp_rest' ),
			);

			// localize and enqueue the script with all of the variable inserted.
			wp_localize_script( 'noptin-helper', 'noptin_helper', $params );

			add_thickbox();
		}

	}

	/**
	 * Various success/error notices.
	 *
	 * Runs during admin page render, so no headers/redirects here.
	 *
	 * @return array Array pairs of message/type strings with notices.
	 */
	private static function _get_return_notices() {
		$return_status = isset( $_GET['noptin-helper-status'] ) ? noptin_clean( wp_unslash( $_GET['noptin-helper-status'] ) ) : null;
		$notices       = array();

		switch ( $return_status ) {
			case 'activate-success':
				$product_id = isset( $_GET['noptin-helper-product-id'] ) ? absint( $_GET['noptin-helper-product-id'] ) : 0;
				$license    = Noptin_COM::get_active_license( $product_id );

				if ( $license ) {
					$notices[]    = array(
						'type'    => 'updated',
						'message' => sprintf(
							/* translators: %s: product name */
							__( '%s activated successfully. You will now receive updates and support for this website.', 'newsletter-optin-box' ),
							'<strong>' . esc_html( $license->get_product_name() ) . '</strong>'
						),
					);
				}

				break;

			case 'activate-error':
				$notices[]    = array(
					'type'    => 'error',
					'message' => __( 'An error has occurred when activating your license key. Please try again later.', 'newsletter-optin-box' ),
				);
				break;

			case 'deactivate-success':

				$notices[] = array(
					'message' => __( 'License key deactivated successfully. You will no longer receive product updates and support for this site.', 'newsletter-optin-box' ),
					'type'    => 'updated',
				);
				break;

			case 'helper-connected':
				$notices[] = array(
					'message' => __( 'You have successfully connected your site to noptin.com', 'newsletter-optin-box' ),
					'type'    => 'updated',
				);
				break;

			case 'helper-disconnected':
				$notices[] = array(
					'message' => __( 'You have successfully disconnected your site from noptin.com', 'newsletter-optin-box' ),
					'type'    => 'updated',
				);
				break;

			case 'helper-refreshed':
				$notices[] = array(
					'message' => __( 'Authentication and license caches refreshed successfully.', 'newsletter-optin-box' ),
					'type'    => 'updated',
				);
				break;
		}

		return $notices;
	}

	/**
	 * Various early-phase actions with possible redirects.
	 *
	 * @param object $screen WP screen object.
	 */
	public static function current_screen( $screen ) {

		if ( 'noptin-newsletter_page_noptin-addons' !== $screen->id || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( empty( $_GET['section'] ) || 'helper' !== $_GET['section'] ) {
			return;
		}

		if ( ! empty( $_GET['noptin-helper-connect'] ) ) {
			return self::_helper_auth_connect();
		}

		if ( ! empty( $_GET['noptin-helper-return'] ) ) {
			return self::_helper_auth_return();
		}

		if ( ! empty( $_GET['noptin-helper-disconnect'] ) ) {
			return self::_helper_auth_disconnect();
		}

		if ( ! empty( $_GET['noptin-helper-refresh'] ) ) {
			return self::_helper_auth_refresh();
		}

		if ( ! empty( $_GET['noptin-helper-activate'] ) ) {
			return self::_helper_license_activate();
		}

		if ( ! empty( $_GET['noptin-helper-deactivate'] ) ) {
			return self::_helper_license_deactivate();
		}

	}

	/**
	 * Initiate a new OAuth connection.
	 */
	private static function _helper_auth_connect() {
		if ( empty( $_GET['noptin-helper-nonce'] ) || ! wp_verify_nonce( wp_unslash( $_GET['noptin-helper-nonce'] ), 'connect' ) ) {
			self::log( 'Could not verify nonce in _helper_auth_connect' );
			wp_die( __( 'Could not verify nonce', 'newsletter-optin-box' ) );
			exit;
		}

		$redirect_uri = add_query_arg(
			array(
				'page'                 => 'noptin-addons',
				'section'              => 'helper',
				'noptin-helper-return' => 1,
				'noptin-helper-nonce'  => wp_create_nonce( 'connect' ),
			),
			admin_url( 'admin.php' )
		);

		$secret = Noptin_COM_API_Client::post(
			'nopcom/1/oauth/request_token',
			array(
				'body' => array(
					'home_url'     => home_url(),
					'site_url'     => site_url(),
					'redirect_url' => $redirect_uri,
					'api_url'      => rest_url( 'noptin-com-site/v1/' ),
					'site_icon'    => get_site_icon_url(),
					'blogname'     => get_option( 'blogname' ),
				),
			)
		);

		if ( is_wp_error( $secret ) ) {
			self::log( sprintf( 'Unable to call oauth/request_token.', $secret->get_error_message() ) );
			wp_die( 'Something went wrong' );
		}

		$code = Noptin_COM_API_Client::$last_response_code;
		if ( 200 !== $code ) {
			self::log( sprintf( 'Call to oauth/request_token returned a non-200 response code (%d)', $code ) );
			wp_die( 'Something went wrong' );
		}

		if ( empty( $secret ) ) {
			self::log( sprintf( 'Call to oauth/request_token returned an invalid body: %s', $secret ) );
			wp_die( 'Something went wrong' );
		}

		/**
		 * Fires when the Helper connection process is initiated.
		 */
		do_action( 'noptin_com_helper_connect_start' );

		$connect_url = add_query_arg(
			array(
				'home_url'     => rawurlencode( home_url() ),
				'redirect_url' => rawurlencode( $redirect_uri ),
				'secret'       => rawurlencode( $secret->secret ),
			),
			apply_filters( 'noptin_com_helper_connection_url', 'https://noptin.com/connect/')
		);

		wp_redirect( esc_url_raw( $connect_url ) );
		die();
	}

	/**
	 * Return from noptin.com OAuth flow.
	 */
	private static function _helper_auth_return() {

		if ( empty( $_GET['noptin-helper-nonce'] ) || ! wp_verify_nonce( wp_unslash( $_GET['noptin-helper-nonce'] ), 'connect' ) ) {
			self::log( 'Could not verify nonce in _helper_auth_return' );
			wp_die( __( 'Could not verify nonce', 'newsletter-optin-box' ) );
		}

		// Bail if the user clicked deny.
		if ( ! empty( $_GET['deny'] ) ) {
			/**
			 * Fires when the Helper connection process is denied/cancelled.
			 */
			do_action( 'noptin_com_helper_denied' );
			wp_safe_redirect( admin_url( 'admin.php?page=noptin-addons&section=helper' ) );
			die();
		}

		// We do need a request token...
		if ( empty( $_GET['refresh_token'] ) || empty( $_GET['access_token'] ) ) {
			self::log( 'Refresh token not found in _helper_auth_return' );
			wp_die( __( 'Something went wrong', 'newsletter-optin-box' ) );
		}

		// Obtain an access token.
		$connected_site = Noptin_COM_API_Client::post(
			'nopcom/1/oauth/access_token',
			array(
				'body' => array(
					'refresh_token' => wp_unslash( $_GET['refresh_token'] ),
					'access_token'  => wp_unslash( $_GET['access_token'] ),
				),
			)
		);

		if ( is_wp_error( $connected_site ) ) {
			self::log( sprintf( 'Unable to call oauth/access_token.', $connected_site->get_error_message() ) );
			wp_die( __( 'Something went wrong', 'newsletter-optin-box' ) );
		}

		$code = Noptin_COM_API_Client::$last_response_code;;
		if ( 200 !== $code ) {
			self::log( sprintf( 'Call to oauth/access_token returned a non-200 response code (%d)', $code ) );
			wp_die( __( 'Something went wrong', 'newsletter-optin-box' ) );
		}

		if ( ! $connected_site ) {
			self::log( sprintf( 'Call to oauth/access_token returned an invalid body: %s', $connected_site ) );
			wp_die( __( 'Something went wrong', 'newsletter-optin-box' ) );
		}

		Noptin_COM::update(
			'auth',
			array(
				'access_token'        => $connected_site->access_token,
				'access_token_secret' => $connected_site->access_token_secret,
				'site_id'             => $connected_site->id,
				'user_id'             => get_current_user_id(),
				'remote_user_id'      => $connected_site->user_id,
				'updated'             => time(),
			)
		);

		// Obtain the connected user info.
		if ( ! self::_flush_authentication_cache() ) {
			self::log( 'Could not obtain connected user info in _helper_auth_return' );
			Noptin_COM::update( 'auth', array() );
			wp_die( __( 'Something went wrong', 'newsletter-optin-box' ) );
		}

		self::_flush_licenses_cache();
		self::_flush_updates_cache();

		/**
		 * Fires when the Helper connection process has completed successfully.
		 */
		do_action( 'noptin_com_helper_connected' );

		// If connecting through in-app purchase, redirects back to noptin.com
		// for product installation.
		if ( ! empty( $_GET['nopcom-install-url'] ) ) {
			wp_redirect( wp_unslash( $_GET['nopcom-install-url'] ) );
			exit;
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'                 => 'noptin-addons',
					'section'              => 'helper',
					'noptin-helper-status' => 'helper-connected',
				),
				admin_url( 'admin.php' )
			)
		);
		die();
	}

	/**
	 * Disconnect from noptin.com, clear OAuth tokens.
	 */
	private static function _helper_auth_disconnect() {
		if ( empty( $_GET['noptin-helper-nonce'] ) || ! wp_verify_nonce( wp_unslash( $_GET['noptin-helper-nonce'] ), 'disconnect' ) ) {
			self::log( 'Could not verify nonce in _helper_auth_disconnect' );
			wp_die( __( 'Could not verify nonce', 'newsletter-optin-box' ) );
		}

		/**
		 * Fires when the Helper has been disconnected.
		 */
		do_action( 'noptin_com_helper_disconnected' );

		$redirect_uri = add_query_arg(
			array(
				'page'                 => 'noptin-addons',
				'section'              => 'helper',
				'noptin-helper-status' => 'helper-disconnected',
			),
			admin_url( 'admin.php' )
		);

		Noptin_COM_API_Client::post(
			'nopcom/1/oauth/invalidate_token',
			array(
				'authenticated' => true,
			)
		);

		Noptin_COM::update( 'auth', array() );
		Noptin_COM::update( 'auth_user_data', array() );

		self::_flush_licenses_cache();
		self::_flush_updates_cache();

		wp_safe_redirect( $redirect_uri );
		die();
	}

	/**
	 * User hit the Refresh button, clear all caches.
	 */
	private static function _helper_auth_refresh() {
		if ( empty( $_GET['noptin-helper-nonce'] ) || ! wp_verify_nonce( wp_unslash( $_GET['noptin-helper-nonce'] ), 'refresh' ) ) {
			self::log( 'Could not verify nonce in _helper_auth_refresh' );
			wp_die( __( 'Could not verify nonce', 'newsletter-optin-box' ) );
		}

		do_action( 'noptin_com_helper_connection_refresh' );

		$redirect_uri = add_query_arg(
			array(
				'page'                 => 'noptin-addons',
				'section'              => 'helper',
				'noptin-helper-status' => 'helper-refreshed',
			),
			admin_url( 'admin.php' )
		);

		self::_flush_authentication_cache();
		self::_flush_licenses_cache();
		self::_flush_updates_cache();

		wp_safe_redirect( $redirect_uri );
		die();
	}

	/**
	 * Activate a product license key.
	 */
	private static function _helper_license_activate() {
		$product_key = isset( $_GET['noptin-helper-product-key'] ) ? noptin_clean( wp_unslash( $_GET['noptin-helper-product-key'] ) ) : '';
		$product_id  = isset( $_GET['noptin-helper-product-id'] ) ? absint( $_GET['noptin-helper-product-id'] ) : 0;

		if ( empty( $_GET['noptin-helper-nonce'] ) || ! wp_verify_nonce( wp_unslash( $_GET['noptin-helper-nonce'] ), 'activate:' . $product_key ) ) {
			self::log( 'Could not verify nonce in _helper_license_activate' );
			wp_die( 'Could not verify nonce' );
		}

		// Activate license key.
		$result = Noptin_COM::activate_license( $product_key, $product_id );

		if ( ! is_wp_error( $result ) ) {

			// Attempt to activate this plugin.
			$local = self::get_plugin_from_id( $product_id );
			if ( $local && 'plugin' == $local['_type'] && current_user_can( 'activate_plugins' ) && ! is_plugin_active( $local['_filename'] ) ) {
				activate_plugin( $local['_filename'] );
			}

		}

		self::_flush_updates_cache();

		$redirect_uri = add_query_arg(
			array(
				'page'                     => 'noptin-addons',
				'section'                  => 'helper',
				'noptin-helper-status'     => ! is_wp_error( $result ) ? 'activate-success' : 'activate-error',
				'noptin-helper-product-id' => $product_id,
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect_uri );
		die();
	}

	/**
	 * Deactivate a product license key.
	 */
	private static function _helper_license_deactivate() {

		// Verify data.
		$product_key = isset( $_GET['noptin-helper-product-key'] ) ? sanitize_text_field( urldecode( wp_unslash( $_GET['noptin-helper-product-key'] ) ) ) : '';
		$product_id  = isset( $_GET['noptin-helper-product-id'] ) ? absint( wp_unslash( $_GET['noptin-helper-product-id'] ) ) : 0;

		if ( empty( $_GET['noptin-helper-nonce'] ) || ! wp_verify_nonce( wp_unslash( $_GET['noptin-helper-nonce'] ), 'deactivate:' . $product_key ) ) {
			self::log( 'Could not verify nonce in _helper_license_deactivate' );
			wp_die( 'Could not verify nonce' );
		}

		// Deactivate license.
		Noptin_COM::deactivate_license( $product_key );

		self::_flush_updates_cache();

		$redirect_uri = add_query_arg(
			array(
				'page'                     => 'noptin-addons',
				'section'                  => 'helper',
				'noptin-helper-status'     => 'deactivate-success',
				'noptin-helper-product-id' => $product_id,
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect_uri );
		die();
	}

	/**
	 * Get the connected user's licenses.
	 *
	 * @param bool $only_cached Whether to skip fetching from remote.
	 * @return string[] $license keys
	 */
	public static function get_licenses( $only_cached = false ) {
		$cache_key = '_noptin_helper_all_licenses';
		$data      = get_transient( $cache_key );
		if ( false !== $data ) {
			return $data;
		}

		if ( $only_cached ) {
			return array();
		}

		// Obtain the connected user's licenses.
		$licenses = Noptin_COM_API_Client::get(
			'nopcom/1/licenses',
			array(
				'authenticated' => true,
			)
		);

		if ( is_wp_error( $licenses ) || empty( $licenses->licenses ) || Noptin_COM_API_Client::$last_response_code !== 200 ) {
			set_transient( $cache_key, array(), 15 * MINUTE_IN_SECONDS );
			return array();
		}

		$return = array();

		foreach ( $licenses->licenses as $license ) {
			Noptin_COM::cache_license_details( $license->license_key, $license );
			$return[] = $license->license_key;
		}

		set_transient( $cache_key, $return, 1 * HOUR_IN_SECONDS );

		return $return;
	}

	/**
	 * Runs when any plugin is activated.
	 *
	 * Depending on the activated plugin, attempts to look through available
	 * licenses and auto-activate one if possible, so the user does not
	 * need to visit the Helper UI at all after installing a new extension.
	 *
	 * @param string $filename The filename of the activated plugin.
	 */
	public static function activated_plugin( $filename ) {
		$plugins = self::get_local_noptin_plugins();

		// Not a local noptin plugin.
		if ( empty( $plugins[ $filename ] ) ) {
			return;
		}

		$product_id = (int) $plugins[ $filename ]['_product_id'];

		// Abort if we have an active license key.
		if ( Noptin_COM::has_active_license( $product_id ) ) {
			return;
		}

		$licenses = self::get_licenses();

		// Search for a compatible license key.
		foreach ( $licenses as $_license ) {
			$license = new Noptin_COM_License( $_license );

			// Don't attempt to activate expired license keys.
			if ( ! $license->exists() || ! $license->is_active() ) {
				continue;
			}

			// No more sites available for this license.
			if ( ! $license->is_maxed() ) {
				continue;
			}

			// Is it valid for this product id.
			if ( $license->is_membership() || $license->get_product_id() == $product_id ) {
				Noptin_COM::activate_license( $_license, $product_id );
				break;
			}

		}

		self::_flush_updates_cache();
	}

	/**
	 * Runs when any plugin is deactivated.
	 *
	 * When a user deactivates a plugin, attempt to deactivate any licenses
	 * associated with the extension.
	 *
	 * @param string $filename The filename of the deactivated plugin.
	 */
	public static function deactivated_plugin( $filename ) {
		$plugins = self::get_local_noptin_plugins();

		// Not a local Noptin plugin.
		if ( empty( $plugins[ $filename ] ) ) {
			return;
		}

		// Get product id.
		$product_id = (int) $plugins[ $filename ]['_product_id'];

		// Retrieve license key.
		$license = Noptin_COM::get_active_license( $product_id );

		// Abort if the license is not active or is a membership.
		if ( empty( $license ) || $license->is_membership() ) {
			return;
		}

		Noptin_COM::deactivate_license( $license->get_license_key() );
		self::_flush_updates_cache();

	}

	/**
	 * Various Helper-related admin notices.
	 */
	public static function admin_notices() {

		if ( apply_filters( 'noptin_com_helper_suppress_admin_notices', false ) ) {
			return;
		}

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		if ( 'update-core' !== $screen_id && 'plugins' !== $screen_id ) {
			return;
		}

		// Don't nag if Noptin doesn't have an update available.
		if ( ! self::_noptin_core_update_available() ) {
			return;
		}

		// Add a note about available extension updates if Noptin core has an update available.
		$notice = self::_get_extensions_update_notice();
		if ( ! empty( $notice ) ) {
			echo '<div class="updated noptin-message"><p>' . $notice . '</p></div>';
		}

	}

	/**
	 * Get an update notice if one or more Noptin extensions has an update available.
	 *
	 * @return string|null The update notice or null if everything is up to date.
	 */
	private static function _get_extensions_update_notice() {
		$plugins   = self::get_local_noptin_plugins();
		$updates   = Noptin_COM_Updater::get_update_data();
		$available = 0;

		foreach ( $plugins as $data ) {
			if ( empty( $updates[ $data['_product_id'] ] ) ) {
				continue;
			}

			$product_id = $data['_product_id'];
			if ( version_compare( $updates[ $product_id ]['version'], $data['Version'], '>' ) ) {
				$available++;
			}

		}

		if ( ! $available ) {
			return;
		}

		return sprintf(
			/* translators: %1$s: helper url, %2$d: number of extensions */
			_n( 'Note: You currently have <a href="%1$s">%2$d paid extension</a> which should be updated first before updating Noptin.', 'Note: You currently have <a href="%1$s">%2$d paid extensions</a> which should be updated first before updating Noptin.', $available, 'newsletter-optin-box' ),
			admin_url( 'admin.php?page=noptin-addons&section=helper' ),
			$available
		);

	}

	/**
	 * Whether Noptin has an update available.
	 *
	 * @return bool True if a Noptin core update is available.
	 */
	private static function _noptin_core_update_available() {
		$updates = get_site_transient( 'update_plugins' );
		if ( empty( $updates->response ) ) {
			return false;
		}

		if ( empty( $updates->response['newsletter-optin-box/noptin.php'] ) ) {
			return false;
		}

		$data = $updates->response['newsletter-optin-box/noptin.php'];
		if ( version_compare( noptin()->version, $data->new_version, '>=' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Flush licenses cache.
	 */
	public static function _flush_licenses_cache() {

		foreach( self::get_licenses( true ) as $license_key ) {
			Noptin_COM::delete_cached_license_details( $license_key );
		}

		delete_transient( '_noptin_helper_all_licenses' );
	}

	/**
	 * Flush auth cache.
	 *
	 * @return bool
	 */
	public static function _flush_authentication_cache() {

		// Check if wew're authenticated.
		$response = Noptin_COM_API_Client::get(
			'nopcom/1/oauth/me',
			array(
				'authenticated' => true,
			)
		);

		if ( is_wp_error( $response ) || empty( $response ) || Noptin_COM_API_Client::$last_response_code !== 200 ) {
			return false;
		}

		Noptin_COM::update( 'auth_user_data', (array) $response );

		return true;
	}

	/**
	 * Flush updates cache.
	 */
	private static function _flush_updates_cache() {
		Noptin_COM_Updater::flush_updates_cache();
	}

	/**
	 * Sort licenses by the product_name.
	 *
	 * @param Noptin_COM_License $a License object.
	 * @param Noptin_COM_License $b License object.
	 *
	 * @return int
	 */
	public static function _sort_by_product_name( $a, $b ) {
		return strcmp( $a->get_product_name(), $b->get_product_name() );
	}

	/**
	 * Sort packages by the Name.
	 *
	 * @param array $a Product array.
	 * @param array $b Product array.
	 *
	 * @return int
	 */
	public static function _sort_by_name( $a, $b ) {
		return strcmp( $a['Name'], $b['Name'] );
	}

	/**
	 * Log a helper event.
	 *
	 * @param string $message Log message.
	 * @param string $level Optional, defaults to info, valid levels: emergency|alert|critical|error|warning|notice|info|debug.
	 */
	public static function log( $message, $level = 'info' ) {
		log_noptin_message( $message, $level );
	}

	/*
	|--------------------------------------------------------------------------
	| Packages
	|--------------------------------------------------------------------------
	|
	| Methods which read/update installed premium addons.
	|
	*/

	/**
	 * Obtain a list of data about locally installed noptin extensions.
	 *
	 * @return array
	 */
	public static function get_local_noptin_plugins() {

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();

		/**
		 * Check if plugins have Noptin headers, if not then clear cache and fetch again.
		 * Noptin Headers will not be present if `add_extra_package_headers` hook was added after a `get_plugins` call -- for example when Noptin is activated/updated.
		 * Also, get_plugins call is expensive so we should clear this cache very conservatively.
		 */
		if ( ! empty( $plugins ) && ! array_key_exists( 'Noptin ID', current( $plugins ) ) ) {
			wp_clean_plugins_cache( false );
			$plugins = get_plugins();
		}

		$noptin_plugins = array();

		foreach ( $plugins as $filename => $data ) {

			if ( empty( $data['Noptin ID'] ) ) {
				continue;
			}

			$product_id = trim( $data['Noptin ID'] );

			$data['_filename']           = $filename;
			$data['_product_id']         = absint( $product_id );
			$data['_type']               = 'plugin';
			$data['slug']                = dirname( $filename );
			$noptin_plugins[ $filename ] = $data;
		}

		return $noptin_plugins;
	}

	/**
	 * Retrieves a plugin file via its product ID.
	 *
	 * @since 1.5.0
	 * @param int $product_id Product ID.
	 * @return string|false
	 */
	public static function get_plugin_file_from_id( $product_id ) {

		$plugin = self::get_plugin_from_id( $product_id );
		return empty( $plugin ) ? false : $plugin['_filename'];

	}

	/**
	 * Retrieves plugin data when given a product ID.
	 *
	 * @since 1.5.0
	 * @param int $product_id Product ID.
	 * @return array|false
	 */
	public static function get_plugin_from_id( $product_id ) {

		$plugins = wp_list_filter(
			self::get_local_noptin_plugins(),
			array(
				'_product_id' => $product_id,
			)
		);

		return reset( $plugins );

	}

}

Noptin_COM_Helper::load();
