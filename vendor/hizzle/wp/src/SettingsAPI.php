<?php
/** Registered settings REST API. */
namespace Hizzle\WordPress;

defined( 'ABSPATH' ) || exit;

class SettingsAPI {

	private static $options = array();

	/** Register on every request, not only when enqueueing the settings page. */
	public static function register( $option, $args ) {
		if ( ! is_callable( $args['fields'] ?? null ) ) {
			throw new \InvalidArgumentException( 'Settings require a fields callback.' );
		}
		self::$options[ $option ] = $args;
	}

	public static function register_routes() {
		$base = '/settings/(?P<option>[a-zA-Z0-9_-]+)';
		register_rest_route( 'hizzlewp/v1', $base, array(
			array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'get_values' ), 'permission_callback' => array( __CLASS__, 'permissions' ) ),
			array( 'methods' => 'POST,PUT', 'callback' => array( __CLASS__, 'update_values' ), 'permission_callback' => array( __CLASS__, 'permissions' ) ),
		) );
		register_rest_route( 'hizzlewp/v1', $base . '/fields', array(
			'methods' => 'GET', 'callback' => array( __CLASS__, 'get_fields' ), 'permission_callback' => array( __CLASS__, 'permissions' ),
		) );
	}

	public static function permissions( $request ) {
		$args = self::$options[ $request['option'] ] ?? null;
		if ( ! $args ) {
			return new \WP_Error( 'unknown_settings', 'These settings are not registered.', array( 'status' => 404 ) );
		}
		return isset( $args['permission_callback'] )
			? call_user_func( $args['permission_callback'], $request )
			: current_user_can( $args['capability'] ?? 'manage_options' );
	}

	/** A selector describes the field tree; it is not an option-value prefix. */
	private static function selected_fields( $request, $values_scope = false ) {
		$fields = call_user_func( self::$options[ $request['option'] ]['fields'] );
		if ( is_wp_error( $fields ) ) return $fields;
		$path = $request->get_param( 'hizzlewp_field' );
		if ( null === $path || '' === $path ) return $fields;
		if ( ! is_string( $path ) || ! preg_match( '/^[a-zA-Z0-9_-]+(?:\.[a-zA-Z0-9_-]+)*$/', $path ) ) {
			return new \WP_Error( 'invalid_settings_path', 'Invalid settings field path.', array( 'status' => 400 ) );
		}
		$node = $fields;
		$parts = explode( '.', $path );
		$key = '';
		while ( $parts ) {
			$key = array_shift( $parts );
			$remaining = implode( '.', array_merge( array( $key ), $parts ) );
			if ( ! is_array( $node ) ) break;
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
				$key = $remaining;
				$node = $node[ $key ];
				$parts = array();
			} elseif ( array_key_exists( $key, $node ) ) {
				$node = $node[ $key ];
			} else {
				return new \WP_Error( 'unknown_settings_field', 'The settings field was not found.', array( 'status' => 404 ) );
			}
		}
		if ( ! is_array( $node ) ) return new \WP_Error( 'unknown_settings_field', 'The settings field was not found.', array( 'status' => 404 ) );
		if ( isset( $node['el'] ) || isset( $node['type'] ) ) return array( $key => $node );
		return $node['settings'] ?? $node;
	}

	public static function get_fields( $request ) {
		$fields = self::selected_fields( $request );
		return is_wp_error( $fields ) ? $fields : rest_ensure_response( (object) $fields );
	}

	private static function values( $option ) {
		$args = self::$options[ $option ];
		return isset( $args['values'] ) ? call_user_func( $args['values'] ) : get_option( $option, array() );
	}

	private static function has_selector( $request ) {
		$path = $request->get_param( 'hizzlewp_field' );
		return null !== $path && '' !== $path;
	}

	private static function field_paths( $fields, $writable = false ) {
		$paths = array();
		foreach ( $fields as $key => $field ) {
			if ( ! is_array( $field ) ) continue;
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

	private static function get_path( $values, $path, &$found ) {
		foreach ( explode( '.', $path ) as $key ) {
			if ( is_object( $values ) ) $values = (array) $values;
			if ( ! is_array( $values ) || ! array_key_exists( $key, $values ) ) { $found = false; return null; }
			$values = $values[ $key ];
		}
		$found = true;
		return $values;
	}

	private static function set_path( &$values, $path, $value ) {
		$node =& $values;
		$parts = explode( '.', $path );
		$key = array_pop( $parts );
		foreach ( $parts as $part ) {
			if ( ! isset( $node[ $part ] ) || ! is_array( $node[ $part ] ) ) $node[ $part ] = array();
			$node =& $node[ $part ];
		}
		$node[ $key ] = $value;
	}

	public static function get_values( $request ) {
		$values = self::values( $request['option'] );
		if ( is_wp_error( $values ) ) return $values;
		if ( self::has_selector( $request ) ) {
			$fields = self::selected_fields( $request, true );
			if ( is_wp_error( $fields ) ) return $fields;
			$selected = array();
			foreach ( self::field_paths( $fields ) as $path ) {
				$value = self::get_path( $values, $path, $found );
				if ( $found ) self::set_path( $selected, $path, $value );
			}
			$values = $selected;
		}
		return rest_ensure_response( (object) $values );
	}

	/** Objects merge recursively; arrays/lists replace rather than retaining removed rows. */
	private static function merge( $old, $patch ) {
		foreach ( $patch as $key => $value ) {
			$old[ $key ] = is_array( $value ) && array_values( $value ) !== $value && isset( $old[ $key ] ) && ( is_array( $old[ $key ] ) || is_object( $old[ $key ] ) )
				? self::merge( (array) $old[ $key ], $value ) : $value;
		}
		return $old;
	}

	private static function validate_patch( $patch, $allowed, $prefix = '' ) {
		foreach ( $patch as $key => $value ) {
			$path = $prefix ? "$prefix.$key" : (string) $key;
			if ( in_array( $path, $allowed, true ) ) continue;
			$descendants = array_filter( $allowed, static function ( $item ) use ( $path ) { return 0 === strpos( $item, $path . '.' ); } );
			if ( ! $descendants || ! is_array( $value ) || ! self::validate_patch( $value, $allowed, $path ) ) return false;
		}
		return true;
	}

	public static function update_values( $request ) {
		$patch = $request->get_json_params();
		if ( ! is_array( $patch ) || ! ( json_decode( $request->get_body() ) instanceof \stdClass ) ) return new \WP_Error( 'invalid_settings', 'Send a JSON object of settings values.', array( 'status' => 400 ) );
		if ( self::has_selector( $request ) ) {
			$fields = self::selected_fields( $request, true );
			if ( is_wp_error( $fields ) ) return $fields;
			if ( ! self::validate_patch( $patch, self::field_paths( $fields, true ) ) ) return new \WP_Error( 'settings_outside_path', 'The update contains settings outside the selected field path.', array( 'status' => 400 ) );
		}
		$option = $request['option'];
		$before = get_option( $option, array() );
		$desired = self::merge( is_array( $before ) || is_object( $before ) ? (array) $before : array(), $patch );
		// Observe the final sanitized value without applying sanitizers twice.
		$capture = static function ( $value, $name ) use ( $option, &$desired ) { if ( $name === $option ) $desired = $value; return $value; };
		add_filter( 'pre_update_option', $capture, PHP_INT_MAX, 2 );
		try { $updated = update_option( $option, $desired ); } finally { remove_filter( 'pre_update_option', $capture, PHP_INT_MAX ); }
		if ( ! $updated && maybe_serialize( $desired ) !== maybe_serialize( $before ) ) return new \WP_Error( 'settings_save_failed', 'Could not save settings.', array( 'status' => 500 ) );
		return self::get_values( $request );
	}
}
