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
	public function get( $record_id, $collection_name ) {

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
	public function get_id_by_prop( $prop, $value, $collection_name ) {

		try {

			$collection = $this->store->get( $collection_name );

			if ( empty( $collection ) ) {
				return false;
			}

			return $collection->get_id_by_prop( $prop, $value );
		} catch ( \Hizzle\Store\Store_Exception $e ) {
			return false;
		}
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
