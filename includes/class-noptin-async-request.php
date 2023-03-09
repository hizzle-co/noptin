<?php
/**
 * Noptin Async Request
 *
 * @package WP-Background-Processing
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Noptin_Async_Request' ) ) {

	/**
	 * This is the base class used to fire off non-blocking asynchronous requests.
	 *
	 * Async requests are useful for pushing slow one-off tasks such as sending emails to a background process.
	 * Once the request has been dispatched it will process in the background instantly.
	 *
	 * @link https://deliciousbrains.com/background-processing-wordpress/
	 * @abstract
	 */
	#[AllowDynamicProperties]
	abstract class Noptin_Async_Request {

		/**
		 * Prefix
		 *
		 * (default value: 'wp')
		 *
		 * @var string
		 * @access protected
		 */
		protected $prefix = 'noptin';

		/**
		 * Action
		 *
		 * (default value: 'async_request')
		 *
		 * @var string
		 * @access protected
		 */
		protected $action = 'noptin_async_request';

		/**
		 * Identifier
		 *
		 * @var mixed
		 * @access protected
		 */
		protected $identifier;

		/**
		 * Data
		 *
		 * (default value: array())
		 *
		 * @var array
		 * @access protected
		 */
		protected $data = array();

		/**
		 * Initiates new non-blocking asynchronous request.
		 *
		 * @ignore
		 */
		public function __construct() {
			$this->identifier = $this->prefix . '_' . $this->action;

			add_action( 'wp_ajax_' . $this->identifier, array( $this, 'maybe_handle' ) );
			add_action( 'wp_ajax_nopriv_' . $this->identifier, array( $this, 'maybe_handle' ) );

		}

		/**
		 * Sets the data to use when processing a non-blocking asynchronous request.
		 *
		 * @param array $data Data.
		 *
		 * @return $this
		 */
		public function data( $data ) {
			$this->data = $data;

			return $this;
		}

		/**
		 * Dispatches a non-blocking asynchronous request.
		 *
		 * @return array|WP_Error
		 */
		public function dispatch() {
			$url  = add_query_arg( $this->get_query_args(), $this->get_query_url() );
			$args = $this->get_post_args();

			return wp_remote_post( esc_url_raw( $url ), $args );
		}

		/**
		 * Get query args
		 *
		 * @return array
		 * @ignore
		 */
		protected function get_query_args() {
			if ( property_exists( $this, 'query_args' ) ) {
				return $this->query_args;
			}

			$args = array(
				'action' => $this->identifier,
				'nonce'  => wp_create_nonce( $this->identifier ),
			);

			/**
			 * Filters the post arguments used during an async request.
			 *
			 * @param array $url
			 */
			return apply_filters( $this->identifier . '_query_args', $args );
		}

		/**
		 * Get query URL
		 *
		 * @return string
		 * @ignore
		 */
		protected function get_query_url() {
			if ( property_exists( $this, 'query_url' ) ) {
				return $this->query_url;
			}

			$url = admin_url( 'admin-ajax.php' );

			/**
			 * Filters the post arguments used during an async request.
			 *
			 * @param string $url
			 */
			return apply_filters( $this->identifier . '_query_url', $url );
		}

		/**
		 * Get post args
		 *
		 * @return array
		 * @ignore
		 */
		protected function get_post_args() {
			if ( property_exists( $this, 'post_args' ) ) {
				return $this->post_args;
			}

			$args = array(
				'timeout'   => 0.01,
				'blocking'  => false,
				'body'      => $this->data,
				'cookies'   => $_COOKIE,
				'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
			);

			/**
			 * Filters the post arguments used during an async request.
			 *
			 * @param array $args
			 */
			return apply_filters( $this->identifier . '_post_args', $args );
		}

		/**
		 * Maybe handle
		 *
		 * Check for correct nonce and pass to handler.
		 * @ignore
		 */
		public function maybe_handle() {
			// Don't lock up other requests while processing.
			session_write_close();

			check_ajax_referer( $this->identifier, 'nonce' );

			$this->handle();

			wp_die();
		}

		/**
		 * Processes the async request.
		 *
		 * Override this method to perform any actions required
		 * during the async request.
		 *
		 */
		abstract protected function handle();

	}
}
