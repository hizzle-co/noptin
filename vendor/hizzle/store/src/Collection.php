<?php

namespace Hizzle\Store;

/**
 * Store API: Handles CRUD operations on a single collection of data.
 *
 * @since   1.0.0
 * @package Hizzle\Store
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Handles CRUD operations on a single collection of data.
 *
 * @since 1.0.0
 */
class Collection {

	/**
	 * The collection's namespace.
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * The collection's name, e.g subscribers.
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * The collection's singular name, e.g subscriber.
	 *
	 * @var string
	 */
	protected $singular_name;

	/**
	 * CRUD class. Should extend Record.
	 *
	 * @var string
	 */
	public $object = null;

	/**
	 * The capabillity allowed to manage this collection.
	 *
	 * @var string
	 */
	public $capabillity = 'manage_options';

	/**
	 * In case this collection is connected to the posts table.
	 *
	 * @var string
	 */
	public $post_type = '';

	/**
	 * A map of collection props to post fields.
	 *
	 * @var array
	 */
	public $post_map = array();

	/**
	 * A list of props for a single record.
	 *
	 * @var Prop[]
	 */
	protected $props = array();

	/**
	 * Indexes. Used by MySQL.
	 *
	 * @var array
	 */
	public $keys;

	/**
	 * The database schema.
	 *
	 * @var string
	 */
	protected $schema;

	/**
	 * The REST schema.
	 *
	 * @var string
	 */
	protected $rest_schema;

	/**
	 * The Query schema.
	 *
	 * @var string
	 */
	protected $query_schema;

	/**
	 * A list of class instances
	 *
	 * @var Collection[]
	 */
	protected static $instances = array();

	/**
	 * Class constructor.
	 *
	 * @param string $namespace Namespace of this store's instance.
	 * @param array  $data An array of relevant data.
	 */
	public function __construct( $namespace, $data ) {

		// Set namespace.
		$this->namespace = $namespace;

		// Set collection data.
		foreach ( apply_filters( $this->hook_prefix( 'collection_data' ), $data ) as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->$key = $value;
			}
		}

		// Prepare props.
		foreach ( $this->props as $key => $prop ) {
			if ( ! $prop instanceof Prop ) {
				$prop['name']        = $key;
				$this->props[ $key ] = new Prop( $this->get_full_name(), $prop );
			}
		}

