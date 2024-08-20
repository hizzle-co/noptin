<?php

/**
 * Main forms class.
 *
 * @since   2.2.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Forms;

defined( 'ABSPATH' ) || exit;

/**
 * Main forms class.
 */
class Main {

	/**
	 * Inits the main emails class.
	 *
	 */
	public static function init() {

		if ( is_admin() ) {
			Admin\Main::init();
		}

		add_action( 'register_noptin_form_post_type', array( __CLASS__, 'register_post_meta' ) );
	}

	/**
	 * Register post meta
	 */
	public static function register_post_meta() {

		// Form type.
		register_post_meta(
			'noptin-form',
			'_noptin_optin_type',
			array(
				'single'        => true,
				'type'          => 'string',
				'default'       => 'inpost',
				'show_in_rest'  => true,
				'auth_callback' => function ( $allowed, $meta_key, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
			)
		);

		// Subscribers count.
		register_post_meta(
			'noptin-form',
			'_noptin_subscribers_count',
			array(
				'single'        => true,
				'type'          => 'integer',
				'default'       => 0,
				'show_in_rest'  => true,
				'auth_callback' => function ( $allowed, $meta_key, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
			)
		);

		// Form views.
		register_post_meta(
			'noptin-form',
			'_noptin_form_views',
			array(
				'single'        => true,
				'type'          => 'integer',
				'default'       => 0,
				'show_in_rest'  => true,
				'auth_callback' => function ( $allowed, $meta_key, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
			)
		);

		// Campaign data.
		register_post_meta(
			'noptin-form',
			'_noptin_state',
			array(
				'single'            => true,
				'type'              => 'object',
				'default'           => (object) array(),
				'show_in_rest'      => array(
					'schema' => array(
						'type'                 => 'object',
						'properties'           => array(
							'fields' => array(
								'type' => 'array',
							),
							'image'  => array(
								'type' => 'string',
							),
						),
						'additionalProperties' => true,
					),
				),
				//'revisions_enabled' => true,
				'auth_callback'     => function ( $allowed, $meta_key, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
			)
		);
	}
}
