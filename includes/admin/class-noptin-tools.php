<?php

defined( 'ABSPATH' ) || exit;

class Noptin_Tools {

	// Class constructor.
	public static function add_hooks() {

		add_action( 'admin_menu', array( __CLASS__, 'tools_menu' ), 60 );
		add_action( 'noptin_before_admin_tools', array( __CLASS__, 'display_opening_wrap' ) );
		add_action( 'noptin_after_admin_tools', array( __CLASS__, 'display_closing_wrap' ) );
		add_action( 'noptin_admin_tools', array( __CLASS__, 'list_tools' ) );
		add_action( 'noptin_admin_tool_debug_log', array( __CLASS__, 'display_debug_log' ) );
		add_action( 'noptin_admin_tool_new_post_notification', array( __CLASS__, 'display_new_post_notification_trigger' ) );
		add_action( 'noptin_trigger_new_post_notification', array( __CLASS__, 'trigger_new_post_notification' ) );
	}

	/**
	 * Add tools menu item.
	 */
	public static function tools_menu() {
		add_submenu_page(
			'noptin',
			esc_html( self::get_admin_title() ),
			esc_html__( 'Tools', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'noptin-tools',
			'Noptin_Tools::output'
		);
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

		$tool = self::current_tool();

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

			'debug_log'             => array(
				'name'   => __( 'Debug Log', 'newsletter-optin-box' ),
				'button' => __( 'View', 'newsletter-optin-box' ),
				'desc'   => __( 'View a list of notices and errors logged by Noptin.', 'newsletter-optin-box' ),
			),

			'new_post_notification' => array(
				'name'   => __( 'New Post Automation', 'newsletter-optin-box' ),
				'button' => __( 'Trigger', 'newsletter-optin-box' ),
				'desc'   => __( 'Trigger a new post automation.', 'newsletter-optin-box' ),
			),

			'reset_noptin'          => array(
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
	 * Retrieves current admin page title.
	 */
	public static function get_admin_title() {

		$current_tool = self::current_tool();

		if ( ! empty( $current_tool ) ) {
			$tools = self::get_tools();

			if ( isset( $tools[ $current_tool ] ) ) {
				return $tools[ $current_tool ]['name'];
			}
		}

		return __( 'Tools', 'newsletter-optin-box' );
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

		$current_tool = self::current_tool();
		if ( ! empty( $current_tool ) ) {
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
	 * Returns the current tool.
	 *
	 * @return void
	 * @since 1.2.3
	 */
	public static function current_tool() {

		// Verify nonce.
		if ( ! empty( $_GET['tool'] ) ) {
			check_admin_referer( 'noptin_tool', 'noptin_tool_nonce' );
		}

		$tool = empty( $_GET['tool'] ) ? '' : sanitize_text_field( wp_unslash( $_GET['tool'] ) );

		return $tool;
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
	 * Displays a form to trigger a new post notification.
	 *
	 * @return void
	 * @since 1.10.1
	 */
	public static function display_new_post_notification_trigger() {
		?>
		<form method="post">
			<input type="hidden" name="noptin_admin_action" value="noptin_trigger_new_post_notification">
			<?php wp_nonce_field( 'noptin_trigger_new_post_notification' ); ?>
			<p>
				<label for="noptin_post_id"><?php esc_html_e( 'Post ID', 'newsletter-optin-box' ); ?></label><br />
				<input type="number" name="noptin_post_id" id="noptin_post_id" class="regular-text" required>
			</p>
			<p>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Trigger', 'newsletter-optin-box' ); ?></button>
			</p>
		</form>
		<?php
	}

	/**
	 * Triggers a new post notification.
	 *
	 * @return void
	 * @since 1.10.1
	 */
	public static function trigger_new_post_notification() {

		if ( ! current_user_can( get_noptin_capability() ) ) {
			wp_die( 'You do not have permission to perform this action.' );
		}

		if ( ! isset( $_POST['noptin_post_id'] ) || ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'noptin_trigger_new_post_notification' ) ) {
			wp_die( 'Invalid request.' );
		}

		$post = get_post( absint( $_POST['noptin_post_id'] ) );

		if ( ! $post ) {
			wp_die( 'Invalid post ID.' );
		}

		delete_post_meta( $post->ID, 'noptin_sent_notification_campaign' );

		do_action( 'noptin_force_trigger_new_post_notification', $post );

		noptin()->admin->show_success( 'New post automations triggered successfully.' );
	}
}
