<?php
/**
 * Regression checks using the installed WordPress date-query implementation.
 *
 * Run: php tests/query-dates.php [path/to/wordpress]
 */

define( 'ABSPATH', isset( $argv[1] ) ? $argv[1] : dirname( __DIR__, 4 ) );
date_default_timezone_set( 'UTC' );
set_error_handler( function ( $severity, $message, $file, $line ) {
	throw new ErrorException( $message, 0, $severity, $file, $line );
} );

/** Return the timezone for the current test. */
function wp_timezone() { return new DateTimeZone( $GLOBALS['test_timezone'] ); }
/** Minimal WordPress integer helper. */
function absint( $value ) { return abs( (int) $value ); }
/** Validate calendar components without loading WordPress hooks. */
function wp_checkdate( $month, $day, $year, $source ) { return checkdate( $month, $day, $year ); }
/** This fixture uses a single site. */
function is_multisite() { return false; }
/** Keep WordPress filters unchanged in this isolated fixture. */
function apply_filters( $hook, $value ) { return $value; }
/** Escape fixture SQL literals. */
function esc_sql( $value ) { return str_replace( "'", "''", (string) $value ); }
/** Sanitize the fixture's plain text. */
function sanitize_text_field( $value ) { return strip_tags( $value ); }
/** Sanitize the fixture's textarea. */
function sanitize_textarea_field( $value ) { return strip_tags( $value ); }

/** Minimal database formatter; no database connection or queries are made. */
class Date_Query_Test_DB {
	public $posts = 'wp_posts';
	public $comments = 'wp_comments';
	public $users = 'wp_users';
	public $prepared_queries = array();

	/** Substitute the placeholders used by the query builder. */
	public function prepare( $sql, ...$values ) {
		$this->prepared_queries[] = array( $sql, $values );
		return preg_replace_callback( '/%[sdf]/', function ( $match ) use ( &$values ) {
			$value = array_shift( $values );
			return '%s' === $match[0] ? "'" . esc_sql( $value ) . "'" : (string) $value;
		}, $sql );
	}
}

require ABSPATH . '/wp-includes/class-wp-date-query.php';
require dirname( __DIR__ ) . '/src/Prop.php';
require dirname( __DIR__ ) . '/src/Query.php';

/** A collection fixture containing datetime, date, and non-date properties. */
class Date_Query_Test_Collection {
	public $keys = array();
	public $props = array();

	/** Retrieve a fixture property. */
	public function get_prop( $key ) { return $this->props[ $key ]; }
}

/** Exercise real WHERE generation without running a database query. */
class Date_Query_Test extends \Hizzle\Store\Query {
	private $fixture;

	/** Initialize the known fields without invoking the parent query runner. */
	public function __construct( $collection ) {
		$this->fixture = $collection;
		$this->known_fields = array( 'main' => array_keys( $collection->props ), 'post' => array() );
	}

	/** Retrieve the fixture collection. */
	public function get_collection() { return $this->fixture; }
	/** Qualify a fixture column. */
	public function prefix_field( $field ) { return 'test_records.' . $field; }

	/** Build WHERE SQL for one isolated test. */
	public function sql( $args ) {
		$this->prepare_where_query( $args, 'test_records' );
		return $this->query_where;
	}
}

/** Assert a condition while counting regression checks. */
function check_date_query( $condition, $description ) {
	if ( ! $condition ) {
		throw new RuntimeException( $description );
	}
	++$GLOBALS['checks'];
}

$GLOBALS['checks'] = 0;
$GLOBALS['wpdb'] = new Date_Query_Test_DB();
$collection = new Date_Query_Test_Collection();
foreach ( array( 'occurred_at' => 'datetime', 'calendar_date' => 'date', 'label' => 'varchar', 'timestamp_at' => 'timestamp' ) as $name => $type ) {
	$collection->props[ $name ] = new \Hizzle\Store\Prop( 'fixture', array( 'name' => $name, 'type' => $type ) );
	check_date_query( strtoupper( $type ) === $collection->props[ $name ]->type, 'Types must be normalized.' );
}
$query = new Date_Query_Test( $collection );

