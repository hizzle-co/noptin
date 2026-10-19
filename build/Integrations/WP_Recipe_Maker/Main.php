<?php

namespace Hizzle\Noptin\Integrations\WP_Recipe_Maker;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles integration with WP Recipe Maker
 *
 * @since 3.0.0
 */
class Main {

	/**
	 * Class constructor.
	 *
	 * @since 2.2.0
	 */
	public function __construct() {
		add_action( 'noptin_register_post_type_objects', array( $this, 'register_custom_objects' ) );
	}

	/**
	 * Registers custom objects.
	 *
	 * @since 3.0.0
	 */
	public function register_custom_objects() {
		\Hizzle\Noptin\Objects\Store::add( new Recipes() );
	}
}
