<?php

namespace Hizzle\Store;

/**
 * Store API: Queries a collection of data.
 *
 * @since   1.0.0
 * @package Hizzle\Store
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Queries a collection of data.
 *
 * @since 1.0.0
 */
class Query {

	/**
	 * The collection name.
	 *
	 * @var string
	 */
	protected $collection_name;

	/**
	 * Query vars, after parsing
	 *
	 * @since 1.0.0
	 * @var array
	 */
	public $query_vars = array();

	/**
	 * The result of an aggregate query.
	 *
	 * @since 1.0.0
	 * @var int|float|array
	 */
	protected $aggregate = null;

	/**
	 * List of found object ids
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $results;

	/**
	 * Total number of found records for the current query
	 *
	 * @since 1.0.0
	 * @var int
	 */
	protected $total_results;

	/**
	 * The SQL query used to fetch matching records.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $request;

	// SQL clauses

	/**
	 * Contains the 'FIELDS' sql clause
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $query_fields;

	/**
	 * Contains the 'FROM' sql clause
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $query_from;

	/**
	 * Contains the 'LEFT JOIN' sql clause
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $query_join;

	/**
	 * Contains the 'WHERE' sql clause
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $query_where;

	/**
	 * Contains the 'GROUP BY' sql clause
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $query_groupby;

	/**
	 * Contains the 'ORDER BY' sql clause
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $query_orderby;

	/**
	 * Contains the 'LIMIT' sql clause
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $query_limit;

	/**
	 * Class constructor.
	 *
	 * @param string $collection_name The collection name.
	 * @param array  $query The actual query.
	 */
	public function __construct( $collection_name, $query = array() ) {

		$this->collection_name = $collection_name;

		// Prepare the query.
		$this->prepare_query( $query );

		// Run the query.
		if ( ! empty( $this->query_vars['aggregate'] ) ) {
			$this->query_aggregate();
		} else {
			$this->query_results();
		}

	}

	/**
	 * Retreives a query var.
	 *
	 * @param string $query_var The query var to retreive.
	 */
	public function get( $query_var ) {
		return isset( $this->query_vars[ $query_var ] ) ? $this->query_vars[ $query_var ] : null;
	}

	/**
	 * Sets a query var.
	 * 
	 * @param string $query_var The query var to set.
	 * @param mixed  $value The value to set.
	 */
	public function set( $query_var, $value ) {
		$this->query_vars[ $query_var ] = $value;
	}

	/**
	 * Prepare the query variables.
	 *
	 * Open https://yourwebsite.com/wp-json/$namespace/v1/$collection/ to see the allowed query parameters.
	 *
	 * @since 1.0.0
	 *
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 */
	public function prepare_query( $query = array() ) {
		global $wpdb;

		$collection        = Collection::instance( $this->collection_name );
		$this->query_limit = null;
		$this->query_join  = '';
		$this->query_vars  = $this->fill_query_vars( $query );

		if ( ! empty( $this->query_vars['fields'] ) && 'all' !== $this->query_vars['fields'] ) {
			$this->query_vars['fields'] = wp_parse_list( $this->query_vars['fields'] );
		}

		// Fires before preparing the query.
		do_action( $collection->hook_prefix( 'before_prepare_query' ), $this );

		// Ensure that query vars are filled after running actions.
		$qv        =& $this->query_vars;
		$qv        = $this->fill_query_vars( $qv );
		$table     = $collection->get_db_table_name();
		$aggregate = ! empty( $qv['aggregate'] );

		// Prepare the query FROM.
		$this->query_from    = "FROM $table";
		$this->query_orderby = '';
		$this->query_groupby = '';

		// Prepare joins.
		if ( $collection->is_cpt() ) {
			$this->query_join .= " LEFT JOIN {$wpdb->posts} ON $table.id = {$wpdb->posts}.ID";
		}

		// Prepare query fields.
		if ( $aggregate ) {
			$this->prepare_aggregate_query( $qv, $table );
		} else {
			$this->prepare_fields( $qv, $table );
		}

		// Set whether or not to count the total number of found records.
		if ( ! $aggregate && isset( $qv['count_total'] ) && $qv['count_total'] ) {
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;
		}

		// Prepare where query.
		$this->prepare_where_query( $qv, $table );

		// Sorting.
		if ( ! $aggregate ) {
			$this->prepare_orderby_query( $qv, $table );
		}

		// limit
		if ( isset( $qv['per_page'] ) && (int) $qv['per_page'] > 0 ) {
			if ( $qv['offset'] ) {
				$this->query_limit = $wpdb->prepare( 'LIMIT %d, %d', $qv['offset'], $qv['per_page'] );
			} else {
				$this->query_limit = $wpdb->prepare( 'LIMIT %d, %d', $qv['per_page'] * ( $qv['page'] - 1 ), $qv['per_page'] );
			}
		}

		// Fires after preparing the query.
		do_action_ref_array( $collection->hook_prefix( 'after_prepare_query' ), array( &$this ) );
	}

