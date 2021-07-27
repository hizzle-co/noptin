<?php
/**
 * Noptin.com Licenses REST API Controller
 *
 * Handles requests to noptin-com-site/v1/licenses.
 *
 * @package Noptin\noptin.com
 * @since   1.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Noptin_COM_Licenses_API_Controller Class.
 *
 * @since 1.5.0
 * @ignore
 * @extends Noptin_COM_REST_Controller
 */
class Noptin_COM_Licenses_API_Controller extends Noptin_COM_REST_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'licenses';

	/**
	 * Register the routes for our endpoint.
	 *
	 * @since 1.5.0
	 */
	public function register_rest_routes() {

		// Activate license.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/activate',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'activate_license' ),
					'permission_callback' => array( $this, 'can_manage_licenses' ),
					'args'                => array(
						'product_id' => array(
							'required' => true,
							'type'     => 'number',
						),
						'license_key' => array(
							'required' => true,
							'type'     => 'string',
						),
					),
				),
			)
		);

		// Deactivate license.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/deactivate',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'deactivate_license' ),
					'permission_callback' => array( $this, 'can_manage_licenses' ),
					'args'                => array(
						'license_key' => array(
							'required' => true,
							'type'     => 'string',
						),
					),
				),
			)
		);

	}

	/**
	 * Check permissions.
	 *
	 * @since 1.5.0
	 * @return bool|WP_Error
	 */
	public function can_manage_licenses() {

		$authenticated = $this->is_authenticated();

		if ( is_wp_error( $authenticated ) ) {
			return $authenticated;
		}

		if ( ! $this->user_can( 'install_plugins' ) ) {
			return new WP_Error( 'forbidden', 'You do not have permission to manage licenses on this site', array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * Activates a license key.
	 *
	 * @param WP_REST_Request $request The request object.
	 */
	public function activate_license( $request ) {

		if ( empty( $request['product_id'] ) || empty( $request['license_key'] ) ) {
			return new WP_Error( 'missing_data', __( 'Specify both the product id and license key.', 'newsletter-optin-box' ), array( 'status' => 400 ) );
		}

		return rest_ensure_response( Noptin_COM::activate_license( trim( $request['license_key'] ), absint( $request['product_id'] ) ) );

	}

	/**
	 * Deactivates a license key.
	 *
	 * @param WP_REST_Request $request The request object.
	 */
	public function deactivate_license( $request ) {

		if ( empty( $request['license_key'] ) ) {
			return new WP_Error( 'missing_data', __( 'Missing license key.', 'newsletter-optin-box' ), array( 'status' => 400 ) );
		}

		Noptin_COM::deactivate_license( trim( $request['license_key'] ) );

		return rest_ensure_response( true );

	}

}

new Noptin_COM_Licenses_API_Controller();
