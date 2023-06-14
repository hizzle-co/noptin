<?php

defined( 'ABSPATH' ) || exit;

class Noptin_Tools {

	// Class constructor.
	public static function add_hooks() {

		add_action( 'noptin_before_admin_tools', array( __CLASS__, 'display_opening_wrap' ) );
		add_action( 'noptin_after_admin_tools', array( __CLASS__, 'display_closing_wrap' ) );
		add_action( 'noptin_admin_tools', array( __CLASS__, 'list_tools' ) );
		add_action( 'noptin_admin_tool_debug_log', array( __CLASS__, 'display_debug_log' ) );
		add_action( 'noptin_admin_tool_custom_content', array( __CLASS__, 'display_custom_content' ) );
		add_action( 'noptin_admin_tool_system_info', array( __CLASS__, 'display_system_info' ) );

	}

	// Renders the settings page.
	public static function output() {

		self::add_hooks();

		/**
		 * Runs before displaying the tools page.
		 *
		 * @since 1.2.3
		 */
		do_action( 'noptin_before_admin_tools' );

		$tool = empty( $_GET['tool'] ) ? '' : noptin_clean( $_GET['tool'] );

		if ( ! empty( $tool ) && has_action( "noptin_admin_tool_$tool" ) ) {

			/**
			 * Runs when displaying a specific tool's page.
			 *
			 * @since 1.2.3
			 */
			do_action( "noptin_admin_tool_$tool" );

		} else {

			/**
			 * Runs when displaying a list of all available tools.
			 *
			 * @since 1.2.3
			 */
			do_action( 'noptin_admin_tools' );
		}

		/**
		 * Runs after displaying the tools page.
		 *
		 * @since 1.2.3
		 */
		do_action( 'noptin_after_admin_tools' );

	}

	/**
	 * A list of available tools for use in the system tools section.
	 *
	 * @return array
	 * @since 1.2.3
	 */
	public static function get_tools() {

		$tools = array(

			'debug_log'      => array(
				'name'   => __( 'Debug Log', 'newsletter-optin-box' ),
				'button' => __( 'View', 'newsletter-optin-box' ),
				'desc'   => __( 'View a list of notices and errors logged by Noptin.', 'newsletter-optin-box' ),
			),

			'custom_content' => array(
				'name'   => __( 'Custom Content', 'newsletter-optin-box' ),
				'button' => __( 'View', 'newsletter-optin-box' ),
				'desc'   => __( 'View a list of available post types and taxonomies.', 'newsletter-optin-box' ),
			),

			'reset_noptin'       => array(
				'name'    => __( 'Reset Noptin', 'newsletter-optin-box' ),
				'button'  => __( 'Reset', 'newsletter-optin-box' ),
				'desc'    => __( 'Deletes subscribers, campaigns, forms, settings then re-installs Noptin', 'newsletter-optin-box' ),
				'url'     => wp_nonce_url( add_query_arg( 'noptin_admin_action', 'noptin_admin_reset_data' ), 'noptin-reset-data' ),
				'confirm' => __( 'Are you sure you want to reset all your data?', 'newsletter-optin-box' ),
			),
		);

		/**
		 * Filters Noptin admin tools.
		 *
		 * @param array $tools An array of admin tools.
		 * @since 1.2.3
		 */
		return apply_filters( 'get_noptin_admin_tools', $tools );
	}

	/**
	 * Displays the opening wrapper of the tools page.
	 *
	 * @return void
	 * @since 1.2.3
	 */
	public static function display_opening_wrap() {
		echo '<div class="wrap noptin-tools" id="noptin-tools-page"><h1 style="margin-bottom: 20px;">' . esc_html( get_admin_page_title() ) . '</h1>';
	}

	/**
	 * Displays the closing wrapper of the tools page.
	 *
	 * @return void
	 * @since 1.2.3
	 */
	public static function display_closing_wrap() {

		if ( ! empty( $_GET['tool'] ) && 'delete_subscribers' !== $_GET['tool'] ) {

			printf(
				'<p class="description"><a href="%s">%s</a></p>',
				esc_url( admin_url( 'admin.php?page=noptin-tools' ) ),
				esc_html__( 'Go back to tools page', 'newsletter-optin-box' )
			);

		}

		echo '</div>';
	}

	/**
	 * Displays a list of available tools.
	 *
	 * @return void
	 * @since 1.2.3
	 */
	public static function list_tools() {

		$tools = self::get_tools();
		get_noptin_template( 'admin-tools.php', compact( 'tools' ) );

	}

	/**
	 * Displays the debug log.
	 *
	 * @return void
	 * @since 1.2.3
	 */
	public static function display_debug_log() {

		$debug_log = get_logged_noptin_messages();
		get_noptin_template( 'debug-log.php', compact( 'debug_log' ) );

	}

	/**
	 * Displays available custom content.
	 *
	 * @return void
	 * @since 1.10.1
	 */
	public static function display_custom_content() {
		include plugin_dir_path( __FILE__ ) . 'views/custom-content.php';
	}
}
