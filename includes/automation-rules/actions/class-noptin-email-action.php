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
	public function run( $subject, $rule, $args ) {

		if ( ! empty( $args['post_meta'] ) ) {
			update_post_meta( (int) $args['post_meta']['id'], $args['post_meta']['key'], array( (int) $args['post_meta']['id'], (int) $rule->get_action_setting( 'automated_email_id' ) ) );
		}

		return noptin_send_email_campaign(
			$rule->get_action_setting( 'automated_email_id' ),
			isset( $args['smart_tags'] ) ? $args['smart_tags'] : null
		);
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
		if ( 'import' === $noptin_subscribers_batch_action && 'new_subscriber' === $rule->get_trigger_id() ) {
			return false;
		}

		$campaign = noptin_get_email_campaign_object( $automated_email_id );

		if ( ! $campaign->can_send() ) {
			return false;
		}

		if ( absint( $campaign->get( 'automation_rule' ) ) !== absint( $rule->get_id() ) ) {
			return false;
		}

		if ( ! empty( $args['post_meta'] ) ) {
			$sent_notification = get_post_meta( $args['post_meta']['id'], $args['post_meta']['key'], true );
			return ! is_array( $sent_notification ) || (int) $args['post_meta']['id'] !== (int) $sent_notification[0];
		}

		return true;
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
