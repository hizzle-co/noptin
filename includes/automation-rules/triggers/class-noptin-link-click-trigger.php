<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fires when a subscriber clicks on link.
 *
 * @since 1.2.8
 */
class Noptin_Link_Click_Trigger extends Noptin_Open_Email_Trigger {

	/**
	 * Constructor.
	 *
	 * @since 1.3.0
	 * @return string
	 */
	public function __construct() {
		add_action( 'log_noptin_subscriber_campaign_click', array( $this, 'maybe_trigger_on_click' ), 10, 3 );
	}

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'link_click';
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'Link Click', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( 'When someone clicks on a link in an email', 'newsletter-optin-box' );
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
				'url' => array(
					'description'       => __( 'Clicked URL', 'newsletter-optin-box' ),
					'conditional_logic' => 'string',
				),
			),
			parent::get_known_smart_tags()
		);
	}

	/**
	 * Called when a subscriber clicks on a url.
	 *
	 * @param int $subscriber_id The subscriber in question.
	 * @param $campaign_id The campaign that was clicked.
	 * @param $url The url that was clicked.
	 */
	public function maybe_trigger_on_click( $subscriber_id, $campaign_id, $url ) {

		$subscriber = noptin_get_subscriber( $subscriber_id );
		$args       = array(
			'campaign_id'    => $campaign_id,
			'campaign_title' => get_the_title( $campaign_id ),
			'url'            => $url,
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
			'url'            => 'https://noptin.com',
		);

		$subject = noptin_get_subscriber( get_current_noptin_subscriber_id() );
		$args    = $this->prepare_trigger_args( $subject, $args );

		return $args['smart_tags'];
	}
}
