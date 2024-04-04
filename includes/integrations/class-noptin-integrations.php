<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles integrations with other products and services
 *
 * @since       1.0.8
 */
class Noptin_Integrations {

	/**
	 * @var array Available Noptin integrations.
	 */
	public $integrations = array();

	/**
	 * Class Constructor.
	 */
	public function __construct() {

		// WooCommerce integration.
		if ( class_exists( 'WooCommerce' ) ) {
			$this->integrations['woocommerce'] = new Noptin_WooCommerce();
		}

		// EDD integration.
		if ( class_exists( 'Easy_Digital_Downloads' ) ) {
			$this->integrations['edd'] = new Noptin_EDD();
		}

		// WP Registration form integration.
		$this->integrations['wp_registration_form'] = new Noptin_WP_Registration_Form();

		// WP Comment form integration.
		$this->integrations['wp_comment_form'] = new Noptin_WP_Comment_Form();

		do_action( 'noptin_integrations_load', $this );
	}
}
