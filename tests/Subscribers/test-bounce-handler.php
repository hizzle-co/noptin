<?php
namespace Hizzle\Noptin\Tests\Subscribers;

use WP_UnitTestCase;
use WP_REST_Request;
use Hizzle\Noptin\Subscribers\Bounce_Handler;

/**
 * Bounce handler test case.
 */
class Test_BounceHandler extends WP_UnitTestCase {

    /**
     * @var int Subscriber ID.
     */
    protected static $subscriber_id;

	public static function wpSetUpBeforeClass() {
        // Create a test subscriber.
        self::$subscriber_id = add_noptin_subscriber( array(
            'email'      => 'bounce@example.com',
            'first_name' => 'Bouncer',
            'last_name'  => 'Test',
        ) );
    }

    public function testMailgunBounceHandling() {
        $request = new WP_REST_Request( 'POST', '/noptin/v1/bounce_handler/mailgun/dummy_code' );
        $request->set_body_params( array(
            'event-data' => array(
                'event'    => 'failed',
                'severity' => 'permanent',
                'recipient' => 'bounce@example.com',
                'user-variables' => array(
                    'noptin_campaign_id' => 123
                ),
            ),
        ) );

       Bounce_Handler::handle_mailgun( $request );

        $subscriber = noptin_get_subscriber( self::$subscriber_id );

        $this->assertEquals( 'bounced', $subscriber->get_status(), 'Subscriber was not marked as bounced.' );
    }

    public function testSendgridSpamComplaint() {
        $request = new WP_REST_Request( 'POST', '/noptin/v1/bounce_handler/sendgrid/dummy_code' );
        $request->set_body( json_encode( array(
            array(
                'event' => 'spamreport',
                'email' => 'bounce@example.com'
            )
        ) ) );
        $request->set_header( 'Content-Type', 'application/json' );

       Bounce_Handler::handle_sendgrid( $request );

        $subscriber = noptin_get_subscriber( self::$subscriber_id );
        $this->assertEquals( 'complained', $subscriber->get_status(), 'Subscriber was not marked as complained.' );
    }

    public function testElasticEmailUnsubscribe() {
        $request = new WP_REST_Request( 'POST', '/noptin/v1/bounce_handler/elasticemail/dummy_code' );
        $request->set_body_params( array(
            'status'   => 'unsubscribed',
            'to'       => 'bounce@example.com',
            'category' => 'ManualCancel',
        ) );

        Bounce_Handler::handle_elasticemail( $request );

        $subscriber = noptin_get_subscriber( self::$subscriber_id );
        $this->assertEquals( 'unsubscribed', $subscriber->get_status(), 'Subscriber was not unsubscribed.' );
    }
}
