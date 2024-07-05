<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles Noptin Ajax Requests
 *
 * @since       1.0.5
 */
class Noptin_Ajax {

	/**
	 * Class Constructor.
	 */
	public function __construct() {

		// Register new subscriber.
		add_action( 'wp_ajax_noptin_new_subscriber', array( $this, 'add_subscriber' ) ); // @deprecated
		add_action( 'wp_ajax_nopriv_noptin_new_subscriber', array( $this, 'add_subscriber' ) ); // @deprecated
	}

	/**
	 * Adds a new subscriber via ajax
	 *
	 * @access      public
	 * @since       1.0.5
	 * @return      void
	 * @deprecated
	 */
	public function add_subscriber() {

		// Verify nonce.
		if ( noptin_verify_subscription_nonces() ) {
			check_ajax_referer( 'noptin' );
		}

		// avoid bot submissions.
		if ( ! empty( $_POST['noptin_confirm_submit'] ) ) {
			exit;
		}

		/**
		 * Fires before a subscriber is added via ajax.
		 *
		 * @since 1.2.4
		 */
		do_action( 'noptin_before_add_ajax_subscriber' );

		// Prepare form fields.
		$form = 0;

		if ( empty( $_POST['noptin_form_id'] ) ) {

			$fields = array(
				array(
					'type'    => array(
						'label' => __( 'Email Address', 'newsletter-optin-box' ),
						'name'  => 'email',
						'type'  => 'email',
					),
					'require' => 'true',
					'key'     => 'email',
				),
			);

		} else {

			// Get the form.
			$form = noptin_get_optin_form( $_POST['noptin_form_id'] );

			if ( empty( $form ) || ! $form->is_published() ) {
				wp_send_json_error( __( 'This form is in-active.', 'newsletter-optin-box' ) );
			}

			$fields = $form->fields;
		}

		$filtered = array();

		// Check gdpr.
		if ( is_object( $form ) && $form->gdprCheckbox && empty( $_POST['noptin_gdpr_checkbox'] ) ) {
			wp_send_json_error( __( 'You must consent to receive promotional emails.', 'newsletter-optin-box' ) );
		}

		if ( ! empty( $_POST['noptin_gdpr_checkbox'] ) ) {
			$filtered['GDPR_consent'] = 1;
		}

		$custom_fields = wp_list_pluck( get_noptin_custom_fields(), 'type', 'merge_tag' );

		foreach ( $fields as $field ) {

			$type = $field['type']['type'];

			if ( isset( $custom_fields[ $type ] ) ) {
				$name  = $type;
				$value = isset( $_POST[ $type ] ) ? $_POST[ $type ] : '';

				if ( 'checkbox' === $custom_fields[ $type ] ) {
					$value = (int) ! empty( $value );
				}
			} else {

				// backwards compatibility.
				$name = 'name' === $type ? 'name' : $field['type']['name'];
				$key  = esc_attr( $field['key'] );

				if ( isset( $_POST[ $key ] ) ) {
					$value = $_POST[ $key ];
				} else {
					$value = '';
				}

				if ( 'checkbox' === $type ) {
					$value = (int) ! empty( $value );
				}
			}

			// required fields.
			if ( ! empty( $field['require'] ) && 'false' !== $field['require'] && empty( $value ) ) {
				wp_send_json_error( esc_html__( 'Ensure that you fill all required fields.', 'newsletter-optin-box' ) );
			}

			// Sanitize email fields.
			if ( 'email' === $type && ! empty( $value ) ) {

				$value = sanitize_email( $value );
				if ( empty( $value ) ) {
					wp_send_json_error( esc_html__( 'Please provide a valid email address', 'newsletter-optin-box' ) );
				}
			}

			$filtered[ $name ] = $value;

		}

		// Add the subscriber's IP address.
		$address = noptin_get_user_ip();
		if ( ! empty( $address ) && '::1' !== $address ) {
			$filtered['ip_address'] = $address;
		}

		if ( ! empty( $_POST['conversion_page'] ) ) {
			$filtered['conversion_page'] = esc_url_raw( trim( $_POST['conversion_page'] ) );
		}

		if ( is_object( $form ) ) {
			$filtered['source'] = $form->ID;
		}

		// Check if the email address already exists.
		$subscribed_message = false;
		$inserted           = empty( $filtered['email'] ) ? 0 : get_noptin_subscriber_id_by_email( $filtered['email'] );
		if ( $inserted ) {
			$subscribed_message = get_noptin_option( 'already_subscribed_message', __( 'You are already subscribed to the newsletter, thank you!', 'newsletter-optin-box' ) );

			// Resubscribe the subscriber if they've unsubscribed.
			if ( apply_filters( 'noptin_resubscribe_subscriber', true, $inserted, $filtered ) ) {
				resubscribe_noptin_subscriber( $inserted );
			}
		} else {
			/**
			 * Filters subscriber details when adding a new subscriber via ajax.
			 *
			 * @since 1.2.4
			 */
			$filtered = apply_filters( 'noptin_add_ajax_subscriber_filter_details', wp_unslash( $filtered ), $form );
			$inserted = add_noptin_subscriber( $filtered );

			if ( is_string( $inserted ) ) {
				wp_send_json_error( $inserted );
			}

			do_action( 'noptin_add_ajax_subscriber', $inserted, $form );

			$subscribed_message = get_noptin_option( 'success_message' );

			if ( is_object( $form ) && ! empty( $form->successMessage ) ) {
				$subscribed_message = $form->successMessage;
			}
		}

		$result = array(
			'action' => 'msg',
			'msg'    => $subscribed_message,
		);

		if ( empty( $result['msg'] ) ) {
			$result['msg'] = esc_html__( 'Thanks for subscribing to the newsletter', 'newsletter-optin-box' );
		}

		if ( is_object( $form ) ) {
			$count = (int) get_post_meta( $form->ID, '_noptin_subscribers_count', true );
			update_post_meta( $form->ID, '_noptin_subscribers_count', $count + 1 );

			// msg.
			if ( 'message' !== $form->subscribeAction ) {
				$result['action']   = 'redirect';
				$result['redirect'] = $form->redirectUrl;
			}
		}

		$result['msg'] = wp_kses_post( add_noptin_merge_tags( $result['msg'], get_noptin_subscriber_merge_fields( $inserted ) ) );

		wp_send_json_success( $result );
	}
}
