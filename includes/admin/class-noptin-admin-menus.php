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
		add_action( 'admin_menu', array( $this, 'subscribers_menu' ), 33 );
		add_action( 'admin_menu', array( $this, 'email_campaigns_menu' ), 35 );
		add_action( 'admin_menu', array( $this, 'automation_rules_menu' ), 40 );
		add_action( 'admin_menu', array( $this, 'settings_menu' ), 50 );
		add_action( 'admin_menu', array( $this, 'tools_menu' ), 60 );
		add_action( 'admin_menu', array( $this, 'extensions_menu' ), 70 );
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
			noptin()->white_label->name,
			noptin()->white_label->name,
			get_noptin_capability(),
			'noptin',
			null,
			noptin()->white_label->icon,
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
	 * Automation Rules.
	 */
	public function automation_rules_menu() {

		if ( isset( $_GET['noptin_create_automation_rule'] ) ) {
			$title  = __( 'Add New Automation Rule', 'newsletter-optin-box' );
			$script = 'create-automation-rule';
			$cb     = array( $this, 'render_create_automation_rule_page' );
		} elseif ( isset( $_GET['noptin_edit_automation_rule'] ) ) {
			$title  = __( 'Edit Automation Rule', 'newsletter-optin-box' );
			$script = 'edit-automation-rule';
			$cb     = array( $this, 'render_edit_automation_rule_page' );
		} else {
			$title  = __( 'Automation Rules', 'newsletter-optin-box' );
			$script = 'view-automation-rules';
			$cb     = array( $this, 'render_automation_rules_page' );
		}

		$hook_suffix = add_submenu_page(
			'noptin',
			apply_filters( 'noptin_admin_automation_rules_page_title', $title ),
			esc_html__( 'Automation Rules', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'noptin-automation-rules',
			$cb
		);

		if ( ! empty( $script ) ) {
			Noptin_Scripts::add_admin_script( $hook_suffix, $script );
		}
	}

	/**
	 * Subscribers.
	 */
	public function subscribers_menu() {

		$hook_suffix = add_submenu_page(
			'noptin',
			esc_html__( 'Email Subscribers', 'newsletter-optin-box' ),
			esc_html__( 'Email Subscribers', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'noptin-subscribers',
			array( $this, 'render_subscribers_page' )
		);

		Noptin_Scripts::add_admin_script( $hook_suffix, 'table' );
	}

	/**
	 * Email campaigns menu.
	 */
	public function email_campaigns_menu() {

		$section     = noptin()->emails->admin->get_current_section();
		$tab         = noptin()->emails->admin->get_current_tab();
		$hook_suffix = add_submenu_page(
			'noptin',
			noptin()->emails->admin->get_current_admin_page_title(),
			esc_html__( 'Email Campaigns', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'noptin-email-campaigns',
			array( noptin()->emails->admin, 'render_admin_page' )
		);

		if ( 'edit_campaign' === $section ) {
			Noptin_Scripts::add_admin_script( $hook_suffix, 'edit-email-campaign' );
		}

		if ( 'edit_campaign' !== $section && 'automations' === $tab ) {
			Noptin_Scripts::add_admin_script( $hook_suffix, 'create-automated-email' );
		}
	}

	/**
	 * Displays the subscribers page.
	 */
	public function render_subscribers_page() {
		if ( current_user_can( get_noptin_capability() ) ) {
			include plugin_dir_path( __FILE__ ) . 'views/view-subscribers.php';
		}
	}

	/**
	 * Displays the create automation rule page.
	 */
	public function render_create_automation_rule_page() {
		if ( current_user_can( get_noptin_capability() ) ) {
			include plugin_dir_path( __FILE__ ) . 'views/automation-rules/create.php';
		}
	}

	/**
	 * Displays the edit automation rule page.
	 */
	public function render_edit_automation_rule_page() {
		if ( current_user_can( get_noptin_capability() ) ) {
			include plugin_dir_path( __FILE__ ) . 'views/automation-rules/edit.php';
		}
	}

	/**
	 * Displays the automation rules page.
	 */
	public function render_automation_rules_page() {
		if ( current_user_can( get_noptin_capability() ) ) {
			include plugin_dir_path( __FILE__ ) . 'views/automation-rules/list.php';
		}
	}

	/**
	 * Registers the settings menu.
	 */
	public function settings_menu() {
		$hook_suffix = add_submenu_page(
			'noptin',
			esc_html__( 'Settings', 'newsletter-optin-box' ),
			esc_html__( 'Settings', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'noptin-settings',
			'Noptin_Settings::output'
		);

		Noptin_Scripts::add_admin_script( $hook_suffix, 'settings' );
	}

	/**
	 * Add tools menu item.
	 */
	public function tools_menu() {
		add_submenu_page(
			'noptin',
			esc_html( apply_filters( 'noptin_admin_tools_page_title', __( 'Noptin Tools', 'newsletter-optin-box' ) ) ),
			esc_html__( 'Tools', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'noptin-tools',
			'Noptin_Tools::output'
		);
	}

	/**
	 * Add extensions menu item.
	 */
	public function extensions_menu() {
		if ( apply_filters( 'noptin_show_addons_page', true ) ) {

			$count_html = Noptin_COM_Updater::get_updates_count_html();

			/* translators: %s: extensions count */
			$menu_title = sprintf( __( 'Extensions %s', 'newsletter-optin-box' ), $count_html );

			add_submenu_page(
				'noptin',
				esc_html__( 'Noptin Extensions', 'newsletter-optin-box' ),
				$menu_title,
				get_noptin_capability(),
				'noptin-addons',
				array( 'Noptin_COM_Helper', 'output_extensions_page' )
			);

		}
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
