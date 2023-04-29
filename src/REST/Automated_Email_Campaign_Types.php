<?php

namespace Hizzle\Noptin\REST;

/**
 * Controller for automated email campaign types.
 *
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Controller for automated email campaign types.
 */
class Automated_Email_Campaign_Types extends Controller {

	/**
	 * Registers REST routes.
	 *
	 * @since 1.0.0
	 */
	public function register_routes() {

		// Read available email campaign types.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'can_manage_noptin' ),
					'args'                => array(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Retrieves a collection of items.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {

		$prepared = array();

		if ( ! defined( 'NOPTIN_WELCOME_EMAILS_FILE' ) ) {
			$prepared[] = array(
				'name'         => 'welcome_users_email',
				'title'        => __( 'Welcome New Users', 'newsletter-optin-box' ),
				'category'     => 'WordPress',
				'description'  => __( 'Welcome new users to your website, introduce yourself, etc.', 'newsletter-optin-box' ),
				'image'        => 'wordpress',
				'create_url'   => '',
				'upgrade_url'  => 'https://noptin.com/ultimate-addons-pack/?utm_medium=plugin-dashboard&utm_campaign=automated-emails&utm_source=welcome_users_email',
				'is_available' => false,
			);

			$prepared[] = array(
				'name'         => 'periodic',
				'title'        => __( 'Periodic', 'newsletter-optin-box' ),
				'category'     => 'General',
				'description'  => __( 'Automatically send your subscribers, users, or customers an email every X days.', 'newsletter-optin-box' ),
				'image'        => array(
					'fill'    => '#3f9ef4',
					'path'    => 'M61.44,0A61.46,61.46,0,1,1,18,18,61.21,61.21,0,0,1,61.44,0ZM32.22,79.39,52.1,59.46,32.22,43.25V79.39ZM54.29,61.24,33.79,81.79H88.91L69.33,61.24l-6.46,5.51h0a1.42,1.42,0,0,1-1.8,0l-6.78-5.53Zm17.18-1.82L90.66,79.55V43.07L71.47,59.42ZM34,41.09l27.9,22.76L88.65,41.09Zm65.4-17.64a53.72,53.72,0,1,0,15.74,38,53.56,53.56,0,0,0-15.74-38Z',
					'viewBox' => '0 0 122.88 122.88',
				),
				'create_url'   => '',
				'upgrade_url'  => 'https://noptin.com/ultimate-addons-pack/?utm_medium=plugin-dashboard&utm_campaign=automated-emails&utm_source=periodic',
				'is_available' => false,
			);
		}

		foreach ( noptin()->emails->automated_email_types->types as $automated_email_type ) {

			if ( empty( $automated_email_type->category ) ) {
				continue;
			}

			$item = array(
				'name'         => $automated_email_type->type,
				'title'        => $automated_email_type->get_name(),
				'category'     => $automated_email_type->category,
				'description'  => $automated_email_type->get_description(),
				'image'        => $automated_email_type->get_image(),
				'create_url'   => $automated_email_type->new_campaign_url(),
				'upgrade_url'  => '',
				'is_available' => true,
			);

			if ( 0 === strpos( $automated_email_type->type, 'automation_rule_' ) ) {
				/** @var \Noptin_Automation_Rule_Email  $automated_email_type */
				$trigger = $automated_email_type->get_trigger();

				if ( empty( $trigger ) || $trigger->depricated || empty( $trigger->category ) ) {
					continue;
				}

				$item['image'] = $trigger->get_image();
			}

			$item       = $this->prepare_item_for_response( $item, $request );
			$prepared[] = $this->prepare_response_for_collection( $item );
		}

		$response = rest_ensure_response( $prepared );

		$response->header( 'X-WP-Total', count( $prepared ) );

		return $response;
	}

	/**
	 * Prepares the item for the REST response.
	 *
	 * @since 1.0.0
	 *
	 * @param array            $item    WordPress representation of the item.
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function prepare_item_for_response( $item, $request ) {
		$fields = $this->get_fields_for_response( $request );
		$data   = array();

		foreach ( $item as $key => $value ) {
			if ( rest_is_field_included( $key, $fields ) ) {
				$data[ $key ] = $value;
			}
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		return apply_filters( 'noptin_prepare_rest_' . $this->get_normalized_rest_base() . '_object', $response, $item, $request );
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
			'title'      => 'Automated email campaign type',
			'type'       => 'object',
			'properties' => array(
				'name'         => array(
					'description' => 'Machine readable campaign type name',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'title'        => array(
					'description' => 'Human readable campaign type description',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'category'     => array(
					'description' => 'Campaign type description category',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'description'  => array(
					'description' => 'Campaign type description',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'image'        => array(
					'description' => 'Campaign type image',
					'type'        => array( 'string', 'object' ),
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'create_url'   => array(
					'description' => 'URL to create a new campaign of this type',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'upgrade_url'  => array(
					'description' => 'URL to upgrade to this campaign type',
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'is_available' => array(
					'description' => 'Whether this campaign type is available for use',
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

}
