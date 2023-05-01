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
		return __( 'When someone subscribes to the newsletter', 'newsletter-optin-box' );
	}

	/**
	 * Retrieve the trigger's rule table description.
	 *
	 * @since 1.11.9
	 * @param Noptin_Automation_Rule $rule
	 * @return array
	 */
	public function get_rule_table_description( $rule ) {
		$settings = $rule->trigger_settings;

		// Check if we're sending before confirmation.
		if ( noptin_has_enabled_double_optin() && empty( $settings['fire_after_confirmation'] ) ) {
			return sprintf(
				'%s<br>%s',
				esc_html__( 'Fires before a subscriber confirms their email', 'newsletter-optin-box' ),
				parent::get_rule_table_description( $rule )
			);
		}

		return parent::get_rule_table_description( $rule );
	}

	/**
	 * @inheritdoc
	 */
	public function get_settings() {

		$settings = array();

		// Allow option to send to users who've not confirmed their subscription.
		if ( noptin_has_enabled_double_optin() ) {

			$settings['fire_after_confirmation'] = array(
				'el'      => 'input',
				'type'    => 'checkbox',
				'label'   => __( 'Fire after someone confirms their subscription via double opt-in', 'newsletter-optin-box' ),
				'default' => true,
			);
		}

		return array_merge( $settings, parent::get_settings() );
	}

	/**
	 * Checks if settings are met.
	 *
	 * @since 1.2.8
	 * @param Noptin_Automation_Rule $rule The rule to check for.
	 * @param mixed $args Extra args for the action.
	 * @param Noptin_Subscriber $subject The subject.
	 * @param Noptin_Abstract_Action $action The action to run.
	 * @return bool
	 */
	public function is_rule_valid_for_args( $rule, $args, $subject, $action ) {

		if ( noptin_has_enabled_double_optin() && ! empty( $rule->trigger_settings['fire_after_confirmation'] ) && ! $subject->is_active() ) {
			return false;
		}

		return parent::is_rule_valid_for_args( $rule, $args, $subject, $action );
	}

	/**
	 * Called when someone subscribes to the newsletter.
	 *
	 * @param int $subscriber The subscriber in question.
	 */
	public function maybe_trigger( $subscriber ) {
		$this->trigger( new Noptin_Subscriber( $subscriber ), array() );
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
