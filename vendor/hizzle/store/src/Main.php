<?php

namespace Hizzle\Store;

defined( 'ABSPATH' ) || exit;

/**
 * The main store class.
 */
class Main {

	/**
	 * Cached instances keyed by store name.
	 *
	 * @var array<string, Main>
	 */
	private static $instances = array();

	/**
	 * The initialized store.
	 *
	 * @var Store|null
	 */
	public $store;

	/**
	 * @var string Store name.
	 */
	protected $store_name;

	/**
	 * @var string Default collection name.
	 */
	protected $default_collection;

	/**
	 * Initializes the main store class.
	 *
	 * @param string $store_name         The store name.
	 * @param string $default_collection The default collection name.
	 */
	protected function __construct( $store_name, $default_collection = '' ) {
		$this->store_name         = $store_name;
		$this->default_collection = $default_collection;
	}

	/**
	 * Get active instance
	 *
	 * @access public
	 * @since  1.0.0
	 * @return Main The main db instance.
	 */
	public static function instance( $store_name, $default_collection = '' ) {
		if ( ! isset( self::$instances[ $store_name ] ) ) {
			self::$instances[ $store_name ] = new static( $store_name, $default_collection );
		}

		return self::$instances[ $store_name ];
	}

	/**
	 * Initializes a store.
	 *
	 * @param array $collections A list of collections.
	 * @return Store The store instance.
	 */
	public function init_store( $collections ) {
		$this->store = Store::init( $this->store_name, $collections );

		if ( ! did_action( "{$this->store_name}_db_init" ) ) {
			do_action( "{$this->store_name}_db_init" );
		}

		return $this->store;
	}

	/**
	 * Resolves an omitted collection name.
	 *
	 * @param string $collection_name Collection name.
	 * @return string
	 */
	protected function get_collection_name( $collection_name = '' ) {
		return empty( $collection_name ) ? $this->default_collection : $collection_name;
	}

	/**
	 * Checks whether the store is ready for use.
	 *
	 * @param string $method Calling method.
	 * @return bool
	 */
	protected function is_initialized( $method ) {
		if ( did_action( "{$this->store_name}_db_init" ) ) {
			return true;
		}

		_doing_it_wrong(
			esc_html( $method ),
			sprintf( '%s database is not yet initialized. Ensure your code runs after the after_setup_theme hook', esc_html( $this->store_name ) ),
			'1.8.4'
		);

		return false;
	}

	/**
	 * Retrieves a collection.
	 *
	 * @param string $collection_name Collection name.
	 * @param string $method          Calling method.
	 * @return Collection|false
	 */
	protected function get_collection( $collection_name = '', $method = '' ) {
		if ( ! $this->is_initialized( empty( $method ) ? __METHOD__ : $method ) ) {
			return false;
		}

		return $this->store->get( $this->get_collection_name( $collection_name ) );
	}

	/**
	 * Retrieves a record from the database.
	 *
	 * @param Record|\WP_Post|int|array $record_id The record ID, object, or props. Leave blank to create a new record.
	 * @param string $collection_name The collection name.
	 * @return Record|\WP_Error record object if found, error object if not found.
	 */
	public function get( $record_id = 0, $collection_name = '' ) {

		// Abort if we already have an error.
		if ( is_wp_error( $record_id ) ) {
			return $record_id;
		}

		// No need to refetch the record if it's already an object.
		if ( $record_id instanceof Record ) {
			return $record_id;
		}

		// Convert posts to IDs.
		if ( $record_id instanceof \WP_Post ) {
			$record_id = $record_id->ID;
		}

		if ( ! $this->is_initialized( __METHOD__ ) ) {
			return false;
		}

		try {
			$collection_name = $this->get_collection_name( $collection_name );
			$collection      = $this->store->get( $collection_name );

			if ( empty( $collection ) ) {
				return new \WP_Error( 'invalid_collection', sprintf( 'Invalid collection: %s', $collection_name ) );
			}

			if ( is_array( $record_id ) ) {
				$record = $collection->get( 0 );
				$record->set_props( $record_id );
				return $record;
			}

			return $collection->get( (int) $record_id );
		} catch ( \Hizzle\Store\Store_Exception $e ) {
			return new \WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		}
	}

