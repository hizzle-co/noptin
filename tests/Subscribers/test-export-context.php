<?php

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

/** Exposes the configured REST path without assuming an API version. */
class Noptin_Export_Test_Controller extends \Hizzle\Store\REST_Controller {
	/** Get the collection path. @return string */
	public function collection_route() {
		return '/' . $this->namespace . '/' . $this->rest_base;
	}
}

/** REST and queued subscriber exports use the same representation and columns. */
class Test_Noptin_Subscriber_Export_Context extends Noptin_Regression_Test_Case {
	/** @var int[] Subscriber fixture IDs. */
	private $subscriber_ids = array();

	/** @var string[] Queued job IDs to clean up. */
	private $job_ids = array();

	/** Remove generated CSV files, cron events, and subscriber fixtures. */
	public function tear_down() {
		foreach ( $this->job_ids as $job_id ) {
			\Hizzle\Store\Export::cleanup_job( $job_id );
			wp_clear_scheduled_hook( \Hizzle\Store\Export::CRON_HOOK, array( $job_id ) );
			wp_clear_scheduled_hook( \Hizzle\Store\Export::CLEANUP_HOOK, array( $job_id ) );
		}
		foreach ( $this->subscriber_ids as $id ) {
			noptin_get_subscriber( $id )->delete( true );
		}
		noptin_flush_subscriber_caches();
		parent::tear_down();
	}

	/** Export context is validated, selects columns, and preserves all rows across batches. */
	public function test_foreground_and_background_exports() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		for ( $index = 0; $index < 3; ++$index ) {
			$record = noptin()->db()->get( 0, 'subscribers' );
			$record->set_email( 'export-' . $index . '-' . wp_generate_uuid4() . '@example.test' );
			$record->set( 'tags', array( 'First choice', 'Second choice' ) );
			$record->save();
			$this->subscriber_ids[] = $record->get_id();
		}

		$controller = new Noptin_Export_Test_Controller( 'noptin', 'subscribers' );
		$fields = array( 'tags', 'email', 'id' );
		$query = array(
			'context' => 'export', '_fields' => implode( ',', $fields ),
			'include' => $this->subscriber_ids, 'orderby' => 'id', 'order' => 'asc', 'per_page' => -1,
		);
		$request = new WP_REST_Request( 'GET', $controller->collection_route() );
		$request->set_query_params( $query );
		$response = rest_do_request( $request );
		$this->assertFalse( $response->is_error(), wp_json_encode( $response->get_data() ) );
		$foreground = $response->get_data();
		$this->assertCount( 3, $foreground );
		$this->assertSame( 'First choice,Second choice', $foreground[0]['tags'] );
		$this->assertSame( array( 'id', 'email', 'tags' ), array_keys( $foreground[0] ) );

		// Small batches exercise cursor advancement instead of completing from one query.
		add_filter( 'hizzle_store_background_export_batch_size', static function () { return 1; } );
		$job_id = \Hizzle\Store\Export::queue( 'noptin', 'subscribers', $query );
		$this->assertNotFalse( $job_id );
		$this->job_ids[] = $job_id;
		$job = get_option( \Hizzle\Store\Export::OPTION_PREFIX . $job_id );
		$this->assertSame( $fields, $job['fields'] );
		$this->assertSame( 'export', $job['query_args']['context'] );
		$this->assertSame( 0, $job['query_args']['offset'] );
		\Hizzle\Store\Export::run( $job_id );
		$handle = fopen( $job['file_path'], 'rb' );
		try {
			$this->assertSame( $fields, fgetcsv( $handle ) );
			foreach ( $foreground as $record ) {
				$this->assertSame( array( $record['tags'], $record['email'], (string) $record['id'] ), fgetcsv( $handle ) );
			}
			$this->assertFalse( fgetcsv( $handle ), 'Export duplicated or added records.' );
		} finally {
			fclose( $handle );
		}
	}
}
