<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fires when a subscriber clicks on link.
 *
 * @since 1.2.8
 */
class Noptin_Link_Click_Trigger extends Noptin_Abstract_Trigger {

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
		add_action( 'log_noptin_subscriber_campaign_click', array( $this, 'maybe_trigger' ), 10, 3 );
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
		return __( 'When a subscriber clicks on a link in an email', 'newsletter-optin-box' );
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
				'url'            => array(
					'description'       => __( 'Clicked URL', 'newsletter-optin-box' ),
					'conditional_logic' => 'string',
				),
			),
			parent::get_known_smart_tags()
		);
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
			'click',
			'link',
		);
	}

	/**
	 * @inheritdoc
	 */
	public function settings_to_conditional_logic( $settings ) {

		// We have no conditional logic here.
		if ( ! is_array( $settings ) || ( ! isset( $settings['campaign_id'] ) && ! isset( $settings['url'] ) ) ) {
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

		// URL.
		if ( isset( $settings['url'] ) ) {

			if ( ! empty( $settings['url'] ) ) {
				$conditions[] = array(
					'type'      => 'url',
					'condition' => 'is',
					'value'     => trim( $settings['url'] ),
				);
			}

			unset( $settings['url'] );
		}

		return array(
			'conditional_logic' => $conditions,
			'settings'          => $settings,
		);
	}

	/**
	 * Called when a subscriber clicks on a url.
	 *
	 * @param int $subscriber_id The subscriber in question.
	 * @param $campaign_id The campaign that was clicked.
	 * @param $url The url that was clicked.
	 */
	public function maybe_trigger( $subscriber_id, $campaign_id, $url ) {

		$subscriber = new Noptin_Subscriber( $subscriber_id );
		$args       = array(
			'campaign_id'    => $campaign_id,
			'campaign_title' => get_the_title( $campaign_id ),
			'url'            => $url,
		);

		noptin_record_subscriber_activity(
			$subscriber_id,
			sprintf(
				// translators: %2 is the campaign name, #1 is the link.
				__( 'Clicked on link %1$s from campaign %2$s', 'newsletter-optin-box' ),
				'<code>' . esc_url( $url ) . '</code>',
				'<code>' . get_the_title( $campaign_id ) . '</code>'
			)
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

		$subject = new Noptin_Subscriber( get_current_noptin_subscriber_id() );
		$args    = $this->prepare_trigger_args( $subject, $args );

		return $args['smart_tags'];
	}
}
