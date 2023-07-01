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
	public function get_image() {
		return plugin_dir_url( Noptin::$file ) . 'includes/assets/images/email-icon.png';
	}

	/**
	 * Retrieve the actions's rule table description.
	 *
	 * @since 1.11.9
	 * @param Noptin_Automation_Rule $rule
	 * @return array
	 */
	public function get_rule_table_description( $rule ) {
		$settings = $rule->action_settings;

		// Abort if we have no email id.
		if ( empty( $settings['automated_email_id'] ) ) {
			return sprintf(
				'<span class="noptin-rule-error">%s</span>',
				esc_html__( 'Error: Email not found', 'newsletter-optin-box' )
			);
		}

		$email_campaign = new Noptin_Automated_Email( $settings['automated_email_id'] );

		// Abort if it doesn't exist.
		if ( ! $email_campaign->exists() ) {
			return sprintf(
				'<span class="noptin-rule-error">%s</span>',
				esc_html__( 'Error: Email not found', 'newsletter-optin-box' )
			);
		}

		$email_subject = $email_campaign->get_subject();

		// Abort if subject is empty.
		if ( empty( $email_subject ) ) {
			return sprintf(
				'<span class="noptin-rule-error">%s</span>',
				esc_html__( 'Error: Email subject is empty', 'newsletter-optin-box' )
			);
		}

		$recipients = $email_campaign->get_recipients();

		// Abort if recipients is empty.
		if ( empty( $recipients ) ) {
			return sprintf(
				'<span class="noptin-rule-error">%s</span>',
				esc_html__( "Error: You've not specified the email recipients", 'newsletter-optin-box' )
			);
		}

		$meta = array(
			esc_html__( 'Subject', 'newsletter-optin-box' )      => esc_html( $email_subject ),
			esc_html__( 'Recipient(s)', 'newsletter-optin-box' ) => esc_html( $recipients ),
		);

		return $this->rule_action_meta( $meta, $rule );
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
		global $noptin_subscribers_batch_action;

		// Abort if we do not have a campaign.
		if ( empty( $rule->action_settings['automated_email_id'] ) ) {
			return false;
		}

		// ... or if we're importing subscribers.
		if ( 'import' === $noptin_subscribers_batch_action ) {
			return false;
		}

		$campaign = new Noptin_Automated_Email( $rule->action_settings['automated_email_id'] );

		return $campaign->can_send();
	}

}
