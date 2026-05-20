<?php

namespace Hizzle\Noptin\Subscribers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Manages subscriber option-based fields via a REST API.
 *
 * Field values are stored in the wp_noptin_subscriber_meta table
 * (meta_key = field merge tag, one row per subscriber-value pair).
 *
 * For tags, unassigned values are stored in the wp_options row
 * 'noptin_subscriber_tags'. For normal custom fields, options are
 * stored in noptin_options.custom_fields[].options.
 *
 * @since 3.3.0
 */
class Fields_REST_API {

	const REST_NAMESPACE = 'noptin/v1';
	const REST_BASE      = 'subscribers/fields';
	const TAGS_FIELD     = 'tags';
	const SKIP_FIELDS    = array( 'status', 'language' );

	/**
	 * Registers REST routes.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * Registers REST routes for tag management.
	 */
	public static function register_routes() {

		// Field options endpoint.
		register_rest_route(
			self::REST_NAMESPACE,
			'/' . self::REST_BASE . '/(?P<field>[a-zA-Z0-9_-]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'get_field_options' ),
					'permission_callback' => array( __CLASS__, 'check_permission' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'create_field_option' ),
					'permission_callback' => array( __CLASS__, 'check_permission' ),
					'args'                => array(
						'value' => array(
							'description'       => 'The option value.',
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'label' => array(
							'description'       => 'An optional label.',
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				'schema' => '__return_empty_array',
			)
		);

		// Update field endpoint.
		register_rest_route(
			self::REST_NAMESPACE,
			'/' . self::REST_BASE . '/(?P<field>[a-zA-Z0-9_-]+)/(?P<option>[^/]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'update_field_option' ),
					'permission_callback' => array( __CLASS__, 'check_permission' ),
					'args'                => array(
						'value' => array(
							'description'       => 'The option value.',
							'type'              => 'string',
							'required'          => true,
							'sanitize_callback' => 'sanitize_text_field',
						),
						'label' => array(
							'description'       => 'An optional label.',
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( __CLASS__, 'delete_field_option' ),
					'permission_callback' => array( __CLASS__, 'check_permission' ),
					'schema'              => '__return_empty_array',
				),
				'schema' => '__return_empty_array',
			)
		);

		// Merge options.
		register_rest_route(
			self::REST_NAMESPACE,
			'/' . self::REST_BASE . '/(?P<field>[a-zA-Z0-9_-]+)/(?P<target_option>[^/]+)/merge',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'merge_field_options' ),
				'permission_callback' => array( __CLASS__, 'check_permission' ),
				'args'                => array(
					'source_options' => array(
						'description' => 'Array of option values to merge into the target value.',
						'type'        => 'array',
						'required'    => true,
						'items'       => array(
							'type' => 'string',
						),
					),
				),
				'schema'              => '__return_empty_array',
			)
		);
	}

	/**
	 * Checks if the current user has permission.
	 */
	public static function check_permission() {
		return current_user_can( get_noptin_capability() );
	}

