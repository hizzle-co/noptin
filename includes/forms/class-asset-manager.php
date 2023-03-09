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
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_gutenberg_assets' ) );
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
			'connect_err' => __( 'Could not establish a connection to the server.', 'newsletter-optin-box' ),
			'cookie_path' => COOKIEPATH,
		);
		$params = apply_filters( 'noptin_form_scripts_params', $params );

		// JS for new forms/shortcodes/widgets/blocks.
		wp_register_script(
			'noptin-form',
			noptin()->plugin_url . 'includes/assets/js/dist/form-scripts.js',
			array(),
			filemtime( noptin()->plugin_path . 'includes/assets/js/dist/form-scripts.js' ),
			true
		);

		wp_localize_script( 'noptin-form', 'noptinParams', $params );

		wp_register_style(
			'noptin_form_styles',
			noptin()->plugin_url . 'includes/assets/css/form-styles.css',
			array(),
			filemtime( noptin()->plugin_path . 'includes/assets/css/form-styles.css' )
		);

		// JS for legacy forms/shortcodes/widgets/blocks.
		wp_register_script(
			'noptin_front',
			noptin()->plugin_url . 'includes/assets/js/dist/legacy-forms.js',
			array(),
			filemtime( noptin()->plugin_path . 'includes/assets/js/dist/legacy-forms.js' ),
			true
		);

		wp_register_script(
			'noptin-legacy-popups',
			noptin()->plugin_url . 'includes/assets/js/dist/legacy-popups.js',
			array( 'jquery', 'noptin_front' ),
			filemtime( noptin()->plugin_path . 'includes/assets/js/dist/legacy-popups.js' ),
			true
		);

		wp_localize_script( 'noptin_front', 'noptin', $params );

		// The css used to style the block in the editor backend
		wp_register_style(
			'noptin_front',
			noptin()->plugin_url . 'includes/assets/css/frontend.css',
			array(),
			filemtime( noptin()->plugin_path . 'includes/assets/css/frontend.css' )
		);

	}

	/**
	 * Load the various stylesheets
	 */
	public function load_stylesheets() {

		// The css used to style the frontend
		if ( is_using_new_noptin_forms() ) {
			wp_enqueue_style( 'noptin_form_styles' );
		} else {
			wp_enqueue_style( 'noptin_front' );
		}

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
		if ( apply_filters( 'noptin_load_legacy_form_scripts', ( $this->load_legacy_scripts || ! is_using_new_noptin_forms() ) ) ) {
			wp_enqueue_script( 'noptin_front' );

			if ( is_using_new_noptin_forms() ) {
				wp_enqueue_style( 'noptin_front' );
			}
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
		if ( ! in_array( $handle, array( 'noptin-form', 'noptin-popups' ), true ) || stripos( $tag, ' defer' ) !== false ) {
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

	/**
	 * Load gutenberg files
	 *
	 */
	public function enqueue_gutenberg_assets() {
		global $pagenow;

		wp_enqueue_style( 'noptin_front' );

		if ( is_using_new_noptin_forms() ) {

			wp_enqueue_script(
				'noptin-form-block',
				noptin()->plugin_url . 'includes/assets/js/dist/blocks-new.js',
				array( 'wp-blocks', 'wp-i18n', 'wp-element', 'underscore', 'wp-components' ),
				filemtime( noptin()->plugin_path . 'includes/assets/js/dist/blocks-new.js' ),
				true
			);

		} elseif ( 'widgets.php' !== $pagenow ) {

			wp_enqueue_script(
				'noptin-form-block',
				noptin()->plugin_url . 'includes/assets/js/dist/blocks.js',
				array( 'wp-blocks', 'wp-editor', 'wp-i18n', 'wp-element', 'underscore', 'wp-components' ),
				filemtime( noptin()->plugin_path . 'includes/assets/js/dist/blocks.js' ),
				true
			);

		}

		$forms = get_posts(
			array(
				'numberposts' => -1,
				'post_status' => array( 'publish' ),
				'post_type'   => 'noptin-form',
			)
		);

		$data = array(
			array(
				'label' => __( 'Default Form', 'newsletter-optin-box' ),
				'value' => 0,
			),
			array(
				'label' => __( 'Single-line / Horizontal Form', 'newsletter-optin-box' ),
				'value' => -1,
			),
		);

		foreach ( $forms as $form ) {
			$data[] = array(
				'label' => $form->post_title,
				'value' => $form->ID,
			);
		}
		wp_localize_script( 'noptin-form-block', 'noptin_forms', $data );
	}

}
