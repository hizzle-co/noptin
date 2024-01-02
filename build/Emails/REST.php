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

		parent::register_routes();

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
	}

	/**
	 * Sends a test email.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request Full details about the request.
	 */
	public function send_test_email( $request ) {

		$GLOBALS['current_noptin_email'] = $request->get_param( 'email' );

		// Check if we have a recipient for the test email.
		if ( empty( $GLOBALS['current_noptin_email'] ) || ! is_email( $GLOBALS['current_noptin_email'] ) ) {
			return new \WP_Error( 'noptin_rest_email_invalid', __( 'Please provide a valid email address', 'newsletter-optin-box' ), array( 'status' => 400 ) );
		}

		$original = get_post( $request->get_param( 'id' ) );
		$autosave = wp_get_post_autosave( $request->get_param( 'id' ) );
		$email    = new Email( $autosave ? $autosave : $original );

		// Abort if the email is not found.
		if ( ! $email->exists() ) {
			return new \WP_Error( 'noptin_rest_email_invalid', __( 'Invalid email', 'newsletter-optin-box' ), array( 'status' => 404 ) );
		}

		// If we have an autosave, use the parent and ID of the original.
		if ( $autosave ) {
			$email->id        = $original->ID;
			$email->parent_id = $original->post_parent;
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
			wp_die( esc_html( $preview->get_error_message() ) );
		}

		// Send the email.

		noptin_error_log( $request->get_params() ); return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Your test email has been sent', 'newsletter-optin-box' ),
			)
		);

	}
}
