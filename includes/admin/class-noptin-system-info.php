<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Generates a system status report
 *
 * @since       1.2.3
 */
class Noptin_System_Info {

	/**
	 * System info cache.
	 *
	 * @var array
	 */
	public $info = null;

	/**
	 * The main constructor
	 *
	 * @since 1.2.3
	 */
	public function __construct() {
		$this->info = $this->get_info();
	}

	/**
	 * Returns an array of system information.
	 *
	 * @since 1.2.3
	 * @return array
	 */
	public function get_info() {

		$plugins = $this->get_all_plugins();

		/**
		 * Filters the system info.
		 *
		 * @since 1.2.3
		 * @param array $info The current system information.
		 */
		return apply_filters(
			'noptin_system_info',
			array(
				'Site Info'               => $this->get_site_info(),
				'WordPress Configuration' => $this->get_wordpress_config(),
				'Server Info'             => $this->get_server_info(),
				'Active Plugins'          => $plugins['active_plugins'],
				'Inactive Plugins'        => $plugins['inactive_plugins'],
			)
		);
	}

	/**
	 * Returns the system information as a text string for use in textarea elements
	 *
	 * @since 1.2.3
	 * @return string
	 */
	public function get_info_as_text() {

		$text = "### Begin System Info ###\n\n";

		foreach ( $this->info as $cat => $info ) {

			$cat = esc_html( $cat );

			if ( is_array( $info ) ) {

				$text .= "\n\n ----------- $cat -------------- \n\n";

				foreach ( $info as $label => $value ) {

					if ( ! is_scalar( $value ) ) {
						$value = print_r( $value, true );
					}

					$text .= esc_html( "\t $label : $value \n\n" );
				}
			} else {
				$text .= esc_html( "$cat : $info \n\n" );
			}
		}

		$text .= "\n ### End System Info ###";

		/**
		 * Filters the TEXT system info.
		 *
		 * @since 1.2.3
		 * @param string $info The current system information string.
		 */
		return apply_filters( 'noptin_system_info_text', $text );

	}

	/**
	 * Returns the system information as a json string for use with apis or download
	 *
	 * @since 1.2.3
	 * @return string
	 */
	public function get_info_as_json() {

		$info = wp_json_encode( $this->info );

		/**
		 * Filters the JSON system info.
		 *
		 * @since 1.2.3
		 * @param string $info The current system information in JSON format.
		 */
		return apply_filters( 'noptin_system_info_json', $info );

	}

	/**
	 * Returns information about the current WordPress site configuration
	 *
	 * @since 1.2.3
	 * @return array
	 */
	public function get_site_info() {

		/**
		 * Filters the system info shown on Noptin's system info admin page.
		 *
		 * @since 1.2.3
		 * @param array $system_info An array of system information.
		 */
		return apply_filters(
			'noptin_site_system_info',
			array(
				'Site URL'  => site_url(),
				'Home URL'  => home_url(),
				'Multisite' => ( is_multisite() ? 'Yes' : 'No' ),
			)
		);
	}

