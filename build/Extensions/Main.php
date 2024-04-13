<?php

/**
 * Main extensions class.
 *
 * @since   3.2.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Extensions;

defined( 'ABSPATH' ) || exit;

/**
 * Main extensions class.
 */
class Main {

	/**
	 * Inits the main emails class.
	 *
	 */
	public static function init() {

		if ( is_admin() ) {
			Menu::init();
		}
	}

}
