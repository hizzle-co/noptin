<?php
/**
 * Forms API: Forms Listener.
 *
 * Contains the main class for listening to form submissions.
 *
 * @since             1.6.2
 * @package           Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Listens to form submissions.
 *
 * This class only listens to form submissions made from [noptin] shortcode,
 * block, widget, and the new opt-in forms.
 *
 * Legacy opt-in forms are listened in Noptin_Ajax::add_subscriber().
 *
 * @see show_noptin_form()
 * @see Noptin_Ajax::add_subscriber()
 * @since 1.6.2
 */
class Noptin_Form_Listener {

	/**
	 * @var WP_Error Contains errors resulting from this submission.
	 */
	public $error;

	/**
	 * @var int Contains the id of the processed form.
	 *
	 * Note that this is not the form id of the saved form.
	 * It is the id passed to Noptin_Form_Element::__construct().
	 */
	public $processed_form = null;

	/**
	 * @var string Whether the last event was subscribe, already_subscribed, update or unsubscribe.
	 *
	 * - subscribed - The subscriber subscribed successfuly.
	 * - already_subscribed - The subscriber tried to subscribe but they are already subscribed, and the form forbids updating existing subscribers.
	 * - update - The subscriber tried to subscribe but they are already subscribed, so their details were updated instead.
	 * - unsubscribe - The subscriber used the form to unsubscribe.
	 *
	 * Will be empty if the request contains an error or no form was submitted.
	 */
	public $last_event;

	/**
	 * @var int The id of the processed subscriber.
	 *
	 */
	public $subscriber_id;

	/**
	 * @var array|WP_REST_Request An array of submitted data.
	 */
	public $submitted = array();

	/**
	 * @var array An array of cached data ( This is the data that was passed to the [noptin] shortcode).
	 */
	public $cached = null;

