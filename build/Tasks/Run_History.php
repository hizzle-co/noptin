<?php

namespace Hizzle\Noptin\Tasks;

defined( 'ABSPATH' ) || exit;

/**
 * Adds automation run status and filtering to the tasks collection.
 */
class Run_History {

	const HOOK = 'noptin_run_automation_rule';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_field' ) );
		add_filter( 'hizzle_rest_noptin_tasks_collection_params', array( __CLASS__, 'collection_params' ) );
		add_filter( 'noptin_tasks_pre_query', array( __CLASS__, 'filter_roots_by_task_status' ), 10, 2 );
	}

	public static function register_field() {
		register_rest_field(
			'noptin_tasks',
			'run_status',
			array(
				'get_callback' => static function ( $data, $field, $request ) {
					// Avoid aggregating run status for unrelated task consumers.
					if ( ! in_array( $field, wp_parse_list( $request['_fields'] ?? '' ), true ) || empty( $data['id'] ) ) {
						return null;
					}

					$root = Main::get( (int) $data['id'] );
					return $root->exists() && self::HOOK === $root->get_hook() && ! $root->get_parent_id()
						? self::get_status( $root )
						: null;
				},
				'schema'       => array(
					'description' => 'Combined status of a root automation task and its steps. Request explicitly via _fields.',
					'type'        => array( 'string', 'null' ),
					'readonly'    => true,
				),
			)
		);
	}

	public static function collection_params( $params ) {
		$params['run_task_status'] = array(
			'description' => 'Return root automation tasks with this status on the root or any of its steps.',
			'type'        => 'string',
			'enum'        => array_keys( Main::get_statuses() ),
		);
		return $params;
	}

	public static function filter_roots_by_task_status( $results, $query ) {
		global $wpdb;

		$status = $query->get( 'run_task_status' );
		if ( null !== $results || ! is_string( $status ) || ! array_key_exists( $status, Main::get_statuses() ) ) {
			return $results;
		}

		$table = $wpdb->prefix . 'noptin_tasks';
		$query->query_where .= $wpdb->prepare(
			' AND %i.hook = %s AND %i.parent_id = 0
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

		$table = $wpdb->prefix . 'noptin_tasks';
		$id    = $root->get_id();
		$states = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT step.status FROM %i step
				WHERE (step.id = %d OR step.parent_id = %d) AND step.hook = %s
				AND (step.status NOT IN ('failed', 'canceled') OR NOT EXISTS (
					SELECT 1 FROM %i retry WHERE retry.parent_id = %d AND retry.status = 'complete'
					AND retry.hook = step.hook AND retry.retry_of = IF(step.retry_of = 0, step.id, step.retry_of)
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
		return reset( $priority ) ?: $root->get_status();
	}

}
