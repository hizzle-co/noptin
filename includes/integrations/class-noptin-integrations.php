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

		// The base class for most integrations.
		require_once plugin_dir_path( __FILE__ ) . 'class-noptin-abstract-integration.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-noptin-abstract-ecommerce-integration.php';

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

		// WooCommerce integration.
		if ( class_exists( 'WooCommerce' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'class-noptin-woocommerce.php';
			$this->integrations['woocommerce'] = new Noptin_WooCommerce();
		}

		// EDD integration.
		if ( class_exists( 'Easy_Digital_Downloads' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'class-noptin-edd.php';
			$this->integrations['edd'] = new Noptin_EDD();
		}

		// WP Registration form integration.
		require_once plugin_dir_path( __FILE__ ) . 'class-noptin-wp-registration-form.php';
		$this->integrations['wp_registration_form'] = new Noptin_WP_Registration_Form();

		// WP Comment form integration.
		require_once plugin_dir_path( __FILE__ ) . 'class-noptin-wp-comment-form.php';
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
		require_once plugin_dir_path( __FILE__ ) . 'class-noptin-wpforms.php';
		new Noptin_WPForms();
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
