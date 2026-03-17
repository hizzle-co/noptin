<?php

use Hizzle\Noptin\Automation_Rules\Actions\Main as Actions;
use Hizzle\Noptin\Automation_Rules\Triggers\Main as Triggers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The automation rules class.
 *
 * @since       1.2.8
 */
class Noptin_Automation_Rules {

	/**
	 * Constructor.
	 *
	 * @since 1.2.8
	 */
	public function __construct() {

		// Register core triggers.
		Triggers::add( new Noptin_New_Comment_Trigger() );
		Triggers::add( new Noptin_Comment_Reply_Trigger() );

		// Handle admin rule CRUD requests.
		do_action( 'noptin_automation_rules_load', $this );
	}

	/**
	 * Registers an action.
	 *
	 * @since 1.2.8
	 */
	public function add_action( $action ) {
		Actions::add( $action );
	}

	/**
	 * Retrieves a registered action.
	 *
	 * @since 1.2.8
	 */
	public function get_action( $action_id ) {
		return Actions::get( $action_id );
	}

	/**
	 * Returns all registered actions.
	 *
	 * @since 1.2.8
	 */
	public function get_actions() {
		return Actions::all();
	}

	/**
	 * Registers a trigger.
	 *
	 * @since 1.2.8
	 */
	public function add_trigger( $trigger ) {
		Triggers::add( $trigger );
	}

	/**
	 * Retrieves a registered trigger.
	 *
	 * @since 1.2.8
	 * @param string $trigger_id The trigger's uniques id.
	 */
	public function get_trigger( $trigger_id ) {
		return Triggers::get( $trigger_id );
	}

	/**
	 * Returns all registered triggers.
	 *
	 * @since 1.2.8
	 */
	public function get_triggers() {
		return Triggers::all();
	}
}
