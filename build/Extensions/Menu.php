<?php
/**
 * Extensions API: Extensions Admin.
 *
 * Contains the main admin class for Noptin Extensions
 *
 * @since   3.2.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Extensions;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main admin class for Noptin Extensions.
 *
 * @since 3.2.0
 * @internal
 * @ignore
 */
class Menu {

	/**
	 * @var string hook suffix
	 */
	public static $hook_suffix;

	/**
	 * Inits the main emails class.
	 *
	 */
	public static function init() {

		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 70 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'wp_ajax_noptin_save_agency_settings', array( __CLASS__, 'save_agency_settings' ) );
	}

	/**
	 * Saves the agency settings via AJAX.
	 */
	public static function save_agency_settings() {
		check_ajax_referer( 'noptin_save_agency_settings' );

		if ( ! current_user_can_manage_noptin() ) {
			wp_send_json_error( array( 'message' => 'You are not authorized to do this.' ), 403 );
		}

		$submitted = isset( $_POST['settings'] ) ? json_decode( wp_unslash( $_POST['settings'] ), true ) : null;
		$settings  = get_noptin_options();

		if ( ! is_array( $submitted ) ) {
			wp_send_json_error( array( 'message' => 'Invalid agency settings.' ), 400 );
		}

		$boolean_fields = array(
			'visibility' => array( 'hide_license', 'hide_workspace', 'split_email_menu' ),
			'templates'  => array( 'hide_cloud', 'hide_custom' ),
		);

		foreach ( $boolean_fields as $section => $fields ) {
			foreach ( $fields as $field ) {
				if ( isset( $submitted[ $section ] ) && array_key_exists( $field, $submitted[ $section ] ) ) {
					$settings[ $section ][ $field ] = rest_sanitize_boolean( $submitted[ $section ][ $field ] );
				}
			}
		}

		$text_fields = array( 'name', 'icon', 'logo', 'support_url' );
		foreach ( $text_fields as $field ) {
			if ( isset( $submitted['white_label'] ) && array_key_exists( $field, $submitted['white_label'] ) ) {
				$value = is_scalar( $submitted['white_label'][ $field ] ) ? (string) $submitted['white_label'][ $field ] : '';

				$settings['white_label'][ $field ] = in_array( $field, array( 'logo', 'support_url' ), true )
					? ( 'none' === $value ? 'none' : esc_url_raw( $value ) )
					: sanitize_text_field( $value );
			}
		}

		$settings['visibility']['last_edited_by'] = get_current_user_id();
		update_option( 'noptin_options', $settings );

		wp_send_json_success( array( 'noptin_options' => $settings ) );
	}

	/**
	 * Settings menu.
	 */
	public static function register_menu() {

		if ( ! apply_filters( 'noptin_show_addons_page', true ) ) {
			return;
		}

		self::$hook_suffix = add_submenu_page(
			'noptin',
			esc_html__( 'Add-ons', 'newsletter-optin-box' ),
			esc_html__( 'Add-ons', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'noptin-addons',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Displays the admin page.
	 */
	public static function render_admin_page() {

		// Check permission.
		if ( ! current_user_can_manage_noptin() ) {
			wp_die( 'You do not have sufficient permissions to access this page.' );
		}

		// Include the settings view.
		require_once plugin_dir_path( __FILE__ ) . 'view.php';
	}

	/**
	 * Enqueues scripts and styles.
	 *
	 * @param string $hook The current admin page.
	 */
	public static function enqueue_scripts( $hook ) {

		// Abort if not on the email campaigns page.
		if ( self::$hook_suffix !== $hook ) {
			return;
		}

		add_filter( 'admin_body_class', 'noptin_add_block_editor_body_class' );

		$config = include plugin_dir_path( __FILE__ ) . 'assets/js/workspace.asset.php';
		wp_enqueue_script(
			'noptin-workspace',
			plugins_url( 'assets/js/workspace.js', __FILE__ ),
			$config['dependencies'],
			$config['version'],
			true
		);

		// Localize the script.
		$catalog = Catalog::config();
		$data    = array(
			'config'       => $catalog,
			'agencyNonce'  => wp_create_nonce( 'noptin_save_agency_settings' ),
			'ui'           => array(
				'brand' => noptin()->white_label->get_details(),
			),
			'options'      => get_option( 'noptin_options', array() ),
			'user_id'      => get_current_user_id(),
		);

		// Localize the script.
		wp_add_inline_script(
			'noptin-workspace',
			'window.noptinWorkspace = ' . wp_json_encode( $data ) . ';',
			'before'
		);

		wp_set_script_translations( 'noptin-workspace', 'newsletter-optin-box', noptin()->plugin_path . 'languages' );

		// Load the css.
		wp_enqueue_style(
			'noptin-workspace',
			plugins_url( 'assets/css/style-workspace.css', __FILE__ ),
			array( 'wp-components' ),
			$config['version']
		);
	}
}
