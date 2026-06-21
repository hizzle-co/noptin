<?php

/**
 * Automation Rules API: Date trigger.
 *
 * Runs automation rules on configured dates.
 *
 * @since   3.4.6
 * @package Noptin
 */

namespace Hizzle\Noptin\Automation_Rules\Triggers;

defined( 'ABSPATH' ) || exit;

/**
 * Runs automation rules on configured dates.
 */
class Date extends Trigger {

	/**
	 * The task lookup key.
	 *
	 * @var string
	 */
	const LOOKUP_KEY = 'date';

	/**
	 * Registers relevant hooks.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'noptin_automation_rule_saved', array( __CLASS__, 'maybe_schedule_rule' ) );
		add_action( 'noptin_automation_rule_deleted', array( __CLASS__, 'delete_scheduled_rule_task' ) );
		add_action( 'noptin_run_automation_rule', array( __CLASS__, 'maybe_reschedule_running_rule' ), 5, 2 );
	}

	/**
	 * @inheritdoc
	 */
	public function get_id() {
		return 'date';
	}

	/**
	 * @inheritdoc
	 */
	public function get_name() {
		return __( 'Date', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_description() {
		return __( 'Runs this automation rule on a schedule.', 'newsletter-optin-box' );
	}

	/**
	 * @inheritdoc
	 */
	public function get_image() {
		return array(
			'icon' => 'calendar',
			'fill' => '#3f9ef4',
		);
	}

	/**
	 * @inheritdoc
	 */
	public function get_settings() {
		return array(
			'frequency' => array(
				'el'          => 'select',
				'options'     => array(
					'daily'   => __( 'Daily', 'newsletter-optin-box' ),
					'weekly'  => __( 'Weekly', 'newsletter-optin-box' ),
					'monthly' => __( 'Monthly', 'newsletter-optin-box' ),
					'yearly'  => __( 'Yearly', 'newsletter-optin-box' ),
					'x_days'  => __( 'Every X days', 'newsletter-optin-box' ),
					'manual'  => __( 'Run manually', 'newsletter-optin-box' ),
				),
				'label'       => __( 'Frequency', 'newsletter-optin-box' ),
				'description' => __( 'How often should this automation rule run?', 'newsletter-optin-box' ),
				'default'     => 'x_days',
			),
			'day'       => array(
				'el'          => 'select',
				'options'     => \Hizzle\Noptin\Emails\Types\Recurring::get_weekdays(),
				'label'       => __( 'Day', 'newsletter-optin-box' ),
				'placeholder' => __( 'Select a day', 'newsletter-optin-box' ),
				'description' => __( 'What day should this rule run?', 'newsletter-optin-box' ),
				'conditions'  => array(
					array(
						'value' => 'weekly',
						'key'   => 'frequency',
					),
				),
				'default'     => '0',
			),
			'date'      => array(
				'el'          => 'combobox',
				'options'     => wp_list_pluck(
					\Hizzle\Noptin\Emails\Types\Recurring::get_days( 29 ),
					'label',
					'value'
				),
				'label'       => __( 'Day', 'newsletter-optin-box' ),
				'placeholder' => __( 'Select a day', 'newsletter-optin-box' ),
				'description' => __( 'What day should this rule run?', 'newsletter-optin-box' ),
				'conditions'  => array(
					array(
						'value' => 'monthly',
						'key'   => 'frequency',
					),
				),
				'default'     => '1',
			),
			'year_day'  => array(
				'el'          => 'combobox',
				'label'       => __( 'Day', 'newsletter-optin-box' ),
				'placeholder' => __( 'Select a day', 'newsletter-optin-box' ),
				'description' => __( 'What day should this rule run?', 'newsletter-optin-box' ),
				'options'     => wp_list_pluck(
					\Hizzle\Noptin\Emails\Types\Recurring::get_days( 366 ),
					'label',
					'value'
				),
				'conditions'  => array(
					array(
						'value' => 'yearly',
						'key'   => 'frequency',
					),
				),
				'default'     => '1',
			),
			'x_days'    => array(
				'el'               => 'input',
				'type'             => 'number',
				'label'            => __( 'Days', 'newsletter-optin-box' ),
				'placeholder'      => __( 'Enter a number', 'newsletter-optin-box' ),
				'description'      => __( 'Number of days between runs of this automation rule.', 'newsletter-optin-box' ),
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
				'default'          => '30',
			),
			'time'      => array(
				'el'          => 'time',
				'label'       => __( 'Time', 'newsletter-optin-box' ),
				'placeholder' => __( 'Enter a time', 'newsletter-optin-box' ),
				'description' => __( 'What time should this automation rule run?', 'newsletter-optin-box' ),
				'conditions'  => array(
					array(
						'value'    => 'manual',
						'key'      => 'frequency',
						'operator' => '!=',
					),
				),
				'default'     => '07:00',
			),
			'next_send' => array(
				'el'          => 'input',
				'type'        => 'text',
				'label'       => __( 'Next run is', 'newsletter-optin-box' ),
				'description' => __( 'The date and time the next automation rule will run.', 'newsletter-optin-box' ),
				'conditions'  => array(
					array(
						'value' => 'x_days',
						'key'   => 'frequency',
					),
				),
			),
			'skip_days' => array(
				'el'          => 'select',
				'options'     => \Hizzle\Noptin\Emails\Types\Recurring::get_weekdays(),
				'label'       => __( 'Skip days', 'newsletter-optin-box' ),
				'placeholder' => __( 'Select days', 'newsletter-optin-box' ),
				'description' => __( 'Select days to skip running this automation rule.', 'newsletter-optin-box' ),
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
	 * @inheritdoc
	 */
	public function get_rule_table_description( $rule ) {
		$settings = $rule->get_trigger_settings();
		$meta     = array();

		if ( 'manual' === ( $settings['frequency'] ?? '' ) ) {
			$meta[ __( 'Schedule', 'newsletter-optin-box' ) ] = __( 'Manual', 'newsletter-optin-box' );
			return parent::get_rule_table_description( $rule ) . $this->rule_trigger_meta( $meta, $rule );
		}

		$next_run = self::get_next_scheduled_timestamp( $rule );

		if ( $next_run ) {
			$meta[ __( 'Next run', 'newsletter-optin-box' ) ] = date_i18n(
				get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
				$next_run + ( (float) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS )
			);
		} else {
			$meta[ __( 'Next run', 'newsletter-optin-box' ) ] = __( 'Not scheduled', 'newsletter-optin-box' );
		}

		return parent::get_rule_table_description( $rule ) . $this->rule_trigger_meta( $meta, $rule );
	}

	/**
	 * Registers the routes for posts.
	 *
	 * @since 4.7.0
	 *
	 * @see register_rest_route()
	 */
	public static function register_routes() {

		// Run a manual automation rule.
		register_rest_route(
			'noptin/v1',
			'/automation_rules/(?P<id>[\d]+)/run',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'rest_run_automation_rule' ),
				'permission_callback' => 'current_user_can_manage_noptin',
				'args'                => array(
					'id' => array(
						'description'       => 'The automation rule id.',
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Runs an automation rule.
	 *
	 * @param \WP_REST_Request $request Full details about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function rest_run_automation_rule( $request ) {
		$rule = noptin_get_automation_rule( absint( $request['id'] ) );

		if ( is_wp_error( $rule ) || ! $rule->exists() ) {
			return new \WP_Error( 'noptin_rest_automation_rule_invalid', 'Automation rule not found.', array( 'status' => 404 ) );
		}

		$result = self::run_rule_now( $rule );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response(
			array(
				'message' => 'Automation rule ran successfully.',
			)
		);
	}

	/**
	 * Runs a manual date rule now.
	 *
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule $rule The rule.
	 * @return bool|\WP_Error
	 */
	public static function run_rule_now( $rule ) {
		$trigger = $rule->get_trigger();

		if ( ! $trigger instanceof self ) {
			return new \WP_Error( 'noptin_invalid_date_rule', 'This automation rule is not a Date rule.', array( 'status' => 400 ) );
		}

		if ( ! $rule->exists() ) {
			return new \WP_Error( 'noptin_invalid_date_rule', 'Save this automation rule before running it.', array( 'status' => 400 ) );
		}

		if ( ! $rule->get_status() ) {
			return new \WP_Error( 'noptin_invalid_date_rule', 'Activate this automation rule before running it.', array( 'status' => 400 ) );
		}

		try {
			$result = $rule->maybe_run(
				get_option( 'admin_email' ),
				$rule->get_trigger(),
				$rule->get_action(),
				array( 'email' => get_option( 'admin_email' ) )
			);

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			if ( false === $result ) {
				return new \WP_Error( 'noptin_date_rule_run_failed', 'The automation rule did not run.', array( 'status' => 500 ) );
			}
		} catch ( \Exception $e ) {
			return new \WP_Error( 'noptin_date_rule_run_failed', $e->getMessage(), array( 'status' => 500 ) );
		}

		return true;
	}

	/**
	 * Maybe schedules a date rule.
	 *
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule $rule The rule.
	 */
	public static function maybe_schedule_rule( $rule ) {
		if ( 'date' !== $rule->get_trigger_id() || $rule->get_parent_id() ) {
			return;
		}

		$trigger = $rule->get_trigger();

		if ( $trigger instanceof self ) {
			$trigger->schedule_rule( $rule );
		}
	}

	/**
	 * Reschedules the next occurrence before a scheduled date task runs.
	 *
	 * @param \Hizzle\Noptin\Tasks\Task $task The task.
	 * @param array                     $args The task args.
	 */
	public static function maybe_reschedule_running_rule( $task, $args ) {
		if (
			! $task instanceof \Hizzle\Noptin\Tasks\Task ||
			self::LOOKUP_KEY !== $task->get_lookup_key() ||
			'noptin_run_automation_rule' !== $task->get_hook()
		) {
			return;
		}

		$rule = noptin_get_automation_rule( $task->get_primary_id() );

		if ( is_wp_error( $rule ) || ! $rule->exists() || 'date' !== $rule->get_trigger_id() ) {
			return;
		}

		$trigger = $rule->get_trigger();

		if ( $trigger instanceof self ) {
			$trigger->schedule_rule( $rule );
		}
	}

	/**
	 * Deletes pending date-rule tasks.
	 *
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule $rule The rule.
	 */
	public static function delete_scheduled_rule_task( $rule ) {
		if ( ! $rule || ! is_callable( array( $rule, 'get_id' ) ) ) {
			return;
		}

		noptin()->db()->delete_where(
			array(
				'hook'       => 'noptin_run_automation_rule',
				'primary_id' => $rule->get_id(),
				'lookup_key' => self::LOOKUP_KEY,
				'status'     => 'pending',
			),
			'tasks'
		);
	}

	/**
	 * Schedules the next run for the rule.
	 *
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule $rule The rule.
	 */
	public function schedule_rule( $rule ) {
		self::delete_scheduled_rule_task( $rule );

		if ( ! $rule->get_status() || 'manual' === $rule->get_trigger_setting( 'frequency' ) ) {
			return;
		}

		$next_run = $this->calculate_next_run_date( $rule, true );

		if ( empty( $next_run ) ) {
			return;
		}

		$scheduled_run  = $next_run - ( (float) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
		$scheduled_run += MINUTE_IN_SECONDS;

		\Hizzle\Noptin\Tasks\Main::create(
			array(
				'hook'           => 'noptin_run_automation_rule',
				'args'           => array(
					'subject' => get_option( 'admin_email' ),
					'email'   => get_option( 'admin_email' ),
				),
				'date_scheduled' => $scheduled_run,
				'subject'        => get_option( 'admin_email' ),
				'status'         => 'pending',
				'primary_id'     => $rule->get_id(),
				'secondary_id'   => $rule->get_action_id(),
				'lookup_key'     => self::LOOKUP_KEY,
			)
		);
	}

	/**
	 * Returns the next scheduled timestamp for a rule.
	 *
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule $rule The rule.
	 * @return int|false
	 */
	public static function get_next_scheduled_timestamp( $rule ) {
		$task = \Hizzle\Noptin\Tasks\Main::query(
			array(
				'status'     => 'pending',
				'number'     => 1,
				'hook'       => 'noptin_run_automation_rule',
				'primary_id' => $rule->get_id(),
				'lookup_key' => self::LOOKUP_KEY,
				'orderby'    => 'date_scheduled',
				'order'      => 'ASC',
			)
		);

		if ( empty( $task ) || is_wp_error( $task ) || empty( $task[0] ) ) {
			return false;
		}

		$date = $task[0]->get_date_scheduled();

		return $date ? $date->getTimestamp() : false;
	}

	/**
	 * Calculates the next valid run date for a rule.
	 *
	 * @param \Hizzle\Noptin\Automation_Rules\Automation_Rule $rule The rule.
	 * @param bool                                            $is_rescheduling Whether this is a reschedule.
	 * @param int|null                                        $last_checked_date The last checked timestamp.
	 * @param int                                             $tries The number of attempts.
	 * @return int|false
	 */
	private function calculate_next_run_date( $rule, $is_rescheduling, $last_checked_date = null, $tries = 0 ) {
		if ( $tries >= 7 ) {
			return false;
		}

		$settings  = $rule->get_trigger_settings();
		$frequency = $rule->get_trigger_setting( 'frequency' );
		$day       = $rule->get_trigger_setting( 'day' );
		$date      = $rule->get_trigger_setting( 'date' );
		$year_day  = $rule->get_trigger_setting( 'year_day' );
		$x_days    = $rule->get_trigger_setting( 'x_days' );
		$time      = $rule->get_trigger_setting( 'time' );

		if ( empty( $frequency ) ) {
			$frequency = 'x_days';
		}

		if ( empty( $time ) ) {
			$time = '07:00';
		}

		$next_run = false;

		switch ( $frequency ) {
			case 'daily':
				$next_run = noptin_string_to_timestamp( "+1 day $time", $last_checked_date );
				break;

			case 'weekly':
				$days     = array( 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday' );
				$day      = is_numeric( $day ) ? $day : 0;
				$day      = $days[ (int) $day ] ?? 'sunday';
				$next_run = noptin_string_to_timestamp( "next $day $time", $last_checked_date );
				break;

			case 'monthly':
				$date       = is_numeric( $date ) ? (int) $date : (int) 1;
				$next_month = (int) gmdate( 'n', $last_checked_date ?? time() );
				$next_year  = (int) gmdate( 'Y', $last_checked_date ?? time() );

				$last_day_of_month = (int) gmdate( 't', noptin_string_to_timestamp( "$next_year-$next_month-1" ) );

				if ( $date > $last_day_of_month ) {
					$date = $last_day_of_month;
				}

				if ( $is_rescheduling && ! $last_checked_date ) {
					$next_run = noptin_string_to_timestamp( "$next_year-$next_month-$date $time" );

					if ( $next_run < time() ) {
						++$next_month;
						if ( $next_month > 12 ) {
							$next_month = 1;
							++$next_year;
						}
					}
				} else {
					++$next_month;
					if ( $next_month > 12 ) {
						$next_month = 1;
						++$next_year;
					}
				}

				$last_day_of_month = (int) gmdate( 't', noptin_string_to_timestamp( "$next_year-$next_month-1" ) );

				if ( $date > $last_day_of_month ) {
					$date = $last_day_of_month;
				}

				$next_run = noptin_string_to_timestamp( "$next_year-$next_month-$date $time" );
				break;

			case 'yearly':
				$year_day = empty( $year_day ) ? 1 : (int) $year_day;
				$year_day = max( 1, min( 366, $year_day ) );
				$run_year = (int) gmdate( 'Y', $last_checked_date ?? time() );

				if ( $tries ) {
					++$run_year;
				} else {
					$run_year         = (int) gmdate( 'Y', time() );
					$current_year_day = (int) gmdate( 'z', time() ) + 1;

					if ( $year_day < $current_year_day ) {
						++$run_year;
					}
				}

				$next_run = noptin_string_to_timestamp( "$run_year-01-01 $time" ) + ( $year_day - 1 ) * DAY_IN_SECONDS;
				break;

			case 'x_days':
				$x_days = empty( $x_days ) ? 30 : (int) $x_days;

				if ( $tries ) {
					$next_run = noptin_string_to_timestamp( "+$x_days days $time", $last_checked_date );
				} else {
					$next_run = $settings['next_send'] ?? '';

					if ( ! empty( $next_run ) ) {
						$next_run     = noptin_string_to_timestamp( $next_run );
						$next_run_gmt = $next_run - ( (float) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
					}

					if ( empty( $next_run ) || empty( $next_run_gmt ) || $next_run_gmt <= time() ) {
						$next_run = noptin_string_to_timestamp( "+$x_days days $time" );
					}
				}
				break;
		}

		if ( empty( $next_run ) ) {
			return false;
		}

		$skip_days   = wp_parse_id_list( $settings['skip_days'] ?? array() );
		$day_of_week = (int) gmdate( 'w', $next_run );

		if ( ! empty( $skip_days ) && in_array( $day_of_week, $skip_days, true ) ) {
			return $this->calculate_next_run_date( $rule, $is_rescheduling, $next_run, $tries + 1 );
		}

		return $next_run;
	}
}
