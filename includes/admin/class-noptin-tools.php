<?php

defined( 'ABSPATH' ) || exit;

class Noptin_Tools {

	// Class constructor.
	public function __construct() {

		add_action( 'noptin_before_admin_tools', array( $this, 'display_opening_wrap' ) );
		add_action( 'noptin_after_admin_tools', array( $this, 'display_closing_wrap' ) );
		add_action( 'noptin_admin_tools', array( $this, 'list_tools' ) );
		add_action( 'noptin_admin_tool_debug_log', array( $this, 'display_debug_log' ) );
		add_action( 'noptin_admin_tool_system_info', array( $this, 'display_system_info' ) );

	}

	// Renders the settings page.
	public static function output() {

		/**
		 * Runs before displaying the tools page.
		 * 
		 * @since 1.2.3
		 */
		do_action( 'noptin_before_admin_tools' );

		$tool = empty( $_GET['tool'] ) ? '' : noptin_clean( $_GET['tool'] );

		if( ! empty( $tool ) && has_action( "noptin_admin_tool_$tool" ) ) {

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
	public function get_tools() {

		$tools = array(

			'debug_log'   => array(
				'name'    => __( 'Debug Log', 'newsletter-optin-box' ),
				'button'  => __( 'View', 'newsletter-optin-box' ),
				'desc'    => __( 'View a list of notices and errors logged by Noptin.', 'newsletter-optin-box' ),
			),

			'system_info' => array(
				'name'    => __( 'System Information', 'newsletter-optin-box' ),
				'button'  => __( 'View', 'newsletter-optin-box' ),
				'desc'    => __( 'View your system information and Noptin configuration.', 'newsletter-optin-box' ),
			),

			'sync_users'  => array(
				'name'    => __( 'Subscribe Users', 'newsletter-optin-box' ),
				'button'  => __( 'Subscribe', 'newsletter-optin-box' ),
				'desc'    => __( 'Subscribe your WordPress users to the newsletter.', 'newsletter-optin-box' ),
			),

			'sync_subscribers'  => array(
				'name'    => __( 'Register Subscribers', 'newsletter-optin-box' ),
				'button'  => __( 'Register', 'newsletter-optin-box' ),
				'desc'    => __( 'Register your newsletter subscribers as WordPress users.', 'newsletter-optin-box' ),
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
	public function display_opening_wrap() {
		echo '<div class="wrap noptin-tools" id="noptin-tools-page"><h1 style="margin-bottom: 20px;">' . esc_html( get_admin_page_title() ) . '</h1>';
	}

	/**
	 * Displays the closing wrapper of the tools page.
	 *
	 * @return void
	 * @since 1.2.3
	 */
	public function display_closing_wrap() {
		
		if ( ! empty( $_GET['tool'] ) && 'sync_users' != $_GET['tool'] && 'sync_subscribers' != $_GET['tool'] ) {
			$tools_page = esc_url( admin_url( 'admin.php?page=noptin-tools' ) );
			$text       = __( 'Go back to tools page', 'noptin' );
			echo "<p class='description'><a href='$tools_page'>$text</a></p>";
		}
		echo '</div>';
	}

	/**
	 * Displays a list of available tools.
	 *
	 * @return void
	 * @since 1.2.3
	 */
	public function list_tools() {

		$tools = $this->get_tools();
		get_noptin_template( 'admin-tools.php', compact( 'tools' ) );

	}

	/**
	 * Displays the debug log.
	 *
	 * @return void
	 * @since 1.2.3
	 */
	public function display_debug_log() {

		$debug_log = get_logged_noptin_messages();
		get_noptin_template( 'debug-log.php', compact( 'debug_log' ) );

	}

	/**
	 * Displays the system info.
	 *
	 * @return void
	 * @since 1.2.3
	 */
	public function display_system_info() {

		$system_info = new Noptin_System_Info();
		$info 		 = $system_info->info;
		$text 		 = $system_info->get_info_as_text();
		get_noptin_template( 'system-info.php', compact( 'info', 'text' ) );

	}

}