	/**
	 * Aggregates field data clauses.
	 *
	 * @since 1.0.0
	 * @param array $qv The query vars.
	 * @param string $table The table name.
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 */
	protected function prepare_aggregate_query( $qv, $table ) {

		$known_fields       = $this->get_known_fields();
		$aggregate_fields   = $qv['aggregate'];
		$this->query_fields = array();
		// TODO: Add support fo query_join.

		// Prepare aggregate fields.
		foreach ( $aggregate_fields as $field => $function ) {

			// Ensure the field is supported.
			$field = esc_sql( sanitize_key( $field ) );
			if ( ! in_array( $field, $known_fields, true ) ) {
				throw new Store_Exception( 'query_invalid_field', __( 'Invalid aggregate field.', 'hizzle-store' ) );
			}

			// Ensure the function is supported.
			$function_upper = strtoupper( $function );
			if ( ! in_array( $function_upper, array( 'AVG', 'COUNT', 'MAX', 'MIN', 'SUM' ), true ) ) {
				throw new Store_Exception( 'query_invalid_function', __( 'Invalid aggregate function.', 'hizzle-store' ) );
			}

			$function             = strtolower( $function );
			$table_field          = $this->prefix_field( $field );
			$this->query_fields[] = "$function_upper($table_field) AS {$function}_{$field}";

		}

		// Prepare groupby fields.
		if ( ! empty( $qv['groupby'] ) ) {
			foreach ( wp_parse_list( $qv['groupby'] ) as $field ) {

				// Ensure the field is supported.
				$field = esc_sql( sanitize_key( $field ) );
				if ( ! in_array( $field, $known_fields, true ) ) {
					throw new Store_Exception( 'query_invalid_field', __( 'Invalid group by field.', 'hizzle-store' ) );
				}

				$this->query_groupby .= ', ' . $this->prefix_field( $field );
				$this->query_fields[] = $this->prefix_field( $field );
			}

			$this->query_groupby = 'GROUP BY ' . ltrim( $this->query_groupby, ',' );
		}

		// Add extra fields.
		if ( ! empty( $qv['extra_fields'] ) ) {
			foreach ( wp_parse_list( $qv['extra_fields'] ) as $field ) {

				// Ensure the field is supported.
				$field = esc_sql( sanitize_key( $field ) );
				if ( ! in_array( $field, $known_fields, true ) ) {
					throw new Store_Exception( 'query_invalid_field', __( 'Invalid extra field.', 'hizzle-store' ) );
				}

				$this->query_fields[] = $this->prefix_field( $field );
			}
		}

		// Abort if no fields were aggregated.
		if ( empty( $this->query_fields ) ) {
			throw new Store_Exception( 'query_missing_aggregate_fields', __( 'Missing aggregate fields.', 'hizzle-store' ) );
		}

		$this->query_fields  = implode( ', ', $this->query_fields );
	}

