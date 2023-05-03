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
		add_action( 'noptin_before_deactivate_subscriber', array( $this, 'maybe_trigger' ) );
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
	 * Called when someone unsubscribes from the newsletter.
	 *
	 * @param int $subscriber The subscriber in question.
	 */
	public function maybe_trigger( $subscriber ) {
		$subscriber = new Noptin_Subscriber( $subscriber );

		// Only trigger if a subscriber is active.
		if ( $subscriber->is_active() ) {
			$this->trigger( $subscriber, array() );
		}

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

		$subject = new Noptin_Subscriber( get_current_noptin_subscriber_id() );
		$args    = $this->prepare_trigger_args( $subject, array() );

		return $args['smart_tags'];
	}
}
