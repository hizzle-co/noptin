<?php

namespace Hizzle\Noptin\Integrations;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Base Automation integration
 *
 * @since 2.0.0
 */
abstract class Automation_Integration {

	/**
	 * @var int The priority for hooks.
	 * @since 2.0.0
	 */
	public $priority = 60;

	/**
	 * Constructor
	 */
	public function __construct() {

		// Load automation rule.
		if ( did_action( 'noptin_automation_rules_load' ) ) {
			$this->load_automation_rules( noptin()->automation_rules );
		} else {
			add_action( 'noptin_automation_rules_load', array( $this, 'load_automation_rules' ), $this->priority );
		}
	}

	/**
	 * Loads the automation rule triggers and actions.
	 *
	 * @param \Noptin_Automation_Rules $rules The automation rules instance.
	 */
	public function load_automation_rules( $rules ) {

		// Register triggers.
		$this->register_triggers( $rules );

		// Register actions.
		$this->register_actions( $rules );
	}

	/**
	 * Registers our automation rule triggers.
	 *
	 * @param \Noptin_Automation_Rules $rules The automation rules instance.
	 */
	protected function register_triggers( $rules ) {}

	/**
	 * Registers our automation rule actions.
	 *
	 * @param \Noptin_Automation_Rules $rules The automation rules instance.
	 */
	protected function register_actions( $rules ) {}
}
