<?php
/**
 * This class handles checkout sessions.
 *
 * @version 1.0.0
 */

namespace Hizzle\Noptin\Tasks;

use Hizzle\Store\Date_Time;

defined( 'ABSPATH' ) || exit;

/**
 * Task.
 */
class Task extends \Hizzle\Store\Record {

	/**
	 * Get the hook.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_hook( $context = 'view' ) {
		return $this->get_prop( 'hook', $context );
	}

	/**
	 * Set the hook.
	 *
	 * @param string $value hook.
	 */
	public function set_hook( $value ) {
		$this->set_prop( 'hook', sanitize_text_field( $value ) );
	}

	/**
	 * Get the subject.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 */
	public function get_subject( $context = 'view' ) {
		return $this->get_prop( 'subject', $context );
	}

	/**
	 * Set the subject.
	 *
	 * @param string $value subject.
	 */
	public function set_subject( $value ) {
		$this->set_prop( 'subject', empty( $value ) ? null : sanitize_text_field( $value ) );
	}

	/**
	 * Get the primary external id.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 */
	public function get_primary_id( $context = 'view' ) {
		return $this->get_prop( 'primary_id', $context );
	}

	/**
	 * Set the primary external id.
	 *
	 * @param string $value primary id.
	 */
	public function set_primary_id( $value ) {
		$this->set_prop( 'primary_id', ! is_numeric( $value ) ? null : absint( $value ) );
	}

	/**
	 * Get the secondary external id.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 */
	public function get_secondary_id( $context = 'view' ) {
		return $this->get_prop( 'secondary_id', $context );
	}

	/**
	 * Set the secondary external id.
	 *
	 * @param string $value secondary id.
	 */
	public function set_secondary_id( $value ) {
		$this->set_prop( 'secondary_id', ! is_numeric( $value ) ? null : absint( $value ) );
	}

	/**
	 * Get the status.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		return $this->get_prop( 'status', $context );
	}

	/**
	 * Set the status.
	 *
	 * @param string $value status.
	 */
	public function set_status( $value ) {
		$this->set_prop( 'status', sanitize_text_field( $value ) );
	}

	/**
	 * Get the args.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_args( $context = 'view' ) {
		return $this->get_prop( 'args', $context );
	}

	/**
	 * Set the args.
	 *
	 * @param string $value args.
	 */
	public function set_args( $value ) {
		$this->set_prop( 'args', $value );
	}

	/**
	 * Get the args hash.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_args_hash( $context = 'view' ) {
		return $this->get_prop( 'args_hash', $context );
	}

	/**
	 * Set the args hash.
	 *
	 * @param string $value args.
	 */
	public function set_args_hash( $value ) {
		$this->set_prop( 'args_hash', $value );
	}

	/**
	 * Get the date created.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return Date_Time|null
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Set the date created.
	 *
	 * @param Date_Time|null|string|int $value The date created.
	 */
	public function set_date_created( $value ) {
		$this->set_date_prop( 'date_created', $value );
	}

	/**
	 * Get the date modified.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return Date_Time|null
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
	}

	/**
	 * Set the date modified.
	 *
	 * @param Date_Time|null|string|int $value The date modified.
	 */
	public function set_date_modified( $value ) {
		$this->set_date_prop( 'date_modified', $value );
	}

	/**
	 * Get the date scheduled.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return Date_Time|null
	 */
	public function get_date_scheduled( $context = 'view' ) {
		return $this->get_prop( 'date_scheduled', $context );
	}

	/**
	 * Set the date scheduled.
	 *
	 * @param Date_Time|null|string|int $value The date scheduled.
	 */
	public function set_date_scheduled( $value ) {
		$this->set_date_prop( 'date_scheduled', empty( $value ) ? time() : $value );
	}

	/**
	 * Checks if the task has expired.
	 *
	 * @return bool
	 */
	public function has_expired() {
		$expiration = $this->get_date_scheduled();
		return empty( $expiration ) || $expiration->getTimestamp() <= time();
	}

	/**
	 * Returns all logs.
	 *
	 * @return array
	 */
	public function get_logs() {
		$logs = $this->get_meta( 'logs' );
		return is_array( $logs ) ? $logs : array();
	}

	/**
	 * Adds a log message.
	 *
	 * @param string $message Log message.
	 */
	public function add_log( $message ) {
		$logs   = $this->get_logs();
		$logs[] = array(
			'message' => $message,
			'date'    => time(),
		);
		$this->update_meta( 'logs', $logs );
	}

	/**
	 * Retrieves the last log message.
	 * @return string
	 */
	public function get_last_log() {
		$logs = $this->get_logs();
		$last = end( $logs );
		return $last['message'] ?? '';
	}

	/**
	 * Processes a task.
	 *
	 */
	public function process() {
		try {
			do_action( 'noptin_tasks_before_execute', $this );

			// Mark the task as running.
			$this->task_started( $this );

			// Execute the task.
			$this->run();

			// Mark the task as complete.
			$this->task_complete( $this );

			do_action( 'noptin_tasks_after_execute', $this );
		} catch ( \Exception $e ) {
			$this->task_failed( $this, $e );
			do_action( 'noptin_tasks_failed_execution', $this, $e );
		}
	}