	/**
	 * Retrieves an ID by a given prop.
	 *
	 * @param string $prop — The prop to search by.
	 * @param int|string|float $value — The value to search for.
	 * @param string $collection_name The collection name.
	 * @return int|false — The ID if found, false otherwise.
	 */
	public function get_id_by_prop( $prop, $value, $collection_name = '' ) {
		$collection = $this->get_collection( $collection_name, __METHOD__ );
		return empty( $collection ) ? false : $collection->get_id_by_prop( $prop, $value );
	}

	/**
	 * Deletes all objects matching the query.
	 *
	 * @param array $where An array of $prop => $value pairs.
	 * @param string $collection_name The collection name.
	 * @return int|false — The number of rows deleted, or false on error.
	 */
	public function delete_where( $where, $collection_name = '' ) {
		$collection = $this->get_collection( $collection_name, __METHOD__ );
		return empty( $collection ) ? false : $collection->delete_where( $where );
	}

	/**
	 * Deletes all objects.
	 *
	 * @param string $collection_name The collection name.
	 */
	public function delete_all( $collection_name = '' ) {
		$collection = $this->get_collection( $collection_name, __METHOD__ );
		return empty( $collection ) ? false : $collection->delete_all();
	}

	/**
	 * Retrieve record meta field for a record.
	 *
	 * @param   int    $record_id  Record ID.
	 * @param   string $meta_key   The meta key to retrieve. By default, returns data for all keys.
	 * @param   bool   $single     If true, returns only the first value for the specified meta key. This parameter has no effect if $key is not specified.
	 * @param string   $collection_name The collection name.
	 * @return  mixed              Will be an array if $single is false. Will be value of meta data field if $single is true.
	 * @access  public
	 * @since   2.0.0
	 */
	public function get_record_meta( $record_id, $meta_key = '', $single = false, $collection_name = '' ) {
		$collection = $this->get_collection( $collection_name, __METHOD__ );
		return empty( $collection ) ? false : $collection->get_record_meta( $record_id, $meta_key, $single );
	}

	/**
	 * Adds record meta field for a record.
	 *
	 * @param   int    $record_id  Record ID.
	 * @param   string $meta_key   The meta key to update.
	 * @param   mixed  $meta_value Metadata value. Must be serializable if non-scalar.
	 * @param   mixed  $unique     Whether the same key should not be added.
	 * @param string   $collection_name The collection name.
	 * @return  int|false  Meta ID on success, false on failure.
	 * @access  public
	 * @since   2.0.0
	 */
	public function add_record_meta( $record_id, $meta_key, $meta_value, $unique = false, $collection_name = '' ) {
		$collection = $this->get_collection( $collection_name, __METHOD__ );
		return empty( $collection ) ? false : $collection->add_record_meta( $record_id, $meta_key, $meta_value, $unique );
	}

