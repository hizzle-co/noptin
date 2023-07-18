<?php
/**
 * Subscriber API: Noptin_Subscriber_Query class
 *
 * Contains core class used to query for Noptin subscribers
 *
 * @since 1.2.7
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main class used for querying subscribers.
 *
 * @since 1.2.7
 *
 * @deprecated
 * @see Noptin_Subscriber_Query::prepare_query() for information on accepted arguments.
 */
class Noptin_Subscriber_Query {

	/**
	 * Query vars, after parsing
	 *
	 * @since 1.2.7
	 * @var array
	 */
	public $query_vars = array();

	/**
	 * List of found subscriber ids
	 *
	 * @since 1.2.7
	 * @var array
	 */
	private $results;

	/**
	 * Total number of found subscribers for the current query
	 *
	 * @since 1.2.7
	 * @var int
	 */
	private $total_subscribers = 0;

	/**
	 * Metadata query container.
	 *
	 * @since 1.2.7
	 * @var WP_Meta_Query
	 */
	public $meta_query = false;

	/**
	 * The SQL query used to fetch matching subscribers.
	 *
	 * @since 1.2.7
	 * @var string
	 */
	public $request;

	// SQL clauses

	/**
	 * Contains the 'FIELDS' sql clause
	 *
	 * @since 1.2.7
	 * @var string
	 */
	public $query_fields;

	/**
	 * Contains the 'FROM' sql clause
	 *
	 * @since 1.2.7
	 * @var string
	 */
	public $query_from;

	/**
	 * Contains the 'WHERE' sql clause
	 *
	 * @since 1.2.7
	 * @var string
	 */
	public $query_where;

	/**
	 * Contains the 'ORDER BY' sql clause
	 *
	 * @since 1.2.7
	 * @var string
	 */
	public $query_orderby;

	/**
	 * Contains the 'LIMIT' sql clause
	 *
	 * @since 1.2.7
	 * @var string
	 */
	public $query_limit;

	/**
	 * Class constructor.
	 *
	 * @since 1.2.7
	 *
	 * @param null|string|array $query Optional. The query variables.
	 */
	public function __construct( $query = null ) {

		// Show deprecated class notice.
		_deprecated_function( __CLASS__, '2.0.0', 'get_noptin_subscribers' );

		if ( ! is_null( $query ) ) {
			$this->prepare_query( $query );
			$this->query();
		}
	}

	/**
	 * Fills in missing query variables with default values.
	 *
	 * @since 1.2.7
	 *
	 * @param array $args Query vars, as passed to `Noptin_Subscriber_Query`.
	 * @return array Complete query variables with undefined ones filled in with defaults.
	 */
	public static function fill_query_vars( $args ) {
		$defaults = array(
			'subscriber_status' => 'all',
			'email_status'      => 'any',
			'meta_key'          => '',
			'meta_value'        => '',
			'meta_compare'      => '=',
			'include'           => array(),
			'exclude'           => array(),
			'search'            => '',
			'search_columns'    => array(),
			'orderby'           => array( 'date_created', 'id' ),
			'order'             => 'DESC',
			'offset'            => '',
			'number'            => '',
			'paged'             => 1,
			'count_total'       => true,
			'fields'            => 'all',
			'meta_query'        => array(),
		);

		return wp_parse_args( $args, $defaults );
	}

