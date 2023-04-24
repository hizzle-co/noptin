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
		return Collection::instance( $this->collection );
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

		// Retrieve from cache.
        if ( ! empty( $this->schema ) ) {
            return $this->schema;
        }

		$string = $this->name . ' ' . strtoupper( $this->type );

		if ( $this->length ) {
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
			$string .= ' DEFAULT ' . ( is_string( $default  ) ? '\'' . esc_sql( $default  ) . '\'' : esc_sql( $default  ) );
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
		if ( $this->nullable || null !== $this->default ) {

			if ( is_array( $schema['type'] ) ) {
				$schema['type'][] = 'null';
			} else {
				$schema['type'] = array( $schema['type'], 'null' );
			}
		} else {
			$schema['required'] = true;
		}

		// Enum.
		if ( ! empty( $this->enum ) ) {
			$schema['enum'] = is_string( $this->enum ) ? call_user_func( $this->enum ) : $this->enum;
		}

		// Sanitize callback.
		if ( $this->sanitize_callback ) {
			$schema['arg_options'] = array(
				'sanitize_callback' => $this->sanitize_callback,
			);
		}

		// Default value.
		if ( null !== $this->default ) {
			$schema['default'] = $this->default;
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

		// Retrieve from cache.
        if ( ! empty( $this->query_schema ) ) {
            return $this->query_schema;
        }

		$rest_schema  = $this->get_rest_schema();
		$query_schema = array();

		$query_schema[ $this->name ] = array(
			'description'       => sprintf(
				// translators: Placeholder %s is the property name.
				__( 'Limit response to resources where %s has the provided value.', 'hizzle-store' ),
				$this->name
			),
			'type'              => array_merge( array( 'array' ), (array) $rest_schema['type'] ),
			'items'             => array(
				'type' => $rest_schema['type'],
			),
			'validate_callback' => 'rest_validate_request_arg',
		);

		if ( isset( $rest_schema['enum'] ) ) {
			$query_schema[ $this->name ]['default'] = 'any';
			$query_schema[ $this->name ]['enum']    = array_merge( array( 'any' ), $rest_schema['enum'] );
		}

		if ( isset( $rest_schema['format'] ) ) {
			$query_schema[ $this->name ]['format'] = $rest_schema['format'];
		}

		// Dates.
		if ( $this->is_date() ) {

			$query_schema[ "{$this->name}_before" ] = array(
				'description'       => sprintf(
					// translators: Placeholder %s is the property name.
					__( 'Limit response to resources where %s is before a given ISO8601 compliant date.', 'hizzle-store' ),
					$this->name
				),
				'type'              => 'string',
				'format'            => 'date-time',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$query_schema[ "{$this->name}_after" ] = array(
				'description'       => sprintf(
					// translators: Placeholder %s is the property name.
					__( 'Limit response to resources where %s is after a given ISO8601 compliant date.', 'hizzle-store' ),
					$this->name
				),
				'type'              => 'string',
				'format'            => 'date-time',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$query_schema[ "{$this->name}_query" ] = array(
				'description'       => __( 'An array to pass to WP_Date_Query.', 'hizzle-store' ),
				'type'              => 'object',
				'validate_callback' => 'rest_validate_request_arg',
			);

		}

		// Numbers & Floats.
		if ( $this->is_float() ) {

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

		if ( $this->is_numeric() ) {
			return '%d';
		}

		if ( $this->is_float() ) {
			return '%f';
		}

		return '%s';
	}

}
