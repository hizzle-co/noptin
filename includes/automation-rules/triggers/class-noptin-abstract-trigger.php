<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Base triggers class.
 *
 * @since       1.2.8
 */
abstract class Noptin_Abstract_Trigger {

	/**
	 * @var array
	 */
	protected $rules = null;

	/**
	 * Whether or not this trigger deals with a subscriber.
	 *
	 * @var bool
	 */
	public $is_subscriber_based = false;

	/**
	 * Whether or not this trigger deals with a user.
	 *
	 * @var bool
	 */
	public $is_user_based = false;

	/**
	 * Retrieve the trigger's unique id.
	 *
	 * Only alphanumerics, dashes and underscrores are allowed.
	 *
	 * @since 1.2.8
	 * @return string
	 */
	abstract public function get_id();

	/**
	 * Retrieve the trigger's name.
	 *
	 * @since 1.2.8
	 * @return string
	 */
	abstract public function get_name();

	/**
	 * Retrieve the trigger's description.
	 *
	 * @since 1.2.8
	 * @return string
	 */
	abstract public function get_description();

	/**
	 * Retrieve the trigger's image.
	 *
	 * @since 1.2.8
	 * @return string
	 */
	public function get_image() {
		return '';
	}

	/**
	 * Retrieve the trigger's keywords.
	 *
	 * @since 1.2.8
	 * @return array
	 */
	public function get_keywords() {
		return array();
	}

	/**
	 * Retrieve the trigger's rule description.
	 *
	 * @since 1.3.0
	 * @param Noptin_Automation_Rule $rule
	 * @return array
	 */
	public function get_rule_description( $rule ) {
		return $this->get_description();
	}

	/**
	 * Retrieve the trigger's settings.
	 *
	 * @since 1.2.8
	 * @return array
	 */
	public function get_settings() {
		return array();
	}

	/**
	 * Returns an array of known smart tags.
	 *
	 * @since 1.9.0
	 * @return array
	 */
	public function get_known_smart_tags() {
		/** @var WP_Locale $wp_locale */
		global $wp_locale;

		$smart_tags = array(

			'cookie'  => array(
				'description' => __( 'Data from a cookie.', 'newsletter-optin-box' ),
				'callback'    => 'Noptin_Dynamic_Content_Tags::get_cookie',
				'example'     => "cookie name='my_cookie' default='Default Value'",
			),

			'date'    => array(
				'description'       => __( 'The current date', 'newsletter-optin-box' ),
				'replacement'       => current_time( 'Y-m-d' ),
				'example'           => 'date',
				'conditional_logic' => 'date',
				'placeholder'       => current_time( 'Y-m-d' ),
			),

			'year'    => array(
				'description'       => __( 'The current year', 'newsletter-optin-box' ),
				'replacement'       => current_time( 'Y' ),
				'example'           => 'year',
				'conditional_logic' => 'number',
				'placeholder'       => current_time( 'Y' ),
			),

			'month'   => array(
				'description'       => __( 'The current month', 'newsletter-optin-box' ),
				'replacement'       => current_time( 'm' ),
				'example'           => 'month',
				'conditional_logic' => 'number',
				'placeholder'       => current_time( 'm' ),
				'options'           => $wp_locale->month,
			),

			'day'     => array(
				'description'       => __( 'The day of the month', 'newsletter-optin-box' ),
				'replacement'       => current_time( 'j' ),
				'example'           => 'day',
				'conditional_logic' => 'number',
				'placeholder'       => current_time( 'j' ),
				'options'           => array_combine( range( 1, 31 ), range( 1, 31 ) ),
			),

			'weekday' => array(
				'description'       => __( 'The day of the week', 'newsletter-optin-box' ),
				'replacement'       => (int) current_time( 'w' ),
				'placeholder'       => (int) current_time( 'w' ),
				'example'           => 'weekday',
				'conditional_logic' => 'number',
				'options'           => $wp_locale->weekday,
			),

			'time'    => array(
				// translators: %s is the current time.
				'description' => __( 'The current time', 'newsletter-optin-box' ),
				'replacement' => gmdate( 'H:i:s' ),
				'example'     => 'time',
			),

		);

		if ( ! $this->is_user_based ) {
			$smart_tags['user_logged_in'] = array(
				'description'       => __( 'Log-in status', 'newsletter-optin-box' ),
				'example'           => 'user_logged_in',
				'conditional_logic' => 'string',
				'callback'          => 'noptin_get_user_logged_in_status',
				'options'           => array(
					'yes' => __( 'Logged in', 'newsletter-optin-box' ),
					'no'  => __( 'Logged out', 'newsletter-optin-box' ),
				),
			);
		}

		if ( $this->is_subscriber_based ) {
			$smart_tags = array_replace( $smart_tags, get_noptin_subscriber_smart_tags() );
		}

		return apply_filters( 'noptin_automation_trigger_known_smart_tags', $smart_tags, $this );
	}

