<?php

namespace Hizzle\Noptin\DB;

/**
 * Contains the main DB class.
 *
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The main DB class.
 */
class Main {

	/**
	 * @var Migrate The migrator.
	 */
	public $migrate;

	/**
	 * @var Schema The database schema.
	 */
	public $schema;

	/**
	 * The installer.
	 *
	 * @var Installer
	 */
	public $installer;

	/**
	 * The data store.
	 *
	 * @var \Hizzle\Store\Store
	 */
	public $store;

	/**
	 * Webhooks manager.
	 *
	 * @var \Hizzle\Store\Webhooks
	 */
	public $webhooks;

	/**
	 * Route controller classes.
	 *
	 * @param \Hizzle\Store\REST_Controller[]
	 */
	public $controllers;

	/**
	 * Stores the main db instance.
	 *
	 * @access private
	 * @var    Main $instance The main db instance.
	 * @since  1.0.0
	 */
	private static $instance = null;

	/**
	 * Get active instance
	 *
	 * @access public
	 * @since  1.0.0
	 * @return Main The main db instance.
	 */
	public static function instance() {

		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Loads the class.
	 *
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'load' ) );
	}

	/**
	 * Loads the DB class.
	 *
	 * @return void
	 */
	public function load() {

		// Migrator.
		$this->migrate = new Migrate();

		// Schema.
		$this->schema = new Schema();

		// The installer.
		$this->installer = new Installer();

		// Init the data store.
		$this->store = \Hizzle\Store\Store::init( 'noptin', $this->schema->get_schema() );

		// Init the webhooks manager.
		$this->webhooks = new \Hizzle\Store\Webhooks( $this->store );

		// Init the REST API manager.
		foreach ( $this->store->get_collections() as $collection ) {

			// Ignore events that are not associated with any CRUD class.
			if ( empty( $collection->object ) ) {
				continue;
			}

			// Init the controller class.
			$this->controllers[ $collection->get_name() ] = new \Hizzle\Store\REST_Controller( $this->store->get_namespace(), $collection->get_name() );
		}

		// Fire action hook.
		do_action( 'hizzle_noptin_db_init', $this );
	}

	/**
	 * Retrieves a record from the database.
	 *
	 * @param \Hizzle\Store\Record|\WP_Post|int $record_id The record ID.
	 * @param string $collection_name The collection name.
	 * @return \Hizzle\Store\Record|\WP_Error record object if found, error object if not found.
	 */
	public function get( $record_id, $collection_name = 'subscribers' ) {

		// Abort if we already have an error.
		if ( is_wp_error( $record_id ) ) {
			return $record_id;
		}

		// No need to refetch the record if it's already an object.
		if ( is_a( $record_id, '\Hizzle\Store\Record' ) ) {
			return $record_id;
		}

		// Convert posts to IDs.
		if ( is_a( $record_id, 'WP_Post' ) ) {
			$record_id = $record_id->ID;
		}

		try {

			$collection = $this->store->get( $collection_name );

			if ( empty( $collection ) ) {
				return new \WP_Error( 'noptin_invalid_collection', sprintf( 'Invalid collection: %s', $collection_name ) );
			}

			return $collection->get( $record_id );
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
	public function get_id_by_prop( $prop, $value, $collection_name = 'subscribers' ) {
		$collection = $this->store->get( $collection_name );
		return empty( $collection ) ? false : $collection->get_id_by_prop( $prop, $value );
	}

	/**
	 * Deletes all objects matching the query.
	 *
	 * @param array $where An array of $prop => $value pairs.
	 * @param string $collection_name The collection name.
	 * @return int|false — The number of rows deleted, or false on error.
	 */
	public function delete_where( $where, $collection_name = 'subscribers' ) {
		$collection = $this->store->get( $collection_name );
		return empty( $collection ) ? false : $collection->delete_where( $where );
	}

	/**
	 * Deletes all objects.
	 *
	 * @param string $collection_name The collection name.
	 */
	public function delete_all( $collection_name = 'subscribers' ) {
		$collection = $this->store->get( $collection_name );
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
	 * @since   1.13.0
	 */
	public function get_record_meta( $record_id, $meta_key = '', $single = false, $collection_name = 'subscribers' ) {
		$collection = $this->store->get( $collection_name );
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
	 * @since   1.13.0
	 */
	public function add_record_meta( $record_id, $meta_key, $meta_value, $unique = false, $collection_name = 'subscribers' ) {
		$collection = $this->store->get( $collection_name );
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
	public function update_record_meta( $record_id, $meta_key, $meta_value, $prev_value = '', $collection_name = 'subscribers' ) {
		$collection = $this->store->get( $collection_name );
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
	public function delete_record_meta( $record_id, $meta_key, $meta_value = '', $collection_name = 'subscribers' ) {
		$collection = $this->store->get( $collection_name );
		return empty( $collection ) ? false : $collection->delete_record_meta( $record_id, $meta_key, $meta_value );
	}

	/**
	 * Deletes all record meta fields for the given record ID.
	 *
	 * @param   int $record_id  Record ID.
	 * @param string   $collection_name The collection name.
	 * @access  public
	 * @since   1.0.0
	 */
	public function delete_all_record_meta( $record_id, $collection_name = 'subscribers' ) {
		$collection = $this->store->get( $collection_name );
		return empty( $collection ) ? false : $collection->delete_all_record_meta( $record_id );
	}

	/**
	 * Determines if a meta field with the given key exists for the given noptin record ID.
	 *
	 * @param int    $record_id  ID of the record metadata is for.
	 * @param string $meta_key       Metadata key.
	 * @param string $collection_name The collection name.
	 *
	 */
	public function record_meta_exists( $record_id, $meta_key, $collection_name = 'subscribers' ) {
		$collection = $this->store->get( $collection_name );
		return empty( $collection ) ? false : $collection->record_meta_exists( $record_id, $meta_key );
	}

	/**
	 * Queries records from the database.
	 *
	 * @param string $collection The collection name.
	 * @param array $args Query arguments.
	 * @param string $return 'results' returns the found records, 'count' returns the total count, 'aggregate' runs an aggregate query, while 'query' returns query object.
	 *
	 * @return int|array|\Hizzle\Store\Record[]|\Hizzle\Store\Query|\WP_Error
	 */
	public function query( $collection_name, $args = array(), $return = 'results' ) {

		// Do not retrieve all fields if we just want the count.
		if ( 'count' === $return ) {
			$args['fields'] = 'id';
			$args['number'] = 1;
		}

		// Do not count all matches if we just want the results.
		if ( 'results' === $return ) {
			$args['count_total'] = false;
		}

		// Run the query.
		try {

			$collection = $this->store->get( $collection_name );

			if ( empty( $collection ) ) {
				return new \WP_Error( 'noptin_invalid_collection', sprintf( 'Invalid collection: %s', $collection_name ) );
			}

			$query = $collection->query( $args );

			if ( 'results' === $return ) {
				return $query->get_results();
			}

			if ( 'count' === $return ) {
				return $query->get_total();
			}

			if ( 'aggregate' === $return ) {
				return $query->get_aggregate();
			}

			return $query;
		} catch ( \Hizzle\Store\Store_Exception $e ) {
			return new \WP_Error( $e->getErrorCode(), $e->getMessage(), $e->getErrorData() );
		}

	}

}
