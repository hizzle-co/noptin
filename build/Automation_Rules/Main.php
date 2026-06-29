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

		// Admin.
		if ( is_admin() ) {
			Admin\Main::init();
		}

		// Schema.
		Schema::init();

		// Anniversary helper.
		Anniversary_Helper::init();

		// Hooks.
		add_action( 'noptin_run_automation_rule', array( __CLASS__, 'handle_automation_rule_task' ), 10, 2 );
		add_action( 'noptin_run_delayed_automation_rule', array( __CLASS__, 'run_delayed_automation_rule' ), 10, 2 );

		// Init default actions.
		Actions\Main::init();

		// Init default triggers.
		add_action( 'noptin_email_manager_init', array( Triggers\Main::class, 'init' ) );
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

		$run_next  = true;
		$to_return = true;
		try {
			$to_return = self::handle_run_automation_rule( $rule, $args );

			// Returns false if conditional logic fails.
			if ( false === $to_return ) {
				$run_next = false;
				throw new \Exception( 'Automation rule is no longer valid for the provided arguments' );
			}
		} catch ( \Exception $e ) {
			log_noptin_message( 'Error running automation rule ID ' . $rule_id . ': ' . $e->getMessage(), 'error' );

			// Stop running child rules if the rule has stop_on_failure set.
			if ( $run_next && $rule->get_action_setting( '_noptin_stop_on_failure' ) ) {
				$run_next = false;
			}

			$to_return = $e;
		}

		// If we're running child rules, schedule now.
		$action       = $rule->get_action();
		$can_run_next = $action ? $action->should_auto_run_child_rules() : true;
		if ( $run_next && apply_filters( 'noptin_can_run_child_rules', $can_run_next, $rule, $args ) ) {
			foreach ( $rule->get_children() as $child_rule ) {
				if ( $child_rule->get_status() ) {
					try {
						$child_rule->maybe_run(
							$args['subject'] ?? '',
							$rule->get_trigger(),
							$child_rule->get_action(),
							$args
						);
					} catch ( \Exception $e ) {
						log_noptin_message( 'Error running child rules for automation rule ID ' . $rule_id . ': ' . $e->getMessage(), 'error' );
					}
				}
			}
		}

		// Rethrow the exception if there was one.
		if ( $to_return instanceof \Exception ) {
			throw $to_return;
		}

		return $to_return;
	}

	/**
	 * Does the actual running of the automation rule.
	 *
	 * @param Automation_Rule $rule
	 * @param array $args
	 * @return bool
	 * @throws \Exception
	 */
	private static function handle_run_automation_rule( $rule, $args ) {

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
		if ( $trigger->is_rule_valid_for_args( $rule, $args, $args['subject'] ?? '', $action ) ) {
			$result = $action->maybe_run( $args['subject'] ?? '', $rule, $args );

			if ( false === $result ) {
				throw new \Exception( 'Failed to run automation rule' );
			}

			if ( is_wp_error( $result ) ) {
				throw new \Exception( esc_html( $result->get_error_message() ) );
			}

			return true;
		}

		// We want to return false here so that the runner does not attempt
		// to auto-run child rules for a rule that is no longer valid for the provided arguments.
		return false;
	}

	/**
	 * Runs an automation rule if the Noptin tasks package is installed.
	 *
	 * @param \Hizzle\Noptin\Tasks\Task $task
	 * @param array $args The trigger arguments.
	 */
	public static function handle_automation_rule_task( $task, $args ) {
		self::run_automation_rule( $task->get_primary_id(), $args );
	}

	/**
	 * Runs a delayed automation rule.
	 *
	 * @param string $rule_id The rule id to schedule.
	 * @param array $args The trigger arguments.
	 */
	public static function run_delayed_automation_rule( $rule_id, $args ) {
		self::run_automation_rule( $rule_id, $args );
	}
}
