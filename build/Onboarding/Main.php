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

		// Install the database.
		Installer::init();

		// Install and activate plugins.
		add_action( 'wp_ajax_noptin_onboarding_ajax_install_plugin', 'wp_ajax_install_plugin' );
		add_action( 'wp_ajax_noptin_onboarding_ajax_activate_plugin', 'wp_ajax_activate_plugin' );
	}

}
