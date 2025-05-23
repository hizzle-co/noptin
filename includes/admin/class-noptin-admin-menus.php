<?php

/**
 * Admin menus handler
 */

defined( 'ABSPATH' ) || exit;

/**
 * Admin menus class.
 */
class Noptin_Admin_Menus {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {

		// Add menus.
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 10 );
		add_action( 'admin_menu', array( $this, 'menu_highlight' ), 15 );
		add_action( 'admin_menu', array( $this, 'forms_menu' ), 30 );
		add_action( 'admin_menu', array( $this, 'documentation_menu' ), 80 );

		add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 10, 3 );
	}

	/**
	 * Add menu items.
	 */
	public function admin_menu() {

		// The main admin page.
		add_menu_page(
			noptin()->white_label->get( 'name', 'Noptin' ),
			noptin()->white_label->get( 'name', 'Noptin' ),
			get_noptin_capability(),
			'noptin',
			null,
			noptin()->white_label->get( 'icon', 'dashicons-forms' ),
			'23.81204129341231'
		);

		\Hizzle\WordPress\ScriptManager::add_namespace(
			'noptin',
			noptin()->white_label->get_details()
		);
	}

	/**
	 * Add forms menu item.
	 */
	public function forms_menu() {
		add_submenu_page(
			'noptin',
			esc_html__( 'Subscription Forms', 'newsletter-optin-box' ),
			esc_html__( 'Subscription Forms', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'edit.php?post_type=noptin-form'
		);

		// Backwards compatibility.
		do_action( 'noptin_after_register_menus', noptin()->admin );
	}

	/**
	 * Add help menu item.
	 */
	public function documentation_menu() {
		if ( apply_filters( 'noptin_show_documentation_link', true ) ) {

			add_submenu_page(
				'noptin',
				esc_html__( 'Documentation', 'newsletter-optin-box' ),
				esc_html__( 'Documentation', 'newsletter-optin-box' ),
				get_noptin_capability(),
				noptin_get_guide_url( 'Admin Menu' ),
				''
			);
		}
	}

	/**
	 * Highlights the correct top level admin menu item for post type add screens.
	 */
	public function menu_highlight() {
		global $parent_file, $post_type;

		if ( 'noptin-form' === $post_type ) {
			$parent_file  = 'noptin'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	/**
	 * Validate screen options on update.
	 *
	 * @param bool|int $status Screen option value. Default false to skip.
	 * @param string   $option The option name.
	 * @param int      $value  The number of rows to use.
	 */
	public function set_screen_option( $status, $option, $value ) {
		if ( in_array( $option, array( 'hpay_orders_per_page', 'hpay_discounts_per_page' ), true ) ) {
			return $value;
		}

		return $status;
	}

}
