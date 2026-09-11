<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package Noptin
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
if ( false !== $_phpunit_polyfills_path ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path );
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

define( 'NOPTIN_ENABLE_FOREGROUND_SENDING', true );
define( 'NOPTIN_DISABLE_TASK_DUPLICATE_CHECK', true );

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/noptin.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

/**
 * Captures emails without handing them to wp_mail().
 *
 * Tests can set Noptin_Test_Email_Sender::$result before sending to control the
 * value returned by the fake transport.
 */
class Noptin_Test_Email_Sender {

	public static $headers = array();
	public static $subject = '';
	public static $message = '';
	public static $recipients = array();
	public static $attachments = array();
	public static $result = true;

	/**
	 * Registers the fake transport for the current test.
	 */
	public static function register() {
		add_filter( 'noptin_email_sending_function', array( __CLASS__, 'filter_sending_function' ), 1000 );
	}

	/**
	 * Removes the fake transport.
	 */
	public static function unregister() {
		remove_filter( 'noptin_email_sending_function', array( __CLASS__, 'filter_sending_function' ), 1000 );
	}

	/**
	 * Resets captured email data and the transport result.
	 */
	public static function reset() {
		self::$headers     = array();
		self::$subject     = '';
		self::$message     = '';
		self::$recipients  = array();
		self::$attachments = array();
		self::$result      = true;
	}

	/**
	 * Replaces wp_mail() with the fake transport.
	 */
	public static function filter_sending_function() {
		return array( __CLASS__, 'send' );
	}

	/**
	 * Captures the arguments passed to the selected email transport.
	 */
	public static function send( $recipients, $subject, $message, $headers, $attachments ) {
		self::$recipients  = $recipients;
		self::$subject     = $subject;
		self::$message     = $message;
		self::$headers     = $headers;
		self::$attachments = $attachments;

		return self::$result;
	}
}

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";
