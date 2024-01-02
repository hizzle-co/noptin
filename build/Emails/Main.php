<?php

/**
 * Main emails class.
 *
 * @since   2.2.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Emails;

defined( 'ABSPATH' ) || exit;

/**
 * Main emails class.
 */
class Main {

	/**
     * @var Type[] The email types.
     */
	private static $types = array();

	/**
	 * Inits the main emails class.
	 *
	 */
	public static function init() {

		// Register post types.
		add_action( 'init', array( __CLASS__, 'register_post_types' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_fields' ) );

		// Register email types.
		self::register_email_types();

		// Email preview.
		Preview::init();

		if ( is_admin() ) {
			Admin\Main::init();
		}
	}

	/**
	 * Register rest fields.
	 */
	public static function register_rest_fields() {

		register_rest_field(
            'noptin-campaign',
            'noptin_campaign_type',
            array(
				'get_callback' => function ( $request ) {

					// Abort if no id.
					if ( empty( $request['id'] ) ) {
						return array();
					}

					$email = new Email( $request['id'] );

					// Abort if email is not found.
					if ( ! $email->exists() ) {
						return array();
					}

					return $email->get_js_data();

					return Main::get_email_type( get_post_meta( $request['id'], 'campaign_type', true ) );
				},
				'schema'       => array(
					'type'                 => 'object',
					'description'          => 'The email campaign type info.',
					'properties'           => array(
						'type'  => array(
							'type' => 'string',
						),
						'label' => array(
							'type' => 'string',
						),
					),
					'additionalProperties' => true,
				),
            )
        );
	}

	/**
	 * Register post types
	 */
	public static function register_post_types() {

		if ( ! is_blog_installed() || post_type_exists( 'noptin-campaign' ) ) {
			return;
		}

		/**
		 * Fires before custom post types are registered
		 *
		 * @since 1.0.0
		*/
		do_action( 'noptin_register_post_type' );

		// Email campaign.
		register_post_type(
			'noptin-campaign',
			apply_filters(
				'noptin_email_campaigns_post_type_details',
				array(
					'labels'                => array(),
					'label'                 => __( 'Email Campaigns', 'newsletter-optin-box' ),
					'description'           => '',
					'public'                => true,
					'rest_controller_class' => '\Hizzle\Noptin\Emails\REST',
					'map_meta_cap'          => true,
					'capabilities'          => array(
						'read'      => 'edit_posts',
						'read_post' => 'edit_post',
					),
					'exclude_from_search'   => true,
					'publicly_queryable'    => true,
					'show_ui'               => false,
					'show_in_menu'          => false,
					'hierarchical'          => false,
					'query_var'             => false,
					'supports'              => array( 'author', 'revisions', 'title', 'editor', 'excerpt', 'custom-fields' ),
					'has_archive'           => false,
					'show_in_rest'          => true,
					'menu_icon'             => '',
				)
			)
		);

		register_post_meta(
			'noptin-campaign',
			'campaign_type',
			array(
				'single'            => true,
				'type'              => 'string',
				'default'           => 'newsletter',
				'show_in_rest'      => true,
				'revisions_enabled' => true,
				'auth_callback'     => function ( $allowed, $meta_key, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
			)
		);

		register_post_meta(
			'noptin-campaign',
			'campaign_data',
			array(
				'single'            => true,
				'type'              => 'object',
				'default'           => (object) array(),
				'show_in_rest'      => array(
					'schema' => array(
						'type'                 => 'object',
						'properties'           => array(
							'email_sender' => array(
								'type' => 'string',
							),
							'subject'      => array(
								'type' => 'string',
							),
						),
						'additionalProperties' => true,
					),
				),
				'revisions_enabled' => true,
				'auth_callback'     => function ( $allowed, $meta_key, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
			)
		);

		foreach ( self::$types as $type ) {
			register_post_meta(
				'noptin-campaign',
				$type->type . '_type',
				array(
					'single'            => true,
					'type'              => 'string',
					'show_in_rest'      => true,
					'revisions_enabled' => true,
					'auth_callback'     => function ( $allowed, $meta_key, $post_id ) {
						return current_user_can( 'edit_post', $post_id );
					},
				)
			);
		}

		/**
		 * Fires after custom post types are registered
		 *
		 * @since 1.0.0
		*/
		do_action( 'noptin_after_register_post_type' );
	}

	/**
	 * Registers email types.
	 *
	 */
	private static function register_email_types() {

		// Newsletter emails.
		self::register_email_type(
			array(
				'type'               => 'newsletter',
				'plural'             => 'newsletters',
				'label'              => __( 'Newsletter', 'newsletter-optin-box' ),
				'plural_label'       => __( 'Newsletters', 'newsletter-optin-box' ),
				'new_campaign_label' => __( 'New Campaign', 'newsletter-optin-box' ),
				'click_to_add_first' => __( 'Click the button below to send your first newsletter campaign', 'newsletter-optin-box' ),
				'is_mass_mail'       => true,
			)
		);

		// Automation emails.
		self::register_email_type(
			array(
				'type'               => 'automation',
				'plural'             => 'automations',
				'label'              => __( 'Automated Email', 'newsletter-optin-box' ),
				'plural_label'       => __( 'Automated Emails', 'newsletter-optin-box' ),
				'new_campaign_label' => __( 'New Automated Email', 'newsletter-optin-box' ),
				'click_to_add_first' => __( 'Click the button below to set-up your first automated email', 'newsletter-optin-box' ),
				'supports_timing'    => true,
			)
		);
	}

	/**
	 * Registers a single email type.
	 *
	 * @param array $args The email type args.
	 */
	public static function register_email_type( $args ) {
		$type = new Type( $args );

		self::$types[ $type->type ] = $type;
	}

	/**
	 * Returns an email type.
	 *
	 * @param string $type The email type.
	 * @return Type|false
	 */
	public static function get_email_type( $type ) {

		// Abort if email type is empty.
		if ( empty( $type ) ) {
			return false;
		}

		return isset( self::$types[ $type ] ) ? self::$types[ $type ] : false;
	}

	/**
	 * Returns an email type by its plural name.
	 *
	 * @param string $plural The email type plural name.
	 * @return Type|false
	 */
	public static function get_email_type_by_plural( $plural ) {
		return current(
			wp_list_filter( self::$types, array( 'plural' => $plural ) )
		);
	}

	/**
	 * Returns the default email type.
	 *
	 * @return string|false
	 */
	public static function get_default_email_type() {
		return current( array_keys( self::$types ) );
	}

	/**
	 * Returns all email types.
	 *
	 * @return Type[]
	 */
	public static function get_email_types() {
		return self::$types;
	}
}

// a:2:{s:12:"email_sender";s:6:"noptin";s:7:"subject";s:0:"";}
