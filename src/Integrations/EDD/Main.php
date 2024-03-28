<?php

namespace Hizzle\Noptin\Integrations\EDD;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles integrations with EDD
 *
 * @since 1.2.6
 */
class Main {

	/**
	 * @var Template Email template.
	 */
	public $email_template;

	/**
	 * Init variables.
	 *
	 * @since       1.2.6
	 */
	public function before_initialize() {
		$this->email_template = new Template();
		add_action( 'noptin_register_post_type_objects', array( $this, 'register_custom_objects' ) );
	}

	/**
	 * Registers custom objects.
	 *
	 * @since 3.0.0
	 */
	public function register_custom_objects() {
		\Hizzle\Noptin\Objects\Store::add( new Customers() );
		\Hizzle\Noptin\Objects\Store::add( new Order_Items() );
		\Hizzle\Noptin\Objects\Store::add( new Orders() );
		\Hizzle\Noptin\Objects\Store::add( new Downloads() );
	}
}
