<?php
/**
 * Controller for bounces.
 *
 * @version 1.0.0
 */

namespace Hizzle\Noptin\Subscribers;

defined( 'ABSPATH' ) || exit;

/**
 * Controller for bounces.
 */
class Bounce_Handler {

	const REST_NAMESPACE = 'noptin/v1';
	const REST_BASE      = 'bounce_handler';

	/**
	 * Loads the class.
	 *
	 * @param string $rest_base The rest base.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_filter( 'noptin_get_settings', array( __CLASS__, 'add_settings' ) );
	}

	/**
	 * Registers REST routes.
	 *
	 * @since 1.0.0
	 */
	public static function register_routes() {

		// Handles a bounced email.
		register_rest_route(
			self::REST_NAMESPACE,
			'/' . self::REST_BASE . '/(?P<noptin_name>[\w-]+)/(?P<noptin_code>[\w\-\.]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::ALLMETHODS,
					'callback'            => array( __CLASS__, 'handle_bounce' ),
					'permission_callback' => array( __CLASS__, 'permission_callback' ),
					'args'                => array(
						'noptin_name' => array(
							'description' => 'Service name, e.g mailgun.',
							'type'        => 'string',
							'required'    => true,
						),
						'noptin_code' => array(
							'description' => 'Security code',
							'type'        => 'string',
							'required'    => true,
						),
					),
				),
				'schema' => '__return_empty_array',
			)
		);
	}

	/**
	 * Service url.
	 *
	 */
	public static function service_url( $service_name ) {

		return rest_url(
			sprintf(
				'%s/%s/%s/%s',
				self::REST_NAMESPACE,
				self::REST_BASE,
				$service_name,
				noptin_encrypt( 'noptin' )
			)
		);
	}

	/**
	 * Returns a list of supported services.
	 *
	 */
	public static function get_supported_services() {

		return apply_filters(
			'noptin_bounce_handler_supported_services',
			array(
				'mailgun'      => array(
					'name' => 'Mailgun',
					'url'  => self::service_url( 'mailgun' ),
				),
				'pepipost'     => array(
					'name' => 'Pepipost',
					'url'  => self::service_url( 'pepipost' ),
				),
				'postmark'     => array(
					'name' => 'Postmark',
					'url'  => self::service_url( 'postmark' ),
				),
				'sendgrid'     => array(
					'name' => 'Sendgrid',
					'url'  => self::service_url( 'sendgrid' ),
				),
				'sparkpost'    => array(
					'name' => 'Sparkpost',
					'url'  => self::service_url( 'sparkpost' ),
				),
				'elasticemail' => array(
					'name' => 'Elasticemail',
					'url'  => self::service_url( 'elasticemail' ),
				),
				'ses'          => array(
					'name' => 'Amazon SES',
					'url'  => noptin_get_guide_url( 'Email Subscribers', 'email-subscribers/bounce-handling/amazon-ses' ),
				),
			)
		);
	}

	/**
	 * Checks if a given request is valid.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return bool
	 */
	public static function permission_callback( $request ) {
		return 'noptin' === noptin_decrypt( $request->get_param( 'noptin_code' ) );
	}

	/**
	 * Handle bounces.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public static function handle_bounce( $request ) {
		$service_name = $request->get_param( 'noptin_name' );

		if ( empty( $service_name ) ) {
			return false;
		}

		$service_name = strtolower( $service_name );

		if ( is_callable( array( __CLASS__, 'handle_' . $service_name ) ) ) {
			return call_user_func( array( __CLASS__, 'handle_' . $service_name ), $request );
		}

		do_action( 'noptin_handle_bounce_' . $service_name, $request );

		return rest_ensure_response( true );
	}

	/**
	 * @param \WP_REST_Request $request Request object.
	 */
	public static function handle_bounced_action( $action, $email_address, $campaign_id = 0 ) {

		if ( ! is_string( $email_address ) || ! is_email( $email_address ) ) {
			return;
		}

		$action = strtolower( $action );

		if ( false !== strpos( $action, 'unsubscribe' ) ) {
			return unsubscribe_noptin_subscriber( $email_address, $campaign_id );
		}

		if ( false !== strpos( $action, 'complain' ) || false !== strpos( $action, 'spam' ) || false !== strpos( $action, 'abuse' ) ) {
			return noptin_subscriber_complained( $email_address, $campaign_id );
		}

		return bounce_noptin_subscriber( $email_address, $campaign_id );
	}

	/**
	 * @param \WP_REST_Request $request Request object.
	 */
	public static function handle_mailgun( $request ) {

		if ( empty( $request['event-data'] ) ) {
			return;
		}

		$event_data = $request['event-data'];

		// Check if this is a supported event.
		if ( ! isset( $event_data['event'] ) || ! in_array( $event_data['event'], array( 'failed', 'unsubscribed', 'complained' ), true ) ) {
			return;
		}

		// If failed, check if it's a bounce.
		if ( 'failed' === $event_data['event'] ) {
			if ( ! isset( $event_data['severity'] ) || 'permanent' !== $event_data['severity'] ) {
				return;
			}
		}

		// Check email recipient.
		if ( isset( $event_data['recipient'] ) ) {
			// Fetch campaign id:- https://documentation.mailgun.com/en/latest/user_manual.html#attaching-data-to-messages
			$campaign_id = isset( $request['user-variables'] ) && isset( $request['user-variables']['noptin_campaign_id'] ) ? $request['user-variables']['noptin_campaign_id'] : '';
			self::handle_bounced_action( $event_data['event'], $event_data['recipient'], $campaign_id );
		}
	}

	/**
	 * @param \WP_REST_Request $request Request object.
	 */
	public static function handle_sendgrid( $request, $param = 'event', $email = 'email' ) {
		$events = $request->get_json_params();

		if ( ! is_array( $events ) || empty( $events ) ) {
			return;
		}

		foreach ( $events as $event_data ) {

			// Check if param is separated by a dot.
			if ( false !== strpos( $param, '.' ) ) {
				$param = explode( '.', $param );
				if ( ! isset( $event_data[ $param[0] ] ) ) {
					continue;
				}

				$event_data = $event_data[ $param[0] ];
				$param      = $param[1];
			}

			if ( ! is_array( $event_data ) || empty( $event_data[ $param ] ) ) {
				continue;
			}

			$event = strtolower( $event_data[ $param ] );
			if ( false !== strpos( $event, 'unsubscribe' ) || false !== strpos( $event, 'complain' ) || false !== strpos( $event, 'spam' ) || false !== strpos( $event, 'bounce' ) ) {
				self::handle_bounced_action( $event, $event_data[ $email ] );
				continue;
			}

			if ( in_array( $event, array( 'dropped', 'invalid' ), true ) ) {
				self::handle_bounced_action( $event, $event_data[ $email ] );
			}
		}
	}

	/**
	 * @param \WP_REST_Request $request Request object.
	 */
	public static function handle_pepipost( $request ) {
		self::handle_sendgrid( $request, 'EVENT', 'EMAIL' );
	}

	/**
	 * @param \WP_REST_Request $request Request object.
	 */
	public static function handle_sparkpost( $request ) {
		self::handle_sendgrid( $request, 'message_event.type', 'rcpt_to' );
	}

	/**
	 * @param \WP_REST_Request $request Request object.
	 */
	public static function handle_postmark( $request ) {

		$metadata    = $request['Metadata'];
		$campaign_id = is_array( $metadata ) && isset( $metadata['noptin_campaign_id'] ) ? $metadata['noptin_campaign_id'] : 0;

		if ( in_array( $request['RecordType'], array( 'Bounce', 'SpamComplaint' ), true ) ) {
			self::handle_bounced_action( $request['RecordType'], $request['Email'], $campaign_id );
		}
	}

	/**
	 * @param \WP_REST_Request $request Request object.
	 */
	public static function handle_elasticemail( $request ) {
		$status = strtolower( $request['status'] );

		if ( 'error' === $status && in_array( $request['category'], array( 'NoMailbox', 'BlackListed', 'ManualCancel' ), true ) ) {
			$status = 'bounced';
		}

		if ( in_array( $status, array( 'bounced', 'abusereport', 'unsubscribed' ), true ) ) {
			self::handle_bounced_action( $status, $request['to'] );
		}
	}

	/**
	 * @param \WP_REST_Request $request Request object.
	 */
	public static function handle_ses( $request ) {
		// Parse the message.
		$payload = json_decode( $request->get_body(), true );

		if ( ! is_array( $payload ) ) {
			return;
		}

		$message = isset( $payload['Message'] ) ? ( is_string( $payload['Message'] ) ? json_decode( $payload['Message'], true ) : $payload['Message'] ) : array();
		$message = is_array( $message ) ? $message : array();
		$type    = $payload['notificationType'] ?? $message['notificationType'] ?? $message['eventType'] ?? $payload['Type'] ?? '';

		// SES sends a subscription confirmation first
		if ( 'SubscriptionConfirmation' === $type ) {
			if ( ! empty( $payload['SubscribeURL'] ) ) {
				\wp_remote_get( $payload['SubscribeURL'] );
			}

			return;
		}

		// Parse the message
		$bounce    = $message['bounce'] ?? $payload['bounce'] ?? array();
		$complaint = $message['complaint'] ?? $payload['complaint'] ?? array();
		$email     = $message['email'] ?? $payload['email'] ?? array();

		// Get campaign ID from message headers if available
		$campaign_id = 0;
		if ( ! empty( $email['headers'] ) ) {
			foreach ( $email['headers'] as $header ) {
				if ( self::campaign_id_header_name() === $header['name'] && is_numeric( $header['value'] ) ) {
					$campaign_id = (int) $header['value'];
					break;
				}
			}
		}

		// Handle bounces.
		if ( ! empty( $bounce ) ) {
			// Only handle permanent bounces.
			if ( 'Permanent' === $bounce['bounceType'] ) {
				foreach ( $bounce['bouncedRecipients'] as $recipient ) {
					if ( ! empty( $recipient['emailAddress'] ) ) {
						self::handle_bounced_action( 'bounce', $recipient['emailAddress'], $campaign_id );
					}
				}
			}
		}

		// Handle complaints.
		if ( ! empty( $complaint ) && 'not-spam' !== $complaint['complaintSubType'] ?? '' ) {
			foreach ( $complaint['complainedRecipients'] as $recipient ) {
				if ( ! empty( $recipient['emailAddress'] ) ) {
					self::handle_bounced_action( 'complaint', $recipient['emailAddress'], $campaign_id );
				}
			}
		}
	}

	private static function campaign_id_header_name() {
		return 'X-' . md5( home_url() );
	}

	/**
	 * Add settings.
	 *
	 * @param array $settings The settings array.
	 * @return array
	 */
	public static function add_settings( $settings ) {
		if ( ! isset( $settings['general_email_info'] ) ) {
			return $settings;
		}

		$settings['general_email_info']['settings']['bounce_webhook_url'] = array(
			'el'          => 'input',
			'type'        => 'text',
			'section'     => 'emails',
			'readonly'    => true,
			'label'       => __( 'Bounce Handler', 'newsletter-optin-box' ),
			'default'     => self::service_url( '{{YOUR_SERVICE}}' ),
			'placeholder' => self::service_url( '{{YOUR_SERVICE}}' ),
			'description' => sprintf(
				// translators: %s is the list of supported services.
				__( 'Supported services:- %s', 'newsletter-optin-box' ),
				implode(
					', ',
					array_map(
						function ( $args, $service ) {
							return sprintf(
								'<a href="%s" target="_blank">%s</a>',
								esc_url( $args['url'] ),
								esc_html( $service )
							);
						},
						self::get_supported_services(),
						array_keys( self::get_supported_services() )
					)
				)
			),
			'disabled'    => true,
		);

		return $settings;
	}
}
