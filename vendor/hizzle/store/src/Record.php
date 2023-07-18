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
	 * Stores the enum transition information so that we can fire appropriate actions when saving records.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $enum_transition = array();

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
			$this->get_collection()->read( $this );
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
	 * Retrieves the collection.
	 *
	 * @since 1.0.0
	 * @return Collection
	 */
	public function get_collection() {
		return Collection::instance( $this->collection_name );
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
	 * Checks if the property is a meta key.
	 *
	 * @since  1.0.0
	 * @param  string $prop Property name.
	 * @return bool
	 */
	public function is_meta_key( $prop ) {
		$prop = $this->has_prop( $prop );
		return $prop && $prop->is_meta_key;
	}

	/**
	 * Checks if the property is a dynamic property.
	 *
	 * @since  1.0.0
	 * @param  string $prop Property name.
	 * @return bool
	 */
	public function is_dynamic_prop( $prop ) {
		$prop = $this->has_prop( $prop );
		return $prop && $prop->is_dynamic;
	}

	/**
	 * Delete an object, set the ID to 0, and return result.
	 *
	 * @since  1.0.0
	 * @param  bool $force_delete Should the data be deleted permanently.
	 * @return bool|\WP_Error result
	 */
	public function delete( $force_delete = false ) {

		if ( ! $this->exists() ) {
			return false;
		}

		try {

			$this->get_collection()->delete( $this, $force_delete );
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

			// Save the data.
			if ( $this->exists() ) {
				call_user_func_array( array( $this->get_collection(), 'update' ), array( &$this ) );
			} else {
				call_user_func_array( array( $this->get_collection(), 'create' ), array( &$this ) );
			}

			// Fire enum transitions.
			$this->enum_transitions();
		} catch ( Store_Exception $e ) {
			return new \WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		}

		do_action( $this->object_type . '_after_save', $this );

		return $this->get_id();
	}

	/**
	 * Handle the enum transitions.
	 *
	 * @since  1.0.0
	 */
	protected function enum_transitions() {
		$transitions = $this->enum_transition;

		// Reset status transition variable.
		$this->enum_transition = array();

		if ( is_array( $transitions ) ) {
			foreach ( array_filter( $transitions ) as $prop => $transition ) {
				try {
					$this->enum_transition( $prop, $transition );
				} catch ( \Exception $e ) {
					_doing_it_wrong( __CLASS__ . '::' . __METHOD__, esc_html( $e->getMessage() ), '1.0.0' );
				}
			}
		}
	}

	/**
	 * Handle the enum transition.
	 *
	 * @since 1.0.0
	 * @param string $prop
	 * @param array $transition
	 */
	protected function enum_transition( $prop, $transition ) {

		$from = is_bool( $transition['from'] ) ? ( $transition['from'] ? 'yes' : 'no' ) : $transition['from'];
		$to   = is_bool( $transition['to'] ) ? ( $transition['to'] ? 'yes' : 'no' ) : $transition['to'];

		// Props that accept multiple values.
		if ( is_array( $from ) || is_array( $to ) ) {
			$from    = is_array( $from ) ? $from : array();
			$to      = is_array( $to ) ? $to : array();
			$added   = array_diff( $to, $from );
			$removed = array_diff( $from, $to );

			// Fire hooks for the added values.
			foreach ( $added as $value ) {
				do_action( "{$this->object_type}_added_to_{$prop}", $this, $value );
			}

			// Fire hooks for the removed values.
			foreach ( $removed as $value ) {
				do_action( "{$this->object_type}_removed_from_{$prop}", $this, $value );
			}

			// Fire hook if the prop is changed.
			if ( ! empty( $added ) || ! empty( $removed ) ) {
				do_action( "{$this->object_type}_{$prop}_changed", $this, $from, $to );
			}

			return;
		}

		// Fire a hook for the enum change.
		do_action( "{$this->object_type}_{$prop}_set_to_{$to}", $this, $from );

		// Fire another hook.
		if ( '' !== $transition['from'] && is_string( $transition['from'] ) ) {
			do_action( "{$this->object_type}_{$prop}_changed_{$from}_to_{$to}", $this );
			do_action( "{$this->object_type}_{$prop}_changed", $this, $from, $to );
		}
	}

	/**
	 * Returns all data for this object.
	 *
	 * Only returns known props, ignoring unknown ones.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function get_data( $context = 'view' ) {
		$data = array( 'id' => $this->get_id() );

		foreach ( $this->get_collection()->get_props() as $prop ) {
			$data[ $prop->name ] = $this->get( $prop, $context );
		}

		return $data;
	}

	/**
	 * Set a collection of props in one go, collect any errors, and return the result.
	 * Only sets known props, ignoring unknown ones.
	 *
	 * @since  1.0.0
	 *
	 * @param array  $props Key value pairs to set. Key is the prop and should map to a setter function name.
	 * @param bool $skip_null Skip null values.
	 *
	 * @return bool
	 */
	public function set_props( $props, $skip_null = false ) {

		foreach ( $props as $prop => $value ) {
			// Checks if the prop being set is allowed.
			if ( ! in_array( $prop, array( 'prop', 'date_prop' ), true ) && ! ( $skip_null && null === $value ) ) {
				$this->set( $prop, $value );
			}
		}

		return true;
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
	 * Checks if we have a given property.
	 *
	 * @since 1.0.0
	 * @param string $key Property key.
	 * @return Prop|null
	 */
	public function has_prop( $key ) {

		if ( $key instanceof Prop ) {
			return $key;
		}

		try {
			return $this->get_collection()->get_prop( $key );
		} catch ( Store_Exception $e ) {
			return null;
		}
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
	protected function get_prop( $prop, $context = 'view' ) {
		$value = null;
		$prop  = $this->has_prop( $prop );

		if ( $prop ) {
			$prop  = $prop->name;
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
		$object = $this->has_prop( $prop );

		// Abort if the prop is not valid.
		if ( ! $object || $object->is_dynamic ) {
			return;
		}

		$prop = $object->name;

		// Sanitize the value.
		$value = $object->sanitize( $value );

		// Limit length.
		if ( is_string( $value ) && is_int( $object->length ) ) {
			$value = $this->limit_length( $value, $object->length );
		}

		// Set the value.
		if ( true === $this->object_read ) {
			if ( $value !== $this->data[ $prop ] || array_key_exists( $prop, $this->changes ) ) {
				$this->changes[ $prop ] = $value;
			}

			// If this is an enum or boolean, record the change.
			if ( $object->is_boolean() || $object->is_tokens || ! empty( $object->enum ) ) {

				if ( ! $this->exists() || $value !== $this->data[ $prop ] ) {
					$this->enum_transition[ $prop ] = array(
						'from' => $this->data[ $prop ],
						'to'   => $value,
					);
				}
			}
		} else {
			$this->data[ $prop ] = $value;
		}
	}

	/**
	 * Sets a prop.
	 *
	 * @since 1.0.0
	 * @param string|Prop $prop Name of prop to get.
	 * @param mixed  $value Value of the prop.
	 */
	public function set( $prop, $value ) {

		// Check if prop ends
		$is_adding   = false;
		$is_removing = false;
		if ( is_string( $prop ) && $this->get_object_read() ) {

			// Check if prop ends with add.
			if ( '::add' === substr( $prop, -5 ) ) {
				$is_adding = true;
				$prop      = substr( $prop, 0, -5 );
			} elseif ( '::remove' === substr( $prop, -8 ) ) {
				$is_removing = true;
				$prop        = substr( $prop, 0, -8 );
			}
		}

		$key = is_object( $prop ) ? $prop->name : $prop;

		if ( empty( $key ) || ! is_string( $key ) ) {
			return;
		}

		$method = "set_$key";

		// Check if we have a setter method.
		if ( method_exists( $this, $method ) ) {
			return $this->{$method}( $value, $is_adding, $is_removing );
		}

		$prop = is_string( $prop ) ? $this->has_prop( $prop ) : $prop;

		if ( empty( $prop ) ) {
			return false;
		}

		if ( $prop->is_meta_key && $prop->is_meta_key_multiple ) {
			$value    = noptin_parse_list( $value, true );
			$existing = $this->get( $prop );
			$existing = is_array( $existing ) ? $existing : array();

			if ( $is_adding ) {
				$value = array_unique( array_merge( $existing, $value ) );
			} elseif ( $is_removing ) {
				$value = array_unique( array_diff( $existing, $value ) );
			}
		}

		// Set directly to the data if we have it.
		if ( array_key_exists( $key, $this->data ) ) {
			return $prop->is_date() ? $this->set_date_prop( $prop, $value ) : $this->set_prop( $prop, $value );
		}

		return false;
	}

	/**
	 * Fetches the value of a given prop.
	 *
	 * @since  1.0.0
	 * @param  string|Prop $prop Name of prop to get.
	 * @param  string      $context What the value is for. Valid values are view and edit.
	 * @return mixed
	 */
	public function get( $prop, $context = 'view' ) {

		if ( is_object( $prop ) ) {
			$prop = $prop->name;
		}

		if ( empty( $prop ) || ! is_string( $prop ) ) {
			return null;
		}

		$method = "get_$prop";

		// Check if we have a getter method.
		if ( method_exists( $this, $method ) ) {
			return $this->{$method}( $context );
		}

		// Force "view" to allow filtering third party props.
		return $this->get_prop( $prop, 'view' );
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
		$metadata = maybe_unserialize( $metadata );
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

	/**
	 * Limit length of a string.
	 *
	 * @param  string  $string string to limit.
	 * @param  integer $limit Limit size in characters.
	 * @return string
	 */
	protected function limit_length( $string, $limit ) {

		if ( empty( $limit ) || empty( $string ) || ! is_string( $string ) ) {
			return $string;
		}

		$str_limit = $limit - 3;

		if ( function_exists( 'mb_strimwidth' ) ) {
			if ( mb_strlen( $string, 'UTF-8' ) > $limit ) {
				$string = mb_strimwidth( $string, 0, $str_limit ) . '...';
			}
		} else {
			if ( strlen( $string ) > $limit ) {
				$string = substr( $string, 0, $str_limit ) . '...';
			}
		}
		return $string;

	}

	/**
	 * Displays a given property.
	 *
	 * @param string $key The property key.
	 */
	public function display_prop( $key ) {

		// Check if we have a special display method for this property.
		$method = 'the_' . $key;

		if ( method_exists( $this, $method ) ) {
			return $this->$method();
		}

		// Retrieve the raw value.
		$value = $this->get( $key );
		$prop  = $this->has_prop( $key );

		// Filter value.
		$value = apply_filters( $this->object_type . '_display_' . $key, $value, $this );

		// In case we have no value.
		if ( is_null( $value ) || '' === $value || array() === $value ) {
			return '&ndash;';
		}

		// Booleans.
		if ( is_bool( $value ) ) {
			return sprintf(
				'<span class="dashicons dashicons-%s" style="color:%s;"></span>',
				$value ? 'yes' : 'no',
				$value ? 'green' : 'red'
			);
		}

		// Dates.
		if ( is_a( $value, '\Hizzle\Store\Date_Time' ) ) {
			return $this->display_date_value( $value, empty( $prop ) ? 'datetime' : $prop->type );
		}

		// Arrays.
		if ( ! is_scalar( $value ) ) {
			$value = wp_json_encode( $value );
		}

		return wp_kses_post( (string) $value );

	}

	/**
	 * Displays a date property.
	 *
	 * @param \Hizzle\Store\Date_Time $date date.
	 * @param string $type type.
	 */
	public function display_date_value( $date, $type ) {

		if ( 'date' === $type ) {
			return esc_html( $date->context( 'view_day' ) );
		}

		// If less than 24 hours, display the human readable time.
		$time_diff = time() - $date->getTimestamp();

		if ( $time_diff < WEEK_IN_SECONDS && $time_diff > 0 ) {
			return sprintf(
				'<abbr title="%1$s">%2$s</abbr>',
				esc_attr( $date->__toString() ),
				esc_html(
					sprintf(
						/* translators: %s: human-readable time difference */
						__( '%s ago', 'hizzle-store' ),
						human_time_diff( $date->getTimestamp(), time() )
					)
				)
			);
		}

		if ( $time_diff < 0 && $time_diff < - WEEK_IN_SECONDS ) {
			return sprintf(
				'<abbr title="%1$s">%2$s</abbr>',
				esc_attr( $date->__toString() ),
				esc_html(
					sprintf(
						/* translators: %s: human-readable time difference */
						__( 'in %s', 'hizzle-store' ),
						human_time_diff( $date->getTimestamp(), time() )
					)
				)
			);
		}

		return '<abbr title="' . esc_attr( $date->__toString() ) . '">' . esc_html( $date->context( 'view_day' ) ) . '</abbr>';
	}

	/**
	 * Returns the record's overview.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_overview() {
		return array();
	}
}