	/**
	 * Prepare the query variables.
	 *
	 * @since 1.2.7
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string|array $query {
	 *     Optional. Array or string of Query parameters.
	 *
	 *     @type string       $subscriber_status   The susbcriber status to filter by. Can either be all, active or inactive.
	 *                                             Default is all.
	 *     @type string       $email_status        The email confirmation status. Can either be any, confirmed or unconfirmed.
	 *                                             Default is any.
	 *     @type array        $date_query          An array to pass to WP_Date_Query. Default empty.
	 *     @type array        $meta_query          An array to pass to WP_Meta_Query. Default empty.
	 *     @type string       $meta_key            Subscriber meta key. Default empty.
	 *     @type string       $meta_value          Subscriber meta value. Default empty.
	 *     @type string       $meta_compare        Comparison operator to test the `$meta_value`. Accepts '=', '!=',
	 *                                             '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN',
	 *                                             'BETWEEN', 'NOT BETWEEN', 'EXISTS', 'NOT EXISTS', 'REGEXP',
	 *                                             'NOT REGEXP', or 'RLIKE'. Default '='.
	 *     @type array        $include             An array of subscriber IDs to include. Default empty array.
	 *     @type array        $exclude             An array of subscriber IDs to exclude. Default empty array.
	 *     @type string       $search              Search keyword. Searches for possible string matches on columns.
	 *     @type array        $search_columns      Array of column names to be searched. Accepts 'id', 'first_name',
	 *                                             'last_name', 'email', 'date_created'. Default an array containing all the above.
	 *     @type string|array $orderby             Field(s) to sort the retrieved subscribers by. May be a single value,
	 *                                             an array of values, or a multi-dimensional array with fields as
	 *                                             keys and orders ('ASC' or 'DESC') as values. Accepted values are
	 *                                             'id', 'first_name', 'last_name', 'include', 'email, 'active',
	 *                                             'date_created', 'meta_value',
	 *                                             'meta_value_num', the value of `$meta_key`, or an array key of
	 *                                             `$meta_query`. To use 'meta_value' or 'meta_value_num', `$meta_key`
	 *                                             must be also be defined. Default array( 'date_created', 'id' ).
	 *     @type string       $order               Designates ascending or descending order of subscribers. Order values
	 *                                             passed as part of an `$orderby` array take precedence over this
	 *                                             parameter. Accepts 'ASC', 'DESC'. Default 'DESC'.
	 *     @type int          $offset              Number of subscribers to offset in retrieved results. Can be used in
	 *                                             conjunction with pagination. Default 0.
	 *     @type int          $number              Number of subscribers to limit the query for. Can be used in
	 *                                             conjunction with pagination. Value -1 (all) is supported, but
	 *                                             should be used with caution on larger sites.
	 *                                             Default -1 (all subscribers).
	 *     @type int          $paged               When used with number, defines the page of results to return.
	 *                                             Default 1.
	 *     @type bool         $count_total         Whether to count the total number of subscribers found. If pagination
	 *                                             is not needed, setting this to false can improve performance.
	 *                                             Default true.
	 *     @type string|array $fields              Which fields to return. Single or all fields (string), or array
	 *                                             of fields. Accepts 'id', 'first_name', 'last_name',
	 *                                             'email', 'confirm_key', 'confirmed', 'date_created', 'active'.
	 *                                             Use 'all' for all fields. Default 'all'.
	 * }
	 */
	public function prepare_query( $query = array() ) {
		global $wpdb;

		if ( empty( $this->query_vars ) || ! empty( $query ) ) {
			$this->query_limit = null;
			$this->query_vars  = $this->fill_query_vars( $query );
		}

		if ( ! empty( $this->query_vars['fields'] ) && 'all' !== $this->query_vars['fields'] ) {
			$this->query_vars['fields'] = noptin_parse_list( $this->query_vars['fields'] );
		}

		/**
		 * Fires before the Noptin_Subscriber_Query has been parsed.
		 *
		 * The passed Noptin_Subscriber_Query object contains the query variables, not
		 * yet passed into SQL.
		 *
		 * @since 1.2.7
		 *
		 * @param Noptin_Subscriber_Query $this The current Noptin_Subscriber_Query instance,
		 *                            passed by reference.
		 */
		do_action( 'noptin_pre_get_subscribers', $this );

		// Ensure that query vars are filled after 'noptin_pre_get_subscribers'.
		$qv    =& $this->query_vars;
		$qv    = $this->fill_query_vars( $qv );
		$table = get_noptin_subscribers_table_name();

		if ( is_array( $qv['fields'] ) ) {
			$qv['fields'] = array_unique( $qv['fields'] );

			$this->query_fields = array();
			foreach ( $qv['fields'] as $field ) {
				$field                = 'id' === strtolower( $field ) ? 'id' : sanitize_key( $field );
				$this->query_fields[] = "$table.$field";
			}
			$this->query_fields = implode( ',', $this->query_fields );
		} elseif ( 'all' === $qv['fields'] ) {
			$this->query_fields = "$table.*";
		} else {
			$this->query_fields = "$table.id";
		}

		if ( isset( $qv['count_total'] ) && $qv['count_total'] ) {
			$this->query_fields = 'SQL_CALC_FOUND_ROWS ' . $this->query_fields;
		}

		$this->query_from  = "FROM $table";
		$this->query_where = 'WHERE 1=1';

		// Parse and sanitize 'include', for use by 'orderby' as well as 'include' below.
		if ( ! empty( $qv['include'] ) ) {
			$include = wp_parse_id_list( $qv['include'] );
		} else {
			$include = false;
		}

		// Status.
		if ( 'all' !== $qv['subscriber_status'] ) {
			$active             = trim( $qv['subscriber_status'] ) === 'active' ? 0 : 1;
			$this->query_where .= $wpdb->prepare( ' AND active = %d', $active );
		}

		// Double optin.
		if ( 'any' !== $qv['email_status'] ) {
			$confirmed          = trim( $qv['email_status'] ) === 'confirmed' ? 1 : 0;
			$this->query_where .= $wpdb->prepare( ' AND confirmed = %d', $confirmed );
		}

		// Meta query.
		$this->meta_query = new WP_Meta_Query();
		$this->meta_query->parse_query_vars( $qv );

		if ( ! empty( $this->meta_query->queries ) ) {
			$clauses            = $this->meta_query->get_sql( 'noptin_subscriber', $table, 'id', $this );
			$this->query_from  .= $clauses['join'];
			$this->query_where .= $clauses['where'];

			if ( $this->meta_query->has_or_relation() ) {
				$this->query_fields = 'DISTINCT ' . $this->query_fields;
			}
		}

		// sorting
		$qv['order'] = isset( $qv['order'] ) ? strtoupper( $qv['order'] ) : '';
		$order       = $this->parse_order( $qv['order'] );

		if ( empty( $qv['orderby'] ) ) {
			// Default order is by 'date_created id' (latest subscribers).
			$ordersby = array( 'date_created', 'id' );
		} elseif ( is_array( $qv['orderby'] ) ) {
			$ordersby = $qv['orderby'];
		} else {
			// 'orderby' values may be a comma- or space-separated list.
			$ordersby = noptin_parse_list( $qv['orderby'] );
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

		// If no valid clauses were found, order by latest subscribers.
		if ( empty( $orderby_array ) ) {
			$orderby_array[] = "date_created $order id $order";
		}

		$this->query_orderby = 'ORDER BY ' . implode( ', ', $orderby_array );

		// limit
		if ( isset( $qv['number'] ) && $qv['number'] > 0 ) {
			if ( $qv['offset'] ) {
				$this->query_limit = $wpdb->prepare( 'LIMIT %d, %d', $qv['offset'], $qv['number'] );
			} else {
				$this->query_limit = $wpdb->prepare( 'LIMIT %d, %d', $qv['number'] * ( $qv['paged'] - 1 ), $qv['number'] );
			}
		}

		$search = '';
		if ( isset( $qv['search'] ) ) {
			$search = trim( $qv['search'] );
		}

		if ( $search ) {
			trim( $search, '*' );

			$search_columns = array();
			if ( $qv['search_columns'] ) {
				$search_columns = array_intersect( $qv['search_columns'], array( 'id', 'first_name', 'last_name', 'email', 'date_created' ) );
			}
			if ( ! $search_columns ) {
				$search_columns = array( 'id', 'first_name', 'last_name', 'email', 'date_created' );
			}

			$this->query_where .= $this->get_search_sql( $search, $search_columns );
		}

		if ( ! empty( $include ) ) {
			// Sanitized earlier.
			$ids                = implode( ',', $include );
			$this->query_where .= " AND $table.id IN ($ids)";
		} elseif ( ! empty( $qv['exclude'] ) ) {
			$ids                = implode( ',', noptin_parse_int_list( $qv['exclude'] ) );
			$this->query_where .= " AND $table.id NOT IN ($ids)";
		}

		// Date queries are allowed for the date_created field.
		if ( ! empty( $qv['date_query'] ) && is_array( $qv['date_query'] ) ) {
			$date_query         = new WP_Date_Query( $qv['date_query'], "$table.date_created" );
			$this->query_where .= $date_query->get_sql();
		}

		/**
		 * Fires after the Noptin_Subscriber_Query has been parsed, and before
		 * the query is executed.
		 *
		 * The passed Noptin_Subscriber_Query object contains SQL parts formed
		 * from parsing the given query.
		 *
		 * @since 1.2.7
		 *
		 * @param Noptin_Subscriber_Query $this The current Noptin_Subscriber_Query instance,
		 *                            passed by reference.
		 */
		do_action_ref_array( 'noptin_pre_subscribers_query', array( &$this ) );
	}

	/**
	 * Execute the query, with the current variables.
	 *
	 * @since 1.2.7
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 */
	public function query() {
		global $wpdb;

		$qv =& $this->query_vars;

		/**
		 * Filters the subscribers query array before the query takes place.
		 *
		 * Return a non-null value to bypass the default Noptin subscriber queries.
		 * Filtering functions that require pagination information are encouraged to set
		 * the `total_subscribers` property of the Noptin_Subscriber_Query object, passed to the filter
		 * by reference. If Noptin_Subscriber_Query does not perform a database query, it will not
		 * have enough information to generate these values itself.
		 *
		 * @since 1.2.7
		 *
		 * @param array|null              $results Return an array of subscriber data to short-circuit the subscriber query
		 *                                or null to allow Noptin to run its normal queries.
		 * @param Noptin_Subscriber_Query $this The Noptin_Subscriber_Query instance (passed by reference).
		 */
		$this->results = apply_filters_ref_array( 'noptin_subscribers_pre_query', array( null, &$this ) );

		if ( null === $this->results ) {
			$this->request = "SELECT $this->query_fields $this->query_from $this->query_where $this->query_orderby $this->query_limit";

			if ( ( is_array( $qv['fields'] ) && 1 !== count( $qv['fields'] ) ) || 'all' === $qv['fields'] ) {
				$this->results = $wpdb->get_results( $this->request ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			} else {
				$this->results = $wpdb->get_col( $this->request ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}

			if ( isset( $qv['count_total'] ) && $qv['count_total'] ) {
				/**
				 * Filters SELECT FOUND_ROWS() query for the current Noptin_Subscriber_Query instance.
				 *
				 * @since 1.2.7
				 *
				 * @global wpdb $wpdb WordPress database abstraction object.
				 *
				 * @param string $sql         The SELECT FOUND_ROWS() query for the current Noptin_Subscriber_Query.
				 * @param Noptin_Subscriber_Query $this The current Noptin_Subscriber_Query instance.
				 */
				$found_subscribers_query = apply_filters( 'noptin_found_subscribers_query', 'SELECT FOUND_ROWS()', $this );

				$this->total_subscribers = (int) $wpdb->get_var( $found_subscribers_query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
		}

		if ( ! $this->results ) {
			return;
		}

		if ( 'all_with_meta' === $qv['fields'] ) {

			$r = array();
			foreach ( $this->results as $subscriber_id ) {
				$r[ $subscriber_id ] = new Noptin_Subscriber( $subscriber_id );
			}

			$this->results = $r;
		} elseif ( 'all' === $qv['fields'] ) {
			foreach ( $this->results as $key => $subscriber ) {
				$this->results[ $key ] = new Noptin_Subscriber( $subscriber );
			}
		}
	}

	/**
	 * Retrieve query variable.
	 *
	 * @since 1.2.7
	 *
	 * @param string $query_var Query variable key.
	 * @return mixed
	 */
	public function get( $query_var ) {
		if ( isset( $this->query_vars[ $query_var ] ) ) {
			return $this->query_vars[ $query_var ];
		}

		return null;
	}

	/**
	 * Set query variable.
	 *
	 * @since 1.2.7
	 *
	 * @param string $query_var Query variable key.
	 * @param mixed $value Query variable value.
	 */
	public function set( $query_var, $value ) {
		$this->query_vars[ $query_var ] = $value;
	}

	/**
	 * Used internally to generate an SQL string for searching across multiple columns
	 *
	 * @since 1.2.7
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
			if ( 'id' === $col ) {
				$searches[] = $wpdb->prepare( "$col = %s", $string );  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			} else {
				$searches[] = $wpdb->prepare( "$col LIKE %s", $like );  // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			}
		}

		return ' AND (' . implode( ' OR ', $searches ) . ')';
	}

	/**
	 * Return the list of subscribers.
	 *
	 * @since 1.2.7
	 *
	 * @return array Array of results.
	 */
	public function get_results() {
		return $this->results;
	}

	/**
	 * Return the total number of subscribers for the current query.
	 *
	 * @since 1.2.7
	 *
	 * @return int Number of total subscribers.
	 */
	public function get_total() {
		return $this->total_subscribers;
	}

	/**
	 * Parse and sanitize 'orderby' keys passed to the subscriber query.
	 *
	 * @since 1.2.7
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string $orderby Alias for the field to order by.
	 * @return string Value to use in the ORDER clause, if `$orderby` is valid.
	 */
	protected function parse_orderby( $orderby ) {
		global $wpdb;

		$meta_query_clauses = $this->meta_query->get_clauses();
		$table              = get_noptin_subscribers_table_name();

		$_orderby = '';
		if ( in_array( $orderby, array( 'first_name', 'last_name', 'email', 'date_created', 'active' ), true ) ) {
			$_orderby = $orderby;
		} elseif ( 'id' === strtolower( $orderby ) ) {
			$_orderby = 'id';
		} elseif ( 'last_name' === strtolower( $orderby ) ) {
			$_orderby = 'last_name';
		} elseif ( 'meta_value' === $orderby || $this->get( 'meta_key' ) === $orderby ) {
			$_orderby = "$wpdb->noptin_subscriber_meta.meta_value";
		} elseif ( 'meta_value_num' === $orderby ) {
			$_orderby = "$wpdb->noptin_subscriber_meta.meta_value+0";
		} elseif ( 'include' === $orderby && ! empty( $this->query_vars['include'] ) ) {
			$include     = noptin_parse_int_list( $this->query_vars['include'] );
			$include_sql = implode( ',', $include );
			$_orderby    = "FIELD( $table.id, $include_sql )";
		} elseif ( isset( $meta_query_clauses[ $orderby ] ) ) {
			$meta_clause = $meta_query_clauses[ $orderby ];
			$_orderby    = sprintf( 'CAST(%s.meta_value AS %s)', esc_sql( $meta_clause['alias'] ), esc_sql( $meta_clause['cast'] ) );
		}

		return $_orderby;
	}

	/**
	 * Parse an 'order' query variable and cast it to ASC or DESC as necessary.
	 *
	 * @since 1.2.7
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
		} else {
			return 'DESC';
		}
	}

}
