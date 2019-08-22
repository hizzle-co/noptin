<?php

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) {
    die;
}

    /**
     * Handles integrations with other products and services
     *
     * @since       1.0.8
     */

    class Noptin_Integrations{

    /**
	 * Class Constructor.
	 */
	public function __construct() {

      	//Maybe ask users to subscribe to the newsletter after commenting...
		add_action( 'comment_form', array( $this, 'comment_form') );
		add_action( 'comment_post', array( $this, 'subscribe_commentor') );

		//... or when registering
		add_action( 'register_form', array( $this, 'register_form') );
		add_action( 'user_register', array( $this, 'subscribe_registered_user') );


    }

    /**
     * Maybe ask users to subscribe to the newsletter after commenting
     *
     * @access      public
     * @since       1.0.8
     * @return      void
     */
    public function comment_form( $post_id ) {

		if(! get_noptin_option( 'comment_form' ) ) {
			return;
		}

		echo '<label class="comment-form-noptin"><input name="noptin-subscribe" type="checkbox" />Subscribe to our newsletter</label>';

	}

	/**
     * Maybe subscribe a commentor
     *
     * @access      public
     * @since       1.0.8
     * @return      void
     */
    public function subscribe_commentor( $comment_id ) {

		if(! get_noptin_option( 'comment_form' ) ) {
			return;
		}

		if( isset( $_POST['noptin-subscribe'] ) ) {

		}

	}

	/**
     * Maybe ask users to users to register on the registration form
     *
     * @access      public
     * @since       1.0.8
     * @return      void
     */
    public function register_form() {

		if(! get_noptin_option( 'register_form' ) ) {
			return;
		}

		echo '<label class="register-form-noptin"><input name="noptin-subscribe" type="checkbox" />Subscribe to our newsletter</label>';

	}

	/**
     * Maybe subscribe a registered user
     *
     * @access      public
     * @since       1.0.8
     * @return      void
     */
    public function subscribe_registered_user( $user_id ) {

		if(! get_noptin_option( 'register_form' ) ) {
			return;
		}

		if( isset( $_POST['noptin-subscribe'] ) ) {

		}

	}


}

new Noptin_Integrations();
