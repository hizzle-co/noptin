<?php

namespace Hizzle\Noptin\Objects;

/**
 * Collection of records.
 *
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Base object type.
 */
abstract class Collection {

	/**
	 * @var string the record class.
	 */
	public $record_class = '\Hizzle\Noptin\Objects\Record';

	/**
	 * @var string object type.
	 */
	public $object_type;

	/**
	 * @var string type.
	 */
	public $type;

	/**
	 * @var string prefix.
	 */
	public $smart_tags_prefix = null;

	/**
	 * @var string label.
	 */
	public $label;

	/**
	 * @var string label.
	 */
	public $singular_label;

	/**
	 * @var string integration.
	 */
	public $integration;

	/**
	 * Class constructor.
	 */
	public function __construct() {

		// Load automation rule.
		if ( did_action( 'noptin_automation_rules_load' ) ) {
			$this->load_automation_rules( noptin()->automation_rules );
		} else {
			add_action( 'noptin_automation_rules_load', array( $this, 'load_automation_rules' ) );
		}

		if ( is_null( $this->smart_tags_prefix ) ) {
			$this->smart_tags_prefix = $this->type;
		}
	}

	/**
	 * Loads the automation rule triggers and actions.
	 *
	 * @param \Noptin_Automation_Rules $rules The automation rules instance.
	 */
	public function load_automation_rules( $rules ) {

		// Register triggers.
		foreach ( $this->get_triggers() as $key => $args ) {
			$rules->add_trigger(
				new Trigger( $key, $args, $this )
			);
		}

		// Register actions.
		foreach ( $this->get_actions() as $key => $args ) {
			$rules->add_action(
				new Action( $this->type, $key, $args )
			);
		}

	}

	/**
	 * Returns a list of available triggers.
	 *
	 * @return array $triggers The triggers.
	 */
	public function get_triggers() {
		return array();
	}

	/**
	 * Triggers actions.
	 *
	 * @param string $trigger The trigger name.
	 * @param array $args The trigger args.
	 */
	public function trigger( $trigger, $args ) {
		noptin_error_log( $args, $trigger );
		do_action( 'noptin_fire_object_trigger_' . $trigger, $args );
	}

	/**
	 * Returns a list of available actions.
	 *
	 * @return array $actions The actions.
	 */
	public function get_actions() {
		return array();
	}

	/**
	 * Retrieves available fields.
	 *
	 */
	abstract public function get_fields();

	/**
	 * Retrieves a single record.
	 *
	 * @param mixed $record The record.
	 * @return Record $record The record.
	 */
	public function get( $record ) {
		$class = $this->record_class;

		return new $class( $record );
	}
}
