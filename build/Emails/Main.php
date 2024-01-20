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

		// Fire hooks.
		add_action( 'wp_after_insert_post', array( __CLASS__, 'on_save_campaign' ), 100, 4 );
		add_action( 'before_delete_post', array( __CLASS__, 'on_delete_campaign' ) );

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

		// Campaign type.
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

		// Automation rule.
		register_rest_field(
			'noptin-campaign',
			'noptin_automation_rule',
			array(
				'get_callback'    => function ( $request ) {

					// Abort if no id.
					if ( empty( $request['id'] ) ) {
						return array();
					}

					$email = new Email( $request['id'] );

					// Abort if email is not found.
					if ( ! $email->is_automation_rule() ) {
						return array();
					}

					$rule = noptin_get_automation_rule( (int) $email->get( 'automation_rule' ) );

					if ( is_wp_error( $rule ) ) {
						$rule = noptin_get_automation_rule( 0 );
					}

					if ( ! $rule->exists() ) {
						$rule->set_action_id( 'email' );
						$rule->set_trigger_id( $email->get_trigger() );
						$rule->set_action_settings( array() );
						$rule->set_trigger_settings( array() );
					}

					// Fetch the trigger.
					$trigger = $rule->get_trigger();
					if ( empty( $trigger ) ) {
						return array(
							'error' => __( 'Your website does not support that trigger.', 'newsletter-optin-box' ),
						);
					}

					// Normal settings.
					$trigger_settings = $trigger->get_settings();

					// Send to inactive subscribers.
					if ( 'new_subscriber' !== $rule->get_trigger_id() ) {
						$trigger_settings['send_email_to_inactive'] = array(
							'label'   => __( 'Also send to unsubscribed contacts', 'newsletter-optin-box' ),
							'el'      => 'input',
							'type'    => 'checkbox',
							'default' => false,
						);
					}

					// Conditional logic.
					$trigger_settings['conditional_logic'] = array(
						'label'       => __( 'Conditional Logic', 'newsletter-optin-box' ),
						'el'          => 'conditional_logic',
						'comparisons' => noptin_get_conditional_logic_comparisons(),
						'toggle_text' => __( 'Optional. Send this email only if certain conditions are met.', 'newsletter-optin-box' ),
						'fullWidth'   => true,
						'in_modal'    => true,
						'default'     => array(
							'enabled' => false,
							'action'  => 'allow',
							'type'    => 'all',
							'rules'   => array(),
						),
					);

					// Heading.
					$trigger_settings = array_merge(
						array(
							'heading' => array(
								'content' => sprintf(
									/* translators: %s: Trigger description. */
									__( 'Noptin will send this email %s', 'newsletter-optin-box' ),
									$trigger->get_description()
								),
								'el'      => 'paragraph',
							),
						),
						$trigger_settings
					);

					return array(
						'id'       => $rule->get_id(),
						'action'   => $rule->get_action_id(),
						'trigger'  => $rule->get_trigger_id(),
						'saved'    => (object) $rule->get_trigger_settings(),
						'settings' => $trigger_settings,
					);
				},
				'update_callback' => function ( $value, $data_object ) {

					if ( empty( $value ) ) {
						return array();
					}

					return \Noptin_Automation_Rule_Email::sync_campaign_to_rule(
						new \Hizzle\Noptin\Emails\Email( $data_object->ID ),
						(array) $value['saved']
					);
				},
				'schema'          => array(
					'type'                 => 'object',
					'description'          => 'The email campaign automation rule info.',
					'properties'           => array(
						'id'       => array(
							'type'        => 'number',
							'description' => 'The automation rule id.',
						),
						'action'   => array(
							'type'        => 'string',
							'description' => 'The automation rule action.',
						),
						'trigger'  => array(
							'type'        => 'string',
							'description' => 'The automation rule trigger.',
						),
						'saved'    => array(
							'type'        => 'object',
							'description' => 'The automation rule saved settings.',
						),
						'settings' => array(
							'type'        => 'object',
							'description' => 'The automation rule settings.',
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
					'labels'                => array(
						'name'                   => __( 'Email Campaigns', 'newsletter-optin-box' ),
						'singular_name'          => __( 'Email Campaign', 'newsletter-optin-box' ),
						'add_new'                => __( 'Add New Campaign', 'newsletter-optin-box' ),
						'add_new_item'           => __( 'Add New Campaign', 'newsletter-optin-box' ),
						'edit_item'              => __( 'Edit Campaign', 'newsletter-optin-box' ),
						'new_item'               => __( 'New Campaign', 'newsletter-optin-box' ),
						'view_item'              => __( 'Preview Campaign', 'newsletter-optin-box' ),
						'view_items'             => __( 'View Campaigns', 'newsletter-optin-box' ),
						'search_items'           => __( 'Search Campaigns', 'newsletter-optin-box' ),
						'insert_into_item'       => __( 'Insert into campaign', 'newsletter-optin-box' ),
						'uploaded_to_this_item'  => __( 'Uploaded to this campaign', 'newsletter-optin-box' ),
						'filter_items_list'      => __( 'Filter campaigns list', 'newsletter-optin-box' ),
						'items_list'             => __( 'Email campaigns list', 'newsletter-optin-box' ),
						'item_published'         => __( 'Email campaign published.', 'newsletter-optin-box' ),
						'item_reverted_to_draft' => __( 'Email campaign reverted to draft.', 'newsletter-optin-box' ),
						'item_trashed'           => __( 'Email campaign trashed.', 'newsletter-optin-box' ),
						'item_scheduled'         => __( 'Email campaign scheduled.', 'newsletter-optin-box' ),
						'item_updated'           => __( 'Email campaign updated.', 'newsletter-optin-box' ),
					),
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

	/**
	 * Checks if the current user can create a new email.
	 *
	 * @return bool
	 */
	public static function current_user_can_create_new_campaign() {
		$post_type = get_post_type_object( 'noptin-campaign' );

		if ( empty( $post_type ) ) {
			return false;
		}

		return current_user_can( $post_type->cap->edit_posts );
	}

	/**
	 * Fires relevant hooks after saving a campaign.
	 *
	 * @param int           $post_id     Post ID.
	 * @param \WP_Post      $post        Post object.
	 * @param bool          $update      Whether this is an existing post being updated.
	 * @param null|\WP_Post $post_before Null for new posts, the WP_Post object prior
	 *                                  to the update for updated posts.
	 */
	public static function on_save_campaign( $post_id, $post, $update, $post_before ) {

		$email = new Email( $post_id );

		// Abort if it does not exist.
		if ( ! $email->exists() || 'auto-draft' === $post->post_status ) {
			return;
		}

		// Fire saved hooks.
		self::fire_email_action_hook( 'saved', $email );

		// Fire published hooks.
		$new_status = $post->post_status;
		$old_status = $post_before ? $post_before->post_status : 'new';

		if ( 'publish' === $new_status && 'publish' !== $old_status ) {
			self::fire_email_action_hook( 'published', $email );
		}

		// Fire unpublished hooks.
		if ( 'publish' !== $new_status && 'publish' === $old_status ) {
			self::fire_email_action_hook( 'unpublished', $email );
		}
	}

	/**
	 * Fires relevant hooks before deleting a campaign.
	 *
	 * @param \WP_Post $post The post object.
	 * @param int $post_id The post id.
	 */
	public static function on_delete_campaign( $post_id ) {

		$email = new Email( $post_id );

		// Ensure email exists.
		if ( $email->exists() ) {
			self::fire_email_action_hook( 'deleted', $email );
		}
	}

	/**
	 * Fires an email action hook.
	 *
	 * @param string $action The action name.
	 * @param Email  $email  The email object.
	 */
	private static function fire_email_action_hook( $action, $email ) {

		$type     = $email->type;
		$sub_type = $email->get_sub_type();

		// Fire saved hooks.
		do_action( "noptin_{$type}_campaign_{$action}", $email );

		if ( ! empty( $sub_type ) ) {
			do_action( "noptin_{$type}_{$sub_type}_campaign_{$action}", $email );
		}
	}
}
