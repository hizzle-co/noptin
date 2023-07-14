<?php

namespace Hizzle\Store;

/**
 * Store API: Manages a single property.
 *
 * @since   1.0.0
 * @package Hizzle\Store
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Manages a single property.
 *
 * @since 1.0.0
 */
class Prop {

	/**
	 * The collection name, e.g subscribers.
	 *
	 * @var string
	 */
	public $collection;

	/**
	 * The prop name, e.g first_name
	 *
	 * @var string
	 */
	public $name;

	/**
	 * The prop label.
	 *
	 * @var string
	 */
	public $label;

	/**
	 * The prop description.
	 *
	 * @var string
	 */
	public $description;

	/**
	 * The prop type, e.g BIGINT. Used by MYSQL.
	 *
	 * @var string
	 */
	public $type;

	/**
	 * The prop length, e.g 20. Used by MYSQL.
	 *
	 * @var int|string
	 */
	public $length = null;

	/**
	 * Whether the prop is nullable.
	 *
	 * @var bool
	 */
	public $nullable = true;

	/**
	 * The default value of the property.
	 *
	 * @var mixed
	 */
	public $default = null;

	/**
	 * Any extra information of the property. Used by MYSQL.
	 *
	 * @var string
	 */
	public $extra = null;

	/**
	 * An array of allowed property values. Pass a string to use as a callback.
	 *
	 * @var string|array
	 */
	public $enum = null;

	/**
	 * A callback to use to sanitize the property value.
	 *
	 * @var string|array
	 */
	public $sanitize_callback = null;

	/**
	 * Extra REST schema data.
	 *
	 * @var array
	 */
	public $extra_rest_schema = null;

	/**
     * The database schema.
     *
     * @var string
     */
    protected $schema;

	/**
     * The REST schema.
     *
     * @var array
     */
    protected $rest_schema;

	/**
     * The Query schema.
     *
     * @var array
     */
    protected $query_schema;

	/**
	 * Whether the prop is readonly.
	 *
	 * @var bool
	 */
	public $readonly = false;

	/**
	 * Whether the prop is saved as a token.
	 *
	 * @var bool
	 */
	public $is_tokens = false;

	/**
	 * Whether the prop is saved as a meta key.
	 *
	 * @var bool
	 */
	public $is_meta_key = false;

	/**
	 * Whether the meta key supports multiple values.
	 *
	 * @var bool
	 */
	public $is_meta_key_multiple = false;

	/**
	 * Dynamic fields are neither saved in the database nor in the meta table.
	 *
	 * @var bool
	 */
	public $is_dynamic = false;

	/**
	 * Class constructor.
	 *
	 * @param string $collection The collection's name, including the prefix.
	 * @param array $args
	 */
	public function __construct( $collection, $args = array() ) {
		$this->collection = $collection;

		foreach ( $args as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->$key = $value;
			}
		}

