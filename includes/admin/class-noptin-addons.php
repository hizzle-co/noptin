<?php
/**
 * Addons Page
 *
 * @package  Noptin
 * @version  1.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Noptin_Addons Class.
 *
 * @since 1.5.0
 * @ignore
 */
class Noptin_Addons {

	/**
	 * Handles output of the addons page in admin.
	 */
	public static function output() {
		$section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '_featured';
		$search  = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';

		if ( 'helper' === $section ) {
			do_action( 'noptin_helper_output' );
			return;
		}

		$sections        = self::get_sections();
		$current_section = $section;
		$addons          = array();

		if ( ! empty( $sections ) ) {
			$_sections = wp_list_pluck( $sections, 'label', 'slug' );

			if ( ! array_key_exists( $current_section, $_sections ) ) {
				$current_section = $section = key( $_sections );
			}

		}

		if ( '_featured' !== $current_section ) {
			$addons   = empty( $search ) ? self::get_section_data( $current_section ) : self::get_extension_data( $current_section, $search );
		}

		add_thickbox();

		include_once Noptin_Com_Helper::get_view_filename( 'html-admin-page-addons.php' );
	}

	/**
	 * Build url parameter string
	 *
	 * @param  string $category Addon (sub) category.
	 * @param  string $term     Search terms.
	 *
	 * @return string url parameter string
	 */
	public static function build_parameter_string( $category, $term ) {

		$parameters = array(
			'category' => $category,
			'term'     => $term,
		);

		return '?' . http_build_query( $parameters );
	}

	/**
	 * Call API to get extensions
	 *
	 * @param  string $category Addon (sub) category.
	 * @param  string $term     Search terms.
	 *
	 * @return array of extensions
	 */
	public static function get_extension_data( $category, $term ) {
		$parameters     = self::build_parameter_string( $category, $term );
		$raw_extensions = Noptin_COM_API_Client::get( 'nopcom/1/extensions/search' . $parameters );

		if ( ! is_wp_error( $raw_extensions ) && 300 > Noptin_COM_API_Client::$last_response_code  ) {
			return $raw_extensions->products;
		}

		return array();
	}

	/**
	 * Get sections for the addons screen
	 *
	 * @return array|false of objects
	 */
	public static function get_sections() {
		$addon_sections = get_transient( 'noptin_addons_sections' );

		if ( false === ( $addon_sections ) ) {

			$raw_sections = Noptin_COM_API_Client::get( 'nopcom/1/extensions/categories' );

			if ( ! is_wp_error( $raw_sections ) && 300 > Noptin_COM_API_Client::$last_response_code ) {
				$addon_sections = $raw_sections;
				set_transient( 'noptin_addons_sections', $addon_sections, DAY_IN_SECONDS );
			}

		}

		return apply_filters( 'noptin_addons_sections', $addon_sections );
	}

	/**
	 * Get section for the addons screen.
	 *
	 * @param  string $section_id Required section ID.
	 *
	 * @return object|false
	 */
	public static function get_section( $section_id ) {
		$sections = self::get_sections();

		if ( empty( $sections ) ) {
			return false;
		}

		foreach ( $sections as $section ) {

			if ( $section->slug === $section_id ) {
				return $section;
			}

		}

		return false;
	}

	/**
	 * Get section content for the addons screen.
	 *
	 * @param  string $section_id Required section ID.
	 *
	 * @return array
	 */
	public static function get_section_data( $section_id ) {
		$section      = self::get_section( $section_id );
		$section_data = '';

		if ( ! empty( $section->endpoint ) ) {
			$section_data = get_transient( 'noptin_addons_section_' . $section_id );
			if ( false === $section_data ) {
				$raw_section = wp_safe_remote_get( esc_url_raw( $section->endpoint ), array( 'user-agent' => 'Noptin Addons Page' ) );

				if ( ! is_wp_error( $raw_section ) ) {
					$raw_section_data = json_decode( wp_remote_retrieve_body( $raw_section ) );

					if ( ! empty( $raw_section_data->products ) ) {
						$section_data = $raw_section_data;
						set_transient( 'noptin_addons_section_' . $section_id, $section_data, DAY_IN_SECONDS );
					}
				}
			}
		}

		return apply_filters( 'noptin_addons_section_data', empty( $section_data->products ) ? array() : $section_data->products, $section_id );
	}

	/**
	 * Returns in-app-purchase URL params.
	 */
	public static function get_in_app_purchase_url_params() {
		// Get url (from path onward) for the current page,
		// so noptin.com "back" link returns the user to where they were.
		$back_admin_path = add_query_arg( array() );
		return array(
			'noptin-site'          => site_url(),
			'noptin-back'          => rawurlencode( $back_admin_path ),
			'noptin-version'       => noptin()->version,
			'noptin-connect-nonce' => wp_create_nonce( 'connect' ),
		);
	}

	/**
	 * Add in-app-purchase URL params to link.
	 *
	 * Adds various url parameters to a url to support a streamlined
	 * flow for obtaining and setting up Noptin extensons.
	 *
	 * @param string $url    Destination URL.
	 */
	public static function add_in_app_purchase_url_params( $url ) {
		return add_query_arg(
			self::get_in_app_purchase_url_params(),
			$url
		);
	}

}
