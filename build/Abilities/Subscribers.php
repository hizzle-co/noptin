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

		$schema        = $collection->get_rest_schema();
		$fields        = array();
		$choice_fields = array();
		$names         = array_merge( array( 'email', 'status', 'tags' ), wp_list_pluck( get_noptin_custom_fields(), 'merge_tag' ) );
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
			if ( 'tags' === $name || ( $prop && null !== $prop->enum ) ) {
				$choice_fields[] = $name;
				$lookup = sprintf( 'Call noptin/get-subscriber-field-options with {"field":"%s"} for this site\'s current values and labels.', $name );
				$fields[ $name ]['description'] .= ' ' . $lookup;
				if ( 'tags' === $name ) {
					$fields[ $name ]['description'] .= ' Pass an array of tag names. New tag names are also accepted.';
				} elseif ( isset( $fields[ $name ]['items'] ) ) {
					$fields[ $name ]['description'] .= ' Pass an array of option values.';
				} else {
					$fields[ $name ]['description'] .= ' Pass one option value.';
				}
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
		$response_fields = array_merge( array_keys( $id + $fields ), array( 'date_created', 'email_engagement_score' ) );
		$list            = $collection->get_query_schema();
		foreach ( $list as $key => $argument ) {
			unset( $list[ $key ]['sanitize_callback'], $list[ $key ]['validate_callback'] );
			if ( isset( $argument['type'] ) && is_array( $argument['type'] ) ) {
				$list[ $key ]['type'] = array_values( $argument['type'] );
			}
		}

		foreach ( $choice_fields as $field ) {
			foreach ( array( $field, $field . '_not' ) as $filter ) {
				if ( isset( $list[ $filter ] ) ) {
					$list[ $filter ]['description'] .= sprintf( ' Look up existing values with noptin/get-subscriber-field-options using {"field":"%s"}.', $field );
				}
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
			'get-subscriber-field-options',
			'Get newsletter subscriber field options',
			'Look up the current tag names, lists, or choices for a Noptin email newsletter subscriber field. Match the label and pass its value when assigning or filtering subscribers.',
			'GET',
			array( 'field' => array( 'type' => 'string', 'enum' => array_values( array_unique( $choice_fields ) ) ) ),
			array( 'field' ),
			array(),
			array(
				'options' => array(
					'type'  => 'array',
					'items' => array(
						'type'       => 'object',
						'properties' => array(
							'value' => array( 'type' => 'string', 'description' => 'Exact value to pass to the subscriber ability.' ),
							'label' => array( 'type' => 'string', 'description' => 'Display label on this site.' ),
						),
						'required'   => array( 'value', 'label' ),
					),
				),
			)
		);

		self::register_tool(
			'list-subscribers',
			'List newsletter subscribers',
			'List Noptin email newsletter subscribers. For newest subscribers, order by date_created descending. For least engaged, order by email_engagement_score ascending.',
			'GET',
			$list,
			array(),
			$response_fields,
			array(
				'subscribers' => array( 'type' => 'array', 'items' => array( 'type' => 'object' ), 'description' => 'Subscriber records on the requested page.' ),
				'total'       => array( 'type' => 'integer', 'description' => 'Number of subscribers matching the filters across all pages.' ),
				'total_pages' => array( 'type' => 'integer', 'description' => 'Number of result pages for the current filters and page size.' ),
			)
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
				'bulk_update' => array( 'type' => 'object', 'properties' => $fields, 'minProperties' => 1, 'additionalProperties' => false ),
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
			$id + array( 'action' => array( 'type' => 'string', 'enum' => array( 'send_confirmation_email', 'block_subscriber', 'unblock_subscriber' ) ) ),
			array( 'id', 'action' ),
			$response_fields
		);
		$tabs = array_filter( apply_filters( 'hizzle_rest_noptin_subscribers_record_tabs', array() ), static function ( $tab ) {
			return is_callable( $tab['callback'] ?? null );
		} );
		if ( $tabs ) {
			self::register_tool(
				'get-subscriber-tab',
				'Get newsletter subscriber tab',
				'Read email history, activity, or another registered tab for a Noptin email newsletter subscriber.',
				'GET',
				$id + array( 'tab_id' => array( 'type' => 'string', 'enum' => array_keys( $tabs ) ) ),
				array( 'id', 'tab_id' ),
				$response_fields
			);
		}

		$filters = array_diff_key( $list, array_flip( array( 'page', 'paged', 'per_page', 'offset', 'order', 'orderby' ) ) );
		$columns = array_intersect( $collection->get_known_fields()['main'], $response_fields );
		$metrics = array_fill_keys( $columns, array( 'type' => 'string', 'enum' => array( 'COUNT', 'SUM', 'AVG', 'MIN', 'MAX' ) ) );
		$metrics['id'] = array( 'type' => 'string', 'enum' => array( 'COUNT', 'MIN', 'MAX' ) );
		self::register_tool(
			'aggregate-subscribers',
			'Summarize newsletter subscribers',
			'Calculate counts and summary statistics for Noptin email newsletter subscribers. For counts by status, use aggregate {"id":"COUNT"} and groupby "status".',
			'GET',
			$filters + array(
				'aggregate' => array( 'type' => 'object', 'properties' => $metrics, 'minProperties' => 1, 'additionalProperties' => false ),
				'groupby'   => array( 'type' => 'string', 'enum' => array_keys( $metrics ) ),
			),
			array( 'aggregate' ),
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
	 * @param array  $output_fields   Properties of a structured result.
	 */
	private static function register_tool( $name, $label, $description, $method, $properties, $required, $response_fields, $output_fields = array() ) {
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
		if ( 'update-subscriber' === $name ) {
			$input['minProperties'] = 2;
		}

		$output = array( 'type' => 'object' );
		if ( $output_fields ) {
			$output['properties'] = $output_fields;
			$output['required']   = array_keys( $output_fields );
		}
		wp_register_ability(
			'noptin/' . $name,
			array(
				'label'               => $label,
				'description'         => $description,
				'category'            => 'noptin',
				'input_schema'        => $input,
				'output_schema'       => $output,
				'permission_callback' => static function () {
					return current_user_can( get_noptin_collection_capability( 'subscribers' ) );
				},
				'execute_callback'    => static function ( $args = array() ) use ( $method, $name, $response_fields ) {
					return self::request( $method, $name, $response_fields, $args );
				},
				'meta'                => array(
					'mcp'         => array( 'public' => true ),
					'annotations' => array(
						'readonly'    => 'GET' === $method,
						'destructive' => 'run-subscriber-action' === $name || in_array( $method, array( 'PATCH', 'DELETE' ), true ),
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
		if ( 'get-subscriber-field-options' === $name ) {
			return self::field_options( $args['field'] );
		}
		$route = '/noptin/v1/subscribers';
		if ( 'get-subscriber' === $name && isset( $args['email'] ) ) {
			$route .= '/email/' . rawurlencode( $args['email'] );
			unset( $args['email'] );
		} elseif ( isset( $args['id'] ) ) {
			$route .= '/' . (int) $args['id'];
			unset( $args['id'] );
		}
		if ( 'run-subscriber-action' === $name ) {
			$route .= '/remote-action/' . rawurlencode( $args['action'] );
			unset( $args['action'] );
		} elseif ( 'get-subscriber-tab' === $name ) {
			$route .= '/' . rawurlencode( $args['tab_id'] );
			unset( $args['tab_id'] );
		} elseif ( 'aggregate-subscribers' === $name ) {
			$route .= '/aggregate';
		}
		$request = new \WP_REST_Request( $method, $route );
		if ( in_array( $name, array( 'bulk-update-subscribers', 'bulk-delete-subscribers' ), true ) ) {
			// Explicit IDs may include blocked subscribers, which collection queries hide by default.
			$args['status'] = array_keys( noptin_get_subscriber_statuses() );
		}
		if ( in_array( $name, array( 'list-subscribers', 'get-subscriber', 'create-subscriber', 'update-subscriber', 'run-subscriber-action' ), true ) ) {
			$request->set_query_params( array( '_fields' => implode( ',', $response_fields ) ) );
		}
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
		if ( 'run-subscriber-action' === $name ) {
			return $response->get_data()['result'];
		}
		if ( in_array( $name, array( 'get-subscriber-tab', 'aggregate-subscribers', 'bulk-update-subscribers' ), true ) ) {
			return array( 'results' => $response->get_data() );
		}
		if ( 'list-subscribers' === $name ) {
			$headers = $response->get_headers();
			return array(
				'subscribers' => $response->get_data(),
				'total'       => (int) $headers['X-WP-Total'],
				'total_pages' => (int) $headers['X-WP-TotalPages'],
			);
		}
		return $response->get_data();
	}

	/**
	 * Read the current option values and labels after the subscriber permission check.
	 *
	 * @param string $field Registered subscriber field name.
	 * @return array
	 */
	private static function field_options( $field ) {
		$options = array();
		if ( 'tags' === $field ) {
			foreach ( get_noptin_subscriber_tags() as $tag ) {
				$options[] = array( 'value' => $tag, 'label' => $tag );
			}
		} else {
			$prop    = noptin()->db()->store->get( 'subscribers' )->get_prop( $field );
			$choices = is_callable( $prop->enum ) ? call_user_func( $prop->enum ) : $prop->enum;
			foreach ( (array) $choices as $value => $label ) {
				$options[] = array( 'value' => (string) $value, 'label' => (string) $label );
			}
		}
		return array( 'options' => $options );
	}
}
