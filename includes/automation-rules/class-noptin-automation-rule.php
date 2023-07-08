<?php
/**
 * Noptin Automation Rule
 *
 */

defined( 'ABSPATH' ) || exit;
/**
 * This class represents a single Noptin automation rule.
 *
 * @link https://noptin.com/guide/automation-rules/
 * @see Noptin_Automation_Rules
 * @see Noptin_Automation_Rules_Table
 * @since       1.2.8
 */
class Noptin_Automation_Rule {

	/**
	 * The automation rule's ID
	 * @var int
	 * @since 1.2.8
	 */
	public $id = 0;

	/**
	 * Are we creating a new rule?
	 * @var bool
	 * @since 1.12.0
	 */
	public $is_creating = false;

	/**
	 * The automation rule's action id
	 * @var string
	 * @since 1.2.8
	 */
	public $action_id = '';

	/**
	 * The automation rule's trigger ID
	 * @var string
	 * @since 1.2.8
	 */
	public $trigger_id = '';

	/**
	 * The automation rule's action settings
	 * @var array
	 * @since 1.2.8
	 */
	public $action_settings = array();

	/**
	 * The automation rule's trigger settings
	 * @var array
	 * @since 1.2.8
	 */
	public $trigger_settings = array();

	/**
	 * The automation rule's conditional logic.
	 * @var array
	 * @since 1.8.0
	 */
	public $conditional_logic = array();

	/**
	 * The automation rule's status
	 * @var int
	 * @since 1.2.8
	 */
	public $status = 1;

	/**
	 * The automation rule's creation time
	 * @var string
	 * @since 1.2.8
	 */
	public $created_at = '0000-00-00 00:00:00';

	/**
	 * The automation rule's last update
	 * @var string
	 * @since 1.2.8
	 */
	public $updated_at = '0000-00-00 00:00:00';

	/**
	 * The automation rule's run times
	 * @var int
	 * @since 1.2.8
	 */
	public $times_run = 0;

	/**
	 * Constructor.
	 *
	 * @since 1.2.8
	 * @var int|stdClass|Noptin_Automation_Rule $rule
	 * @return string
	 */
	public function __construct( $rule ) {

		if ( empty( $rule ) ) {

			// Check if we are creating a new rule.
			if ( ! empty( $_GET['noptin-trigger'] ) && ! empty( $_GET['noptin-action'] ) ) {
				$this->is_creating = true;
				$this->trigger_id  = sanitize_text_field( $_GET['noptin-trigger'] );
				$this->action_id   = sanitize_text_field( $_GET['noptin-action'] );

				// Sanitize trigger and action settings.
				$this->trigger_settings  = $this->sanitize_trigger_settings( $this->trigger_settings );
				$this->action_settings   = $this->sanitize_action_settings( $this->action_settings );
				$this->conditional_logic = noptin_get_default_conditional_logic();
			}
			return;
		}

		if ( is_numeric( $rule ) ) {
			$this->init( self::get_rule( $rule ) );
			return;
		}

		$this->init( $rule );
	}

	/**
	 * Sets up object properties.
	 *
	 * @since  1.2.8
	 *
	 * @param object $data Rule DB row object.
	 */
	public function init( $data ) {

		if ( empty( $data ) ) {
			return;
		}

		foreach ( get_object_vars( $data ) as $key => $var ) {
			if ( property_exists( $this, $key ) ) {
				$this->$key = $this->make_bool( maybe_unserialize( $var ) );
			}
		}

		// Set conditional logic.
		$this->conditional_logic = noptin_get_default_conditional_logic();
		if ( isset( $this->trigger_settings['conditional_logic'] ) ) {
			$this->conditional_logic = wp_parse_args( (array) $this->trigger_settings['conditional_logic'], $this->conditional_logic );

			$this->conditional_logic['enabled'] = (bool) $this->conditional_logic['enabled'];

			unset( $this->trigger_settings['conditional_logic'] );
		}

		// Sanitize trigger and action settings.
		$this->trigger_settings = $this->sanitize_trigger_settings( $this->trigger_settings );
		$this->action_settings  = $this->sanitize_action_settings( $this->action_settings );

	}

