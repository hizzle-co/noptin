<?php

namespace Hizzle\Noptin\Tasks;

defined( 'ABSPATH' ) || exit;

/**
 * Adds automation run status and filtering to the tasks collection.
 */
class Run_History {

	const HOOK = 'noptin_run_automation_rule';

	/**
	 * Register the task history hooks.
	 */
	public static function init() {
		add_filter( 'hizzle_rest_noptin_tasks_collection_params', array( __CLASS__, 'collection_params' ) );
		add_filter( 'noptin_tasks_pre_query', array( __CLASS__, 'filter_roots_by_task_status' ), 10, 2 );
	}

	/**
	 * Register the run status filter for the tasks endpoint.
	 *
	 * @param array $params Collection query parameters.
	 * @return array Collection query parameters with the run status filter.
	 */
	public static function collection_params( $params ) {
		$params['run_task_status'] = array(
			'description' => 'Return root automation tasks with this status on the root or any of its steps.',
			'type'        => 'string',
			'enum'        => array_keys( Main::get_statuses() ),
		);
		return $params;
	}

	/**
	 * Restrict run roots to those containing a task with the requested status.
	 *
	 * @param mixed $results Results supplied by an earlier query filter.
	 * @param \Hizzle\Store\Query $query Tasks query.
	 * @return mixed Results supplied by an earlier query filter.
	 */
	public static function filter_roots_by_task_status( $results, $query ) {
		global $wpdb;

		$status = $query->get( 'run_task_status' );
		if ( null !== $results || ! is_string( $status ) || ! array_key_exists( $status, Main::get_statuses() ) ) {
			return $results;
		}

		$table               = $wpdb->prefix . 'noptin_tasks';
		$query->query_where .= $wpdb->prepare(
			' AND %i.hook = %s AND %i.parent_id IS NULL
			AND (%i.status = %s OR EXISTS (
				SELECT 1 FROM %i step WHERE step.parent_id = %i.id AND step.hook = %s AND step.status = %s
			))',
			$table,
			self::HOOK,
			$table,
			$table,
			$status,
			$table,
			$table,
			self::HOOK,
			$status
		);
		return $results;
	}

	/**
	 * Outstanding tasks take precedence. A successful retry resolves failures
	 * from the same chain while preserving the individual attempts in history.
	 */
	public static function get_status( $root ) {
		global $wpdb;

		$table  = $wpdb->prefix . 'noptin_tasks';
		$id     = $root->get_id();
		$states = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT step.status FROM %i step
				WHERE (step.id = %d OR step.parent_id = %d) AND step.hook = %s
				AND (step.status NOT IN ('failed', 'canceled') OR NOT EXISTS (
					SELECT 1 FROM %i retry WHERE retry.parent_id = %d AND retry.status = 'complete'
					AND retry.hook = step.hook AND retry.retry_of = COALESCE(step.retry_of, step.id)
				))",
				$table,
				$id,
				$id,
				self::HOOK,
				$table,
				$id
			)
		);

		$priority = array_intersect( array( 'running', 'pending', 'manual', 'failed', 'canceled', 'complete' ), $states );
		$priority = reset( $priority );

		return $priority ? $priority : $root->get_status();
	}
}
