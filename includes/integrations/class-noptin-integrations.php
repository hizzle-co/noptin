<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

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
			'nf_init'            => 'load_ninja_forms_integration',
			'wpforms_loaded'     => 'load_wpforms_integration',
			'wpcf7_init'         => 'load_contact_form_7_integration',
			'elementor_pro/init' => 'load_elementor_forms_integration',
			'gform_loaded'       => 'load_gravity_forms_integration',
			'getpaid_actions'    => 'load_getpaid_integration',
			'wpml_loaded'        => 'load_wpml_integration',
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
	 * Loads the GetPaid and Noptin integration
	 *
	 * @access      public
	 * @since       1.4.1
	 */
	public function load_getpaid_integration() {
		$this->integrations['getpaid'] = new Noptin_GetPaid();
	}

	/**
	 * Loads WPForms integration
	 *
	 * @access      public
	 * @since       1.2.6
	 */
	public function load_wpforms_integration() {
		new Noptin_WPForms();
	}

	/**
	 * Loads WPML integration
	 *
	 * @access      public
	 * @since       1.6.2
	 */
	public function load_wpml_integration() {
		new Noptin_WPML();
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
	 * Loads Ninja Forms integration
	 *
	 * @access      public
	 * @since       1.3.3
	 */
	public function load_ninja_forms_integration() {
		if ( class_exists( 'Ninja_Forms' ) ) {
			$ninja_forms = Ninja_Forms::instance();
			$ninja_forms->actions['noptin'] = new Noptin_Ninja_Forms();
		}
	}

	/**
	 * Loads Gravity Forms integration
	 *
	 * @access      public
	 * @since       1.3.3
	 */
	public function load_gravity_forms_integration() {
		if ( class_exists( 'GFAddOn' ) ) {
			GFAddOn::register( 'Noptin_Gravity_Forms' );
		}
	}

	/**
	 * Loads Contact Form 7 integration
	 *
	 * @access      public
	 * @since       1.3.3
	 */
	public function load_contact_form_7_integration() {
		new Noptin_Contact_Form_7();
	}

	/**
	 * Loads Elementor forms integration
	 *
	 * @access      public
	 * @since       1.3.2
	 */
	public function load_elementor_forms_integration() {

		// Ensure the elementor pro class exists.
		if ( ! class_exists( '\ElementorPro\Plugin' ) ) {
			return;
		}

		// Instantiate the action class
		$action = new Noptin_Elementor_Forms_Integration();

		// Register the action with form widget
		\ElementorPro\Plugin::instance()->modules_manager->get_modules( 'forms' )->add_form_action( $action->get_name(), $action );
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
