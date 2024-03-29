<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The automation rules class.
 *
 * @since       1.2.8
 */
class Noptin_Automation_Rules {

	/**
	 * @var Noptin_Abstract_Action[] $actions All registered actions.
	 */
	public $actions = array();

	/**
	 * @var Noptin_Abstract_Trigger[] $triggers All registered triggers.
	 */
	public $triggers = array();

	/**
	 * Constructor.
	 *
	 * @since 1.2.8
	 * @return string
	 */
	public function __construct() {

		// Register core actions.
		$this->add_action( new Noptin_Custom_Field_Action() );
		$this->add_action( new Noptin_Email_Action() );
		$this->add_action( new Noptin_Subscribe_Action() );
		$this->add_action( new Noptin_Unsubscribe_Action() );
		$this->add_action( new Noptin_Delete_Subscriber_Action() );

		// Register core triggers.
		$this->add_trigger( new Noptin_New_Comment_Trigger() );
		$this->add_trigger( new Noptin_Comment_Reply_Trigger() );

		// Handle admin rule CRUD requests.
		do_action( 'noptin_automation_rules_load', $this );

		if ( function_exists( 'geodir_get_posttypes' ) ) {
			foreach ( geodir_get_posttypes() as $post_type ) {
				$this->add_trigger( new Noptin_GeoDirectory_Listing_Saved_Trigger( $post_type ) );
				$this->add_trigger( new Noptin_GeoDirectory_Listing_Published_Trigger( $post_type ) );
				$this->add_action( new Noptin_GeoDirectory_Update_Listing_Action( $post_type ) );

				if ( defined( 'GEODIR_PRICING_VERSION' ) ) {
					$this->add_trigger( new Noptin_GeoDirectory_Listing_Downgraded_Trigger( $post_type ) );
					$this->add_trigger( new Noptin_GeoDirectory_Listing_Expire_Trigger( $post_type ) );
				}
			}
		}

		// Maybe migrate automation rules.
		add_action( 'noptin_run_delayed_automation_rule', array( $this, 'run_delayed_automation_rule' ), 10, 2 );
	}

	/**
	 * Registers an action.
	 *
	 * @since 1.2.8
	 * @param Noptin_Abstract_Action $action An ancestor of Noptin_Abstract_Action
	 */
	public function add_action( $action ) {
		$this->actions[ $action->get_id() ] = $action;
	}

	/**
	 * Checks if there is an action with that id.
	 *
	 * @since 1.2.8
	 * @param string $action_id The action's uniques id.
	 * @return bool whether or not the action exists.
	 */
	public function has_action( $action_id ) {
		return is_scalar( $action_id ) && ! empty( $this->actions[ $action_id ] );
	}

	/**
	 * Retrieves a registered action.
	 *
	 * @since 1.2.8
	 * @param string $action_id The action's uniques id.
	 * @return Noptin_Abstract_Action|null
	 */
	public function get_action( $action_id ) {
		return empty( $this->actions[ $action_id ] ) ? null : $this->actions[ $action_id ];
	}

	/**
	 * Returns all registered actions.
	 *
	 * @since 1.2.8
	 * @return Noptin_Abstract_Action[]
	 */
	public function get_actions() {
		return $this->actions;
	}

	/**
	 * Registers a trigger.
	 *
	 * @since 1.2.8
	 * @param Noptin_Abstract_Trigger $trigger An ancestor of Noptin_Abstract_Trigger
	 */
	public function add_trigger( $trigger ) {

		if ( isset( $this->triggers[ $trigger->get_id() ] ) ) {
			return _doing_it_wrong( __METHOD__, 'Trigger with id ' . esc_html( $trigger->get_id() ) . ' already exists', '3.0.0' );
		}

		$this->triggers[ $trigger->get_id() ] = $trigger;

		if ( empty( noptin()->emails->automated_email_types ) ) {
			return _doing_it_wrong( __METHOD__, 'Noptin_Automation_Rules::add_trigger should be called after noptin_email_manager_init action', '3.0.0' );
		}

		// Register email type.
		$email_type = 'automation_rule_' . $trigger->get_id();

		noptin()->emails->automated_email_types->register_automated_email_type(
			$email_type,
			new Noptin_Automation_Rule_Email( $email_type, $trigger )
		);
	}

	/**
	 * Retrieves a registered trigger.
	 *
	 * @since 1.2.8
	 * @param string $trigger_id The trigger's uniques id.
	 * @return Noptin_Abstract_Trigger|null
	 */
	public function get_trigger( $trigger_id ) {
		return empty( $this->triggers[ $trigger_id ] ) ? null : $this->triggers[ $trigger_id ];
	}

	/**
	 * Returns all registered triggers.
	 *
	 * @since 1.2.8
	 * @return Noptin_Abstract_Trigger[]
	 */
	public function get_triggers() {
		return $this->triggers;
	}

	/**
	 * Checks if there is a trigger with that id.
	 *
	 * @since 1.2.8
	 * @param string $trigger_id The trigger's unique id.
	 * @return bool whether or not the trigger exists.
	 */
	public function has_trigger( $trigger_id ) {
		return is_scalar( $trigger_id ) && ! empty( $this->triggers[ $trigger_id ] );
	}

	/**
	 * Runs a delayed automation rule.
	 *
	 * @param string $automation_rule The rule id to schedule.
	 * @param array $args The trigger arguments.
	 */
	public function run_delayed_automation_rule( $rule_id, $args ) {

		$rule = noptin_get_automation_rule( $rule_id );

		if ( is_wp_error( $rule ) || ! $rule->exists() ) {
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
			$action->maybe_run( $args['subject'], $rule, $args );
		} else {
			throw new \Exception( 'Automation rule is no longer valid for the provided arguments' );
		}
	}
}
