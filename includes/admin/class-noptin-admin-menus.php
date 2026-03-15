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
		add_action( 'admin_menu', array( $this, 'documentation_menu' ), 80 );
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
	 * Add help menu item.
	 */
	public function documentation_menu() {
		$url = noptin()->white_label->get( 'support_url', noptin_get_guide_url( 'Admin Menu' ) );

		// noptin_show_documentation_link is here for backward compatibility. It can be removed in a future release.
		if ( 'none' !== $url && apply_filters( 'noptin_show_documentation_link', true ) && apply_filters( 'noptin_show_documentation_page', true ) ) {
			add_submenu_page(
				'noptin',
				esc_html__( 'Need Help?', 'newsletter-optin-box' ),
				esc_html__( 'Need Help?', 'newsletter-optin-box' ),
				get_noptin_capability(),
				$url,
				''
			);
		}
	}
}
