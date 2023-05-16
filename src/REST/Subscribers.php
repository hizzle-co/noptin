<?php

namespace Hizzle\Noptin\REST;

/**
 * Controller for adding subscribers.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Controller for adding subscribers.
 */
class Subscribers extends Controller {

	/**
	 * Registers REST routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'add_subscriber' ),
					'permission_callback' => array( $this, 'can_manage_noptin' ),
					'args'                => array(
						'email' => array(
							'description' => __( 'Subscriber email address.', 'newsletter-optin-box' ),
							'type'        => 'string',
							'required'    => true,
						),
						'name' => array(
							'description' => __( 'Subscriber name.', 'newsletter-optin-box' ),
							'type'        => 'string',
							'required'    => false,
						),
					),
				),
			)
		);
	}

	/**
	 * Adds a subscriber.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 *
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function add_subscriber( $request ) {
		$email = $request->get_param( 'email' );
		$name  = $request->get_param( 'name' );

		$result = add_noptin_subscriber( $email, $name );

		if ( $result ) {
			return rest_ensure_response( 'Subscriber added successfully.' );
		} else {
			return new \WP_Error( 'subscriber_addition_failed', 'Failed to add subscriber.', array( 'status' => 500 ) );
		}
	}

}
