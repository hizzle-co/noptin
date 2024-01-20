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
	 * @inheritdoc
	 */
	public function get_rule_table_description( $rule ) {
		$automated_email_id = $rule->get_action_setting( 'automated_email_id' );

		// Abort if we have no email id.
		if ( empty( $automated_email_id ) ) {
			return sprintf(
				'<span class="noptin-rule-error">%s</span>',
				esc_html__( 'Error: Email not found', 'newsletter-optin-box' )
			);
		}

		$email_campaign = noptin_get_email_campaign_object( $automated_email_id );

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
	 * @inheritdoc
	 */
	public function run( $subject, $rule, $args ) {

		if ( empty( $args['email'] ) || ! is_email( $args['email'] ) ) {
			$args['email'] = $this->get_subject_email( $subject, $rule, $args );
		}

		$args['trigger_id'] = $rule->get_trigger_id();
		$args['rule_id']    = $rule->get_id();
		$campaign           = noptin_get_email_campaign_object( $rule->get_action_setting( 'automated_email_id' ) );

		$args['send_email_to_inactive'] = ! empty( $rule->get_trigger_setting( 'send_email_to_inactive' ) );

		do_action( 'noptin_send_automation_rule_email_' . $rule->get_trigger_id(), $args, $campaign );
	}

	/**
	 * @inheritdoc
	 */
	public function can_run( $subject, $rule, $args ) {
		global $noptin_subscribers_batch_action;

		// Abort if we do not have a campaign.
		$automated_email_id = $rule->get_action_setting( 'automated_email_id' );
		if ( empty( $automated_email_id ) ) {
			return false;
		}

		// ... or if we're importing subscribers.
		if ( 'import' === $noptin_subscribers_batch_action ) {
			return false;
		}

		$campaign = noptin_get_email_campaign_object( $automated_email_id );

		return $campaign->can_send();
	}

	/**
	 * @inheritdoc
	 */
	public function run_if() {
		// translators: %s is a list of conditions.
		return __( 'Sends if %s', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function skip_if() {
		// translators: %s is a list of conditions.
		return __( 'Does not send if %s', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function before_delete( $rule ) {
		$automated_email_id = $rule->get_action_setting( 'automated_email_id' );

		if ( ! empty( $automated_email_id ) ) {
			wp_delete_post( $automated_email_id, true );
		}
	}
}
