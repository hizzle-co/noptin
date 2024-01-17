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
		add_action( 'save_post_noptin-campaign', array( __CLASS__, 'on_save_campaign' ), 10, 3 );

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

					// Abort if no id.
					if ( empty( $data_object->ID || 'auto-draft' === $data_object->post_status ) ) {
						return array();
					}

					if ( empty( $value ) ) {
						return;
					}

					$value = (array) $value;
					$rule  = noptin_get_automation_rule( empty( $value['id'] ) ? 0 : (int) $value['id'] );

					if ( is_wp_error( $rule ) ) {
						$rule = noptin_get_automation_rule( 0 );
					}

					$is_new = $rule->exists();
					if ( $is_new ) {
						$rule->set_action_id( 'email' );
						$rule->set_trigger_id( $value['trigger'] );
						$rule->set_action_settings( array() );
						$rule->set_trigger_settings( array() );
					}

					// Action settings.
					$old_settings = $rule->get_action_settings();
					if ( ! isset( $old_settings['automated_email_id'] ) || $old_settings['automated_email_id'] !== $data_object->ID ) {
						$rule->set_action_settings(
							array_merge(
								$old_settings,
								array(
									'automated_email_id' => $data_object->ID,
								)
							)
						);

						$is_new = true;
					}

					// Trigger settings.
					$rule->set_trigger_settings(
						array_merge(
							$rule->get_trigger_settings(),
							(array) $value['settings']
						)
					);

					// Save the rule.
					$rule->save();

					if ( ! $rule->exists() ) {
						return new \WP_Error( 'noptin_automation_rule', __( 'Failed to save automation rule.', 'newsletter-optin-box' ) );
					}

					if ( $is_new ) {
						$campaign_data = get_post_meta( $data_object->ID, 'campaign_data', true );
						$campaign_data = ! is_array( $campaign_data ) ? array() : $campaign_data;

						$campaign_data['automation_rule'] = $rule->get_id();
						update_post_meta( $data_object->ID, 'campaign_data', $campaign_data );
					}

					return $value;
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
	 * @param \WP_Post $post The post object.
	 * @param int $post_id The post id.
	 */
	public static function on_save_campaign( $post_id, $post ) {

		// Skip revisions.
		if ( 'revision' === $post->post_type ) {
			return;
		}

		$email = new Email( $post_id );

		// Abort if it does not exist.
		if ( ! $email->exists() ) {
			return;
		}

		// Fire hooks.
		do_action( 'noptin_' . $email->type . '_campaign_saved', $email );
		do_action( 'noptin_' . $email->get_sub_type() . '_campaign_saved', $email );
	}
}
