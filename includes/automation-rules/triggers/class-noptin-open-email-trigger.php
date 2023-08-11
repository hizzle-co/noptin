<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fires when a subscriber opens an email address.
 *
 * @since       1.2.8
 */
class Noptin_Open_Email_Trigger extends Noptin_Abstract_Trigger {

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
		add_action( 'log_noptin_subscriber_campaign_open', array( $this, 'maybe_trigger_on_open' ), 10, 2 );
	}

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'open_email';
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'Open Email', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( 'When someone opens an email campaign', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_image() {
		return plugin_dir_url( Noptin::$file ) . 'includes/assets/images/email-icon.png';
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
				'campaign_id'    => array(
					'description'       => __( 'Campaign ID', 'newsletter-optin-box' ),
					'conditional_logic' => 'number',
				),
				'campaign_title' => array(
					'description'       => __( 'Campaign Title', 'newsletter-optin-box' ),
					'conditional_logic' => 'string',
				),
			),
			parent::get_known_smart_tags()
		);
	}

	/**
	 * Called when a subscriber opens an email.
	 *
	 * @param int $subscriber_id The subscriber in question.
	 * @param $campaign_id The campaign that was clicked.
	 */
	public function maybe_trigger_on_open( $subscriber_id, $campaign_id ) {

		$subscriber = noptin_get_subscriber( $subscriber_id );

		$args = array(
			'campaign_id'    => $campaign_id,
			'campaign_title' => get_the_title( $campaign_id ),
		);

		$this->trigger( $subscriber, $args );
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
		$args = array(
			'campaign_id'    => 1,
			'campaign_title' => 'Test Campaign',
		);

		$subject = noptin_get_subscriber( get_current_noptin_subscriber_id() );
		$args    = $this->prepare_trigger_args( $subject, $args );

		return $args['smart_tags'];
	}
}
