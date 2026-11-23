<?php

namespace Hizzle\Noptin\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Optional feedback before an administrator deactivates Noptin.
 */
class Deactivation_Feedback {
	/** Registers the plugin-screen assets. */
	public static function add_hooks() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Loads the survey only on the installed plugins screen.
	 *
	 * @param string $hook Current admin screen.
	 */
	public static function enqueue_assets( $hook ) {
		if ( 'plugins.php' !== $hook || ! current_user_can( 'deactivate_plugin', plugin_basename( \Noptin::$file ) ) ) {
			return;
		}
		$asset = include __DIR__ . '/assets/js/deactivation-feedback.asset.php';
		wp_enqueue_script( 'noptin-deactivation-feedback', plugins_url( 'assets/js/deactivation-feedback.js', __FILE__ ), $asset['dependencies'], $asset['version'], true );
		wp_enqueue_style( 'noptin-deactivation-feedback', plugins_url( 'assets/css/style-deactivation-feedback.css', __FILE__ ), array( 'wp-components' ), $asset['version'] );
		wp_add_inline_script(
			'noptin-deactivation-feedback',
			'window.noptinDeactivationFeedback = ' . wp_json_encode(
				array(
					'plugin'   => plugin_basename( \Noptin::$file ),
					'url'      => apply_filters( 'noptin_telemetry_url', 'https://noptin.com/api/telemetry' ),
					'versions' => array(
						'plugin_version' => noptin()->version,
						'wp_version'     => get_bloginfo( 'version' ),
						'php_version'    => PHP_VERSION,
					),
				)
			) . ';',
			'before'
		);
		wp_set_script_translations( 'noptin-deactivation-feedback', 'newsletter-optin-box', noptin()->plugin_path . 'languages' );
	}
}
