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
	 * Custom meta table.
	 *
	 * @var bool
	 */
	protected $use_meta_table = false;

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
	 * Known fields.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $known_fields;

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
	 * The collection labels.
	 *
	 * @var array
	 */
	public $labels = array();

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
		global $wpdb;

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

		// Register our custom meta table.
		if ( $this->create_meta_table() ) {
			$meta_type          = $this->get_meta_type() . 'meta';
			$wpdb->{$meta_type} = $this->get_meta_table_name();
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
	 * Retrieves the meta type.
	 *
	 * @return string
	 */
	public function get_meta_type() {
		$meta_type = $this->is_cpt() ? 'post' : $this->get_full_name( true );
		return apply_filters( $this->hook_prefix( 'meta_type' ), $meta_type );
	}

	/**
	 * Retrieves the database name.
	 *
	 * @return string
	 */
	public function get_db_table_name() {
		global $wpdb;

		$db_table = $wpdb->prefix . $this->get_full_name();
		return apply_filters( $this->hook_prefix( 'db_table_name' ), $db_table );
	}

	/**
	 * Retrieves the meta table name.
	 *
	 * @return string
	 */
	public function get_meta_table_name() {
		global $wpdb;

		if ( $this->is_cpt() ) {
			return $wpdb->postmeta;
		}

		return $wpdb->prefix . $this->get_meta_type() . '_meta';
	}

	/**
	 * Checks if we should create a custom meta table.
	 *
	 * @return bool
	 */
	public function create_meta_table() {
		return $this->use_meta_table && ! $this->is_cpt();
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
	 * Retrieves known fields.
	 *
	 * @return array
	 */
	public function get_known_fields() {

		if ( ! empty( $this->known_fields ) ) {
			return $this->known_fields;
		}

		$this->known_fields = array(
			'main'    => array(),
			'post'    => array(),
			'meta'    => array(),
			'dynamic' => array(),
		);

		foreach ( $this->get_props() as $prop ) {

			// Dynamic properties.
			if ( $prop->is_dynamic ) {
				$this->known_fields['dynamic'][] = $prop->name;
				continue;
			}

			// Meta keys.
			if ( $prop->is_meta_key && ( $this->is_cpt() || $this->use_meta_table ) ) {
				$this->known_fields['meta'][] = $prop->name;
				continue;
			}

			// CPT fields.
			if ( $this->is_cpt() && in_array( $prop->name, $this->post_map, true ) ) {
				$this->known_fields['post'][] = $prop->name;
				continue;
			}

			// Main fields.
			$this->known_fields['main'][] = $prop->name;
		}

		return $this->known_fields;
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

		$table    = $this->get_db_table_name();
		$schema   = "CREATE TABLE $table (\n";
		$has_prop = false;

		// Add each property.
		foreach ( $this->get_props() as $key => $prop ) {

			// Do not add CPT fields to the schema.
			if ( ! $this->is_cpt() || ! in_array( $key, $this->post_map, true ) ) {
				$prop_schema = $prop->get_schema();

				if ( ! empty( $prop_schema ) ) {
					$schema  .= $prop_schema . ",\n";
					$has_prop = true;
				}
			}
		}

		// Abort if no props were added.
		if ( ! $has_prop ) {
			return '';
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
	 * Retrieves the meta schema.
	 *
	 * @return string
	 */
	public function get_meta_schema() {
		global $wpdb;

		// Abort if we're not using a custom meta table.
		if ( ! $this->create_meta_table() ) {
			return '';
		}

		// Get character collation.
		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$table  = $this->get_meta_table_name();
		$id_col = $this->get_meta_type() . '_id';

		return "CREATE TABLE $table (
			meta_id bigint(20) unsigned NOT NULL auto_increment,
			$id_col bigint(20) unsigned NOT NULL default '0',
			meta_key varchar(255) default NULL,
			meta_value longtext,
			PRIMARY KEY  (meta_id),
			KEY $id_col ($id_col),
			KEY meta_key (meta_key(191))
		) $collate;";
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

			if ( $prop->is_meta_key && $prop->is_meta_key_multiple ) {
				$schema['properties'][ $prop->name . '::add' ] = array(
					'description' => sprintf(
						/* translators: %s: field label */
						__( 'Add %s', 'hizzle-store' ),
						strtolower( $prop->description )
					),
					'type'        => 'array',
					'context'     => array( 'edit' ),
				);

				$schema['properties'][ $prop->name . '::remove' ] = array(
					'description' => sprintf(
						/* translators: %s: field label */
						__( 'Remove %s', 'hizzle-store' ),
						strtolower( $prop->description )
					),
					'type'        => 'array',
					'context'     => array( 'edit' ),
				);
			}
		}

		$schema['properties'][ $prop->name ] = array_filter( $schema['properties'][ $prop->name ] );

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

		$query_schema = array();

		$query_schema['paged'] = array(
			'description' => __( 'Current page of the collection.', 'hizzle-store' ),
			'type'        => 'integer',
		);

		$query_schema['per_page'] = array(
			'description'       => __( 'Maximum number of items to be returned in result set.', 'hizzle-store' ),
			'type'              => 'integer',
			'default'           => 25,
			'minimum'           => -1,
			'maximum'           => 100,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$query_schema['offset'] = array(
			'description'       => __( 'Offset the result set by a specific number of items.', 'hizzle-store' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$query_schema['search'] = array(
			'description'       => __( 'Limit results to those matching a string.', 'hizzle-store' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$query_schema['search_columns'] = array(
			'description'       => __( 'An array of props to search in.', 'hizzle-store' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'string',
			),
			'validate_callback' => 'rest_validate_request_arg',
			'default'           => array(),
		);

		$query_schema['exclude'] = array(
			'description'       => __( 'Ensure result set excludes specific IDs.', 'hizzle-store' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'default'           => array(),
			'sanitize_callback' => 'wp_parse_id_list',
		);

		$query_schema['include'] = array(
			'description'       => __( 'Limit result set to specific ids.', 'hizzle-store' ),
			'type'              => array( 'array' ),
			'items'             => array(
				'type' => 'integer',
			),
			'default'           => array(),
			'sanitize_callback' => 'wp_parse_id_list',
		);

		// Add each prop.
		foreach ( $this->props as $prop ) {
			if ( 'id' !== $prop->name ) {
				$query_schema = array_merge( $query_schema, $prop->get_query_schema() );
			}
		}

		$query_schema['order']           = array(
			'description'       => __( 'Order sort attribute ascending or descending.', 'hizzle-store' ),
			'type'              => 'string',
			'default'           => 'desc',
			'enum'              => array( 'asc', 'desc' ),
			'validate_callback' => 'rest_validate_request_arg',
		);

		$all_fields = $this->get_known_fields();

		$query_schema['orderby']         = array(
			'description'       => __( 'Sort collection by object attribute.', 'hizzle-store' ),
			'type'              => 'string',
			'default'           => 'id',
			'items'             => array(
				'type' => 'string',
			),
			'enum'              => array_merge( $all_fields['main'], $all_fields['post'], array( 'id', 'include' ) ),
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

		if ( empty( $value ) || ! in_array( $prop, $this->get_cache_keys(), true ) ) {
			return false;
		}

		// Try the cache.
		$value = trim( $value );
		$id    = wp_cache_get( $value, $this->hook_prefix( 'ids_by_' . $prop, true ) );

		// Maybe retrieve from the db.
		if ( false === $id ) {

			// Fetch the ID.
			$table = $this->get_db_table_name();
			$id    = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE $prop = %s LIMIT 1", $value ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$id    = empty( $id ) ? 0 : (int) $id;

			// Update the cache.
			wp_cache_set( $value, $id, $this->hook_prefix( 'ids_by_' . $prop, true ) );
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
		$prepared     = array();
		$known_fields = $this->get_known_fields();

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

			// Handle arrays, except for meta keys.
			if ( ! in_array( $key, $known_fields['meta'], true ) ) {
				$value = maybe_serialize( $value );
			}

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

		// Fires before creating a record.
		do_action( $this->hook_prefix( 'before_create', true ), $record );

		$data = $record->get_data( 'edit' );

		// Save date created in UTC time.
		if ( ! $this->is_cpt() && array_key_exists( 'date_created', $data ) && empty( $data['date_created'] ) ) {
			$data['date_created'] = new Date_Time( 'now', new \DateTimeZone( 'UTC' ) );
		}

		// Save date modified in UTC time.
		if ( ! $this->is_cpt() && array_key_exists( 'date_modified', $data ) ) {
			$data['date_modified'] = new Date_Time( 'now', new \DateTimeZone( 'UTC' ) );
		}

		$data = $this->prepare_data( $data );

		// Is this a custom post type?
		$record_id = $this->save_post( $record, $data );

		if ( empty( $record_id ) && ! empty( $record->create_with_id ) ) {
			$record_id = $record->create_with_id;
		}

		// Save custom data.
		$record_id = $this->save_custom( $record, $data, $record_id );

		// Abort if the record ID is empty.
		if ( empty( $record_id ) ) {
			$this->not_saved();
		}

		// Set the record ID.
		$record->set_id( $record_id );

		// Save meta data.
		$this->save_meta( $record, $data );

		// Apply changes.
		$record->apply_changes();

		// Clear the cache.
		$this->clear_cache( $record->get_data() );

		// Fires after creating a record.
		do_action( $this->hook_prefix( 'created', true ), $record );

		return true;
	}

	/**
	 * Saves custom data for a record to the database.
	 *
	 * @param Record $record
	 * @param array  $data
	 * @param int $record_id
	 * @return int
	 */
	protected function save_custom( $record, $data, $record_id = 0 ) {
		global $wpdb;

		// Fires before saving a record.
		do_action( $this->hook_prefix( 'before_save_custom', true ), $record );

		$all_fields = $this->get_known_fields();
		$fields     = array();
		$formats    = array();

		// Save meta data.
		foreach ( $this->props as $key => $prop ) {

			// Skip non-main fields...
			if ( ! in_array( $prop->name, $all_fields['main'], true ) ) {
				continue;
			}

			// ... OR ID field.
			if ( 'id' === $prop->name ) {
				continue;
			}

			// ... or fields that are already saved.
			if ( array_key_exists( $key, $data ) ) {
				$fields[ $key ] = $data[ $key ];
				$formats[]      = $prop->get_data_type();
			}
		}

		// Abort if no fields to save.
		if ( empty( $fields ) ) {
			return $record->exists() ? $record->get_id() : $record_id;
		}

		// Creating a new record?
		if ( ! $record->exists() ) {

			// Creating with an ID?
			if ( ! empty( $record_id ) ) {
				$fields['id'] = (int) $record_id;
				$formats[]    = '%d';
			}

			$result = $wpdb->insert(
				$this->get_db_table_name(),
				apply_filters( $this->hook_prefix( 'insert_data', true ), $fields, $record ),
				apply_filters( $this->hook_prefix( 'insert_formats', true ), $formats, $record )
			);

			return $result ? $wpdb->insert_id : 0;
		}

		// Update the record.
		$result = $wpdb->update(
			$this->get_db_table_name(),
			apply_filters( $this->hook_prefix( 'update_data', true ), $fields, $record ),
			array( 'id' => $record->get_id() ),
			apply_filters( $this->hook_prefix( 'update_formats', true ), $formats, $record ),
			array( '%d' )
		);

		return $record->get_id();
	}

	/**
	 * Saves post data for a record from the database.
	 *
	 * @param Record $record
	 * @param array  $data
	 * @return int
	 */
	protected function save_post( $record, $data ) {

		// Abort if not a CPT.
		if ( ! $this->is_cpt() ) {
			return $record->get_id();
		}

		// Fires before saving a record.
		do_action( $this->hook_prefix( 'before_save_post', true ), $record );

		$post_data = array();

		foreach ( $this->post_map as $key => $post_field ) {
			if ( array_key_exists( $key, $data ) ) {
				$post_data[ $post_field ] = $data[ $key ];
			}
		}

		if ( $record->exists() ) {

			// Update the post.
			if ( ! empty( $post_data ) ) {
				$post_data['ID'] = $record->get_id();
				wp_update_post( $post_data );
			}

			return $record->get_id();
		}

		$post_data['post_type'] = $this->post_type;

		// Create the post.
		$post_id = wp_insert_post( $post_data, true );

		// Abort if the post was not created.
		if ( is_wp_error( $post_id ) ) {
			throw new Store_Exception( $post_id->get_error_code(), $post_id->get_error_message() );
		}

		if ( empty( $post_id ) ) {
			$this->not_saved();
		}

		return $post_id;
	}

	/**
	 * Saves meta data for a record to the database.
	 *
	 * @param Record $record
	 * @param array $data
	 */
	protected function save_meta( $record, $data ) {

		// Abort if no meta table.
		if ( ! $this->use_meta_table && ! $this->is_cpt() ) {
			return;
		}

		// Fires before saving a record's meta.
		do_action( $this->hook_prefix( 'before_save_meta', true ), $record );

		// Meta is not cached with normal data as WP has its own caching.
		foreach ( $this->props as $prop ) {

			// Abort if not a meta key.
			if ( ! $prop->is_meta_key || ! array_key_exists( $prop->name, $data ) ) {
				continue;
			}

			$current = $this->get_record_meta( $record->get_id(), $prop->name, ! $prop->is_meta_key_multiple );
			$new     = $data[ $prop->name ];
			$new     = is_null( $new ) ? '' : $new;

			if ( $prop->is_meta_key_multiple ) {

				$new       = (array) $new;
				$to_delete = array_diff( $current, $new );
				$to_create = array_diff( $new, $current );

				// Add new meta.
				foreach ( $to_create as $value ) {
					if ( ! is_null( $value ) && '' !== $value ) {
						$this->add_record_meta( $record->get_id(), $prop->name, $value );
					}
				}

				// Delete old meta.
				foreach ( $to_delete as $value ) {
					$this->delete_record_meta( $record->get_id(), $prop->name, $value );
				}
			} else {

				// If the value is empty, delete the meta.
				if ( '' === $new ) {
					$this->delete_record_meta( $record->get_id(), $prop->name );
					continue;
				}

				// If the value is different, update the meta.
				if ( $current !== $new ) {
					$this->update_record_meta( $record->get_id(), $prop->name, $new );
				}
			}
		}
	}

	/**
	 * Reads a record from the database or cache.
	 *
	 * @param Record $record
	 * @throws Store_Exception
	 */
	public function read( &$record ) {

		// Fires before reading a record.
		do_action( $this->hook_prefix( 'before_read', true ), $record );

		$data = array_merge(
			$this->read_custom( $record ),
			$this->read_post( $record ),
			$this->read_meta( $record )
		);

		// Format the raw data.
		foreach ( $this->props as $prop ) {

			// Dates are stored in UTC time, but without a timezone.
			// Fix that.
			if ( $prop->is_date() && ! empty( $data[ $prop->name ] ) ) {
				$data[ $prop->name ] = new Date_Time( $data[ $prop->name ], new \DateTimeZone( 'UTC' ) );
			}
		}

		// Set the record data.
		$data = apply_filters( $this->hook_prefix( 'read_data' ), $data, $record );
		$record->set_props( $data );
	}

	/**
	 * Reads custom data for a record from the database.
	 *
	 * @param Record $record
	 * @return array
	 */
	protected function read_custom( $record ) {
		global $wpdb;

		// Fires before reading a record.
		do_action( $this->hook_prefix( 'before_read_custom', true ), $record );

		// Maybe fetch from cache.
		$data = wp_cache_get( $record->get_id(), $this->get_full_name() );

		// If not found, read from the db.
		if ( false === $data ) {

			// Include meta data.
			$table_name = $this->get_db_table_name();
			$raw_data   = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $record->get_id() ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				ARRAY_A
			);

			// In case of auto-drafts etc.
			if ( empty( $raw_data ) && $this->is_cpt() ) {

				// Ensure there is always a record to avoid any errors.
				$this->save_defaults( $record );
				$raw_data = $record->get_data();

			} elseif ( empty( $raw_data ) ) {
				$this->not_found();
			}

			$data       = array();
			$all_fields = $this->get_known_fields();

			foreach ( $all_fields['main'] as $key ) {
				if ( array_key_exists( $key, $raw_data ) ) {
					$data[ $key ] = $raw_data[ $key ];
				}
			}

			// Cache the record data.
			$this->update_cache( $data );

		}

		return empty( $data ) ? array() : (array) $data;
	}

	/**
	 * Reads post data for a record from the database.
	 *
	 * @param Record $record
	 * @return array
	 */
	protected function read_post( $record ) {

		// Abort if not a CPT.
		if ( ! $this->is_cpt() ) {
			return array();
		}

		// Fires before reading a record.
		do_action( $this->hook_prefix( 'before_read_post', true ), $record );

		$post = get_post( $record->get_id() );
		$data = array();

		if ( empty( $post ) || $this->post_type !== $post->post_type ) {
			$this->not_found();
		}

		// Post data is not cached with normal data as WP has its own caching.
		foreach ( $this->post_map as $key => $post_field ) {
			$data[ $key ] = $post->$post_field;
		}

		return $data;
	}

	/**
	 * Reads meta data for a record from the database.
	 *
	 * @param Record $record
	 * @return array
	 */
	protected function read_meta( $record ) {

		// Abort if no meta table.
		if ( ! $this->use_meta_table && ! $this->is_cpt() ) {
			return array();
		}

		// Fires before reading a record.
		do_action( $this->hook_prefix( 'before_read_meta', true ), $record );

		$meta = array();

		// Meta is not cached with normal data as WP has its own caching.
		foreach ( $this->props as $prop ) {

			// Abort if not a meta key.
			if ( ! $prop->is_meta_key ) {
				continue;
			}

			$meta[ $prop->name ] = $this->get_record_meta( $record->get_id(), $prop->name, ! $prop->is_meta_key_multiple );
		}

		return $meta;
	}

	/**
	 * Updates a record in the database.
	 *
	 * @param Record $record The record to update.
	 * @throws Store_Exception If an error occurs.
	 */
	public function update( &$record ) {

		// Fires before updating a record.
		do_action( $this->hook_prefix( 'before_update', true ), $record );

		$raw_changes = array_keys( $record->get_changes() );
		$changes     = array();

		foreach ( $raw_changes as $key ) {
			$changes[ $key ] = $record->get( $key, 'edit' );
		}

		// Update meta data.
		// Save date modified in UTC time.
		if ( ! empty( $changes ) && ! $this->is_cpt() && isset( $this->props['date_modified'] ) ) {
			$changes['date_modified'] = new Date_Time( 'now', new \DateTimeZone( 'UTC' ) );
			$record->set( 'date_modified', $changes['date_modified'] );
		}

		$changes = $this->prepare_data( $changes );

		// Is this a custom post type?
		$this->save_post( $record, $changes );

		// Save custom data.
		if ( 0 === $this->save_custom( $record, $changes, $record->get_id() ) ) {
			$this->not_saved();
		}

		// Save meta data.
		$this->save_meta( $record, $changes );

		// Apply changes.
		$record->apply_changes();

		// Clear the cache.
		$this->clear_cache( $record->get_data() );

		// Fires after creating a record.
		do_action( $this->hook_prefix( 'updated', true ), $record );

		return true;
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
		$this->clear_cache( $record->get_data() );

		// If this is a CPT, delete the post.
		if ( $this->is_cpt() ) {
			wp_delete_post( $record->get_id(), $delete_permanently );

			if ( $delete_permanently ) {
				$this->delete_all_record_meta( $record->get_id() );
			}
		}

		// Delete the record from the database.
		if ( ! $this->is_cpt() || $delete_permanently ) {

			// Delete the record.
			$wpdb->delete( $this->get_db_table_name(), array( 'id' => $record->get_id() ), array( '%d' ) );

			// Delete meta data.
			if ( $this->use_meta_table ) {
				$this->delete_all_record_meta( $record->get_id() );
			}
		}

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

		// Fetch matching records.
		$query = $this->query( $where );

		// Delete each record individually.
		foreach ( $query->get_results() as $record ) {
			$record->delete();
		}

		// Truncase the tables if there are no more records.
		$main_table = esc_sql( $this->get_db_table_name() );
		$has_record = $wpdb->get_var( "SELECT id FROM $main_table LIMIT 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! $has_record ) {
			$wpdb->query( "TRUNCATE TABLE $main_table" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			if ( $this->create_meta_table() ) {
				$meta_table = $this->get_meta_table_name();
				$wpdb->query( "TRUNCATE TABLE $meta_table" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			}
		}

		return true;
	}

	/**
	 * Deletes all objects.
	 */
	public function delete_all() {
		return $this->delete_where( array() );
	}

	/**
	 * Retrieve record meta field for a record.
	 *
	 * @param   int    $record_id  Record ID.
	 * @param   string $meta_key   The meta key to retrieve. By default, returns data for all keys.
	 * @param   bool   $single     If true, returns only the first value for the specified meta key. This parameter has no effect if $key is not specified.
	 * @return  mixed              Will be an array if $single is false. Will be value of meta data field if $single is true.
	 * @access  public
	 * @since   1.0.0
	 */
	public function get_record_meta( $record_id, $meta_key = '', $single = false ) {
		return get_metadata( $this->get_meta_type(), $record_id, $meta_key, $single );
	}

	/**
	 * Adds record meta field for a record.
	 *
	 * @param   int    $record_id  Record ID.
	 * @param   string $meta_key   The meta key to update.
	 * @param   mixed  $meta_value Metadata value. Must be serializable if non-scalar.
	 * @param   mixed  $unique     Whether the same key should not be added.
	 * @return  int|false  Meta ID on success, false on failure.
	 * @access  public
	 * @since   1.0.0
	 */
	public function add_record_meta( $record_id, $meta_key, $meta_value, $unique = false ) {
		return add_metadata( $this->get_meta_type(), $record_id, $meta_key, $meta_value, $unique );
	}

	/**
	 * Deletes a record meta field for the given record ID.
	 *
	 * You can match based on the key, or key and value. Removing based on key and value, will keep from removing duplicate metadata with the same key. It also allows removing all metadata matching the key, if needed.
	 *
	 * @param   int    $record_id  Record ID.
	 * @param   string $meta_key   The meta key to delete.
	 * @param   mixed  $meta_value Metadata value. Must be serializable if non-scalar.
	 * @return  bool  True on success, false on failure.
	 * @access  public
	 * @since   1.0.0
	 */
	public function delete_record_meta( $record_id, $meta_key, $meta_value = '' ) {
		return delete_metadata( $this->get_meta_type(), $record_id, $meta_key, $meta_value );
	}

	/**
	 * Deletes all record meta fields for the given record ID.
	 *
	 * @param   int $record_id  Record ID.
	 * @access  public
	 * @since   1.0.0
	 */
	public function delete_all_record_meta( $record_id ) {
		$all_meta = array_keys( $this->get_record_meta( $record_id ) );

		foreach ( $all_meta as $meta_key ) {
			$this->delete_record_meta( $record_id, $meta_key );
		}
	}

	/**
	 * Deletes all record meta fields for the given meta key.
	 *
	 * This function selects all records with the given meta key, then deletes the meta key.
	 * It would be faster to delete the meta key directly, but this function ensures that
	 * caches are cleared for each record.
	 *
	 * @param   string $meta_key  Meta key.
	 * @access  public
	 */
	public function delete_all_meta( $meta_key ) {
		global $wpdb;

		$meta_table = $this->get_meta_table_name();

		// Select all records with the given meta key.
		$id_col     = $this->get_meta_type() . '_id';
		$record_ids = $wpdb->get_col( $wpdb->prepare( "SELECT $id_col FROM $meta_table WHERE meta_key = %s", $meta_key ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Delete all meta for each record.
		foreach ( $record_ids as $record_id ) {
			$this->delete_record_meta( $record_id, $meta_key );
		}

		return true;
	}

	/**
	 * Fetches all record meta values for the given meta key.
	 *
	 * @param   string $meta_key  Meta key.
	 * @access  public
	 */
	public function get_all_meta( $meta_key ) {
		global $wpdb;

		$meta_table = $this->get_meta_table_name();

		return $wpdb->get_col(
			$wpdb->prepare( "SELECT DISTINCT meta_value FROM $meta_table WHERE meta_key = %s", $meta_key ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	/**
	 * Determines if a meta field with the given key exists for the given record ID.
	 *
	 * @param int    $record_id  ID of the record metadata is for.
	 * @param string $meta_key       Metadata key.
	 *
	 */
	public function record_meta_exists( $record_id, $meta_key ) {
		return metadata_exists( $this->get_meta_type(), $record_id, $meta_key );
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
	 * @return  mixed  The new meta field ID if a field with the given key didn't exist and was therefore added, true on successful update, false on failure.
	 * @access  public
	 * @since   1.0.0
	 */
	public function update_record_meta( $record_id, $meta_key, $meta_value, $prev_value = '' ) {
		return update_metadata( $this->get_meta_type(), $record_id, $meta_key, $meta_value, $prev_value );
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
			wp_cache_set( $record[ $key ], $record['id'], $this->hook_prefix( 'ids_by_' . $key, true ), WEEK_IN_SECONDS );
		}

		// Cache the entire record.
		wp_cache_set( $record['id'], $record, $this->get_full_name(), DAY_IN_SECONDS );
	}

	/**
	 * Clean caches.
	 *
	 * @param object $record The raw db record.
	 */
	public function clear_cache( $record ) {

		$record = (object) $record;

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
				$this->get_label( 'singular_name', $this->get_singular_name() )
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
		global $wpdb;

		// Fetch last db error.
		$db_error = $wpdb->last_error;
		$message  = apply_filters(
			$this->hook_prefix( 'not_saved_message', true ),
			sprintf(
				// Translators: %s is the resource type.
				__( 'Error saving %1$s: %2$s.', 'hizzle-store' ),
				$this->get_label( 'singular_name', $this->get_singular_name() ),
				empty( $db_error ) ? __( 'Unknown error', 'hizzle-store' ) : $db_error
			)
		);

		throw new Store_Exception( $this->hook_prefix( 'not_saved', true ), $message, 404 );
	}

	/**
	 * Retrieves a label.
	 *
	 * @param string $key The label key.
	 * @param string $default The default label.
	 */
	public function get_label( $key, $default ) {
		return isset( $this->labels[ $key ] ) ? $this->labels[ $key ] : $default;
	}
}
