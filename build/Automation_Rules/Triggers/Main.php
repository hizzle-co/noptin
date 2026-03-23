<?php

/**
 * Main triggers class.
 *
 * @since   3.1.0
 * @package Noptin
 */

namespace Hizzle\Noptin\Automation_Rules\Triggers;

defined( 'ABSPATH' ) || exit;

/**
 * Main triggers class.
 */
class Main {

	/**
	 * @var Trigger[] List of all registered triggers.
	 */
	public static $triggers = array();

	/**
	 * Registers a trigger.
	 *
	 * @param Trigger $trigger The trigger to register.
	 */
	public static function add( $trigger ) {
		if ( isset( self::$triggers[ $trigger->get_id() ] ) ) {
			return _doing_it_wrong( __METHOD__, 'Trigger with id ' . esc_html( $trigger->get_id() ) . ' already exists', '3.0.0' );
		}

		self::$triggers[ $trigger->get_id() ] = $trigger;

		if ( empty( noptin()->emails->automated_email_types ) ) {
			return _doing_it_wrong( __METHOD__, 'Register triggers after noptin_email_manager_init action', '3.0.0' );
		}

		// Register email type.
		if ( class_exists( 'Noptin_Automated_Email_Type' ) ) {
			$email_type = 'automation_rule_' . $trigger->get_id();

			noptin()->emails->automated_email_types->register_automated_email_type(
				$email_type,
				new Email_Type( $email_type, $trigger )
			);
		}
	}

	/**
	 * Retrieves a registered trigger.
	 *
	 * @param string $trigger_id The trigger's unique id.
	 * @return Trigger|null
	 */
	public static function get( $trigger_id ) {
		if ( ! is_scalar( $trigger_id ) || empty( $trigger_id ) ) {
			return null;
		}

		if ( isset( self::$triggers[ $trigger_id ] ) ) {
			return self::$triggers[ $trigger_id ];
		}

		foreach ( self::$triggers as $trigger ) {
			if ( isset( $trigger->alias ) && $trigger->alias === $trigger_id ) {
				return $trigger;
			}
		}

		return null;
	}

	/**
	 * Returns all registered triggers.
	 * @return Trigger[]
	 */
	public static function all() {
		return self::$triggers;
	}

	/**
	 * Checks if a trigger is registered.
	 */
	public static function exists( $trigger_id ) {
		return self::get( $trigger_id ) !== null;
	}
}
