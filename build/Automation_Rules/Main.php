<?php

/**
 * Main automation rules class.
 *
 * @since   3.1.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Automation_Rules;

defined( 'ABSPATH' ) || exit;

/**
 * Main automation rules class.
 */
class Main {

	/**
	 * Inits the main emails class.
	 *
	 */
	public static function init() {

		if ( is_admin() ) {
			Admin\Main::init();
		}

		add_action( 'noptin_run_automation_rule', array( __CLASS__, 'handle_automation_rule_task' ), 10, 2 );
		add_action( 'noptin_run_delayed_automation_rule', array( __CLASS__, 'run_delayed_automation_rule' ), 10, 2 );
	}

	/**
	 * Runs an automation rule if the Noptin tasks package is installed.
	 *
	 * @param int $rule_id
	 * @return Automation_Rule
	 * @throws \Exception
	 */
	public static function run_automation_rule( $rule_id, $args ) {
		$rule = noptin_get_automation_rule( $rule_id );

		if ( is_wp_error( $rule ) ) {
			throw new \Exception( esc_html( $rule->get_error_message() ) );
		}

		if ( ! $rule->exists() ) {
			throw new \Exception( 'Automation rule not found' );
		}

		// Fetch the trigger.
		$trigger = $rule->get_trigger();

		if ( empty( $trigger ) ) {
			throw new \Exception( 'Invalid or unregistered trigger' );
		}

		// Fetch the action.
		$action = $rule->get_action();

		if ( empty( $action ) ) {
			throw new \Exception( 'Invalid or unregistered action' );
		}

		// Unserialize the trigger arguments.
		$args = $trigger->unserialize_trigger_args( $args );

		// Abort if the trigger does not support scheduling.
		if ( ! is_array( $args ) ) {
			throw new \Exception( 'Trigger does not support scheduling' );
		}

		// Ensure that the rule is valid for the provided args.
		if ( $trigger->is_rule_valid_for_args( $rule, $args, $args['subject'], $action ) ) {
			if ( false === $action->maybe_run( $args['subject'], $rule, $args ) ) {
				throw new \Exception( 'Failed to run automation rule' );
			}
		} else {
			throw new \Exception( 'Automation rule is no longer valid for the provided arguments' );
		}
	}

	/**
	 * Runs an automation rule if the Noptin tasks package is installed.
	 *
	 * @param \Noptin\Addons_Pack\Tasks\Task $task
	 * @param array $args The trigger arguments.
	 */
	public static function handle_automation_rule_task( $task, $args ) {
		self::run_automation_rule( $task->get_primary_id(), $args );
	}

	/**
	 * Runs a delayed automation rule.
	 *
	 * @param string $automation_rule The rule id to schedule.
	 * @param array $args The trigger arguments.
	 */
	public static function run_delayed_automation_rule( $rule_id, $args ) {
		self::run_automation_rule( $rule_id, $args );
	}
}
