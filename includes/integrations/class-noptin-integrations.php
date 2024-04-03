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

		// Load integrations.
		$integrations = array(
			'pll_init'           => 'load_polylang_integration',
		);

		foreach ( $integrations as $action => $method ) {

			add_action( $action, array( $this, $method ) );
			if ( did_action( $action ) ) {
				call_user_func( array( $this, $method ) );
			}
		}

		// WooCommerce integration.
		if ( class_exists( 'WooCommerce' ) ) {
			$this->integrations['woocommerce'] = new Noptin_WooCommerce();
		}

		// EDD integration.
		if ( class_exists( 'Easy_Digital_Downloads' ) ) {
			$this->integrations['edd'] = new Noptin_EDD();
		}

		// WS Form integration.
		if ( class_exists( 'WS_Form' ) ) {
			$this->integrations['ws_form'] = new Noptin_WS_Form();
		}

		// WP Registration form integration.
		$this->integrations['wp_registration_form'] = new Noptin_WP_Registration_Form();

		// WP Comment form integration.
		$this->integrations['wp_comment_form'] = new Noptin_WP_Comment_Form();

		// Formidable forms.
		add_action( 'frm_registered_form_actions', array( $this, 'register_formidable_form_action' ) );
		add_action( 'frm_action_groups', array( $this, 'group_formidable_form_action' ) );
		add_action( 'frm_trigger_noptin_action', 'Noptin_Formidable_Forms::process_form', 10, 2 );

		do_action( 'noptin_integrations_load', $this );

	}

	/**
	 * Loads Polylang integration
	 *
	 * @access      public
	 * @since       1.6.2
	 */
	public function load_polylang_integration() {
		new Noptin_Polylang();
	}

	/**
	 * Registers formidable forms action.
	 *
	 * @param      array $actions
	 * @since      1.5.5
	 */
	public function register_formidable_form_action( $actions ) {
		$actions['noptin'] = 'Noptin_Formidable_Forms';
		return $actions;
	}

	/**
	 * Groups the formidable forms action.
	 *
	 * @param      array $groups
	 * @since      1.5.5
	 */
	public function group_formidable_form_action( $groups ) {
		$groups['marketing']['actions'][] = 'noptin';
		return $groups;
	}
}
