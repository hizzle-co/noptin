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
	 * @var bool Whether the form scripts have been enqueued.
	 */
	public static $scripts_loaded = false;

	/**
	 * Inits the main emails class.
	 *
	 */
	public static function init() {

		// Load modules.
		self::$listener = new Listener();
		Widgets\Main::init();
		Popups::init();

		// Adds forms before and after post content.
		Content_Embedder::init();

		// Renders forms.
		Renderer::init();

		// Previewer.
		Previewer::init();

		if ( is_admin() ) {
			Admin\Main::init();
		}

		// Register hooks.
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'disable_block_editor_for_forms' ), 10, 2 );
		add_action( 'register_noptin_form_post_type', array( __CLASS__, 'register_post_meta' ) );
		add_action( 'init', array( __CLASS__, 'register_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
		add_filter( 'noptin_load_form_scripts', array( __CLASS__, 'should_enqueue_scripts' ), 20 );
		add_action( 'init', array( __CLASS__, 'register_blocks' ) );
	}

	/**
	 * Register our form post type.
	 */
	public static function register_post_type() {

		if ( ! is_blog_installed() || post_type_exists( 'noptin-form' ) ) {
			return;
		}

		/**
		 * Fires before the newsletter form post type is registered.
		 *
		 * @since 1.6.2
		 */
		do_action( 'before_register_noptin_form_post_type' );

		// Register post type.
		register_post_type(
			'noptin-form',
			apply_filters(
				'noptin_optin_form_post_type_details',
				array(
					'labels'              => array(
						'name'               => _x( 'Subscription Forms', 'Post type general name', 'newsletter-optin-box' ),
						'singular_name'      => _x( 'Subscription Form', 'Post type singular name', 'newsletter-optin-box' ),
						'menu_name'          => _x( 'Subscription Forms', 'Admin Menu text', 'newsletter-optin-box' ),
						'name_admin_bar'     => _x( 'Subscription Form', 'Add New on Toolbar', 'newsletter-optin-box' ),
						'add_new'            => __( 'Add New', 'newsletter-optin-box' ),
						'add_new_item'       => __( 'Add New Form', 'newsletter-optin-box' ),
						'new_item'           => __( 'New Form', 'newsletter-optin-box' ),
						'edit_item'          => __( 'Edit Form', 'newsletter-optin-box' ),
						'view_item'          => __( 'View Form', 'newsletter-optin-box' ),
						'search_items'       => __( 'Search Forms', 'newsletter-optin-box' ),
						'parent_item_colon'  => __( 'Parent Forms:', 'newsletter-optin-box' ),
						'not_found'          => __( 'No forms found.', 'newsletter-optin-box' ),
						'not_found_in_trash' => __( 'No forms found in Trash.', 'newsletter-optin-box' ),
					),
					'label'               => __( 'Subscription Forms', 'newsletter-optin-box' ),
					'description'         => '',
					'public'              => false,
					'show_ui'             => true,
					'map_meta_cap'        => true,
					'publicly_queryable'  => false,
					'exclude_from_search' => true,
					'hierarchical'        => false,
					'query_var'           => false,
					'supports'            => array( 'title', 'custom-fields' ),
					'has_archive'         => false,
					'show_in_nav_menus'   => false,
					'show_in_rest'        => true,
					'show_in_menu'        => false,
					'menu_icon'           => '',
					'can_export'          => false,
				)
			)
		);

		/**
		 * Fires after the newsletter form post type is registered.
		 *
		 * @since 1.6.2
		 */
		do_action( 'register_noptin_form_post_type' );
	}

	/**
	 * Disables the block editor for forms.
	 *
	 * @param bool $use_block_editor Whether to use the block editor.
	 * @param string $post_type The post type being edited.
	 * @return bool
	 */
	public static function disable_block_editor_for_forms( $use_block_editor, $post_type ) {
		if ( 'noptin-form' === $post_type ) {
			return false;
		}

		return $use_block_editor;
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
	 * Register scripts
	 */
	public static function register_scripts() {
		$config = include plugin_dir_path( __FILE__ ) . '/assets/js/form.asset.php';

		wp_register_script(
			'noptin-form',
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

		if ( function_exists( 'disable_wp_rest_api' ) ) {
			$params['resturl'] = $params['ajaxurl'];
		}

		$params = apply_filters( 'noptin_form_scripts_params', $params );

		wp_localize_script( 'noptin-form', 'noptinParams', $params );

		wp_register_style(
			'noptin-form',
			plugin_dir_url( __FILE__ ) . 'assets/css/style-form.css',
			array(),
			$config['version']
		);
	}

	/**
	 * Checks if we should enqueue scripts and styles.
	 */
	public static function should_enqueue_scripts( $should_enqueue ) {
		global $post;

		// Check if we're in the admin area.
		if ( $should_enqueue || is_admin() ) {
			return $should_enqueue;
		}

		if ( defined( 'IS_NOPTIN_PREVIEW' ) && IS_NOPTIN_PREVIEW ) {
			return true;
		}

		if ( is_a( $post, 'WP_Post' ) && ( has_shortcode( $post->post_content, 'noptin' ) || has_shortcode( $post->post_content, 'noptin-form' ) ) ) {
			return true;
		}

		if ( is_active_widget( false, false, 'noptin_widget_premade', true ) ) {
			return true;
		}

		if ( is_active_widget( false, false, 'noptin_widget', true ) ) {
			return true;
		}

		return $should_enqueue;
	}

	/**
	 * Enqueue scripts
	 */
	public static function enqueue_scripts() {
		if ( apply_filters( 'noptin_load_form_scripts', false ) && ! self::$scripts_loaded ) {
			wp_enqueue_script( 'noptin-form' );
			wp_enqueue_style( 'noptin-form' );
			self::$scripts_loaded = true;
		}
	}

	/**
	 * Register blocks
	 */
	public static function register_blocks() {

		// Bail if register_block_type does not exist (available since WP 5.0)
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		do_action( 'before_register_noptin_form_block_type' );

		// Allows users to create forms on the fly.
		register_block_type( plugin_dir_path( __FILE__ ) . '/assets/new-form-block' );

		// Allows users to use existing forms.
		register_block_type( plugin_dir_path( __FILE__ ) . '/assets/block' );

		do_action( 'register_noptin_form_block_type' );
	}
}