	/**
	 * Register relevant hooks.
	 *
	 * @since 1.6.2
	 * @ignore
	 */
	public function add_hooks() {

		$this->error = new WP_Error();

		// Prepare submitted data.
		$submitted  = wp_unslash( array_merge( (array) $_GET, (array) $_POST ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing

		// Abort if this is not a subscription request.
		if ( empty( $submitted['noptin_process_request'] ) ) {
			return;
		}

		$this->submitted = $submitted;

		// Process subscription requests.
		add_action( 'init', array( $this, 'process_request' ) );

		// User is subscribing via ajax.
		add_action( 'wp_ajax_noptin_process_ajax_subscriber', array( $this, 'ajax_add_subscriber' ) );
		add_action( 'wp_ajax_nopriv_noptin_process_ajax_subscriber', array( $this, 'ajax_add_subscriber' ) );
	}

	/**
	 * Processes form submissions.
	 *
	 * @since 1.6.2
	 * @return void
	 */
	public function process_request() {

		// We want to process this once.
		if ( ! is_null( $this->processed_form ) ) {
			return;
		}

		$this->processed_form = (int) $this->get_submitted( 'noptin_element_id', 0 );

		// Maybe verify nonce.
		if ( noptin_verify_subscription_nonces() && false === check_ajax_referer( 'noptin_subscription_nonce', 'noptin_nonce', false ) ) {
			return $this->error->add( 'error', get_noptin_form_message( 'error' ) );
		}

		// Spam checks.
		if ( ! isset( $this->submitted['noptin_timestamp'] ) || $this->submitted['noptin_timestamp'] > ( time() - 2 ) ) {
			return $this->error->add( 'error', get_noptin_form_message( 'error' ) );
		}

		if ( ! isset( $this->submitted['noptin_ign'] ) || ! empty( $this->submitted['noptin_ign'] ) ) {
			return $this->error->add( 'error', get_noptin_form_message( 'error' ) );
		}

		// validate email field.
		if ( ! is_email( $this->get_field_value( 'email' ) ) ) {
			return $this->error->add( 'invalid_email', get_noptin_form_message( 'invalid_email' ) );
		}

		// Validate other required fields.
		foreach ( $this->get_fields_for_request() as $custom_field ) {

			if ( ! empty( $custom_field['required'] ) ) {

				$value = $this->get_field_value( $custom_field['merge_tag'] );

				if ( '' === $value || array() === $value ) {
					return $this->error->add( 'required_field_missing', get_noptin_form_message( 'required_field_missing' ) );
				}
			}
		}

		// Make sure acceptance checkbox is checked.
		$acceptance = trim( $this->get_cached( 'acceptance' ) );

		if ( ! empty( $acceptance ) && empty( $this->submitted['GDPR_consent'] ) ) {
			return $this->error->add( 'accept_terms', get_noptin_form_message( 'accept_terms' ) );
		}

		/**
		 * Fires when checking a form submission for errors.
		 *
		 * Add any errors to $listener->error->add( $code, $message );
		 *
		 * @since 1.6.2
		 *
		 * @param Noptin_Form_Listener $listener
		 */
		do_action( 'noptin_form_errors', $this );

		// Do not proceed if we have an error.
		if ( $this->error->has_errors() ) {
			return;
		}

		// Process the form.
		$form_action = $this->get_submitted( 'form_action', 'subscribe' );

		if ( 'subscribe' === $form_action ) {
			$this->process_subscribe_request();
		}

		if ( 'unsubscribe' === $form_action ) {
			$this->process_unsubscribe_request();
		}

		// Trigger success/error hooks and maybe redirect to a different page.
		$this->respond();

	}

	/**
	 * Retrieves a given custom field's submitted value.
	 *
	 * Note: The returned value is unescaped.
	 *
	 * @since 1.6.2
	 * @param string $field_key The key to retrieve, e.g, email, first_name etc.
	 * @param mixed $default Optional. The default to return in case the specified field was not submitted in the current request.
	 */
	public function get_field_value( $field_key, $default = '' ) {
		$fields = $this->get_submitted( 'noptin_fields', array() );
		return isset( $fields[ $field_key ] ) ? $fields[ $field_key ] : $default;
	}

	/**
	 * Retrieves which fields we should process for this submission.
	 *
	 * @since 1.6.2
	 * @see get_noptin_custom_fields
	 * @see prepare_noptin_form_fields
	 * @return array
	 */
	public function get_fields_for_request() {

		/**
		 * Filters the fields to process for the current submission.
		 *
		 * @since 1.6.2
		 *
		 * @see get_noptin_custom_field
		 * @see get_noptin_custom_fields
		 * @param array $custom_fields
		 * @param Noptin_Form_Listener $listener
		 *
		 */
		return apply_filters( 'custom_noptin_fields_to_process', prepare_noptin_form_fields( $this->get_cached( 'fields' ) ), $this );
	}

	/**
	 * Processes a newsletter subscribe request.
	 *
	 * @since 1.6.2
	 * @return void
	 */
	protected function process_subscribe_request() {

		// Prepare form data.
		$source     = sanitize_text_field( $this->get_submitted( 'source' ) );
		$subscriber = array();

		// Process form fields.
		foreach ( $this->get_fields_for_request() as $custom_field ) {

			$value = $this->get_field_value( $custom_field['merge_tag'] );

			// Checkboxes should always be 0 or 1.
			if ( 'checkbox' === $custom_field['type'] ) {
				$value = (int) ! empty( $value );
			}

			$subscriber[ $custom_field['merge_tag'] ] = $value;

		}

		// GDPR acceptance text.
		if ( ! empty( $this->submitted['GDPR_consent'] ) ) {
			$subscriber['GDPR_consent'] = 1;
		}

		// Add the subscriber's IP address...
		$address = noptin_get_user_ip();
		if ( ! empty( $address ) && '::1' !== $address ) {
			$subscriber['ip_address'] = $address;
		}

		// ... the conversion page...
		if ( ! empty( $this->submitted['conversion_page'] ) ) {
			$subscriber['conversion_page'] = esc_url_raw( trim( wp_unslash( $this->submitted['conversion_page'] ) ) );
		}

		// ... and the source.
		if ( ! empty( $source ) ) {
			$subscriber['source'] = noptin_clean( $source );
		}

		// Finally, add connection data.
		$subscriber = Noptin_Hooks::add_connections( $subscriber, $this->get_cached( null, array() ) );

		/**
		 * Filters subscriber details when adding a new subscriber via a noptin form.
		 *
		 * @param array $subscriber Subscriber details
		 * @param string $source Subscriber source
		 * @param Noptin_Form_Listener $listener The listener object
		 * @since 1.6.2
		 */
		$subscriber = apply_filters( 'noptin_form_subscriber_details', $subscriber, $source, $this );

		// Does the subscriber exist already?
		$subscriber_id = get_noptin_subscriber_id_by_email( sanitize_email( $subscriber['email'] ) );
		if ( ! empty( $subscriber_id ) ) {

			$this->subscriber_id = $subscriber_id;

			// Maybe abort...
			$update_existing = $this->get_cached( 'update_existing' );
			if ( empty( $update_existing ) ) {
				$this->last_event = 'already_subscribed';
				return;
			}

			// ... or update the subscriber.
			update_noptin_subscriber( $subscriber_id, $subscriber );
			$this->last_event = 'updated';

			/**
			 * Fires right after a newsletter form was used to update an existing subscriber.
			 *
			 * @since 1.6.2
			 *
			 * @param int $subscriber_id
			 * @param array $subscriber_data
			 * @param Noptin_Form_Listener $listener The listener object
			 */
			do_action( 'noptin_form_updated_subscriber', $subscriber_id, $subscriber, $this );
			return;
		}

		// Add the subscriber.
		$subscriber_id = add_noptin_subscriber( $subscriber );

		// Check if an error occured while adding the subscriber.
		if ( is_string( $subscriber_id ) ) {
			return $this->error->add( 'other_error', $subscriber_id );
		}

		// Update form subscriptions.
		if ( is_numeric( $source ) ) {
			$count = (int) get_post_meta( $source, '_noptin_subscribers_count', true );
			update_post_meta( $source, '_noptin_subscribers_count', $count + 1 );
		}

		$this->last_event    = 'subscribed';
		$this->subscriber_id = $subscriber_id;

		/**
		 * Fires right after a newsletter form was used to add a new subscriber.
		 *
		 * @since 1.6.2
		 *
		 * @param int $subscriber_id
		 * @param array $subscriber_data
		 * @param Noptin_Form_Listener $listener The listener object
		 */
		do_action( 'noptin_form_subscribed', $subscriber_id, $subscriber, $this );
	}

	/**
	 * Processes a newsletter unsubscribe request.
	 *
	 * @since 1.6.2
	 * @return void
	 */
	protected function process_unsubscribe_request() {

		// Ensure the subscriber exists.
		$email         = sanitize_email( $this->get_field_value( 'email' ) );
		$subscriber_id = get_noptin_subscriber_id_by_email( $email );

		if ( empty( $subscriber_id ) ) {
			return $this->error->add( 'not_subscribed', get_noptin_form_message( 'not_subscribed' ) );
		}

		// Unsubscribe the subscriber.
		unsubscribe_noptin_subscriber( $email );

		// Prevent subscription redirects.
		$this->last_event    = 'unsubscribed';
		$this->subscriber_id = $subscriber_id;

		/**
		 * Fires right after a newsletter form was used to unsubscribe an email.
		 *
		 * @since 1.6.2
		 *
		 * @param string $email
		 * @param Noptin_Form_Listener $listener
		 *
		 */
		do_action( 'noptin_form_unsubscribed', $email, $this );
	}

	/**
	 * Prepares the response for the current form submission.
	 *
	 * @since 1.6.2
	 * @return void
	 */
	protected function respond() {

		$success = ! $this->error->has_errors();

		if ( $success ) {

			/**
			 * Fires when a form is submitted without any errors (success).
			 *
			 * @since 1.6.2
			 *
			 * @param Noptin_Form_Listener $listener
			 */
			do_action( 'noptin_form_success', $this );

		} else {

			/**
			 * Fires when a form is submitted with errors.
			 *
			 * @since 1.6.2
			 *
			 * @param Noptin_Form_Listener $listener
			 */
			do_action( 'noptin_form_error', $this );

			// Fire a dedicated event for each error.
			foreach ( $this->error->get_error_codes() as $error ) {

				/**
				 * Fires when a form is submitted with specific error codes.
				 *
				 * The dynamic portion of the hook, `$error`, refers to the error that occurred.
				 *
				 * Default errors give us the following possible hooks:
				 *
				 * - noptin_form_error_error                    General errors
				 * - noptin_form_error_invalid_email            Invalid email address
				 * - noptin_form_error_already_subscribed       Email is already subscribed
				 * - noptin_form_error_required_field_missing   One or more required fields are missing
				 * - noptin_form_error_accept_terms             Terms were not accepted
				 * - noptin_form_error_already_subscribed       The email being unsubscribed is not subscribed
				 *
				 * @since 1.6.2
				 * @see get_default_noptin_form_messages()
				 * @param Noptin_Form_Listener $listener
				 */
				do_action( "noptin_form_error_$error", $this );

			}
		}

		/**
		 * Fires right before responding to the form submission.
		 *
		 * @since 1.6.2
		 *
		 * @param Noptin_Form_Listener $listener
		 */
		do_action( 'noptin_form_respond', $this );

		// Do stuff on success (if form was submitted over plain HTTP, not for AJAX or REST requests).
		$redirect_url = $this->get_redirect_url();
		if ( $success && ! empty( $redirect_url ) && ! $this->is_json_request() ) {
			wp_safe_redirect( esc_url_raw( $redirect_url ) );
			exit;
		}

	}

	/**
	 * Checks if this is an Ajax/WP_Rest request instead of a regular HTTP request.
	 *
	 * @since 1.6.2
	 * @ignore
	 */
	private function is_json_request() {
		return wp_doing_ajax() || ( isset( $_SERVER['HTTP_ACCEPT'] ) && false !== strpos( $_SERVER['HTTP_ACCEPT'], 'application/json' ) );
	}

	/**
	 * Returns the current submission's redirect URL.
	 *
	 * @since 1.6.2
	 * @return string
	 */
	public function get_redirect_url() {

		/**
		 * Filter's a shortcode/form's redirect URL.
		 *
		 * @since 1.6.2
		 *
		 * @param string $redirect_url
		 * @param Noptin_Form_Listener $listener
		 */
		return apply_filters( 'noptin_form_redirect_url', trim( $this->get_cached( 'redirect' ) ), $this );
	}

	/**
	 * Returns a submitted value.
	 *
	 * @since 1.6.2
	 * @param $key The key to check for.
	 * @param $default The default value to return.
	 * @return mixed
	 */
	public function get_submitted( $key, $default = '' ) {
		return isset( $this->submitted[ $key ] ) ? $this->submitted[ $key ] : $default;
	}

	/**
	 * Returns a cached value or form option.
	 *
	 * @since 1.6.2
	 * @param string|null $key The key to check for or null to return entire cache.
	 * @param mixed $default The default value to return.
	 * @return mixed
	 */
	public function get_cached( $key, $default = '' ) {

		// Maybe retrieve from cache.
		if ( null === $this->cached ) {

			// Prepare args.
			$cached    = array();
			$source    = $this->get_submitted( 'source' );
			$cache_key = $this->get_submitted( 'noptin_unique_id' );

			if ( empty( $cache_key ) && empty( $source ) ) {
				return null === $key ? array() : $default;
			}

			// Retrieve from form settings.
			if ( is_numeric( $source ) ) {
				$form_settings = get_post_meta( (int) $source, 'form_settings', true );

				if ( ! empty( $form_settings ) ) {
					$cached = $form_settings;
				}
			}

			// Retrieve cache by the submitted cache key.
			if ( ! empty( $cache_key ) ) {
				$saved_cache = get_option( $cache_key );

				if ( ! empty( $saved_cache ) ) {
					$cached = array_merge( $cached, $saved_cache );
				}
			}

			$this->cached = $cached;
		}

		// Are we retrieving the entire cache?
		if ( null === $key ) {
			return $this->cached;
		}

		return isset( $this->cached[ $key ] ) && '' !== noptin_clean( $this->cached[ $key ] ) ? $this->cached[ $key ] : $default;
	}

	/**
	 * Returns a response HTML.
	 *
	 * @since 1.6.2
	 * @param bool $force_response Whether or not to force a response if no submission was made.
	 * @return string
	 */
	public function get_response_html( $force_response = false ) {

		// Check if form was submitted.
		if ( empty( $this->processed_form ) ) {

			if ( ! $force_response ) {
				return '<div class="noptin-form-notice noptin-response" role="alert"></div>';
			}

			if ( ! $this->error->has_errors() ) {
				$this->error->add( 'error', get_noptin_form_message( 'error' ) );
			}
		}

		// Prepare response args.
		$html     = '';
		$source   = $this->get_submitted( 'source' );
		$messages = array();

		if ( is_numeric( $source ) ) {
			$messages = get_post_meta( $source, 'form_messages', true );
		}

		if ( ! is_array( $messages ) ) {
			$messages = array();
		}

		// Were there any errors?
		if ( $this->error->has_errors() ) {

			foreach ( $this->error->errors as $code => $message ) {

				// Prepare error message.
				$message = get_noptin_form_message( $code, is_array( $message ) ? $message[0] : $message );

				// Maybe overide it from form settings...
				if ( ! empty( $messages[ $code ] ) ) {
					$message = $messages[ $code ];
				}

				// Wrap in error tags.
				$html .= sprintf(
					'<div class="noptin-alert noptin-error noptin-alert-%s" role="alert">%s</div>',
					sanitize_html_class( $code ),
					esc_html( $this->get_cached( $code, $message ) )
				);

			}
		} else {

			$key = 'subscribed' === $this->last_event ? 'success' : $this->last_event;

			// Prepare success message.
			$message = get_noptin_form_message( $key, __( 'Thanks!', 'newsletter-optin-box' ) );

			// Maybe overide it from form settings...
			if ( ! empty( $messages[ $key ] ) ) {
				$message = $messages[ $key ];
			}

			$html = sprintf(
				'<div class="noptin-alert noptin-success noptin-alert-%s" role="alert">%s</div>',
				sanitize_html_class( $this->last_event ),
				esc_html( $this->get_cached( $key, $message ) )
			);

		}

		/**
		 * Filter the subscription response HTML
		 *
		 * Use this to add your own HTML to the subscription response.
		 *
		 * @since 1.6.2
		 *
		 * @param string $html The complete HTML string of the response, excluding the wrapper element.
		 * @param Noptin_Form_Listener $listener The listener object
		 */
		$html = (string) apply_filters( 'noptin_form_response_html', $html, $this );

		return sprintf(
			'<div class="noptin-response noptin-form-notice">%s</div>',
			wp_kses_post( $html )
		);

	}

	/**
	 * Returns a response JSON.
	 *
	 * @since 1.6.2
	 * @return array
	 */
	public function get_response_json() {

		$html_response = $this->get_response_html( true );

		// Check if we have an error.
		if ( $this->error->has_errors() ) {

			return array(
				'success' => false,
				'data'    => $html_response,
			);

		}

		// Prepare the response and an optional redirect URL.
		$redirect_url = $this->get_redirect_url();

		if ( ! empty( $redirect_url ) ) {

			$response = array(
				'action'       => 'redirect',
				'redirect_url' => esc_url_raw( $redirect_url ),
				'msg'          => $html_response,
			);

		} else {

			$response = array(
				'action' => 'msg',
				'msg'    => $html_response,
			);

		}

		return array(
			'success' => true,
			'data'    => $response,
		);

	}

	/**
	 * Process requests to the form endpoint.
	 *
	 */
	public function ajax_add_subscriber() {

		// Force listen.
		$this->process_request();

		// Send back the result.
		wp_send_json( $this->get_response_json() );
	}

}
