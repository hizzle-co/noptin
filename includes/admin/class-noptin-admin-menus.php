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
		add_action( 'admin_menu', array( $this, 'dashboard_menu' ), 20 );
		add_action( 'admin_menu', array( $this, 'forms_menu' ), 30 );
		add_action( 'admin_menu', array( $this, 'documentation_menu' ), 80 );

		// Welcome wizzard.
		add_action( 'admin_menu', array( $this, 'welcome_wizard_menu' ), 5 );
		add_action( 'admin_head', array( $this, 'hide_welcome_wizard_menu' ), 10 );

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
	}

	/**
	 * Add dashboard menu item.
	 */
	public function dashboard_menu() {
		add_submenu_page(
			'noptin',
			__( 'Noptin Dashboard', 'newsletter-optin-box' ),
			__( 'Dashboard', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'noptin',
			array( $this, 'render_dashboard_page' )
		);
	}

	/**
	 * Displays the dashboard page.
	 */
	public function render_dashboard_page() {
		if ( ! current_user_can( get_noptin_capability() ) ) {
			return;
		}

		/**
		 * Runs before displaying the main menu page.
		 *
		 */
		do_action( 'noptin_before_admin_main_page' );

		if ( is_using_new_noptin_forms() ) {
			$all_forms = noptin_count_optin_forms();
		} else {
			$popups   = noptin_count_optin_forms( 'popup' );
			$inpost   = noptin_count_optin_forms( 'inpost' );
			$widget   = noptin_count_optin_forms( 'sidebar' );
			$slide_in = noptin_count_optin_forms( 'slide_in' );
		}

		include plugin_dir_path( __FILE__ ) . 'welcome.php';

		/**
		 * Runs after displaying the main menu page.
		 *
		 */
		do_action( 'noptin_after_admin_main_page' );
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
				noptin_get_upsell_url( 'guide/', 'documentation', 'link' ),
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
	 * Add welcome wizzard menu item.
	 */
	public function welcome_wizard_menu() {
		$hook_suffix = add_dashboard_page(
			esc_html__( 'Noptin Settings Welcome Wizzard', 'newsletter-optin-box' ),
			esc_html__( 'Welcome Wizzard', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'noptin-welcome-wizzard',
			array( $this, 'display_welcome_wizard_page' )
		);

		Noptin_Scripts::add_admin_script( $hook_suffix, 'welcome-wizard' );
	}

	/**
	 * Displays the welcome wizzard page.
	 */
	public function display_welcome_wizard_page() {
		include plugin_dir_path( __FILE__ ) . 'views/welcome-wizard.php';
	}

	/**
	 * Hide the welcome wizzard menu item.
	 */
	public function hide_welcome_wizard_menu() {
		remove_submenu_page( 'index.php', 'noptin-welcome-wizzard' );
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
