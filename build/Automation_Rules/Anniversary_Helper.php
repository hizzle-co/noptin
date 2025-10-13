<?php

/**
 * Helper for dealing with anniversaries.
 *
 * @version 1.0.0
 */

namespace Hizzle\Noptin\Automation_Rules;

defined( 'ABSPATH' ) || exit;

/**
 * Helper for dealing with anniversaries.
 */
class Anniversary_Helper {

	/**
	 * @var string[] Triggers.
	 */
	private static $triggers = array();

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function init() {
		add_action( 'noptin_daily_maintenance', array( __CLASS__, 'daily_maintenance' ) );
		add_action( 'noptin_anniversary_background_hook', array( __CLASS__, 'run_background_hook' ) );
	}

	/**
	 * Registers a trigger.
	 *
	 * @param string $trigger Trigger name.
	 */
	public static function register_trigger( $trigger ) {
		if ( ! in_array( $trigger, self::$triggers, true ) ) {
			self::$triggers[] = $trigger;
		}
	}

	/**
	 * Checks if a trigger is registered.
	 *
	 * @param string $trigger Trigger name.
	 * @return bool
	 */
	public static function has_trigger( $trigger ) {
		return in_array( $trigger, self::$triggers, true );
	}

	/**
	 * Performs daily maintenance.
	 */
	public static function daily_maintenance() {
		// Abort if no triggers are registered.
		if ( empty( self::$triggers ) ) {
			return;
		}

		// Fetch automation rules.
		$rules = noptin_get_automation_rules(
			array(
				'trigger_id' => self::$triggers,
				'status'     => true,
				'fields'     => array( 'id' ),
			)
		);

		// Schedule background actions for each rule.
		foreach ( $rules as $index => $rule ) {
			schedule_noptin_background_action(
				time() + ( ( $index + 1 ) * MINUTE_IN_SECONDS ),
				'noptin_anniversary_background_hook',
				$rule
			);
		}
	}

	/**
	 * Checks if today is the user's anniversary.
	 *
	 * @param int $rule_id The rule ID.
	 */
	public static function run_background_hook( $rule_id ) {
		$rule = noptin_get_automation_rule( $rule_id );

		// Abort if the rule is not found.
		if ( is_wp_error( $rule ) || ! $rule->exists() || ! $rule->get_status() ) {
			throw new \Exception( 'Invalid rule ID: ' . esc_html( $rule_id ) );
		}

		if ( 'noptin_anniversary' !== $rule->get_trigger_id() ) {
			do_action( 'check_noptin_anniversary', $rule );
		}

		do_action( 'check_' . $rule->get_trigger_id(), $rule );
	}
}
