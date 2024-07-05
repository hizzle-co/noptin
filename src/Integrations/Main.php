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
	 * @var array Installed Noptin integrations.
	 */
	public $integrations = array();

	/**
	 * @var array Known Noptin integrations.
	 */
	private $all_integrations = null;

	/**
	 * @var array Admin notices.
	 */
	private $notices = array();

	/**
	 * @var array Loaded integration paths.
	 */
	private $paths = array();

	/**
	 * Class Constructor.
	 */
	public function __construct() {

		// Register autoloader.
		spl_autoload_register( array( $this, 'autoload' ) );

		// Load core integrations.
		$this->load_integrations();

		add_filter( 'noptin_get_all_known_integrations', array( $this, 'get_all_known_integrations' ), 0 );

		add_action( 'noptin_refresh_integrations', array( __CLASS__, 'refresh' ) );
		add_action( 'noptin_upgrade_db', array( __CLASS__, 'refresh' ) );

		// Admin notices.
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}

	/**
	 * Loads available integrations.
	 *
	 */
	public function load_integrations() {

		$integration_dirs   = apply_filters( 'noptin_integration_dirs', array() );
		$integration_dirs[] = plugin_dir_path( __FILE__ ) . '*';

		foreach ( $integration_dirs as $integrations_dir ) {
			foreach ( glob( $integrations_dir, GLOB_ONLYDIR ) as $integration_dir ) {

				// Get the integration namespace.
				$namespace = basename( $integration_dir );

				// Abort if the integration is already loaded.
				if ( isset( $this->paths[ $namespace ] ) ) {
					continue;
				}

				// Load the config file.
				$config = wp_json_file_decode( $integration_dir . '/config.json', array( 'associative' => true ) );

				// Check if the integration is usable.
				if ( empty( $config ) || ! $this->is_integration_usable( $config ) ) {
					continue;
				}

				$this->paths[ $namespace ] = $integration_dir;

				// Load the integration class.
				$class_name = 'Hizzle\\Noptin\\Integrations\\' . $namespace . '\\Main';

				if ( class_exists( $class_name ) ) {

					// Are we loading via a hook?
					if ( ! empty( $config['hook'] ) ) {
						add_action( $config['hook'], $class_name . '::noptin_init', 10, 2 );
					} else {
						$this->integrations[ $config['slug'] ] = new $class_name();
					}
				}

				// Optionally load premium functionality.
				$class_name = 'Hizzle\\Noptin\\Integrations\\' . $namespace . '\\Premium\\Main';

				if ( class_exists( $class_name ) && noptin_has_active_license_key() ) {

					// Are we loading via a hook?
					if ( ! empty( $config['hook'] ) ) {
						add_action( $config['hook'], $class_name . '::noptin_init', 11, 2 );
					} else {
						new $class_name();
					}
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

				// Specific theme.
				case 'theme':
					if ( get_template() === $value['template'] ) {
						break;
					}

					$current_theme = wp_get_theme();
					$parent_theme  = $current_theme->exists() ? $current_theme->parent() : false;

					// Check if either the parent or child theme is active.
					if ( $parent_theme && $parent_theme->get( 'Name' ) === $value['name'] ) {
						break;
					}

					if ( $current_theme->get( 'Name' ) !== $value['name'] ) {
						$this->notices[ $config['label'] ] = sprintf(
							// translators: %1$s is the integration label, %2$s is the required theme.
							__( 'The %1$s integration requires the %2$s theme.', 'newsletter-optin-box' ),
							$config['label'],
							$value['name']
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

	/**
	 * Autoloads integration classes.
	 *
	 * @param string $class_name The class name.
	 */
	public function autoload( $class_name ) {

		if ( 0 !== strpos( $class_name, 'Hizzle\\Noptin\\Integrations\\' ) ) {
			return;
		}

		// Remove our namespace prefix.
		$class_name = str_replace( 'Hizzle\\Noptin\\Integrations\\', '', $class_name );
		$namespace  = substr( $class_name, 0, strpos( $class_name, '\\' ) );
		$class_name = str_replace( $namespace . '\\', '', $class_name );

		if ( ! isset( $this->paths[ $namespace ] ) ) {
			return;
		}

		$path = $this->paths[ $namespace ] . '/' . str_replace( '\\', '/', $class_name ) . '.php';

		if ( file_exists( $path ) ) {
			require $path;
		}
	}

	/**
	 * Returns all known integrations.
	 *
	 */
	public function get_all_known_integrations() {

		if ( is_array( $this->all_integrations ) ) {
			return $this->all_integrations;
		}

		$all = get_option( 'noptin_integrations' );

		if ( ! is_array( $all ) ) {
			$all = wp_json_file_decode( plugin_dir_path( __FILE__ ) . 'integrations.json', array( 'associative' => true ) );
		}

		$this->all_integrations = array();
		if ( empty( $all ) ) {
			return $this->all_integrations;
		}

		$installed_addons = wp_list_pluck( \Noptin_COM::get_installed_addons(), '_filename', 'slug' );
		$has_license_key  = noptin_has_active_license_key();
		$old_notices      = $this->notices;
		foreach ( $all as $integration ) {
			if ( $this->is_integration_usable( $integration ) ) {
				$integration['is_installed'] = isset( $this->integrations[ $integration['slug'] ] );

				// Prepare actions and triggers.
				foreach ( array( 'triggers', 'actions' ) as $part ) {
					if ( ! isset( $integration[ $part ] ) || ! is_array( $integration[ $part ] ) ) {
						continue;
					}

					foreach ( $integration[ $part ] as $group => $group_parts ) {
						if ( 'requires' === $group_parts[0]['id'] ) {
							if ( ! $this->is_integration_usable( $group_parts[0] ) ) {
								unset( $integration[ $part ][ $group ] );
								continue;
							}

							if ( ! empty( $group_parts[0]['premium'] ) ) {
								foreach ( $group_parts as $index => $group_part ) {
									$integration[ $part ][ $group ][ $index ]['premium'] = true;
								}
							}

							array_shift( $integration[ $part ][ $group ] );
						}
					}
				}

				// Add install info.
				if ( $has_license_key && isset( $integration['plan'] ) && 'free' !== $integration['plan'] ) {
					$slug                        = 'noptin-' . $integration['slug'];
					$integration['installation'] = array(
						'install_text' => isset( $installed_addons[ $slug ] ) ? __( 'Activate', 'newsletter-optin-box' ) : __( 'Install', 'newsletter-optin-box' ),
						'install_desc' => sprintf(
							// translators: %s plugin name.
							isset( $installed_addons[ $slug ] ) ? __( 'Activate the %s integration to unlock.', 'newsletter-optin-box' ) : __( 'Install the %s integration to unlock.', 'newsletter-optin-box' ),
							$integration['label']
						),
						'install_url'  => isset( $installed_addons[ $slug ] ) ?
							str_replace( '&amp;', '&', wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . $installed_addons[ $slug ] ), 'activate-plugin_' . $installed_addons[ $slug ] ) ) :
							str_replace( '&amp;', '&', wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=noptin-plugin-with-slug-' . $slug ), 'install-plugin_noptin-plugin-with-slug-' . $slug ) ),
					);
				}

				$this->all_integrations[] = $integration;
			}
		}

		$this->notices = $old_notices;

		return $this->all_integrations;
	}

	/**
	 * Refreshes all known integrations.
	 *
	 */
	public static function refresh() {

		// Fetch the integrations.
		$result = \Noptin_COM::process_api_response( wp_remote_get( 'https://noptin.com/wp-content/uploads/noptin/integrations.json' ) );
		if ( is_array( $result ) ) {
			$result = json_decode( wp_json_encode( $result ), true );
			update_option( 'noptin_integrations', $result );
		}
	}

	/**
	 * Retrieves an integration info.
	 */
	public static function get_integration_info( $slug ) {
		$all = wp_json_file_decode( plugin_dir_path( __FILE__ ) . 'integrations.json', array( 'associative' => true ) );

		if ( empty( $all ) ) {
			return null;
		}

		foreach ( $all as $integration ) {
			if ( $slug === $integration['slug'] ) {
				return $integration;
			}
		}

		return null;
	}
}
