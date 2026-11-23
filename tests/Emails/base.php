<?php
/**
 * Base test case for Noptin.
 */

namespace Hizzle\Noptin\Tests\Bulk_Emails;

use WP_UnitTestCase;
use Hizzle\Noptin\Emails\Bulk\Main;
use Hizzle\Noptin\Emails\Email;

abstract class Noptin_Emails_Test_Case extends WP_UnitTestCase {

	/**
	 * @var Email|null
	 */
	protected $campaign;

	/** @var int User active before the authorized campaign fixture. */
	private $original_user;

	public function set_up() {
		parent::set_up();

		// Publishing a fixture must satisfy the same authorization as a real send.
		$this->original_user = get_current_user_id();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		// Init email senders once.
		if ( ! did_action( 'noptin_init' ) ) {
			Main::init_email_senders();
		}

		// Release any existing lock.
		Main::release_lock();

		// Reset noptin options.
		update_option( 'noptin_options', array() );

		// Create a test campaign.
		$this->campaign = $this->create_test_campaign();
	}

	public function tear_down() {
		// Delete campaign if created.
		if ( $this->campaign && $this->campaign->exists() ) {
			wp_delete_post( $this->campaign->id, true );
		}

		// Clear subscribers.
		noptin()->db()->delete_all( 'subscribers' );

		// Clear email logs.
		noptin()->db()->delete_all( 'email_logs' );

		// Clear background tasks.
		noptin()->db()->delete_all( 'noptin_tasks' );

		// Release any existing lock.
		delete_option( Main::release_lock() );

		wp_set_current_user( $this->original_user );
		parent::tear_down();
	}

	/**
	 * Helper method to create a test email campaign
	 *
	 * @param array $args Optional. Campaign arguments.
	 * @return Email
	 */
	protected function create_test_campaign($args = array()) {
		$default_args = array(
			'author'    => 1,
			'type'      => 'newsletter',
			'status'    => 'publish',
			'name'      => 'Test Campaign',
			'subject'   => 'Test Subject',
			'content'   => 'Test Content',
			'options'   => array(
				'email_sender'   => 'noptin',
				'email_type'     => 'normal',
				'template'       => 'default',
				'content_normal' => 'Test Content',
				'template'       => 'paste',
			),
		);

		$args = wp_parse_args($args, $default_args);
		return new Email($args);
	}

	/**
	 * Create test subscribers.
	 */
	protected function create_test_subscribers( $count ) {
		$ids = array();
		for ( $i = 1; $i <= $count; $i++ ) {
			$ids[] = add_noptin_subscriber(
				array(
					'email'  => "test{$i}@example.com",
					'status' => 'subscribed',
				)
			);
		}

		return $ids;
	}
}
