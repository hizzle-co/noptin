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
	 * @var Listener Form submissions listener.
	 */
	public static $listener;

	/**
	 * Inits the main emails class.
	 *
	 */
	public static function init() {

		// Load modules.
		self::$listener = new Listener();

		if ( is_admin() ) {
			Admin\Main::init();
		}

		// Register hooks.
		add_action( 'register_noptin_form_post_type', array( __CLASS__, 'register_post_meta' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_action( 'init', array( __CLASS__, 'register_blocks' ) );
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

		if ( did_action( 'noptin_full_install' ) ) {
			self::create_default_forms();
		}
	}

	/**
	 * Create default forms
	 */
	public static function create_default_forms() {
		// Create default subscribe form.
		$count_forms = wp_count_posts( 'noptin-form' );

		if ( 0 < array_sum( (array) $count_forms ) ) {
			return;
		}

		$new_form = new \Noptin_Form_Legacy(
			array(
				'optinName' => __( 'Newsletter Subscription Form', 'newsletter-optin-box' ),
			)
		);

		$new_form->save();
	}

	/**
	 * Enqueue scripts
	 */
	public static function enqueue_scripts() {
		$config = include plugin_dir_path( __FILE__ ) . '/assets/js/form.asset.php';

		wp_enqueue_script(
			'noptin-form__new',
			plugin_dir_url( __FILE__ ) . 'assets/js/form.js',
			$config['dependencies'],
			$config['version'],
			true
		);

		// Scripts params.
		$params = array(
			'ajaxurl'     => admin_url( 'admin-ajax.php' ),
			'resturl'     => esc_url_raw( rest_url( 'noptin/v1/form' ) ),
			'nonce'       => wp_create_nonce( 'noptin_subscription_nonce' ),
			'cookie'      => get_noptin_option( 'subscribers_cookie' ),
			'connect_err' => __( 'Could not establish a connection to the server.', 'newsletter-optin-box' ),
			'cookie_path' => COOKIEPATH,
		);
		$params = apply_filters( 'noptin_form_scripts_params', $params );

		wp_localize_script( 'noptin-form__new', 'noptinParams', $params );

		wp_enqueue_style(
			'noptin-form__new',
			plugin_dir_url( __FILE__ ) . 'assets/css/style-form.css',
			array(),
			$config['version']
		);
	}

	/**
     * Register blocks
     */
    public static function register_blocks() {

        // Bail if register_block_type does not exist (available since WP 5.0)
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

        // Allows users to create forms on the fly.
        register_block_type( plugin_dir_path( __FILE__ ) . '/assets/new-form-block' );

        // Allows users to use existing forms.
        register_block_type( plugin_dir_path( __FILE__ ) . '/assets/block' );
    }
}
