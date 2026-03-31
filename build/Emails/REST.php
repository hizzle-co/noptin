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

		// Get template content.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/get-template',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_template_content' ),
				'permission_callback' => function () {
					return current_user_can( get_noptin_capability() );
				},
				'args'                => array(
					'noptin_template' => array(
						'description' => 'The template ID.',
						'type'        => 'string',
						'required'    => true,
					),
				),
			)
		);

		// Send a test email.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/send-test',
			array(
				'args'        => array(
					'id' => array(
						'description' => 'Unique identifier for the post.',
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
						'description' => 'The IDs of the emails to reorder.',
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
	 * Checks if a given request has access to update a post.
	 *
	 * @since 4.7.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has access to update the item, WP_Error object otherwise.
	 */
	public function update_item_permissions_check( $request ) {
		if ( current_user_can( get_noptin_capability() ) ) {
			add_filter( 'is_protected_meta', '__return_false', PHP_INT_MAX );
		}

		return parent::update_item_permissions_check( $request );
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
			return new \WP_Error( 'noptin_rest_email_invalid', 'Invalid email', array( 'status' => 404 ) );
		}

		// Ensure we have a subject.
		$subject = $email->get_subject();
		if ( empty( $subject ) ) {
			return new \WP_Error( 'noptin_rest_email_invalid', 'You need to provide a subject for your email.', array( 'status' => 404 ) );
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
				'reply_to'                 => noptin_parse_email_subject_tags( $email->get( 'reply_to' ) ),
				'from_email'               => noptin_parse_email_subject_tags( $email->get( 'from_email' ) ),
				'from_name'                => noptin_parse_email_subject_tags( $email->get( 'from_name' ) ),
				'content_type'             => $email->get_email_type() === 'plain_text' ? 'text' : 'html',
				'unsubscribe_url'          => get_noptin_action_url( 'unsubscribe', noptin_encrypt( wp_json_encode( $user ) ) ),
				'disable_template_plugins' => ! ( $email->get_email_type() === 'normal' && $email->get_template() === 'default' ),
			)
		);

		if ( empty( $result ) ) {
			$error = Main::get_phpmailer_last_error();
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
			return new \WP_Error( 'noptin_rest_email_invalid', 'Invalid email IDs', array( 'status' => 400 ) );
		}

		$ids = array_map( 'intval', $ids );

		do_action( 'noptin_before_reorder_emails', $ids );

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

		do_action( 'noptin_after_reorder_emails', $ids );

		return rest_ensure_response( true );
	}

	/**
	 * Retrieves the content of an email template.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_template_content( $request ) {
		$template = sanitize_text_field( $request->get_param( 'noptin_template' ) );

		if ( empty( $template ) || 'blank' === $template ) {
			$defaults = get_noptin_email_template_defaults();
			return rest_ensure_response(
				array(
					'content' => '',
					'options' => $defaults['noptin-visual']
				)
			);
		}

		// Check if the template is a local campaign template.
		if ( false !== strpos( $template, 'noptin_campaign_' ) ) {
			$campaign_id = (int) str_replace( 'noptin_campaign_', '', $template );
			$email       = noptin_get_email_campaign_object( $campaign_id );

			if ( empty( $email->id ) ) {
				return new \WP_Error(
					'noptin_template_not_found',
					'Template not found.',
					array( 'status' => 404 )
				);
			}

			$options = array_filter(
				array(
					'color'             => $email->get( 'color' ),
					'button_background' => $email->get( 'button_background' ),
					'button_color'      => $email->get( 'button_color' ),
					'background_color'  => $email->get( 'background_color' ),
					'custom_css'        => $email->get( 'custom_css' ),
					'font_family'       => $email->get( 'font_family' ),
					'font_size'         => $email->get( 'font_size' ),
					'font_style'        => $email->get( 'font_style' ),
					'font_weight'       => $email->get( 'font_weight' ),
					'line_height'       => $email->get( 'line_height' ),
					'link_color'        => $email->get( 'link_color' ),
					'block_css'         => $email->get( 'block_css' ),
					'background_image'  => $email->get( 'background_image' ),
				)
			);

			return rest_ensure_response(
				array(
					'content' => $email->content,
					'options' => $options,
				)
			);
		}

		// Fetch a remote template from noptin.com.
		$response = \Noptin_COM::process_api_response( wp_remote_get( "https://noptin.com/email-templates/{$template}.json" ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$template_data = json_decode( wp_json_encode( $response ), true );

		if ( ! is_array( $template_data ) || empty( $template_data['content'] ) ) {
			return new \WP_Error(
				'noptin_template_invalid',
				'Invalid template data.',
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response(
			array(
				'content' => str_ireplace(
					array( '{{DEFAULT_FOOTER_TEXT}}', '{{FOOTER_TEXT}}' ),
					get_noptin_footer_text(),
					$template_data['content']
				),
				'options' => isset( $template_data['options'] ) ? $template_data['options'] : array(),
			)
		);
	}

	protected function prepare_item_for_database( $request ) {
		$data = parent::prepare_item_for_database( $request );

		if ( ! is_wp_error( $data ) && isset( $data->post_content ) && false !== strpos( $data->post_content, '{{DEFAULT_FOOTER_TEXT}}' ) ) {
			$data->post_content = str_replace( '{{DEFAULT_FOOTER_TEXT}}', get_default_noptin_footer_text(), $data->post_content );
		}

		return $data;
	}
}
