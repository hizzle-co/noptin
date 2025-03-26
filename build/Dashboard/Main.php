<?php
/**
 * Dashboard API.
 *
 * @since   1.0.0
 */

namespace Hizzle\Noptin\Dashboard;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Dashboard API Class.
 *
 * @since 1.0.0
 */
class Main {

	/**
	 * @var string hook suffix
	 */
	public static $hook_suffix;

	/**
	 * Class constructor.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'dashboard_menu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
	}

	/**
	 * Add dashboard menu item.
	 */
	public static function dashboard_menu() {
		self::$hook_suffix = add_submenu_page(
			'noptin',
			__( 'Noptin Dashboard', 'newsletter-optin-box' ),
			__( 'Dashboard', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'noptin',
			array( __CLASS__, 'render_dashboard_page' )
		);
	}

	/**
	 * Displays the dashboard page.
	 */
	public static function render_dashboard_page() {
		if ( ! current_user_can( get_noptin_capability() ) ) {
			return;
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
		// Abort if not on the dashboard page.
		if ( self::$hook_suffix !== $hook ) {
			return;
		}

		// Enqueue the dashboard script.
		$config = include plugin_dir_path( __FILE__ ) . 'assets/js/dashboard.asset.php';
		wp_enqueue_script(
			'noptin-dashboard',
			plugin_dir_url( __FILE__ ) . 'assets/js/dashboard.js',
			$config['dependencies'],
			$config['version'],
			true
		);

		$plugins = array();

		if ( ! class_exists( '\Hizzle\Recaptcha\Main' ) ) {
			$plugins[] = array(
				'label' => 'Hizzle CAPTCHA',
				'info'  => __( 'Protects your subscription, contact, checkout, and registration forms from spammers.', 'newsletter-optin-box' ),
				'icon'  => 'lock',
				'url'   => network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=hizzle-recaptcha&TB_iframe=true&width=772&height=600' ),
			);
		}

		if ( ! function_exists( 'hizzle_downloads' ) ) {
			$plugins[] = array(
				'label' => 'Hizzle Downloads',
				'info'  => __( 'Add downloadable files to your site and restrict access by user role or newsletter subscription status.', 'newsletter-optin-box' ),
				'icon'  => 'download',
				'url'   => network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=hizzle-downloads&TB_iframe=true&width=772&height=600' ),
			);
		}

		// Localize the script.
		wp_localize_script(
			'noptin-dashboard',
			'noptinDashboard',
			array(
				'brand'               => noptin()->white_label->get_details(),
				'forms'               => noptin_count_optin_forms(),
				'subscriber_statuses' => noptin_get_subscriber_statuses(),
				'plugins'             => $plugins,
				'links'               => array(
					array(
						'text' => __( 'Report a bug or request a feature', 'newsletter-optin-box' ),
						'href' => 'https://github.com/hizzle-co/noptin/issues/new/choose',
					),
					array(
						'text' => __( 'Prevent spam sign-ups.', 'newsletter-optin-box' ),
						'href' => noptin_get_guide_url( 'Dashboard', '/subscription-forms/preventing-spam-sign-ups/' ),
					),
					array(
						'text' => __( 'Set-up new post notifications.', 'newsletter-optin-box' ),
						'href' => noptin_get_guide_url( 'Dashboard', '/guide/sending-emails/new-post-notifications/' ),
					),
					array(
						'text' => __( 'Email sending limits.', 'newsletter-optin-box' ),
						'href' => noptin_get_guide_url( 'Dashboard', '/guide/sending-emails/email-sending-limits/' ),
					),
					array(
						'text' => __( 'How to fix emails not sending.', 'newsletter-optin-box' ),
						'href' => noptin_get_guide_url( 'Dashboard', '/guide/sending-emails/how-to-fix-emails-not-sending/' ),
					),
				),
			)
		);

		wp_set_script_translations( 'noptin-dashboard', 'newsletter-optin-box', noptin()->plugin_path . 'languages' );

		// Load the css.
		wp_enqueue_style(
			'noptin-dashboard',
			plugin_dir_url( __FILE__ ) . 'assets/css/style-dashboard.css',
			array( 'wp-components' ),
			$config['version']
		);
	}
}
