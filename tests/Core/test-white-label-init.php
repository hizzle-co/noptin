<?php
/**
 * Regression coverage for white-label initialization timing.
 *
 * @package Noptin
 */

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

/** Verify branding is applied on init before the main Noptin initialization. */
class Test_White_Label_Init extends Noptin_Regression_Test_Case {

	/** The helper must no longer read options during noptin_load. */
	public function test_callback_runs_at_start_of_init() {
		$callback = array( 'Noptin_COM_Helper', 'noptin_load' );
		$this->assertFalse( has_action( 'noptin_load', $callback ) );
		$this->assertSame( 0, has_action( 'init', $callback ) );

		$previous_brand = noptin()->white_label;
		try {
			noptin()->white_label = new Noptin_White_Label();
			update_option( 'noptin_helper_data', array( 'license_key' => 'white-label-fixture' ) );
			update_option( 'noptin_options', array( 'white_label' => array( 'name' => 'Custom brand' ) ) );

			// Isolate this callback so replaying init cannot run unrelated plugin setup.
			$GLOBALS['wp_filter']['init'] = new WP_Hook();
			add_action( 'init', $callback, 0 );
			$observed_brand = null;
			add_action( 'init', static function () use ( &$observed_brand ) {
				$observed_brand = noptin()->white_label->name;
			}, 5 );
			do_action( 'init' );
			$this->assertSame( 'Custom brand', $observed_brand );
		} finally {
			noptin()->white_label = $previous_brand;
		}
	}

	/** Sites without a saved license must retain their default branding. */
	public function test_missing_license_preserves_default_brand() {
		$previous_brand = noptin()->white_label;
		try {
			noptin()->white_label = new Noptin_White_Label();
			update_option( 'noptin_helper_data', array() );
			update_option( 'noptin_options', array( 'white_label' => array( 'name' => 'Custom brand' ) ) );
			Noptin_COM_Helper::noptin_load();
			$this->assertSame( 'Noptin', noptin()->white_label->name );
		} finally {
			noptin()->white_label = $previous_brand;
		}
	}
}
