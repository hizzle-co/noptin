<?php
/**
 * Script Manager
 *
 * @package Hizzle\WordPress
 */

namespace Hizzle\WordPress;

if ( did_action( 'hizzlewp_scripts_init' ) ) {
	return;
}

/**
 * Handles script and style registration.
 */
class ScriptManager {

	/**
	 * Handles for registered styles.
	 *
	 * @var array
	 */
	private static $style_handles = array();

	/**
	 * Initializes the script manager.
	 *
	 * @var array
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_scripts' ), 5 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'auto_load_styles' ), 1000 );
		do_action( 'hizzlewp_scripts_init' );
	}

	/**
	 * Registers all scripts.
	 */
	public static function register_scripts() {
		$current_file = defined( 'HIZZLE_SCRIPT_MANAGER_FILE' ) ? constant( 'HIZZLE_SCRIPT_MANAGER_FILE' ) : __FILE__;

		// Register scripts from all folders in ./build.
		$scripts_dir = plugin_dir_path( $current_file ) . 'build';

		$folders = scandir( $scripts_dir );
		foreach ( $folders as $folder ) {
			if ( '.' !== $folder && '..' !== $folder && is_dir( $scripts_dir . '/' . $folder ) ) {
				$folder_path     = trailingslashit( wp_normalize_path( $scripts_dir . '/' . $folder ) );
				$load_components = false;

				// Script.
				if ( file_exists( $folder_path . 'index.js' ) ) {
					$config_file     = include $folder_path . 'index.asset.php';
					$load_components = in_array( 'wp-components', $config_file['dependencies'], true );

					wp_register_script(
						'hizzlewp-' . $folder,
						plugins_url( $folder . '/index.js', $folder_path ),
						$config_file['dependencies'],
						$config_file['version'],
						true
					);
				}

				// Style.
				if ( file_exists( $folder_path . 'style-index.css' ) ) {
					wp_register_style(
						'hizzlewp-' . $folder,
						plugins_url( $folder . '/style-index.css', $folder_path ),
						$load_components ? array( 'wp-components' ) : array(),
						filemtime( $folder_path . 'style-index.css' )
					);

					self::$style_handles[] = 'hizzlewp-' . $folder;
				}
			}
		}
	}

	/**
	 * Auto-loads styles whenever their corresponding scripts are loaded.
	 */
	public static function auto_load_styles() {

		// Loop through all our styles.
		foreach ( self::$style_handles as $handle ) {
			if (
				// Script is enqueued.
				wp_script_is( $handle, 'enqueued' )

				// Style is registered.
				&& wp_style_is( $handle, 'registered' )

				// Style is not enqueued.
				&& ! wp_style_is( $handle, 'enqueued' )
			) {
				wp_enqueue_style( $handle );

				// Add 'block-editor-page' to body class.
				if ( 'hizzlewp-interface' === $handle ) {
					add_filter( 'admin_body_class', array( __CLASS__, 'add_block_editor_body_class' ) );
				}
			}
		}
	}

	/**
	 * Adds 'block-editor-page' to the body class.
	 *
	 * @param string $classes The current body classes.
	 * @return string The modified body classes.
	 */
	public static function add_block_editor_body_class( $classes ) {
		$classes .= ' block-editor-page is-fullscreen-mode';
		return $classes;
	}
}

ScriptManager::init();
