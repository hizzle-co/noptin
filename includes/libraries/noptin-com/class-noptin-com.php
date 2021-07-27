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
		require_once plugin_dir_path( __FILE__ ) . 'class-noptin-com-license.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-noptin-com-helper.php';
		include_once plugin_dir_path( __FILE__ ) . 'class-noptin-com-updater.php';
		include_once plugin_dir_path( __FILE__ ) . 'class-noptin-com-api-client.php';
		require_once plugin_dir_path( __FILE__ ) . 'rest-api/class-noptin-com-rest-controller.php';
		require_once plugin_dir_path( __FILE__ ) . 'rest-api/class-noptin-com-licenses-api-controller.php';

		$share_stats = get_noptin_option( 'allow_tracking', false );
		if ( defined( 'DOING_CRON' ) && ! empty( $share_stats ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'class-noptin-com-tracker.php';
		}

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
	 * Fetches license details from the cache or remotely.
	 *
	 * @param string $license_key
	 * @return object|WP_Error
	 * @since 1.5.0
	 */
	public static function get_license_details( $license_key ) {
		$license_key = sanitize_text_field( $license_key );
		$cache_key   = sanitize_key( 'nopcom_' . $license_key );
		$cached      = get_transient( $cache_key );

		// Abort early if details were cached.
		if ( false !== $cached && $cached = json_decode( $cached ) ) {
			return $cached;
		}

		// Fetch details remotely.
		$license = Noptin_COM_API_Client::get( "nopcom/1/licenses/$license_key" );

		if ( is_wp_error( $license ) ) {
			return $license;
		}

		if ( empty( $license ) || empty( $license->license ) ) {
			return new WP_Error( 'invalid_license', __( 'Error fetching your license key.', 'newsletter-optin-box' ) );
		}

		$license = $license->license;

		// Cache for an hour.
		set_transient( $cache_key, wp_json_encode( $license ), HOUR_IN_SECONDS );

		return $license;
	}

	/**
	 * Caches license details.
	 *
	 * @param string $license_key
	 * @param object|Noptin_COM_License $license
	 * @since 1.5.0
	 */
	public static function cache_license_details( $license_key, $license ) {
		$license_key = sanitize_text_field( $license_key );
		$cache_key   = sanitize_key( 'nopcom_' . $license_key );

		// Cache for an hour.
		set_transient( $cache_key, wp_json_encode( $license ), HOUR_IN_SECONDS );

	}

	/**
	 * Deletes cached license details.
	 *
	 * @param string $license_key
	 * @since 1.5.0
	 */
	public static function delete_cached_license_details( $license_key ) {
		$license_key = sanitize_text_field( $license_key );
		$cache_key   = sanitize_key( 'nopcom_' . $license_key );
		delete_transient( $cache_key );
	}

	/**
	 * Checks if the site has an active membership.
	 *
	 * @return bool
	 * @since 1.5.0
	 */
	public static function has_active_membership() {
		$membership_key = self::get( 'membership_key' );

		if ( empty( $membership_key ) ) {
			return false;
		}

		$membership = new Noptin_COM_License( $membership_key );

		return $membership->exists() && $membership->is_active() && $membership->is_activated_on_site();

	}

	/**
	 * Checks if a license is the active membership.
	 *
	 * @param string $license_key
	 * @return bool
	 * @since 1.5.0
	 */
	public static function is_active_membership( $license_key ) {
		return self::has_active_membership() && $license_key === self::get( 'membership_key' );
	}

	/**
	 * Returns an array of active license keys.
	 *
	 * @return string[]
	 * @since 1.5.0
	 */
	public static function get_active_licenses() {
		$licenses = self::get( 'active_license_keys' );
		$licenses = is_array( $licenses ) ? $licenses : array();

		$old_licenses = get_option( 'noptin_updates_licenses' );

		if ( is_array( $old_licenses ) ) {
			$licenses = $licenses + array_values( $old_licenses );
			self::update_active_licenses( $licenses );
			delete_option( 'noptin_updates_licenses' );
		}

		return is_array( $licenses ) ? $licenses : array();
	}

	/**
	 * Checks if a given license key is active on the site.
	 *
	 * @param string $license_key License key to check for.
	 * @return bool
	 * @since 1.5.0
	 */
	public static function is_license_key_active( $license_key ) {
		return in_array( $license_key, self::get_active_licenses() );
	}

	/**
	 * Checks if a given product id has an active license key on the site.
	 *
	 * @param int $product_id Product id to check for.
	 * @return bool
	 * @since 1.5.0
	 */
	public static function has_active_license( $product_id ) {
		return false !== self::get_active_license( $product_id );
	}

	/**
	 * Retrieves a product's active license key.
	 *
	 * @param int $product_id Product id to check for.
	 * @return Noptin_COM_License|false
	 * @since 1.5.0
	 */
	public static function get_active_license( $product_id ) {

		foreach ( self::get_active_licenses() as $license ) {

			$license = new Noptin_COM_License( $license );

			if ( ! $license->exists() || ! $license->is_active() || ! $license->is_activated_on_site() ) {
				continue;
			}

			if ( $license->is_membership() || $license->get_product_id() == $product_id ) {
				return $license;
			}

		}

		return false;
	}

	/**
	 * Updates the active licenses array.
	 *
	 * @param string[] $licenses
	 * @since 1.5.0
	 */
	public static function update_active_licenses( $licenses ) {
		self::update( 'active_license_keys', $licenses );
	}

	/**
	 * Activates a Noptin.com license key.
	 *
	 * @param string|object $license Activates a license.
	 * @param int $product_id
	 * @return WP_Error|object
	 * @since 1.5.0
	 */
	public static function activate_license( $license, $product_id ) {

		// Prepare license key.
		$license_key = is_string( $license ) ? trim( $license ) : $license->license_key;

		// Delete details cache.
		self::delete_cached_license_details( $license_key );

		// Activate the license key remotely.
		$result = Noptin_COM_API_Client::post(
			'nopcom/1/licenses/activate',
			array(
				'body' => array(
					'home_url'    => home_url(),
					'license_key' => $license_key,
					'product_id'  => $product_id,
				),
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( empty( $result ) || empty( $result->download_url ) ) {
			return new WP_Error( 'activation_error', __( 'Error activating your license key.', 'newsletter-optin-box' ) );
		}

		// Fetch the new license key.
		$license = new Noptin_COM_License( $license_key );

		if ( ! $license->exists() ) {
			return empty( $license->license_error ) ? new WP_Error( 'invalid_license', __( 'Error fetching your license key.', 'newsletter-optin-box' ) ) : $license->license_error;
		}

		// If this is a membership, de-activate all other license keys.
		if ( $license->is_membership() ) {
			self::deactivate_all_licenses();
			self::update( 'membership_key', $license->get_license_key() );
		}

		$active_licenses   = self::get_active_licenses();
		$active_licenses[] = $license->get_license_key();

		self::update_active_licenses( array_unique( $active_licenses ) );

		do_action( 'nopcom_activated_license', $license, $result );

		return $result;
	}

	/**
	 * De-activates a Noptin.com license key.
	 *
	 * @param object|string $license The license key to deactivate.
	 * @return void
	 * @since 1.5.0
	 */
	public static function deactivate_license( $license ) {

		// Prepare license key.
		$license_key = is_string( $license ) ? trim( $license ) : $license->license_key;

		// Delete details cache.
		self::delete_cached_license_details( $license_key );

		// Deactivate the license key remotely.
		$result = Noptin_COM_API_Client::post(
			'nopcom/1/licenses/deactivate',
			array(
				'body' => array(
					'home_url'    => home_url(),
					'license_key' => $license_key,
				),
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Deactivate locally.
		$active_licenses = self::get_active_licenses();

		self::update_active_licenses( array_diff( $active_licenses, array( $license_key ) ) );

		// Remove active membership.
		if ( $license_key == self::get( 'membership_key' ) ) {
			self::update( 'membership_key', '' );
		}

	}

	/**
	 * De-activates all Noptin.com license keys.
	 *
	 * @return void
	 * @since 1.5.0
	 */
	public static function deactivate_all_licenses() {

		array_map(
			array( __CLASS__, 'deactivate_license' ),
			self::get_active_licenses()
		);

	}

	/**
	 * Fetches available integrations.
	 *
	 * @return array
	 * @since 1.5.0
	 */
	public static function get_integrations() {

		$integrations = get_transient( 'available_noptin_integrations' );

		if ( is_array( $integrations ) ) {
			return $integrations;
		}

		$integrations = Noptin_COM_API_Client::get( 'nopcom/1/extensions/integrations' );

		if ( is_wp_error( $integrations ) ) {
			$integrations = array();
		}

		set_transient( 'available_noptin_integrations', (array) $integrations, DAY_IN_SECONDS );

		return $integrations;
	}

}

Noptin_COM::includes();
