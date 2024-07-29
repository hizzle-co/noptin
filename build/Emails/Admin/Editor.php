<?php

namespace Hizzle\Noptin\Emails\Admin;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

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

		// Block editor context.
		$post                 = self::get_post( $edited_campaign );
		$block_editor_context = new \WP_Block_Editor_Context( array( 'post' => $post ) );

		/*
		 * Emoji replacement is disabled for now, until it plays nicely with React.
		 */
		remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );

		/*
		 * Block editor implements its own Options menu for toggling Document Panels.
		 */
		add_filter( 'screen_options_show_screen', '__return_false' );

		$rest_path = rest_get_route_for_post( $post );

		// Preload common data.
		$preload_paths = array(
			'/wp/v2/types?context=view',
			'/wp/v2/types?context=edit&per_page=100',
			'/wp/v2/taxonomies?context=view',
			add_query_arg( 'context', 'edit', $rest_path ),
			sprintf( '/wp/v2/types/%s?context=edit', 'noptin-campaign' ),
			'/wp/v2/users/me',
			array( rest_get_route_for_post_type_items( 'page' ), 'OPTIONS' ),
			sprintf( '%s/autosaves?context=edit', $rest_path ),
			'/wp/v2/settings',
			array( '/wp/v2/settings', 'OPTIONS' ),
		);

		block_editor_rest_api_preload( $preload_paths, $block_editor_context );

		/*
		* Assign initial edits, if applicable. These are not initially assigned to the persisted post,
		* but should be included in its save payload.
		*/
		$initial_edits = array();
		$is_new_post   = false;
		if ( 'auto-draft' === $post->post_status ) {
			$is_new_post = true;

			// Override "(Auto Draft)" new post default title with empty string, or filtered value.
			$initial_edits['title']   = 'Auto Draft' === $post->post_title ? '' : $post->post_title;
			$initial_edits['content'] = $post->post_content;
			$initial_edits['excerpt'] = $post->post_excerpt;
		}

		// Lock settings.
		$user_id = wp_check_post_lock( $post->ID );
		if ( $user_id ) {
			$locked = false;

			/** This filter is documented in wp-admin/includes/post.php */
			if ( apply_filters( 'show_post_locked_dialog', true, $post, $user_id ) ) {
				$locked = true;
			}

			$user_details = null;
			if ( $locked ) {
				$user         = get_userdata( $user_id );
				$user_details = array(
					'avatar' => get_avatar_url( $user_id, array( 'size' => 128 ) ),
					'name'   => $user->display_name,
				);
			}

			$lock_details = array(
				'isLocked' => $locked,
				'user'     => $user_details,
			);
		} else {
			// Lock the post.
			$active_post_lock = wp_set_post_lock( $post->ID );
			if ( $active_post_lock ) {
				$active_post_lock = esc_attr( implode( ':', $active_post_lock ) );
			}

			$lock_details = array(
				'isLocked'       => false,
				'activePostLock' => $active_post_lock,
			);
		}

		$editor_settings = array(
			'availableTemplates'                    => array(),
			'disablePostFormats'                    => true,
			'titlePlaceholder'                      => __( 'Add email subject', 'newsletter-optin-box' ),
			'bodyPlaceholder'                       => __( 'Start writing or type / to choose a block', 'newsletter-optin-box' ),
			'autosaveInterval'                      => AUTOSAVE_INTERVAL,
			'richEditingEnabled'                    => user_can_richedit(),
			'postLock'                              => $lock_details,
			'postLockUtils'                         => array(
				'nonce'       => wp_create_nonce( 'lock-post_' . $post->ID ),
				'unlockNonce' => wp_create_nonce( 'update-post_' . $post->ID ),
				'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			),
			'supportsLayout'                        => false,
			'supportsTemplateMode'                  => false,
			'enableCustomFields'                    => false,
			'__experimentalAdditionalBlockPatterns' => array(),
			'__experimentalBlockPatterns'           => array(),
		);

		$autosave = wp_get_post_autosave( $post->ID );
		if ( $autosave ) {
			if ( mysql2date( 'U', $autosave->post_modified_gmt, false ) > mysql2date( 'U', $post->post_modified_gmt, false ) ) {
				$editor_settings['autosave'] = array(
					'editLink' => get_edit_post_link( $autosave->ID ),
				);
			} else {
				wp_delete_post_revision( $autosave->ID );
			}
		}

		// Media.
		self::setup_media( $post->ID );

		// TinyMCE.
		wp_enqueue_editor();

		$init_script = <<<JS
		( function() {
			window._wpLoadBlockEditor = new Promise( function( resolve ) {
				wp.domReady( function() {
					resolve( noptin.editEmail.initializeEditor( 'noptin-email-campaigns__editor', "%s", %d, %s, %s ) );
				} );
			} );
		} )();
JS;

		$script = sprintf(
			$init_script,
			$post->post_type,
			$post->ID,
			wp_json_encode( $editor_settings ),
			wp_json_encode( $initial_edits )
		);
		wp_add_inline_script( 'noptin-email-editor', $script );

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
		$current_user = wp_get_current_user();
		$objects      = apply_filters( 'noptin_email_editor_objects', array() );
		$blocks       = array();

		foreach ( $edited_campaign->get_merge_tags() as $tag => $data ) {
			if ( ! empty( $data['block'] ) ) {
				$blocks[ $tag ] = array_merge(
					array(
						'description' => isset( $data['description'] ) ? $data['description'] : $data['label'],
						'mergeTag'    => $tag,
						'name'        => self::merge_tag_to_block_name( $tag ),
					),
					$data['block']
				);

				unset( $blocks[ $tag ]['metadata']['ancestor'] );
			}
		}

		foreach ( wp_list_pluck( $objects, 'merge_tags' ) as $merge_tags ) {
			foreach ( $merge_tags as $tag => $data ) {
				if ( ! empty( $data['block'] ) && ! isset( $blocks[ $tag ] ) ) {
					$blocks[ $tag ] = array_merge(
						array(
							'description' => isset( $data['description'] ) ? $data['description'] : $data['label'],
							'mergeTag'    => $tag,
							'name'        => self::merge_tag_to_block_name( $tag ),
						),
						$data['block']
					);
				}
			}
		}

		wp_localize_script(
			'noptin-blocks',
			'noptinEmailEditorSettings',
			array(
				'isTest'           => defined( 'NOPTIN_IS_TESTING' ),
				'styles'           => (object) $to_load,
				'settings'         => self::get_editor_settings(),
				'types'            => get_noptin_email_types(),
				'templates'        => get_noptin_email_templates(),
				'templateDefaults' => get_noptin_email_template_defaults(),
				'languages'        => noptin_get_available_languages(),
				'back'             => esc_url( $edited_campaign->get_base_url() ),
				'objects'          => (object) $objects,
				'context'          => $edited_campaign->get_contexts(),
				'dynamicBlocks'    => array_values( $blocks ),
				'user'             => array(
					'id'        => $current_user->ID,
					'email'     => $current_user->user_email,
					'canUpload' => current_user_can( 'upload_files' ),
				),
				'logo_url'         => noptin()->white_label->get( 'logo', noptin()->plugin_url . 'includes/assets/images/logo.png' ),
			)
		);

		// Add 'block-editor-page' to body class.
		add_filter( 'admin_body_class', array( __CLASS__, 'add_block_editor_body_class' ) );

		// Load block library translations.
		Main::load_script_translations( 'wp-block-library' );
	}

	public static function merge_tag_to_block_name( $merge_tag ) {

		// Remove the optional [[ and ]] from the merge tag.
		$merge_tag = trim( $merge_tag, '[]' );

		// Check if we have a prefix.
		if ( false === strpos( $merge_tag, '.' ) ) {
			$prefix = 'merge-tag';
			$field  = $merge_tag;
		} else {
			$prefix = strtok( $merge_tag, '.' );
			$field  = implode( '.', array_slice( explode( '.', $merge_tag ), 1 ) );
		}

		return self::sanitize_block_name( $prefix ) . '/' . self::sanitize_block_name( $field );
	}

	public static function sanitize_block_name( $block_name ) {
		return preg_replace( '/[^a-z0-9\-]/', '-', strtolower( $block_name ) );
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
						'theme' => self::get_editor_colors(),
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
	 * Set up Gutenberg editor colors
	 *
	 * @return Array
	 */
	private static function get_editor_colors() {
		return apply_filters(
			'noptin_editor_colors',
			array(
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
					'color' => '#666666',
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
			)
		);
	}

	/**
	 * Ensure media works in Gutenberg
	 *
	 * @return void
	 */
	public static function setup_media( $post_id ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';

		wp_enqueue_media(
			array(
				'post' => $post_id,
			)
		);
	}

	/**
	 * Fetches the current post object.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $edited_campaign The edited campaign.
	 *
	 * @return \WP_Post
	 */
	public static function get_post( $edited_campaign ) {

		if ( $edited_campaign->exists() ) {
			return get_post( $edited_campaign->id );
		}

		$email_type = \Hizzle\Noptin\Emails\Main::get_email_type( $edited_campaign->type );

		// Prepare post args.
		$defaults = apply_filters(
			'noptin_get_default_email_props',
			array(
				'block_css' => array(
					'footer-text' => ' #noptin-email-content .footer-text a { color: #111111 }',
				),
				'subject'          => $edited_campaign->get( 'subject' ),
				'recipients'       => 'automation' === $edited_campaign->type ? '[[email]]' : '',
				'content_visual'   => noptin_email_wrap_blocks( '', get_noptin_footer_text() ),
				'email_sender'     => $edited_campaign->get_sender(),
				'email_type'       => $edited_campaign->get_email_type(),
				'template'         => $edited_campaign->get_template(),
				'sends_after_unit' => $edited_campaign->get_sends_after_unit(),
				'footer_text'      => get_noptin_footer_text(),
			),
			$edited_campaign
		);

		if ( 'normal' !== $edited_campaign->get_email_type() ) {
			$defaults = array_merge(
				get_noptin_email_template_settings( 'noptin-visual', $edited_campaign ),
				$defaults
			);
		} else {
			$defaults = array_merge(
				get_noptin_email_template_settings( $edited_campaign->get_template(), $edited_campaign ),
				$defaults
			);
		}

		$args     = array(
			'post_type'    => 'noptin-campaign',
			'post_parent'  => $edited_campaign->parent_id,
			'post_title'   => empty( $defaults['name'] ) ? 'Auto Draft' : $defaults['name'],
			'post_status'  => 'auto-draft',
			'post_author'  => get_current_user_id(),
			'post_content' => isset( $defaults['content_visual'] ) ? $defaults['content_visual'] : '',
			'meta_input'   => array(
				'campaign_type' => $edited_campaign->type,
				'campaign_data' => array_merge(
					$defaults,
					$edited_campaign->options
				),
			),
		);

		unset( $args['meta_input']['campaign_data']['name'] );
		unset( $args['meta_input']['campaign_data']['content_visual'] );

		// Store subtype in a separate meta key.
		$args['meta_input'][ $edited_campaign->type . '_type' ] = $edited_campaign->get_sub_type();

		// Convert campaign data to an object. See: https://core.trac.wordpress.org/ticket/60314
		$args['meta_input']['campaign_data'] = (object) $args['meta_input']['campaign_data'];

		$args['meta_input'] = array_filter( $args['meta_input'] );

		// Maybe calculate menu order.
		if ( $email_type && $email_type->supports_menu_order ) {
			$count_args = array(
				'post_type'      => 'noptin-campaign',
				'post_parent'    => $edited_campaign->parent_id,
				'fields'         => 'ids',
				'meta_key'       => 'campaign_type',
				'meta_value'     => $edited_campaign->type,
				'posts_per_page' => -1,
				'post_status'    => 'any',
			);

			$args['menu_order'] = count( get_posts( $count_args ) ) + 1;
		}

		$post_id = wp_insert_post( $args, false, false );
		$post    = get_post( $post_id );

		wp_after_insert_post( $post, false, null );

		// Schedule auto-draft cleanup.
		if ( ! wp_next_scheduled( 'wp_scheduled_auto_draft_delete' ) ) {
			wp_schedule_event( time(), 'daily', 'wp_scheduled_auto_draft_delete' );
		}

		return $post;
	}
}
