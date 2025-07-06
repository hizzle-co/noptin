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
	 * @var array The current email recipient.
	 */
	public static $current_email_recipient = array();

	/**
	 * @var Email The current email.
	 */
	public static $current_email = null;

	/**
	 * Inits the main emails class.
	 *
	 */
	public static function init() {

		// Load functions.
		require_once plugin_dir_path( __FILE__ ) . 'functions.php';

		// Register post types.
		add_action( 'init', array( __CLASS__, 'register_post_types' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_fields' ) );
		add_action( 'after_setup_theme', array( __CLASS__, 'register_email_types' ), 0 );

		// Fire hooks.
		add_action( 'noptin_before_send_email', array( __CLASS__, 'set_current_email' ), -10 );
		add_filter( 'noptin_get_last_send_date', array( __CLASS__, 'filter_last_send_date' ) );
		add_action( 'wp_after_insert_post', array( __CLASS__, 'on_save_campaign' ), 100, 4 );
		add_action( 'before_delete_post', array( __CLASS__, 'on_delete_campaign' ) );
		add_filter( 'rest_pre_insert_noptin-campaign', array( __CLASS__, 'filter_campaign_rest_request' ), 10, 2 );
		add_action( 'noptin_pre_load_actions_page', __NAMESPACE__ . '\Actions::init', 0 );

		// Add shortcode to display past newsletters.
		add_shortcode( 'past_noptin_newsletters', array( __CLASS__, 'past_newsletters' ) );

		// Email preview.
		Preview::init();

		// Templates.
		Templates::init();

		// Revenue.
		Revenue::init();

		// Activity logs.
		Logs\Main::init();

		// Admin.
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
					$description = $trigger->get_description();

					// Lowercase the first letter.
					$description = strtolower( $description[0] ) . substr( $description, 1 );
					$trigger_settings = array_merge(
						array(
							'heading' => array(
								'content' => sprintf(
									/* translators: %s: Trigger description. */
									__( 'Noptin will send this email %s', 'newsletter-optin-box' ),
									$description
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
						'view_item'              => __( 'Preview', 'newsletter-optin-box' ),
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
					'public'                => false,
					'rest_controller_class' => __NAMESPACE__ . '\REST',
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
					'rewrite'               => false,
				)
			)
		);

		register_post_meta(
			'noptin-campaign',
			'campaign_type',
			array(
				'single'        => true,
				'type'          => 'string',
				'default'       => 'newsletter',
				'show_in_rest'  => true,
				'auth_callback' => function ( $allowed, $meta_key, $post_id ) {
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
			if ( ! $type->supports_sub_types ) {
				continue;
			}

			register_post_meta(
				'noptin-campaign',
				$type->type . '_type',
				array(
					'single'        => true,
					'type'          => 'string',
					'show_in_rest'  => true,
					'auth_callback' => function ( $allowed, $meta_key, $post_id ) {
						return current_user_can( 'edit_post', $post_id );
					},
				)
			);
		}

		\Hizzle\Noptin\Objects\Store::add( new Records() );

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
	public static function register_email_types() {

		// Newsletter emails.
		self::register_email_type(
			array(
				'type'               => 'newsletter',
				'plural'             => 'newsletters',
				'label'              => __( 'Newsletter', 'newsletter-optin-box' ),
				'plural_label'       => __( 'Newsletters', 'newsletter-optin-box' ),
				'new_campaign_label' => __( 'New Campaign', 'newsletter-optin-box' ),
				'click_to_add_first' => __( 'Click the button below to send your first newsletter campaign', 'newsletter-optin-box' ),
				'defaults'           => array(
					'name' => sprintf(
						// Translators: %s is the current date.
						__( 'Newsletter - %s', 'newsletter-optin-box' ),
						date_i18n( get_option( 'date_format' ) )
					),
				)
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
				'supports_sub_types' => true,
			)
		);

		do_action( 'noptin_register_email_types' );

		if ( ! isset( self::$types['sequence'] ) ) {
			self::register_email_type(
				array(
					'type'         => 'sequence',
					'plural'       => 'sequences',
					'label'        => __( 'Sequence / Course', 'newsletter-optin-box' ),
					'plural_label' => __( 'Sequences / Courses', 'newsletter-optin-box' ),
					'icon'         => 'clock',
					'upsell'       => __( 'Set-up a series of emails to be sent at specific intervals one after another. Usefull for courses, welcome series, etc.', 'newsletter-optin-box' ),
				)
			);
		}

		self::register_email_type(
			array(
				'type'                => 'email_template',
				'plural'              => 'email_templates',
				'label'               => __( 'Template', 'newsletter-optin-box' ),
				'plural_label'        => __( 'Templates', 'newsletter-optin-box' ),
				'new_campaign_label'  => __( 'New Email Template', 'newsletter-optin-box' ),
				'click_to_add_first'  => __( 'Click the button below to set-up your first email template', 'newsletter-optin-box' ),
				'upsell'              => noptin_has_alk() ? false : __( 'Create your own reusable email templates.', 'newsletter-optin-box' ),
				'supports_recipients' => false,
				'supports_menu_order' => true,
				'icon'                => 'admin-page',
			)
		);

		// Trash emails.
		self::register_email_type(
			array(
				'type'         => 'trash',
				'plural'       => 'trash',
				'label'        => __( 'Trash', 'newsletter-optin-box' ),
				'plural_label' => __( 'Trash', 'newsletter-optin-box' ),
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

		// Delete the last error.
		delete_post_meta( $post_id, '_bulk_email_last_error' );

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
		global $wpdb;

		$email = new Email( $post_id );

		// Ensure email exists.
		if ( $email->exists() ) {

			// Fire deleted hooks.
			self::fire_email_action_hook( 'deleted', $email );

			// Delete related stats.
			delete_noptin_subscriber_meta_by_key( "_campaign_$post_id" );

			$wpdb->delete(
				$wpdb->usermeta,
				array(
					'meta_key' => "_campaign_$post_id",
				)
			);
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

	/**
	 * Inits the current email recipient.
	 *
	 * @param array $recipient The email recipient.
	 */
	public static function init_current_email_recipient( $recipient, $campaign = null ) {
		if ( ! empty( $recipient['email'] ) && is_email( $recipient['email'] ) ) {
			$GLOBALS['current_noptin_email'] = $recipient['email'];
		}

		self::$current_email_recipient = array_filter( $recipient );

		do_action( 'noptin_init_current_email_recipient', $campaign );
	}

	/**
	 * Retrieves the current unsubscribe url.
	 *
	 * @return string
	 */
	public static function get_current_unsubscribe_url() {
		return get_noptin_action_url( 'unsubscribe', noptin_encrypt( wp_json_encode( self::$current_email_recipient ) ) );
	}

	/**
	 * Retrieves the current view in browser url.
	 *
	 * @return string
	 */
	public static function get_current_view_in_browser_url() {
		global $noptin_current_objects;
		$recipient = self::$current_email_recipient;

		if ( is_array( $noptin_current_objects ) ) {
			foreach ( $noptin_current_objects as $collection => $obj ) {
				if ( ! isset( $recipient[ $collection ] ) ) {
					$recipient[ $collection ] = $obj->get( 'id' );
				}
			}
		}

		return get_noptin_action_url( 'view_in_browser', noptin_encrypt( wp_json_encode( $recipient ) ), true );
	}

	/**
	 * Deletes sent campaigns.
	 *
	 */
	public static function delete_old_emails() {

		$save_days = (int) get_noptin_option( 'delete_campaigns', 0 );
		if ( empty( $save_days ) ) {
			return;
		}

		$args = array(
			'posts_per_page' => -1,
			'post_type'      => 'noptin-campaign',
			'fields'         => 'ids',
			'date_query'     => array(
				'before' => "-$save_days days",
			),
			'meta_query'     => array(
				array(
					'key'   => 'completed',
					'value' => '1',
				),
				array(
					'key'   => 'campaign_type',
					'value' => 'newsletter',
				),
			),
		);

		foreach ( get_posts( $args ) as $post_id ) {
			wp_delete_post( $post_id, true );
		}
	}

	/**
	 * Sets the current email.
	 *
	 * @param Email $email The current email.
	 */
	public static function set_current_email( $email ) {
		self::$current_email = $email;
	}

	/**
	 * Filters the last send date.
	 *
	 * @param string $date The last send date.
	 * @return string
	 */
	public static function filter_last_send_date( $date ) {

		if ( ( Preview::$simulation || ! did_action( 'noptin_prepare_email_preview' ) ) && ! empty( self::$current_email ) ) {
			$last_date = self::$current_email->get_last_send();

			if ( ! empty( $last_date ) ) {
				return $last_date;
			}

			switch ( self::$current_email->get( 'frequency' ) ) {

				// Get posts published since last send, accounting for skipped days.
				case 'daily':
					$skip_days = wp_parse_id_list( self::$current_email->get( 'skip_days' ) );

					// Get current time in WordPress timezone
					$today = (int) gmdate( 'w' );

					// Calculate yesterday's weekday number (0-6).
					// $today - 1 would give us yesterday's day number.
					// + 7 is added to ensure we don't get negative numbers.
					// % 7 (modulo 7) wraps the number back into the 0-6 range
					// If today is Monday (1), then (1 - 1 + 7) % 7 = 0 (Sunday).
					// If today is Sunday (0), then (0 - 1 + 7) % 7 = 6 (Saturday).
					$yesterday = ( $today + 6 ) % 7;

					// If yesterday was a skip day, look back to the last non-skip day.
					if ( in_array( $yesterday, $skip_days, true ) ) {
						// Look back up to 7 days to find the last non-skip day.
						for ( $i = 2; $i <= 7; $i++ ) {
							$check_day = ( $today - $i + 7 ) % 7;
							if ( ! in_array( $check_day, $skip_days, true ) ) {
								return strtotime( "-$i days midnight" );
							}
						}

						return $date;
					}

					return strtotime( 'yesterday midnight' );

				// Get posts published in the last 7 days.
				case 'weekly':
					return strtotime( '-7 days' );

				// Get posts published in the last 30 days.
				case 'monthly':
					return strtotime( '-30 days' );

				// Get posts published in the last 365 days.
				case 'yearly':
					return strtotime( '-365 days' );

				// Get posts published last x days.
				case 'x_days':
					$days = self::$current_email->get( 'x_days' );
					if ( ! empty( $days ) ) {
						$days = absint( $days );
						return strtotime( "-$days days" );
					}
			}
		}

		return $date;
	}

	/**
	 * Displays past newsletters.
	 *
	 * @param array $atts The shortcode attributes.
	 * @return string
	 */
	public static function past_newsletters( $atts ) {
		$atts = shortcode_atts(
			array(
				'limit'        => 20,
				'with_parents' => 'no',
				'show'         => 'subject',
			),
			$atts
		);

		$args = array(
			'posts_per_page' => (int) $atts['limit'],
			'post_type'      => 'noptin-campaign',
			'meta_query'     => array(
				array(
					'key'     => 'paused',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'   => 'campaign_type',
					'value' => 'newsletter',
				),
			),
		);

		if ( 'no' === $atts['with_parents'] ) {
			$args['post_parent'] = 0;
		}

		$emails = get_posts( $args );

		if ( empty( $emails ) ) {
			return '';
		}

		ob_start();
		?>
		<style>
			.noptin-past-newsletters .noptin-past-newsletters__list {
				list-style-type: none;
				padding: 0;
				margin: 20px 0;
			}

			.noptin-past-newsletters .noptin-past-newsletters__list .noptin-past-newsletters__list-item {
				margin-bottom: 15px;
				padding-bottom: 15px;
				border-bottom: 1px solid #e0e0e0;
			}

			.noptin-past-newsletters a {
				font-weight: bold;
				display: block;
			}

			.noptin-past-newsletters .noptin-past-newsletters-list-item__date {
				color: #757575;
				font-size: 0.9em;
			}
		</style>
		<div class="noptin-past-newsletters">
			<ul class="noptin-past-newsletters__list">
				<?php foreach ( $emails as $email_id ) : ?>
					<?php
						$email = new Email( $email_id );
						$date  = date_i18n( get_option( 'date_format', 'F j, Y' ), strtotime( $email->created ) );
						$url   = $email->get_view_in_browser_url(
							array(
								'source' => 'past-newsletters',
							)
						);
						$title = $email->name;

					if ( empty( $title ) || 'title' !== $atts['show'] ) {
						$title = $email->get( 'subject' );
					}
					?>

					<li class="noptin-past-newsletters__list-item">
						<a href="<?php echo esc_url( $url ); ?>">
							<?php echo esc_html( $title ); ?>
						</a>
						<div class="noptin-past-newsletters-list-item__date">
							<?php echo esc_html( $date ); ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>

		<?php
		return ob_get_clean();
	}

	/**
	 * Filters the campaign rest request.
	 *
	 * @param object $prepared_post The prepared post object.
	 * @param \WP_REST_Request $request The request object.
	 * @return object
	 */
	public static function filter_campaign_rest_request( $prepared_post, $request ) {
		// Only filter Noptin campaign posts.
		if ( 'noptin-campaign' !== $prepared_post->post_type ) {
			return $prepared_post;
		}

		// Get the request parameters.
		$params = $request->get_params();

		// If meta is being updated, ensure it only contains our meta keys.
		// The WP Meta API is still buggy and errors when updating some third party meta keys.
		if ( isset( $params['meta'] ) && is_array( $params['meta'] ) ) {
			$our_meta_keys = array_keys( get_registered_meta_keys( 'post', $prepared_post->post_type ) );

			$filtered_meta = array_filter(
				$params['meta'],
				function ( $key ) use ( $our_meta_keys ) {
					// Filter out protected meta keys.
					return strpos( $key, '_' ) !== 0 && in_array( $key, $our_meta_keys, true );
				},
				ARRAY_FILTER_USE_KEY
			);

			// Update the request with filtered meta.
			$request->set_param( 'meta', $filtered_meta );
		}

		return $prepared_post;
	}

	/**
	 * Gets the most recent PHPMailer error message.
	 *
	 * @since 1.7.0
	 *
	 * @return string
	 */
	public static function get_phpmailer_last_error() {
		global $phpmailer;

		/** @var \PHPMailer\PHPMailer\PHPMailer $phpmailer */
		if ( $phpmailer && ! empty( $phpmailer->ErrorInfo ) ) {
			return $phpmailer->ErrorInfo;
		}

		return 'The mail function returned false.';
	}
}
