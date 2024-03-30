<?php
/**
 * Class SubscriberTest
 *
 * @package Noptin
 */

/**
 * Subscriber test case.
 */
class SubscriberTest extends WP_UnitTestCase {

    /**
     * @var int|string|\WP_Error $subscriber_id
     */
    protected static $subscriber_id;

	public static function wpSetUpBeforeClass(){
        // Create a subscriber for testing.
        self::$subscriber_id = add_noptin_subscriber(
            array(
                'email'       => 'brian@noptin.com',
                'first_name'  => 'Brian',
                'last_name'   => 'Mutende',
                'tags'        => array( 'tag 1', '"Tag 2,' ),
                'nonexisting' => 'example', // If should not throw an error when a non existent field is added.
            )
        );
    }

    public function testCreateNoptinSubscriber() {
        // If subscriber id is a string, it means that the subscriber was not created.
        $this->assertIsInt(self::$subscriber_id, self::$subscriber_id);

        // Compare ids.
        $this->assertEquals( self::$subscriber_id, get_noptin_subscriber_id_by_email( 'brian@noptin.com' ), 'get_noptin_subscriber_id_by_email does not match.' );

        // Fetch the subscriber.
        $subscriber = noptin_get_subscriber(self::$subscriber_id);

        // Assert that the subscriber exists.
        $this->assertTrue($subscriber->exists(), 'Subscriber does not exist.');

        // Assert that the subscriber's email is correct.
        $this->assertEquals( 'brian@noptin.com', $subscriber->get_email() );

        // Assert that the subscriber's first name is correct.
        $this->assertEquals( 'Brian', $subscriber->get_first_name() );

        // Assert that the subscriber's last name is correct.
        $this->assertEquals( 'Mutende', $subscriber->get_last_name() );

        // Assert that the subscriber's tags are correct.
        $this->assertEquals( array( 'tag 1', '"Tag 2,' ), $subscriber->get( 'tags' ) );

        // Check the the subscriber is active.
        $this->assertEquals( 'subscribed', $subscriber->get_status(), 'Subscriber is not active.' );
    }

    public function testUpdateNoptinSubscriber() {
        // Update the subscriber.
        self::$subscriber_id = update_noptin_subscriber(
            self::$subscriber_id,
            array(
                'email'      => 'lewis@noptin.com',
                'first_name' => 'Lewis',
                'last_name'  => 'Ushindi',
                'tags'       => array( 'tag 3', 'tag 4' ),
            )
        );

        // Check if we have a wp_error object.
        if ( is_wp_error( self::$subscriber_id ) ) {
            $this->fail( self::$subscriber_id->get_error_message() );
        }

        $this->assertIsInt(self::$subscriber_id);

        // Fetch the subscriber.
        $subscriber = noptin_get_subscriber(self::$subscriber_id);

        // Assert that the subscriber exists.
        $this->assertTrue($subscriber->exists(), 'Subscriber does not exist.');

        // Assert that the subscriber's email is correct.
        $this->assertEquals( 'lewis@noptin.com', $subscriber->get_email() );

        // Assert that the subscriber's first name is correct.
        $this->assertEquals( 'Lewis', $subscriber->get_first_name() );

        // Assert that the subscriber's last name is correct.
        $this->assertEquals( 'Ushindi', $subscriber->get_last_name() );

        // Assert that the subscriber's tags are correct.
        $this->assertEquals( array( 'tag 3', 'tag 4' ), $subscriber->get( 'tags' ) );

        // Check the the subscriber is active.
        $this->assertEquals( 'subscribed', $subscriber->get_status(), 'Subscriber is not active.' );

        // Update status.
        update_noptin_subscriber_status(self::$subscriber_id, 'unsubscribed');

        $subscriber = noptin_get_subscriber( self::$subscriber_id );

        // Check the the subscriber is active.
        $this->assertEquals( 'unsubscribed', $subscriber->get_status(), 'Subscriber is not unsubscribed.' );

        // Check if activity is updated.
        $last_activity = $subscriber->get_activity();
        $last_activity = end( $last_activity );
        $last_activity = $last_activity ? $last_activity['content'] : '';
        $this->assertEquals( 'Unsubscribed from the newsletter', $last_activity, 'Activity was not updated.' );

        // Resubscribe them.
        resubscribe_noptin_subscriber( self::$subscriber_id );

        $subscriber = noptin_get_subscriber( self::$subscriber_id );

        // Check the the subscriber is active.
        $this->assertEquals( 'subscribed', $subscriber->get_status(), 'Subscriber is not subscribed.' );
    }

