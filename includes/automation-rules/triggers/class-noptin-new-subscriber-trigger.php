<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fired when there is a new subscriber.
 *
 * @since       1.2.8
 */
class Noptin_New_Subscriber_Trigger extends Noptin_Abstract_Trigger {

	/**
     * Whether or not this trigger deals with a subscriber.
     *
     * @var bool
     */
    public $is_subscriber_based = true;

	/**
	 * Constructor.
	 *
	 * @since 1.3.0
	 * @return string
	 */
	public function __construct() {
		add_action( 'noptin_insert_subscriber', array( $this, 'maybe_trigger' ), 1000 );
		add_action( 'noptin_subscriber_confirmed', array( $this, 'maybe_trigger' ), 1000 );
	}

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'new_subscriber';
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'New Subscriber', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( 'Fired when there is a new subscriber', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_rule_description( $rule ) {
		return __( 'When someone subscribes to the newsletter', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_image() {
		return '';
	}

	/**
	 * @inheritdoc
	 */
	public function get_keywords() {
		return array(
			'noptin',
			'subscriber',
			'new',
		);
	}

	/**
	 * Called when someone subscribes to the newsletter.
	 *
	 * @param int $subscriber The subscriber in question.
	 */
	public function maybe_trigger( $subscriber ) {
		$subscriber = new Noptin_Subscriber( $subscriber );

		// Only trigger if a subscriber is active.
		if ( $subscriber->is_active() ) {
			$this->trigger( $subscriber, $subscriber->to_array() );
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
