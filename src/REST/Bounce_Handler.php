<?php
/**
 * Controller for bounces.
 *
 * @version 1.0.0
 */

namespace Hizzle\Noptin\REST;

defined( 'ABSPATH' ) || exit;

/**
 * Controller for bounces.
 */
class Bounce_Handler extends Controller {

	/**
	 * Registers REST routes.
	 *
	 * @since 1.0.0
	 */
	public function register_routes() {

		// Handles a bounced email.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<noptin_name>[\w-]+)/(?P<noptin_code>[\w\-\.]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'handle_bounce' ),
					'permission_callback' => array( $this, 'permission_callback' ),
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
	public function service_url( $service_name ) {

		return rest_url(
			sprintf(
				'%s/%s/%s/%s',
				$this->namespace,
				$this->rest_base,
				$service_name,
				noptin_encrypt( 'noptin' )
			)
		);
	}

	/**
	 * Returns a list of supported services.
	 *
	 */
	public function get_supported_services() {

		return apply_filters(
			'noptin_bounce_handler_supported_services',
			array(
				'mailgun'      => array(
					'name' => 'Mailgun',
					'url'  => $this->service_url( 'mailgun' ),
				),
				'pepipost'     => array(
					'name' => 'Pepipost',
					'url'  => $this->service_url( 'pepipost' ),
				),
				'postmark'     => array(
					'name' => 'Postmark',
					'url'  => $this->service_url( 'postmark' ),
				),
				'sendgrid'     => array(
					'name' => 'Sendgrid',
					'url'  => $this->service_url( 'sendgrid' ),
				),
				'sparkpost'    => array(
					'name' => 'Sparkpost',
					'url'  => $this->service_url( 'sparkpost' ),
				),
				'elasticemail' => array(
					'name' => 'Elasticemail',
					'url'  => $this->service_url( 'elasticemail' ),
				),
			),
			$this
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
	public function permission_callback( $request ) {
		return 'noptin' === noptin_decrypt( $request->get_param( 'noptin_code' ) );
	}

	/**
	 * Handle bounces.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function handle_bounce( $request ) {
		$service_name = $request->get_param( 'noptin_name' );

		if ( empty( $service_name ) ) {
			return false;
		}

		$service_name = strtolower( $service_name );

		if ( is_callable( array( $this, 'handle_' . $service_name ) ) ) {
			return $this->{'handle_' . $service_name}( $request );
		}

		do_action( 'noptin_handle_bounce_' . $service_name, $request, $this );

		return rest_ensure_response( true );
	}

	/**
	 * @param \WP_REST_Request $request Request object.
	 */
	public static function handle_bounced_action( $action, $email_address, $campaign_id = 0 ) {

		if ( ! is_string( $email_address ) || ! is_email( $email_address ) ) {
			return false;
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
	public function handle_sendgrid( $request, $param = 'event', $email = 'email' ) {
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
	public function handle_pepipost( $request ) {
		$this->handle_sendgrid( $request, 'EVENT', 'EMAIL' );
	}

	/**
	 * @param \WP_REST_Request $request Request object.
	 */
	public function handle_sparkpost( $request ) {
		$this->handle_sendgrid( $request, 'message_event.type', 'rcpt_to' );
	}

	/**
	 * @param \WP_REST_Request $request Request object.
	 */
	public function handle_postmark( $request ) {

		$metadata    = $request['Metadata'];
		$campaign_id = is_array( $metadata ) && isset( $metadata['noptin_campaign_id'] ) ? $metadata['noptin_campaign_id'] : 0;

		if ( in_array( $request['RecordType'], array( 'Bounce', 'SpamComplaint' ), true ) ) {
			self::handle_bounced_action( $request['RecordType'], $request['Email'], $campaign_id );
		}
	}

	/**
	 * @param \WP_REST_Request $request Request object.
	 */
	public function handle_elasticemail( $request ) {
		$status = strtolower( $request['status'] );

		if ( 'error' === $status && in_array( $request['category'], array( 'NoMailbox', 'BlackListed', 'ManualCancel' ), true ) ) {
			$status = 'bounced';
		}

		if ( ! in_array( $status, array( 'bounced', 'abusereport', 'unsubscribed' ), true ) ) {
			self::handle_bounced_action( $request['RecordType'], $request['to'] );
		}
	}
}
