<?php

/**
 * Main templates class.
 *
 * @since   3.0.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Emails;

defined( 'ABSPATH' ) || exit;

/**
 * Main templates class.
 */
class Templates {

	/**
	 * Inits the main templates class.
	 *
	 */
	public static function init() {
		add_action( 'noptin_refresh_email_templates', array( __CLASS__, 'refresh_templates' ) );
		add_filter( 'noptin_email_settings_misc', array( __CLASS__, 'add_templates' ), 10, 2 );
		add_filter( 'noptin_get_default_email_props', array( __CLASS__, 'add_template' ), 100, 2 );
	}

	/**
	 * Refreshes all known email templates.
	 *
	 */
	public static function refresh_templates() {

		// Fetch the templates.
		$result = \Noptin_COM::process_api_response( wp_remote_get( 'https://noptin.com/email-templates/list.json' ) );
		if ( is_array( $result ) ) {
			$result = json_decode( wp_json_encode( $result ), true );
			update_option( 'noptin_email_templates', $result );
		}
	}

	/**
	 * Add templates.
	 *
	 * @param array $settings The settings.
	 * @param string $script The script.
	 * @return array
	 */
	public static function add_templates( $settings, $script ) {
		if ( 'view-campaigns' !== $script ) {
			return $settings;
		}

		$templates = get_option( 'noptin_email_templates', array() );

		if ( ! is_array( $templates ) || empty( $templates ) ) {
			$templates = wp_json_file_decode( plugin_dir_path( __FILE__ ) . 'templates.json', array( 'associative' => true ) );
		}

		if ( ! is_array( $templates ) || empty( $templates ) ) {
			return $settings;
		}

		$settings['templates'] = $templates;

		return $settings;
	}

	/**
	 * Add template.
	 *
	 * @param array $props The props.
	 * @return array
	 */
	public static function add_template( $props, $edited_campaign ) {
		$template = $edited_campaign->get( 'noptin_source_template' );

		if ( empty( $template ) || 'blank' === $template ) {
			return $props;
		}

		$template = \Noptin_COM::process_api_response( wp_remote_get( "https://noptin.com/email-templates/{$template}.json" ) );

		if ( is_wp_error( $template ) ) {
			wp_die( 'Could not fetch template code:- ' . esc_html( $template->get_error_message() ) );
		}

		$template = json_decode( wp_json_encode( $template ), true );

		$props['content_visual'] = str_ireplace( array( '{{DEFAULT_FOOTER_TEXT}}', '{{FOOTER_TEXT}}' ), get_noptin_footer_text(), $template['content'] );

		return array_merge(
			$props,
			$template['options']
		);
	}
}
