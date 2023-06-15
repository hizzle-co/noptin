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

		// Displays sender options.
		add_action( 'noptin_sender_options_' . $this->sender, array( $this, 'display_sending_options' ) );

		// Adds a new email to the queue.
		add_action( 'noptin_send_email_via_' . $this->sender, array( $this, 'send' ), 10, 2 );

		// Prepares a recipient.
		add_filter( "noptin_{$this->sender}_email_recipient", array( $this, 'filter_recipient' ), 10, 2 );

		// Checks cron to ensure all scheduled emails are sent.
		add_action( $this->cron_hook_identifier, array( $this, 'handle_cron_healthcheck' ) );
		add_filter( 'cron_schedules', array( $this, 'schedule_cron_healthcheck' ) );

		// Handle cron on action scheduler cron.
		add_action( $this->identifier, array( $this, 'maybe_handle' ) );
	}

	/**
	 * Displays newsletter sending options.
	 *
	 * @param Noptin_Newsletter_Email|Noptin_automated_Email $campaign
	 *
	 * @return bool
	 */
	abstract public function display_sending_options( $campaign );

	/**
	 * Displays setting fields.
	 *
	 * @param Noptin_Newsletter_Email|Noptin_automated_Email $campaign
	 * @param string $key
	 * @param array $fields
	 *
	 * @return bool
	 */
	public function display_sending_fields( $campaign, $key, $fields ) {

		if ( empty( $fields ) ) {
			return;
		}

		// Render sender options.
		$options = $campaign->get( $key );
		$options = is_array( $options ) ? $options : array();

		foreach ( $fields as $field_id => $data ) {

			$data['name']  = "noptin_email[$key][$field_id]";
			$data['value'] = isset( $options[ $field_id ] ) ? $options[ $field_id ] : '';
			$description   = '';

			if ( ! empty( $data['description'] ) ) {
				$description = '<span class="noptin-help-text">' . wp_kses_post( $data['description'] ) . '</span>';
			}

			$data['description'] = $description;

			$method = "display_sending_field_{$data['type']}";

			if ( method_exists( $this, $method ) ) {
				call_user_func( array( $this, $method ), $data );
			}
		}
	}

	/**
	 * Displays a select setting field.
	 *
	 * @param array $field
	 */
	public function display_sending_field_select( $field ) {

		?>
			<p>
				<label>
					<strong><?php echo wp_kses_post( $field['label'] ); ?></strong>
					<select name="<?php echo esc_attr( $field['name'] ); ?>" class="widefat">
						<?php foreach ( $field['options'] as $option_key => $option_label ) : ?>
							<option value="<?php echo esc_attr( $option_key ); ?>" <?php selected( $option_key, $field['value'] ); ?>><?php echo esc_html( $option_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<?php echo wp_kses_post( $field['description'] ); ?>
			</p>
		<?php

	}

	/**
	 * Displays multi select setting field.
	 *
	 * @param array $field
	 *
	 * @return bool
	 */
	public function display_sending_field_multi_checkbox( $field ) {

		$value = is_array( $field['value'] ) ? $field['value'] : array();
		?>
			<?php echo wp_kses_post( $field['description'] ); ?>
			<ul style="overflow: auto; min-height: 42px; max-height: 200px; padding: 0 .9em; border: solid 1px #dfdfdf; background-color: #fdfdfd; margin-bottom: 1rem;">
				<?php foreach ( $field['options'] as $option_key => $option_label ) : ?>
					<li>
						<label>
							<input
								name='<?php echo esc_attr( $field['name'] ); ?>[]'
								type='checkbox'
								value='<?php echo esc_attr( $option_key ); ?>'
								<?php checked( in_array( $option_key, $value, true ) ); ?>
							>
							<span><?php echo esc_html( $option_label ); ?></span>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php

	}

	/**
	 * Displays a checkbox setting field.
	 *
	 * @param array $field
	 *
	 * @return bool
	 */
	public function display_sending_field_checkbox( $field ) {

		?>
			<p>
				<label>
					<input type="checkbox" name="<?php echo esc_attr( $field['name'] ); ?>" value="1" <?php checked( ! empty( $field['value'] ), true ); ?>>
					<?php echo wp_kses_post( $field['label'] ); ?>
				</label>
				<?php echo wp_kses_post( $field['description'] ); ?>
			</p>
		<?php

	}

	/**
	 * Displays a text setting field.
	 *
	 * @param array $field
	 *
	 * @return bool
	 */
	public function display_sending_field_text( $field ) {

		?>
			<p>
				<label>
					<strong><?php echo wp_kses_post( $field['label'] ); ?></strong>
					<input type="text" name="<?php echo esc_attr( $field['name'] ); ?>" value="<?php echo esc_attr( $field['value'] ); ?>" placeholder="<?php echo empty( $data['placeholder'] ) ? '' : esc_attr( $data['placeholder'] ); ?>" class="widefat">
				</label>
				<?php echo wp_kses_post( $field['description'] ); ?>
			</p>
		<?php

	}

	/**
	 * Displays a textarea setting field.
	 *
	 * @param array $field
	 *
	 * @return bool
	 */
	public function display_sending_field_textarea( $field ) {

		?>
			<p>
				<label>
					<strong><?php echo wp_kses_post( $field['label'] ); ?></strong>
					<textarea name="<?php echo esc_attr( $field['name'] ); ?>" placeholder="<?php echo empty( $data['placeholder'] ) ? '' : esc_attr( $data['placeholder'] ); ?>" class="widefat"><?php echo esc_textarea( $field['value'] ); ?></textarea>
				</label>
				<?php echo wp_kses_post( $field['description'] ); ?>
			</p>
		<?php

	}

	/**
	 * Fetches relevant recipients for the campaign.
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
	 * Fired after a campaign is done sending.
	 *
	 * @param @param Noptin_Newsletter_Email $campaign
	 *
	 */
	abstract public function done_sending( $campaign );

	/**
	 * Pushes the queue forward to be handled in the future.
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
		return ! empty( $limited ) && $this->emails_sent_this_hour() >= (int) $limited;
	}

	/**
	 * Filters a recipient.
	 *
	 * @param false|array $recipient
	 * @param int $recipient_id
	 *
	 * @return array
	 */
	public function filter_recipient( $recipient, $recipient_id ) {

		if ( ! is_array( $recipient ) ) {
			$recipient = array();
		}

		return $recipient;
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
				'blog_id'     => get_current_blog_id(),
				'campaign_id' => $campaign->id,
				'recipients'  => array_unique( $recipients ), // Ensure no duplicates.
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

		// Maybe switch to the correct blog.
		if ( is_multisite() && isset( $item['blog_id'] ) && get_current_blog_id() !== $item['blog_id'] ) {
			switch_to_blog( $item['blog_id'] );
		}

		// Prepare args.
		$campaign   = new Noptin_Newsletter_Email( $item['campaign_id'] );
		$recipients = $item['recipients'];

		// Bail if the campaign is not valid or we're out of recipients.
		if ( ! $campaign->can_send() || empty( $recipients ) ) {

			// Check whether the campaign is done sending or it was paused.
			if ( $campaign->can_send() ) {

				// Update status.
				update_post_meta( $campaign->id, 'completed', 1 );

				// Clean up.
				$this->done_sending( $campaign );

				// Fire action.
				do_action( 'noptin_mass_mailer_complete', $item, $campaign );
			}

			// Switch back to the original blog.
			if ( ! empty( $switched ) ) {
				restore_current_blog();
			}

			return false;

		}

		// Retrieve the next recipient.
		$recipient = array_shift( $recipients );

		if ( empty( $recipient ) ) {
			return array(
				'blog_id'     => isset( $item['blog_id'] ) ? $item['blog_id'] : get_current_blog_id(),
				'campaign_id' => $campaign->id,
				'recipients'  => $recipients,
			);
		}

		// Send the email & log success or failure.
		$result = $this->_send( $campaign, $recipient );
		if ( true === $result ) {
			increment_noptin_campaign_stat( $campaign->id, '_noptin_sends' );
		} elseif ( false === $result ) {
			increment_noptin_campaign_stat( $campaign->id, '_noptin_fails' );
		}

		// Log number of emails sent.
		$this->increase_emails_sent_this_hour();

		// Return the remaining recipients.
		return array(
			'blog_id'     => isset( $item['blog_id'] ) ? $item['blog_id'] : get_current_blog_id(),
			'campaign_id' => $campaign->id,
			'recipients'  => $recipients,
		);

	}

}
