<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Base actions class.
 *
 * @since       1.2.8
 */
abstract class Noptin_Abstract_Action extends Noptin_Abstract_Trigger_Action {

	/**
	 * @var bool
	 */
	public $is_action_or_trigger = 'action';

	/**
	 * Groups rule action into a single string.
	 *
	 * @since 1.11.9
	 * @param array $meta
	 * @param \Hizzle\Noptin\DB\Automation_Rule $rule
	 * @return string
	 */
	public function rule_action_meta( $meta, $rule ) {

		// Add delay.
		$delay = $rule->get_delay();

		if ( $delay > 0 ) {
			$meta[ __( 'Delay', 'newsletter-optin-box' ) ] = human_time_diff( time(), time() + $delay );
		}

		$meta = apply_filters( 'noptin_rule_action_meta_' . $this->get_id(), $meta, $rule, $this );
		$meta = apply_filters( 'noptin_rule_action_meta', $meta, $rule, $this );

		return $this->prepare_rule_meta( $meta, $rule );
	}

	/**
	 * Returns whether or not the action can run (dependancies are installed).
	 *
	 * @since 1.2.8
	 * @param mixed $subject The subject.
	 * @param \Hizzle\Noptin\DB\Automation_Rule $rule The automation rule that triggered the action.
	 * @param array $args Extra arguments passed to the action.
	 * @return bool
	 */
	public function can_run( $subject, $rule, $args ) {
		return true;
	}

	/**
	 * (Maybe) run the action.
	 *
	 * @since 1.3.0
	 * @param mixed $subject The subject.
	 * @param \Hizzle\Noptin\DB\Automation_Rule $rule
	 * @param array $args Extra arguments passed to the action.
	 */
	public function maybe_run( $subject, $rule, $args ) {
		// Set the current email.
		$GLOBALS['current_noptin_email'] = $this->get_subject_email( $subject, $this, $args );

		// Ensure that we can run the action.
		if ( ! $this->can_run( $subject, $rule, $args ) ) {
			return false;
		}

		// Run the action.
		$result = $this->run( $subject, $rule, $args );

		if ( is_wp_error( $result ) ) {
			log_noptin_message( $result->get_error_message() );
		}

		// Update the run counts.
		$rule->set_times_run( $rule->get_times_run() + 1 );
		$rule->save();

		// Record activity.
		$subject_email = $this->get_subject_email( $subject, $rule, $args );
		if ( is_email( $subject_email ) ) {
			$action  = $this->get_name();
			$trigger = $rule->get_trigger();
			$trigger = $trigger ? $trigger->get_name() : $rule->get_trigger_id();

			noptin_record_subscriber_activity(
				$subject_email,
				sprintf(
					// translators: %1 is the trigger, %2 is the action.
					__( 'Excecuted automation rule, Trigger: %1$s, Action: %2$s.', 'newsletter-optin-box' ),
					'<code>' . esc_html( $trigger ) . '</code>',
					'<code>' . esc_html( $action ) . '</code>'
				) . ( is_wp_error( $result ) ? ' <span style="color: red;">' . $result->get_error_message() . '</span>' : '' )
			);
		}

		return $result;
	}

	/**
	 * Runs the action.
	 *
	 * @since 1.2.8
	 * @param mixed $subject The subject.
	 * @param \Hizzle\Noptin\DB\Automation_Rule $rule The automation rule.
	 * @param array $args Extra arguments passed to the action.
	 * @return void|bool|WP_Error
	 */
	abstract public function run( $subject, $rule, $args );

	/**
	 * The prefix used for the action's conditional logic.
	 *
	 * @return string
	 */
	public function run_if() {
		// translators: %s is a list of conditions.
		return __( 'Runs if %s', 'newsletter-optin-box' );
	}

	/**
	 * The prefix used for the action's conditional logic.
	 *
	 * @return string
	 */
	public function skip_if() {
		// translators: %s is a list of conditions.
		return __( 'Does not run if %s', 'newsletter-optin-box' );
	}

	/**
	 * Fired before deleting an automation rule.
	 *
	 * @param \Hizzle\Noptin\DB\Automation_Rule $rule The automation rule.
	 */
	public function before_delete( $rule ) {
		// Override this method to perform actions before deleting an automation rule.
	}
}
