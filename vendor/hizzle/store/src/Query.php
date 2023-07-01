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
	 * The field to count.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $count_field;

	/**
	 * The SQL query used to fetch matching records.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	public $request;

	/**
	 * Known fields.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	protected $known_fields;

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
	 * Contains the 'LEFT|Inner JOIN' sql clause
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

		// If any JOINs are LEFT JOINs, then all JOINs should be LEFT.
		if ( false !== strpos( $this->query_join, 'LEFT JOIN' ) ) {
			$this->query_join = str_replace( 'INNER JOIN', 'LEFT JOIN', $this->query_join );
		}

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
	 * Retrieves the collection.
	 *
	 * @return Collection
	 */
	public function get_collection() {
		return Collection::instance( $this->collection_name );
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

		// Prepare known fields.
		$this->prepare_known_fields();

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
		$this->query_join    = '';

		// Prepare joins.
		if ( $collection->is_cpt() ) {
			$this->query_join .= " INNER JOIN {$wpdb->posts} ON $table.id = {$wpdb->posts}.ID";
		}

		// Prepare query fields.
		if ( $aggregate ) {
			$this->prepare_aggregate_query( $qv );
		} else {
			$this->prepare_fields( $qv, $table );
		}

		// Prepare where query.
		$this->prepare_where_query( $qv, $table );

		// Prepare meta query. After WHERE and JOINs have been prepared.
		$this->prepare_meta_query( $qv, $table );

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
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 */
	protected function prepare_aggregate_query( $qv ) {
		$aggregate_fields   = $qv['aggregate'];
		$this->query_fields = array();

		// Prepare aggregate fields.
		foreach ( $aggregate_fields as $field => $function ) {

			// Ensure the field is supported.
			$field       = esc_sql( sanitize_key( $field ) );
			$table_field = $this->prefix_field( $field );

			if ( empty( $table_field ) ) {
				throw new Store_Exception( 'query_invalid_field', __( 'Invalid aggregate field.', 'hizzle-store' ) );
			}

			// Ensure the function is supported.
			$function_upper = strtoupper( $function );
			if ( ! in_array( $function_upper, array( 'AVG', 'COUNT', 'MAX', 'MIN', 'SUM' ), true ) ) {
				throw new Store_Exception( 'query_invalid_function', __( 'Invalid aggregate function.', 'hizzle-store' ) );
			}

			$function             = strtolower( $function );
			$this->query_fields[] = "$function_upper($table_field) AS {$function}_{$field}";

		}

		// Prepare groupby fields.
		if ( ! empty( $qv['groupby'] ) ) {
			foreach ( wp_parse_list( $qv['groupby'] ) as $field ) {

				// Ensure the field is supported.
				$field       = esc_sql( sanitize_key( $field ) );
				$table_field = $this->prefix_field( $field );
				if ( empty( $table_field ) ) {
					throw new Store_Exception( 'query_invalid_field', __( 'Invalid group by field.', 'hizzle-store' ) );
				}

				$this->query_groupby .= ', ' . $table_field;
				$this->query_fields[] = $table_field;
			}

			$this->query_groupby = 'GROUP BY ' . ltrim( $this->query_groupby, ',' );
		}

		// Add extra fields.
		if ( ! empty( $qv['extra_fields'] ) ) {
			foreach ( wp_parse_list( $qv['extra_fields'] ) as $field ) {

				// Ensure the field is supported.
				$field = $this->prefix_field( esc_sql( sanitize_key( $field ) ) );
				if ( empty( $field ) ) {
					throw new Store_Exception( 'query_invalid_field', __( 'Invalid extra field.', 'hizzle-store' ) );
				}

				$this->query_fields[] = $field;
			}
		}

		// Abort if no fields were aggregated.
		if ( empty( $this->query_fields ) ) {
			throw new Store_Exception( 'query_missing_aggregate_fields', __( 'Missing aggregate fields.', 'hizzle-store' ) );
		}

		$this->query_fields  = implode( ', ', array_unique( $this->query_fields ) );
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

		if ( ! empty( $qv['count_only'] ) ) {
			$this->query_fields = "DISTINCT COUNT($table.id)";
			return;
		}

		// Check if we need to count the total number of items.
		if ( ! empty( $qv['count_total'] ) ) {
			$this->count_field = "DISTINCT COUNT($table.id)";
		}

		if ( is_array( $qv['fields'] ) ) {

			$query_fields = array();
			foreach ( $qv['fields'] as $field ) {
				$table_field = $this->prefix_field( esc_sql( sanitize_key( $field ) ) );

				if ( empty( $table_field ) ) {
					throw new Store_Exception( 'query_invalid_field', "Invalid field $field." );
				}
				$query_fields[] = $table_field;
			}

			$query_fields       = array_unique( $query_fields );
			$this->query_fields = implode( ',', $query_fields );

			if ( 1 === count( $query_fields ) ) {
				$this->query_fields = 'DISTINCT ' . $this->query_fields;
			}
		} elseif ( 'all' === $qv['fields'] ) {
			$this->query_fields = '*';
		} else {
			$this->query_fields = "$table.id";
		}

	}

	/**
	 * Prepares the meta query.
	 *
	 * @since 1.0.0
	 * @param array $qv The query vars.
	 */
	protected function prepare_meta_query( $qv ) {
		global $wpdb;

		// Abort if the collection does not support meta fields.
		if ( empty( $this->known_fields['meta'] ) && empty( $qv['meta_query'] ) ) {
			return;
		}

		$collection  = $this->get_collection();
		$meta_query  = empty( $qv['meta_query'] ) ? array() : $qv['meta_query'];
		$table      = $collection->get_db_table_name();
		$meta_table = $collection->get_meta_table_name();
		$id_col     = $collection->get_meta_type() . '_id';
		$not_exists = array();

		foreach ( $collection->get_props() as $prop ) {

			if ( ! $prop->is_meta_key ) {
				continue;
			}

			$meta_field = $prop->name;

			// Check if this is a multi-value field.
			if ( $prop->is_meta_key_multiple && isset( $qv[ "{$meta_field}_not" ] ) ) {
				$value = $qv[ "{$meta_field}_not" ];

				if ( empty( $value ) && ! is_numeric( $value ) ) {
					continue;
				}

				if ( is_array( $value ) && 1 < count( $value ) ) {
					$value = "'" . implode( "','", array_map( 'esc_sql', $value ) ) . "'";
					$where = "IN ( $value )";
				} else {
					$value = is_array( $value ) ? $value[0] : $value;
					$where = "= '" . esc_sql( $value ) . "'";
				}

				$not_exists[] = $wpdb->prepare(
					"( meta_key = %s AND meta_value $where )",
					$meta_field
				);
			}

			// = or IN.
			if ( isset( $qv[ $meta_field ] ) ) {
				$value        = is_array( $qv[ $meta_field ] ) && 1 === count( $qv[ $meta_field ] ) ? $qv[ $meta_field ][0] : $qv[ $meta_field ];
				$meta_query[] = array(
					'key'     => $meta_field,
					'value'   => $value,
					'compare' => is_array( $value ) ? 'IN' : '=',
				);
			}

			// != or NOT IN.
			if ( isset( $qv[ "{$meta_field}_not" ] ) && ! $prop->is_meta_key_multiple ) {
				$value        = is_array( $qv[ "{$meta_field}_not" ] ) && 1 === count( $qv[ "{$meta_field}_not" ] ) ? $qv[ "{$meta_field}_not" ][0] : $qv[ "{$meta_field}_not" ];
				$meta_query[] = array(
					'key'     => $meta_field,
					'value'   => $value,
					'compare' => is_array( $value ) ? 'NOT IN' : '!=',
				);
			}

		}

		if ( ! empty( $not_exists ) ) {

			if ( 1 === count( $not_exists ) ) {
				$select             = current( $not_exists );
				$this->query_where .= " AND $table.id NOT IN ( SELECT DISTINCT $id_col FROM $meta_table WHERE $select )";
			} else {
				$select             = implode( ' OR ', $not_exists );
				$this->query_where .= " AND NOT EXISTS ( SELECT 1 FROM $meta_table WHERE $meta_table.$id_col = $table.id AND ( $select ) )";
			}
		}

		if ( empty( $meta_query ) ) {
			return;
		}

		// Meta query.
		$meta_query = new \WP_Meta_Query( $meta_query );
		$meta_type  = $collection->get_meta_type();
		$table      = $collection->is_cpt() ? $wpdb->posts : $collection->get_db_table_name();
		$id_prop    = $collection->is_cpt() ? 'ID' : 'id';

		if ( ! empty( $meta_query->queries ) ) {
			$clauses = $meta_query->get_sql( $meta_type, $table, $id_prop, $this );

			if ( empty( $clauses ) ) {
				return;
			}

			$this->query_join  .= $clauses['join'];
			$this->query_where .= $clauses['where'];

			if ( $meta_query->has_or_relation() && false === strpos( $this->query_fields, 'DISTINCT' ) ) {
				$this->query_fields = 'DISTINCT ' . $this->query_fields;
			}
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

		$collection = $this->get_collection();
		$all_fields = array_merge( $this->known_fields['main'], $this->known_fields['post'] );

		// Table fields.
		foreach ( $all_fields as $key ) {

			$field_name = $this->prefix_field( $key );
			$field      = $collection->get_prop( $key );
			$data_type  = $field->get_data_type();

			// = or IN.
			if ( isset( $qv[ $key ] ) && 'any' !== $qv[ $key ] ) {

				if ( is_array( $qv[ $key ] ) ) {
					$enums              = "'" . implode( "','", array_map( 'esc_sql', $qv[ $key ] ) ) . "'";
					$this->query_where .= " AND $field_name IN ($enums)";
				} else {
					$value = $field->sanitize( $qv[ $key ] );
					$value = is_bool( $value ) ? (int) $value : $value;
					$this->query_where .= $wpdb->prepare( " AND $field_name=$data_type", $value ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
				}
			}

			// != or NOT IN.
			if ( isset( $qv[ "{$key}_not" ] ) ) {

				if ( is_array( $qv[ "{$key}_not" ] ) ) {
					$enums              = "'" . implode( "','", array_map( 'esc_sql', $qv[ "{$key}_not" ] ) ) . "'";
					$this->query_where .= " AND $field_name NOT IN ($enums)";
				} else {
					$value = $field->sanitize( $qv[ "{$key}_not" ] );
					$value = is_bool( $value ) ? (int) $value : $value;
					$this->query_where .= $wpdb->prepare( " AND $field_name<>$data_type", $value ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
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

					$date_query         = new \WP_Date_Query( $date_query, $field_name );
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
			if ( $qv['search_columns'] ) {
				$search_columns = array_intersect( $qv['search_columns'], $all_fields );
			}
			if ( ! $search_columns ) {
				$search_columns = $all_fields;
			}

			$this->query_where .= $this->get_search_sql( $search, $search_columns );
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
	 * @return string
	 */
	protected function get_search_sql( $string, $cols ) {
		global $wpdb;

		$searches = array();
		$string   = trim( $string, '%' );
		$like     = '%' . $wpdb->esc_like( $string ) . '%';

		foreach ( $cols as $col ) {
			$field_name = $this->prefix_field( $col );

			if ( 'id' === $col ) {
				$searches[] = $wpdb->prepare( "$field_name = %s", $string ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			} else {
				$searches[] = $wpdb->prepare( "$field_name LIKE %s", $like ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			}
		}

		return ' AND (' . implode( ' OR ', $searches ) . ')';
	}

	/**
	 * Prepares the ORDER BY query.
	 *
	 * @since 1.0.0
	 * @param array $qv The query vars.
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 */
	public function prepare_orderby_query( $qv ) {
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

			$parsed = $this->parse_orderby( $_orderby );

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
	 * @return string Value to use in the ORDER clause, if `$orderby` is valid.
	 */
	protected function parse_orderby( $orderby ) {

		// Orderby include.
		if ( 'include' === $orderby && ! empty( $this->query_vars['include'] ) ) {
			$include     = wp_parse_id_list( $this->query_vars['include'] );
			$include_sql = implode( ',', $include );
			$field_name  = $this->prefix_field( 'id' );
			return "FIELD( $field_name, $include_sql )";
		}

		return $this->prefix_field( $orderby );
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
			'search_columns' => array(),
			'orderby'        => array( 'id' ),
			'order'          => 'DESC',
			'offset'         => '',
			'per_page'       => -1,
			'page'           => 1,
			'count_total'    => true,
			'count_only'     => false,
			'fields'         => 'all',
			'aggregate'      => false, // pass an array of property_name and function to aggregate the results.
			'meta_query'     => array(),
		);

		if ( isset( $args['number'] ) ) {
			$args['per_page'] = $args['number'];
		}

		if ( ! empty( $args['paged'] ) ) {
			$args['page'] = $args['paged'];
		}

		return wp_parse_args( $args, $defaults );
	}

	/**
	 * Prepares known fields for use in a query.
	 */
	protected function prepare_known_fields() {

		$this->known_fields = $this->get_collection()->get_known_fields();
	}

	/**
	 * Prefixes a field name with the table name.
	 *
	 * @since 1.0.0
	 * @return string|false
	 */
	public function prefix_field( $field ) {
		global $wpdb;

		$collection = $this->get_collection();

		// Main db table field.
		if ( in_array( $field, $this->known_fields['main'], true ) || 'id' === strtolower( $field ) ) {
			$table = $collection->get_db_table_name();
			return "$table.$field";
		}

		// Posts table fields.
		if ( in_array( $field, $this->known_fields['post'], true ) ) {
			$field = $collection->post_map[ $field ];
			return "$wpdb->posts.$field";
		}

		// Uknown field.
		return false;
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

		// Maybe abort if we're only counting.
		if ( ! empty( $this->query_vars['count_only'] ) ) {
			$this->total_results = $wpdb->get_var( "SELECT $this->query_fields $this->query_from $this->query_join $this->query_where" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			return;
		}

		// Allow third party plugins to modify the query.
		$collection    = Collection::instance( $this->collection_name );
		$this->results = apply_filters_ref_array( $collection->hook_prefix( 'pre_query' ), array( null, &$this ) );

		// Run query if it was not short-circuted.
		if ( null === $this->results ) {
			$this->request = "SELECT $this->query_fields $this->query_from $this->query_join $this->query_where $this->query_orderby $this->query_limit";

			if ( ( is_array( $this->query_vars['fields'] ) && 1 !== count( $this->query_vars['fields'] ) ) || 'all' === $this->query_vars['fields'] ) {
				$this->results = $wpdb->get_results( $this->request ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			} else {
				$this->results = $wpdb->get_col( $this->request ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}

			if ( ! empty( $this->count_field ) ) {
				$this->total_results = (int) $wpdb->get_var( "SELECT $this->count_field $this->query_from $this->query_join $this->query_where" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
		}

		// Maybe init objects.
		if ( $this->results && 'all' === $this->query_vars['fields'] ) {
			$results       = $this->results;
			$this->results = array();

			foreach ( $results as $result ) {

				if ( isset( $result->id ) ) {
					// Cache object.
					$collection->update_cache( (array) $result );

					// Replace raw data with Record objects.
					$this->results[] = $collection->get( $result->id );
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
			$this->request   = "SELECT $this->query_fields $this->query_from $this->query_join $this->query_where $this->query_groupby $this->query_limit";
			$this->aggregate = $wpdb->get_results( $this->request ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

	}

}