	/**
	 * Updates record meta field for a record.
	 *
	 * Use the $prev_value parameter to differentiate between meta fields with the same key and record ID.
	 *
	 * If the meta field for the record does not exist, it will be added and its ID returned.
	 *
	 * @param   int    $record_id   Record ID.
	 * @param   string $meta_key    The meta key to update.
	 * @param   mixed  $meta_value  Metadata value. Must be serializable if non-scalar.
	 * @param   mixed  $prev_value  Previous value to check before updating.
	 * @param string   $collection_name The collection name.
	 * @return  mixed  The new meta field ID if a field with the given key didn't exist and was therefore added, true on successful update, false on failure.
	 * @access  public
	 * @since   1.0.0
	 */
	public function update_record_meta( $record_id, $meta_key, $meta_value, $prev_value = '', $collection_name = '' ) {
		$collection = $this->get_collection( $collection_name, __METHOD__ );
		return empty( $collection ) ? false : $collection->update_record_meta( $record_id, $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Deletes a record meta field for the given record ID.
	 *
	 * You can match based on the key, or key and value. Removing based on key and value, will keep from removing duplicate metadata with the same key. It also allows removing all metadata matching the key, if needed.
	 *
	 * @param   int    $record_id  Record ID.
	 * @param   string $meta_key   The meta key to delete.
	 * @param   mixed  $meta_value Metadata value. Must be serializable if non-scalar.
	 * @param string   $collection_name The collection name.
	 * @return  bool  True on success, false on failure.
	 * @access  public
	 * @since   1.0.0
	 */
	public function delete_record_meta( $record_id, $meta_key, $meta_value = '', $collection_name = '' ) {
		$collection = $this->get_collection( $collection_name, __METHOD__ );
		return empty( $collection ) ? false : $collection->delete_record_meta( $record_id, $meta_key, $meta_value );
	}

	/**
	 * Deletes all meta values for the given meta key.
	 *
	 * @param   string $meta_key  The meta key.
	 * @param string   $collection_name The collection name.
	 * @access  public
	 * @since   1.0.0
	 */
	public function delete_all_meta_by_key( $meta_key, $collection_name = '' ) {
		$collection = $this->get_collection( $collection_name, __METHOD__ );
		return empty( $collection ) ? false : $collection->delete_all_meta( $meta_key );
	}

	/**
	 * Deletes all record meta fields for the given record ID.
	 *
	 * @param   int $record_id  Record ID.
	 * @param string   $collection_name The collection name.
	 * @access  public
	 * @since   1.0.0
	 */
	public function delete_all_record_meta( $record_id, $collection_name = '' ) {
		$collection = $this->get_collection( $collection_name, __METHOD__ );
		return empty( $collection ) ? false : $collection->delete_all_record_meta( $record_id );
	}

	/**
	 * Gets all meta values for a given key.
	 *
	 * @param string $meta_key        Meta key.
	 * @param string $collection_name Collection name.
	 * @return array
	 */
	public function get_all_meta_by_key( $meta_key, $collection_name = '' ) {
		$collection = $this->get_collection( $collection_name, __METHOD__ );
		return empty( $collection ) ? array() : $collection->get_all_meta( $meta_key );
	}

	/**
	 * Determines if a meta field with the given key exists for the given record ID.
	 *
	 * @param int    $record_id  ID of the record metadata is for.
	 * @param string $meta_key       Metadata key.
	 * @param string $collection_name The collection name.
	 *
	 */
	public function record_meta_exists( $record_id, $meta_key, $collection_name = '' ) {
		$collection = $this->get_collection( $collection_name, __METHOD__ );
		return empty( $collection ) ? false : $collection->record_meta_exists( $record_id, $meta_key );
	}

	/**
	 * Queries records from the database.
	 *
	 * @param string $collection The collection name.
	 * @param array $args Query arguments.
	 * @param string $to_return 'results' returns the found records, 'count' returns the total count, 'aggregate' runs an aggregate query, while 'query' returns query object.
	 *
	 * @return int|array|Record[]|\Hizzle\Store\Query|\WP_Error
	 */
	public function query( $collection_name = '', $args = array(), $to_return = 'results' ) {

		if ( ! $this->is_initialized( __METHOD__ ) ) {
			return false;
		}

		// Do not retrieve any fields if we just want the count.
		if ( 'count' === $to_return ) {
			$args['count_only'] = true;
		}

		// Do not count all matches if we just want the results.
		if ( 'results' === $to_return ) {
			$args['count_total'] = false;
		}

		// Run the query.
		try {
			$collection_name = $this->get_collection_name( $collection_name );
			$collection      = $this->store->get( $collection_name );

			if ( empty( $collection ) ) {
				return new \WP_Error( 'hizzle_invalid_collection', sprintf( 'Invalid collection: %s', $collection_name ) );
			}

			$query = $collection->query( $args );

			if ( 'results' === $to_return ) {
				return $query->get_results();
			}

			if ( 'count' === $to_return ) {
				return $query->get_total();
			}

			if ( 'aggregate' === $to_return ) {
				return $query->get_aggregate();
			}

			return $query;
		} catch ( \Hizzle\Store\Store_Exception $e ) {
			return new \WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		}
	}
}