	/**
	 * Returns information about the current WordPress site configuration
	 *
	 * @since 1.2.3
	 * @return array
	 */
	public function get_wordpress_config() {

		global $wpdb;

		// Retrieve active theme.
		$theme_data = wp_get_theme();
		$theme_name = strip_tags( $theme_data->Name . ' ' . $theme_data->Version );

		if ( ! empty( $theme_data->author ) ) {
			$author      = $theme_data->author;
			$theme_name .= " by $author";
		}

		/**
		 * Filters the WordPress configuration info shown on Noptin's system info admin page.
		 *
		 * @since 1.2.3
		 * @param array $wordpress An array of the current WordPress configuration information.
		 */
		return apply_filters(
			'noptin_wordpress_config_system_info',
			array(
				'Version'               => get_bloginfo( 'version' ),
				'Language'              => get_locale(),
				'Permalink Structure'   => ( get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default' ),
				'Active Theme'          => $theme_name,
				'Table Prefix'          => $wpdb->prefix,
				'WP_DEBUG'              => ( defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' : 'Disabled' : 'Not set' ),
				'Memory Limit'          => WP_MEMORY_LIMIT,
				'Registered Post Stati' => implode( ', ', get_post_stati() ),
			)
		);
	}

	/**
	 * Returns information about the server
	 *
	 * @since 1.2.3
	 * @return array
	 */
	public function get_server_info() {

		global $wpdb;
		$server_data = array();

		if ( function_exists( 'ini_get' ) ) {
			$server_data['Safe Mode']       = ini_get( 'safe_mode' ) ? 'Enabled' : 'Disabled';
			$server_data['Memory Limit']    = ini_get( 'memory_limit' );
			$server_data['Upload Max Size'] = ini_get( 'upload_max_filesize' );
			$server_data['Time Limit']      = ini_get( 'max_execution_time' );
			$server_data['Max Input Vars']  = ini_get( 'max_input_vars' );
		}

		/**
		 * Filters the server system info shown on Noptin's system info admin page.
		 *
		 * @since 1.2.3
		 * @param array $server_data An array of the current server information.
		 */
		return apply_filters(
			'noptin_server_system_info',
			array_merge(
				array(
					'PHP Version'   => PHP_VERSION,
					'MySQL Version' => $wpdb->db_version(),
					'Server'        => ( empty( $_SERVER['SERVER_SOFTWARE'] ) ? 'Unknown' : $_SERVER['SERVER_SOFTWARE'] ),
					'cURL'          => ( function_exists( 'curl_init' ) ? 'Supported' : 'Not Supported' ),
					'fsockopen'     => ( function_exists( 'fsockopen' ) ? 'Supported' : 'Not Supported' ),
					'SOAP Client'   => ( class_exists( 'SoapClient' ) ? 'Installed' : 'Not Installed' ),
					'Suhosin'       => ( extension_loaded( 'suhosin' ) ? 'Installed' : 'Not Installed' ),
				),
				$server_data
			)
		);
	}

	/**
	 * Returns all installed plugins.
	 *
	 * @since 1.2.3
	 * @return array
	 */
	public function get_all_plugins() {

		// Ensure get_plugins function is loaded
		if ( ! function_exists( 'get_plugins' ) ) {
			include ABSPATH . '/wp-admin/includes/plugin.php';
		}

		$plugins             = get_plugins();
		$active_plugins_keys = get_option( 'active_plugins', array() );
		$active_plugins      = array();
		$inactive_plugins    = array();

		foreach ( $plugins as $k => $v ) {

			// Take care of formatting the data how we want it.
			$details     = '';
			$plugin_name = strip_tags( $v['Name'] );

			// (Maybe) Link the plugin name to its url.
			if ( ! empty( $v['PluginURI'] ) ) {
				$plugin_url  = esc_url( $v['PluginURI'] );
				$plugin_name = '<a href="' . $plugin_url . '" aria-label="' . esc_attr__( 'Visit plugin homepage', 'newsletter-optin-box' ) . '" target="_blank">' . $plugin_name . '</a>';
			}

			if ( ! empty( $v['Author'] ) ) {
				$author  = strip_tags( $v['Author'] );

				if ( ! empty( $v['Author'] ) ) {
					$author_url = esc_url( $v['AuthorURI'] );
					$author     = '<a href="' . $author_url . '" aria-label="' . esc_attr__( 'Visit author homepage', 'newsletter-optin-box' ) . '" target="_blank">' . $author . '</a>';
				}
				$details = "by $author &mdash; ";
			}

			if ( ! empty( $v['Version'] ) ) {
				$details .= strip_tags( $v['Version'] );
			}

			if ( ! empty( $v['Network'] ) ) {
				$details .= ' (Network Activated)';
			}

			if ( in_array( $k, $active_plugins_keys, true ) ) {
				$active_plugins[ $plugin_name ] = $details;
			} else {
				$inactive_plugins[ $plugin_name ] = $details;
			}
		}

		return array(
			'active_plugins'   => $active_plugins,
			'inactive_plugins' => $inactive_plugins,
		);
	}
}
