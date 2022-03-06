<?php
/**
 * Emails API: Post digests.
 *
 * Automatically send your subscribers a daily, weekly or monthly email highlighting your latest content.
 *
 * @since   1.7.0
 * @package Noptin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Automatically send your subscribers a daily, weekly or monthly email highlighting your latest content.
 *
 * @since 1.7.0
 * @internal
 * @ignore
 */
class Noptin_Post_Digest extends Noptin_Automated_Email_Type {

	/**
	 * @var string
	 */
	public $type = 'post_digest';

	/**
	 * @var WP_Post[]
	 */
	public $posts;

	/**
	 * @var string
	 */
	public $notification_hook = 'noptin_send_post_digest';

	/**
	 * Retrieves the automated email type name.
	 *
	 */
	public function get_name() {
		return __( 'Post Digest', 'newsletter-optin-box' );
	}

	/**
	 * Retrieves the automated email type description.
	 *
	 */
	public function get_description() {
		return __( 'Automatically send your subscribers a daily, weekly or monthly email highlighting your latest content.', 'newsletter-optin-box' );
	}

	/**
	 * Retrieves the automated email type image.
	 *
	 */
	public function the_image() {
		echo '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" fill="#ff5722" x="0px" y="0px" viewBox="0 0 122.88 120.06" xml:space="preserve"><g><path d="M69.66,4.05c0-2.23,2.2-4.05,4.94-4.05c2.74,0,4.94,1.81,4.94,4.05v17.72c0,2.23-2.2,4.05-4.94,4.05 c-2.74,0-4.94-1.81-4.94-4.05V4.05L69.66,4.05z M91.37,57.03c4.26,0,8.33,0.85,12.05,2.39c3.87,1.6,7.34,3.94,10.24,6.84 c2.9,2.9,5.24,6.38,6.84,10.23c1.54,3.72,2.39,7.79,2.39,12.05c0,4.26-0.85,8.33-2.39,12.05c-1.6,3.87-3.94,7.34-6.84,10.24 c-2.9,2.9-6.38,5.24-10.23,6.84c-3.72,1.54-7.79,2.39-12.05,2.39c-4.26,0-8.33-0.85-12.05-2.39c-3.87-1.6-7.34-3.94-10.24-6.84 c-2.9-2.9-5.24-6.38-6.84-10.24c-1.54-3.72-2.39-7.79-2.39-12.05c0-4.26,0.85-8.33,2.39-12.05c1.6-3.87,3.94-7.34,6.84-10.24 c2.9-2.9,6.38-5.24,10.23-6.84C83.04,57.88,87.1,57.03,91.37,57.03L91.37,57.03z M89.01,75.37c0-0.76,0.31-1.45,0.81-1.95l0,0l0,0 c0.5-0.5,1.19-0.81,1.96-0.81c0.77,0,1.46,0.31,1.96,0.81c0.5,0.5,0.81,1.19,0.81,1.96v14.74l11.02,6.54l0.09,0.06 c0.61,0.39,1.01,0.98,1.17,1.63c0.17,0.68,0.09,1.42-0.28,2.06l-0.02,0.03c-0.02,0.04-0.04,0.07-0.07,0.1 c-0.39,0.6-0.98,1-1.62,1.16c-0.68,0.17-1.42,0.09-2.06-0.28l-12.32-7.29c-0.43-0.23-0.79-0.58-1.05-0.99 c-0.26-0.42-0.41-0.91-0.41-1.43h0L89.01,75.37L89.01,75.37L89.01,75.37z M109.75,70.16c-2.4-2.4-5.26-4.33-8.43-5.64 c-3.06-1.27-6.42-1.96-9.95-1.96s-6.89,0.7-9.95,1.96c-3.17,1.31-6.03,3.24-8.43,5.64c-2.4,2.4-4.33,5.26-5.64,8.43 c-1.27,3.06-1.96,6.42-1.96,9.95c0,3.53,0.7,6.89,1.96,9.95c1.31,3.17,3.24,6.03,5.64,8.43c2.4,2.4,5.26,4.33,8.43,5.64 c3.06,1.27,6.42,1.96,9.95,1.96s6.89-0.7,9.95-1.96c3.17-1.31,6.03-3.24,8.43-5.64c4.71-4.71,7.61-11.2,7.61-18.38 c0-3.53-0.7-6.89-1.96-9.95C114.08,75.42,112.15,72.56,109.75,70.16L109.75,70.16z M13.45,57.36c-0.28,0-0.53-1.23-0.53-2.74 c0-1.51,0.22-2.73,0.53-2.73h13.48c0.28,0,0.53,1.23,0.53,2.73c0,1.51-0.22,2.74-0.53,2.74H13.45L13.45,57.36z M34.94,57.36 c-0.28,0-0.53-1.23-0.53-2.74c0-1.51,0.22-2.73,0.53-2.73h13.48c0.28,0,0.53,1.23,0.53,2.73c0,1.51-0.22,2.74-0.53,2.74H34.94 L34.94,57.36z M56.43,57.36c-0.28,0-0.53-1.23-0.53-2.74c0-1.51,0.22-2.73,0.53-2.73h13.48c0.28,0,0.53,1.22,0.53,2.72 c-1.35,0.84-2.65,1.76-3.89,2.75H56.43L56.43,57.36z M13.48,73.04c-0.28,0-0.53-1.23-0.53-2.74c0-1.51,0.22-2.74,0.53-2.74h13.48 c0.28,0,0.53,1.23,0.53,2.74c0,1.51-0.22,2.74-0.53,2.74H13.48L13.48,73.04z M34.97,73.04c-0.28,0-0.53-1.23-0.53-2.74 c0-1.51,0.22-2.74,0.53-2.74h13.48c0.28,0,0.53,1.23,0.53,2.74c0,1.51-0.22,2.74-0.53,2.74H34.97L34.97,73.04z M13.51,88.73 c-0.28,0-0.53-1.23-0.53-2.74c0-1.51,0.22-2.74,0.53-2.74h13.48c0.28,0,0.53,1.23,0.53,2.74c0,1.51-0.22,2.74-0.53,2.74H13.51 L13.51,88.73z M35,88.73c-0.28,0-0.53-1.23-0.53-2.74c0-1.51,0.22-2.74,0.53-2.74h13.48c0.28,0,0.53,1.23,0.53,2.74 c0,1.51-0.22,2.74-0.53,2.74H35L35,88.73z M25.29,4.05c0-2.23,2.2-4.05,4.94-4.05c2.74,0,4.94,1.81,4.94,4.05v17.72 c0,2.23-2.21,4.05-4.94,4.05c-2.74,0-4.94-1.81-4.94-4.05V4.05L25.29,4.05z M5.44,38.74h94.08v-20.4c0-0.7-0.28-1.31-0.73-1.76 c-0.45-0.45-1.09-0.73-1.76-0.73h-9.02c-1.51,0-2.74-1.23-2.74-2.74c0-1.51,1.23-2.74,2.74-2.74h9.02c2.21,0,4.19,0.89,5.64,2.34 c1.45,1.45,2.34,3.43,2.34,5.64v32.39c-1.8-0.62-3.65-1.12-5.55-1.49v-5.06h0.06H5.44v52.83c0,0.7,0.28,1.31,0.73,1.76 c0.45,0.45,1.09,0.73,1.76,0.73h44.71c0.51,1.9,1.15,3.75,1.92,5.53H7.98c-2.2,0-4.19-0.89-5.64-2.34C0.89,101.26,0,99.28,0,97.07 V18.36c0-2.2,0.89-4.19,2.34-5.64c1.45-1.45,3.43-2.34,5.64-2.34h9.63c1.51,0,2.74,1.23,2.74,2.74c0,1.51-1.23,2.74-2.74,2.74H7.98 c-0.7,0-1.31,0.28-1.76,0.73c-0.45,0.45-0.73,1.09-0.73,1.76v20.4H5.44L5.44,38.74z M43.07,15.85c-1.51,0-2.74-1.23-2.74-2.74 c0-1.51,1.23-2.74,2.74-2.74h18.36c1.51,0,2.74,1.23,2.74,2.74c0,1.51-1.23,2.74-2.74,2.74H43.07L43.07,15.85z"/></g></svg>';
	}

