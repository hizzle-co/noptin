<?php

namespace Hizzle\Noptin\Tests\Core;

use WP_UnitTestCase;

/** URL comparisons preserve every part of the URL except a trailing slash. */
class Test_Conditional_Url_Comparison extends WP_UnitTestCase {

	public function test_url_comparisons() {
		$cases = array(
			array( 'https://noptin.test/', 'https://noptin.test/butik/', false ),
			array( 'https://noptin.test/other', 'https://noptin.test/unsubscribe-from-notifications', false ),
			array( 'https://noptin.test/butik/', 'https://noptin.test/butik', true ),
			array( 'https://noptin.test/', 'https://noptin.test', true ),
			array( 'https://noptin.test/butik/?a=1', 'https://noptin.test/butik?a=1', true ),
			array( 'https://noptin.test/butik/#section', 'https://noptin.test/butik#section', true ),
			array( 'https://noptin.test/butik?a=1', 'https://noptin.test/butik?a=2', false ),
			array( 'https://noptin.test/butik#one', 'https://noptin.test/butik#two', false ),
			array( 'https://noptin.test/butik', 'https://other.test/butik', false ),
			array( 'http://noptin.test/butik', 'https://noptin.test/butik', false ),
		);

		// Keep warnings fatal even when this test is run with a different configuration.
		set_error_handler( static function ( $severity, $message, $file, $line ) {
			throw new \ErrorException( $message, 0, $severity, $file, $line );
		} );
		try {
			foreach ( $cases as list( $clicked, $expected, $matches ) ) {
				$this->assertSame( $matches, noptin_is_conditional_logic_met( $clicked, $expected, 'is' ), "$clicked vs $expected" );
			}
		} finally {
			restore_error_handler();
		}
	}
}
