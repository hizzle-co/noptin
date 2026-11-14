<?php

namespace Hizzle\Noptin\Abilities;

defined( 'ABSPATH' ) || exit;

/** Exposes subscriber management through the WordPress Abilities API and MCP. */
class Subscribers {
	/** Register the subscriber tools with the fields configured on this site. */
	public static function register() {
		$collection = noptin()->db()->store->get( 'subscribers' );
		if ( ! $collection ) {
			return;
		}

		$schema = $collection->get_rest_schema();
		$fields = array();
		$names  = array_merge( array( 'email', 'status', 'tags' ), wp_list_pluck( get_noptin_custom_fields(), 'merge_tag' ) );
		foreach ( $names as $name ) {
			$property = $schema['properties'][ $name ] ?? array();
			if ( empty( $property['type'] ) || ! empty( $property['readonly'] ) ) {
				continue;
			}
			$fields[ $name ] = array_intersect_key( $property, array_flip( array( 'type', 'description', 'format', 'items', 'enum', 'maxLength' ) ) );
			if ( in_array( 'array', (array) $property['type'], true ) && empty( $fields[ $name ]['items'] ) ) {
				$fields[ $name ]['items'] = array( 'type' => 'string' );
			}
		}
		$fields['email']['format'] = 'email';
		$fields['status']['enum']  = array_keys( noptin_get_subscriber_statuses() );

		$id              = array(
			'id' => array(
				'type'    => 'integer',
				'minimum' => 1,
			),
		);
		$response_fields = array_keys( $id + $fields );
		$list            = $collection->get_query_schema();
		foreach ( $list as $key => $argument ) {
			unset( $list[ $key ]['sanitize_callback'], $list[ $key ]['validate_callback'] );
			if ( isset( $argument['type'] ) && is_array( $argument['type'] ) ) {
				$list[ $key ]['type'] = array_values( $argument['type'] );
			}
		}

		// The REST endpoint also accepts WordPress's standard page parameter.
		$list += array(
			'page' => array(
				'type'    => 'integer',
				'minimum' => 1,
				'default' => 1,
			),
		);

		self::register_tool(
			'list-subscribers',
			'List newsletter subscribers',
			'List Noptin email newsletter subscribers with filtering, sorting, and pagination.',
			'GET',
			$list,
			array(),
			$response_fields
		);

		self::register_tool(
			'get-subscriber',
			'Get newsletter subscriber',
			'Retrieve one Noptin email newsletter subscriber by subscriber ID or email address. Supply exactly one.',
			'GET',
			$id + array( 'email' => $fields['email'] ),
			array(),
			$response_fields
		);

		self::register_tool(
			'create-subscriber',
			__( 'Create newsletter subscriber', 'newsletter-optin-box' ),
			__( 'Create a Noptin email newsletter subscriber with an email address and optional subscriber fields.', 'newsletter-optin-box' ),
			'POST',
			$fields,
			array( 'email' ),
			$response_fields
		);
		self::register_tool(
			'update-subscriber',
			__( 'Update newsletter subscriber', 'newsletter-optin-box' ),
			__( 'Update the supplied fields on a Noptin email newsletter subscriber by subscriber ID.', 'newsletter-optin-box' ),
			'PATCH',
			$id + $fields,
			array( 'id' ),
			$response_fields
		);
		self::register_tool(
			'delete-subscriber',
			__( 'Delete newsletter subscriber', 'newsletter-optin-box' ),
			__( 'Permanently delete one Noptin email newsletter subscriber and their associated email logs and tasks by subscriber ID.', 'newsletter-optin-box' ),
			'DELETE',
			$id,
			array( 'id' ),
			$response_fields
		);
	}

	/**
	 * Register an ability backed by the corresponding subscriber REST endpoint.
	 *
	 * @param string $name            Ability slug.
	 * @param string $label           Display label.
	 * @param string $description     Tool description.
	 * @param string $method          REST method.
	 * @param array  $properties      Accepted input fields.
	 * @param array  $required        Required field names.
	 * @param array  $response_fields Subscriber fields exposed to callers.
	 */
	private static function register_tool( $name, $label, $description, $method, $properties, $required, $response_fields ) {
		$single = 'get-subscriber' === $name || in_array( 'id', $required, true );
		$input  = array(
			'type'                 => 'object',
			'properties'           => $properties,
			'additionalProperties' => false,
		);
		if ( 'get-subscriber' === $name ) {
			$input['minProperties'] = 1;
			$input['maxProperties'] = 1;
		} elseif ( $required ) {
			$input['required'] = $required;
		} else {
			$input['default'] = array();
		}
		if ( 'PATCH' === $method ) {
			$input['minProperties'] = 2;
		}

		wp_register_ability(
			'noptin/' . $name,
			array(
				'label'               => $label,
				'description'         => $description,
				'category'            => 'noptin',
				'input_schema'        => $input,
				'output_schema'       => array( 'type' => 'object' ),
				'permission_callback' => static function () {
					return current_user_can( get_noptin_collection_capability( 'subscribers' ) );
				},
				'execute_callback'    => static function ( $args = array() ) use ( $method, $single, $response_fields ) {
					return self::request( $method, $single, $response_fields, $args );
				},
				'meta'                => array(
					'mcp'         => array( 'public' => true ),
					'annotations' => array(
						'readonly'    => 'GET' === $method,
						'destructive' => in_array( $method, array( 'PATCH', 'DELETE' ), true ),
					),
				),
			)
		);
	}

	/**
	 * Run the ability through the permission and validation checks of the subscriber endpoint.
	 *
	 * @param string $method          HTTP method.
	 * @param bool   $single          Whether to address an individual subscriber.
	 * @param array  $response_fields Fields to include in returned subscriber records.
	 * @param array  $args            Validated ability input.
	 * @return array|\WP_Error
	 */
	private static function request( $method, $single, $response_fields, $args ) {
		$route = '/noptin/v1/subscribers';
		if ( $single && 'GET' === $method && isset( $args['email'] ) ) {
			$route .= '/email/' . rawurlencode( $args['email'] );
			unset( $args['email'] );
		} elseif ( $single ) {
			$route .= '/' . (int) $args['id'];
			unset( $args['id'] );
		}
		$request = new \WP_REST_Request( $method, $route );
		$request->set_query_params( array( '_fields' => implode( ',', $response_fields ) ) );
		foreach ( $args as $key => $value ) {
			$request->set_param( $key, $value );
		}
		$response = rest_do_request( $request );
		if ( $response->is_error() ) {
			return $response->as_error();
		}
		if ( 'DELETE' === $method ) {
			return array( 'deleted' => true );
		}
		if ( 'GET' === $method && ! $single ) {
			$headers = $response->get_headers();
			return array(
				'subscribers' => $response->get_data(),
				'total'       => (int) $headers['X-WP-Total'],
				'total_pages' => (int) $headers['X-WP-TotalPages'],
			);
		}
		return $response->get_data();
	}
}
