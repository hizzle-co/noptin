<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' )  ) {
	die;
}

/**
 * Base actions class.
 *
 * @since       1.2.8
 */
abstract class Noptin_Abstract_Action {

    /**
     * @var array
     */
    protected $rules = null;
    
    /**
     * Retrieve the action's unique id.
     *
     * Only alphanumerics, dashes and underscrores are allowed.
     * Maximum 255 characters.
     * 
     * @since 1.2.8
     * @return string
     */
    public abstract function get_id();

    /**
     * Retrieve the action's name.
     *
     * @since 1.2.8
     * @return string
     */
    public abstract function get_name();

    /**
     * Retrieve the action's description.
     *
     * @since 1.2.8
     * @return string
     */
    public abstract function get_description();

    /**
     * Retrieve the actions's rule description.
     *
     * @since 1.3.0
     * @param Noptin_Automation_Rule $rule
     * @return array
     */
    public function get_rule_description( $rule ) {
        return $this->get_description();
    }

    /**
     * Retrieve the action's image.
     *
     * @since 1.2.8
     * @return string
     */
    public function get_image() {
        return '';
    }

    /**
     * Retrieve the action's keywords.
     *
     * @since 1.2.8
     * @return array
     */
    public function get_keywords() {
        return array();
    }

    /**
     * Retrieve the action's settings.
     *
     * @since 1.2.8
     * @return array
     */
    public abstract function get_settings();

    /**
     * Returns all active rules attached to this action.
     *
     * @since 1.2.8
     * @return array
     */
    public function get_rules() {
        global $wpdb;

        if ( is_array( $this->rules ) ) {
            return $this->rules;
        }

        $table =  noptin()->automation_rules->get_table();
        $this->rules = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE `action_id`=%s AND `status`='1'",
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
     * @param Noptin_Subscriber $subscriber The subscriber.
     * @param Noptin_Automation_Rule $rule The automation rule used to trigger the action.
     * @param array $args Extra arguments passed to the action.
     * @return bool
     */
    public function can_run( $subscriber, $rule, $args ) {
        return true;
    }

    /**
     * (Maybe) run the action.
     *
     * @since 1.3.0
     * @param Noptin_Subscriber $subscriber The subscriber.
     * @param Noptin_Automation_Rule $rule The automation rule used to trigger the action.
     * @param array $args Extra arguments passed to the action.
     */
    public function maybe_run( $subscriber, $rule, $args ) {

        // Ensure that we can run the action.
        if ( ! $this->can_run( $subscriber, $rule, $args ) ) {
            return;
        }

        // Run the action.
        $this->run( $subscriber, $rule, $args );

        // Update the run counts.
        $times_run = (int) $rule->times_run + 1;
        noptin()->automation_rules->update_rule( $rule, compact( 'times_run' ) );

    }
    
    /**
     * Runs the action.
     *
     * @since 1.2.8
     * @param Noptin_Subscriber $subscriber The subscriber.
     * @param Noptin_Automation_Rule $rule The automation rule used to trigger the action.
     * @param array $args Extra arguments passed to the action.
     * @return void
     */
    public abstract function run( $subscriber, $rule, $args );

}