	/**
	 * Returns the default subject.
	 *
	 */
	public function default_subject() {
		return __( 'Check out our latest blog posts', 'newsletter-optin-box' );
	}

	/**
	 * Returns the default heading.
	 *
	 */
	public function default_heading() {
		return $this->default_subject();
	}

	/**
	 * Returns the default content.
	 *
	 */
	public function default_content_normal() {
		return '<div>[[post_digest style=list]]</div>';
	}

	/**
	 * Returns the default plain text content.
	 *
	 */
	public function default_content_plain_text() {
		return '[[post_digest style=list]]';
	}

	/**
	 * Returns the default frequency.
	 *
	 */
	public function default_frequency() {
		return 'daily';
	}

	/**
	 * Returns the default day.
	 *
	 */
	public function default_day() {
		return '0';
	}

	/**
	 * Returns the default date.
	 *
	 */
	public function default_date() {
		return '1';
	}

	/**
	 * Returns the default time.
	 *
	 */
	public function default_time() {
		return '07:00';
	}

	/**
	 * Returns an array of weekdays.
	 *
	 * @global WP_Locale $wp_locale WordPress date and time locale object.
	 * @return array
	 */
	public function get_weekdays() {
		global $wp_locale;

		return $wp_locale->weekday;
	}

	/**
	 * Returns an array of dates.
	 *
	 * @return array
	 */
	public function get_month_days() {

		$dates     = array();

		for( $i = 1; $i < 29; $i++ ) {

			switch ( $i ) {

				case 1:
				case 21:
					$label = sprintf( __( '%1$dst day', 'newsletter-optin-box' ), $i );
					break;

				case 2:
				case 22:
					$label = sprintf( __( '%1$dnd day', 'newsletter-optin-box' ), $i );
					break;

				case 3:
				case 23:
					$label = sprintf( __( '%1$drd day', 'newsletter-optin-box' ), $i );
					break;

				default:
					$label = sprintf( __( '%1$dth day', 'newsletter-optin-box' ), $i );
					break;
			}

			$dates["$i"] = $label;
		}

		return $dates;
	}

