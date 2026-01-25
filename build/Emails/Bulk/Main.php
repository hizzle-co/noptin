<?php

/**
 * Contains the main bulk email sender class.
 *
 * @since   1.0.0
 */

namespace Hizzle\Noptin\Emails\Bulk;

defined( 'ABSPATH' ) || exit;

/**
 * The main bulk email sender class.
 */
class Main {

	/**
	 * The task hook.
	 */
	const TASK_HOOK             = 'noptin_send_bulk_emails';
	const TASK_INTERVAL         = 300; // Send every 5 minutes.
	const HEALTH_CHECK_HOOK     = 'noptin_send_bulk_emails_health_check';
	const HEALTH_CHECK_INTERVAL = 600; // 10 minutes

	/**
	 * Locking constants
	 */
	const LOCK_KEY    = 'noptin_send_bulk_emails_process_lock';
	const LOCK_TTL    = 60; // seconds
	const MAX_RUNTIME = 20; // seconds

	/**
	 * @var Sender[] $senders
	 */
	public static $senders = array();

	/**
	 * @var int[]|string[] $next_recipients
	 */
	private static $next_recipients = array();

	/**
	 * Loads the class.
	 *
	 */
	public static function init() {
		// Init the email senders.
		add_action( 'noptin_init', array( __CLASS__, 'init_email_senders' ), 100 );

		// Send newsletter emails.
		add_action( 'noptin_newsletter_campaign_published', array( __CLASS__, 'send_newsletter_campaign' ) );
		add_action( 'noptin_email_campaign_resumed', array( __CLASS__, 'send_newsletter_campaign' ), 1000 );

		add_action( self::HEALTH_CHECK_HOOK, array( __CLASS__, 'send_pending' ) );
		add_action( self::TASK_HOOK, array( __CLASS__, 'run' ) );
		add_action( 'wp_ajax_' . self::TASK_HOOK, array( __CLASS__, 'maybe_handle_via_ajax' ) );
		add_action( 'wp_ajax_nopriv_' . self::TASK_HOOK, array( __CLASS__, 'maybe_handle_via_ajax' ) );
	}

	/**
	 * Inits the email senders.
	 */
	public static function init_email_senders() {
		$senders = apply_filters(
			'noptin_bulk_email_senders',
			array()
		);

		foreach ( $senders as $sender => $class ) {
			if ( is_string( $class ) ) {
				$class = new $class();
			}

			self::$senders[ $sender ] = $class;
		}
	}

	/**
	 * Sends a newsletter campaign.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $campaign The new campaign object.
	 */
	public static function send_newsletter_campaign( $campaign ) {

		$campaign = \Hizzle\Noptin\Emails\Email::from( $campaign );

		// Abort if the campaign is not ready to be sent.
		if ( 'newsletter' !== $campaign->type || ! $campaign->can_send() ) {
			return;
		}

		// Delete the last error.
		delete_post_meta( $campaign->id, '_bulk_email_last_error' );

		// Log the campaign.
		log_noptin_message(
			sprintf(
				'Sending the campaign: "%s"',
				esc_html( $campaign->name )
			)
		);

		// Check if this is a mass mail.
		if ( ! $campaign->is_mass_mail() ) {
			$campaign->send();

			// Update status.
			return self::done_sending( $campaign );
		}

		// Send the campaign.
		if ( self::has_sender( $campaign->get_sender() ) ) {
			update_post_meta( $campaign->id, '_noptin_last_activity', time() );
			self::send_pending();
		} elseif ( has_action( 'noptin_send_email_via_' . $campaign->get_sender() ) ) {
			log_noptin_message(
				sprintf(
					'Forwarding the campaign to custom sender handler: %s',
					esc_html( $campaign->get_sender() )
				)
			);
			do_action( 'noptin_send_email_via_' . $campaign->get_sender(), $campaign, null );
		} else {
			noptin_pause_email_campaign(
				$campaign->id,
				sprintf( 'Unsupported sender: %s', esc_html( $campaign->get_sender() ) )
			);
		}
	}

	/**
	 * Checks if we have a given email sender.
	 */
	public static function has_sender( $sender ) {
		return self::$senders[ $sender ] ?? false;
	}

