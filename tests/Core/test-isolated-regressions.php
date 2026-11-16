<?php

/** First-party regressions whose WordPress doubles require a clean PHP process. */
class Test_Noptin_Isolated_Regressions extends WP_UnitTestCase {
	/** List standalone fixtures that operate only on packaged Noptin code. */
	public static function regression_cases() {
		return array(
			'action-token-fallback' => array( 'action-token-fallback' ),
			'bulk-campaign-selection' => array( 'bulk-campaign-selection' ),
			'bulk-email-recovery' => array( 'bulk-email-recovery' ),
			'campaign-browser-poll' => array( 'campaign-browser-poll' ),
			'task-cron-diagnostics' => array( 'task-cron-diagnostics' ),
			'user-collection-ownership' => array( 'user-collection-ownership' ),
		);
	}

	/**
	 * Run doubles outside the WordPress process and report captured diagnostics.
	 *
	 * @dataProvider regression_cases
	 * @param string $fixture Fixture basename.
	 */
	public function test_isolated_regression( $fixture ) {
		$file = dirname( __DIR__ ) . '/fixtures/isolated/' . $fixture . '.php';
		$process = proc_open( array( PHP_BINARY, $file ), array( 0 => array( 'pipe', 'r' ), 1 => array( 'pipe', 'w' ), 2 => array( 'pipe', 'w' ) ), $pipes );
		$this->assertIsResource( $process );
		fclose( $pipes[0] );
		$output = stream_get_contents( $pipes[1] );
		$error = stream_get_contents( $pipes[2] );
		fclose( $pipes[1] );
		fclose( $pipes[2] );
		$this->assertSame( 0, proc_close( $process ), $output . $error );
		$this->assertNotEmpty( trim( $output ), 'The regression must reach its completion message.' );
		$this->assertSame( '', $error, 'Isolated regressions must not emit PHP diagnostics.' );
	}
}
