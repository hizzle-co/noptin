<?php

namespace Hizzle\Noptin\Misc;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main misc class.
 *
 * @since 3.0.0
 */
class Main {

	/**
	 * Enqueues interface scripts and styles.
	 *
	 */
	public static function load_interface_styles() {

		$config = include plugin_dir_path( __FILE__ ) . 'assets/js/interface.asset.php';

		wp_enqueue_style(
			'noptin-interface',
			plugins_url( 'assets/css/style-interface.css', __FILE__ ),
			array(),
			$config['version']
		);
	}
}
