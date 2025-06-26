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
	 * @var Subscription_Checkbox Subscription checkbox.
	 */
	public $subscription_checkbox;

	/**
	 * Init variables.
	 *
	 * @since       1.2.6
	 */
	public function __construct() {
		$this->email_template = new Template();

		// Backwards compatiblity, check if the class exists before instantiating.
		if ( class_exists( '\Hizzle\Noptin\Integrations\Checkbox_Integration' ) ) {
			$this->subscription_checkbox = new Subscription_Checkbox();
		}

		add_action( 'init', array( $this, 'register_custom_objects' ), 5 );
		add_filter( 'noptin_supports_ecommerce_tracking', '__return_true' );
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
