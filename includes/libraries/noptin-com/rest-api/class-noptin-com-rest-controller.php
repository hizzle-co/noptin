<?php
/**
 * Noptin.com REST API Controller
 *
 * Handles requests from noptin.com.
 *
 * @package Noptin\noptin.com
 * @since   1.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Noptin_COM_REST_Controller Class.
 *
 * @since 1.5.0
 * @ignore
 * @extends WP_REST_Controller
 */
class Noptin_COM_REST_Controller extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'noptin-com-site/v1';

	/**
	 * API User.
	 *
	 * @var WP_User
	 */
	protected $api_user;

	/**
	 * Class constructor.
	 *
	 * @since 1.5.0
	 */
	public function __construct() {

		// Register rest routes.
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

    }

	/**
	 * Authenticates API request.
	 *
	 * @since 1.5.0
	 * @return bool|WP_Error
	 */
	public function is_authenticated() {

		if ( is_user_logged_in() ) {
			$this->api_user = wp_get_current_user();
			return true;
		}

		// Retrieve the authorication header.
		$auth_header = trim( self::get_authorization_header() );

		// Prepare the access token.
		if ( stripos( $auth_header, 'Bearer ' ) === 0 ) {
			$access_token = trim( substr( $auth_header, 7 ) );
		} else if ( ! empty( $_GET['token'] ) && is_string( $_GET['token'] ) ) {
			$access_token = trim( $_GET['token'] );
		} else {
			return new WP_Error( 'no_access_token', 'No access token provided', array( 'status' => 400 ) );
		}

		// And the authentication signature.
		if ( ! empty( $_SERVER['HTTP_X_NOPTIN_SIGNATURE'] ) ) {
			$signature = trim( $_SERVER['HTTP_X_NOPTIN_SIGNATURE'] );
		} else if ( ! empty( $_GET['signature'] ) && is_string( $_GET['signature'] ) ) {
			$signature = trim( $_GET['signature'] );
		} else {
			return new WP_Error( 'no_signature', 'No signature provided', array( 'status' => 400 ) );
		}

		// Ensure the site is connected to noptin.com.
		$site_auth = Noptin_COM::get( 'auth' );

		if ( empty( $site_auth['access_token'] ) ) {
			return new WP_Error( 'site_not_connnected', 'Site not connected to noptin.com', array( 'status' => 401 ) );
		}

		// Compare the access tokens.
		if ( ! hash_equals( $access_token, $site_auth['access_token'] ) ) {
			return new WP_Error( 'invalid_token', 'Invalid access token provided', array( 'status' => 401 ) );
		}

		$body = WP_REST_Server::get_raw_data();

		// Verify the request using a signature code.
		if ( ! self::verify_noptin_com_request( $body, $signature, $site_auth['access_token_secret'] ) ) {
			return new WP_Error( 'request_verification_failed', 'Request verification by signature failed', array( 'status' => 400 ) );
		}

		$user = get_user_by( 'id', $site_auth['user_id'] );
		if ( ! $user ) {
			return new WP_Error( 'user_not_found', 'Token owning user not found', array( 'status' => 401 ) );
		}

		$this->api_user = $user;
		return true;
    }

	/**
	 * Get the authorization header.
	 *
	 * On certain systems and configurations, the Authorization header will be
	 * stripped out by the server or PHP. Typically this is then used to
	 * generate `PHP_AUTH_USER`/`PHP_AUTH_PASS` but not passed on. We use
	 * `getallheaders` here to try and grab it out instead.
	 *
	 * @since 1.5.0
	 * @return string Authorization header if set.
	 */
	protected static function get_authorization_header() {

		if ( ! empty( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
			return wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] );
		}

		if ( function_exists( 'getallheaders' ) ) {
			$headers = getallheaders();
			// Check for the authoization header case-insensitively.
			foreach ( $headers as $key => $value ) {
				if ( 'authorization' === strtolower( $key ) ) {
					return $value;
				}
			}
		}

		return '';
	}

	/**
	 * Verify noptin.com request from a given body and signature request.
	 *
	 * @since 1.5.0
	 * @param string $body                Request body.
	 * @param string $signature           Request signature found in X-Noptin-Signature header.
	 * @param string $access_token_secret Access token secret for this site.
	 * @return bool
	 */
	protected static function verify_noptin_com_request( $body, $signature, $access_token_secret ) {

		$data = array(
			'host'        => $_SERVER['HTTP_HOST'],
			'request_uri' => urldecode( remove_query_arg( array( 'token', 'signature' ), $_SERVER['REQUEST_URI'] ) ),
			'method'      => strtoupper( $_SERVER['REQUEST_METHOD'] ),
		);

		if ( ! empty( $body ) ) {
			$data['body'] = $body;
		}

		$expected_signature = hash_hmac( 'sha256', wp_json_encode( $data ), $access_token_secret );

		return hash_equals( $expected_signature, $signature );
	}

	/**
	 * Check permissions.
	 *
	 * @since 1.5.0
	 * @return false
	 */
	public function user_can( $permission ) {
		return ! empty( $this->api_user ) && user_can( $this->api_user, $permission );
	}

}