foreach ( array( 'UTC', 'Africa/Nairobi', 'America/New_York', 'Asia/Kolkata' ) as $timezone ) {
	$GLOBALS['test_timezone'] = $timezone;
	foreach ( array( '2026-01-15 15:34:56', '2026-07-15 15:34:56' ) as $local ) {
		$instant = new DateTimeImmutable( $local, wp_timezone() );
		$utc = $instant->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
		foreach ( array( $local, $instant->format( DATE_ATOM ), $utc . ' UTC', $instant->getTimestamp(), (string) $instant->getTimestamp(), $instant ) as $value ) {
			foreach ( array( 'occurred_at', 'occurred_at_not', 'occurred_at_before', 'occurred_at_after', 'timestamp_at' ) as $key ) {
				$sql = $query->sql( array( $key => $value ) );
				check_date_query( false !== strpos( $sql, "'$utc'" ), "$timezone $key must use the exact UTC second: $sql" );
			}
			foreach ( array( 'occurred_at', 'occurred_at_not' ) as $key ) {
				$sql = $query->sql( array( $key => array( $value, $value ) ) );
				check_date_query( false !== strpos( $sql, "('$utc','$utc')" ), 'List values must be converted individually.' );
			}
		}
		$sql = $query->sql( array( 'occurred_at_query' => array( 'relation' => 'OR', array( 'after' => $local ), array( array( 'before' => $utc . ' UTC', 'inclusive' => true ) ) ) ) );
		check_date_query( 2 === substr_count( $sql, "'$utc'" ), 'Nested boundaries must use UTC and preserve seconds.' );
	}
	foreach ( array( 'calendar_date', 'calendar_date_not', 'calendar_date_before', 'calendar_date_after' ) as $key ) {
		check_date_query( false !== strpos( $query->sql( array( $key => '2026-07-15' ) ), '2026-07-15' ), 'Calendar dates must not shift.' );
	}
	check_date_query( false !== strpos( $query->sql( array( 'occurred_at' => null ) ), 'IS NULL' ), 'Null equality must remain unchanged.' );
	check_date_query( false !== strpos( $query->sql( array( 'occurred_at_not' => null ) ), 'IS NOT NULL' ), 'Null inequality must remain unchanged.' );
	check_date_query( false !== strpos( $query->sql( array( 'occurred_at_is_null' => false ) ), 'IS NOT NULL' ), 'Explicit null checks must remain unchanged.' );
	check_date_query( false !== strpos( $query->sql( array( 'label' => 'unchanged' ) ), "label='unchanged'" ), 'Non-date equality must remain unchanged.' );
	$sql = $query->sql( array( 'occurred_at_query' => array( 'month' => 7, 'day' => 15 ) ) );
	check_date_query( false !== strpos( $sql, 'MONTH(' ) && false !== strpos( $sql, 'DAYOFMONTH(' ), 'Anniversary components must remain native date queries.' );
	$sql = $query->sql( array( 'occurred_at_before' => array( 'year' => 2026, 'month' => 7, 'day' => 15 ) ) );
	check_date_query( false !== strpos( $sql, '2026-07-15 00:00:00' ), 'Boundary component arrays must retain WordPress semantics.' );
}

foreach ( array( 'occurred_at', 'occurred_at_before', 'occurred_at_after' ) as $key ) {
	check_date_query( false !== strpos( $query->sql( array( $key => 0 ) ), '1970-01-01 00:00:00' ), 'Unix epoch comparisons must be supported.' );
}
$GLOBALS['test_timezone'] = 'America/New_York';
foreach ( array( '2026-11-01 01:30:56-04:00' => '2026-11-01 05:30:56', '2026-11-01 01:30:56-05:00' => '2026-11-01 06:30:56' ) as $local => $utc ) {
	check_date_query( false !== strpos( $query->sql( array( 'occurred_at' => $local ) ), $utc ), 'Offsets must distinguish repeated daylight-saving hours.' );
}
$calls = 0;
$collection->props['occurred_at']->sanitize_callback = function ( $value ) use ( &$calls ) {
	++$calls;
	return $value;
};
foreach ( array( 'occurred_at', 'occurred_at_not' ) as $key ) {
	check_date_query( false !== strpos( $query->sql( array( $key => '2026-11-01 01:30:56-04:00' ) ), '2026-11-01 05:30:56' ), 'Custom sanitizers must receive UTC comparison values.' );
}
check_date_query( 2 === $calls, 'Scalar comparison sanitizers must still run.' );

