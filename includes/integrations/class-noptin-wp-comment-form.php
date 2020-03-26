<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' )  ) {
	die;
}

/**
 * Handles integrations with the WP comment form.
 *
 * @since       1.2.6
 */
class Noptin_WP_Comment_Form extends Noptin_Abstract_Integration {

	/**
	 * Init variables.
	 *
	 * @since       1.2.6
	 */
	public function before_initialize() {
		$this->slug        = 'comment_form';
		$this->name        = __( 'Comment Form', 'newsletter-optin-box' );
		$this->description = __( 'Subscribes people from your WordPress comment form.', 'newsletter-optin-box' );
	}

	/**
	 * Setup hooks in case the integration is enabled.
	 *
	 * @since 1.2.6
	 */
	public function initialize() {
		add_action( 'comment_post', array( $this, 'subscribe_from_comment' ), $this->priority, 2 );
	}

	/**
	 * Displays a checkbox if the integration uses checkbox positions.
	 *
	 * @since 1.2.6
	 */
	public function hook_checkbox_code() {
		add_filter( 'comment_form_submit_field', array( $this, 'append_checkbox' ), $this->priority );
	}

	/**
	 * Prints the checkbox wrapper.
	 *
	 */
	function before_checkbox_wrapper() {
		echo "<p class='noptin_comment_form_optin_checkbox_wrapper'>";
	}

	/**
	 * Prints the checkbox closing wrapper.
	 *
	 */
	function after_checkbox_wrapper() {
		echo '</p>';
	}

	/**
	 * Returns the checkbox message option name.
	 *
	 * @since 1.2.6
	 * @return string
	 */
	public function get_checkbox_message_integration_option_name() {
		return 'comment_form_msg';
	}

	/**
	 * Returns the enable option name.
	 *
	 * @since 1.2.6
	 * @return string
	 */
	public function get_enable_integration_option_name() {
		return 'comment_form';
	}

	/**
	 * Subscribes from WP Registration Form
	 *
	 * @param int $comment_id
	 *
	 * @return int|null
	 */
	public function subscribe_from_comment( $comment_id, $comment_approved = '' ) {

		// is this a spam comment?
		if ( $comment_approved === 'spam' ) {
			return false;
		}

		// Check if the user exists.
		$author = get_comment_author( $comment_id );

		if ( 'Anonymous' === $author ) {
			$author = '';
		}


		// Prepare subscriber fields.
		$noptin_fields = array(
			'_subscriber_via' => 'comment',
			'comment_id'      => $comment_id,
			'email'           => get_comment_author_email( $comment_id ),
			'name'            => $author,
			'website'         => get_comment_author_url( $comment_id ),
			'ip_address'      => get_comment_author_IP( $comment_id ),
		);

		$noptin_fields = array_filter( $noptin_fields );
		$subscriber_id = get_noptin_subscriber_id_by_email( $noptin_fields['email'] );

		// If the subscriber does not exist, create a new one.
		if ( empty( $subscriber_id ) ) {

			// Ensure the subscription checkbox was triggered.
			if( $this->triggered() ) {
				return $this->add_subscriber( $noptin_fields, $comment_id );
			}
			return null;

		}

		// Else, update the existing subscriber.
		unset( $noptin_fields['_subscriber_via'] );
		return $this->update_subscriber( $subscriber_id, $noptin_fields, $comment_id );

	}

}
