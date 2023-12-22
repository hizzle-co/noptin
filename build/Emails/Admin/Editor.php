<?php

namespace Noptin\Emails\Admin;

/**
 * Provides functions to load Gutenberg assets
 */
class Editor {
	private $hook_suffix;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'email_campaigns_menu' ), 37 );
		add_action( 'admin_enqueue_scripts', array( $this, 'load' ) );
	}

	/**
	 * Load Gutenberg
	 *
	 * Based on wp-admin/edit-form-blocks.php
	 *
	 * @return void
	 */
	public function load( $hook_suffix ) {

		if ( $hook_suffix !== $this->hook_suffix ) {
			return;
		}

		// TinyMCE.
		wp_enqueue_editor();
		$this->setup_media();
		add_action( 'wp_print_footer_scripts', array( '_WP_Editors', 'print_default_editor_scripts' ), 45 );

		// Editor scripts.
		$path   = plugin_dir_path( NOPTIN_PREMIUM_FILE );
		$config = include $path . 'assets/js/dnd-email/index.asset.php';

		wp_enqueue_script(
			'noptin-email-editor',
			plugins_url( 'assets/js/dnd-email/index.js', NOPTIN_PREMIUM_FILE ),
			$config['dependencies'],
			$config['version'],
			true
		);

		$styles  = wp_styles();
		$to_load = array();

		// Add wp-components.
		foreach ( array( 'wp-edit-blocks', 'dashicons', 'format-library' ) as $style ) {
			if ( isset( $styles->registered[ $style ] ) ) {

				// Maybe load dependencies.
				if ( ! empty( $styles->registered[ $style ]->deps ) ) {
					foreach ( $styles->registered[ $style ]->deps as $dep ) {
						if ( isset( $styles->registered[ $dep ] ) ) {
							$to_load[ $dep ] = $styles->_css_href(
								$styles->registered[ $dep ]->src,
								$styles->default_version,
								$styles->registered[ $dep ]->handle
							);
						}
					}
				}

				// Load style.
				$to_load[ $style ] = $styles->_css_href(
					$styles->registered[ $style ]->src,
					$styles->default_version,
					$styles->registered[ $style ]->handle
				);
			}
		}

		// Localize noptin-email-editor with url to wp-components style.
		wp_localize_script(
			'noptin-email-editor',
			'noptinEmailEditorSettings',
			array(
				'styles'    => (object) $to_load,
				'settings'  => $this->get_editor_settings(),
				'types'     => get_noptin_email_types(),
				'templates' => get_noptin_email_templates(),
				'back'      => esc_url( remove_query_arg( 'campaign', add_query_arg( 'sub_section', false ) ) ),
				'user'      => array(
					'id'        => get_current_user_id(),
					'canUpload' => current_user_can( 'upload_files' ),
				),
				'logo_url'  => noptin()->white_label->get( 'logo', noptin()->plugin_url . 'includes/assets/images/logo.png' ),
			)
		);

		wp_enqueue_style(
			'noptin-email-editor',
			plugins_url( 'assets/js/assets/style-es6-dnd-email.css', NOPTIN_PREMIUM_FILE ),
			array( 'wp-components', 'wp-block-editor', 'wp-edit-post', 'wp-format-library' ),
			$config['version']
		);

		// Add 'block-editor-page' to body class.
		add_filter( 'admin_body_class', array( $this, 'add_block_editor_body_class' ) );

	}

	public function add_block_editor_body_class( $classes ) {
		$classes .= ' block-editor-page is-fullscreen-mode';
		return $classes;
	}

	/**
	 * Set up Gutenberg editor settings
	 *
	 * @return Array
	 */
	public function get_editor_settings() {
		// This is copied from core.
		// phpcs:disable
		global $editor_styles, $post;

		$color_palette = current( (array) get_theme_support( 'editor-color-palette' ) );
		$font_sizes    = current( (array) get_theme_support( 'editor-font-sizes' ) );

		$max_upload_size = wp_max_upload_size();
		if ( ! $max_upload_size ) {
			$max_upload_size = 0;
		}

		// Editor Styles.
		$styles = array(
			array(
				'css' => file_get_contents(
					ABSPATH . WPINC . '/css/dist/block-library/editor.min.css'
				),
			),
			array(
				'css' => file_get_contents(
					ABSPATH . WPINC . '/css/dist/block-library/theme.min.css'
				),
			),
		);

		if ( $editor_styles && current_theme_supports( 'editor-styles' ) ) {
			foreach ( $editor_styles as $style ) {
				if ( preg_match( '~^(https?:)?//~', $style ) ) {
					$response = wp_remote_get( $style );
					if ( ! is_wp_error( $response ) ) {
						$styles[] = array(
							'css' => wp_remote_retrieve_body( $response ),
						);
					}
				} else {
					$file = get_theme_file_path( $style );
					if ( is_file( $file ) ) {
						$styles[] = array(
							'css'     => file_get_contents( $file ),
							'baseURL' => get_theme_file_uri( $style ),
						);
					}
				}
			}
		}

		$image_size_names = apply_filters(
			'image_size_names_choose',
			array(
				'thumbnail' => __( 'Thumbnail' ),
				'medium'    => __( 'Medium' ),
				'large'     => __( 'Large' ),
				'full'      => __( 'Full Size' ),
			)
		);

		$available_image_sizes = array();
		foreach ( $image_size_names as $image_size_slug => $image_size_name ) {
			$available_image_sizes[] = array(
				'slug' => $image_size_slug,
				'name' => $image_size_name,
			);
		}

		/**
		 * @psalm-suppress TooManyArguments
		 */
		$editor_settings = array(
			'alignWide'              => false,
			'disableCustomColors'    => false,
			'disableCustomFontSizes' => false,
			'titlePlaceholder'       => __( 'Add email subject', 'newsletter-optin-box' ),
			'bodyPlaceholder'        => __( 'Start writing or type / to choose a block' ),
			'isRTL'                  => is_rtl(),
			'autosaveInterval'       => AUTOSAVE_INTERVAL,
			'maxUploadFileSize'      => $max_upload_size,
			'allowedMimeTypes'       => array(
				// Image formats.
				'jpg|jpeg|jpe'                 => 'image/jpeg',
				'gif'                          => 'image/gif',
				'png'                          => 'image/png',
				'bmp'                          => 'image/bmp',
				'tiff|tif'                     => 'image/tiff',
				'webp'                         => 'image/webp',
				'ico'                          => 'image/x-icon',
				'heic'                         => 'image/heic',
			),
			'wpAllowedMimeTypes'     => wp_get_mime_types(),
			'styles'                 => $styles,
			'imageSizes'             => $available_image_sizes,
			'richEditingEnabled'     => user_can_richedit(),
			'codeEditingEnabled'     => current_user_can( 'edit_others_posts' ),
			'__experimentalCanUserUseUnfilteredHTML' => current_user_can( 'edit_others_posts' ),
			'__experimentalBlockPatterns' => [],
			'__experimentalBlockPatternCategories' => [],
			'disableLayoutStyles' => true,
			'__experimentalFeatures' => [
				'color' => array(
					'custom'     => true,
					'text'       => true,
					'background' => true,
					'link'       => true,
					'palette'    => array(
						'theme' => array(
							array(
								'name'  => __( 'Primary', 'newsletter-optin-box' ),
								'slug'  => 'primary',
								'color' => get_noptin_option( 'brand_color', '#1a82e2' ),
							),
							array(
								'name'  => __( 'White', 'newsletter-optin-box' ),
								'slug'  => 'white',
								'color' => '#ffffff',
							),
							array(
								'name'  => __( 'Black', 'newsletter-optin-box' ),
								'slug'  => 'black',
								'color' => '#000000',
							),
							array(
								'name' => 'Grey',
								'slug' => 'grey',
								'color' => '#95a5a6',
							),
							array(
								'name'  => __( 'Dark Gray', 'newsletter-optin-box' ),
								'slug'  => 'dark-gray',
								'color' => '#111111',
							),
							array(
								'name'  => __( 'Light Gray', 'newsletter-optin-box' ),
								'slug'  => 'light-gray',
								'color' => '#f1f1f1',
							),
							array(
								'name' => 'Blue',
								'slug' => 'blue',
								'color' => '#3498db',
							),
							array(
								'name' => 'Green',
								'slug' => 'green',
								'color' => '#2ecc71',
							),
							array(
								'name' => 'Red',
								'slug' => 'red',
								'color' => '#e74c3c',
							),
							array(
								'name' => 'Yellow',
								'slug' => 'yellow',
								'color' => '#f1c40f',
							),
							array(
								'name' => 'Orange',
								'slug' => 'orange',
								'color' => '#e67e22',
							),
							array(
								'name' => 'Purple',
								'slug' => 'purple',
								'color' => '#9b59b6',
							),
							array(
								'name' => 'Pink',
								'slug' => 'pink',
								'color' => '#e91e63',
							),
							array(
								'name' => 'Teal',
								'slug' => 'teal',
								'color' => '#008080',
							),
						),
					),
				),
				'fontSizes' => true,
				'linkColor' => true,
				'customLineHeight' => true,
				'customUnits' => true,
				'layout' => true,
				'linkColor' => true,
				'linkColorPalette' => true,
				'linkColorSuggestions' => true,
				'linkPreviews' => true,
				'linkSettings' => true,
				'linkToolbar' => true,
				'list' => true,
				'spacing' => array(
					'padding' => true,
					'margin' => true,
				),
				'background' => array(
					'backgroundImage' => true,
				),
				'typography' => [
					'dropCap' => false,
					'fluid'   => false,
				],
				"appearanceTools" => true,
			],
		);

		return $editor_settings;
	}

	/**
	 * Ensure media works in Gutenberg
	 *
	 * @return void
	 */
	public function setup_media() {
		require_once ABSPATH . 'wp-admin/includes/media.php';

		wp_enqueue_media();
	}

	/**
	 * Email campaigns menu.
	 */
	public function email_campaigns_menu() {

		$this->hook_suffix = add_submenu_page(
			'noptin',
			'Email Campaigns --- New',
			'Email Campaigns --- New',
			get_noptin_capability(),
			'noptin-email-campaigns-new',
			function() {
				?>
				<div id="noptin-email-campaigns__new" class="block-editor"></div>
				<?php
			}
		);
	}
}
