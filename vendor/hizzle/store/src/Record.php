<?php

namespace Hizzle\Store;

/**
 * Store API: Handles CRUD operations on a single object.
 *
 * @since   1.0.0
 * @package Hizzle\Store
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles CRUD operations on a single object.
 *
 * @since 1.0.0
 */
class Record {

	/**
	 * ID for this object.
	 *
	 * @since 1.0.0
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Returns an ID to create the record with.
	 */
	public $create_with_id = 0;

	/**
	 * Core data for this object. Name value pairs (name + default value).
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $data = array();

	/**
	 * Core data changes for this object.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $changes = array();

	/**
	 * Set to _data on construct so we can track and reset data if needed.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $default_data = array();

	/**
	 * This is false until the object is read from the DB.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	protected $object_read = false;

	/**
	 * The collection.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $collection_name = '';

	/**
	 * This is the name of this object type, including the prefix. Used for hooks, etc.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $object_type;

	/**
	 * Default constructor.
	 *
	 * This class is not meant to be init directly.
	 * @see Collection::get()
	 * @param int|Record|array $record ID to load from the DB (optional) or already queried data.
	 * @throws Store_Exception Throws exception if ID is set & invalid.
	 */
	public function __construct( $record = 0, $args = array() ) {

		// Init class properties.
		foreach ( $args as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->$key = $value;
			}
		}

		// Set the default data.
		$this->default_data = $this->data;

		// If this is a record instance, retrieve the ID.
		if ( ! empty( $record ) && is_callable( array( $record, 'get_id' ) ) ) {
			$record = call_user_func( array( $record, 'get_id' ) );
		}

		// If this is a post object, fetch the ID.
		if ( $record instanceof \WP_Post ) {
			$this->set_id( absint( $record->ID ) );
		}

		// If we have an array of data, check id.
		if ( is_array( $record ) && ! empty( $record['id'] ) ) {
			$record = $record['id'];
		}

		// Read the record from the DB.
		if ( ! empty( $record ) && is_numeric( $record ) ) {
			$this->set_id( absint( $record ) );
			Collection::instance( $this->collection_name )->read( $this );
		}