		// Register the collection.
		self::$instances[ $this->get_full_name() ] = $this;
	}

	/**
	 * Retrieves a collection by its name.
	 *
	 * @param string $name Name of the collection.
	 * @return Collection
	 * @throws Store_Exception
	 */
	public static function instance( $name ) {

		if ( ! isset( self::$instances[ $name ] ) ) {
			throw new Store_Exception( 'missing_collection', wp_sprintf( 'Collection %s not found.', $name ) );
		}

		return self::$instances[ $name ];
	}

	/**
	 * Retrieves the hook prefix.
	 *
	 * @param string $suffix Suffix to append to the hook prefix.
	 * @param bool $use_singular Whether to use the singular name.
	 * @return string
	 */
	public function hook_prefix( $suffix = '', $use_singular = false ) {
		return $this->get_full_name( $use_singular ) . '_' . $suffix;
	}

	/**
	 * Retrieves the namespace.
	 *
	 * @return string
	 */
	public function get_namespace() {
		return $this->namespace;
	}

	/**
	 * Retrieves the name.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Retrieves the singular name.
	 *
	 * @return string
	 */
	public function get_singular_name() {
		return $this->singular_name;
	}

	/**
	 * Retrieves the full name.
	 *
	 * @param bool $use_singular Whether to use the singular name.
	 * @return string
	 */
	public function get_full_name( $use_singular = false ) {

		if ( $use_singular ) {
			$name = $this->get_singular_name();
		} else {
			$name = $this->get_name();
		}

		return $this->get_namespace() . '_' . $name;
	}

	/**
	 * Retrieves the database name.
	 *
	 * @return string
	 */
	public function get_db_table_name() {
		$db_table = $GLOBALS['wpdb']->prefix . $this->get_full_name();
		return apply_filters( $this->hook_prefix( 'db_table_name' ), $db_table );
	}

	/**
	 * Retrieves a single property.
	 *
	 * @param string $key The property key.
	 * @return null|Prop
	 */
	public function get_prop( $key ) {
		return isset( $this->props[ $key ] ) ? $this->props[ $key ] : null;
	}

	/**
	 * Retrieves all props.
	 *
	 * @return Prop[]
	 */
	public function get_props() {
		return $this->props;
	}

	/**
	 * Retrieves the main store.
	 *
	 * @return Store|null
	 */
	public function get_store() {
		return Store::instance( $this->namespace );
	}

	/**
	 * Checks if the collection stores data in a custom post type.
	 *
	 * @return bool
	 */
	public function is_cpt() {
		return ! empty( $this->post_type );
	}

	/**
	 * Returns the table definition as a string.
	 *
	 * @return string
	 */
	public function get_schema() {
		global $wpdb;

		// Retrieve from cache.
		if ( ! empty( $this->schema ) ) {
			return $this->schema;
		}

		$table  = $this->get_db_table_name();
		$schema = "CREATE TABLE $table (\n";

		// Add each property.
		foreach ( $this->props as $key => $prop ) {
			if ( ! $this->is_cpt() || ! in_array( $key, $this->post_map, true ) ) {
				$schema .= $prop->get_schema() . ",\n";
			}
		}

		// Add indexes.
		foreach ( $this->keys as $index => $cols ) {
			$cols = $this->prepare_index_cols( $cols );

			// Unique keys will be added separately.
			if ( 'unique' !== $index ) {
				$cols = implode( ',', $cols );
			}

			if ( 'primary' === $index ) {
				$schema .= "PRIMARY KEY  ($cols),\n"; // Maintain 2 spaces between key and opening bracket.
			} elseif ( 'unique' === $index ) {

				foreach ( $cols as $prop => $index ) {
					$schema .= "UNIQUE KEY $prop ($index),\n";
				}
			} else {
				$schema .= "KEY $index ($cols),\n";
			}
		}

		$schema = rtrim( $schema, ",\n" );

		// Add character collation.
		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$schema .= ") $collate;";

		$this->schema = apply_filters( $this->hook_prefix( 'table_schema' ), $schema, $this );
		return $this->schema;
	}

	/**
	 * Prepares indexes for insertion.
	 *
	 * @param array $cols
	 * @return array
	 */
	protected function prepare_index_cols( $cols ) {
		$prepared = array();

		foreach ( wp_parse_list( $cols ) as $col ) {
			$col              = trim( $col );
			$prepared[ $col ] = $this->prepare_index_col( $col );
		}

		return $prepared;
	}

	/**
	 * Prepares an index for insertion.
	 *
	 * @param string $col
	 * @return string
	 */
	protected function prepare_index_col( $col ) {

		$max_index_length = 191;
		$column_length    = 0;

		if ( isset( $this->props[ $col ] ) && is_int( $this->props[ $col ]->length ) ) {
			$column_length = $this->props[ $col ]->length;
		}

		return $max_index_length > $column_length ? $col : $col . '(' . $max_index_length . ')';
	}

	/**
	 * Returns the REST schema as an array.
	 *
	 * @return string
	 */
	public function get_rest_schema() {

		// Retrieve from cache.
		if ( ! empty( $this->rest_schema ) ) {
			return $this->rest_schema;
		}

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->get_full_name(),
			'type'       => 'object',
			'properties' => array(),
		);

		// Add each prop.
		foreach ( $this->props as $prop ) {
			$schema['properties'][ $prop->name ] = $prop->get_rest_schema();
		}

		$this->rest_schema = apply_filters( $this->hook_prefix( 'rest_schema' ), $schema, $this );
		return $this->rest_schema;
	}

	/**
	 * Returns the Query schema as an array.
	 *
	 * @return string
	 */
	public function get_query_schema() {

		// Retrieve from cache.
		if ( ! empty( $this->query_schema ) ) {
			return $this->query_schema;
		}

		$query_schema              = array();

		$query_schema['page']      = array(
			'description'       => __( 'Current page of the collection.', 'hizzle-store' ),
			'type'              => 'integer',
			'default'           => 1,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
			'minimum'           => 1,
		);

		$query_schema['per_page']  = array(
			'description'       => __( 'Maximum number of items to be returned in result set.', 'hizzle-store' ),
			'type'              => 'integer',
			'default'           => 10,
			'minimum'           => 1,
			'maximum'           => 100,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$query_schema['offset']          = array(
			'description'       => __( 'Offset the result set by a specific number of items.', 'hizzle-store' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$query_schema['search']          = array(
			'description'       => __( 'Limit results to those matching a string.', 'hizzle-store' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$query_schema['search_columns']          = array(
			'description'       => __( 'An array of props to search in.', 'hizzle-store' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'string',
			),
			'validate_callback' => 'rest_validate_request_arg',
			'default'           => array_keys( $this->props ),
		);

		$query_schema['exclude']         = array(
			'description'       => __( 'Ensure result set excludes specific IDs.', 'hizzle-store' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'default'           => array(),
			'sanitize_callback' => 'wp_parse_id_list',
		);

		$query_schema['include']         = array(
			'description'       => __( 'Limit result set to specific ids.', 'hizzle-store' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'default'           => array(),
			'sanitize_callback' => 'wp_parse_id_list',
		);

		// Add each prop.
		foreach ( $this->props as $prop ) {
			$query_schema = array_merge( $query_schema, $prop->get_query_schema() );
		}

		$query_schema['order']           = array(
			'description'       => __( 'Order sort attribute ascending or descending.', 'hizzle-store' ),
			'type'              => 'string',
			'default'           => 'desc',
			'enum'              => array( 'asc', 'desc' ),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$query_schema['orderby']         = array(
			'description'       => __( 'Sort collection by object attribute.', 'hizzle-store' ),
			'type'              => 'string',
			'default'           => 'id',
			'items'             => array(
				'type' => 'string',
			),
			'enum'              => array_merge( array_keys( $this->props ), array( 'id', 'include' ) ),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$this->query_schema = apply_filters( $this->hook_prefix( 'query_schema' ), $query_schema, $this );
		return $this->query_schema;
	}

	/**
	 * Runs a query against the collection.
	 *
	 * @param array $args The query arguments.
	 * @return Query
	 * @throws Store_Exception
	 */
	public function query( $args ) {
		return new Query( $this->get_full_name(), $args );
	}

	/**
	 * Retrieves a single item from the collection.
	 *
	 * @param int $item_id The item id.
	 * @return Record
	 * @throws Store_Exception
	 */
	public function get( $item_id ) {
		$class = $this->object;
		$args  = array(
			'data'            => array(),
			'collection_name' => $this->get_full_name(),
			'object_type'     => $this->get_full_name( true ),
		);

		foreach ( $this->props as $key => $prop ) {

			// Skip id.
			if ( 'id' === $key ) {
				continue;
			}

			// The default will be null if not explicitly set.
			$args['data'][ $key ] = $prop->default;

			// If the prop is not nullable, ensure that the default is not nullable.
			if ( ! $prop->nullable && is_null( $args['data'][ $key ] ) ) {
				$args['data'][ $key ] = '';
			}
		}

		return new $class( $item_id, $args );
	}

	/**
	 * Retrieves an ID by a given prop.
	 *
	 * @param string $prop The prop to search by.
	 * @param int|string|float $value The value to search for.
	 * @return int|false The ID if found, false otherwise.
	 */
	public function get_id_by_prop( $prop, $value ) {
		global $wpdb;

		if ( '' === $value ) {
			return false;
		}

		// Try the cache.
		$prop  = sanitize_key( $prop );
		$value = trim( $value );
		$id    = wp_cache_get( $value, $this->hook_prefix( 'ids_by_' . $prop, true ) );

		// Maybe retrieve from the db.
		if ( false === $id ) {

			$table = $this->get_db_table_name();
			$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE $prop = %s LIMIT 1", $value ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			if ( ! empty( $row ) ) {
				$id = $row['id'];
				$this->update_cache( $row );
			} else {
				$id = 0;
				wp_cache_set( $value, $id, $this->hook_prefix( 'ids_by_' . $prop, true ) );
			}
		}

		return (int) $id;
	}

	/**
	 * Prepares data for insertion.
	 *
	 * @param array $data The data to insert/update.
	 * @return array
	 */
	protected function prepare_data( $data ) {
		$prepared = array();

		foreach ( $data as $key => $value ) {

			// Handle boolean values.
			if ( is_bool( $value ) ) {
				$value = (int) $value;
			}

			// Date fields.
			if ( $value instanceof Date_Time ) {

				if ( ! empty( $this->props[ $key ] ) && 'date' === strtolower( $this->props[ $key ]->type ) ) {
					$value = $value->utc( 'Y-m-d' );
				} else {
					$value = $value->utc();
				}
			}

			// Handle arrays.
			$value = maybe_serialize( $value );

			$prepared[ $key ] = $value;
		}

		return $prepared;
	}

	/**
	 * Creates a new record.
	 *
	 * @param Record $record The record to create.
	 * @throws Store_Exception If an error occurs.
	 */
	public function create( &$record ) {
		global $wpdb;

		// Fires before creating a record.
		do_action( $this->hook_prefix( 'before_create', true ), $record );

		$fields  = array();
		$formats = array();

		// Is this a custom post type?
		if ( $this->is_cpt() ) {

			// Insert into $wp->posts.
			$args = array(
				'post_type' => $this->post_type,
			);

			foreach ( $this->post_map as $key => $post_field ) {
				$args[ $post_field ] = $record->{"get_$key"}( 'edit' );
			}

			$post_id = wp_insert_post( $this->prepare_data( $args ), true );

			// Abort if the post was not created.
			if ( is_wp_error( $post_id ) ) {
				throw new Store_Exception( $post_id->get_error_code(), $post_id->get_error_message() );
			}

			if ( empty( $post_id ) ) {
				$this->not_saved();
			}

			$record->set_id( $post_id );

			$fields['id'] = $post_id;
			$formats[]    = '%d';
		} elseif ( ! empty( $record->create_with_id ) ) {
			$fields['id'] = (int) $record->create_with_id;
			$formats[]    = '%d';
			$record->set_id( (int) $record->create_with_id );
		}

		// Save meta data.
		foreach ( $this->props as $key => $prop ) {
			$fields[ $key ] = $record->{"get_$key"}( 'edit' );
			$formats[]      = $prop->get_data_type();
		}

		// Save date created in UTC time.
		if ( ! $this->is_cpt() && isset( $this->props['date_created'] ) && empty( $fields['date_created'] ) ) {
			$fields['date_created'] = new Date_Time( 'now', new \DateTimeZone( 'UTC' ) );
		}

		// Save date modified in UTC time.
		if ( ! $this->is_cpt() && isset( $this->props['date_modified'] ) ) {
			$fields['date_modified'] = new Date_Time( 'now', new \DateTimeZone( 'UTC' ) );
		}

		// Insert values in the db.
		$result = $wpdb->insert(
			$this->get_db_table_name(),
			apply_filters( $this->hook_prefix( 'insert_data', true ), $this->prepare_data( $fields ), $record ),
			apply_filters( $this->hook_prefix( 'insert_formats', true ), $formats, $record )
		);

		// If the insert failed, throw an exception.
		if ( $result ) {

			if ( ! $record->exists() ) {
				$record->set_id( $wpdb->insert_id );
			}

			$record->apply_changes();

			$this->clear_cache( (object) $record->get_data() );

			do_action( $this->hook_prefix( 'created', true ), $record );
			return $result;
		}

		return $this->not_saved();

	}

	/**
	 * Reads a record from the database or cache.
	 *
	 * @param Record $record
	 * @throws Store_Exception
	 */
	public function read( &$record ) {
		global $wpdb;

		// Fires before reading a record.
		do_action( $this->hook_prefix( 'before_read', true ), $record );

		// Fetch post data.
		// Not cached with normal data as WP has its own caching.
		$extra_data = array();
		if ( $this->is_cpt() ) {
			$post = get_post( $record->get_id() );

			if ( empty( $post ) || $this->post_type !== $post->post_type ) {
				$this->not_found();
			}

			foreach ( $this->post_map as $key => $post_field ) {
				$extra_data[ $key ] = $post->$post_field;
			}
		}

		// Maybe fetch from cache.
		$data = wp_cache_get( $record->get_id(), $this->get_full_name() );

		// If not found, read from the db.
		if ( false === $data ) {

			// Include meta data.
			$table_name = $this->get_db_table_name();
			$data       = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $record->get_id() ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				ARRAY_A
			);

			// In case of auto-drafts etc.
			if ( empty( $data ) && $this->is_cpt() ) {

				// Ensure there is always a record to avoid any errors.
				$this->save_defaults( $record );
				$data = $record->get_data();

			} elseif ( empty( $data ) ) {
				$this->not_found();
			}

			$data = array_merge( $data, $extra_data );

			// Cache the record data.
			$this->update_cache( $data );

		}

		// Merge meta data and post data.
		$data = array_merge( $data, $extra_data );

		// Format the raw data.
		foreach ( $this->props as $prop ) {

			// Dates are stored in UTC time, but without a timezone.
			// Fix that.
			if ( $prop->is_date() && ! empty( $data[ $prop->name ] ) ) {
				$data[ $prop->name ] = new Date_Time( $data[ $prop->name ], new \DateTimeZone( 'UTC' ) );
			}
		}

		// Set the record data.
		// Save data in the record.
		$data = apply_filters( $this->hook_prefix( 'read_data' ), $data, $record );
		$record->set_props( $data );
	}

	/**
	 * Updates a record in the database.
	 *
	 * @param Record $record The record to update.
	 * @throws Store_Exception If an error occurs.
	 */
	public function update( &$record ) {
		global $wpdb;

		// Fires before updating a record.
		do_action( $this->hook_prefix( 'before_update', true ), $record );

		// Prepare args.
		$fields    = array();
		$formats   = array();
		$post_args = array();
		$changes   = array_keys( $record->get_changes() );

		// Prepare args.
		foreach ( $this->props as $key => $prop ) {
			if ( in_array( $key, $changes, true ) ) {

				// Post fields.
				if ( $this->is_cpt() && in_array( $key, array_keys( $this->post_map ), true ) ) {
					$post_args[ $this->post_map[ $key ] ] = $record->{"get_$key"}( 'edit' );
				} else {
					$fields[ $key ] = $record->{"get_$key"}( 'edit' );
					$formats[]      = $prop->get_data_type();
				}
			}
		}

		// Update post.
		if ( $this->is_cpt() && ! empty( $post_args ) ) {

			$post_args['ID'] = $record->get_id();
			$result          = wp_update_post( $post_args, true );

			if ( is_wp_error( $result ) ) {
				throw new Store_Exception( $result->get_error_code(), $result->get_error_message() );
			}

			if ( ! $result ) {
				$this->not_saved();
			}
		}

		// Update meta data.
		// Save date modified in UTC time.
		if ( ! $this->is_cpt() && isset( $this->props['date_modified'] ) ) {
			$fields['date_modified'] = new Date_Time( 'now', new \DateTimeZone( 'UTC' ) );
			$formats[]               = '%s';

			$record->set_props( array( 'date_modified' => $fields['date_modified'] ) );
		}

		// Update values in the db if there are changes.
		if ( ! empty( $fields ) ) {

			$result = $wpdb->update(
				$this->get_db_table_name(),
				apply_filters( $this->hook_prefix( 'update_data', true ), $this->prepare_data( $fields ), $record ),
				array( 'id' => $record->get_id() ),
				apply_filters( $this->hook_prefix( 'update_formats', true ), $formats, $record )
			);

			if ( ! $result ) {
				return false;
			}
		}

		$this->clear_cache( (object) $record->get_data() );

		$record->apply_changes();

		do_action( $this->hook_prefix( 'updated', true ), $record );

	}

	/**
	 * Deletes an object from the database.
	 *
	 * @param Record $record The record to delete.
	 * @param bool     $delete_permanently   Whether or not to delete permanently. Only applies to CPT objects.
	 */
	public function delete( &$record, $delete_permanently = true ) {

		/**@var wpdb $wpdb */
		global $wpdb;

		// Fires before deleting a record.
		do_action( $this->hook_prefix( 'before_delete', true ), $record );

		// Invalidate cache.
		$this->clear_cache( (object) $record->get_data() );

		// If this is a CPT, delete the post.
		if ( $this->is_cpt() ) {
			wp_delete_post( $record->get_id(), $delete_permanently );
		}

		// Delete the record from the database.
		$wpdb->delete( $this->get_db_table_name(), array( 'id' => $record->get_id() ), array( '%d' ) );

		do_action( $this->hook_prefix( 'deleted', true ), $record, $delete_permanently );

		$record->set_id( 0 );
	}

	/**
	 * Deletes all objects matching the query.
	 *
	 * @param array $where An array of $prop => $value pairs.
	 * @return int|false — The number of rows updated, or false on error.
	 */
	public function delete_where( $where ) {
		global $wpdb;

		return $wpdb->delete( $this->get_db_table_name(), $where );
	}

	/**
	 * Deletes all objects.
	 */
	public function delete_all() {
		global $wpdb;

		$wpdb->query( "TRUNCATE TABLE {$this->get_db_table_name()}" );
	}

	/**
	 * Saves default values.
	 *
	 * @param Record $record The record to create.
	 */
	protected function save_defaults( $record ) {
		global $wpdb;

		$to_insert    = array( 'id' => $record->get_id() );
		$data_formats = array( '%d' );

		// Save meta data.
		foreach ( $this->props as $key => $prop ) {
			if ( ! $this->is_cpt() || ! in_array( $key, array_keys( $this->post_map ), true ) ) {
				$to_insert[ $key ] = $record->{"get_$key"}( 'edit' );
				$data_formats[]    = $prop->get_data_type();
			}
		}

		$wpdb->insert( $this->get_db_table_name(), $this->prepare_data( $to_insert ), $data_formats );
	}

	/**
	 * Retrieves the cache keys.
	 *
	 */
	public function get_cache_keys() {

		$keys = array();

		// Cache by unique keys.
		if ( isset( $this->keys['unique'] ) ) {
			$keys = array_merge( $keys, $this->keys['unique'] );
		}

		// Filter and return.
		return apply_filters( $this->hook_prefix( 'cache_fields', true ), $keys );
	}

	/**
	 * Update caches.
	 *
	 * @param array|Record $record The raw db record.
	 */
	public function update_cache( $record ) {

		// Check if a record instance was passed.
		if ( is_object( $record ) && is_callable( array( $record, 'get_data' ) ) ) {
			$record = $record->get_data();
		}

		// Ensure we have an array.
		if ( ! is_array( $record ) ) {
			return;
		}

		foreach ( $this->get_cache_keys() as $key ) {
			wp_cache_set( $record[ $key ], $record['id'], $this->hook_prefix( 'ids_by_' . $key, true ) );
		}

		// Cache the entire record.
		wp_cache_set( $record['id'], $record, $this->get_full_name() );
	}

	/**
	 * Clean caches.
	 *
	 * @param object $record The raw db record.
	 */
	public function clear_cache( $record ) {

		foreach ( $this->get_cache_keys() as $key ) {
			wp_cache_delete( $record->$key, $this->hook_prefix( 'ids_by_' . $key, true ) );
		}

		wp_cache_delete( $record->id, $this->get_full_name() );
	}

	/**
	 * Throws a not found error.
	 *
	 * @throws Store_Exception
	 */
	protected function not_found() {

		$message = apply_filters(
			$this->hook_prefix( 'not_found_message', true ),
			sprintf(
				// Translators: %s is the resource type.
				__( '%s not found.', 'hizzle-store' ),
				$this->get_singular_name()
			)
		);

		throw new Store_Exception( $this->hook_prefix( 'not_found', true ), $message, 404 );
	}

	/**
	 * Throws an error saving error.
	 *
	 * @throws Store_Exception
	 */
	protected function not_saved() {

		$message = apply_filters(
			$this->hook_prefix( 'not_saved_message', true ),
			sprintf(
				// Translators: %s is the resource type.
				__( 'Error saving %s.', 'hizzle-store' ),
				$this->get_singular_name()
			)
		);

		throw new Store_Exception( $this->hook_prefix( 'not_saved', true ), $message, 404 );
	}

}
