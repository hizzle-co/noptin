<?php

namespace Hizzle\Noptin\Integrations\Polylang;

defined( 'ABSPATH' ) || exit;

/** Bridges Polylang's native language metabox to Noptin's form editor. */
class Editor {
	public static function can_translate( $language ) {
		return $language && ( ! class_exists( '\WP_Syntex\Polylang\Capabilities\Capabilities' ) || \WP_Syntex\Polylang\Capabilities\Capabilities::get_user()->can_translate( $language ) );
	}

	public function __construct() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_fields' ) );
		add_filter( 'pll_copy_post_metas', array( __CLASS__, 'copy_metas' ), 10, 5 );
		add_filter( 'pll_post_metas_to_export', array( __CLASS__, 'export_metas' ), 10, 2 );
	}

	public static function register_fields() {
		register_rest_field(
			'noptin-form',
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
		}
		return $metas;
	}

}
