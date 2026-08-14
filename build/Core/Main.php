<?php

/**
 * Main core class.
 *
 * @package Noptin
 */

namespace Hizzle\Noptin\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Loads core functionality.
 */
class Main {

	/**
	 * Loads core functions.
	 */
	public static function init() {
		require_once plugin_dir_path( __FILE__ ) . 'functions.php';
	}
}
