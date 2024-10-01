<?php

/**
 * Container for a single automation rule.
 *
 * @version 1.0.0
 */

namespace Hizzle\Noptin\Automation_Rules;

defined( 'ABSPATH' ) || exit;

/**
 * Automation Rule.
 */
class Automation_Rule extends \Hizzle\Store\Record {

	/**
	 * Are we creating a new rule?
	 * @var bool
	 * @since 1.12.0
	 */
	public $is_creating = false;

	/**
	 * @inheritdoc
	 */
	public function __construct( $record = 0, $args = array() ) {

		parent::__construct( $record, $args );

		// Check if we are creating a new rule.
		if ( empty( $record ) && ! empty( $_GET['noptin-trigger'] ) && ! empty( $_GET['noptin-action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$this->is_creating = true;

			$this->set_trigger_id( sanitize_text_field( $_GET['noptin-trigger'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$this->set_action_id( sanitize_text_field( $_GET['noptin-action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			// Set default action and trigger settings.
			$this->set_trigger_settings( array( 'conditional_logic' => noptin_get_default_conditional_logic() ) );
			$this->set_action_settings( array() );
		}
	}

	/**
	 * Returns the deprecated rule object.
	 *
	 * @return \Noptin_Automation_Rule
	 */
	public function get_deprecated_rule() {
		return new \Noptin_Automation_Rule( $this->get_id() );
	}

	/**
	 * Gets the action id.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_action_id( $context = 'view' ) {
		return $this->get_prop( 'action_id', $context );
	}

	/**
	 * Sets the action id.
	 *
	 * @param string $value Action id.
	 */
	public function set_action_id( $value ) {
		$this->set_prop( 'action_id', sanitize_text_field( $value ) );
		$this->sanitize_action_settings();
	}

	/**
	 * Gets the action.
	 *
	 * @return false|\Noptin_Abstract_Action
	 */
	public function get_action() {
		return noptin()->automation_rules->get_action( $this->get_action_id() );
	}

	/**
	 * Returns a single action setting.
	 *
	 * @param string $key The setting key.
	 * @return mixed|null
	 */
	public function get_action_setting( $key ) {

		$settings = $this->get_action_settings();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
	}

	/**
	 * Returns the action settings.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return array
	 */
	public function get_action_settings( $context = 'view' ) {
		$value = $this->get_prop( 'action_settings', $context );
		return is_array( $value ) ? $value : array();
	}

	/**
	 * Sets the action settings.
	 *
	 * @param string|array $value Action settings.
	 */
	public function set_action_settings( $value ) {
		$value = empty( $value ) ? array() : maybe_unserialize( $value );
		$value = is_array( $value ) ? $value : array();
		$this->set_prop( 'action_settings', $value );
		$this->sanitize_action_settings();
	}

	/**
	 * Gets the trigger id.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_trigger_id( $context = 'view' ) {
		return $this->get_prop( 'trigger_id', $context );
	}

	/**
	 * Sets the trigger id.
	 *
	 * @param string $value Trigger id.
	 */
	public function set_trigger_id( $value ) {
		$this->set_prop( 'trigger_id', sanitize_text_field( $value ) );
		$this->sanitize_trigger_settings();
	}

	/**
	 * Gets the trigger.
	 *
	 * @return false|\Noptin_Abstract_Trigger
	 */
	public function get_trigger() {
		return noptin()->automation_rules->get_trigger( $this->get_trigger_id() );
	}

	/**
	 * Returns a single trigger setting.
	 *
	 * @param string $key The setting key.
	 * @return mixed|null
	 */
	public function get_trigger_setting( $key ) {

		$settings = $this->get_trigger_settings();
		return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
	}

	/**
	 * Returns the trigger settings.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return array
	 */
	public function get_trigger_settings( $context = 'view' ) {
		$value = $this->get_prop( 'trigger_settings', $context );
		return is_array( $value ) ? $value : array();
	}

	/**
	 * Sets the trigger settings.
	 *
	 * @param string|array $value Trigger settings.
	 */
	public function set_trigger_settings( $value ) {
		$value = empty( $value ) ? array() : maybe_unserialize( $value );
		$value = is_array( $value ) ? $value : array();
		$this->set_prop( 'trigger_settings', $value );
		$this->sanitize_trigger_settings();
	}

	/**
	 * Returns the conditional logic settings.
	 *
	 * @return array
	 */
	public function get_conditional_logic() {
		$conditional_logic = $this->get_trigger_setting( 'conditional_logic' );

		return is_array( $conditional_logic ) ? $conditional_logic : array();
	}

	/**
	 * Add conditional logic rules.
	 *
	 * @param array $rules The rules.
	 */
	public function add_conditional_logic_rules( $rules, $delete_settings = array(), $overwrite = array() ) {
		$conditional_logic = array_merge( noptin_get_default_conditional_logic(), $this->get_conditional_logic() );
		$existing_rules    = $conditional_logic['enabled'] ? $conditional_logic['rules'] : array();

		$conditional_logic['rules']   = array_merge( $existing_rules, $rules );
		$conditional_logic['enabled'] = ! empty( $conditional_logic['rules'] );

		$new_settings = $this->get_trigger_settings();

		if ( ! empty( $delete_settings ) ) {
			foreach ( $delete_settings as $setting ) {
				unset( $new_settings[ $setting ] );
			}
		}

		if ( is_array( $overwrite ) ) {
			$conditional_logic = array_merge( $conditional_logic, $overwrite );
		}

		$new_settings['conditional_logic'] = $conditional_logic;
		$this->set_trigger_settings( $new_settings );
	}

	/**
	 * Returns the rule status.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return bool
	 */
	public function get_status( $context = 'view' ) {
		return $this->get_prop( 'status', $context );
	}

	/**
	 * Sets the rule status.
	 *
	 * @param bool|int $value Rule status.
	 */
	public function set_status( $value ) {
		$this->set_prop( 'status', ! empty( $value ) );
	}

	/**
	 * Returns the number of seconds to wait before executing the rule.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int|null
	 */
	public function get_delay( $context = 'view' ) {
		return $this->get_prop( 'delay', $context );
	}

	/**
	 * Sets the number of seconds to wait before executing the rule.
	 *
	 * @param int $value Delay in seconds.
	 */
	public function set_delay( $value ) {
		$value = empty( $value ) ? null : absint( $value );
		$this->set_prop( 'delay', $value );
	}

	/**
	 * Returns the number of times this rule has been run.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return int
	 */
	public function get_times_run( $context = 'view' ) {
		return $this->get_prop( 'times_run', $context );
	}

	/**
	 * Sets the number of times this rule has been run.
	 *
	 * @param int $value Delay in seconds.
	 */
	public function set_times_run( $value ) {
		$value = empty( $value ) ? 0 : absint( $value );
		$this->set_prop( 'times_run', $value );
	}

	/**
	 * Get the date this rule was created.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return null|\Hizzle\Store\Date_Time
	 */
	public function get_created_at( $context = 'view' ) {
		return $this->get_prop( 'created_at', $context );
	}

	/**
	 * Set the date this rule was created.
	 *
	 * @param string|int|null|\Hizzle\Store\Date_Time $created_at Date this rule was created.
	 */
	public function set_created_at( $created_at ) {
		$this->set_date_prop( 'created_at', $created_at );
	}

	/**
	 * Get the date this rule was last modified.
	 *
	 * @param string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return null|\Hizzle\Store\Date_Time
	 */
	public function get_updated_at( $context = 'view' ) {
		return $this->get_prop( 'updated_at', $context );
	}

	/**
	 * Set the date this rule was last modified.
	 *
	 * @param string|int|null|\Hizzle\Store\Date_Time $updated_at Date this rule was last modified.
	 */
	public function set_updated_at( $updated_at ) {
		$this->set_date_prop( 'updated_at', $updated_at );
	}

	/**
	 * Saves the rule.
	 *
	 * @return int|\WP_Error
	 */
	public function save() {
		$this->set_updated_at( time() );

		if ( ! $this->get_id() ) {
			$this->set_created_at( time() );
		}

		return parent::save();
	}

	/**
	 * Deletes the rule.
	 *
	 * @return int|\WP_Error
	 */
	public function delete( $fire_actions = true ) {
		$action = $this->get_action();

		if ( ! empty( $action ) && $fire_actions ) {
			$action->before_delete( $this );
		}

		return parent::delete();
	}

	/**
	 * Fetches the rule's edit url.
	 *
	 * @return string
	 */
	public function get_edit_url() {

		$edit_url = add_query_arg(
			array(
				'page'                        => 'noptin-automation-rules',
				'noptin_edit_automation_rule' => $this->get_id(),
			),
			admin_url( 'admin.php' )
		);

		$email = $this->get_email_campaign();
		if ( $email ) {
			$edit_url = $email->get_edit_url();
		}

		return apply_filters( 'noptin_automation_rule_edit_url', $edit_url, $this );
	}

	/**
	 * Returns the related email campaign.
	 *
	 * @return false|\Hizzle\Noptin\Emails\Email
	 */
	public function get_email_campaign() {

		if ( 'email' !== $this->get_action_id() ) {
			return false;
		}

		$settings = $this->get_action_settings();

		if ( empty( $settings['automated_email_id'] ) ) {
			return false;
		}

		$email = noptin_get_email_campaign_object( $settings['automated_email_id'] );

		return $email->exists() ? $email : false;
	}

	/**
	 * Sanitize the trigger settings.
	 *
	 */
	private function sanitize_trigger_settings() {

		// Fetch the trigger.
		$trigger = $this->get_trigger();

		if ( empty( $trigger ) ) {
			return;
		}

		$trigger_settings = apply_filters( 'noptin_automation_rule_trigger_settings_' . $trigger->get_id(), $trigger->get_settings(), $this, $trigger );
		$trigger_settings = apply_filters( 'noptin_automation_rule_trigger_settings', $trigger_settings, $this, $trigger );
		$settings         = $this->prepare_settings( $this->get_trigger_settings(), $trigger_settings );

		// Set the sanitized settings.
		$this->set_prop( 'trigger_settings', $settings );
	}

	/**
	 * Sanitize the action settings.
	 *
	 */
	private function sanitize_action_settings() {

		// Fetch the trigger.
		$action = $this->get_action();

		if ( empty( $action ) ) {
			return;
		}

		$action_settings = apply_filters( 'noptin_automation_rule_action_settings_' . $action->get_id(), $action->get_settings(), $this, $action );
		$action_settings = apply_filters( 'noptin_automation_rule_action_settings', $action_settings, $this, $action );
		$settings        = $this->prepare_settings( $this->get_action_settings(), $action_settings );

		// Set the sanitized settings.
		$this->set_prop( 'action_settings', $settings );
	}

	/**
	 * Prepares settings.
	 *
	 * @param array $options  The saved options.
	 * @param array $settings The known settings.
	 * @return array
	 */
	private function prepare_settings( $options, $settings ) {

		// Prepare the options.
		$prepared_options = array();

		foreach ( $settings as $key => $args ) {

			$default  = isset( $args['default'] ) ? $args['default'] : '';
			$is_array = is_array( $default );
			$value    = isset( $options[ $key ] ) ? $options[ $key ] : $default;

			if ( $is_array && ! is_array( $value ) ) {
				$value = noptin_parse_list( $value, true );
			}

			// If there are options, make sure the value is one of them.
			if ( ! empty( $args['options'] ) ) {
				$choices = array_keys( $args['options'] );

				if ( is_array( $value ) ) {
					$value = array_values( array_intersect( $value, $choices ) );
				} else {
					$value = in_array( $value, $choices ) ? $value : $default; // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				}
			}

			$prepared_options[ $key ] = $value;
		}

		if ( isset( $options['conditional_logic'] ) ) {
			$prepared_options['conditional_logic'] = $options['conditional_logic'];
		}

		$prepared_options = wp_parse_args( $prepared_options, $options );
		return $prepared_options;
	}

	/**
	 * Fires the rule action.
	 *
	 * @param \Noptin_Abstract_Trigger $trigger  The trigger.
	 * @param \Noptin_Abstract_Action $action  The action.
	 * @param mixed $subject The subject.
	 * @param array $args The arguments.
	 * @return bool|\WP_Error
	 */
	public function maybe_run( $subject, $trigger, $action, $args ) {

		// Check if the rule is valid.
		if ( ! $trigger->is_rule_valid_for_args( $this, $args, $subject, $action ) ) {
			log_noptin_message( 'Automation rule trigger "' . $trigger->get_name() . '" not valid for args.' );

			return false;
		}

		// Run the automation rule.
		return \Hizzle\Noptin\Tasks\Main::run_automation_rule( $subject, $this, $args, $trigger );
	}

	/**
	 * Fetch rule data for JS.
	 */
	public function get_data( $context = 'view' ) {

		$prepared = parent::get_data( $context );
		$settings = array();
		$trigger  = $this->get_trigger();
		if ( ! empty( $trigger ) ) {
			$prepared['smartTags'] = $trigger->get_known_smart_tags_for_js();

			$trigger_settings = apply_filters( 'noptin_automation_rule_trigger_settings_' . $trigger->get_id(), $trigger->get_settings(), $this, $trigger );

			// Trigger settings.
			$settings['trigger'] = array(
				'label'    => __( 'Trigger', 'newsletter-optin-box' ),
				'prop'     => 'trigger_settings',
				'settings' => array_merge(
					array(
						'action_id_info' => array(
							'el'      => 'paragraph',
							'content' => $trigger->get_description(),
						),
					),
					$trigger_settings
				),
			);

			// Conditional logic.
			$settings['conditional_logic'] = array(
				'label'    => __( 'Conditional Logic', 'newsletter-optin-box' ),
				'prop'     => 'trigger_settings',
				'settings' => array(
					'conditional_logic' => array(
						'label'       => __( 'Conditional Logic', 'newsletter-optin-box' ),
						'el'          => 'conditional_logic',
						'comparisons' => noptin_get_conditional_logic_comparisons(),
						'fullWidth'   => true,
						'default'     => array(
							'enabled' => false,
							'action'  => 'allow',
							'type'    => 'all',
							'rules'   => array(
								array(
									'condition' => 'is',
									'type'      => 'date',
									'value'     => gmdate( 'Y-m-d' ),
								),
							),
						),
					),
				),
			);
		}

		$action = $this->get_action();
		if ( ! empty( $action ) ) {
			$action_settings = apply_filters( 'noptin_automation_rule_action_settings_' . $action->get_id(), $action->get_settings(), $this, $action );

			// Map fields.
			$map_fields = array();

			foreach ( $action_settings as $key => $data ) {

				if ( ! empty( $data['map_field'] ) ) {
					$map_fields[ $key ] = $data;
					unset( $action_settings[ $key ] );
				}
			}

			// Action settings.
			$settings['action'] = array(
				'label'    => __( 'Action', 'newsletter-optin-box' ),
				'prop'     => 'action_settings',
				'settings' => array_merge(
					array(
						'action_id_info' => array(
							'el'      => 'paragraph',
							'content' => $action->get_description(),
						),
					),
					$action_settings
				),
			);

			if ( ! empty( $map_fields ) ) {
				$settings['map_fields'] = array(
					'label'    => __( 'Map custom fields', 'newsletter-optin-box' ),
					'prop'     => 'action_settings',
					'settings' => array_merge(
						array(
							'map_field_tip' => array(
								'content' => __( 'Click on the merge tag button to insert a dynamic value.', 'newsletter-optin-box' ),
								'el'      => 'paragraph',
							),
						),
						$map_fields
					),
				);
			}
		}

		$settings = apply_filters( 'noptin_automation_rule_settings', $settings, $this, $trigger, $action );

		$prepared['settings'] = $settings;

		return $prepared;
	}
}
