<?php
/**
 * Onboarding API: Onboarding Admin.
 *
 * Contains the main admin class for Noptin Onboarding
 *
 * @since   4.1.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Onboarding;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main admin class for Noptin Onboarding.
 *
 * @since 4.1.0
 * @internal
 * @ignore
 */
class Menu {

	/**
	 * @var string hook suffix
	 */
	public static $hook_suffix;

	/**
	 * Inits the main onboarding menu.
	 *
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		// Hide the menu item.
		add_action( 'admin_head', array( __CLASS__, 'hide_menu_item' ) );
	}

	/**
	 * Setup wizard menu.
	 */
	public static function register_menu() {

		self::$hook_suffix = add_submenu_page(
			'noptin',
			__( 'Setup Wizard', 'newsletter-optin-box' ),
			__( 'Setup Wizard', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'noptin-setup-wizard',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Hides the menu item.
	 */
	public static function hide_menu_item() {
		global $submenu;

		if ( isset( $submenu['noptin'] ) ) {
			$to_remove = array( 'noptin-setup-wizard' );

			if ( ! noptin_should_split_emails_menu() ) {
				$to_remove[] = 'noptin-tools';
			}

			foreach ( $submenu['noptin'] as $index => $menu_item ) {
				if ( isset( $menu_item[2] ) && in_array( $menu_item[2], $to_remove, true ) ) {
					unset( $submenu['noptin'][ $index ] );
				}
			}
		}

		// CSS fallback in case another hook re-adds the menu item later.
		?>
		<style>
			#adminmenu a[href="admin.php?page=noptin-setup-wizard"],
			body:not(.noptin-has-split-email-menu) #adminmenu a[href="admin.php?page=noptin-tools"] {
				display: none !important;
			}
			li#toplevel_page_noptin .wp-menu-image img {
				width: 20px;
				height: auto;
			}

			.noptin-has-split-email-menu #toplevel_page_noptin .wp-submenu li:not(:has(a[href*="page=noptin-email-campaigns"])) + li:has(a[href*="page=noptin-email-campaigns"]) a {
				position: relative;
				margin-top: 16px;
			}

			.noptin-has-split-email-menu #toplevel_page_noptin .wp-submenu li:has(a[href*="page=noptin-email-campaigns"]):not(:has(~ li a[href*="page=noptin-email-campaigns"])) a {
				position: relative;
				margin-bottom: 16px;
			}

