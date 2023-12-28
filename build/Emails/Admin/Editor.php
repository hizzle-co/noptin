<?php

namespace Hizzle\Noptin\Emails\Admin;

/**
 * Provides functions to load Gutenberg assets
 */
class Editor {

	/**
	 * Load Gutenberg
	 *
	 * @param \Hizzle\Noptin\Emails\Email $edited_campaign The edited campaign.
	 *
	 * @return void
	 */
	public static function load( $edited_campaign ) {

		// TinyMCE.
		wp_enqueue_editor();
		self::setup_media();

		// Prepare iframe styles.
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

		// Localize noptin-email-editor.
		wp_localize_script(
			'noptin-email-editor',
			'noptinEmailEditorSettings',
			array(
				'styles'    => (object) $to_load,
				'settings'  => self::get_editor_settings(),
				'campaign'  => $edited_campaign->to_array(),
				'types'     => get_noptin_email_types(),
				'templates' => get_noptin_email_templates(),
				'back'      => esc_url( $edited_campaign->get_base_url() ),
				'user'      => array(
					'id'        => get_current_user_id(),
					'canUpload' => current_user_can( 'upload_files' ),
				),
				'logo_url'  => noptin()->white_label->get( 'logo', noptin()->plugin_url . 'includes/assets/images/logo.png' ),
			)
		);

		// Add 'block-editor-page' to body class.
		add_filter( 'admin_body_class', array( __CLASS__, 'add_block_editor_body_class' ) );
	}

	public static function add_block_editor_body_class( $classes ) {
		$classes .= ' block-editor-page is-fullscreen-mode';
		return $classes;
	}

	/**
	 * Set up Gutenberg editor settings
	 *
	 * @return Array
	 */
	public static function get_editor_settings() {
		// This is copied from core.
		// phpcs:disable
		global $editor_styles;

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
	public static function setup_media() {
		require_once ABSPATH . 'wp-admin/includes/media.php';

		wp_enqueue_media();
	}
}
