<?php
/** Isolated URL-builder failure checks; no WordPress, records, or email. */
$source = file_get_contents( dirname( __DIR__, 3 ) . '/build/Core/functions.php' );
if ( ! preg_match( '/function get_noptin_action_url\([^\n]+\{.*?\n\}/s', $source, $matches ) ) throw new RuntimeException( 'URL builder not found.' );
eval( $matches[0] );
$failure = null;
/** Fixture implementation for the isolated regression. */
function noptin_encrypt_action_data( $data ) {
	global $failure;
	if ( $failure ) throw $failure;
	return 'v1:' . json_encode( $data );
}
/** Fixture implementation for the isolated regression. */
function noptin_encrypt( $value ) { return 'legacy:' . $value; }
/** Fixture implementation for the isolated regression. */
function noptin_decrypt( $value ) { return substr( $value, strpos( $value, ':' ) + 1 ); }
/** Fixture implementation for the isolated regression. */
function wp_json_encode( $value ) { return json_encode( $value ); }
/** Fixture implementation for the isolated regression. */
function get_home_url() { return 'https://example.test'; }
/** Fixture implementation for the isolated regression. */
function apply_filters( $name, $value ) { return $value; }
/** Fixture implementation for the isolated regression. */
function trailingslashit( $url ) { return rtrim( $url, '/' ) . '/'; }
/** Fixture implementation for the isolated regression. */
function add_query_arg( $args, $url ) {
	return $url . '?' . http_build_query( array_map( static fn( $value ) => rawurldecode( (string) $value ), array_filter( $args, static fn( $value ) => false !== $value ) ) );
}
/** Fixture implementation for the isolated regression. */
function decode_url( $url ) {
	parse_str( parse_url( $url, PHP_URL_QUERY ), $query );
	return $query;
}
$checks = 0;
$assert = static function ( $ok, $message ) use ( &$checks ) {
	if ( ! $ok ) throw new RuntimeException( $message );
	++$checks;
};
$data = array( 'email' => 'fixture@example.test', 'subscriber' => 2 );
$legacy = noptin_encrypt( wp_json_encode( $data ) );
$bound = 'v1:' . wp_json_encode( array_merge( $data, array( 'noptin_ns' => 'unsubscribe', 'nte' => 1 ) ) );
foreach ( array( new RuntimeException( 'Encryption failed' ), new Exception( 'Randomness failed' ) ) as $failure ) {
	foreach ( array( $legacy, $bound ) as $value ) {
		$query = decode_url( get_noptin_action_url( 'view_in_browser', $value, true ) );
		$assert( 'view_in_browser' === $query['noptin_ns'], 'Failure must retain the requested query-string action.' );
		$assert( $legacy === $query['nv'], 'Failure must preserve recipient data without a conflicting embedded action.' );
		$assert( '1' === $query['nte'], 'Failure must preserve empty-template selection.' );
	}
	$query = decode_url( get_noptin_action_url( 'manage_preferences' ) );
	$assert( 'manage_preferences' === $query['noptin_ns'] && ! isset( $query['nv'] ), 'Action-only URLs must fall back without inventing recipient data.' );
}
$failure = null;
$query = decode_url( get_noptin_action_url( 'view_in_browser', $legacy ) );
$assert( ! isset( $query['noptin_ns'] ) && 0 === strpos( $query['nv'], 'v1:' ), 'Successful encryption must still use the authenticated format.' );
foreach ( array( false, true ) as $empty_template ) {
	$query = decode_url( get_noptin_action_url( 'view_in_browser', $bound, $empty_template ) );
	$decoded = json_decode( noptin_decrypt( $query['nv'] ), true );
	$assert( ! isset( $query['nte'] ) && ( $empty_template ? 1 === $decoded['nte'] : ! isset( $decoded['nte'] ) ), 'Successful generation must bind template choice and omit its query flag.' );
}
echo "Passed $checks action-token fallback checks.\n";
