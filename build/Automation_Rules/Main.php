<?php

/**
 * Main automation rules class.
 *
 * @since   3.1.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Automation_Rules;

defined( 'ABSPATH' ) || exit;

/**
 * Main automation rules class.
 */
class Main {

	/**
	 * Inits the main emails class.
	 *
	 */
	public static function init() {

		if ( is_admin() ) {
			Admin\Main::init();
		}
	}
}
