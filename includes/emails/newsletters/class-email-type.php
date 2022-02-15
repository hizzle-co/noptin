<?php
/**
 * Emails API: Newsletter Email Type.
 *
 * Container for the newsletter email type.
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Container for the newsletter email type.
 *
 * @since 1.7.0
 * @internal
 * @ignore
 */
class Noptin_Newsletter_Email_Type extends Noptin_Email_Type {

	/**
	 * @var string
	 */
	public $type = 'newsletter';

	/**
	 * Registers relevant hooks.
	 *
	 */
	public function add_hooks() {

        // Register parent hooks.
		parent::add_hooks();

        // Send newsletter emails.
		add_action( 'transition_post_status', array( $this, 'maybe_send_campaign' ), 100, 3 );

	}

	/**
	 * Returns the default email sender.
	 *
	 */
	public function default_email_sender() {

        if ( ! empty( $_GET['email_sender'] ) ) {
            return sanitize_text_field( $_GET['email_sender'] );
        }

		return 'noptin';
	}

	/**
	 * Returns the URL to create a new campaign.
	 *
	 */
	public function new_campaign_url() {
		return add_query_arg( 'campaign', '0', admin_url( 'admin.php?page=noptin-email-campaigns&section=newsletters&sub_section=edit_campaign' ) );
	}

	/**
	 *  (Maybe) Sends a newsletter campaign.
	 *
	 * @param string  $new_status The new campaign status.
	 * @param string  $old_status The old campaign status.
	 * @param WP_Post $post The new campaign post object.
	 */
	public function maybe_send_campaign( $new_status, $old_status, $post ) {

		// Maybe abort early.
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}

		// Ensure this is a newsletter campaign.
		if ( 'noptin-campaign' === $post->post_type && $this->type === get_post_meta( $post->ID, 'campaign_type', true ) ) {
			$this->send_campaign( $post );
		}

	}

	/**
	 * Sends a newsletter campaign.
	 *
	 * @param WP_Post $post The new campaign post object.
	 */
	public function send_campaign( $post ) {

		// Prepare campaign.
		$campaign = new Noptin_Newsletter_Email( $post->ID );

		// Abort if the campaign is not ready to be sent.
		if ( ! $campaign->can_send() ) {
			return;
		}

		// Log the campaign.
		log_noptin_message(
			sprintf(
				__( 'Sending the campaign: "%s"', 'newsletter-optin-box' ),
				esc_html( $post->post_title )
			)
		);

		$item = array(
			'campaign_id'       => $campaign->id,
			'subscribers_query' => $campaign->get( '' ), // By default, send this to all active subscribers.
			'campaign_data'     => array(
				'campaign_id'   => $post->ID,
				'email_body'    => $post->post_content,
				'email_subject' => $post->post_title,
				'preview_text'  => get_post_meta( $post->ID, 'preview_text', true ),
			),
		);

		if ( apply_filters( 'noptin_should_send_campaign', true, $item ) ) {

			$sender = get_noptin_email_sender( $post->ID );

			if ( 'noptin' == $sender ) {
				noptin()->bg_mailer->push_to_queue( $item );
				noptin()->bg_mailer->save()->dispatch();
			} else {
				do_action( "handle_noptin_email_sender_$sender", $item, $post );
			}

		}

	}

	/**
	 * Schedules an automated email.
	 *
	 * @param int|string $object_id
	 * @param Noptin_Automated_Email $automation
	 */
	public function schedule_notification( $object_id, $automation ) {

		if ( ! $automation->supports_timing() || $automation->sends_immediately() ) {
			return do_noptin_background_action( $this->notification_hook, $object_id, $automation->id );
		}

		$sends_after      = (int) $automation->get_sends_after();
		$sends_after_unit = $automation->get_sends_after_unit();

		$timestamp        = strtotime( "+ $sends_after $sends_after_unit", current_time( 'timestamp', true ) );
		return schedule_noptin_background_action( $timestamp, $this->notification_hook, $object_id, $automation->id );

	}

	/**
	 * Returns an array of email recipients.
	 *
	 * @param Noptin_Automated_Email $automation
	 * @param array $merge_tags
	 * @return array
	 */
	public function get_recipients( $automation, $merge_tags = array() ) {

		$recipients = array();

		$merge_tags['--notracking'] = '';
		foreach ( explode( ',', $automation->get_recipients() ) as $recipient ) {

			$no_tracking = false !== strpos( $recipient, '--notracking' );
			$recipient   = trim( str_replace( array_keys( $merge_tags ), array_values( $merge_tags ), $recipient ) );

			$recipients[ $recipient ] = $no_tracking;

		}

		return $recipients;
	}

}