	/**
	 * Sends pending emails.
	 */
	public static function send_pending() {
		// Abort if sending is paused due to limits.
		if ( noptin_email_sending_limit_reached() ) {
			return self::on_sending_limit_reached();
		}

		// Create scheduled tasks if needed...
		// ... then trigger the sending task via AJAX as a backup.
		if ( self::check_scheduled_tasks() ) {
			if ( ! defined( 'NOPTIN_ENABLE_FOREGROUND_SENDING' ) || ! NOPTIN_ENABLE_FOREGROUND_SENDING ) {
				wp_remote_get(
					add_query_arg(
						array(
							'action'      => self::TASK_HOOK,
							'_ajax_nonce' => wp_create_nonce( self::TASK_HOOK ),
						),
						admin_url( 'admin-ajax.php' )
					),
					array(
						'timeout'   => 0.01,
						'blocking'  => false,
						'sslverify' => false,
						'cookies'   => $_COOKIE,
					)
				);
			} else {
				self::run();
			}
		}
	}

	/**
	 * Fires when the sending limit is reached.
	 */
	public static function on_sending_limit_reached() {
		// Log the event and clear scheduled tasks to pause sending.
		log_noptin_message( 'Email sending limit reached. Pausing sending until limit resets.' );
		self::clear_scheduled_tasks();

		// Create a health check task to resume sending later.
		$next_send_time = noptin_get_next_email_send_time();
		if ( empty( $next_send_time ) || $next_send_time <= time() ) {
			$next_send_time = time() + self::HEALTH_CHECK_INTERVAL;
		}

		create_noptin_background_task(
			array(
				'interval'       => self::HEALTH_CHECK_INTERVAL,
				'hook'           => self::HEALTH_CHECK_HOOK,
				'args'           => array(),
				// Set to a unique hash to ensure Noptin always schedules it.
				'args_hash'      => wp_generate_password( 20, false, false ) . time(),
				'date_scheduled' => $next_send_time,
			)
		);
	}

	/**
	 * Health check.
	 *
	 * @return bool false if no pending campaign exists, true otherwise.
	 */
	public static function check_scheduled_tasks() {

		// Check the next campaign.
		$next_campaign = self::prepare_pending_campaign();

		// No need to have a scheduled task if no campaigns.
		if ( ! $next_campaign ) {
			self::clear_scheduled_tasks();
			return false;
		}

		// Create the tasks.
		foreach ( array( self::TASK_HOOK, self::HEALTH_CHECK_HOOK ) as $hook ) {
			// Backwards compat. Remove old wp event.
			if ( wp_next_scheduled( $hook ) ) {
				wp_clear_scheduled_hook( $hook );
			}

			/** @var \Hizzle\Noptin\Tasks\Task[] $tasks */
			$tasks = \Hizzle\Noptin\Tasks\Main::query(
				array(
					'hook'   => $hook,
					'status' => 'pending',
				)
			);

			// If we don't have a scheduled task, schedule one now.
			if ( empty( $tasks ) ) {
				$interval = ( self::TASK_HOOK === $hook ) ? self::TASK_INTERVAL : self::HEALTH_CHECK_INTERVAL;
				create_noptin_background_task(
					array(
						'interval'       => $interval,
						'hook'           => $hook,
						'args'           => array(),
						// Set to a unique hash to ensure Noptin always schedules it.
						'args_hash'      => wp_generate_password( 20, false, false ) . time(),
						'date_scheduled' => time() + $interval,
					)
				);
			} else {
				// Keep the first scheduled task.
				foreach ( $tasks as $index => $task ) {
					if ( $index > 0 ) {
						$task->delete();
					}
				}
			}
		}

		return true;
	}

	/**
	 * Clear scheduled tasks.
	 */
	public static function clear_scheduled_tasks() {
		\Hizzle\Noptin\Tasks\Main::delete_scheduled_task( self::TASK_HOOK );
		\Hizzle\Noptin\Tasks\Main::delete_scheduled_task( self::HEALTH_CHECK_HOOK );
	}