	/**
	 * Prepares the fields for the query.
	 *
	 * @since 1.0.0
	 * @param array $qv The query vars.
	 * @param string $table The table name.
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 */
	protected function prepare_fields( $qv, $table ) {

		if ( is_array( $qv['fields'] ) ) {
			$qv['fields'] = array_unique( $qv['fields'] );

			$this->query_fields = array();
			foreach ( $qv['fields'] as $field ) {
				$field                = 'id' === strtolower( $field ) ? 'id' : sanitize_key( $field );
				$this->query_fields[] = $this->prefix_field( $field );
			}
			$this->query_fields = implode( ',', $this->query_fields );
		} elseif ( 'all' === $qv['fields'] ) {
			$this->query_fields = '*';
		} else {
			$this->query_fields = "$table.id";
		}

	}

	/**
	 * Prepares the WHERE query.
	 *
	 * @since 1.0.0
	 * @param array $qv The query vars.
	 * @param string $table The table name.
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 */
	public function prepare_where_query( $qv, $table ) {
		global $wpdb;

		$this->query_where = 'WHERE 1=1';

		// Table fields.
		foreach ( $this->get_known_fields( 'objects' ) as $key => $field ) {

			$rest_schema = $field->get_rest_schema();
			$data_type   = $field->get_data_type();
			$field_name  = $this->prefix_field( $key );

			// Normal $field = 'x' filters.
			if ( isset( $qv[ $key ] ) && 'any' !== $qv[ $key ] ) {

				if ( is_array( $qv[ $key ] ) ) {
					$enums              = "'" . implode( "','", array_map( 'esc_sql', $qv[ $key ] ) ) . "'";
					$this->query_where .= " AND $field_name IN ($enums)";
				} else {
					$this->query_where .= $wpdb->prepare( " AND $field_name=$data_type", $qv[ $key ] ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				}
			}

			// Date queries.
			if ( $field->is_date() ) {

				$date_query = array(
					'column'   => $field_name,
					'relation' => 'AND',
				);

				if ( ! empty( $qv[ "{$key}_before" ] ) ) {
					$date_query[] = array( 'before' => $qv[ "{$key}_before" ] );
				}

				if ( ! empty( $qv[ "{$key}_after" ] ) ) {
					$date_query[] = array( 'after' => $qv[ "{$key}_after" ] );
				}

				if ( ! empty( $qv[ "{$key}_query" ] ) && is_array( $qv[ "{$key}_query" ] ) ) {
					$date_query = array_merge( $date_query, $qv[ "{$key}_query" ] );
				}

				if ( 2 < count( $date_query ) ) {

					$date_query         = new \WP_Date_Query( $date_query, $this->prefix_field( $key ) );
					$this->query_where .= $date_query->get_sql();
				}
			}

			// Numbers & Floats.
			if ( $field->is_numeric() || $field->is_float() ) {

				if ( ! empty( $qv[ "{$key}_min" ] ) ) {
					$this->query_where .= $wpdb->prepare( " AND $field_name >= $data_type", $qv[ "{$key}_min" ] ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				}

				if ( ! empty( $qv[ "{$key}_max" ] ) ) {
					$this->query_where .= $wpdb->prepare( " AND $field_name <= $data_type", $qv[ "{$key}_max" ] ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				}
			}
		}

		// Search.
		$search = '';
		if ( isset( $qv['search'] ) ) {
			$search = trim( $qv['search'] );
		}

		if ( $search ) {
			trim( $search, '*' );

			$search_columns = array();
			$known_fields   = $this->get_known_fields();
			if ( $qv['search_columns'] ) {
				$search_columns = array_intersect( $qv['search_columns'], $known_fields );
			}
			if ( ! $search_columns ) {
				$search_columns = $known_fields;
			}

			$this->query_where .= $this->get_search_sql( $search, $search_columns, $table );
		}

		// Include.
		if ( ! empty( $qv['include'] ) ) {
			$ids                = implode( ',', wp_parse_id_list( $qv['include'] ) );
			$this->query_where .= " AND $table.id IN ($ids)";
		} elseif ( ! empty( $qv['exclude'] ) ) {
			$ids                = implode( ',', wp_parse_id_list( $qv['exclude'] ) );
			$this->query_where .= " AND $table.id NOT IN ($ids)";
		}
	}

	/**
	 * Used internally to generate an SQL string for searching across multiple columns
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string $string The string to search for.
	 * @param array  $cols The columns to search in.
	 * @param string $table_name The table name.
	 * @return string
	 */
	protected function get_search_sql( $string, $cols, $table_name ) {
		global $wpdb;

		$searches = array();
		$string   = trim( $string, '%' );
		$like     = '%' . $wpdb->esc_like( $string ) . '%';

		foreach ( $cols as $col ) {
			if ( 'id' === $col ) {
				$searches[] = $wpdb->prepare( "$table_name.$col = %s", $string ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			} else {
				$searches[] = $wpdb->prepare( $this->prefix_field( $col ) . " LIKE %s", $like ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			}
		}

		return ' AND (' . implode( ' OR ', $searches ) . ')';
	}

	/**
	 * Prepares the ORDER BY query.
	 *
	 * @since 1.0.0
	 * @param array $qv The query vars.
	 * @param string $table The table name.
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 */
	public function prepare_orderby_query( $qv, $table ) {
		$qv['order'] = isset( $qv['order'] ) ? strtoupper( $qv['order'] ) : '';
		$order       = $this->parse_order( $qv['order'] );

		if ( empty( $qv['orderby'] ) ) {
			// Default order is by 'id'.
			$ordersby = array( 'id' );
		} else {
			// 'orderby' values may be a comma- or space-separated list.
			$ordersby = wp_parse_list( $qv['orderby'] );
		}

		$orderby_array = array();
		foreach ( $ordersby as $_key => $_value ) {
			if ( ! $_value ) {
				continue;
			}

			if ( is_int( $_key ) ) {
				// Integer key means this is a flat array of 'orderby' fields.
				$_orderby = $_value;
				$_order   = $order;
			} else {
				// Non-integer key means this the key is the field and the value is ASC/DESC.
				$_orderby = $_key;
				$_order   = $_value;
			}

			$parsed = $this->parse_orderby( $_orderby, $table );

			if ( ! $parsed ) {
				continue;
			}

			$orderby_array[] = $parsed . ' ' . $this->parse_order( $_order );
		}

		// If no valid clauses were found, order by ID.
		if ( empty( $orderby_array ) ) {
			$orderby_array[] = "id $order";
		}

		$this->query_orderby = 'ORDER BY ' . implode( ', ', $orderby_array );
	}

	/**
	 * Parse and sanitize 'orderby' keys passed to the query.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string $orderby Alias for the field to order by.
	 * @param string $table The table name.
	 * @return string Value to use in the ORDER clause, if `$orderby` is valid.
	 */
	protected function parse_orderby( $orderby, $table ) {

		$_orderby = '';
		if ( in_array( $orderby, $this->get_known_fields(), true ) ) {
			$_orderby = $this->prefix_field( $orderby );
		} elseif ( 'id' === strtolower( $orderby ) ) {
			$_orderby = "$table.id";
		} elseif ( 'include' === $orderby && ! empty( $this->query_vars['include'] ) ) {
			$include     = wp_parse_id_list( $this->query_vars['include'] );
			$include_sql = implode( ',', $include );
			$_orderby    = "FIELD( $table.id, $include_sql )";
		}

		return $_orderby;
	}

	/**
	 * Parse an 'order' query variable and cast it to ASC or DESC as necessary.
	 *
	 * @since 1.0.0
	 *
	 * @param string $order The 'order' query variable.
	 * @return string The sanitized 'order' query variable.
	 */
	protected function parse_order( $order ) {
		if ( ! is_string( $order ) || empty( $order ) ) {
			return 'DESC';
		}

		if ( 'ASC' === strtoupper( $order ) ) {
			return 'ASC';
		}

		return 'DESC';
	}

	/**
	 * Fills in missing query variables with default values.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Query vars.
	 * @return array Complete query variables with undefined ones filled in with defaults.
	 */
	public function fill_query_vars( $args ) {
		$defaults   = array(
			'include'        => array(),
			'exclude'        => array(),
			'search'         => '',
			'search_columns' => $this->get_known_fields(),
			'orderby'        => array( 'id' ),
			'order'          => 'DESC',
			'offset'         => '',
			'per_page'       => -1,
			'page'           => 1,
			'count_total'    => true,
			'fields'         => 'all',
			'aggregate'      => false, // pass an array of property_name and function to aggregate the results.
		);

		if ( isset( $args['number'] ) ) {
			$args['per_page'] = $args['number'];
		}

		return wp_parse_args( $args, $defaults );
	}

	/**
	 * Retrieves an array of all known fields.
	 *
	 * @since 1.0.0
	 *
	 * @return string[]|Prop[]
	 */
	public function get_known_fields( $return = 'names' ) {
		$collection = Collection::instance( $this->collection_name );

		return 'names' === $return ? array_keys( $collection->get_props() ) : $collection->get_props();
	}

	/**
	 * Prefixes a field name with the table name.
	 *
	 * @since 1.0.0
	 */
	public function prefix_field( $field ) {
		$collection = Collection::instance( $this->collection_name );

		if ( $this->is_post_field( $collection, $field ) ) {
			$table = $GLOBALS['wpdb']->posts;
			$field = $collection->post_map[ $field ];
		} else {
			$table = $collection->get_db_table_name();
		}

		return $table . '.' . $field;
	}

	/**
	 * Checks if a given field is a post field.
	 *
	 * @since 1.0.0
	 * @param Collection $collection The collection.
	 * @param string $field The field name.
	 * @return bool True if the field is a post field, false otherwise.
	 */
	public function is_post_field( $collection, $field ) {
		return $collection->is_cpt() && isset( $collection->post_map[ $field ] );
	}

	/**
	 * Retrieves the query results.
	 *
	 * @return array[]|int[]|Record[]
	 */
	public function get_results() {
		return $this->results;
	}

	/**
	 * Return the total number of records for the current query.
	 *
	 * @since 1.0.0
	 *
	 * @return int Number of total records.
	 */
	public function get_total() {
		return $this->total_results;
	}

	/**
	 * Runs the query.
	 *
	 */
	protected function query_results() {
		global $wpdb;

		$this->total_results = 0;

		// Allow third party plugins to modify the query.
		$collection    = Collection::instance( $this->collection_name );
		$this->results = apply_filters_ref_array( $collection->hook_prefix( 'pre_query' ), array( null, &$this ) );

		// Run query if it was not short-circuted.
		if ( null === $this->results ) {
			$this->request = "SELECT $this->query_fields $this->query_from $this->query_where $this->query_orderby $this->query_limit";

			if ( ( is_array( $this->query_vars['fields'] ) && 1 !== count( $this->query_vars['fields'] ) ) || 'all' === $this->query_vars['fields'] ) {
				$this->results = $wpdb->get_results( $this->request ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			} else {
				$this->results = $wpdb->get_col( $this->request ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}

			if ( isset( $this->query_vars['count_total'] ) && $this->query_vars['count_total'] ) {
				$this->total_results = (int) $wpdb->get_var( 'SELECT FOUND_ROWS()' );
			}
		}

		// Maybe init objects.
		if ( $this->results && 'all' === $this->query_vars['fields'] ) {
			foreach ( $this->results as $key => $result ) {

				if ( isset( $result->id ) ) {
					// Cache object.
					$collection->update_cache( (array) $result );

					// Replace raw data with Record objects.
					$this->results[ $key ] = $collection->get( $result->id );
				}
			}
		}
	}

	/**
	 * Retrieves the aggregate results.
	 *
	 * @return int|float|array
	 */
	public function get_aggregate() {
		return $this->aggregate;
	}

	/**
	 * RUns an aggregate query.
	 *
	 */
	protected function query_aggregate() {
		global $wpdb;

		// Allow third party plugins to modify the query.
		$collection      = Collection::instance( $this->collection_name );
		$this->aggregate = apply_filters_ref_array( $collection->hook_prefix( 'pre_aggregate_query' ), array( null, &$this ) );

		// Run query if it was not short-circuted.
		if ( null === $this->aggregate ) {
			$this->request   = "SELECT $this->query_fields $this->query_from $this->query_where $this->query_groupby $this->query_limit";
			$this->aggregate = $wpdb->get_results( $this->request ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

	}

}
