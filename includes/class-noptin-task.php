<?php

/**
 * This class represents a single task.
 *
 * A task is simply a `do_action` call that runs in the background.
 *
 * @since 1.2.7
 */
class Noptin_Task {

	/**
	 * The group to assign this task to.
	 *
	 * @since 1.2.7
	 */
	public $group = 'noptin';

	/**
	 * The action to fire when running this task.
	 *
	 * @since 1.2.7
	 *
	 * @var string
	 */
	protected $action;

	/**
	 * Arguments to pass to callbacks when the action fires.
	 *
	 * @since 1.2.7
	 *
	 * @var array
	 */
	protected $params;

	/**
	 * Task constructor.
	 *
	 * @since 1.2.7
	 *
	 * @param string $action (required) The action to fire when running this task.
	 */
	public function __construct( $action ) {
		$this->action = sanitize_key( $action );
	}

	/**
	 * Pass any number of params to the action callback.
	 *
	 * @since 1.2.7
	 * @param array $params Optional parameters to attach to the hook.
	 *
	 * @return Noptin_Task
	 */
	public function set_params( $params = array() ) {

		$this->params = $params;

		return $this;
	}

	/**
	 * Checks whether ActionScheduler is loaded and is not migrating.
	 *
	 * @since 1.2.7
	 *
	 * @return bool
	 */
	public function is_usable() {

		// No tasks if ActionScheduler wasn't loaded.
		if ( ! class_exists( 'ActionScheduler_DataController' ) ) {
			return false;
		}

		// No tasks if ActionScheduler has not migrated.
		if ( ! ActionScheduler_DataController::is_migration_complete() ) {
			return false;
		}

		return true;
	}

	/**
	 * Runs the task in the background as soon as possible.
	 *
	 * @since 1.2.7
	 * @see do_noptin_background_action
	 * @return int|bool The action id on success. False otherwise.
	 */
	public function do_async() {

		// Fallback to normal cron jobs if action scheduler is not installed.
		if ( ! $this->is_usable() || ! function_exists( 'as_enqueue_async_action' ) ) {
			return wp_schedule_single_event( time(), $this->action, $this->params );
		}

		return as_enqueue_async_action(
			$this->action,
			$this->params,
			$this->group
		);
	}

	/**
	 * Run the task repeatedly with a specified interval in seconds.
	 *
	 * @since 1.2.7
	 *
	 * @param int $timestamp When the first instance of the job will run.
	 * @param int $interval  How long to wait between runs.
	 * @see schedule_noptin_recurring_background_action
	 * @return int|bool The action id on success. False otherwise.
	 */
	public function do_recurring( $timestamp, $interval ) {

		if ( ! $this->is_usable() || ! function_exists( 'as_schedule_recurring_action' ) ) {
			_doing_it_wrong( 'schedule_noptin_recurring_background_action', 'You need to load the action scheduler library or install the action scheduler plugin to use schedule_noptin_recurring_background_action', '1.7.0' );
			return false;
		}

		return as_schedule_recurring_action(
			$timestamp,
			$interval,
			$this->action,
			$this->params,
			$this->group
		);
	}

	/**
	 * Run the task once at some defined point in the future.
	 *
	 * @since 1.2.7
	 *
	 * @param int $timestamp When the first instance of the job will run.
	 * @see schedule_noptin_background_action
	 * @return int|bool The action id on success. False otherwise.
	 */
	public function do_once( $timestamp ) {

		// Try the addons pack task manager first.
		if ( class_exists( '\Noptin\Addons_Pack\Tasks\Main' ) ) {
			return \Noptin\Addons_Pack\Tasks\Main::schedule_task( $this->action, $this->params, $timestamp - time() );
		}

		// Fallback to normal cron jobs if action scheduler is not installed.
		if ( ! $this->is_usable() || ! function_exists( 'as_schedule_single_action' ) ) {
			return wp_schedule_single_event( $timestamp, $this->action, $this->params );
		}

		return as_schedule_single_action(
			$timestamp,
			$this->action,
			$this->params,
			$this->group
		);
	}

	/**
	 * Checks if a task is scheduled.
	 *
	 * @since 3.0.0
	 */
	public function next_scheduled() {

		// Try the addons pack task manager first.
		if ( class_exists( '\Noptin\Addons_Pack\Tasks\Main' ) ) {
			$tasks = \Noptin\Addons_Pack\Tasks\Main::query(
				array(
					'status' => 'pending',
					'number' => 1,
					'hook'   => $this->action,
					'args'   => wp_json_encode( $this->params ),
				)
			);

			if ( ! empty( $tasks ) && ! is_wp_error( $tasks ) ) {
				/** @var \Noptin\Addons_Pack\Tasks\Task[] $tasks */
				$date = $tasks[0]->get_date_scheduled();
				return empty( $date ) ? false : $date->getTimestamp();
			}
		}

		// Check in action scheduler.
		if ( $this->is_usable() && function_exists( 'as_next_scheduled_action' ) ) {
			$timestamp = as_next_scheduled_action( $this->action, $this->params, $this->group );

			if ( is_numeric( $timestamp ) ) {
				return $timestamp;
			}
		}

		return wp_next_scheduled( $this->action, $this->params );
	}

	/**
	 * Deletes a scheduled task.
	 *
	 * @since 3.0.0
	 */
	public function delete() {

		// Try the addons pack task manager.
		if ( class_exists( '\Noptin\Addons_Pack\Tasks\Main' ) ) {
			\Noptin\Addons_Pack\Tasks\Main::delete_scheduled_task( $this->action, $this->params, -1 );
		}

		wp_clear_scheduled_hook( $this->action, $this->params );

		// Delete in action scheduler.
		if ( $this->is_usable() && function_exists( 'as_unschedule_action' ) ) {
			do {
				$unscheduled_action = as_unschedule_action( $this->action, $this->params, $this->group );
			} while ( ! empty( $unscheduled_action ) );
		}
	}
}
