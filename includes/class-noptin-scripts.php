<?php

/**
 * Handles the loading of scripts.
 *
 * @since 1.9.0
 */
class Noptin_Scripts {

	/**
	 * An array of menu hooks and their scripts.
	 */
	protected static $admin_scripts = array();

	/**
	 * Inits the scripts.
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'maybe_load_default_scripts' ) );
	}

	/**
	 * Loads default admin scripts.
	 */
	public static function maybe_load_default_scripts( $hook ) {
		global $current_screen;

		// Load our CSS styles on all pages.
		$assets_url = plugin_dir_url( __FILE__ ) . 'assets';
		$version    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : noptin()->version;
		wp_enqueue_style( 'noptin', $assets_url . '/css/admin.css', array(), $version );

		// Check if the hook suffix contains noptin.
		if ( false === strpos( $hook, 'noptin' ) && false === strpos( $hook, noptin()->white_label->admin_screen_id() ) && ( empty( $current_screen ) || false === strpos( $current_screen->id, 'noptin' ) ) ) {
			return;
		}

		// Remove AUI scripts as they break our pages.
		if ( class_exists( 'AyeCode_UI_Settings' ) && is_callable( 'AyeCode_UI_Settings::instance' ) ) {
			$aui = AyeCode_UI_Settings::instance();
			remove_action( 'admin_enqueue_scripts', array( $aui, 'enqueue_scripts' ), 1 );
			remove_action( 'admin_enqueue_scripts', array( $aui, 'enqueue_style' ), 1 );
		}

		// And EDD too.
		add_filter( 'edd_load_admin_scripts', '__return_false', 1000 );
	}
}
