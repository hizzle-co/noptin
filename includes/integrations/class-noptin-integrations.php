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
		add_action( 'after_setup_theme', array( $this, 'load_integrations' ), 10 );
	}

	public function load_integrations() {

		// WP Registration form integration.
		$this->integrations['wp_registration_form'] = new Noptin_WP_Registration_Form();

		// WP Comment form integration.
		$this->integrations['wp_comment_form'] = new Noptin_WP_Comment_Form();

		do_action( 'noptin_integrations_load', $this );
	}
}