	/**
	 * Prepare smart tags.
	 *
	 * @param Noptin_Subscriber|WP_User|WC_Customer $subject
	 * @since 1.9.0
	 * @return array
	 */
	public function prepare_known_smart_tags( $subject ) {
		$smart_tags = array();

		if ( $this->is_subscriber_based && $subject instanceof Noptin_Subscriber ) {

			foreach ( get_noptin_subscriber_smart_tags() as $merge_tag => $data ) {

				if ( ! isset( $data['type'] ) ) {
					$smart_tags[ $merge_tag ] = $subject->get( $merge_tag );
				} else {
					$value = sanitize_noptin_custom_field_value( $subject->get( $merge_tag ), $data['type'], $subject );

					if ( is_array( $value ) ) {
						$value = format_noptin_custom_field_value( $subject->get( $merge_tag ), $data['type'], $subject );
					}

					if ( is_scalar( $value ) ) {
						$smart_tags[ $merge_tag ] = $value;
					}
				}
			}
		}

		return $smart_tags;
	}

	/**
	 * Returns all active rules attached to this trigger.
	 *
	 * @since 1.2.8
	 * @return array
	 */
	public function get_rules() {
		global $wpdb;

		if ( is_array( $this->rules ) ) {
			return $this->rules;
		}

		$this->rules = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}noptin_automation_rules WHERE `trigger_id`=%s AND `status`='1'",
				$this->get_id()
			)
		);

		return $this->rules;
	}

	/**
	 * Checks if there are rules for this trigger.
	 *
	 * @since 1.2.8
	 * @return array
	 */
	public function has_rules() {
		$rules = $this->get_rules;
		return ! empty( $rules );
	}

	/**
	 * Checks if conditional logic if met.
	 *
	 * @since 1.2.8
	 * @param Noptin_Automation_Rule $rule The rule to check for.
	 * @param mixed $args Extra args for the action.
	 * @param mixed $subject The subject.
	 * @param Noptin_Abstract_Action $action The action to run.
	 * @return bool
	 */
	public function is_rule_valid_for_args( $rule, $args, $subject, $action ) {

		// Abort if conditional logic is not set.
		if ( empty( $rule->conditional_logic['enabled'] ) || empty( $args['smart_tags'] ) ) {
			return true;
		}

		// Retrieve the conditional logic.
		$action      = $rule->conditional_logic['action']; // allow or prevent.
		$type        = $rule->conditional_logic['type']; // all or any.
		$rules_met   = 0;
		$rules_total = count( $rule->conditional_logic['rules'] );

		/** @var Noptin_Automation_Rules_Smart_Tags $smart_tags */
		$smart_tags = $args['smart_tags'];

		// Loop through each rule.
		foreach ( $rule->conditional_logic['rules'] as $rule ) {

			$current_value = $smart_tags->replace_in_text_field( '[[' . $rule['type'] . ']]' );
			$compare_value = $rule['value'];
			$comparison    = $rule['condition'];

			// If the rule is met.
			if ( noptin_is_conditional_logic_met( $current_value, $compare_value, $comparison ) ) {

				// Increment the number of rules met.
				$rules_met ++;

				// If we're using the "any" condition, we can stop here.
				if ( 'any' === $type ) {
					break;
				}
			} elseif ( 'all' === $type ) {

				// If we're using the "all" condition, we can stop here.
				break;
			}
		}

		// Check if the conditions are met.
		if ( 'all' === $type ) {
			$is_condition_met = $rules_met === $rules_total;
		} else {
			$is_condition_met = $rules_met > 0;
		}

		// Return the result.
		return 'allow' === $action ? $is_condition_met : ! $is_condition_met;
	}

	/**
	 * Triggers action callbacks.
	 *
	 * @since 1.2.8
	 * @param mixed $subject The subject.
	 * @param array $args Extra arguments passed to the action.
	 * @return void
	 */
	public function trigger( $subject, $args ) {

		if ( ! is_array( $args ) ) {
			$args = array();
		}

		$args['subject'] = $subject;

		$args = apply_filters( 'noptin_automation_trigger_args', $args, $this );

		$args['smart_tags'] = new Noptin_Automation_Rules_Smart_Tags( $this, $subject, $args );

		foreach ( $this->get_rules() as $rule ) {

			// Retrieve the action.
			$action = noptin()->automation_rules->get_action( $rule->action_id );
			if ( empty( $action ) ) {
				continue;
			}

			// Prepare the rule.
			$rule = noptin()->automation_rules->prepare_rule( $rule );

			// Ensure that the rule is valid for the provided args.
			if ( $this->is_rule_valid_for_args( $rule, $args, $subject, $action ) ) {
				$action->maybe_run( $subject, $rule, $args );
			}
		}

	}

}
