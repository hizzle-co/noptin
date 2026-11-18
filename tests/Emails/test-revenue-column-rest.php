<?php

require_once dirname( __DIR__ ) . '/includes/class-regression-test-case.php';

/** First-party regression coverage migrated from revenue-column-rest.php. */
class Test_Revenue_Column_REST extends Noptin_Regression_Test_Case {
	/** Verify revenue column rest using the isolated WordPress test database. */
	public function test_revenue_visibility_respects_license_and_tracking_settings() {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		remove_all_actions( 'noptin_newsletter_campaign_published' );
		remove_all_actions( 'noptin_send_newsletter' );
		add_filter( 'noptin_saved_task_background_run', '__return_false' );
		remove_action( 'shutdown', array( $GLOBALS['noptin_tasks'], 'run_overdue_tasks_on_shutdown' ), 1 );
		add_filter( 'pre_wp_mail', '__return_false', PHP_INT_MAX );
		add_filter( 'pre_http_request', static function () { return new WP_Error( 'test_no_network', 'Network disabled during checks.' ); } );
		$licensed = false;
		$supported = false;
		$enabled = false;
		add_filter( 'pre_option_noptin_helper_data', static function ( $options ) use ( &$licensed ) {
			$options = is_array( $options ) ? $options : array();
			unset( $options['license_key'], $options['active_license_keys'] );
			if ( $licensed ) $options['license_key'] = 'local-test-fixture';
			return $options;
		} );
		add_filter( 'noptin_supports_ecommerce_tracking', static function () use ( &$supported ) { return $supported; }, PHP_INT_MAX );
		add_filter( 'get_noptin_option_enable_ecommerce_tracking', static function () use ( &$enabled ) { return $enabled; }, PHP_INT_MAX );
		$id = wp_insert_post( array( 'post_type' => 'noptin-campaign', 'post_status' => 'draft', 'post_title' => 'Revenue visibility fixture' ), true );
		if ( is_wp_error( $id ) ) throw new RuntimeException( $id->get_error_message() );
		try {
			update_post_meta( $id, 'campaign_type', 'newsletter' );
			foreach ( array( false, true ) as $licensed ) {
				foreach ( array( false, true ) as $supported ) {
					foreach ( array( false, true ) as $enabled ) {
						$request = new WP_REST_Request( 'GET', '/wp/v2/noptin-campaign/' . $id );
						$request->set_query_params( array( 'context' => 'edit', '_fields' => 'id,noptin_list' ) );
						$response = rest_do_request( $request );
						$expected = $supported && ( ! $licensed || $enabled );
						$this->assertSame( $licensed, noptin_has_alk(), 'License fixture must control the saved-key state.' );
						$this->assertSame( 200, $response->get_status() );
						$this->assertSame( $expected, $response->get_data()['noptin_list']['show_revenue'], 'Revenue visibility must respect support, license, and tracking settings.' );
					}
				}
			}
		} finally {
			wp_delete_post( $id, true );
		}
	}
}
