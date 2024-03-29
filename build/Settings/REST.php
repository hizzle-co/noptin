<?php

/**
 * Controller for settings.
 *
 * @version 1.0.0
 */

namespace Hizzle\Noptin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Controller for settings.
 */
class Settings extends \WP_REST_Controller {

	/**
	 * Loads the class.
	 *
	 * @param string $rest_base The rest base.
	 */
	public function __construct( $rest_base ) {
		$this->namespace = 'noptin/v1';
		$this->rest_base = $rest_base;

		// Register rest routes.
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

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

				// Read settings.
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'can_manage_noptin' ),
					'args'                => array(),
				),

				// Updates multiple settings at once.
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_items' ),
					'permission_callback' => array( $this, 'can_manage_noptin' ),
					'args'                => array(
						'settings' => array(
							'description' => __( 'Settings to update.', 'newsletter-optin-box' ),
							'type'        => 'object',
							'required'    => true,
						),
					),
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
						'name'  => array(
							'description' => __( 'Setting name.', 'newsletter-optin-box' ),
							'type'        => 'string',
							'required'    => true,
						),
						'value' => array(
							'description' => __( 'Setting value.', 'newsletter-optin-box' ),
							'type'        => 'mixed',
							'required'    => true,
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Checks if the current user can manage noptin.
	 *
	 */
	public function can_manage_noptin() {
		if ( ! current_user_can( get_noptin_capability() ) ) {
            return new \WP_Error( 'noptin_rest_cannot_view', 'Sorry, you cannot view this resource.', array( 'status' => rest_authorization_required_code() ) );
        }

        return true;
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
		$options = get_noptin_options();
		$user    = wp_get_current_user();

		$options['noptin_signup_name']  = $user->display_name;
		$options['noptin_signup_email'] = $user->user_email;

		return rest_ensure_response( get_noptin_options() );
	}

	/**
	 * Updates multiple settings at once.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_items( $request ) {

		$settings = $request->get_param( 'settings' );

		foreach ( $settings as $name => $value ) {
			update_noptin_option( $name, $value );
		}

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
