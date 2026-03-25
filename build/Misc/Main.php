<?php

namespace Hizzle\Noptin\Misc;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main misc class.
 *
 * @since 3.0.0
 */
class Main {

	/**
	 * Inits the main emails class.
	 *
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Registers the routes for posts.
	 *
	 * @since 4.7.0
	 *
	 * @see register_rest_route()
	 */
	public static function register_routes() {

		// Retrieve merge tags for automation triggers and automated email types.
		register_rest_route(
			'noptin/v1',
			'/merge-tags',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_merge_tags' ),
				'permission_callback' => array( __CLASS__, 'can_view_tags' ),
				'args'                => array(
					'type' => array(
						'description'       => 'The merge tag source type.',
						'type'              => 'string',
						'enum'              => array( 'automation_trigger', 'automated_email' ),
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					),
					'id'   => array(
						'description'       => 'The source ID (trigger id or automated email type id).',
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Retrieve available recipients (senders) for a given email type and sub type.
		register_rest_route(
			'noptin/v1',
			'/recipients',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_recipients' ),
				'permission_callback' => array( __CLASS__, 'can_view_tags' ),
				'args'                => array(
					'type'     => array(
						'description'       => 'The campaign type.',
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_key',
					),
					'sub_type' => array(
						'description'       => 'The campaign sub type.',
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Retrieve automation rule trigger and action settings.
		register_rest_route(
			'noptin/v1',
			'/automation-rule-settings',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'trigger_action_config' ),
				'permission_callback' => array( __CLASS__, 'can_view_tags' ),
				'args'                => array(
					'trigger_id' => array(
						'description' => 'The automation trigger id.',
						'type'        => array( 'string', 'array' ),
						'required'    => false,
					),
					'action_id'  => array(
						'description' => 'The automation action id.',
						'type'        => array( 'string', 'array' ),
						'required'    => false,
					),
				),
			)
		);
	}

	/**
	 * Checks if a given request has access to update a post.
	 *
	 * @since 4.7.0
	 *
	 * @return true|\WP_Error True if the request has access to update the item, WP_Error object otherwise.
	 */
	public static function can_view_tags() {
		return current_user_can( get_noptin_capability() );
	}

	/**
	 * Retrieves merge tags for a source and prepares them for JS.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function get_merge_tags( $request ) {

		$source_type = $request->get_param( 'type' );
		$source_id   = $request->get_param( 'id' );

		if ( empty( $source_type ) || empty( $source_id ) ) {
			return new \WP_Error( 'noptin_rest_merge_tags_invalid', 'Please provide both type and id.', array( 'status' => 400 ) );
		}

		if ( 'automation_trigger' === $source_type ) {
			$trigger = \Hizzle\Noptin\Automation_Rules\Triggers\Main::get( $source_id );

			if ( ! $trigger ) {
				return new \WP_Error( 'noptin_rest_merge_tags_invalid', 'Invalid automation trigger.', array( 'status' => 404 ) );
			}

			$merge_tags = self::normalize_merge_tags_for_response( $trigger->get_known_smart_tags_for_js() );

			return rest_ensure_response( $merge_tags );
		}

		if ( 'automated_email' === $source_type ) {
			$email = new \Hizzle\Noptin\Emails\Email(
				array(
					'type'            => 'automation',
					'automation_type' => $source_id,
				)
			);

			return rest_ensure_response(
				self::normalize_merge_tags_for_response( $email->get_merge_tags() )
			);
		}

		return new \WP_Error( 'noptin_rest_merge_tags_invalid', 'Invalid merge tag type.', array( 'status' => 400 ) );
	}

	/**
	 * Retrieves available recipients (senders) for a given campaign type and sub type.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function get_recipients( $request ) {

		$type     = $request->get_param( 'type' );
		$sub_type = $request->get_param( 'sub_type' );

		if ( empty( $type ) ) {
			return new \WP_Error( 'noptin_rest_recipients_invalid', 'Please provide a campaign type.', array( 'status' => 400 ) );
		}

		$email_type = \Hizzle\Noptin\Emails\Main::get_email_type( $type );
		if ( ! $email_type ) {
			return new \WP_Error( 'noptin_rest_recipients_invalid', 'Invalid campaign type.', array( 'status' => 404 ) );
		}

		$senders = array_merge(
			array(
				'manual_recipients' => array(
					'label'        => __( 'Specific People', 'newsletter-optin-box' ),
					'description'  => __( 'Enter one or more email addresses manually, separated by commas.', 'newsletter-optin-box' ),
					'image'        => array(
						'icon' => 'businessperson',
						'fill' => '#212121',
					),
					'is_active'    => true,
					'is_installed' => true,
					'is_local'     => true,
					'settings'     => array(
						'disableMergeTags' => false,
						'fields'           => array(
							'recipients' => array(
								'label'       => __( 'Recipient(s)', 'newsletter-optin-box' ),
								'description' => sprintf(
									'%s<br /> <br />%s',
									__( 'Enter recipients (comma-separated) for this email.', 'newsletter-optin-box' ),
									sprintf(
										/* translators: %s: code */
										__( 'Add %s after an email to disable send, open and click tracking for that recipient.', 'newsletter-optin-box' ),
										'<code>--notracking</code>'
									)
								),
								'type'        => 'text',
								'placeholder' => sprintf(
									/* translators: %s: The Example */
									__( 'For example, %s', 'newsletter-optin-box' ),
									'[[email]], ' . get_option( 'admin_email' ) . ' --notracking'
								),
							),
						),
					),
				),
			),
			get_noptin_email_senders( true )
		);

		$sub_types         = get_noptin_campaign_sub_types( $type );
		$selected_sub_type = array();

		if ( ! empty( $sub_type ) && isset( $sub_types[ $sub_type ] ) && is_array( $sub_types[ $sub_type ] ) ) {
			$selected_sub_type = $sub_types[ $sub_type ];
		}

		// Match select_sender behavior in the add-new campaign flow.
		if ( ! empty( $selected_sub_type['manual_recipients'] ) && empty( $selected_sub_type['is_mass_mail'] ) ) {
			$senders = array_intersect_key( $senders, array( 'manual_recipients' => true ) );
		}

		if ( isset( $senders['manual_recipients'] ) ) {
			$senders['[[site_admin_email]]'] = array_merge(
				$senders['manual_recipients'],
				array(
					'label'       => __( 'Site Admin', 'newsletter-optin-box' ),
					'description' => sprintf(
						/* translators: %s: description */
						__( 'Send an email to the %s', 'newsletter-optin-box' ),
						__( 'site admin', 'newsletter-optin-box' )
					),
				)
			);

			if ( ! empty( $selected_sub_type['manual_recipients'] ) && is_array( $selected_sub_type['manual_recipients'] ) ) {
				foreach ( $selected_sub_type['manual_recipients'] as $recipient_tag => $label ) {
					$senders[ $recipient_tag ] = array_merge(
						$senders['manual_recipients'],
						array(
							'label'       => $label,
							'description' => '[[current_user.email]]' === $recipient_tag
								? __( 'Send an email to the currently logged-in user.', 'newsletter-optin-box' )
								: sprintf(
									/* translators: %s: description */
									__( 'Send an email to the %s', 'newsletter-optin-box' ),
									$label
								),
						)
					);
				}
			}
		}

		$prepared = array();

		foreach ( $senders as $key => $sender ) {
			if ( ! is_array( $sender ) || empty( $sender['is_installed'] ) ) {
				continue;
			}

			$prepared[ $key ] = array(
				'label'       => $sender['label'] ?? $key,
				'description' => $sender['description'] ?? '',
				'settings'    => $sender['settings'] ?? array(),
			);
		}

		return rest_ensure_response( $prepared );
	}

	/**
	 * Retrieves automation rule trigger and action settings.
	 *
	 * If trigger_id/action_id is provided, returns settings for that item only.
	 * If omitted, returns settings for all available triggers/actions.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function trigger_action_config( $request ) {

		$trigger_id = $request->get_param( 'trigger_id' );
		$action_id  = $request->get_param( 'action_id' );

		$triggers = array();
		$actions  = array();

		if ( ! empty( $trigger_id ) ) {
			foreach ( noptin_parse_list( $trigger_id ) as $trigger_id ) {
				$trigger = \Hizzle\Noptin\Automation_Rules\Triggers\Main::get( $trigger_id );

				if ( ! $trigger ) {
					$triggers[ $trigger_id ] = new \WP_Error( 'noptin_rest_automation_settings_invalid_trigger', 'Invalid automation trigger.', array( 'status' => 404 ) );
					continue;
				}

				$triggers[ $trigger->get_id() ] = array(
					'label'       => $trigger->get_name(),
					'description' => $trigger->get_description(),
					'settings'    => $trigger->get_settings(),
					'merge_tags'  => self::normalize_merge_tags_for_response( $trigger->get_known_smart_tags_for_js() ),
				);
			}
		}

		if ( ! empty( $action_id ) ) {
			foreach ( noptin_parse_list( $action_id ) as $action_id ) {
				$action = \Hizzle\Noptin\Automation_Rules\Actions\Main::get( $action_id );

				if ( ! $action ) {
					$actions[ $action_id ] = new \WP_Error( 'noptin_rest_automation_settings_invalid_action', 'Invalid automation action.', array( 'status' => 404 ) );
					continue;
				}

				$actions[ $action->get_id() ] = array(
					'label'       => $action->get_name(),
					'description' => $action->get_description(),
					'settings'    => $action->get_settings(),
				);
			}
		}

		return rest_ensure_response(
			array(
				'triggers' => (object) $triggers,
				'actions'  => (object) $actions,
			)
		);
	}

	/**
	 * Normalizes merge tags into grouped format with tag keys and label/options values.
	 *
	 * @param array $merge_tags Merge tags to normalize.
	 * @return array
	 */
	private static function normalize_merge_tags_for_response( $merge_tags ) {

		if ( ! is_array( $merge_tags ) ) {
			return array();
		}

		$prepared = array();

		foreach ( $merge_tags as $key => $value ) {

			// Grouped tags format: group => [tag => details].
			if ( is_array( $value ) && self::is_grouped_tag_set( $value ) ) {
				foreach ( $value as $merge_tag => $details ) {
					self::add_normalized_tag( $prepared, $merge_tag, $details, $key );
				}
				continue;
			}

			// Flat tags format: tag => details.
			self::add_normalized_tag( $prepared, $key, $value, '' );
		}

		return $prepared;
	}

	/**
	 * Checks if an array is a grouped merge tag set.
	 *
	 * @param array $value Potential grouped tag set.
	 * @return bool
	 */
	private static function is_grouped_tag_set( $value ) {

		if ( ! is_array( $value ) || empty( $value ) ) {
			return false;
		}

		foreach ( $value as $tag => $details ) {
			if ( ! is_string( $tag ) || ! is_array( $details ) ) {
				return false;
			}

			if ( isset( $details['description'] ) || isset( $details['label'] ) || isset( $details['callback'] ) || isset( $details['group'] ) || isset( $details['options'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Adds a normalized merge tag to the response array.
	 *
	 * @param array  $prepared Existing prepared tags.
	 * @param string $merge_tag Merge tag key.
	 * @param mixed  $details Merge tag configuration.
	 * @param string $fallback_group Group name fallback.
	 */
	private static function add_normalized_tag( &$prepared, $merge_tag, $details, $fallback_group ) {

		if ( ! is_string( $merge_tag ) || '' === $merge_tag ) {
			return;
		}

		if ( ! is_array( $details ) ) {
			$details = array(
				'label' => is_scalar( $details ) ? (string) $details : $merge_tag,
			);
		}

		$group = empty( $details['group'] ) ? $fallback_group : $details['group'];
		$group = empty( $group ) ? __( 'General', 'newsletter-optin-box' ) : $group;

		if ( ! isset( $prepared[ $group ] ) ) {
			$prepared[ $group ] = array();
		}

		$tag_data = array(
			'label' => empty( $details['label'] ) ? ( empty( $details['description'] ) ? $merge_tag : $details['description'] ) : $details['label'],
		);

		if ( isset( $details['options'] ) ) {
			$options = $details['options'];

			if ( is_callable( $options ) ) {
				$options = call_user_func( $options );
			}

			if ( is_array( $options ) ) {
				$tag_data['options'] = $options;
			}
		}

		$prepared[ $group ][ $merge_tag ] = $tag_data;
	}

	/**
	 * Enqueues interface scripts and styles.
	 *
	 */
	public static function load_interface_styles() {

		$config = include plugin_dir_path( __FILE__ ) . 'assets/js/interface.asset.php';

		wp_enqueue_style(
			'noptin-interface',
			plugins_url( 'assets/css/style-interface.css', __FILE__ ),
			array(),
			$config['version']
		);
	}
}
