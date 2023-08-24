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
		add_action( 'noptin_subscriber_created', array( $this, 'maybe_trigger' ), 1000 );

		if ( noptin_has_enabled_double_optin() ) {
			add_action( 'noptin_subscriber_status_set_to_subscribed', array( $this, 'maybe_trigger' ), 1000 );
		}
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
		return __( 'Subscriber > Created', 'newsletter-optin-box' );
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
		if ( noptin_has_enabled_double_optin() ) {

			if ( empty( $settings['fire_after_confirmation'] ) ) {
				return sprintf(
					'%s<br>%s',
					esc_html__( 'Fires before a subscriber confirms their email', 'newsletter-optin-box' ),
					parent::get_rule_table_description( $rule )
				);
			}

			return sprintf(
				'%s<br>%s',
				esc_html__( 'Fires after a subscriber confirms their email', 'newsletter-optin-box' ),
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
	 * Called when someone subscribes to the newsletter.
	 *
	 * @param \Hizzle\Noptin\DB\Subscriber $subscriber The subscriber in question.
	 */
	public function maybe_trigger( $subscriber ) {
		$this->trigger( $subscriber, array( 'email' => $subscriber->get_email() ) );
	}

	/**
	 * Triggers action callbacks.
	 *
	 * @since 1.12.0
	 * @param \Hizzle\Noptin\DB\Subscriber $subject The subject.
	 * @param array $args Extra args for the action.
	 * @return void
	 */
	public function trigger( $subject, $args ) {

		$args = $this->prepare_trigger_args( $subject, $args );

		foreach ( $this->get_rules() as $rule ) {

			// Retrieve the action.
			$action = noptin()->automation_rules->get_action( $rule->action_id );
			if ( empty( $action ) ) {
				continue;
			}

			// Prepare the rule.
			$rule = noptin()->automation_rules->prepare_rule( $rule );

			// Check if we're sending before confirmation or after confirmation.
			if ( noptin_has_enabled_double_optin() ) {

				// Sends before confirmation.
				$sends_before_double_optin = empty( $rule->trigger_settings['fire_after_confirmation'] );
				$has_confirmed             = doing_action( 'noptin_subscriber_status_set_to_subscribed' );

				// If we're sending before confirmation, ensure the subscriber is not active.
				if ( $sends_before_double_optin && $has_confirmed ) {
					continue;
				}

				// If we're sending after confirmation, ensure the subscriber is active.
				if ( ! $sends_before_double_optin && ! $has_confirmed ) {
					continue;
				}
			}

			// Set the current email.
			$GLOBALS['current_noptin_email'] = $subject->get_email();

			// Are we delaying the action?
			$delay = $rule->get_delay();

			if ( $delay > 0 ) {
				do_action( 'noptin_delay_automation_rule_execution', $rule, $args, $delay );
				continue;
			}

			// Ensure that the rule is valid for the provided args.
			if ( $this->is_rule_valid_for_args( $rule, $args, $subject, $action ) ) {
				$action->maybe_run( $subject, $rule, $args );
			}
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

		$subject = noptin_get_subscriber( get_current_noptin_subscriber_id() );
		$args    = $this->prepare_trigger_args( $subject, array( 'email' => $subject->get_email() ) );

		return $args['smart_tags'];
	}
}
