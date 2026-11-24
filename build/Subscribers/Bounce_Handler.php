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

		$services = apply_filters(
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
					'name' => 'SendGrid',
					'url'  => self::service_url( 'sendgrid' ),
				),
				'sparkpost'    => array(
					'name' => 'SparkPost',
					'url'  => self::service_url( 'sparkpost' ),
				),
				'elasticemail' => array(
					'name' => 'Elastic Email',
					'url'  => self::service_url( 'elasticemail' ),
				),
				'ses'          => array(
					'name' => 'Amazon SES',
					'url'  => self::service_url( 'ses' ),
				),
				'postal'       => array(
					'name' => 'Postal',
					'url'  => self::service_url( 'postal' ),
				),
				'smtp2go'      => array(
					'name' => 'SMTP2GO',
					'url'  => self::service_url( 'smtp2go' ),
				),
				'brevo'        => array(
					'name' => 'Brevo',
					'url'  => self::service_url( 'brevo' ),
				),
				'cloudflare'   => array(
					'name' => 'Cloudflare Email Service',
					'url'  => self::service_url( 'cloudflare' ),
				),
				'tosend'       => array(
					'name' => 'ToSend',
					'url'  => self::service_url( 'tosend' ),
				),
			)
		);

		$guide_slug = array( 'ses' => 'amazon-ses', 'elasticemail' => 'elastic-email' );
		foreach ( $services as $slug => &$service ) {
			if ( empty( $service['guide_url'] ) ) {
				$service['guide_url'] = noptin_get_guide_url( 'Email Subscribers', 'email-subscribers/bounce-handling/' . ( $guide_slug[ $slug ] ?? $slug ) );
			}
		}
		unset( $service );

		return $services;
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

		$path  = explode( '.', $param );
		$param = array_pop( $path );
		foreach ( $events as $event_data ) {
			// Resolve the same path independently for every event in the batch.
			foreach ( $path as $part ) {
				$event_data = is_array( $event_data ) ? ( $event_data[ $part ] ?? null ) : null;
			}

			if ( ! is_array( $event_data ) || ! is_string( $event_data[ $param ] ?? null ) || empty( $event_data[ $email ] ) ) {
				continue;
			}

			$event = strtolower( $event_data[ $param ] );
			// SendGrid calls a temporary delivery block a bounce event too.
			if ( 'bounce' === $event && 'blocked' === ( $event_data['type'] ?? '' ) ) {
				continue;
			}
			if ( false !== strpos( $event, 'unsubscribe' ) || false !== strpos( $event, 'complain' ) || false !== strpos( $event, 'spam' ) || false !== strpos( $event, 'bounce' ) ) {
				self::handle_bounced_action( $event, $event_data[ $email ] );
				continue;
			}

			if ( in_array( $event, array( 'dropped', 'invalid', 'out_of_band' ), true ) ) {
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
		// SparkPost wraps each event in msys; older relays may omit that wrapper.
		self::handle_sendgrid( $request, 'msys.message_event.type', 'rcpt_to' );
		self::handle_sendgrid( $request, 'message_event.type', 'rcpt_to' );
	}

	/**
	 * Handles Brevo permanent failures, complaints, and unsubscribe events.
	 *
	 * @param \WP_REST_Request $request Request object.
	 */
	public static function handle_brevo( $request ) {
		$event = $request['event'];
		if ( in_array( $event, array( 'hard_bounce', 'invalid', 'invalid_email', 'spam', 'unsubscribe', 'unsubscribed' ), true ) ) {
			self::handle_bounced_action( $event, $request['email'] );
		}
	}

	/**
	 * Handles SMTP2GO events without permanently suppressing temporary failures.
	 *
	 * @param \WP_REST_Request $request Request object.
	 */
	public static function handle_smtp2go( $request ) {
		$event = $request['event'];
		if ( 'bounce' === $event && 'hard' !== $request['bounce'] ) {
			return;
		}
		if ( in_array( $event, array( 'bounce', 'spam', 'unsubscribe' ), true ) ) {
			self::handle_bounced_action( $event, $request['rcpt'] );
		}
	}

	/**
	 * Handles Postal independently of whether its sending connection is installed.
	 *
	 * @param \WP_REST_Request $request Request object.
	 */
	public static function handle_postal( $request ) {
		// Keep an installed Postal connection's campaign attribution and extensions.
		if ( has_action( 'noptin_handle_bounce_postal' ) ) {
			do_action( 'noptin_handle_bounce_postal', $request );
			return;
		}
		$payload = $request['payload'];
		if ( ! is_array( $payload ) ) {
			return;
		}
		if ( 'MessageBounced' === $request['event'] ) {
			$email = $payload['original_message']['to'] ?? '';
		} elseif ( 'MessageDeliveryFailed' === $request['event'] && 'HardFail' === ( $payload['status'] ?? '' ) ) {
			$email = $payload['message']['to'] ?? '';
		} else {
			return;
		}
		self::handle_bounced_action( 'bounce', $email );
	}

	/**
	 * Handles ToSend complaints and recipient-caused hard bounces.
	 *
	 * @param \WP_REST_Request $request Request object.
	 */
	public static function handle_tosend( $request ) {
		$data = is_array( $request['data'] ) ? $request['data'] : $request->get_params();
		if ( 'complaint' === $request['type'] ) {
			self::handle_bounced_action( 'complaint', $data['email'] ?? '' );
		} elseif ( in_array( $request['type'], array( 'bounce', 'bounced' ), true ) && true === ( $data['is_hard_bounce'] ?? false ) && empty( $data['sender_fault'] ) ) {
			self::handle_bounced_action( 'bounce', $data['email'] ?? '' );
		}
	}

	/**
	 * Handles Email Sending events relayed from a Cloudflare Queue consumer.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response Acknowledgement for the queue relay.
	 */
	public static function handle_cloudflare( $request ) {
		$event   = $request->get_json_params();
		$payload = is_array( $event ) && is_array( $event['payload'] ?? null ) ? $event['payload'] : array();
		$type    = is_array( $event ) ? ( $event['type'] ?? '' ) : '';
		$bounce  = is_array( $payload['bounce'] ?? null ) ? $payload['bounce'] : array();

		if ( 'cf.email.sending.message.complained' === $type ) {
			self::handle_bounced_action( 'complaint', $payload['recipient'] ?? '' );
		} elseif ( 'cf.email.sending.message.bounced' === $type && 'hard' === ( $bounce['type'] ?? '' ) ) {
			// Exhausted temporary retries are also message.bounced events, with type soft.
			self::handle_bounced_action( 'bounce', $payload['recipient'] ?? '' );
		}

		return rest_ensure_response( true );
	}

	/**
	 * @param \WP_REST_Request $request Request object.
	 */
	public static function handle_postmark( $request ) {
		// Postmark leaves recipients active after temporary failures.
		if ( 'Bounce' === $request['RecordType'] && false === $request['Inactive'] ) {
			return;
		}

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
			return self::confirm_ses_subscription( $payload['SubscribeURL'] ?? null );
		}

		// Parse the message
		$bounce    = $message['bounce'] ?? $payload['bounce'] ?? array();
		$complaint = $message['complaint'] ?? $payload['complaint'] ?? array();
		$email     = $message['mail'] ?? $payload['mail'] ?? $message['email'] ?? $payload['email'] ?? array();

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
			if ( 'Permanent' === ( $bounce['bounceType'] ?? '' ) ) {
				foreach ( (array) ( $bounce['bouncedRecipients'] ?? array() ) as $recipient ) {
					if ( ! empty( $recipient['emailAddress'] ) ) {
						self::handle_bounced_action( 'bounce', $recipient['emailAddress'], $campaign_id );
					}
				}
			}
		}

		// Handle complaints.
		if ( ! empty( $complaint ) && 'not-spam' !== ( $complaint['complaintSubType'] ?? '' ) ) {
			foreach ( (array) ( $complaint['complainedRecipients'] ?? array() ) as $recipient ) {
				if ( ! empty( $recipient['emailAddress'] ) ) {
					self::handle_bounced_action( 'complaint', $recipient['emailAddress'], $campaign_id );
				}
			}
		}
	}

	/**
	 * Confirms SNS subscriptions without allowing arbitrary server-side requests.
	 *
	 * @param mixed $url Subscription confirmation URL from the webhook.
	 * @return bool|\WP_Error True on confirmation, or an error without sensitive URL data.
	 */
	private static function confirm_ses_subscription( $url ) {
		$parts = is_string( $url ) ? wp_parse_url( $url ) : false;
		$query = array();
		if ( is_array( $parts ) ) {
			wp_parse_str( $parts['query'] ?? '', $query );
		}
		// Restrict to SNS itself, not all of amazonaws.com: S3 and EC2 endpoints can
		// be attacker-controlled. Never follow a redirect away from the checked host.
		if ( ! is_array( $parts ) || 'https' !== strtolower( $parts['scheme'] ?? '' ) ||
			! preg_match( '/^sns(?:-fips)?\.[a-z0-9-]+\.amazonaws\.com(?:\.cn)?$/D', strtolower( $parts['host'] ?? '' ) ) ||
			isset( $parts['user'] ) || isset( $parts['pass'] ) || isset( $parts['fragment'] ) ||
			( isset( $parts['port'] ) && 443 !== $parts['port'] ) || ! in_array( $parts['path'] ?? '', array( '', '/' ), true ) ||
			'ConfirmSubscription' !== ( $query['Action'] ?? null ) || ! is_string( $query['TopicArn'] ?? null ) || empty( $query['TopicArn'] ) ||
			! is_string( $query['Token'] ?? null ) || empty( $query['Token'] ) ) {
			return new \WP_Error( 'noptin_invalid_sns_url', 'Invalid Amazon SNS subscription confirmation URL.', array( 'status' => 400 ) );
		}
		$response = wp_safe_remote_get( $url, array( 'redirection' => 0, 'timeout' => 10, 'limit_response_size' => 65536 ) );
		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) < 200 || wp_remote_retrieve_response_code( $response ) >= 300 ) {
			return new \WP_Error( 'noptin_sns_confirmation_failed', 'Amazon SNS subscription confirmation failed.', array( 'status' => 502 ) );
		}
		return true;
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
		unset( $settings['general_email_info']['settings']['bounce_webhook_url'] );
		$services = self::get_supported_services();
		$fields   = array(
			'bounce_email_service' => array(
				'el'          => 'select',
				'label'       => __( 'Email Service', 'newsletter-optin-box' ),
				'default'     => '',
				'options'     => wp_list_pluck( $services, 'name' ),
				'description' => __( 'Select the service that delivers your emails to show its webhook URL.', 'newsletter-optin-box' ),
			),
		);
		foreach ( $services as $slug => $service ) {
			$fields[ 'bounce_handler_url_' . $slug ] = array(
				'el'          => 'input',
				'type'        => 'text',
				/* translators: %s: Email delivery service name. */
				'label'       => sprintf( __( '%s Bounce Handler URL', 'newsletter-optin-box' ), $service['name'] ),
				'readonly'    => true,
				'disabled'    => true,
				'default'     => $service['url'],
				'conditions'  => array(
					array(
						'key'      => 'bounce_email_service',
						'operator' => '==',
						'value'    => $slug,
					),
				),
				/* translators: %s: Email delivery service name. */
				'description' => sprintf( '<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>', esc_url( $service['guide_url'] ), sprintf( __( 'Set up %s bounce handling', 'newsletter-optin-box' ), esc_html( $service['name'] ) ) ),
			);
		}
		$settings['bounce_handler_settings'] = array(
			'el'          => 'settings_group',
			'label'       => __( 'Bounce Handling', 'newsletter-optin-box' ),
			'section'     => 'emails',
			'sub_section' => 'main',
			'settings'    => $fields,
		);

		return $settings;
	}
}
