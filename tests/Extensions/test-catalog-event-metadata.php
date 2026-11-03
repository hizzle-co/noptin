<?php

namespace Hizzle\Noptin\Tests\Extensions;

use Hizzle\Noptin\Extensions\Catalog;
use WP_UnitTestCase;

/** Local events retain ownership without overriding public catalog metadata. */
class Test_Catalog_Event_Metadata extends WP_UnitTestCase {

	public function test_local_event_metadata() {
		$event = new class() {
			public $depricated = false;
			public $category = 'Local';
			public $integration = 'dynamic_owner';
			public $connection = null;
			public $featured = false;
			public $premium = true;
			public $is_recommended = true;

			public function get_id() { return 'actual_dynamic_id'; }
			public function get_name() { return 'Actual event'; }
			public function get_description() { return 'Local registration'; }
			public function get_image() { return ''; }
			public function get_settings() { return array(); }
			public function has_wizard_settings() { return false; }
		};

		$local = Catalog::events( array( $event ) );
		$this->assertArrayHasKey( 'actual_dynamic_id', $local );
		foreach ( array( 'premium', 'is_recommended', 'featured' ) as $flag ) {
			$this->assertArrayNotHasKey( $flag, $local['actual_dynamic_id'] );
		}
		$this->assertSame( 'dynamic-owner', $local['actual_dynamic_id']['integration'] );
		$this->assertSame( 'Actual event', $local['actual_dynamic_id']['label'] );
	}
}
