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
						'label' => 'Email Address',
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

			$field_type  = $field['type']['type'];
			$field_name  = $field['type']['name'];
			$field_label = $field['type']['label'];
			$value 		 = $_REQUEST[$field_name];

			//required fields
			if( 'true' == $field['require'] && empty( $value ) ) {
				die( sprintf(
					'%s is required',
					esc_html( $field_label ))
				);
			}



			//Sanitize email fields
			if( 'email' == $field_type && !empty( $value ) ) {

				if(! $value = sanitize_email( $value ) ) {

					die( sprintf(
						'%s is not valid',
						esc_html( $field_label ))
					);

				}
			}

			//Sanitize text fields
			if( 'textarea' != $field_type && !is_array( $value ) ) {
				$value = sanitize_text_field( $value );
			} else {
				if( !is_array( $value ) ) {
					$value = esc_html( $value );
				}
			}

			$filtered[$field_name] = $value;

		}

		$inserted = add_noptin_subscriber( $filtered );

		if ( is_string( $inserted ) ) {
			die( $inserted );
		}

		do_action('noptin_after_after_ajax_subscriber');

		$result = array(
			'action' => 'msg',
			'msg'    => esc_html__('Success!', 'noptin'),
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

}

new Noptin_Ajax();