    public function testNoptinSubscriberMeta() {

        // Fetch the subscriber.
        $subscriber = noptin_get_subscriber(self::$subscriber_id);

        // Add a meta to the subscriber.
        update_noptin_subscriber_meta(self::$subscriber_id, 'meta_key_2', 'meta_value_2');
        $subscriber->update_meta('meta_key', 'meta_value');
        $result = $subscriber->save();

        if ( is_wp_error( $result ) ) {
            $this->fail( $result->get_error_message() );
        }

        // Assert that the meta was added.
        $this->assertEquals('meta_value', $subscriber->get_meta('meta_key'), 'Subscriber meta was not added ($subscriber->get_meta).');
        $this->assertEquals('meta_value', get_noptin_subscriber_meta(self::$subscriber_id, 'meta_key', true), 'Subscriber meta was not added (get_noptin_subscriber_meta).');
        $this->assertEquals('meta_value_2', get_noptin_subscriber_meta(self::$subscriber_id, 'meta_key_2', true), 'Subscriber meta was not added (get_noptin_subscriber_meta).');

        // Update the meta.
        $subscriber->update_meta('meta_key', 'new_meta_value');
        update_noptin_subscriber_meta(self::$subscriber_id, 'meta_key_2', 'new_meta_value_2');

        // Assert that the meta was updated.
        $this->assertEquals('new_meta_value', $subscriber->get_meta('meta_key'));
        $this->assertEquals('new_meta_value_2', get_noptin_subscriber_meta(self::$subscriber_id, 'meta_key_2', true));

        // Delete the meta.
        $subscriber->remove_meta('meta_key');
        delete_noptin_subscriber_meta(self::$subscriber_id, 'meta_key_2');

        // Assert that the meta was deleted.
        $this->assertEmpty($subscriber->get_meta('meta_key'), 'Subscriber meta was not deleted.');
        $this->assertEmpty(get_noptin_subscriber_meta(self::$subscriber_id, 'meta_key_2', true), 'Subscriber meta was not deleted.');

        // Delete all meta by key.
        update_noptin_subscriber_meta( self::$subscriber_id, 'meta_key_3', 'meta_value_3' );
        delete_noptin_subscriber_meta_by_key( 'meta_key_3' );

        $this->assertFalse( noptin_subscriber_meta_exists( self::$subscriber_id, 'meta_key_3' ), 'Subscriber meta was not deleted.' );
    }

    public function testNoptinDoubleOptin() {

        // Enable double opt in.
        update_noptin_option('double_optin', true);

        // Check if double opt in is enabled.
        $this->assertTrue(noptin_has_enabled_double_optin(), 'Double opt in is not enabled.');

        // Create a new subscriber.
        $subscriber = add_noptin_subscriber(
            array(
                'email' => 'test.do@noptin.com',
            )
        );

        // Confirm that an email was sent.
        $this->assertTrue( 0 < did_action( 'noptin_email_sender_after_sending' ), 'No email was sent.' );

        $subscriber = noptin_get_subscriber($subscriber);

        // Assert that the subscriber exists.
        $this->assertTrue($subscriber->exists(), 'Subscriber does not exist.');

        // Assert that the subscriber is pending.
        $this->assertEquals('pending', $subscriber->get_status(), 'Subscriber is not pending.');

        // And their email is not confirmed.
        $this->assertFalse($subscriber->get_confirmed(), 'Subscriber email is confirmed.');

        // Confirm the subscriber.
        confirm_noptin_subscriber_email( $subscriber->get_id() );

        $subscriber = noptin_get_subscriber($subscriber);

        // Assert that the subscriber is active.
        $this->assertEquals('subscribed', $subscriber->get_status(), 'Subscriber is not active.');

        // And their email is confirmed.
        $this->assertTrue($subscriber->get_confirmed(), 'Subscriber email is not confirmed.');

        // Delete the subscriber.
        delete_noptin_subscriber($subscriber->get_id());

    }

    public function testQueryNoptinSubscribers() {
        update_noptin_subscriber_meta(self::$subscriber_id, 'meta_key', 'meta_value');

        // Query subscribers.
        $subscribers = noptin_get_subscribers(
            array(
                'email'       => 'brian@noptin.com',
                'first_name'  => 'Brian',
                'last_name'   => 'Mutende',
                'tags'        => array( 'tag 1', '"Tag 2,' ),
                'meta_query' => array(
                    array(
                        'key'     => 'meta_key',
                        'value'   => 'meta_value',
                        'compare' => '=',
                    ),
                ),
            )
        );

        foreach ( $subscribers as $subscriber ) {
            echo $subscriber->get_email();
        }

        // Assert that we have one subscriber.
        $this->assertCount(1, $subscribers);

        // Fetch the subscriber.
        $subscriber = current( $subscribers[0] );

        // Assert that the subscriber's email is correct.
        $this->assertEquals( 'brian@noptin.com', $subscriber->get_email() );

        // Query subscribers.
        $subscribers = noptin_get_subscribers(
            array(
                'email'      => 'brian@noptin.com',
                'meta_query' => array(
                    array(
                        'key'     => 'meta_key',
                        'value'   => 'meta_value',
                        'compare' => '!=',
                    ),
                ),
            ),
            'count'
        );

        // Assert that we have no subscriber.
        $this->assertEquals(0, $subscribers);
    }

    public function testMiscNoptinSubscriber() {
        // Fetch the subscriber.
        $subscriber = noptin_get_subscriber(self::$subscriber_id);

        // Assert that the subscriber exists.
        $this->assertTrue(noptin_email_exists( 'brian@noptin.com' ), 'Email should exist.');

        // Assert that the subscriber does not exist.
        $this->assertFalse(noptin_email_exists( 'test.do@noptin.com' ), 'Email should not exist.');

        // Split names.
        $name = noptin_split_subscriber_name( $subscriber->get_name() );

        // Assert that the name is correct.
        $this->assertEquals( array( 'Brian', 'Mutende' ), $name );
    }
}
