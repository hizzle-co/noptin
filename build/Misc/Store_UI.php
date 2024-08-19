<?php
/**
 * Displays a hizzle store's collection.
 *
 * @since   3.2.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Misc;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Displays a hizzle store's collection.
 *
 * @since 3.2.0
 * @internal
 * @ignore
 */
class Store_UI {

    /**
	 * @var array stores
	 */
	public static $stores;

	/**
	 * Inits the main emails class.
	 *
	 */
	public static function init() {

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
	}

	/**
	 * Collection menu.
	 */
	public static function collection_menu( $hook_suffix, $collection ) {

		self::$stores[ $hook_suffix ] = '/' . ltrim( $collection, '/' );
	}

	/**
	 * Displays the admin page.
	 */
	public static function render_admin_page() {
        ?>
            <div class="wrap noptin-collection-page" id="noptin-wrapper">
                <h1><?php esc_html( get_admin_page_title() ); ?></h1>

                <div id="noptin-collection__app">
                    <!-- spinner -->
                    <span class="spinner" style="visibility: visible; float: none;"></span>
                    <!-- /spinner -->
                </div>
            </div>
        <?php
	}

	/**
	 * Enqueues scripts and styles.
	 *
	 * @param string $hook The current admin page.
	 */
	public static function enqueue_scripts( $hook ) {

		$config = include plugin_dir_path( __FILE__ ) . 'assets/js/interface.asset.php';

		wp_register_script(
			'noptin-interface',
			plugins_url( 'assets/css/style-interface.css', __FILE__ ),
			array(),
			$config['version']
		);

		// Abort if not on the email subscribers page.
		if ( ! isset( self::$stores[ $hook ] ) ) {
			return;
		}

		$config = include plugin_dir_path( __FILE__ ) . 'assets/js/collection.asset.php';

		wp_enqueue_script(
			'noptin-collection',
			plugins_url( 'assets/js/collection.js', __FILE__ ),
			$config['dependencies'],
			$config['version'],
			true
		);

        wp_localize_script(
            'noptin-collection',
            'noptinCollection',
            array(
                'collection' => self::$stores[ $hook ],
				'brand'      => noptin()->white_label->get_details(),
            )
        );

		wp_set_script_translations( 'noptin-collection', 'newsletter-optin-box', noptin()->plugin_path . 'languages' );

		// Load the css.
		wp_enqueue_style(
			'noptin-collection',
			plugins_url( 'assets/css/style-collection.css', __FILE__ ),
			array( 'wp-components' ),
			$config['version']
		);

		Main::load_interface_styles();
	}
}
