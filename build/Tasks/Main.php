<?php

/**
 * Contains the main task manager class.
 *
 * @since 1.0.0
 */

namespace Hizzle\Noptin\Tasks;

defined( 'ABSPATH' ) || exit;

/**
 * Main component Class.
 *
 */
class Main {

	/**
	 * The cron hook.
	 */
	public $cron_hook = 'noptin_run_tasks';

	/**
	 * The cron health check hook.
	 */
	public $cron_health_check_hook = 'noptin_run_tasks_health_check';

	/**
	 * The start time.
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
	 * Current task.
	 *
	 * @var Task
	 * @since 1.0.0
	 */
	private static $current_task = null;

	/**
	 * Scheduled tasks in this request.
	 *
	 * @var string[]
	 */
	private static $scheduled_tasks = array();

	/**
	 * Loads the class.
	 *
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'add_wp_cron_event' ) );
		add_action( $this->cron_hook, array( $this, 'run' ) );
		add_action( $this->cron_health_check_hook, array( $this, 'handle_cron_healthcheck' ) );
		add_filter( 'cron_schedules', array( $this, 'filter_cron_schedules' ) );
		add_action( 'wp_ajax_' . $this->cron_hook, array( $this, 'maybe_handle_rescheduled' ) );
		add_action( 'wp_ajax_nopriv_' . $this->cron_hook, array( $this, 'maybe_handle_rescheduled' ) );

		add_filter( 'noptin_db_schema', array( __CLASS__, 'add_tasks_table' ) );
		add_filter( 'hizzle_rest_noptin_tasks_collection_js_params', array( __CLASS__, 'filter_tasks_collection_js_params' ) );
		add_filter( 'hizzle_rest_noptin_tasks_record_tabs', array( __CLASS__, 'add_record_tabs' ), 1000 );
		add_action( 'shutdown', array( __CLASS__, 'handle_unexpected_shutdown' ) );
		add_action( 'shutdown', array( $this, 'run_overdue_tasks_on_shutdown' ), 1 );
		add_action( 'noptin_tasks_before_execute', array( __CLASS__, 'set_task' ), 0, 1 );
		add_action( 'noptin_tasks_after_execute', array( __CLASS__, 'reset_task' ), 0 );
		add_action( 'noptin_tasks_failed_execution', array( __CLASS__, 'reset_task' ), 0 );
		add_action( 'noptin_tasks_run_pending', array( $this, 'run_pending' ) );
		add_action( 'noptin_tasks_ensure_cron_scheduled', array( $this, 'ensure_cron_scheduled' ) );

		if ( is_admin() ) {
			add_filter( 'get_noptin_admin_tools', array( __CLASS__, 'filter_admin_tools' ) );
			add_action( 'admin_menu', array( __CLASS__, 'tasks_menu' ), 100 );
			add_action( 'admin_head', array( __CLASS__, 'hide_tasks_menu' ), 0 );
		}
	}

	/**
	 * Class loader.
	 */
	public static function init() {
		$GLOBALS['noptin_tasks'] = new self();
	}

	/**
	 * Filter CRON schedules.
	 *
	 * @param array $schedules The CRON schedules.
	 */
	public static function filter_cron_schedules( $schedules ) {

		$schedules['every_5_minutes'] = array(
			'interval' => 300,
			'display'  => sprintf(
				'Every %d Minutes',
				5
			),
		);

		$schedules['every_minute'] = array(
			'interval' => 60,
			'display'  => 'Every minute',
		);

		return $schedules;
	}

	/**
	 * Reconcile the cron event with the next pending task.
	 */
	public function add_wp_cron_event() {
		if ( ! did_action( 'noptin_db_init' ) ) {
			return;
		}

		$this->ensure_cron_scheduled();
	}

