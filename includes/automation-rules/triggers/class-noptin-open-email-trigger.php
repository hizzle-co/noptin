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
		add_action( 'log_noptin_subscriber_campaign_open', array( $this, 'maybe_trigger' ), 10, 2 );
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
		return __( 'When a subscriber opens an email campaign', 'newsletter-optin-box' );
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
			'open',
			'email',
		);
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
	 * @inheritdoc
	 */
	public function settings_to_conditional_logic( $settings ) {

		// We have no conditional logic here.
		if ( ! is_array( $settings ) || ! isset( $settings['campaign_id'] ) ) {
			return false;
		}

		$conditions = array();

		// Campaign ID.
		if ( isset( $settings['campaign_id'] ) ) {

			if ( ! empty( $settings['campaign_id'] ) ) {
				$conditions[] = array(
					'type'      => 'campaign_id',
					'condition' => 'is',
					'value'     => (int) $settings['campaign_id'],
				);
			}

			unset( $settings['campaign_id'] );
		}

		return array(
			'conditional_logic' => $conditions,
			'settings'          => $settings,
		);
	}

	/**
	 * Called when a subscriber opens an email.
	 *
	 * @param int $subscriber_id The subscriber in question.
	 * @param $campaign_id The campaign that was clicked.
	 */
	public function maybe_trigger( $subscriber_id, $campaign_id ) {

		$subscriber = new Noptin_Subscriber( $subscriber_id );

		$args = array_merge(
			$subscriber->to_array(),
			array(
				'campaign_id'    => $campaign_id,
				'campaign_title' => get_the_title( $campaign_id ),
			)
		);

		noptin_record_subscriber_activity(
			$subscriber_id,
			sprintf(
				// translators: %s is the campaign name.
				__( 'Opened email campaign %s', 'newsletter-optin-box' ),
				'<code>' . get_the_title( $campaign_id ) . '</code>'
			)
		);

		$this->trigger( $subscriber, $args );
	}

}