	/**
	 * Returns all values for an option-based field with subscriber counts.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function get_field_options( $request ) {
		global $wpdb;

		$field = self::get_field_from_request( $request );
		if ( is_wp_error( $field ) ) {
			return $field;
		}

		list( $field, $field_details ) = $field;

		if ( ! self::is_multi_value_field( $field, $field_details ) ) {
			$counts = noptin()->db()->query(
				'subscribers',
				array(
					'aggregate' => array(
						'id' => array( 'COUNT' ),
					),
					'groupby'   => array( $field ),
					'per_page'  => -1,
				),
				'aggregate'
			);

			$counts  = wp_list_pluck( $counts, 'count_id', $field );
			$options = array();

			$all_options = $field_details['options'] ?? array();

			asort( $all_options );

			foreach ( $all_options as $key => $label ) {
				$options[] = array(
					'value' => $key,
					'label' => $label,
					'count' => $counts[ $key ] ?? 0,
				);
			}

			return rest_ensure_response( $options );
		}

		// Fetch all values from subscriber meta table with subscriber counts.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_value AS field_value, COUNT(*) AS subscriber_count
			 FROM {$wpdb->prefix}noptin_subscriber_meta
			 WHERE meta_key = %s
			 GROUP BY meta_value
			 ORDER BY meta_value ASC",
				$field
			),
			ARRAY_A
		);

		$counts = wp_list_pluck( $results, 'subscriber_count', 'field_value' );

		$all_options = $field_details['options'] ?? array();
		if ( self::TAGS_FIELD === $field ) {
			$all_options = array_keys( $counts );
			$unassigned  = get_option( 'noptin_subscriber_tags', array() );

			if ( is_array( $unassigned ) ) {
				$all_options = array_unique( array_merge( $all_options, $unassigned ) );
			}

			$all_options = array_combine( $all_options, $all_options );
		}

		ksort( $all_options );

		$options = array();
		foreach ( $all_options as $key => $label ) {
			$options[] = array(
				'value' => $key,
				'label' => $label,
				'count' => $counts[ $key ] ?? 0,
			);
		}

		return rest_ensure_response( $options );
	}

	/**
	 * Creates a new unassigned option value.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function create_field_option( $request ) {
		$field = self::get_field_from_request( $request );

		if ( is_wp_error( $field ) ) {
			return $field;
		}

		list ( $field ) = $field;

		$value = $request->get_param( 'value' );
		if ( '' === $value ) {
			return new \WP_Error( 'noptin_empty_option', 'Option value cannot be empty.', array( 'status' => 400 ) );
		}

		$unassigned = self::get_unassigned_options( $field );
		if ( self::TAGS_FIELD === $field ) {
			$unassigned[] = $value;
			$unassigned   = array_unique( array_values( $unassigned ) );
		} else {
			$label = $request->get_param( 'label' );

			$unassigned[ $value ] = empty( $label ) ? $value : $label;
		}

		return rest_ensure_response( self::save_field_options( $field, $unassigned ) );
	}

	/**
	 * Updates a field option value.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function update_field_option( $request ) {
		global $wpdb;

		$field = self::get_field_from_request( $request );

		if ( is_wp_error( $field ) ) {
			return $field;
		}

		list ( $field, $config ) = $field;

		$option = $request->get_param( 'option' );
		$value  = $request->get_param( 'value' );

		if ( ! isset( $option, $value ) || '' === $option || '' === $value ) {
			return new \WP_Error( 'noptin_empty_option', 'Old and new values are required.', array( 'status' => 400 ) );
		}

		// 1. Update unassigned values stored in settings.
		$unassigned = self::get_unassigned_options( $field );
		if ( self::TAGS_FIELD === $field ) {
			$unassigned[] = $value;
			$unassigned   = array_diff( $unassigned, array( $option ) );
		} else {
			$label = $request->get_param( 'label' );

			$unassigned[ $value ] = empty( $label ) ? $value : $label;
			unset( $unassigned[ $option ] );
		}

		$saved = self::save_field_options( $field, $unassigned );

		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		if ( $option === $value ) {
			return rest_ensure_response( array( 'updated' => 0 ) );
		}

		return self::save_merged_field_options_subscribers_db( $option, $value, $field, $config );
	}

	/**
	 * Updates the subscribers db with merged values.
	 *
	 * For each subscriber that has a source value but not the target value,
	 * the source value is renamed to the target value. For subscribers that
	 * already have both values the source value is removed to avoid
	 * duplicate entries.
	 *
	 * @param string|string[] $source_options Option value(s) to merge from.
	 * @param string $target_option Option value to merge into.
	 * @param string $field The field being merged.
	 * @param array $config Field config.
	 */
	private static function save_merged_field_options_subscribers_db( $source_options, $target_option, $field, $config ) {
		global $wpdb;

		$source_options = (array) $source_options;

		// Sanitize source values and remove the target from the list.
		$source_options = array_values( array_diff( array_map( 'sanitize_text_field', $source_options ), array( $target_option ) ) );

		if ( empty( $source_options ) ) {
			return rest_ensure_response( array( 'merged' => true ) );
		}

		// 1. Update rules and emails.
		self::update_automation_rules_field( $field, $source_options, $target_option );
		self::update_email_options_field( $field, $source_options, $target_option );

		// 2. Update subscriber table.
		$placeholders = implode( ', ', array_fill( 0, count( $source_options ), '%s' ) );
		if ( self::is_multi_value_field( $field, $config ) ) {
			// Fetch subscriber ids who have either source or target.
			$list_args      = array_merge( array( $field, $target_option ), $source_options );
			$subscriber_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT noptin_subscriber_id
					FROM {$wpdb->prefix}noptin_subscriber_meta
					WHERE meta_key = %s
					AND meta_value IN (%s, $placeholders)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$list_args
				)
			);

			if ( empty( $subscriber_ids ) ) {
				return rest_ensure_response( array( 'updated' => 0 ) );
			}