foreach ( array( 'DATETIME', 'TIMESTAMP' ) as $type ) {
	$prop = new \Hizzle\Store\Prop( 'fixture', array( 'name' => 'date_value', 'type' => $type ) );
	foreach ( array( 1784129696, '1784129696', '2026-07-15 15:34:56 UTC', '2026-07-15 18:34:56+03:00' ) as $value ) {
		check_date_query( '2026-07-15 15:34:56' === $prop->sanitize( $value ), "$type must sanitize to a SQL datetime string." );
	}
	check_date_query( '1970-01-01 00:00:00' === $prop->sanitize( 0 ), "$type must preserve the Unix epoch." );
	check_date_query( null === $prop->sanitize( null ), "$type must preserve null." );
	$object = new DateTimeImmutable( '2026-07-15 15:34:56 UTC' );
	check_date_query( $object === $prop->sanitize( $object ), 'Date objects must remain available to the storage layer.' );
}

$payloads = array( "2026-07-15' OR 1=1 --", "2026-07-15'); DROP TABLE test_records; --", "2026-07-15\\' OR SLEEP(5) #" );
$collection->props['occurred_at']->sanitize_callback = null;
foreach ( $payloads as $payload ) {
	foreach ( array( 'occurred_at', 'occurred_at_not', 'occurred_at_before', 'occurred_at_after' ) as $key ) {
		$sql = $query->sql( array( $key => $payload ) );
		check_date_query( false === strpos( $sql, 'OR 1=1' ) && false === strpos( $sql, 'DROP TABLE' ) && false === strpos( $sql, 'SLEEP' ), 'Date strings must not carry SQL fragments into the generated query.' );
	}
	foreach ( array( 'occurred_at', 'occurred_at_not' ) as $key ) {
		$sql = $query->sql( array( $key => array( $payload ) ) );
		check_date_query( false !== strpos( $sql, "('1970-01-01 00:00:00')" ), 'Malformed datetime list values must become formatted dates.' );
	}
	$sql = $query->sql( array( 'occurred_at_query' => array( array( 'before' => $payload ) ) ) );
	check_date_query( false !== strpos( $sql, "'1970-01-01 00:00:00'" ), 'Nested string boundaries must also discard SQL fragments.' );

	// Even a custom sanitizer returning raw SQL-like text must use a value placeholder.
	$collection->props['occurred_at']->sanitize_callback = function ( $value ) use ( $payload ) { return $payload; };
	foreach ( array( 'occurred_at', 'occurred_at_not' ) as $key ) {
		$query->sql( array( $key => '2026-07-15' ) );
		$prepared = end( $GLOBALS['wpdb']->prepared_queries );
		check_date_query( false !== strpos( $prepared[0], '%s' ) && false === strpos( $prepared[0], $payload ) && array( $payload ) === $prepared[1], 'Custom sanitizer output must remain separate from the SQL template.' );
	}
	$collection->props['occurred_at']->sanitize_callback = null;
}
$resource = fopen( 'php://memory', 'r+' );
foreach ( array( new stdClass(), $resource, true ) as $unsupported ) {
	foreach ( array( 'occurred_at', 'occurred_at_not', 'occurred_at_before', 'occurred_at_after', 'calendar_date_before', 'calendar_date_after' ) as $key ) {
		check_date_query( false !== strpos( $query->sql( array( $key => $unsupported ) ), '1970-01-01 00:00:00' ), 'Unsupported values must use the invalid-date fallback without throwing.' );
	}
	foreach ( array( 'occurred_at', 'occurred_at_not' ) as $key ) {
		check_date_query( false !== strpos( $query->sql( array( $key => array( $unsupported ) ) ), '1970-01-01 00:00:00' ), 'Unsupported list values must not reach SQL escaping.' );
	}
	check_date_query( false !== strpos( $query->sql( array( 'occurred_at_query' => array( array( 'before' => $unsupported ) ) ) ), '1970-01-01 00:00:00' ), 'Unsupported nested boundaries must not reach WordPress date parsing.' );
}
fclose( $resource );
echo $GLOBALS['checks'] . " date query checks passed.\n";