	/**
	 * Runs the task.
	 *
	 * @throws \Exception When the task is invalid.
	 */
	protected function run() {

		// Abort if no hook.
		$hook = $this->get_hook();
		if ( empty( $hook ) ) {
			throw new \Exception( 'Invalid task: no hook' );
		}

		$args = json_decode( $this->get_args(), true );

		if ( ! is_array( $args ) ) {
			$args = array();
		}

		// Run the task.
		if ( is_null( $this->get_subject() ) && is_null( $this->get_primary_id() ) && is_null( $this->get_secondary_id() ) ) {
			do_action_ref_array( $hook, $args );
		} else {
			do_action( $hook, $this, $args );
		}
	}

	/**
	 * @param Task $task
	 * @param string $message
	 * @param string $status
	 *
	 * @return string The log entry ID
	 */
	public function set_state( $task, $message, $status = '' ) {
		$task->add_log( $message );

		if ( ! empty( $status ) ) {
			$task->set_status( $status );
		}

		$task->save();

		// Is this a recurring task?
		if ( in_array( $this->get_status(), array( 'complete', 'pending' ), true ) && $task->get_meta( 'interval' ) ) {
			$new_task = $task->clone();
			$new_task->set_date_created( time() );
			$new_task->set_date_modified( time() );
			$new_task->set_date_scheduled( time() + (int) $task->get_meta( 'interval' ) );
			$new_task->add_log( 'Task rescheduled from #' . $task->get_id() );
			$new_task->set_status( 'pending' );
			$result = $new_task->save();

			if ( is_wp_error( $result ) ) {
				$task->add_log( 'Failed to reschedule task: ' . $result->get_error_message() );
				$task->save();
			}
		}
	}

	public function task_canceled( $task, $note = 'task canceled' ) {
		$this->set_state( $task, $note, 'canceled' );
	}

	public function task_started( $task ) {
		$this->set_state( $task, 'task started', 'running' );
	}

	public function task_complete( $task ) {
		$this->set_state( $task, 'task complete', 'complete' );
	}

	public function task_failed( $task, $exception ) {
		$this->set_state( $task, sprintf( 'task failed: %s', $exception->getMessage() ), 'failed' );
	}

	public function timed_out( $task, $timeout ) {
		$this->set_state( $task, sprintf( 'task marked as failed after %s seconds. Unknown error occurred. Check server, PHP and database error logs to diagnose cause.', $timeout ), 'failed' );
	}

	public function unexpected_shutdown( $task, $error ) {
		if ( ! empty( $error ) ) {
			$this->set_state( $task, sprintf( 'unexpected shutdown: PHP Fatal error %1$s in %2$s on line %3$s', $error['message'], $error['file'], $error['line'] ), 'failed' );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function save() {
		$result = parent::save();

		if ( $this->exists() && $this->get_status() === 'pending' && $this->has_expired() ) {
			if ( $this->get_subject() ) {
				$this->process();
			} else {
				do_action( 'noptin_tasks_run_pending' );
			}
		}

		return $result;
	}

	/**
	 * Returns the record's overview.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_overview() {

		$overview = array();

		// Prepare action links.
		$action_links = array();

		if ( 'pending' === $this->get_status() ) {
			$action_links[] = array(
				'label' => __( 'Run', 'newsletter-optin-box' ),
				'value' => wp_nonce_url(
					add_query_arg(
						array(
							'task_action' => 'run',
							'task_id'     => $this->get_id(),
							'page'        => 'noptin-tasks',
						),
						admin_url( 'admin.php' )
					),
					'noptin_task_action',
					'task_nonce'
				),
			);

			$action_links[] = array(
				'label' => __( 'Cancel', 'newsletter-optin-box' ),
				'value' => wp_nonce_url(
					add_query_arg(
						array(
							'task_action' => 'cancel',
							'task_id'     => $this->get_id(),
							'page'        => 'noptin-tasks',
						),
						admin_url( 'admin.php' )
					),
					'noptin_task_action',
					'task_nonce'
				),
			);
		} else {
			$action_links[] = array(
				'label' => __( 'Re-run', 'newsletter-optin-box' ),
				'value' => wp_nonce_url(
					add_query_arg(
						array(
							'task_action' => 're_run',
							'task_id'     => $this->get_id(),
							'page'        => 'noptin-tasks',
						),
						admin_url( 'admin.php' )
					),
					'noptin_task_action',
					'task_nonce'
				),
			);
		}

		// Delete task.
		$action_links[] = array(
			'label'  => __( 'Delete', 'newsletter-optin-box' ),
			'value'  => __( 'Are you sure you want to delete this record?', 'newsletter-optin-box' ),
			'action' => 'delete',
		);

		$overview['action_links'] = array(
			'type'  => 'action_links',
			'links' => $action_links,
		);

		return apply_filters( 'noptin_task_overview', $overview, $this );
	}

	public function clone() {
		$new_task = Main::get( 0 );
		$data     = $this->get_data();

		unset( $data['id'] );
		unset( $data['date_created'] );
		unset( $data['date_modified'] );
		unset( $data['date_scheduled'] );
		unset( $data['metadata']['logs'] );

		$new_task->set_props( $data );
		return $new_task;
	}
}