			.noptin-has-split-email-menu #toplevel_page_noptin .wp-submenu li:not(:has(a[href*="page=noptin-email-campaigns"])) + li:has(a[href*="page=noptin-email-campaigns"]) a:before,
			.noptin-has-split-email-menu #toplevel_page_noptin .wp-submenu li:has(a[href*="page=noptin-email-campaigns"]):not(:has(~ li a[href*="page=noptin-email-campaigns"])) a:after,
			.noptin-has-split-email-menu #toplevel_page_noptin li:not(:last-child) a[href="admin.php?page=noptin-automation-rules"]:after,
			#toplevel_page_noptin li:not(:last-child) a[href="admin.php?page=noptin-addons"]:before,
			#toplevel_page_noptin li:not(:last-child) a[href="admin.php?page=noptin-addons"]:after {
				display: block;
				content: '';
				position: absolute;
				left: 5px;
				right: 5px;
				height: 0;
				border-bottom: 1px solid rgba( 255, 255, 255, 0.15 );
			}

			.noptin-has-split-email-menu #toplevel_page_noptin .wp-submenu li:not(:has(a[href*="page=noptin-email-campaigns"])) + li:has(a[href*="page=noptin-email-campaigns"]) a:before,
			#toplevel_page_noptin li:not(:last-child) a[href="admin.php?page=noptin-addons"]:before {
				top: -8px;
			}

			.noptin-has-split-email-menu #toplevel_page_noptin .wp-submenu li:has(a[href*="page=noptin-email-campaigns"]):not(:has(~ li a[href*="page=noptin-email-campaigns"])) a:after,
			.noptin-has-split-email-menu #toplevel_page_noptin li:not(:last-child) a[href="admin.php?page=noptin-automation-rules"]:after,
			#toplevel_page_noptin li:not(:last-child) a[href="admin.php?page=noptin-addons"]:after { 
				bottom: -8px;
			}

			.noptin-has-split-email-menu #toplevel_page_noptin li:not(:last-child) a[href="admin.php?page=noptin-automation-rules"] {
				position: relative;
				margin-bottom: 16px;
			}

			#toplevel_page_noptin li:not(:last-child) a[href="admin.php?page=noptin-addons"] {
				position: relative;
				margin: 16px 0;
			}

			#adminmenu #toplevel_page_noptin a[href="admin.php?page=noptin-addons"] {
				color: #adff2f;
			}
		</style>
		<?php
	}

	/**
	 * Displays the admin page.
	 */
	public static function render_admin_page() {

		// Check permission.
		if ( current_user_can( get_noptin_capability() ) ) {
			echo '<div id="noptin-setup-wizard-app" class="noptin-setup-wizard"></div>';
		}
	}

	/**
	 * Enqueues scripts and styles.
	 *
	 * @param string $hook The current admin page.
	 */
	public static function enqueue_scripts( $hook ) {

		// Abort if not on the setup wizard page.
		if ( self::$hook_suffix !== $hook ) {
			return;
		}

		// Enqueue the setup wizard script.
		$config = include plugin_dir_path( __FILE__ ) . 'assets/js/setup-wizard.asset.php';
		wp_enqueue_script(
			'noptin-setup-wizard',
			plugin_dir_url( __FILE__ ) . 'assets/js/setup-wizard.js',
			$config['dependencies'],
			$config['version'],
			true
		);

		// Get license info.
		$current_user = wp_get_current_user();
		$data         = array(
			'brand'                => noptin()->white_label->get_details(),
			'dashboardURL'         => add_query_arg(
				array(
					'page' => 'noptin',
				),
				admin_url( 'admin.php' )
			),
			'detectedIntegrations' => Main::get_detected_integrations(),
			'crmConnections'       => Main::get_crm_connections(),
			'ajaxUrl'              => admin_url( 'admin-ajax.php' ),
			'updatesNonce'         => wp_create_nonce( 'updates' ),
			'options'              => array_merge(
				get_noptin_options(),
				array(
					'custom_fields' => array_values( get_noptin_custom_fields() ),
				)
			),
			'plugins'              => self::get_installed_plugins(),
			'emailSettings'        => array(
				'from_email'        => array(
					'el'      => 'input',
					'section' => 'emails',
					'type'    => 'email',
					'label'   => __( '"From" Email', 'newsletter-optin-box' ),
					'tooltip' => __( 'How the sender email appears in outgoing emails. Leave this field blank if you are not able to send any emails.', 'newsletter-optin-box' ),
				),

				'from_name'         => array(
					'el'          => 'input',
					'section'     => 'emails',
					'label'       => __( '"From" Name', 'newsletter-optin-box' ),
					'placeholder' => get_option( 'blogname' ),
					'default'     => get_option( 'blogname' ),
					'tooltip'     => __( 'How the sender name appears in outgoing emails', 'newsletter-optin-box' ),
				),

				'reply_to'          => array(
					'el'      => 'input',
					'section' => 'emails',
					'type'    => 'email',
					'label'   => __( '"Reply-to" Email', 'newsletter-optin-box' ),
					'default' => get_option( 'admin_email' ),
					'tooltip' => __( 'Where should subscribers reply to in case they need to get in touch with you?', 'newsletter-optin-box' ),
				),
				'sending_frequency' => array(
					'el'       => 'horizontal',
					'settings' => array(
						'per_hour'                     => array(
							'el'               => 'input',
							'type'             => 'number',
							'section'          => 'emails',
							'label'            => __( 'Sending rate', 'newsletter-optin-box' ),
							'placeholder'      => __( 'Unlimited', 'newsletter-optin-box' ),
							'customAttributes' => array(
								'min'    => 1,
								'suffix' => array( __( 'email', 'newsletter-optin-box' ), __( 'emails', 'newsletter-optin-box' ) ),
							),
						),
						'email_sending_rolling_period' => array(
							'el'               => 'unit',
							'section'          => 'emails',
							'label'            => __( 'Time Period', 'newsletter-optin-box' ),
							'default'          => '1hours',
							'customAttributes' => array(
								'min'                  => 1,
								'placeholder'          => '1 hour',
								'prefix'               => __( 'per', 'newsletter-optin-box' ),
								'className'            => 'hizzlewp-components-unit-control__select--large',
								'units'                => array(
									array(
										'default' => HOUR_IN_SECONDS,
										'label'   => __( 'second(s)', 'newsletter-optin-box' ),
										'value'   => 'seconds',
									),
									array(
										'default' => MINUTE_IN_SECONDS,
										'label'   => __( 'minute(s)', 'newsletter-optin-box' ),
										'value'   => 'minutes',
									),
									array(
										'default' => 1,
										'label'   => __( 'hour(s)', 'newsletter-optin-box' ),
										'value'   => 'hours',
									),
									array(
										'default' => 1,
										'label'   => __( 'day(s)', 'newsletter-optin-box' ),
										'value'   => 'days',
									),
								),
								'__unstableInputWidth' => null,
								'labelPosition'        => 'top',
							),
						),
					),
				),
			),
			'userEmail'            => $current_user->user_email ?? '',
			'emailSignup'          => (object) array_filter(
				array(
					'first_name' => $current_user->first_name ?? '',
					'last_name'  => $current_user->last_name ?? '',
				)
			),
		);

		// Localize the script.
		wp_add_inline_script(
			'noptin-setup-wizard',
			'window.noptinSetupWizard = ' . wp_json_encode( $data ) . ';',
			'before'
		);

		wp_set_script_translations( 'noptin-setup-wizard', 'newsletter-optin-box', noptin()->plugin_path . 'languages' );

		// Load the css.
		wp_enqueue_style(
			'noptin-setup-wizard',
			plugin_dir_url( __FILE__ ) . 'assets/css/style-setup-wizard.css',
			array( 'wp-components' ),
			$config['version']
		);
	}

	/**
	 * Retrieves a list of installed plugins.
	 *
	 * @return array
	 */
	public static function get_installed_plugins() {

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = array();

		foreach ( get_plugins() as $filename => $data ) {
			// Skip if does not have a directory.
			if ( false === strpos( $filename, '/' ) ) {
				continue;
			}

			$plugins[ basename( dirname( $filename ) ) ] = array(
				'file_name' => $filename,
				'name'      => $data['Name'],
				'isActive'  => is_plugin_active( $filename ),
			);
		}

		return $plugins;
	}
}
