<?php
/**
 * Noptin.com Product Installation and Communications.
 *
 * @package Noptin\noptin.com
 * @since   1.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Noptin_COM Class
 *
 * Main class for noptin.com connected sites.
 * @ignore
 */
class Noptin_COM {

	/**
	 * The option name used to store the helper data.
	 *
	 * @var string
	 */
	private static $option_name = 'noptin_helper_data';

	/**
	 * Include helper files.
	 *
	 * @since 1.5.0
	 */
	public static function includes() {
		require_once plugin_dir_path( __FILE__ ) . 'class-noptin-com-helper.php';
		include_once plugin_dir_path( __FILE__ ) . 'class-noptin-com-updater.php';
	}

	/**
	 * Get an option by key
	 *
	 * @see self::update
	 *
	 * @param string $key The key to fetch.
	 * @param mixed  $default The default option to return if the key does not exist.
	 *
	 * @return mixed An option or the default.
	 */
	public static function get( $key, $default = false ) {
		$options = get_option( self::$option_name, array() );
		$options = is_array( $options ) ? $options : array();

		return array_key_exists( $key, $options ) ? $options[ $key ] : $default;
	}

	/**
	 * Update an option by key
	 *
	 * All helper options are grouped in a single options entry. This method
	 * is not thread-safe, use with caution.
	 *
	 * @param string $key The key to update.
	 * @param mixed  $value The new option value.
	 *
	 * @return bool True if the option has been updated.
	 */
	public static function update( $key, $value ) {
		$options         = get_option( self::$option_name, array() );
		$options         = is_array( $options ) ? $options : array();
		$options[ $key ] = $value;
		return update_option( self::$option_name, $options, true );
	}

	/*
	|--------------------------------------------------------------------------
	| License keys
	|--------------------------------------------------------------------------
	|
	| Methods which activate/deactivate license keys locally and remotely.
	|
	*/

	/**
	 * Returns the active license key.
	 *
	 * @param bool $include_details
	 * @return object|WP_Error|string|false
	 * @since 1.8.0
	 */
	public static function get_active_license_key( $include_details = false ) {

		// Fetch the license key.
		$license_key = self::get( 'license_key' );

		// If not set, try to fetch the old style license keys.
		if ( empty( $license_key ) ) {
			$licenses = self::get( 'active_license_keys' );

			if ( is_array( $licenses ) && ! empty( $licenses ) ) {
				$license_key = array_pop( $licenses );
			}
		}

		if ( empty( $license_key ) ) {
			return false;
		}

		if ( ! $include_details ) {
			return $license_key;
		}

		$details = self::fetch_license_details( $license_key );

		if ( empty( $details ) || is_wp_error( $details ) ) {
			return $details;
		}

		// Check if it was deactivated remotely.
		if ( empty( $details->is_active_on_site ) ) {
			self::update( 'license_key', '' );
			return false;
		}

		return $details;
	}

	/**
	 * Fetches license details from the cache or remotely.
	 *
	 * @param string $license_key
	 * @return object|WP_Error
	 * @since 1.7.0
	 */
	private static function fetch_license_details( $license_key ) {
		$license_key = sanitize_text_field( $license_key );
		$cache_key   = sanitize_key( 'noptin_license_' . $license_key );
		$cached      = get_transient( $cache_key );

		// Abort early if details were cached.
		if ( false !== $cached ) {
			return $cached;
		}

		// Fetch details remotely.
		$license = self::process_api_response(
			wp_remote_get(
				"https://noptin.com/wp-json/hizzle/v1/licenses/$license_key/?website=" . rawurlencode( home_url() ),
				array(
					'timeout' => 15,
					'headers' => array(
						'Accept'           => 'application/json',
						'X-Requested-With' => 'Noptin',
					),
				)
			)
		);

		if ( is_wp_error( $license ) ) {
			return $license;
		}

		if ( empty( $license ) || empty( $license->license ) ) {
			return new WP_Error( 'invalid_license', __( 'Error fetching your license key.', 'newsletter-optin-box' ) );
		}

		$license = $license->license;

		// Cache for an hour.
		set_transient( $cache_key, $license, HOUR_IN_SECONDS );

		return $license;
	}

	/**
	 * Processes API responses
	 *
	 * @param mixed $response WP_HTTP Response.
	 * @return WP_Error|object
	 */
	public static function process_api_response( $response ) {

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$res = json_decode( wp_remote_retrieve_body( $response ) );

		if ( empty( $res ) ) {
			return new WP_Error( 'invalid_response', __( 'Invalid response from the server.', 'newsletter-optin-box' ) );
		}

		if ( isset( $res->code ) && isset( $res->message ) ) {
			return new WP_Error( $res->code, $res->message, (array) $res->data );
		}

		return $res;
	}

	/**
	 * Get by slug.
	 *
	 * @param string $slug
	 * @param array $items
	 * @return object|false
	 */
	private static function get_by_slug( $slug, $items ) {

		if ( ! is_array( $items ) ) {
			return false;
		}

		foreach ( $items as $item ) {
			if ( $item->slug === $slug ) {
				return $item;
			}
		}

		return false;
	}

	/**
	 * Retrieves a single connection.
	 *
	 * @param string $slug
	 * @return object|false
	 */
	public static function get_connection( $slug ) {
		return self::get_by_slug( $slug, self::get_connections() );
	}

	/**
	 * Retrieves all connections.
	 *
	 */
	public static function get_connections() {

		// Read from cache.
		$cached = get_transient( 'noptin_com_connections' );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		// Fetch the connections.
		$result = self::process_api_response( wp_remote_get( 'https://noptin.com/wp-content/uploads/noptin/connections.json' ) );

		if ( ! is_array( $result ) ) {
			$result = json_decode( file_get_contents( plugin_dir_path( __FILE__ ) . 'connections.json' ) );
		}

		// Cache the connections.
		set_transient( 'noptin_com_connections', $result, 12 * HOUR_IN_SECONDS );
		return $result;
	}

	/**
	 * Retrieves a single integration.
	 *
	 * @param string $slug
	 * @return object|false
	 */
	public static function get_integration( $slug ) {
		return self::get_by_slug( $slug, self::get_integrations() );
	}

	/**
	 * Retrieves all integrations.
	 *
	 */
	public static function get_integrations() {

		// Read from cache.
		$cached = get_transient( 'noptin_com_integrations' );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		// Fetch the integrations.
		$result = self::process_api_response( wp_remote_get( 'https://noptin.com/wp-content/uploads/noptin/integrations.json' ) );

		if ( ! is_array( $result ) ) {
			$result = json_decode( file_get_contents( plugin_dir_path( __FILE__ ) . 'integrations.json' ) );
		}

		// Cache the integrations.
		set_transient( 'noptin_com_integrations', $result, 12 * HOUR_IN_SECONDS );
		return $result;
	}

	/**
	 * Retrieves a list of installed extensions.
	 *
	 * @return array
	 */
	public static function get_installed_addons() {

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$noptin_plugins = array();

		foreach ( get_plugins() as $filename => $data ) {

			$slug = basename( dirname( $filename ) );

			if ( 0 === strpos( $slug, 'noptin-' ) ) {
				$data['_filename']           = $filename;
				$data['slug']                = $slug;
				$data['_type']               = 'plugin';
				$noptin_plugins[ $filename ] = $data;
			}
		}

		return $noptin_plugins;
	}

}

Noptin_COM::includes();
