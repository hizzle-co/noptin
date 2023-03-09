<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sends a an email to subjects.
 *
 * @since 1.3.0
 */
class Noptin_Email_Action extends Noptin_Abstract_Action {

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'email';
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'Send Email', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( 'Send an email', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_rule_description( $rule ) {

		$settings = $rule->action_settings;

		if ( empty( $settings['automated_email_id'] ) ) {
			$rule_description = esc_html__( 'send an email', 'newsletter-optin-box' );
		} else {
			$email_subject    = esc_html( get_the_title( $settings['automated_email_id'] ) );
			$rule_description = sprintf(
				// translators: %s is the email subject
				esc_html__( 'send an email with the subject %s', 'newsletter-optin-box' ),
				"<code>$email_subject</code>"
			);
		}

		return apply_filters( 'noptin_email_action_rule_description', $rule_description, $rule );

	}

	/**
	 * @inheritdoc
	 */
	public function get_keywords() {
		return array(
			'noptin',
			'email',
			'send email',
		);
	}

	/**
	 * Sends an email to the subject.
	 *
	 * @since 1.3.0
	 * @param mixed $subject The subject.
	 * @param Noptin_Automation_Rule $rule The automation rule that triggered the action.
	 * @param array $args Extra arguments passed to the action.
	 * @return void
	 */
	public function run( $subject, $rule, $args ) {

		if ( empty( $args['email'] ) || ! is_email( $args['email'] ) ) {
			$args['email'] = $this->get_subject_email( $subject, $rule, $args );
		}

		$args['trigger_id'] = $rule->trigger_id;
		$args['rule_id']    = $rule->id;
		$campaign           = new Noptin_Automated_Email( $rule->action_settings['automated_email_id'] );

		do_action( 'noptin_send_automation_rule_email', $args, $campaign );

	}

	/**
	 * Returns whether or not the action can run (dependancies are installed).
	 *
	 * @since 1.3.3
	 * @param mixed $subject The subject.
	 * @param Noptin_Automation_Rule $rule The automation rule used to trigger the action.
	 * @param array $args Extra arguments passed to the action.
	 * @return bool
	 */
	public function can_run( $subject, $rule, $args ) {

		// Abort if we do not have a campaign.
		if ( empty( $rule->action_settings['automated_email_id'] ) ) {
			return false;
		}

		$campaign = new Noptin_Automated_Email( $rule->action_settings['automated_email_id'] );

		return $campaign->can_send();
	}

}
