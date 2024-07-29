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
class Main extends \Hizzle\Noptin\Core\Bulk_Task_Runner {

	/**
	 * The cron hook.
	 */
	public $cron_hook = 'noptin_run_tasks';

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
		parent::__construct();

		add_filter( 'noptin_db_schema', array( __CLASS__, 'add_tasks_table' ) );
		add_filter( 'hizzle_rest_noptin_tasks_collection_js_params', array( __CLASS__, 'filter_tasks_collection_js_params' ) );
		add_filter( 'hizzle_rest_noptin_tasks_record_tabs', array( __CLASS__, 'add_record_tabs' ), 1000 );
		add_action( 'shutdown', array( __CLASS__, 'handle_unexpected_shutdown' ) );
		add_action( 'noptin_tasks_before_execute', array( __CLASS__, 'set_task' ), 0, 1 );
		add_action( 'noptin_tasks_after_execute', array( __CLASS__, 'reset_task' ), 0 );
		add_action( 'noptin_tasks_failed_execution', array( __CLASS__, 'reset_task' ), 0 );
		add_action( 'noptin_tasks_run_pending', array( $this, 'run_pending' ) );
		add_filter( 'noptin-addons-pack-modules', array( __CLASS__, 'remove_addons_tasks' ) );

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

		$schedules = parent::filter_cron_schedules( $schedules );

		$schedules['every_minute'] = array(
			'interval' => 60, // in seconds
			'display'  => __( 'Every minute', 'newsletter-optin-box' ),
		);

