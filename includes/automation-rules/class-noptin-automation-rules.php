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

		// Register core triggers.
		$this->add_trigger( new Noptin_New_Subscriber_Trigger() );
		$this->add_trigger( new Noptin_Open_Email_Trigger() );
		$this->add_trigger( new Noptin_Link_Click_Trigger() );
		$this->add_trigger( new Noptin_Unsubscribe_Trigger() );
		$this->add_trigger( new Noptin_New_Comment_Trigger() );
		$this->add_trigger( new Noptin_Comment_Reply_Trigger() );

		if ( function_exists( 'geodir_get_posttypes' ) ) {
			foreach ( geodir_get_posttypes() as $post_type ) {
				$this->add_trigger( new Noptin_GeoDirectory_Listing_Saved_Trigger( $post_type ) );
				$this->add_action( new Noptin_GeoDirectory_Update_Listing_Action( $post_type ) );
			}
		}

		if ( defined( 'PMPRO_VERSION' ) ) {
			$this->add_trigger( new Noptin_PMPro_Membership_Level_Change_Trigger() );
			$this->add_action( new Noptin_PMPro_Change_Level_Action() );
		}

		// Handle admin rule CRUD requests.
		add_action( 'noptin_create_automation_rule', array( $this, 'admin_create_automation_rule' ) );
		add_action( 'noptin_delete_automation_rule', array( $this, 'admin_delete_automation_rule' ) );
		add_action( 'before_delete_post', array( $this, 'delete_automation_rule_on_campaign_delete' ), 10, 2 );
		add_action( 'noptin_deleted_automation_rule', array( $this, 'delete_campaign_on_automation_rule_delete' ) );
		do_action( 'noptin_automation_rules_load', $this );

		// Register automated email types.
		foreach ( $this->get_triggers() as $trigger ) {
			$email_type = 'automation_rule_' . $trigger->get_id();

			noptin()->emails->automated_email_types->register_automated_email_type(
				$email_type,
				new Noptin_Automation_Rule_Email( $email_type )
			);
		}
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
		$this->triggers[ $trigger->get_id() ] = $trigger;
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
	 * Prepares a rule.
	 *
	 * @since 1.2.8
	 * @param stdClass|int|Noptin_Automation_Rule $rule The (maybe) raw rule.
	 * @return Noptin_Automation_Rule The prepared rule.
	 */
	public function prepare_rule( $rule ) {
		return new Noptin_Automation_Rule( $rule );
	}

	/**
	 * Creates a new rule.
	 *
	 * @since 1.2.8
	 * @param array $rule The rule arguments.
	 * @return bool|Noptin_Automation_Rule
	 */
	public function create_rule( $rule ) {
		global $wpdb;

		// Ensure that we have an array.
		if ( ! is_array( $rule ) ) {
			$rule = array();
		}

		// Our database fields with defaults set.
		$fields = array(
			'action_id'        => '',
			'action_settings'  => array(),
			'trigger_id'       => '',
			'trigger_settings' => array(),
			'status'           => 1, // Active, 0 inactive, 2 automated email.
			'times_run'        => 0,
			'created_at'       => current_time( 'mysql' ),
			'updated_at'       => current_time( 'mysql' ),
		);

		foreach ( array_keys( $fields ) as $key ) {

			if ( isset( $rule[ $key ] ) ) {
				$fields[ $key ] = $rule[ $key ];
			}

			$fields[ $key ] = maybe_serialize( $fields[ $key ] );

		}

		if ( ! $wpdb->insert( $this->get_table(), $fields, '%s' ) ) {
			log_noptin_message( $wpdb->last_query );
			log_noptin_message( $wpdb->last_error );
			return false;
		}

		return new Noptin_Automation_Rule( $wpdb->insert_id );

	}

	/**
	 * Updates a rule.
	 *
	 * @since 1.2.8
	 * @param int|Noptin_Automation_Rule $rule The rule to update
	 * @param array $to_update The new $arguments.
	 * @return bool|Noptin_Automation_Rule
	 */
	public function update_rule( $rule, $to_update ) {
		global $wpdb;

		if ( ! is_a( $rule, 'Noptin_Automation_Rule' ) ) {
			$rule = new Noptin_Automation_Rule( $rule );
		}

		// Does the rule exist?
		if ( ! $rule->exists() ) {
			return false;
		}

		// Our database fields with defaults set.
		$fields = array(
			'action_id'        => $rule->action_id,
			'action_settings'  => $rule->action_settings,
			'trigger_id'       => $rule->trigger_id,
			'trigger_settings' => $rule->trigger_settings,
			'status'           => $rule->status, // Active, 0 inactive, 2 automated email.
			'times_run'        => $rule->times_run,
			'created_at'       => $rule->created_at,
		);

		foreach ( array_keys( $fields ) as $key ) {

			if ( isset( $to_update[ $key ] ) ) {
				$fields[ $key ] = $to_update[ $key ];
				$fields[ $key ] = maybe_serialize( $fields[ $key ] );
			} else {
				unset( $fields[ $key ] );
			}
		}

		$fields['updated_at'] = current_time( 'mysql' );

		if ( ! $wpdb->update( $this->get_table(), $fields, array( 'id' => $rule->id ) ) ) {
			return false;
		}

		wp_cache_delete( $rule->id, 'noptin_automation_rules' );
		return new Noptin_Automation_Rule( $rule->id );

	}

	/**
	 * Deletes a rule.
	 *
	 * @since 1.3.0
	 * @param int|Noptin_Automation_Rule $rule The rule to delete
	 * @return bool
	 */
	public function delete_rule( $rule ) {
		global $wpdb;

		$rule = new Noptin_Automation_Rule( $rule );

		// Does the rule exist?
		if ( ! $rule->exists() ) {
			return false;
		}

		wp_cache_delete( $rule->id, 'noptin_automation_rules' );

		$result = $wpdb->delete( $this->get_table(), array( 'id' => $rule->id ), '%d' );

		do_action( 'noptin_deleted_automation_rule', $rule );

		return $result;

	}

	/**
	 * Returns the rule's database table.
	 *
	 * @since 1.2.8
	 * @return string
	 */
	public function get_table() {
		global $wpdb;
		return $wpdb->prefix . 'noptin_automation_rules';
	}

	/**
	 * Saves a created rule
	 *
	 * @access      public
	 * @since       1.3.0
	 * @return      void
	 */
	public function admin_create_automation_rule() {

		if ( ! current_user_can( get_noptin_capability() ) || empty( $_POST['noptin-admin-create-automation-rule'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['noptin-admin-create-automation-rule'], 'noptin-admin-create-automation-rule' ) ) {
			return;
		}

		$post       = wp_unslash( $_POST );
		$action_id  = noptin_clean( $post['noptin-automation-rule-action'] );
		$trigger_id = noptin_clean( $post['noptin-automation-rule-trigger'] );

		if ( empty( $action_id ) || empty( $trigger_id ) ) {
			noptin()->admin->show_error( __( 'Select a trigger and action for your rule.', 'newsletter-optin-box' ) );
		}

		$rule = $this->create_rule( compact( 'action_id', 'trigger_id' ) );

		if ( ! empty( $rule ) ) {
			wp_safe_redirect( $rule->get_edit_url() );
			exit;
		}

		noptin()->admin->show_error( __( 'There was a problem creating your automation rule. Please try again.', 'newsletter-optin-box' ) );

	}

	/**
	 * Deletes a rule when a campaign is deleted.
	 *
	 * @var int $campaign_id The campaign id.
	 * @param WP_Post $post   Post object.
	 */
	public function delete_automation_rule_on_campaign_delete( $campaign_id, $post ) {

		if ( 'noptin-campaign' !== $post->post_type || 'automation' !== get_post_meta( $post->ID, 'campaign_type', true ) ) {
			return;
		}

		$campaign = new Noptin_Automated_Email( (int) $campaign_id );

		if ( ! $campaign->exists() || ! $campaign->is_automation_rule() || ! $campaign->get( 'automation_rule' ) ) {
			return;
		}

		$this->delete_rule( intval( $campaign->get( 'automation_rule' ) ) );

	}

	/**
	 * Deletes a campaign when a rule is deleted.
	 *
	 * @var Noptin_Automation_Rule $rule The rule.
	 */
	public function delete_campaign_on_automation_rule_delete( $rule ) {

		if ( 'email' !== $rule->action_id || empty( $rule->action_settings['automated_email_id'] ) ) {
			return;
		}

		wp_delete_post( $rule->action_settings['automated_email_id'], true );
	}

	/**
	 * Deletes a rule
	 *
	 * @access      public
	 * @since       1.3.0
	 * @return      void
	 */
	public function admin_delete_automation_rule() {

		if ( ! current_user_can( get_noptin_capability() ) || empty( $_GET['_wpnonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'noptin-automation-rule' ) ) {
			return;
		}

		$this->delete_rule( intval( $_GET['delete'] ) );

		noptin()->admin->show_info( __( 'The automation rule has been deleted.', 'newsletter-optin-box' ) );

		wp_safe_redirect(
			add_query_arg(
				array(
					'_wpnonce'            => false,
					'delete'              => false,
					'noptin_admin_action' => false,
				)
			)
		);
		exit;

	}

}