	/**
	 * Displays a metabox.
	 *
	 * @param Noptin_Automated_Email $campaign
	 */
	public function render_metabox( $campaign ) {

		$frequencies = array(
			'daily'   => __( 'Daily', 'newsletter-optin-box' ),
			'weekly'  => __( 'Weekly', 'newsletter-optin-box' ),
			'monthly' => __( 'Monthly', 'newsletter-optin-box' ),
		);

		$frequency = $campaign->get( 'frequency' );
		$day       = (string) $campaign->get( 'day' );
		$dates     = $this->get_month_days();
		$date      = (string) $campaign->get( 'date' );
		$time      = $campaign->get( 'time' );
// TODO: Make it possible to filter recipients by source and sign-up time.
		?>

		<p>
			<label>
				<strong class="noptin-label-span"><?php _e( 'Send this email...', 'newsletter-optin-box' ); ?></strong>

				<span class="noptin-post-digest-frequency noptin-inline-block" style="margin-bottom: 10px;">
					<select name="noptin_email[frequency]" id="noptin-post-digest-frequency">
						<?php foreach ( $frequencies as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $frequency ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</span>

				<span  class="noptin-post-digest-day noptin-inline-block" style="margin-bottom: 10px; display: <?php echo $frequency == 'weekly' ? 'inline-block' : 'none' ; ?>">
					<?php _e( 'on', 'newsletter-optin-box' ); ?>
					<select name="noptin_email[day]">
						<?php foreach ( $this->get_weekdays() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( (string) $key, $day ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</span>

				<span class="noptin-post-digest-date noptin-inline-block" style="margin-bottom: 10px; display: <?php echo $frequency == 'monthly' ? 'inline-block' : 'none' ; ?>">
					<?php _e( 'on the', 'newsletter-optin-box' ); ?>
					<select name="noptin_email[date]">
						<?php foreach ( $dates as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $date ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</span>

				<span class="noptin-post-digest-time noptin-inline-block" style="margin-bottom: 10px;">
					<?php _e( 'at', 'newsletter-optin-box' ); ?>
					<input name="noptin_email[time]" class="noptin-schedule-input-time" style="width: 60px;" type="time" value="<?php echo esc_attr( $time ); ?>" placeholder="H:i">
				</span>

			</label>
		</p>

		<?php

		if ( defined( 'NOPTIN_WELCOME_EMAILS_FILE' ) ) {
			return;
		}

		$url = add_query_arg(
			array(
				'utm_medium'   => 'plugin-dashboard',
				'utm_campaign' => 'post-digests',
				'utm_source'   => 'email-editor',
			),
			'https://noptin.com/product/ultimate-addons-pack'
		);

		printf(
			'<p>%s</p><p>%s</p>',
			__( 'By default, this email will only send for new blog posts.', 'newsletter-optin-box' ),
			sprintf(
				__( 'Install the %s to send notifications for products and other post types or limit notifications to certain categories and tags.', 'newsletter-optin-box' ),
				"<a href='$url' target='_blank'>Ultimate Addons Pack</a>"
			)
		);

	}

	/**
	 * Fires after an automation is saved.
	 *
	 * @param Noptin_Automated_Email $campaign
	 */
	public function on_save_campaign( $campaign ) {
		$this->schedule_campaign( $campaign );
	}

	/**
	 * Schedules the next send for a given campain.
	 *
	 * @param Noptin_Automated_Email $campaign
	 */
	public function schedule_campaign( $campaign ) {
		// Clear any existing scheduled events.
		wp_clear_scheduled_hook( $this->notification_hook, array( $campaign->id ) );

		// Abort if the campaign is not active.
		if ( ! $campaign->can_send() ) {
			delete_post_meta( $campaign->id, '_noptin_next_send' );
			return;
		}

		// Get the frequency.
		$frequency = $campaign->get( 'frequency' );
		$day       = (string) $campaign->get( 'day' );
		$date      = (string) $campaign->get( 'date' );
		$time      = $campaign->get( 'time' );

		if ( empty( $time ) ) {
			$time = '07:00';
		}

		// Get the next send date.
		switch( $frequency ) {

			case 'daily':
				$today    = strtotime( "today $time" );
				$tomorrow = strtotime( "tomorrow $time" );

				// Schedule earliest possible send.
				if ( ( $today + HOUR_IN_SECONDS ) > current_time( 'timestamp' ) ) {
					$next_send = $today;
				} else {
					$next_send = $tomorrow;
				} 

				break;

			case 'weekly':

				if ( empty( $day ) ) {
					$day = '0';
				}

				// The weekdays.
				$days = array(
					'sunday',
					'monday',
					'tuesday',
					'wednesday',
					'thursday',
					'friday',
					'saturday'
				);

				// Abort if the day is invalid.
				if ( ! isset( $days[ (int) $day ] ) ) {
					return;
				}

				$day       = $days[ (int) $day ];
				$next_send = strtotime( "$day $time" );
				break;

			case 'monthly':

				if ( empty( $date ) ) {
					$date = '1';
				}

				$month_1 = (int) date( 'n' );
				$year_1  = (int) date( 'Y' );

				$month_2 = $month_1 < 12 ? $month_1 + 1 : 1;
				$year_2  = $month_1 < 12 ? $year_1 : $year_1 + 1;

				$date_1 = strtotime( "$year_1-$month_1-$date $time" );
				$date_2 = strtotime( "$year_2-$month_2-$date $time" );

				// Schedule earliest possible send.
				if ( ( $date_1 + HOUR_IN_SECONDS ) > current_time( 'timestamp' ) ) {
					$next_send = $date_1;
				} else {
					$next_send = $date_2;
				}

				break;

		}

		if ( ! empty( $next_send ) ) {
			$next_send = $next_send - ( (float) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
			wp_schedule_single_event( $next_send, $this->notification_hook, array( $campaign->id ) );
			update_post_meta( $campaign->id, '_noptin_next_send', $next_send );
		} else {
			delete_post_meta( $campaign->id, '_noptin_next_send' );
		}

	}

	/**
	 * (Maybe) Send out post digests.
	 *
	 * @param int $campaign_id
	 * @param string $key
	 */
	public function maybe_send_notification( $campaign_id ) {

		// Get the campaign.
		$campaign = new Noptin_Automated_Email( $campaign_id );

		// Ensure that the campaign is still published.
		if ( ! $campaign->can_send() ) {
			return;
		}

		// Reschedule next send.
		$this->schedule_campaign( $campaign );

		// Get the last send date (GMT).
		$last_send = get_post_meta( $campaign_id, '_noptin_last_send', true );

		// Don't send if we already sent today.
		if ( ! empty( $last_send ) && date( 'Ymd', $last_send ) == current_time( 'Ymd', true ) ) {
			return;
		}

		// Retrieve matching posts.
		$this->posts = $this->get_posts( $campaign );

		// Abort if there are no posts.
		if ( empty( $this->posts ) ) {
			return;
		}

		// Prepare environment.
		$type    = $campaign->get_email_type();
		$content = $campaign->get_content( $type );

		$this->before_send( $campaign );

		// Prepare campaign args.
		$args = array_merge(
			$campaign->options,
			array(
				'parent_id'         => $campaign->id,
				'status'            => 'publish',
				'subject'           => noptin_parse_email_subject_tags( $campaign->get_subject(), true ),
				'content_' . $type  => noptin_parse_email_content_tags( $content, true ),
				'subscribers_query' => array(),
				'preview_text'      => noptin_parse_email_subject_tags( $campaign->get( 'preview_text' ), true ),
				'footer_text'       => noptin_parse_email_subject_tags( $campaign->get( 'footer_text' ), true ),
				'custom_title'      => sprintf( __( '%s [%s]', 'newsletter-optin-box' ), esc_html( $campaign->name ), date_i18n( get_option( 'date_format' ) ) ),
			)
		);

		// Remove unrelated content.
		foreach ( array( 'content_normal', 'content_plain_text', 'content_raw_html' ) as $content_type ) {
			if ( $content_type !== 'content_' . $type ) {
				unset( $args[ $content_type ] );
			}
		}

		// Prepare the newsletter.
		$newsletter = new Noptin_Newsletter_Email( $args );

		// Send normal campaign.
		if ( apply_filters( 'noptin_should_send_post_digest', true, $newsletter, $campaign ) ) {
			$newsletter->save();
		}

		// Clear environment.
		$this->posts = null;
		$this->after_send( $campaign );

		// TODO: Everything is unslashed.

	}

	/**
	 * Retrieve matching posts since last send.
	 *
	 * @param Noptin_Automated_Email $campaign
	 * @return WP_Post[]
	 */
	public function get_posts( $campaign ) {

		$time = $campaign->get( 'time' );

		if ( empty( $time ) ) {
			$time = '07:00';
		}

		switch ( $campaign->get( 'frequency' ) ) {

			case 'daily':

				// Get posts published in the last 1 day.
				return get_posts(
					array(
						'post_type'      => $campaign->get( 'post_type' ),
						'post_status'    => 'publish',
						'posts_per_page' => -1,
						'orderby'        => 'date',
						'order'          => 'DESC',
						'date_query'     => array(
							'after' => date( 'Y-m-d', strtotime( '-1 day' ) ),
						),
					)
				);
				break;

			case 'weekly':

				// Get posts published in the last 7 days.
				return get_posts(
					array(
						'post_type'      => $campaign->get( 'post_type' ),
						'post_status'    => 'publish',
						'posts_per_page' => -1,
						'orderby'        => 'date',
						'order'          => 'DESC',
						'date_query'     => array(
							'after' => date( 'Y-m-d', strtotime( '-7 days' ) ),
						),
					)
				);
				break;

			case 'monthly':

				// Get posts published in the last 30 days.
				return get_posts(
					array(
						'post_type'      => $campaign->get( 'post_type' ),
						'post_status'    => 'publish',
						'posts_per_page' => -1,
						'orderby'        => 'date',
						'order'          => 'DESC',
						'date_query'     => array(
							'after' => date( 'Y-m-d', strtotime( '-30 days' ) ),
						),
					)
				);

		}

		return array();
	}

	/**
	 * Filters automation summary.
	 *
	 * @param string $about
	 * @param Noptin_Automated_Email $campaign
	 */
	public function about_automation( $about, $campaign ) {

		// Prepare time.
		$time = $campaign->get( 'time' );

		if ( empty( $time ) ) {
			$time = '07:00';
		}

		// Convert time to a readable format.
		$time = date_i18n( get_option( 'time_format' ), strtotime( $time ) );

		// Prepare next send time.
		$next_send = get_post_meta( $campaign->id, '_noptin_next_send', true );

		if ( $next_send ) {
			$next_send = $next_send + ( (float) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
			$next_send = '<p class="noptin-list-table-misc noptin-tip" title="' . esc_attr( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next_send ) ) . '">' . esc_html(
				sprintf(
					// Translators: human friendly diff time.
					__( 'Next send in %s', 'newsletter-optin-box' ),
					human_time_diff( current_time( 'timestamp' ), $next_send )
				)
			) . '</p>';
		}

		// Do not display the next send time if the campaign is paused.
		if ( ! $campaign->can_send() ) {
			$next_send = '';
		}

		switch ( $campaign->get( 'frequency' ) ) {

			case 'daily':
				return sprintf(
					__( 'Sends a digest of your latest content every day at %s', 'newsletter-optin-box' ),
					esc_html( $time )
				) . $next_send;
				break;

			case 'weekly':

				return sprintf(
					__( 'Sends a weekly digest of your latest content every %1$s at %2$s', 'newsletter-optin-box' ),
					$GLOBALS['wp_locale']->get_weekday( (int) $campaign->get( 'day' ) ),
					esc_html( $time )
				) . $next_send;
				break;

			case 'monthly':

				$dates = $this->get_month_days();
				$date  = (string) $campaign->get( 'date' );
				return sprintf(
					__( 'Sends a digest of your latest content on the %1$s of every month at %2$s', 'newsletter-optin-box' ),
					isset( $dates[ $date ] ) ? $dates[ $date ] : $dates['1'],
					esc_html( $time )
				) . $next_send;
				break;

			default:
				return $about;
				break;
		}

	}

	/**
	 * Retrieves an array of supported merge tags.
	 *
	 * @return array
	 */
	public function get_merge_tags() {

		return array(
			__( 'Digest', 'noptin' )    => array(

				'post_digest' => array(
					'description' => __( 'Displays your latest content.', 'newsletter-optin-box' ),
					'callback'    => array( $this, 'process_merge_tag' ),
					'example'     => 'post_digest template="list" limit="8"',
				),

			),

		);

	}

	/**
	 * Processes the post digest merge tag.
	 *
	 * @param array $args
	 * @param string $field
	 * @return string
	 */
	public function process_merge_tag( $args = array() ) {

		$template = isset( $args['style'] ) ? $args['style'] : 'list';
		$limit    = isset( $args['limit'] ) ? absint( $args['limit'] ) : 6;

		if ( empty( $this->posts ) ) {
			return '';
		}

		return $this->get_posts_html( $template, array_slice( $this->posts, 0, $limit ) );
	}

	/**
	 * Get posts html to display.
	 *
	 * @param string $template
	 * @param WP_Post[] $posts
	 *
	 * @return string
	 */
	public function get_posts_html( $template = 'grid', $posts = array() ) {

		// Allow overwriting this.
		$html = apply_filters( 'noptin_post_digest_html', null, $template, $posts );

		if ( null !== $html ) {
			return $html;
		}

		ob_start();
		get_noptin_template( 'post-digests/email-posts-' . $template . '.php', compact( 'posts' ) );
		return ob_get_clean();
	}

	/**
	 * Sends a test email.
	 *
	 * @param Noptin_Automated_Email $campaign
	 * @param string $recipient
	 * @return bool Whether or not the test email was sent
	 */
	public function send_test( $campaign, $recipient ) {

		$this->prepare_test_data( $campaign );

		// Maybe set related subscriber.
		$subscriber = get_noptin_subscriber( sanitize_email( $recipient ) );

		if ( $subscriber->exists() ) {
			$this->subscriber = $subscriber;
		}

		return $this->send( $campaign, 'test', array( sanitize_email( $recipient ) => false ) );

	}

	/**
	 * Prepares test data.
	 *
	 * @param Noptin_Automated_Email $email
	 */
	public function prepare_test_data( $email ) {

		// Prepare user and subscriber.
		parent::prepare_test_data( $email );

		// Fetch test posts.
		$this->posts = get_posts(
			array(
				'numberposts'      => 6,
				'category'         => 0,
				'orderby'          => 'date',
				'order'            => 'DESC',
				'post_type'        => 'post',
				'suppress_filters' => true,
			)
		);

		// If no posts found, abort.
		if ( empty( $this->posts ) ) {
			throw new Exception( __( 'Could not find posts for this preview.', 'newsletter-optin-box' ) );
		}

	}

	/**
	 * Fired after sending a campaign.
	 *
	 * @param Noptin_Automated_Email $campaign
	 */
	protected function after_send( $campaign ) {

		// Remove temp variables.
		$this->posts = null;

		parent::after_send( $campaign );
	}

}
