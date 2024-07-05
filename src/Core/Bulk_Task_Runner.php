<?php

namespace Hizzle\Noptin\Core;

/**
 * Bulk task runner class.
 *
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bulk task runner class.
 */
abstract class Bulk_Task_Runner {

	/**
	 * The cron hook.
	 */
	public $cron_hook;

	/**
	 * The cron health check hook.
	 */
	public $cron_health_check_hook;

	/**
	 * The start time.
	 *
	 * Represents when the queue runner was started.
	 *
	 * @var int
	 */
	protected $start_time;

	/**
	 * The number of tasks processed.
	 *
	 * @var int
	 */
	public $processed_tasks = 0;

	/**
	 * Loads the class.
	 *
	 */
	public function __construct() {

		if ( empty( $this->cron_health_check_hook ) ) {
			$this->cron_health_check_hook = $this->cron_hook . '_health_check';
		}

		add_action( 'admin_init', array( $this, 'add_wp_cron_event' ) );
		add_action( $this->cron_hook, array( $this, 'run' ) );
		add_action( $this->cron_health_check_hook, array( $this, 'handle_cron_healthcheck' ) );
		add_filter( 'cron_schedules', array( $this, 'filter_cron_schedules' ) );
		add_action( 'wp_ajax_' . $this->cron_hook, array( $this, 'maybe_handle_rescheduled' ) );
		add_action( 'wp_ajax_nopriv_' . $this->cron_hook, array( $this, 'maybe_handle_rescheduled' ) );
	}

	/**
	 * Add cron workers
	 */
	public function add_wp_cron_event() {
		if ( ! wp_next_scheduled( $this->cron_hook ) ) {
			wp_schedule_event( time(), 'hourly', $this->cron_hook );
		}
	}

	/**
	 * Fired before running the queue.
	 *
	 */
	public function before_run() {

		// Set the start time.
		$this->start_time = time();

		// Lock process.
		$this->lock_process();

		// Raise the memory limit.
		wp_raise_memory_limit();

		// Raise the time limit.
		$this->raise_time_limit( $this->get_time_limit() + 10 );

		// Start cron healthcheck.
		$this->start_cron_healthcheck();
	}

	/**
	 * Returns the next task.
	 *
	 * @return mixed
	 */
	abstract protected function get_next_task();

	/**
	 * Processes the current task.
	 *
	 * @param mixed $task The task to process.
	 */
	abstract protected function process_task( $task );

	/**
	 * Runs the queue.
	 *
	 * Pass each queue item to the task handler, while remaining
	 * within server memory and time limit constraints.
	 */
	public function run() {

		// If already running, bail.
		if ( $this->is_process_running() ) {
			return;
		}

		// Set-up environment.
		$this->before_run();

		// Run the queue.
		do {

			// Fetch the next task.
			$task = $this->get_next_task();

			// Stop if there are no more tasks.
			if ( empty( $task ) ) {
				break;
			}

			// Process the task.
			$this->process_task( $task );

			// Fetch the next task.
			$task = $this->get_next_task();

			// Increment the processed tasks counter.
			++ $this->processed_tasks;

		} while ( ! $this->batch_limits_exceeded() );

		// Unlock process.
		$this->unlock_process();

		// Clear the caches.
		$this->clear_caches();

		// If we have more tasks, wait 3 minutes then complete in the background.
		if ( ! empty( $task ) ) {
			$this->schedule_remaining_tasks();
		} else {
			$this->end_cron_healthcheck();
		}
	}

	/**
	 * Schedules the remaining tasks to be run in the background.
	 */
	protected function schedule_remaining_tasks() {

		if ( class_exists( '\Noptin\Addons_Pack\Tasks\Main' ) ) {
			\Noptin\Addons_Pack\Tasks\Main::delete_scheduled_task( $this->cron_hook, array(), 100 );
		}

		schedule_noptin_background_action( time() + 180, $this->cron_hook );
	}

	/**
	 * Running large batches can eat up memory, as WP adds data to its object cache.
	 *
	 * If using a persistent object store, this has the side effect of flushing that
	 * as well, so this is disabled by default. To enable:
	 *
	 * add_filter( 'noptin_tasks_runner_flush_cache', '__return_true' );
	 */
	protected function clear_caches() {
		if ( ! wp_using_ext_object_cache() || apply_filters( 'noptin_tasks_runner_flush_cache', false ) ) {
			wp_cache_flush();
		}
	}

	/**
	 * Is process running
	 *
	 * Check whether the current process is already running
	 * in a background process.
	 */
	public function is_process_running() {
		if ( get_transient( $this->cron_hook . '_process_lock' ) ) {
			// Process already running.
			return true;
		}

		return false;
	}

	/**
	 * Lock process
	 *
	 * Lock the process so that multiple instances can't run simultaneously.
	 * Override if applicable, but the duration should be greater than that
	 * defined in the time_exceeded() method.
	 */
	protected function lock_process() {

		$lock_duration = apply_filters( $this->cron_hook . '_queue_lock_time', 60 ); // 1 minute.

		set_transient( $this->cron_hook . '_process_lock', microtime(), $lock_duration );
	}

	/**
	 * Unlock process
	 *
	 * Unlock the process so that other instances can spawn.
	 *
	 * @return $this
	 */
	protected function unlock_process() {
		delete_transient( $this->cron_hook . '_process_lock' );

		return $this;
	}

