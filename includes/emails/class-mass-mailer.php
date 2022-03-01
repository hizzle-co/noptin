<?php
/**
 * Emails API: Mass Email Sender.
 *
 * Contains the main mass mailer class.
 *
 * @since   1.7.0
 * @package Noptin
 */

defined( 'ABSPATH' ) || exit;

/**
 * The mass mailing class. 
 */
abstract class Noptin_Mass_Mailer extends Noptin_Background_Process {

	/**
	 * The email sender.
	 * @var string
	 */
	protected $sender = 'noptin';

	/**
	 * Initiates new non-blocking asynchronous request.
	 *
	 * @ignore
	 */
	public function __construct() {
		$this->action                   = 'noptin' === $this->sender ? 'mass_mailer' : $this->sender . '_mass_mailer';
		$this->identifier               = $this->prefix . '_' . $this->action;
		$this->cron_hook_identifier     = $this->identifier . '_cron';
		$this->cron_interval_identifier = $this->identifier . '_cron_interval';

		// Adds a new email to the qeue.
		add_action( 'noptin_send_email_via_' . $this->sender, array( $this, 'send' ), 10, 2 );

		// Checks cron to ensure all scheduled emails are sent.
		add_action( $this->cron_hook_identifier, array( $this, 'handle_cron_healthcheck' ) );
		add_filter( 'cron_schedules', array( $this, 'schedule_cron_healthcheck' ) );

		// Handle cron on action scheduler cron.
		add_action( $this->identifier, array( $this, 'maybe_handle' ) );
	}

	/**
	 * Fetches relevant subscribers for the campaign.
	 *
	 * @param Noptin_Newsletter_Email $campaign
	 *
	 * @return bool
	 */
	abstract public function _fetch_recipients( $campaign );

	/**
	 * Sends the actual email.
	 *
	 * @param @param Noptin_Newsletter_Email $campaign
	 * @param int|string $recipient
	 *
	 * @return bool
	 */
	abstract public function _send( $campaign, $recipient );

	/**
	 * Pushes the qeue forward to be handled in the future.
	 *
	 */
	protected function push_forwards() {
		$this->unlock_process();
		wp_die();
	}

	/**
	 * Returns the current hour.
	 *
	 * @return string
	 */
	public function current_hour() {
		return date( 'YmdH' );
	}

	/**
	 * Returns the number sent this hour.
	 *
	 * @return int
	 */
	public function emails_sent_this_hour() {
		return (int) get_transient( 'noptin_emails_sent_' . $this->current_hour() );
	}

	/**
	 * Increase sent this hour.
	 *
	 * @return void
	 */
	public function increase_emails_sent_this_hour() {
		set_transient( 'noptin_emails_sent_' . $this->current_hour(), $this->emails_sent_this_hour() + 1, 2 * HOUR_IN_SECONDS );
	}

	/**
	 * Checks if we've exceeded the hourly limit.
	 *
	 * @return bool
	 */
	public function exceeded_hourly_limit() {
		$limited = get_noptin_option( 'per_hour', 0 );
		return ! empty( $limited ) && $this->emails_sent_this_hour() > (int) $limited;
	}

	/**
	 * Push to queue
	 *
	 * @param Noptin_Newsletter_Email $campaign The campaign.
	 * @param array $recipients
	 *
	 * @return Noptin_Mass_Mailer
	 */
	public function send( $campaign, $recipients = null ) {

		// Maybe fetch recipients.
		if ( null === $recipients ) {
			$recipients = $this->_fetch_recipients( $campaign );
		}

		// Prepare campaign data.
		$this->data[] = apply_filters(
			'noptin_mass_mailer_data',
			array(
				'campaign_id' => $campaign->id,
				'recipients'  => $recipients,
			),
			$campaign
		);

		// Save the data.
		$this->save();

		// Start sending the emails.
		$this->dispatch();

		return $this;
	}

	/**
	 * Dispatch
	 *
	 * @access public
	 * @return void
	 */
	public function dispatch() {
		// Schedule the cron healthcheck.
		$this->schedule_event();

		// Perform remote post.
		do_noptin_background_action( $this->identifier );
	}

	/**
	 * Maybe process queue
	 *
	 * Checks whether data exists within the queue and that
	 * the process is not already running.
	 */
	public function maybe_handle() {
		// Don't lock up other requests while processing.
		session_write_close();

		if ( $this->is_process_running() ) {
			// Background process already running.
			wp_die();
		}

		if ( $this->is_queue_empty() ) {
			// No data to process.
			wp_die();
		}

		$this->handle();

		wp_die();
	}

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param array $item The current item being processed.
	 *
	 * @return mixed
	 */
	public function task( $item ) {

		// Abort if we've exceeded the hourly limit.
		if ( $this->exceeded_hourly_limit() ) {
			$this->push_forwards();
		}

		// Prepare args.
		$campaign   = new Noptin_Newsletter_Email( $item['campaign_id'] );
		$recipients = $item['recipients'];

		// Bail if the campaign is not valid or we're out of recipients.
		if ( ! $campaign->can_send() || empty( $recipients ) ) {

			if ( ! empty( $campaign->id ) ) {
				update_post_meta( $campaign->id, 'completed', 1 );
			}

			do_action( 'noptin_background_mailer_complete', $item, $campaign );

			return false;

		}

		// Retrieve the next recipient.
		$recipient = array_shift( $recipients );

		if ( empty( $recipient ) ) {
			return array(
				'campaign_id' => $campaign->id,
				'recipients'  => $recipients,
			);
		}

		// Send the email & log success or failure.
		if ( $this->_send( $campaign, $recipient ) ) {
			$sents = (int) get_post_meta( $campaign->id, '_noptin_sends', true );
			update_post_meta( $campaign->id, '_noptin_sends', $sents + 1 );
		} else {
			$fails = (int) get_post_meta( $campaign->id, '_noptin_fails', true );
			update_post_meta( $campaign->id, '_noptin_fails', $fails + 1 );
		}

		// Log number of emails sent.
		$this->increase_emails_sent_this_hour();

		// Return the remaining recipients.
		return array(
			'campaign_id' => $campaign->id,
			'recipients'  => $recipients,
		);

	}

}