	/**
	 * Checks if we have a pending campaign.
	 */
	public static function prepare_pending_campaign( $exclude = array() ) {
		$campaigns = get_posts(
			array(
				'post_type'      => 'noptin-campaign',
				'post_status'    => 'publish',
				'posts_per_page' => 5,
				'order'          => 'ASC', // Oldest first.
				'fields'         => 'ids',
				'exclude'        => $exclude,
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

			// Check if the sender is supported.
			if ( ! self::has_sender( $campaign->get_sender() ) ) {
				noptin_pause_email_campaign(
					$campaign->id,
					sprintf(
						'The email sender "%s" is not supported.',
						esc_html( $campaign->get_sender() )
					)
				);
				continue;
			}

			if ( true === $can_send ) {
				return $campaign;
			}
		}

		if ( count( $campaigns ) === 5 ) {
			return self::prepare_pending_campaign( array_merge( $exclude, $campaigns ) );
		}

		return false;
	}

	/**
	 * Checks if we have a pending campaign.
	 * @return \Hizzle\Noptin\Emails\Email|false
	 */
	public static function has_pending_campaign() {
		$offset      = 0;
		$batch_size  = 5;
		$found_valid = false;

		// We loop through a maximum of 30 campaigns to prevent timeouts.
		while ( $offset < 30 ) {
			$campaign_ids = get_posts(
				array(
					'post_type'      => 'noptin-campaign',
					'post_status'    => 'publish',
					'posts_per_page' => 5,
					'offset'         => $offset,
					'order'          => 'ASC', // Oldest first.
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

			// If no more campaigns are found in the database, exit.
			if ( empty( $campaign_ids ) ) {
				break;
			}

			foreach ( $campaign_ids as $id ) {
				$campaign = noptin_get_email_campaign_object( $id );

				if ( ! $campaign ) {
					continue;
				}

				$can_send = $campaign->can_send( true );

				// Handle WP_Error (e.g., campaign expired or configuration error).
				if ( is_wp_error( $can_send ) ) {
					noptin_pause_email_campaign(
						$campaign->id,
						$can_send->get_error_message()
					);
					continue;
				}

				// Check if the sender is supported by the system.
				if ( ! self::has_sender( $campaign->get_sender() ) ) {
					noptin_pause_email_campaign(
						$campaign->id,
						sprintf(
							'The email sender "%s" is not supported.',
							esc_html( $campaign->get_sender() )
						)
					);
					continue;
				}

				// If it passes all checks, return the campaign object.
				if ( true === $can_send ) {
					return $campaign;
				}
			}

			// Increment offset to fetch the next batch.
			$offset += $batch_size;
		}

		return false;
	}

	/**
	 * Runs a rescheduled batch process.
	 *
	 */
	public static function maybe_handle_via_ajax() {

		// Don't lock up other requests while processing.
		session_write_close();

		check_ajax_referer( self::TASK_HOOK );
		self::run();

		wp_die();
	}

	/**
	 * Runs the queue.
	 *
	 * Pass each queue item to the task handler, while remaining
	 * within server memory and time limit constraints.
	 */
	public static function run() {
		// If already running, bail.
		if ( ! self::acquire_lock() ) {
			return;
		}

		try {

			// Raise the memory limit.
			wp_raise_memory_limit();

			// Raise the time limit.
			noptin_raise_time_limit( self::MAX_RUNTIME + 10 );

			// Fetch the next campaign.
			$campaign = self::prepare_pending_campaign();

			if ( ! $campaign ) {
				log_noptin_message( 'No pending bulk email campaigns found. Exiting.' );
				return;
			}

			log_noptin_message(
				sprintf(
					'Processing bulk email campaign: "%s"',
					esc_html( $campaign->name )
				)
			);

			// Reset next recipients.
			self::$next_recipients = array();

			$start_time = time();
			$processed  = 0;

			// Run the queue.
			do {

				// Send the campaign to the next recipient.
				// Abort if the campaign is invalid or no recipient.
				if ( ! self::send_campaign( self::get_next_recipient( $campaign ), $campaign ) ) {
					log_noptin_message(
						'Skipping further processing for this campaign. Either no more recipients or an error occurred.'
					);
					break;
				}

				// Increment the processed tasks counter.
				++$processed;
			} while ( time() - $start_time < self::MAX_RUNTIME && ! noptin_memory_exceeded() );
		} catch ( \Throwable $t ) {

			// Log the error.
			if ( isset( $campaign ) && ! empty( $campaign ) ) {
				noptin_pause_email_campaign(
					$campaign->id,
					esc_html( $t->getMessage() ),
					10 * MINUTE_IN_SECONDS
				);
			}
		} finally {
			// Release the lock.
			self::release_lock();

			// Trigger sending of pending emails.
			self::send_pending();
		}
	}

	/**
	 * Returns the next recipient.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $campaign The campaign object.
	 * @return int|string
	 */
	protected static function get_next_recipient( $campaign ) {

		// Abort if no sendable campaign...
		if ( empty( $campaign ) || ! $campaign->can_send() ) {
			return false;
		}

		// ... or we've reached the max number of emails.
		if ( noptin_email_sending_limit_reached() ) {
			return false;
		}

		// Abort if no sender.
		$sender = self::has_sender( $campaign->get_sender() );

		if ( empty( $sender ) ) {
			return false;
		}

		// Fetch from cache.
		if ( ! empty( self::$next_recipients ) ) {
			return array_shift( self::$next_recipients );
		}

		// Retrieves the next recipients.
		self::$next_recipients = $sender->get_recipients( $campaign );

		// If we have no recipient, mark the campaign as completed.
		if ( empty( self::$next_recipients ) || ! is_array( self::$next_recipients ) ) {

			// Clean up.
			self::done_sending( $campaign );

			return false;
		}

		return array_shift( self::$next_recipients );
	}

	/**
	 * Sends the current campaign to the given recipient.
	 *
	 * @param int|string $recipient The task to process.
	 * @param \Hizzle\Noptin\Emails\Email $campaign The campaign object.
	 */
	private static function send_campaign( $recipient, $campaign ) {

		// Update last activity.
		update_post_meta( $campaign->id, '_noptin_last_activity', time() );

		if ( empty( $recipient ) ) {
			return false;
		}

		// Prepare vars.
		$sender = self::has_sender( $campaign->get_sender() );

		if ( empty( $sender ) ) {
			return false;
		}

		// Send the email.
		try {
			$result = $sender->send( $campaign, $recipient );
		} catch ( \Throwable $t ) {

			// Log the error.
			noptin_pause_email_campaign(
				$campaign->id,
				sprintf(
					'Error sending email: %s',
					esc_html( $t->getMessage() )
				)
			);

			return false;
		}

		// Pause the campaign if there was an error.
		if ( false === $result ) {
			noptin_pause_email_campaign(
				$campaign->id,
				sprintf(
					'Error sending email: %s',
					esc_html( \Hizzle\Noptin\Emails\Main::get_phpmailer_last_error() )
				),
				10 * MINUTE_IN_SECONDS
			);
		}

		return $result;
	}

	/**
	 * Completes a campaign.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $campaign The campaign object.
	 */
	private static function done_sending( $campaign ) {
		// Update status.
		update_post_meta( $campaign->id, 'completed', 1 );

		// Clean up.
		$sender = self::has_sender( $campaign->get_sender() );
		if ( empty( $sender ) || ! $campaign->is_mass_mail() ) {
			return;
		}

		$sender->done_sending( $campaign );

		// If this was a mass newsletter with a parent and no sends, delete it.
		$sends = get_post_meta( $campaign->id, '_noptin_sends', true );
		if ( empty( $sends ) && ! empty( $campaign->parent_id ) && 'newsletter' === $campaign->type && $campaign->is_mass_mail() ) {
			noptin_error_log(
				sprintf(
					'Deleting email "%s" as it had no sends.',
					esc_html( $campaign->name )
				)
			);
			$campaign->delete();
		}
	}

	/* ----------------------------------------
	 * Locking
	 * ------------------------------------- */

	/**
	 * Lock process
	 *
	 * Lock the process so that multiple instances can't run simultaneously.
	 */
	public static function acquire_lock() {
		$lock = get_option( self::LOCK_KEY );

		// Delete stale lock.
		if ( $lock && ( time() - $lock ) >= self::LOCK_TTL ) {
			self::release_lock();
		}

		return add_option( self::LOCK_KEY, time(), '', 'no' );
	}

	/**
	 * Unlock process
	 *
	 * Unlock the process so that other instances can spawn.
	 */
	public static function release_lock() {
		delete_option( self::LOCK_KEY );
	}
}
