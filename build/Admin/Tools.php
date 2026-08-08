<?php

namespace Hizzle\Noptin\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Admin tools controller.
 */
class Tools {

	/**
	 * Tools page hook suffix.
	 *
	 * @var string
	 */
	public static $hook_suffix = '';

	/**
	 * Registers hooks.
	 */
	public static function add_hooks() {
		add_action( 'admin_menu', array( __CLASS__, 'tools_menu' ), 60 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_ajax_noptin_run_tool', array( __CLASS__, 'maybe_do_ajax_action' ) );
		add_action( 'noptin_reset_data', array( __CLASS__, 'reset_data' ) );
		add_action( 'noptin_trigger_new_post_notification', array( __CLASS__, 'trigger_new_post_notification' ) );
	}

	/**
	 * Registers the tools page.
	 */
	public static function tools_menu() {
		self::$hook_suffix = add_submenu_page(
			'noptin',
			esc_html__( 'Tools', 'newsletter-optin-box' ),
			esc_html__( 'Tools', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'noptin-tools',
			array( __CLASS__, 'output' )
		);
	}

	/**
	 * Enqueues the tools interface.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_assets( $hook ) {
		if ( self::$hook_suffix !== $hook ) {
			return;
		}

		$config = include plugin_dir_path( __FILE__ ) . 'assets/js/tools.asset.php';

		wp_enqueue_script(
			'noptin-tools',
			plugin_dir_url( __FILE__ ) . 'assets/js/tools.js',
			$config['dependencies'],
			$config['version'],
			true
		);

		$data = array(
			'ajax_url'     => admin_url( 'admin-ajax.php' ),
			'brand'        => noptin()->white_label->get_details(),
			'nonce'        => wp_create_nonce( 'noptin-tools' ),
			'tools'        => array_values( self::get_tools() ),
			'current_tool' => isset( $_GET['tool'] ) ? sanitize_key( wp_unslash( $_GET['tool'] ) ) : '',
			'debug_log'    => get_logged_noptin_messages(),
			'tools_url'    => add_query_arg( 'page', 'noptin-tools', admin_url( 'admin.php' ) ),
		);

		wp_add_inline_script( 'noptin-tools', 'window.noptinTools = ' . wp_json_encode( $data ) . ';', 'before' );
		wp_set_script_translations( 'noptin-tools', 'newsletter-optin-box', noptin()->plugin_path . 'languages' );

		wp_enqueue_style(
			'noptin-tools',
			plugin_dir_url( __FILE__ ) . 'assets/css/style-tools.css',
			array( 'wp-components' ),
			$config['version']
		);
	}

	/**
	 * Renders the React mount point.
	 */
	public static function output() {
		if ( ! current_user_can( get_noptin_capability() ) ) {
			return;
		}

		echo '<div class="wrap"><div id="noptin-tools-app"><span class="spinner" style="visibility:visible;float:none"></span></div></div>';
	}

	/**
	 * Returns registered tools using the public tools schema.
	 *
	 * Link tools require a title, description, URL, and button props.
	 * Background tools require a title, description, AJAX action, and button props.
	 * Form tools additionally require form fields and submit button props.
	 *
	 * @return array
	 */
	public static function get_tools() {
		$tools = array(
			'debug_log'             => array(
				'type'        => 'link',
				'title'       => __( 'Debug Log', 'newsletter-optin-box' ),
				'description' => __( 'Review notices and errors recorded by Noptin.', 'newsletter-optin-box' ),
				'icon'        => 'editor-code',
				'url'         => add_query_arg(
                    array(
						'page' => 'noptin-tools',
						'tool' => 'debug_log',
                    ),
                    admin_url( 'admin.php' )
                ),
				'button'      => array( 'text' => __( 'View', 'newsletter-optin-box' ) ),
			),
			'new_post_notification' => array(
				'type'          => 'form',
				'title'         => __( 'New Post Automation', 'newsletter-optin-box' ),
				'description'   => __( 'Trigger new-post automations for a specific post.', 'newsletter-optin-box' ),
				'icon'          => 'megaphone',
				'ajax_action'   => 'noptin_trigger_new_post_notification',
				'button'        => array( 'text' => __( 'Choose post', 'newsletter-optin-box' ) ),
				'submit_button' => array(
					'text'    => __( 'Trigger', 'newsletter-optin-box' ),
					'variant' => 'primary',
				),
				'form_fields'   => array(
					'noptin_post_id' => array(
						'el'               => 'input',
						'type'             => 'number',
						'label'            => __( 'Post ID', 'newsletter-optin-box' ),
						'description'      => __( 'Enter the ID of the post whose automation should run.', 'newsletter-optin-box' ),
						'customAttributes' => array(
							'min'      => 1,
							'required' => true,
						),
					),
				),
			),
			'reset_noptin'          => array(
				'type'        => 'background',
				'title'       => __( 'Reset Noptin', 'newsletter-optin-box' ),
				'description' => __( 'Deletes subscribers, campaigns, forms, settings then re-installs Noptin', 'newsletter-optin-box' ),
				'icon'        => 'trash',
				'ajax_action' => 'noptin_reset_data',
				'button'      => array(
					'text'          => __( 'Reset Noptin', 'newsletter-optin-box' ),
					'isDestructive' => true,
				),
				'confirm'     => 'Are you sure you want to reset all Noptin data? This cannot be undone.',
			),
		);

		/**
		 * Filters Noptin admin tools.
		 *
		 * @param array $tools An array of admin tools.
		 * @since 1.2.3
		 */
		return self::normalize_tools( apply_filters( 'get_noptin_admin_tools', $tools ) );
	}

	/**
	 * Normalizes legacy and current tool registrations.
	 *
	 * @param array $tools Registered tools.
	 * @return array
	 */
	private static function normalize_tools( $tools ) {
		$normalized = array();

		foreach ( $tools as $id => $tool ) {
			if ( ! is_array( $tool ) ) {
				continue;
			}

			$tool['id']          = sanitize_key( $id );
			$tool['title']       = isset( $tool['title'] ) ? $tool['title'] : ( isset( $tool['name'] ) ? $tool['name'] : $id );
			$tool['description'] = isset( $tool['description'] ) ? $tool['description'] : ( isset( $tool['desc'] ) ? $tool['desc'] : '' );

			if ( empty( $tool['type'] ) ) {
				$tool['type'] = ! empty( $tool['form_fields'] ) ? 'form' : ( ! empty( $tool['ajax_action'] ) ? 'background' : 'link' );
			}

			if ( empty( $tool['url'] ) && 'link' === $tool['type'] ) {
				$tool['url'] = wp_nonce_url(
					add_query_arg(
						array(
							'page' => 'noptin-tools',
							'tool' => $tool['id'],
                        ),
                        admin_url( 'admin.php' )
                    ),
					'noptin_tool',
					'noptin_tool_nonce'
				);
			}

			if ( empty( $tool['button'] ) || is_string( $tool['button'] ) ) {
				$tool['button'] = array( 'text' => empty( $tool['button'] ) ? 'Continue' : $tool['button'] );
			}

			$normalized[ $tool['id'] ] = $tool;
		}

		return $normalized;
	}

	/**
	 * Sends a tools AJAX response.
	 *
	 * @param bool   $success Whether the action succeeded.
	 * @param string $message Response message.
	 */
	public static function send_response( $success, $message ) {
		wp_send_json(
			array(
				'success' => (bool) $success,
				'message' => $message,
			)
		);
	}

	/**
	 * Verifies and dispatches a tools AJAX request.
	 */
	public static function maybe_do_ajax_action() {
		if ( ! current_user_can( get_noptin_capability() ) ) {
			self::send_response( false, 'You do not have permission to run this tool.' );
		}

		if ( false === check_ajax_referer( 'noptin-tools', '_ajax_nonce', false ) ) {
			self::send_response( false, 'The request could not be verified. Refresh the page and try again.' );
		}

		$request     = noptin_clean( wp_unslash( $_GET ) );
		$ajax_action = isset( $request['ajax_action'] ) ? sanitize_key( $request['ajax_action'] ) : '';

		if ( empty( $ajax_action ) || ! has_action( $ajax_action ) ) {
			self::send_response( false, 'The requested tool action is not available.' );
		}

		$action_args = $request;

		foreach ( self::get_tools() as $tool ) {
			if ( 'form' === $tool['type'] && isset( $tool['ajax_action'] ) && $ajax_action === $tool['ajax_action'] ) {
				$action_args = isset( $_POST['noptin_tool_values'] ) && is_string( $_POST['noptin_tool_values'] )
					? json_decode( wp_unslash( $_POST['noptin_tool_values'] ), true )
					: array();
				$action_args = is_array( $action_args ) ? wp_kses_post_deep( $action_args ) : array();
				break;
			}
		}

		do_action( $ajax_action, $action_args );

		self::send_response( true, 'The tool completed successfully.' );
	}

	/**
	 * Resets Noptin data.
	 */
	public static function reset_data() {
		define( 'NOPTIN_RESETING_DATA', true );
		include noptin()->plugin_path . 'uninstall.php';
		wp_cache_flush();
		self::send_response( true, 'Noptin data was reset successfully.' );
	}

	/**
	 * Triggers a new-post notification.
	 *
	 * @param array $request Sanitized request data.
	 */
	public static function trigger_new_post_notification( $request ) {
		$post_id = isset( $request['noptin_post_id'] ) ? absint( $request['noptin_post_id'] ) : 0;
		$post    = get_post( $post_id );

		if ( ! $post ) {
			self::send_response( false, 'Enter a valid post ID.' );
		}

		delete_post_meta( $post->ID, 'noptin_sent_notification_campaign' );
		do_action( 'noptin_force_trigger_new_post_notification', $post );
		self::send_response( true, 'New-post automations were triggered successfully.' );
	}
}