			// We already have the subscriber IDS, so delete all existing meta entries.
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->prefix}noptin_subscriber_meta
					WHERE meta_key = %s
					AND meta_value IN (%s, $placeholders)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					(array) $list_args
				)
			);

			// Insert target value for all subscribers who have either source or target value.
			$updated = 0;

			// Process in chunks of 500 to avoid query size limits.
			$chunked_subscribers = array_chunk( $subscriber_ids, 500 );
			foreach ( $chunked_subscribers as $chunk ) {
				if ( empty( $chunk ) ) {
					continue;
				}

				$insert_placeholders = array();
				$insert_args         = array();

				foreach ( $chunk as $sub_id ) {
					$insert_placeholders[] = '(%d, %s, %s)';
					$insert_args[]         = $sub_id;
					$insert_args[]         = $field;
					$insert_args[]         = $target_option;
				}

				$values_sql = implode( ', ', $insert_placeholders );
				$updated   += (int) $wpdb->query(
					$wpdb->prepare(
						"INSERT INTO {$wpdb->prefix}noptin_subscriber_meta (noptin_subscriber_id, meta_key, meta_value) VALUES $values_sql", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						$insert_args
					)
				);
			}
		} else {
			$esc_field = esc_sql( $field );
			$updated   = (int) $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->prefix}noptin_subscribers SET $esc_field = %s WHERE $esc_field IN ($placeholders)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					array_merge(
						array( $target_option ),
						$source_options
					)
				)
			);
		}

		self::flush_subscriber_caches();

		return rest_ensure_response( array( 'updated' => (int) $updated ) );
	}

	/**
	 * Deletes an option value from subscriber meta and unassigned options.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function delete_field_option( $request ) {
		global $wpdb;

		$field = self::get_field_from_request( $request );

		if ( is_wp_error( $field ) ) {
			return $field;
		}

		list ( $field, $config ) = $field;

		$value = $request->get_param( 'option' );
		if ( '' === $value ) {
			return new \WP_Error( 'noptin_empty_option', 'Option value cannot be empty.', array( 'status' => 400 ) );
		}

		// 1. Update unassigned values stored in settings.
		$unassigned = self::get_unassigned_options( $field );
		if ( self::TAGS_FIELD === $field ) {
			$unassigned = array_diff( $unassigned, array( $value ) );
		} else {
			unset( $unassigned[ $value ] );
		}

		$saved = self::save_field_options( $field, $unassigned );

		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		// 2. Delete all subscriber-value relationships.
		if ( self::is_multi_value_field( $field, $config ) ) {
			$deleted = $wpdb->delete(
				"{$wpdb->prefix}noptin_subscriber_meta",
				array(
					'meta_key'   => $field,
					'meta_value' => $value,
				),
				array( '%s', '%s' )
			);
		} else {
			$deleted = $wpdb->update(
				"{$wpdb->prefix}noptin_subscribers",
				array( $field => '' ),
				array( $field => $value ),
				array( '%s' ),
				array( '%s' )
			);
		}

		self::flush_subscriber_caches();

		return rest_ensure_response( array( 'deleted' => (int) ( false === $deleted ? 0 : $deleted ) ) );
	}

	/**
	 * Merges one or more source option values into a target value.
	 *
	 * For each subscriber that has a source value but not the target value,
	 * the source value is renamed to the target value. For subscribers that
	 * already have both values the source value is removed to avoid
	 * duplicate entries.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function merge_field_options( $request ) {
		global $wpdb;

		$field = self::get_field_from_request( $request );

		if ( is_wp_error( $field ) ) {
			return $field;
		}

		list ( $field, $config ) = $field;

		$source_options = $request->get_param( 'source_options' );
		$target_option  = $request->get_param( 'target_option' );

		if ( empty( $source_options ) || ! is_array( $source_options ) || '' === $target_option ) {
			return new \WP_Error( 'noptin_invalid_params', 'Source options and target option are required.', array( 'status' => 400 ) );
		}

		// Sanitize source values and remove the target from the list.
		$source_options = array_values( array_diff( array_map( 'sanitize_text_field', $source_options ), array( $target_option ) ) );

		if ( empty( $source_options ) ) {
			return rest_ensure_response( array( 'updated' => 0 ) );
		}

		// 1. Update unassigned values stored in settings.
		$unassigned = self::get_unassigned_options( $field );
		if ( self::TAGS_FIELD === $field ) {
			$unassigned[] = $target_option;
			$unassigned   = array_diff( $unassigned, $source_options );
		} else {
			if ( ! isset( $unassigned[ $target_option ] ) ) {
				$unassigned[ $target_option ] = $target_option;
			}

			$unassigned = array_diff_key( $unassigned, array_flip( $source_options ) );
		}

		$saved = self::save_field_options( $field, $unassigned );

		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return self::save_merged_field_options_subscribers_db( $source_options, $target_option, $field, $config );
	}

	// -------------------------------------------------------------------------
	// Helper methods
	// -------------------------------------------------------------------------

	/**
	 * Retrieves and validates field key from a request.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 */
	private static function get_field_from_request( $request ) {
		$field = (string) $request->get_param( 'field' );

		if ( '' === $field ) {
			return new \WP_Error( 'noptin_missing_field', 'Field is required', array( 'status' => 400 ) );
		}

		if ( in_array( $field, self::SKIP_FIELDS, true ) ) {
			return new \WP_Error( 'noptin_invalid_field', 'Invalid field.', array( 'status' => 400 ) );
		}

		$all_fields = Records::subscriber_fields();

		if ( ! isset( $all_fields[ $field ] ) ) {
			return new \WP_Error( 'noptin_field_not_found', 'Field not found.', array( 'status' => 404 ) );
		}

		return array( $field, $all_fields[ $field ] );
	}

	/**
	 * Reads unassigned options for a field from its configured storage.
	 *
	 * @param string $field Field merge tag.
	 * @return array
	 */
	private static function get_unassigned_options( $field ) {
		if ( self::TAGS_FIELD === $field ) {
			$options = get_option( 'noptin_subscriber_tags', array() );
			return is_array( $options ) ? array_unique( array_values( array_filter( array_map( 'sanitize_text_field', $options ) ) ) ) : array();
		}

		$field = get_noptin_custom_field( $field );

		if ( is_array( $field ) && ! empty( $field['options'] ) ) {
			return noptin_newslines_to_array( $field['options'] );
		}

		return array();
	}

	/**
	 * Persists unassigned options for a field in the correct storage location.
	 *
	 * @param string $field Field merge tag.
	 * @param array  $options Option values.
	 * @return true|\WP_Error
	 */
	public static function save_field_options( $field, $options ) {
		$prepared = array();

		foreach ( (array) $options as $key => $value ) {
			if ( is_int( $key ) ) {
				$key = $value;
			}

			$prepared[ sanitize_text_field( $key ) ] = sanitize_text_field( $value );
		}

		if ( self::TAGS_FIELD === $field ) {
			update_option( 'noptin_subscriber_tags', array_unique( array_values( $prepared ) ), false );
			return true;
		}

		// Fetch available fields.
		$custom_fields = get_noptin_option(
			'custom_fields',
			\Hizzle\Noptin\Fields\Main::default_fields()
		);

		foreach ( $custom_fields as $index => $custom_field ) {
			if ( ! is_array( $custom_field ) || empty( $custom_field['merge_tag'] ) ) {
				continue;
			}

			if ( $custom_field['merge_tag'] === $field ) {
				$to_string = '';

				foreach ( $prepared as $key => $value ) {
					if ( $key === $value ) {
						$to_string .= $key . "\n";
					} else {
						$to_string .= $key . '|' . $value . "\n";
					}
				}

				$custom_fields[ $index ]['options'] = trim( $to_string );
				update_noptin_option( 'custom_fields', $custom_fields );
				return true;
			}
		}

		return new \WP_Error( 'noptin_field_not_found', 'Field not found.', array( 'status' => 404 ) );
	}

	/**
	 * Flushes all subscriber-related object caches.
	 *
	 * Clears both the main record cache group and the WP metadata cache group
	 * so that subsequent reads via noptin_get_subscriber() always return fresh data.
	 */
	private static function flush_subscriber_caches() {
		if ( function_exists( 'wp_cache_supports' ) && wp_cache_supports( 'flush_group' ) ) {
			// Main record cache (noptin_subscribers).
			wp_cache_flush_group( 'noptin_subscribers' );
			// WP metadata API cache (get_metadata uses <meta_type>_meta as group).
			wp_cache_flush_group( 'noptin_subscriber_meta' );
		} else {
			wp_cache_flush();
		}
	}

	/**
	 * Determines whether a field stores multiple values in subscriber meta.
	 *
	 * @param string $field Field merge tag.
	 * @param array  $field_config Field config.
	 * @return bool
	 */
	private static function is_multi_value_field( $field, $field_config ) {
		return self::TAGS_FIELD === $field || ! empty( $field_config['multiple'] ) || ! empty( $field_config['is_multiple'] );
	}

	/**
	 * Updates automation rules that reference the old tag name in their
	 * conditional logic or action/trigger settings.
	 *
	 * @param string $field The field merge tag.
	 * @param string|string[] $old_name The old tag name.
	 * @param string $new_name The new tag name.
	 */
	private static function update_automation_rules_field( $field, $old_name, $new_name ) {
		$old_name = (array) $old_name;

		/** @var \Hizzle\Noptin\Automation_Rules\Automation_Rule $rule */
		foreach ( noptin_get_automation_rules() as $rule ) {
			$trigger_settings  = $rule->get_trigger_settings();
			$action_settings   = $rule->get_action_settings();
			$conditional_logic = $rule->get_conditional_logic();
			$needs_saving      = false;

			// 1. Update Trigger Settings
			if ( isset( $trigger_settings[ $field ] ) ) {
				$trigger_settings[ $field ] = self::update_value( $trigger_settings[ $field ], $old_name, $new_name, $needs_saving );
			}

			// 2. Update Action Settings
			if ( isset( $action_settings[ $field ] ) ) {
				$action_settings[ $field ] = self::update_value( $action_settings[ $field ], $old_name, $new_name, $needs_saving );
			}

			// 3. Update Conditional Logic
			if ( ! empty( $conditional_logic['rules'] ) && is_array( $conditional_logic['rules'] ) ) {
				$rule_keys = array( 'full', 'type', 'value' );
				$pattern   = '/(?<=^|,|\s)(' . implode( '|', array_map( 'preg_quote', $old_name, array_fill( 0, count( $old_name ), '/' ) ) ) . ')(?=$|,|\s)/';
				foreach ( $conditional_logic['rules'] as $index => $condition_rule ) {
					foreach ( $rule_keys as $key ) {
						$value = $condition_rule[ $key ] ?? null;
						if ( is_string( $value ) && strpos( $value, '[[' . $field ) !== false ) {
							foreach ( $rule_keys as $other_key ) {
								$other_value = $condition_rule[ $other_key ] ?? null;
								// Check if any of the other values in the rule reference the old names.
								if ( is_string( $other_value ) && strpos( $other_value, '[[' ) === false && preg_match( $pattern, $other_value ) ) {
									$conditional_logic['rules'][ $index ][ $other_key ] = preg_replace( $pattern, $new_name, $other_value );

									$needs_saving = true;
								}
							}
						}
					}
				}

				// Attach the updated conditional logic back to the trigger settings payload
				$trigger_settings['conditional_logic'] = $conditional_logic;
			}

			// 4. Save the rule if anything was modified
			if ( $needs_saving ) {
				$rule->set_trigger_settings( $trigger_settings );
				$rule->set_action_settings( $action_settings );
				$rule->save();
			}
		}
	}

	/**
	 * Helper function to update matching values.
	 * Supports both single string values and arrays of values.
	 *
	 * @param string|array $current_value The current value(s) to check and update.
	 * @param string[] $old_values The old value(s) to look for.
	 * @param string $new_value The new value to replace with.
	 * @param bool $needs_saving Reference flag to indicate if an update was made.
	 * @return string|array The updated value(s).
	 */
	private static function update_value( $current_value, $old_values, $new_value, &$needs_saving ) {
		if ( is_array( $current_value ) ) {
			$updated_array = array_map(
				function ( $v ) use ( $old_values, $new_value, &$needs_saving ) {
					if ( in_array( $v, $old_values, true ) ) {
						$needs_saving = true;
						return $new_value;
					}

					return $v;
				},
				$current_value
			);

			if ( $needs_saving ) {
				return array_unique( $updated_array );
			}
		} elseif ( in_array( $current_value, $old_values, true ) ) {
			$needs_saving = true;
			return $new_value;
		}

		return $current_value;
	}

	/**
	 * Updates email campaign subscriber options that reference the old tag name.
	 *
	 * The options are stored as JSON in the 'noptin_subscriber_options' post meta.
	 *
	 * @param string $field The field merge tag.
	 * @param string $old_name The old tag name.
	 * @param string $new_name The new tag name.
	 */
	private static function update_email_options_field( $field, $old_name, $new_name ) {
		global $wpdb;

		$like = '%' . $wpdb->esc_like( $field ) . '%';

		$email_ids = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT post_id
				 FROM {$wpdb->postmeta}
				 WHERE meta_key = 'campaign_data'
				 AND meta_value LIKE %s",
				$like
			)
		);

		foreach ( $email_ids as $email_id ) {
			$email = noptin_get_email_campaign_object( $email_id->post_id );

			if ( ! $email->exists() ) {
				continue;
			}

			$options = $email->options['noptin_subscriber_options'];

			if ( is_array( $options ) && isset( $options[ $field ] ) ) {
				$needs_saving      = false;
				$options[ $field ] = self::update_value( $options[ $field ], (array) $old_name, $new_name, $needs_saving );

				if ( $needs_saving ) {
					$email->options['noptin_subscriber_options'] = $options;
					$email->save();
				}
			}
		}
	}
}