		return $schedules;
	}

	/**
	 * Add cron workers
	 */
	public function add_wp_cron_event() {
		if ( ! wp_next_scheduled( $this->cron_hook ) ) {
			$result = wp_schedule_event( time(), 'every_minute', $this->cron_hook, array(), true );

			if ( is_wp_error( $result ) ) {
				log_noptin_message( 'Failed to schedule task runner cron event: ' . $result->get_error_message() );
			}
		}
	}

	/**
	 * Fired before running the queue.
	 *
	 */
	public function before_run() {

		// Parent actions.
		parent::before_run();

		// Cleanup long running actions.
		self::clean( 10 * $this->get_time_limit() );
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
	 * Schedules the remaining tasks to be run in the background.
	 */
	protected function schedule_remaining_tasks() {
		// Do nothing.
	}

	/**
	 * End cron healthcheck
	 */
	protected function end_cron_healthcheck() {
		// Do nothing.
	}

	/**
	 * Handle cron healthcheck
	 *
	 * Restart the background process if not already running
	 * and data exists in the queue.
	 */
	public function handle_cron_healthcheck() {
		$this->add_wp_cron_event();

		parent::handle_cron_healthcheck();
	}

	/**
	 * Runs pending tasks.
	 */
	public function run_pending() {
		wp_remote_get( $this->get_query_url(), $this->get_ajax_args() );
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

		$new_task = self::get( 0 );

		if ( is_wp_error( $new_task ) ) {
			return $new_task;
		}

		// Set the props.
		$new_task->set_hook( $task->get_hook() );
		$new_task->set_subject( $task->get_subject() );
		$new_task->set_primary_id( $task->get_primary_id() );
		$new_task->set_secondary_id( $task->get_secondary_id() );
		$new_task->set_status( 'pending' );
		$new_task->set_args( $task->get_args() );
		$new_task->set_args_hash( $task->get_args_hash() );
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
	 * Schedules a task to run in the background.
	 *
	 * @param string $hook
	 * @param array  $args
	 * @param int    $delay In seconds.
	 * @param int    $interval In seconds.
	 * @return false|\WP_Error|Task
	 */
	public static function schedule_task( $hook, $args = array(), $delay = 0, $interval = 0 ) {

		$runs_on      = empty( $delay ) ? time() : time() + $delay;
		$args_hash    = md5( maybe_serialize( $args ) );
		$unique_check = $hook . '|' . $args_hash . '|' . $delay . '|' . $interval;

		// Ensure the same task is not scheduled twice in the same request.
		if ( in_array( $unique_check, self::$scheduled_tasks, true ) ) {
			return;
		}

		$task = self::get( 0 );

		if ( is_wp_error( $task ) ) {
			return $task;
		}

		// Set the props.
		/** @var Task $task */
		$task->set_hook( $hook );
		$task->set_status( 'pending' );
		$task->set_args( wp_json_encode( $args ) );
		$task->set_args_hash( $args_hash );
		$task->set_date_scheduled( $runs_on );

		if ( ! empty( $interval ) ) {
			$task->update_meta( 'interval', $interval );
		}

		$task->save();

		self::$scheduled_tasks[] = $unique_check;

		return $task;
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
		$delay        = $rule->get_delay();
		$delay        = ! is_numeric( $delay ) ? 0 : (int) $delay;
		$trigger_args = $trigger->serialize_trigger_args( $args );
		$email        = $trigger->get_subject_email( $subject, $rule, $args );
		$args_hash    = md5( maybe_serialize( $trigger_args ) );
		$unique_check = 'noptin_run_automation_rule|' . $args_hash . '|' . $delay . '|' . $rule->get_id() . '|' . $email;

		// Ensure the same task is not scheduled twice in the same request.
		if ( in_array( $unique_check, self::$scheduled_tasks, true ) ) {
			return new \WP_Error( 'noptin_task_already_scheduled', 'Task already scheduled.' );
		}

		$task = self::get( 0 );

		if ( is_wp_error( $task ) ) {
			return $task;
		}

		// Set the props.
		/** @var Task $task */
		$task->set_hook( 'noptin_run_automation_rule' );
		$task->set_status( 'pending' );
		$task->set_args( wp_json_encode( $trigger_args ) );
		$task->set_args_hash( $args_hash );
		$task->set_date_scheduled( time() + $delay );
		$task->set_subject( $email );
		$task->set_primary_id( $rule->get_id() );
		$task->save();

		self::$scheduled_tasks[] = $unique_check;

		if ( 'failed' === $task->get_status() ) {
			$log = $task->get_last_log();
			return new \WP_Error( 'noptin_task_failed', empty( $log ) ? 'Task failed to run.' : $log );
		}

		return true;
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
		$lifespan = apply_filters( 'noptin_tasks_retention_period', MONTH_IN_SECONDS );

		/** @var Task[] $tasks */
		$tasks = self::query(
			array(
				'status'               => array( 'complete', 'canceled' ),
				'number'               => static::get_batch_size(),
				'date_modified_before' => gmdate( 'Y-m-d H:i:s', time() + (int) ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) - (int) $lifespan ),
			)
		);

		foreach ( $tasks as $task ) {
			// Only delete if no subject, primary id, and secondary id.
			if ( is_null( $task->get_subject() ) && is_null( $task->get_primary_id() ) && is_null( $task->get_secondary_id() ) ) {
				$task->delete();
			}
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

		$params['badges']  = array( 'status' );

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
			'\Hizzle\Noptin\Misc\Store_UI::render_admin_page'
		);

		\Hizzle\Noptin\Misc\Store_UI::collection_menu( $hook_suffix, 'noptin/tasks' );
	}

	/**
	 * Hide the tasks menu.
	 */
	public static function hide_tasks_menu() {
		remove_submenu_page( 'noptin', 'noptin-tasks' );

		if ( isset( $_GET['task_action'] ) && isset( $_GET['task_id'] ) && isset( $_GET['task_nonce'] ) && wp_verify_nonce( $_GET['task_nonce'], 'noptin_task_action' ) ) {
			$action  = sanitize_text_field( wp_unslash( $_GET['task_action'] ) );

			$task = self::get( absint( $_GET['task_id'] ) );

			if ( ! is_wp_error( $task ) ) {
				if ( 'pending' === $task->get_status() && 'run' === $action ) {
					$task->process();
				} elseif ( 'pending' === $task->get_status() && 'cancel' === $action ) {
					$task->task_canceled( $task );
				} elseif ( 'pending' !== $task->get_status() && 're_run' === $action ) {
					self::retry_task( $task, 0 );
				}
			}
		}
	}

	/**
	 * Removes the tasks module from the addons pack.
	 *
	 * @param array $modules
	 * @return array
	 */
	public static function remove_addons_tasks( $modules ) {
		unset( $modules['tasks'] );
		return $modules;
	}
}
