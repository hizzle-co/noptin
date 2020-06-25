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

		// Comment prompts.
		add_filter( 'comment_post_redirect', array( $this, 'comment_post_redirect' ), 10, 2 );

		// Ninja forms integration.
		if ( class_exists( 'Ninja_Forms' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'class-noptin-ninja-forms.php';
		}

		// WPForms integration.
		add_action( 'wpforms_loaded', array( $this, 'load_wpforms_integration' ) );
		if ( did_action( 'wpforms_loaded' ) ) {
			$this->load_wpforms_integration();
		}

		// Elementor forms integration.
		add_action( 'elementor_pro/init', array( $this, 'load_elementor_forms_integration' ) );
		if ( did_action( 'elementor_pro/init' ) ) {
			$this->load_elementor_forms_integration();
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

		do_action( 'noptin_integrations_load', $this );

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
	 * Redirect to a custom URL after a comment is submitted
	 * Added query arg used for displaying prompt
	 *
	 * @param string $location Redirect URL.
	 * @param object $comment Comment object.
	 * @return string $location New redirect URL
	 */
	function comment_post_redirect( $location, $comment ) {
		return add_query_arg( 'noptin-ca', $comment->comment_ID, $location );
	}

}
