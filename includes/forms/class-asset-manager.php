<?php
/**
 * Forms API: Assets manager.
 *
 * Contains the main class for loading form assets.
 *
 * @since   1.6.1
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * This class takes care of all form assets related functionality
 *
 * @access private
 * @since 1.6.1
 * @ignore
 */
class Noptin_Form_Asset_Manager {

	/**
	 * @var bool Flag to determine whether scripts should be enqueued.
	 */
	protected $load_scripts = false;

	/**
	 * @var bool Flag to determine whether legacy scripts should be enqueued.
	 */
	protected $load_legacy_scripts = false;

	/**
	 * Add hooks
	 */
	public function add_hooks() {
		add_action( 'init', array( $this, 'register_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'load_stylesheets' ) );
		add_action( 'wp_footer', array( $this, 'load_scripts' ) );
		add_action( 'before_output_noptin_form', array( $this, 'before_output_form' ) );
		add_action( 'before_output_legacy_noptin_form', array( $this, 'before_output_legacy_form' ) );
		add_action( 'script_loader_tag', array( $this, 'add_defer_attribute' ), 10, 2 );
	}

	/**
	 * Register scripts to be enqueued later.
	 */
	public function register_scripts() {

		// Scripts params.
		$params = array(
			'ajaxurl'     => admin_url( 'admin-ajax.php' ),
			'resturl'     => esc_url_raw( rest_url( 'noptin/v1/form' ) ),
			'nonce'       => wp_create_nonce( 'noptin' ),
			'cookie'      => get_noptin_option( 'subscribers_cookie' ),
			'cookie_path' => COOKIEPATH,
		);

		// JS for new forms/shortcodes/widgets/blocks.
		wp_register_script(
			'noptin-form',
			noptin()->plugin_url . 'includes/assets/js/dist/form-scripts.js',
			array(),
			filemtime( noptin()->plugin_path . 'includes/assets/js/dist/form-scripts.js' ),
			true
		);

		wp_localize_script( 'noptin-form', 'noptinParams', $params );

		// JS for legacy forms/shortcodes/widgets/blocks.
		wp_register_script(
			'noptin_front',
			noptin()->plugin_url . 'includes/assets/js/dist/frontend.js',
			array( 'jquery' ),
			filemtime( noptin()->plugin_path . 'includes/assets/js/dist/frontend.js' ),
			true
		);

		wp_localize_script( 'noptin_front', 'noptin', $params );
	}

	/**
	 * Load the various stylesheets
	 */
	public function load_stylesheets() {

		// The css used to style the frontend
		wp_enqueue_style(
			'noptin_front',
			noptin()->plugin_url . 'includes/assets/css/frontend.css',
			array(),
			filemtime( noptin()->plugin_path . 'includes/assets/css/frontend.css' )
		);

	}

	/**
	* Outputs the inline JavaScript that is used to enhance forms
	*/
	public function load_scripts() {

		// Maybe load new form scripts.
		if ( apply_filters( 'noptin_load_form_scripts', $this->load_scripts ) ) {
			wp_enqueue_script( 'noptin-form' );
		}

		// Maybe load legacy form scripts.
		if ( apply_filters( 'noptin_load_legacy_form_scripts', $this->load_legacy_scripts ) ) {
			wp_enqueue_script( 'noptin_front' );
		}

		do_action( 'noptin_load_form_scripts', $this );
	}

	/**
	 * Adds `defer` attribute to all form-related `<script>` elements so they do not block page rendering.
	 *
	 * @param string $tag
	 * @param string $handle
	 * @return string
	 */
	public function add_defer_attribute( $tag, $handle ) {
		if ( ! in_array( $handle, array( 'noptin-form' ), true ) || stripos( $tag, ' defer' ) !== false ) {
			return $tag;
		}

		return str_replace( ' src=', ' defer src=', $tag );
	}

	/**
	 * Load JavaScript files
	 *
	 */
	public function before_output_form() {
		$this->load_scripts = true;
	}

	/**
	 * Load JavaScript files
	 *
	 */
	public function before_output_legacy_form() {
		$this->load_legacy_scripts = true;
	}

}
