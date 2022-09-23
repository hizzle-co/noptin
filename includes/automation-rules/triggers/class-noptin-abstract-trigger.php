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
     * Retrieves conditional logic filters.
     *
     * @since 1.7.9
     * @return array
     */
    public function get_conditional_logic_filters() {
        $filters = array();

        if ( $this->is_subscriber_based ) {
            $filters = get_noptin_subscriber_filters();
        }

        return $filters;
    }

    /**
     * Prepares the conditional logic for display.
     *
     * @since 1.7.9
     * @param Noptin_Automation_Rule $rule The rule to check for.
     * @return string
     */
    public function prepare_conditional_logic( $rule ) {

        $filters = $this->get_conditional_logic_filters();
        if ( ! empty( $filters ) ) {
            return noptin_prepare_conditional_logic_for_display( $rule->conditional_logic, $filters );
        }

        return '';
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
     * Returns whether or not the action can run (dependancies are installed).
     *
     * @since 1.2.8
     * @return bool
     */
    public function can_run() {
        return true;
    }

    /**
     * Checks if this rule is valid for the above parameters.
     *
     * @since 1.2.8
     * @param Noptin_Automation_Rule $rule The rule to check for.
     * @param mixed $args Extra args for the action.
     * @param Noptin_Subscriber $subscriber The subscriber that this rule was triggered for.
     * @param Noptin_Abstract_Action $action The action to run.
     * @return bool
     */
    public function is_rule_valid_for_args( $rule, $args, $subscriber, $action ) {
        return $this->is_conditional_logic_met( $rule, $subscriber );
    }

    /**
     * Checks if conditional logic is met.
     *
     * @since 1.7.9
     * @param Noptin_Automation_Rule $rule The rule to check for.
     * @param Noptin_Subscriber $subscriber The subscriber that this rule was triggered for.
     * @return bool
     */
    public function is_conditional_logic_met( $rule, $subscriber ) {
        if ( $this->is_subscriber_based ) {
            return noptin_subscriber_meets_conditional_logic( $rule->conditional_logic, $subscriber );
        }

        return true;
    }

    /**
     * Triggers action callbacks.
     *
     * @since 1.2.8
     * @param int|object|array|Noptin_Subscriber $subscriber The subscriber.
     * @param array $args Extra arguments passed to the action.
     * @return void
     */
    public function trigger( $subscriber, $args ) {

        $subscriber = new Noptin_Subscriber( $subscriber );

        foreach ( $this->get_rules() as $rule ) {

            // Retrieve the action.
            $action = noptin()->automation_rules->get_action( $rule->action_id );
            if ( empty( $action ) ) {
                continue;
            }

            // Prepare the rule.
            $rule = noptin()->automation_rules->prepare_rule( $rule );

            // Ensure that the rule is valid for the provided args.
            if ( $this->is_rule_valid_for_args( $rule, $args, $subscriber, $action ) ) {
                $action->maybe_run( $subscriber, $rule, $args );
            }
        }

    }

}
