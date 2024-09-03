<?php
/**
 * Forms API: Popups.
 *
 * Displays popups on the front page.
 *
 * @since             1.6.2
 * @package           Noptin
 */

namespace Hizzle\Noptin\Forms;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Displays popups on the front page.
 *
 * @since 1.6.2
 */
class Popups {

	/**
	 * Cached popups.
	 *
	 * @var \Noptin_Form[]|\Noptin_Form_Legacy[]
	 */
	private static $popups = null;

	/**
	 * Constructor.
	 */
	public static function init() {
		add_action( 'wp_footer', array( __CLASS__, 'display_popups' ), 5 );
		add_action( 'wp_footer', array( __CLASS__, 'enqueue_scripts' ), 6 );
		add_filter( 'noptin_load_form_scripts', array( __CLASS__, 'maybe_load_form_scripts' ) );
		add_action( 'save_post', array( __CLASS__, 'empty_cache' ) );
	}

	/**
	 * Enqueues scripts.
	 */
	public static function enqueue_scripts() {

		if ( empty( self::get_popups() ) && empty( $GLOBALS['noptin_showing_popup'] ) ) {
			return;
		}

		$config = include plugin_dir_path( __FILE__ ) . '/assets/js/popups.asset.php';

		wp_enqueue_style(
			'noptin-popups',
			plugin_dir_url( __FILE__ ) . 'assets/css/style-popups.css',
			array(),
			$config['version']
		);

		wp_enqueue_script(
			'noptin-popups',
			plugin_dir_url( __FILE__ ) . 'assets/js/popups.js',
			$config['dependencies'],
			$config['version'],
			true
		);
	}

	/**
	 * Determines whether to load form scripts based on the presence of popups.
	 *
	 * @param bool $load Whether to load form scripts.
	 * @return bool
	 */
	public static function maybe_load_form_scripts( $load ) {
		if ( ! empty( self::get_popups() ) || ! empty( $GLOBALS['noptin_showing_popup'] ) ) {
			self::enqueue_scripts();
			return true;
		}

		return $load;
	}

	/**
	 * Fetches and displays popups on the site.
	 */
	public static function display_popups() {
		$popups = self::get_popups();

		if ( empty( $popups ) ) {
			return;
		}

		do_action( 'before_noptin_popup_display' );
		foreach ( $popups as $popup ) {
			show_noptin_form( $popup->id );
		}
		do_action( 'after_noptin_popup_display' );
	}

	/**
	 * Fetches popups to be displayed.
	 *
	 * @return \Noptin_Form[]|\Noptin_Form_Legacy[] Array of popup data.
	 */
	private static function get_popups() {
		// Maybe abort early.
		if ( is_admin() || is_noptin_actions_page() || noptin_is_preview() || is_preview() ) {
			return array();
		}

		if ( null === self::$popups ) {
			// Fetch forms.
			$forms = wp_cache_get( 'noptin_popup_forms', 'noptin' );

			if ( false === $forms ) {

				$forms = get_posts(
					array(
						'numberposts' => -1,
						'fields'      => 'ids',
						'post_type'   => 'noptin-form',
						'post_status' => 'publish',
						'meta_query'  => array(
							'relation' => 'OR',
							array(
								'key'     => 'form_settings',
								'compare' => 'EXISTS',
							),
							array(
								'key'     => '_noptin_optin_type',
								'value'   => array( 'popup', 'slide_in' ),
								'compare' => 'IN',
							),
						),
					)
				);

				wp_cache_set( 'noptin_popup_forms', $forms, 'noptin', DAY_IN_SECONDS );
			}

			self::$popups = array();

			foreach ( $forms as $popup ) {
				$form = noptin_get_optin_form( $popup );

				// Can it be displayed?
				if ( $form->is_slide_in() || $form->is_popup() ) {
					self::$popups[] = $form;
				}
			}
		}

		return self::$popups;
	}

	/**
	 * Empties the cache when a form is saved.
	 *
	 * @param int $post_id The post ID.
	 */
	public static function empty_cache( $post_id ) {
		if ( 'noptin-form' === get_post_type( $post_id ) ) {
			wp_cache_delete( 'noptin_popup_forms', 'noptin' );
		}
	}
}
