<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
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
		add_action( 'wp_ajax_noptin_process_ajax_subscriber', array( $this, 'add_subscriber_new' ) );
		add_action( 'wp_ajax_nopriv_noptin_process_ajax_subscriber', array( $this, 'add_subscriber_new' ) );

		// Log form impressions.
		add_action( 'wp_ajax_noptin_log_form_impression', array( $this, 'log_form_impression' ) );
		add_action( 'wp_ajax_nopriv_noptin_log_form_impression', array( $this, 'log_form_impression' ) );

		// Download forms.
		add_action( 'wp_ajax_noptin_download_forms', array( $this, 'download_forms' ) );

		// Save settings.
		add_action( 'wp_ajax_noptin_save_options', array( $this, 'save_options' ) );

		// Save rule.
		add_action( 'wp_ajax_noptin_save_automation_rule', array( $this, 'save_rule' ) );

		// Create a new automation.
		add_action( 'wp_ajax_noptin_setup_automation', array( $this, 'setup_automation' ) );

		// Delete campaign.
		add_action( 'wp_ajax_noptin_delete_campaign', array( $this, 'delete_campaign' ) );

		// Stop campaigns.
		add_action( 'wp_ajax_noptin_stop_campaign', array( $this, 'stop_campaign' ) );

		// Send a test email.
		add_action( 'wp_ajax_noptin_send_test_email', array( $this, 'send_test_email' ) );

		// Import subscribers.
		add_action( 'wp_ajax_noptin_import_subscribers', array( $this, 'import_subscribers' ) );
		add_action( 'wp_ajax_noptin_prepare_subscriber_fields', array( $this, 'prepare_subscriber_fields' ) );

		// Import forms.
		add_action( 'wp_ajax_noptin_import_forms', array( $this, 'import_forms' ) );

		// Double opt-in email.
		add_action( 'wp_ajax_noptin_send_double_optin_email', array( $this, 'send_double_optin_email' ) );

	}

	/**
	 * Logs a form view
	 *
	 * @access      public
	 * @since       1.1.1
	 * @return      void
	 */
	public function log_form_impression() {

		// Verify nonce.
		check_ajax_referer( 'noptin' );

		if ( ! empty( $_POST['form_id'] ) ) {
			$form_id = intval( $_POST['form_id'] );
			$count   = (int) get_post_meta( $form_id, '_noptin_form_views', true );
			update_post_meta( $form_id, '_noptin_form_views', $count + 1 );
		}
		exit;

	}

	/**
	 * Deletes a campaign
	 *
	 * @access      public
	 * @since       1.1.2
	 * @return      void
	 */
	public function delete_campaign() {

		// Verify nonce.
		check_ajax_referer( 'noptin_admin_nonce' );

		if ( ! current_user_can( get_noptin_capability() ) || empty( $_GET['id'] ) ) {
			wp_die( -1, 403 );
		}

		if ( wp_delete_post( trim( $_GET['id'] ), true ) ) {
			exit;
		}

		wp_die( -1, 500 );
	}

	/**
	 * Stop sending a campaign
	 *
	 * @access      public
	 * @since       1.2.3
	 * @return      void
	 */
	public function stop_campaign() {

		// Verify nonce.
		check_ajax_referer( 'noptin_admin_nonce' );

		if ( ! current_user_can( get_noptin_capability() ) || empty( $_GET['id'] ) ) {
			wp_die( -1, 403 );
		}

		$updated = wp_update_post(
			array(
				'ID'          => trim( $_GET['id'] ),
				'post_status' => 'draft',
			)
		);

		if ( ! empty( $updated ) ) {
			exit;
		}

		wp_die( -1, 500 );
	}

	/**
	 * Sets up a new automation
	 *
	 * @access      public
	 * @since       1.1.2
	 * @return      void
	 */
	public function setup_automation() {

		// Verify nonce.
		check_ajax_referer( 'noptin_campaign' );

		if ( ! current_user_can( get_noptin_capability() ) ) {
			wp_die( -1, 401 );
		}

		$data = stripslashes_deep( $_POST );
		unset( $data['_wpnonce'] );
		unset( $data['_wp_http_referer'] );
		unset( $data['action'] );

		if ( empty( $data['automation_name'] ) ) {
			$data['automation_name'] = __( 'No Name', 'newsletter-optin-box' );
		}

		if ( empty( $data['automation_type'] ) ) {
			wp_die( -1, 400 );
		}

		/**
		 * Filters email automation setup data.
		 * 
		 * @param array $data The automation setup data.
		 */
		$data = apply_filters( 'noptin_email_automation_setup_data', $data );

		// Create a new automation.
		$args = array(
			'post_title'   => $data['automation_name'],
			'post_content' => empty( $data['email_body'] ) ? '' : $data['email_body'],
			'post_status'  => 'draft',
			'post_type'    => 'noptin-campaign',
		);

		unset( $data['automation_name'] );
		unset( $data['email_body'] );

		$data['campaign_type'] = 'automation';
		$args['meta_input']    = $data;

		$id = wp_insert_post( $args, true );

		// If an error occured, return it.
		if ( is_wp_error( $id ) ) {
			wp_die( $id, 400 );
		}

		/**
		 * Runs before displaying automation settings
		 */
		do_action( 'noptin_setup_automation', $id, $data );

		echo get_noptin_automation_campaign_url( $id );
		exit;

	}

	/**
	 * Sends a double opt-in email to a subscriber.
	 *
	 * @access      public
	 * @since       1.2.7
	 * @return      void
	 */
	public function send_double_optin_email() {

		// Ensure the nonce is valid...
		check_ajax_referer( 'noptin_subscribers' );

		// ... and that the user can import subscribers.
		if ( ! current_user_can( get_noptin_capability() ) ) {
			wp_die( -1, 403 );
		}

		if ( empty( $_POST['email'] ) || ! is_email( $_POST['email'] ) ) {
			wp_send_json_error( __( 'Subscriber not found', 'newsletter-optin-box' ) );
			exit;
		}

		$subscriber = new Noptin_Subscriber( sanitize_email( $_POST['email'] ) );
		if ( ! $subscriber->exists() ) {
			wp_send_json_error( __( 'This subscriber no longer exists. They might have been deleted.', 'newsletter-optin-box' ) );
			exit;
		}

		if ( ! $subscriber->send_confirmation_email() ) {
			wp_send_json_error( __( 'An error occured while sending the double opt-in email.', 'newsletter-optin-box' ) );
			exit;
		}

		wp_send_json_success( __( 'A double opt-in confirmation email has been sent to the subscriber.', 'newsletter-optin-box' ) );

	}

	/**
	 * Prepares subscriber fields for import.
	 *
	 * @access public
	 * @since  1.5.6
	 */
	public function prepare_subscriber_fields() {

		// Ensure the nonce is valid...
		check_ajax_referer( 'noptin_subscribers' );

		// ... and that the user can import subscribers.
		if ( ! current_user_can( get_noptin_capability() ) ) {
			wp_die( -1, 403 );
		}

		// Maybe abort early.
		if ( empty( $_POST['headers'] ) ) {
			wp_send_json_error( __( 'CSV files does not have valid headers', 'newsletter-optin-box' ) );
		}

		// Prepare headers and try to guess where possible.
		$headers = noptin_clean( $_POST['headers'] );
		$fields  = Noptin_Hooks::guess_fields( array_combine( $headers, $headers ) );

		ob_start();
		require_once plugin_dir_path( __FILE__ ) . 'admin/views/map-imported-subscriber-fields.php';
		wp_send_json_success( ob_get_clean() );
	}

	/**
	 * Imports subscribers
	 *
	 * @access      public
	 * @since       1.2.2
	 * @return      void
	 */
	public function import_subscribers() {
		global $wpdb;

		// Ensure the nonce is valid...
		check_ajax_referer( 'noptin_subscribers' );

		// ... and that the user can import subscribers.
		if ( ! current_user_can( get_noptin_capability() ) ) {
			wp_die( -1, 403 );
		}

		// Maybe abort early.
		if ( ! isset( $_POST['data'] ) ) {
			wp_die( -1, 400 );
		}

		$data = json_decode( wp_unslash( $_POST['data'] ), true );

		if ( empty( $data['rows'] ) ) {
			wp_die( -1, 400 );
		}

		$table    = get_noptin_subscribers_table_name();
		$imported = 0;
		$failed   = 0;
		$updated  = 0;
		$skipped  = 0;

		foreach ( $data['rows'] as $row ) {
			$row        = array_combine( wp_unslash( $data['headers'] ), $row );
			$subscriber = array();
			$updating   = false;

			foreach ( $data['mapped'] as $noptin => $_imported ) {

				// Manually entered.
				if ( '-1' == $_imported ) {

					if ( isset( $data['custom'][ $noptin ] ) ) {
						$subscriber[ $noptin ] = $data['custom'][ $noptin ];
					}

					continue;
				}

				// Mapped.
				if ( isset( $row[ $_imported ] ) && '' !== $row[ $_imported ] && null !== $row[ $_imported ] ) {
					$subscriber[ $noptin ] = $row[ $_imported ];

					if ( 'active' == $noptin && is_numeric( $_imported ) ) {
						$subscriber[ $noptin ] = (int) $subscriber[ $noptin ] == '1';
					}

					if ( 'confirmed' == $noptin && is_numeric( $_imported ) ) {
						$subscriber[ $noptin ] = (int) $subscriber[ $noptin ] == '1';
					}

					if ( '_subscriber_via' == $noptin && ( empty( $subscriber[ $noptin ] ) || '-1' == $_imported ) ) {
						$subscriber[ $noptin ] = 'import';
					}
				}

			}

			if ( empty( $subscriber['email'] ) || ! is_email( $subscriber['email'] ) ) {
				$failed ++;
				continue;
			}

			if ( isset( $subscriber['confirmed'] ) ) {

				$subscriber['confirmed'] = strtolower( $subscriber['confirmed'] );
				if ( ( is_numeric( $subscriber['confirmed'] ) && 0 === ( int ) $subscriber['confirmed'] ) || 
					false         === $subscriber['confirmed'] || 
					'false'       === $subscriber['confirmed'] || 
					'unconfirmed' === $subscriber['confirmed'] || 
					''            === $subscriber['confirmed'] || 
					'no'          === $subscriber['confirmed'] ) {
					$subscriber['confirmed'] = 0;
				} else {
					$subscriber['confirmed'] = 1;
				}

			}

			if ( isset( $subscriber['active'] ) ) {

				$subscriber['active'] = strtolower( $subscriber['active'] );
				if ( ( is_numeric( $subscriber['active'] ) && 1 === ( int ) $subscriber['active'] ) || 
					true         === $subscriber['active'] || 
					'true'       === $subscriber['active'] || 
					'subscribed' === $subscriber['active'] || 
					'active'     === $subscriber['active'] || 
					'yes'        === $subscriber['active'] ) {
					$subscriber['active'] = 0;
				} else {
					$subscriber['active'] = 1;
				}

			}

			$_subscriber = get_noptin_subscriber( $subscriber['email'] );
			if ( $_subscriber->exists() ) {

				if ( empty( $data['update'] ) ) {
					$skipped ++;
					continue;
				}

				$result   = update_noptin_subscriber( $_subscriber->id, $subscriber, true );
				$updating = true;
			} else {
				$result = add_noptin_subscriber( $subscriber, true );
			}

			if ( true === $result || is_numeric( $result ) ) {

				if ( $updating ) {
					$updated ++;
				} else {
					$imported ++;
				}

				do_action( 'noptin_after_import_subscriber', $updating ? $_subscriber->id : $result, $subscriber, $updating );
			} else {
				$failed ++;
			}

		}

		wp_send_json_success( compact( 'imported', 'updated', 'failed', 'skipped' ) );

	}

	/**
	 * Imports forms
	 *
	 * @access      public
	 * @since       1.2.6
	 * @return      void
	 */
	public function import_forms() {

		// Ensure the nonce is valid...
		check_ajax_referer( 'noptin_admin_nonce' );

		// ... and that the user can import subscribers.
		if ( ! current_user_can( get_noptin_capability() ) ) {
			wp_die( -1, 403 );
		}

		// Maybe abort early.
		if ( ! isset( $_POST['forms'] ) ) {
			wp_die( -1, 400 );
		}

		// Prepare forms.
		$forms = json_decode( stripslashes_deep( $_POST['forms'] ), true );

		if ( ! is_array( $forms ) ) {
			_e( 'Invalid export file', 'newsletter-optin-box' );
			exit;
		}

		foreach ( $forms as $form ) {
			$form['id'] = null;
			$form       = new Noptin_Form( $form );
			$form->create();
		}

		wp_send_json_success( true );
		exit;

	}

	/**
	 * Sends a test email
	 *
	 * @access      public
	 * @since       1.1.2
	 * @return      void
	 */
	public function send_test_email() {

		// Verify nonce.
		check_ajax_referer( 'noptin-edit-newsletter', 'noptin-edit-newsletter-nonce' );

		if ( ! current_user_can( get_noptin_capability() ) ) {
			wp_die( -1, 403 );
		}

		// Prepare data.
		$data = $_POST;

		unset( $data['_wpnonce'] );
		unset( $data['_wp_http_referer'] );
		unset( $data['action'] );

		// Remove slashes.
		$data = stripslashes_deep( $data );

		// Ensure a valid test email has been provided.
		if ( empty( $data['email'] ) || ! is_email( $data['email'] ) ) {
			wp_send_json_error( __( 'Please provide a valid email address', 'newsletter-optin-box' ) );
			exit;
		}

		$data['email'] = sanitize_email( $data['email'] );

		// Subject, body and preview text.
		if ( empty( $data['email_subject'] ) && empty( $data['subject'] ) ) {
			wp_send_json_error( __( 'You need to provide a subject for your email.', 'newsletter-optin-box' ) );
			exit;
		}

		if ( empty( $data['email_subject'] ) ) {
			$data['email_subject'] = $data['subject'];
		}

		$data['email_subject'] = '[TEST] ' . $data['email_subject'];

		if ( empty( $data['email_body'] ) ) {
			wp_send_json_error( __( 'The email body cannot be empty.', 'newsletter-optin-box' ) );
			exit;
		}

		// Is there a subscriber with that email?
		$subscriber = new Noptin_Subscriber( $data['email'] );
		$merge_tags = array();

		if ( $subscriber->exists() ) {
			$merge_tags = $subscriber->to_array();

			$merge_tags['unsubscribe_url'] = get_noptin_action_url( 'unsubscribe', $subscriber->confirm_key );

			$meta = $subscriber->get_meta();
			foreach ( $meta as $key => $values ) {

				if ( isset( $values[0] ) && is_string( $values[0] ) ) {
					$merge_tags[ $key ] = esc_html( $values[0] );
				}

			}
		}

		$data['merge_tags'] = $merge_tags;

		/**
		 * Filters the newsletter test email data.
		 * 
		 * @param array $data The test email data.
		 */
		$data = apply_filters( 'noptin_test_email_data', $data );

		if ( noptin()->mailer->prepare_then_send( $data ) ) {
			wp_send_json_success( __( 'Your test email has been sent', 'newsletter-optin-box' ) );
		}

		wp_send_json_error( __( 'Could not send the test email', 'newsletter-optin-box' ) );

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
			return;
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
					'type'      => array(
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
				echo __( 'This form is in-active.', 'newsletter-optin-box' );
				exit;
			}

			$fields = $form->fields;
		}

		$filtered = array();

		// Check gdpr.
		if ( is_object( $form ) && $form->gdprCheckbox && empty( $_POST['noptin_gdpr_checkbox'] ) ) {
			echo __( 'You must consent to receive promotional emails.', 'newsletter-optin-box' );
			exit;
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
				die( __( 'Ensure that you fill all required fields.', 'newsletter-optin-box' ) );
			}

			// Sanitize email fields.
			if ( 'email' === $type && ! empty( $value ) ) {

				$value = sanitize_email( $value );
				if ( empty( $value ) ) {
					die( __( 'That email address is not valid.', 'newsletter-optin-box' ) );
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
			$filtered['_subscriber_via'] = $form->ID;
		}

		/**
		 * Filters subscriber details when adding a new subscriber via ajax.
		 * 
		 * @since 1.2.4
		 */
		$filtered = apply_filters( 'noptin_add_ajax_subscriber_filter_details', wp_unslash( $filtered ), $form );
		$inserted = add_noptin_subscriber( $filtered );

		if ( is_string( $inserted ) ) {
			die( $inserted );
		}

		do_action( 'noptin_add_ajax_subscriber', $inserted, $form );

		$result = array(
			'action' => 'msg',
			'msg'    => get_noptin_option( 'success_message' ),
		);

		if ( empty( $result['msg'] ) ) {
			$result['msg'] = esc_html__( 'Thanks for subscribing to the newsletter', 'newsletter-optin-box' );
		}

		if ( is_object( $form ) ) {

			$count = (int) get_post_meta( $form->ID, '_noptin_subscribers_count', true );
			update_post_meta( $form->ID, '_noptin_subscribers_count', $count + 1 );

			// msg.
			if ( $form->subscribeAction === 'message' ) {
				$result['msg'] = $form->successMessage;
			} else {
				// redirects.
				$result['action']   = 'redirect';
				$result['redirect'] = $form->redirectUrl;
			}
	
		}

		$result['msg'] = add_noptin_merge_tags( $result['msg'], get_noptin_subscriber_merge_fields( $inserted ) );

		wp_send_json( $result );
		exit;

	}
	// TODO: Add redirect_url && success message to shortcode, add novalidate to form, use filters to match the functionality of the above method.
	/**
	 * Adds a new subscriber via ajax
	 *
	 * @access      public
	 * @since       1.6.0
	 * @return      void
	 */
	public function add_subscriber_new() {

		// Verify nonce.
		if ( noptin_verify_subscription_nonces() ) {
			check_ajax_referer( 'noptin_subscription_nonce' );
		}

		// avoid bot submissions.
		if ( ! empty( $_POST['noptin_ign'] ) ) {
			return;
		}

		/**
		 * Fires before a subscriber is added via ajax.
		 *
		 * @since 1.2.4
		 */
		do_action( 'noptin_before_add_ajax_subscriber' );

		// Prepare form data.
		$source     = empty( $_POST['source'] ) ? '' : sanitize_text_field( wp_unslash( $_POST['source'] ) );
		$fields     = empty( $_POST['noptin_fields'] ) ? array() : wp_unslash( $_POST['noptin_fields'] );
		$misc       = empty( $_POST['noptin_misc'] ) ? array() : wp_unslash( $_POST['noptin_misc'] );
		$to_process = empty( $_POST['processed_fields'] ) ? 'email' : wp_unslash( $_POST['processed_fields'] );
		$to_process = apply_filters( 'noptin_custom_fields_to_process', array_map( 'get_noptin_custom_field', noptin_parse_list( $to_process ) ), $source );
		$subscriber = array();

		// Process form fields.
		foreach ( $to_process as $custom_field ) {

			if ( empty( $custom_field ) ) {
				continue;
			}

			// Ensure required fields are filled.
			$merge_tag = $custom_field['merge_tag'];
			if ( ! empty( $custom_field['required'] ) && ( ! isset( $fields[ $merge_tag ] ) || '' === $fields[ $merge_tag ] ) ) {
				wp_send_json_error(
					sprintf(
						__( 'The "%s" field is required.', 'newsletter-optin-box' ),
						sanitize_text_field( $custom_field['label'] )
					)
				);
			}

			$value = isset( $fields[ $merge_tag ] ) ? $fields[ $merge_tag ] : '';

			// Checkboxes should always be 0 or 1.
			if ( 'checkbox' === $custom_field['type'] ) {
				$value = (int) ! empty( $value );
			}

			$subscriber[ $merge_tag ] = $value;
	
		}

		// An email address is required.
		if ( empty( $subscriber['email'] ) || ! is_email( $subscriber['email'] ) ) {
			wp_send_json_error( __( 'Missing or invalid email address.', 'newsletter-optin-box' ) );
		}

		// Add the subscriber's IP address...
		$address = noptin_get_user_ip();
		if ( ! empty( $address ) && '::1' !== $address ) {
			$subscriber['ip_address'] = $address;
		}

		// ... the conversion page...
		if ( ! empty( $_POST['conversion_page'] ) ) {
			$subscriber['conversion_page'] = esc_url_raw( trim( wp_unslash( $_POST['conversion_page'] ) ) );
		}

		// ... and the source.
		if ( ! empty( $source ) ) {
			$subscriber['_subscriber_via'] = noptin_clean( $source );
		}

		// Finally, add connection data.
		$subscriber = Noptin_Hooks::add_connections( $subscriber, $misc );

		/**
		 * Filters subscriber details when adding a new subscriber via ajax.
		 *
		 * @param array $subscriber Subscriber details
		 * @param string $source Subscriber source
		 * @since 1.6.0
		 */
		$subscriber    = apply_filters( 'noptin_ajax_subscriber_details', $subscriber, $source );
		$subscriber_id = add_noptin_subscriber( $subscriber );

		// Check if an error occured while registering the subscriber.
		if ( is_string( $subscriber_id ) ) {
			wp_send_json_error( $subscriber_id );
		}

		// Fired after a subscriber is added to the newsletter.
		do_action( 'noptin_add_ajax_subscriber', $subscriber_id, $source );

		// Either display a success message or redirect to a success URL.
		if ( ! empty( $_POST['redirect_url'] ) ) {

			$result = array(
				'action'   => 'redirect',
				'redirect' => esc_url_raw( trim( wp_unslash( $_POST['redirect_url'] ) ) ),
			);

		} else {

			// Either fetch the default success msg or use an overidden success message.
			$result = array(
				'action' => 'msg',
				'msg'    => isset( $_POST['success_msg'] ) ? esc_html( trim( wp_unslash( $_POST['success_msg'] ) ) ) : get_noptin_option( 'success_message' ),
			);

			// Ensure the success message is not empty.
			if ( empty( $result['msg'] ) ) {
				$result['msg'] = esc_html__( 'Thanks for subscribing to the newsletter', 'newsletter-optin-box' );
			}

			// Replace any merge tags in the success message.
			$result['msg'] = add_noptin_merge_tags( $result['msg'], get_noptin_subscriber_merge_fields( $subscriber_id ) );

		}

		// Update form subscriptions.
		if ( is_numeric( $source ) ) {
			$count = (int) get_post_meta( $source, '_noptin_subscribers_count', true );
			update_post_meta( $source, '_noptin_subscribers_count', $count + 1 );
		}

		// Send back the result.
		wp_send_json_success( $result );
		exit;

	}

	/**
	 * Saves settings
	 *
	 * @access      public
	 * @since       1.0.8
	 */
	public function save_options() {

		if ( ! current_user_can( get_noptin_capability() ) ) {
			wp_die( -1, 403 );
		}

		// Check nonce.
		check_ajax_referer( 'noptin_admin_nonce' );

		/**
		 * Runs before saving a settings
		 */
		do_action( 'noptin_before_save_options' );

		// Prepare settings.
		$settings = stripslashes_deep( $_POST['state'] );
		unset( $settings['noptin_admin_nonce'] );
		unset( $settings['saved'] );
		unset( $settings['error'] );
		unset( $settings['currentTab'] );
		unset( $settings['currentSection'] );
		unset( $settings['openSections'] );
		unset( $settings['fieldTypes'] );

		$settings = map_deep( $settings, 'noptin_sanitize_booleans' );

		if ( ! empty( $settings['custom_fields'] ) ) {

			foreach ( $settings['custom_fields'] as $index => $custom_field ) {
				if ( isset( $custom_field['new'] ) ) {
					unset( $custom_field['new'] );
					$settings['custom_fields'][ $index ] = $custom_field;
				}
			}
		}

		/**
		 * Sanitizes noptin settings.
		 * 
		 * @param array $settings Noptin settings.
		 */
		$settings = apply_filters( 'noptin_sanitize_settings', $settings );

		// Check if we're tracking.
		$is_tracking = get_noptin_option( 'allow_tracking', false );

		if ( ! $is_tracking && ! empty( $settings['allow_tracking'] ) ) {
			wp_schedule_single_event( time() + 10, 'noptin_com_tracker_send_event', array( true ) );
		}

		// Save them.
		update_noptin_options( $settings );

		wp_send_json_success( 1 );

	}

	/**
	 * Saves rules
	 *
	 * @access      public
	 * @since       1.3.0
	 */
	public function save_rule() {

		if ( ! current_user_can( get_noptin_capability() ) ) {
			wp_die( -1, 403 );
		}

		// Check nonce.
		check_ajax_referer( 'noptin_automation_rules' );

		/**
		 * Runs before saving rules
		 */
		do_action( 'noptin_before_save_automation_rule' );

		// Prepare the rule.
		$trigger_settings = array();
		if ( ! empty( $_POST['trigger_settings'] ) ) {
			$trigger_settings = stripslashes_deep( $_POST['trigger_settings'] );
		}

		$action_settings = array();
		if ( ! empty( $_POST['action_settings'] ) ) {
			$action_settings = stripslashes_deep( $_POST['action_settings'] );
		}

		// Save them.
		noptin()->automation_rules->update_rule( $_POST['id'], compact( 'action_settings', 'trigger_settings' ) );

		wp_send_json_success( 1 );

	}

	/**
	 * Downloads optin forms
	 *
	 * @access      public
	 * @since       1.2.6
	 */
	public function download_forms() {

		if ( ! current_user_can( get_noptin_capability() ) ) {
			wp_die( -1, 403 );
		}

		// Check nonce.
		$nonce = $_GET['admin_nonce'];
		if ( ! wp_verify_nonce( $nonce, 'noptin_admin_nonce' ) ) {
			_e ( 'Reload the page and try again.', 'newsletter-optin-box' );
			exit;
		}

		/**
		 * Runs before downloading opt-in forms.
		 *
		 */
		do_action( 'noptin_before_download_forms' );

		$output = fopen( 'php://output', 'w' ) or die( 'Unsupported server' );

		// Let the browser know what content we're streaming and how it should save the content.
		$time = time();
		header( "Content-Type:application/json" );
		header( "Content-Disposition:attachment;filename=noptin-forms-$time.json" );

		$forms = array();

		foreach( get_noptin_optin_forms() as $form ) {
			$forms[] = $form->get_all_data();
		}

		echo wp_json_encode( wp_unslash( $forms ) );

		fclose( $output );

		/**
		 * Runs after after downloading opt-in forms.
		 *
		 */
		do_action( 'noptin_after_download_forms' );

		exit; // This is important.
	}

}
