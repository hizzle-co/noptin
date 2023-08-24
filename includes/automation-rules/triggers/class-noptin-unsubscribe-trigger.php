<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fired when there is a a subscriber is deactivated.
 *
 * @since       1.3.1
 */
class Noptin_Unsubscribe_Trigger extends Noptin_Abstract_Trigger {

	/**
	 * @var string
	 */
	public $category = 'Subscribers';

	/**
     * Whether or not this trigger deals with a subscriber.
     *
     * @var bool
     */
    public $is_subscriber_based = true;

	/**
	 * Constructor.
	 *
	 * @since 1.3.1
	 * @return string
	 */
	public function __construct() {
		add_action( 'noptin_subscriber_status_set_to_unsubscribed', array( $this, 'maybe_trigger' ) );
	}

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'unsubscribe';
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'Subscriber > Unsubscribed', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( 'When someone unsubscribes', 'newsletter-optin-box' );
	}

	/**
	 * Called when someone subscribes to the newsletter.
	 *
	 * @param \Hizzle\Noptin\DB\Subscriber $subscriber The subscriber in question.
	 */
	public function maybe_trigger( $subscriber ) {
		$this->trigger( $subscriber, array( 'email' => $subscriber->get_email() ) );
	}

	/**
	 * Prepares email test data.
	 *
	 * @since 1.11.0
	 * @param Noptin_Automation_Rule $rule
	 * @return Noptin_Automation_Rules_Smart_Tags
	 * @throws Exception
	 */
	public function get_test_smart_tags( $rule ) {

		$subject = noptin_get_subscriber( get_current_noptin_subscriber_id() );
		$args    = $this->prepare_trigger_args( $subject, array() );

		return $args['smart_tags'];
	}
}
