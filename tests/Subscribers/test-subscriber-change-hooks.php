<?php

namespace Hizzle\Noptin\Tests\Subscribers;

use WP_UnitTestCase;

/**
 * Tests subscriber change hooks.
 */
class Test_Subscriber_Change_Hooks extends WP_UnitTestCase {

	/**
	 * Subscriber ID created by the test.
	 *
	 * @var int
	 */
	private $subscriber_id;

	public function set_up() {
		parent::set_up();

		$this->subscriber_id = add_noptin_subscriber(
			array(
				'email'      => 'change-hooks@example.com',
				'first_name' => 'Change',
				'last_name'  => 'Hooks',
			)
		);
	}

	public function tear_down() {
		if ( is_int( $this->subscriber_id ) ) {
			delete_noptin_subscriber( $this->subscriber_id );
		}

		parent::tear_down();
	}

	public function test_email_statistics_do_not_fire_subscriber_change_hooks() {
		$updated = 0;
		$saved   = 0;

		$on_updated = function () use ( &$updated ) {
			++$updated;
		};
		$on_saved   = function () use ( &$saved ) {
			++$saved;
		};

		add_action( 'noptin_subscriber_updated', $on_updated );
		add_action( 'noptin_subscriber_saved', $on_saved );

		try {
			$subscriber = noptin_get_subscriber( $this->subscriber_id );
			$subscriber->record_sent_campaign();
			$subscriber->set( 'total_emails_opened', 2 );
			$subscriber->set( 'last_email_opened_date', time() );
			$subscriber->set( 'total_links_clicked', 1 );
			$subscriber->set( 'last_email_clicked_date', time() );
			$subscriber->set( 'email_engagement_score', 0.5 );
			$subscriber->save();

			$this->assertSame( 0, $updated );
			$this->assertSame( 0, $saved );

			$subscriber = noptin_get_subscriber( $this->subscriber_id );
			$this->assertSame( 1, (int) $subscriber->get( 'total_emails_sent' ) );
			$this->assertSame( 2, (int) $subscriber->get( 'total_emails_opened' ) );
			$this->assertSame( 1, (int) $subscriber->get( 'total_links_clicked' ) );
			$this->assertNotEmpty( $subscriber->get( 'last_email_sent_date' ) );
			$this->assertNotEmpty( $subscriber->get( 'last_email_opened_date' ) );
			$this->assertNotEmpty( $subscriber->get( 'last_email_clicked_date' ) );

			$subscriber->set_first_name( 'Updated' );
			$subscriber->save();

			$this->assertSame( 1, $updated );
			$this->assertSame( 1, $saved );
		} finally {
			remove_action( 'noptin_subscriber_updated', $on_updated );
			remove_action( 'noptin_subscriber_saved', $on_saved );
		}
	}
}
