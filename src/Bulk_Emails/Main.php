<?php

/**
 * Contains the main bulk email sender class.
 *
 * @since   1.0.0
 */

namespace Hizzle\Noptin\Bulk_Emails;

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
	 * @var \Hizzle\Noptin\Emails\Email
	 */
	private $current_campaign;

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
		add_action( 'noptin_init', array( $this, 'init_email_senders' ), 100 );

		// Send newsletter emails.
		add_action( 'noptin_newsletter_campaign_published', array( $this, 'send_newsletter_campaign' ) );
		add_action( 'noptin_resume_email_campaign', array( $this, 'send_newsletter_campaign' ), 1000 );

		add_action( 'shutdown', array( $this, 'handle_unexpected_shutdown' ) );
	}

	/**
	 * Inits the email senders.
	 */
	public function init_email_senders() {
		$senders = apply_filters(
			'noptin_bulk_email_senders',
			array()
		);

		foreach ( $senders as $sender => $class ) {
			if ( is_string( $class ) ) {
				$class = new $class();
			}

			$this->senders[ $sender ] = $class;
		}
	}

	/**
	 * Sends a newsletter campaign.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $campaign The new campaign object.
	 */
	public function send_newsletter_campaign( $campaign ) {

		$campaign = \Hizzle\Noptin\Emails\Email::from( $campaign );

		// Abort if the campaign is not ready to be sent.
		if ( 'newsletter' !== $campaign->type || ! $campaign->can_send() ) {
			return;
		}

		// Log the campaign.
		log_noptin_message(
			sprintf(
				// Translators: %s is the campaign title.
				__( 'Sending the campaign: "%s"', 'newsletter-optin-box' ),
				esc_html( $campaign->name )
			)
		);

		// Check if this is a mass mail.
		if ( ! $campaign->is_mass_mail() ) {
			$campaign->send();

			// Update status.
			update_post_meta( $campaign->id, 'completed', 1 );

			return;
		}

		// Send the campaign.
		$sender = $campaign->get_sender();

		if ( $this->has_sender( $sender ) ) {
			$this->send_pending();
		} else {
			do_action( 'noptin_send_email_via_' . $campaign->get_sender(), $campaign, null );
		}
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
	 * Sets the current campaign.
	 *
	 */
	private function set_current_campaign() {
		$campaigns = get_posts(
			array(
				'post_type'      => 'noptin-campaign',
				'post_status'    => 'publish',
				'posts_per_page' => 5,
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
					array(
						'key'     => 'paused',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);

		foreach ( $campaigns as $campaign ) {
			$campaign = noptin_get_email_campaign_object( $campaign );
			$can_send = $campaign->can_send( true );

			if ( is_wp_error( $can_send ) ) {
				noptin_pause_email_campaign(
					$campaign->id,
					$can_send->get_error_message()
				);
				continue;
			}

			if ( true === $can_send ) {
				$this->current_campaign = $campaign;
				break;
			}
		}

		if ( empty( $this->current_campaign ) && count( $campaigns ) === 5 ) {
			$this->schedule_remaining_tasks();
		}
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
			$this->set_current_campaign();
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
		if ( empty( $this->current_campaign ) || ! $this->current_campaign->can_send() || ! isset( $this->senders[ $this->current_campaign->get_sender() ] ) ) {
			return false;
		}

		// ... or we've reached the max number of emails.
		if ( $this->exceeded_hourly_limit() ) {
			return false;
		}

		// Fetch from cache.
		if ( ! empty( $this->next_recipients ) ) {
			return array_shift( $this->next_recipients );
		}

		// Retrieves the next recipients.
		$sender                = $this->senders[ $this->current_campaign->get_sender() ];
		$this->next_recipients = $sender->get_recipients( $this->current_campaign );

		// If we have no recipient, mark the campaign as completed.
		if ( empty( $this->next_recipients ) ) {

			// Update status.
			update_post_meta( $this->current_campaign->id, 'completed', 1 );

			// Clean up.
			$sender->done_sending( $this->current_campaign );

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
		$sender = $this->current_campaign->get_sender();

		// Send the email.
		$result = $this->senders[ $sender ]->send( $this->current_campaign, $recipient );

		// Increase stats.
		if ( true === $result ) {

			// Increase emails sent this hour.
			$this->increase_emails_sent_this_hour();

		} elseif ( false === $result ) {
			noptin_pause_email_campaign(
				$this->current_campaign->id,
				sprintf(
					// Translators: %s The error message.
					__( 'Error sending email: %s', 'newsletter-optin-box' ),
					esc_html( \Noptin_Email_Sender::get_phpmailer_last_error() )
				)
			);
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
			noptin_pause_email_campaign(
				$this->current_campaign->id,
				$error['message']
			);
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
		$limited = noptin_max_emails_per_period();
		return ! empty( $limited ) && $this->emails_sent_this_hour() >= (int) $limited;
	}
}
