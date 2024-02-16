<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fired when a subscriber is imported.
 *
 * @since 3.0.1
 */
class Noptin_Import_Subscriber_Trigger extends Noptin_Abstract_Trigger {

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
	 * @since 1.3.0
	 * @return string
	 */
	public function __construct() {
		add_action( 'noptin_subscribers_import_item', array( $this, 'maybe_trigger' ), 10, 2 );
	}

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'import_subscriber';
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'Subscriber > Imported', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( 'When a subscriber is imported', 'newsletter-optin-box' );
	}

	/**
	 * Returns an array of known smart tags.
	 *
	 * @since 1.10.1
	 * @return array
	 */
	public function get_known_smart_tags() {

		return array_merge(
			array(
				'subscriber.action' => array(
					'description'       => __( 'Action', 'newsletter-optin-box' ),
					'conditional_logic' => 'string',
					'option'            => array(
						'updated' => __( 'Updated', 'newsletter-optin-box' ),
						'created' => __( 'Created', 'newsletter-optin-box' ),
					),
				),
			),
			parent::get_known_smart_tags()
		);
	}

	/**
	 * Called when someone subscribes to the newsletter.
	 *
	 * @param int $subscriber The subscriber in question.
	 * @param string $action The action that was performed.
	 */
	public function maybe_trigger( $subscriber, $action ) {
		$subscriber = noptin_get_subscriber( $subscriber );

		if ( ! $subscriber->exists() ) {
			return;
		}

		$this->trigger(
			$subscriber,
			array(
				'email'             => $subscriber->get_email(),
				'subscriber.action' => $action,
			)
		);
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
		$args    = $this->prepare_trigger_args(
			$subject,
			array(
				'email'             => $subject->get_email(),
				'subscriber.action' => 'created',
			)
		);

		return $args['smart_tags'];
	}
}
