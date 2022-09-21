<?php
/**
 * The update helper for noptin.com plugins.
 *
 * @class Noptin_COM_Updater
 * @package Noptin\noptin.com
 */

defined( 'ABSPATH' ) || exit;

/**
 * Noptin_COM_Updater Class
 *
 * Contains the logic to fetch available updates and hook into Core's update
 * routines to serve updates for noptin.com-provided packages.
 *
 * @since 1.5.0
 * @ignore
 */
class Noptin_COM_Updater {

	/**
	 * Loads the class, runs on init.
	 */
	public static function load() {
		add_action( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'transient_update_plugins' ), 21, 1 );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'upgrader_process_complete' ) );
		add_action( 'upgrader_pre_download', array( __CLASS__, 'block_expired_updates' ), 10, 2 );
		add_filter( 'plugins_api', array( __CLASS__, 'plugins_api' ), 20, 3 );
		add_action( 'plugins_loaded', array( __CLASS__, 'add_notice_unlicensed_product' ), 10, 4 );
		add_filter( 'site_transient_update_plugins', array( __CLASS__, 'change_update_information' ) );
	}

	/**
	 * Runs in a cron thread, or in a visitor thread if triggered
	 * by _maybe_update_plugins(), or in an auto-update thread.
	 *
	 * @param object $transient The update_plugins transient object.
	 *
	 * @return object The same or a modified version of the transient.
	 */
	public static function transient_update_plugins( $transient ) {
		$update_data = self::get_update_data();

		foreach ( Noptin_COM::get_installed_addons() as $plugin ) {
			if ( empty( $update_data[ $plugin['slug'] ] ) || empty( $update_data[ $plugin['slug'] ]['version'] ) ) {
				continue;
			}

			$data     = $update_data[ $plugin['slug'] ];
			$filename = $plugin['_filename'];

			$item = array(
				'id'             => 'noptin-com-' . $plugin['slug'],
				'slug'           => $plugin['slug'],
				'plugin'         => $filename,
				'new_version'    => $data['version'],
				'url'            => 'https://noptin.com/pricing/',
				'package'        => empty( $data['download_link'] ) ? 'noptin-com-expired-' . $data['product_id'] : $data['download_link'],
				'upgrade_notice' => '',
			);

			if ( isset( $data['requires_php'] ) ) {
				$item['requires_php'] = $data['requires_php'];
			}

			if ( version_compare( $plugin['Version'], $data['version'], '<' ) ) {
				$transient->response[ $filename ] = (object) $item;
				unset( $transient->no_update[ $filename ] );
			} else {
				$transient->no_update[ $filename ] = (object) $item;
				unset( $transient->response[ $filename ] );
			}
		}

		return $transient;
	}

	/**
	 * Get update data for all installed extensions.
	 *
	 * Scans through all extensions and obtains update
	 * data for each product.
	 *
	 * @return array Update data {slug => data}
	 */
	public static function get_update_data() {
		$payload = wp_list_pluck( Noptin_COM::get_installed_addons(), 'slug' );
		return self::update_check( array_filter( array_unique( array_values( $payload ) ) ) );
	}

	/**
	 * Run an update check API call.
	 *
	 * The call is cached based on the payload (download slugs). If
	 * the payload changes, the cache is going to miss.
	 *
	 * @param array $payload Information about the plugin to update.
	 * @return array Update data for each requested product.
	 */
	private static function update_check( $payload ) {

		// Abort if no downloads installed.
		if ( empty( $payload ) ) {
			return array();
		}

		sort( $payload );

		$hash      = md5( wp_json_encode( $payload ) . Noptin_COM::get_active_license_key() );
		$cache_key = '_noptin_update_check';
		$data      = get_transient( $cache_key );
		if ( false !== $data ) {
			if ( hash_equals( $hash, $data['hash'] ) ) {
				return $data['downloads'];
			}
		}

		$data = array(
			'hash'      => $hash,
			'updated'   => time(),
			'downloads' => array(),
			'errors'    => array(),
		);

		$git_urls = array();

		foreach ( $payload as $slug ) {
			$git_urls[ $slug ] = 'https://github.com/hizzle-co/' . sanitize_key( $slug );
		}

		$endpoint = add_query_arg(
			array(
				'hizzle_license_url' => rawurlencode( home_url() ),
				'hizzle_license'     => rawurlencode( Noptin_COM::get_active_license_key() ),
				'downloads'          => rawurlencode( implode( ',', $git_urls ) ),
			),
			'https://noptin.com/wp-json/hizzle_download/v1/versions'
		);

		$response = Noptin_COM::process_api_response( wp_remote_get( $endpoint ) );

		if ( is_wp_error( $response ) ) {
			$data['errors'][]  = $response->get_error_message();
		} else {

			foreach ( $git_urls as $slug => $git_url ) {

				$response = json_decode( wp_json_encode( $response ), true );
				if ( ! empty( $response[ $git_url ]['error'] ) ) {
					$data['errors'][] = $response[ $git_url ]['error'];
					continue;
				}

				$data['downloads'][ $slug ]         = $response[ $git_url ];
				$data['downloads'][ $slug ]['slug'] = $slug;
			}
		}

		delete_transient( '_noptin_helper_updates_count' );
		set_transient( $cache_key, $data, 12 * HOUR_IN_SECONDS );
		return $data['downloads'];
	}

	/**
	 * Get the number of products that have updates.
	 *
	 * @return int The number of products with updates.
	 */
	public static function get_updates_count() {
		$cache_key = '_noptin_helper_updates_count';
		$count     = get_transient( $cache_key );
		if ( false !== $count ) {
			return $count;
		}

		if ( ! get_transient( '_noptin_update_check' ) ) {
			return 0;
		}

		$count       = 0;
		$update_data = self::get_update_data();

		if ( empty( $update_data ) ) {
			set_transient( $cache_key, $count, 12 * HOUR_IN_SECONDS );
			return $count;
		}

		// Scan local plugins.
		foreach ( Noptin_COM::get_installed_addons() as $plugin ) {
			if ( empty( $update_data[ $plugin['slug'] ] ) ) {
				continue;
			}

			if ( version_compare( $plugin['Version'], $update_data[ $plugin['slug'] ]['version'], '<' ) ) {
				$count++;
			}
		}

		set_transient( $cache_key, $count, 12 * HOUR_IN_SECONDS );
		return $count;
	}

	/**
	 * Return the updates count markup.
	 *
	 * @return string Updates count markup, empty string if no updates avairable.
	 */
	public static function get_updates_count_html() {
		$count = (int) self::get_updates_count();
		if ( ! $count ) {
			return '';
		}

		$count_html = sprintf( '<span class="update-plugins count-%d"><span class="update-count">%d</span></span>', $count, number_format_i18n( $count ) );
		return $count_html;
	}

	/**
	 * Checks if a given extension has an update.
	 *
	 * @param string $slug The extension slug.
	 * @return bool
	 */
	public static function has_extension_update( $slug ) {

		if ( ! get_transient( '_noptin_update_check' ) ) {
			return false;
		}

		$update_data = self::get_update_data();

		if ( empty( $update_data ) || empty( $update_data[ $slug ] ) ) {
			return false;
		}

		// Fetch local plugin.
		$local_plugin = current( wp_list_filter( Noptin_COM::get_installed_addons(), array( 'slug' => $slug ) ) );

		return ! empty( $local_plugin ) && version_compare( $local_plugin['Version'], $update_data[ $slug ]['version'], '<' );

	}

	/**
	 * Flushes cached update data.
	 */
	public static function flush_updates_cache() {
		delete_transient( '_noptin_update_check' );
		delete_transient( '_noptin_helper_updates_count' );
		delete_site_transient( 'update_plugins' );
	}

	/**
	 * Fires when a user successfully updated a plugin.
	 */
	public static function upgrader_process_complete() {
		delete_transient( '_noptin_helper_updates_count' );
	}

	/**
	 * Hooked into the upgrader_pre_download filter in order to better handle error messaging around expired
	 * plugin updates.
	 *
	 * @since 1.5.0
	 * @param bool   $reply Holds the current filtered response.
	 * @param string $package The path to the package file for the update.
	 * @return false|WP_Error False to proceed with the update as normal, anything else to be returned instead of updating.
	 */
	public static function block_expired_updates( $reply, $package ) {
		// Don't override a reply that was set already.
		if ( false !== $reply ) {
			return $reply;
		}

		// Only for packages with expired licenses.
		if ( 0 !== strpos( $package, 'noptin-com-expired-' ) ) {
			return false;
		}

		$product_id = str_replace( 'noptin-com-expired-', '', $package );
		return new WP_Error(
			'noptin_subscription_expired',
			sprintf(
				// translators: %s: URL of the package.
				__( 'Please <a href="%s" target="_blank">buy a new license key</a> to receive automatic updates.', 'newsletter-optin-box' ),
				esc_url(
					add_query_arg(
						'product_id',
						(int) $product_id,
						'https://noptin.com/pricing/?hizzle_license_action=wc_renew_license&utm_source=extensionsscreen&utm_medium=product&utm_campaign=expired'
					)
				)
			)
		);

	}

	/**
	 * Plugin information callback for Noptin extensions.
	 *
	 * @param object $response The response core needs to display the modal.
	 * @param string $action The requested plugins_api() action.
	 * @param object $args Arguments passed to plugins_api().
	 *
	 * @return object An updated $response.
	 */
	public static function plugins_api( $response, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) ) {
			return $response;
		}

		// Only for slugs that start with noptin-
		if ( 0 !== strpos( $args->slug, 'noptin-' ) ) {
			return $response;
		}

		// Get download slug.
		$download_slug = str_replace( 'noptin-plugin-with-slug-', '', sanitize_key( $args->slug ) );
		$git_url       = 'https://github.com/hizzle-co/' . $download_slug;

		// Abort if cannot get download slug.
		if ( empty( $download_slug ) ) {
			return $response;
		}

		$endpoint = add_query_arg(
			array(
				'hizzle_license_url' => rawurlencode( home_url() ),
				'hizzle_license'     => rawurlencode( Noptin_COM::get_active_license_key() ),
				'downloads'          => rawurlencode( $git_url ),
			),
			'https://noptin.com/wp-json/hizzle_download/v1/versions'
		);

		$response = Noptin_COM::process_api_response( wp_remote_get( $endpoint ) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'plugins_api_failed', $response->get_error_message() );
		}

		$response = json_decode( wp_json_encode( $response ), true );
		if ( empty( $response[ $git_url ] ) ) {
			return new WP_Error( 'plugins_api_failed', __( 'Error fetching downloadable file', 'newsletter-optin-box' ) );
		}

		if ( ! empty( $response[ $git_url ]['error'] ) ) {

			if ( ! empty( $response[ $git_url ]['error']['error_code'] ) && 'download_file_not_found' === $response[ $git_url ]['error']['error_code'] ) {
				return $response;
			}

			return new WP_Error( 'plugins_api_failed', $response[ $git_url ]['error'] );
		}

		$response[ $git_url ]['slug'] = $args->slug;

		return (object) $response[ $git_url ];

	}

	/**
	 * Add action for queued products to display message for unlicensed products.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public static function add_notice_unlicensed_product() {
		if ( is_admin() && function_exists( 'get_plugins' ) ) {
			foreach ( array_keys( Noptin_COM::get_installed_addons() ) as $key ) {
				add_action( 'in_plugin_update_message-' . $key, array( __CLASS__, 'need_license_message' ), 10, 2 );
			}
		}
	}

	/**
	 * Message displayed if license not activated
	 *
	 * @param  array  $plugin_data The plugin data.
	 * @param  object $r The api response.
	 * @return void
	 */
	public static function need_license_message( $plugin_data, $r ) {

		if ( empty( $r->package ) || 0 === strpos( $r->package, 'noptin-com-expired-' ) ) {

			printf(
				'<span style="display: block;margin-top: 10px;font-weight: 600;">%s</span>',
				sprintf(
					/* translators: %s: updates page URL. */
					wp_kses_post( __( 'To update, please <a href="%s">activate your license key</a>.', 'newsletter-optin-box' ) ),
					esc_url( admin_url( 'admin.php?page=noptin-addons' ) )
				)
			);
		}

	}

	/**
	 * Change the update information for unlicensed Noptin products
	 *
	 * @param  object $transient The update-plugins transient.
	 * @return object
	 */
	public static function change_update_information( $transient ) {

		// If we are on the update core page, change the update message for unlicensed products.
		global $pagenow;
		if ( ( 'update-core.php' === $pagenow ) && $transient && isset( $transient->response ) && ! isset( $_GET['action'] ) ) {

			$notice = sprintf(
				/* translators: %s: updates page URL. */
				__( 'To update, please <a href="%s">activate your license key</a>.', 'newsletter-optin-box' ),
				admin_url( 'admin.php?page=noptin-addons' )
			);

			foreach ( array_keys( Noptin_COM::get_installed_addons() ) as $key ) {
				if ( isset( $transient->response[ $key ] ) && ( empty( $transient->response[ $key ]->package ) || 0 === strpos( $transient->response[ $key ]->package, 'noptin-com-expired-' )  ) ) {
					$transient->response[ $key ]->upgrade_notice = $notice;
				}
			}
		}

		return $transient;
	}

}

Noptin_COM_Updater::load();
