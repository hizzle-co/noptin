<?php

/**
 * Emails API: Recurring email.
 *
 * Sends the same email on a regular basis.
 *
 * @since   3.0.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Emails\Types;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sends the same email on a regular basis.
 *
 * @since 3.0.0
 * @internal
 * @ignore
 */
class Recurring extends \Noptin_Automated_Email_Type {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->category          = __( 'Recurring', 'newsletter-optin-box' );
		$this->type              = 'periodic';
		$this->notification_hook = 'noptin_send_periodic_email';
	}

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
	 * Registers the email sub types.
	 *
	 * @param array $types
	 * @return array
	 */
	public function register_automation_type( $types ) {
		return array_merge(
			$types,
			array(
				$this->type => array_merge(
					array(
						'label'                      => $this->get_name(),
						'description'                => $this->get_description(),
						'image'                      => $this->get_image(),
						'category'                   => $this->category,
						'supports_timing'            => false,
						'contexts'                   => $this->contexts,
						'supports_general_templates' => 'periodic' === $this->type,
					),
					$this->mail_config
				),
			)
		);
	}

	/**
	 * Retrieves the automated email type name.
	 *
	 */
	public function get_name() {
		return __( 'Periodic', 'newsletter-optin-box' );
	}

	/**
	 * Retrieves the automated email type description.
	 *
	 */
	public function get_description() {
		return __( 'Automatically send your subscribers, users, or customers an email every X days.', 'newsletter-optin-box' );
	}

	/**
	 * Returns the image URL or dashicon for the automated email type.
	 *
	 * @return string|array
	 */
	public function get_image() {
		return array(
			'icon' => 'calendar',
			'fill' => '#3f9ef4',
		);
	}

	/**
	 * Returns the next send date.
	 *
	 */
	public function default_next_send() {
		return gmdate( 'Y-m-d H:i:s', strtotime( 'tomorrow 07:00' ) );
	}

	/**
	 * Returns the default frequency.
	 *
	 */
	public function default_frequency() {
		return 'x_days';
	}

	/**
	 * Returns the default x days.
	 */
	public function default_x_days() {
		return '30';
	}

	/**
	 * Returns the default time.
	 *
	 */
	public function default_time() {
		return '07:00';
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
	 * Returns an array of weekdays.
	 *
	 * @global \WP_Locale $wp_locale WordPress date and time locale object.
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
	public static function get_month_days() {

		$dates = array();

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
				if ( ( ( $day % 100 ) >= 11 ) && ( ( $day % 100 ) <= 13 ) ) {
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
					'manual'  => __( 'Send manually', 'newsletter-optin-box' ),
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
					'min'    => 1,
					'step'   => 1,
					'max'    => 366,
					'suffix' => array( __( 'Day', 'newsletter-optin-box' ), __( 'Days', 'newsletter-optin-box' ) ),
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
				'conditions'  => array(
					array(
						'value'    => 'manual',
						'key'      => 'frequency',
						'operator' => '!=',
					),
				),
			),
			'next_send' => array(
				'el'          => 'input',
				'type'        => 'text',
				'label'       => __( 'Next send is', 'newsletter-optin-box' ),
				'description' => __( 'The date and time the next email will be sent.', 'newsletter-optin-box' ),
				'conditions'  => array(
					array(
						'value' => 'x_days',
						'key'   => 'frequency',
					),
				),
			),
			'skip_days' => array(
				'el'          => 'select',
				'options'     => (object) self::get_weekdays(),
				'label'       => __( 'Skip days', 'newsletter-optin-box' ),
				'placeholder' => __( 'Select days', 'newsletter-optin-box' ),
				'description' => __( 'Select days to skip sending this email.', 'newsletter-optin-box' ),
				'multiple'    => true,
				'conditions'  => array(
					array(
						'value'    => 'manual',
						'key'      => 'frequency',
						'operator' => '!=',
					),
				),
			),
		);
	}

	/**
	 * Updates CRON jobs.
	 */
	public function maybe_update_cron_jobs() {
		global $wpdb;

		// Get all post ids from wp->postmeta where meta_key is automation_type and meta_value is $this->type.
		// This is faster than using get_posts
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s",
				'automation_type',
				$this->type
			)
		);

		foreach ( array_unique( $ids ) as $id ) {
			$email = noptin_get_email_campaign_object( $id );

			if ( ! $email->can_send() || $this->type !== $email->get_sub_type() ) {
				continue;
			}

			// Ensure that it is scheduled.
			if ( ! next_scheduled_noptin_background_action( $this->notification_hook, $email->id ) ) {
				$this->schedule_campaign( $email, true );
			}
		}
	}

	/**
	 * Fires after an automation is saved.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $campaign
	 */
	public function on_save_campaign( $campaign ) {

		// If next send is empty, set it to the default.
		$next_send = $campaign->get( 'next_send' );
		if ( 'x_days' === $campaign->get( 'frequency' ) && empty( $next_send ) ) {
			$days = $campaign->get( 'x_days' );

			if ( empty( $days ) ) {
				$days                        = $this->default_x_days();
				$campaign->options['x_days'] = $days;
			}

			$time = $campaign->get( 'time' );

			if ( empty( $time ) ) {
				$time                      = $this->default_time();
				$campaign->options['time'] = $time;
			}

			// Add days to today.
			$next_send                      = gmdate( 'Y-m-d H:i:s', strtotime( "+$days days $time" ) );
			$campaign->options['next_send'] = $next_send;
			return $campaign->save();
		}

		$this->schedule_campaign( $campaign );
	}

	/**
	 * Fires before an automation is deleted.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $campaign
	 */
	public function on_delete_campaign( $campaign ) {
		delete_noptin_background_action( $this->notification_hook, $campaign->id );
	}

	/**
	 * Calculates the next valid send date for a campaign.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $campaign The campaign object.
	 * @param boolean $is_rescheduling Whether this is a rescheduling.
	 * @param int $last_checked_date The timestamp of the last checked date.
	 * @param int $tries The number of attempts made to find a valid date.
	 * @return int|false The next valid send timestamp or false if no valid date found.
	 */
	private function calculate_next_send_date( $campaign, $is_rescheduling, $last_checked_date = null, $tries = 0 ) {
		// Prevent infinite loops.
		if ( $tries >= 7 ) {
			return false;
		}

		// Get the frequency.
		$frequency = $campaign->get( 'frequency' );
		$day       = (string) $campaign->get( 'day' );
		$date      = (string) $campaign->get( 'date' );
		$year_day  = (int) $campaign->get( 'year_day' );
		$x_days    = (int) $campaign->get( 'x_days' );
		$time      = $campaign->get( 'time' );

		if ( empty( $frequency ) ) {
			$frequency = $this->default_frequency();
		}

		if ( empty( $time ) ) {
			$time = $this->default_time();
		}

		// Calculate the next send date based on frequency.
		$next_send = false;
		switch ( $frequency ) {
			case 'daily':
				$next_send = noptin_string_to_timestamp( "+1 day $time", $last_checked_date );
				break;

			case 'weekly':
				$day  = is_numeric( $day ) ? $day : $this->default_day();
				$days = array( 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday' );
				$day  = $days[ (int) $day ] ?? 'sunday';

				$next_send = noptin_string_to_timestamp( "next $day $time", $last_checked_date );
				break;

			case 'monthly':
				$date = is_numeric( $date ) ? $date : $this->default_date();

				$next_month = (int) gmdate( 'n', $last_checked_date ?? time() );
				$next_year  = (int) gmdate( 'Y', $last_checked_date ?? time() );

				$last_day_of_month = (int) gmdate( 't', noptin_string_to_timestamp( "$next_year-$next_month-1" ) );

				if ( $date > $last_day_of_month ) {
					$date = $last_day_of_month;
				}

				// If we're rescheduling, and this is the first try, schedule in the same month...
				if ( $is_rescheduling && ! $last_checked_date ) {
					$next_send = noptin_string_to_timestamp( "$next_year-$next_month-$date $time" );

					// ... unless the date is in the past.
					if ( $next_send < time() ) {
						++$next_month;
						if ( $next_month > 12 ) {
							$next_month = 1;
							++$next_year;
						}
					}

					// Else, schedule in the next month.
				} else {
					++$next_month;
					if ( $next_month > 12 ) {
						$next_month = 1;
						++$next_year;
					}
				}

				// Again, check if the date is valid.
				$last_day_of_month = (int) gmdate( 't', noptin_string_to_timestamp( "$next_year-$next_month-1" ) );

				if ( $date > $last_day_of_month ) {
					$date = $last_day_of_month;
				}

				$next_send = noptin_string_to_timestamp( "$next_year-$next_month-$date $time" );
				break;

			case 'yearly':
				$year_day = $year_day ?? 1;

				$send_year = (int) gmdate( 'Y', $last_checked_date ?? time() );

				// If we're retrying, schedule in the next year.
				if ( $tries ) {
					++$send_year;
				} else {
					$send_year        = (int) gmdate( 'Y', time() );
					$current_year_day = (int) gmdate( 'z', time() ) + 1;

					if ( $year_day < $current_year_day ) {
						++$send_year;
					}
				}

				$next_send = noptin_string_to_timestamp( "$send_year-01-01 $time" ) + ( $year_day - 1 ) * DAY_IN_SECONDS;
				break;

			case 'x_days':
				$x_days = $x_days ?? 14;

				if ( $tries ) {
					$next_send = noptin_string_to_timestamp( "+$x_days days $time", $last_checked_date );
				} else {
					$next_send = $campaign->get( 'next_send' );
					$last_send = $campaign->get_last_send();

					// Next send is saved in local time.
					// Convert it to GMT.
					if ( ! empty( $next_send ) ) {
						$next_send = noptin_string_to_timestamp( $next_send ) - ( (float) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
					}

					if ( empty( $next_send ) || $next_send < time() ) {
						$current_time = time();
						$seconds      = $x_days * DAY_IN_SECONDS;

						if ( $is_rescheduling && ! empty( $last_send ) && ( $last_send + $seconds ) > $current_time ) {
							$current_time = $last_send;
						}

						$next_send = noptin_string_to_timestamp( "+$x_days days $time", $current_time );
					}
				}
				break;
		}

		// If we couldn't calculate a next send date, return false.
		if ( empty( $next_send ) ) {
			return false;
		}

		// Check if the calculated date is a skip day.
		$skip_days   = wp_parse_id_list( $campaign->get( 'skip_days' ) );
		$day_of_week = (int) gmdate( 'w', $next_send );

		if ( ! empty( $skip_days ) && in_array( $day_of_week, $skip_days, true ) ) {
			return $this->calculate_next_send_date( $campaign, $is_rescheduling, $next_send, $tries + 1 );
		}

		return $next_send;
	}

	/**
	 * Schedules the next send for a given campain.
	 *
	 * @param \Hizzle\Noptin\Emails\Email $campaign
	 * @param boolean $is_rescheduling Either saving or rescheduling after sending.
	 */
	public function schedule_campaign( $campaign, $is_rescheduling = false ) {

		// Clear scheduled task.
		delete_noptin_background_action( $this->notification_hook, $campaign->id );

		// Abort if the campaign is not active.
		if ( ! $campaign->can_send() || 'manual' === $campaign->get( 'frequency' ) ) {
			delete_post_meta( $campaign->id, '_noptin_next_send' );
			return;
		}

		// Get the last send date.
		$last_send = $campaign->get_last_send();
		// Calculate the next send date.
		$next_send = $this->calculate_next_send_date( $campaign, $is_rescheduling );
		if ( ! empty( $next_send ) ) {
			$next_send -= ( (float) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
			$next_send += MINUTE_IN_SECONDS; // Add a minute to avoid sending the email at the same time as the cron event.
			schedule_noptin_background_action( $next_send, $this->notification_hook, $campaign->id );
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
		$campaign    = noptin_get_email_campaign_object( $campaign_id );
		$campaign_id = $campaign->id;

		// Delete the last error.
		delete_post_meta( $campaign_id, '_bulk_email_last_error' );

		// Reschedule next send.
		$campaign->options['next_send'] = '';
		$this->on_save_campaign( $campaign );

		// Get the last send date (GMT).
		$last_send = $campaign->get_last_send();

		// Don't send if we already sent today.
		if ( ! empty( $last_send ) && gmdate( 'Ymd', $last_send ) === gmdate( 'Ymd' ) && ! defined( 'NOPTIN_RESENDING_CAMPAIGN' ) ) {
			log_noptin_message( sprintf( 'Skipped sending campaign:- %s. Reason:- Already sent today.', $campaign->name ) );
			return;
		}

		// Don't send if we are not supposed to send today.
		$skip_days = wp_parse_id_list( $campaign->get( 'skip_days' ) );
		$today     = (int) gmdate( 'w' );

		if ( in_array( $today, $skip_days, true ) && 'manual' !== $campaign->get( 'frequency' ) && ! defined( 'NOPTIN_RESENDING_CAMPAIGN' ) ) {
			update_post_meta(
				$campaign_id,
				'_bulk_email_last_error',
				array(
					'message' => sprintf( 'Skipped sending campaign. Reason:- %s is a skip day.', date_i18n( 'l', $today ) ),
				)
			);

			log_noptin_message( sprintf( 'Skipped sending campaign:- %s. Reason:- %s is a skip day.', $campaign->name, date_i18n( 'l', $today ) ) );
			return;
		}

		// Send the email.
		$result = $campaign->send();

		if ( is_wp_error( $result ) ) {
			update_post_meta(
				$campaign_id,
				'_bulk_email_last_error',
				array(
					'message' => 'Skipped sending campaign. Reason:- ' . $result->get_error_message(),
				)
			);

			log_noptin_message( 'Skipped sending campaign:- ' . $campaign->name . '. Reason:- ' . $result->get_error_message() );
		}
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

		// Prepare next send time.
		$next_send = get_post_meta( $campaign->id, '_noptin_next_send', true );

		if ( 'manual' === $campaign->get( 'frequency' ) ) {
			$next_send = sprintf(
				'<div class="noptin-strong">%s</div>',
				esc_html__( 'This email is sent manually.', 'newsletter-optin-box' )
			);

			$last_send = $campaign->get_last_send();

			if ( ! empty( $last_send ) ) {
				$next_send .= sprintf(
					'<div class="noptin-strong">%s</div>',
					esc_html__( 'Last sent:', 'newsletter-optin-box' ) . ' ' . date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_send + ( (float) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) )
				);
			}

			return $about . $next_send;
		}

		if ( $next_send ) {
			$scheduled  = next_scheduled_noptin_background_action( $this->notification_hook, $campaign->id );
			$next_send  = $scheduled ? $scheduled : $next_send;
			$local_time = $next_send + ( (float) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
			$skip_days  = wp_parse_id_list( $campaign->get( 'skip_days' ) );
			$error      = ( $next_send < time() || in_array( (int) gmdate( 'w', $next_send ), $skip_days, true ) ) ? 'noptin-text-warning' : 'noptin-text-success';

			$next_send = sprintf(
				'<div class="noptin-strong %s noptin-has-tooltip" data-title="%s">%s</div>',
				$error,
				esc_attr( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $local_time ) ),
				wp_kses_post( $this->get_formatted_next_send_time( $local_time, wp_parse_id_list( $campaign->get( 'skip_days' ) ) ) )
			);

			// If we have a next send time, but no cron event, display a warning.
			if ( ! is_numeric( $scheduled ) ) {

				// Try rescheduling the event.
				$this->schedule_campaign( $campaign, true );

				// If the event is still not scheduled, display a warning.
				if ( ! is_numeric( next_scheduled_noptin_background_action( $this->notification_hook, $campaign->id ) ) ) {
					$next_send .= sprintf(
						'<div class="noptin-strong noptin-text-error">%s</div>',
						esc_attr__( 'The cron event for this campaign is not scheduled. We tried to reschedule it, but it seems that your server is not configured to run cron jobs.', 'newsletter-optin-box' )
					);
				} else {
					$next_send .= sprintf(
						'<div class="noptin-strong noptin-text-error">%s</div>',
						esc_attr__( 'The cron event for this campaign is not scheduled. Rescheduling it.', 'newsletter-optin-box' )
					);
				}
			}

			// Display a list of skip days.
			if ( ! empty( $skip_days ) ) {
				$skip_days = array_map(
					function ( $day ) {
						global $wp_locale;
						return $wp_locale->weekday[ $day ];
					},
					$skip_days
				);

				$next_send .= sprintf(
					'</div><div><span class="noptin-strong">%s</span>: <span>%s</span>',
					esc_html__( 'Skip days', 'newsletter-optin-box' ),
					esc_attr( implode( ', ', $skip_days ) )
				);
			}
		}

		return $about . $next_send;
	}

	/**
	 * Prepares the next send time.
	 *
	 * @param int $timestamp
	 */
	private function get_formatted_next_send_time( $timestamp, $skip_days = array() ) {

		$now = current_time( 'timestamp' );

		// If past, abort.
		if ( $timestamp < $now ) {
			return sprintf(
				'%s. %s',
				sprintf(
					// translators: %1 is the time.
					__( 'Was supposed to be send %1$s ago', 'newsletter-optin-box' ),
					human_time_diff( $timestamp, $now )
				),
				sprintf(
					'<a href="%s" target="_blank">%s</a>',
					noptin_get_guide_url(
						'CRON Jobs',
						'/sending-emails/how-to-set-up-an-external-cron-job-in-wordpress-and-speed-up-email-sending/'
					),
					sprintf(
						// translators: %s is the cron URL.
						__( 'Set up an external cron job for "%s" to fix such issues.', 'newsletter-optin-box' ),
						trailingslashit( get_site_url() ) . 'wp-cron.php'
					)
				)
			);
		}

		// If next send is a skip day, let the user know.
		if ( in_array( (int) gmdate( 'w', $timestamp ), $skip_days, true ) ) {
			return sprintf(
				// translators: %1$s is the day.
				__( 'This email will skip sending on %1$s since %2$s is a skip day.', 'newsletter-optin-box' ),
				date_i18n( get_option( 'date_format' ), $timestamp ),
				date_i18n( 'l', $timestamp )
			);
		}

		// If less than 24 hours, show human time diff.
		if ( ( $timestamp - $now ) < DAY_IN_SECONDS ) {
			return sprintf(
				// translators: %1 is the time.
				__( 'Next send in %1$s', 'newsletter-optin-box' ),
				human_time_diff( $timestamp, $now )
			);
		}

		// If tomorrow, show tomorrow.
		if ( ( $timestamp - $now ) < ( 2 * DAY_IN_SECONDS ) ) {
			return sprintf(
				// translators: %1 is the time.
				__( 'Next send tomorrow at %1$s', 'newsletter-optin-box' ),
				date_i18n( get_option( 'time_format' ), $timestamp )
			);
		}

		// If less than a week, show the day.
		if ( $timestamp - $now < WEEK_IN_SECONDS ) {
			return sprintf(
				// translators: %1 is the day, %2 is the time.
				__( 'Next send on %1$s at %2$s', 'newsletter-optin-box' ),
				date_i18n( 'l', $timestamp ),
				date_i18n( get_option( 'time_format' ), $timestamp )
			);
		}

		// Return human time diff.
		return sprintf(
			// translators: %1 is the time.
			__( 'Next send in %1$s', 'newsletter-optin-box' ),
			human_time_diff( $timestamp )
		);
	}
}
