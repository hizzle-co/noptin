<?php

/**
 * Handles integrations with the WP comment form.
 *
 * @since 1.0.0
 */

namespace Hizzle\Noptin\Integrations\WordPress_Comment_Form;

defined( 'ABSPATH' ) || exit;

/**
 * Handles integrations with the WP comment form.
 *
 * @since 1.2.6
 */
class Main extends \Hizzle\Noptin\Integrations\Checkbox_Integration {

	/**
	 * Init variables.
	 *
	 * @since 1.2.6
	 */
	public function __construct() {
		$this->slug   = 'comment_form';
		$this->source = 'comment';
		$this->name   = __( 'Comment Form', 'newsletter-optin-box' );
		$this->url    = 'getting-email-subscribers/wordpress-comment-forms/';

		parent::__construct();
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
		add_filter( 'comment_form_submit_field', array( $this, 'prepend_checkbox' ), $this->priority );
	}

	/**
	 * Prints the checkbox wrapper.
	 *
	 */
	public function before_checkbox_wrapper() {
		echo "<p class='noptin_comment_form_optin_checkbox_wrapper'>";
	}

	/**
	 * Prints the checkbox closing wrapper.
	 *
	 */
	public function after_checkbox_wrapper() {
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

		// Abort if checkbox was not checked.
		if ( ! $this->triggered() ) {
			return;
		}

		// is this a spam comment?
		if ( 'spam' === $comment_approved ) {
			return false;
		}

		// Commentor.
		$author = get_comment_author( $comment_id );

		if ( 'Anonymous' === $author ) {
			$author = '';
		}

		// Process the submission.
		$this->process_submission(
			array(
				'source'     => $this->source,
				'comment_id' => $comment_id,
				'email'      => get_comment_author_email( $comment_id ),
				'name'       => $author,
				'website'    => get_comment_author_url( $comment_id ),
				'ip_address' => get_comment_author_IP( $comment_id ),
			)
		);
	}

	/**
	 * @inheritdoc
	 */
	public function custom_fields() {
		return array(
			'comment_id' => __( 'Comment ID', 'newsletter-optin-box' ),
			'name'       => __( 'Name', 'newsletter-optin-box' ),
			'website'    => __( 'Website', 'newsletter-optin-box' ),
		);
	}
}
