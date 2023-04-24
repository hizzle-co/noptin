<?php

namespace Hizzle\Store;

/**
 * Store API: Handles Exceptions.
 *
 * @since   1.0.0
 * @package Hizzle\Store
 */

defined( 'ABSPATH' ) || exit;

/**
 * Data exception class.
 */
class Store_Exception extends \Exception {

	/**
	 * Sanitized error code.
	 *
	 * @var string
	 */
	protected $error_code;

	/**
	 * Error extra data.
	 *
	 * @var array
	 */
	protected $error_data;

	/**
	 * Setup exception.
	 *
	 * @param string $code             Machine-readable error code, e.g `subscriber_not_found`.
	 * @param string $message          User-friendly translated error message, e.g. 'The subscriber was not found'.
	 * @param int    $http_status_code Proper HTTP status code to respond with, e.g. 400.
	 * @param array  $data             Additional error data.
	 */
	public function __construct( $code, $message, $http_status_code = 400, $data = array() ) {
		$this->error_code = $code;
		$this->error_data = array_merge( array( 'status' => $http_status_code ), $data );

		parent::__construct( $message, $http_status_code );
	}

	/**
	 * Returns the error code.
	 *
	 * @return string
	 */
	public function getErrorCode() {
		return $this->error_code;
	}

	/**
	 * Returns error data.
	 *
	 * @return array
	 */
	public function getErrorData() {
		return $this->error_data;
	}

	/**
	 * Returns an error data key value.
	 *
	 * @param string $key Key of the data to retrieve.
	 * @param mixed  $default Default value to return if the key is not set.
	 * @return mixed
	 */
	public function getErrorDataValue( $key, $default = null ) {
		return isset( $this->error_data[ $key ] ) ? $this->error_data[ $key ] : $default;
	}

	/**
	 * Sets an error data value.
	 *
	 * @param string $key Key of the data to retrieve.
	 * @param mixed  $value The value of the key
	 */
	public function setErrorDataValue( $key, $value ) {
		$this->error_data[ $key ] = $value;
	}

}