	/**
	 * Schedules a single cron event at the time of the next pending task.
	 * If no pending tasks exist, the cron event is unscheduled.
	 */
	public function ensure_cron_scheduled() {
		// Find the earliest pending task.
		$next_tasks = self::query(
			array(
				'status'  => 'pending',
				'number'  => 1,
				'orderby' => 'date_scheduled',
				'order'   => 'ASC',
			)
		);

		if ( empty( $next_tasks ) || is_wp_error( $next_tasks ) ) {
			// No pending tasks — unschedule.
			$timestamp = wp_next_scheduled( $this->cron_hook );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $this->cron_hook );
			}
			return;
		}

		/** @var Task $next_task */
		$next_task      = $next_tasks[0];
		$scheduled_time = $next_task->get_date_scheduled() ? $next_task->get_date_scheduled()->getTimestamp() : time();
		$run_at         = max( time(), $scheduled_time );
		$existing       = wp_next_scheduled( $this->cron_hook );

		// Already scheduled within a 60-second window — nothing to do.
		if ( $existing && abs( $existing - $run_at ) <= MINUTE_IN_SECONDS ) {
			return;
		}

		if ( $existing ) {
			wp_unschedule_event( $existing, $this->cron_hook );
		}

		$result = wp_schedule_event( $run_at, 'every_minute', $this->cron_hook, array(), true );

		if ( is_wp_error( $result ) ) {
			log_noptin_message( 'Failed to schedule task runner cron event: ' . $result->get_error_message() );
		}
	}

	/**
	 * Fired before running the queue.
	 *
	 */
	public function before_run() {

		$this->start_time = time();
		$this->lock_process();
		wp_raise_memory_limit();
		noptin_raise_time_limit( $this->get_time_limit() + 10 );
		$this->start_cron_healthcheck();

		// Cleanup long running actions.
		self::clean( 10 * $this->get_time_limit() );
	}

	/**
	 * Runs the queue.
	 */
	public function run() {

		if ( $this->is_process_running() ) {
			return;
		}

		$this->before_run();

		do {
			$task = $this->get_next_task();

			if ( empty( $task ) ) {
				break;
			}

			$this->process_task( $task );
			++$this->processed_tasks;

		} while ( ! $this->batch_limits_exceeded() );

		$this->unlock_process();
		$this->clear_caches();

		// Schedule the next run at the time of the next pending task.
		$this->ensure_cron_scheduled();

		if ( empty( $task ) ) {
			$this->end_cron_healthcheck();
		}
	}

	/**
	 * Lock the process so multiple instances can't run simultaneously.
	 */
	protected function lock_process() {
		$lock_duration = apply_filters( $this->cron_hook . '_queue_lock_time', 60 );
		set_transient( $this->cron_hook . '_process_lock', microtime(), $lock_duration );
	}

	/**
	 * Unlock the process.
	 */
	protected function unlock_process() {
		delete_transient( $this->cron_hook . '_process_lock' );
	}

	/**
	 * Flush object caches between batches.
	 */
	protected function clear_caches() {
		if ( ! wp_using_ext_object_cache() || apply_filters( 'noptin_tasks_runner_flush_cache', false ) ) {
			wp_cache_flush();
		}
	}

	/**
	 * Get the maximum number of seconds a batch can run.
	 */
	protected function get_time_limit() {
		return absint( apply_filters( $this->cron_hook . '_time_limit', 20 ) );
	}

	/**
	 * Check whether the time limit is likely to be exceeded.
	 */
	protected function time_likely_to_be_exceeded() {
		if ( 0 === $this->processed_tasks ) {
			return false;
		}

		$execution_time        = time() - $this->start_time;
		$max_execution_time    = $this->get_time_limit();
		$time_per_action       = $execution_time / $this->processed_tasks;
		$estimated_time        = $execution_time + ( $time_per_action * 3 );
		$likely_to_be_exceeded = $estimated_time > $max_execution_time;

		return apply_filters( $this->cron_hook . '_maximum_execution_time_likely_to_be_exceeded', $likely_to_be_exceeded, $this, $execution_time, $max_execution_time );
	}

	/**
	 * Check if batch limits (memory or time) have been exceeded.
	 */
	protected function batch_limits_exceeded() {
		return noptin_memory_exceeded() || $this->time_likely_to_be_exceeded();
	}

	/**
	 * Get the URL for async AJAX processing.
	 */
	protected function get_query_url() {
		$url = add_query_arg(
			array(
				'action'      => $this->cron_hook,
				'_ajax_nonce' => wp_create_nonce( $this->cron_hook ),
			),
			admin_url( 'admin-ajax.php' )
		);

		return apply_filters( $this->cron_hook . '_ajax_query_url', $url );
	}

	/**
	 * Get the arguments for the async AJAX request.
	 */
	protected function get_ajax_args() {
		$args = array(
			'timeout'   => 0.01,
			'blocking'  => false,
			'cookies'   => $_COOKIE,
			'sslverify' => false,
		);

		return apply_filters( $this->cron_hook . '_ajax_query_args', $args );
	}

	/**
	 * Handles a rescheduled batch process via AJAX.
	 */
	public function maybe_handle_rescheduled() {
		session_write_close();
		check_ajax_referer( $this->cron_hook );
		$this->ensure_cron_scheduled();
		$this->run();
		wp_die();
	}

	/**
	 * Start cron healthcheck.
	 */
	protected function start_cron_healthcheck() {
		if ( ! wp_next_scheduled( $this->cron_health_check_hook ) ) {
			wp_schedule_event( time(), 'every_5_minutes', $this->cron_health_check_hook );
		}
	}

	/**
	 * Retrieves the next task.
	 *
	 * @return false|Task Return the first batch from the queue
	 */
	protected function get_next_task() {

		$tasks = self::query(
			array(
				'status'                => 'pending',
				'number'                => 1,
				'date_scheduled_before' => current_time( 'Y-m-d H:i:s' ),
			)
		);

		return empty( $tasks ) ? false : $tasks[0];
	}

	/**
	 * Process an individual task.
	 *
	 * @param Task $task The task to process.
	 */
	public function process_task( $task ) {
		// Abort if the task is not pending.
		if ( 'pending' !== $task->get_status() || ! $task->has_expired() ) {
			return;
		}

		$task->process();
	}

	/**
	 * Remove the healthcheck cron event when all tasks are done.
	 * The main cron hook is managed by ensure_cron_scheduled().
	 */
	protected function end_cron_healthcheck() {
		$healthcheck_timestamp = wp_next_scheduled( $this->cron_health_check_hook );
		if ( $healthcheck_timestamp ) {
			wp_unschedule_event( $healthcheck_timestamp, $this->cron_health_check_hook );
		}
	}

	/**
	 * Handle cron healthcheck.
	 *
	 * Restart the background process if not already running
	 * and data exists in the queue.
	 */
	public function handle_cron_healthcheck() {
		$this->add_wp_cron_event();

		if ( ! $this->is_process_running() ) {
			$this->run();
		}

		exit;
	}

	/**
	 * Runs pending tasks.
	 */
	public function run_pending() {
		wp_remote_get( $this->get_query_url(), $this->get_ajax_args() );
	}

	/**
	 * Runs overdue tasks on shutdown via ajax.
	 */
	public function run_overdue_tasks_on_shutdown() {
		if ( wp_doing_ajax() || wp_doing_cron() || ! did_action( 'noptin_db_init' ) || $this->is_process_running() ) {
			return;
		}

		if ( ! empty( $this->get_next_task() ) ) {
			$this->run_pending();
		}
	}

	/**
	 * Fetch a task by task ID.
	 *
	 * @param int|Task $task Task ID or object.
	 * @return Task Task object.
	 */
	public static function get( $task = 0 ) {

		// If task is already a task object, return it.
		if ( $task instanceof Task ) {
			$task = $task->get_id();
		}

		// Fetch task.
		if ( ! is_numeric( $task ) ) {
			$task = 0;
		}

		$task = noptin()->db()->get( (int) $task, 'tasks' );
		return is_wp_error( $task ) ? noptin()->db()->get( 0, 'tasks' ) : $task;
	}

	/**
	 * Retry a task in 1 hour.
	 *
	 * @param Task $task
	 */
	public static function retry_task( $task, $after = HOUR_IN_SECONDS ) {

		$new_task = $task->clone();
		$new_task->set_status( 'pending' );
		$new_task->set_date_scheduled( time() + $after );
		$new_task->add_log( "Task was retried from task {$task->get_id()}" );
		$new_task->save();

		return $new_task;
	}

	/**
	 * Returns the task statuses.
	 *
	 * @return array
	 */
	public static function get_statuses() {
		return array(
			'pending'  => _x( 'Pending', 'Status', 'newsletter-optin-box' ),
			'running'  => _x( 'Running', 'Status', 'newsletter-optin-box' ),
			'failed'   => _x( 'Failed', 'Status', 'newsletter-optin-box' ),
			'canceled' => _x( 'Canceled', 'Status', 'newsletter-optin-box' ),
			'complete' => _x( 'Complete', 'Status', 'newsletter-optin-box' ),
		);
	}

	public static function set_task( $task ) {
		self::$current_task = $task;
	}

	public static function reset_task() {
		self::$current_task = null;
	}

	public static function handle_unexpected_shutdown() {
		$error = error_get_last();

		if ( ! empty( $error ) && in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ), true ) ) {
			$task = self::$current_task;
			if ( ! empty( $task ) ) {
				$task->unexpected_shutdown( $task, $error );
			}
		}
	}

	/**
	 * Fetches tasks.
	 *
	 * @param array $args Query arguments.
	 * @param string $to_return 'results' returns the found records, 'count' returns the total count, 'aggregate' runs an aggregate query, while 'query' returns query object.
	 * @return int|array|Task[]|Store\Query|WP_Error
	 */
	public static function query( $args = array(), $to_return = 'results' ) {
		return noptin()->db()->query( 'tasks', $args, $to_return );
	}

	/**
	 * Creates a new task.
	 *
	 * @param array $args Task arguments.
	 *    - hook: Required. The hook to trigger, e.g, 'noptin_run_automation_rule'
	 *    - status: The task status. Leave empty or set to 'pending' so that the task can run.
	 *    - args: An array of arguments to pass when running the task.
	 *    - subject: The task subject.
	 *    - primary_id: The primary ID.
	 *    - secondary_id: The secondary ID.
	 *    - lookup_key: An optional lookup key.
	 *    - date_scheduled: The date scheduled.
	 * @return Task|WP_Error
	 */
	public static function create( $args = array() ) {

		$defaults = array(
			'date_scheduled' => time(),
			'args_hash'      => md5( maybe_serialize( $args ) ),
		);

		$args = wp_parse_args( $args, $defaults );

		// Ensure the same task is not scheduled twice in the same request.
		if ( in_array( $args['args_hash'], self::$scheduled_tasks, true ) ) {
			return new \WP_Error( 'noptin_task_already_scheduled', 'Task already scheduled.' );
		}

		// Validate required fields.
		if ( empty( $args['hook'] ) ) {
			return new \WP_Error( 'missing_hook', 'Hook is required.' );
		}

		// If the database is not initialized, schedule the task on init.
		if ( ! did_action( 'noptin_db_init' ) ) {
			add_action(
				'noptin_db_init',
				function () use ( $args ) {
					\Hizzle\Noptin\Tasks\Main::create( $args );
				}
			);
			return null;
		}

		// Get a new task instance.
		$task = self::get( 0 );

		if ( is_wp_error( $task ) ) {
			return $task;
		}

		// Set task properties.
		$task->set_props( $args );

		$result = $task->save();

		if ( is_wp_error( $result ) ) {
			noptin_error_log( 'Error scheduling task: ' . $result->get_error_message() );
		}

		self::$scheduled_tasks[] = $args['args_hash'];

		return $task;
	}

	/**
	 * Schedules a task to run in the background.
	 *
	 * @param string $hook
	 * @param array  $args
	 * @param int    $delay In seconds.
	 * @param int    $interval In seconds.
	 * @return false|\WP_Error|Task
	 */
	public static function schedule_task( $hook, $args = array(), $delay = 0, $interval = 0 ) {

		return self::create(
			array(
				'hook'           => $hook,
				'args'           => $args,
				'date_scheduled' => time() + ( $delay ? $delay : - MINUTE_IN_SECONDS ), // If no delay, set to expire 1 minute ago so it runs immediately.
				'interval'       => $interval,
				'status'         => 'pending',
			)
		);
	}

	/**
	 * Runs an automation rule in the background.
	 *
	 * @param \Hizzle\Noptin\DB\Automation_Rule $rule
	 * @param \Noptin_Abstract_Trigger $trigger  The trigger.
	 * @param \Noptin_Abstract_Action $action  The action.
	 * @param mixed $subject The subject.
	 * @param array $args The arguments.
	 * @return mixed
	 */
	public static function run_automation_rule( $subject, $rule, $args, $trigger ) {

		// Are we delaying the action?
		$delay = $rule->get_delay();
		$delay = ! is_numeric( $delay ) ? 0 : (int) $delay;

		// Create the task.
		$task = self::create(
			array(
				'hook'           => 'noptin_run_automation_rule',
				'args'           => $trigger->serialize_trigger_args( $args ),
				'date_scheduled' => time() + ( $delay ? $delay : - MINUTE_IN_SECONDS ), // If no delay, set to expire 1 minute ago so it runs immediately.
				'subject'        => $trigger->get_subject_email( $subject, $rule, $args ),
				'status'         => 'pending',
				'primary_id'     => $rule->get_id(),
				'secondary_id'   => $args['automation_rule_secondary_id'] ?? null,
				'lookup_key'     => $args['automation_rule_lookup_key'] ?? $trigger->get_id(),
			)
		);

		if ( $task instanceof Task && 'failed' === $task->get_status() ) {
			$log = $task->get_last_log();
			return new \WP_Error( 'noptin_task_failed', empty( $log ) ? 'Task failed to run.' : $log );
		}

		return is_wp_error( $task ) ? $task : true;
	}

	/**
	 * Counts the number of times an automation rule has run.
	 *
	 * @param array $args
	 * @return int
	 */
	public static function count_rule_runs( $args ) {
		static $cache = array();

		if ( empty( $GLOBALS['current_noptin_rule'] ) ) {
			return 0;
		}

		$query = array(
			'hook'       => 'noptin_run_automation_rule',
			'primary_id' => $GLOBALS['current_noptin_rule'],
			'status'     => array( 'complete', 'pending' ),
		);

		if ( ! empty( $args['count_for'] ) && 'user' === $args['count_for'] ) {
			$query['subject'] = $GLOBALS['current_noptin_email'];
		}

		// Optional since in seconds.
		if ( ! empty( $args['since'] ) ) {
			$query['date_created_after'] = gmdate( 'Y-m-d H:i:s', time() + (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) - (int) $args['since'] );
		}

		$cache_key = md5( wp_json_encode( $query ) );

		if ( ! isset( $cache[ $cache_key ] ) ) {
			$cache[ $cache_key ] = self::query( $query, 'count' );
		}

		return $cache[ $cache_key ];
	}

	/**
	 * Gets the next scheduled task.
	 *
	 * @param string $hook
	 * @param array  $args
	 * @return false|Task
	 */
	public static function get_next_scheduled_task( $hook, $args = array() ) {

		/** @var Task[] $tasks */
		$tasks = self::query(
			array(
				'status' => 'pending',
				'number' => 1,
				'hook'   => $hook,
				'args'   => wp_json_encode( $args ),
			)
		);

		if ( ! empty( $tasks ) && ! is_wp_error( $tasks ) ) {
			return $tasks[0];
		}

		return false;
	}

	/**
	 * Deletes a scheduled task.
	 *
	 * @param string $hook
	 * @param array  $args
	 * @param int    $limit
	 * @return false|\WP_Error|Task
	 */
	public static function delete_scheduled_task( $hook, $args = array(), $limit = 1 ) {
		noptin()->db()->delete_where(
			array(
				'status' => 'pending',
				'number' => $limit,
				'hook'   => $hook,
				'args'   => wp_json_encode( $args ),
			),
			'tasks'
		);
	}

	public static function delete_old_tasks() {
		$lifespan = apply_filters( 'noptin_tasks_retention_period', 3 * MONTH_IN_SECONDS );

		/** @var Task[] $tasks */
		$tasks = self::query(
			array(
				'status'               => array( 'complete', 'canceled' ),
				'number'               => static::get_batch_size(),
				'date_modified_before' => gmdate( 'Y-m-d H:i:s', time() + (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) - (int) $lifespan ),
			)
		);

		foreach ( $tasks as $task ) {
			$task->delete();
		}
	}

	/**
	 * Mark tasks that have been running for more than a given time limit as failed, based on
	 * the assumption some uncatachable and unloggable fatal error occurred during processing.
	 *
	 *
	 * @param int $time_limit The number of seconds to allow a task to run before it is considered to have failed. Default 300 (5 minutes).
	 */
	public static function mark_failures( $time_limit = 300 ) {
		$timeout = apply_filters( 'noptin_tasks_failure_period', $time_limit );
		if ( $timeout < 0 ) {
			return;
		}

		$tasks = self::query(
			array(
				'status'               => 'running',
				'number'               => static::get_batch_size(),
				'date_modified_before' => gmdate( 'Y-m-d H:i:s', time() + (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) - (int) $timeout ),
			)
		);

		foreach ( $tasks as $task ) {
			$task->timed_out( $task, $timeout );
		}
	}

	/**
	 * Do all of the cleaning actions.
	 *
	 * @param int $time_limit The number of seconds to use as the timeout and failure period. Default 300 (5 minutes).
	 */
	public static function clean( $time_limit = 300 ) {
		static::delete_old_tasks();
		static::mark_failures( $time_limit );
	}

	/**
	 * Get the batch size for cleaning the queue.
	 *
	 * @return int
	 */
	protected static function get_batch_size() {
		return absint( apply_filters( 'noptin_tasks_cleanup_batch_size', 20 ) );
	}

	/**
	 * Filters admin tools.
	 *
	 * @param array $tools
	 * @return array
	 */
	public static function filter_admin_tools( $tools ) {

		$tools['scheduled_tasks'] = array(
			'name'   => __( 'Scheduled Tasks', 'newsletter-optin-box' ),
			'button' => __( 'View', 'newsletter-optin-box' ),
			'desc'   => __( 'View a list of scheduled tasks.', 'newsletter-optin-box' ),
			'url'    => add_query_arg( 'page', 'noptin-tasks', admin_url( 'admin.php' ) ),
		);

		return $tools;
	}

	/**
	 * Adds the tasks table to the schema.
	 *
	 * @param array $schema The database schema.
	 * @return array
	 */
	public static function add_tasks_table( $schema ) {

		return array_merge(
			$schema,
			array(

				// Tasks.
				'tasks' => array(
					'object'        => '\Hizzle\Noptin\Tasks\Task',
					'singular_name' => 'task',
					'labels'        => array(
						'name'          => __( 'Tasks', 'newsletter-optin-box' ),
						'singular_name' => __( 'Task', 'newsletter-optin-box' ),
						'add_new'       => __( 'Add New', 'newsletter-optin-box' ),
						'add_new_item'  => __( 'Add New Task', 'newsletter-optin-box' ),
						'edit_item'     => __( 'Edit Task', 'newsletter-optin-box' ),
						'new_item'      => __( 'New Task', 'newsletter-optin-box' ),
						'view_item'     => __( 'View Task', 'newsletter-optin-box' ),
						'view_items'    => __( 'View Tasks', 'newsletter-optin-box' ),
						'search_items'  => __( 'Search Tasks', 'newsletter-optin-box' ),
						'not_found'     => __( 'No tasks found.', 'newsletter-optin-box' ),
						'import'        => __( 'Import Tasks', 'newsletter-optin-box' ),
					),
					'props'         => array(

						'id'             => array(
							'type'        => 'BIGINT',
							'length'      => 20,
							'nullable'    => false,
							'description' => __( 'Unique identifier for this resource.', 'newsletter-optin-box' ),
							'readonly'    => true,
							'extra'       => 'AUTO_INCREMENT',
						),

						'hook'           => array(
							'type'        => 'VARCHAR',
							'length'      => 191,
							'nullable'    => false,
							'description' => __( 'The hook ID.', 'newsletter-optin-box' ),
						),

						'subject'        => array(
							'type'        => 'VARCHAR',
							'length'      => 191,
							'nullable'    => true,
							'description' => __( 'The subject ID, email, etc.', 'newsletter-optin-box' ),
						),

						'primary_id'     => array(
							'type'        => 'BIGINT',
							'length'      => 20,
							'nullable'    => true,
							'description' => 'Primary identifier for this resource.',
						),

						'secondary_id'   => array(
							'type'        => 'BIGINT',
							'length'      => 20,
							'nullable'    => true,
							'description' => 'Secondary identifier for this resource.',
						),

						'status'         => array(
							'type'        => 'VARCHAR',
							'length'      => 20,
							'nullable'    => false,
							'description' => __( 'Status', 'newsletter-optin-box' ),
							'enum'        => __CLASS__ . '::get_statuses',
							'default'     => 'pending',
						),

						'lookup_key'     => array(
							'type'        => 'VARCHAR',
							'length'      => 191,
							'nullable'    => true,
							'description' => 'The lookup key.',
						),

						'args'           => array(
							'type'        => 'TEXT',
							'description' => __( 'The args to pass to the hook.', 'newsletter-optin-box' ),
						),

						'args_hash'      => array(
							'type'        => 'VARCHAR',
							'length'      => 32,
							'nullable'    => false,
							'description' => __( 'The args hash.', 'newsletter-optin-box' ),
						),

						'date_created'   => array(
							'type'        => 'DATETIME',
							'nullable'    => false,
							'description' => 'Creation date',
							'readonly'    => true,
						),

						'date_modified'  => array(
							'type'        => 'DATETIME',
							'nullable'    => false,
							'description' => 'Last modification date',
							'readonly'    => true,
						),

						'date_scheduled' => array(
							'type'        => 'DATETIME',
							'nullable'    => false,
							'description' => __( 'Scheduled date', 'newsletter-optin-box' ),
						),

						'metadata'       => array(
							'type'        => 'TEXT',
							'description' => 'A key value array of additional metadata about the task',
						),
					),

					'keys'          => array(
						'primary'        => array( 'id' ),
						'status'         => array( 'status' ),
						'subject'        => array( 'subject' ),
						'primary_id'     => array( 'primary_id' ),
						'secondary_id'   => array( 'secondary_id' ),
						'lookup_key'     => array( 'lookup_key' ),
						'date_scheduled' => array( 'date_scheduled' ),
						'hook'           => array( 'hook' ),
					),
				),
			)
		);
	}

	/**
	 * Filters the subscriber's collection JS params.
	 *
	 * @param array $params
	 * @return array
	 */
	public static function filter_tasks_collection_js_params( $params ) {

		$params['hidden'] = array_merge(
			$params['hidden'],
			array( 'args_hash', 'date_modified' )
		);

		$params['badges'] = array( 'status' );

		foreach ( $params['schema'] as $key => $field ) {
			if ( 'hook' === $field['name'] ) {
				$hook               = $params['schema'][ $key ];
				$hook['is_primary'] = true;
				unset( $params['schema'][ $key ] );
				array_unshift( $params['schema'], $hook );
				$params['schema'] = array_values( $params['schema'] );
				break;
			}
		}

		return $params;
	}

	/**
	 * Adds a logs tab to the record's overview string.
	 *
	 * @param array $tabs
	 * @return array
	 */
	public static function add_record_tabs( $tabs ) {

		// Add logs.
		$tabs['logs'] = array(
			'title'    => __( 'Log', 'newsletter-optin-box' ),
			'type'     => 'table',
			'headers'  => array(
				array(
					'label'      => __( 'Time', 'newsletter-optin-box' ),
					'name'       => 'date',
					'is_primary' => true,
				),
				array(
					'label' => __( 'Message', 'newsletter-optin-box' ),
					'name'  => 'message',
				),
			),
			'callback' => array( __CLASS__, 'logs_callback' ),
		);

		return $tabs;
	}

	/**
	 * Retrieves the task's logs.
	 *
	 * @param array $request
	 * @return array
	 */
	public static function logs_callback( $request ) {

		$task = self::get( $request['id'] );

		if ( is_wp_error( $task ) ) {
			return $task;
		}

		$logs = $task->get_logs();

		if ( ! is_array( $logs ) || empty( $logs ) ) {
			return array();
		}

		$prepared = array();

		foreach ( $logs as $index => $log ) {
			$prepared[] = array_merge(
				$log,
				array(
					'id'   => $index + 1,
					'date' => gmdate( 'Y-m-d H:i:s', $log['date'] + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ),
				)
			);
		}

		return $prepared;
	}

	/**
	 * Tasks menu.
	 */
	public static function tasks_menu() {

		$hook_suffix = add_submenu_page(
			'noptin',
			esc_html__( 'Tasks', 'newsletter-optin-box' ),
			esc_html__( 'Tasks', 'newsletter-optin-box' ),
			get_noptin_capability(),
			'noptin-tasks',
			'\Hizzle\WordPress\ScriptManager::render_collection'
		);

		\Hizzle\WordPress\ScriptManager::add_collection(
			$hook_suffix,
			'noptin',
			'tasks'
		);
	}

	/**
	 * Hide the tasks menu.
	 */
	public static function hide_tasks_menu() {
		remove_submenu_page( 'noptin', 'noptin-tasks' );
	}

	/**
	 * Is process running
	 *
	 * Check whether the current process is already running
	 * in a background process.
	 */
	public function is_process_running() {
		if ( ! wp_doing_ajax() && get_transient( $this->cron_hook . '_process_lock' ) ) {
			// Process already running.
			return true;
		}

		return false;
	}
}
