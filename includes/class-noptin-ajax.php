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
		add_action( 'wp_ajax_noptin_new_subscriber', array( $this, 'add_subscriber' ) );
		add_action( 'wp_ajax_nopriv_noptin_new_subscriber', array( $this, 'add_subscriber' ) );
		add_action( 'wp_ajax_noptin_admin_add_subscriber', array( $this, 'admin_add_subscriber' ) );

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

		// Import forms.
		add_action( 'wp_ajax_noptin_import_forms', array( $this, 'import_forms' ) );

		// Delete subscribers.
		add_action( 'wp_ajax_noptin_delete_all_subscribers', array( $this, 'delete_all_subscribers' ) );

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

		$subscriber = new Noptin_Subscriber( $_POST['email'] );
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
	 * Deletes all subscribers.
	 *
	 * @access      public
	 * @since       1.2.4
	 * @return      void
	 */
	public function delete_all_subscribers() {
		global $wpdb;

		// Ensure the nonce is valid...
		check_ajax_referer( 'noptin_subscribers' );

		// ... and that the user can import subscribers.
		if ( ! current_user_can( get_noptin_capability() ) ) {
			wp_die( -1, 403 );
		}

		// Check if we're only deleting a few subscribers.
		if ( ! empty( $_POST['data'] ) && ( ! empty( $_POST['data']['meta_key'] ) || ! empty( $_POST['data']['_subscriber_via'] ) ) ) {

			$data = $_POST['data'];
			$delete = array( 'paged', 'orderby', 'order', 'page' );

			foreach ( $delete as $key ) {
				if ( isset( $data[$key] ) ) {
					unset( $data[$key] );
				}
			}

			if ( empty( $data['meta_query'] ) || ! is_array( $data['meta_query'] ) ) {
				$data['meta_query'] = array();
			}

			if ( ! empty( $data['_subscriber_via'] ) ) {
				$data['meta_query'][] = array(
					'key'   => '_subscriber_via',
					'value' => $data['_subscriber_via'],
				);
				unset( $data['_subscriber_via'] );
			}

			if ( ! empty( $data['_subscriber_via'] ) ) {
				$data['meta_query'][] = array(
					'key'   => '_subscriber_via',
					'value' => $data['_subscriber_via'],
				);
				unset( $data['_subscriber_via'] );
			}

			$data['fields'] = array( 'id' );
			$data['count_total'] = false;
			$data['number'] = '-1';

			$subscribers = new Noptin_Subscriber_Query( $data );

            foreach ( $subscribers->get_results() as $subscriber ) {
				delete_noptin_subscriber( $subscriber );
			}

			exit;
		}

		$table    = get_noptin_subscribers_table_name();
		$wpdb->query( "TRUNCATE TABLE $table" );

		$table    = get_noptin_subscribers_meta_table_name();
		$wpdb->query( "TRUNCATE TABLE $table" );

		$wpdb->delete( $wpdb->usermeta, array( 'meta_key' => 'noptin_subscriber_id' ), '%s' );

		do_action( 'noptin_delete_all_subscribers' );
		exit;
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
		if ( ! isset( $_POST['subscribers'] ) ) {
			wp_die( -1, 400 );
		}

		// Prepare subscribers.
		$subscribers = stripslashes_deep( $_POST['subscribers'] );

		// Are there subscribers?
		if ( empty( $subscribers ) ) {
			wp_send_json_success( array(
				'imported'	=> 0,
				'skipped'	=> 0,
			) );
			exit;
		}

		$table    = get_noptin_subscribers_table_name();
		$imported = 0;
		$skipped  = 0;

		foreach ( $subscribers as $subscriber ) {
			if( ! is_array( $subscriber ) ) {
				$skipped ++;
				log_noptin_message( __( 'Import error: not an array. Skipping.', 'newsletter-optin-box' ) );
				continue;
			}

			$subscriber = apply_filters( 'noptin_format_imported_subscriber_fields', $subscriber );

			// Ensure that there is a unique email address.
			if ( empty( $subscriber['email'] ) ) {
				log_noptin_message( __( 'Import error: email not found. Skipping.', 'newsletter-optin-box' ) );
				$skipped ++;
				continue;
			}

			if ( ! is_email( $subscriber['email'] )  ) {
				log_noptin_message( sprintf(
					__( 'Import skipping %s: Invalid email' ),
					esc_html( $subscriber['email'] )
				));
				$skipped ++;
				continue;
			}

			// Ensure that there is a unique email address.
			if ( noptin_email_exists( $subscriber['email'] ) ) {
				log_noptin_message( sprintf(
					__( 'Import skipping %s: Subscriber already exists' ),
					esc_html( $subscriber['email'] )
				));
				$skipped ++;
				continue;
			}

			// Sanitize email status
			if( ! isset( $subscriber['confirmed'] ) ) {
				$subscriber['confirmed'] = 0;
			}

			if( is_string( $subscriber['confirmed'] ) ) {
				$subscriber['confirmed'] = strtolower( $subscriber['confirmed'] );
			}

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

			// Sanitize subscriber status

			if( ! isset( $subscriber['active'] ) ) {
				$subscriber['active'] = 1;
			}

			if( is_string( $subscriber['active'] ) ) {
				$subscriber['active'] = strtolower( $subscriber['active'] );
			}
			if ( 
				( is_numeric( $subscriber['active'] ) && 1 === ( int ) $subscriber['active'] )  || 
				true         === $subscriber['active'] ||
				'true'       === $subscriber['active'] ||
				'subscribed' === $subscriber['active'] ||
				'active'     === $subscriber['active'] ||
				'yes'        === $subscriber['active'] ) {
				$subscriber['active'] = 0;
			} else {
				$subscriber['active'] = 1;
			}

			// Maybe split name into first and last.
			if ( isset( $subscriber['name'] ) ) {
				$names = noptin_split_subscriber_name( $subscriber['name'] );

				$subscriber['first_name']   = empty( $subscriber['first_name'] ) ? $names[0] : trim( $subscriber['first_name'] );
				$subscriber['second_name']  = empty( $subscriber['second_name'] ) ? $names[1] : trim( $subscriber['second_name'] );
				unset( $subscriber['name'] );
			}

			// Save the main subscriber fields.
			$database_fields = array(
				'email'        => $subscriber['email'],
				'first_name'   => empty( $subscriber['first_name'] ) ? '' : $subscriber['first_name'],
				'second_name'  => empty( $subscriber['second_name'] ) ? '' : $subscriber['second_name'],
				'confirm_key'  => empty( $subscriber['confirm_key'] ) ? md5( $subscriber['email'] . wp_generate_password( 32 ) ) : $subscriber['confirm_key'],
				'date_created' => empty( $subscriber['date_created'] ) ? date( 'Y-m-d', current_time( 'timestamp' ) ) : $subscriber['date_created'],
				'confirmed'	   => $subscriber['confirmed'],
				'active'	   => $subscriber['active'],
			);

			if ( ! $wpdb->insert( $table, $database_fields, '%s' ) ) {
				$skipped ++;
				log_noptin_message( __( 'Import error:', 'newsletter-optin-box' ) . ' ' . $wpdb->last_error );
				continue;
			}

			$id = $wpdb->insert_id;

			$meta = $subscriber['meta'];
			unset( $subscriber['meta'] );

			$extra_meta = array_diff_key( $subscriber, $database_fields );
			foreach ( $extra_meta as $field => $value ) {

				if ( is_null( $value ) ) {
					continue;
				}
				if ( ! isset( $meta[ $field ] ) ) {
					$meta[ $field ] = array();
				}

				$meta[ $field ][] = $value;

			}

			foreach ( $meta as $field => $value ) {

				if ( ! is_array( $value ) ) {
					$value = array( $value );
				}

				foreach ( $value as $val ) {
					update_noptin_subscriber_meta( $id, $field, $val );
				}
			}

			update_noptin_subscriber_meta( $id, '_subscriber_via', 'import' );
			do_action( 'noptin_after_import_subscriber', $id, $subscriber, $meta );

			$imported += 1;

		}

		wp_send_json_success( array(
			'imported'	=> $imported,
			'skipped'	=> $skipped,
		) );
		exit;

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

		foreach ( $fields as $field ) {

			$type = $field['type']['type'];

			// Prepare the field name.
			$name = '';

			if ( 'email' == $type ) {
				$name = 'email';
			}

			if ( 'first_name' == $type ) {
				$name = 'first_name';
			}

			if ( 'last_name' == $type ) {
				$name = 'last_name';
			}

			if ( 'name' == $type ) {
				$name = 'name';
			}

			if ( empty( $name ) ) {
				$name = $field['type']['name'];
			}

			$key = esc_attr( $field['key'] );

			if ( isset( $_POST[ $key ] ) ) {
				$value = $_POST[ $key ];
			} else {
				$value = '';
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

			// Sanitize checkboxes.
			if ( 'checkbox' === $type && empty( $value ) ) {
				$value = __( 'No', 'newsletter-optin-box' );
			}

			// Sanitize text fields.
			if ( 'textarea' !== $type && ! is_array( $value ) ) {
				$value = sanitize_text_field( urldecode( $value ) );
			} else {
				if ( ! is_array( $value ) ) {
					$value = esc_html( urldecode( $value ) );
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

		wp_send_json( $result );
		exit;

	}

	/**
	 * Manually add a new subscriber via ajax
	 *
	 * @access      public
	 * @since       1.2.6
	 * @return      void
	 */
	public function admin_add_subscriber() {

		// Ensure the nonce is valid...
		check_ajax_referer( 'noptin_subscribers' );

		// ... and that the user can import subscribers.
		if ( ! current_user_can( get_noptin_capability() ) ) {
			wp_die( -1, 403 );
		}

		$fields = array(
			'name'            => $_POST['name'],
			'email'           => $_POST['email'],
			'_subscriber_via' => 'manual',
		);

		$inserted = add_noptin_subscriber( $fields );

		if ( is_string( $inserted ) ) {
			die( $inserted );
		}

		do_action( 'noptin_after_admin_add_subscriber', $inserted );

		wp_send_json_success( true );
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
		$_settings = stripslashes_deep( $_POST['state'] );
		unset( $_settings['noptin_admin_nonce'] );
		unset( $_settings['saved'] );
		unset( $_settings['error'] );
		unset( $_settings['currentTab'] );

		$settings = array();
		foreach ( $_settings as $key => $val ) {

			if ( 'false' === $val ) {
				$val = false;
			}

			if ( 'true' === $val ) {
				$val = true;
			}

			$settings[ $key ] = $val;
		}

		/**
		 * Sanitizes noptin settings.
		 * 
		 * @param array $settings Noptin settings.
		 */
		$settings = apply_filters( 'noptin_sanitize_settings', $settings );

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
