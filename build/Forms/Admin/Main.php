<?php
/**
 * Forms API: Forms Admin.
 *
 * Contains the main admin class for Noptin forms.
 *
 * @since   2.3.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Forms\Admin;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The main admin class for Noptin forms.
 *
 * @since 2.3.0
 * @internal
 * @ignore
 */
class Main {

	/**
	 * @var string hook suffix
	 */
	public static $hook_suffix;

	/**
	 * Inits the main forms class.
	 *
	 */
	public static function init() {

		add_action( 'admin_menu', array( __CLASS__, 'newsletter_forms_menu' ), 35 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
	}

	/**
	 * Newsletter forms menu.
	 */
	public static function newsletter_forms_menu() {

		self::$hook_suffix = add_submenu_page(
			'noptin',
			esc_html__( 'Forms new', 'newsletter-optin-box' ),
			esc_html__( 'Forms new', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'noptin-email-forms__new',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Displays the admin page.
	 */
	public static function render_admin_page() {
		?>
			<div id="noptin-form-editor" class="wrap">
				<span class="spinner" style="float: none; visibility: visible;"></span>
			</div>
		<?php
	}

	/**
	 * Enqueues scripts and styles.
	 *
	 * @param string $hook The current admin page.
	 */
	public static function enqueue_scripts( $hook ) {
		global $post;

		// Abort if not on the email forms page.
		if ( self::$hook_suffix !== $hook ) {
			return;
		}

		$config = include plugin_dir_path( __DIR__ ) . 'assets/js/form-editor.asset.php';
		wp_enqueue_script(
			'noptin-form-editor',
			plugins_url( 'assets/js/form-editor.js', __DIR__ ),
			$config['dependencies'],
			$config['version'],
			true
		);

		// Localize the script.
		wp_localize_script(
			'noptin-form-editor',
			'noptinForm',
			array(
				'data' => apply_filters(
					'noptin_form_editor_data',
						array(
						'form'      => 3316, // empty( $post ) ? null : $post->ID,
						'brand'     => noptin()->white_label->get_details(),
						'settings'  => self::sidebar_fields(),
						'templates' => self::get_templates(),
					)
				),
			)
		);

		// Load the translations.
		wp_set_script_translations( 'noptin-form-editor', 'newsletter-optin-box', noptin()->plugin_path . 'languages' );

		// Preload the data.
		self::preload( 3316 );

		// Load the css.
		wp_enqueue_style( 'wp-components' );

		if ( file_exists( plugin_dir_path( __DIR__ ) . 'assets/css/style-form-editor.css' ) ) {
			$version = empty( $config ) ? filemtime( plugin_dir_path( __DIR__ ) . 'assets/css/style-form-editor.css' ) : $config['version'];
			wp_enqueue_style(
				'noptin-form-editor',
				plugins_url( 'assets/css/style-form-editor.css', __DIR__ ),
				array( 'wp-block-editor', 'wp-edit-post', 'wp-format-library', 'wp-editor' ),
				$version
			);
		}
	}

	private static function preload( $post_id ) {
		global $post, $wp_scripts, $wp_styles;
		$rest_path = rest_get_route_for_post( $post_id );

		// Preload common data.
		$preload_paths = array(
			'/wp/v2/types?context=view',
			'/wp/v2/types?context=edit&per_page=100',
			'/wp/v2/taxonomies?context=view',
			add_query_arg( 'context', 'edit', $rest_path ),
			sprintf( '/wp/v2/types/%s?context=edit', 'noptin-form' ),
			'/wp/v2/users/me',
			sprintf( '%s/autosaves?context=edit', $rest_path ),
			'/wp/v2/settings',
			array( '/wp/v2/settings', 'OPTIONS' ),
		);

		/*
		* Ensure the global $post, $wp_scripts, and $wp_styles remain the same after
		* API data is preloaded.
		* Because API preloading can call the_content and other filters, plugins
		* can unexpectedly modify the global $post or enqueue assets which are not
		* intended for the block editor.
		*/
		$backup_global_post = ! empty( $post ) ? clone $post : $post;
		$backup_wp_scripts  = ! empty( $wp_scripts ) ? clone $wp_scripts : $wp_scripts;
		$backup_wp_styles   = ! empty( $wp_styles ) ? clone $wp_styles : $wp_styles;

		foreach ( $preload_paths as &$path ) {
			if ( is_string( $path ) && ! str_starts_with( $path, '/' ) ) {
				$path = '/' . $path;
				continue;
			}
	
			if ( is_array( $path ) && is_string( $path[0] ) && ! str_starts_with( $path[0], '/' ) ) {
				$path[0] = '/' . $path[0];
			}
		}
	
		unset( $path );
	
		$preload_data = array_reduce(
			$preload_paths,
			'rest_preload_api_request',
			array()
		);

		// Restore the global $post, $wp_scripts, and $wp_styles as they were before API preloading.
		$post       = $backup_global_post;
		$wp_scripts = $backup_wp_scripts;
		$wp_styles  = $backup_wp_styles;

		wp_add_inline_script(
			'wp-api-fetch',
			sprintf(
				'wp.apiFetch.use( wp.apiFetch.createPreloadingMiddleware( %s ) );',
				wp_json_encode( $preload_data )
			),
			'after'
		);
	}

	/**
	 * Returns sidebar fields
	 */
	public static function sidebar_fields() {
		$editor_settings = include plugin_dir_path( __FILE__ ) . 'editor-settings.php';

		/**
		 * Filters the Noptin Form Editor's sidebar fields.
		 *
		 * @param array $fields Sidebar fields.
		 */
		$editor_settings = apply_filters( 'noptin_optin_form_editor_sidebar_section', $editor_settings );

		if ( empty( $editor_settings['integrations'] ) || 1 === count( $editor_settings['integrations'] ) ) {
			unset( $editor_settings['integrations'] );
		}

		return $editor_settings;
	}

	/**
	 * Returns available templates.
	 */
	private static function get_templates() {

		$custom_templates  = get_option( 'noptin_templates' );
		$inbuilt_templates = include plugin_dir_path( __FILE__ ) . 'optin-templates.php';

		if ( ! is_array( $custom_templates ) ) {
			$custom_templates = array();
		}

		return array_merge( $custom_templates, $inbuilt_templates );

	}
}