		if ( empty( $this->label ) ) {
			$this->label = ucfirst( str_replace( '_', ' ', $this->name ) );
		}
	}

	/**
	 * Retrieves the hook prefix.
	 *
	 * @param string $suffix Suffix to append to the hook prefix.
	 * @return string
	 */
	public function hook_prefix( $suffix = '' ) {
		return $this->collection . '_' . $this->name . '_' . $suffix;
	}

	/**
	 * Retrieves the collection.
	 *
	 * @return Collection|null
	 */
	public function get_collection() {
		try {
			return Collection::instance( $this->collection );
		} catch ( Store_Exception $e ) {
			return null;
		}
	}

	/**
	 * Checks if the corresponding collection exists.
	 *
	 * @return bool
	 */
	public function has_collection() {
		return null !== $this->get_collection();
	}

	/**
	 * Returns the property database column definition as a string.
	 *
	 * @return string E.g, orders_count BIGINT(20) UNSIGNED NOT NULL DEFAULT 0
	 */
	public function __toString() {
		return $this->get_schema();
	}

	/**
	 * Returns the property database column definition as a string.
	 *
	 * @return string
	 */
	public function get_schema() {
		global $wpdb;

		// Abort for dynamic and meta key props.
		if ( $this->is_dynamic || $this->is_meta_key ) {
			return '';
		}

		// Retrieve from cache.
        if ( ! empty( $this->schema ) ) {
            return $this->schema;
        }

		$string = $this->name . ' ' . strtoupper( $this->type );

		if ( $this->length && ! $this->is_date() ) {
			$string .= '(' . $this->length . ')';
		}

		if ( 'bigint' === strtolower( $this->type ) ) {
			$string .= ' UNSIGNED';
		}

		if ( $this->nullable ) {
			$string .= ' NULL';
		} else {
			$string .= ' NOT NULL';
		}

		$default = is_bool( $this->default ) ? (int) $this->default : $this->default;
		if ( $default || 0 === $default ) {
			$string  .= $wpdb->prepare(
				' DEFAULT %s',
				maybe_serialize( $default )
			);
		}

		if ( $this->extra ) {
			$string .= ' ' . $this->extra;
		}

		$this->schema = apply_filters( $this->hook_prefix( 'db_schema' ), $string, $this );
		return $this->schema;
	}

	/**
	 * Returns the REST schema as an array.
	 *
	 * @return array
	 */
	public function get_rest_schema() {

		// Retrieve from cache.
        if ( ! empty( $this->rest_schema ) ) {
            return $this->rest_schema;
        }

		$schema = array(
			'description' => $this->description,
			'readonly'    => 'id' === $this->name || $this->readonly,
			'context'     => array( 'view', 'edit' ),
		);

		// Value type.
		if ( 'metadata' === $this->name ) {
			$schema['type'] = array( 'object', 'array' );
		} elseif( $this->is_meta_key ) {
			$schema['type'] = $this->is_meta_key_multiple ? 'array' : 'string';
		} elseif ( $this->is_boolean() ) {
			$schema['type'] = array( 'boolean', 'int' );
		} elseif ( $this->is_numeric() ) {
			$schema['type'] = 'integer';

			if ( $this->length ) {
				$schema['maximum'] = pow( 10, intval( $this->length ) ) - 1;
			}
		} elseif ( $this->is_float() ) {
			$schema['type'] = 'number';

			if ( $this->length ) {
				$schema['maximum'] = pow( 10, intval( $this->length ) ) - 1;
			}
		} else {
			$schema['type'] = 'string';

			if ( is_numeric( $this->length ) ) {
				$schema['maxLength'] = $this->length;
			}
		}

		// Nullable.
		if ( $this->nullable || null !== $this->default || $this->is_meta_key || $this->is_dynamic ) {

			if ( is_array( $schema['type'] ) ) {
				$schema['type'][] = 'null';
			} else {
				$schema['type'] = array( $schema['type'], 'null' );
			}
		} else {
			$schema['required'] = true;
		}

		// Sanitize callback.
		if ( $this->sanitize_callback ) {
			$schema['arg_options'] = array(
				'sanitize_callback' => $this->sanitize_callback,
			);
		}

		// Default value.
		if ( null !== $this->default ) {
			$schema['default'] = $this->is_boolean() ? (bool) $this->default : $this->default;
		}

		// Extra REST schema.
		if ( $this->extra_rest_schema ) {
			$schema = array_merge( $schema, $this->extra_rest_schema );
		}

		$this->rest_schema = apply_filters( $this->hook_prefix( 'rest_schema' ), $schema, $this );
		return $this->rest_schema;
	}

	/**
	 * Returns the query schema as an array.
	 *
	 * @return array
	 */
	public function get_query_schema() {

		// Abort for dynamic props.
		if ( $this->is_dynamic ) {
			return array();
		}

		// Retrieve from cache.
        if ( ! empty( $this->query_schema ) ) {
            return $this->query_schema;
        }

		$rest_schema  = $this->get_rest_schema();
		$query_schema = array();

		// Has the value.
		$query_schema[ $this->name ] = array(
			'description'       => sprintf(
				// translators: Placeholder %s is the property name.
				__( 'Limit response to resources where %s has the provided value.', 'hizzle-store' ),
				$this->name
			),
			'type'              => array_unique( array_merge( (array) $rest_schema['type'], array( 'array' ) ) ),
			'validate_callback' => 'rest_validate_request_arg',
		);

		if ( isset( $rest_schema['format'] ) ) {
			$query_schema[ $this->name ]['format'] = $rest_schema['format'];
		}

		// Does not have the value.
		$query_schema[ "{$this->name}_not" ] = array(
			'description'       => sprintf(
				// translators: Placeholder %s is the property name.
				__( 'Limit response to resources where %s does not have the provided value.', 'hizzle-store' ),
				$this->name
			),
			'type'              => array_unique( array_merge( (array) $rest_schema['type'], array( 'array' ) ) ),
			'validate_callback' => 'rest_validate_request_arg',
		);

		if ( isset( $rest_schema['format'] ) ) {
			$query_schema[ "{$this->name}_not" ]['format'] = $rest_schema['format'];
		}

		// Dates.
		if ( $this->is_date() && ! $this->is_meta_key ) {

			$query_schema[ "{$this->name}_before" ] = array(
				'description'       => sprintf(
					// translators: Placeholder %s is the property name.
					__( 'Limit response to resources where %s is before a given strtotime compatible date.', 'hizzle-store' ),
					$this->name
				),
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$query_schema[ "{$this->name}_after" ] = array(
				'description'       => sprintf(
					// translators: Placeholder %s is the property name.
					__( 'Limit response to resources where %s is after a given strtotime compatible date.', 'hizzle-store' ),
					$this->name
				),
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$query_schema[ "{$this->name}_query" ] = array(
				'description'       => __( 'An array to pass to WP_Date_Query.', 'hizzle-store' ),
				'type'              => 'object',
				'validate_callback' => 'rest_validate_request_arg',
			);

		}

		// Numbers & Floats.
		if ( $this->is_float() && ! $this->is_meta_key ) {

			$query_schema[ "{$this->name}_min" ] = array(
				'description'       => sprintf(
					// translators: Placeholder %s is the property name.
					__( 'Limit response to resources where %s is greater than or equal to a given number.', 'hizzle-store' ),
					$this->name
				),
				'type'              => $rest_schema['type'],
				'validate_callback' => 'rest_validate_request_arg',
			);

			$query_schema[ "{$this->name}_max" ] = array(
				'description'       => sprintf(
					// translators: Placeholder %s is the property name.
					__( 'Limit response to resources where %s is less than or equal to a given number.', 'hizzle-store' ),
					$this->name
				),
				'type'              => $rest_schema['type'],
				'validate_callback' => 'rest_validate_request_arg',
			);

			if ( ! empty( $rest_schema['maximum'] ) ) {
				$query_schema[ "{$this->name}_min" ]['maximum'] = $rest_schema['maximum'];
				$query_schema[ "{$this->name}_max" ]['maximum'] = $rest_schema['maximum'];
			}
		}

		$this->query_schema = apply_filters( $this->hook_prefix( 'query_schema' ), $query_schema, $this );
		return $this->query_schema;
	}

	/**
	 * Checks if the property value is a boolean.
	 *
	 * @return bool
	 */
	public function is_boolean() {
		return 1 === $this->length && 'tinyint' === strtolower( $this->type );
	}

	/**
	 * Checks if the property value is numeric.
	 *
	 * @return bool
	 */
	public function is_numeric() {
		return in_array( strtolower( $this->type ), array( 'int', 'tinyint', 'smallint', 'mediumint', 'bigint' ), true );
	}

	/**
	 * Checks if the property value is a float.
	 *
	 * @return bool
	 */
	public function is_float() {
		return in_array( strtolower( $this->type ), array( 'float', 'double', 'decimal' ), true );
	}

	/**
	 * Checks if the property value is a date.
	 *
	 * @return bool
	 */
	public function is_date() {
		return in_array( strtolower( $this->type ), array( 'date', 'datetime', 'timestamp' ), true );
	}

	/**
	 * Retrieves the data type.
	 *
	 * @return string
	 */
	public function get_data_type() {

		if ( $this->is_numeric() || $this->is_boolean() ) {
			return '%d';
		}

		if ( $this->is_float() ) {
			return '%f';
		}

		return '%s';
	}

	/**
	 * Sanitizes the property value.
	 *
	 * @param mixed $value The value to sanitize.
	 * @return mixed
	 */
	public function sanitize( $value ) {

		// Abort if value is null.
		if ( null === $value ) {
			return $value;
		}

		// Do we have a custom callback?
		if ( ! empty( $this->sanitize_callback ) ) {
			return call_user_func( $this->sanitize_callback, $value );
		}

		// Abort if not scalar.
		if ( ! is_scalar( $value ) || null === $value ) {
			return $value;
		}

		if ( $this->is_boolean() ) {

			if ( 'yes' === $value || 'true' === $value || '1' === $value ) {
				return true;
			}

			if ( 'no' === $value || 'false' === $value || '0' === $value ) {
				return false;
			}

			return (bool) $value;
		}

		if ( $this->is_numeric() ) {
			return (int) $value;
		}

		if ( $this->is_float() ) {
			return (float) $value;
		}

		if ( $this->is_date() ) {
			return gmdate( 'Y-m-d H:i:s', strtotime( $value ) );
		}

		// Var chars.
		if ( 'varchar' === strtolower( $this->type ) ) {
			return sanitize_text_field( $value );
		}

		return sanitize_textarea_field( $value );
	}

	/**
	 * Retrieves available choices.
	 *
	 * @return array
	 */
	public function get_choices() {

		// Booleans.
		if ( $this->is_boolean() ) {
			return array(
				'yes' => __( 'Yes', 'hizzle-store' ),
				'no'  => __( 'No', 'hizzle-store' ),
			);
		}

		if ( empty( $this->enum ) ) {
			return array();
		}

		return is_callable( $this->enum ) ? call_user_func( $this->enum ) : $this->enum;
	}
}
