<?php
/** Registered settings REST API. */
namespace Hizzle\WordPress;

defined( 'ABSPATH' ) || exit;

class SettingsAPI {

	private static $options = array();

	/**
	 * Register a WordPress option with the settings REST API.
	 *
	 * Call this on every request, including REST requests, rather than only when
	 * enqueueing the settings page. Updates use update_option() and the option's
	 * registered WordPress sanitization callbacks.
	 *
	 * @param string $option WordPress option name. Must match the option_name passed
	 *                       to SettingsProvider and contain only letters, numbers,
	 *                       underscores, or hyphens.
	 * @param array  $args {
	 *     Registration arguments.
	 *
	 *     @type callable $fields              Required. Called without arguments to
	 *                                         return the current grouped settings
	 *                                         field tree as an array, or WP_Error.
	 *                                         Build fields from current saved values
	 *                                         so labels and action URLs can refresh.
	 *     @type callable $values              Optional. Called without arguments to
	 *                                         return saved values prepared for the
	 *                                         editor, or WP_Error. Defaults to
	 *                                         get_option( $option, array() ). This
	 *                                         callback does not handle saving.
	 *     @type string   $capability          Capability required for all reads and
	 *                                         updates. Default 'manage_options'.
	 *                                         Ignored when permission_callback is set.
	 *     @type callable $permission_callback Optional. Receives WP_REST_Request and
	 *                                         returns bool or WP_Error to authorize
	 *                                         values reads, fields reads, and updates.
	 *                                         Replaces the capability check.
	 * }
	 * @return void
	 * @throws \InvalidArgumentException When fields is missing or is not callable.
	 */
	public static function register( $option, $args ) {
		if ( ! is_callable( $args['fields'] ?? null ) ) {
			throw new \InvalidArgumentException( 'Settings require a fields callback.' );
		}
		self::$options[ $option ] = $args;
	}

