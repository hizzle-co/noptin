<?php

namespace Hizzle\Noptin\Integrations\WooCommerce;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles integration with WooCommerce
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
		\Hizzle\Noptin\Objects\Store::add( new Customers() );
		\Hizzle\Noptin\Objects\Store::add( new Orders() );
		\Hizzle\Noptin\Objects\Store::add( new Order_Items() );
		\Hizzle\Noptin\Objects\Store::add( new Products() );
	}
}
