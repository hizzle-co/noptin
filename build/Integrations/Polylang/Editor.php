<?php

namespace Hizzle\Noptin\Integrations\Polylang;

defined( 'ABSPATH' ) || exit;

/** Bridges Polylang's native language metabox to Noptin's custom editors. */
class Editor {
	private static $campaign;

	public static function can_translate( $language ) {
		return $language && ( ! class_exists( '\WP_Syntex\Polylang\Capabilities\Capabilities' ) || \WP_Syntex\Polylang\Capabilities\Capabilities::get_user()->can_translate( $language ) );
	}

	public function __construct() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_fields' ) );
		add_filter( 'noptin_prepare_email_editor_post', array( __CLASS__, 'prepare_campaign' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'add_ajax_context' ), PHP_INT_MAX );
		add_action( 'admin_footer', array( __CLASS__, 'campaign_metabox' ), 5 );
		add_filter( 'noptin_is_allowed_email_editor_asset', array( __CLASS__, 'allow_assets' ), 10, 2 );
		add_filter( 'pll_get_new_post_translation_link', array( __CLASS__, 'translation_link' ), 10, 3 );
		add_filter( 'get_edit_post_link', array( __CLASS__, 'edit_link' ), 10, 2 );
		add_filter( 'pll_copy_post_metas', array( __CLASS__, 'copy_metas' ), 10, 5 );
		add_filter( 'pll_post_metas_to_export', array( __CLASS__, 'export_metas' ), 10, 2 );
		add_filter( 'noptin_campaign_row_meta', array( __CLASS__, 'campaign_language' ), 10, 2 );
		add_filter( 'rest_noptin-campaign_query', array( __CLASS__, 'campaign_query' ), 100, 2 );
	}

	/** The custom campaign browser lists every language unless explicitly filtered. */
	public static function campaign_query( $args, $request ) {
		if ( ! $request->get_param( 'lang' ) ) {
			$args['lang'] = 'all';
		}
		return $args;
	}

	/** Add the post context after Polylang has defined its inline configuration. */
	public static function add_ajax_context() {
		if ( self::$campaign && wp_script_is( 'pll_admin', 'enqueued' ) ) {
			wp_add_inline_script( 'pll_admin', sprintf( 'pll_admin.ajax_filter.pll_post_id = %d;', self::$campaign->ID ), 'before' );
		}
	}

	/** Only allow this integration's native assets into the campaign editor. */
	public static function allow_assets( $allowed, $handle ) {
		return $allowed || in_array( $handle, array( 'pll_admin', 'pll_classic-editor', 'pll_metabox_button', 'polylang_admin', 'polylang_dialog', 'polylang-pro' ), true );
	}

	public static function register_fields() {
		register_rest_field(
			array( 'noptin-form', 'noptin-campaign' ),
			'noptin_polylang',
			array(
				'get_callback'    => static function ( $post ) {
					$language = pll_get_post_language( $post['id'] );
					$translations = pll_get_post_translations( $post['id'] );
					unset( $translations[ $language ] );
					return array(
						'language'     => $language ? $language : '',
						'translations' => (object) $translations,
					);
				},
				'update_callback' => array( __CLASS__, 'save_language' ),
				'schema'          => array(
					'type'       => 'object',
					'context'    => array( 'edit' ),
					'properties' => array(
						'language'     => array( 'type' => 'string' ),
						'translations' => array(
							'type'                 => 'object',
							'additionalProperties' => array(
								'type'    => 'integer',
								'minimum' => 0,
							),
						),
					),
				),
			)
		);
	}

	/** Validate every linked record before modifying a translation group. */
	public static function save_language( $value, $post ) {
		$language = PLL()->model->get_language( $value['language'] ?? '' );
		if ( ! current_user_can( 'edit_post', $post->ID ) || ! $language ) {
			return new \WP_Error( 'noptin_invalid_language', __( 'Choose a valid language.', 'newsletter-optin-box' ), array( 'status' => 400 ) );
		}
		if ( ! self::can_translate( $language ) ) {
			return new \WP_Error( 'noptin_language_forbidden', __( 'You cannot edit translations in this language.', 'newsletter-optin-box' ), array( 'status' => 403 ) );
		}
		$translations = (array) ( $value['translations'] ?? array() );
		foreach ( $translations as $slug => $id ) {
			if ( $id && ( get_post_type( $id ) !== $post->post_type || ! current_user_can( 'edit_post', $id ) || ( (int) $id !== $post->ID && pll_get_post_language( $id ) !== $slug ) ) ) {
				return new \WP_Error( 'noptin_invalid_translation', __( 'Choose an editable translation in the matching language.', 'newsletter-optin-box' ), array( 'status' => 400 ) );
			}
		}
		pll_set_post_language( $post->ID, $language->slug );
		PLL()->model->post->save_translations( $post->ID, array_filter( $translations ) );
		return true;
	}

	public static function copy_metas( $metas, $sync, $from, $to, $lang ) {
		if ( 'noptin-form' === get_post_type( $from ) ) {
			$metas = array_merge( $metas, array( '_noptin_state', '_noptin_optin_type' ) );
		} elseif ( 'noptin-campaign' === get_post_type( $from ) && ! $sync ) {
			$metas = array_merge( $metas, array( 'campaign_type', 'automation_type', 'campaign_data' ) );
		}
		return array_unique( $metas );
	}

	/** Translate reader-facing text; retain field identifiers, values, and design settings. */
	public static function export_metas( $metas, $from ) {
		if ( 'noptin-form' === get_post_type( $from ) ) {
			$metas['_noptin_state'] = array_fill_keys( array( 'title', 'prefix', 'description', 'note', 'noptinButtonLabel', 'gdprConsentText' ), 1 );
			foreach ( array_keys( get_default_noptin_form_messages() ) as $message ) {
				$metas['_noptin_state'][ $message . 'Message' ] = 1;
			}
			$metas['_noptin_state']['fields'] = array(
				'*' => array(
					'label' => 1,
					'type'  => array( 'label' => 1 ),
				),
			);
		} elseif ( 'noptin-campaign' === get_post_type( $from ) ) {
			$metas['campaign_data'] = array_fill_keys( array( 'subject', 'preview_text', 'footer_text', 'content_normal', 'content_plain_text', 'content_raw_html' ), 1 );
		}
		return $metas;
	}

	/** Keep translation creation inside the campaign editor, always as a draft. */
	public static function translation_link( $link, $language, $post_id ) {
		if ( 'noptin-campaign' !== get_post_type( $post_id ) ) {
			return $link;
		}
		$email = new \Hizzle\Noptin\Emails\Email( $post_id );
		return add_query_arg(
			array(
				'noptin_campaign' => 0,
				'from_post'       => $post_id,
				'new_lang'        => $language->slug,
				'_wpnonce'        => wp_create_nonce( 'new-post-translation' ),
			),
			$email->get_base_url()
		);
	}

	public static function edit_link( $link, $post_id ) {
		if ( 'noptin-campaign' === get_post_type( $post_id ) ) {
			return ( new \Hizzle\Noptin\Emails\Email( $post_id ) )->get_edit_url();
		}
		return $link;
	}

	public static function prepare_campaign( $post ) {
		$source = absint( $_GET['from_post'] ?? 0 );
		$lang   = sanitize_key( $_GET['new_lang'] ?? '' );
		if ( 'auto-draft' === $post->post_status && $source && $lang && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'new-post-translation' ) && current_user_can( 'edit_post', $source ) && 'noptin-campaign' === get_post_type( $source ) && self::can_translate( PLL()->model->get_language( $lang ) ) ) {
			$original = get_post( $source );
			foreach ( array( 'campaign_type', 'automation_type', 'campaign_data' ) as $key ) {
				if ( metadata_exists( 'post', $source, $key ) ) {
					update_post_meta( $post->ID, $key, get_post_meta( $source, $key, true ) );
				}
			}
			wp_update_post(
				array(
					'ID'           => $post->ID,
					'post_title'   => $original->post_title,
					'post_content' => $original->post_content,
					'post_status'  => 'draft',
				)
			);
			$post->post_title   = $original->post_title;
			$post->post_content = $original->post_content;
			$post->post_status  = 'draft';
			pll_set_post_language( $post->ID, $lang );
			$translations          = pll_get_post_translations( $source );
			$translations[ $lang ] = $post->ID;
			pll_save_post_translations( $translations );
			// Run Polylang Pro's normal duplication/machine-translation preparation.
			$previous_context   = array_intersect_key( $GLOBALS, array_flip( array( 'post', 'post_type', 'pagenow' ) ) );
			$previous_post_type = $_GET['post_type'] ?? null;
			try {
				$_GET['post_type']    = 'noptin-campaign';
				$GLOBALS['post']      = $post;
				$GLOBALS['post_type'] = 'noptin-campaign';
				$GLOBALS['pagenow']   = 'post-new.php';
				apply_filters( 'use_block_editor_for_post', false, $post );
				$post = $GLOBALS['post'];
			} finally {
				if ( null === $previous_post_type ) {
					unset( $_GET['post_type'] );
				} else {
					$_GET['post_type'] = $previous_post_type;
				}
				foreach ( array( 'post', 'post_type', 'pagenow' ) as $key ) {
					if ( array_key_exists( $key, $previous_context ) ) {
						$GLOBALS[ $key ] = $previous_context[ $key ];
					} else {
						unset( $GLOBALS[ $key ] );
					}
				}
			}
			// A translated campaign starts unsent and targets its own language.
			$options = (array) get_post_meta( $post->ID, 'campaign_data', true );
			if ( 'noptin' === ( $options['email_sender'] ?? '' ) ) {
				$options['noptin_subscriber_options']             = (array) ( $options['noptin_subscriber_options'] ?? array() );
				$options['noptin_subscriber_options']['language'] = PLL()->model->get_language( $lang )->locale;
				unset( $options['noptin_subscriber_options']['language_not'] );
			} elseif ( 'wp_users' === ( $options['email_sender'] ?? '' ) ) {
				$options['wp_users_options']                = (array) ( $options['wp_users_options'] ?? array() );
				$options['wp_users_options']['user_locale'] = PLL()->model->get_language( $lang )->locale;
			}
			update_post_meta( $post->ID, 'campaign_data', (object) $options );
			wp_update_post(
				array(
					'ID'          => $post->ID,
					'post_status' => 'draft',
				)
			);
			$post->post_status = 'draft';
		}
		self::$campaign = $post;
		if ( ! pll_get_post_language( $post->ID ) ) {
			pll_set_post_language( $post->ID, pll_default_language() );
		}
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script( 'pll_classic-editor', plugins_url( '/js/build/classic-editor' . $suffix . '.js', POLYLANG_ROOT_FILE ), array( 'pll_admin', 'jquery', 'wp-ajax-response', 'post', 'jquery-ui-dialog', 'wp-i18n' ), POLYLANG_VERSION, true );
		wp_set_script_translations( 'pll_classic-editor', 'polylang' );
		if ( class_exists( '\PLL_Metabox_Button' ) ) {
			wp_enqueue_script( 'pll_metabox_button', plugins_url( '/js/build/metabox-button' . $suffix . '.js', POLYLANG_ROOT_FILE ), array( 'jquery', 'wp-ajax-response', 'post' ), POLYLANG_VERSION, true );
			wp_localize_script( 'pll_metabox_button', 'pll_sync_post', array( 'confirm_text' => __( 'You are about to overwrite an existing translation. Are you sure you want to proceed?', 'polylang-pro' ) ) );
			wp_enqueue_style( 'pll_metabox_button', plugins_url( '/css/build/metabox-button' . $suffix . '.css', POLYLANG_ROOT_FILE ), array(), POLYLANG_VERSION );
		}
		return $post;
	}

	/** Output before footer scripts initialize Polylang's native handlers. */
	public static function campaign_metabox() {
		if ( ! self::$campaign || empty( PLL()->classic_editor ) ) {
			return;
		}
		$post = self::$campaign;
		echo '<div id="noptin-polylang-source" hidden><div id="ml_box"><div class="inside">';
		printf( '<input type="hidden" id="post_ID" value="%d"><input type="hidden" id="post_type" value="noptin-campaign">', $post->ID );
		$previous_post = $GLOBALS['post'] ?? null;
		try {
			$GLOBALS['post'] = $post;
			PLL()->classic_editor->post_language( $post );
		} finally {
			$GLOBALS['post'] = $previous_post;
		}
		echo '</div></div></div>';
	}

	public static function campaign_language( $meta, $email ) {
		$language         = pll_get_post_language( $email->id, 'name' );
		$meta['language'] = array(
			'label' => __( 'Language', 'newsletter-optin-box' ),
			'value' => $language ? $language : __( 'Not set', 'newsletter-optin-box' ),
		);
		return $meta;
	}
}
