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
	private $actions = array();

	/**
	 * @var Noptin_Abstract_Trigger[] $triggers All registered triggers.
	 */
	private $triggers = array();

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
		$this->add_trigger( new Noptin_New_Subscriber_Trigger() );
		$this->add_trigger( new Noptin_Open_Email_Trigger() );
		$this->add_trigger( new Noptin_Link_Click_Trigger() );
		$this->add_trigger( new Noptin_Unsubscribe_Trigger() );
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

		if ( defined( 'PMPRO_VERSION' ) ) {
			$this->add_trigger( new Noptin_PMPro_Membership_Level_Change_Trigger() );
			$this->add_action( new Noptin_PMPro_Change_Level_Action() );
		}

		// Maybe migrate automation rules.
		add_action( 'admin_init', array( $this, 'migrate_automation_rule_triggers' ) );
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
	 * Migrates automation rule triggers.
	 *
	 * @since 3.0.0
	 */
	public function migrate_automation_rule_triggers() {
		$migrators = apply_filters( 'noptin_automation_rule_migrate_triggers', array(), $this );

		// Nothing to migrate.
		if ( empty( $migrators ) ) {
			return;
		}

		$migrated = (array) get_option( 'noptin_automation_rule_migrated_triggers', array() );

		foreach ( $migrators as $migrator ) {

			if ( empty( $migrator['id'] ) || in_array( $migrator['id'], $migrated, true ) ) {
				continue;
			}

			$rules = noptin_get_automation_rules(
				array(
					'trigger_id' => $migrator['trigger'],
				)
			);

			foreach ( $rules as $rule ) {
				call_user_func_array( $migrator['callback'], array( &$rule ) );
				$rule->save();
			}

			$migrated[] = $migrator['id'];
		}

		update_option( 'noptin_automation_rule_migrated_triggers', $migrated );
	}
}