	/**
	 * Sanitize the trigger settings.
	 *
	 * @param array $settings The trigger settings.
	 * @return array
	 */
	public function sanitize_trigger_settings( $settings ) {

		// Fetch the trigger.
		$trigger = noptin()->automation_rules->get_trigger( $this->trigger_id );

		if ( empty( $trigger ) ) {
			return $settings;
		}

		$trigger_settings = apply_filters( 'noptin_automation_rule_trigger_settings_' . $trigger->get_id(), $trigger->get_settings(), $this, $trigger );
		$trigger_settings = apply_filters( 'noptin_automation_rule_trigger_settings', $trigger_settings, $this, $trigger );
		return $this->prepare_settings( $settings, $trigger_settings );
	}

	/**
	 * Sanitize the action settings.
	 *
	 * @param array $settings The action settings.
	 * @return array
	 */
	public function sanitize_action_settings( $settings ) {

		// Fetch the trigger.
		$action = noptin()->automation_rules->get_action( $this->action_id );

		if ( empty( $action ) ) {
			return $settings;
		}

		$action_settings = apply_filters( 'noptin_automation_rule_action_settings_' . $action->get_id(), $action->get_settings(), $this, $action );
		$action_settings = apply_filters( 'noptin_automation_rule_action_settings', $action_settings, $this, $action );
		return $this->prepare_settings( $settings, $action_settings );
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
				$value = (array) $value;
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

		$prepared_options = wp_parse_args( $prepared_options, $options );
		return $prepared_options;
	}

	/**
	 * Converts bool strings to their bool counterparts.
	 *
	 * @since  1.3.0
	 *
	 * @param mixed $val The val to make boolean.
	 */
	public function make_bool( $val ) {

		if ( is_scalar( $val ) ) {

			// Make true.
			if ( 'true' === $val ) {
				$val = true;
			}

			// Make false.
			if ( 'false' === $val ) {
				$val = false;
			}

			return $val;

		}

		if ( is_array( $val ) ) {
			return map_deep( $val, array( $this, 'make_bool' ) );
		}

		return $val;

	}

	/**
	 * Retrieves a rule from the database or cache.
	 *
	 * @since  1.2.8
	 *
	 * @param int $id The rule id.
	 */
	public static function get_rule( $id ) {
		global $wpdb;

		$rule  = wp_cache_get( $id, 'noptin_automation_rules' );

		if ( ! empty( $rule ) ) {
			return (object) $rule;
		}

		$rule = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}noptin_automation_rules WHERE id = %d LIMIT 1",
				$id
			),
			ARRAY_A
		);

		if ( ! empty( $rule ) ) {
			wp_cache_set( $rule['id'], $rule, 'noptin_automation_rules', 10 );
		}

		return empty( $rule ) ? false : (object) $rule;

	}

	/**
	 * Determine whether the rule exists in the database.
	 *
	 * @since 1.2.8
	 *
	 * @return bool True if rule exists in the database, false if not.
	 */
	public function exists() {
		return ! empty( $this->id );
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
				'noptin_edit_automation_rule' => $this->id,
			),
			admin_url( 'admin.php' )
		);

		if ( 'email' === $this->action_id && ! empty( $this->action_settings['automated_email_id'] ) ) {
			$edit_url = add_query_arg(
				array(
					'page'        => 'noptin-email-campaigns',
					'section'     => 'automations',
					'sub_section' => 'edit_campaign',
					'campaign'    => $this->action_settings['automated_email_id'],
				),
				admin_url( 'admin.php' )
			);
		}

		return apply_filters( 'noptin_automation_rule_edit_url', $edit_url, $this );
	}

	/**
	 * Retrieves the delay (in seconds) before the rule is executed.
	 *
	 * @return int
	 * @since 1.11.5
	 */
	public function get_delay() {
		return apply_filters( 'noptin_automation_rule_delay', 0, $this );
	}

}