	/**
	 * Attempts to raise the PHP timeout for time intensive processes.
	 *
	 * Only allows raising the existing limit and prevents lowering it.
	 *
	 * @param int $limit The time limit in seconds.
	 */
	public static function raise_time_limit( $limit = 0 ) {
		$limit              = (int) $limit;
		$max_execution_time = (int) ini_get( 'max_execution_time' );

		/*
		 * If the max execution time is already unlimited (zero), or if it exceeds or is equal to the proposed
		 * limit, there is no reason for us to make further changes (we never want to lower it).
		 */
		if ( 0 === $max_execution_time || ( $max_execution_time >= $limit && 0 !== $limit ) ) {
			return;
		}

		if ( function_exists( 'set_time_limit' ) && false === strpos( ini_get( 'disable_functions' ), 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) { // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.safe_modeDeprecatedRemoved
			@set_time_limit( $limit ); // @codingStandardsIgnoreLine
		}
	}

	/**
	 * Get the maximum number of seconds a batch can run for.
	 *
	 * @return int The number of seconds.
	 */
	protected function get_time_limit() {
		$limit = defined( 'NOPTIN_TASKS_RUNNER_TIME_LIMIT' ) ? NOPTIN_TASKS_RUNNER_TIME_LIMIT : 20;

		return absint( apply_filters( $this->cron_hook . '_time_limit', $limit ) );
	}

	/**
	 * Check if the host's max execution time is (likely) to be exceeded if we send any more emails.
	 *
	 * @return bool
	 */
	protected function time_likely_to_be_exceeded() {

		$execution_time        = time() - $this->start_time;
		$max_execution_time    = $this->get_time_limit();
		$time_per_action       = $execution_time / $this->processed_tasks;
		$estimated_time        = $execution_time + ( $time_per_action * 3 );
		$likely_to_be_exceeded = $estimated_time > $max_execution_time;

		return apply_filters( $this->cron_hook . '_maximum_execution_time_likely_to_be_exceeded', $likely_to_be_exceeded, $this, $execution_time, $max_execution_time );
	}

	/**
	 * Get memory limit
	 *
	 * @return int
	 */
	protected function get_memory_limit() {
		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			// Sensible default.
			$memory_limit = '128M';
		}

		if ( ! $memory_limit || -1 === intval( $memory_limit ) ) {
			// Unlimited, set to 32GB.
			$memory_limit = '32G';
		}

		return wp_convert_hr_to_bytes( $memory_limit );
	}

	/**
	 * Memory exceeded
	 *
	 * Ensures the batch process never exceeds 90% of the maximum WordPress memory.
	 *
	 * Based on WP_Background_Process::memory_exceeded()
	 *
	 * @return bool
	 */
	protected function memory_exceeded() {

		$memory_limit    = $this->get_memory_limit() * 0.90;
		$current_memory  = memory_get_usage( true );
		$memory_exceeded = $current_memory >= $memory_limit;

		return apply_filters( $this->cron_hook . '_memory_exceeded', $memory_exceeded, $this );
	}

	/**
	 * See if the batch limits have been exceeded, which is when memory usage is almost at
	 * the maximum limit, or the time to process more actions will exceed the max time limit.
	 *
	 * Based on WP_Background_Process::batch_limits_exceeded()
	 *
	 * @return bool
	 */
	protected function batch_limits_exceeded() {
		return $this->memory_exceeded() || $this->time_likely_to_be_exceeded( $this->processed_tasks );
	}

	/**
	 * Get query URL
	 *
	 * @return string
	 */
	protected function get_query_url() {

		$url = add_query_arg(
			array(
				'action'      => $this->cron_hook,
				'_ajax_nonce' => wp_create_nonce( $this->cron_hook ),
			),
			admin_url( 'admin-ajax.php' )
		);

		/**
		 * Filters the post arguments used during an async request.
		 *
		 * @param string $url
		 */
		return apply_filters( $this->cron_hook . '_ajax_query_url', $url );
	}

	/**
	 * Get ajax args
	 *
	 * @return array
	 */
	protected function get_ajax_args() {

		$args = array(
			'timeout'   => 0.01,
			'blocking'  => false,
			'cookies'   => $_COOKIE,
			'sslverify' => false,
		);

		/**
		 * Filters the post arguments used during an async request.
		 *
		 * @param array $args
		 */
		return apply_filters( $this->cron_hook . '_ajax_query_args', $args );
	}

	/**
	 * Runs a rescheduled batch process.
	 *
	 */
	public function maybe_handle_rescheduled() {

		// Don't lock up other requests while processing.
		session_write_close();

		check_ajax_referer( $this->cron_hook );
		$this->run();

		wp_die();
	}

	/**
	 * Filter CRON schedules.
	 *
	 * @param array $schedules The CRON schedules.
	 */
	public static function filter_cron_schedules( $schedules ) {

		// Adds every 5 minutes to the existing schedules.
		$schedules['every_5_minutes'] = array(
			'interval' => 300,
			'display'  => sprintf(
				/* Translators: %d Number of minutes. */
				__( 'Every %d Minutes', 'newsletter-optin-box' ),
				5
			),
		);

		return $schedules;
	}

	/**
	 * Start cron healthcheck
	 */
	protected function start_cron_healthcheck() {
		if ( ! wp_next_scheduled( $this->cron_health_check_hook ) ) {
			wp_schedule_event( time(), 'every_5_minutes', $this->cron_health_check_hook );
		}
	}

	/**
	 * Handle cron healthcheck
	 *
	 * Restart the background process if not already running
	 * and data exists in the queue.
	 */
	public function handle_cron_healthcheck() {
		if ( ! $this->is_process_running() ) {
			$this->run();
		}

		exit;
	}

	/**
	 * End cron healthcheck
	 */
	protected function end_cron_healthcheck() {
		$timestamp = wp_next_scheduled( $this->cron_health_check_hook );

		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, $this->cron_health_check_hook );
		}
	}
}
