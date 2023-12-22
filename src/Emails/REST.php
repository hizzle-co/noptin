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
class REST extends \Hizzle\Noptin\REST\Controller {

    /**
	 * Registers REST routes.
	 *
	 * @since 1.0.0
	 */
	public function register_routes() {

		// Create a new email campaign.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'can_manage_noptin' ),
					'args'                => array(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

        // Update an email campaign.
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<id>[\d]+)',
            array(
                array(
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => array( $this, 'update_item' ),
                    'permission_callback' => array( $this, 'can_manage_noptin' ),
                    'args'                => array(),
                ),
                'schema' => array( $this, 'get_public_item_schema' ),
            )
        );

		// Send a test email.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/send-test',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'send_test_email' ),
					'permission_callback' => array( $this, 'can_manage_noptin' ),
					'args'                => array(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

    /**
	 * Retrieves the item's schema, conforming to JSON Schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'Email campaign',
			'type'       => 'object',
			'properties' => array(
				'id'         => array(
					'description' => 'Unique identifier for the email',
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'parent_id'  => array(
					'description' => 'Unique identifier for the parent email',
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'author'     => array(
					'description' => 'The ID for the author of the email',
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'created'    => array(
					'description' => 'Schedule date for the email',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'status'     => array(
					'description' => 'Email status',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'enum'        => array( 'draft', 'publish', 'future' ),
				),
				'subject'    => array(
					'description' => 'Email subject',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'required'    => true,
				),
				'name'       => array(
					'description' => 'Campaign name',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'type'       => array(
					'description' => 'Campaign type',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'default'     => 'newsletter',
				),
				'options'    => array(
					'description' => 'Extra campaign options',
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
				),
				'test_email' => array(
					'description' => 'The email address to send a test email to',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Creates a single item.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request Full details about the request.
	 */
	public function create_item( $request ) {

		// Prepare the email.
		$email         = new Email( $request->get_params() );
		$email->author = get_current_user_id();

		// Create the email.
		$email->save();

		// Return the email.
		if ( $email->exists() ) {
			return rest_ensure_response( $email->to_array() );
		}

		return new \WP_Error( 'noptin_rest_cannot_create', __( 'Cannot create email', 'newsletter-optin-box' ), array( 'status' => 500 ) );
	}

	/**
	 * Updates a single item.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request Full details about the request.
	 */
	public function update_item( $request ) {
		// Init the email.
		$email = new Email( $request->get_params() );

		// Abort if the email does not exist.
		if ( ! $email->exists() ) {
			return new \WP_Error( 'noptin_rest_email_invalid', __( 'Invalid email', 'newsletter-optin-box' ), array( 'status' => 404 ) );
		}

		// Update the email.
		$email->save();

		// Return the email.
		return rest_ensure_response( $email->to_array() );
	}

	/**
	 * Sends a test email.
	 *
	 * @since 1.0.0
	 * @param \WP_REST_Request $request Full details about the request.
	 */
	public function send_test_email( $request ) {

		// Prepare the email.
		$email = new Email( $request->get_params() );

		// Prepare the test email.
		$test_email = noptin_parse_list( $request->get_param( 'test_email' ), true );
		$test_email = ( ! empty( $test_email ) && is_email( $test_email[0] ) ) ? $test_email : array( get_option( 'admin_email' ) );

		// Abort if no subject.
		if ( empty( $email->subject ) ) {
			return new \WP_Error( 'noptin_rest_email_invalid', __( 'You need to provide a subject for your email.', 'newsletter-optin-box' ), array( 'status' => 404 ) );
		}

		foreach ( $test_email as $email_address ) {

			// Abort if the email address is invalid.
			if ( ! is_email( $email_address ) ) {
				return new \WP_Error( 'noptin_rest_email_invalid', __( 'Invalid email address', 'newsletter-optin-box' ), array( 'status' => 400 ) );
			}

			// Prepare the preview.
			$preview = $email->get_browser_preview_content( $email_address );

			// Abort if the preview is empty.
			if ( empty( $preview ) ) {
				return new \WP_Error( 'noptin_rest_email_invalid', __( 'The email body cannot be empty.', 'newsletter-optin-box' ), array( 'status' => 404 ) );
			}

			// Send the email.
			$result = $email->send( $email_address );

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => __( 'Your test email has been sent', 'newsletter-optin-box' ),
			)
		);
	}
}
