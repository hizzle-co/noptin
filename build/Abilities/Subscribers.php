<?php

namespace Hizzle\Noptin\Abilities;

use Hizzle\Noptin\Subscribers\Fields_REST_API;

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
			$prop = $collection->get_prop( $name );
			if ( $prop && ( $prop->is_tokens || null !== $prop->enum ) && wp_has_ability( 'noptin/get-subscriber-field-options' ) ) {
				$fields[ $name ]['description'] .= sprintf( ' Look up current values and labels with noptin/get-subscriber-field-options using {"field":"%s"}.', $name );
			}
			if ( $prop && $prop->is_tokens ) {
				$fields[ $name ]['description'] .= ' New values are also accepted.';
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
		$response_fields = wp_list_pluck( $collection->get_props(), 'name' );

		self::register_tool(
			'create-subscriber',
			'Create newsletter subscriber',
			'Create a Noptin email newsletter subscriber with an email address and optional subscriber fields.',
			'POST',
			$fields,
			array( 'email' ),
			$response_fields
		);

		self::register_tool(
			'update-subscriber',
			'Update newsletter subscriber',
			'Update the supplied fields on a Noptin email newsletter subscriber by subscriber ID.',
			'PATCH',
			$id + $fields,
			array( 'id' ),
			$response_fields
		);

		self::register_tool(
			'delete-subscriber',
			'Delete newsletter subscriber',
			'Permanently delete one Noptin email newsletter subscriber and their associated email logs and tasks by subscriber ID.',
			'DELETE',
			$id,
			array( 'id' ),
			$response_fields
		);

		$selection = array(
			'include' => array(
				'type'        => 'array',
				'items'       => $id['id'],
				'minItems'    => 1,
				'uniqueItems' => true,
				'description' => 'IDs of the newsletter subscribers to change.',
			),
		);
		self::register_tool(
			'bulk-update-subscribers',
			'Bulk update newsletter subscribers',
			'Apply the same changes to the selected Noptin email newsletter subscribers. Results report success or failure by subscriber ID.',
			'PATCH',
			$selection + array(
				'bulk_update' => array(
					'type'                 => 'object',
					'properties'           => $fields,
					'minProperties'        => 1,
					'additionalProperties' => false,
				),
			),
			array( 'include', 'bulk_update' ),
			$response_fields
		);
		self::register_tool(
			'bulk-delete-subscribers',
			'Bulk delete newsletter subscribers',
			'Permanently delete the selected Noptin email newsletter subscribers and their associated email logs and tasks.',
			'DELETE',
			$selection,
			array( 'include' ),
			$response_fields
		);
		self::register_tool(
			'run-subscriber-action',
			'Run a newsletter subscriber action',
			'Send a confirmation email to a Noptin email newsletter subscriber, block the subscriber, or unblock them and set their status to subscribed.',
			'POST',
			$id + array(
				'action' => array(
					'type' => 'string',
					'enum' => array( 'send_confirmation_email', 'block_subscriber', 'unblock_subscriber' ),
				),
			),
			array( 'id', 'action' ),
			$response_fields
		);

		$segment_fields = array();
		foreach ( Fields_REST_API::get_custom_field_args() as $key => $definition ) {
			// Share the REST constraints without exposing PHP callbacks or injecting defaults.
			$segment_fields[ $key ] = array_intersect_key( $definition, array_flip( array( 'type', 'description', 'enum', 'items', 'format', 'minLength', 'maxLength', 'pattern' ) ) );
		}
		$segment_fields['merge_tag']['minLength'] = 1;
		$segment_fields['merge_tag']['description'] = 'Stable segment merge tag. The built-in tags field is protected.';
		self::register_tool(
			'save-subscriber-segment',
			'Save newsletter subscriber segment',
			'Create or update a Noptin email newsletter subscriber segment by merge tag. New segments require label and type. Supplied settings, including type and options, replace saved values; omitted settings are preserved. Options use one value per line or value|label. The built-in tags field cannot be redefined.',
			'POST',
			$segment_fields,
			array( 'merge_tag' ),
			array()
		);
		self::register_tool(
			'delete-subscriber-segment',
			'Delete newsletter subscriber segment',
			'Permanently delete a Noptin email newsletter subscriber segment, its options, and its assigned subscriber values by merge tag. Subscriber records are kept. Update forms, emails, or automations that use this segment. The built-in tags field is protected.',
			'DELETE',
			array( 'merge_tag' => $segment_fields['merge_tag'] ),
			array( 'merge_tag' ),
			array()
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
		$input  = array(
			'type'                 => 'object',
			'properties'           => $properties,
			'additionalProperties' => false,
		);
		if ( $required ) {
			$input['required'] = $required;
		} else {
			$input['default'] = array();
		}
		if ( 'update-subscriber' === $name ) {
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
				'execute_callback'    => static function ( $args = array() ) use ( $method, $name, $response_fields ) {
					return self::request( $method, $name, $response_fields, $args );
				},
				'meta'                => array(
					'show_in_rest' => true,
					'mcp'          => array( 'public' => true ),
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => in_array( $name, array( 'run-subscriber-action', 'save-subscriber-segment' ), true ) || in_array( $method, array( 'PATCH', 'DELETE' ), true ),
					),
				),
			)
		);
	}

	/**
	 * Run the ability through the permission and validation checks of the subscriber endpoint.
	 *
	 * @param string $method          HTTP method.
	 * @param string $name            Subscriber ability slug.
	 * @param array  $response_fields Fields to include in returned subscriber records.
	 * @param array  $args            Validated ability input.
	 * @return array|\WP_Error
	 */
	private static function request( $method, $name, $response_fields, $args ) {
		$route = '/noptin/v1/subscribers';
		if ( in_array( $name, array( 'save-subscriber-segment', 'delete-subscriber-segment' ), true ) ) {
			$route = '/' . Fields_REST_API::REST_NAMESPACE . '/' . Fields_REST_API::REST_BASE;
			if ( 'delete-subscriber-segment' === $name ) {
				$route .= '/' . rawurlencode( $args['merge_tag'] );
				unset( $args['merge_tag'] );
			}
		}
		if ( isset( $args['id'] ) ) {
			$route .= '/' . (int) $args['id'];
			unset( $args['id'] );
		}
		if ( 'run-subscriber-action' === $name ) {
			$route .= '/remote-action/' . rawurlencode( $args['action'] );
			unset( $args['action'] );
		}
		$request = new \WP_REST_Request( $method, $route );
		if ( in_array( $name, array( 'bulk-update-subscribers', 'bulk-delete-subscribers' ), true ) ) {
			// Explicit IDs may include blocked subscribers, which collection queries hide by default.
			$args['status'] = array_keys( noptin_get_subscriber_statuses() );
		}
		if ( in_array( $name, array( 'create-subscriber', 'update-subscriber', 'run-subscriber-action' ), true ) ) {
			$request->set_query_params( array( '_fields' => implode( ',', $response_fields ) ) );
		}
		foreach ( $args as $key => $value ) {
			$request->set_param( $key, $value );
		}
		$response = rest_do_request( $request );
		if ( $response->is_error() ) {
			return $response->as_error();
		}
		if ( 'DELETE' === $method && 'delete-subscriber-segment' !== $name ) {
			return array( 'deleted' => true );
		}
		if ( 'run-subscriber-action' === $name ) {
			return $response->get_data()['result'];
		}
		if ( 'bulk-update-subscribers' === $name ) {
			return array( 'results' => $response->get_data() );
		}
		return $response->get_data();
	}
}