	/**
	 * Register the hizzlewp/v1 settings REST routes on rest_api_init.
	 *
	 * Provides GET /settings/{option}, GET /settings/{option}/fields, and
	 * POST/PUT /settings/{option}. All routes use permissions() for access checks.
	 *
	 * @return void
	 */
	public static function register_routes() {
		$base = '/settings/(?P<option>[a-zA-Z0-9_-]+)';
		register_rest_route(
			'hizzlewp/v1',
			$base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'get_values' ),
					'permission_callback' => array( __CLASS__, 'permissions' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( __CLASS__, 'update_values' ),
					'permission_callback' => array( __CLASS__, 'permissions' ),
				),
			)
		);
		register_rest_route(
			'hizzlewp/v1',
			$base . '/fields',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_fields' ),
				'permission_callback' => array( __CLASS__, 'permissions' ),
			)
		);
	}

	/**
	 * Authorize access to a registered option's values and field definitions.
	 *
	 * Uses the registered permission callback, or checks the configured capability
	 * (manage_options by default). Unregistered options return a 404 error.
	 *
	 * @param \WP_REST_Request $request Request containing the option route parameter.
	 * @return bool|\WP_Error Whether access is allowed, or an authorization error.
	 */
	public static function permissions( $request ) {
		$args = self::$options[ $request['option'] ] ?? null;
		if ( ! $args ) {
			return new \WP_Error( 'unknown_settings', 'These settings are not registered.', array( 'status' => 404 ) );
		}

		return isset( $args['permission_callback'] )
			? call_user_func( $args['permission_callback'], $request )
			: current_user_can( $args['capability'] ?? 'manage_options' );
	}

	/**
	 * Build the current field tree and resolve an optional field selector.
	 *
	 * hizzlewp_field selects a dot-separated path in the field tree, not a prefix
	 * in the saved option. Paths may include or omit sub_sections, settings, and
	 * children wrappers. A matching field key containing dots takes precedence
	 * over further traversal. A selected field is returned under its original key;
	 * a selected section returns its settings or child tree.
	 *
	 * @param \WP_REST_Request $request      Request containing option and optionally
	 *                                      hizzlewp_field. Without a selector, returns
	 *                                      the complete field tree.
	 * @param bool             $values_scope Whether selecting fields for reading or
	 *                                      updating values. Rejects traversal into
	 *                                      repeater children, which do not identify
	 *                                      a particular saved row. Default false.
	 * @return array|\WP_Error Selected definitions, or a callback/selector error.
	 */
	private static function selected_fields( $request, $values_scope = false ) {
		$fields = call_user_func( self::$options[ $request['option'] ]['fields'] );
		if ( is_wp_error( $fields ) ) {
			return $fields;
		}
		$path = $request->get_param( 'hizzlewp_field' );
		if ( null === $path || '' === $path ) {
			return $fields;
		}
		if ( ! is_string( $path ) || ! preg_match( '/^[a-zA-Z0-9_-]+(?:\.[a-zA-Z0-9_-]+)*$/', $path ) ) {
			return new \WP_Error( 'invalid_settings_path', 'Invalid settings field path.', array( 'status' => 400 ) );
		}
		$node  = $fields;
		$parts = explode( '.', $path );
		$key   = '';
		while ( $parts ) {
			$key       = array_shift( $parts );
			$remaining = implode( '.', array_merge( array( $key ), $parts ) );
			if ( ! is_array( $node ) ) {
				break;
			}
			if ( $values_scope && ( $node['el'] ?? '' ) === 'repeater' ) {
				return new \WP_Error( 'ambiguous_repeater_path', 'Select the repeater field to read or update its values; a child definition does not identify a saved row.', array( 'status' => 400 ) );
			}
			// Explicit wrapper names work; section.subsection.field is also accepted.
			if ( ! array_key_exists( $key, $node ) ) {
				foreach ( array( 'sub_sections', 'settings', 'children' ) as $wrapper ) {
					if ( isset( $node[ $wrapper ] ) && is_array( $node[ $wrapper ] ) && ( array_key_exists( $key, $node[ $wrapper ] ) || array_key_exists( $remaining, $node[ $wrapper ] ) ) ) {
						$node = $node[ $wrapper ];
						break;
					}
				}
			}
			// Some field keys themselves contain dots.
			$remaining = implode( '.', array_merge( array( $key ), $parts ) );
			if ( array_key_exists( $remaining, $node ) ) {
				$key   = $remaining;
				$node  = $node[ $key ];
				$parts = array();
			} elseif ( array_key_exists( $key, $node ) ) {
				$node = $node[ $key ];
			} else {
				return new \WP_Error( 'unknown_settings_field', 'The settings field was not found.', array( 'status' => 404 ) );
			}
		}
		if ( ! is_array( $node ) ) {
			return new \WP_Error( 'unknown_settings_field', 'The settings field was not found.', array( 'status' => 404 ) );
		}
		if ( isset( $node['el'] ) || isset( $node['type'] ) ) {
			return array( $key => $node );
		}
		return $node['settings'] ?? $node;
	}

	/**
	 * Handle GET /settings/{option}/fields using freshly generated definitions.
	 *
	 * @param \WP_REST_Request $request Request containing option and an optional
	 *                                 hizzlewp_field selector into the field tree.
	 * @return \WP_REST_Response|\WP_Error Field definitions as an object, or an error.
	 */
	public static function get_fields( $request ) {
		$fields = self::selected_fields( $request );
		return is_wp_error( $fields ) ? $fields : rest_ensure_response( (object) $fields );
	}

	/**
	 * Read editor values for an already registered option.
	 *
	 * Calls the optional values callback without arguments, otherwise reads the
	 * WordPress option with an empty array as the default.
	 *
	 * @param string $option Registered WordPress option name.
	 * @return mixed Saved values, or WP_Error returned by the values callback.
	 */
	private static function values( $option ) {
		$args = self::$options[ $option ];
		return isset( $args['values'] ) ? call_user_func( $args['values'] ) : get_option( $option, array() );
	}

	/**
	 * Check whether a request supplies a nonempty field selector.
	 *
	 * This checks presence only; selected_fields() validates the selector.
	 *
	 * @param \WP_REST_Request $request Request with an optional hizzlewp_field parameter.
	 * @return bool Whether the selector is neither null nor an empty string.
	 */
	private static function has_selector( $request ) {
		$path = $request->get_param( 'hizzlewp_field' );
		return null !== $path && '' !== $path;
	}

	/**
	 * Collect unique option-value paths represented by a field tree.
	 *
	 * Traverses sections and layout groups without adding their keys to the value
	 * paths. Excludes display-only fields and treats repeaters as whole values.
	 * Field keys use dots to identify nested option values.
	 *
	 * @param array $fields   Settings definitions or a selected subtree.
	 * @param bool  $writable Whether to also exclude disabled and readonly fields.
	 *                        Default false.
	 * @return string[] Unique field paths; array keys are not necessarily sequential.
	 */
	private static function field_paths( $fields, $writable = false ) {
		$paths = array();
		foreach ( $fields as $key => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			if ( isset( $field['el'] ) || isset( $field['type'] ) ) {
				$type = $field['el'] ?? $field['type'];
				if ( in_array( $type, array( 'integration_panel', 'settings_group', 'horizontal' ), true ) ) {
					$paths = array_merge( $paths, self::field_paths( $field['settings'] ?? $field['children'] ?? array(), $writable ) );
				} elseif ( ! in_array( $type, array( 'button', 'paragraph', 'hero', 'heading', 'html' ), true ) && ( ! $writable || ( empty( $field['disabled'] ) && empty( $field['readonly'] ) ) ) ) {
					$paths[] = (string) $key;
				}
			} else {
				$paths = array_merge( $paths, self::field_paths( $field['sub_sections'] ?? $field['settings'] ?? $field, $writable ) );
			}
		}
		return array_unique( $paths );
	}

	/**
	 * Read a dot-separated value path from nested arrays or objects.
	 *
	 * @param mixed  $values Values to traverse.
	 * @param string $path   Dot-separated option-value path.
	 * @param bool   $found  Output parameter. Set to true when the path exists,
	 *                       including when its value is null; otherwise false.
	 * @return mixed Value at the path, or null if the path does not exist.
	 */
	private static function get_path( $values, $path, &$found ) {
		foreach ( explode( '.', $path ) as $key ) {
			if ( is_object( $values ) ) {
				$values = (array) $values;
			}
			if ( ! is_array( $values ) || ! array_key_exists( $key, $values ) ) {
				$found = false;
				return null;
			}
			$values = $values[ $key ];
		}
		$found = true;
		return $values;
	}

	/**
	 * Write a value to a dot-separated path in an array, modifying it in place.
	 *
	 * Missing or non-array intermediate values are replaced with arrays.
	 *
	 * @param array  $values Destination array, passed by reference.
	 * @param string $path   Dot-separated option-value path.
	 * @param mixed  $value  Value to assign.
	 * @return void
	 */
	private static function set_path( &$values, $path, $value ) {
		$node  =& $values;
		$parts = explode( '.', $path );
		$key   = array_pop( $parts );
		foreach ( $parts as $part ) {
			if ( ! isset( $node[ $part ] ) || ! is_array( $node[ $part ] ) ) {
				$node[ $part ] = array();
			}
			$node =& $node[ $part ];
		}
		$node[ $key ] = $value;
	}

	/**
	 * Handle GET /settings/{option}, optionally restricting values to selected fields.
	 *
	 * A field selector determines which option-value paths to include. Returned
	 * values retain their option structure; missing paths are omitted.
	 *
	 * @param \WP_REST_Request $request Request containing option and an optional
	 *                                 hizzlewp_field selector into the field tree.
	 * @return \WP_REST_Response|\WP_Error Saved values as an object, or a read/selector error.
	 */
	public static function get_values( $request ) {
		$values = self::values( $request['option'] );
		if ( is_wp_error( $values ) ) {
			return $values;
		}
		if ( self::has_selector( $request ) ) {
			$fields = self::selected_fields( $request, true );
			if ( is_wp_error( $fields ) ) {
				return $fields;
			}
			$selected = array();
			foreach ( self::field_paths( $fields ) as $path ) {
				$value = self::get_path( $values, $path, $found );
				if ( $found ) {
					self::set_path( $selected, $path, $value );
				}
			}
			$values = $selected;
		}
		return rest_ensure_response( (object) $values );
	}

	/**
	 * Merge a settings patch into saved values without modifying the input array.
	 *
	 * Associative arrays merge recursively into existing arrays or objects. Lists,
	 * including empty arrays, and scalar values replace the previous value so
	 * removed repeater rows are not retained. Omitted keys remain unchanged.
	 *
	 * @param array $old   Existing option values.
	 * @param array $patch New option values to merge.
	 * @return array Merged option values.
	 */
	private static function merge( $old, $patch ) {
		foreach ( $patch as $key => $value ) {
			$old[ $key ] = is_array( $value ) && array_values( $value ) !== $value && isset( $old[ $key ] ) && ( is_array( $old[ $key ] ) || is_object( $old[ $key ] ) )
				? self::merge( (array) $old[ $key ], $value ) : $value;
		}
		return $old;
	}

	/**
	 * Check that every submitted value belongs to an allowed writable field path.
	 *
	 * An exact allowed path accepts its entire value. Otherwise, nested arrays
	 * are checked recursively against allowed descendants. This checks which
	 * fields may be updated; WordPress option sanitizers validate their values.
	 *
	 * @param array    $patch   Submitted option values or a nested part of the patch.
	 * @param string[] $allowed Allowed dot-separated option-value paths.
	 * @param string   $prefix  Parent value path used during recursion. Default ''.
	 * @return bool Whether all submitted paths are allowed.
	 */
	private static function validate_patch( $patch, $allowed, $prefix = '' ) {
		foreach ( $patch as $key => $value ) {
			$path = $prefix ? "$prefix.$key" : (string) $key;
			if ( in_array( $path, $allowed, true ) ) {
				continue;
			}
			$descendants = array_filter(
				$allowed,
				static function ( $item ) use ( $path ) {
					return 0 === strpos( $item, $path . '.' );
				}
			);
			if ( ! $descendants || ! is_array( $value ) || ! self::validate_patch( $value, $allowed, $path ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Handle POST/PUT /settings/{option} by merging and saving a JSON object patch.
	 *
	 * With hizzlewp_field, only writable paths represented by the selected fields
	 * may be submitted. Patch keys still follow the option's value structure,
	 * rather than being relative to the field selector. Unscoped updates are not
	 * restricted to field definitions. Omitted values remain unchanged.
	 *
	 * Uses update_option() so registered sanitizers run, treats unchanged values
	 * as success, and reports database write failures. Returns freshly read values
	 * using the same selector as the request.
	 *
	 * @param \WP_REST_Request $request Request containing option, a JSON object body,
	 *                                 and an optional hizzlewp_field selector.
	 * @return \WP_REST_Response|\WP_Error Saved values, or a payload, selector, scope,
	 *                                   read, or persistence error.
	 */
	public static function update_values( $request ) {
		$patch = $request->get_json_params();
		if ( ! is_array( $patch ) || ! ( json_decode( $request->get_body() ) instanceof \stdClass ) ) {
			return new \WP_Error( 'invalid_settings', 'Send a JSON object of settings values.', array( 'status' => 400 ) );
		}
		if ( self::has_selector( $request ) ) {
			$fields = self::selected_fields( $request, true );
			if ( is_wp_error( $fields ) ) {
				return $fields;
			}
			if ( ! self::validate_patch( $patch, self::field_paths( $fields, true ) ) ) {
				return new \WP_Error( 'settings_outside_path', 'The update contains settings outside the selected field path.', array( 'status' => 400 ) );
			}
		}
		$option  = $request['option'];
		$before  = get_option( $option, array() );
		$desired = self::merge( is_array( $before ) || is_object( $before ) ? (array) $before : array(), $patch );
		// Observe the final sanitized value without applying sanitizers twice.
		$capture = static function ( $value, $name ) use ( $option, &$desired ) {
			if ( $name === $option ) {
				$desired = $value;
			} return $value;
		};
		add_filter( 'pre_update_option', $capture, PHP_INT_MAX, 2 );
		try {
			$updated = update_option( $option, $desired );
		} finally {
			remove_filter( 'pre_update_option', $capture, PHP_INT_MAX );
		}
		if ( ! $updated && maybe_serialize( $desired ) !== maybe_serialize( $before ) ) {
			return new \WP_Error( 'settings_save_failed', 'Could not save settings.', array( 'status' => 500 ) );
		}
		return self::get_values( $request );
	}
}
