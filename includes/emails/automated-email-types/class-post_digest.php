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
	public $category = 'Mass Mail';

	/**
	 * @var string
	 */
	public $type = 'post_digest';

	/**
	 * @var WP_Post[]
	 */
	public $posts;

	/**
	 * The current date query.
	 *
	 * @var array
	 */
	public $date_query;

	/**
	 * The current post digest.
	 *
	 * @var \Hizzle\Noptin\Emails\Email
	 */
	public $post_digest;

	/**
	 * Whether or not posts were found.
	 *
	 * @var bool
	 */
	public $posts_found = false;

	/**
	 * @var string
	 */
	public $notification_hook = 'noptin_send_post_digest';

	/**
	 * Registers relevant hooks.
	 *
	 */
	public function add_hooks() {

		parent::add_hooks();

		// Periodically check post digest CRON jobs.
		add_action( 'noptin_daily_maintenance', array( $this, 'maybe_update_cron_jobs' ) );
	}

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
		return __( 'Automatically send your subscribers a daily, weekly, monthly or yearly email highlighting your latest content.', 'newsletter-optin-box' );
	}

	/**
	 * Returns the image URL or dashicon for the automated email type.
	 *
	 * @return string|array
	 */
	public function get_image() {
		return array(
			'icon' => 'calendar-alt',
			'fill' => '#3f9ef4',
		);
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
	 * Prepares the default blocks.
	 *
	 * @return string
	 */
	protected function prepare_default_blocks() {
		return '<!-- wp:html -->[[post_digest style=list]]<!-- /wp:html -->';
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
	 * Returns the default year day.
	 *
	 */
	public function default_year_day() {
		return '1';
	}

	/**
	 * Returns the default x days.
	 */
	public function default_x_days() {
		return '14';
	}

	/**
	 * Returns the default time.
	 *
	 */
	public function default_time() {
		return '07:00';
	}

	/**
	 * Returns default email properties.
	 *
	 * @param array $props
	 * @param \Hizzle\Noptin\Emails\Email $email
	 * @return array
	 */
	public function get_default_props( $props, $email ) {

		if ( $email->type !== $this->type && $email->get_sub_type() !== $this->type ) {
			return $props;
		}

		$props['noptin-ap-post-type'] = 'post';

		return parent::get_default_props( $props, $email );
	}

	/**
	 * Returns an array of weekdays.
	 *
	 * @global WP_Locale $wp_locale WordPress date and time locale object.
	 * @return array
	 */
	public static function get_weekdays() {
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

		for ( $i = 1; $i < 29; $i++ ) {

			switch ( $i ) {

				case 1:
				case 21:
					// translators: %d is the day number.
					$label = sprintf( __( '%1$dst day', 'newsletter-optin-box' ), $i );
					break;

				case 2:
				case 22:
					// translators: %d is the day number.
					$label = sprintf( __( '%1$dnd day', 'newsletter-optin-box' ), $i );
					break;

				case 3:
				case 23:
					// translators: %d is the day number.
					$label = sprintf( __( '%1$drd day', 'newsletter-optin-box' ), $i );
					break;

				default:
					// translators: %d is the day number.
					$label = sprintf( __( '%1$dth day', 'newsletter-optin-box' ), $i );
					break;
			}

			$dates[ "$i" ] = $label;
		}

		return $dates;
	}

	/**
	 * Returns a list of days.
	 *
	 * @return array
	 */
	public static function get_days( $max ) {
		return array_map(
			function ( $day ) {

				$ends = array( 'th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th' );
				if ( (($day % 100) >= 11) && (($day % 100) <= 13) ) {
					$ordinal = $day . 'th';
				} else {
					$ordinal = $day . $ends[ $day % 10 ];
				}

				return array(
					'value'  => "$day",
					// translators: %d is the day number.
					'label'  => sprintf( __( '%s day', 'newsletter-optin-box' ), $ordinal ),
					// translators: %d is the day number.
					'render' => sprintf( __( '%s day', 'newsletter-optin-box' ), "<strong>$ordinal</strong>" ),
				);
			},
			range( 1, $max )
		);
	}

	/**
	 * Displays a metabox.
	 *
	 * @param array $options
	 */
	public function campaign_options( $options ) {
		return array_merge( $options, self::get_campaign_timing_options() );
	}

	/**
	 * Retrieves campaign options.
	 *
	 * @return array
	 */
	public static function get_campaign_timing_options() {

		return array(
			'frequency' => array(
				'el'          => 'select',
				'options'     => array(
					'daily'   => __( 'Daily', 'newsletter-optin-box' ),
					'weekly'  => __( 'Weekly', 'newsletter-optin-box' ),
					'monthly' => __( 'Monthly', 'newsletter-optin-box' ),
					'yearly'  => __( 'Yearly', 'newsletter-optin-box' ),
					'x_days'  => __( 'Every X days', 'newsletter-optin-box' ),
				),
				'label'       => __( 'Frequency', 'newsletter-optin-box' ),
				'description' => __( 'How often should this email be sent?', 'newsletter-optin-box' ),
			),
			'day'       => array(
				'el'          => 'select',
				'options'     => (object) self::get_weekdays(),
				'label'       => __( 'Day', 'newsletter-optin-box' ),
				'placeholder' => __( 'Select a day', 'newsletter-optin-box' ),
				'description' => __( 'What day should this email be sent?', 'newsletter-optin-box' ),
				'conditions'  => array(
					array(
						'value' => 'weekly',
						'key'   => 'frequency',
					),
				),
			),

			'date'      => array(
				'el'          => 'combobox',
				'options'     => self::get_days( 29 ),
				'label'       => __( 'Day', 'newsletter-optin-box' ),
				'placeholder' => __( 'Select a day', 'newsletter-optin-box' ),
				'description' => __( 'What day should this email be sent?', 'newsletter-optin-box' ),
				'conditions'  => array(
					array(
						'value' => 'monthly',
						'key'   => 'frequency',
					),
				),
			),

			'year_day'  => array(
				'el'          => 'combobox',
				'label'       => __( 'Day', 'newsletter-optin-box' ),
				'placeholder' => __( 'Select a day', 'newsletter-optin-box' ),
				'description' => __( 'What day should this email be sent?', 'newsletter-optin-box' ),
				'options'     => self::get_days( 366 ),
				'conditions'  => array(
					array(
						'value' => 'yearly',
						'key'   => 'frequency',
					),
				),
			),

			'x_days'    => array(
				'el'               => 'input',
				'type'             => 'number',
				'label'            => __( 'Days', 'newsletter-optin-box' ),
				'placeholder'      => __( 'Enter a number', 'newsletter-optin-box' ),
				'description'      => __( 'Number of days between each email.', 'newsletter-optin-box' ),
				'customAttributes' => array(
					'min'  => 1,
					'step' => 1,
					'max'  => 366,
				),
				'conditions'       => array(
					array(
						'value' => 'x_days',
						'key'   => 'frequency',
					),
				),
			),

			'time'      => array(
				'el'          => 'time',
				'label'       => __( 'Time', 'newsletter-optin-box' ),
				'placeholder' => __( 'Enter a time', 'newsletter-optin-box' ),
				'description' => __( 'What time should this email be sent?', 'newsletter-optin-box' ),
			),
		);
	}

	/**
	 * Updates CRON jobs.
	 */
	public function maybe_update_cron_jobs() {

		foreach ( $this->get_automations() as $automation ) {

			// If the event is still not scheduled, display a warning.
			if ( ! wp_get_scheduled_event( $this->notification_hook, array( $automation->id ) ) ) {
				$this->schedule_campaign( $automation, true );
			}
		}
	}

	/**
	 * Fires after an automation is saved.
	 *
	 * @param Hizzle\Noptin\Emails\Email $campaign
	 */
	public function on_save_campaign( $campaign ) {
		$this->schedule_campaign( $campaign, true );
	}

	/**
	 * Fires before an automation is deleted.
	 *
	 * @param Hizzle\Noptin\Emails\Email $campaign
	 */
	public function on_delete_campaign( $campaign ) {
		wp_clear_scheduled_hook( $this->notification_hook, array( $campaign->id ) );
	}

	/**
	 * Schedules the next send for a given campain.
	 *
	 * @param Hizzle\Noptin\Emails\Email $campaign
	 * @param int $is_saving
	 */
	public function schedule_campaign( $campaign, $is_saving = false ) {
		// Clear any existing scheduled events.
		wp_clear_scheduled_hook( $this->notification_hook, array( $campaign->id ) );

		// Abort if the campaign is not active.
		if ( ! $campaign->can_send() ) {
			delete_post_meta( $campaign->id, '_noptin_next_send' );
			return;
		}

		// Get the frequency.
		$last_send = get_post_meta( $campaign->id, '_noptin_last_send', true );
		$frequency = $campaign->get( 'frequency' );
		$day       = (string) $campaign->get( 'day' );
		$date      = (string) $campaign->get( 'date' );
		$year_day  = (int) $campaign->get( 'year_day' );
		$x_days    = (int) $campaign->get( 'x_days' );
		$time      = $campaign->get( 'time' );

		if ( empty( $time ) ) {
			$time = '07:00';
		}

		// Get the next send date.
		switch ( $frequency ) {

			case 'daily':
				$next_send = strtotime( "tomorrow $time" );
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
					'saturday',
				);

				// Abort if the day is invalid.
				if ( ! isset( $days[ (int) $day ] ) ) {
					return;
				}

				$day       = $days[ (int) $day ];
				$next_send = strtotime( "next $day $time" );
				break;

			case 'monthly':
				if ( empty( $date ) ) {
					$date = '1';
				}

				$this_month = (int) gmdate( 'n' );
				$this_year  = (int) gmdate( 'Y' );

				$send_month = $this_month < 12 ? $this_month + 1 : 1;
				$send_year  = $this_month < 12 ? $this_year : $this_year + 1;

				if ( $is_saving ) {
					$next_send = strtotime( "$this_year-$this_month-$date $time" );

					if ( $next_send < time() ) {
						$next_send = strtotime( "$send_year-$send_month-$date $time" );
					}
				} else {
					$next_send = strtotime( "$send_year-$send_month-$date $time" );
				}

				break;

			case 'yearly':
				if ( empty( $year_day ) ) {
					$year_day = 1;
				}

				$send_year        = (int) gmdate( 'Y' );
				$current_year_day = (int) gmdate( 'z' ) + 1;

				if ( $year_day < $current_year_day ) {
					++$send_year;
				}

				$next_send = strtotime( "$send_year-01-01 $time" ) + ( $year_day - 1 ) * DAY_IN_SECONDS;
				break;

			case 'x_days':
				if ( empty( $x_days ) ) {
					$x_days = 14;
				}

				$current_time = time();
				$seconds      = $x_days * DAY_IN_SECONDS;

				if ( $is_saving && ! empty( $last_send ) && ( $last_send + $seconds ) > $current_time ) {
					$current_time = $last_send;
				}

				$next_send = strtotime( "+$x_days days $time", $current_time );
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
	 * @param \Hizzle\Noptin\Emails\Email|int $campaign_id
	 * @param string $key
	 */
	public function maybe_send_notification( $campaign_id ) {

		$campaign = \Hizzle\Noptin\Emails\Email::from( $campaign_id );

		// Ensure that the campaign is still published.
		if ( ! $campaign->can_send() ) {
			return;
		}

		// Reschedule next send.
		$this->schedule_campaign( $campaign );

		// Get the last send date (GMT).
		$last_send = get_post_meta( $campaign_id, '_noptin_last_send', true );

		// Don't send if we already sent today.
		if ( ! empty( $last_send ) && gmdate( 'Ymd', $last_send ) === gmdate( 'Ymd' ) && ! defined( 'NOPTIN_RESENDING_CAMPAIGN' ) ) {
			return;
		}

		// Set the last send date.
		update_post_meta( $campaign_id, '_noptin_last_send', time() );

		// Prepare environment.
		$this->post_digest = $campaign;
		$this->posts_found = false;
		$this->date_query  = $this->get_date_query( $campaign );

		$this->before_send( $campaign );

		// Prepare campaign args.
		$type = $campaign->get_email_type();
		$args = array_merge(
			$campaign->options,
			array(
				'parent_id'         => $campaign->id,
				'status'            => 'publish',
				'type'              => 'newsletter',
				'name'              => sprintf( '%1$s [%2$s]', esc_html( $campaign->name ), date_i18n( get_option( 'date_format' ) ) ),
				'subject'           => noptin_parse_email_subject_tags( $campaign->get_subject(), true ),
				'heading'           => noptin_parse_email_content_tags( $campaign->get( 'heading' ), true ),
				'content'           => noptin_parse_email_content_tags( $campaign->content, true ),
				'author'            => $campaign->author,
				'subscribers_query' => array(),
				'preview_text'      => noptin_parse_email_content_tags( $campaign->get( 'preview_text' ), true ),
				'footer_text'       => noptin_parse_email_content_tags( $campaign->get( 'footer_text' ), true ),
			)
		);

		foreach ( $args as $key => $value ) {

			// Check if the key starts with content_.
			if ( 0 === strpos( $key, 'content_' ) ) {

				// Parse paragraphs.
				if ( 'content_normal' === $type ) {
					$value = wpautop( trim( $value ) );
				}

				$args[ $key ] = trim( noptin_parse_email_content_tags( $value, true ) );

				// Strip HTML.
				if ( 'content_plain_text' === $type && ! empty( $args[ $key ] ) ) {
					$args[ $key ] = noptin_convert_html_to_text( $args[ $key ] );
				}
			}
		}

		// Skip if there are no posts.
		if ( ! $this->posts_found ) {
			add_filter(
				'noptin_email_sent_successfully_message',
				function () {
					return __( 'No posts found.', 'newsletter-optin-box' );
				}
			);
			return;
		}

		// Prepare the newsletter.
		$newsletter = new \Hizzle\Noptin\Emails\Email( $args );

		// Send normal campaign.
		if ( apply_filters( 'noptin_should_send_post_digest', true, $newsletter, $campaign ) ) {
			$newsletter->save();
		}

		// Remove temp variables.
		$this->post_digest = null;

		// Clear environment.
		$this->after_send( $campaign );
	}

	/**
	 * Retrieve matching posts since last send.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $campaign
	 * @return array
	 */
	public function get_date_query( $campaign ) {

		$time = $campaign->get( 'time' );

		if ( empty( $time ) ) {
			$time = '07:00';
		}

		switch ( $campaign->get( 'frequency' ) ) {

			// Get posts published yesterday.
			case 'daily':
				return array(
					array(
						'after'     => 'yesterday midnight',
						'before'    => 'today midnight',
						'inclusive' => true,
					),
				);

			// Get posts published in the last 7 days.
			case 'weekly':
				return array(
					'after' => gmdate( 'Y-m-d', strtotime( '-7 days' ) ),
				);

			// Get posts published in the last 30 days.
			case 'monthly':
				return array(
					'after' => gmdate( 'Y-m-d', strtotime( '-30 days' ) ),
				);

			// Get posts published in the last 365 days.
			case 'yearly':
				return array(
					'after' => gmdate( 'Y-m-d', strtotime( '-365 days' ) ),
				);

			// Get posts published last x days.
			case 'x_days':
				$days = $campaign->get( 'x_days' );
				if ( empty( $days ) ) {
					$days = 14;
				}

				return array(
					'after' => gmdate( 'Y-m-d', strtotime( "-$days days" ) ),
				);
		}

		return array();
	}

	/**
	 * Filters automation summary.
	 *
	 * @param string $about
	 * @param \Hizzle\Noptin\Emails\Email $campaign
	 */
	public function about_automation( $about, $campaign ) {

		// Do not display the next send time if the campaign is paused.
		if ( ! $campaign->can_send() ) {
			return $about;
		}

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
			$next_send = sprintf(
				'<p class="noptin-list-table-misc noptin-tip" title="%s">%s</p>',
				esc_attr( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next_send + ( (float) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) ),
				esc_html( $this->get_formatted_next_send_time( $next_send ) )
			);
		}

		// If we have a next send time, but no cron event, display a warning.
		if ( ! empty( $next_send ) && ! wp_get_scheduled_event( $this->notification_hook, array( $campaign->id ) ) ) {

			// Try rescheduling the event.
			$this->schedule_campaign( $campaign, true );

			// If the event is still not scheduled, display a warning.
			if ( ! wp_get_scheduled_event( $this->notification_hook, array( $campaign->id ) ) ) {
				$next_send .= sprintf(
					'<p class="noptin-list-table-misc noptin-list-table-misc-error">%s</p>',
					esc_attr__( 'The cron event for this campaign is not scheduled. We tried to reschedule it, but it seems that your server is not configured to run cron jobs.', 'newsletter-optin-box' )
				);
			} else {
				$next_send .= sprintf(
					'<p class="noptin-list-table-misc noptin-list-table-misc-error">%s</p>',
					esc_attr__( 'The cron event for this campaign is not scheduled. Rescheduling it.', 'newsletter-optin-box' )
				);
			}
		}

		return $about . $next_send;
	}

	/**
	 * Prepares the next send time.
	 *
	 * @param int $timestamp
	 * @param \Hizzle\Noptin\Emails\Email $campaign
	 */
	private function get_formatted_next_send_time( $timestamp ) {

		$now             = time();
		$local_timestamp = $timestamp + ( (float) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

		// If past, abort.
		if ( $timestamp < $now ) {
			return sprintf(
				// translators: %1 is the time.
				__( 'Was supposed to be send %1$s ago', 'newsletter-optin-box' ),
				human_time_diff( $local_timestamp )
			);
		}

		// If less than 24 hours, show human time diff.
		if ( $timestamp - $now < DAY_IN_SECONDS ) {
			return sprintf(
				// translators: %1 is the time.
				__( 'Next send in %1$s', 'newsletter-optin-box' ),
				human_time_diff( $timestamp, $now )
			);
		}

		// If tomorrow, show tomorrow.
		if ( $timestamp - $now < 2 * DAY_IN_SECONDS ) {
			return sprintf(
				// translators: %1 is the time.
				__( 'Next send tomorrow at %1$s', 'newsletter-optin-box' ),
				date_i18n( get_option( 'time_format' ), $local_timestamp )
			);
		}

		// If less than a week, show the day.
		if ( $timestamp - $now < WEEK_IN_SECONDS ) {
			return sprintf(
				// translators: %1 is the day, %2 is the time.
				__( 'Next send on %1$s at %2$s', 'newsletter-optin-box' ),
				date_i18n( 'l', $local_timestamp ),
				date_i18n( get_option( 'time_format' ), $local_timestamp )
			);
		}

		// Return human time diff.
		return sprintf(
			// translators: %1 is the time.
			__( 'Next send in %1$s', 'newsletter-optin-box' ),
			human_time_diff( $local_timestamp )
		);
	}

	/**
	 * Retrieves an array of supported merge tags.
	 *
	 * @return array
	 */
	public function get_merge_tags() {

		return array(
			__( 'Digest', 'newsletter-optin-box' ) => array(

				'post_digest' => array(
					'description' => __( 'Displays your latest content.', 'newsletter-optin-box' ),
					'callback'    => array( $this, 'process_merge_tag' ),
					'example'     => 'post_digest style="list" limit="10"',
					'partial'     => true,
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

		// Fetch the posts.
		$posts = $this->get_merge_tag_posts( $args );

		// Abort if we have no posts.
		if ( empty( $posts ) ) {
			return '';
		}

		// We have posts.
		$this->posts_found = true;

		return $this->get_posts_html( $args, $posts );
	}

	/**
	 * Retrieves the content for the posts merge tag.
	 *
	 * @param array $args
	 * @return WP_Post[]
	 */
	public function get_merge_tag_posts( $args = array() ) {

		$query = array(
			'numberposts'      => isset( $args['limit'] ) ? intval( $args['limit'] ) : 10,
			'orderby'          => 'date',
			'order'            => 'DESC',
			'suppress_filters' => true,
		);

		if ( ! empty( $this->date_query ) ) {
			$query['date_query'] = $this->date_query;
		}

		return get_posts( apply_filters( 'noptin_post_digest_merge_tag_query', $query, $args, $this ) );
	}

	/**
	 * Get posts html to display.
	 *
	 * @param array $args
	 * @param WP_Post[] $campaign_posts
	 *
	 * @return string
	 */
	public function get_posts_html( $args = array(), $campaign_posts = array() ) {

		$template = isset( $args['style'] ) ? $args['style'] : 'list';

		// Allow overwriting this.
		$html = apply_filters( 'noptin_post_digest_html', null, $template, $campaign_posts );

		if ( null !== $html ) {
			return $html;
		}

		$args['campaign_posts'] = $campaign_posts;

		ob_start();
		get_noptin_template( 'post-digests/email-posts-' . $template . '.php', $args );
		return ob_get_clean();
	}

	/**
	 * Prepares test data.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $email
	 */
	public function prepare_test_data( $email ) {

		// Prepare user and subscriber.
		parent::prepare_test_data( $email );

		$this->post_digest = $email;
	}
}
