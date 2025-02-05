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

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

if (!function_exists('namespace_function_include')) {
    function namespace_function_include($function_name, $function_implementation) {
        $function_name = ltrim($function_name, '\\');
        $namespace = __NAMESPACE__;

        if (!empty($namespace)) {
            $namespace .= '\\';
        }

        $code = sprintf(
            'namespace %s { function %s() { $args = func_get_args(); return ($GLOBALS["%s"])(...$args); } }',
            $namespace,
            $function_name,
            "_mock_function_$function_name"
        );

        $GLOBALS["_mock_function_$function_name"] = $function_implementation;
        eval($code);
    }
}

if (!function_exists('namespace_function_restore')) {
    function namespace_function_restore($function_name) {
        $function_name = ltrim($function_name, '\\');
        unset($GLOBALS["_mock_function_$function_name"]);
    }
}

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/noptin.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";
