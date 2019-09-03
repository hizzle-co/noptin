<?php

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) {
    die;
}

    /**
     * Handles Noptin Ajax Requests
     *
     * @since       1.0.5
     */

    class Noptin_Ajax{

    /**
	 * Class Constructor.
	 */
	public function __construct() {

      	//Register new subscriber
		add_action( 'wp_ajax_noptin_new_subscriber', array( $this, 'add_subscriber' ) );
		add_action( 'wp_ajax_nopriv_noptin_new_subscriber', array( $this, 'add_subscriber' ) );

		//Download subscribers
		add_action('wp_ajax_noptin_download_subscribers', array($this, 'download_subscribers'));

		//Save settings
		add_action('wp_ajax_noptin_save_options', array($this, 'save_options'));


    }

    /**
     * Adds a new subscriber via ajax
     *
     * @access      public
     * @since       1.0.5
     * @return      void
     */
    public function add_subscriber() {

		//Verify nonce
		check_ajax_referer( 'noptin' );

		//Prepare form fields
		$form = 0;

		if( empty( $_REQUEST['noptin_form_id'] ) ) {

			$fields = array(
				array(
					'type'   => array(
						'label' => __( 'Email Address', 'noptin' ),
						'name' => 'email',
						'type' => 'email',
					),
					'require'=> 'true',
					'key'	 => 'noptin_email_key',
				)
			);

		} else {

			//Get the form
			$form   = noptin_get_optin_form( $_REQUEST['noptin_form_id'] );
			$fields = $form->fields;
		}

		//Filter and sanitize the fields
		$filtered = array();

		foreach( $fields as $field ) {

			$type = $field['type']['type'];

			//Prepare the field name
			$name = '';

			if( 'email' == $type ) {
				$name = 'email';
			}

			if( 'first_name' == $type ) {
				$name = 'first_name';
			}

			if( 'last_name' == $type ) {
				$name = 'last_name';
			}

			if( 'name' == $type ) {
				$name = 'name';
			}

			if( empty( $name ) ) {
				$name = $field['type']['name'];
			}

			$field_label = $field['type']['label'];
			$value 		 = $_REQUEST[$name];

			//required fields
			if( 'true' == $field['require'] && empty( $value ) ) {
				die( sprintf(
					'%s is required',
					esc_html( $field_label ))
				);
			}

			//Sanitize email fields
			if( 'email' == $type && !empty( $value ) ) {

				if(! $value = sanitize_email( $value ) ) {

					die( sprintf(
						'%s is not valid',
						esc_html( $field_label ))
					);

				}
			}

			//Sanitize text fields
			if( 'textarea' != $type && !is_array( $value ) ) {
				$value = sanitize_text_field( $value );
			} else {
				if( !is_array( $value ) ) {
					$value = esc_html( $value );
				}
			}

			$filtered[$name] = $value;

		}

		$inserted = add_noptin_subscriber( $filtered );

		if ( is_string( $inserted ) ) {
			die( $inserted );
		}

		do_action('noptin_after_after_ajax_subscriber');

		$result = array(
			'action' => 'msg',
			'msg'    => esc_html__('Thanks for subscribing to the newsletter', 'noptin'),
		);

		if( is_object( $form ) ) {

			//Basic housekeeping
			update_noptin_subscriber_meta( $inserted, '_subscriber_via', $form->ID );
			$count = (int) get_post_meta( $form->ID, '_noptin_subscribers_count', true );
			update_post_meta( $form->ID, '_noptin_subscribers_count', $count + 1);

			//msg
			if( $form->subscribeAction == 'message' ) {
				$result['msg'] = $form->successMessage;
			} else {
				//redirects
				$result['action']   = $form->redirect;
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
	public function save_options(){

		if (!current_user_can('manage_options')) {
			wp_die( -1, 403 );
		}

		//Check nonce
		check_ajax_referer( 'noptin_admin_nonce' );

		/**
         * Runs before saving a settings
         *
         */
		do_action('noptin_before_save_options');

		//Prepare settings
		$_settings =  $_POST['state'];
		unset( $_settings['noptin_admin_nonce'] );
		unset( $_settings['saved'] );
		unset( $_settings['error'] );
		unset( $_settings['currentTab'] );

		$settings = array();
		foreach( $_settings as $key => $val ) {

			if( 'false' === $val ) {
				$val = false;
			}

			if( 'true' === $val ) {
				$val = true;
			}

			$settings[$key] = $val;
		}
		$settings = apply_filters( 'noptin_sanitize_settings', $settings );

		//Save them
		update_noptin_options( $settings );

		wp_send_json_success(1);

	}

	/**
	 * Downloads subscribers
	 *
	 * @access      public
	 * @since       1.0.5
	 */
	public function download_subscribers() {
		global $wpdb;

		if (!current_user_can('manage_options')) {
			return;
		}

		//Check nonce
		$nonce = $_GET['admin_nonce'];
		if (!wp_verify_nonce($nonce, 'noptin_admin_nonce')) {
			echo __( 'Reload the page and try again.', 'noptin' );
			exit;
		}

		/**
		 * Runs before downloading subscribers.
		 *
		 * @param array $this The admin instance
		 */
		do_action('noptin_before_download_subscribers', $this);

		$output  = fopen("php://output", 'w') or die("Unsupported server");
		$table   = $wpdb->prefix . 'noptin_subscribers';
		$results = $wpdb->get_results("SELECT `first_name`, `second_name`, `email`, `active`, `confirmed`, `date_created`  FROM $table", ARRAY_N );

		header("Content-Type:application/csv");
		header("Content-Disposition:attachment;filename=subscribers.csv");

	//create the csv
	fputcsv($output, array(
		__( 'First Name', 'noptin' ),
		__( 'Second Name', 'noptin' ),
		__( 'Email Address', 'noptin' ),
		__( 'Active', 'noptin' ),
		__( 'Email Confirmed', 'noptin' ),
		__( 'Subscribed On', 'noptin' )
	));
	foreach ($results as $result) {
		fputcsv($output, $result);
	}
	fclose($output);

	/**
	 * Runs after after downloading.
	 *
	 * @param array $this The admin instance
	 */
	do_action('noptin_after_download_subscribers', $this);

	exit; //This is important
}

}

new Noptin_Ajax();
