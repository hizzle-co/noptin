<?php

/**
 * Main onboarding class.
 *
 * @since   3.4.6
 * @package Noptin
 */

namespace Hizzle\Noptin\Onboarding;

defined( 'ABSPATH' ) || exit;

/**
 * Main onboarding class.
 */
class Main {

	/**
	 * Inits the main onboarding class.
	 *
	 */
	public static function init() {

		// Admin.
		if ( is_admin() ) {
			Menu::init();
		}

		// Install and activate plugins.
		add_action( 'wp_ajax_noptin_onboarding_ajax_install_plugin', 'wp_ajax_install_plugin' );
		add_action( 'wp_ajax_noptin_onboarding_ajax_activate_plugin', 'wp_ajax_activate_plugin' );
	}

	/**
	 * Get CRM connections.
	 *
	 * @return array
	 */
	public static function get_crm_connections() {
		return array_map(
			function ( $connection ) {
				return array(
					'slug'        => $connection->slug,
					'name'        => $connection->name,
					'description' => sprintf( /* translators: %s Integration such as Mailchimp */ __( 'Connect Noptin to %s', 'newsletter-optin-box' ), $connection->name ),
					'icon'        => $connection->image_url ?? '',
					'plugin_url'  => $connection->connect_url ?? '',
				);
			},
			\Noptin_COM::get_connections()
		);
	}

	/**
	 * Get detected integrations.
	 *
	 * @return array
	 */
	public static function get_detected_integrations() {
		return array_map(
			function ( $integration ) {
				return array(
					'slug'        => $integration['slug'],
					'name'        => $integration['label'],
					'description' => $integration['description'] ?? '',
					'icon'        => $integration['icon_url'] ?? '',
					'plugin_url'  => $integration['url'] ?? '',
					'isFree'      => 'free' === ( $integration['plan'] ?? false ),
				);
			},
			apply_filters( 'noptin_get_all_known_integrations', array() )
		);
	}
}
