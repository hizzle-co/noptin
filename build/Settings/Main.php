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
}
