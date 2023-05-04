<?php
/**
 * Noptin.com Helper class.
 *
 * @package Noptin\noptin.com
 */

defined( 'ABSPATH' ) || exit;

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
	 * Loads the helper class, runs on init.
	 */
	public static function load() {

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );

		do_action( 'noptin_com_helper_loaded' );
	}

	/**
	 * Render the helper section content based on context.
	 */
	public static function output_extensions_page() {
		require plugin_dir_path( __FILE__ ) . 'views/html-admin-page-extensions.php';
	}

	/**
	 * Enqueue admin scripts and styles.
	 */
	public static function admin_enqueue_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$our_id    = noptin()->white_label->admin_screen_id() . '_page_noptin-addons';
		$version   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : noptin()->version;

		if ( $our_id === $screen_id ) {
			wp_enqueue_style( 'noptin-addons-page', noptin()->plugin_url . 'includes/assets/css/addons-page.css', array(), $version );
		}
	}

	/**
	 * Fires after admin screen inits.
	 */
	public static function admin_init() {

		// Handle license deactivation.
		if ( isset( $_GET['noptin-deactivate-license-nonce'] ) && wp_verify_nonce( rawurldecode( $_GET['noptin-deactivate-license-nonce'] ), 'noptin-deactivate-license' ) ) {
			self::handle_license_deactivation();
			wp_safe_redirect( remove_query_arg( 'noptin-deactivate-license-nonce' ) );
			exit;
		}

		// Handle license activation.
		if ( isset( $_POST['noptin-license'] ) && isset( $_POST['noptin_save_license_key_nonce'] ) && wp_verify_nonce( wp_unslash( $_POST['noptin_save_license_key_nonce'] ), 'noptin_save_license_key' ) ) {
			self::handle_license_save( $_POST['noptin-license'] );
		}

	}

	/**
	 * Saves a license key.
	 *
	 * @param string $license_key The license key to save.
	 */
	private static function handle_license_save( $license_key ) {

		// Prepare license key.
		$license_key = sanitize_text_field( $license_key );

		if ( empty( $license_key ) ) {
			return;
		}

		// Delete cached details.
		delete_transient( sanitize_key( 'noptin_license_' . $license_key ) );

		// Activate the license key remotely.
		$result = Noptin_COM::process_api_response(
			wp_remote_post(
				'https://noptin.com/wp-json/hizzle/v1/licenses/' . $license_key . '/activate',
				array(
					'body'    => array(
						'website' => home_url(),
					),
					'headers' => array(
						'Accept'           => 'application/json',
						'X-Requested-With' => 'Noptin',
					),
				)
			)
		);

		// Abort if there was an error.
		if ( is_wp_error( $result ) ) {
			return noptin()->admin->show_error(
				sprintf(
					/* translators: %s: Error message. */
					__( 'There was an error activating your license key: %s', 'newsletter-optin-box' ),
					$result->get_error_message()
				)
			);
		}

		// Save the license key.
		Noptin_COM::update( 'license_key', $license_key );

		Noptin_COM_Updater::flush_updates_cache();

		noptin()->admin->show_success( __( 'Your license key has been activated successfully. You will now receive updates and support for this website.', 'newsletter-optin-box' ) );
	}

	/**
	 * Handle license deactivation.
	 *
	 */
	private static function handle_license_deactivation() {

		$license_key = Noptin_COM::get_active_license_key();

		if ( empty( $license_key ) ) {
			return;
		}

		// Delete cached details.
		delete_transient( sanitize_key( 'noptin_license_' . $license_key ) );

		// Deactive the license key remotely.
		$result = Noptin_COM::process_api_response(
			wp_remote_post(
				'https://noptin.com/wp-json/hizzle/v1/licenses/' . $license_key . '/deactivate',
				array(
					'body'    => array(
						'website' => home_url(),
					),
					'headers' => array(
						'Accept'           => 'application/json',
						'X-Requested-With' => 'Noptin',
					),
				)
			)
		);

		// Abort if there was an error.
		if ( is_wp_error( $result ) ) {
			return noptin()->admin->show_error(
				sprintf(
					/* translators: %s: Error message. */
					__( 'There was an error deactivating your license key: %s', 'newsletter-optin-box' ),
					$result->get_error_message()
				)
			);
		}

		// Save the license key.
		Noptin_COM::update( 'license_key', '' );

		Noptin_COM_Updater::flush_updates_cache();

		noptin()->admin->show_success( __( 'License key deactivated successfully. You will no longer receive product updates and support for this site.', 'newsletter-optin-box' ) );
	}

	/**
	 * Various Helper-related admin notices.
	 */
	public static function admin_notices() {

		self::maybe_print_expired_license_key_notice();

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		if ( 'update-core' !== $screen_id && 'plugins' !== $screen_id ) {
			return;
		}

		// Don't nag if Noptin doesn't have an update available.
		if ( ! self::noptin_core_update_available() ) {
			return;
		}

		// Add a note about available extension updates if Noptin core has an update available.
		$notice = self::get_extensions_update_notice();
		if ( ! empty( $notice ) ) {
			printf(
				'<div class="updated noptin-message"><p>%s</p></div>',
				wp_kses_post( $notice )
			);
		}

	}

	/**
	 * Checks if we should print an expired license key notice.
	 *
	 */
	public static function maybe_print_expired_license_key_notice() {

		// Fetch premium add-ons.
		$premium_addons = array();

		if ( defined( 'NOPTIN_ADDONS_PACK_VERSION' ) ) {
			$premium_addons[] = 'Noptin Addons Pack';
		}

		$premium_addons = apply_filters( 'noptin_com_helper_premium_addons', $premium_addons );

		// Abort if none exists.
		if ( empty( $premium_addons ) ) {
			return;
		}

		// Check if we have an active license key.
		$license = Noptin_COM::get_active_license_key( true );
		$notice  = __( 'You need an active license key to keep using premium Noptin Addons.', 'newsletter-optin-box' );

		// Add WordPress error message if any.
		if ( is_wp_error( $license ) ) {
			$notice .= ' ' . sprintf(
				/* translators: %s: Error message. */
				__( 'There was an error checking your license key: %s', 'newsletter-optin-box' ),
				'<code>' . $license->get_error_message() . '</code>'
			);
		}

		// Add active addons.
		if ( 1 === count( $premium_addons ) ) {
			$notice .= "\n\n" . sprintf(
				/* translators: %s: Addon name. */
				__( 'The following addon is currently active: %s', 'newsletter-optin-box' ),
				'<code>' . $premium_addons[0] . '</code>'
			);
		} else {
			$notice .= "\n\n" . sprintf(
				/* translators: %s: Addon names. */
				__( 'The following addons are currently active: %s.', 'newsletter-optin-box' ),
				'<code>' . implode( '</code>, <code>', $premium_addons ) . '</code>'
			);
		}

		// Add a link to the license page.
		$notice .= "\n\n" . '<a href="' . esc_url( admin_url( 'admin.php?page=noptin-addons' ) ) . '" class="button button-primary">' . __( 'Manage your license key', 'newsletter-optin-box' ) . '</a>';

		if ( empty( $license ) || is_wp_error( $license ) || empty( $license->is_active_on_site ) ) {
			printf(
				'<div class="error noptin-message">%s</div>',
				wp_kses_post( wpautop( $notice ) )
			);
		}

	}

	/**
	 * Get an update notice if one or more Noptin extensions has an update available.
	 *
	 * @return string|null The update notice or null if everything is up to date.
	 */
	private static function get_extensions_update_notice() {
		$plugins   = Noptin_COM::get_installed_addons();
		$updates   = Noptin_COM_Updater::get_update_data();
		$available = 0;

		foreach ( $plugins as $data ) {
			if ( empty( $updates[ $data['slug'] ] ) ) {
				continue;
			}

			if ( version_compare( $updates[ $data['slug'] ]['version'], $data['Version'], '>' ) ) {
				$available++;
			}
		}

		if ( ! $available ) {
			return;
		}

		return sprintf(
			/* translators: %1$s: helper url, %2$d: number of extensions */
			_n( 'Note: You currently have <a href="%1$s">%2$d paid extension</a> which should be updated first before updating Noptin.', 'Note: You currently have <a href="%1$s">%2$d paid extensions</a> which should be updated first before updating Noptin.', $available, 'newsletter-optin-box' ),
			admin_url( 'admin.php?page=noptin-addons' ),
			$available
		);

	}

	/**
	 * Whether Noptin has an update available.
	 *
	 * @return bool True if a Noptin core update is available.
	 */
	private static function noptin_core_update_available() {
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
	 * Displays the main action button.
	 *
	 * @param object|WP_Error|false $license The active license
	 * @param string $slug The extension slug.
	 * @param array $installed_addons The installed addons.
	 * @param bool  $is_connection Whether this is a connection.
	 */
	public static function display_main_action_button( $license, $slug, $installed_addons, $is_connection ) {
		include plugin_dir_path( __FILE__ ) . 'views/html-extension-action-button.php';
	}

}

Noptin_COM_Helper::load();