		$this->set_object_read( true );
	}

	/**
	 * Only store the object ID to avoid serializing the data object instance.
	 *
	 * @return array
	 */
	public function __sleep() {
		return array( 'id' );
	}

	/**
	 * Re-run the constructor with the object ID.
	 *
	 * If the object no longer exists, remove the ID.
	 */
	public function __wakeup() {
		try {
			$this->__construct( absint( $this->id ) );
		} catch ( Store_Exception $e ) {
			$this->set_id( 0 );
			$this->set_object_read( true );
		}
	}

	/**
	 * Change data to JSON format.
	 *
	 * @since  1.0.0
	 * @return string Data in JSON format.
	 */
	public function __toString() {
		return wp_json_encode( $this->get_data() );
	}

	/**
	 * Set the object ID.
	 *
	 * @since 1.0.0
	 * @param int $id Object ID.
	 */
	public function set_id( $id ) {
		$this->id = absint( $id );
	}

	/**
	 * Returns the object ID.
	 *
	 * @since 1.0.0
	 * @return int
	 */
	public function get_id() {
		return absint( $this->id );
	}

	/**
	 * Check if the object exists in the DB.
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function exists() {
		return ! empty( $this->id );
	}

	/**
	 * Set object read property.
	 *
	 * @since 1.0.0
	 * @param boolean $read Should read?.
	 */
	public function set_object_read( $read = true ) {
		$this->object_read = (bool) $read;
	}

	/**
	 * Get object read property.
	 *
	 * @since  1.0.0
	 * @return boolean
	 */
	public function get_object_read() {
		return (bool) $this->object_read;
	}

	/**
	 * Delete an object, set the ID to 0, and return result.
	 *
	 * @since  1.0.0
	 * @param  bool $force_delete Should the data be deleted permanently.
	 * @return bool|\WP_Error result
	 */
	public function delete( $force_delete = false ) {

		try {

			$collection = Collection::instance( $this->collection_name );
			$collection->delete( $this, $force_delete );
			return true;

		} catch ( Store_Exception $e ) {
			return new \WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		}
	}

	/**
	 * Save should create or update based on object existence.
	 *
	 * @since  1.0.0
	 * @return int|\WP_Error
	 */
	public function save() {

		do_action( $this->object_type . '_before_save', $this );

		try {

			$collection = Collection::instance( $this->collection_name );

			if ( $this->exists() ) {
				call_user_func_array( array( $collection, 'update' ), array( &$this ) );
			} else {
				call_user_func_array( array( $collection, 'create' ), array( &$this ) );
			}
		} catch ( Store_Exception $e ) {
			return new \WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		}

		do_action( $this->object_type . '_after_save', $this );

		return $this->get_id();
	}

	/**
	 * Returns all data for this object.
	 *
	 * @since  2.6.0
	 * @return array
	 */
	public function get_data() {
		$data = array( 'id' => $this->get_id() );

		foreach ( array_keys( $this->data ) as $key ) {

			if ( method_exists( $this, "get_{$key}" ) ) {
				$data[ $key ] = $this->{"get_{$key}"}();
			} else {
				$data[ $key ] = $this->get_prop( $key );
			}
		}

		return $data;
	}

	/**
	 * Set all props to default values.
	 *
	 * @since 1.0.0
	 */
	public function set_defaults() {
		$this->data    = $this->default_data;
		$this->changes = array();
		$this->set_object_read( false );
	}

	/**
	 * Set a collection of props in one go, collect any errors, and return the result.
	 * Only sets using public methods.
	 *
	 * @since  1.0.0
	 *
	 * @param array  $props Key value pairs to set. Key is the prop and should map to a setter function name.
	 * @param bool $skip_null Skip null values.
	 *
	 * @return bool|WP_Error
	 */
	public function set_props( $props, $skip_null = false ) {
		$errors = false;

		foreach ( $props as $prop => $value ) {
			try {
				/**
				 * Checks if the prop being set is allowed.
				 */
				if ( in_array( $prop, array( 'prop', 'date_prop' ), true ) || ( $skip_null && null === $value ) ) {
					continue;
				}
				$setter = "set_$prop";

				if ( is_callable( array( $this, $setter ) ) ) {
					$this->{$setter}( $value );
				}
			} catch ( Store_Exception $e ) {
				if ( ! $errors ) {
					$errors = new \WP_Error();
				}
				$errors->add( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
			}
		}

		return $errors && count( $errors->get_error_codes() ) ? $errors : true;
	}

	/**
	 * Return data changes only.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_changes() {
		return $this->changes;
	}

	/**
	 * Merge changes with data and clear.
	 *
	 * @since 1.0.0
	 */
	public function apply_changes() {
		$this->data    = array_replace_recursive( $this->data, $this->changes ); // @codingStandardsIgnoreLine
		$this->changes = array();
	}

	/**
	 * Gets a property.
	 *
	 * Gets the value from either current pending changes, or the data itself.
	 * Context controls what happens to the value before it's returned.
	 *
	 * @since  1.0.0
	 * @param  string $prop Name of prop to get.
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return mixed
	 */
	public function get_prop( $prop, $context = 'view' ) {
		$value = null;

		if ( array_key_exists( $prop, $this->data ) ) {
			$value = array_key_exists( $prop, $this->changes ) ? $this->changes[ $prop ] : $this->data[ $prop ];

			if ( 'view' === $context ) {
				$value = apply_filters( $this->object_type . '_get_' . $prop, $value, $this );
			}
		}

		return $value;
	}

	/**
	 * Sets a prop for a setter method.
	 *
	 * This stores changes in a special array so we can track what needs saving
	 * the the DB later.
	 *
	 * @since 1.0.0
	 * @param string $prop Name of prop to set.
	 * @param mixed  $value Value of the prop.
	 */
	protected function set_prop( $prop, $value ) {
		if ( array_key_exists( $prop, $this->data ) ) {
			if ( true === $this->object_read ) {
				if ( $value !== $this->data[ $prop ] || array_key_exists( $prop, $this->changes ) ) {
					$this->changes[ $prop ] = $value;
				}
			} else {
				$this->data[ $prop ] = $value;
			}
		}
	}

	/**
	 * Sets a prop.
	 *
	 * @since 1.0.0
	 * @param string $prop Name of prop to set.
	 * @param mixed  $value Value of the prop.
	 */
	public function set( $prop, $value ) {

		$setter = "set_$prop";

		if ( is_callable( array( $this, $setter ) ) ) {
			return $this->{$setter}( $value );
		}

		return $this->set_prop( $prop, $value );
	}

	/**
	 * Sets a date prop whilst handling formatting and datetime objects.
	 *
	 * @since 1.0.0
	 * @param string         $prop Name of prop to set.
	 * @param string|integer $value Value of the prop.
	 */
	protected function set_date_prop( $prop, $value ) {
		try {
			if ( empty( $value ) || ( is_string( $value ) && false !== strpos( $value, '0000-00-00' ) ) ) {
				$this->set_prop( $prop, null );
				return;
			}

			// Create date/time object from passed date value.
			if ( $value instanceof Date_Time ) {
				$datetime = $value;
			} elseif ( is_numeric( $value ) ) {
				// Timestamps are handled as UTC timestamps in all cases.
				$datetime = new Date_Time( "@{$value}", new \DateTimeZone( 'UTC' ) );
			} else {
				// Strings are defined in local WP timezone. Convert to UTC.
				if ( 1 === preg_match( '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(Z|((-|\+)\d{2}:\d{2}))$/', $value, $date_bits ) ) {
					$offset    = ! empty( $date_bits[7] ) ? iso8601_timezone_to_offset( $date_bits[7] ) : wp_timezone()->getOffset( new \DateTime( 'now' ) );
					$timestamp = gmmktime( $date_bits[4], $date_bits[5], $date_bits[6], $date_bits[2], $date_bits[3], $date_bits[1] ) - $offset;
					$datetime  = new Date_Time( "@{$timestamp}", new \DateTimeZone( 'UTC' ) );
				} else {
					$datetime = new Date_Time( $value, wp_timezone() );
				}
			}

			// Set local timezone or offset.
			$datetime->setTimezone( wp_timezone() );

			$this->set_prop( $prop, $datetime );
		} catch ( \Exception $e ) {} // @codingStandardsIgnoreLine.
	}

	/**
	 * Sets the meta data for an object.
	 *
	 * @since 1.0.0
	 * @param array $metadata Array of meta data.
	 */
	public function set_metadata( $metadata ) {

		// Maybe decode JSON.
		if ( is_string( $metadata ) ) {
			$metadata = json_decode( $metadata, true );
		}

		// Ensure we have an array.
		if ( ! is_array( $metadata ) ) {
			$metadata = array();
		}

		// Remove unset meta keys.
		$prepared = array();

		foreach ( $metadata as $key => $value ) {
			if ( null !== $value && '' !== $value ) {
				$prepared[ $key ] = $value;
			}
		}

		// Update the meta.
		$this->set_prop( 'metadata', $prepared );
	}

	/**
	 * Adds / Updates meta data for an object.
	 *
	 * @since 1.0.0
	 * @param string $meta_key meta key.
	 * @param mixed $value meta value.
	 */
	public function update_meta( $meta_key, $value ) {
		$metadata = $this->get_metadata();

		if ( null === $value ) {
			unset( $metadata[ $meta_key ] );
		} else {
			$metadata[ $meta_key ] = $value;
		}

		$this->set_metadata( $metadata );
	}

	/**
	 * Removes meta data for an object.
	 *
	 * @since 1.0.0
	 * @param string $meta_key meta key.
	 */
	public function remove_meta( $meta_key ) {
		$metadata = $this->get_metadata();
		unset( $metadata[ $meta_key ] );
		$this->set_metadata( $metadata );
	}

	/**
	 * Retrieves an array of meta data for an object.
	 *
	 * @since 1.0.0
	 * @param string $context Context to retrieve meta data for.
	 * @return array|string
	 */
	public function get_metadata( $context = 'view' ) {
		$metadata = $this->get_prop( 'metadata', 'edit' );
		$metadata = is_array( $metadata ) ? $metadata : array();
		return 'edit' === $context ? wp_json_encode( $metadata ) : $metadata;
	}

	/**
	 * Retrieves the value of a meta data property.
	 *
	 * @since 1.0.0
	 * @param string $meta_key meta key.
	 * @param mixed $default default value.
	 * @return mixed
	 */
	public function get_meta( $meta_key, $default = null ) {
		$metadata = $this->get_metadata();

		if ( isset( $metadata[ $meta_key ] ) ) {
			return $metadata[ $meta_key ];
		}

		return $default;
	}

}
