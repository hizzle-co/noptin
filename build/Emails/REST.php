<?php

/**
 * Main rest class.
 *
 * @since   2.2.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Emails;

defined( 'ABSPATH' ) || exit;

/**
 * Main rest class.
 */
class REST extends \WP_REST_Posts_Controller {

    /**
	 * Registers the routes for posts.
	 *
	 * @since 4.7.0
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {

		// Send a test email.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/send-test',
			array(
				'args'        => array(
					'id' => array(
						'description' => __( 'Unique identifier for the post.', 'newsletter-optin-box' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'send_test_email' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( \WP_REST_Server::EDITABLE ),
				),
				'allow_batch' => $this->allow_batch,
				'schema'      => array( $this, 'get_public_item_schema' ),
			)
		);

		// Reorder emails.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/reorder',
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'reorder_emails' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'                => array(
					'ids' => array(
						'description' => __( 'The IDs of the emails to reorder.', 'newsletter-optin-box' ),
						'type'        => 'array',
						'items'       => array(
							'type' => 'integer',
						),
					),
				),
				'schema'              => array( $this, 'get_public_item_schema' ),
			)
		);

		parent::register_routes();
	}

	/**
	 * Sends a test email.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request Full details about the request.
	 */
	public function send_test_email( $request ) {

		add_filter( 'noptin_log_email_send', '__return_false', 10000 );

		$GLOBALS['current_noptin_email'] = $request->get_param( 'email' );

		// Check if we have a recipient for the test email.
		if ( empty( $GLOBALS['current_noptin_email'] ) || ! is_email( $GLOBALS['current_noptin_email'] ) ) {
			return new \WP_Error( 'noptin_rest_email_invalid', __( 'Please provide a valid email address', 'newsletter-optin-box' ), array( 'status' => 400 ) );
		}

		$email = new Email( $request->get_param( 'id' ) );

		// Abort if the email is not found.
		if ( ! $email->exists() ) {
			return new \WP_Error( 'noptin_rest_email_invalid', __( 'Invalid email', 'newsletter-optin-box' ), array( 'status' => 404 ) );
		}

		// Ensure we have a subject.
		$subject = $email->get_subject();
		if ( empty( $subject ) ) {
			return new \WP_Error( 'noptin_rest_email_invalid', __( 'You need to provide a subject for your email.', 'newsletter-optin-box' ), array( 'status' => 404 ) );
		}

		$user = array(
			'sid'   => get_current_noptin_subscriber_id(),
			'uid'   => get_current_user_id(),
			'email' => $GLOBALS['current_noptin_email'],
		);

		// Prepare test content if needed.
		$prepare_preview = $email->prepare_preview( 'preview', $user );

		if ( is_wp_error( $prepare_preview ) ) {
			return $prepare_preview;
		}

		// Generate the preview.
		$preview = noptin_generate_email_content( $email, $user, false );

		if ( is_wp_error( $preview ) ) {
			return $preview;
		}

		// Send the email.
		$result = noptin_send_email(
			array(
				'recipients'               => $request->get_param( 'email' ),
				'subject'                  => noptin_parse_email_subject_tags( $subject ),
				'message'                  => $preview,
				'campaign_id'              => $email->id,
				'campaign'                 => $email,
				'headers'                  => array(),
				'attachments'              => array(),
				'reply_to'                 => '',
				'from_email'               => '',
				'from_name'                => '',
				'content_type'             => $email->get_email_type() === 'plain_text' ? 'text' : 'html',
				'unsubscribe_url'          => get_noptin_action_url( 'unsubscribe', noptin_encrypt( wp_json_encode( $user ) ) ),
				'disable_template_plugins' => ! ( $email->get_email_type() === 'normal' && $email->get_template() === 'default' ),
			)
		);

		if ( empty( $result ) ) {
			$error = \Noptin_Email_Sender::get_phpmailer_last_error();
			return new \WP_Error(
				'noptin_rest_email_invalid',
				sprintf(
					// Translators: %s The error message.
					__( 'Unable to send test email. Error: %s', 'newsletter-optin-box' ),
					esc_html( $error )
				),
				array( 'status' => 404 )
			);
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Your test email has been sent', 'newsletter-optin-box' ),
			)
		);
	}

	/**
	 * Reorders emails.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request Full details about the request.
	 */
	public function reorder_emails( $request ) {

		$ids = $request->get_param( 'ids' );

		if ( empty( $ids ) || ! is_array( $ids ) ) {
			return new \WP_Error( 'noptin_rest_email_invalid', __( 'Invalid email IDs', 'newsletter-optin-box' ), array( 'status' => 400 ) );
		}

		$ids = array_map( 'intval', $ids );

		// Update the menu order.
		foreach ( $ids as $index => $id ) {

			// Check if user can edit the email.
			if ( current_user_can( 'edit_post', $id ) ) {
				wp_update_post(
					array(
						'ID'         => $id,
						'menu_order' => $index + 1,
					)
				);
			}
		}

		return rest_ensure_response( true );
	}
}
