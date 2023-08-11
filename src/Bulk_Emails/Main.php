<?php

namespace Hizzle\Noptin\Bulk_Emails;

/**
 * Contains the main bulk email sender class.
 *
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The main bulk email sender class.
 */
class Main extends \Hizzle\Noptin\Core\Bulk_Task_Runner {

	/**
	 * The cron hook.
	 */
	public $cron_hook = 'noptin_send_bulk_emails';

	/**
	 * The current campaign ID being processed.
	 *
	 * @var \Noptin_Newsletter_Email
	 */
	public $current_campaign;

	/**
	 * @var Email_Sender[] $senders
	 */
	public $senders = array();

	/**
	 * @var int[]|string[] $next_recipients
	 */
	private $next_recipients = array();

	/**
	 * Stores the main bulk email instance.
	 *
	 * @access private
	 * @var    Main $instance The main bulk email instance.
	 * @since  1.0.0
	 */
	private static $instance = null;

	/**
	 * Get active instance
	 *
	 * @access public
	 * @since  1.0.0
	 * @return Main The main bulk email instance.
	 */
	public static function instance() {

		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Loads the class.
	 *
	 */
	private function __construct() {
		parent::__construct();

		// Init the email senders.
		$senders = apply_filters(
			'noptin_bulk_email_senders',
			array(
				'noptin' => '\Hizzle\Noptin\Bulk_Emails\Email_Sender_Subscribers',
			)
		);

		foreach ( $senders as $sender => $class ) {
			$this->senders[ $sender ] = new $class();
		}

		add_action( 'shutdown', array( $this, 'handle_unexpected_shutdown' ) );
	}

	/**
	 * Checks if we have a given email sender.
	 *
	 * @return bool
	 */
	public function has_sender( $sender ) {
		return isset( $this->senders[ $sender ] );
	}

	/**
	 * Sends pending emails.
	 */
	public function send_pending() {
		wp_remote_get( $this->get_query_url(), $this->get_ajax_args() );
	}

	/**
	 * Fired before running the queue.
	 *
	 */
	public function before_run() {

		// Parent before run.
		parent::before_run();

		// Fetch the next campaign.
		if ( empty( $this->current_campaign ) ) {
			$campaigns = get_posts(
				array(
					'post_type'      => 'noptin-campaign',
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'order'          => 'ASC',
					'fields'         => 'ids',
					'meta_query'     => array(
						array(
							'key'   => 'campaign_type',
							'value' => 'newsletter',
						),
						array(
							'key'     => 'completed',
							'compare' => 'NOT EXISTS',
						),
					),
				)
			);

			$this->current_campaign = ! empty( $campaigns[0] ) ? new \Noptin_Newsletter_Email( $campaigns[0] ) : 0;
		}

		$this->next_recipients = array();
	}

	/**
	 * Returns the next recipient.
	 *
	 * @return int|string
	 */
	protected function get_next_task() {

		// Abort if no sendable campaign...
		if ( empty( $this->current_campaign ) || ! $this->current_campaign->can_send() ) {
			return false;
		}

		// ... or we've reached the max number of emails.
		if ( $this->exceeded_hourly_limit() ) {
			return false;
		}

		// Ensure the sender is supported.
		if ( ! $this->has_sender( $this->current_campaign->get_sender() ) ) {
			return false;
		}

		// Fetch from cache.
		if ( ! empty( $this->next_recipients ) ) {
			return array_shift( $this->next_recipients );
		}

		// Retrieves the next recipients.
		$sender                = $this->current_campaign->get_sender();
		$this->next_recipients = $this->senders[ $sender ]->get_recipients( $this->current_campaign );

		// If we have no recipient, mark the campaign as completed.
		if ( empty( $this->next_recipients ) ) {

			// Update status.
			update_post_meta( $this->current_campaign->id, 'completed', 1 );

			// Clean up.
			$this->senders[ $sender ]->done_sending( $this->current_campaign );

			return false;
		}

		// Sleep for a second.
		sleep( 1 );

		return array_shift( $this->next_recipients );
	}

	/**
	 * Sends the current campaign to the given recipient.
	 *
	 * @param int|string $recipient The task to process.
	 */
	protected function process_task( $recipient ) {

		// Abort if no sendable campaign...
		if ( empty( $this->current_campaign ) || ! $this->current_campaign->can_send() ) {
			return false;
		}

		// Ensure the sender is supported.
		if ( ! $this->has_sender( $this->current_campaign->get_sender() ) ) {
			return false;
		}

		// Prepare vars.
		$campaign = $this->current_campaign;
		$sender   = $campaign->get_sender();

		// Send the email.
		$result = $this->senders[ $sender ]->send( $campaign, $recipient );

		// Increase stats.
		if ( true === $result ) {
			increment_noptin_campaign_stat( $campaign->id, '_noptin_sends' );

			// Increase emails sent this hour.
			$this->increase_emails_sent_this_hour();
		} elseif ( false === $result ) {
			increment_noptin_campaign_stat( $campaign->id, '_noptin_fails' );
		}

	}

	/**
	 * Check if the host's max execution time is (likely) to be exceeded if we send any more emails.
	 *
	 * @return bool
	 */
	public function handle_unexpected_shutdown() {
		$error = error_get_last();

		if ( ! empty( $this->current_campaign ) && ! empty( $error ) && in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ), true ) ) {
			update_post_meta( $this->current_campaign->id, '_bulk_email_last_error', wp_slash( $error ) );
			$this->unlock_process();
		}
	}

	/**
	 * Returns the current hour.
	 *
	 * @return string
	 */
	private function current_hour() {
		return gmdate( 'YmdH' );
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
	private function increase_emails_sent_this_hour() {
		set_transient( 'noptin_emails_sent_' . $this->current_hour(), $this->emails_sent_this_hour() + 1, 2 * HOUR_IN_SECONDS );
	}

	/**
	 * Checks if we've exceeded the hourly limit.
	 *
	 * @return bool
	 */
	public function exceeded_hourly_limit() {
		$limited = get_noptin_option( 'per_hour', 0 );
		return ! empty( $limited ) && $this->emails_sent_this_hour() >= (int) $limited;
	}
}
