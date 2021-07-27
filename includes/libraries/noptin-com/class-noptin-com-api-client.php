<?php
/**
 * Noptin.com API helper.
 *
 * @package Noptin\noptin.com
 * @since   1.5.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Noptin_COM_API_Client Class
 *
 * Provides a communication interface with the Noptin.com API.
 */
class Noptin_COM_API_Client {

	/**
	 * Base path for API routes.
	 *
	 * @var string $api_base
	 */
	public static $api_base = 'https://noptin.com/wp-json/';

	/**
	 * The last response.
	 *
	 * @var array $last_response
	 */
	public static $last_response = array();

	/**
	 * The last response code.
	 *
	 * @var int $last_response_code
	 */
	public static $last_response_code = 0;

	/**
	 * Perform an HTTP request to the Helper API.
	 *
	 * @param string $endpoint The endpoint to request.
	 * @param array  $args Additional data for the request. Set authenticated to a truthy value to enable auth.
	 *
	 * @return array|WP_Error The response from wp_safe_remote_request()
	 */
	public static function request( $endpoint, $args = array() ) {

		self::$last_response      = array();
		self::$last_response_code = 0;
		$url                      = self::url( $endpoint );

		if ( ! empty( $args['authenticated'] ) ) {
			if ( ! self::_authenticate( $url, $args ) ) {
				self::$last_response      = new WP_Error( 'authentication', 'Authentication failed.' );
				self::$last_response_code = 401;
				return self::$last_response;
			}
		}

		/**
		 * Allow developers to filter the request args passed to wp_safe_remote_request().
		 * Useful to remove sslverify when working on a local api dev environment.
		 */
		$args = apply_filters( 'noptin_helper_api_request_args', $args, $endpoint );

		// TODO: Check response signatures on certain endpoints.
		return self::process_api_response( wp_safe_remote_request( $url, $args ) );
	}

	/**
	 * Adds authentication headers to an HTTP request.
	 *
	 * @param string $url The request URI.
	 * @param array  $args By-ref, the args that will be passed to wp_remote_request().
	 * @return bool Were the headers added?
	 */
	private static function _authenticate( &$url, &$args ) {
		$auth = Noptin_COM::get( 'auth' );

		if ( empty( $auth['access_token'] ) || empty( $auth['access_token_secret'] ) ) {
			return false;
		}

		$request_uri  = parse_url( $url, PHP_URL_PATH );
		$query_string = parse_url( $url, PHP_URL_QUERY );

		if ( is_string( $query_string ) ) {
			$request_uri .= '?' . $query_string;
		}

		$data = array(
			'host'        => parse_url( $url, PHP_URL_HOST ),
			'request_uri' => $request_uri,
			'method'      => ! empty( $args['method'] ) ? $args['method'] : 'GET',
		);

		if ( ! empty( $args['body'] ) ) {
			$data['body'] = $args['body'];
		}

		$signature = hash_hmac( 'sha256', json_encode( $data ), $auth['access_token_secret'] );
		if ( empty( $args['headers'] ) ) {
			$args['headers'] = array();
		}

		$headers         = array(
			'Authorization'      => 'Bearer ' . $auth['access_token'],
			'X-Noptin-Signature' => $signature,
		);

		$args['headers'] = wp_parse_args( $headers, $args['headers'] );

		$url = add_query_arg(
			array(
				'token'     => $auth['access_token'],
				'signature' => $signature,
			),
			$url
		);

		return true;
	}

	/**
	 * Wrapper for self::request().
	 *
	 * @param string $endpoint The helper API endpoint to request.
	 * @param array  $args Arguments passed to wp_remote_request().
	 *
	 * @return array|WP_Error The response object from wp_safe_remote_request().
	 */
	public static function get( $endpoint, $args = array() ) {
		$args['method'] = 'GET';
		return self::request( $endpoint, $args );
	}

	/**
	 * Wrapper for self::request().
	 *
	 * @param string $endpoint The helper API endpoint to request.
	 * @param array  $args Arguments passed to wp_remote_request().
	 *
	 * @return array|WP_Error The response object from wp_safe_remote_request().
	 */
	public static function post( $endpoint, $args = array() ) {
		$args['method'] = 'POST';
		return self::request( $endpoint, $args );
	}

	/**
	 * Wrapper for self::request().
	 *
	 * @param string $endpoint The helper API endpoint to request.
	 * @param array  $args Arguments passed to wp_remote_request().
	 *
	 * @return array|WP_Error The response object from wp_safe_remote_request().
	 */
	public static function put( $endpoint, $args = array() ) {
		$args['method'] = 'PUT';
		return self::request( $endpoint, $args );
	}

	/**
	 * Wrapper for self::request().
	 *
	 * @param string $endpoint The helper API endpoint to delete.
	 * @param array  $args Arguments passed to wp_remote_request().
	 *
	 * @return array|WP_Error The response object from wp_safe_remote_request().
	 */
	public static function delete( $endpoint, $args = array() ) {
		$args['method'] = 'DELETE';
		return self::request( $endpoint, $args );
	}

	/**
	 * Using the API base, form a request URL from a given endpoint.
	 *
	 * @param string $endpoint The endpoint to request.
	 *
	 * @return string The absolute endpoint URL.
	 */
	public static function url( $endpoint ) {
		$api_base = untrailingslashit( apply_filters( 'noptin_helper_api_base', self::$api_base, $endpoint ) );
		$endpoint = ltrim( $endpoint, '/' );
		$endpoint = sprintf( '%s/%s', $api_base, $endpoint );
		$endpoint = esc_url_raw( $endpoint );
		return $endpoint;
	}

	/**
	 * Processes API responses
	 *
	 * @param mixed $response WP_HTTP Response.
	 * @return WP_Error|object
	 */
	public static function process_api_response( $response ) {

		self::$last_response_code = (int) wp_remote_retrieve_response_code( $response );
		self::$last_response      = $response;

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$res = json_decode( wp_remote_retrieve_body( $response ) );
		if ( isset( $res->code ) && isset( $res->message ) ) {
			return new WP_Error( $res->code, $res->message, (array) $res->data );
		}

		return  $res;
	}

}
