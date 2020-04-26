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

		// Download subscribers.
		add_action( 'wp_ajax_noptin_download_subscribers', array( $this, 'download_subscribers' ) );

		// Download forms.
		add_action( 'wp_ajax_noptin_download_forms', array( $this, 'download_forms' ) );

		// Save settings.
		add_action( 'wp_ajax_noptin_save_options', array( $this, 'save_options' ) );

		// Create a new automation.
		add_action( 'wp_ajax_noptin_setup_automation', array( $this, 'setup_automation' ) );

		// Delete campaign.
		add_action( 'wp_ajax_noptin_delete_campaign', array( $this, 'delete_campaign' ) );

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

		if ( ! current_user_can( 'manage_options' ) || empty( $_GET['id'] ) ) {
			wp_die( -1, 403 );
		}

		if ( wp_delete_post( trim( $_GET['id'] ), true ) ) {
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

		if ( ! current_user_can( 'manage_options' ) ) {
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

		$table    = get_noptin_subscribers_table_name();
		$wpdb->query( "TRUNCATE TABLE $table" );

		$table    = get_noptin_subscribers_meta_table_name();
		$wpdb->query( "TRUNCATE TABLE $table" );

		$wpdb->delete( $wpdb->usermeta, array( 'meta_key' => 'noptin_subscriber_id' ), '%s' );
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
				continue;
			}

			$subscriber = apply_filters( 'noptin_format_imported_subscriber_fields', $subscriber );

			// Ensure that there is a unique email address.
			if ( empty( $subscriber['email'] ) ) {
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
				'date_created' => empty( $subscriber['date_created'] ) ? date( 'Y-m-d' ) : $subscriber['date_created'],
				'confirmed'	   => $subscriber['confirmed'],
				'active'	   => $subscriber['active'],
			);

			if ( ! $wpdb->insert( $table, $database_fields, '%s' ) ) {
				$skipped ++;
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
		check_ajax_referer( 'noptin_campaign', 'noptin_campaign_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
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
		if ( empty( $data['email_subject'] ) ) {
			wp_send_json_error( __( 'You need to provide a subject for your email.', 'newsletter-optin-box' ) );
			exit;
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
		check_ajax_referer( 'noptin' );

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
					'type'    => array(
						'label' => __( 'Email Address', 'newsletter-optin-box' ),
						'name'  => 'email',
						'type'  => 'email',
					),
					'require' => 'true',
					'key'     => 'noptin_email_key',
				),
			);

		} else {

			// Get the form.
			$form   = noptin_get_optin_form( $_POST['noptin_form_id'] );
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

			$field_label = $field['type']['label'];

			if ( isset( $_POST[ $name ] ) ) {
				$value = $_POST[ $name ];
			} else {
				$value = 0;
			}

			// required fields.
			if ( 'true' == $field['require'] && empty( $value ) ) {
				die(
					sprintf(
						'%s is required',
						esc_html( $field_label )
					)
				);
			}

			// Sanitize email fields.
			if ( 'email' == $type && ! empty( $value ) ) {

				$value = sanitize_email( $value );
				if ( empty( $value ) ) {

					die(
						sprintf(
							'%s is not valid',
							esc_html( $field_label )
						)
					);

				}
			}

			// Sanitize text fields.
			if ( 'textarea' != $type && ! is_array( $value ) ) {
				$value = sanitize_text_field( $value );
			} else {
				if ( ! is_array( $value ) ) {
					$value = esc_html( $value );
				}
			}

			$filtered[ $name ] = $value;

		}

		if ( ! empty( $_POST['ipAddress'] ) ) {
			$address                = trim( sanitize_text_field( $_POST['ipAddress'] ) );
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
		$filtered = apply_filters( 'noptin_add_ajax_subscriber_filter_details', $filtered, $form );
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

		if ( ! current_user_can( 'manage_options' ) ) {
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
	 * Downloads subscribers
	 *
	 * @access      public
	 * @since       1.0.5
	 */
	public function download_subscribers() {
		global $wpdb;

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1, 403 );
		}

		// Check nonce.
		$nonce = $_GET['admin_nonce'];
		if ( ! wp_verify_nonce( $nonce, 'noptin_admin_nonce' ) ) {
			echo __( 'Reload the page and try again.', 'newsletter-optin-box' );
			exit;
		}

		/**
		 * Runs before downloading subscribers.
		 *
		 * @param array $this The admin instance
		 */
		do_action( 'noptin_before_download_subscribers', $this );

		$output  = fopen( 'php://output', 'w' ) or die( 'Unsupported server' );

		$fields    = empty( $_GET['fields'] )    ? get_noptin_subscribers_fields() : $_GET['fields'];
		$file_type = empty( $_GET['file_type'] ) ? 'csv' : sanitize_text_field( $_GET['file_type'] );

		// Let the browser know what content we're streaming and how it should save the content.
		$name = time();
		header( "Content-Type:application/$file_type" );
		header( "Content-Disposition:attachment;filename=noptin-subscribers-$name.$file_type" );

		if ( empty( $_GET['file_type'] ) || 'csv' == $_GET['file_type'] ) {
			$this->download_subscribers_csv( $fields, $output );
		} else if( 'xml' == $_GET['file_type'] ) {
			$this->download_subscribers_xml( $fields, $output );
		}

		else {
			$this->download_subscribers_json( $fields, $output );
		}

		fclose( $output );

		/**
		 * Runs after after downloading.
		 *
		 * @param array $this The admin instance
		 */
		do_action( 'noptin_after_download_subscribers', $this );

		exit; // This is important.
	}

	/**
	 * Downloads optin forms
	 *
	 * @access      public
	 * @since       1.2.6
	 */
	public function download_forms() {

		if ( ! current_user_can( 'manage_options' ) ) {
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

	/**
	 * Downloads subscribers as csv
	 *
	 * @access      public
	 * @since       1.2.4
	 */
	public function download_subscribers_csv( $fields, $output ) {
		global $wpdb;

		// Retrieve subscribers.
		$table       = get_noptin_subscribers_table_name();
		$subscribers = $wpdb->get_results( "SELECT *  FROM $table" );

		// Output the csv column headers.
		fputcsv( $output, noptin_sanitize_title_slug( $fields ) );

		// Loop through 
		foreach ( $subscribers as $subscriber ) {
			$row  = array();

			// Fetch meta data.
			$meta = get_noptin_subscriber_meta( $subscriber->id );
			if ( ! is_array( $meta ) ) {
				$meta = array();
			}

			foreach ( $fields as $field ) {

				if ( $field === 'confirmed' ) {
					$row[] = intval( $subscriber->confirmed );
					continue;
				}

				if ( $field === 'active' ) {
					$row[] = empty( $subscriber->active ) ? 1 : 0;
					continue;
				}

				if ( $field === 'full_name' ) {
					$row[] = trim( $subscriber->first_name . ' ' . $subscriber->second_name );
					continue;
				}

				// Check if this is a core field.
				if ( isset( $subscriber->{$field} ) ) {
					$row[] = $subscriber->{$field};
					continue;
				}

				// Special meta field.
				if( isset( $meta[$field] ) ) {

					if ( 1 === count( $meta[$field] ) ) {
						$row[] = maybe_serialize( $meta[$field][0] );
					} else {
						$row[] = maybe_serialize( $meta[$field] );
					}

					continue;
				}

				// Missing value for the field.
				$row[] = '';
			}

			fputcsv( $output, $row );
		}

	}

	/**
	 * Downloads subscribers as json
	 *
	 * @access      public
	 * @since       1.2.4
	 */
	public function download_subscribers_json( $fields, $stream ) {
		global $wpdb;

		// Retrieve subscribers.
		$table       = get_noptin_subscribers_table_name();
		$subscribers = $wpdb->get_results( "SELECT *  FROM $table" );
		$output      = array();

		// Loop through 
		foreach ( $subscribers as $subscriber ) {
			$row  = array();

			// Fetch meta data.
			$meta = get_noptin_subscriber_meta( $subscriber->id );
			if ( ! is_array( $meta ) ) {
				$meta = array();
			}

			foreach ( $fields as $field ) {

				if ( $field === 'active' ) {
					$row[ $field ] = empty( $subscriber->active ) ? 1 : 0;
					continue;
				}

				if ( $field === 'confirmed' ) {
					$row[ $field ] = intval( $subscriber->confirmed );
					continue;
				}

				if ( $field === 'full_name' ) {
					$row[ $field ] = trim( $subscriber->first_name . ' ' . $subscriber->second_name );
					continue;
				}
				
				// Check if this is a core field.
				if ( isset( $subscriber->{$field} ) ) {
					$row[$field] = $subscriber->{$field};
					continue;
				}

				// Special meta field.
				if( isset( $meta[$field] ) ) {

					if ( 1 === count( $meta[$field] ) ) {
						$row[$field] = $meta[$field][0];
					} else {
						$row[$field] = $meta[$field];
					}

					continue;
				}

				// Missing value for the field.
				$row[$field] = null;
			}

			$output[] = $row;

		}
		
		fwrite( $stream, wp_json_encode( $output ) );

	}

	/**
	 * Downloads subscribers as xml
	 *
	 * @access      public
	 * @since       1.2.4
	 */
	public function download_subscribers_xml( $fields, $stream ) {
		global $wpdb;

		// Retrieve subscribers.
		$table       = get_noptin_subscribers_table_name();
		$subscribers = $wpdb->get_results( "SELECT *  FROM $table" );
		$output      = array();

		// Loop through 
		foreach ( $subscribers as $subscriber ) {
			$row  = array();

			// Fetch meta data.
			$meta = get_noptin_subscriber_meta( $subscriber->id );
			if ( ! is_array( $meta ) ) {
				$meta = array();
			}

			foreach ( $fields as $field ) {

				if ( $field === 'active' ) {
					$row[ $field ] = empty( $subscriber->active ) ? 1 : 0;
					continue;
				}

				if ( $field === 'confirmed' ) {
					$row[ $field ] = intval( $subscriber->confirmed );
					continue;
				}

				if ( $field === 'full_name' ) {
					$row[ $field ] = trim( $subscriber->first_name . ' ' . $subscriber->second_name );
					continue;
				}
				
				// Check if this is a core field.
				if ( isset( $subscriber->{$field} ) ) {
					$row[$field] = $subscriber->{$field};
					continue;
				}

				// Special meta field.
				if( isset( $meta[$field] ) ) {

					if ( 1 === count( $meta[$field] ) ) {
						$row[$field] = $meta[$field][0];
					} else {
						$row[$field] = $meta[$field];
					}

					continue;
				}

				// Missing value for the field.
				$row[$field] = null;
			}

			$output[] = $row;

		}
		
		$xml = new SimpleXMLElement('<?xml version="1.0"?><data></data>');
		$this->convert_array_xml( $output, $xml );

		fwrite( $stream, $xml->asXML() );

	}

	/**
	 * Converts subscribers array to xml
	 *
	 * @access      public
	 * @since       1.2.4
	 */
	public function convert_array_xml( $data, $xml ) {

		// Loop through 
		foreach ( $data as $key => $value ) {

			if ( is_array( $value ) ) {

				if( is_numeric( $key ) ){
					$key = 'item'.$key; //dealing with <0/>..<n/> issues
				}

				$subnode = $xml->addChild( $key );
				$this->convert_array_xml( $value, $subnode );

			} else {
				$xml->addChild( $key, htmlspecialchars( $value ) );
			}
		}

	}

}
