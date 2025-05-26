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
	 * Inits the main forms class.
	 *
	 */
	public static function init() {
		// Enqueue scripts and styles.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );

		// Forms list table.
		List_Table::init();

		// Admin menu.
		add_action( 'admin_menu', array( __CLASS__, 'forms_menu' ), 30 );
		add_action( 'admin_menu', array( __CLASS__, 'menu_highlight' ), 15 );
	}

	/**
	 * Checks if the current page is the forms edit page.
	 */
	public static function is_forms_edit_page() {
		global $pagenow, $post;

		// Check if we're on the post edit or new post page.
		if ( ! in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
			return false;
		}

		// Check if the current post type is 'form' (assuming 'form' is your custom post type for forms).
		if ( ! isset( $post ) || 'noptin-form' !== $post->post_type ) {
			return false;
		}

		// If we've made it this far, we're on the forms edit page
		return true;
	}

	/**
	 * Enqueues scripts and styles.
	 *
	 */
	public static function enqueue_scripts() {
		global $post;

		// Check if we're on the post edit screen
		if ( ! self::is_forms_edit_page() ) {
			return;
		}

		add_filter( 'admin_body_class', array( __CLASS__, 'add_block_editor_body_class' ) );

		$config = include plugin_dir_path( __DIR__ ) . 'assets/js/form-editor.asset.php';
		wp_enqueue_script(
			'noptin-form-editor',
			plugins_url( 'assets/js/form-editor.js', __DIR__ ),
			$config['dependencies'],
			$config['version'],
			true
		);

		// Localize the script.
		$params = apply_filters(
			'noptin_form_editor_data',
			array(
				'form'         => $post->ID,
				'brand'        => noptin()->white_label->get_details(),
				'settings'     => self::sidebar_fields(),
				'templates'    => self::get_templates(),
				'default_form' => include plugin_dir_path( __FILE__ ) . 'default-form.php',
			)
		);

		// Check if it was created by the legacy editor.
		$state = get_post_meta( $post->ID, '_noptin_state', true );

		if ( is_object( $state ) ) {
			$state = (array) $state;
		}

		if ( ! is_array( $state ) || empty( $state['fields'] ) ) {
			$form                    = new \Hizzle\Noptin\Forms\Form( $post->ID );
			$params['form_settings'] = $form->get_all_data();
		}

		wp_add_inline_script(
			'noptin-form-editor',
			sprintf(
				'var noptinForm = %s;',
				wp_json_encode( $params )
			),
			'before'
		);

		// Load the translations.
		wp_set_script_translations( 'noptin-form-editor', 'newsletter-optin-box', noptin()->plugin_path . 'languages' );

		// Preload the data.
		self::preload( $post->ID );

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
			array( '/wp/v2/pages', 'OPTIONS' ),
			array( '/wp/v2/media', 'OPTIONS' ),
			'wp/v2/block-patterns/categories',
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

	public static function add_block_editor_body_class( $classes ) {
		$classes .= ' block-editor-page is-fullscreen-mode';
		return $classes;
	}

	/**
	 * Add forms menu item.
	 */
	public static function forms_menu() {
		add_submenu_page(
			'noptin',
			esc_html__( 'Subscription Forms', 'newsletter-optin-box' ),
			esc_html__( 'Subscription Forms', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'edit.php?post_type=noptin-form'
		);
	}

	/**
	 * Highlights the correct top level admin menu item for post type add screens.
	 */
	public static function menu_highlight() {
		global $parent_file, $post_type;

		if ( 'noptin-form' === $post_type ) {
			$parent_file  = 'noptin'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}
}
