<?php

/**
 * Main settings class.
 *
 * @since   3.2.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Main settings class.
 */
class Main {

	/**
	 * Inits the main emails class.
	 *
	 */
	public static function init() {

		// Prepare rest API.
		add_action( 'rest_api_init', array( __CLASS__, 'register_setting' ), 11 );

		if ( is_admin() ) {
			Menu::init();
		}
	}

	/**
	 * Register rest fields.
	 */
	public static function register_setting() {

		// Register settings.
		register_setting(
			'options',
			'noptin_options',
			array(
				'type'              => 'object',
				'description'       => 'Noptin settings',
				'default'           => array(
					'success_message' => __( 'Thanks for subscribing to our newsletter', 'newsletter-optin-box' ),
				),
				'show_in_rest'      => array(
					'schema' => array(
						'type'                 => 'object',
						'properties'           => array(
							'success_message' => array(
								'type' => 'string',
							),
						),
						'additionalProperties' => true,
					),
				),
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
			)
		);

		// Retrieve available settings for a given section and sub-section.
		register_rest_route(
			'noptin/v1',
			'/settings/(?P<section_slug>[a-zA-Z0-9-_]+)/(?P<sub_section_slug>[a-zA-Z0-9-_]+)?',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_settings_section' ),
				'permission_callback' => function () {
					return current_user_can( get_noptin_capability() );
				},
			)
		);
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $settings Settings.
	 *
	 * @return array
	 */
	public static function sanitize_settings( $settings ) {

		if ( ! empty( $settings['custom_fields'] ) ) {

			$prepared = array();
			foreach ( $settings['custom_fields'] as $custom_field ) {

				if ( ! empty( $custom_field['label'] ) && empty( $custom_field['merge_tag'] ) ) {
					$custom_field['merge_tag'] = sanitize_key( preg_replace( '/[^a-z0-9_]/', '_', strtolower( $custom_field['label'] ) ) );
				}

				// Skip fields that don't have a type, label, or merge tag.
				if ( empty( $custom_field['type'] ) || empty( $custom_field['label'] ) || empty( $custom_field['merge_tag'] ) ) {
					continue;
				}

				if ( isset( $custom_field['new'] ) ) {
					unset( $custom_field['new'] );
				}

				$prepared[] = $custom_field;
			}

			$settings['custom_fields'] = $prepared;
		}

		return $settings;
	}

	/**
	 * Get settings section by slug.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public static function get_settings_section( $request ) {
		if ( empty( $request['section_slug'] ) || empty( $request['sub_section_slug'] ) ) {
			return new \WP_Error( 'missing_section_slug', 'Provide both section and subsection slugs.', array( 'status' => 400 ) );
		}

		$settings = \Hizzle\Noptin\Settings\Menu::prepare_settings();
		$prepared = $settings[ $request['section_slug'] ]['sub_sections'][ $request['sub_section_slug'] ]['settings'] ?? null;

		if ( empty( $prepared ) ) {
			return new \WP_Error( 'invalid_section_slug', 'Invalid section or subsection slug.', array( 'status' => 404 ) );
		}

		return rest_ensure_response( $prepared );
	}
}
