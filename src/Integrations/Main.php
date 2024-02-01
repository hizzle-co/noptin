<?php

namespace Hizzle\Noptin\Integrations;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Connects Noptin to plugins such as WooCommerce and EDD.
 *
 * @since 2.0.0
 */
class Main {

	/**
	 * @var array Available Noptin integrations.
	 */
	public $integrations = array();

	/**
	 * @var array Admin notices.
	 */
	private $notices = array();

	/**
	 * Class Constructor.
	 */
	public function __construct() {

		// Load core integrations.
		add_action( 'noptin_load', array( $this, 'load_integrations' ) );

		// Admin notices.
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	/**
	 * Loads available integrations.
	 *
	 */
	public function load_integrations() {

		$integrations_dir = apply_filters(
			'noptin_integrations_dir',
			plugin_dir_path( __FILE__ ) . '*'
		);

		foreach ( glob( $integrations_dir, GLOB_ONLYDIR ) as $integration_dir ) {

			// Get the integration namespace.
			$namespace = basename( $integration_dir );

			// Load the config file.
			$config = wp_json_file_decode( $integration_dir . '/config.json', array( 'associative' => true ) );

			// Check if the integration is usable.
			if ( empty( $config ) || ! $this->is_integration_usable( $config ) ) {
				continue;
			}

			// Load the integration class.
			$class = 'Hizzle\\Noptin\\Integrations\\' . $namespace . '\\Main';

			if ( class_exists( $class ) ) {

				// Are we loading via a hook?
				if ( ! empty( $config['hook'] ) ) {
					add_action( $config['hook'], $class . '::noptin_init', 10, 2 );
				} else {
					$this->integrations[ $config['slug'] ] = new $class();
				}
			}

			// Optionally load premium functionality.
			$class = 'Hizzle\\Noptin\\Integrations\\' . $namespace . '\\Premium\\Main';

			if ( class_exists( $class ) ) {

				// Are we loading via a hook?
				if ( ! empty( $config['hook'] ) ) {
					add_action( $config['hook'], $class . '::noptin_init', 11, 2 );
				} else {
					new $class();
				}
			}
		}
	}

	/**
	 * Checks if an integration is usable.
	 *
	 * @param array $config The config file.
	 */
	private function is_integration_usable( $config ) {

		if ( empty( $config['requires'] ) || ! is_array( $config['requires'] ) ) {
			return true;
		}

		foreach ( $config['requires'] as $key => $value ) {

			switch ( $key ) {

				// Specific noptin version.
				case 'noptin':
					if ( version_compare( noptin()->version, $value, '<' ) ) {
						$this->notices[ $config['label'] ] = sprintf(
							// translators: %1$s is the integration label, %2$s is the required Noptin version.
							__( 'The %1$s integration requires Noptin version %2$s or higher.', 'newsletter-optin-box' ),
							$config['label'],
							$value
						);

						return false;
					}
					break;

				// Specific PHP version.
				case 'php':
					if ( version_compare( PHP_VERSION, $value, '<' ) ) {
						$this->notices[ $config['label'] ] = sprintf(
							// translators: %1$s is the integration label, %2$s is the required PHP version.
							__( 'The %1$s integration requires PHP version %2$s or higher.', 'newsletter-optin-box' ),
							$config['label'],
							$value
						);

						return false;
					}
					break;

				// Specific WordPress version.
				case 'wp':
					if ( version_compare( get_bloginfo( 'version' ), $value, '<' ) ) {
						$this->notices[ $config['label'] ] = sprintf(
							// translators: %1$s is the integration label, %2$s is the required WordPress version.
							__( 'The %1$s integration requires WordPress version %2$s or higher.', 'newsletter-optin-box' ),
							$config['label'],
							$value
						);

						return false;
					}
					break;

				// Specific plugin version.
				case 'plugin_version':
					$plugin_name       = $value['label'];
					$check_constant    = $value['constant'];
					$value             = $value['version'];
					$installed_version = defined( $check_constant ) ? constant( $check_constant ) : '0';

					// No need for a notice if the plugin is not installed.
					if ( '0' === $installed_version ) {
						return false;
					}

					if ( version_compare( $installed_version, $value, '<' ) ) {
						$this->notices[] = sprintf(
							// translators: %1$s is the integration label, %2$s is the required plugin version.
							__( 'The %1$s integration requires %2$s version %3$s or higher.', 'newsletter-optin-box' ),
							$config['label'],
							$plugin_name,
							$value
						);

						return false;
					}
					break;

				// Specific class.
				case 'class':
					if ( ! class_exists( $value ) ) {
						return false;
					}
					break;

				// Specific function.
				case 'function':
					if ( ! function_exists( $value ) ) {
						return false;
					}
					break;

				// Specific constant.
				case 'constant':
					if ( ! defined( $value ) ) {
						return false;
					}
					break;
			}
		}

		return true;
	}

	/**
	 * Displays admin notices.
	 */
	public function admin_notices() {

		if ( empty( $this->notices ) ) {
			return;
		}

		foreach ( $this->notices as $notice ) {
			printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $notice ) );
		}
	}
}
