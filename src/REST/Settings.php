<?php

namespace Hizzle\Noptin\REST;

/**
 * Controller for settings.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Controller for settings.
 */
class Settings extends Controller {

	/**
	 * Registers REST routes.
	 *
	 * @since 1.0.0
	 */
	public function register_routes() {

		// Reads the current settings.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'can_manage_noptin' ),
					'args'                => array(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		// Updates a single setting.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<name>[\w-]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'can_manage_noptin' ),
					'args'                => array(
						'name' => array(
							'description' => __( 'Setting name.', 'newsletter-optin-box' ),
							'type'        => 'string',
							'required'    => true,
						),
						'value' => array(
							'description' => __( 'Setting value.', 'newsletter-optin-box' ),
							'type'        => 'string',
							'required'    => true,
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Retrieves a collection of items.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		return rest_ensure_response( get_noptin_options() );
	}

	/**
	 * Updates a single setting.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {

		$name  = $request->get_param( 'name' );
		$value = $request->get_param( 'value' );

		update_noptin_option( $name, $value );

		return rest_ensure_response( get_noptin_option( $name ) );
	}
}
