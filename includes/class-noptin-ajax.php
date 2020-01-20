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

		// Log form impressions.
		add_action( 'wp_ajax_noptin_log_form_impression', array( $this, 'log_form_impression' ) );
		add_action( 'wp_ajax_nopriv_noptin_log_form_impression', array( $this, 'log_form_impression' ) );

		// Download subscribers.
		add_action( 'wp_ajax_noptin_download_subscribers', array( $this, 'download_subscribers' ) );

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

		if ( ! empty( $_REQUEST['form_id'] ) ) {
			$form_id = intval( $_REQUEST['form_id'] );
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
			$data['automation_name'] = __( 'No Name' );
		}

		if ( empty( $data['automation_type'] ) ) {
			wp_die( -1, 400 );
		}

		// Filter automation setup data.
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

		// Prepare subscribers.
		$subscribers = stripslashes_deep( $_POST['subscribers'] );

		// Are there subscribers?
		if ( empty( $subscribers ) ) {
			wp_send_json_error( __( 'The import file is either empty or corrupted' ) );
			exit;
		}

		$table    = get_noptin_subscribers_table_name();
		$imported = 0;
		$mappings = array(
			'first name'		=> 'first_name',
			'second name'		=> 'second_name',
			'last name' 		=> 'second_name',
			'email address'		=> 'email',
			'email'				=> 'email',
			'active' 			=> 'active',
			'list status' 		=> 'active',
			'email confirmed' 	=> 'confirmed',
			'global status' 	=> 'confirmed',
			'subscribed on' 	=> 'date_created',
			'confirm key' 		=> 'confirm_key',
			'meta' 				=> 'meta',
		);

		foreach( $subscribers as $subscriber ) {
			
			// Prepare subscriber fields.
			foreach( $subscriber as $key => $value ) {
				$lowercase = strtolower( $key );

				if( isset( $mappings[ $lowercase ] ) ) {
					$subscriber[ $mappings[ $lowercase ] ] = $value;
					unset( $subscriber[ $key ] );
				}

			}

			// Ensure that there is a unique email address.
			if( empty( $subscriber[ 'email' ] ) || ! is_email( $subscriber['email'] ) || noptin_email_exists( $subscriber['email'] ) ) {
				continue;
			}

			// Sanitize email status
			if( empty( $subscriber['confirmed'] ) || 'false' == $subscriber['confirmed'] || 'unconfirmed'  == $subscriber['confirmed'] ) {
				$subscriber['confirmed'] = 0;
			} else {
				$subscriber['confirmed'] = 1;
			}

			// Sanitize subscriber status
			if( empty( $subscriber['active'] ) || 'true' == $subscriber['active'] || 'subscribed'  == $subscriber['active'] ) {
				$subscriber['active'] = 0;
			} else {
				$subscriber['active'] = 1;
			}

			// Save the main subscriber fields.
			$database_fields = array(
				'email'        => $subscriber['email'],
				'first_name'   => empty( $subscriber['first_name'] ) ? '' : $subscriber['first_name'],
				'second_name'  => empty( $subscriber['second_name'] ) ? '' : $subscriber['second_name'],
				'confirm_key'  => empty( $subscriber['confirm_key'] ) ? md5( $subscriber['email'] ) . wp_generate_password( 4, false ) : $subscriber['confirm_key'],
				'date_created' => empty( $subscriber['date_created'] ) ? date( 'Y-m-d' ) : $subscriber['date_created'],
				'confirmed'	   => $subscriber['confirmed'],
				'active'	   => $subscriber['active'],
			);

			if ( ! $wpdb->insert( $table, $database_fields, '%s' ) ) {
				continue;
			}

			$id = $wpdb->insert_id;

			$meta = array();
			if( !empty( $subscriber['meta'] ) ) {

				$subscriber['meta'] = maybe_unserialize( $subscriber['meta'] );

				// Arrays
				if( is_array( $subscriber['meta'] ) ) {
					$meta = array( $subscriber['meta'] );
				}

				// Json
				if( is_string( $subscriber['meta'] ) ) {
					$meta = json_decode( $subscriber['meta'], true );
				}

				unset( $subscriber['meta'] );

			}

			if( empty( $meta ) ) {
				$meta = array();
			}

			$extra_meta = array_diff_key( $subscriber, $database_fields );
			foreach ( $extra_meta as $field => $value ) {

				if( is_null( $value ) ) {
					continue;
				}
				
				if( ! isset( $meta[$field] ) ) {
					$meta[$field] = array();
				}

				$meta[$field][] = $value;

			}

			foreach ( $meta as $field => $value ) {

				if( ! is_array( $value ) ) {
					$value = array( $value );
				}

				foreach( $value as $val ) {
					update_noptin_subscriber_meta( $id, $field, $val );
				}
				
			}

			$imported += 1;

		}

		// Did we import any subscribers?
		if ( empty( $imported ) ) {
			wp_send_json_error( __( 'There was no unique subscriber to import' ) );
			exit;
		}

		wp_send_json_success( sprintf(
			__( 'Successfuly imported %s subscribers' ),
			$imported
		)  );
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
			wp_send_json_error( __( 'Please provide a valid email address' ) );
			exit;
		}

		$to = sanitize_text_field( $data['email'] );

		// Subject, body and preview text.
		if ( empty( $data['email_subject'] ) ) {
			wp_send_json_error( __( 'You need to provide a subject for your email.' ) );
			exit;
		}

		$data['email_subject'] = '[TEST] ' . $data['email_subject'];

		if ( empty( $data['email_body'] ) ) {
			wp_send_json_error( __( 'The email body cannot be empty.' ) );
			exit;
		}

		// Is there a subscriber with that email?
		$subscriber = get_noptin_subscriber_by_email( $to );
		$merge_tags = array();

		if ( ! empty( $subscriber ) ) {
			$merge_tags = (array) $subscriber;

			$merge_tags['unsubscribe_url'] = get_noptin_action_url( 'unsubscribe', $subscriber->confirm_key );

			$meta = get_noptin_subscriber_meta( $subscriber->id );
			foreach ( $meta as $key => $values ) {

				if ( isset( $values[0] ) && is_string( $values[0] ) ) {
					$merge_tags[ $key ] = esc_html( $values[0] );
				}
			}
		}

		$data['merge_tags'] = $merge_tags;
		$data['template']   = get_noptin_include_dir( 'admin/templates/email-templates/paste.php' );

		$data = apply_filters( 'noptin_test_email_data', $data );

		// Try sending the email.
		$mailer  = new Noptin_Mailer();
		$email   = $mailer->get_email( $data );
		$subject = $mailer->get_subject( $data );

		if ( $mailer->send( $to, $subject, $email ) ) {
			wp_send_json_success( __( 'Your test email has been sent' ) );
		}

		wp_send_json_error( __( 'Could not send the test email' ) );

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
		if ( ! empty( $_REQUEST['noptin_confirm_submit'] ) ) {
			return;
		}

		// Prepare form fields.
		$form = 0;

		if ( empty( $_REQUEST['noptin_form_id'] ) ) {

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
			$form   = noptin_get_optin_form( $_REQUEST['noptin_form_id'] );
			$fields = $form->fields;
		}

		// Filter and sanitize the fields.
		$filtered = array();

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
			$value       = $_REQUEST[ $name ];

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

				if ( ! $value = sanitize_email( $value ) ) {

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

			// Basic housekeeping.
			update_noptin_subscriber_meta( $inserted, '_subscriber_via', $form->ID );
			$count = (int) get_post_meta( $form->ID, '_noptin_subscribers_count', true );
			update_post_meta( $form->ID, '_noptin_subscribers_count', $count + 1 );

			// msg.
			if ( $form->subscribeAction == 'message' ) {
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
		$table   = get_noptin_subscribers_table_name();
		$results = $wpdb->get_results( "SELECT `id`, `first_name`, `second_name`, `email`, `active`, `confirmed`, `date_created`, `confirm_key`  FROM $table", ARRAY_N );

		header( 'Content-Type:application/csv' );
		header( 'Content-Disposition:attachment;filename=subscribers.csv' );

		// create the csv.
		fputcsv(
			$output,
			array(
				__( 'First Name', 'newsletter-optin-box' ),
				__( 'Second Name', 'newsletter-optin-box' ),
				__( 'Email Address', 'newsletter-optin-box' ),
				__( 'Active', 'newsletter-optin-box' ),
				__( 'Email Confirmed', 'newsletter-optin-box' ),
				__( 'Subscribed On', 'newsletter-optin-box' ),
				__( 'Confirm Key', 'newsletter-optin-box' ),
				__( 'Meta', 'newsletter-optin-box' ),
			)
		);

		foreach ( $results as $result ) {
			$result[] = wp_json_encode( get_noptin_subscriber_meta( $result[0] ) );
			unset( $result[0] );
			fputcsv( $output, $result );
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

}

new Noptin_Ajax();
